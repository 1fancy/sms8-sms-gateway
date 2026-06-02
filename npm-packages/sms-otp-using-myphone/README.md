# sms-otp-using-myphone — send and verify SMS OTP codes using your own phone

[![npm version](https://img.shields.io/npm/v/sms-otp-using-myphone)](https://www.npmjs.com/package/sms-otp-using-myphone)
[![npm downloads](https://img.shields.io/npm/dm/sms-otp-using-myphone)](https://www.npmjs.com/package/sms-otp-using-myphone)
[![Node 18+](https://img.shields.io/badge/node-%E2%89%A518-339933)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send SMS OTP codes using your own phone.** No Twilio. No Vonage. No per-OTP fee. Pair your Android phone with the SMS8 cloud and generate, send, and verify one-time codes from any script, server, CI job, or AI agent.

```bash
npx sms-otp-using-myphone send +14155550100
# → 6-digit code arrives via your real phone number
```

---

## Why use your own phone for SMS OTP

CPaaS providers (Twilio Verify, Vonage Verify, MessageBird Verify) charge **$0.05 to $0.10 per OTP attempt** and require A2P 10DLC registration in the US. At any meaningful signup volume that's hundreds of dollars per month before you've shipped a single feature.

This package routes OTP SMS through the Android phone **you already own**:

- **Codes arrive from your real phone number** — recipients trust the sender
- **$29/month flat unlimited** instead of per-OTP fees
- **No A2P 10DLC** paperwork — these are P2P SMS from a real SIM
- **Built-in OTP store** — server tracks code, expiry, attempts, resend cooldown
- **`wait` command** — block until the next code arrives on your test SIM

## Install

```bash
# One-off
npx sms-otp-using-myphone send +14155550100

# Or install globally
npm install -g sms-otp-using-myphone
sms-otp send +14155550100
```

Node 18 or newer.

## Quick start

1. **Sign up free** at [sms8.io](https://sms8.io) (5-day trial, no card)
2. **Pair an Android phone** via the [SMS8 app](https://sms8.io/sms-gateway-apk-android)
3. **Grab your API key** from [app.sms8.io/api.php](https://app.sms8.io/api.php)
4. **Set the key once:**
   ```bash
   export SMS8_API_KEY=sk_xxx
   ```

## Send / verify / wait

```bash
# Send a code (defaults: 6 digits, 5 min expiry, 5 attempts, 60s resend cooldown)
sms-otp-using-myphone otp send +14155550100

# Verify a code the user typed in
sms-otp-using-myphone otp verify +14155550100 482937
# → { "verified": true }

# Block until an SMS code arrives on a paired Android (handy in tests)
# Pass the sender phone (or partial match) — e.g. the bank or Google
CODE=$(sms-otp-using-myphone otp wait +Google --contains="Google" --timeout=180)
echo "Got: $CODE"
```

> The shorter alias `sms-otp` works for every command shown above
> (e.g. `sms-otp otp send +1234`).

## OTP send options

Tune length, expiry, template, and attempt limit at send time:

```bash
sms-otp-using-myphone otp send +14155550100 --length=8 --expires-in=180
sms-otp-using-myphone otp send +14155550100 --template="Your YourApp code: {code}"
sms-otp-using-myphone otp send +14155550100 --max-attempts=3
```

## Route through a specific device or SIM

By default SMS8 sends through your primary paired Android. To pin a specific
phone or SIM slot (dual-SIM Androids), or to broadcast:

```bash
sms-otp-using-myphone otp send +14155550100 --device-id=10700
sms-otp-using-myphone otp send +14155550100 --device-id=10700 --sim-slot=2
sms-otp-using-myphone otp send +14155550100 --devices=10700,10701|0
sms-otp-using-myphone otp send +14155550100 --option=1     # broadcast all devices
sms-otp-using-myphone otp send +14155550100 --option=2     # broadcast all SIMs
sms-otp-using-myphone otp send +14155550100 --random-device
```

The same routing flags work on `otp wait` (which Android device & SIM should watch
for the incoming code). Run `sms-otp-using-myphone devices` to list device IDs.

`otp verify` does not take routing flags — the code lives server-side, not on a SIM.

## Example: end-to-end login flow

```bash
#!/usr/bin/env bash
read -p "Phone (E.164): " PHONE
sms-otp-using-myphone otp send "$PHONE"
read -p "Code: " CODE
if sms-otp-using-myphone otp verify "$PHONE" "$CODE" | grep -q '"verified": true'; then
  echo "Welcome!"
else
  echo "Wrong code. Try again."
fi
```

## Example: CI signup test

```bash
# Trigger your app to send an OTP
curl -X POST https://staging.your-app.com/otp -d 'phone=+14155550100'

# Block until the code arrives on the test SIM (max 3 minutes)
CODE=$(sms-otp-using-myphone wait +14155550100 --timeout=180)

# Submit it to your app
curl -X POST https://staging.your-app.com/verify -d "phone=+14155550100&code=$CODE"
```

## Defaults

| Setting | Default |
|---|---|
| Code length | 6 digits |
| Expiry | 5 minutes |
| Max attempts | 5 |
| Resend cooldown | 60 seconds |
| Per-phone cap | 5 OTPs per 24 h |

Configurable via the API. See the [OTP API docs](https://sms8.io/sms-otp-verification-api-android).

## Pairs nicely with

- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — full SMS + OTP CLI (same backend)
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — let Claude / Cursor / Windsurf send OTPs through your AI agent
- [`phone-sms-gateway`](https://www.npmjs.com/package/phone-sms-gateway) — phone-as-gateway brand

## Links

- OTP API docs: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- Marketing home: [sms8.io](https://sms8.io)
- Android app: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- Dashboard: [app.sms8.io](https://app.sms8.io)
- MCP server: [mcp.sms8.io](https://mcp.sms8.io)
- GitHub: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
