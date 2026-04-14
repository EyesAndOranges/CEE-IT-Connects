// script.js — global utilities, runs on every page

/* ── CONSTANTS ── */
const GRAD = {
  purple: 'linear-gradient(135deg,#e040fb,#9c27b0)',
  teal:   'linear-gradient(135deg,#0891b2,#0e7490)',
  brand:  'linear-gradient(135deg,#FF6B2C,#b83e0a)',
  green:  'linear-gradient(135deg,#16a34a,#166534)',
  pink:   'linear-gradient(135deg,#db2777,#9d174d)',
};

const STATUS_BADGE = {
  'On Track':  'badge-green',
  'Behind':    'badge-amber',
  'At Risk':   'badge-red',
  'Completed': 'badge-blue',
};

/* ── TOAST ── */
function toast(msg, duration = 2800) {
  const el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  clearTimeout(el._timer);
  el._timer = setTimeout(() => el.classList.remove('show'), duration);
}

/* ── MODAL HELPERS ── */
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
  });
});

/* ── SEARCH / FILTER ── */
function filterTable(query, rows, matchFn) {
  const q = query.toLowerCase();
  rows.forEach(row => {
    row.style.display = matchFn(row, q) ? '' : 'none';
  });
}

/* ── DATE UTILS ── */
function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function todayStr() { return fmtDate(new Date()); }

/* ── NAVBAR INTERACTIONS ── */
document.addEventListener('DOMContentLoaded', () => {
  const notifBtn      = document.getElementById('notifBtn');
  const notifOverlay  = document.getElementById('notifOverlay');
  const notifBackdrop = document.getElementById('notifBackdrop');
  const profileBtn    = document.getElementById('profileBtn');
  const profileDrop   = document.getElementById('profileDrop');

  if (!notifBtn || !profileBtn) return;

  notifBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    profileDrop.classList.remove('open');
    notifOverlay.classList.toggle('open');
  });

  notifBackdrop.addEventListener('click', function () {
    notifOverlay.classList.remove('open');
  });

  profileBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    notifOverlay.classList.remove('open');
    profileDrop.classList.toggle('open');
  });

  document.addEventListener('click', function () {
    profileDrop.classList.remove('open');
  });

  profileDrop.addEventListener('click', function (e) {
    e.stopPropagation();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      notifOverlay.classList.remove('open');
      profileDrop.classList.remove('open');
    }
  });
});