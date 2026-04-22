/* ===== INTERNSHIP MANAGEMENT SYSTEM — MAIN JS ===== */

// ── Alert dismiss ────────────────────────────────────────────
document.querySelectorAll('.alert-close').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.alert').remove());
});

// Auto-dismiss flash alerts after 5s
const flash = document.querySelector('.alert[data-auto-dismiss]');
if (flash) setTimeout(() => flash.style.opacity = 0, 5000);

// ── Sidebar toggle (mobile) ──────────────────────────────────
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar       = document.querySelector('.sidebar');
const overlay       = document.getElementById('sidebarOverlay');

function openSidebar()  { sidebar?.classList.add('open');  overlay?.classList.add('open'); }
function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); }

sidebarToggle?.addEventListener('click', openSidebar);
overlay?.addEventListener('click', closeSidebar);

// ── Notification dropdown ────────────────────────────────────
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');

notifBtn?.addEventListener('click', e => {
  e.stopPropagation();
  notifDropdown?.classList.toggle('open');
});
document.addEventListener('click', () => notifDropdown?.classList.remove('open'));

// Mark notifications read via fetch
document.querySelectorAll('.notif-item[data-id]').forEach(item => {
  item.addEventListener('click', () => {
    const id = item.dataset.id;
    fetch('/internship-system/api/notifications.php?action=read&id=' + id)
      .then(() => item.classList.remove('unread'));
  });
});

// ── Modal helpers ────────────────────────────────────────────
window.openModal  = id => document.getElementById(id)?.classList.add('open');
window.closeModal = id => document.getElementById(id)?.classList.remove('open');

document.querySelectorAll('[data-modal-open]').forEach(btn =>
  btn.addEventListener('click', () => openModal(btn.dataset.modalOpen))
);
document.querySelectorAll('[data-modal-close]').forEach(btn =>
  btn.addEventListener('click', () => closeModal(btn.dataset.modalClose))
);
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ── File upload preview ──────────────────────────────────────
document.querySelectorAll('.upload-zone input[type="file"]').forEach(input => {
  input.addEventListener('change', () => {
    const zone = input.closest('.upload-zone');
    const nameEl = zone?.querySelector('.upload-file-name');
    if (nameEl && input.files[0]) nameEl.textContent = '📎 ' + input.files[0].name;
  });
  const zone = input.closest('.upload-zone');
  zone?.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone?.addEventListener('dragleave', ()  => zone.classList.remove('drag-over'));
  zone?.addEventListener('drop',      e => { e.preventDefault(); zone.classList.remove('drag-over'); input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); });
});

// ── Confirm delete ────────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
});

// ── Search / filter table ─────────────────────────────────────
const tableSearch = document.getElementById('tableSearch');
if (tableSearch) {
  tableSearch.addEventListener('input', () => {
    const q = tableSearch.value.toLowerCase();
    document.querySelectorAll('.searchable-table tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Progress animate on load ──────────────────────────────────
document.querySelectorAll('.progress-fill[data-pct]').forEach(bar => {
  bar.style.width = '0%';
  requestAnimationFrame(() => {
    setTimeout(() => bar.style.width = bar.dataset.pct + '%', 80);
  });
});

// ── Form validation helpers ───────────────────────────────────
function showFieldError(fieldId, msg) {
  const field = document.getElementById(fieldId);
  const err   = document.createElement('p');
  err.className = 'form-error'; err.textContent = msg;
  field?.parentNode?.querySelector('.form-error')?.remove();
  field?.after(err);
  field?.classList.add('is-invalid');
}
function clearFieldError(fieldId) {
  const field = document.getElementById(fieldId);
  field?.classList.remove('is-invalid');
  field?.parentNode?.querySelector('.form-error')?.remove();
}

// Live email validation
document.querySelectorAll('input[type="email"]').forEach(input => {
  input.addEventListener('blur', () => {
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
    if (!ok && input.value) showFieldError(input.id, 'Please enter a valid email.');
    else clearFieldError(input.id);
  });
});

// Password strength meter
const pwInput = document.getElementById('password');
const pwMeter = document.getElementById('pwMeter');
if (pwInput && pwMeter) {
  pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const pct = (score / 4) * 100;
    pwMeter.style.width = pct + '%';
    pwMeter.style.background = ['#e53e3e','#dd6b20','#d69e2e','#38a169'][score - 1] || '#e2e8f0';
  });
}

// ── AJAX delete user (admin) ──────────────────────────────────
document.querySelectorAll('.btn-delete-user').forEach(btn => {
  btn.addEventListener('click', () => {
    if (!confirm('Delete this user? This cannot be undone.')) return;
    const row = btn.closest('tr');
    fetch('/internship-system/admin/users.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'action=delete&id=' + btn.dataset.id + '&_token=' + btn.dataset.token
    }).then(r => r.json()).then(data => {
      if (data.ok) row?.remove();
      else alert(data.error || 'Delete failed');
    });
  });
});

// ── Charts placeholder — used by dashboard pages ─────────────
window.IMS = window.IMS || {};
IMS.initDonut = function(canvasId, labels, data, colors) {
  const ctx = document.getElementById(canvasId);
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
    options: {
      cutout: '68%',
      plugins: {
        legend: { position: 'bottom', labels: { font: { family: 'DM Sans', size: 12 }, padding: 14 } }
      }
    }
  });
};
IMS.initBar = function(canvasId, labels, datasets) {
  const ctx = document.getElementById(canvasId);
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: datasets.length > 1 } },
      scales: {
        y: { beginAtZero: true, ticks: { font: { family: 'DM Sans' } }, grid: { color: '#eef0f5' } },
        x: { ticks: { font: { family: 'DM Sans' } }, grid: { display: false } }
      }
    }
  });
};
