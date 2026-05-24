<?php
$page      = 'api';
$title     = 'SMS Gateway API for Vibe Coding: Claude Code, Cursor, Windsurf | SMS8';
$desc      = 'SMS gateway API built for AI-assisted coding. Send SMS and MMS, verify phone numbers, run USSD and read your inbox from Claude Code, Cursor, Windsurf or plain curl. Android-powered, no Twilio, no A2P 10DLC.';
$canonical = 'https://mcp.sms8.io/sms-api-documentation';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"SMS Gateway API for Vibe Coding","description":"Reference for the SMS8 SMS gateway API: endpoints, authentication, request and response shapes, device routing, and MCP integration for Claude Code, Cursor and Windsurf.","url":"https://mcp.sms8.io/sms-api-documentation","keywords":"sms gateway api, android sms gateway, sms api for claude code, sms api mcp, sms api cursor, sms api windsurf, vibe coding sms, sms otp api","publisher":{"@type":"Organization","name":"SMS8","url":"https://sms8.io"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"What is the SMS8 SMS gateway API?","acceptedAnswer":{"@type":"Answer","text":"An HTTP API that sends SMS and MMS, manages contacts, runs USSD, and reads your inbox. Every endpoint is a POST under https://app.sms8.io/services/ authenticated with a key parameter. Messages route through a paired Android phone so there is no A2P 10DLC and no per-message fee."}},
{"@type":"Question","name":"How do I use the SMS gateway from Claude Code, Cursor or Windsurf?","acceptedAnswer":{"@type":"Answer","text":"Add the MCP server at mcp.sms8.io to your AI tool with your SMS8 API key as a Bearer token. Claude Code, Cursor and Windsurf all support HTTP MCP servers. The MCP wraps the same REST endpoints in JSON-RPC tools like send_sms, send_otp and verify_otp."}},
{"@type":"Question","name":"How do I authenticate against the SMS gateway API?","acceptedAnswer":{"@type":"Answer","text":"Pass your SMS8 API key in the key field of the POST body. The same key is shown on app.sms8.io/api.php and authorises every endpoint."}},
{"@type":"Question","name":"What is the response format?","acceptedAnswer":{"@type":"Answer","text":"JSON. Every response has a top-level success boolean. On success the payload lives in data; on error a structured error object is returned with code and message."}},
{"@type":"Question","name":"Can I send SMS without a Twilio number?","acceptedAnswer":{"@type":"Answer","text":"Yes. SMS8 uses your own Android phone and SIM card as the gateway. No Twilio number, no A2P 10DLC registration, no per-message carrier fees."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<style>
/* Docs layout (scoped to this page): left nav, scrollable content */
.docs-shell {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 48px;
  max-width: 1280px;
  margin: 0 auto;
  padding: 120px 24px 64px;
}
.docs-side {
  position: sticky;
  top: 88px;
  align-self: start;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
  padding-right: 8px;
  border-right: 1px solid rgba(255,255,255,0.06);
}
.docs-side h4 {
  font-size: 11px; font-weight: 700; letter-spacing: 0.12em;
  color: #c4b5fd; text-transform: uppercase;
  margin: 18px 0 8px;
}
.docs-side h4:first-child { margin-top: 0; }
.docs-side a {
  display: block;
  padding: 6px 10px;
  font-size: 13.5px;
  color: #a8a8bd;
  border-radius: 6px;
  line-height: 1.45;
  border-left: 2px solid transparent;
  margin-left: -2px;
}
.docs-side a:hover { color: #fff; background: rgba(255,255,255,0.03); }
.docs-side a.is-active {
  color: #ddd6fe;
  background: rgba(168,85,247,0.08);
  border-left-color: #a855f7;
}
.docs-main { min-width: 0; max-width: 820px; }
.docs-main h1 {
  font-size: clamp(36px, 4.5vw, 52px);
  line-height: 1.08;
  letter-spacing: -0.025em;
  font-weight: 800;
  margin-bottom: 16px;
}
.docs-main .lede {
  font-size: 18px; color: #b8b8c8;
  line-height: 1.65;
  margin-bottom: 28px;
}
.docs-main .meta-pills {
  display: flex; flex-wrap: wrap; gap: 8px;
  margin-bottom: 36px;
}
.docs-main .meta-pills span {
  font-size: 12px; font-weight: 500;
  color: #c4b5fd;
  background: rgba(168,85,247,0.10);
  border: 1px solid rgba(168,85,247,0.25);
  padding: 5px 10px; border-radius: 999px;
}
.docs-main h2 {
  font-size: 26px; font-weight: 700;
  margin: 56px 0 14px;
  letter-spacing: -0.015em;
  scroll-margin-top: 96px;
}
.docs-main h2:first-of-type { margin-top: 0; }
.docs-main h3 {
  font-size: 17px; font-weight: 600;
  margin: 32px 0 10px;
  color: #ddd6fe;
  scroll-margin-top: 96px;
}
.docs-main p { margin-bottom: 14px; color: #c8c8d8; line-height: 1.72; }
.docs-main ul, .docs-main ol { margin: 0 0 16px 22px; color: #c8c8d8; }
.docs-main li { margin-bottom: 6px; line-height: 1.65; }
.docs-main code {
  font-family: 'JetBrains Mono', ui-monospace, 'SF Mono', Menlo, monospace;
  font-size: 0.88em;
  background: rgba(168,85,247,0.10);
  color: #ddd6fe;
  padding: 2px 6px; border-radius: 4px;
  border: 1px solid rgba(168,85,247,0.15);
}
.docs-main pre {
  background: #0a0a14;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  padding: 18px 20px;
  overflow-x: auto;
  font-family: 'JetBrains Mono', ui-monospace, 'SF Mono', Menlo, monospace;
  font-size: 13px; line-height: 1.65;
  color: #d8d8e8;
  margin: 16px 0 22px;
}
.docs-main pre code {
  background: transparent; border: 0; padding: 0; color: inherit; font-size: inherit;
}
.docs-main pre .k { color: #a5b4fc; }
.docs-main pre .s { color: #86efac; }
.docs-main pre .c { color: #6b7280; font-style: italic; }
.docs-main pre .n { color: #fcd34d; }
.docs-main table {
  width: 100%;
  border-collapse: collapse;
  margin: 16px 0 26px;
  font-size: 14px;
}
.docs-main thead th {
  text-align: left;
  font-size: 11px; font-weight: 700;
  color: #c4b5fd; text-transform: uppercase; letter-spacing: 0.08em;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.docs-main tbody td {
  padding: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  vertical-align: top;
  color: #c8c8d8;
}
.docs-main tbody tr:hover td { background: rgba(255,255,255,0.02); }
.docs-main tbody td:first-child { color: #ddd6fe; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 13px; white-space: nowrap; }
.docs-main tbody td:nth-child(2) { color: #a8a8bd; font-size: 12.5px; font-family: 'JetBrains Mono', ui-monospace, monospace; }

.callout {
  border: 1px solid rgba(168,85,247,0.25);
  background: rgba(168,85,247,0.06);
  border-left: 3px solid #a855f7;
  padding: 14px 18px;
  border-radius: 8px;
  margin: 20px 0 24px;
  font-size: 14.5px;
}
.callout strong { color: #ddd6fe; }
.endpoint-row {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 10px;
  margin-bottom: 10px;
}
.method-pill {
  font-size: 10px; font-weight: 800; letter-spacing: 0.08em;
  background: #86efac; color: #052e16;
  padding: 4px 8px; border-radius: 4px;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
}
.endpoint-row code {
  background: transparent; border: 0; padding: 0;
  color: #fff; font-size: 14px; font-weight: 500;
}
.endpoint-row .ep-desc {
  margin-left: auto; color: #888899; font-size: 13px;
}

/* Playground */
.playground {
  margin: 0 0 56px;
  border: 1px solid rgba(168,85,247,0.25);
  background: linear-gradient(180deg, rgba(168,85,247,0.06), rgba(99,102,241,0.03));
  border-radius: 14px;
  overflow: hidden;
}
.playground-head {
  padding: 18px 22px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.playground-head h2 { margin: 0 0 4px; font-size: 18px; color: #fff; }
.playground-head p { margin: 0; font-size: 13.5px; color: #a8a8bd; }
.playground-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
}
.playground-form {
  padding: 20px 22px;
  border-right: 1px solid rgba(255,255,255,0.06);
}
.pg-field { margin-bottom: 14px; }
.pg-field label {
  display: block;
  font-size: 12px; font-weight: 600;
  color: #c4b5fd;
  letter-spacing: 0.04em; text-transform: uppercase;
  margin-bottom: 6px;
}
.pg-field input,
.pg-field select,
.pg-field textarea {
  width: 100%;
  background: #0a0a14;
  border: 1px solid rgba(255,255,255,0.10);
  color: #e8e8f0;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.15s ease, background-color 0.15s ease;
}
.pg-field input:focus,
.pg-field select:focus,
.pg-field textarea:focus {
  border-color: rgba(168,85,247,0.6);
  background: #0d0d18;
}
.pg-field textarea { resize: vertical; min-height: 80px; font-family: inherit; }
.pg-field-mono input { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 13px; }
.pg-hint { font-size: 12px; color: #888899; margin-top: 5px; }
.pg-actions {
  display: flex; align-items: center; gap: 12px;
  margin-top: 6px;
}
.pg-actions .btn-cta { font-size: 14px; padding: 10px 18px; }
.pg-status {
  font-size: 13px;
  color: #888899;
}
.pg-status.is-ok    { color: #86efac; }
.pg-status.is-err   { color: #fca5a5; }
.pg-status.is-busy  { color: #c4b5fd; }

.playground-output {
  padding: 20px 22px;
  background: rgba(0,0,0,0.18);
  min-width: 0;
}
.pg-tabs {
  display: flex; flex-wrap: wrap; gap: 4px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  margin-bottom: 14px;
}
.pg-tab {
  background: transparent;
  border: 0;
  color: #888899;
  font-size: 13px; font-weight: 500;
  padding: 8px 12px;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  font-family: inherit;
  transition: color 0.15s ease, border-color 0.15s ease;
}
.pg-tab:hover { color: #ddd6fe; }
.pg-tab.is-active {
  color: #c4b5fd;
  border-bottom-color: #a855f7;
}
.pg-panel { display: none; }
.pg-panel.is-active { display: block; }
.pg-panel pre {
  margin: 0;
  max-height: 360px;
  font-size: 12.5px;
}
.pg-response {
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: 8px;
  background: #0a0a14;
  border: 1px solid rgba(255,255,255,0.08);
  font-size: 12.5px;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  color: #d8d8e8;
  max-height: 240px; overflow: auto;
  white-space: pre-wrap; word-break: break-word;
}
.pg-response.is-ok  { border-color: rgba(134,239,172,0.35); }
.pg-response.is-err { border-color: rgba(252,165,165,0.35); }
.pg-copy {
  position: absolute;
  top: 10px; right: 12px;
  background: rgba(168,85,247,0.18);
  border: 1px solid rgba(168,85,247,0.3);
  color: #ddd6fe;
  font-size: 11px; font-weight: 600;
  padding: 4px 10px; border-radius: 5px;
  cursor: pointer;
  font-family: inherit;
}
.pg-copy:hover { background: rgba(168,85,247,0.28); }
.pg-panel { position: relative; }

@media (max-width: 740px) {
  .playground-body { grid-template-columns: 1fr; }
  .playground-form { border-right: 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
}

@media (max-width: 900px) {
  .docs-shell { grid-template-columns: 1fr; padding-top: 96px; }
  .docs-side {
    position: static;
    max-height: none;
    border-right: 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding-bottom: 16px; margin-bottom: 28px;
  }
}
</style>

<div class="docs-shell">

  <aside class="docs-side" aria-label="API documentation sections">
    <h4>Try it</h4>
    <a href="#playground" class="docs-link">Live playground</a>

    <h4>Get started</h4>
    <a href="#overview" class="docs-link">Overview</a>
    <a href="#authentication" class="docs-link">Authentication</a>
    <a href="#first-call" class="docs-link">Your first SMS</a>
    <a href="#response-shape" class="docs-link">Response shape</a>
    <a href="#errors" class="docs-link">Errors</a>

    <h4>Endpoints</h4>
    <a href="#send" class="docs-link">Send SMS &amp; MMS</a>
    <a href="#read-messages" class="docs-link">Read messages</a>
    <a href="#resend" class="docs-link">Resend</a>
    <a href="#contacts" class="docs-link">Contacts</a>
    <a href="#devices" class="docs-link">Devices</a>
    <a href="#ussd" class="docs-link">USSD</a>
    <a href="#webhooks" class="docs-link">Inbound webhooks</a>

    <h4>Concepts</h4>
    <a href="#routing" class="docs-link">Device &amp; SIM routing</a>
    <a href="#scheduling" class="docs-link">Scheduling</a>
    <a href="#balance" class="docs-link">Credit balance</a>

    <h4>AI tools</h4>
    <a href="#mcp" class="docs-link">Claude Code, Cursor, Windsurf</a>

    <h4>SDKs</h4>
    <a href="#sdk-php" class="docs-link">PHP</a>
    <a href="#sdk-csharp" class="docs-link">C#</a>
    <a href="#sdk-curl" class="docs-link">curl</a>

    <h4>Reference</h4>
    <a href="#faq" class="docs-link">FAQ</a>
  </aside>

  <article class="docs-main">

    <h1>SMS gateway API for <span class="gradient-text">vibe coders</span></h1>
    <p class="lede">SMS8 is a developer-first SMS gateway built on your own Android phone. Send SMS, MMS, OTP codes and USSD from Claude Code, Cursor, Windsurf or plain curl. One key, one base URL, JSON in and out.</p>
    <div class="meta-pills">
      <span>REST · form-urlencoded</span>
      <span>JSON response</span>
      <span>MCP-ready</span>
      <span>No A2P 10DLC</span>
      <span>No Twilio</span>
    </div>

    <h2 id="playground">Live playground</h2>
    <p>Try the SMS gateway right here. Paste your API key, fill in a phone and message, hit <strong>Send</strong>. We will POST to <code>/services/send.php</code> on your behalf and show the real JSON response, then switch tabs to grab the exact request as <strong>curl</strong>, <strong>PHP</strong>, <strong>JavaScript</strong>, <strong>Python</strong> or as a <strong>Claude Code / Cursor</strong> prompt.</p>

    <div class="playground" id="pg">
      <div class="playground-head">
        <h2>Test &amp; copy</h2>
        <p>Sends one real SMS. Uses 1 credit. Your API key never leaves your browser. The request goes directly to app.sms8.io.</p>
      </div>
      <div class="playground-body">
        <form class="playground-form" id="pg-form" autocomplete="off">
          <div class="pg-field pg-field-mono">
            <label for="pg-key">API key</label>
            <input type="text" id="pg-key" name="key" placeholder="sk_…" spellcheck="false" required>
            <p class="pg-hint">From <a href="https://app.sms8.io/api.php" target="_blank" rel="noopener">app.sms8.io/api.php</a> · stays in your browser</p>
          </div>
          <div class="pg-field">
            <label for="pg-number">Phone number</label>
            <input type="text" id="pg-number" name="number" placeholder="+11234567890" required>
          </div>
          <div class="pg-field">
            <label for="pg-message">Message</label>
            <textarea id="pg-message" name="message" rows="3" placeholder="Hello from the SMS8 playground" required></textarea>
          </div>
          <div class="pg-field">
            <label for="pg-option">Routing</label>
            <select id="pg-option" name="option">
              <option value="">Primary device (default)</option>
              <option value="1">Use all devices (option=1)</option>
              <option value="2">Use all SIMs (option=2)</option>
            </select>
          </div>
          <div class="pg-field">
            <label for="pg-device">Device ID (optional)</label>
            <input type="text" id="pg-device" name="devices" placeholder="e.g. 1 or 2|0">
            <p class="pg-hint">Leave blank for primary. Use <code>2|0</code> for device 2, SIM slot 0.</p>
          </div>
          <div class="pg-actions">
            <button type="submit" class="btn-cta" id="pg-submit">Send SMS</button>
            <span class="pg-status" id="pg-status">Ready</span>
          </div>
        </form>

        <div class="playground-output">
          <div class="pg-tabs" role="tablist">
            <button type="button" class="pg-tab is-active" data-lang="curl"   role="tab">curl</button>
            <button type="button" class="pg-tab"            data-lang="php"    role="tab">PHP</button>
            <button type="button" class="pg-tab"            data-lang="js"     role="tab">JavaScript</button>
            <button type="button" class="pg-tab"            data-lang="python" role="tab">Python</button>
            <button type="button" class="pg-tab"            data-lang="cli"    role="tab">Claude CLI</button>
            <button type="button" class="pg-tab"            data-lang="mcp"    role="tab">AI prompt</button>
          </div>
          <div class="pg-panel is-active" data-lang="curl">
            <button type="button" class="pg-copy" data-target="pg-code-curl">Copy</button>
            <pre id="pg-code-curl"></pre>
          </div>
          <div class="pg-panel" data-lang="php">
            <button type="button" class="pg-copy" data-target="pg-code-php">Copy</button>
            <pre id="pg-code-php"></pre>
          </div>
          <div class="pg-panel" data-lang="js">
            <button type="button" class="pg-copy" data-target="pg-code-js">Copy</button>
            <pre id="pg-code-js"></pre>
          </div>
          <div class="pg-panel" data-lang="python">
            <button type="button" class="pg-copy" data-target="pg-code-python">Copy</button>
            <pre id="pg-code-python"></pre>
          </div>
          <div class="pg-panel" data-lang="cli">
            <button type="button" class="pg-copy" data-target="pg-code-cli">Copy</button>
            <pre id="pg-code-cli"></pre>
          </div>
          <div class="pg-panel" data-lang="mcp">
            <button type="button" class="pg-copy" data-target="pg-code-mcp">Copy</button>
            <pre id="pg-code-mcp"></pre>
          </div>
          <div class="pg-response" id="pg-response" hidden></div>
        </div>
      </div>
    </div>

    <h2 id="overview">Overview</h2>
    <p>The SMS8 SMS gateway exposes a single host with one endpoint per capability. There are no path parameters and no nested resources. Every action is a flat <code>POST</code> with form-encoded fields.</p>
    <ul>
      <li><strong>Base URL</strong>: <code>https://app.sms8.io/services/</code></li>
      <li><strong>Method</strong>: <code>POST</code> on every endpoint</li>
      <li><strong>Body</strong>: <code>application/x-www-form-urlencoded</code></li>
      <li><strong>Response</strong>: <code>application/json</code></li>
      <li><strong>Auth</strong>: <code>key=YOUR_SMS8_API_KEY</code> in the body</li>
    </ul>
    <div class="callout">
      <strong>Vibe coding shortcut.</strong> If you live in Claude Code, Cursor or Windsurf, skip the curl and add the MCP server at <code>mcp.sms8.io</code> so your AI assistant calls these endpoints for you. See <a href="#mcp">AI tools</a>.
    </div>

    <h2 id="authentication">Authentication</h2>
    <p>Every request sends the API key in the POST body. The key is per-account and authorises every endpoint. Grab it from the API page of your dashboard at <a href="https://app.sms8.io/api.php">app.sms8.io/api.php</a> and regenerate any time. Old keys are invalidated immediately.</p>
<pre><span class="c"># every request</span>
key=YOUR_SMS8_API_KEY</pre>
    <div class="callout">
      <strong>Tip.</strong> The MCP server at <code>mcp.sms8.io</code> accepts the same key as a Bearer header (<code>Authorization: Bearer YOUR_SMS8_API_KEY</code>). Useful when the tool only supports header-based auth.
    </div>

    <h2 id="first-call">Your first SMS</h2>
    <p>Send a message to one number from a paired Android using the primary device. Replace <code>YOUR_SMS8_API_KEY</code> and the destination number.</p>
<pre>curl https://app.sms8.io/services/send.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"number=+11234567890"</span> \
  -d <span class="s">"message=Hello from SMS8"</span></pre>

    <h2 id="response-shape">Response shape</h2>
    <p>Every endpoint wraps its payload in a <code>success</code> envelope.</p>
<pre>{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"data"</span>: {
    <span class="k">"messages"</span>: [
      {
        <span class="k">"ID"</span>: <span class="s">"1"</span>,
        <span class="k">"number"</span>: <span class="s">"+11234567890"</span>,
        <span class="k">"message"</span>: <span class="s">"Hello from SMS8"</span>,
        <span class="k">"deviceID"</span>: <span class="s">"1"</span>,
        <span class="k">"simSlot"</span>: <span class="s">"0"</span>,
        <span class="k">"status"</span>: <span class="s">"Pending"</span>,
        <span class="k">"type"</span>: <span class="s">"sms"</span>,
        <span class="k">"sentDate"</span>: <span class="s">"2026-05-24T10:30:00+00:00"</span>,
        <span class="k">"deliveredDate"</span>: <span class="n">null</span>,
        <span class="k">"groupID"</span>: <span class="s">"…"</span>
      }
    ]
  }
}</pre>

    <h2 id="errors">Errors</h2>
    <p>On failure, <code>success</code> is <code>false</code> and the body carries a structured <code>error</code>:</p>
<pre>{
  <span class="k">"success"</span>: <span class="n">false</span>,
  <span class="k">"error"</span>: {
    <span class="k">"code"</span>: <span class="n">401</span>,
    <span class="k">"message"</span>: <span class="s">"Invalid API key"</span>
  }
}</pre>
    <table>
      <thead><tr><th>Code</th><th>Meaning</th></tr></thead>
      <tbody>
        <tr><td>400</td><td>Missing or malformed parameters</td></tr>
        <tr><td>401</td><td>Missing or invalid API key</td></tr>
        <tr><td>402</td><td>Out of message credits</td></tr>
        <tr><td>403</td><td>Action not allowed for this account or plan</td></tr>
        <tr><td>404</td><td>Resource not found (e.g. unknown message ID)</td></tr>
        <tr><td>429</td><td>Rate limit hit, slow down and retry</td></tr>
        <tr><td>500</td><td>Server error, please retry</td></tr>
      </tbody>
    </table>

    <h2 id="send">Send SMS &amp; MMS</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/send.php</code>
      <span class="ep-desc">single, batch, list broadcast, MMS</span>
    </div>
    <p>One endpoint covers four send modes: single message, batch (different message per number), broadcast to a contacts list, and MMS with attachments. Pick the mode by which fields you send.</p>
    <table>
      <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
      <tbody>
        <tr><td>key</td><td>string</td><td>API key, required</td></tr>
        <tr><td>number</td><td>string</td><td>E.164 phone, or comma-separated list, for the single/blast mode</td></tr>
        <tr><td>message</td><td>string</td><td>Message body, required with <code>number</code> or <code>listID</code></td></tr>
        <tr><td>messages</td><td>JSON</td><td>Array of <code>{number, message, type, attachments}</code> for true batch mode</td></tr>
        <tr><td>listID</td><td>int</td><td>Send to every contact in this list</td></tr>
        <tr><td>devices</td><td>JSON|int</td><td>Single ID, JSON array, or SIM-qualified form <code>"2|0"</code></td></tr>
        <tr><td>option</td><td>0|1|2</td><td>See <a href="#routing">routing</a>: specified, all devices, or all SIMs</td></tr>
        <tr><td>useRandomDevice</td><td>0|1</td><td>Pick one device at random from the selection</td></tr>
        <tr><td>prioritize</td><td>0|1</td><td>Jump the queue (OTPs, replies)</td></tr>
        <tr><td>type</td><td>sms|mms</td><td>Defaults to sms</td></tr>
        <tr><td>attachments</td><td>string</td><td>Comma-separated image URLs (MMS only)</td></tr>
        <tr><td>schedule</td><td>unix ts</td><td>Send at this future timestamp</td></tr>
      </tbody>
    </table>

    <h3>Send a batch with one call</h3>
<pre>curl https://app.sms8.io/services/send.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">'messages=[{"number":"+1...","message":"hi 1"},{"number":"+1...","message":"hi 2"}]'</span> \
  -d <span class="s">"option=2"</span></pre>

    <h3>Broadcast to a contacts list</h3>
<pre>curl https://app.sms8.io/services/send.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"listID=1"</span> \
  -d <span class="s">"message=Sale ends tonight"</span></pre>

    <h2 id="read-messages">Read messages</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/read-messages.php</code>
      <span class="ep-desc">by ID, group, status, device, time</span>
    </div>
    <table>
      <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
      <tbody>
        <tr><td>key</td><td>string</td><td>API key, required</td></tr>
        <tr><td>id</td><td>int</td><td>Get one message by ID</td></tr>
        <tr><td>groupId</td><td>string</td><td>Group ID returned at send time, useful for batches</td></tr>
        <tr><td>status</td><td>string</td><td><code>Received</code>, <code>Sent</code>, <code>Pending</code>, <code>Failed</code></td></tr>
        <tr><td>deviceID</td><td>int</td><td>Filter by device</td></tr>
        <tr><td>simSlot</td><td>int</td><td>0 for first SIM, 1 for second</td></tr>
        <tr><td>startTimestamp</td><td>unix ts</td><td>Inclusive lower bound</td></tr>
        <tr><td>endTimestamp</td><td>unix ts</td><td>Inclusive upper bound</td></tr>
      </tbody>
    </table>
<pre><span class="c"># last 24h of received SMS on SIM 0 of device 8</span>
curl https://app.sms8.io/services/read-messages.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"status=Received"</span> \
  -d <span class="s">"deviceID=8"</span> \
  -d <span class="s">"simSlot=0"</span> \
  -d <span class="s">"startTimestamp=$(( $(date +%s) - 86400 ))"</span></pre>

    <h2 id="resend">Resend</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/resend.php</code>
      <span class="ep-desc">retry by ID, group, status</span>
    </div>
    <p>Same filter shape as <a href="#read-messages">read-messages</a>. Retry one ID, every message in a group, or every message of a given status in a time window.</p>

    <h2 id="contacts">Contacts</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/manage-contacts.php</code>
      <span class="ep-desc">add, resubscribe, unsubscribe</span>
    </div>
    <table>
      <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
      <tbody>
        <tr><td>key</td><td>string</td><td>API key, required</td></tr>
        <tr><td>listID</td><td>int</td><td>Target contacts list, required</td></tr>
        <tr><td>number</td><td>string</td><td>Contact phone, required</td></tr>
        <tr><td>name</td><td>string</td><td>Friendly name (add only)</td></tr>
        <tr><td>resubscribe</td><td>0|1</td><td>Resubscribe if previously unsubscribed</td></tr>
        <tr><td>unsubscribe</td><td>0|1</td><td>Remove from list</td></tr>
      </tbody>
    </table>

    <h2 id="devices">Devices</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/get-devices.php</code>
      <span class="ep-desc">list paired Android devices</span>
    </div>
    <p>Returns every enabled device with model, SIM slots and primary flag. Useful before constructing <code>devices</code> on a send call.</p>

    <h2 id="ussd">USSD</h2>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/send-ussd-request.php</code>
      <span class="ep-desc">e.g. *150# to check balance</span>
    </div>
    <div class="endpoint-row">
      <span class="method-pill">POST</span>
      <code>/services/read-ussd-requests.php</code>
      <span class="ep-desc">look up sent USSD requests</span>
    </div>
    <p>Run a carrier USSD code on a paired device and read back the response. Handy for prepaid balance checks and operator menus.</p>

    <h2 id="webhooks">Inbound webhooks</h2>
    <p>Set a webhook URL on the API page of your dashboard. SMS8 will <code>POST</code> every received SMS to that URL with an HMAC-SHA256 signature, so your receiver can verify the payload came from SMS8 and was not tampered with.</p>
    <p>From AI tools, register the webhook via the MCP <code>create_webhook</code> tool. It validates the URL against an SSRF block-list before saving.</p>

    <h2 id="routing">Device &amp; SIM routing</h2>
    <p>Every send endpoint takes the same routing controls. Mix them to load-balance across phones and SIMs.</p>
    <table>
      <thead><tr><th>option</th><th>Behaviour</th></tr></thead>
      <tbody>
        <tr><td>0</td><td>Use exactly the IDs in <code>devices</code>. SIM slots use the form <code>"2|0"</code> (device 2, SIM 0).</td></tr>
        <tr><td>1</td><td>Use every enabled device, default SIM each. Batch is split across them.</td></tr>
        <tr><td>2</td><td>Use every enabled device and every SIM. Maximum throughput on dual-SIM phones.</td></tr>
      </tbody>
    </table>
    <p>Set <code>useRandomDevice=1</code> to pick exactly one sender from the selection at random, useful for OTPs.</p>

    <h2 id="scheduling">Scheduling</h2>
    <p>Pass <code>schedule</code> as a unix timestamp to defer a send. Schedule the same message to many numbers, or send a batch on a future date. Both work.</p>

    <h2 id="balance">Credit balance</h2>
    <p>Call <code>/services/send.php</code> with only the <code>key</code> field. The response's <code>credits</code> field returns the remaining credits or <code>"Unlimited"</code>.</p>

    <h2 id="mcp">Use it from Claude Code, Cursor &amp; Windsurf</h2>
    <p>SMS8 ships an MCP server at <code>mcp.sms8.io</code> that wraps every REST endpoint above in a JSON-RPC tool. Same API key, no separate account.</p>
<pre><span class="c">// ~/.config/claude/mcp-servers.json (Claude Code)</span>
<span class="c">// ~/.cursor/mcp.json (Cursor)</span>
<span class="c">// ~/.codeium/windsurf/mcp_config.json (Windsurf)</span>
{
  <span class="k">"mcpServers"</span>: {
    <span class="k">"sms8"</span>: {
      <span class="k">"url"</span>: <span class="s">"https://mcp.sms8.io"</span>,
      <span class="k">"transport"</span>: <span class="s">"http"</span>,
      <span class="k">"headers"</span>: {
        <span class="k">"Authorization"</span>: <span class="s">"Bearer YOUR_SMS8_API_KEY"</span>
      }
    }
  }
}</pre>
    <table>
      <thead><tr><th>MCP tool</th><th>Wraps</th></tr></thead>
      <tbody>
        <tr><td>send_sms</td><td>/services/send.php</td></tr>
        <tr><td>send_otp</td><td>OTP store + /services/send.php</td></tr>
        <tr><td>verify_otp</td><td>OTP store, constant-time compare</td></tr>
        <tr><td>get_messages</td><td>/services/read-messages.php</td></tr>
        <tr><td>list_devices</td><td>/services/get-devices.php</td></tr>
        <tr><td>create_webhook</td><td>user webhook URL (SSRF-checked)</td></tr>
        <tr><td>setup_sms8</td><td>account + devices handshake</td></tr>
      </tbody>
    </table>

    <h2 id="sdk-php">PHP SDK</h2>
    <p>The dashboard at <a href="https://app.sms8.io/api.php">app.sms8.io/api.php</a> renders a complete PHP class with your API key prefilled. It includes <code>sendSingleMessage</code>, <code>sendMessages</code>, <code>sendMessageToContactsList</code>, <code>getMessageByID</code>, <code>getMessagesByStatus</code>, <code>resendMessageByID</code>, <code>addContact</code>, <code>unsubscribeContact</code>, <code>getBalance</code>, <code>sendUssdRequest</code>, <code>getDevices</code>.</p>
<pre>$msg = sendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"Hello from SMS8"</span>);

$msg = sendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"From device 1"</span>, <span class="n">1</span>);

$msg = sendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"From SIM 0"</span>, <span class="s">"1|0"</span>);

$msg = sendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"In 2 min"</span>, <span class="n">null</span>, strtotime(<span class="s">"+2 minutes"</span>));</pre>

    <h2 id="sdk-csharp">C# SDK</h2>
    <p>A complete C# <code>API</code> class is also prefilled on the dashboard. Same surface as the PHP SDK, ready for .NET / Unity / Xamarin projects.</p>
<pre>SMS.API.SendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"Hello from SMS8"</span>);

var msg = SMS.API.SendSingleMessage(<span class="s">"+11234567890"</span>, <span class="s">"From device 1"</span>, <span class="s">"1"</span>);

var msgs = SMS.API.SendMessages(messages, SMS.API.Option.USE_ALL_SIMS);</pre>

    <h2 id="sdk-curl">curl recipes</h2>
<pre><span class="c"># Send and prioritize an OTP-style message</span>
curl https://app.sms8.io/services/send.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"number=+11234567890"</span> \
  -d <span class="s">"message=Your code is 482910"</span> \
  -d <span class="s">"prioritize=1"</span> \
  -d <span class="s">"devices=1"</span>

<span class="c"># Get message credits</span>
curl https://app.sms8.io/services/send.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span>

<span class="c"># Add a contact and resubscribe if they exist</span>
curl https://app.sms8.io/services/manage-contacts.php \
  -d <span class="s">"key=YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"listID=1"</span> \
  -d <span class="s">"number=+11234567890"</span> \
  -d <span class="s">"name=Alex"</span> \
  -d <span class="s">"resubscribe=1"</span></pre>

    <h2 id="faq">FAQ</h2>
    <h3>What is the SMS8 SMS gateway API?</h3>
    <p>An HTTP API that sends SMS and MMS, manages contacts, runs USSD, and reads your inbox. Every endpoint is a POST under <code>https://app.sms8.io/services/</code> authenticated with a <code>key</code> parameter. Messages route through a paired Android phone so there is no A2P 10DLC and no per-message fee.</p>

    <h3>How do I use this from Claude Code, Cursor or Windsurf?</h3>
    <p>Add the MCP server at <code>mcp.sms8.io</code> to your AI tool with your SMS8 API key as a Bearer token. The MCP wraps the same REST endpoints in JSON-RPC tools like <code>send_sms</code>, <code>send_otp</code> and <code>verify_otp</code>. See the <a href="#mcp">AI tools section</a>.</p>

    <h3>Can I send without a Twilio number?</h3>
    <p>Yes. SMS8 uses your own Android phone and SIM card as the gateway. No Twilio number, no A2P 10DLC registration, no per-message carrier fees.</p>

    <h3>How do I receive inbound SMS?</h3>
    <p>Set a webhook URL on the API page of your dashboard. SMS8 POSTs every received SMS to that URL with an HMAC signature so you can verify the payload.</p>

    <h3>What rate limits apply?</h3>
    <p>SMS throughput is bounded by the speed of your paired devices and your plan's monthly volume. The OTP flow adds a hard cap of 5 codes per phone per 24h plus a configurable resend cooldown.</p>

    <h3>How do I rotate the API key?</h3>
    <p>Regenerate it from the dashboard at <a href="https://app.sms8.io/api.php">app.sms8.io/api.php</a>. The old key is invalidated immediately.</p>

    <div style="margin-top: 56px; padding: 28px; border-radius: 14px; background: linear-gradient(135deg, rgba(168,85,247,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(168,85,247,0.25);">
      <h2 style="margin: 0 0 8px; font-size: 22px;">Build your first integration</h2>
      <p style="margin: 0 0 16px; color: #c8c8d8;">Sign up free, pair your Android, grab your key, ship.</p>
      <a class="btn-cta" href="https://app.sms8.io/">Create free account</a>
      &nbsp;
      <a class="btn-ghost" href="/sms-otp-verification-api">Read OTP docs</a>
    </div>

  </article>
</div>

<script>
(function(){
  // ── API playground ─────────────────────────────────────────────────
  var form    = document.getElementById('pg-form');
  if (!form) return;
  var status  = document.getElementById('pg-status');
  var submit  = document.getElementById('pg-submit');
  var respEl  = document.getElementById('pg-response');
  var keyEl   = document.getElementById('pg-key');
  var numEl   = document.getElementById('pg-number');
  var msgEl   = document.getElementById('pg-message');
  var optEl   = document.getElementById('pg-option');
  var devEl   = document.getElementById('pg-device');

  function esc(s){ return String(s).replace(/[\\"'`$]/g, function(c){ return '\\' + c; }); }
  function jsStr(s){ return JSON.stringify(String(s)); }
  function maskKey(k){
    if (!k) return 'YOUR_SMS8_API_KEY';
    if (k.length <= 8) return k;
    return k.slice(0, 4) + '…' + k.slice(-4);
  }

  function renderSnippets(){
    var key    = keyEl.value.trim() || 'YOUR_SMS8_API_KEY';
    var number = numEl.value.trim() || '+11234567890';
    var msg    = msgEl.value || 'Hello from SMS8';
    var opt    = optEl.value;
    var dev    = devEl.value.trim();

    // curl
    var lines = ['curl https://app.sms8.io/services/send.php \\\n  -d "key=' + key + '" \\\n  -d "number=' + number + '" \\\n  -d "message=' + msg.replace(/"/g,'\\"') + '"'];
    if (opt) lines[0] += ' \\\n  -d "option=' + opt + '"';
    if (dev) lines[0] += ' \\\n  -d "devices=' + dev + '"';
    document.getElementById('pg-code-curl').textContent = lines[0];

    // PHP
    var phpData = "  'key'     => '" + esc(key) + "',\n  'number'  => '" + esc(number) + "',\n  'message' => '" + esc(msg) + "'";
    if (opt) phpData += ",\n  'option'  => '" + opt + "'";
    if (dev) phpData += ",\n  'devices' => '" + esc(dev) + "'";
    var php = "$ch = curl_init('https://app.sms8.io/services/send.php');\n" +
              "curl_setopt($ch, CURLOPT_POST, true);\n" +
              "curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\n" +
              "curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([\n" + phpData + "\n]));\n" +
              "$res = json_decode(curl_exec($ch), true);\n" +
              "if ($res['success']) {\n" +
              "    $msg = $res['data']['messages'][0];\n" +
              "}";
    document.getElementById('pg-code-php').textContent = php;

    // JS / Node fetch
    var jsBody = "    key: " + jsStr(key) + ",\n" +
                 "    number: " + jsStr(number) + ",\n" +
                 "    message: " + jsStr(msg);
    if (opt) jsBody += ",\n    option: " + jsStr(opt);
    if (dev) jsBody += ",\n    devices: " + jsStr(dev);
    var js = "const res = await fetch('https://app.sms8.io/services/send.php', {\n" +
             "  method: 'POST',\n" +
             "  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },\n" +
             "  body: new URLSearchParams({\n" + jsBody + "\n  })\n" +
             "});\n" +
             "const json = await res.json();\n" +
             "if (json.success) {\n" +
             "  console.log(json.data.messages[0]);\n" +
             "}";
    document.getElementById('pg-code-js').textContent = js;

    // Python
    var pyData = "    'key': " + jsStr(key) + ",\n" +
                 "    'number': " + jsStr(number) + ",\n" +
                 "    'message': " + jsStr(msg);
    if (opt) pyData += ",\n    'option': " + jsStr(opt);
    if (dev) pyData += ",\n    'devices': " + jsStr(dev);
    var py = "import requests\n\n" +
             "res = requests.post('https://app.sms8.io/services/send.php', data={\n" + pyData + "\n})\n" +
             "json = res.json()\n" +
             "if json['success']:\n" +
             "    msg = json['data']['messages'][0]\n" +
             "    print(msg)";
    document.getElementById('pg-code-python').textContent = py;

    // MCP / AI-assistant prompt
    var aiExtras = '';
    if (opt === '1') aiExtras = ' Use all my paired devices.';
    else if (opt === '2') aiExtras = ' Spread across all SIMs of all paired devices.';
    else if (dev) aiExtras = ' Use device ' + dev + '.';
    var prompt = 'Send an SMS to ' + number + ' saying "' + msg.replace(/"/g,'\\"') + '"' +
                 ' via the sms8 MCP.' + aiExtras;
    var maskedKey = maskKey(keyEl.value.trim());
    var mcpConfigBlock =
        "{\n" +
        "  \"mcpServers\": {\n" +
        "    \"sms8\": {\n" +
        "      \"url\": \"https://mcp.sms8.io\",\n" +
        "      \"transport\": \"http\",\n" +
        "      \"headers\": {\n" +
        "        \"Authorization\": \"Bearer " + maskedKey + "\"\n" +
        "      }\n" +
        "    }\n" +
        "  }\n" +
        "}";
    var mcp = "// In Claude Code, Cursor or Windsurf with the SMS8 MCP added,\n" +
              "// paste this prompt:\n\n" +
              prompt + "\n\n" +
              "// MCP config (one-time):\n" +
              "// ~/.config/claude/mcp-servers.json (Claude Code)\n" +
              "// ~/.cursor/mcp.json (Cursor)\n" +
              "// ~/.codeium/windsurf/mcp_config.json (Windsurf)\n" +
              mcpConfigBlock;
    document.getElementById('pg-code-mcp').textContent = mcp;

    // Claude CLI: one-liner that registers the MCP just for this run
    // and prints the assistant's reply. Uses the real claude CLI flags.
    var cliConfig = '{"mcpServers":{"sms8":{"url":"https://mcp.sms8.io","transport":"http","headers":{"Authorization":"Bearer ' + maskedKey + '"}}}}';
    var cli = "# Already set up the SMS8 MCP? Just describe the job:\n" +
              "claude -p " + JSON.stringify(prompt) + "\n\n" +
              "# Not set up yet? Pass the MCP config inline for one run:\n" +
              "claude -p " + JSON.stringify(prompt) + " \\\n" +
              "  --mcp-config " + JSON.stringify(cliConfig) + " \\\n" +
              "  --allowedTools \"mcp__sms8__send_sms\"\n\n" +
              "# Or start an interactive session:\n" +
              "claude --mcp-config " + JSON.stringify(cliConfig);
    document.getElementById('pg-code-cli').textContent = cli;
  }

  // Tab switching
  var tabs   = form.parentElement.querySelectorAll('.pg-tab');
  var panels = form.parentElement.querySelectorAll('.pg-panel');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      var lang = t.getAttribute('data-lang');
      tabs.forEach(function(x){ x.classList.toggle('is-active', x === t); });
      panels.forEach(function(p){ p.classList.toggle('is-active', p.getAttribute('data-lang') === lang); });
    });
  });

  // Copy buttons
  form.parentElement.querySelectorAll('.pg-copy').forEach(function(b){
    b.addEventListener('click', function(){
      var tgt = document.getElementById(b.getAttribute('data-target'));
      if (!tgt) return;
      navigator.clipboard.writeText(tgt.textContent).then(function(){
        var prev = b.textContent;
        b.textContent = 'Copied';
        setTimeout(function(){ b.textContent = prev; }, 1200);
      });
    });
  });

  // Re-render snippets on any field change (or load)
  [keyEl, numEl, msgEl, optEl, devEl].forEach(function(el){
    el.addEventListener('input',  renderSnippets);
    el.addEventListener('change', renderSnippets);
  });
  renderSnippets();

  // Submit → real send via app.sms8.io (CORS allowed for *.sms8.io)
  form.addEventListener('submit', function(e){
    e.preventDefault();
    if (!keyEl.value.trim()) { status.textContent = 'Add your API key first'; status.className = 'pg-status is-err'; return; }
    submit.disabled = true;
    status.textContent = 'Sending…';
    status.className  = 'pg-status is-busy';
    respEl.hidden = true;

    var body = new URLSearchParams();
    body.set('key',     keyEl.value.trim());
    body.set('number',  numEl.value.trim());
    body.set('message', msgEl.value);
    if (optEl.value) body.set('option',  optEl.value);
    if (devEl.value.trim()) body.set('devices', devEl.value.trim());

    fetch('https://app.sms8.io/services/send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(function(r){ return r.text().then(function(t){ return { ok: r.ok, status: r.status, text: t }; }); })
    .then(function(out){
      var parsed; try { parsed = JSON.parse(out.text); } catch(e) { parsed = null; }
      var pretty = parsed ? JSON.stringify(parsed, null, 2) : out.text;
      respEl.hidden = false;
      respEl.textContent = pretty;
      if (parsed && parsed.success) {
        status.textContent = 'Sent · check the device';
        status.className   = 'pg-status is-ok';
        respEl.classList.add('is-ok'); respEl.classList.remove('is-err');
      } else {
        var msg = (parsed && parsed.error && parsed.error.message) || ('HTTP ' + out.status);
        status.textContent = 'Failed · ' + msg;
        status.className   = 'pg-status is-err';
        respEl.classList.add('is-err'); respEl.classList.remove('is-ok');
      }
    })
    .catch(function(err){
      status.textContent = 'Network error · ' + err.message;
      status.className   = 'pg-status is-err';
      respEl.hidden = false;
      respEl.textContent = err.message;
      respEl.classList.add('is-err'); respEl.classList.remove('is-ok');
    })
    .finally(function(){ submit.disabled = false; });
  });
})();

(function(){
  // Highlight the side-nav link of the section currently in view.
  var links = document.querySelectorAll('.docs-side a.docs-link');
  if (!links.length) return;
  var map = {};
  links.forEach(function(a){
    var id = a.getAttribute('href').slice(1);
    var el = document.getElementById(id);
    if (el) map[id] = a;
  });
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if (e.isIntersecting) {
        links.forEach(function(l){ l.classList.remove('is-active'); });
        var a = map[e.target.id];
        if (a) a.classList.add('is-active');
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
  Object.keys(map).forEach(function(id){
    var el = document.getElementById(id);
    if (el) io.observe(el);
  });
})();
</script>

<?php require __DIR__ . '/_footer.php'; ?>
