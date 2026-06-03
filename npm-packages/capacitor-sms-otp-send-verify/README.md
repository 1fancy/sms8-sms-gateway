# capacitor-sms-otp-send-verify — SMS + OTP for Ionic / Capacitor, a free Twilio Verify alternative

[![npm version](https://img.shields.io/npm/v/capacitor-sms-otp-send-verify)](https://www.npmjs.com/package/capacitor-sms-otp-send-verify)
[![npm downloads](https://img.shields.io/npm/dm/capacitor-sms-otp-send-verify)](https://www.npmjs.com/package/capacitor-sms-otp-send-verify)
[![Capacitor 5+](https://img.shields.io/badge/Capacitor-5%2B-119EFF)](https://capacitorjs.com/)
[![iOS 13+](https://img.shields.io/badge/iOS-13%2B-000)](https://developer.apple.com/ios/)
[![Android 22+](https://img.shields.io/badge/Android-22%2B-3DDC84)](https://developer.android.com/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Capacitor plugin** to send SMS, send and verify OTP codes from your **Ionic / Capacitor** app on **iOS and Android** — routed through **your own Android phone**. No Twilio, no Vonage Verify, no MessageBird. No per-OTP fees, no markups, no A2P 10DLC. Free 5-day trial at **[sms8.io](https://sms8.io)** — no card.

```ts
import { SmsOtp } from 'capacitor-sms-otp-send-verify';

await SmsOtp.configure({ apiKey: 'sk_xxx' });
await SmsOtp.sendOtp({ phone: '+14155550100' });
const { verified } = await SmsOtp.verifyOtp({ phone: '+14155550100', code: '482937' });
```

---

## Why this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | Capacitor SMS8 plugin |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 each | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required in the US | Not required — P2P SMS from a real SIM |
| Sender ID | Short code / random LCN | Your real mobile number |
| Setup time | Days | Minutes |
| Native iOS Swift + Android Kotlin | Yes (separate SDKs) | Yes (one plugin) |
| Free trial | None | 5 days unlimited, no card |

## Install

```bash
npm install capacitor-sms-otp-send-verify
npx cap sync
```

Then in your Capacitor project:

```bash
npx cap copy ios
npx cap copy android
```

Done. The plugin's native iOS Swift and Android Kotlin bridges are compiled into your app automatically.

## Quick start

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — 5-day trial, no card
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Configure the plugin

Call `configure` once, typically in your app's bootstrap or before the first send:

```ts
import { SmsOtp } from 'capacitor-sms-otp-send-verify';

await SmsOtp.configure({
  apiKey: process.env.SMS8_API_KEY,
  // baseUrl: 'https://mcp.sms8.io' (default, rarely needs override)
});
```

> **Production:** never embed your master API key in the app bundle. Proxy through your backend and pass a short-lived token to the plugin.

### 3. Send an OTP

```ts
const result = await SmsOtp.sendOtp({
  phone: '+14155550100',
  length: 6,                                          // default 6
  template: 'Your YourApp code: {code}',              // optional
  expiresIn: 300,                                     // default 300s
  maxAttempts: 5,                                     // default 5
});
console.log('OTP id:', result.otpId, 'expires:', result.expiresAt);
```

### 4. Verify the code the user typed

```ts
const r = await SmsOtp.verifyOtp({ phone: '+14155550100', code: '482937' });
if (r.verified) {
  // Welcome the user
} else {
  // r.error, r.attemptsLeft
}
```

### 5. Send a plain SMS

```ts
await SmsOtp.sendSms({ phone: '+14155550100', message: 'Order shipped!' });
```

## Full Ionic example with auto-fill

```tsx
import { IonInput, IonButton } from '@ionic/react';
import { useState } from 'react';
import { SmsOtp } from 'capacitor-sms-otp-send-verify';

export function VerifyPhone() {
  const [phone, setPhone] = useState('');
  const [code, setCode]   = useState('');
  const [stage, setStage] = useState<'phone' | 'code'>('phone');

  return stage === 'phone' ? (
    <>
      <IonInput
        type="tel"
        autocomplete="tel"
        value={phone}
        onIonChange={(e) => setPhone(e.detail.value!)}
        placeholder="+14155550100"
      />
      <IonButton onClick={async () => {
        await SmsOtp.sendOtp({ phone });
        setStage('code');
      }}>Send code</IonButton>
    </>
  ) : (
    <>
      <IonInput
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength={6}
        value={code}
        onIonChange={(e) => setCode(e.detail.value!)}
      />
      <IonButton onClick={async () => {
        const { verified } = await SmsOtp.verifyOtp({ phone, code });
        if (verified) navigate('/dashboard');
      }}>Verify</IonButton>
    </>
  );
}
```

The `autocomplete="one-time-code"` + `inputmode="numeric"` attributes activate iOS "From Messages" keyboard suggestion and Android SMS Retriever auto-fill automatically.

## API

### `configure(options): Promise<void>`

| Option | Type | Default | Notes |
|---|---|---|---|
| `apiKey` | `string` | — | Required. Get one free at https://sms8.io |
| `baseUrl` | `string` | `https://mcp.sms8.io` | Override server (self-hosted MCP) |

### `sendSms(options): Promise<SendResult>`

| Option | Type | Notes |
|---|---|---|
| `phone` | `string` | E.164 phone number |
| `message` | `string` | SMS body |
| `deviceId?` | `number` | Pin a specific paired Android |
| `simSlot?` | `string` | Pick a SIM slot (multi-SIM) |

Returns `{ success, messageId, phone, error }`.

### `sendOtp(options): Promise<SendOtpResult>`

| Option | Type | Default | Notes |
|---|---|---|---|
| `phone` | `string` | — | E.164 phone |
| `length` | `number` | 6 | Digits in the code (4-8) |
| `template` | `string` | "Your verification code is {code}" | Use `{code}` placeholder |
| `expiresIn` | `number` | 300 | Seconds until expiry |
| `maxAttempts` | `number` | 5 | Verification attempts allowed |
| `deviceId?` | `number` | — | Pin a paired Android |
| `simSlot?` | `string` | — | Pick a SIM |

Returns `{ success, otpId, phone, expiresAt, expiresIn, error }`.

### `verifyOtp(options): Promise<VerifyOtpResult>`

| Option | Type | Notes |
|---|---|---|
| `phone` | `string` | Same phone you sent the OTP to |
| `code` | `string` | Code the user typed |

Returns `{ success, verified, error, attemptsLeft }`.

## How it works

When you call `SmsOtp.sendOtp` from your Ionic/Capacitor JavaScript code:

1. **On iOS** → native Swift bridge in `ios/Sources/...` runs `URLSession` against `https://mcp.sms8.io`
2. **On Android** → native Kotlin bridge in `android/src/...` runs `HttpURLConnection` against `https://mcp.sms8.io`
3. **In the browser** → web fallback in `dist/esm/web.js` runs `fetch` against `https://mcp.sms8.io`

In all 3 cases the SMS8 backend queues the message, your paired Android phone polls SMS8, sends the SMS through its real SIM, and reports back. Typical round-trip: under 4 seconds.

## Related packages

- [`cordova-plugin-sms-otp-send`](https://www.npmjs.com/package/cordova-plugin-sms-otp-send) — same flow for Cordova
- [`react-sms-otp`](https://www.npmjs.com/package/react-sms-otp) — React hook + components for web
- [`sms-otp-verify`](https://www.npmjs.com/package/sms-otp-verify) — same as react-sms-otp, alt name
- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — terminal CLI
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude Code / Cursor / Windsurf

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **OTP API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **Android app**: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- **GitHub**: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
