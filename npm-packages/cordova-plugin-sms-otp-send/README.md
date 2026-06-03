# cordova-plugin-sms-otp-send — SMS + OTP for Cordova / Ionic, a free Twilio Verify alternative

[![npm version](https://img.shields.io/npm/v/cordova-plugin-sms-otp-send)](https://www.npmjs.com/package/cordova-plugin-sms-otp-send)
[![npm downloads](https://img.shields.io/npm/dm/cordova-plugin-sms-otp-send)](https://www.npmjs.com/package/cordova-plugin-sms-otp-send)
[![Cordova 10+](https://img.shields.io/badge/Cordova-10%2B-E8E8E8)](https://cordova.apache.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Cordova plugin** for **Ionic / Cordova** apps to send SMS and send/verify OTP codes on **iOS and Android** — through your own paired Android phone. No Twilio, no Vonage Verify, no MessageBird. No per-OTP fees, no markups, no A2P 10DLC. Free 5-day trial at **[sms8.io](https://sms8.io)**.

```js
await SmsOtp.configure({ apiKey: 'sk_xxx' });
await SmsOtp.sendOtp({ phone: '+14155550100' });
const { verified } = await SmsOtp.verifyOtp({ phone: '+14155550100', code: '482937' });
```

For **Capacitor** apps (the modern Ionic stack), use [capacitor-sms-otp-send-verify](https://www.npmjs.com/package/capacitor-sms-otp-send-verify).

---

## Install

```bash
cordova plugin add cordova-plugin-sms-otp-send
```

Or for Ionic Cordova projects:

```bash
ionic cordova plugin add cordova-plugin-sms-otp-send
```

The plugin's iOS Swift + Android Kotlin native code compiles into your app automatically.

## Quick start

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — 5-day trial, no card
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Configure once at app boot

```js
document.addEventListener('deviceready', async () => {
  await SmsOtp.configure({ apiKey: 'sk_xxx' });
}, false);
```

> **Production:** never embed your master API key in the app bundle. Proxy through your backend.

### 3. Send + verify

```js
async function loginFlow(phone) {
  await SmsOtp.sendOtp({ phone, length: 6 });
  const code = await promptUserForCode();
  const result = await SmsOtp.verifyOtp({ phone, code });
  if (result.verified) {
    navigateTo('home');
  } else {
    alert('Wrong code. Attempts left: ' + result.attemptsLeft);
  }
}
```

### 4. Plain SMS

```js
await SmsOtp.sendSms({ phone: '+14155550100', message: 'Order shipped!' });
```

## API

### `SmsOtp.configure({ apiKey, baseUrl? }): Promise<void>`

Must be called once before any send/verify.

### `SmsOtp.sendSms({ phone, message, deviceId?, simSlot? }): Promise<{ success, messageId, error }>`

Send a plain SMS through your paired Android phone.

### `SmsOtp.sendOtp({ phone, length?, template?, expiresIn?, maxAttempts?, deviceId?, simSlot? }): Promise<{ success, otpId, phone, expiresAt, expiresIn, error }>`

Send a verification code. Defaults: 6 digits, 5-minute expiry, 5 attempts allowed.

### `SmsOtp.verifyOtp({ phone, code }): Promise<{ success, verified, error, attemptsLeft }>`

Verify the code the user typed.

## iOS SMS auto-fill

When your Ionic UI uses standard HTML inputs with these attributes, iOS shows the "From Messages" suggestion above the keyboard automatically:

```html
<input type="text"
       inputmode="numeric"
       autocomplete="one-time-code"
       maxlength="6"
       pattern="[0-9]*" />
```

No extra plugin call needed — the attributes do the work.

## How it works

1. JS calls `SmsOtp.sendOtp` (or `sendSms`, `verifyOtp`)
2. Cordova bridge routes to native iOS Swift or Android Kotlin
3. Native code POSTs to `https://mcp.sms8.io` with your API key
4. SMS8 queues the message; your paired Android phone polls, sends via its real SIM, reports back

Typical round-trip: under 4 seconds.

## Why this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | This plugin |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required in the US | Not required — P2P from real SIM |
| Sender ID | Short code / random LCN | Your real mobile number |
| Free trial | None | 5 days unlimited, no card |

## Related packages

- [`capacitor-sms-otp-send-verify`](https://www.npmjs.com/package/capacitor-sms-otp-send-verify) — same flow for Capacitor (modern Ionic)
- [`react-sms-otp`](https://www.npmjs.com/package/react-sms-otp) — React hook + components for web
- [`sms-otp-verify`](https://www.npmjs.com/package/sms-otp-verify) — same as react-sms-otp, alt name
- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — terminal CLI
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude Code / Cursor / Windsurf

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **OTP API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **GitHub**: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
