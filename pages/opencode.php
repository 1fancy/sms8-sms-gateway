<?php
$page      = 'opencode';
$title     = 'OpenCode SMS MCP Server: Send SMS & OTPs from OpenCode AI Agent';
$desc      = 'Add SMS, OTP and webhooks to OpenCode (the open-source AI coding agent from sst) using the SMS8 MCP. Drop a remote MCP config in opencode.json, point at mcp.sms8.io, and your agent can send and wait for SMS through your own paired Android phone.';
$canonical = 'https://mcp.sms8.io/opencode-sms-mcp-server';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"OpenCode SMS MCP Server","description":"Install and configure the SMS8 MCP server inside OpenCode. Send SMS, issue OTPs, wait for incoming codes and register webhooks from an OpenCode session using your own Android phone.","url":"https://mcp.sms8.io/opencode-sms-mcp-server","publisher":{"@type":"Organization","name":"SMS8.io","url":"https://sms8.io"},"keywords":"opencode mcp, opencode sms, opencode sms gateway, sms mcp opencode, send sms opencode, opencode otp, opencode wait_for_otp, sms8 opencode"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"Send SMS from OpenCode using the SMS8 MCP","description":"Three-step setup to enable SMS, OTP and webhook tools inside OpenCode by adding the SMS8 remote MCP server to opencode.json.","step":[
{"@type":"HowToStep","position":1,"name":"Install OpenCode","text":"Run curl -fsSL https://opencode.ai/install | bash to install OpenCode on macOS, Linux or WSL."},
{"@type":"HowToStep","position":2,"name":"Get an SMS8 API key","text":"Sign up at app.sms8.io, pair an Android phone with the dashboard, copy the API key from the API page."},
{"@type":"HowToStep","position":3,"name":"Add SMS8 to opencode.json","text":"Drop a remote MCP entry into your opencode.json config pointing at https://mcp.sms8.io with an Authorization: Bearer header carrying your API key."}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"Does OpenCode support MCP servers?","acceptedAnswer":{"@type":"Answer","text":"Yes. OpenCode supports both remote (HTTP/SSE) and local (stdio) MCP servers via the mcp section of opencode.json or opencode.jsonc. You can also manage them from the CLI with opencode mcp list, opencode mcp auth and opencode mcp debug."}},
{"@type":"Question","name":"What does the SMS8 MCP add to OpenCode?","acceptedAnswer":{"@type":"Answer","text":"Nine new tools your OpenCode agent can call directly: setup_sms8 to handshake, send_sms to send a single SMS through your paired Android, send_otp and verify_otp for verification flows, wait_for_otp to block until an OTP arrives on your real SIM, get_messages to read your inbox, list_devices, get_balance, and create_webhook to register an inbound callback URL."}},
{"@type":"Question","name":"How do I add SMS8 MCP to opencode.json?","acceptedAnswer":{"@type":"Answer","text":"Open opencode.json (or opencode.jsonc) at the root of your project. Add an entry under mcp with type set to remote, url set to https://mcp.sms8.io and an Authorization Bearer header carrying your SMS8 API key. Restart OpenCode and the nine tools appear in the agent's tool list."}},
{"@type":"Question","name":"Do I need Twilio to use SMS8 with OpenCode?","acceptedAnswer":{"@type":"Answer","text":"No. SMS8 routes every SMS through your own Android phone and SIM card. There is no Twilio account, no virtual number rental, no A2P 10DLC registration and no per-message fee. The dashboard is 16 USD per month flat, the SMS cost is whatever your carrier charges (often zero on an unlimited plan)."}},
{"@type":"Question","name":"Can my OpenCode agent wait for an incoming SMS?","acceptedAnswer":{"@type":"Answer","text":"Yes. The wait_for_otp tool blocks until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code and returns it. Optional filters for sender phone, device, SIM slot, body substring and code length. Default timeout 60 seconds, configurable up to 180."}},
{"@type":"Question","name":"How is SMS8 different from AgentSIM for OpenCode?","acceptedAnswer":{"@type":"Answer","text":"AgentSIM rents a disposable real-SIM number per session, charged per session. SMS8 connects your own persistent phone number to OpenCode for a flat 16 USD per month. Use AgentSIM for one-shot autonomous signups under a throwaway identity. Use SMS8 for any real business where customers should see your real number."}},
{"@type":"Question","name":"Where is the SMS8 MCP server hosted?","acceptedAnswer":{"@type":"Answer","text":"At mcp.sms8.io. It speaks JSON-RPC 2.0 over HTTPS, MCP revision 2024-11-05. Auth is a Bearer header carrying your SMS8 API key. The MCP source code is MIT-licensed at github.com/1fancy/sms8-sms-gateway."}},
{"@type":"Question","name":"Can I share the same API key with Claude Code, Cursor and OpenCode?","acceptedAnswer":{"@type":"Answer","text":"Yes. One SMS8 API key works across every MCP client (Claude Code, Cursor, Windsurf, OpenCode and any future MCP-compatible tool). All clients hit the same MCP endpoint at mcp.sms8.io with the same Bearer header."}}
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
      <p class="lede">OpenCode is an open-source AI coding agent (160k+ stars, MIT, from sst). It speaks MCP. SMS8 ships an MCP server at <code>mcp.sms8.io</code>. Drop one block into <code>opencode.json</code> and your OpenCode session can send SMS, issue OTPs, wait for incoming codes and register webhooks through your own paired Android phone.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/" target="_blank" rel="noopener">Get your SMS8 API key</a>
        <a class="btn-ghost btn-lg" href="#config">Jump to opencode.json</a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="why">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Why this pairing</span>
      <h2>OpenCode handles the reasoning. SMS8 handles the SMS.</h2>
      <p class="section-lead">OpenCode runs locally, supports any model (Claude, GPT, Gemini, Copilot, local), and is fully open source. SMS8 gives it a real SIM card to talk to humans through. Together your agent can do SMS-touching workflows without a Twilio bill or A2P registration.</p>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>Real persistent number</h3>
        <p>Your own SIM. Your contacts see the same number they already saved you under. Better deliverability than a virtual long code, no shortcode reputation drift, no carrier filtering.</p>
      </div>
      <div class="step-card reveal">
        <h3>Same API key as Claude Code, Cursor</h3>
        <p>One SMS8 account, one API key, every MCP-compatible tool. Switch editors without rotating keys or rewriting integration code.</p>
      </div>
      <div class="step-card reveal">
        <h3>Wait-for-OTP, not just send</h3>
        <p>The agent-autonomy gap most SMS MCPs leave open. <code>wait_for_otp</code> blocks until an OTP-shaped SMS arrives on your real SIM and extracts the code. No polling code in your agent.</p>
      </div>
      <div class="step-card reveal">
        <h3>$16/month flat</h3>
        <p>No per-session billing. The SMS portion runs through your carrier plan (often zero on an unlimited tier). Compare to per-message billing on Twilio, MessageBird, AgentSIM.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="config">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Setup</span>
      <h2>Three commands. Three minutes.</h2>
    </div>
    <div class="split-row reveal" style="align-items: stretch;">
      <div class="split-text">
        <h3 style="color:#c4b5fd;font-size:17px;margin-bottom:12px;">1. Install OpenCode</h3>
