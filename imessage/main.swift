import SwiftUI
import AppKit
import SQLite3
import Network
import AuthenticationServices

// ============================================================================
//  SMS8 iMessage Bridge — native macOS app
//  Sends & receives iMessage/SMS through this Mac, linked to your SMS8 account.
//  Uses the SMS8 /services/v-ios/ device contract (isolated from the Android app).
// ============================================================================

let SMS8_BASE = "https://app.sms8.io"
let VIOS      = "\(SMS8_BASE)/services/v-ios"
let STATE_URL = FileManager.default.homeDirectoryForCurrentUser
    .appendingPathComponent(".sms8-imessage-agent.json")
// The real user home (inside App Sandbox, homeDirectoryForCurrentUser points
// at the app container, not at ~/Library/Messages).
let REAL_HOME: URL = {
    if let pw = getpwuid(getuid()), let dir = pw.pointee.pw_dir {
        return URL(fileURLWithPath: String(cString: dir), isDirectory: true)
    }
    return FileManager.default.homeDirectoryForCurrentUser
}()
let CHAT_DB   = REAL_HOME.appendingPathComponent("Library/Messages/chat.db").path

// MARK: - Messages folder access (App Store sandbox)
// The App Store build cannot read ~/Library/Messages on its own. The user
// grants it once through the standard macOS folder picker; we keep a
// read-only security-scoped bookmark and reopen it at every launch.
// Full Disk Access is still required on top of it (macOS privacy protection).
// The direct-download build is not sandboxed and skips all of this.
enum MessagesFolder {
    static let key = "messagesFolderBookmark"
    static var isSandboxed: Bool { ProcessInfo.processInfo.environment["APP_SANDBOX_CONTAINER_ID"] != nil }
    private static var active: URL?
    private static var folder: URL { REAL_HOME.appendingPathComponent("Library/Messages", isDirectory: true) }
    static var needsGrant: Bool { isSandboxed && active == nil }

    @discardableResult static func restore() -> Bool {
        guard isSandboxed else { return true }
        if active != nil { return true }
        guard let data = UserDefaults.standard.data(forKey: key) else { return false }
        var stale = false
        guard let url = try? URL(resolvingBookmarkData: data, options: [.withSecurityScope],
                                 relativeTo: nil, bookmarkDataIsStale: &stale),
              url.startAccessingSecurityScopedResource() else { return false }
        active = url
        if stale, let fresh = try? url.bookmarkData(options: [.withSecurityScope, .securityScopeAllowOnlyReadAccess],
                                                    includingResourceValuesForKeys: nil, relativeTo: nil) {
            UserDefaults.standard.set(fresh, forKey: key)
        }
        return true
    }

    /// Opens the folder picker already inside ~/Library/Messages; one click on Allow.
    static func request(completion: @escaping (Bool) -> Void) {
        let panel = NSOpenPanel()
        panel.message = "To bring replies into your SMS8 inbox and confirm delivery, SMS8 Gateway needs to read your Messages folder. It is already selected. Click Allow."
        panel.prompt = "Allow"
        panel.canChooseDirectories = true
        panel.canChooseFiles = false
        panel.allowsMultipleSelection = false
        panel.canCreateDirectories = false
        panel.showsHiddenFiles = true
        panel.directoryURL = folder
        NSApp.activate(ignoringOtherApps: true)
        panel.begin { resp in
            guard resp == .OK, let url = panel.url,
                  url.standardizedFileURL.resolvingSymlinksInPath().path == folder.standardizedFileURL.resolvingSymlinksInPath().path,
                  let data = try? url.bookmarkData(options: [.withSecurityScope, .securityScopeAllowOnlyReadAccess],
                                                   includingResourceValuesForKeys: nil, relativeTo: nil)
            else { completion(false); return }
            UserDefaults.standard.set(data, forKey: key)
            completion(restore())
        }
    }
}

// MARK: - In-app sign-in sheet
// Pages that need an SMS8 sign-in open in an in-app ASWebAuthenticationSession
// sheet, not the default browser (App Review guideline 4).
final class WebSignIn: NSObject, ASWebAuthenticationPresentationContextProviding {
    static let shared = WebSignIn()
    private var session: ASWebAuthenticationSession?
    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        NSApp.keyWindow ?? NSApp.windows.first ?? ASPresentationAnchor()
    }
    func open(_ path: String) {
        guard let url = URL(string: SMS8_BASE + path) else { return }
        let s = ASWebAuthenticationSession(url: url, callbackURLScheme: "sms8gateway") { _, _ in }
        s.presentationContextProvider = self
        session = s
        s.start()
    }
}

// MARK: - Persistent state
final class AppState: ObservableObject {
    @Published var paired = false
    @Published var email  = ""
    // Masked email for on-screen display so a shoulder-surfer or a screen
    // recording never reveals the full account address. "3anakebnt@gmail.com"
    // becomes "3a***@gmail.com".
    var maskedEmail: String {
        guard let at = email.firstIndex(of: "@") else {
            return email.isEmpty ? "your account" : email
        }
        let user = String(email[..<at])
        let domain = String(email[at...])
        let keep = user.count <= 2 ? user : String(user.prefix(2))
        return keep + "***" + domain
    }
    @Published var userId = ""
    @Published var receiveEnabled = true               // ON by default -> SMS8 is two-way, so receiving + Full Disk Access are expected
    @Published var sendMode: SendMode = .auto
    @Published var autoStart = true                    // start the bridge automatically on launch
    var apiKey = "", deviceId = "", androidId = "", lastRowId: Int64 = 0

    init() { load() }

    func load() {
        guard let d = try? Data(contentsOf: STATE_URL),
              let j = try? JSONSerialization.jsonObject(with: d) as? [String:Any] else {
            androidId = "mac-" + UUID().uuidString.prefix(16); save(); return
        }
        paired   = (j["paired"] as? Bool) ?? false
        email    = (j["email"] as? String) ?? ""
        userId   = (j["userId"] as? String) ?? ""
        apiKey   = (j["apiKey"] as? String) ?? ""
        deviceId = (j["deviceId"] as? String) ?? ""
        androidId = (j["androidId"] as? String) ?? ("mac-" + UUID().uuidString.prefix(16))
        lastRowId = (j["lastRowId"] as? Int64) ?? ((j["lastRowId"] as? NSNumber)?.int64Value ?? 0)
        receiveEnabled = (j["receiveEnabled"] as? Bool) ?? true
        sendMode = SendMode(rawValue: (j["sendMode"] as? String) ?? "") ?? .auto
        autoStart = (j["autoStart"] as? Bool) ?? true
    }
    func save() {
        let j: [String:Any] = ["paired":paired,"email":email,"userId":userId,
            "apiKey":apiKey,"deviceId":deviceId,"androidId":androidId,"lastRowId":lastRowId,
            "receiveEnabled":receiveEnabled,"sendMode":sendMode.rawValue,
            "autoStart":autoStart]
        if let d = try? JSONSerialization.data(withJSONObject: j) { try? d.write(to: STATE_URL) }
    }
}

// MARK: - SMS8 API
enum SMS8 {
    // Dedicated session with sane timeouts so a cold-start stall can't hang
    // the UI forever (URLSession.shared sometimes stalls on first use).
    static let session: URLSession = {
        let c = URLSessionConfiguration.ephemeral
        c.timeoutIntervalForRequest = 20
        c.timeoutIntervalForResource = 25
        c.waitsForConnectivity = false
        return URLSession(configuration: c)
    }()

    static func post(_ path: String, _ fields: [String:String]) -> [String:Any]? {
        // Try up to 3 times — the first request on a fresh process can stall
        // (URLSession cold start). Short backoff between tries so a transient
        // network blip doesn't surface as "unreachable".
        for attempt in 0..<3 {
            if let r = attemptPost(path, fields) { return r }
            if attempt < 2 { Thread.sleep(forTimeInterval: attempt == 0 ? 0.5 : 1.2) }
        }
        return nil
    }

    // Warm the URLSession so the first real request doesn't cold-start and
    // look "unreachable". Fire-and-forget on app launch.
    static func warmUp() {
        DispatchQueue.global().async {
            var req = URLRequest(url: URL(string: "\(VIOS)/heartbeat.php")!)
            req.httpMethod = "POST"; req.timeoutInterval = 8
            session.dataTask(with: req) { _,_,_ in }.resume()
        }
    }

    private static func attemptPost(_ path: String, _ fields: [String:String]) -> [String:Any]? {
        var req = URLRequest(url: URL(string: "\(VIOS)/\(path)")!)
        req.httpMethod = "POST"
        req.setValue("XMLHttpRequest", forHTTPHeaderField: "X-Requested-With")
        req.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        req.httpBody = fields.map { "\($0.key)=\($0.value.addingPercentEncoding(withAllowedCharacters: .urlQueryValueAllowed) ?? "")" }
            .joined(separator: "&").data(using: .utf8)
        req.timeoutInterval = 20
        let sem = DispatchSemaphore(value: 0)
        var out: [String:Any]? = nil
        var httpOk = false
        session.dataTask(with: req) { d,r,_ in
            if let h = r as? HTTPURLResponse { httpOk = (h.statusCode >= 200 && h.statusCode < 500) }
            if let d = d { out = try? JSONSerialization.jsonObject(with: d) as? [String:Any] }
            sem.signal()
        }.resume()
        // Hard ceiling so the caller never blocks forever.
        if sem.wait(timeout: .now() + 22) == .timedOut { return nil }
        return httpOk ? (out ?? [:]) : out
    }
}
extension CharacterSet {
    static let urlQueryValueAllowed: CharacterSet = {
        var c = CharacterSet.urlQueryAllowed; c.remove(charactersIn: "&=+"); return c
    }()
}

// MARK: - iMessage send (AppleScript) + receive (chat.db)
enum SendMode: String, CaseIterable { case auto = "Auto (iMessage, fall back to SMS)", imessage = "iMessage only", sms = "SMS only" }

enum iMsg {
    // Cache the resolved SMS/iMessage service ids so we send via a concrete
    // service object (reliable) instead of `whose service type = …` (flaky).
    private static var _svcCache: [String:String] = [:]
    private static let _svcLock = NSLock()
    /// Forget cached service ids (after the user signs in again in Messages).
    static func resetServiceCache() { _svcLock.lock(); _svcCache.removeAll(); _svcLock.unlock() }
    private static func serviceId(_ type: String) -> String? {
        _svcLock.lock(); let cached = _svcCache[type]; _svcLock.unlock()
        if let c = cached { return c }
        let script = """
        tell application "Messages"
          repeat with s in services
            try
              if (service type of s as text) is "\(type)" then return (id of s)
            end try
          end repeat
        end tell
        return ""
        """
        let out = runOsa(script).trimmingCharacters(in: .whitespacesAndNewlines)
        if !out.isEmpty && !out.hasPrefix("err") { _svcLock.lock(); _svcCache[type] = out; _svcLock.unlock(); return out }
        return nil
    }

    /// State of the Messages accounts: is there a usable iMessage account, and
    /// is there an SMS account (paired iPhone with Text Message Forwarding)?
    /// Only call once Automation is granted, or macOS shows its prompt.
    static func accountStatus() -> (IMessageAccountStatus, Bool) {
        let script = """
        tell application "Messages"
          set out to ""
          repeat with a in accounts
            try
              set out to out & (service type of a as text) & "|" & (enabled of a as text) & "|" & (connection status of a as text) & linefeed
            end try
          end repeat
          return out
        end tell
        """
        let out = runOsa(script)
        if out.hasPrefix("err") { return (.unknown, false) }
        var imsg: [(Bool, String)] = []
        var sms = false
        for line in out.split(whereSeparator: { $0 == "\n" || $0 == "\r" }) {
            let f = line.split(separator: "|").map { $0.trimmingCharacters(in: .whitespaces).lowercased() }
            guard f.count >= 3 else { continue }
            let enabled = (f[1] == "true")
            if f[0] == "imessage" { imsg.append((enabled, f[2])) }
            if f[0] == "sms" && enabled { sms = true }
        }
        if imsg.isEmpty { return (.notSignedIn, sms) }
        if imsg.contains(where: { $0.0 && $0.1 == "connected" }) { return (.ready, sms) }
        if imsg.contains(where: { $0.0 && $0.1 == "connecting" }) { return (.connecting, sms) }
        if imsg.contains(where: { $0.0 }) { return (.notSignedIn, sms) }   // enabled but disconnected
        return (.turnedOff, sms)
    }

    /// Turn raw AppleScript errors into something a user can act on.
    static func friendlyError(_ raw: String) -> String {
        if raw.contains("-1743") || raw.localizedCaseInsensitiveContains("not authorized to send apple events") {
            return "SMS8 Gateway is not allowed to control Messages. Turn it on in System Settings, Privacy & Security, Automation."
        }
        if raw.contains("-1744") { return "macOS is waiting for you to allow SMS8 Gateway to control Messages." }
        if raw.contains("-600") || raw.contains("-609") { return "Messages is not running and could not be opened." }
        return raw
    }

