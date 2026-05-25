<?php
$page      = 'openclaw';
$title     = 'OpenClaw SMS MCP Server: Send SMS From Your Personal AI Agent';
$desc      = 'Give OpenClaw (Peter Steinberger\'s open-source personal AI) a real phone number. Add the SMS8 MCP block to openclaw.json, point at mcp.sms8.io, and your local agent texts and reads SMS through your own paired Android. Free trial, no Twilio.';
$canonical = 'https://mcp.sms8.io/openclaw-sms-mcp-server';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"OpenClaw SMS MCP Server","description":"Send SMS, issue OTPs, wait for incoming codes and register webhooks from an OpenClaw session using your own paired Android phone. SMS8 MCP plugs into openclaw.json as a streamable-http remote MCP server.","url":"https://mcp.sms8.io/openclaw-sms-mcp-server","publisher":{"@type":"Organization","name":"SMS8.io","url":"https://sms8.io"},"keywords":"openclaw mcp, openclaw sms, openclaw sms gateway, send sms from openclaw, openclaw otp, sms mcp openclaw, personal ai sms, ai assistant sms whatsapp, sms8 openclaw, mcp android phone gateway"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"Add an SMS MCP server to OpenClaw","totalTime":"PT3M","step":[
{"@type":"HowToStep","position":1,"name":"Install OpenClaw","text":"Run curl -fsSL https://openclaw.ai/install.sh | bash or npm i -g openclaw on Mac, Windows or Linux."},
{"@type":"HowToStep","position":2,"name":"Get an SMS8 API key","text":"Sign up at app.sms8.io. Pair an Android phone with the dashboard QR. Copy the API key from the API page."},
{"@type":"HowToStep","position":3,"name":"Add SMS8 under mcp.servers","text":"Open ~/.openclaw/openclaw.json. Add an entry named sms8 with transport set to streamable-http, url set to https://mcp.sms8.io, and headers.Authorization carrying Bearer ${SMS8_API_KEY}. OpenClaw hot-reloads."}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"How do I send SMS from OpenClaw?","acceptedAnswer":{"@type":"Answer","text":"Add the SMS8 MCP entry to ~/.openclaw/openclaw.json under mcp.servers.sms8. Set transport to streamable-http, url to https://mcp.sms8.io, and headers.Authorization to Bearer ${SMS8_API_KEY}. OpenClaw hot-reloads the file. The agent now has send_sms, send_otp, verify_otp, wait_for_otp, get_messages, list_devices, get_balance and create_webhook tools, routed through your paired Android."}},
{"@type":"Question","name":"Does OpenClaw support MCP servers?","acceptedAnswer":{"@type":"Answer","text":"Yes. OpenClaw is built around Model Context Protocol from day one. The mcp.servers section of ~/.openclaw/openclaw.json accepts local stdio servers (command + args) and remote HTTP servers (url + transport + headers). Hot reload is enabled by default."}},
{"@type":"Question","name":"How do I give my personal AI a phone number?","acceptedAnswer":{"@type":"Answer","text":"With SMS8 you pair your existing Android phone to the SMS8 dashboard once, then your AI agent uses that SIM as its outbound and inbound SMS gateway. The phone number your contacts see is the number you already own. No Twilio, no virtual number, no A2P 10DLC. Works with OpenClaw, Claude Code, Cursor, Windsurf and OpenCode out of the same SMS8 account."}},
{"@type":"Question","name":"Can OpenClaw read incoming SMS?","acceptedAnswer":{"@type":"Answer","text":"Yes. get_messages returns recent inbox or sent SMS, filterable by direction or phone. wait_for_otp blocks the agent until an OTP-shaped SMS lands. create_webhook registers an HMAC-signed callback URL so OpenClaw can react to incoming SMS in real time."}},
{"@type":"Question","name":"Why not use WhatsApp instead of SMS for OpenClaw?","acceptedAnswer":{"@type":"Answer","text":"OpenClaw already talks to WhatsApp natively. SMS is the gap. Banks and government portals reject WhatsApp numbers for 2FA. People who do not have your chat app cannot be reached. Critical alerts on cellular travel when Wi-Fi is down. SMS8 fills exactly that gap without replacing any of OpenClaw's chat integrations."}},
{"@type":"Question","name":"How much does an SMS MCP for OpenClaw cost?","acceptedAnswer":{"@type":"Answer","text":"SMS8 is 16 USD per month flat after a free 5-day trial. There is no per-message fee because messages route through your own SIM, so the SMS cost is whatever your carrier already charges (often zero on an unlimited plan)."}},
{"@type":"Question","name":"Where is openclaw.json located?","acceptedAnswer":{"@type":"Answer","text":"At ~/.openclaw/openclaw.json on Mac, Linux and Windows. The file is created automatically on first run. OpenClaw watches it for changes and reloads MCP servers without a restart."}},
{"@type":"Question","name":"Is OpenClaw open source?","acceptedAnswer":{"@type":"Answer","text":"Yes. Github.com/openclaw/openclaw, MIT-style license, written in TypeScript by Peter Steinberger (creator of PSPDFKit). Runs locally with persistent memory and any LLM provider (Claude, GPT, Gemini, local models)."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<section class="page-hero page-hero-sm">
  <div class="container">
    <div class="page-hero-inner reveal">
      <span class="hero-badge"><span class="badge-dot"></span>OpenClaw &times; SMS8</span>
      <h1>Give <span class="gradient-text">OpenClaw</span> a real phone number with the SMS8 MCP</h1>
      <p class="lede">OpenClaw already speaks WhatsApp, Telegram, Discord, Signal and iMessage. The one channel none of those bridges cover is plain SMS &mdash; the channel banks, governments and people without your chat app still use. SMS8 plugs that gap with one block in <code>openclaw.json</code>.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/" target="_blank" rel="noopener">Get your API key</a>
        <a class="btn-ghost btn-lg" href="#setup">Jump to setup</a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">How it works</span>
      <h2>What happens when OpenClaw calls <code>send_sms</code></h2>
      <p class="section-lead">OpenClaw is local-first &mdash; it reasons on your machine, then asks SMS8 to deliver. The actual SMS leaves from the SIM in your pocket, not a rented number.</p>
    </div>

    <div class="flow-wrap reveal" aria-label="OpenClaw to SMS8 to Android to recipient flow">
      <svg class="flow-svg" viewBox="0 0 900 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path class="flow-line" d="M 90 100 L 810 100" />

        <g transform="translate(90,100)">
          <circle class="flow-node-bg is-active delay-1" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <path d="M3 7l8-4 8 4M3 7l8 4 8-4M3 7v10l8 4 8-4V7" />
          </g>
          <text class="flow-node-label" y="58">OpenClaw</text>
          <text class="flow-node-sub" y="74">local agent</text>
        </g>

        <g transform="translate(290,100)">
          <circle class="flow-node-bg is-active delay-2" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <path d="M7 9l-2 3 2 3M17 9l2 3-2 3M13 8l-2 8"/>
          </g>
          <text class="flow-node-label" y="58">MCP client</text>
          <text class="flow-node-sub" y="74">openclaw.json</text>
        </g>

        <g transform="translate(470,100)">
          <circle class="flow-node-bg is-active delay-3" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M3 5v14a9 3 0 0 0 18 0V5M3 12a9 3 0 0 0 18 0"/>
          </g>
          <text class="flow-node-label" y="58">SMS8 MCP</text>
          <text class="flow-node-sub" y="74">mcp.sms8.io</text>
        </g>

        <g transform="translate(650,100)">
          <circle class="flow-node-bg is-active delay-4" r="36"/>
          <g class="flow-node-icon" transform="translate(-9,-13)">
            <rect x="2" y="2" width="14" height="22" rx="2.5"/>
            <line x1="2" y1="20" x2="16" y2="20"/>
            <circle cx="9" cy="22" r="0.8" fill="#c4b5fd"/>
          </g>
          <text class="flow-node-label" y="58">Your Android</text>
          <text class="flow-node-sub" y="74">paired SIM</text>
        </g>

        <g transform="translate(810,100)">
          <circle class="flow-node-bg is-active delay-4" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 22c0-5 4-8 8-8s8 3 8 8"/>
          </g>
          <text class="flow-node-label" y="58">Recipient</text>
          <text class="flow-node-sub" y="74">real phone</text>
        </g>

        <circle class="flow-packet"     r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
        <circle class="flow-packet p2"  r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
        <circle class="flow-packet p3"  r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
      </svg>

      <div class="flow-step-list">
        <div class="flow-step reveal"><span class="num">01</span><strong>OpenClaw decides</strong><span>Cron skill or chat trigger fires. Agent reasons locally, picks <code>send_sms</code> from the MCP tool list.</span></div>
        <div class="flow-step reveal"><span class="num">02</span><strong>Hot-reloaded MCP client</strong><span>OpenClaw watches <code>openclaw.json</code> live. The SMS8 server is already loaded, no restart.</span></div>
        <div class="flow-step reveal"><span class="num">03</span><strong>SMS8 routes to your phone</strong><span>JSON-RPC over HTTPS with your Bearer token. SMS8 wakes your paired Android via a signed push.</span></div>
        <div class="flow-step reveal"><span class="num">04</span><strong>Recipient sees your number</strong><span>Real SMS from the number contacts already saved. No bridge, no shortcode, no spam filter.</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="setup">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Setup</span>
      <h2>One block in <code>openclaw.json</code></h2>
      <p class="section-lead">OpenClaw watches <code>~/.openclaw/openclaw.json</code> live. Drop this in under <code>mcp.servers</code>, save the file, and the 9 SMS8 tools appear in your next agent reply.</p>
    </div>

    <div class="reveal" style="max-width:760px;margin:0 auto;">
      <div class="code-card">
        <div class="code-card-head">
          <span class="code-card-label">~/.openclaw/openclaw.json</span>
          <button type="button" class="code-card-copy" aria-label="Copy openclaw.json snippet">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span class="label">Copy</span>
          </button>
        </div>
<pre>{
  <span class="k">mcp</span>: {
    <span class="k">servers</span>: {
      <span class="k">sms8</span>: {
        <span class="k">url</span>: <span class="s">"https://mcp.sms8.io"</span>,
        <span class="k">transport</span>: <span class="s">"streamable-http"</span>,
        <span class="k">headers</span>: {
          <span class="k">Authorization</span>: <span class="s">"Bearer ${SMS8_API_KEY}"</span>
        }
      }
    }
  }
}</pre>
      </div>
      <p style="margin-top:16px;color:#9999ad;font-size:13.5px;text-align:center;">Export <code>SMS8_API_KEY</code> in your shell (e.g. <code>~/.zshrc</code>) so the key never lands in the JSON file.</p>
    </div>
  </div>
</section>

<section class="section" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">What you get</span>
      <h2>Nine tools registered the moment <code>openclaw.json</code> reloads</h2>
      <p class="section-lead">Identical surface to Claude Code, Cursor, Windsurf and OpenCode. One SMS8 account behind all of them.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
      <div class="step-card reveal"><h3><code>send_sms</code></h3><p>Send a single SMS through a paired Android. Per-device and per-SIM routing.</p></div>
      <div class="step-card reveal"><h3><code>send_otp</code></h3><p>Generate and dispatch a one-time code. Configurable length, expiry, attempts.</p></div>
      <div class="step-card reveal"><h3><code>verify_otp</code></h3><p>Constant-time compare against the latest OTP for that phone.</p></div>
      <div class="step-card reveal" style="border-color: rgba(16,185,129,0.45); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.04));"><h3 style="color:#34d399;"><code>wait_for_otp</code> <span style="font-size:10px;background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;letter-spacing:0.06em;">NEW</span></h3><p>Block the agent until an OTP-shaped SMS lands on your paired Android.</p></div>
      <div class="step-card reveal"><h3><code>get_messages</code></h3><p>Fetch recent inbox or sent SMS. Filter by direction or phone.</p></div>
      <div class="step-card reveal"><h3><code>list_devices</code></h3><p>List paired Android devices. Pick the sender when load-balancing.</p></div>
      <div class="step-card reveal" style="border-color: rgba(16,185,129,0.45); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.04));"><h3 style="color:#34d399;"><code>get_balance</code> <span style="font-size:10px;background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;letter-spacing:0.06em;">NEW</span></h3><p>Quick credit check. Returns remaining SMS, days until renewal, summary.</p></div>
      <div class="step-card reveal"><h3><code>create_webhook</code></h3><p>Register a callback URL for inbound SMS and delivery events. HMAC-signed.</p></div>
      <div class="step-card reveal"><h3><code>setup_sms8</code></h3><p>Handshake. Validates the API key, returns devices, plan and integration context.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="use-cases">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Real prompts</span>
      <h2>What people actually use SMS8 in OpenClaw for</h2>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>"Text my partner when my last meeting ends"</h3>
        <p>Cron skill watches your calendar. <code>send_sms</code> fires from your real number when the day wraps. Just a normal SMS, not a chatbot message.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Paste the bank OTP into my session"</h3>
        <p>Agent opens the bank portal in a browser. When the OTP screen appears, <code>wait_for_otp</code> pulls the code off your SIM and pastes it.</p>
      </div>
      <div class="step-card reveal">
        <h3>"SMS me urgent emails when I'm offline"</h3>
        <p>OpenClaw watches Gmail. If a VIP sends mail while you are Do-Not-Disturb, it summarizes and SMS-es you the headline. Cellular delivers when Wi-Fi cannot.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Daily morning summary by SMS"</h3>
        <p>OpenClaw composes a 160-character digest at 7:30 AM (weather, top emails, calendar). Lands on your lock screen as a normal text.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Forward inbound SMS into Telegram"</h3>
        <p><code>create_webhook</code> points at OpenClaw's webhook handler. Every inbound SMS is read by the agent and forwarded to the Telegram chat you actually live in.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Sign me up on a site that needs my real number"</h3>
        <p>Where chat-app numbers fail (KYC, banks, government), OpenClaw uses your real SIM through SMS8. No disposable-SIM service required.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="vs-opencode">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Which AI agent</span>
      <h2>OpenClaw vs OpenCode &mdash; both work with SMS8</h2>
      <p class="section-lead">Pick by job. Many people install both with the same SMS8 API key.</p>
    </div>
    <div class="compare-wrap reveal">
      <table class="compare">
        <thead>
          <tr><th>Trait</th><th>OpenClaw</th><th>OpenCode</th></tr>
        </thead>
        <tbody>
          <tr><td>Primary use</td>                <td class="ok">Personal AI for daily life</td>           <td class="ok">Coding agent for terminal / IDE</td></tr>
          <tr><td>Chat-app integrations</td>      <td class="ok">WhatsApp, Telegram, Discord, Signal, iMessage</td><td class="bad">None native</td></tr>
          <tr><td>System access</td>              <td class="ok">Full (browser, files, shell, cron)</td>  <td class="ok">Repo + shell</td></tr>
          <tr><td>Config file</td>                <td class="ok"><code>~/.openclaw/openclaw.json</code></td><td class="ok"><code>opencode.json</code></td></tr>
          <tr><td>MCP transport</td>              <td class="ok"><code>streamable-http</code></td><td class="ok"><code>type: remote</code></td></tr>
          <tr><td>Hot reload config</td>          <td class="ok">Yes</td>                <td class="bad">Restart needed</td></tr>
          <tr><td>SMS8 tool count</td>            <td class="ok">9 tools</td>                       <td class="ok">9 tools</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>Real questions people search and ask AI assistants</h2>
      <p class="section-lead">Mined from Google, Reddit, OpenClaw community and AI prompts.</p>
    </div>
    <div class="faq reveal">
      <details open><summary>How do I send SMS from OpenClaw?</summary><p>Add the SMS8 MCP entry to <code>~/.openclaw/openclaw.json</code> under <code>mcp.servers.sms8</code>. Set <code>transport</code> to <code>streamable-http</code>, <code>url</code> to <code>https://mcp.sms8.io</code>, and <code>headers.Authorization</code> to <code>Bearer ${SMS8_API_KEY}</code>. OpenClaw hot-reloads. The agent now has <code>send_sms</code>, <code>send_otp</code>, <code>verify_otp</code>, <code>wait_for_otp</code>, <code>get_messages</code>, <code>list_devices</code>, <code>get_balance</code> and <code>create_webhook</code>, all routed through your paired Android.</p></details>

      <details><summary>Does OpenClaw support MCP servers?</summary><p>Yes. OpenClaw is built around Model Context Protocol from day one. The <code>mcp.servers</code> section of <code>openclaw.json</code> accepts both local stdio (<code>command</code> + <code>args</code>) and remote HTTP (<code>url</code> + <code>transport</code> + <code>headers</code>). Hot reload is the default.</p></details>

      <details><summary>How do I give my personal AI a phone number?</summary><p>Pair your existing Android phone to the SMS8 dashboard once. Your AI agent uses that SIM as its SMS gateway. Contacts see the number you already own. No Twilio, no virtual number, no A2P 10DLC. Works with OpenClaw, Claude Code, Cursor, Windsurf and OpenCode from the same SMS8 account.</p></details>

      <details><summary>Can OpenClaw read incoming SMS?</summary><p>Yes. <code>get_messages</code> returns recent inbox or sent SMS, filterable by direction or phone. <code>wait_for_otp</code> blocks the agent until an OTP-shaped SMS lands. <code>create_webhook</code> registers an HMAC-signed callback so OpenClaw can react in real time.</p></details>

      <details><summary>Why not use WhatsApp instead of SMS for OpenClaw?</summary><p>OpenClaw already talks to WhatsApp. SMS is the gap. Banks and government portals reject WhatsApp numbers for 2FA. People without your chat app cannot be reached. Critical alerts on cellular travel when Wi-Fi is down. SMS8 fills exactly that gap.</p></details>

      <details><summary>How much does an SMS MCP for OpenClaw cost?</summary><p>SMS8 is $16/month flat after a free 5-day trial. There is no per-message fee because messages route through your own SIM, so SMS cost is whatever your carrier already charges.</p></details>

      <details><summary>Where is openclaw.json located?</summary><p>At <code>~/.openclaw/openclaw.json</code> on Mac, Linux and Windows. Created on first run. OpenClaw watches the file and hot-reloads MCP servers without a restart.</p></details>

      <details><summary>Is OpenClaw open source?</summary><p>Yes. <a href="https://github.com/openclaw/openclaw">github.com/openclaw/openclaw</a>, written in TypeScript by Peter Steinberger (creator of PSPDFKit). Runs locally with persistent memory and any LLM provider.</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Give OpenClaw a phone number in 3 minutes</h2>
      <p>Free 5-day trial. No credit card. Same API key works in Claude Code, Cursor, Windsurf and OpenCode.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/">Create free account</a>
        <a class="btn-ghost btn-lg" href="/opencode-sms-mcp-server">See OpenCode setup</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
