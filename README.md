# SMS8 MCP Server — Add SMS, OTP & Webhooks to Any AI Coding Tool

> The official **Model Context Protocol** server for [SMS8.io](https://sms8.io) — the unlimited-SMS gateway that uses your own Android phone.
>
> Plug SMS8 into **Claude Code, Cursor, Windsurf, Codex, Devin** or any MCP-compatible AI coding assistant. Your AI agent can now send SMS, verify phone numbers with OTP, manage devices, and wire up webhooks — directly from your IDE, in any project.

[![MCP](https://img.shields.io/badge/Model%20Context%20Protocol-2024--11--05-7c3aed)](https://modelcontextprotocol.io)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Built with PHP](https://img.shields.io/badge/built%20with-PHP%208-777BB4)](https://php.net)

---

## 🚀 Why this exists

If you're a vibe coder using AI tools to ship apps fast, you've probably hit this wall:

> *"I want my AI assistant to add SMS notifications to my app. But Twilio's docs are 40 pages, A2P 10DLC takes weeks to register, and I just want it to work."*

**SMS8 MCP fixes that.** One install, one tool call, and your AI generates production-ready SMS code using **your own Android phone as the gateway** — no carrier registration, no per-message fees, no waiting.

## ✨ What this MCP gives your AI agent

| Tool | What it does |
|------|--------------|
| `setup_sms8` | One-shot handshake: validate API key, fetch devices, return endpoints + code samples + dashboard links |
| `send_sms` | Send a single SMS through the user's paired Android |
| `send_otp` | Generate + send a 6-digit verification code (5-min expiry, configurable) |
| `verify_otp` | Confirm a user-typed code |
| `get_messages` | Read the inbox / sent items — for delivery checks, reply flows, debugging |
| `list_devices` | List paired Android devices |
| `create_webhook` | Register a callback URL for inbound SMS + delivery events |

All tools re-use the existing SMS8 API — credits, retries, multi-device routing, and webhooks behave identically to a direct API call.

---

## 🏃 Quick start (60 seconds)

### 1 · Get a free SMS8 account

Sign up at **[app.sms8.io](https://app.sms8.io)** — 5-day free trial, no credit card. Then:

1. Install the [SMS8 Android app](https://app.sms8.io/devices.php) and pair via QR
2. Open **Profile → API** and copy your API key

### 2 · Connect the MCP to your AI tool

Three ways — pick the one that matches your tool.

#### Option A · Hosted HTTP (Claude Code, Cursor, Windsurf)

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

```
/plugin marketplace add 1fancy/sms8-sms-gateway
/plugin install sms8-sms-gateway
```

Then set `SMS8_API_KEY=sk_xxx` in your shell environment before launching Claude Code. The plugin ships a Skill that teaches Claude when to invoke SMS8 tools, plus the HTTP MCP server config — no JSON to copy.

#### Option C · `npx` (any stdio-MCP client)

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

The fastest path is the in-app wizard — pre-fills your API key, copy-buttons per tool, and a "send first test SMS" form. Open it at:

> **<https://app.sms8.io/mcp-setup.php>**

### 3 · Tell your AI to use it

> *"Add SMS verification to my signup flow using SMS8"*

Your AI will call `setup_sms8`, get all the context it needs, and write code that just works.

---

## 📦 Drop-in code examples

Each example in [`examples/`](examples/) is **production-ready, zero-dependency, copy-paste-able code** — what the AI agent generates after a `setup_sms8` call:

- [`php-send.php`](examples/php-send.php) — Minimal PHP `Sms8Sender` class
- [`js-fetch.js`](examples/js-fetch.js) — Node / browser / Cloudflare Worker client
- [`otp-flow.php`](examples/otp-flow.php) — Complete phone-verification flow (signup-start + verify)
- [`webhook-handler.php`](examples/webhook-handler.php) — Inbound SMS handler with signature verification

---

## 🔐 Security & secrets

**Never commit your API key.** This repo contains zero secrets — all credentials live in environment variables or your AI tool's MCP config:

```bash
SMS8_API_KEY=sk_…       # from app.sms8.io → Profile → API
SMS8_WEBHOOK_SECRET=…    # from app.sms8.io → Webhook
```

The MCP server enforces:

- Per-key rate limiting (inherits from the main SMS8 API)
- SSRF guards on `create_webhook` (no loopback / RFC1918 / metadata addresses)
- HMAC-SHA256 webhook signatures (`X-SMS8-Signature`)
- Per-OTP attempt limits + 5-minute expiry + 60-second resend cooldown

---

## 🛠 Self-hosting (optional)

The hosted server at **`mcp.sms8.io`** is free — but if you want to run your own:

```bash
git clone https://github.com/1fancy/sms8-sms-gateway.git
cd sms8-mcp
export SMS8_APP_PATH=/path/to/your/sms8-install   # the main SMS8 PHP app
php -S 127.0.0.1:8080 index.php
```

Point your AI tool at `http://127.0.0.1:8080`.

---

## 🤖 What "AI-friendly" means here

This MCP is built so an AI agent can integrate SMS into any project **without external documentation lookups**:

1. **`setup_sms8` returns a complete context block** — endpoints, env-var hints, device list, code samples, dashboard URLs. One call gives the AI everything it needs.
2. **Errors are self-documenting** — they tell the AI exactly what's wrong and which URL fixes it (`"Pair an Android device at https://app.sms8.io/devices.php"`).
3. **Tool descriptions explain the *why***, not just the *what* — so an AI picks the right tool for the right job.
4. **Defaults are sane** — 6-digit OTP, 5-minute expiry, 5 attempts, primary device. The AI doesn't have to guess.

---

## 📚 Docs & links

- **MCP docs & playground:** [mcp.sms8.io](https://mcp.sms8.io)
- **SMS8 main site:** [sms8.io](https://sms8.io)
- **API reference:** [app.sms8.io/api.php](https://app.sms8.io/api.php)
- **Android SMS Gateway guide:** [sms8.io/android-sms-gateway](https://sms8.io/android-sms-gateway)
- **OTP / verification guide:** [sms8.io/sms8-api-documentation](https://sms8.io/sms8-api-documentation)
- **Integrations** (WooCommerce, Shopify, Zapier, n8n, GoHighLevel): [sms8.io/integrations](https://sms8.io/integrations)

---

## 🆚 SMS8 vs Twilio / MessageBird (for AI-coded apps)

|  | SMS8 + MCP | Twilio | MessageBird |
|---|---|---|---|
| AI / MCP integration | ✅ Native | ❌ None | ❌ None |
| Per-message fee | $0 (uses your SIM) | $0.0079+ | $0.045+ |
| A2P 10DLC registration | ❌ Not needed | ✅ Required (weeks) | Region-dependent |
| Setup time | 60 seconds | Days |  Hours |
| Two-way SMS | ✅ | ✅ | ✅ |
| OTP / 2FA | ✅ Built-in | Add-on ($) | Add-on ($) |

---

## 🏷 Topics

`mcp` · `mcp-server` · `model-context-protocol` · `claude-code` · `cursor` · `windsurf` · `sms-api` · `sms-gateway` · `otp` · `otp-verification` · `2fa` · `android-sms-gateway` · `twilio-alternative` · `messagebird-alternative` · `ai-tools` · `vibe-coding` · `developer-experience` · `webhooks`

---

## 📄 License

MIT — see [LICENSE](LICENSE).

## 🤝 Contributing

Issues + PRs welcome. For commercial support, write to **hello@sms8.io**.

---

<sub>**SMS8.io** — Convert your Android phone to an SMS gateway. Send unlimited SMS, OTP, and notifications without per-message fees or A2P 10DLC registration. Built for AI-era developers who want to ship fast.</sub>
