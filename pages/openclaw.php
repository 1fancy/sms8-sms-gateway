<?php
$page      = 'openclaw';
$title     = 'OpenClaw SMS MCP Server: Add SMS & OTP to OpenClaw Personal AI';
$desc      = 'Wire SMS, OTPs and webhooks into OpenClaw (Peter Steinberger\'s open-source personal AI agent) via the SMS8 MCP server. Drop one block into openclaw.json, point at mcp.sms8.io, and your local agent can send and wait for SMS through your own paired Android phone alongside its WhatsApp, Telegram and Discord skills.';
$canonical = 'https://mcp.sms8.io/openclaw-sms-mcp-server';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"OpenClaw SMS MCP Server","description":"Install and configure the SMS8 MCP server inside OpenClaw. Send SMS, issue OTPs, wait for incoming codes and register webhooks from an OpenClaw session using your own paired Android phone, alongside the WhatsApp, Telegram and Discord skills OpenClaw already ships.","url":"https://mcp.sms8.io/openclaw-sms-mcp-server","publisher":{"@type":"Organization","name":"SMS8.io","url":"https://sms8.io"},"keywords":"openclaw mcp, openclaw sms, openclaw sms gateway, sms mcp openclaw, send sms openclaw, openclaw otp, openclaw wait_for_otp, sms8 openclaw, openclaw whatsapp sms, openclaw bridge sms"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"Add an SMS MCP server to OpenClaw","description":"Three-step setup to enable SMS, OTP and webhook tools inside OpenClaw by adding the SMS8 remote MCP server to ~/.openclaw/openclaw.json.","step":[
{"@type":"HowToStep","position":1,"name":"Install OpenClaw","text":"Run curl -fsSL https://openclaw.ai/install.sh | bash to install OpenClaw on Mac, Windows or Linux."},
{"@type":"HowToStep","position":2,"name":"Get an SMS8 API key","text":"Sign up at app.sms8.io, pair an Android phone with the dashboard, copy the API key from the API page."},
{"@type":"HowToStep","position":3,"name":"Add SMS8 to openclaw.json","text":"Drop a remote server entry under mcp.servers in ~/.openclaw/openclaw.json. Set transport to streamable-http, url to https://mcp.sms8.io, and a headers.Authorization Bearer line carrying your API key."}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"Does OpenClaw support MCP servers?","acceptedAnswer":{"@type":"Answer","text":"Yes. OpenClaw is built around Model Context Protocol from day one. The mcp.servers section of ~/.openclaw/openclaw.json accepts both local stdio servers (command + args) and remote HTTP servers (url + transport + headers). Hot reload is enabled, so changes take effect without a restart."}},
{"@type":"Question","name":"What does the SMS8 MCP add to OpenClaw?","acceptedAnswer":{"@type":"Answer","text":"Nine new tools your OpenClaw agent can call directly: setup_sms8 to handshake, send_sms to send a single SMS through your paired Android, send_otp and verify_otp for verification flows, wait_for_otp to block until an OTP arrives on your real SIM, get_messages to read your inbox, list_devices, get_balance, and create_webhook to register an inbound callback URL."}},
{"@type":"Question","name":"How do I add SMS8 MCP to openclaw.json?","acceptedAnswer":{"@type":"Answer","text":"Open ~/.openclaw/openclaw.json. Inside the mcp section, under servers, add an entry named sms8 with url set to https://mcp.sms8.io, transport set to streamable-http and a headers block carrying Authorization: Bearer ${SMS8_API_KEY}. Save the file. OpenClaw picks the change up live."}},
{"@type":"Question","name":"Why pair OpenClaw with SMS8 instead of using Beeper or iMessage?","acceptedAnswer":{"@type":"Answer","text":"OpenClaw already speaks to WhatsApp, Telegram, Discord and Signal through Beeper-style bridges. SMS is the one channel that none of those cover. SMS8 plugs that gap: your OpenClaw agent can now SMS a real phone number (yours or anyone else's), receive SMS replies, and use OTPs in verification flows."}},
{"@type":"Question","name":"Can OpenClaw wait for an incoming SMS?","acceptedAnswer":{"@type":"Answer","text":"Yes. The wait_for_otp tool blocks until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code. Useful when OpenClaw is doing autonomous tasks that need a code from your real number (bank apps, government portals, two-factor logins, anything that won\\u2019t accept a virtual number)."}},
{"@type":"Question","name":"Is OpenClaw open source?","acceptedAnswer":{"@type":"Answer","text":"Yes. OpenClaw lives at github.com/openclaw/openclaw, written in TypeScript by Peter Steinberger (creator of PSPDFKit). It runs locally on your machine with persistent memory, supports any LLM provider (Claude, GPT, Gemini, local), and now ships an MCP registry so SMS8 plugs in with one JSON block."}},
{"@type":"Question","name":"Where is the SMS8 MCP server hosted?","acceptedAnswer":{"@type":"Answer","text":"At mcp.sms8.io. It speaks JSON-RPC 2.0 over HTTPS, MCP revision 2024-11-05, both streamable-http and SSE transports. Auth is a Bearer header carrying your SMS8 API key. The MCP source code is MIT-licensed at github.com/1fancy/sms8-sms-gateway."}},
{"@type":"Question","name":"Can I share the same API key with Claude Code, OpenCode and OpenClaw?","acceptedAnswer":{"@type":"Answer","text":"Yes. One SMS8 API key works across every MCP client (Claude Code, Cursor, Windsurf, OpenCode, OpenClaw and any future MCP-compatible agent). All clients hit the same MCP endpoint with the same Bearer header."}}
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
      <p class="lede">OpenClaw is a local-first AI agent that talks through WhatsApp, Telegram, Discord, Signal and iMessage. The one channel it does not natively cover is plain SMS. SMS8 plugs that gap: add the <code>sms8</code> entry to <code>openclaw.json</code> and your personal agent can text, receive, and verify codes through your own paired Android phone.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/" target="_blank" rel="noopener">Get your SMS8 API key</a>
        <a class="btn-ghost btn-lg" href="#config">Jump to openclaw.json</a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="why">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Why this pairing</span>
      <h2>OpenClaw runs the agent. SMS8 owns the SIM.</h2>
      <p class="section-lead">OpenClaw is a personal AI with persistent memory and full system access &mdash; cron jobs, browser control, file access, 50+ chat integrations. SMS8 hands it the one channel chat bridges never reach: native SMS through a SIM card you already own.</p>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>Bridge the SMS gap</h3>
        <p>Beeper handles WhatsApp / iMessage / Signal. OpenClaw lights those up natively. SMS8 fills the missing column: real GSM SMS, your number, your SIM, no bridge required.</p>
      </div>
      <div class="step-card reveal">
        <h3>Hot-reload, no restart</h3>
        <p>OpenClaw watches <code>openclaw.json</code> live. Drop in the SMS8 entry, save the file, and the nine new tools appear in your agent within seconds. No daemon restart, no <code>kill -HUP</code>.</p>
      </div>
      <div class="step-card reveal">
        <h3>Real-number 2FA / OTP</h3>
        <p>Bank apps, government portals, KYC flows refuse virtual numbers. With <code>wait_for_otp</code> your OpenClaw agent can paste codes that land on your actual SIM into autonomous tasks.</p>
      </div>
      <div class="step-card reveal">
        <h3>$16/month flat, no per-message</h3>
        <p>No Twilio account, no A2P 10DLC, no per-segment billing. Your SMS cost is whatever your carrier already charges (often $0 on unlimited).</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="config">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Setup</span>
      <h2>Three steps. Three minutes.</h2>
    </div>
    <div class="split-row reveal" style="align-items: stretch;">
      <div class="split-text">
        <h3 style="color:#c4b5fd;font-size:17px;margin-bottom:12px;">1. Install OpenClaw</h3>
