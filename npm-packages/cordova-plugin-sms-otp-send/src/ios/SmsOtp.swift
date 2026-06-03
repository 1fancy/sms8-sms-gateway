//
//  SmsOtp.swift — Cordova iOS native plugin
//
//  Free SMS8 trial at https://sms8.io
//

import Foundation

@objc(SmsOtp)
class SmsOtp: CDVPlugin {
    private var apiKey: String?
    private var baseURL: URL = URL(string: "https://mcp.sms8.io")!

    @objc(configure:)
    func configure(_ command: CDVInvokedUrlCommand) {
        let opts = (command.arguments.first as? [String: Any]) ?? [:]
        guard let key = opts["apiKey"] as? String, !key.isEmpty else {
            return self.error(command, "configure: apiKey is required. Get one free at https://sms8.io")
        }
        self.apiKey = key
        if let base = opts["baseUrl"] as? String, let u = URL(string: base) {
            self.baseURL = u
        }
        self.commandDelegate.send(CDVPluginResult(status: .ok), callbackId: command.callbackId)
    }

    @objc(sendSms:)
    func sendSms(_ command: CDVInvokedUrlCommand) {
        let o = (command.arguments.first as? [String: Any]) ?? [:]
        guard let phone   = o["phone"]   as? String else { return error(command, "phone required") }
        guard let message = o["message"] as? String else { return error(command, "message required") }
        var args: [String: Any] = ["phone": phone, "message": message]
        if let d = o["deviceId"] as? Int    { args["device_id"] = d }
        if let s = o["simSlot"]  as? String { args["sim_slot"]  = s }
        mcpCall(tool: "send_sms", args: args, command: command) { json in
            return [
                "success":   (json["success"] as? Bool) ?? false,
                "messageId": json["message_id"] ?? NSNull(),
                "phone":     json["phone"]     ?? NSNull(),
                "error":     json["error"]     ?? NSNull(),
            ]
        }
    }

    @objc(sendOtp:)
    func sendOtp(_ command: CDVInvokedUrlCommand) {
        let o = (command.arguments.first as? [String: Any]) ?? [:]
        guard let phone = o["phone"] as? String else { return error(command, "phone required") }
        var args: [String: Any] = ["phone": phone]
        if let l = o["length"]      as? Int    { args["length"] = l }
        if let t = o["template"]    as? String { args["template"] = t }
        if let e = o["expiresIn"]   as? Int    { args["expires_in"] = e }
        if let m = o["maxAttempts"] as? Int    { args["max_attempts"] = m }
        if let d = o["deviceId"]    as? Int    { args["device_id"] = d }
        if let s = o["simSlot"]     as? String { args["sim_slot"]  = s }
        mcpCall(tool: "send_otp", args: args, command: command) { json in
            return [
                "success":   (json["success"] as? Bool) ?? false,
                "otpId":     json["otp_id"]     ?? NSNull(),
                "phone":     json["phone"]      ?? NSNull(),
                "expiresAt": json["expires_at"] ?? NSNull(),
                "expiresIn": json["expires_in"] ?? NSNull(),
                "error":     json["error"]      ?? NSNull(),
            ]
        }
    }

    @objc(verifyOtp:)
    func verifyOtp(_ command: CDVInvokedUrlCommand) {
        let o = (command.arguments.first as? [String: Any]) ?? [:]
        guard let phone = o["phone"] as? String else { return error(command, "phone required") }
        guard let code  = o["code"]  as? String else { return error(command, "code required") }
        mcpCall(tool: "verify_otp", args: ["phone": phone, "code": code], command: command) { json in
            return [
                "success":      (json["success"] as? Bool) ?? false,
                "verified":     json["verified"]      ?? NSNull(),
                "error":        json["error"]         ?? NSNull(),
                "attemptsLeft": json["attempts_left"] ?? NSNull(),
            ]
        }
    }

    // MARK: - Internal

    private func mcpCall(
        tool: String,
        args: [String: Any],
        command: CDVInvokedUrlCommand,
        map: @escaping ([String: Any]) -> [String: Any]
    ) {
        guard let key = self.apiKey else {
            return self.error(command, "SmsOtp not configured. Call SmsOtp.configure({ apiKey }) first.")
        }
        var merged = args
        merged["api_key"] = key
        let body: [String: Any] = [
            "jsonrpc": "2.0",
            "method":  "tools/call",
            "params":  ["name": tool, "arguments": merged],
            "id":      Int(Date().timeIntervalSince1970 * 1000)
        ]
        guard let payload = try? JSONSerialization.data(withJSONObject: body) else {
            return self.error(command, "serialization failed")
        }
        var req = URLRequest(url: self.baseURL)
        req.httpMethod = "POST"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        req.setValue("Bearer \(key)",    forHTTPHeaderField: "Authorization")
        req.setValue("cordova-plugin-sms-otp-send/1.0.0", forHTTPHeaderField: "User-Agent")
        req.httpBody = payload

        URLSession.shared.dataTask(with: req) { [weak self] data, _, err in
            guard let self = self else { return }
            if let err = err { return self.error(command, "HTTP error: \(err.localizedDescription)") }
            guard let data = data,
                  let env  = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            else { return self.error(command, "bad response") }
            if let e = env["error"] as? [String: Any], let msg = e["message"] as? String {
                return self.error(command, msg)
            }
            guard
                let result  = env["result"]   as? [String: Any],
                let content = result["content"] as? [[String: Any]],
                let first   = content.first,
                let text    = first["text"]   as? String,
                let inner   = text.data(using: .utf8),
                let parsed  = try? JSONSerialization.jsonObject(with: inner) as? [String: Any]
            else {
                let r = CDVPluginResult(status: .ok, messageAs: env["result"] as? [String: Any] ?? [:])
                self.commandDelegate.send(r, callbackId: command.callbackId)
                return
            }
            let r = CDVPluginResult(status: .ok, messageAs: map(parsed))
            self.commandDelegate.send(r, callbackId: command.callbackId)
        }.resume()
    }

    private func error(_ command: CDVInvokedUrlCommand, _ msg: String) {
        let r = CDVPluginResult(status: .error, messageAs: msg)
        self.commandDelegate.send(r, callbackId: command.callbackId)
    }
}