<pre class="code-block">curl -fsSL https://opencode.ai/install | bash</pre>
        <p style="margin-top:18px;color:#9999ad;font-size:14px;">macOS, Linux and WSL. Windows native is in beta. <a href="https://opencode.ai/docs" target="_blank" rel="noopener" style="color:#c4b5fd;">OpenCode docs</a>.</p>

        <h3 style="color:#c4b5fd;font-size:17px;margin-top:28px;margin-bottom:12px;">2. Get an SMS8 API key</h3>
<pre class="code-block">1. Sign up at https://app.sms8.io (free 5-day trial)
2. Install SMS8 Android app, scan dashboard QR
3. Open API page, copy key</pre>

        <h3 style="color:#c4b5fd;font-size:17px;margin-top:28px;margin-bottom:12px;">3. Add SMS8 to opencode.json</h3>
        <p style="font-size:14px;color:#9999ad;">Drop this into <code>opencode.json</code> at the root of your project (or <code>~/.config/opencode/opencode.json</code> for global).</p>
      </div>
      <div class="visual-card">
        <div class="visual-card-header">opencode.json &middot; remote MCP</div>
<pre>{
  <span class="k">"$schema"</span>: <span class="s">"https://opencode.ai/config.json"</span>,
  <span class="k">"mcp"</span>: {
    <span class="k">"sms8"</span>: {
      <span class="k">"type"</span>: <span class="s">"remote"</span>,
      <span class="k">"url"</span>: <span class="s">"https://mcp.sms8.io"</span>,
      <span class="k">"enabled"</span>: true,
      <span class="k">"headers"</span>: {
        <span class="k">"Authorization"</span>:
          <span class="s">"Bearer YOUR_SMS8_API_KEY"</span>
      }
    }
  }
}</pre>
      </div>
    </div>

    <div class="reveal" style="max-width:820px;margin:36px auto 0;text-align:center;color:#9999ad;font-size:14px;">
      Restart OpenCode (<code>opencode</code> in a fresh terminal). Confirm the SMS8 tools are registered with <code>opencode mcp list</code> &mdash; you should see <code>sms8</code> with 9 tools available.
    </div>
  </div>
