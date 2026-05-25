<?php
$page      = 'opencode';
$title     = 'OpenCode SMS MCP Server: Send SMS & OTPs From OpenCode Agents';
$desc      = 'Wire SMS, OTPs and webhooks into OpenCode (the open-source AI coding agent from sst) using the SMS8 MCP. One block in opencode.json, point at mcp.sms8.io, and your agent texts through your own Android phone. Free trial, no Twilio, no A2P.';
$canonical = 'https://mcp.sms8.io/opencode-sms-mcp-server';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"OpenCode SMS MCP Server","description":"Send SMS, issue OTPs, wait for incoming codes and register webhooks from an OpenCode session using your own Android phone. SMS8 MCP plugs into opencode.json as a remote MCP server.","url":"https://mcp.sms8.io/opencode-sms-mcp-server","publisher":{"@type":"Organization","name":"SMS8.io","url":"https://sms8.io"},"keywords":"opencode mcp, opencode sms, opencode sms gateway, send sms from opencode, opencode otp, opencode wait_for_otp, sms8 opencode, sms mcp server, mcp send sms, ai agent sms gateway, vibe coding sms, claude code sms mcp"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"Send SMS from OpenCode with the SMS8 MCP","totalTime":"PT3M","step":[
{"@type":"HowToStep","position":1,"name":"Install OpenCode","text":"Run curl -fsSL https://opencode.ai/install | bash on macOS, Linux or WSL."},
{"@type":"HowToStep","position":2,"name":"Get an SMS8 API key","text":"Sign up at app.sms8.io for a free 5-day trial. Pair your Android phone with the dashboard QR code. Copy the API key from the API page."},
{"@type":"HowToStep","position":3,"name":"Add SMS8 to opencode.json","text":"Drop a remote MCP entry into opencode.json with type set to remote, url set to https://mcp.sms8.io, and an Authorization Bearer header carrying your API key. Restart OpenCode."}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"How do I send SMS from OpenCode?","acceptedAnswer":{"@type":"Answer","text":"Add the SMS8 MCP server to opencode.json under the mcp key. Set type to remote, url to https://mcp.sms8.io and a headers.Authorization Bearer line with your SMS8 API key. Restart OpenCode. Your agent now has send_sms, send_otp, verify_otp, wait_for_otp, get_messages, list_devices, get_balance and create_webhook tools. SMS routes through your own paired Android phone."}},
{"@type":"Question","name":"Is there a free SMS MCP server for AI agents?","acceptedAnswer":{"@type":"Answer","text":"SMS8 offers a 5-day free trial of its SMS MCP server with no credit card. After the trial it costs 16 USD per month flat. There is no per-message cost because SMS goes through your own SIM card, not a Twilio number, so the actual SMS portion is whatever your carrier charges (often zero on an unlimited plan)."}},
{"@type":"Question","name":"What is the best SMS MCP server for vibe coders?","acceptedAnswer":{"@type":"Answer","text":"SMS8 MCP works with every MCP-compatible AI coding tool: Claude Code, Cursor, Windsurf and OpenCode. It exposes nine tools through one Bearer-authenticated endpoint at mcp.sms8.io. A single API key works in every tool, so switching editors does not break your SMS integration. The MCP source is MIT-licensed on GitHub."}},
{"@type":"Question","name":"Can my OpenCode agent wait for an SMS to arrive?","acceptedAnswer":{"@type":"Answer","text":"Yes. The wait_for_otp tool blocks the agent until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code automatically. Configurable filters for sender phone, device, SIM slot, body substring and code length. Default timeout 60 seconds, configurable up to 180."}},
{"@type":"Question","name":"Do I need Twilio to send SMS from an AI agent?","acceptedAnswer":{"@type":"Answer","text":"No. SMS8 uses your own Android phone and SIM card as the gateway. No Twilio account, no virtual number rental, no A2P 10DLC registration, no per-segment billing. Your existing carrier plan is the only SMS cost."}},
{"@type":"Question","name":"How does the SMS8 MCP authenticate requests?","acceptedAnswer":{"@type":"Answer","text":"Bearer token over HTTPS. The opencode.json headers.Authorization field carries Bearer YOUR_SMS8_API_KEY. The MCP server validates the key on every JSON-RPC call against the user's SMS8 account. Rotate keys from the API page in the dashboard."}},
{"@type":"Question","name":"What MCP transport does SMS8 use with OpenCode?","acceptedAnswer":{"@type":"Answer","text":"Remote HTTP over JSON-RPC 2.0, MCP revision 2024-11-05. OpenCode calls this transport type remote in opencode.json. Both streamable-http and SSE responses are supported. There is no local stdio binary to install."}},
{"@type":"Question","name":"Can OpenCode send SMS to multiple phone numbers in one run?","acceptedAnswer":{"@type":"Answer","text":"Yes. Call send_sms once per recipient. There is no per-message cost so iterating across a list does not change billing. SMS8 also supports multi-device routing: if you have several Android phones paired, list_devices lets the agent pick which one sends, balancing load or splitting by region."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<section class="page-hero page-hero-sm">
  <div class="container">
    <div class="page-hero-inner reveal">
      <span class="hero-badge"><span class="badge-dot"></span>OpenCode &times; SMS8</span>
      <h1>Send SMS from <span class="gradient-text">OpenCode</span> with the SMS8 MCP server</h1>
      <p class="lede">OpenCode is the open-source AI coding agent from sst &mdash; MIT, 160k+ stars, runs locally, picks any model. SMS8 ships an MCP server at <code>mcp.sms8.io</code>. One block in <code>opencode.json</code> and your agent can text, OTP, and receive replies through your own Android phone. No Twilio. No A2P. $16/month flat.</p>
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
      <h2>What happens when your OpenCode agent calls <code>send_sms</code></h2>
      <p class="section-lead">Four hops, all under one second on a good connection. No phantom backend &mdash; the SIM card in your own pocket is the last hop.</p>
    </div>

    <div class="flow-wrap reveal" aria-label="OpenCode to SMS8 to Android to recipient flow">
      <svg class="flow-svg" viewBox="0 0 900 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
          <path id="flowPath" d="M 90 100 L 290 100 L 470 100 L 650 100 L 810 100" fill="none"/>
        </defs>

        <!-- dashed flowing line -->
        <path class="flow-line" d="M 90 100 L 810 100" />

        <!-- node 1: OpenCode -->
        <g transform="translate(90,100)">
          <circle class="flow-node-bg is-active delay-1" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <path d="M3 7l8-4 8 4M3 7l8 4 8-4M3 7v10l8 4 8-4V7" />
          </g>
          <text class="flow-node-label" y="58">OpenCode</text>
          <text class="flow-node-sub" y="74">agent</text>
        </g>

        <!-- node 2: opencode.json / MCP client -->
        <g transform="translate(290,100)">
          <circle class="flow-node-bg is-active delay-2" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <path d="M7 9l-2 3 2 3M17 9l2 3-2 3M13 8l-2 8"/>
          </g>
          <text class="flow-node-label" y="58">MCP client</text>
          <text class="flow-node-sub" y="74">opencode.json</text>
        </g>

        <!-- node 3: mcp.sms8.io -->
        <g transform="translate(470,100)">
          <circle class="flow-node-bg is-active delay-3" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M3 5v14a9 3 0 0 0 18 0V5M3 12a9 3 0 0 0 18 0"/>
          </g>
          <text class="flow-node-label" y="58">SMS8 MCP</text>
          <text class="flow-node-sub" y="74">mcp.sms8.io</text>
        </g>

        <!-- node 4: Your Android -->
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

        <!-- node 5: Recipient -->
        <g transform="translate(810,100)">
          <circle class="flow-node-bg is-active delay-4" r="36"/>
          <g class="flow-node-icon" transform="translate(-12,-12)">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 22c0-5 4-8 8-8s8 3 8 8"/>
          </g>
          <text class="flow-node-label" y="58">Recipient</text>
          <text class="flow-node-sub" y="74">real phone</text>
        </g>

        <!-- packets running along the path -->
        <circle class="flow-packet"     r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
        <circle class="flow-packet p2"  r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
        <circle class="flow-packet p3"  r="4.5" style="offset-path: path('M 90 100 L 290 100 L 470 100 L 650 100 L 810 100');"/>
      </svg>

      <div class="flow-step-list">
        <div class="flow-step reveal"><span class="num">01</span><strong>Agent decides</strong><span>OpenCode sees a tool named <code>send_sms</code> in the manifest and calls it during reasoning.</span></div>
        <div class="flow-step reveal"><span class="num">02</span><strong>MCP client posts JSON-RPC</strong><span>OpenCode wraps the call in JSON-RPC 2.0 and POSTs it to mcp.sms8.io with your Bearer token.</span></div>
        <div class="flow-step reveal"><span class="num">03</span><strong>SMS8 routes to your phone</strong><span>The MCP server picks a paired Android, signs a push, the phone wakes and sends the SMS via its modem.</span></div>
        <div class="flow-step reveal"><span class="num">04</span><strong>Recipient sees your number</strong><span>The message lands as a normal SMS from the number they already saved you under. No short code, no spam filter.</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="setup">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Setup</span>
      <h2>One config block in <code>opencode.json</code></h2>
      <p class="section-lead">Install OpenCode if you do not already have it. Grab an API key from your SMS8 dashboard. Paste this into <code>opencode.json</code> at the root of your project (or <code>~/.config/opencode/opencode.json</code> for global).</p>
    </div>

    <div class="reveal" style="max-width:760px;margin:0 auto;">
      <div class="code-card">
        <div class="code-card-head">
          <span class="code-card-label">opencode.json</span>
          <button type="button" class="code-card-copy" aria-label="Copy opencode.json snippet">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span class="label">Copy</span>
          </button>
        </div>
<pre>{
  <span class="k">"$schema"</span>: <span class="s">"https://opencode.ai/config.json"</span>,
  <span class="k">"mcp"</span>: {
    <span class="k">"sms8"</span>: {
      <span class="k">"type"</span>: <span class="s">"remote"</span>,
      <span class="k">"url"</span>: <span class="s">"https://mcp.sms8.io"</span>,
      <span class="k">"enabled"</span>: <span class="p">true</span>,
      <span class="k">"headers"</span>: {
        <span class="k">"Authorization"</span>: <span class="s">"Bearer YOUR_SMS8_API_KEY"</span>
      }
    }
  }
}</pre>
      </div>
      <p style="margin-top:16px;color:#9999ad;font-size:13.5px;text-align:center;">Restart OpenCode. Confirm with <code>opencode mcp list</code> &mdash; you should see <code>sms8</code> with 9 tools.</p>
    </div>
  </div>
</section>

<section class="section" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">What you get</span>
      <h2>Nine tools, one MCP endpoint</h2>
      <p class="section-lead">Same surface as Claude Code, Cursor and Windsurf. One SMS8 account behind all of them.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
      <div class="step-card reveal"><h3><code>send_sms</code></h3><p>Send a single SMS through a paired Android. Per-device and per-SIM routing.</p></div>
      <div class="step-card reveal"><h3><code>send_otp</code></h3><p>Generate and dispatch a one-time code. Configurable length, expiry, attempts.</p></div>
      <div class="step-card reveal"><h3><code>verify_otp</code></h3><p>Constant-time compare against the latest OTP for that phone.</p></div>
      <div class="step-card reveal" style="border-color: rgba(16,185,129,0.45); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.04));"><h3 style="color:#34d399;"><code>wait_for_otp</code> <span style="font-size:10px;background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;letter-spacing:0.06em;">NEW</span></h3><p>Block the agent until an OTP-shaped SMS lands on your paired Android. Code is extracted automatically.</p></div>
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
      <h2>What an OpenCode + SMS8 session looks like</h2>
      <p class="section-lead">These are the actual prompts users type into OpenCode after wiring up SMS8.</p>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>"Wire phone OTP into our signup"</h3>
        <p>Agent edits the React form, plugs <code>send_otp</code> and <code>verify_otp</code> into the backend, tests against your real number end-to-end.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Sign me up on this site, paste the OTP"</h3>
        <p>OpenCode drives the browser via Playwright, fills the form, blocks on <code>wait_for_otp</code>, pastes the code that lands on your real SIM.</p>
      </div>
      <div class="step-card reveal">
        <h3>"SMS me when CI fails"</h3>
        <p>Agent writes the GitHub Action, plugs in <code>send_sms</code>, commits. Red builds now text you within seconds.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Test our STOP keyword end-to-end"</h3>
        <p>Sends an SMS via <code>send_sms</code>, replies STOP from a test number, calls <code>get_messages</code> to confirm the STOP handler ran.</p>
      </div>
      <div class="step-card reveal">
        <h3>"What is the SMS bill if I switch from Twilio?"</h3>
        <p>Agent reads your Twilio CSV, applies $0 per-message and $16/month, returns the delta. Most users see 90%+ savings.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Stream incoming SMS into my dev server"</h3>
        <p>Calls <code>create_webhook</code> with your ngrok URL. Every inbound SMS streams to your laptop in real time. No production exposure.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>Real questions people search and ask AI assistants</h2>
      <p class="section-lead">Mined from Google, Reddit and Cursor / Claude Code / OpenCode community prompts.</p>
    </div>
    <div class="faq reveal">
      <details open><summary>How do I send SMS from OpenCode?</summary><p>Add the SMS8 MCP server to <code>opencode.json</code> under the <code>mcp</code> key. Set <code>type</code> to <code>remote</code>, <code>url</code> to <code>https://mcp.sms8.io</code> and a <code>headers.Authorization</code> Bearer line with your SMS8 API key. Restart OpenCode. Your agent now has <code>send_sms</code>, <code>send_otp</code>, <code>verify_otp</code>, <code>wait_for_otp</code>, <code>get_messages</code>, <code>list_devices</code>, <code>get_balance</code> and <code>create_webhook</code> tools. SMS routes through your own paired Android phone.</p></details>

      <details><summary>Is there a free SMS MCP server for AI agents?</summary><p>SMS8 offers a 5-day free trial with no credit card. After the trial it costs $16/month flat. There is no per-message cost because SMS goes through your own SIM card, not a Twilio number, so the actual SMS portion is whatever your carrier charges (often $0 on an unlimited plan).</p></details>

      <details><summary>What is the best SMS MCP server for vibe coders?</summary><p>SMS8 MCP works with every MCP-compatible AI coding tool: Claude Code, Cursor, Windsurf and OpenCode. It exposes nine tools through one Bearer-authenticated endpoint at <code>mcp.sms8.io</code>. A single API key works in every tool, so switching editors does not break your SMS integration. The MCP source is MIT-licensed on <a href="https://github.com/1fancy/sms8-sms-gateway">GitHub</a>.</p></details>

      <details><summary>Can my OpenCode agent wait for an SMS to arrive?</summary><p>Yes. The <code>wait_for_otp</code> tool blocks the agent until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code automatically. Configurable filters for sender phone, device, SIM slot, body substring and code length. Default timeout 60 seconds, configurable up to 180.</p></details>

      <details><summary>Do I need Twilio to send SMS from an AI agent?</summary><p>No. SMS8 uses your own Android phone and SIM card as the gateway. No Twilio account, no virtual number rental, no A2P 10DLC registration, no per-segment billing.</p></details>

      <details><summary>How does the SMS8 MCP authenticate requests?</summary><p>Bearer token over HTTPS. The <code>opencode.json</code> <code>headers.Authorization</code> field carries <code>Bearer YOUR_SMS8_API_KEY</code>. Rotate keys from the API page in the dashboard.</p></details>

      <details><summary>What MCP transport does SMS8 use with OpenCode?</summary><p>Remote HTTP over JSON-RPC 2.0, MCP revision 2024-11-05. OpenCode calls this transport <code>type: remote</code> in <code>opencode.json</code>. Both streamable-http and SSE responses are supported. There is no local stdio binary to install.</p></details>

      <details><summary>Can OpenCode send SMS to multiple phone numbers in one run?</summary><p>Yes. Call <code>send_sms</code> once per recipient. There is no per-message cost so iterating across a list does not change billing. SMS8 also supports multi-device routing: if you have several Android phones paired, <code>list_devices</code> lets the agent pick which one sends.</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Add SMS to your OpenCode agent in 3 minutes</h2>
      <p>Free 5-day trial. No credit card. Same API key works in Claude Code, Cursor and Windsurf if you switch later.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/">Create free account</a>
        <a class="btn-ghost btn-lg" href="/openclaw-sms-mcp-server">See OpenClaw setup</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