    // Runs AppleScript in-process (NSAppleScript). Works inside App Sandbox
    // with the Messages apple-events exception, unlike spawning osascript.
    private static func runOsa(_ script: String) -> String {
        var result = ""
        let work = {
            var err: NSDictionary?
            guard let s = NSAppleScript(source: script) else { result = "err:could not compile script"; return }
            let d = s.executeAndReturnError(&err)
            if let e = err {
                let num = (e[NSAppleScript.errorNumber] as? NSNumber).map { " (\($0))" } ?? ""
                result = "err:" + ((e[NSAppleScript.errorMessage] as? String) ?? "\(e)") + num
                return
            }
            result = (d.stringValue ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        }
        if Thread.isMainThread { work() } else { DispatchQueue.main.sync(execute: work) }
        return result
    }

    /// Send via a specific Messages service type ("iMessage" or "SMS").
    /// Uses the concrete service id so SMS is actually forced (not silently
    /// re-routed), then VERIFIES against chat.db that the row was really sent.
    private static func sendVia(_ service: String, to: String, body: String, verifySteps: Int = 8) -> (Bool, String) {
        let esc = body.replacingOccurrences(of: "\\", with: "\\\\").replacingOccurrences(of: "\"", with: "\\\"")
        guard let sid = serviceId(service) else {
            return (false, service == "iMessage"
                ? "Messages on this Mac is not signed in to iMessage (or SMS8 Gateway is not allowed to control Messages)."
                : "no SMS service on this Mac (pair an iPhone with Text Message Forwarding)")
        }
        let before = lastSentRowId(to: to)
        let script = """
        tell application "Messages"
          try
            set svc to service id "\(sid)"
            send "\(esc)" to buddy "\(to)" of svc
            return "ok"
          on error e
            return "err:" & e
          end try
        end tell
        """
        let out = runOsa(script)
        if out != "ok" { return (false, out.isEmpty ? "no response from Messages" : friendlyError(out)) }
        // Verify: a NEW outgoing row to this recipient must appear with error=0.
        // AppleScript returns "ok" even when the message never queues, so we
        // confirm it actually landed in the Messages DB before reporting Sent.
        //
        // For iMessage to a NON-Apple number, Messages first creates the row,
        // then a few seconds later flips `error` to a non-zero "not iMessage"
        // code. So we poll long enough (verifySteps * 0.5s) for that late error
        // to surface — otherwise auto-fallback to SMS would never trigger.
        if canReadDB() {
            for _ in 0..<verifySteps {
                usleep(500_000)
                if let (rid, err) = newestSent(to: to), rid > before {
                    return err == 0 ? (true, "\(service)")
                                    : (false, "\(service) error \(err) (not delivered)")
                }
            }
            // No new row appeared at all -> the send silently failed.
            return (false, "\(service): message was not created (send dropped)")
        }
        // No DB access (receive toggle off) -> trust the osascript ok.
        return (true, "\(service) (unverified)")
    }

    /// Mode-aware send on THIS Mac.
    ///
    /// iMessage -> send via the concrete iMessage service id (reliable to Apple
    ///             recipients; DB-verified).
    /// SMS      -> send via the concrete SMS service id. This routes through a
    ///             paired iPhone's SIM via Text Message Forwarding and reaches
    ///             Android (green bubble). Requires an iPhone paired to this Mac
    ///             with Text Message Forwarding ON; a Mac with no SMS service
    ///             (no paired iPhone) returns "no SMS service on this Mac".
    /// auto     -> try iMessage; if chat.db shows the iMessage row errored (or no
    ///             row was created), resend over SMS. Apple's `send` never throws
    ///             on iMessage failure, but the failure IS recorded in chat.db, so
    ///             the fallback is driven off the verified DB row — not off a
    ///             thrown error (which never comes).
    @discardableResult
    static func send(to: String, body: String, mode: SendMode = .auto) -> (Bool, String) {
        switch mode {
        case .imessage:
            let (ok, info) = sendVia("iMessage", to: to, body: body)
            return ok ? (true, "iMessage") : (false, info)

        case .sms:
            let (ok, info) = sendVia("SMS", to: to, body: body)
            return ok ? (true, "SMS") : (false, info)

        case .auto:
            // An email handle can only be iMessage (SMS has no email concept),
            // so there is nothing to fall back to — send iMessage and report.
            if to.contains("@") {
                let (ok, info) = sendVia("iMessage", to: to, body: body)
                return ok ? (true, "iMessage") : (false, info)
            }
            // 1) Try iMessage. Use a LONGER verification window than a plain
            //    iMessage-only send: when the recipient is NOT on iMessage,
            //    Messages creates the row first and flips `error` non-zero a few
            //    seconds later. We must wait for that so the fallback fires.
            //    (~7s)
            let (iok, iinfo) = sendVia("iMessage", to: to, body: body, verifySteps: 14)
            if iok { return (true, "iMessage") }
            // If we could NOT read chat.db, sendVia returned success-unverified
            // above (so iok would be true) — reaching here with FDA off only
            // happens on a hard AppleScript error, which SMS can also hit. Still
            // worth trying SMS.
            // 2) iMessage failed (recipient not on iMessage, or row errored).
            //    Auto-fall back to SMS via the paired iPhone — the ONE path that
            //    reaches Android. Requires an SMS service (paired iPhone + TMF).
            if serviceId("SMS") == nil {
                return (false, "iMessage failed and no SMS fallback available (pair an iPhone with Text Message Forwarding on this Mac to send SMS). \(iinfo)")
            }
            let (sok, sinfo) = sendVia("SMS", to: to, body: body)
            return sok ? (true, "SMS (iMessage unavailable)") : (false, "iMessage and SMS both failed. \(sinfo)")
        }
    }

    /// Send into an EXISTING group chat by its chat.db GUID (e.g.
    /// "iMessage;+;chat1234..."). Native AppleScript CANNOT create a group, but
    /// it CAN send into one that already exists and is loaded in Messages. The
    /// server supplies the chat GUID; we address it directly. No DB verification
    /// here because group iMessages never flip is_delivered on modern macOS, so a
    /// per-row error check would false-negative — we trust the osascript result.
    @discardableResult
    static func sendToGroup(chatGuid: String, body: String) -> (Bool, String) {
        let esc = body.replacingOccurrences(of: "\\", with: "\\\\").replacingOccurrences(of: "\"", with: "\\\"")
        let gesc = chatGuid.replacingOccurrences(of: "\\", with: "\\\\").replacingOccurrences(of: "\"", with: "\\\"")
        let script = """
        tell application "Messages"
          try
            set targetChat to a reference to chat id "\(gesc)"
            send "\(esc)" to targetChat
            return "ok"
          on error e
            return "err:" & e
          end try
        end tell
        """
        let out = runOsa(script)
        if out == "ok" { return (true, "group") }
        // The usual failure is a dormant chat Messages hasn't loaded ("Can't get
        // chat id"). Surface it plainly so the caller can tell the user to open
        // the conversation once.
        return (false, out.isEmpty ? "group send failed (no response)" : friendlyError(out))
    }

    // MARK: Accessibility GUI-send (SMS to phone numbers)
    /// True if this process holds Accessibility. Pass prompt:true ONLY from an
    /// explicit user action (the Settings "Allow" button) — never on every send,
    /// or macOS re-pops the prompt each time.
    static func hasAccessibility(prompt: Bool = false) -> Bool {
        let opts = [kAXTrustedCheckOptionPrompt.takeUnretainedValue() as String: prompt] as CFDictionary
        return AXIsProcessTrustedWithOptions(opts)
    }

    /// Send an SMS by driving the Messages compose window. Verifies delivery
    /// against chat.db when Full Disk Access is available.
    static func guiSendSMS(to: String, body: String) -> (Bool, String) {
        // Check WITHOUT prompting (prompt only fires from the Settings button).
        guard hasAccessibility(prompt: false) else {
            return (false, "Accessibility permission needed to send SMS. Allow “SMS8 iMessage” in System Settings → Privacy & Security → Accessibility.")
        }
        let before = lastSentRowId(to: to)
        // Escape for AppleScript string literals.
        func esc(_ s: String) -> String { s.replacingOccurrences(of: "\\", with: "\\\\").replacingOccurrences(of: "\"", with: "\\\"") }
        // NOTE: After locking the recipient (Return), focus stays on the "To:"
        // field, so we must TAB into the message body before typing — otherwise
        // the text lands in the recipient field (the "stuck in box" bug). We then
        // press Return to send. macOS may still label the field "iMessage"; SMS on
        // this engine is best-effort because Apple broke scripted SMS since macOS 11.
        let script = """
        tell application "Messages" to activate
        delay 0.6
        tell application "System Events"
          tell process "Messages"
            set frontmost to true
            delay 0.3
            keystroke "n" using command down
            delay 0.9
            keystroke "\(esc(to))"
            delay 1.0
            key code 36
            delay 0.8
            keystroke tab
            delay 0.5
            keystroke "\(esc(body))"
            delay 0.5
            key code 36
          end tell
        end tell
        return "ok"
        """
        let out = runOsa(script)
        if out != "ok" {
            if out.contains("not allowed to send keystrokes") || out.contains("1002") {
                return (false, "Accessibility permission is off. Allow “SMS8 iMessage” in System Settings → Privacy & Security → Accessibility.")
            }
            return (false, out.isEmpty ? "SMS send failed (no response)" : out)
        }
        // Verify via chat.db that a new outgoing row appeared with error=0.
        if canReadDB() {
            for _ in 0..<10 {              // up to ~5s
                usleep(500_000)
                if let (rid, err) = newestSent(to: to), rid > before {
                    return err == 0 ? (true, "SMS") : (false, "SMS error \(err) — not delivered (check iPhone Text Message Forwarding)")
                }
            }
            return (false, "SMS not confirmed delivered — the message may not have sent")
        }
        return (true, "SMS (unverified)")
    }

    /// Latest outgoing ROWID to this recipient right now (0 if none / no access).
    static func lastSentRowId(to: String) -> Int64 { newestSent(to: to)?.0 ?? 0 }

    /// Newest outgoing row to `to`: (rowid, error). nil if none or no DB access.
    static func newestSent(to: String) -> (Int64, Int)? {
        var db: OpaquePointer?
        guard sqlite3_open_v2(CHAT_DB, &db, SQLITE_OPEN_READONLY, nil) == SQLITE_OK else { return nil }
        defer { sqlite3_close(db) }

        // Email handles (iMessage to an Apple email) vs phone numbers need
        // different matching. For an email, match the handle case-insensitively.
        // For a phone, match on the last 8 digits so "+212627316921" matches
        // whatever formatting Messages normalised it to. The old code digit-
        // filtered emails too, turning "saad.lamnabhi01@gmail.com" into "01" —
        // which matched the wrong row (or none), so an email that actually
        // DELIVERED was reported as Failed.
        let sql: String
        let bindValue: String
        if to.contains("@") {
            sql = """
            SELECT m.ROWID, m.error
            FROM message m JOIN handle h ON m.handle_id=h.ROWID
            WHERE m.is_from_me=1 AND LOWER(h.id) = LOWER(?)
            ORDER BY m.ROWID DESC LIMIT 1;
            """
            bindValue = to
        } else {
            sql = """
            SELECT m.ROWID, m.error
            FROM message m JOIN handle h ON m.handle_id=h.ROWID
            WHERE m.is_from_me=1 AND replace(replace(replace(h.id,' ',''),'-',''),'(','') LIKE ?
            ORDER BY m.ROWID DESC LIMIT 1;
            """
            bindValue = "%" + String(to.filter { $0.isNumber }.suffix(8))
        }
        var st: OpaquePointer?
        guard sqlite3_prepare_v2(db, sql, -1, &st, nil) == SQLITE_OK else { return nil }
        sqlite3_bind_text(st, 1, bindValue, -1, unsafeBitCast(-1, to: sqlite3_destructor_type.self))
        defer { sqlite3_finalize(st) }
        if sqlite3_step(st) == SQLITE_ROW {
            return (sqlite3_column_int64(st, 0), Int(sqlite3_column_int(st, 1)))
        }
        return nil
    }

    static func canReadDB() -> Bool { fileReadable() }
    static func fileReadable() -> Bool {
        var db: OpaquePointer?
        let ok = sqlite3_open_v2(CHAT_DB, &db, SQLITE_OPEN_READONLY, nil) == SQLITE_OK
        if ok, let db = db {
            let probe = sqlite3_exec(db, "SELECT 1 FROM message LIMIT 1;", nil, nil, nil) == SQLITE_OK
            sqlite3_close(db); return probe
        }
        if let db = db { sqlite3_close(db) }; return false
    }

    /// Returns new inbound (rowid, handle, text, unixTs) with rowid > since.
    /// Modern macOS often stores the body in `attributedBody` (a binary blob)
    /// with `text` NULL, so we read both and decode the blob when needed —
    /// otherwise those messages would be silently skipped.
    static func newInbound(since: Int64) -> [(Int64,String,String,Int64)] {
        var db: OpaquePointer?
        guard sqlite3_open_v2(CHAT_DB, &db, SQLITE_OPEN_READONLY, nil) == SQLITE_OK else { return [] }
        defer { sqlite3_close(db) }
        let sql = """
        SELECT m.ROWID, COALESCE(h.id,''), m.text, m.attributedBody,
               (m.date/1000000000 + 978307200)
        FROM message m LEFT JOIN handle h ON m.handle_id=h.ROWID
        WHERE m.is_from_me=0 AND m.ROWID>?
        ORDER BY m.ROWID ASC LIMIT 50;
        """
        var st: OpaquePointer?
        guard sqlite3_prepare_v2(db, sql, -1, &st, nil) == SQLITE_OK else { return [] }
        sqlite3_bind_int64(st, 1, since)
        var rows: [(Int64,String,String,Int64)] = []
        while sqlite3_step(st) == SQLITE_ROW {
            let rid = sqlite3_column_int64(st, 0)
            let h = String(cString: sqlite3_column_text(st, 1))
            var t = ""
            if sqlite3_column_type(st, 2) != SQLITE_NULL { t = String(cString: sqlite3_column_text(st, 2)) }
            if t.isEmpty, sqlite3_column_type(st, 3) != SQLITE_NULL,
               let blob = sqlite3_column_blob(st, 3) {
                let n = Int(sqlite3_column_bytes(st, 3))
                let data = Data(bytes: blob, count: n)
                t = decodeAttributedBody(data)
            }
            let ts = sqlite3_column_int64(st, 4)
            if !h.isEmpty && !t.isEmpty { rows.append((rid, h, t, ts)) }
        }
        sqlite3_finalize(st); return rows
    }

    /// Extract readable text from a Messages `attributedBody` archive.
    /// Tries NSUnarchiver first; falls back to scanning for the UTF-8 string that
    /// follows the "NSString"/"+" marker in the typedstream.
    static func decodeAttributedBody(_ data: Data) -> String {
        if let s = try? NSAttributedString(data: data, options: [.documentType: NSAttributedString.DocumentType.plain], documentAttributes: nil) {
            if !s.string.isEmpty { return s.string }
        }
        if let a = try? NSKeyedUnarchiver.unarchivedObject(ofClass: NSAttributedString.self, from: data), !a.string.isEmpty {
            return a.string
        }
        // Heuristic fallback for the classic NSArchiver typedstream layout:
        // the plain text is a UTF-8 run that starts shortly after "NSString".
        if let marker = "NSString".data(using: .utf8),
           let r = data.range(of: marker) {
            let tail = data.subdata(in: r.upperBound..<data.count)
            // Skip a few framing bytes, then take the longest printable ASCII/UTF-8 run.
            let bytes = [UInt8](tail)
            var start = 0
            // find first byte that begins a plausible string (length-prefixed)
            while start < bytes.count && (bytes[start] < 0x20 || bytes[start] == 0x81 || bytes[start] == 0x84 || bytes[start] == 0x2b) { start += 1 }
            var out = [UInt8]()
            var i = start
            while i < bytes.count {
                let b = bytes[i]
                if b == 0x00 || b == 0x86 { break }      // typedstream terminators
                out.append(b); i += 1
            }
            if let s = String(bytes: out, encoding: .utf8)?.trimmingCharacters(in: .whitespacesAndNewlines), s.count >= 1 {
                return s
            }
        }
        return ""
    }
    static func currentMaxRowId() -> Int64 {
        var db: OpaquePointer?
        guard sqlite3_open_v2(CHAT_DB, &db, SQLITE_OPEN_READONLY, nil) == SQLITE_OK else { return 0 }
        defer { sqlite3_close(db) }
        var st: OpaquePointer?; var mx: Int64 = 0
        if sqlite3_prepare_v2(db, "SELECT COALESCE(MAX(ROWID),0) FROM message;", -1, &st, nil) == SQLITE_OK {
            if sqlite3_step(st) == SQLITE_ROW { mx = sqlite3_column_int64(st, 0) }
        }
        sqlite3_finalize(st); return mx
    }
}

// MARK: - Unified send dispatcher
enum Messaging {
    /// Per-message override: the server may tag a queued message with a channel
    /// ("imessage"/"sms") and/or a group chat GUID. When present, these win over
    /// the account-wide sendMode so GHL/API/Zapier can pick the channel per send.
    @discardableResult
    static func send(_ state: AppState, to: String, body: String,
                     channel: String? = nil, chatGuid: String? = nil) -> (Bool, String) {
        // Group send (existing chat) is iMessage-only.
        if let g = chatGuid, !g.isEmpty {
            return iMsg.sendToGroup(chatGuid: g, body: body)
        }
        // Resolve the effective mode: explicit per-message channel overrides the
        // account default.
        let mode: SendMode = {
            switch channel?.lowercased() {
            case "sms":      return .sms
            case "imessage": return .imessage
            default:         return state.sendMode
            }
        }()
        return iMsg.send(to: to, body: body, mode: mode)
    }
}

// MARK: - Health checks
// Detects, at launch and periodically, every condition that silently breaks the
// gateway: no network, SMS8 unreachable, Automation (control Messages) not
// allowed, Messages not signed in to iMessage, Full Disk Access missing.
// Everything here works inside the App Sandbox (MAS build) as well as in the
// direct-download build: AEDeterminePermissionToAutomateTarget is covered by the
// com.apple.MobileSMS apple-events exception, NWPathMonitor needs no extra
// entitlement, and System Settings / Messages are opened through NSWorkspace.

enum AutomationStatus { case granted, notDetermined, denied, messagesNotRunning, unknown }
enum IMessageAccountStatus { case ready, connecting, notSignedIn, turnedOff, unknown }

struct HealthIssue: Identifiable, Equatable {
    enum Kind: String {
        case offline             = "offline"
        case serverUnreachable   = "server_unreachable"
        case automationNeeded    = "automation_not_allowed_yet"
        case automationDenied    = "automation_denied"
        case notSignedIn         = "imessage_signed_out"
        case iMessageOff         = "imessage_off"
        case fullDiskMissing     = "full_disk_access_missing"
        case messagesFolder      = "messages_folder_access"
    }
    let kind: Kind
    var id: String { kind.rawValue }
    let title: String
    let summary: String
    let steps: [String]
    let blocksSending: Bool

