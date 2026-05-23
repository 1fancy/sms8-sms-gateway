
</main>

<footer>
  <div class="container">
    <div class="footer-row">
      <span>&copy; <?= date('Y') ?> SMS8.io &middot; Turn your Android phone into an SMS gateway</span>
      <span>
        <a href="https://sms8.io">sms8.io</a> &middot;
        <a href="https://app.sms8.io">dashboard</a> &middot;
        <a href="/api">API docs</a> &middot;
        <a href="/otp">OTP docs</a> &middot;
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

// Mobile nav toggle
(function () {
  var btn = document.getElementById('menu-toggle');
  var nav = document.getElementById('mobile-nav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.classList.toggle('open', open);
  });
})();
</script>

</body>
</html>
