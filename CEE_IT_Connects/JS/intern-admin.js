function toast(msg, duration = 2800) {
  const el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  clearTimeout(el._timer);
  el._timer = setTimeout(() => el.classList.remove('show'), duration);
}

function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
  });
});

function filterTable(query, rows, matchFn) {
  const q = query.toLowerCase();
  rows.forEach(row => {
    row.style.display = matchFn(row, q) ? '' : 'none';
  });
}

function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function todayStr() { return fmtDate(new Date()); }

document.addEventListener('DOMContentLoaded', () => {
  const profileBtn   = document.getElementById('profileBtn');
  const profileDrop  = document.getElementById('profileDrop');

  if (profileBtn && profileDrop) {
    profileBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      profileDrop.classList.toggle('open');
    });
    document.addEventListener('click', function () {
      profileDrop.classList.remove('open');
    });
    profileDrop.addEventListener('click', function (e) {
      e.stopPropagation();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') profileDrop.classList.remove('open');
    });
  }
});