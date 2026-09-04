<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $roleId = $roleId ?? 0;
  $isStudentRole = in_array($roleId, [4, 5]);
  $isStaffRole   = in_array($roleId, [1, 2, 3, 7]);
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-calendar3 me-2 text-primary"></i>Kalender Akademik
    </h5>
    <?php if ($activeYear): ?>
      <div class="text-muted small"><?= esc($activeYear['year']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Tombol kembali ke hari ini -->
  <button class="btn btn-sm btn-outline-primary" id="btnToday">
    <i class="bi bi-calendar-check me-1"></i>Hari Ini
  </button>
</div>

<!-- Filter tipe event -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="eventFilters">
  <span class="small text-muted fw-semibold me-1">Tampilkan:</span>

  <label class="filter-chip" data-type="holiday">
    <input type="checkbox" checked class="d-none"> 🏖 Libur
  </label>
  <label class="filter-chip" data-type="agenda">
    <input type="checkbox" checked class="d-none"> 📅 Agenda
  </label>
  <label class="filter-chip" data-type="schedule">
    <input type="checkbox" checked class="d-none"> 📚 Jadwal
  </label>
  <label class="filter-chip" data-type="tugas">
    <input type="checkbox" checked class="d-none"> 📝 Tugas
  </label>
  <label class="filter-chip" data-type="exam">
    <input type="checkbox" checked class="d-none"> 📋 Ujian
  </label>
  <label class="filter-chip" data-type="cbt">
    <input type="checkbox" checked class="d-none"> 💻 CBT
  </label>
  <label class="filter-chip" data-type="quiz">
    <input type="checkbox" checked class="d-none"> ✏️ Kuis
  </label>
</div>

<!-- Kalender -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-2 p-md-3">
    <div id="calendar"></div>
  </div>
</div>

<!-- Modal detail event -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header py-2" id="eventModalHeader">
        <h6 class="modal-title fw-bold" id="eventModalTitle">—</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3" id="eventModalBody">—</div>
      <div class="modal-footer py-2" id="eventModalFooter" style="display:none;">
        <a href="#" id="eventModalLink" target="_blank"
           class="btn btn-sm btn-primary">
          <i class="bi bi-box-arrow-up-right me-1"></i>Buka
        </a>
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<!-- FullCalendar v6 (CDN, ~100KB gzip) -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<!-- Locale Indonesia -->
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/id.global.min.js"></script>

<style>
/* ── Filter chips ── */
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  font-size: 0.78rem;
  cursor: pointer;
  border: 1.5px solid #dee2e6;
  background: #fff;
  user-select: none;
  transition: all .15s;
  font-weight: 500;
}
.filter-chip.active {
  border-color: #0d6efd;
  background: #e7f0ff;
  color: #0d6efd;
}
.filter-chip[data-type="holiday"].active  { border-color:#dc3545; background:#fce8ea; color:#dc3545; }
.filter-chip[data-type="agenda"].active   { border-color:#0d6efd; background:#e7f0ff; color:#0d6efd; }
.filter-chip[data-type="schedule"].active { border-color:#6f42c1; background:#f0ebff; color:#6f42c1; }
.filter-chip[data-type="tugas"].active    { border-color:#fd7e14; background:#fff3e8; color:#fd7e14; }
.filter-chip[data-type="exam"].active     { border-color:#20c997; background:#e0f9f2; color:#20c997; }
.filter-chip[data-type="cbt"].active      { border-color:#e83e8c; background:#fde8f3; color:#e83e8c; }
.filter-chip[data-type="quiz"].active     { border-color:#0dcaf0; background:#e2f9fd; color:#0a7a94; }

/* ── FullCalendar overrides ── */
#calendar .fc-toolbar-title { font-size: 1rem; font-weight: 700; }
#calendar .fc-event { font-size: 0.75rem; cursor: pointer; }
#calendar .fc-event-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#calendar .fc-daygrid-day-number { font-size: 0.8rem; }
#calendar .fc-col-header-cell-cushion { font-size: 0.78rem; font-weight: 600; }

/* ── Mobile: mini list view ── */
@media (max-width: 767px) {
  #calendar .fc-toolbar { flex-direction: column; gap: 0.5rem; }
  #calendar .fc-toolbar-title { font-size: 0.9rem; }
}
</style>

<script>
(function(){
'use strict';

var EVENTS_URL = "<?= base_url('kalender/events') ?>";
var CSRF_NAME  = "<?= csrf_token() ?>";
var CSRF_HASH  = "<?= csrf_hash() ?>";

// ── State filter aktif ────────────────────────────────────────────────
var activeFilters = {
  holiday: true, agenda: true, schedule: true,
  tugas: true, exam: true, cbt: true, quiz: true
};

// Inisialisasi chip state
document.querySelectorAll('.filter-chip').forEach(function(chip){
  chip.classList.add('active');
  chip.addEventListener('click', function(){
    var type = this.dataset.type;
    activeFilters[type] = !activeFilters[type];
    this.classList.toggle('active', activeFilters[type]);
    // Re-filter events yang sudah di-cache
    calendar.refetchEvents();
  });
});

// ── Modal instance ────────────────────────────────────────────────────
var eventModal    = new bootstrap.Modal(document.getElementById('eventModal'));
var modalHeader   = document.getElementById('eventModalHeader');
var modalTitle    = document.getElementById('eventModalTitle');
var modalBody     = document.getElementById('eventModalBody');
var modalFooter   = document.getElementById('eventModalFooter');
var modalLink     = document.getElementById('eventModalLink');

var TYPE_LABELS = {
  holiday:  { label: 'Hari Libur',        icon: 'bi-sunset',           color: '#dc3545' },
  agenda:   { label: 'Agenda',             icon: 'bi-calendar-event',   color: '#0d6efd' },
  schedule: { label: 'Jadwal Pelajaran',   icon: 'bi-book',             color: '#6f42c1' },
  tugas:    { label: 'Tugas',              icon: 'bi-pencil',           color: '#fd7e14' },
  exam:     { label: 'Jadwal Ujian',       icon: 'bi-clipboard-check',  color: '#20c997' },
  cbt:      { label: 'CBT Online',         icon: 'bi-laptop',           color: '#e83e8c' },
  quiz:     { label: 'Kuis Mandiri',       icon: 'bi-pencil-square',    color: '#0dcaf0' },
};

function showEventDetail(info) {
  var ev   = info.event;
  var ext  = ev.extendedProps;
  var type = ext.type || 'agenda';
  var meta = TYPE_LABELS[type] || TYPE_LABELS['agenda'];

  // Header warna
  modalHeader.style.background      = meta.color;
  modalHeader.style.color            = '#fff';
  document.querySelector('#eventModal .btn-close').style.filter = 'invert(1)';

  // Judul
  modalTitle.textContent = ev.title.replace(/^[^\s]+ /, ''); // hapus emoji prefix

  // Badge tipe
  var badge = '<span class="badge rounded-pill me-2" style="background:' + meta.color + ';opacity:.85;font-size:.7rem;">'
            + '<i class="bi ' + meta.icon + ' me-1"></i>' + meta.label + '</span>';

  // Body
  var rows = '';

  // Tanggal & waktu
  var startStr = ev.start ? formatDT(ev.start) : '—';
  var endStr   = ev.end   ? ' – ' + formatDT(ev.end) : '';
  rows += row('bi-clock', 'Waktu', startStr + endStr);

  // Mapel / kelas
  if (ext.subject_name)  rows += row('bi-journal-text', 'Mapel',  ext.subject_name);
  if (ext.class_name)    rows += row('bi-people',        'Kelas',  ext.class_name);
  if (ext.teacher)       rows += row('bi-person',        'Guru',   ext.teacher);
  if (ext.description && ext.description.trim())
    rows += row('bi-card-text', 'Keterangan', ext.description);

  // Status khusus tugas
  if (type === 'tugas' && ext.submitted !== undefined) {
    var statusBadge = ext.submitted
      ? '<span class="badge bg-success">✓ Sudah Dikumpulkan</span>'
      : '<span class="badge bg-warning text-dark">Belum Dikumpulkan</span>';
    rows += row('bi-check2-circle', 'Status', statusBadge);
  }

  modalBody.innerHTML = '<div class="mb-2">' + badge + '</div>'
    + '<div class="d-flex flex-column gap-2 small">' + rows + '</div>';

  // Footer dengan link
  if (ev.url) {
    modalFooter.style.display = '';
    modalLink.href = ev.url;
  } else {
    modalFooter.style.display = 'none';
  }

  eventModal.show();
  info.jsEvent.preventDefault(); // mencegah navigasi default FullCalendar
}

function row(icon, label, value) {
  return '<div class="d-flex gap-2 align-items-start">'
    + '<i class="bi ' + icon + ' text-muted flex-shrink-0 mt-1"></i>'
    + '<div><span class="text-muted">' + label + ':</span> ' + value + '</div>'
    + '</div>';
}

function formatDT(dt) {
  if (!dt) return '—';
  var d = new Date(dt);
  var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
  var date = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  if (d.getHours() === 0 && d.getMinutes() === 0) return date;
  return date + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}

// ── FullCalendar init ─────────────────────────────────────────────────
var calEl = document.getElementById('calendar');

var calendar = new FullCalendar.Calendar(calEl, {
  locale: 'id',
  initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
  headerToolbar: {
    left:   'prev,next',
    center: 'title',
    right:  'dayGridMonth,timeGridWeek,listMonth'
  },
  buttonText: {
    dayGridMonth: 'Bulan',
    timeGridWeek: 'Minggu',
    listMonth:    'Daftar',
  },
  height: 'auto',
  nowIndicator: true,
  dayMaxEvents: 4,             // "+N more" setelah 4 event
  eventMaxStack: 3,
  weekNumbers: false,
  firstDay: 1,                 // Mulai dari Senin

  // Sumber event dari API
  events: function(fetchInfo, successCb, failureCb) {
    fetch(EVENTS_URL
      + '?start=' + fetchInfo.startStr.slice(0,10)
      + '&end='   + fetchInfo.endStr.slice(0,10)
      + '&_=' + Date.now()  // cache-bust
    )
    .then(function(r){ return r.json(); })
    .then(function(data){
      // Filter sesuai checkbox aktif
      var filtered = data.filter(function(ev){
        return activeFilters[ev.type] !== false;
      });
      successCb(filtered);
    })
    .catch(function(err){
      console.error('[Kalender] Gagal load events:', err);
      failureCb(err);
    });
  },

  // Warnai hari libur di cell kalender
  dayCellDidMount: function(info) {
    // Tandai weekend
    var dow = info.date.getDay();
    if (dow === 0 || dow === 6) {
      info.el.style.background = 'rgba(220,53,69,0.04)';
    }
  },

  // Klik event → modal detail
  eventClick: function(info) {
    showEventDetail(info);
  },

  // Tooltip saat hover (desktop)
  eventDidMount: function(info) {
    var ext = info.event.extendedProps;
    var tooltip = info.event.title;
    if (ext.description) tooltip += '\n' + ext.description;
    info.el.setAttribute('title', tooltip);
  },

  // Loading indicator
  loading: function(isLoading) {
    document.getElementById('calendar').style.opacity = isLoading ? '0.6' : '1';
  },

  // Style event sesuai tipe
  eventDidMount: function(info) {
    var type = info.event.extendedProps.type;
    // Tambah border-left tebal per tipe
    info.el.style.borderLeft = '3px solid ' + (info.event.backgroundColor || '#ccc');
    info.el.style.borderRadius = '3px';
  },
});

calendar.render();

// Tombol "Hari Ini"
document.getElementById('btnToday').addEventListener('click', function(){
  calendar.today();
});

// Responsive: ganti view saat resize
var resizeTimer;
window.addEventListener('resize', function(){
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function(){
    var isMobile = window.innerWidth < 768;
    var cur = calendar.view.type;
    if (isMobile && cur === 'dayGridMonth') {
      calendar.changeView('listMonth');
    } else if (!isMobile && cur === 'listMonth') {
      calendar.changeView('dayGridMonth');
    }
  }, 200);
});

})();
</script>
<?= $this->endSection() ?>
