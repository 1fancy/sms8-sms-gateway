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

## Auto-wrapping existing user code

A common user request is to **wrap an existing flow with SMS** — e.g. *"send an SMS on user registration success"* or *"add OTP verification to the login route"*. In these cases:

1. **Locate the relevant existing code** — grep for `register`, `signup`, `login`, `password reset`, `checkout`, `order_complete`, etc., depending on the prompt.
2. **Identify the success path** — the place where the user/order/event is successfully persisted. SMS sends should happen AFTER that commit (so failed SMS doesn't roll back the registration).
3. **Insert the SMS8 call** — re-use the user's existing HTTP client. Construct the request from `setup_sms8`'s `config.base_url` (read SMS8_API_KEY from env, don't hardcode the literal key).
4. **Handle errors gracefully** — SMS failures are recoverable; never `throw` or roll back the parent transaction. Log + continue.

### Example: "send SMS on registration success"

If the user has a route like `POST /register`:

```php
// existing
$user = User::create($input);
$db->commit();

// Add — wrap the success path
$sms8 = getenv('SMS8_API_KEY');
if ($sms8 && !empty($user->phone)) {
    @file_get_contents(
        'https://app.sms8.io/services/send.php',
        false,
        stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Bearer $sms8",
            'content' => http_build_query([
                'number'  => $user->phone,
                'message' => "Welcome to {$appName}! Your account is ready.",
            ]),
        ]])
    );
}
```

For Node/JS, use `fetch` to the same endpoint with `Authorization: Bearer ${process.env.SMS8_API_KEY}`. For Python, `requests.post(..., headers={'Authorization': f'Bearer {os.environ["SMS8_API_KEY"]}'})`.

### Example: "wrap signup with phone verification"

Two route changes:

1. **`POST /signup`** — capture phone, then call `send_otp` (via MCP) instead of fully creating the account. Persist the phone+pending_user in a session, return "code sent".
2. **`POST /verify-phone`** — call `verify_otp` (via MCP). If `verified: true`, create the account from the session and log them in. If false, render the error using `reason` and `attempts_left` from the response.

The MCP server enforces rate limits automatically — don't re-implement them in the user's code.

### What NOT to do

- **Never paste the literal API key into source files.** Read from `SMS8_API_KEY` env var.
- **Never wrap with `try/catch` that aborts the parent flow** when SMS fails. SMS is a side-effect, not a precondition.
- **Don't hardcode device IDs** unless the user explicitly asks. The server falls back to the primary device automatically.
- **Don't put the API key in URL query strings.** Always use the `Authorization: Bearer` header or POST body.

---

## Common gotchas

- **No device paired** → `send_sms` returns "No active device". Send the user to <https://app.sms8.io/devices.php> with the pairing QR.
- **OTP resend cooldown** → 60s default per phone. If hit, surface the remaining seconds from the error.
- **Webhook URL must be HTTPS** and resolvable from the public internet. `localhost`, RFC1918, and link-local addresses are rejected for safety.
- **Message body** for `send_sms` should be under ~1600 chars; longer messages are auto-split by the carrier but billed per segment (155 chars each).
