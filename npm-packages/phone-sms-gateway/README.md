# phone-sms-gateway — turn your Android phone into a free SMS gateway

[![npm version](https://img.shields.io/npm/v/phone-sms-gateway)](https://www.npmjs.com/package/phone-sms-gateway)
[![npm downloads](https://img.shields.io/npm/dm/phone-sms-gateway)](https://www.npmjs.com/package/phone-sms-gateway)
[![Node 18+](https://img.shields.io/badge/node-%E2%89%A518-339933)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A **phone SMS gateway** in your terminal. Send SMS, generate and verify OTP codes, read your inbox — all routed through **your own Android phone** instead of paying Twilio, Vonage, MessageBird, or Plivo.

```bash
npx phone-sms-gateway send +14155550100 "Hello from my phone"
```

The message leaves your real mobile number and arrives in the recipient's regular SMS app. **No CPaaS account. No per-SMS fees. No A2P 10DLC paperwork.**

---

## Why a phone SMS gateway?

CPaaS providers charge **$0.0075 to $0.04 per SMS** in the US, force you through A2P 10DLC registration, and route messages from short codes recipients don't recognise. Reply rates collapse.

A **phone-based SMS gateway** routes through the Android phone you already own. Messages arrive from your real local number. Recipients **reply directly to you**. And the cost is **$29/month flat** for unlimited SMS through one device.

This package gives you a CLI for that flow.

## Install

```bash
# One-off
npx phone-sms-gateway send +14155550100 "Hi"

# Or install globally
npm install -g phone-sms-gateway
phone-sms-gateway send +14155550100 "Hi"
# alias also installed:
sms-gateway send +14155550100 "Hi"
```

Node 18 or newer.

## Quick start

1. **Sign up free** at [sms8.io](https://sms8.io) (5-day trial, no card)
2. **Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android)** on any Android phone, pair it with your account
3. **Grab your API key** from [app.sms8.io/api.php](https://app.sms8.io/api.php)
4. **Set the key** once:
   ```bash
   export SMS8_API_KEY=sk_xxx
   ```
5. **Send your first SMS:**
   ```bash
   phone-sms-gateway send +14155550100 "It works!"
   ```

## Commands

```bash
# SMS
phone-sms-gateway send <phone> "<message>"

# OTP / 2FA
phone-sms-gateway otp send <phone>
phone-sms-gateway otp verify <phone> <code>
phone-sms-gateway otp wait <phone> [--timeout=120]

# Inbox
phone-sms-gateway inbox [--limit=25] [--received] [--sent]

# Devices
phone-sms-gateway devices

# Account
phone-sms-gateway balance
phone-sms-gateway setup
```

## Example: send notifications from a cron job

```bash
# /etc/cron.hourly/server-watchdog
LOAD=$(uptime | awk -F'load average:' '{print $2}')
phone-sms-gateway send +14155550100 "Server load: $LOAD" --api-key=$SMS8_API_KEY
```

## Example: OTP loop in CI

```bash
# Trigger your app's OTP send
curl -X POST https://staging.app.com/otp -d 'phone=+14155550100'

# Block until the SMS arrives on the test SIM
CODE=$(phone-sms-gateway otp wait +14155550100 --timeout=180)

# Hand it back to the app
curl -X POST https://staging.app.com/verify -d "code=$CODE"
```

## What's under the hood

This package shares its codebase with [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) and connects to the SMS8 SMS-gateway platform. Pick whichever package name reads better in your toolchain — they expose the same `bin/sms8.js`.

For an AI / MCP integration (Claude Code, Cursor, Windsurf), see [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp).

## Links

- Phone SMS gateway home: [sms8.io](https://sms8.io)
- Android app: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- Dashboard: [app.sms8.io](https://app.sms8.io)
- API docs: [sms8.io/sms-api-documentation](https://sms8.io/sms-api-documentation)
- GitHub: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
