<?php
$page      = 'api';
$title     = 'API Documentation | SMS8 MCP Server';
$desc      = 'Complete reference for the SMS8 MCP server. JSON-RPC 2.0 over HTTPS, Bearer authentication, 7 tools (setup_sms8, send_sms, send_otp, verify_otp, get_messages, list_devices, create_webhook), rate limits, security model, and curl examples.';
$canonical = 'https://mcp.sms8.io/api';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"SMS8 MCP API Documentation","description":"Reference for the SMS8 Model Context Protocol server: JSON-RPC interface, tools, authentication, and rate limits.","url":"https://mcp.sms8.io/api","publisher":{"@type":"Organization","name":"SMS8.io"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"What protocol does the SMS8 MCP server use?","acceptedAnswer":{"@type":"Answer","text":"JSON-RPC 2.0 over HTTPS at mcp.sms8.io. The server implements MCP revision 2024-11-05 with initialize, tools/list, tools/call and ping methods."}},
{"@type":"Question","name":"How do I authenticate against the SMS8 MCP API?","acceptedAnswer":{"@type":"Answer","text":"Send your SMS8 API key in the Authorization: Bearer header on every tools/call request. The X-Api-Key header is also accepted. The setup_sms8 tool is public; all other tools require authentication."}},
{"@type":"Question","name":"What are the SMS8 MCP rate limits?","acceptedAnswer":{"@type":"Answer","text":"Per-key SMS sending inherits from the SMS8 plan. The OTP endpoints enforce a hard cap of 5 OTPs per phone per 24-hour window, a configurable resend cooldown (30 to 600 seconds, default 60), and a per-OTP attempt cap (default 5)."}},
{"@type":"Question","name":"Is A2P 10DLC required to use the SMS8 MCP?","acceptedAnswer":{"@type":"Answer","text":"No. SMS8 routes messages through your paired Android phone and SIM card, so A2P 10DLC registration is not required."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<section class="page-hero page-hero-sm">
  <div class="container">
    <div class="page-hero-inner reveal">
      <span class="hero-badge"><span class="badge-dot"></span>API reference</span>
      <h1>SMS8 MCP <span class="gradient-text">API documentation</span></h1>
      <p class="lede">JSON-RPC 2.0 over HTTPS. Seven tools. Bearer authentication. Works with Claude Code, Cursor, Windsurf, Codex and any MCP-compatible AI tool.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/mcp-setup.php">Get your API key</a>
        <a class="btn-ghost btn-lg" href="https://github.com/1fancy/sms8-sms-gateway" target="_blank" rel="noopener">View on GitHub</a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="endpoint">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Base endpoint</span>
      <h2>One HTTPS endpoint, JSON-RPC 2.0</h2>
      <p class="section-lead">All calls go to <code style="background:rgba(168,85,247,0.15);color:#c4b5fd;padding:2px 8px;border-radius:4px;">https://mcp.sms8.io</code>. GET serves the landing. POST runs the protocol.</p>
    </div>
    <div class="split-row reveal">
      <div class="split-text">
        <ul class="check-list">
          <li><strong>Protocol</strong>: Model Context Protocol revision 2024-11-05</li>
          <li><strong>Methods</strong>: <code>initialize</code>, <code>tools/list</code>, <code>tools/call</code>, <code>ping</code></li>
          <li><strong>Transport</strong>: HTTP (or stdio via <code>@sms8/mcp</code>)</li>
          <li><strong>Content type</strong>: <code>application/json</code></li>
          <li><strong>Auth</strong>: <code>Authorization: Bearer YOUR_KEY</code></li>
        </ul>
      </div>
      <div class="visual-card">
        <div class="visual-card-header">curl &middot; tools/list</div>
<pre>curl https://mcp.sms8.io \
  -H <span class="s">"Content-Type: application/json"</span> \
  -H <span class="s">"Authorization: Bearer sk_…"</span> \
  -d <span class="s">'{"jsonrpc":"2.0","id":1,
      "method":"tools/list"}'</span></pre>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="tools">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Tools reference</span>
      <h2>Seven tools, full schemas</h2>
      <p class="section-lead">Each description matches what your AI assistant sees in <code style="background:rgba(168,85,247,0.15);color:#c4b5fd;padding:2px 8px;border-radius:4px;">tools/list</code>.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
      <div class="step-card reveal">
        <h3><code>setup_sms8</code></h3>
        <p><strong>Public</strong>. Validates the API key, returns account info, devices, and next-steps. Call once per session.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>api_key</code> (optional, header preferred)</p>
      </div>
      <div class="step-card reveal">
        <h3><code>send_sms</code></h3>
        <p>Send one SMS through a paired Android. Per-device and per-SIM routing.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>phone</code>, <code>message</code>, <code>device_id</code>, <code>sim_slot</code>, <code>devices</code>, <code>option</code>, <code>random_device</code></p>
      </div>
      <div class="step-card reveal">
        <h3><code>send_otp</code></h3>
        <p>Generate and dispatch a verification code. Stores hash + expiry server-side.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>phone</code>, <code>length</code> (4-8), <code>template</code>, <code>expires_in</code> (60-900), <code>max_attempts</code> (1-10)</p>
      </div>
      <div class="step-card reveal">
        <h3><code>verify_otp</code></h3>
        <p>Constant-time compare against the latest OTP for that phone. Returns <code>verified</code> + <code>attempts_left</code> on mismatch.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>phone</code>, <code>code</code></p>
      </div>
      <div class="step-card reveal">
        <h3><code>get_messages</code></h3>
        <p>Fetch recent inbox or sent SMS. Bound SQL parameters.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>direction</code> (all|received|sent), <code>limit</code> (1-100), <code>phone</code></p>
      </div>
      <div class="step-card reveal">
        <h3><code>list_devices</code></h3>
        <p>Return every paired Android device with enabled flag, primary flag, and model.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> none</p>
      </div>
      <div class="step-card reveal">
        <h3><code>create_webhook</code></h3>
        <p>Register a callback URL for inbound SMS + delivery events. SSRF-guarded. HMAC-signed.</p>
        <p style="margin-top:8px;"><strong>Args:</strong> <code>url</code> (HTTPS), <code>enabled</code> (default true)</p>
      </div>
      <div class="step-card reveal">
        <h3>Error codes</h3>
        <p><code>-32601</code> method not found · <code>-32602</code> invalid params · <code>-32001</code> missing api_key · <code>-32002</code> invalid api_key · <code>-32603</code> internal · <code>-32700</code> parse error</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="auth">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Authentication</span>
      <h2>Bearer header, three accepted patterns</h2>
      <p class="section-lead">Authorization header is preferred. Other paths exist for backward compatibility.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(3, 1fr);">
      <div class="step-card reveal">
        <span class="step-num">PREFERRED</span>
        <h3>Authorization: Bearer</h3>
        <p>Header-based. Key stays out of URLs, browser history, and server access logs.</p>
        <pre class="code-block" style="margin-top:8px;">Authorization: Bearer sk_…</pre>
      </div>
      <div class="step-card reveal">
        <span class="step-num">SUPPORTED</span>
        <h3>X-Api-Key header</h3>
        <p>Alternative header for tools that cannot set Authorization.</p>
        <pre class="code-block" style="margin-top:8px;">X-Api-Key: sk_…</pre>
      </div>
      <div class="step-card reveal">
        <span class="step-num">LEGACY</span>
        <h3>POST body field</h3>
        <p>OTP endpoints accept <code>api_key=</code> in the form body. POST only — GET returns 405.</p>
        <pre class="code-block" style="margin-top:8px;">api_key=sk_…</pre>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="examples">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">curl examples</span>
      <h2>Working calls you can paste in</h2>
    </div>
    <div class="reveal" style="max-width:820px;margin:0 auto;">
      <h3 style="font-size:16px;color:#c4b5fd;margin-bottom:10px;">Send an SMS</h3>
      <pre class="code-block">curl https://mcp.sms8.io \
  -H <span class="s">"Content-Type: application/json"</span> \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">'{"jsonrpc":"2.0","id":1,"method":"tools/call",
      "params":{"name":"send_sms",
                "arguments":{"phone":"+1234567890",
                             "message":"Hello from SMS8"}}}'</span></pre>

      <h3 style="font-size:16px;color:#c4b5fd;margin:26px 0 10px;">Issue and verify an OTP</h3>
      <pre class="code-block">curl https://mcp.sms8.io \
  -H <span class="s">"Content-Type: application/json"</span> \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">'{"jsonrpc":"2.0","id":2,"method":"tools/call",
      "params":{"name":"send_otp",
                "arguments":{"phone":"+1234567890"}}}'</span>

