#!/usr/bin/env node
/**
 * SMS8 MCP — stdio launcher
 *
 * IDEs / AI tools that prefer stdio MCP (rather than HTTP) can use this:
 *
 *   sms8-mcp --api-key=<key>
 *
 * It bridges a local stdio JSON-RPC session to the hosted HTTPS endpoint
 * at https://mcp.sms8.io, forwarding the API key as a Bearer token.
 *
 * Read input as length-prefixed MCP frames OR newline-delimited JSON.
 * Both formats are widely used; we detect on the fly.
 *
 * Environment variables:
 *   SMS8_API_KEY    your SMS8 API key (preferred over --api-key)
 *   SMS8_BASE_URL   override server (default: https://mcp.sms8.io)
 *
 * Usage in an MCP config:
 *   {
 *     "mcpServers": {
 *       "sms8": {
 *         "command": "npx",
 *         "args": ["-y", "@sms8/mcp"],
 *         "env": { "SMS8_API_KEY": "sk_..." }
 *       }
 *     }
 *   }
 */
import readline from 'node:readline';
import process   from 'node:process';

const args = process.argv.slice(2);
const argv = Object.fromEntries(
  args.flatMap((a) => {
    const m = a.match(/^--([^=]+)(?:=(.*))?$/);
    return m ? [[m[1], m[2] ?? true]] : [];
  })
);

const API_KEY  = argv['api-key']  || process.env.SMS8_API_KEY  || '';
const BASE_URL = (argv['base-url'] || process.env.SMS8_BASE_URL || 'https://mcp.sms8.io').replace(/\/$/, '');

if (!API_KEY) {
  process.stderr.write(
`sms8-mcp: missing API key.

Pass via env:        SMS8_API_KEY=sk_xxx npx -y @sms8/mcp
Or via flag:         npx -y @sms8/mcp --api-key=sk_xxx

Get your key:        https://app.sms8.io  →  Profile → API
Full setup wizard:   https://app.sms8.io/mcp-setup.php
`
  );
  process.exit(1);
}

/**
 * Send one JSON-RPC envelope to the SMS8 HTTP endpoint and return the parsed
 * response (or a synthesized JSON-RPC error envelope on transport failure).
 */
async function forward(payload) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), 30_000);
  try {
    const r = await fetch(BASE_URL, {
      method:  'POST',
      headers: {
        'content-type':  'application/json',
        'authorization': `Bearer ${API_KEY}`,
        'user-agent':    'sms8-mcp-node/1.0.0',
      },
      body:   JSON.stringify(payload),
      signal: ctrl.signal,
    });
    const text = await r.text();
    try { return JSON.parse(text); }
    catch {
      return {
        jsonrpc: '2.0',
        id:      payload.id ?? null,
        error:   { code: -32603, message: `Non-JSON response from server: ${text.slice(0, 200)}` },
      };
    }
  } catch (e) {
    return {
      jsonrpc: '2.0',
      id:      payload.id ?? null,
      error:   { code: -32603, message: `Transport error: ${e.message}` },
    };
  } finally {
    clearTimeout(t);
  }
}

/**
 * MCP over stdio can use either:
 *  1. Content-Length framed messages (LSP-style)
 *  2. Newline-delimited JSON (NDJSON)
 *
 * We detect the format on the first chunk and stick with it.
 */
let mode = null;                  // 'framed' | 'ndjson'
let buffer = Buffer.alloc(0);

function writeResponse(obj) {
  const str = JSON.stringify(obj);
  if (mode === 'framed') {
    const body = Buffer.from(str, 'utf8');
    process.stdout.write(`Content-Length: ${body.length}\r\n\r\n`);
    process.stdout.write(body);
  } else {
    process.stdout.write(str + '\n');
  }
}

async function handle(msg) {
  // MCP notifications have no `id` and expect no response (per JSON-RPC 2.0)
  const isNotification = (msg.id === undefined || msg.id === null);
  const resp = await forward(msg);
  if (!isNotification) writeResponse(resp);
}

process.stdin.on('data', (chunk) => {
  buffer = Buffer.concat([buffer, chunk]);

  // Detect mode once
  if (mode === null) {
    const head = buffer.slice(0, 200).toString('utf8');
    mode = head.startsWith('Content-Length:') ? 'framed' : 'ndjson';
  }

  if (mode === 'framed') {
    while (true) {
      const sep = buffer.indexOf('\r\n\r\n');
      if (sep === -1) return;
      const header = buffer.slice(0, sep).toString('utf8');
      const m = header.match(/Content-Length:\s*(\d+)/i);
      if (!m) {
        // malformed — drop the header and continue
        buffer = buffer.slice(sep + 4);
        continue;
      }
      const len = parseInt(m[1], 10);
      const start = sep + 4;
      if (buffer.length < start + len) return;
      const body = buffer.slice(start, start + len).toString('utf8');
      buffer = buffer.slice(start + len);
      try { handle(JSON.parse(body)); }
      catch (e) { process.stderr.write(`sms8-mcp: parse error — ${e.message}\n`); }
    }
  } else {
    // NDJSON — split on newlines
    let nl;
    while ((nl = buffer.indexOf(0x0a)) !== -1) {
      const line = buffer.slice(0, nl).toString('utf8').trim();
      buffer = buffer.slice(nl + 1);
      if (!line) continue;
      try { handle(JSON.parse(line)); }
      catch (e) { process.stderr.write(`sms8-mcp: parse error — ${e.message}\n`); }
    }
  }
});

process.stdin.on('end', () => process.exit(0));
process.on('SIGINT',  () => process.exit(0));
process.on('SIGTERM', () => process.exit(0));

process.stderr.write(`sms8-mcp ready (relaying to ${BASE_URL})\n`);
