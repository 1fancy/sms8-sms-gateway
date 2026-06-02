# send-sms-from-android — send SMS from any Android phone via CLI

[![npm version](https://img.shields.io/npm/v/send-sms-from-android)](https://www.npmjs.com/package/send-sms-from-android)
[![npm downloads](https://img.shields.io/npm/dm/send-sms-from-android)](https://www.npmjs.com/package/send-sms-from-android)
[![Node 18+](https://img.shields.io/badge/node-%E2%89%A518-339933)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send SMS from Android** without writing API code. This CLI routes through your own Android phone — real SIM as sender — and skips Twilio, A2P 10DLC paperwork, and per-message fees. $29/month flat for unlimited messages.

```bash
npx send-sms-from-android send +14155550100 "Hi from my Android phone"
```

---

## What this does

- **Send SMS from an Android phone** to any number — from any script, cron, CI job, AI agent, or shell
- **Generate, verify, and wait for OTP codes** — perfect for 2FA, signup flows, and integration tests
- **Read your inbox** programmatically
- **No CPaaS account** — your Android SIM is the sender

## Why send SMS from Android instead of Twilio

| | Twilio / Vonage / Plivo | Send-SMS-from-Android |
|---|---|---|
| Per-SMS cost (US) | $0.0075 – $0.04 | $0 (flat $29/mo) |
| A2P 10DLC paperwork | Required for any bulk SMS | Not required — P2P from real SIM |
| Sender ID | Short code or random LCN | Your real mobile number |
| Recipient reply rate | Drops near 0% on cold lists | Stays normal — they recognise the number |
| Setup time | Days (brand registration) | Minutes (pair Android + paste API key) |
| Cancel any time | Hard contracts | $29/mo, cancel anytime |

## Install

```bash
# Run without installing
npx send-sms-from-android send +14155550100 "Hi"

# Or install globally for the shorthand
npm install -g send-sms-from-android
send-sms-from-android send +14155550100 "Hi"
send-android-sms send +14155550100 "Hi"   # short alias
```

Node 18 or newer.

## Get started in 4 steps

1. **Sign up free** at [sms8.io](https://sms8.io) (5-day trial, no card)
2. **Download the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android)** on any Android phone, scan the QR code from your dashboard to pair
3. **Copy your API key** from [app.sms8.io/api.php](https://app.sms8.io/api.php)
4. **Send your first SMS:**
   ```bash
   export SMS8_API_KEY=sk_xxx
   send-sms-from-android send +14155550100 "It works!"
   ```

## What you can do

```bash
# Send SMS
send-sms-from-android send <phone> "<message>"

# Generate / verify / wait for OTP codes
send-sms-from-android otp send <phone>
send-sms-from-android otp verify <phone> <code>
send-sms-from-android otp wait <sender-phone> [--timeout=120]

# Read your inbox
send-sms-from-android inbox [--limit=25] [--received] [--sent] [--phone=+14155550100]

# Inspect your paired phones
send-sms-from-android devices

# Account / balance
send-sms-from-android balance
send-sms-from-android setup
```

### Pick which Android device or SIM sends each message

By default SMS8 uses your primary paired Android. To route through a specific phone
or SIM slot (on dual-SIM phones), or to broadcast across multiple phones:

```bash
# Pin a specific paired Android
send-sms-from-android send +14155550100 "Hi" --device-id=10700

# Pin device + SIM 2 (dual-SIM phone)
send-sms-from-android send +14155550100 "Hi" --device-id=10700 --sim-slot=2

# Explicit list (each entry: deviceID or deviceID|simSlot)
send-sms-from-android send +14155550100 "Hi" --devices=10700,10701|0

# Broadcast across every paired phone
send-sms-from-android send +14155550100 "Status update" --option=1

# Broadcast across every SIM of every paired phone
send-sms-from-android send +14155550100 "Status update" --option=2

# Round-robin: pick one random sender from the resolved list
send-sms-from-android send +14155550100 "Hi" --random-device
```

The same routing flags work on `otp send` and `otp wait`. Run
`send-sms-from-android devices` to list your device IDs.

### OTP send options

```bash
send-sms-from-android otp send +14155550100 --length=8 --expires-in=180
send-sms-from-android otp send +14155550100 --template="Your YourApp code: {code}"
```

### OTP verify

`otp verify` checks the most-recent unverified code for the phone. No device routing
needed (the code lives server-side):

```bash
send-sms-from-android otp verify +14155550100 482937
```

### OTP wait

`otp wait` watches incoming SMS on a paired Android and extracts the verification code
from the body. Pass the sender's phone or partial match:

```bash
CODE=$(send-sms-from-android otp wait +Google --contains="Google" --timeout=180)
```

## Common use cases

- **OTP / 2FA login** for SaaS and mobile apps
- **WooCommerce / Shopify order notifications**
- **Server / deploy alerts** to your real phone
- **CI integration tests** that need a real incoming SMS
- **Bulk reminders** for appointments, drip sequences, abandoned carts
- **Two-way conversations** — replies flow back via webhook

## AI / MCP integration

Want Claude Code, Cursor, or Windsurf to send SMS from your AI assistant? Use [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — the companion Model Context Protocol launcher. Same API key, zero extra setup.

```json
{
  "mcpServers": {
    "sms8": {
      "command": "npx",
      "args": ["-y", "sms8-mcp"],
      "env": { "SMS8_API_KEY": "sk_xxx" }
    }
  }
}
```

## Related packages

- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — same CLI under the SMS8 brand
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — MCP server for Claude / Cursor / Windsurf
- [`phone-sms-gateway`](https://www.npmjs.com/package/phone-sms-gateway) — phone-as-gateway brand of the CLI
- [`sms-otp-using-myphone`](https://www.npmjs.com/package/sms-otp-using-myphone) — OTP-focused brand

All four CLI packages share one codebase — pick whichever name fits your project's wording best.

## Links

- Android SMS gateway home: [sms8.io/android-sms-gateway](https://sms8.io/android-sms-gateway)
- Android app: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- Dashboard: [app.sms8.io](https://app.sms8.io)
- API docs: [sms8.io/sms-api-documentation](https://sms8.io/sms-api-documentation)
- MCP server: [mcp.sms8.io](https://mcp.sms8.io)
- GitHub: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