<pre class="code-block">curl -fsSL https://openclaw.ai/install.sh | bash
# or
npm i -g openclaw</pre>
        <p style="margin-top:18px;color:#9999ad;font-size:14px;">Mac, Linux and Windows. <a href="https://docs.openclaw.ai" target="_blank" rel="noopener" style="color:#c4b5fd;">OpenClaw docs</a>.</p>

        <h3 style="color:#c4b5fd;font-size:17px;margin-top:28px;margin-bottom:12px;">2. Get an SMS8 API key</h3>
<pre class="code-block">1. Sign up at https://app.sms8.io (free 5-day trial)
2. Install SMS8 Android app, scan dashboard QR
3. Open the API page, copy your key</pre>

        <h3 style="color:#c4b5fd;font-size:17px;margin-top:28px;margin-bottom:12px;">3. Add SMS8 to openclaw.json</h3>
        <p style="font-size:14px;color:#9999ad;">Open <code>~/.openclaw/openclaw.json</code> (created on first run). Under <code>mcp.servers</code>, add the <code>sms8</code> entry. OpenClaw picks the change up live.</p>
      </div>
      <div class="visual-card">
        <div class="visual-card-header">~/.openclaw/openclaw.json &middot; remote MCP</div>
<pre>{
  <span class="k">mcp</span>: {
    <span class="k">servers</span>: {
      <span class="k">sms8</span>: {
        <span class="k">url</span>: <span class="s">"https://mcp.sms8.io"</span>,
        <span class="k">transport</span>: <span class="s">"streamable-http"</span>,
        <span class="k">headers</span>: {
          <span class="k">Authorization</span>:
            <span class="s">"Bearer ${SMS8_API_KEY}"</span>
        }
      }
    }
  }
}</pre>
      </div>
    </div>

    <div class="reveal" style="max-width:820px;margin:36px auto 0;text-align:center;color:#9999ad;font-size:14px;">
      Set <code>SMS8_API_KEY</code> in your shell (e.g. <code>~/.zshrc</code>) so the key never lands in the JSON file. Confirm the SMS8 tools are loaded by asking your agent: <em>"List the SMS8 MCP tools you have."</em>
    </div>
  </div>