    static func make(_ k: Kind) -> HealthIssue {
        switch k {
        case .offline:
            return HealthIssue(kind: k, title: "This Mac is offline",
                summary: "SMS8 Gateway can't reach the internet, so it can't pick up queued messages or send replies to your inbox. Nothing is lost: messages wait in SMS8 and are delivered when the connection is back.",
                steps: ["Check that Wi-Fi or Ethernet is connected.",
                        "Open Network settings if you need to reconnect.",
                        "Keep this app open. It reconnects on its own."],
                blocksSending: true)
        case .serverUnreachable:
            return HealthIssue(kind: k, title: "Can't reach SMS8",
                summary: "This Mac is online, but the SMS8 servers did not answer the last few requests. Queued messages stay in SMS8 until the connection works again.",
                steps: ["Check that a firewall, VPN or content filter is not blocking app.sms8.io.",
                        "Open the SMS8 dashboard in your browser to confirm it loads.",
                        "Click Retry now. The app also retries on its own."],
                blocksSending: false)
        case .automationNeeded:
            return HealthIssue(kind: k, title: "Allow SMS8 Gateway to control Messages",
                summary: "To send iMessage and SMS, SMS8 Gateway needs permission to control the Messages app. macOS asks you once.",
                steps: ["Click Allow access below.",
                        "In the macOS prompt, click OK to let SMS8 Gateway control Messages.",
                        "This window closes on its own once access is granted."],
                blocksSending: false)
        case .automationDenied:
            return HealthIssue(kind: k, title: "SMS8 Gateway is not allowed to control Messages",
                summary: "Permission to control Messages was turned off, so no message can be sent. Queued messages stay in SMS8 until you turn it back on.",
                steps: ["Click Open Automation Settings below.",
                        "Find SMS8 Gateway in the list and expand it.",
                        "Turn on the switch next to Messages.",
                        "Come back to this app. It checks again automatically."],
                blocksSending: true)
        case .notSignedIn:
            return HealthIssue(kind: k, title: "Messages is not signed in to iMessage",
                summary: "The Messages app on this Mac has no working iMessage account, so iMessage cannot be sent or received.",
                steps: ["Click Open Messages below.",
                        "In the menu bar, choose Messages, then Settings, then iMessage.",
                        "Sign in with your Apple ID (Apple Account), or check that the account is signed in and Enable this account is on.",
                        "Wait until Messages finishes activating, then come back to this app."],
                blocksSending: true)
        case .iMessageOff:
            return HealthIssue(kind: k, title: "iMessage is turned off in Messages",
                summary: "An iMessage account exists on this Mac but it is disabled, so iMessage cannot be sent or received.",
                steps: ["Click Open Messages below.",
                        "In the menu bar, choose Messages, then Settings, then iMessage.",
                        "Turn on Enable this account.",
                        "Come back to this app. It checks again automatically."],
                blocksSending: true)
        case .messagesFolder:
            return HealthIssue(kind: k, title: "Allow access to your Messages folder",
                summary: "macOS asks you once to let SMS8 Gateway read your Messages folder. Without it, replies can't reach your SMS8 inbox and delivery can't be confirmed. Sending still works.",
                steps: ["Click Choose Messages folder below.",
                        "The Messages folder is already open in the window. Click Allow.",
                        "If the app then asks for Full Disk Access, turn it on too."],
                blocksSending: false)
        case .fullDiskMissing:
            return HealthIssue(kind: k, title: "Full Disk Access is off",
                summary: "Without Full Disk Access, replies can't reach your SMS8 inbox, delivery can't be confirmed, and Auto mode can't fall back to SMS. Sending iMessage still works.",
                steps: ["Click Open Full Disk Access below.",
                        "Turn on SMS8 Gateway in the list. If it is not listed, click the + button and choose SMS8 Gateway from Applications.",
                        "Click Relaunch app. macOS only applies this permission after the app restarts."],
                blocksSending: false)
        }
    }
}

final class HealthMonitor: ObservableObject {
    @Published private(set) var automation: AutomationStatus = .unknown
    @Published private(set) var account: IMessageAccountStatus = .unknown
    @Published private(set) var hasSMSAccount = false
    @Published private(set) var fullDisk = iMsg.fileReadable()
    @Published private(set) var online = true
    @Published private(set) var serverReachable = true
    @Published private(set) var issues: [HealthIssue] = []

    let state: AppState
    private let queue = DispatchQueue(label: "io.sms8.gateway.health")
    private let pathQueue = DispatchQueue(label: "io.sms8.gateway.path")
    private let pathMonitor = NWPathMonitor()
    private var timer: Timer?
    private var checking = false
    private var started = false
    private var launchedMessages = false
    private var serverFailures = 0

    init(_ s: AppState) { state = s }

    /// Start live monitoring: network path, a 15 s re-check, and a re-check every
    /// time the app comes to the front (e.g. back from System Settings).
    func startMonitoring() {
        guard !started else { return }
        started = true
        MessagesFolder.restore()   // reopen the App Store folder grant, if any
        pathMonitor.pathUpdateHandler = { [weak self] p in
            DispatchQueue.main.async {
                guard let self = self else { return }
                let on = (p.status == .satisfied)
                if on != self.online { self.online = on; if on { self.serverFailures = 0; self.serverReachable = true }; self.rebuild() }
            }
        }
        pathMonitor.start(queue: pathQueue)
        timer = Timer.scheduledTimer(withTimeInterval: 15, repeats: true) { [weak self] _ in self?.refresh() }
        NotificationCenter.default.addObserver(forName: NSApplication.didBecomeActiveNotification,
                                               object: nil, queue: .main) { [weak self] _ in self?.refresh() }
        refresh()
    }

    /// Cheap check (Full Disk Access only) for the UI's fast poll.
    func quickCheck() {
        let fd = iMsg.fileReadable()
        if fd != fullDisk { fullDisk = fd; rebuild() }
    }

    /// Full re-check off the main thread.
    func refresh() {
        guard !checking else { return }
        checking = true
        let paired = state.paired
        queue.async {
            let fd = iMsg.fileReadable()
            var auto = Self.automationStatus(ask: false)
            // Messages has to be running for the permission check (and for
            // sending). Once paired, open it in the background so a closed
            // Messages app is not mistaken for a broken gateway.
            if auto == .messagesNotRunning && paired && !self.launchedMessages {
                self.launchedMessages = true
                Self.launchMessages(activate: false)
                for _ in 0..<10 {
                    Thread.sleep(forTimeInterval: 0.5)
                    auto = Self.automationStatus(ask: false)
                    if auto != .messagesNotRunning { break }
                }
            }
            var acct: IMessageAccountStatus = .unknown
            var sms = false
            if auto == .granted { (acct, sms) = iMsg.accountStatus() }
            DispatchQueue.main.async {
                if acct == .ready && self.account != .ready { iMsg.resetServiceCache() }
                self.fullDisk = fd; self.automation = auto
                self.account = acct; self.hasSMSAccount = sms
                self.checking = false
                self.rebuild()
            }
        }
    }

    /// Shows the macOS "control Messages" prompt (only from a user click).
    func requestAutomation() {
        queue.async {
            if Self.automationStatus(ask: false) == .messagesNotRunning {
                Self.launchMessages(activate: false)
                for _ in 0..<10 {
                    Thread.sleep(forTimeInterval: 0.5)
                    if Self.automationStatus(ask: false) != .messagesNotRunning { break }
                }
            }
            _ = Self.automationStatus(ask: true)   // blocks until the user answers
            DispatchQueue.main.async { self.refresh() }
        }
    }

    /// Fed by the bridge after each tick: did any SMS8 request get an answer?
    func recordServer(reached: Bool) {
        if reached { serverFailures = 0 } else { serverFailures += 1 }
        let ok = serverFailures < 2
        if ok != serverReachable { serverReachable = ok; rebuild() }
    }
    func resetServer() { serverFailures = 0; if !serverReachable { serverReachable = true; rebuild() } }

    private func rebuild() {
        var out: [HealthIssue] = []
        if !online { out.append(.make(.offline)) }
        else if !serverReachable && state.paired { out.append(.make(.serverUnreachable)) }
        switch automation {
        case .denied:        out.append(.make(.automationDenied))
        case .notDetermined: out.append(.make(.automationNeeded))
        default: break
        }
        // A disconnected iMessage account while offline is just the network.
        if automation == .granted && online {
            switch account {
            case .notSignedIn: out.append(.make(.notSignedIn))
            case .turnedOff:   out.append(.make(.iMessageOff))
            default: break
            }
        }
        if state.receiveEnabled || state.sendMode == .auto {
            if MessagesFolder.needsGrant { out.append(.make(.messagesFolder)) }
            else if !fullDisk { out.append(.make(.fullDiskMissing)) }
        }
        if out != issues { issues = out }
    }

