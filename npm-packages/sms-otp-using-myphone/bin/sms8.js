#!/usr/bin/env node
/**
 * SMS8 CLI — send SMS, send/verify OTPs, list inbox from any terminal.
 *
 *   sms8 send +14155550100 "Hi from SMS8"
 *   sms8 otp send +14155550100
 *   sms8 otp verify +14155550100 482937
 *   sms8 otp wait +14155550100              # blocks until a code arrives
 *   sms8 inbox --since 1h
 *   sms8 devices
 *   sms8 balance
 *   sms8 setup                              # validates the API key + prints account info
 *
 * Auth: reads SMS8_API_KEY from env, OR pass --api-key=… on any command.
 *   Get one free: https://app.sms8.io/api.php (5-day trial, no card).
 */
import process from 'node:process';
import fs      from 'node:fs/promises';
import os      from 'node:os';
import path    from 'node:path';

const VERSION  = '1.0.0';
const BASE_URL = (process.env.SMS8_BASE_URL || 'https://app.sms8.io').replace(/\/$/, '');
const MCP_URL  = (process.env.SMS8_MCP_URL  || 'https://mcp.sms8.io').replace(/\/$/, '');
const CFG_PATH = path.join(os.homedir(), '.sms8', 'config.json');

const args = process.argv.slice(2);
const flags = {};
const positional = [];
for (const a of args) {
  const m = a.match(/^--([^=]+)(?:=(.*))?$/);
  if (m) flags[m[1]] = m[2] ?? true;
  else if (a.startsWith('-')) flags[a.slice(1)] = true;
  else positional.push(a);
}

const HELP = `SMS8 CLI v${VERSION}

USAGE
  sms8 <command> [args] [--api-key=KEY]

COMMANDS
  setup                                  Validate API key, print account info
  send <phone> <message>                 Send one SMS
  otp send <phone>                       Send a one-time verification code
  otp verify <phone> <code>              Verify a code
  otp wait <phone> [--timeout=120]       Block until an SMS code arrives
  inbox [--since=1h] [--limit=25]        Recent messages (in & out)
  devices                                List paired Android devices
  balance                                Account credits + expiry
  config set <key>=<value>               Persist key/value to ~/.sms8/config.json
  config get                             Show stored config
  help, --help, -h                       Show this
  version, --version, -v                 Print version

AUTH
  Get a free key at https://app.sms8.io/api.php (5-day trial, no card).
  Set once:  export SMS8_API_KEY=sk_xxx
  Or:        sms8 config set api_key=sk_xxx
  Or per-call: sms8 send +1234 "hi" --api-key=sk_xxx

EXAMPLES
  sms8 send +14155550100 "Welcome aboard!"
  sms8 otp send +14155550100
  sms8 otp verify +14155550100 482937
  CODE=$(sms8 otp wait +14155550100 --timeout=180) && echo "got $CODE"

LINKS
  Docs:       https://mcp.sms8.io
  Dashboard:  https://app.sms8.io
  Free trial: https://sms8.io
`;

const cmd = positional[0];

(async () => {
  if (!cmd || cmd === 'help' || flags.help || flags.h) {
    process.stdout.write(HELP);
    return;
  }
  if (cmd === 'version' || flags.version || flags.v) {
    process.stdout.write(VERSION + '\n');
    return;
  }
  if (cmd === 'config') return cmdConfig();
  const apiKey = await resolveApiKey();
  if (!apiKey) {
    fatal(
`No API key. Pass one of:
  export SMS8_API_KEY=sk_xxx
  sms8 config set api_key=sk_xxx
  sms8 ${cmd} ... --api-key=sk_xxx
Get a free key at https://app.sms8.io/api.php`
    );
  }
  switch (cmd) {
    case 'setup':    return cmdSetup(apiKey);
    case 'send':     return cmdSend(apiKey);
    case 'otp':      return cmdOtp(apiKey);
    case 'inbox':    return cmdInbox(apiKey);
    case 'devices':  return cmdDevices(apiKey);
    case 'balance':  return cmdBalance(apiKey);
    default:         fatal(`Unknown command: ${cmd}\n\n${HELP}`);
  }
})().catch((e) => fatal(e?.message || String(e)));

async function resolveApiKey() {
  if (flags['api-key']) return String(flags['api-key']);
  if (process.env.SMS8_API_KEY) return process.env.SMS8_API_KEY;
  try {
    const raw = await fs.readFile(CFG_PATH, 'utf8');
    const cfg = JSON.parse(raw);
    if (cfg.api_key) return cfg.api_key;
  } catch {}
  return null;
}