</section>

<section class="section" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">What OpenClaw gains</span>
      <h2>Nine tools registered the moment <code>openclaw.json</code> reloads</h2>
      <p class="section-lead">Identical surface to Claude Code, Cursor, Windsurf, OpenCode. One SMS8 account behind all of them.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
      <div class="step-card reveal"><h3><code>setup_sms8</code></h3><p>Handshake. Validates the API key, returns devices, plan and integration context.</p></div>
      <div class="step-card reveal"><h3><code>send_sms</code></h3><p>Send a single SMS through a paired Android. Per-device and per-SIM routing.</p></div>
      <div class="step-card reveal"><h3><code>send_otp</code></h3><p>Generate and dispatch a one-time code. Configurable length, expiry and attempts.</p></div>
      <div class="step-card reveal"><h3><code>verify_otp</code></h3><p>Constant-time compare against the latest OTP for that phone.</p></div>
      <div class="step-card reveal" style="border-color: rgba(16,185,129,0.45); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.04));"><h3 style="color:#34d399;"><code>wait_for_otp</code> <span style="font-size:10px;background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;letter-spacing:0.06em;">NEW</span></h3><p>Block until an OTP-shaped SMS lands on your paired Android. Extracts the code automatically.</p></div>
      <div class="step-card reveal"><h3><code>get_messages</code></h3><p>Fetch recent inbox or sent SMS. Filter by direction or phone.</p></div>
      <div class="step-card reveal"><h3><code>list_devices</code></h3><p>List paired Android devices. Pick the sender when load-balancing.</p></div>
      <div class="step-card reveal" style="border-color: rgba(16,185,129,0.45); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.04));"><h3 style="color:#34d399;"><code>get_balance</code> <span style="font-size:10px;background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;letter-spacing:0.06em;">NEW</span></h3><p>Quick credit check. Returns remaining SMS, days until renewal, summary.</p></div>
      <div class="step-card reveal"><h3><code>create_webhook</code></h3><p>Register a callback URL for inbound SMS and delivery events. HMAC-signed.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="use-cases">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Use cases</span>
      <h2>What OpenClaw + SMS8 looks like in real life</h2>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>"Text my partner when I'm leaving the office"</h3>
        <p>Cron skill in OpenClaw watches your calendar. When the last meeting ends, it calls <code>send_sms</code> from your real number. They get a normal SMS, not a chatbot message, not an unknown short code.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Paste the bank OTP into my session"</h3>
        <p>OpenClaw drives the browser to your bank's portal. When the OTP screen appears, it calls <code>wait_for_otp</code> against your SIM, gets the code, pastes it. No screenshare, no manual relay.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Forward urgent SMS to my Telegram"</h3>
        <p>OpenClaw registers <code>create_webhook</code> pointing at its own webhook handler. Every inbound SMS is read by the agent and the important ones are forwarded into the Telegram chat where you actually live.</p>
      </div>
      <div class="step-card reveal">
        <h3>"SMS me when the Mac mini overheats"</h3>
        <p>OpenClaw has full system access. Pair it with a temperature check and call <code>send_sms</code> when a threshold trips. Real SMS to your real number even when you are out of Wi-Fi range and chat apps cannot reach you.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Verify a phone for a sign-up I'm doing"</h3>
        <p>Need to register on a site that demands a real number? OpenClaw uses your number (via <code>send_otp</code>/<code>wait_for_otp</code>) and finishes the signup. No disposable-SIM service required when it should be you on the other end.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Daily morning summary by SMS"</h3>
        <p>OpenClaw composes a 160-character morning digest (weather, top emails, calendar). Cron skill fires <code>send_sms</code> at 7:30 AM. Shows up as a normal text from your own number, readable on the lock screen.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="why-sms">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Why SMS at all</span>
      <h2>OpenClaw already does WhatsApp. Why bother with SMS?</h2>
    </div>
    <div class="reveal" style="max-width:820px;margin:0 auto;color:#cfcfdc;font-size:15.5px;line-height:1.85;">
      <p>OpenClaw's chat-app integrations cover the channels where your contacts live by choice. SMS covers the channels where they live by necessity:</p>
      <ul style="margin: 18px 0 18px 22px; color:#cfcfdc;">
        <li><strong>2FA and bank apps.</strong> Almost every regulated service refuses VOIP numbers. They need a real SIM with carrier-routed SMS.</li>
        <li><strong>Government / KYC.</strong> National IDs, tax portals, residency apps &mdash; SMS-only verification, no exceptions.</li>
        <li><strong>People who do not have your chat app.</strong> Your accountant. A delivery driver. A new client. SMS is the lowest common denominator.</li>
        <li><strong>Critical alerts that must arrive.</strong> When the data-only Mac mini melts down, your Wi-Fi went with it. SMS travels on cellular and lands every time.</li>
      </ul>
      <p>SMS8 is how OpenClaw reaches all of those without your buying a Twilio plan or registering an A2P brand.</p>
    </div>
  </div>
