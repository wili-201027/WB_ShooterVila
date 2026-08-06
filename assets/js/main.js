/* ============================================================
   VR GAME WEB — main.js
   Nav toggle · Toast · Utilities globals
   ============================================================ */

/* ── Mobile nav ─────────────────────────────────────────── */
function toggleMenu() {
  document.querySelector('.nav__links').classList.toggle('open');
}

/* ── Toast notifications ────────────────────────────────── */
function showToast(msg, type) {
  type = type || 'success';
  var container = document.getElementById('toast-container');
  if (!container) return;

  var icons = { success: '✓', error: '✕', warn: '⚠' };
  var toast = document.createElement('div');
  toast.className = 'toast toast--' + type;

  var icon = document.createElement('span');
  icon.style.color = (type === 'error' ? 'var(--red)' : type === 'warn' ? 'var(--gold)' : 'var(--cyan)');
  icon.textContent = (icons[type] || '•');

  toast.appendChild(icon);
  toast.appendChild(document.createTextNode(String(msg)));

  container.appendChild(toast);
  setTimeout(function () {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = 'opacity .3s, transform .3s';
    setTimeout(function () { toast.remove(); }, 350);
  }, 3500);
}

/* ── Format score number ────────────────────────────────── */
function formatScore(n) {
  return Number(n).toLocaleString('ca-ES');
}

/* ── Relative time ──────────────────────────────────────── */
function timeAgo(dateStr) {
  var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 60)  return 'Ara mateix';
  if (diff < 3600) return Math.floor(diff / 60) + ' min';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h';
  return Math.floor(diff / 86400) + 'd';
}

/* ── Close nav on link click (mobile) ───────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.nav__links a').forEach(function (a) {
    a.addEventListener('click', function () {
      document.querySelector('.nav__links').classList.remove('open');
    });
  });
});