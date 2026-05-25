# SMS8 MCP Server

[**SMS gateway** &rarr; sms8.io](https://sms8.io) &nbsp;·&nbsp; [**SMS Gateway MCP for vibe coders &amp; developers** &rarr; mcp.sms8.io](https://mcp.sms8.io)

Send SMS, issue and verify OTPs, wait for incoming codes, and configure webhooks from Claude Code, Cursor, Windsurf, OpenCode and any other AI coding tool that speaks the Model Context Protocol. SMS routes through a paired Android phone via the SMS8 platform.

[![MCP](https://img.shields.io/badge/Model%20Context%20Protocol-2024--11--05-7c3aed)](https://modelcontextprotocol.io)
[![Claude Code](https://img.shields.io/badge/Claude%20Code-Compatible-cc6600)](https://claude.com/claude-code)
[![Cursor](https://img.shields.io/badge/Cursor-Compatible-000)](https://cursor.com)
[![OpenCode](https://img.shields.io/badge/OpenCode-Compatible-10b981)](https://opencode.ai)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![PHP](https://img.shields.io/badge/built%20with-PHP%208-777BB4)](https://php.net)

**Live server:** [https://mcp.sms8.io](https://mcp.sms8.io)
**Dashboard:** [https://app.sms8.io](https://app.sms8.io)
**Marketing site:** [https://sms8.io](https://sms8.io)

## Why use this

Building an app and want SMS notifications, OTPs, two-way messaging, or autonomous-agent phone verification? Most options force you to register with carrier programs, provision phone numbers, pay per message, or rent a disposable SIM per session. SMS8 routes SMS through a phone you already own. The MCP server gives your AI assistant nine tools it can call directly, so adding SMS to a project takes one prompt instead of an hour of integration work.

## What you get

| Tool | Purpose |
| --- | --- |
| `setup_sms8` | Validates the API key, returns account context and code samples |
| `send_sms` | Sends one SMS through a paired Android device |
| `send_otp` | Generates and sends a verification code |
| `verify_otp` | Compares a typed code against the latest issued OTP |
| `wait_for_otp` | **NEW.** Blocks until an OTP arrives on the paired Android; extracts the code |
| `get_messages` | Returns recent inbox or sent SMS |
| `list_devices` | Lists paired Android devices |
| `get_balance` | **NEW.** Lightweight credit and renewal check |
| `create_webhook` | Registers a callback URL for inbound SMS and delivery events |

All tools share the same send pipeline as the SMS8 dashboard. Credits, retries, multi-device routing, and webhook signing work identically to a direct API call.
## SMS8 vs Twilio vs MessageBird

| Capability | SMS8 | Twilio | MessageBird |
| --- | --- | --- | --- |
| Built-in MCP server | Yes | No | No |
| Per-message fee | None | $0.0079+ | $0.05+ |
| A2P 10DLC required | No | Yes | Yes |
| Phone number provisioning | Not needed | $1+/mo | $2+/mo |
| Setup time | 60 seconds | Days to weeks | Days |
| OTP verification built-in | Yes | Extra service | Extra service |
| Open-source MCP code | MIT | No | No |

## Quick start

### 1. Get an SMS8 account

Sign up at `app.sms8.io`. Free 5-day trial, no credit card. Pair your Android phone via QR and copy your API key from **Profile then API**.

### 2. Add the MCP server to your tool

#### Hosted HTTP (Claude Code, Cursor, Windsurf, Codex, Devin)

```json
{
  "mcpServers": {
    "sms8": {
      "url": "https://mcp.sms8.io",
      "transport": "http",
      "headers": {
        "Authorization": "Bearer YOUR_SMS8_API_KEY"
      }
    }
  }
}
```

#### Claude Code plugin (one command)

```
/plugin marketplace add 1fancy/sms8-sms-gateway
/plugin install sms8-sms-gateway
```

Then `export SMS8_API_KEY=sk_xxx` in your shell before launching Claude Code.

#### npx for stdio clients

```json
{
  "mcpServers": {
    "sms8": {
      "command": "npx",
      "args": ["-y", "@sms8/mcp"],
      "env": { "SMS8_API_KEY": "sk_your_key" }
    }
  }
}
```

### 3. Setup wizard

The dashboard at `app.sms8.io/mcp-setup.php` prefills the JSON for each AI tool, shows your device pairing QR if needed, and includes a live SMS test form.

### 4. Ask your AI

```
Add SMS verification to my signup flow using the sms8 MCP.
```

The AI will call `setup_sms8`, then `send_otp` and `verify_otp`, and write production code that works on the first try.

## Use cases

These are real one-line prompts you can paste into Claude Code, Cursor, or Windsurf.

**Phone verification on signup**
```
Wire phone-number verification into this app using the sms8 MCP. Use
send_otp on /signup, verify_otp on /verify-phone. Render the remaining
attempts on error.
```

**Order shipping notifications**
```
When an order ships, send the customer an SMS with their tracking link
via the sms8 MCP.
```

**Two-way support inbox**
```
Register a webhook with the sms8 MCP at https://my-app.com/sms-inbox.
Scaffold the handler that verifies the HMAC signature and routes inbound
SMS to the support queue.
```

**Passwordless login by SMS**
```
Replace the magic-link email login with SMS OTPs using the sms8 MCP.
6-digit code, 5-minute expiry, 5 attempts.
```

**SMS 2FA for admin pages**
```
Add SMS 2FA to /admin login via the sms8 MCP. Lock the account after
5 failed verify_otp attempts.
```

## Drop-in code examples

Each file in [`examples/`](examples/) is production-ready, zero-dependency, and copy-paste-able. These are what the AI generates after a `setup_sms8` handshake.

* [`php-send.php`](examples/php-send.php): minimal `Sms8Sender` class with `send`, `sendOtp`, `verifyOtp`
* [`js-fetch.js`](examples/js-fetch.js): Node / browser / Cloudflare Worker client (ESM and CommonJS)
* [`otp-flow.php`](examples/otp-flow.php): complete phone-verification flow (`startSignup` + `verifySignup`)
* [`webhook-handler.php`](examples/webhook-handler.php): inbound SMS handler with HMAC signature check

## Frequently asked questions

### What is the SMS8 MCP server?

An HTTPS endpoint at `mcp.sms8.io` that implements the Model Context Protocol. AI coding tools call its JSON-RPC interface to send SMS, issue OTPs, and configure webhooks. SMS routes through a paired Android phone via the SMS8 platform.

### How is this different from Twilio?

SMS8 uses your own Android phone as the gateway with your existing SIM card. There are no per-message carrier fees, no A2P 10DLC registration, and no phone number to provision. You pay a flat plan and send unlimited SMS. The MCP server exposes seven tools your AI assistant can call directly, which Twilio does not offer.

### Does this work with Claude Code?

Yes. Add the MCP server to `~/.config/claude/mcp-servers.json` with the HTTP transport pointing at `https://mcp.sms8.io` and your SMS8 API key as a Bearer token. Or run `/plugin marketplace add 1fancy/sms8-sms-gateway` followed by `/plugin install sms8-sms-gateway`.

### Does this work with Cursor and Windsurf?

Yes. Both support HTTP MCP servers. Add `https://mcp.sms8.io` with your API key as a Bearer header to `~/.cursor/mcp.json` (Cursor) or `~/.codeium/windsurf/mcp_config.json` (Windsurf). The setup wizard at `app.sms8.io/mcp-setup.php` prefills the JSON per tool.

### Can the AI add OTP verification automatically?

Yes. The bundled Claude Code Skill (`skills/send-sms/SKILL.md`) teaches the assistant when to call `send_otp` and `verify_otp`, and how to wrap an existing signup or login route with the verification step. A prompt like *"add phone verification to /signup"* is enough.

### Is the source code public?

Yes, MIT-licensed. The server, all seven tool implementations, the npm launcher (`@sms8/mcp`), the Claude Code plugin manifest, and example code live in [this repo](https://github.com/1fancy/sms8-sms-gateway).

### What happens to my API key?

Your API key authenticates each MCP tool call through an `Authorization: Bearer` header (or `X-Api-Key`). The hosted server validates it against your SMS8 account, then routes the call through the existing SMS8 send pipeline. The `setup_sms8` response returns only the last four characters of your key, so it never leaks into AI chat history or logs.

### Is this safe to use in production?

Yes. Server-side defences include:

* Per-key rate limiting inherited from the SMS8 API
* Hard cap of 5 OTPs per phone number per 24 hours, not user-configurable
* Resend cooldown of 30 to 600 seconds (default 60 seconds)
* POST-only OTP endpoints; GET returns 405; cookies ignored
* Transaction-wrapped rate-limit checks with row locks; concurrent calls cannot race past the limits
* HMAC-SHA256 webhook signatures
* Comprehensive SSRF block list on `create_webhook` covering RFC1918, CGNAT, link-local, IPv4-mapped IPv6, and DNS-resolved hosts
* CORS only advertised for discovery methods; `tools/call` omits CORS so a malicious web page cannot drive SMS sends

### Can I self-host the MCP server?

Yes. Clone the repo, set `SMS8_APP_PATH` to your SMS8 install directory, and run:

```bash
git clone https://github.com/1fancy/sms8-sms-gateway.git
cd sms8-sms-gateway
export SMS8_APP_PATH=/path/to/your/sms8-install
php -S 127.0.0.1:8080 index.php
```

The hosted server at `mcp.sms8.io` is free and easier for most users.

### What is "vibe coding"?

Vibe coding is shipping working software by describing what you want in plain English to an AI assistant instead of writing every line by hand. SMS8 MCP makes one of the most common requests (*"add SMS notifications"*) possible without ever touching a carrier portal or A2P paperwork.

## Security and secrets

This repository contains no secrets. All credentials live in environment variables or your AI tool's MCP config:

```bash
SMS8_API_KEY=sk_…        # from app.sms8.io → Profile → API
SMS8_WEBHOOK_SECRET=…    # from app.sms8.io → Webhook
```

For full security details see the FAQ section above. A separate security audit summary is available on request.

## Repository layout

```
.
├── README.md                       this file
├── LICENSE                         MIT
├── index.php                       MCP server entry (JSON-RPC dispatcher)
├── landing.html                    marketing landing at mcp.sms8.io
├── .claude-plugin/plugin.json      Claude Code plugin manifest
├── .mcp.json                       MCP server config referenced by the plugin
├── skills/send-sms/SKILL.md        teaches Claude when to invoke SMS8 tools
├── npm/                            @sms8/mcp stdio bridge for stdio clients
│   ├── package.json
│   ├── bin/sms8-mcp.js
│   └── README.md
├── lib/                            Auth, JsonRpc, ToolRegistry
├── tools/                          7 tool implementations
└── examples/                       PHP and JS drop-in samples
```

## Roadmap

* [x] HTTP MCP server live at `mcp.sms8.io`
* [x] OTP endpoints with per-phone abuse cap
* [x] npm package `@sms8/mcp` for stdio clients
* [x] Claude Code plugin and Skill
* [ ] MMS support
* [ ] Inbound SMS auto-routed to AI for reply generation
* [ ] Per-tool rate limits configurable in the dashboard
* [ ] Code examples in Python, Go, and Ruby

## Contributing

Issues and pull requests welcome at [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway). Open a discussion before larger changes.

## License

MIT. See [LICENSE](LICENSE).

## Keywords

model context protocol, mcp server, mcp sms, claude code sms, claude code mcp, cursor mcp, windsurf mcp, sms api, otp api, sms gateway, android sms gateway, twilio alternative, vibe coding, ai sms integration, sms notifications, send sms from claude, send sms from cursor, phone verification api, sms otp, 2fa sms, webhook sms, inbound sms, two-way sms, unlimited sms, no per-message fees, no A2P 10DLC, ai coding tools, sms8
