# sms8-mcp — SMS gateway MCP server for Claude Code, Cursor, Windsurf

[![npm version](https://img.shields.io/npm/v/sms8-mcp)](https://www.npmjs.com/package/sms8-mcp)
[![npm downloads](https://img.shields.io/npm/dm/sms8-mcp)](https://www.npmjs.com/package/sms8-mcp)
[![MCP 2024-11-05](https://img.shields.io/badge/MCP-2024--11--05-7c3aed)](https://modelcontextprotocol.io)
[![Node 18+](https://img.shields.io/badge/node-%E2%89%A518-339933)](https://nodejs.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Plug SMS into any AI coding tool that speaks the Model Context Protocol.** Your assistant can now send SMS, generate and verify OTPs, wait for incoming codes, list inbox messages, and configure webhooks — through **your own Android phone** rather than Twilio.

Add to any MCP client config and you're done:

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

---

## What it does

Exposes 9 tools to your AI assistant:

| Tool | What it does |
|---|---|
| `setup_sms8` | Validate the API key, return account context and code samples |
| `send_sms` | Send one SMS to one phone number |
| `send_otp` | Generate and send a verification code |
| `verify_otp` | Compare a typed code against the latest issued OTP |
| `wait_for_otp` | Block until an OTP arrives on the paired Android; extract the code |
| `list_devices` | List paired Android phones (model, primary flag, enabled) |
| `get_messages` | Recent inbox or sent items, filter by direction, limit, phone |
| `get_balance` | Account credits + expiry + paired-device count |
| `create_webhook` | Register an inbound-SMS webhook URL |

## Why use this

- **Real SMS from your AI assistant** — Claude / Cursor / Windsurf can text users without a Twilio account
- **OTP loops your AI can complete end-to-end** — `send_otp → wait_for_otp` lets agents handle phone verification in tests, signups, password resets
- **No CPaaS fees** — your own Android SIM is the sender; $29/month flat unlimited
- **No A2P 10DLC** paperwork (US) — these are P2P messages from a real phone

## Install

You don't have to install anything — `npx -y sms8-mcp` does it on demand inside the MCP config. The launcher is ~10 KB.

To install globally:

```bash
npm install -g sms8-mcp
```

Node 18 or newer.

## Get an API key

1. Sign up free at [sms8.io](https://sms8.io) (5-day trial, no card required)
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

## Client config

### Claude Code

`~/.claude/config.json` or per-project `.mcp.json`:

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

### Cursor

`~/.cursor/mcp.json`:

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

### Windsurf

`~/.codeium/windsurf/mcp_config.json`:

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

### OpenCode

Add to `opencode.json`:

```json
{
  "mcp": {
    "sms8": {
      "command": "npx",
      "args": ["-y", "sms8-mcp"],
      "env": { "SMS8_API_KEY": "sk_xxx" }
    }
  }
}
```

### Claude.ai web (Custom Connector)

Connect directly to the hosted MCP — no local install needed. Add `https://mcp.sms8.io` as a connector in claude.ai → Settings → Connectors. Then ask: *"Send SMS via SMS8 to +1234 saying Hi"*.

## Example prompts to try

After adding the connector and restarting your client:

- *"Use sms8 to send +14155550100 'Test from Claude'"*
- *"Send an OTP code to +14155550100, then wait until it arrives and tell me the code"*
- *"Show me my SMS8 inbox from the last hour"*
- *"Which Android devices are paired with my SMS8 account?"*

## How it works

1. Your AI client launches `npx -y sms8-mcp` over stdio
2. The launcher forwards JSON-RPC frames to the hosted MCP server at `https://mcp.sms8.io`, adding `Authorization: Bearer <SMS8_API_KEY>` to each call
3. The MCP server validates the key, runs the tool against your SMS8 account, and queues the SMS
4. Your paired Android phone polls SMS8, sends the SMS via its real SIM, and reports back
5. The result returns up the chain to your AI assistant

Round-trip is typically under 4 seconds.

## Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `SMS8_API_KEY` | _(required)_ | Your API key from app.sms8.io |
| `SMS8_BASE_URL` | `https://mcp.sms8.io` | Override server (rare; for self-hosted) |

## Want CLI instead?

If you want to call SMS8 from shell scripts, cron, CI without an AI loop, use the companion package [`sms8-cli`](https://www.npmjs.com/package/sms8-cli):

```bash
npx sms8-cli send +14155550100 "Hi"
npx sms8-cli otp send +14155550100
CODE=$(npx sms8-cli otp wait +14155550100 --timeout=120)
```

## Links

- MCP docs: [mcp.sms8.io](https://mcp.sms8.io)
- Marketing: [sms8.io](https://sms8.io)
- Dashboard: [app.sms8.io](https://app.sms8.io)
- API docs: [sms8.io/sms-api-documentation](https://sms8.io/sms-api-documentation)
- Android app: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- GitHub: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)
- Issues: [github.com/1fancy/sms8-sms-gateway/issues](https://github.com/1fancy/sms8-sms-gateway/issues)

## License

MIT
