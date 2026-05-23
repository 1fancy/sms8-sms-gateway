# SMS8 MCP Server — SMS, OTP & Webhooks for Claude Code, Cursor & Any AI Coding Tool

> **The official Model Context Protocol (MCP) server for sending SMS, one-time passwords (OTPs), and webhooks from AI coding tools like Claude Code, Cursor, Windsurf, Codex, and Devin.** Use your own Android phone as the gateway — no Twilio, no A2P 10DLC, no per-message fees.

[![MCP](https://img.shields.io/badge/Model%20Context%20Protocol-2024--11--05-7c3aed)](https://modelcontextprotocol.io)
[![Claude Code](https://img.shields.io/badge/Claude%20Code-Compatible-cc6600)](https://claude.com/claude-code)
[![Cursor](https://img.shields.io/badge/Cursor-Compatible-000)](https://cursor.com)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![PHP](https://img.shields.io/badge/built%20with-PHP%208-777BB4)](https://php.net)
[![GitHub Stars](https://img.shields.io/github/stars/1fancy/sms8-sms-gateway?style=social)](https://github.com/1fancy/sms8-sms-gateway)

**Live server:** [`https://mcp.sms8.io`](https://mcp.sms8.io)
**Dashboard:** [`https://app.sms8.io`](https://app.sms8.io)
**Marketing:** [`https://sms8.io`](https://sms8.io)

---

## What this MCP does, in one sentence

Lets an AI coding assistant send real SMS messages, issue and verify one-time passwords (OTPs), and register webhooks — using **your own paired Android phone as the SMS gateway**, controlled through the SMS8 platform.

## Why developers pick SMS8 over Twilio for AI-built apps

| Feature | SMS8 MCP | Twilio | MessageBird |
|---|---|---|---|
| MCP server (works in Claude Code, Cursor, Windsurf) | **Yes — built-in** | No | No |
| Per-message fees | **None** (flat plan) | $0.0079+ per SMS | $0.05+ per SMS |
| A2P 10DLC registration required | **No** | Yes (weeks) | Yes |
| Phone-number provisioning required | **No** (uses your phone) | Yes ($1+/month) | Yes |
| Setup time | **60 seconds** | Days to weeks |  Days |
| Open-source MCP server | **Yes** ([this repo](https://github.com/1fancy/sms8-sms-gateway)) | No | No |
| Works with personal SIM card | **Yes** | No | No |
| OTP verification built-in | **Yes — `send_otp` + `verify_otp` tools** | Verify API extra cost | Verify API extra cost |

---

## Quick start (60 seconds)

### 1. Get a free SMS8 account
- Sign up at [`app.sms8.io`](https://app.sms8.io) — 5-day trial, no credit card.
- Install the [SMS8 Android app](https://app.sms8.io/devices.php), pair your phone via QR.
- Copy your API key from **Profile → API**.

### 2. Pick your install method

#### Option A · Hosted HTTP (recommended — works in Claude Code, Cursor, Windsurf)

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

#### Option B · Claude Code plugin (one command)

```bash
/plugin marketplace add 1fancy/sms8-sms-gateway
/plugin install sms8-sms-gateway
```

Then `export SMS8_API_KEY=sk_xxx` in your shell before launching Claude Code.

#### Option C · `npx` for stdio-MCP clients

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

#### Full setup wizard

For copy-paste configs prefilled with your API key, the QR for device pairing, and a live "send test SMS" form, open the in-app wizard:

> **<https://app.sms8.io/mcp-setup.php>**

### 3. Ask your AI to send something

> *"Add SMS verification to my signup flow using the sms8 MCP."*

Your AI will call `setup_sms8`, get the account context it needs, and write production-ready integration code that works on the first try.

---

## What your AI can do (7 tools)

| Tool | Purpose | When the AI invokes it |
|---|---|---|
| `setup_sms8` | Validate API key, return account + devices + integration context | Once per session, before code generation |
| `send_sms` | Send a single SMS through your paired Android phone | Notifications, alerts, transactional messaging |
| `send_otp` | Generate and send a verification code | Sign-up phone verification, 2FA, password reset |
| `verify_otp` | Compare a user-typed code against the latest issued OTP | After the user enters the code |
| `get_messages` | Fetch recent inbox / sent SMS | Reply flows, delivery checks, debugging |
| `list_devices` | List paired Android devices | Pick which phone to send from |
| `create_webhook` | Register a callback URL for inbound SMS + delivery events | Wire up bi-directional SMS, status updates |

Every tool re-uses the existing SMS8 send pipeline — credits, retries, multi-device routing, and webhook signing behave identically to a direct API call.

---

## Use cases (real prompts you can paste into Claude Code)

### Add phone verification to a sign-up form
```
Wire phone-number verification into this app using the sms8 MCP. Use send_otp on
the /signup endpoint, store the phone in the session, then verify_otp on the
/verify-phone endpoint. Render an error if the OTP is wrong; show the remaining
attempts count.
```

### Send transactional SMS notifications
```
When a new order is placed, send the customer an SMS confirmation via the sms8
MCP. Use the order ID + tracking link in the message body.
```

### Build a two-way SMS support inbox
```
Use the sms8 MCP to register a webhook at https://my-app.com/sms-inbox. Scaffold
a webhook handler that verifies the HMAC-SHA256 signature, then routes inbound
SMS to the support queue in our database.
```

### SMS-based passwordless login
```
Replace the email magic-link login with SMS one-time codes using the sms8 MCP.
Use a 6-digit code, 5-minute expiry, 5 attempts. Apply the existing rate limits.
```

---

## Drop-in code examples

Each example in [`examples/`](examples/) is production-ready, zero-dependency, and copy-paste-able — what the AI generates after a `setup_sms8` handshake:

- **[`php-send.php`](examples/php-send.php)** — Minimal PHP `Sms8Sender` class with `send()`, `sendOtp()`, `verifyOtp()`
- **[`js-fetch.js`](examples/js-fetch.js)** — Node / browser / Cloudflare Worker client (ESM + CommonJS)
- **[`otp-flow.php`](examples/otp-flow.php)** — Complete phone-verification flow (`startSignup` + `verifySignup`)
- **[`webhook-handler.php`](examples/webhook-handler.php)** — Inbound SMS handler with HMAC signature verification

---

## Frequently Asked Questions

### What is the SMS8 MCP server?

The SMS8 MCP server is an HTTPS endpoint at `mcp.sms8.io` that implements the [Model Context Protocol](https://modelcontextprotocol.io) so AI coding tools (Claude Code, Cursor, Windsurf, etc.) can send SMS, issue OTPs, and configure webhooks on behalf of a developer. It bridges the AI's tool-calling capability to the SMS8 SMS gateway, which uses your own Android phone as the SMS transport.

### How is this different from Twilio?

SMS8 routes SMS through a paired Android device using your existing SIM card, so there are no per-message carrier fees, no A2P 10DLC registration, and no phone number to provision. You pay one flat plan and send unlimited SMS. The MCP server exposes 7 tools an AI assistant can call directly — Twilio has no equivalent MCP integration.

### Does this work with Claude Code?

Yes. Add the MCP server to `~/.config/claude/mcp-servers.json` with the HTTP transport pointing at `https://mcp.sms8.io` and your SMS8 API key as a Bearer token. Or use `/plugin marketplace add 1fancy/sms8-sms-gateway` for the one-command install that bundles an MCP Skill teaching Claude when to invoke each tool.

### Does this work with Cursor and Windsurf?

Yes. Both support HTTP MCP servers. Add `https://mcp.sms8.io` with your API key as a Bearer header to `~/.cursor/mcp.json` (Cursor) or `~/.codeium/windsurf/mcp_config.json` (Windsurf). The [`/mcp-setup.php`](https://app.sms8.io/mcp-setup.php) wizard in the SMS8 dashboard prefills the configs per tool.

### Is the source code public?

Yes — MIT-licensed. The entire server, all 7 tool implementations, the npm launcher, the Claude Code plugin manifest, and 4 production-ready code examples live in [this repo](https://github.com/1fancy/sms8-sms-gateway).

### What happens to my API key?

Your API key authenticates each MCP tool call via an `Authorization: Bearer` header (or `X-Api-Key`). The hosted server validates it against your SMS8 account, then routes the call through the existing SMS8 send pipeline. The `setup_sms8` tool deliberately returns only the **last 4 characters** of your key so it never leaks into AI chat history or logs.

### Is this safe to use in production?

Yes. Server-side defences include:
- Per-key rate limiting (inherits SMS8 API limits)
- **Hard cap: 5 OTPs per phone number per 24h** (not user-configurable)
- Resend cooldown 30–600s (default 60s)
- Verify endpoint requires POST; rejects GET; ignores cookies
- Transaction-wrapped check+insert to defeat race conditions
- HMAC-SHA256 webhook signatures
- SSRF block list on `create_webhook` (RFC1918, CGNAT, link-local, IPv4-mapped IPv6, DNS-resolved hosts)
- CORS only advertised for discovery methods; `tools/call` deliberately omits CORS headers

### Can I self-host the MCP server?

Yes. Clone the repo, point `SMS8_APP_PATH` at your SMS8 install, and run:

```bash
git clone https://github.com/1fancy/sms8-sms-gateway.git
cd sms8-sms-gateway
export SMS8_APP_PATH=/path/to/your/sms8-install
php -S 127.0.0.1:8080 index.php
```

The hosted server at `mcp.sms8.io` is free and the easier path for most users.

### What does "vibe coding" mean here?

Vibe coding is shipping working software by describing what you want in plain English to an AI assistant, instead of writing every line by hand. SMS8 MCP makes one of the most common requests — *"add SMS notifications"* — possible without ever touching Twilio's dashboard, A2P 10DLC paperwork, or carrier portal.

---

## Security & secrets

This repository contains **zero secrets** — all credentials live in environment variables or your AI tool's MCP config:

```bash
SMS8_API_KEY=sk_…        # from app.sms8.io → Profile → API
SMS8_WEBHOOK_SECRET=…    # from app.sms8.io → Webhook
```

The server enforces:

- Per-user API-key auth on every `tools/call`
- POST-only on OTP endpoints; rejects GET to prevent CSRF via tag injection
- Constant-time `hash_equals` compare for OTP verification
- Transaction-wrapped rate-limit checks (no race past the 5-OTP-per-phone-per-24h cap)
- SSRF guards on webhook URLs — blocks loopback, RFC1918, CGNAT, link-local, IPv4-mapped IPv6, plus DNS resolution of the host at validation time
- HMAC-SHA256 webhook signatures (`X-SMS8-Signature`)
- `setup_sms8` response masks the API key (last 4 chars only) to prevent leakage into AI chat history

For a full security audit summary, see [`SECURITY.md`](SECURITY.md) (if present) or open an issue.

---

## Repository layout

```
.
├── README.md                       ← this file
├── LICENSE                         ← MIT
├── index.php                       ← MCP server entry (JSON-RPC dispatcher)
├── landing.html                    ← marketing landing at mcp.sms8.io
├── .claude-plugin/plugin.json      ← Claude Code plugin manifest
├── .mcp.json                       ← MCP server config (referenced by plugin)
├── skills/send-sms/SKILL.md        ← Skill that teaches Claude when to use SMS8
├── npm/                            ← @sms8/mcp npm package (stdio bridge)
│   ├── package.json
│   ├── bin/sms8-mcp.js
│   └── README.md
├── lib/                            ← Auth, JsonRpc, ToolRegistry
├── tools/                          ← 7 tool implementations (setup, send_sms, …)
└── examples/                       ← Drop-in PHP + JS code samples
```

---

## Roadmap

- [x] HTTP MCP server (live at `mcp.sms8.io`)
- [x] Hosted OTP endpoints with per-phone abuse cap
- [x] npm package `@sms8/mcp` for stdio clients
- [x] Claude Code plugin + Skill
- [ ] MMS support
- [ ] Inbound SMS auto-routing to AI for replies
- [ ] Per-tool granular rate limits in the dashboard
- [ ] Multi-language code examples (Python, Go, Ruby)

---

## Contributing

Issues and pull requests welcome at [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway). Open a discussion before large changes.

## License

MIT — see [LICENSE](LICENSE).

---

## Keywords

*model context protocol, mcp server, mcp sms, claude code sms, claude code mcp, cursor mcp, windsurf mcp, sms api, otp api, sms gateway, android sms gateway, twilio alternative, vibe coding, ai sms integration, sms notifications, send sms from claude, send sms from cursor, phone verification api, sms otp, 2fa sms, webhook sms, inbound sms, two-way sms, unlimited sms, no per-message fees, no A2P 10DLC, ai coding tools, sms8*
