# @sms8/mcp

Stdio launcher for the **SMS8 MCP server**. Connects Claude Code, Cursor, Windsurf, and other MCP-compatible AI coding tools to your SMS8 account so they can send SMS, OTPs, and webhooks on your behalf.

> **Don't need stdio?** If your AI tool supports HTTP MCP (Claude Code does), just point it at `https://mcp.sms8.io` directly with your API key as a Bearer token — no install needed.

---

## Quick start

```bash
# Run directly (no install)
npx -y @sms8/mcp --api-key=sk_your_key

# Or via environment variable
SMS8_API_KEY=sk_your_key npx -y @sms8/mcp
```

Then add this to your AI tool's MCP config:

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

Get your API key at <https://app.sms8.io> → Profile → API.
Step-by-step setup wizard: <https://app.sms8.io/mcp-setup.php>.

---

## What your AI can do

| Tool | What it does |
|---|---|
| `setup_sms8` | Handshake — returns config, devices, code samples |
| `send_sms` | Send a single SMS through your paired Android |
| `send_otp` | Issue a verification code |
| `verify_otp` | Check a code the user typed |
| `get_messages` | Fetch recent inbox / sent SMS |
| `list_devices` | List your paired phones |
| `create_webhook` | Register a callback URL for inbound SMS |

---

## Env vars

| Var | Default | Purpose |
|---|---|---|
| `SMS8_API_KEY` | — (required) | Your SMS8 API key |
| `SMS8_BASE_URL` | `https://mcp.sms8.io` | Override server (for self-hosted) |

---

## License

MIT — see [LICENSE](https://github.com/1fancy/sms8-sms-gateway/blob/main/LICENSE).
