---
description: Send SMS messages, OTPs, and configure webhooks through the SMS8 gateway. Use when the user wants to send a text message, set up phone verification, register a webhook for inbound SMS, or check their SMS inbox.
---

# SMS8 — Send SMS, OTPs, and configure webhooks

The user has the SMS8 MCP server installed. You can call seven tools to interact with their SMS account, which is powered by their own paired Android phone.

## When to use this skill

Trigger this skill whenever the user asks to:

- Send a text message / SMS
- Set up phone-number verification (OTP / 2FA)
- Verify a code the user typed
- Register a webhook so their app receives inbound SMS
- Show recent SMS messages from their inbox
- List paired devices (phones)
- Check that SMS8 is configured

## Available tools

| Tool | When to call it |
|---|---|
| `setup_sms8` | First time — validates the API key, returns devices + config the user can paste into their `.env` |
| `send_sms` | Single SMS to one number. Returns the message ID. |
| `send_otp` | Generate + send a verification code. 5-minute expiry, 5 attempts default. |
| `verify_otp` | Compare a typed code against the most recent OTP issued to that phone |
| `get_messages` | Recent inbox/sent — for delivery checks and reply flows |
| `list_devices` | Inspect paired phones, pick a sender device |
| `create_webhook` | Register a callback URL for inbound SMS + delivery events |

## Phone format

Always pass phone numbers in **E.164 format**: `+` followed by country code + national number, no spaces or dashes. Example: `+14155551234`. If the user gives you a domestic-format number, ask them for the country.

## Typical flows

### Sending a single SMS

```
Call: send_sms
Args: { phone: "+14155551234", message: "Hello from Claude" }
```

If `list_devices` returns no devices, the user hasn't paired a phone yet — direct them to <https://app.sms8.io/mcp-setup.php> for the pairing QR.

### Adding phone verification to a sign-up form

1. On sign-up submit → call `send_otp` with the user's phone number.
2. Show a "code entry" UI to the user.
3. On code submit → call `verify_otp` with the same phone + the typed code.
4. If `verified: true`, mark the phone confirmed and continue.
5. If `verified: false`, check `reason` — `code_mismatch` shows remaining attempts; `expired` or `max_attempts` means start over with a fresh `send_otp`.

### Receiving inbound SMS

1. Build an HTTPS endpoint in the user's app that accepts JSON POSTs.
2. Call `create_webhook` with the endpoint URL.
3. SMS8 will POST inbound SMS + delivery events to that URL. Each request is signed via `X-SMS8-Signature` (HMAC-SHA256). Verify the signature in the handler.
4. Sample payload schema is returned from `create_webhook` — scaffold the handler from that.

## Auth

The Bearer token is already configured in the MCP server's transport. You do not need to pass an `api_key` argument in tool calls — the server reads it from the Authorization header. If a tool returns "Missing api_key" or "Invalid api_key", the user needs to update their `SMS8_API_KEY` environment variable and restart Claude Code.

## When the user asks "what can SMS8 do?"

Call `setup_sms8` first — it returns a structured summary with their account info, device list, code samples, and dashboard links. Show the highlights from that response rather than re-listing capabilities from memory.

## Common gotchas

- **No device paired** → `send_sms` returns "No active device". Send the user to <https://app.sms8.io/devices.php> with the pairing QR.
- **OTP resend cooldown** → 60s default per phone. If hit, surface the remaining seconds from the error.
- **Webhook URL must be HTTPS** and resolvable from the public internet. `localhost`, RFC1918, and link-local addresses are rejected for safety.
- **Message body** for `send_sms` should be under ~1600 chars; longer messages are auto-split by the carrier but billed per segment (155 chars each).
