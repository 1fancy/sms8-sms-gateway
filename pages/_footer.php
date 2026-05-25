
</main>

<footer>
  <div class="container">
    <div class="footer-row">
      <span>&copy; <?= date('Y') ?> SMS8.io &middot; Turn your Android phone into an SMS gateway</span>
      <span>
        <a href="https://sms8.io">sms8.io</a> &middot;
        <a href="https://app.sms8.io">dashboard</a> &middot;
        <a href="/sms-api-documentation">API docs</a> &middot;
        <a href="/sms-otp-verification-api">OTP docs</a> &middot;
        <a href="https://github.com/1fancy/sms8-sms-gateway" target="_blank" rel="noopener">github</a>
      </span>
    </div>
  </div>
</footer>

<script>
// Reveal-on-scroll
(function () {
  var els = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    els.forEach(function (el) { el.classList.add('visible'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  els.forEach(function (el) { io.observe(el); });
})();

// Scroll-state header: transparent + merged on top, solid blurred dark when
// the user scrolls down. Triggered at 24px of vertical scroll.
(function () {
  var hdr = document.getElementById('site-header');
  if (!hdr) return;
  function update() {
    if (window.scrollY > 24) hdr.classList.add('is-scrolled');
    else hdr.classList.remove('is-scrolled');
  }
  update();
  window.addEventListener('scroll', update, { passive: true });
})();

// Mobile nav toggle (+ close on link tap, esc, and outside click)
(function () {
  var btn = document.getElementById('menu-toggle');
  var nav = document.getElementById('mobile-nav');
  if (!btn || !nav) return;
  function setOpen(open) {
    nav.classList.toggle('open', open);
    btn.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  btn.addEventListener('click', function () { setOpen(!nav.classList.contains('open')); });
  nav.addEventListener('click', function (e) {
    if (e.target.tagName === 'A') setOpen(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && nav.classList.contains('open')) setOpen(false);
  });
})();

// Copy-to-clipboard for .code-card blocks. Markup contract:
//   <div class="code-card"><div class="code-card-head">…<button class="code-card-copy">…</button></div><pre>…</pre></div>
(function () {
  var btns = document.querySelectorAll('.code-card-copy');
  if (!btns.length || !navigator.clipboard) return;
  btns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('.code-card');
      var pre = card && card.querySelector('pre');
      if (!pre) return;
      var text = pre.innerText;
      navigator.clipboard.writeText(text).then(function () {
        var label = btn.querySelector('.label');
        var prev = label ? label.textContent : '';
        if (label) label.textContent = 'Copied';
        btn.classList.add('is-ok');
        setTimeout(function () {
          btn.classList.remove('is-ok');
          if (label) label.textContent = prev || 'Copy';
        }, 1400);
      });
    });
  });
})();
</script>

</body>
</html>
