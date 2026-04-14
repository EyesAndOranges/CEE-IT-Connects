function rdTab(btn, panelId) {
  btn.closest('.rd-tabs')?.querySelectorAll('.rd-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.rd-panel').forEach(p => p.classList.remove('active'));
  document.getElementById(panelId)?.classList.add('active');
}

function rate(id, el) {
  document.querySelectorAll(`#${id} .rbtn-star`).forEach(b => b.classList.remove('sel'));
  el.classList.add('sel');
}

function saveEvaluation() {
  toast('Evaluation saved! ✓');
}

function filterStudents(q) {
  const rows = document.querySelectorAll('#all-students-tbody tr');
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const page = document.body.dataset.page;
  if (page === 'room-detail') initRoomDetail();
});

function initRoomDetail() {
  // room detail page setup
}