async function cmdConfig() {
  const sub = positional[1];
  if (sub === 'get') {
    try {
      const raw = await fs.readFile(CFG_PATH, 'utf8');
      process.stdout.write(raw + '\n');
    } catch {
      process.stdout.write('{}\n');
    }
    return;
  }
  if (sub === 'set') {
    const pair = positional[2] || '';
    const eq = pair.indexOf('=');
    if (eq < 0) fatal('Use: sms8 config set api_key=sk_xxx');
    const k = pair.slice(0, eq);
    const v = pair.slice(eq + 1);
    await fs.mkdir(path.dirname(CFG_PATH), { recursive: true });
    let cfg = {};
    try { cfg = JSON.parse(await fs.readFile(CFG_PATH, 'utf8')); } catch {}
    cfg[k] = v;
    await fs.writeFile(CFG_PATH, JSON.stringify(cfg, null, 2));
    process.stdout.write(`Saved ${k} to ${CFG_PATH}\n`);
    return;
  }
  fatal('Use: sms8 config set api_key=sk_xxx  |  sms8 config get');
}

async function cmdSetup(apiKey) {
  const out = await mcp(apiKey, 'setup_sms8', {});
  print(out);
  if (out?.account) {
    process.stderr.write(`\nReady. ${out.account.email} (${out.account.credits} credits, ${out.account.expires_in}).\n`);
  }
}

async function cmdSend(apiKey) {
  const phone   = positional[1];
  const message = positional.slice(2).join(' ');
  if (!phone || !message) fatal('Use: sms8 send +14155550100 "Hello"');
  const out = await mcp(apiKey, 'send_sms', { phone, message });
  print(out);
}

async function cmdOtp(apiKey) {
  const sub = positional[1];
  if (sub === 'send') {
    const phone = positional[2];
    if (!phone) fatal('Use: sms8 otp send +14155550100');
    const out = await mcp(apiKey, 'send_otp', { phone });
    print(out);
    return;
  }
  if (sub === 'verify') {
    const phone = positional[2];
    const code  = positional[3];
    if (!phone || !code) fatal('Use: sms8 otp verify +14155550100 482937');
    const out = await mcp(apiKey, 'verify_otp', { phone, code });
    print(out);
    return;
  }
  if (sub === 'wait') {
    const phone   = positional[2];
    const timeout = parseInt(flags.timeout || '120', 10);
    if (!phone) fatal('Use: sms8 otp wait +14155550100 [--timeout=120]');
    const out = await mcp(apiKey, 'wait_for_otp', { phone, timeout_seconds: timeout });
    if (out?.code) process.stdout.write(out.code + '\n');
    else print(out);
    return;
  }
  fatal('Use: sms8 otp send|verify|wait …');
}

async function cmdInbox(apiKey) {
  const sinceArg = String(flags.since || '24h');
  const limit    = parseInt(flags.limit || '25', 10);
  const direction = flags.sent ? 'sent' : flags.received ? 'received' : 'all';
  const out = await mcp(apiKey, 'get_messages', { direction, limit });
  print(out);
}

async function cmdDevices(apiKey) {
  const out = await mcp(apiKey, 'list_devices', {});
  print(out);
}

async function cmdBalance(apiKey) {
  const out = await mcp(apiKey, 'get_balance', {});
  print(out);
}

async function mcp(apiKey, name, args) {
  const body = {
    jsonrpc: '2.0',
    method:  'tools/call',
    params:  { name, arguments: { api_key: apiKey, ...args } },
    id:      Date.now(),
  };
  const res = await fetch(MCP_URL, {
    method: 'POST',
    headers: {
      'Content-Type':  'application/json',
      'Authorization': `Bearer ${apiKey}`,
      'User-Agent':    `sms8-cli/${VERSION}`,
    },
    body: JSON.stringify(body),
  });
  if (!res.ok) fatal(`HTTP ${res.status}: ${await res.text().catch(() => '')}`);
  const env = await res.json();
  if (env.error) fatal(`${env.error.message}`);
  const text = env.result?.content?.[0]?.text;
  if (typeof text !== 'string') return env.result;
  try { return JSON.parse(text); } catch { return text; }
}

function print(out) {
  if (typeof out === 'string') process.stdout.write(out + '\n');
  else process.stdout.write(JSON.stringify(out, null, 2) + '\n');
}

function fatal(msg) {
  process.stderr.write(msg + '\n');
  process.exit(1);
}
