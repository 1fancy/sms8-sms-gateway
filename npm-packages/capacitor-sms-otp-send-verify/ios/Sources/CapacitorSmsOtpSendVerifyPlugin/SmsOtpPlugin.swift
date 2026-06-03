//
//  SmsOtpPlugin.swift
//  Capacitor plugin: send and verify SMS OTP codes through your own Android
//  phone, via the SMS8 platform. Twilio Verify alternative.
//
//  Free 5-day trial at https://sms8.io
//

import Foundation
import Capacitor

@objc(SmsOtpPlugin)
public class SmsOtpPlugin: CAPPlugin, CAPBridgedPlugin {
    public let identifier = "SmsOtpPlugin"
    public let jsName = "SmsOtp"
    public let pluginMethods: [CAPPluginMethod] = [
        CAPPluginMethod(name: "configure",    returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "sendSms",      returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "sendOtp",      returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "verifyOtp",    returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "listDevices",  returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "getMessages",  returnType: CAPPluginReturnPromise),
        CAPPluginMethod(name: "getBalance",   returnType: CAPPluginReturnPromise),
    ]

    private var apiKey: String?
    private var baseURL: URL = URL(string: "https://mcp.sms8.io")!

    @objc func configure(_ call: CAPPluginCall) {
        guard let key = call.getString("apiKey"), !key.isEmpty else {
            call.reject("configure: apiKey is required. Get one free at https://sms8.io")
            return
        }
        self.apiKey = key
        if let base = call.getString("baseUrl"), let u = URL(string: base) {
            self.baseURL = u
        }
        call.resolve()
    }

    @objc func sendSms(_ call: CAPPluginCall) {
        guard let phone   = call.getString("phone") else { call.reject("phone required"); return }
        guard let message = call.getString("message") else { call.reject("message required"); return }
        var args: [String: Any] = ["phone": phone, "message": message]
        if let d = call.getInt("deviceId") { args["device_id"] = d }
        if let s = call.getString("simSlot") { args["sim_slot"] = s }
        mcpCall(tool: "send_sms", args: args, call: call) { json in
            return [
                "success":   (json["success"] as? Bool) ?? false,
                "messageId": json["message_id"] as Any,
                "phone":     json["phone"]     as Any,
                "error":     json["error"]     as Any,
            ]
        }
    }

    @objc func sendOtp(_ call: CAPPluginCall) {
        guard let phone = call.getString("phone") else { call.reject("phone required"); return }
        var args: [String: Any] = ["phone": phone]
        if let l = call.getInt("length")      { args["length"] = l }
        if let t = call.getString("template") { args["template"] = t }
        if let e = call.getInt("expiresIn")   { args["expires_in"] = e }
        if let m = call.getInt("maxAttempts") { args["max_attempts"] = m }
        if let d = call.getInt("deviceId")    { args["device_id"] = d }
        if let s = call.getString("simSlot")  { args["sim_slot"] = s }
        mcpCall(tool: "send_otp", args: args, call: call) { json in
            return [
                "success":   (json["success"] as? Bool) ?? false,
                "otpId":     json["otp_id"]     as Any,
                "phone":     json["phone"]      as Any,
                "expiresAt": json["expires_at"] as Any,
                "expiresIn": json["expires_in"] as Any,
                "error":     json["error"]      as Any,
            ]
        }
    }

    @objc func verifyOtp(_ call: CAPPluginCall) {
        guard let phone = call.getString("phone") else { call.reject("phone required"); return }
        guard let code  = call.getString("code")  else { call.reject("code required");  return }
        mcpCall(tool: "verify_otp", args: ["phone": phone, "code": code], call: call) { json in
            return [
                "success":      (json["success"]  as? Bool) ?? false,
                "verified":     json["verified"]      as Any,
                "error":        json["error"]         as Any,
                "attemptsLeft": json["attempts_left"] as Any,
            ]
        }
    }

    @objc func listDevices(_ call: CAPPluginCall) {
        mcpCall(tool: "list_devices", args: [:], call: call) { json in
            return [
                "success": (json["success"] as? Bool) ?? false,
                "count":   json["count"]   ?? 0,
                "devices": json["devices"] ?? [],
                "error":   json["error"]   as Any,
            ]
        }
    }

    @objc func getMessages(_ call: CAPPluginCall) {
        var args: [String: Any] = [
            "direction": call.getString("direction") ?? "all",
            "limit":     call.getInt("limit") ?? 25
        ]
        if let p = call.getString("phone") { args["phone"] = p }
        mcpCall(tool: "get_messages", args: args, call: call) { json in
            return [
                "success":  (json["success"]  as? Bool) ?? false,
                "count":    json["count"]    ?? 0,
                "messages": json["messages"] ?? [],
                "error":    json["error"]    as Any,
            ]
        }
    }

    @objc func getBalance(_ call: CAPPluginCall) {
        mcpCall(tool: "get_balance", args: [:], call: call) { json in
            return [
                "success":   (json["success"] as? Bool) ?? false,
                "credits":   json["credits"]    ?? NSNull(),
                "unlimited": (json["unlimited"] as? Bool) ?? false,
                "expiresAt": json["expires_at"] as Any,
                "daysLeft":  json["days_left"]  as Any,
                "summary":   json["summary"]    as Any,
                "error":     json["error"]      as Any,
            ]
        }
    }

    // MARK: - JSON-RPC over HTTPS

    private func mcpCall(
        tool: String,
        args: [String: Any],
        call: CAPPluginCall,
        map: @escaping ([String: Any]) -> [String: Any]
    ) {
        guard let key = self.apiKey else {
            call.reject("SmsOtp not configured. Call SmsOtp.configure({ apiKey }) first.")
            return
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
            call.reject("Failed to serialize request")
            return
        }
        var req = URLRequest(url: self.baseURL)
        req.httpMethod = "POST"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        req.setValue("Bearer \(key)",    forHTTPHeaderField: "Authorization")
        req.setValue("capacitor-sms-otp-send-verify/1.0.0", forHTTPHeaderField: "User-Agent")
        req.httpBody = payload

        URLSession.shared.dataTask(with: req) { data, response, error in
            if let error = error {
                call.reject("HTTP error: \(error.localizedDescription)")
                return
            }
            guard let data = data,
                  let env  = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            else {
                call.reject("Bad response")
                return
            }
            if let err = env["error"] as? [String: Any], let msg = err["message"] as? String {
                call.reject(msg)
                return
            }
            guard
                let result  = env["result"]   as? [String: Any],
                let content = result["content"] as? [[String: Any]],
                let first   = content.first,
                let text    = first["text"]   as? String,
                let inner   = text.data(using: .utf8),
                let parsed  = try? JSONSerialization.jsonObject(with: inner) as? [String: Any]
            else {
                call.resolve(env["result"] as? [String: Any] ?? [:])
                return
            }
            call.resolve(map(parsed))
        }.resume()
    }
}