    /// Why sending is paused right now, or nil if messages can be sent. While
    /// paused the bridge does not pull messages, so they stay queued in SMS8
    /// instead of being marked Failed.
    var sendingBlockedReason: String? {
        if !online { return "this Mac is offline" }
        if automation == .denied { return "SMS8 Gateway is not allowed to control Messages" }
        if account == .notSignedIn || account == .turnedOff {
            let smsUsable = hasSMSAccount && state.sendMode != .imessage
            if !smsUsable { return account == .turnedOff ? "iMessage is turned off in Messages" : "Messages is not signed in to iMessage" }
        }
        return nil
    }

    /// Compact status string sent with the existing heartbeat call.
    var healthCode: String { issues.isEmpty ? "ok" : issues.map { $0.id }.joined(separator: ",") }

    static func automationStatus(ask: Bool) -> AutomationStatus {
        let target = NSAppleEventDescriptor(bundleIdentifier: "com.apple.MobileSMS")
        guard let desc = target.aeDesc else { return .unknown }
        let st = AEDeterminePermissionToAutomateTarget(desc, AEEventClass(typeWildCard), AEEventID(typeWildCard), ask)
        switch st {
        case noErr:  return .granted
        case -1744:  return .notDetermined        // errAEEventWouldRequireUserConsent
        case -1743:  return .denied               // errAEEventNotPermitted
        case -600:   return .messagesNotRunning   // procNotFound
        default:     return .unknown
        }
    }

    static func launchMessages(activate: Bool) {
        guard let url = NSWorkspace.shared.urlForApplication(withBundleIdentifier: "com.apple.MobileSMS") else { return }
        let cfg = NSWorkspace.OpenConfiguration()
        cfg.activates = activate
        if !activate { cfg.hides = true }
        DispatchQueue.main.async { NSWorkspace.shared.openApplication(at: url, configuration: cfg) { _, _ in } }
    }

    static func openSettings(_ pane: String) {
        if let u = URL(string: "x-apple.systempreferences:\(pane)") { NSWorkspace.shared.open(u) }
    }
}

// MARK: - Bridge engine (the send/receive loop)
final class Bridge: ObservableObject {
    let state: AppState
    let health: HealthMonitor
    @Published var running = false
    @Published var log: [String] = []
    @Published private(set) var pausedReason: String? = nil
    private var timer: Timer?
    private var warnedNoFDA = false
    private var tickInFlight = false
    init(_ s: AppState, _ h: HealthMonitor) { state = s; health = h }

    func logLine(_ s: String) {
        DispatchQueue.main.async {
            self.log.insert("\(Self.ts())  \(s)", at: 0)
            if self.log.count > 200 { self.log.removeLast() }
        }
    }
    static func ts() -> String { let f = DateFormatter(); f.dateFormat="HH:mm:ss"; return f.string(from: Date()) }

    func start() {
        guard state.paired, !running else { return }
        running = true
        if state.lastRowId == 0 { state.lastRowId = iMsg.currentMaxRowId(); state.save() }
        logLine("Bridge started.")
        tick() // immediate
        timer = Timer.scheduledTimer(withTimeInterval: 8, repeats: true) { [weak self] _ in self?.tick() }
    }
    func stop() { running = false; timer?.invalidate(); timer = nil; pausedReason = nil; logLine("Bridge stopped.") }

    // True if a server reply means this device no longer exists on the account
    // (deleted from the Devices page, account reset, etc.). We detect it so the
    // app can stop silently failing and send the user back to pairing.
    private func isDeviceGone(_ r: [String:Any]?) -> Bool {
        guard let r = r, (r["success"] as? Bool) == false else { return false }
        let msg = ((r["error"] as? [String:Any])?["message"] as? String ?? "").lowercased()
        return msg.contains("device not found") || msg.contains("doesn't exist")
            || msg.contains("does not exist") || msg.contains("no device")
    }

    /// Runs on the main thread (timer / UI). One tick at a time: a slow network
    /// used to let ticks pile up and pull the same queue concurrently.
    func tick() {
        guard running, !tickInFlight else { return }
        tickInFlight = true
        let blocked = health.sendingBlockedReason
        if blocked != pausedReason {
            if let b = blocked { logLine("Sending paused: \(b). Queued messages stay in SMS8 until this is fixed.") }
            else if pausedReason != nil { logLine("Sending resumed.") }
            pausedReason = blocked
        }
        let offline = !health.online
        let healthCode = health.healthCode
        DispatchQueue.global().async {
            var reached = false
            var gone = false
            defer {
                DispatchQueue.main.async {
                    self.tickInFlight = false
                    if !offline { self.health.recordServer(reached: reached) }
                    if gone { self.handleDeviceGone() }
                }
            }
            // Offline: every request would stall ~75 s on retries. Skip the tick.
            if offline { return }
            // SEND (skipped while sending is blocked, so nothing is marked Failed)
            if blocked == nil,
               let r = SMS8.post("get-messages.php", ["androidId":self.state.androidId,"userId":self.state.userId,"limit":"10"]) {
                reached = true
                if self.isDeviceGone(r) { gone = true; return }
                let msgs = ((r["data"] as? [String:Any])?["messages"] as? [[String:Any]]) ?? (r["messages"] as? [[String:Any]]) ?? []
                for m in msgs {
                    let mid = "\(m["ID"] ?? m["id"] ?? "")"
                    let to = "\(m["number"] ?? "")", body = "\(m["message"] ?? "")"
                    // Optional per-message routing from the server. `channel`
                    // forces imessage/sms; `chatGuid` targets an existing group.
                    // Absent on legacy rows -> fall back to the account default.
                    let channel = (m["channel"] as? String) ?? (m["sendAs"] as? String)
                    let guidRaw = (m["chatGuid"] as? String) ?? (m["groupGuid"] as? String) ?? ""
                    let chatGuid = guidRaw.isEmpty ? nil : guidRaw
                    let (ok, info) = Messaging.send(self.state, to: to, body: body,
                                                    channel: channel, chatGuid: chatGuid)
                    let rep = SMS8.post("report-status.php", ["androidId":self.state.androidId,"userId":self.state.userId,"messageId":mid,"status": ok ? "Sent":"Failed"])
                    self.logLine(ok ? "→ sent to \(to) via \(info)" : "→ send FAILED \(to): \(info)")
                    if rep == nil { self.logLine("Warning: could not report the status of message \(mid) to SMS8 (network).") }
                }
                // A permission/sign-in failure during sending: re-check right away.
                if !msgs.isEmpty { DispatchQueue.main.async { self.health.refresh() } }
            }
            // RECEIVE — only when the user enabled it.
            if self.state.receiveEnabled { self.receiveTick() }
            // Heartbeat (existing call). Also carries this Mac's health so the
            // dashboard can show why a device is not delivering.
            let hb = SMS8.post("heartbeat.php", ["androidId":self.state.androidId,"userId":self.state.userId,
                                                 "healthStatus": healthCode])
            if hb != nil { reached = true }
            if self.isDeviceGone(hb) { gone = true }
        }
    }

    // The paired device was removed server-side. Stop the bridge and reset to
    // the pairing screen so the user can connect again, instead of looping on
    // failed polls (which looked like "unreachable").
    private func handleDeviceGone() {
        DispatchQueue.main.async {
            guard self.state.paired else { return }
            self.logLine("Warning: this Mac is no longer paired to your account. Please pair again.")
            self.stop()
            self.state.paired = false
            self.state.deviceId = ""; self.state.apiKey = ""; self.state.userId = ""; self.state.email = ""
            self.state.lastRowId = 0
            // Fresh androidId so re-pairing creates a clean device.
            self.state.androidId = "mac-" + UUID().uuidString.prefix(16)
            self.state.save()
        }
    }

    private func receiveTick() {
        // Send ISO-8601 WITH an explicit UTC-offset (e.g. 2026-10-30T18:24:37+0000)
        // so the server never has to guess the timezone. The old format was a
        // naive "yyyy-MM-dd HH:mm:ss" with no offset, which the server parsed in
        // its own TIMEZONE (Africa/Casablanca) even though the string was the
        // Mac's local wall-clock — producing the ±1h skew on received messages.
        let fmt = DateFormatter()
        fmt.locale = Locale(identifier: "en_US_POSIX")
        fmt.dateFormat = "yyyy-MM-dd'T'HH:mm:ssZ"   // Z = numeric offset like +0000
        // Returns false when SMS8 did not answer, so the caller keeps the row
        // and retries next tick (previously the cursor advanced anyway and the
        // reply was lost for good whenever the network blipped).
        func forward(number: String, text: String, sentTs: TimeInterval) -> Bool {
            let payload = [["number":number,"message":text,
                "sentDate":fmt.string(from: Date(timeIntervalSince1970: sentTs)),
                "receivedDate":fmt.string(from: Date())]]
            guard let data = try? JSONSerialization.data(withJSONObject: payload),
                  let js = String(data: data, encoding: .utf8) else { return true }  // unencodable: skip it
            guard SMS8.post("receive-message.php", ["androidId":state.androidId,"userId":state.userId,"messages":js]) != nil else {
                logLine("Warning: could not deliver a reply from \(number) to SMS8. Will retry.")
                return false
            }
            logLine("← recv from \(number): \(text.prefix(40))")
            return true
        }
        // Reads chat.db (needs Full Disk Access).
        guard iMsg.canReadDB() else {
            // Surface the real blocker ONCE per run instead of silently reading nothing.
            if !warnedNoFDA {
                warnedNoFDA = true
                logLine("Warning: can't read incoming messages because Full Disk Access is off. Grant it in Settings, then relaunch the app.")
            }
            return
        }
        warnedNoFDA = false
        // First successful read: anchor at current max so we don't replay history.
        if state.lastRowId == 0 { state.lastRowId = iMsg.currentMaxRowId(); state.save() }
        let rows = iMsg.newInbound(since: state.lastRowId)
        var advanced = false
        for (rid, handle, text, ts) in rows where !handle.isEmpty {
            if !forward(number: handle, text: text, sentTs: TimeInterval(ts)) { break }
            state.lastRowId = rid; advanced = true
        }
        if advanced { state.save() }
    }
}

// MARK: - UI (tabbed, theme-aware)
// SMS8 brand (sms8.io look): ink black in light mode, slate gray in dark mode
// (prominent buttons always draw white labels, so dark mode cannot use white).
let BRAND = Color(nsColor: NSColor(name: nil) { a in
    a.bestMatch(from: [.darkAqua, .aqua]) == .darkAqua
        ? NSColor(srgbRed: 0.357, green: 0.396, blue: 0.412, alpha: 1)
        : NSColor(srgbRed: 0.047, green: 0.063, blue: 0.071, alpha: 1)
})
let BRAND_LIGHT = Color(red: 0.545, green: 0.580, blue: 0.561)

enum Tab: String, CaseIterable, Identifiable {
    case connection = "Connection", settings = "Settings", activity = "Activity"
    var id: String { rawValue }
    var icon: String {
        switch self { case .connection: return "link"; case .settings: return "gearshape"; case .activity: return "list.bullet.rectangle" }
    }
}

// A small "?" icon that shows an explanation in a popover on click.
// Use next to anything that might puzzle the user.
struct HelpTip: View {
    let text: String
    @State private var show = false
    var body: some View {
        Button { show.toggle() } label: {
            Image(systemName: "questionmark.circle")
                .font(.system(size: 12)).foregroundColor(.secondary)
        }
        .buttonStyle(.plain)
        .help(text)                               // native hover tooltip too
        .popover(isPresented: $show, arrowEdge: .bottom) {
            Text(text).font(.system(size: 12)).foregroundColor(.primary)
                .fixedSize(horizontal: false, vertical: true)
                .frame(width: 240).padding(12)
        }
    }
}

struct RootView: View {
    @StateObject var state: AppState
    @StateObject var bridge: Bridge
    @StateObject var health: HealthMonitor
    @State private var tab: Tab = .connection
    var fullDisk: Bool { health.fullDisk }
    @State private var presentedIssue: HealthIssue? = nil   // the issue dialog on screen
    @State private var dismissedIssues: Set<String> = []     // "Later" for this session
    @State private var code = ""
    @State private var pairing = false
    @State private var pairMsg = ""
    @State private var testTo = ""
    @State private var testResult = ""
    @State private var sending = false
    @State private var wizardDismissed = false     // user chose to view the full app
    @State private var testPassed = false           // first successful test send
    @State private var pairPhase = "Connecting…"     // live label during pairing
    @State private var wizPageState = 0               // current wizard slide (0..2)
    @State private var pairSucceeded = false          // show success checkmark after pairing

    init() {
        let s = AppState()
        let h = HealthMonitor(s)
        _state = StateObject(wrappedValue: s)
        _health = StateObject(wrappedValue: h)
        _bridge = StateObject(wrappedValue: Bridge(s, h))
    }

    // Show the guided single-page wizard until the bridge is actually running.
    // After that, switch to the compact tabbed layout (for disconnect / settings
    // / history). This way the user isn't shown everything at once up front.
    var inWizard: Bool { !bridge.running && !wizardDismissed }