</section>

<section class="section" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">What OpenCode can do</span>
      <h2>Nine tools registered the moment OpenCode loads</h2>
      <p class="section-lead">Same surface as Claude Code, Cursor, Windsurf. Same SMS8 account.</p>
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
      <h2>What an OpenCode + SMS8 session looks like in real life</h2>
    </div>

    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3>"Verify my phone before checkout"</h3>
        <p>Tell OpenCode to wire phone verification into your signup. The agent edits the React form, adds the API call, and uses <code>send_otp</code> + <code>verify_otp</code> with real OTP delivery during local testing.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Sign me up on Stripe Atlas, paste the OTP"</h3>
        <p>OpenCode opens the browser via Playwright, fills the form, then calls <code>wait_for_otp</code> against your real SIM. The code lands, the agent pastes it. No disposable number to rent.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Send a notification to my admin when the build fails"</h3>
        <p>Ask the agent to add a CI hook. It writes the GitHub Action step, plugs in <code>send_sms</code> with your number, commits. Every red build now texts you within seconds.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Test our SMS opt-out flow end to end"</h3>
        <p>OpenCode sends an SMS through <code>send_sms</code>, replies STOP from a test number, calls <code>get_messages</code> to confirm receipt, then exercises your STOP handler. Real round trip in a fresh test run.</p>
      </div>
      <div class="step-card reveal">
        <h3>"Receive incoming SMS in my dev environment"</h3>
        <p>OpenCode calls <code>create_webhook</code> with your ngrok URL during local dev, so every incoming SMS streams to your laptop in real time. No production exposure.</p>
      </div>
      <div class="step-card reveal">
        <h3>"What is the SMS bill if I switch to SMS8?"</h3>
        <p>Ask OpenCode to read your Twilio billing CSV and compute the same volume on SMS8. It reads the CSV, applies $0 per-message, $16 plan, returns the delta. Most users see 90%+ savings.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="cli">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">CLI helpers</span>
      <h2>OpenCode MCP commands you will actually use</h2>
    </div>
    <div class="reveal" style="max-width:820px;margin:0 auto;">
      <h3 style="font-size:16px;color:#c4b5fd;margin-bottom:10px;">List registered MCP servers</h3>
<pre class="code-block">opencode mcp list</pre>
      <h3 style="font-size:16px;color:#c4b5fd;margin:24px 0 10px;">Debug the SMS8 MCP connection</h3>
<pre class="code-block">opencode mcp debug sms8</pre>
      <h3 style="font-size:16px;color:#c4b5fd;margin:24px 0 10px;">Drop &amp; re-add the credentials</h3>
<pre class="code-block">opencode mcp logout sms8
# then edit opencode.json with a new key</pre>
      <p style="margin-top:18px;color:#9999ad;font-size:14px;">Full OpenCode MCP docs: <a href="https://opencode.ai/docs/mcp-servers" target="_blank" rel="noopener" style="color:#c4b5fd;">opencode.ai/docs/mcp-servers</a>.</p>
    </div>
  </div>
</section>