</section>

<section class="section section-alt" id="opencode-vs-openclaw">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Which agent fits you</span>
      <h2>OpenClaw vs OpenCode &mdash; pick the one that matches your workflow</h2>
      <p class="section-lead">Both speak MCP, both work with SMS8, both are open source. They solve different problems.</p>
    </div>
    <div class="compare-wrap reveal">
      <table class="compare">
        <thead>
          <tr><th>Trait</th><th>OpenClaw</th><th>OpenCode</th></tr>
        </thead>
        <tbody>
          <tr><td>Primary persona</td>            <td class="ok">Personal AI for daily life</td>           <td class="ok">Coding agent for the terminal / IDE</td></tr>
          <tr><td>Chat-app integrations</td>      <td class="ok">WhatsApp, Telegram, Discord, Signal, iMessage</td><td class="bad">None native</td></tr>
          <tr><td>System access</td>              <td class="ok">Full (browser, files, shell, cron)</td>  <td class="ok">Repo &amp; shell only</td></tr>
          <tr><td>Best at</td>                    <td class="ok">Automating life admin</td>               <td class="ok">Writing and refactoring code</td></tr>
          <tr><td>Config file</td>                <td class="ok"><code>~/.openclaw/openclaw.json</code></td><td class="ok"><code>opencode.json</code> in repo or <code>~/.config/opencode/</code></td></tr>
          <tr><td>MCP transport syntax</td>       <td class="ok"><code>transport: streamable-http</code></td><td class="ok"><code>type: remote</code></td></tr>
          <tr><td>Hot reload of config</td>       <td class="ok">Yes, live file watch</td>                <td class="bad">Restart needed</td></tr>
          <tr><td>SMS8 integration</td>           <td class="ok">Same 9 tools</td>                        <td class="ok">Same 9 tools</td></tr>
        </tbody>
      </table>
    </div>
    <p class="reveal" style="max-width:820px;margin:36px auto 0;color:#9999ad;font-size:14.5px;line-height:1.7;">
      Many users run both side by side. OpenClaw owns inbox / chat / cron / browser. OpenCode owns the repo. SMS8 is wired into both with the same API key, so a code change in OpenCode and a "tell me when this deploys" in OpenClaw share the same SMS channel.
    </p>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>OpenClaw &times; SMS8 questions</h2>
    </div>
    <div class="faq reveal">
      <details open><summary>Does OpenClaw support MCP servers?</summary><p>Yes. OpenClaw is built around Model Context Protocol from day one. The <code>mcp.servers</code> section of <code>~/.openclaw/openclaw.json</code> accepts both local stdio servers (<code>command</code> + <code>args</code>) and remote HTTP servers (<code>url</code> + <code>transport</code> + <code>headers</code>). Hot reload is enabled, so changes take effect without a restart.</p></details>

      <details><summary>What does the SMS8 MCP add to OpenClaw?</summary><p>Nine new tools your OpenClaw agent can call directly: <code>setup_sms8</code>, <code>send_sms</code>, <code>send_otp</code>, <code>verify_otp</code>, <code>wait_for_otp</code>, <code>get_messages</code>, <code>list_devices</code>, <code>get_balance</code>, and <code>create_webhook</code>.</p></details>

      <details><summary>How do I add SMS8 MCP to openclaw.json?</summary><p>Open <code>~/.openclaw/openclaw.json</code>. Inside the <code>mcp</code> section, under <code>servers</code>, add an entry named <code>sms8</code> with <code>url</code> set to <code>https://mcp.sms8.io</code>, <code>transport</code> set to <code>streamable-http</code> and a <code>headers</code> block carrying <code>Authorization: Bearer ${SMS8_API_KEY}</code>. Save the file. OpenClaw picks the change up live.</p></details>

      <details><summary>Why pair OpenClaw with SMS8 instead of using Beeper or iMessage?</summary><p>OpenClaw already speaks to WhatsApp, Telegram, Discord and Signal through bridges. SMS is the one channel that none of those cover. SMS8 plugs that gap: your agent can now SMS a real phone number, receive SMS replies, and use OTPs in verification flows.</p></details>

      <details><summary>Can OpenClaw wait for an incoming SMS?</summary><p>Yes. The <code>wait_for_otp</code> tool blocks until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code. Default timeout 60 seconds, configurable up to 180.</p></details>

      <details><summary>Is OpenClaw open source?</summary><p>Yes. OpenClaw lives at <a href="https://github.com/openclaw/openclaw" target="_blank" rel="noopener">github.com/openclaw/openclaw</a>, written in TypeScript by Peter Steinberger (creator of PSPDFKit). It runs locally with persistent memory and supports any LLM provider.</p></details>

      <details><summary>Where is the SMS8 MCP server hosted?</summary><p>At <code>mcp.sms8.io</code>. It speaks JSON-RPC 2.0 over HTTPS, MCP revision 2024-11-05, both streamable-http and SSE transports. Source: <a href="https://github.com/1fancy/sms8-sms-gateway">github.com/1fancy/sms8-sms-gateway</a>.</p></details>

      <details><summary>Can I share the same API key with Claude Code, OpenCode and OpenClaw?</summary><p>Yes. One SMS8 API key works across every MCP client (Claude Code, Cursor, Windsurf, OpenCode, OpenClaw and any future MCP-compatible agent).</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Give OpenClaw a phone number in 3 minutes</h2>
      <p>Free 5-day trial. No credit card. Same API key works in Claude Code, Cursor, Windsurf and OpenCode if you also use them.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/">Create free account</a>
        <a class="btn-ghost btn-lg" href="/opencode-sms-mcp-server">Also use OpenCode</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
