# SMS8 CLI — send SMS & OTP from your terminal via your own Android phone

[![npm version](https://img.shields.io/npm/v/sms8-cli)](https://www.npmjs.com/package/sms8-cli)
[![npm downloads](https://img.shields.io/npm/dm/sms8-cli)](https://www.npmjs.com/package/sms8-cli)
[![Node 18+](https://img.shields.io/badge/node-%E2%89%A518-339933)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**`sms8`** is a zero-dependency CLI that sends SMS, generates and verifies OTP codes, and reads your SMS inbox — routed through **your own Android phone** instead of Twilio. One command. No CPaaS account. No A2P 10DLC paperwork. $29/month flat for unlimited messages.

```bash
npx sms8-cli send +14155550100 "Welcome aboard!"
```

That's it. The SMS leaves your real mobile number and arrives in the recipient's regular SMS app.

---

## Why use `sms8-cli`

- **Send SMS from any script, cron, CI job, or shell** without writing API code
- **No Twilio, Vonage, MessageBird, or Plivo** account required
- **Real phone number** as sender — recipients can reply directly to you
- **No A2P 10DLC** paperwork (US) — these are person-to-person texts from a real SIM
- **Free 5-day trial**, then $29/mo unlimited — no per-SMS fees, no overages
- Built on the [SMS8 platform](https://sms8.io)

## Install

```bash
# One-off use
npx sms8-cli send +14155550100 "Hello"

# Or install globally
npm install -g sms8-cli
sms8 send +14155550100 "Hello"
```

Node 18 or newer.

## Get an API key

1. Sign up free at [sms8.io](https://sms8.io) (5-day trial, no card required)
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

Set it once:

```bash
export SMS8_API_KEY=sk_xxx
# or
sms8 config set api_key=sk_xxx
```

## Commands

### Send an SMS

```bash
sms8 send +14155550100 "Order #1234 shipped — track at example.com/t/1234"
```

#### Route through a specific device or SIM

By default SMS8 picks your primary paired Android. To pin a specific phone or SIM slot:

```bash
# Specific device
sms8 send +14155550100 "Hi" --device-id=10700

# Device + SIM 2 (dual-SIM Android)
sms8 send +14155550100 "Hi" --device-id=10700 --sim-slot=2

# Explicit list (each entry is deviceID or deviceID|simSlot)
sms8 send +14155550100 "Hi" --devices=10700,10701|0

# Broadcast across all paired devices
sms8 send +14155550100 "Status update" --option=1

# Broadcast across all SIMs of all paired devices
sms8 send +14155550100 "Status update" --option=2

# Pick a random device from the resolved list (load-balancing)
sms8 send +14155550100 "Hi" --random-device
```

Run `sms8 devices` to see your paired devices and their IDs.

### Send a verification code (OTP)

```bash
sms8 otp send +14155550100
# → 6-digit code arrives on the user's phone
```

OTP options (length, expiry, template, routing — all optional):

```bash
sms8 otp send +14155550100 --length=8 --expires-in=180
sms8 otp send +14155550100 --template="Your YourApp code: {code}"
sms8 otp send +14155550100 --device-id=10700 --sim-slot=2
```

### Verify a code

```bash
sms8 otp verify +14155550100 482937
# → { "verified": true }
```

`verify_otp` checks the most-recent unverified code for that phone — no device routing
needed because the code lives server-side, not on a specific SIM.

### Block until a code arrives (perfect for tests, automation)

`wait` watches **incoming SMS** on a paired Android and pulls out the verification code.
Pass the **sender's phone or shortcode** (or part of it):

```bash
# Wait for any code sent from a Google number
CODE=$(sms8 otp wait +Google --timeout=180 --contains="Google")

# Wait for a code from a specific E.164 number, only on device 10700
CODE=$(sms8 otp wait +12025550100 --timeout=120 --device-id=10700)

# 8-digit code only (some banks)
CODE=$(sms8 otp wait +YourBank --code-min-length=8 --code-max-length=8 --timeout=300)

echo "Got code: $CODE"
```

### Inbox

```bash
sms8 inbox --limit=10
sms8 inbox --received --limit=20
sms8 inbox --sent --limit=20
sms8 inbox --phone=+14155550100   # filter to one conversation
```

### Devices

```bash
sms8 devices   # shows IDs to use with --device-id
```

### Account / balance

```bash
sms8 balance
sms8 setup
```

## Example: Bash OTP login flow

```bash
read -p "Phone (E.164): " PHONE
sms8 otp send "$PHONE"
read -p "Code: " CODE
if sms8 otp verify "$PHONE" "$CODE" | grep -q '"verified": true'; then
  echo "Welcome!"
else
  echo "Wrong code."
fi
```

## Example: CI test that waits for a real SMS

```bash
# Trigger a transactional SMS in your app
curl -X POST https://your.app/trigger-otp -d 'phone=+14155550100'

# Wait for the code to arrive on the test SIM
CODE=$(sms8 otp wait +14155550100 --timeout=180)
echo "Code received: $CODE"

# Submit it to your app
curl -X POST https://your.app/verify -d "phone=+14155550100&code=$CODE"
```

## Use cases

- **OTP / 2FA login** in CLI tools, scripts, integration tests
- **Order notifications** from e-commerce cron jobs
- **Status alerts** from servers, CI, deployments
- **Lead notifications** to your sales team's real number
- **Bulk reminders** — paste a phone list into a shell loop

## How it works

Your Android phone runs the free [SMS8 app](https://sms8.io/sms-gateway-apk-android) and stays online. Each `sms8` command POSTs to the SMS8 cloud, which queues the message; your phone polls, sends the SMS over its real SIM, and reports back. Round-trip is typically under 4 seconds.

## What about MCP / Claude Code / Cursor?

Want your AI assistant to send SMS directly? Use the companion package [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — it exposes the same tools to any Model Context Protocol client.

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

Full docs at [mcp.sms8.io](https://mcp.sms8.io).

## Links

- Home: [sms8.io](https://sms8.io)
- Dashboard: [app.sms8.io](https://app.sms8.io)
- API docs: [sms8.io/sms-api-documentation](https://sms8.io/sms-api-documentation)
- MCP server: [mcp.sms8.io](https://mcp.sms8.io)
- Android app: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- Issues: [github.com/1fancy/sms8-sms-gateway/issues](https://github.com/1fancy/sms8-sms-gateway/issues)

## License

MIT
