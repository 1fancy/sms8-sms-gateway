//
//  SMS8.swift
//  SMS8 — Swift SDK for sending and verifying SMS OTP codes via your own phone.
//
//  Sign up free at https://sms8.io (5-day trial, no card).
//

import Foundation

/// SMS8 SDK errors.
public enum SMS8Error: Error, LocalizedError {
    case missingApiKey
    case http(Int, String?)
    case decoding(Error)
    case mcp(String)
    case noData

    public var errorDescription: String? {
        switch self {
        case .missingApiKey:           return "Missing SMS8 API key. Get one at https://sms8.io"
        case .http(let code, let msg): return "HTTP \(code): \(msg ?? "")"
        case .decoding(let e):         return "Decoding error: \(e.localizedDescription)"
        case .mcp(let m):              return m
        case .noData:                  return "No response body"
        }
    }
}

/// Result of a `send` call.
public struct SendOTPResult: Codable {
    public let success: Bool
    public let otpId: Int?
    public let phone: String?
    public let expiresAt: String?
    public let expiresIn: Int?
    public let error: String?

    enum CodingKeys: String, CodingKey {
        case success
        case otpId      = "otp_id"
        case phone
        case expiresAt  = "expires_at"
        case expiresIn  = "expires_in"
        case error
    }
}

/// Result of a `verify` call.
public struct VerifyOTPResult: Codable {
    public let success: Bool
    public let verified: Bool?
    public let error: String?
    public let attemptsLeft: Int?

    enum CodingKeys: String, CodingKey {
        case success
        case verified
        case error
        case attemptsLeft = "attempts_left"
    }
}

/// SMS8 client. Sends SMS and OTP codes through your paired Android phone.
///
/// ```swift
/// let sms8 = SMS8(apiKey: "sk_xxx")
/// Task {
///     let sent = try await sms8.sendOTP(phone: "+14155550100")
///     // ...show the OTP input to the user...
///     let result = try await sms8.verifyOTP(phone: "+14155550100", code: "482937")
///     if result.verified == true { /* allow login */ }
/// }
/// ```
public final class SMS8 {
    private let apiKey: String
    private let baseURL: URL
    private let session: URLSession

    /// Initialise the client.
    /// - Parameter apiKey: Your SMS8 API key from https://app.sms8.io
    /// - Parameter baseURL: Override the MCP endpoint (rarely needed).
    /// - Parameter session: URLSession to use. Defaults to .shared.
    public init(
        apiKey: String,
        baseURL: URL = URL(string: "https://mcp.sms8.io")!,
        session: URLSession = .shared
    ) {
        self.apiKey = apiKey
        self.baseURL = baseURL
        self.session = session
    }

    // ── SMS ─────────────────────────────────────────────────────────────
    /// Send a plain SMS through your paired Android phone.
    @discardableResult
    public func sendSMS(
        phone: String,
        message: String,
        deviceId: Int? = nil,
        simSlot: String? = nil
    ) async throws -> [String: Any] {
        var args: [String: Any] = ["phone": phone, "message": message]
        if let d = deviceId { args["device_id"] = d }
        if let s = simSlot  { args["sim_slot"]  = s }
        return try await mcpCall(tool: "send_sms", arguments: args)
    }

    // ── OTP send ────────────────────────────────────────────────────────
    /// Generate and send an OTP code. Returns the otpId and expiry.
    public func sendOTP(
        phone: String,
        length: Int = 6,
        template: String? = nil,
        expiresIn: Int = 300,
        maxAttempts: Int = 5,
        deviceId: Int? = nil,
        simSlot: String? = nil
    ) async throws -> SendOTPResult {
        var args: [String: Any] = [
            "phone":        phone,
            "length":       length,
            "expires_in":   expiresIn,
            "max_attempts": maxAttempts
        ]
        if let t = template { args["template"] = t }
        if let d = deviceId { args["device_id"] = d }
        if let s = simSlot  { args["sim_slot"]  = s }
        let raw = try await mcpCall(tool: "send_otp", arguments: args)
        return try decode(raw, as: SendOTPResult.self)
    }

    // ── OTP verify ──────────────────────────────────────────────────────
    /// Verify a code the user typed. The server compares against the most
    /// recent unverified code for that phone.
    public func verifyOTP(phone: String, code: String) async throws -> VerifyOTPResult {
        let raw = try await mcpCall(tool: "verify_otp", arguments: ["phone": phone, "code": code])
        return try decode(raw, as: VerifyOTPResult.self)
    }

    // ── Internal: JSON-RPC over HTTPS ────────────────────────────────────
    private func mcpCall(tool: String, arguments: [String: Any]) async throws -> [String: Any] {
        var args = arguments
        args["api_key"] = apiKey
        let body: [String: Any] = [
            "jsonrpc": "2.0",
            "method":  "tools/call",
            "params":  ["name": tool, "arguments": args],
            "id":      Int(Date().timeIntervalSince1970 * 1000)
        ]
        var req = URLRequest(url: baseURL)
        req.httpMethod = "POST"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        req.setValue("Bearer \(apiKey)", forHTTPHeaderField: "Authorization")
        req.setValue("sms8-swift/1.0.0", forHTTPHeaderField: "User-Agent")
        req.httpBody = try JSONSerialization.data(withJSONObject: body)

        let (data, response) = try await session.data(for: req)
        guard let http = response as? HTTPURLResponse else {
            throw SMS8Error.noData
        }
        if !(200..<300).contains(http.statusCode) {
            let msg = String(data: data, encoding: .utf8)
            throw SMS8Error.http(http.statusCode, msg)
        }
        guard let env = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw SMS8Error.noData
        }
        if let err = env["error"] as? [String: Any], let m = err["message"] as? String {
            throw SMS8Error.mcp(m)
        }
        guard
            let result  = env["result"]   as? [String: Any],
            let content = result["content"] as? [[String: Any]],
            let first   = content.first,
            let text    = first["text"]   as? String
        else {
            return (env["result"] as? [String: Any]) ?? [:]
        }
        if let inner = text.data(using: .utf8),
           let parsed = try? JSONSerialization.jsonObject(with: inner) as? [String: Any] {
            return parsed
        }
        return ["text": text]
    }

    private func decode<T: Decodable>(_ dict: [String: Any], as type: T.Type) throws -> T {
        let data = try JSONSerialization.data(withJSONObject: dict)
        do {
            return try JSONDecoder().decode(type, from: data)
        } catch {
            throw SMS8Error.decoding(error)
        }
    }
}