    var body: some View {
        Group {
            if inWizard { wizardView }
            else { tabbedView }
        }
        .frame(minWidth: 760, idealWidth: 780, minHeight: 540, idealHeight: 560)
        .onReceive(Timer.publish(every: 3, on: .main, in: .common).autoconnect()) { _ in
            // Keep permission state live so the checklist reflects reality as the
            // user flips toggles in System Settings (no need to reopen the app).
            health.quickCheck()
            // Self-heal: if things just became runnable and auto-start is on, start.
            if state.paired && state.autoStart && canRun && !bridge.running { bridge.start() }
        }
        .onAppear {
            SMS8.warmUp()   // prime the network so the first request isn't a cold-start "unreachable"
            // Detect Messages sign-in, Automation, Full Disk Access and network
            // now, then every 15 s and whenever the app comes to the front.
            health.startMonitoring()
            // Auto-start the bridge on launch when paired, set up, and the user
            // hasn't explicitly turned it off. Keeps it running by default.
            if state.paired && state.autoStart && canRun && !bridge.running {
                bridge.start()
            }
            // Open the wizard on the right slide for returning users.
            if state.paired && wizPageState < 2 { wizPageState = 2 }
        }
        // Auto-advance the slide when a step completes. On pairing, let the
        // success checkmark show for ~2s before sliding to the next step.
        .onChange(of: state.paired) { _, paired in
            if paired {
                DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) {
                    withAnimation { wizPageState = 2 }
                }
            }
        }
        .onChange(of: testPassed) { _, passed in
            if passed { withAnimation { wizPageState = 2 } }
        }
        // Problems found by the health monitor open a dialog with steps and a
        // button to the right System Settings pane. Only once setup is done
        // (during the wizard the Permissions step shows the same status inline),
        // and once per problem per session after the user clicks Later.
        .onChange(of: health.issues) { _, issues in presentNextIssue(issues) }
        .onChange(of: state.paired) { _, _ in presentNextIssue(health.issues) }
        .sheet(item: $presentedIssue) { issue in
            IssueSheet(issue: issue, health: health,
                       onAction: { performPrimary(issue.kind) },
                       onSecondary: { performSecondary(issue.kind) },
                       onLater: { dismissedIssues.insert(issue.id); presentedIssue = nil })
        }
    }