<section class="section section-alt" id="vs-agentsim">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">SMS MCP comparison</span>
      <h2>SMS8 MCP vs AgentSIM for OpenCode users</h2>
      <p class="section-lead">Both are MCP servers. Different products solving different problems.</p>
    </div>
    <div class="compare-wrap reveal">
      <table class="compare">
        <thead>
          <tr><th>Capability</th><th>SMS8 MCP</th><th>AgentSIM</th></tr>
        </thead>
        <tbody>
          <tr><td>Phone number you talk to</td>           <td class="ok">Your own SIM, persistent</td>          <td class="bad">Disposable, rented per session</td></tr>
          <tr><td>Pricing</td>                            <td class="ok">$16/month flat</td>                   <td class="bad">$0.99 per session</td></tr>
          <tr><td>send_sms (outbound)</td>                <td class="ok">Yes, your real SIM</td>               <td class="bad">No</td></tr>
          <tr><td>send_otp + verify_otp (your end-users)</td><td class="ok">Yes, configurable</td>             <td class="bad">No, only inbound</td></tr>
          <tr><td>wait_for_otp (agent receives)</td>      <td class="ok">Yes (your SIM)</td>                   <td class="ok">Yes (disposable SIM)</td></tr>
          <tr><td>Reply / two-way SMS</td>                <td class="ok">Native</td>                           <td class="bad">No</td></tr>
          <tr><td>Inbound webhook</td>                    <td class="ok">Yes, HMAC-signed</td>                 <td class="bad">No</td></tr>
          <tr><td>A2P 10DLC registration</td>             <td class="ok">Not required</td>                     <td class="ok">Not required</td></tr>
          <tr><td>Open-source MCP code</td>               <td class="ok">MIT, GitHub</td>                      <td class="ok">MIT, GitHub</td></tr>
          <tr><td>Best for</td>                           <td class="ok">Real product, real users</td>         <td class="bad">One-shot autonomous signups</td></tr>
        </tbody>
      </table>
    </div>
    <p class="reveal" style="max-width:820px;margin:36px auto 0;color:#9999ad;font-size:14.5px;line-height:1.7;">
      We genuinely like AgentSIM. It is the right tool when an agent needs a throwaway identity to register on a third-party service. The two MCPs are complementary &mdash; you can register both in <code>opencode.json</code> and let the agent pick the right one per task. Most of our customers register both: SMS8 for "send SMS to my real users" and AgentSIM for "sign up for a SaaS the agent needs an account on".
    </p>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>OpenCode &times; SMS8 questions</h2>
    </div>
    <div class="faq reveal">
      <details open><summary>Does OpenCode support MCP servers?</summary><p>Yes. OpenCode supports both remote (HTTP/SSE) and local (stdio) MCP servers via the <code>mcp</code> section of <code>opencode.json</code> or <code>opencode.jsonc</code>. You can also manage them from the CLI with <code>opencode mcp list</code>, <code>opencode mcp auth</code> and <code>opencode mcp debug</code>.</p></details>

      <details><summary>What does the SMS8 MCP add to OpenCode?</summary><p>Nine new tools your OpenCode agent can call directly: <code>setup_sms8</code> to handshake, <code>send_sms</code>, <code>send_otp</code>, <code>verify_otp</code>, <code>wait_for_otp</code>, <code>get_messages</code>, <code>list_devices</code>, <code>get_balance</code>, and <code>create_webhook</code>.</p></details>

      <details><summary>How do I add SMS8 MCP to opencode.json?</summary><p>Open <code>opencode.json</code> (or <code>opencode.jsonc</code>) at the root of your project. Add an entry under <code>mcp</code> with <code>type</code> set to <code>remote</code>, <code>url</code> set to <code>https://mcp.sms8.io</code> and an <code>Authorization: Bearer</code> header carrying your SMS8 API key. Restart OpenCode and the nine tools appear in the agent's tool list.</p></details>

      <details><summary>Do I need Twilio to use SMS8 with OpenCode?</summary><p>No. SMS8 routes every SMS through your own Android phone and SIM card. There is no Twilio account, no virtual number rental, no A2P 10DLC registration and no per-message fee.</p></details>

      <details><summary>Can my OpenCode agent wait for an incoming SMS?</summary><p>Yes. The <code>wait_for_otp</code> tool blocks until an OTP-shaped SMS lands on your paired Android, then extracts the numeric code and returns it. Optional filters for sender phone, device, SIM slot, body substring and code length. Default timeout 60 seconds, configurable up to 180.</p></details>

      <details><summary>How is SMS8 different from AgentSIM for OpenCode?</summary><p>AgentSIM rents a disposable real-SIM number per session, charged per session. SMS8 connects your own persistent phone number to OpenCode for a flat 16 USD per month. Use AgentSIM for one-shot autonomous signups under a throwaway identity. Use SMS8 for any real business where customers should see your real number.</p></details>

      <details><summary>Where is the SMS8 MCP server hosted?</summary><p>At <code>mcp.sms8.io</code>. It speaks JSON-RPC 2.0 over HTTPS, MCP revision 2024-11-05. Auth is a Bearer header carrying your SMS8 API key. The MCP source code is MIT-licensed at <a href="https://github.com/1fancy/sms8-sms-gateway">github.com/1fancy/sms8-sms-gateway</a>.</p></details>

      <details><summary>Can I share the same API key with Claude Code, Cursor and OpenCode?</summary><p>Yes. One SMS8 API key works across every MCP client (Claude Code, Cursor, Windsurf, OpenCode and any future MCP-compatible tool). All clients hit the same MCP endpoint with the same Bearer header.</p></details>
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
        <a class="btn-ghost btn-lg" href="/sms-api-documentation">Browse the API docs</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