curl https://mcp.sms8.io \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d <span class="s">'{"jsonrpc":"2.0","id":3,"method":"tools/call",
      "params":{"name":"verify_otp",
                "arguments":{"phone":"+1234567890","code":"123456"}}}'</span></pre>

      <h3 style="font-size:16px;color:#c4b5fd;margin:26px 0 10px;">List devices</h3>
      <pre class="code-block">curl https://mcp.sms8.io \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d <span class="s">'{"jsonrpc":"2.0","id":4,"method":"tools/call",
      "params":{"name":"list_devices","arguments":{}}}'</span></pre>
    </div>
  </div>
</section>

<section class="section" id="security">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Security model</span>
      <h2>What the server enforces</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3>Bound SQL parameters</h3><p>Every query uses prepared statements. No user-input string interpolation.</p></div>
      <div class="step-card reveal"><h3>SSRF block list</h3><p>create_webhook blocks RFC1918, CGNAT, link-local, IPv4-mapped IPv6 and resolves the host at validation time.</p></div>
      <div class="step-card reveal"><h3>Constant-time compare</h3><p>verify_otp uses <code>hash_equals</code> for code matching. No byte-by-byte timing leaks.</p></div>
      <div class="step-card reveal"><h3>Race-proof rate limits</h3><p>OTP send and verify both wrap check + update in DB transactions with row locks.</p></div>
      <div class="step-card reveal"><h3>API key redaction</h3><p>setup_sms8 returns only the last 4 chars so the full key never lands in AI chat history.</p></div>
      <div class="step-card reveal"><h3>CORS off for tools/call</h3><p>Only discovery methods advertise CORS. Malicious web pages cannot drive SMS sends.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>API questions</h2>
    </div>
    <div class="faq reveal">
      <details open><summary>What protocol does the SMS8 MCP server use?</summary><p>JSON-RPC 2.0 over HTTPS at <code>mcp.sms8.io</code>. The server implements MCP revision 2024-11-05 with <code>initialize</code>, <code>tools/list</code>, <code>tools/call</code> and <code>ping</code> methods.</p></details>
      <details><summary>How do I authenticate?</summary><p>Send your SMS8 API key in the <code>Authorization: Bearer</code> header on every <code>tools/call</code> request. The <code>X-Api-Key</code> header is also accepted. The <code>setup_sms8</code> tool is public; all other tools require authentication.</p></details>
      <details><summary>What are the rate limits?</summary><p>SMS sending inherits from your SMS8 plan. OTP endpoints add a hard cap of 5 OTPs per phone per 24-hour window, a configurable resend cooldown (30 to 600 seconds, default 60), and a per-OTP attempt cap (default 5).</p></details>
      <details><summary>Is A2P 10DLC required?</summary><p>No. SMS8 routes messages through your paired Android phone and SIM, so A2P 10DLC registration is not required. No carrier fees, no phone-number provisioning, no per-message charges.</p></details>
      <details><summary>Does the server log my API key?</summary><p>No. The <code>setup_sms8</code> response masks the key to last 4 chars. OTP endpoints accept POST only and ignore cookies. Use the Bearer header rather than putting the key in URL query strings.</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Start sending in 60 seconds</h2>
      <p>Sign up free, pair your Android, paste the MCP config into your AI tool, and ship.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/">Create free account</a>
        <a class="btn-ghost btn-lg" href="/otp">Read OTP docs</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
