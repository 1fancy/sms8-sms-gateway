<?php
$page      = 'otp';
$title     = 'SMS OTP Verification API | SMS8 MCP Phone Verification for Claude Code, Cursor';
$desc      = 'SMS OTP verification API for the SMS8 MCP server. Send and verify one-time passwords from Claude Code, Cursor, Windsurf or any HTTP client through your Android phone as the SMS gateway. Configurable code length, expiry, and attempts. Hard per-phone abuse cap. No A2P 10DLC, no per-message fees.';
$canonical = 'https://mcp.sms8.io/sms-otp-verification-api';
$jsonld = <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"TechArticle","headline":"SMS OTP Verification API | SMS8 MCP","description":"Reference for send_otp and verify_otp on the SMS8 MCP server: code length, expiry, rate limits, security model, curl examples.","url":"https://mcp.sms8.io/sms-otp-verification-api","publisher":{"@type":"Organization","name":"SMS8.io"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"How do I send an OTP through SMS8?","acceptedAnswer":{"@type":"Answer","text":"POST to https://app.sms8.io/ajax/otp-send.php with your phone number. The endpoint accepts an Authorization: Bearer header. Optional fields: length (4 to 8), expires_in (60 to 900 seconds), max_attempts (1 to 10), template, and a device picker."}},
{"@type":"Question","name":"How does verify_otp work?","acceptedAnswer":{"@type":"Answer","text":"POST the phone and the code the user typed to https://app.sms8.io/ajax/otp-verify.php. The server runs a constant-time compare against the latest OTP and returns verified true or false. On failure, reason and attempts_left are returned."}},
{"@type":"Question","name":"What is the OTP abuse cap?","acceptedAnswer":{"@type":"Answer","text":"Any single phone can receive at most 5 OTPs in a rolling 24-hour window. This hard cap is not user-configurable and protects recipients from being spammed even if your API key leaks."}},
{"@type":"Question","name":"Do I need A2P 10DLC for SMS OTPs through SMS8?","acceptedAnswer":{"@type":"Answer","text":"No. SMS8 routes OTPs through your paired Android phone and SIM card."}}
]}
</script>
HTML;
require __DIR__ . '/_header.php';
?>

<section class="page-hero page-hero-sm">
  <div class="container">
    <div class="page-hero-inner reveal">
      <span class="hero-badge"><span class="badge-dot"></span>OTP reference</span>
      <h1>OTP and <span class="gradient-text">phone verification</span> through SMS8</h1>
      <p class="lede">Send and verify one-time passwords from Claude Code, Cursor, Windsurf or any HTTP client. Configurable code length, expiry, and attempts. Hard per-phone abuse cap.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/otp">Try the live test</a>
        <a class="btn-ghost btn-lg" href="/sms-api-documentation">API reference</a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">How it works</span>
      <h2>Two endpoints, one round-trip</h2>
      <p class="section-lead">Same logic whether the caller is an AI assistant via MCP, an external client via HTTPS, or your own backend.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: repeat(3, 1fr);">
      <div class="step-card reveal">
        <span class="step-num">STEP 01</span>
        <h3>send_otp</h3>
        <p>Generate a code, store the hash with expiry and attempt counter, dispatch the SMS through your paired Android.</p>
      </div>
      <div class="step-card reveal">
        <span class="step-num">STEP 02</span>
        <h3>User reads it</h3>
        <p>The customer receives the SMS on their phone, types the code into your app's verify form.</p>
      </div>
      <div class="step-card reveal">
        <span class="step-num">STEP 03</span>
        <h3>verify_otp</h3>
        <p>Constant-time compare against the latest issued code. Returns verified plus remaining attempts on mismatch.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt" id="endpoints">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Endpoints</span>
      <h2>POST only, Bearer auth</h2>
      <p class="section-lead">GET returns 405. Cookies ignored. The API key never lands in URLs or browser history.</p>
    </div>
    <div class="split-row reveal" style="align-items: start;">
      <div class="split-text">
        <h3 style="color:#c4b5fd;font-size:17px;margin-bottom:12px;">Send endpoint</h3>
        <p><code style="background:rgba(168,85,247,0.15);padding:2px 8px;border-radius:4px;color:#c4b5fd;">POST https://app.sms8.io/ajax/otp-send.php</code></p>
        <ul class="check-list" style="margin-top:14px;">
          <li><strong>phone</strong> &mdash; required, E.164</li>
          <li><strong>length</strong> &mdash; 4 to 8 digits, default 6</li>
          <li><strong>expires_in</strong> &mdash; 60 to 900 seconds, default 300</li>
          <li><strong>max_attempts</strong> &mdash; 1 to 10, default 5</li>
          <li><strong>template</strong> &mdash; body with <code>{code}</code> placeholder</li>
          <li><strong>option / devices / useRandomDevice</strong> &mdash; device picker</li>
        </ul>
      </div>
      <div class="split-text">
        <h3 style="color:#c4b5fd;font-size:17px;margin-bottom:12px;">Verify endpoint</h3>
        <p><code style="background:rgba(168,85,247,0.15);padding:2px 8px;border-radius:4px;color:#c4b5fd;">POST https://app.sms8.io/ajax/otp-verify.php</code></p>
        <ul class="check-list" style="margin-top:14px;">
          <li><strong>phone</strong> &mdash; required, E.164</li>
          <li><strong>code</strong> &mdash; the code the user typed</li>
        </ul>
        <p style="margin-top:14px;"><strong>Responses</strong>:</p>
        <ul class="check-list" style="margin-top:6px;">
          <li><code>{verified: true}</code> on success</li>
          <li><code>{verified: false, reason: "code_mismatch", attempts_left: 4}</code></li>
          <li>Other reasons: <code>expired</code>, <code>not_found</code>, <code>max_attempts</code></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section" id="curl">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">curl examples</span>
      <h2>Working calls</h2>
    </div>
    <div class="reveal" style="max-width:820px;margin:0 auto;">
      <h3 style="font-size:16px;color:#c4b5fd;margin-bottom:10px;">Send an OTP</h3>
      <pre class="code-block">curl -X POST https://app.sms8.io/ajax/otp-send.php \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"phone=+1234567890"</span>

