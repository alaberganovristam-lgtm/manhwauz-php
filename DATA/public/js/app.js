'use strict';

/* ── Toast ───────────────────────────────── */
window.showToast = function (msg, ms) {
  ms = ms || 3000;
  var t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(function () { t.classList.remove('show'); }, ms);
};

/* ── Mobile nav ──────────────────────────── */
(function () {
  var overlay = document.getElementById('mobile-nav-overlay');
  var drawer  = document.getElementById('mobile-nav-drawer');
  var openBtn = document.getElementById('hamburger');
  var closeBtn= document.getElementById('mobile-nav-close');
  if (!overlay || !drawer) return;

  function open() {
    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    overlay.classList.remove('open');
    drawer.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (openBtn)  openBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);
})();

/* ── Trending tabs (Today / Weekly / Monthly) ── */
window.switchTrend = function (btn, panelId) {
  // Deactivate all tabs
  var tabs   = document.querySelectorAll('.trend-tab');
  var panels = document.querySelectorAll('.trend-panel');
  tabs.forEach(function (t) {
    t.style.background = '';
    t.style.color = 'rgba(255,255,255,.6)';
  });
  panels.forEach(function (p) { p.hidden = true; });
  // Activate clicked
  btn.style.background = '#913FE2';
  btn.style.color = '#fff';
  var panel = document.getElementById(panelId);
  if (panel) panel.hidden = false;
};

/* ── Popular sidebar tabs (Weekly / Monthly / All Time) ── */
window.switchPopular = function (btn, panelId) {
  var tabs   = document.querySelectorAll('.pop-tab');
  var panels = document.querySelectorAll('.pop-panel');
  tabs.forEach(function (t) {
    t.style.background = '';
    t.style.color = 'rgba(255,255,255,.6)';
  });
  panels.forEach(function (p) { p.hidden = true; });
  btn.style.background = '#913FE2';
  btn.style.color = '#fff';
  var panel = document.getElementById(panelId);
  if (panel) panel.hidden = false;
};

/* ── Image error handler ─────────────────── */
(function () {
  document.addEventListener('error', function (e) {
    if (e.target.tagName === 'IMG') {
      e.target.style.background = '#1D1B22';
      e.target.style.minHeight  = '60px';
    }
  }, true);
})();