    func presentNextIssue(_ issues: [HealthIssue]) {
        let ids = Set(issues.map { $0.id })
        dismissedIssues = dismissedIssues.intersection(ids)   // a problem that comes back is shown again
        if let cur = presentedIssue {
            if !ids.contains(cur.id) { presentedIssue = nil } else { return }
        }
        guard state.paired else { return }
        // Wait for the sheet to finish closing before opening the next one.
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.4) {
            guard presentedIssue == nil else { return }
            presentedIssue = health.issues.first { !dismissedIssues.contains($0.id) }
        }
    }

    func performPrimary(_ k: HealthIssue.Kind) {
        switch k {
        case .offline:           HealthMonitor.openSettings("com.apple.preference.network")
        case .serverUnreachable: health.resetServer(); bridge.tick()
        case .automationNeeded:  health.requestAutomation()
        case .automationDenied:  HealthMonitor.openSettings("com.apple.preference.security?Privacy_Automation")
        case .notSignedIn, .iMessageOff: HealthMonitor.launchMessages(activate: true)
        case .fullDiskMissing:   openFullDisk()
        case .messagesFolder:    MessagesFolder.request { _ in health.refresh() }
        }
    }
    func performSecondary(_ k: HealthIssue.Kind) {
        switch k {
        case .serverUnreachable: WebSignIn.shared.open("/")
        case .fullDiskMissing:   relaunchApp()
        default: health.refresh()
        }
    }

    // Persistent list of current problems (stays after the dialog is closed).
    @ViewBuilder var issueBanner: some View {
        if !health.issues.isEmpty {
            VStack(alignment: .leading, spacing: 8) {
                ForEach(health.issues) { issue in
                    HStack(spacing: 10) {
                        Image(systemName: "exclamationmark.triangle.fill")
                            .foregroundColor(issue.blocksSending ? .red : .orange).font(.system(size: 14))
                        VStack(alignment: .leading, spacing: 1) {
                            Text(issue.title).font(.system(size: 12.5, weight: .semibold))
                            Text(issue.blocksSending ? "Sending is paused until this is fixed." : "Some features are limited until this is fixed.")
                                .font(.system(size: 11)).foregroundColor(.secondary)
                        }
                        Spacer()
                        Button("How to fix") { presentedIssue = issue }
                            .buttonStyle(.borderedProminent).tint(BRAND).controlSize(.small)
                    }
                }
            }
            .padding(12)
            .background(Color.orange.opacity(0.08))
            .overlay(RoundedRectangle(cornerRadius: 10).stroke(Color.orange.opacity(0.3)))
            .cornerRadius(10)
        }
    }

    // ── Tabbed layout (shown after setup / when running) ──
    var tabbedView: some View {
        HStack(spacing: 0) {
            sidebar
            Divider()
            VStack(spacing: 0) {
                topBar
                Divider()
                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                    issueBanner
                    Group {
                        switch tab {
                        case .connection: connectionTab
                        case .settings:   settingsTab
                        case .activity:   activityTab
                        }
                    }
                    }.padding(22).frame(maxWidth: .infinity, alignment: .leading)
                }
            }
        }
    }

    // ── Guided wizard — horizontal slides, one step at a time ──
    // Steps: 1 Permissions → 2 Connect → 3 Test & start. Advances automatically
    // when a step completes, or with the Next button. Back returns a step.
    var wizardView: some View {
        VStack(spacing: 0) {
            // Header + progress dots
            HStack(spacing: 12) {
                appIcon.frame(width: 36, height: 36)
                VStack(alignment: .leading, spacing: 1) {
                    Text("SMS8 Gateway").font(.system(size: 16, weight: .bold))
                    Text(wizStepTitle).font(.system(size: 12)).foregroundColor(.secondary)
                }
                Spacer()
                if bridge.running || state.paired {
                    Button("Skip to app") { wizardDismissed = true }
                        .buttonStyle(.plain).font(.system(size: 12)).foregroundColor(.secondary)
                }
            }
            .padding(.horizontal, 26).padding(.top, 22).padding(.bottom, 14)

            progressDots.padding(.bottom, 10)
            Divider()

            // The sliding step content.
            ZStack {
                switch wizPage {
                case 0: wizCard { AnyView(permissionsStepBody) }
                case 1: wizCard { AnyView(onboardingPanel) }
                default: wizCard { AnyView(startStepBody) }
                }
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
            .animation(.easeInOut(duration: 0.28), value: wizPage)
            .transition(.asymmetric(insertion: .move(edge: .trailing), removal: .move(edge: .leading)))

            Divider()
            // Footer nav: Back / Next
            HStack {
                Button("Back") { withAnimation { wizPage = max(0, wizPage - 1) } }
                    .buttonStyle(.bordered).disabled(wizPage == 0)
                Spacer()
                legalFooterCompact
                Spacer()
                nextButton
            }
            .padding(.horizontal, 26).padding(.vertical, 14)
        }
    }

    // The visible page index (clamped to what's reachable).
    var wizPage: Int {
        get { min(wizPageState, 2) }
        nonmutating set { wizPageState = min(max(0, newValue), 2) }
    }
    var wizStepTitle: String {
        switch wizPage {
        case 0: return "Step 1 of 3 — Permissions"
        case 1: return "Step 2 of 3 — Connect your account"
        default: return "Step 3 of 3 — Test and start"
        }
    }

    // A step card: scrolls if tall, consistent padding.
    func wizCard(@ViewBuilder _ content: () -> AnyView) -> some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 14) { content() }
                .padding(26).frame(maxWidth: .infinity, alignment: .leading)
        }
        .id(wizPage)   // forces the slide transition between pages
    }

    // Numbered 1-2-3 progress indicator on top, with labels + connectors.
    var progressDots: some View {
        let labels = ["Permissions", "Connect", "Start"]
        return HStack(spacing: 0) {
            ForEach(0..<3, id: \.self) { i in
                let done = i < wizPage, active = i == wizPage
                HStack(spacing: 7) {
                    ZStack {
                        Circle().fill(done ? Color.green : (active ? BRAND : Color.secondary.opacity(0.22)))
                            .frame(width: 24, height: 24)
                        if done { Image(systemName: "checkmark").font(.system(size: 11, weight: .bold)).foregroundColor(.white) }
                        else { Text("\(i + 1)").font(.system(size: 12, weight: .bold)).foregroundColor(active ? .white : .secondary) }
                    }
                    Text(labels[i]).font(.system(size: 12, weight: active ? .semibold : .regular))
                        .foregroundColor(active || done ? .primary : .secondary)
                }
                if i < 2 {
                    Rectangle().fill(done ? Color.green : Color.secondary.opacity(0.18))
                        .frame(height: 2).frame(maxWidth: 40).padding(.horizontal, 8)
                }
            }
        }.animation(.easeInOut(duration: 0.25), value: wizPage)
    }

    // Context-aware primary button at the bottom-right.
    @ViewBuilder var nextButton: some View {
        switch wizPage {
        case 0:
            Button("Next") { withAnimation { wizPage = 1 } }
                .buttonStyle(.borderedProminent).tint(BRAND)
        case 1:
            // On the connect page, Next only enables once paired.
            Button(state.paired ? "Next" : "Waiting for code…") { withAnimation { wizPage = 2 } }
                .buttonStyle(.borderedProminent).tint(BRAND).disabled(!state.paired)
        default:
            if bridge.running {
                Button("Open app") { wizardDismissed = true }
                    .buttonStyle(.borderedProminent).tint(BRAND)
            } else {
                EmptyView()   // Start lives inside the step card (after test)
            }
        }
    }

    var legalFooterCompact: some View {
        HStack(spacing: 8) {
            Button("Privacy") { NSWorkspace.shared.open(URL(string: "https://sms8.io/privacy")!) }
                .buttonStyle(.plain).font(.system(size: 11)).foregroundColor(.secondary)
            Text("·").foregroundColor(.secondary).font(.system(size: 11))
            Button("Terms") { NSWorkspace.shared.open(URL(string: "https://sms8.io/terms")!) }
                .buttonStyle(.plain).font(.system(size: 11)).foregroundColor(.secondary)
        }
    }

    // Privacy / Terms links — required for the App Store and good practice.
    var legalFooter: some View {
        HStack(spacing: 10) {
            Button("Privacy Policy") { NSWorkspace.shared.open(URL(string: "https://sms8.io/privacy")!) }
                .buttonStyle(.plain).font(.system(size: 11)).foregroundColor(.secondary)
            Text("·").foregroundColor(.secondary).font(.system(size: 11))
            Button("Terms") { NSWorkspace.shared.open(URL(string: "https://sms8.io/terms")!) }
                .buttonStyle(.plain).font(.system(size: 11)).foregroundColor(.secondary)
            Spacer()
            Text("SMS8 Gateway").font(.system(size: 11)).foregroundColor(.secondary.opacity(0.7))
        }
    }

    // One row of the vertical timeline: a numbered/checked dot, a green line down
    // to the next row, and (when active) the step's expanded content beside it.
    func timelineRow<C: View>(index: Int, title: String, done: Bool, active: Bool,
                              summary: String?, isLast: Bool = false,
                              @ViewBuilder _ content: () -> C) -> some View {
        HStack(alignment: .top, spacing: 14) {
            // Dot + connector column
            VStack(spacing: 0) {
                ZStack {
                    Circle().fill(done ? Color.green : (active ? BRAND : Color.secondary.opacity(0.25)))
                        .frame(width: 26, height: 26)
                    if done { Image(systemName: "checkmark").font(.system(size: 12, weight: .bold)).foregroundColor(.white) }
                    else { Text("\(index)").font(.system(size: 12, weight: .bold)).foregroundColor(active ? .white : .secondary) }
                }
                if !isLast {
                    Rectangle().fill(done ? Color.green : Color.secondary.opacity(0.2))
                        .frame(width: 2).frame(maxHeight: .infinity)
                }
            }
            .frame(width: 26)
            // Content column
            VStack(alignment: .leading, spacing: 8) {
                Text(title).font(.system(size: 14, weight: active ? .bold : .semibold))
                    .foregroundColor(done || active ? .primary : .secondary)
                if active {
                    content()
                } else if let s = summary {
                    Text(s).font(.system(size: 12)).foregroundColor(.secondary)
                }
            }
            .padding(.bottom, isLast ? 0 : 22)
            Spacer(minLength: 0)
        }
        .fixedSize(horizontal: false, vertical: true)
    }

    // Permissions step — full detailed panel with per-permission status.
    var permissionsStepBody: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Here’s what SMS8 Gateway can access and why. Automation is required to send; Full Disk Access lets it receive replies. You can grant these now or later in Settings.")
                .font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
            permissionsPanel
        }
    }

    // Step 3 body — test then start. Start button appears after a successful test.
    var startStepBody: some View {
        VStack(alignment: .leading, spacing: 14) {
            issueBanner
            if bridge.running {
                statusBanner()
                if !bridge.log.isEmpty {
                    panel {
                        HStack {
                            Text("Activity").font(.system(size: 13, weight: .semibold))
                            Spacer()
                            Button("Clear") { bridge.log.removeAll() }.buttonStyle(.borderless).font(.system(size: 11))
                        }
                        VStack(alignment: .leading, spacing: 4) {
                            ForEach(bridge.log.prefix(20), id: \.self) {
                                Text($0).font(.system(size: 11.5, design: .monospaced))
                                    .foregroundColor(.secondary).textSelection(.enabled)
                            }
                        }.frame(maxWidth: .infinity, alignment: .leading)
                    }
                }
            } else {
                testPanel
                if testPassed {
                    // Appears only after the first successful test.
                    HStack(spacing: 12) {
                        Image(systemName: "checkmark.circle.fill").foregroundColor(.green).font(.system(size: 26))
                        VStack(alignment: .leading, spacing: 1) {
                            Text("Success — your test was delivered").font(.system(size: 14, weight: .semibold))
                            Text("You can now start the bridge. It’ll keep running and start automatically each time you open the app.").font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                        }
                        Spacer()
                        Button("Start bridge") { state.autoStart = true; state.save(); bridge.start() }
                            .buttonStyle(.borderedProminent).tint(BRAND).controlSize(.large)
                            .disabled(!canRun)
                    }
                    .padding(14)
                    .background(Color.green.opacity(0.10))
                    .overlay(RoundedRectangle(cornerRadius: 12).stroke(Color.green.opacity(0.35)))
                    .cornerRadius(12)
                } else {
                    Text("Send a quick test above. Once it goes through, you can start the bridge.")
                        .font(.system(size: 12)).foregroundColor(.secondary)
                    if canRun {
                        Button("Skip test and start now") { state.autoStart = true; state.save(); bridge.start() }
                            .buttonStyle(.bordered).controlSize(.small)
                    }
                }
            }
        }
    }

    // ── Sidebar ──
    var sidebar: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack(spacing: 10) {
                appIcon.frame(width: 34, height: 34)
                VStack(alignment: .leading, spacing: 0) {
                    Text("SMS8").font(.system(size: 14, weight: .bold))
                    Text("Gateway").font(.system(size: 11)).foregroundColor(.secondary)
                }
            }.padding(.bottom, 14).padding(.horizontal, 6)
            ForEach(Tab.allCases) { t in
                Button { tab = t } label: {
                    HStack(spacing: 9) {
                        Image(systemName: t.icon).frame(width: 18)
                        Text(t.rawValue).font(.system(size: 13, weight: tab == t ? .semibold : .regular))
                            .fixedSize(horizontal: true, vertical: false).lineLimit(1)
                        Spacer(minLength: 0)
                    }
                    .padding(.vertical, 7).padding(.horizontal, 9)
                    .background(tab == t ? Color.primary.opacity(0.08) : .clear)
                    .foregroundColor(.primary)
                    .cornerRadius(8)
                }.buttonStyle(.plain)
            }
            Spacer()
            // status footer
            HStack(spacing: 7) {
                Circle().fill(bridge.running ? Color.green : Color.gray.opacity(0.4)).frame(width: 8, height: 8)
                Text(bridge.running ? "Running" : "Stopped").font(.system(size: 11)).foregroundColor(.secondary)
            }.padding(.horizontal, 8).padding(.bottom, 4)
        }
        .padding(12).frame(width: 170)
        .background(Color(nsColor: .underPageBackgroundColor))
    }
    var appIcon: some View {
        Group {
            if let p = Bundle.main.path(forResource: "logo", ofType: "png"), let img = NSImage(contentsOfFile: p) {
                Image(nsImage: img).resizable().aspectRatio(contentMode: .fit).cornerRadius(8)
            } else { RoundedRectangle(cornerRadius: 8).fill(BRAND) }
        }
    }

    // ── Top bar ──
    var topBar: some View {
        HStack {
            VStack(alignment: .leading, spacing: 1) {
                Text(tab.rawValue).font(.system(size: 16, weight: .bold))
                Text(state.paired ? "Connected — \(state.maskedEmail)" : "Not connected")
                    .font(.system(size: 12)).foregroundColor(.secondary)
            }
            Spacer()
            // Start/Stop lives in one place: the status banner (Start) and the
            // Bridge card (Stop).
        }.padding(.horizontal, 20).padding(.vertical, 13)
    }

    // Readiness checklist: what still needs doing before messages flow.
    // Returned as (allGood, lines) where each line is (ok, text, actionLabel?, action?).
    // `blocking` checks must pass before the bridge can run at all.
    // Non-blocking checks are advisory — they degrade a feature (e.g. SMS send)
    // but don't stop the bridge, so we never trap a user who only wants iMessage
    // or only wants to receive.
    struct Check: Identifiable { let id = UUID(); let ok: Bool; let text: String; let action: (() -> Void)?; let actionLabel: String?; let blocking: Bool }
    var checks: [Check] {
        var out: [Check] = []
        out.append(Check(ok: state.paired, text: state.paired ? "Linked to your SMS8 account" : "Not linked yet — enter your pairing code", action: nil, actionLabel: nil, blocking: true))
        do {
            // Real status instead of an always-green "ready" line. Advisory, so
            // the bridge still runs (and reports its health) while it is fixed.
            switch health.automation {
            case .denied:
                out.append(Check(ok: false, text: "Not allowed to control Messages, so nothing can be sent", action: { performPrimary(.automationDenied) }, actionLabel: "Open Settings", blocking: false))
            case .notDetermined:
                out.append(Check(ok: false, text: "Permission to control Messages not given yet", action: { performPrimary(.automationNeeded) }, actionLabel: "Allow", blocking: false))
            default: break
            }
            switch health.account {
            case .ready:
                out.append(Check(ok: true, text: "Messages is signed in to iMessage", action: nil, actionLabel: nil, blocking: false))
            case .notSignedIn, .turnedOff:
                out.append(Check(ok: false, text: health.account == .turnedOff ? "iMessage is turned off in Messages" : "Messages is not signed in to iMessage", action: { HealthMonitor.launchMessages(activate: true) }, actionLabel: "Open Messages", blocking: false))
            default: break
            }
            if !health.online {
                out.append(Check(ok: false, text: "This Mac is offline", action: { performPrimary(.offline) }, actionLabel: "Network Settings", blocking: false))
            }
            if state.receiveEnabled {
                // Advisory: without FDA, receiving is off, but sending still works.
                out.append(Check(ok: fullDisk, text: fullDisk ? "Full Disk Access granted (receiving works)" : "Full Disk Access off — incoming replies won't sync",
                    action: { openFullDisk() }, actionLabel: fullDisk ? nil : "Allow", blocking: false))
            }
        }
        return out
    }
    // Can the bridge run? Only blocking checks must pass.
    var canRun: Bool { checks.filter { $0.blocking }.allSatisfy { $0.ok } }
    // Is everything (including advisory) green?
    var allGood: Bool { checks.allSatisfy { $0.ok } }

    // Prominent at-a-glance status banner.
    func statusBanner() -> some View {
        let live = bridge.running
        let runnable = canRun
        let perfect = allGood
        // running > runnable-with-advisory > ready > blocked
        let (tint, icon, title, sub): (Color, String, String, String)
        if live, let why = bridge.pausedReason ?? health.sendingBlockedReason {
            tint = .red; icon = "pause.circle.fill"; title = "Running, but sending is paused"
            sub = "Sending is paused because " + why + ". Queued messages stay in SMS8 and go out once this is fixed."
        } else if live {
            tint = .green; icon = "checkmark.circle.fill"; title = "Everything is running"
            sub = perfect ? "Your SMS8 messages are flowing through this Mac."
                          : "Running. One optional permission is still off (see Setup below)."
        } else if runnable && perfect {
            tint = BRAND; icon = "bolt.circle.fill"; title = "Ready — press Start"
            sub = "All set. Click Start bridge to begin delivering messages."
        } else if runnable {
            tint = BRAND; icon = "bolt.circle.fill"; title = "Ready — press Start"
            sub = "You can start now. One optional permission is off, which only limits SMS or receiving."
        } else {
            tint = .orange; icon = "exclamationmark.triangle.fill"; title = "Action needed"
            sub = "Finish the required step below, then start the bridge."
        }
        return HStack(spacing: 12) {
            Image(systemName: icon).font(.system(size: 26)).foregroundColor(tint)
            VStack(alignment: .leading, spacing: 2) {
                Text(title).font(.system(size: 15, weight: .bold))
                Text(sub).font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
            }
            Spacer()
            if state.paired && !live {
                Button("Start bridge") {
                    state.autoStart = true; state.save(); bridge.start()
                }
                    .buttonStyle(.borderedProminent).tint(BRAND)
                    .disabled(!runnable && !live)
            }
        }
        .padding(16)
        .background(tint.opacity(0.10))
        .overlay(RoundedRectangle(cornerRadius: 12).stroke(tint.opacity(0.35)))
        .cornerRadius(12)
    }

    func checklist() -> some View {
        panel {
            Text("Setup").font(.system(size: 13, weight: .semibold))
            ForEach(checks) { c in
                HStack(alignment: .center, spacing: 9) {
                    Image(systemName: c.ok ? "checkmark.circle.fill" : "circle")
                        .foregroundColor(c.ok ? .green : .secondary).font(.system(size: 15))
                    Text(c.text).font(.system(size: 12.5)).foregroundColor(c.ok ? .primary : .secondary)
                    Spacer()
                    if let label = c.actionLabel, let act = c.action {
                        Button(label) { act() }.buttonStyle(.bordered).controlSize(.small)
                    }
                }
            }
        }
    }

    // The current onboarding step (1=connect, 2=permissions, 3=start).
    // Once live, setup is done and we show the live view instead of the wizard.
    // Permissions step is "ready" when the engine's own requirements are met.
    // This Mac: iMessage works out of the box, so it's ready immediately
    // (Accessibility/Full Disk Access are optional and handled inline).
    var permissionsReady: Bool { true }

    // New order: 1 Permissions → 2 Connect → 3 Test & start.
    var onboardingStep: Int {
        if !permissionsReady { return 1 }
        if !state.paired { return 2 }
        return 3
    }

    // 1-2-3 progress stepper shown during setup only.
    func stepper() -> some View {
        let labels = ["Connect", "Permissions", "Start"]
        let cur = onboardingStep
        return HStack(spacing: 0) {
            ForEach(0..<labels.count, id: \.self) { i in
                let n = i + 1
                let done = n < cur, active = n == cur
                HStack(spacing: 7) {
                    ZStack {
                        Circle().fill(done ? Color.green : (active ? BRAND : Color.secondary.opacity(0.25)))
                            .frame(width: 22, height: 22)
                        if done { Image(systemName: "checkmark").font(.system(size: 11, weight: .bold)).foregroundColor(.white) }
                        else { Text("\(n)").font(.system(size: 11, weight: .bold)).foregroundColor(active ? .white : .secondary) }
                    }
                    Text(labels[i]).font(.system(size: 12, weight: active ? .semibold : .regular))
                        .foregroundColor(active || done ? .primary : .secondary)
                }
                if i < labels.count - 1 {
                    Rectangle().fill(done ? Color.green : Color.secondary.opacity(0.2))
                        .frame(height: 2).frame(maxWidth: .infinity).padding(.horizontal, 8)
                }
            }
        }.padding(.vertical, 4)
    }

    // ── CONNECTION TAB ──
    var connectionTab: some View {
        VStack(alignment: .leading, spacing: 18) {
            // Show the 1-2-3 stepper only while still setting up.
            if !bridge.running && onboardingStep < 3 { stepper() }
            if state.paired {
                statusBanner()
                // Show the checklist whenever something isn't perfect yet
                // (required OR optional), so advisory items stay visible.
                if !bridge.running && !allGood { checklist() }
                if bridge.running && !allGood { checklist() }
                if bridge.running {
                    // Live: keep it focused on the running bridge, not the test box.
                    panel {
                        HStack(spacing: 10) {
                            Circle().fill(Color.green).frame(width: 9, height: 9)
                            Text("Bridge is live").font(.system(size: 15, weight: .bold))
                            Spacer()
                            Button("Stop") { state.autoStart = false; state.save(); bridge.stop() }.buttonStyle(.bordered).tint(.red)
                        }
                        Text("Linked as device \(state.deviceId) on \(state.maskedEmail). Messages queued in SMS8 are delivered here as \(state.sendMode == .sms ? "SMS" : state.sendMode == .imessage ? "iMessage" : "SMS/iMessage"), and \(state.receiveEnabled ? "replies flow back to your inbox." : "receiving is off (send only).")")
                            .font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                        HStack(spacing: 14) {
                            liveStat("Last activity", bridge.log.first.map { String($0.prefix(8)) } ?? "—")
                            liveStat("Mode", state.sendMode == .sms ? "SMS" : state.sendMode == .imessage ? "iMessage" : "Auto")
                            liveStat("Receiving", state.receiveEnabled ? "On" : "Off")
                        }.padding(.top, 2)
                    }
                    DisclosureGroup("Send a test message") { testPanel.padding(.top, 8) }
                        .font(.system(size: 12, weight: .medium))
                } else {
                    panel {
                        HStack {
                            Label("Linked as device \(state.deviceId) — \(state.maskedEmail)", systemImage: "checkmark.seal.fill")
                                .font(.system(size: 13, weight: .semibold)).foregroundColor(.green)
                            Spacer()
                            Button("Disconnect") { state.paired = false; state.save(); bridge.stop() }
                                .buttonStyle(.bordered).controlSize(.small)
                        }
                    }
                    testPanel
                }
            } else {
                onboardingPanel
            }
        }
    }

    // ── Guided onboarding (not paired yet) ──
    // Keeps the clean two-card look; amendments: Create-account is a button,
    // Add-device is a link under the code, and pasting shows a real loading state.
    var onboardingPanel: some View {
        VStack(alignment: .leading, spacing: 14) {
            if pairSucceeded {
                // Success state — a clear checkmark, then auto-advances after ~2s.
                panel {
                    HStack(spacing: 12) {
                        Image(systemName: "checkmark.circle.fill").foregroundColor(.green).font(.system(size: 30))
                        VStack(alignment: .leading, spacing: 2) {
                            Text("Connected!").font(.system(size: 16, weight: .bold))
                            Text("Linked as \(state.maskedEmail) — taking you to the next step…")
                                .font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                        }
                        Spacer()
                    }
                }
            } else {
            // Card 1 — enter the code (or show live pairing progress)
            panel {
                if pairing {
                    pairingProgressView
                } else {
                    Text("Enter your pairing code").font(.system(size: 14, weight: .semibold))
                    Text("Open the Devices page in SMS8, choose Connect a device, and copy the 6-character code.")
                        .font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                    // Input + buttons share the same 46pt height so they line up.
                    HStack(spacing: 10) {
                        TextField("", text: $code, prompt: Text("ABC123"))
                            .textFieldStyle(.plain)
                            .font(.system(size: 22, weight: .bold, design: .monospaced))
                            .multilineTextAlignment(.center)
                            .frame(width: 180, height: 46)
                            .background(Color(nsColor: .textBackgroundColor))
                            .overlay(RoundedRectangle(cornerRadius: 10).stroke(code.count == 6 ? BRAND : Color.primary.opacity(0.18), lineWidth: code.count == 6 ? 2 : 1))
                            .cornerRadius(10)
                            .onChange(of: code) { _, nv in
                                code = String(nv.uppercased().filter { $0.isLetter || $0.isNumber }.prefix(6))
                                if !pairMsg.isEmpty { pairMsg = "" }
                                if code.count == 6 && !pairing { doPair() }
                            }
                        Button { pasteCode() } label: {
                            Label("Paste", systemImage: "doc.on.clipboard").font(.system(size: 13))
                                .frame(height: 46).padding(.horizontal, 14)
                                .background(Color.primary.opacity(0.06))
                                .overlay(RoundedRectangle(cornerRadius: 10).stroke(Color.primary.opacity(0.15)))
                                .cornerRadius(10)
                        }.buttonStyle(.plain)
                        Button { doPair() } label: {
                            Text("Connect").font(.system(size: 13, weight: .semibold))
                                .frame(height: 46).padding(.horizontal, 22)
                                .background(code.count == 6 ? BRAND : Color.secondary.opacity(0.25))
                                .foregroundColor(.white).cornerRadius(10)
                        }.buttonStyle(.plain).disabled(code.count < 6)
                    }
                    if !pairMsg.isEmpty {
                        HStack(spacing: 6) {
                            Image(systemName: pairMsg.hasPrefix("✓") ? "checkmark.circle.fill" : "exclamationmark.circle.fill")
                                .foregroundColor(pairMsg.hasPrefix("✓") ? .green : .red).font(.system(size: 12))
                            Text(pairMsg.replacingOccurrences(of: "✓ ", with: "").replacingOccurrences(of: "✗ ", with: ""))
                                .font(.system(size: 12.5)).foregroundColor(pairMsg.hasPrefix("✓") ? .green : .red)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }
                    Text("It connects automatically once all 6 characters are in.")
                        .font(.system(size: 11)).foregroundColor(.secondary)
                    // Both actions as inline links (no separate card).
                    HStack(spacing: 16) {
                        Button { WebSignIn.shared.open("/devices") } label: {
                            Text("Sign in to see your code").font(.system(size: 12)).underline()
                        }.buttonStyle(.plain).foregroundColor(.primary)
                    }.padding(.top, 2)
                }
            }
            } // end else (not paired-success)
        }
    }

    // Live loading view shown while pairing (real progress, not a frozen button).
    var pairingProgressView: some View {
        HStack(spacing: 12) {
            ProgressView().controlSize(.small)
            VStack(alignment: .leading, spacing: 2) {
                Text(pairPhase).font(.system(size: 13, weight: .semibold))
                Text("This takes a few seconds — please keep the app open.")
                    .font(.system(size: 11)).foregroundColor(.secondary)
            }
            Spacer()
        }
    }

    var testPanel: some View {
        panel {
            HStack(spacing: 6) {
                Image(systemName: "paperplane.fill").font(.system(size: 12)).foregroundColor(.primary)
                Text("Send a test message").font(.system(size: 14, weight: .semibold))
            }
            // Styled input + send button pill.
            HStack(spacing: 10) {
                HStack(spacing: 8) {
                    Image(systemName: "person.crop.circle").foregroundColor(.secondary).font(.system(size: 14))
                    TextField("Phone number or Apple ID email", text: $testTo)
                        .textFieldStyle(.plain).font(.system(size: 13))
                        .onSubmit { runTest() }
                }
                .padding(.horizontal, 12).padding(.vertical, 9)
                .background(Color(nsColor: .textBackgroundColor))
                .overlay(RoundedRectangle(cornerRadius: 9).stroke(Color.primary.opacity(0.12)))
                .cornerRadius(9)

                Button { runTest() } label: {
                    HStack(spacing: 6) {
                        if sending { ProgressView().controlSize(.small) }
                        else { Image(systemName: "paperplane.fill").font(.system(size: 12)) }
                        Text(sending ? "Sending" : "Send").font(.system(size: 13, weight: .semibold))
                    }
                    .padding(.horizontal, 18).padding(.vertical, 9)
                    .background(testTo.isEmpty || sending ? Color.secondary.opacity(0.25) : BRAND)
                    .foregroundColor(.white).cornerRadius(9)
                }
                .buttonStyle(.plain).disabled(testTo.isEmpty || sending)
            }
            if !testResult.isEmpty {
                HStack(spacing: 6) {
                    Image(systemName: testResult.hasPrefix("✓") ? "checkmark.circle.fill" : "xmark.circle.fill")
                        .foregroundColor(testResult.hasPrefix("✓") ? .green : .red).font(.system(size: 13))
                    Text(testResult.replacingOccurrences(of: "✓ ", with: "").replacingOccurrences(of: "✗ ", with: ""))
                        .font(.system(size: 12)).foregroundColor(testResult.hasPrefix("✓") ? .green : .red)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
            Text("Tip: phone numbers go as SMS (Android & iPhone). Apple-ID emails go as iMessage.")
                .font(.system(size: 11)).foregroundColor(.secondary)
        }
    }

    func runTest() {
        guard !testTo.isEmpty, !sending else { return }
        sending = true; testResult = ""
        let to = testTo
        DispatchQueue.global().async {
            let (ok, info) = Messaging.send(state, to: to, body: "SMS8 bridge test — reply to check receive.")
            DispatchQueue.main.async {
                sending = false
                testResult = ok ? "✓ Sent via \(info)." : "✗ Not delivered: \(info)"
                if ok { testPassed = true } else { health.refresh() }
            }
        }
    }

    // Permissions panel (This Mac engine) — reused in wizard + settings.
    var permissionsPanel: some View {
        panel {
            HStack(spacing: 6) {
                Text("Permissions").font(.system(size: 14, weight: .semibold))
                HelpTip(text: "macOS protects Messages and your message history. Each permission below shows whether it’s already allowed, and exactly what it’s for. Grant only what you use.")
            }
            // 1) Automation — required for sending.
            permRow2(status: automationPerm, name: "Automation → Messages",
                     tag: "Required",
                     why: automationWhy) {
                switch health.automation {
                case .notDetermined:
                    Button("Allow access") { performPrimary(.automationNeeded) }.buttonStyle(.borderedProminent).tint(BRAND).controlSize(.small)
                case .denied:
                    Button("Open Automation Settings") { performPrimary(.automationDenied) }.buttonStyle(.borderedProminent).tint(BRAND).controlSize(.small)
                    Button("Re-check") { health.refresh() }.buttonStyle(.bordered).controlSize(.small)
                case .messagesNotRunning, .unknown:
                    Button("Open Messages") { HealthMonitor.launchMessages(activate: true); DispatchQueue.main.asyncAfter(deadline: .now() + 2) { health.refresh() } }
                        .buttonStyle(.bordered).controlSize(.small)
                case .granted: EmptyView()
                }
            }
            // iMessage account in Messages.app (only knowable once Automation is allowed).
            if health.automation == .granted {
                permRow2(status: health.account == .ready ? .allowed : (health.account == .connecting || health.account == .unknown ? .unknown : .needed),
                         name: "Messages signed in to iMessage", tag: "Required",
                         why: iMessageWhy) {
                    if health.account != .ready {
                        Button("Open Messages") { HealthMonitor.launchMessages(activate: true) }.buttonStyle(.borderedProminent).tint(BRAND).controlSize(.small)
                        Button("Re-check") { health.refresh() }.buttonStyle(.bordered).controlSize(.small)
                    }
                }
            }
            // 2) Full Disk Access — for receiving replies.
            permRow2(status: !state.receiveEnabled ? .optional : (fullDisk ? .allowed : .needed),
                     name: "Full Disk Access",
                     tag: "For receiving replies",
                     why: state.receiveEnabled
                          ? "Needed to read incoming messages so replies reach your SMS8 inbox. Turn it ON for SMS8 Gateway, then reopen the app — macOS only applies this on restart. You can change this anytime."
                          : "Turn on Receiving to pull replies into your SMS8 inbox. This needs Full Disk Access. You can enable it now or later in Settings.") {
                // Receiving toggle lives here so the user can enable it (and FDA) inline.
                Toggle(isOn: Binding(get: { state.receiveEnabled }, set: { on in
                    state.receiveEnabled = on; state.save()
                    if on { health.quickCheck(); if !fullDisk { openFullDisk() } }
                })) {
                    Text("Receive replies").font(.system(size: 12))
                }.toggleStyle(.switch).tint(BRAND).controlSize(.mini)
                if state.receiveEnabled {
                    Button("Open settings") { openFullDisk() }.buttonStyle(.borderedProminent).tint(BRAND).controlSize(.small)
                    Button("Re-check") { health.refresh() }.buttonStyle(.bordered).controlSize(.small)
                    if !fullDisk { Button("Relaunch app") { relaunchApp() }.buttonStyle(.bordered).controlSize(.small) }
                }
            }
        }
    }

    enum PermStatus { case allowed, needed, optional, notNeeded, unknown }

    var automationPerm: PermStatus {
        switch health.automation {
        case .granted: return .allowed
        case .notDetermined, .denied: return .needed
        default: return .unknown
        }
    }
    var automationWhy: String {
        switch health.automation {
        case .granted:       return "Allowed. SMS8 Gateway can send iMessage and SMS through the Messages app."
        case .notDetermined: return "Lets SMS8 Gateway send through the Messages app. Click Allow access, then OK in the macOS prompt."
        case .denied:        return "Turned off, so nothing can be sent. Open Automation Settings, expand SMS8 Gateway and turn on Messages."
        case .messagesNotRunning: return "Messages is not open, so this can't be checked yet. Open Messages and this updates on its own."
        case .unknown:       return "Lets SMS8 Gateway send iMessage through the Messages app. Checking..."
        }
    }
    var iMessageWhy: String {
        switch health.account {
        case .ready:       return "Messages is signed in and connected to iMessage."
        case .connecting:  return "Messages is connecting to iMessage. This usually takes a few seconds."
        case .notSignedIn: return "Messages has no working iMessage account. Open Messages, choose Messages > Settings > iMessage and sign in with your Apple ID (Apple Account), then turn on Enable this account."
        case .turnedOff:   return "The iMessage account is disabled. Open Messages, choose Messages > Settings > iMessage and turn on Enable this account."
        case .unknown:     return "Checking the Messages account..."
        }
    }

    // A permission row with a clear status badge, a purpose line, and actions.
    func permRow2<C: View>(status: PermStatus, name: String, tag: String, why: String,
                           @ViewBuilder _ actions: () -> C = { EmptyView() }) -> some View {
        let (icon, color, label): (String, Color, String) = {
            switch status {
            case .allowed:   return ("checkmark.circle.fill", .green, "Allowed")
            case .needed:    return ("exclamationmark.circle.fill", .orange, "Needs allowing")
            case .optional:  return ("circle", .secondary, "Optional — set later")
            case .notNeeded: return ("minus.circle", .secondary, "Not needed")
            case .unknown:   return ("questionmark.circle", .secondary, "Checking")
            }
        }()
        return HStack(alignment: .top, spacing: 10) {
            Image(systemName: icon).foregroundColor(color).font(.system(size: 16))
            VStack(alignment: .leading, spacing: 4) {
                HStack(spacing: 8) {
                    Text(name).font(.system(size: 13, weight: .semibold))
                    Text(tag).font(.system(size: 9.5, weight: .medium)).foregroundColor(.secondary)
                        .padding(.horizontal, 6).padding(.vertical, 1)
                        .background(Color.secondary.opacity(0.12)).cornerRadius(4)
                    Spacer()
                    Text(label).font(.system(size: 10.5, weight: .semibold)).foregroundColor(color)
                }
                Text(why).font(.system(size: 11.5)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                HStack(spacing: 8) { actions() }
            }
        }
        .padding(11)
        .background(status == .needed ? color.opacity(0.06) : Color.primary.opacity(0.03))
        .overlay(RoundedRectangle(cornerRadius: 8).stroke(status == .needed ? color.opacity(0.3) : Color.clear))
        .cornerRadius(8)
    }

    // "Send as" picker — Auto / iMessage only / SMS only, for BOTH engines.
    // This Mac can now send SMS via a paired iPhone (Text Message Forwarding),
    // so it gets the full choice. Auto needs Full Disk Access to detect an
    // iMessage failure and fall back to SMS — we surface that requirement.
    @ViewBuilder var sendAsPanel: some View {
        let thisMac = true
        panel {
            HStack(spacing: 6) {
                Text("Send as").font(.system(size: 14, weight: .semibold))
                HelpTip(text: "Auto: try iMessage first, fall back to SMS if it can’t deliver. iMessage only: Apple contacts only. SMS only: always send as a text message (needs a paired iPhone).")
            }
            Picker("", selection: Binding(get: { state.sendMode }, set: { state.sendMode = $0; state.save() })) {
                ForEach(SendMode.allCases, id: \.self) { Text($0.rawValue).tag($0) }
            }.pickerStyle(.radioGroup).labelsHidden()
            Text("Auto is recommended: tries iMessage first, and if it can’t deliver (e.g. an Android number), it sends as SMS automatically.")
                .font(.system(size: 11)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
            if thisMac && (state.sendMode == .sms || state.sendMode == .auto) {
                // SMS on This Mac relies on an attached iPhone relaying over its SIM.
                Text("SMS on this Mac sends through a paired iPhone (same Apple ID, Text Message Forwarding on). Auto fallback also needs Full Disk Access so the app can tell when an iMessage didn’t deliver.")
                    .font(.system(size: 11)).foregroundColor(fullDisk ? .secondary : .orange)
                    .fixedSize(horizontal: false, vertical: true)
            }
        }
    }

    // ── SETTINGS TAB ──
    var settingsTab: some View {
        VStack(alignment: .leading, spacing: 18) {
            permissionsPanel

            sendAsPanel
            // Startup (shared)
            panel {
                Text("Startup").font(.system(size: 14, weight: .semibold))
                Toggle(isOn: Binding(get: { state.autoStart }, set: { on in
                    state.autoStart = on; state.save()
                    if on && canRun && !bridge.running { bridge.start() }
                    if !on && bridge.running { bridge.stop() }
                })) {
                    Text("Start the bridge automatically").font(.system(size: 13))
                }.toggleStyle(.switch).tint(BRAND)
                Text(state.autoStart
                     ? "On — the bridge runs as soon as the app opens and everything is set up."
                     : "Off — you start the bridge manually from the Connection tab.")
                    .font(.system(size: 11)).foregroundColor(.secondary)
            }
            // Receiving (shared)
            panel {
                HStack(spacing: 6) {
                    Text("Receiving").font(.system(size: 14, weight: .semibold))
                    HelpTip(text: "When on, messages people send you are read from this Mac and pushed into your SMS8 inbox and webhooks. On “This Mac” this needs Full Disk Access. For incoming SMS, your iPhone must have Text Message Forwarding to this Mac turned on.")
                }
                Toggle(isOn: Binding(get: { state.receiveEnabled }, set: { on in
                    state.receiveEnabled = on; state.save()
                    if on { health.quickCheck(); if !fullDisk { openFullDisk() } }
                })) {
                    Text("Receive incoming messages into SMS8").font(.system(size: 13))
                }.toggleStyle(.switch).tint(BRAND)
                Text(state.receiveEnabled
                     ? "On — replies flow back into your SMS8 inbox and webhooks."
                     : "Off — this Mac only sends. No Full Disk Access required.")
                    .font(.system(size: 11)).foregroundColor(.secondary)
                if state.receiveEnabled && !fullDisk {
                    Label("Full Disk Access not granted — receiving won't work until you allow it and reopen the app.", systemImage: "exclamationmark.triangle.fill")
                        .font(.system(size: 11)).foregroundColor(.orange)
                }
            }
        }
    }

    // ── ACTIVITY TAB ──
    var activityTab: some View {
        panel {
            HStack {
                Text("Activity log").font(.system(size: 14, weight: .semibold))
                Spacer()
                Button("Clear") { bridge.log.removeAll() }.buttonStyle(.borderless).font(.system(size: 11))
            }
            if bridge.log.isEmpty {
                Text("No activity yet. Start the bridge and send or receive a message.")
                    .font(.system(size: 12)).foregroundColor(.secondary).padding(.vertical, 8)
            } else {
                VStack(alignment: .leading, spacing: 4) {
                    ForEach(bridge.log.prefix(100), id: \.self) {
                        Text($0).font(.system(size: 11.5, design: .monospaced))
                            .foregroundColor(.secondary).textSelection(.enabled)
                    }
                }.frame(maxWidth: .infinity, alignment: .leading)
            }
        }
    }

    func liveStat(_ label: String, _ value: String) -> some View {
        VStack(alignment: .leading, spacing: 2) {
            Text(label.uppercased()).font(.system(size: 9, weight: .semibold)).foregroundColor(.secondary).tracking(0.5)
            Text(value).font(.system(size: 13, weight: .semibold, design: .monospaced))
        }
    }

    // ── helpers ──
    func openFullDisk() {
        NSWorkspace.shared.open(URL(string: "x-apple.systempreferences:com.apple.preference.security?Privacy_AllFiles")!)
    }
    func openAccessibility() {
        _ = iMsg.hasAccessibility(prompt: true)   // also triggers the system prompt
        NSWorkspace.shared.open(URL(string: "x-apple.systempreferences:com.apple.preference.security?Privacy_Accessibility")!)
    }
    // Relaunch the app so a freshly-granted Full Disk Access takes effect
    // (macOS only picks up FDA at process launch).
    func relaunchApp() {
        let url = Bundle.main.bundleURL
        let cfg = NSWorkspace.OpenConfiguration()
        cfg.createsNewApplicationInstance = true
        // Launch a fresh instance after a short delay, then quit this one.
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.2) {
            NSWorkspace.shared.openApplication(at: url, configuration: cfg) { _, _ in }
        }
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) {
            NSApp.terminate(nil)
        }
    }
    func panel<C: View>(@ViewBuilder _ content: () -> C) -> some View {
        VStack(alignment: .leading, spacing: 10) { content() }
            .padding(16).frame(maxWidth: .infinity, alignment: .leading)
            .background(Color(nsColor: .controlBackgroundColor))
            .cornerRadius(12)
            .overlay(RoundedRectangle(cornerRadius: 12).stroke(Color.primary.opacity(0.08)))
    }
    func permRow<C: View>(ok: Bool, name: String, why: String, @ViewBuilder _ actions: () -> C = { EmptyView() }) -> some View {
        HStack(alignment: .top, spacing: 10) {
            Image(systemName: ok ? "checkmark.circle.fill" : "exclamationmark.circle.fill")
                .foregroundColor(ok ? .green : .orange).font(.system(size: 16))
            VStack(alignment: .leading, spacing: 4) {
                Text(name).font(.system(size: 13, weight: .semibold))
                Text(why).font(.system(size: 12)).foregroundColor(.secondary).fixedSize(horizontal: false, vertical: true)
                HStack { actions() }
            }
        }.padding(10).background(Color.primary.opacity(0.03)).cornerRadius(8)
    }

    // Paste a code from the clipboard (strips formatting, keeps 6 alphanumerics).
    func pasteCode() {
        let s = NSPasteboard.general.string(forType: .string) ?? ""
        code = String(s.uppercased().filter { $0.isLetter || $0.isNumber }.prefix(6))
        if code.count == 6 { doPair() }
    }

    func doPair() {
        let entered = code.trimmingCharacters(in: .whitespaces).uppercased()
        // Guard: must be exactly 6 alphanumerics.
        guard entered.count == 6, entered.allSatisfy({ $0.isLetter || $0.isNumber }) else {
            pairMsg = "✗ Enter the full 6-character code."
            return
        }
        guard !pairing else { return }           // don't double-submit
        pairing = true; pairMsg = ""; pairPhase = "Checking your code…"
        // Animate the phase labels so the wait feels real and informative.
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.9) { if pairing { pairPhase = "Setting up your account…" } }
        DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) { if pairing { pairPhase = "Linking this Mac…" } }

        let startedAt = Date()
        DispatchQueue.global().async {
            let r = SMS8.post("pairing-code-claim.php", [
                "code": entered,
                "androidId": state.androidId, "model": "Mac — iMessage",
                "appVersion": "i10.0.54", "androidVersion": "macOS"])
            // Ensure the progress is visible for at least ~1.4s so it reads as
            // "setting up", not an instant flash.
            let elapsed = Date().timeIntervalSince(startedAt)
            if elapsed < 1.4 { Thread.sleep(forTimeInterval: 1.4 - elapsed) }
            DispatchQueue.main.async {
                // Success
                if let r = r, (r["success"] as? Bool) == true, let d = r["data"] as? [String:Any] {
                    state.userId = "\(d["userID"] ?? "")"; state.apiKey = "\(d["apiKey"] ?? d["key"] ?? "")"
                    state.deviceId = "\(d["deviceID"] ?? "")"; state.email = "\(d["email"] ?? "")"
                    pairPhase = "Connected!"
                    pairSucceeded = true          // show the success checkmark card
                    pairing = false
                    state.paired = true; state.save()   // triggers the 2s auto-advance
                    pairMsg = ""
                    code = ""
                    return
                }
                pairing = false
                // No network / no response
                if r == nil {
                    pairMsg = health.online
                        ? "✗ This Mac is online but SMS8 did not answer. Check that a VPN, firewall or content filter is not blocking app.sms8.io, then try again. Your code is still valid for a few minutes."
                        : "✗ This Mac is offline. Connect to Wi-Fi or Ethernet, then enter the code again."
                    return
                }
                // Server returned an error — map the common cases to clear guidance.
                let raw = ((r?["error"] as? [String:Any])?["message"] as? String ?? "").lowercased()
                let msg: String
                if raw.contains("expire") {
                    msg = "That code has expired. Open the Devices page for a fresh one."
                } else if raw.contains("used") || raw.contains("claim") || raw.contains("already") {
                    msg = "That code was already used. Generate a new one on the Devices page."
                } else if raw.contains("not found") || raw.contains("invalid") || raw.contains("exist") {
                    msg = "That code isn’t valid. Double-check it or get a new one."
                } else if raw.contains("limit") || raw.contains("maximum") {
                    msg = "This account already has the maximum number of devices. Remove a device in your SMS8 account, then retry."
                } else if !raw.isEmpty {
                    msg = raw.prefix(1).uppercased() + raw.dropFirst()
                } else {
                    msg = "That code did not work. It may be mistyped or expired. Codes are 6 letters and numbers, shown on the Devices page under Connect a device."
                }
                pairMsg = "✗ \(msg)"
                code = ""
            }
        }
    }
}