<span class="c"># Override defaults:</span>
<span class="c">#   -d "length=6"  -d "expires_in=300"</span>
<span class="c">#   -d "max_attempts=5"</span>
<span class="c">#   -d "template=Your code is {code}, expires soon."</span>

<span class="c"># Pick a sender device or SIM:</span>
<span class="c">#   -d "option=0" --data-urlencode 'devices=["DEVICE_ID"]'</span>
<span class="c">#   -d "option=1"               # broadcast across all devices</span>
<span class="c">#   -d "option=2"               # broadcast across all SIMs</span>
<span class="c">#   -d "useRandomDevice=1"      # one random sender</span></pre>

      <h3 style="font-size:16px;color:#c4b5fd;margin:26px 0 10px;">Verify a code</h3>
      <pre class="code-block">curl -X POST https://app.sms8.io/ajax/otp-verify.php \
  -H <span class="s">"Authorization: Bearer YOUR_SMS8_API_KEY"</span> \
  -d <span class="s">"phone=+1234567890"</span> \
  -d <span class="s">"code=123456"</span>

<span class="c"># Success: {"verified": true}
# Failure: {"verified": false, "reason": "code_mismatch", "attempts_left": 4}</span></pre>

      <h3 style="font-size:16px;color:#c4b5fd;margin:26px 0 10px;">From an AI assistant (MCP)</h3>
      <p style="color:#b8b8c8;line-height:1.6;">If Claude Code, Cursor, or Windsurf is connected to <a href="/" style="color:#c4b5fd;">mcp.sms8.io</a>, ask: <em>"Add SMS phone verification to my signup flow using the sms8 MCP"</em>. The assistant calls <code>send_otp</code> and <code>verify_otp</code> with your defaults and wraps the existing signup or login routes.</p>
    </div>
  </div>
</section>

