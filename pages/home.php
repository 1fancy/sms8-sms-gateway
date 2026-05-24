<?php
$page      = 'home';
$title     = 'SMS Gateway MCP for Claude Code, Cursor &amp; Windsurf | SMS8';
$desc      = 'SMS gateway MCP server for AI coding tools. Send SMS, issue and verify OTPs, register webhooks from Claude Code, Cursor and Windsurf using your paired Android phone. No Twilio, no A2P 10DLC, no per-message fees.';
$canonical = 'https://mcp.sms8.io/';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareSourceCode","name":"SMS8 MCP Server","description":"SMS gateway MCP server: send SMS, issue and verify OTPs, register webhooks from Claude Code, Cursor and Windsurf.","codeRepository":"https://github.com/1fancy/sms8-sms-gateway","programmingLanguage":"PHP","license":"https://opensource.org/licenses/MIT","url":"https://mcp.sms8.io","keywords":"sms gateway, android sms gateway, sms mcp, sms api claude code, sms api cursor, sms api windsurf, vibe coding sms","publisher":{"@type":"Organization","name":"SMS8","url":"https://sms8.io"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"What is the SMS8 MCP server?","acceptedAnswer":{"@type":"Answer","text":"A Model Context Protocol server at mcp.sms8.io so AI coding tools like Claude Code, Cursor and Windsurf can send SMS, issue one-time passwords, and configure webhooks. SMS routes through a paired Android phone via the SMS8 SMS gateway."}},
{"@type":"Question","name":"Do I need A2P 10DLC?","acceptedAnswer":{"@type":"Answer","text":"No. SMS8 uses your own Android phone and SIM card as the gateway. No A2P 10DLC, no per-message fees, no phone-number provisioning."}},
{"@type":"Question","name":"How do I add SMS to a Claude Code project?","acceptedAnswer":{"@type":"Answer","text":"Add the MCP server to ~/.config/claude/mcp-servers.json with the HTTP transport pointing at https://mcp.sms8.io and your SMS8 API key as a Bearer token. Or run /plugin marketplace add 1fancy/sms8-sms-gateway followed by /plugin install sms8-sms-gateway."}},
{"@type":"Question","name":"Does this work with Cursor and Windsurf?","acceptedAnswer":{"@type":"Answer","text":"Yes. Both support HTTP MCP servers. Add https://mcp.sms8.io with your SMS8 API key as a Bearer header."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner reveal">
      <span class="hero-badge"><span class="badge-dot"></span>MCP server live at mcp.sms8.io</span>
      <h1>SMS, OTP and webhooks for <span class="gradient-text">Claude Code, Cursor and Windsurf</span></h1>
      <p class="lede">
        Plug unlimited SMS, OTP verification, and inbound webhooks into your AI coding assistant using your paired Android phone as the gateway. Same SMS8 API key, JSON-RPC tools for every flow. No Twilio, no A2P 10DLC, no per-message fees.
      </p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/mcp-setup.php">Open setup wizard</a>
        <a class="btn-ghost btn-lg" href="https://github.com/1fancy/sms8-sms-gateway" target="_blank" rel="noopener">View on GitHub</a>
      </div>
      <div class="hero-trust">
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Free 5-day trial</span>
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> No A2P 10DLC</span>
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> No credit card</span>
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> MIT licensed</span>
      </div>
    </div>
  </div>
</section>

<section class="section" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Seven tools</span>
      <h2>What your AI assistant can do</h2>
      <p class="section-lead">Every tool re-uses the same send pipeline as the SMS8 dashboard. Credits, retries, multi-device routing and webhook signing behave identically to a direct API call.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3><code>setup_sms8</code></h3><p>Handshake. Validates the API key, returns devices, plan and integration context.</p></div>
      <div class="step-card reveal"><h3><code>send_sms</code></h3><p>Send a single SMS through a paired Android. Per-device and per-SIM routing.</p></div>
      <div class="step-card reveal"><h3><code>send_otp</code></h3><p>Generate and dispatch a verification code. Configurable length, expiry and attempts.</p></div>
      <div class="step-card reveal"><h3><code>verify_otp</code></h3><p>Constant-time compare against the latest OTP. Returns remaining attempts on mismatch.</p></div>
      <div class="step-card reveal"><h3><code>get_messages</code></h3><p>Fetch recent inbox or sent SMS. Filter by direction or phone.</p></div>
      <div class="step-card reveal"><h3><code>list_devices</code></h3><p>List paired Android devices. Pick the sender when load-balancing across SIMs.</p></div>
      <div class="step-card reveal"><h3><code>create_webhook</code></h3><p>Register a callback URL for inbound SMS. HTTPS only, SSRF-protected, HMAC-signed.</p></div>
      <div class="step-card reveal" style="background: linear-gradient(180deg, rgba(168,85,247,0.10), rgba(168,85,247,0.04)); border-color: rgba(168,85,247,0.4);">
        <h3 style="color: #c4b5fd;">Read the docs</h3>
        <p>Full API + OTP reference with curl examples, rate limits, and security model.</p>
        <p style="margin-top: 8px;"><a href="/sms-api-documentation" style="color: #c4b5fd; font-weight: 600;">API docs →</a> &nbsp; <a href="/sms-otp-verification-api" style="color: #c4b5fd; font-weight: 600;">OTP docs →</a></p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="install">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Install</span>
      <h2>Three install paths, pick one</h2>
      <p class="section-lead">SMS8 ships as a hosted HTTP MCP server, a Claude Code plugin with a built-in Skill, and an npx launcher for stdio clients.</p>
    </div>
    <div class="split-row reveal">
      <div class="split-text">
        <ul class="check-list">
          <li><strong>Hosted HTTP</strong> works in Claude Code, Cursor and Windsurf with a Bearer token</li>
          <li><strong>Claude plugin</strong> via <code>/plugin marketplace add 1fancy/sms8-sms-gateway</code></li>
          <li><strong>npx</strong> launches the stdio bridge: <code>npx -y @sms8/mcp</code></li>
        </ul>
        <div class="hero-cta" style="margin-top: 26px; justify-content: flex-start;">
          <a class="btn-cta" href="https://app.sms8.io/mcp-setup.php">Open setup wizard</a>
          <a class="btn-ghost" href="/sms-api-documentation">Read API docs</a>
        </div>
      </div>
      <div class="visual-card reveal">
        <div class="visual-card-header">Claude Code &middot; mcp-servers.json</div>
<pre>{
  <span class="k">"mcpServers"</span>: {
    <span class="k">"sms8"</span>: {
      <span class="k">"url"</span>: <span class="s">"https://mcp.sms8.io"</span>,
      <span class="k">"transport"</span>: <span class="s">"http"</span>,
      <span class="k">"headers"</span>: {
        <span class="k">"Authorization"</span>:
          <span class="s">"Bearer YOUR_SMS8_API_KEY"</span>
      }
    }
  }
}</pre>
      </div>
    </div>
  </div>
</section>

<section class="section" id="use-cases">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Real prompts</span>
      <h2>Use cases you can paste right now</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3>Phone verification</h3><p>"Wire phone-number verification into this app using the sms8 MCP. Use send_otp on /signup, verify_otp on /verify-phone, render the remaining attempts on error."</p></div>
      <div class="step-card reveal"><h3>Order notifications</h3><p>"When an order ships, send the customer an SMS with their tracking link using the sms8 MCP."</p></div>
      <div class="step-card reveal"><h3>Two-way support inbox</h3><p>"Register a webhook with the sms8 MCP at https://app.com/sms-in. Scaffold the handler that verifies HMAC and routes inbound SMS to the support queue."</p></div>
      <div class="step-card reveal"><h3>Passwordless login</h3><p>"Replace the magic-link email login with SMS OTPs using the sms8 MCP. 6-digit code, 5-minute expiry."</p></div>
      <div class="step-card reveal"><h3>Appointment reminders</h3><p>"Read tomorrow's appointments from the DB and send a reminder SMS 24h before each one. Use send_sms."</p></div>
      <div class="step-card reveal"><h3>2FA for admin</h3><p>"Add SMS 2FA to /admin login via the sms8 MCP. Lock the account after 5 failed verify_otp attempts."</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="compare">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Compared to</span>
      <h2>SMS8 MCP vs Twilio vs MessageBird</h2>
    </div>
    <div class="compare-wrap reveal">
      <table class="compare">
        <thead>
          <tr><th>Capability</th><th>SMS8 MCP</th><th>Twilio</th><th>MessageBird</th></tr>
        </thead>
        <tbody>
          <tr><td>Built-in MCP server</td>       <td class="ok">Yes</td>          <td class="bad">No</td>          <td class="bad">No</td></tr>
          <tr><td>Per-message fee</td>           <td class="ok">None</td>         <td class="bad">$0.0079+</td>    <td class="bad">$0.05+</td></tr>
          <tr><td>A2P 10DLC required</td>        <td class="ok">No</td>           <td class="bad">Yes</td>         <td class="bad">Yes</td></tr>
          <tr><td>Phone number provisioning</td> <td class="ok">Not needed</td>   <td class="bad">$1+ / mo</td>    <td class="bad">$2+ / mo</td></tr>
          <tr><td>Setup time</td>                <td class="ok">60 seconds</td>   <td class="bad">Days to weeks</td><td class="bad">Days</td></tr>
          <tr><td>OTP verification</td>          <td class="ok">Built-in, free</td><td class="bad">Extra service</td><td class="bad">Extra service</td></tr>
          <tr><td>Open-source MCP code</td>      <td class="ok">MIT</td>          <td class="bad">No</td>          <td class="bad">No</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section" id="security">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Production ready</span>
      <h2>Security defaults, not foot-guns</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3>Hard per-phone cap</h3><p>5 OTPs per number per 24h. Not user-configurable.</p></div>
      <div class="step-card reveal"><h3>Race-proof rate limits</h3><p>Cooldown and cap checks wrapped in DB transactions with row locks.</p></div>
      <div class="step-card reveal"><h3>POST-only OTP endpoints</h3><p>GET returns 405. Cookies ignored.</p></div>
      <div class="step-card reveal"><h3>API key redaction</h3><p>setup_sms8 returns only the last 4 chars of your key.</p></div>
      <div class="step-card reveal"><h3>SSRF-guarded webhooks</h3><p>Blocks loopback, RFC1918, CGNAT, link-local and IPv4-mapped IPv6.</p></div>
      <div class="step-card reveal"><h3>HMAC-signed deliveries</h3><p>Inbound SMS and delivery events signed with HMAC-SHA256.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>Frequently asked questions</h2>
    </div>
    <div class="faq reveal">
      <details open><summary>What is the SMS8 MCP server?</summary><p>A Model Context Protocol server at <code>mcp.sms8.io</code> so AI coding tools (Claude Code, Cursor, Windsurf, Codex, Devin) can send SMS, issue one-time passwords and configure webhooks. SMS routes through a paired Android phone via the SMS8 SMS gateway.</p></details>
      <details><summary>Do I need A2P 10DLC?</summary><p>No. SMS8 uses your own Android phone and SIM card as the gateway. No A2P 10DLC registration, no per-message carrier fees, no phone-number provisioning.</p></details>
      <details><summary>How do I add SMS to a Claude Code project?</summary><p>Add the MCP server to <code>~/.config/claude/mcp-servers.json</code> with the HTTP transport pointing at <code>https://mcp.sms8.io</code> and your SMS8 API key as a Bearer token. Or run <code>/plugin marketplace add 1fancy/sms8-sms-gateway</code> then <code>/plugin install sms8-sms-gateway</code>.</p></details>
      <details><summary>Does this work with Cursor and Windsurf?</summary><p>Yes. Both support HTTP MCP servers. Add <code>https://mcp.sms8.io</code> with your SMS8 API key as a Bearer header to <code>~/.cursor/mcp.json</code> or <code>~/.codeium/windsurf/mcp_config.json</code>.</p></details>
      <details><summary>Is this a Twilio alternative?</summary><p>Yes. SMS8 uses your own Android phone with your existing SIM. No per-message fees, no A2P 10DLC, no phone number to provision. Flat pricing from $16/month with unlimited SMS.</p></details>
      <details><summary>Is the source code public?</summary><p>Yes, MIT-licensed at <a href="https://github.com/1fancy/sms8-sms-gateway">github.com/1fancy/sms8-sms-gateway</a>.</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Send your first SMS from Claude Code in 60 seconds</h2>
      <p>Open the setup wizard, copy the prefilled MCP config into your AI tool, and ship.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/mcp-setup.php">Get started free</a>
        <a class="btn-ghost btn-lg" href="/sms-api-documentation">Browse API docs</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