// MARK: - Issue dialog
// A native sheet for one problem: what is wrong, numbered steps, and a button
// that opens the exact System Settings pane (or Messages). Closes by itself
// once the health monitor sees the problem is gone.
struct IssueSheet: View {
    let issue: HealthIssue
    @ObservedObject var health: HealthMonitor
    let onAction: () -> Void
    let onSecondary: () -> Void
    let onLater: () -> Void

    var primaryLabel: String {
        switch issue.kind {
        case .offline:           return "Open Network Settings"
        case .serverUnreachable: return "Retry now"
        case .automationNeeded:  return "Allow access"
        case .automationDenied:  return "Open Automation Settings"
        case .notSignedIn, .iMessageOff: return "Open Messages"
        case .fullDiskMissing:   return "Open Full Disk Access"
        case .messagesFolder:    return "Choose Messages folder"
        }
    }
    var secondaryLabel: String {
        switch issue.kind {
        case .serverUnreachable: return "Open SMS8 Dashboard"
        case .fullDiskMissing:   return "Relaunch app"
        default:                 return "Check again"
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack(alignment: .top, spacing: 12) {
                Image(systemName: "exclamationmark.triangle.fill")
                    .font(.system(size: 28)).foregroundColor(issue.blocksSending ? .red : .orange)
                VStack(alignment: .leading, spacing: 4) {
                    Text(issue.title).font(.system(size: 16, weight: .bold))
                    Text(issue.summary).font(.system(size: 12)).foregroundColor(.secondary)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
            VStack(alignment: .leading, spacing: 8) {
                ForEach(Array(issue.steps.enumerated()), id: \.offset) { i, step in
                    HStack(alignment: .top, spacing: 10) {
                        Text("\(i + 1).").font(.system(size: 12.5, weight: .semibold)).frame(width: 18, alignment: .trailing)
                        Text(step).font(.system(size: 12.5)).fixedSize(horizontal: false, vertical: true)
                    }
                }
            }
            .padding(12).frame(maxWidth: .infinity, alignment: .leading)
            .background(Color.primary.opacity(0.04)).cornerRadius(8)
            HStack {
                Button("Later") { onLater() }.keyboardShortcut(.cancelAction)
                Spacer()
                Button(secondaryLabel) { onSecondary() }.buttonStyle(.bordered)
                Button(primaryLabel) { onAction() }
                    .buttonStyle(.borderedProminent).tint(BRAND).keyboardShortcut(.defaultAction)
            }
        }
        .padding(22).frame(width: 480)
    }
}

// MARK: - App entry
@main
struct SMS8BridgeApp: App {
    var body: some Scene {
        WindowGroup("SMS8 Gateway") { RootView() }
            .windowResizability(.contentSize)
            .commands {
                CommandGroup(replacing: .help) {
                    Button("SMS8 Devices") { WebSignIn.shared.open("/devices") }
                    Button("SMS8 Dashboard") { WebSignIn.shared.open("/") }
                    Divider()
                    Button("Privacy Policy") { NSWorkspace.shared.open(URL(string: "https://sms8.io/privacy")!) }
                    Button("Terms of Service") { NSWorkspace.shared.open(URL(string: "https://sms8.io/terms")!) }
                    Button("Get Support") { NSWorkspace.shared.open(URL(string: "https://sms8.io/support")!) }
                }
            }
    }
}
