# SMS8 iOS SDK — Swift SMS & OTP, a free Twilio Verify alternative

[![Swift 5.9+](https://img.shields.io/badge/Swift-5.9%2B-FA7343)](https://swift.org)
[![iOS 13+](https://img.shields.io/badge/iOS-13%2B-000)](https://developer.apple.com/ios/)
[![Swift Package Manager](https://img.shields.io/badge/SPM-supported-brightgreen)](https://swift.org/package-manager/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send SMS, send and verify OTP codes from your iOS app** without Twilio, Vonage Verify, or MessageBird. Routes through your own paired Android phone via the SMS8 platform. **No per-OTP fees, no markups, no A2P 10DLC.**

```swift
import SMS8

let sms8 = SMS8(apiKey: "sk_xxx")
let sent   = try await sms8.sendOTP(phone: "+14155550100")
let result = try await sms8.verifyOTP(phone: "+14155550100", code: "482937")
if result.verified == true { /* allow login */ }
```

Free 5-day trial at **[sms8.io](https://sms8.io)** — no credit card.

---

## Why this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | SMS8 iOS SDK |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 each | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required in the US | Not required — P2P SMS from a real SIM |
| Sender ID | Short code or random LCN | Your real mobile number |
| Setup time | Days | Minutes |
| Free trial | None — pay from message 1 | 5 days unlimited, no card required |

## Install with Swift Package Manager

### Xcode

1. **File → Add Packages…**
2. Paste: `https://github.com/1fancy/sms8-swift`
3. Select version `From: 1.0.0` and add to your target.

### Package.swift

```swift
dependencies: [
    .package(url: "https://github.com/1fancy/sms8-swift", from: "1.0.0")
],
targets: [
    .target(name: "MyApp", dependencies: ["SMS8"])
]
```

## Quick start

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — 5-day trial, no card
2. Pair your Android phone via the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android)
3. Copy your key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Send an OTP

```swift
import SMS8

let sms8 = SMS8(apiKey: "sk_xxx")

let result = try await sms8.sendOTP(phone: "+14155550100")
print("OTP id:", result.otpId ?? -1, "expires at:", result.expiresAt ?? "")
```

### 3. Verify the code

```swift
let v = try await sms8.verifyOTP(phone: "+14155550100", code: "482937")
if v.verified == true {
    // Code matched — proceed.
} else {
    print("Failed:", v.error ?? "unknown", "attempts left:", v.attemptsLeft ?? 0)
}
```

### 4. Send a plain SMS

```swift
try await sms8.sendSMS(phone: "+14155550100", message: "Welcome aboard!")
```

## iOS SMS auto-fill — "From Messages" keyboard suggestion

iOS detects 4-8 digit codes in the latest SMS and offers them as a keyboard suggestion when your input has `textContentType = .oneTimeCode`. The SDK ships a ready-made input:

### SwiftUI

```swift
import SwiftUI
import SMS8

struct VerifyView: View {
    let phone: String
    @State var code = ""
    @State var verified = false

    var body: some View {
        VStack {
            OTPTextField(code: $code, length: 6) { fullCode in
                Task {
                    let r = try await SMS8(apiKey: ApiConfig.smsKey)
                        .verifyOTP(phone: phone, code: fullCode)
                    verified = r.verified == true
                }
            }
            if verified { Text("Verified ✓").foregroundColor(.green) }
        }
    }
}
```

### UIKit

If you already have a `UITextField`, one call wires up auto-fill:

```swift
SMS8.enableOTPAutoFill(on: codeTextField)
```

> **Tip:** for the iOS suggestion to appear, the code must be in an SMS the user received from any phone number in the last 3 minutes. SMS8's `send_otp` sends a real SMS through your paired Android, so it just works.

## API reference

### `SMS8(apiKey:baseURL:session:)`

Creates a client. `baseURL` defaults to `https://mcp.sms8.io` (rarely needs override). `session` defaults to `.shared`.

### `sendSMS(phone:message:deviceId:simSlot:)`

Send a plain SMS. `deviceId` pins a specific paired Android; `simSlot` picks a SIM on multi-SIM phones.

### `sendOTP(phone:length:template:expiresIn:maxAttempts:deviceId:simSlot:)`

Send a verification code. Defaults: 6 digits, 5-minute expiry, 5 attempts. Customise the SMS body with `template: "Your code: {code}"`.

### `verifyOTP(phone:code:)`

Verify the code the user typed. The server checks the most-recent unverified code for that phone — no device routing needed.

## Recommended pattern: keep the API key OFF the device

Like Twilio, you should not ship your master API key in an iOS app. Instead, call your backend, which calls SMS8.

```swift
// iOS app code — calls YOUR backend, not SMS8 directly
let res = try await URLSession.shared.data(
    for: URLRequest.post("https://your.app/api/send-otp", json: ["phone": phone])
)
```

```swift
// Your backend (Vapor, Hummingbird, Express, FastAPI, etc.)
let sms8 = SMS8(apiKey: ProcessInfo.processInfo.environment["SMS8_API_KEY"]!)
return try await sms8.sendOTP(phone: phone)
```

For development / prototyping, embedding the key is fine.

## Use cases

- **Phone-verified signup / login**
- **2FA on sensitive actions** (password change, payment, account deletion)
- **Account recovery** without email magic links
- **Trusted-device enrollment** for biometric chains
- **In-app purchase confirmation** for high-value items

## Companion packages

- [react-sms-otp](https://www.npmjs.com/package/react-sms-otp) — React hooks + components
- [sms8-cli](https://www.npmjs.com/package/sms8-cli) — terminal CLI
- [sms8-android](https://github.com/1fancy/sms8-android) — Android Kotlin SDK
- [sms8-mcp](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude Code, Cursor, Windsurf

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **OTP API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **GitHub**: [github.com/1fancy/sms8-swift](https://github.com/1fancy/sms8-swift)

## License

MIT