<section class="section section-alt" id="defaults">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Defaults you control</span>
      <h2>Per-user defaults via the dashboard</h2>
      <p class="section-lead">Set fallbacks once. They apply whenever a caller leaves the field blank. Hard limits stay in place regardless.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3>Code length</h3><p>4 to 8 digits. Default 6. Tune for memorability vs brute-force resistance.</p></div>
      <div class="step-card reveal"><h3>Expiry</h3><p>60 to 900 seconds. Default 300 (5 min). Short enough to limit replay, long enough for users to type.</p></div>
      <div class="step-card reveal"><h3>Verify attempts</h3><p>1 to 10 wrong guesses before the code is locked. Default 5.</p></div>
      <div class="step-card reveal"><h3>Resend cooldown</h3><p>30 to 600 seconds between sends to the same phone. Default 60.</p></div>
      <div class="step-card reveal"><h3>SMS template</h3><p>Customize the body. Must contain the literal <code>{code}</code> placeholder.</p></div>
      <div class="step-card reveal" style="background: linear-gradient(180deg, rgba(168,85,247,0.10), rgba(168,85,247,0.04)); border-color: rgba(168,85,247,0.4);">
        <h3 style="color:#c4b5fd;">Edit your defaults</h3>
        <p>Live preview reflects every setting. Send a real OTP to your phone and verify the round-trip.</p>
        <p style="margin-top:8px;"><a href="https://app.sms8.io/otp" style="color:#c4b5fd;font-weight:600;">Open OTP settings &rarr;</a></p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="security">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">Security model</span>
      <h2>Hard caps that protect your customers</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal"><h3>5 OTPs per phone per 24h</h3><p>The hardest cap. Not user-configurable. Even if your API key leaks, no single phone can be spammed beyond 5 codes per rolling 24-hour window.</p></div>
      <div class="step-card reveal"><h3>Transaction-locked checks</h3><p>Cooldown and the 24h cap run under <code>SELECT ... FOR UPDATE</code>. Parallel callers cannot race past the limit.</p></div>
      <div class="step-card reveal"><h3>Constant-time verify</h3><p>verify_otp uses <code>hash_equals</code> for the compare. No timing leaks.</p></div>
      <div class="step-card reveal"><h3>POST only, no cookies</h3><p>GET on either endpoint returns 405. Cookies are ignored on auth resolution. CSRF via image tags is not possible.</p></div>
      <div class="step-card reveal"><h3>Per-OTP attempt cap</h3><p>Default 5 wrong attempts per code. After that, the code is locked and a new <code>send_otp</code> is required.</p></div>
      <div class="step-card reveal"><h3>Generic error messages</h3><p>Internal exceptions return <code>Internal error</code>. Stack traces never leak to clients.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-head reveal">
      <span class="section-eyebrow">FAQ</span>
      <h2>OTP questions</h2>
    </div>
    <div class="faq reveal">
      <details open><summary>How do I send an OTP through SMS8?</summary><p>POST to <code>https://app.sms8.io/ajax/otp-send.php</code> with your phone number. The endpoint accepts an <code>Authorization: Bearer</code> header. Optional fields: <code>length</code> (4 to 8), <code>expires_in</code> (60 to 900s), <code>max_attempts</code> (1 to 10), <code>template</code>, plus a device picker.</p></details>
      <details><summary>How does verify_otp work?</summary><p>POST the phone and the code the user typed to <code>https://app.sms8.io/ajax/otp-verify.php</code>. The server runs a constant-time compare against the latest OTP and returns <code>verified: true</code> or <code>verified: false</code>. On failure, <code>reason</code> and <code>attempts_left</code> are returned.</p></details>
      <details><summary>What is the OTP abuse cap?</summary><p>Any single phone can receive at most 5 OTPs in a rolling 24-hour window. This hard cap is not user-configurable and protects recipients even if your API key leaks.</p></details>
      <details><summary>Do I need A2P 10DLC for SMS OTPs through SMS8?</summary><p>No. SMS8 routes OTPs through your paired Android phone and SIM, so A2P 10DLC registration is not required.</p></details>
      <details><summary>Can the AI assistant add OTP verification automatically?</summary><p>Yes. The bundled Claude Code Skill teaches the assistant when to call <code>send_otp</code> and <code>verify_otp</code>. A prompt like <em>Add phone verification to /signup</em> is enough.</p></details>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner reveal">
      <h2>Add phone verification in 60 seconds</h2>
      <p>Sign up free, set your OTP defaults, and let your AI assistant wire the verification into your app.</p>
      <div class="hero-cta">
        <a class="btn-cta btn-lg" href="https://app.sms8.io/">Create free account</a>
        <a class="btn-ghost btn-lg" href="/">Back to MCP overview</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
