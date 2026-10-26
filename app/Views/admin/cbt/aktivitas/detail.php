<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
// Hitung ringkasan statistik untuk filter cepat dan proctor overview
$totalStudents = is_array($sessions) ? count($sessions) : 0;
$activeCount = 0;
$finishedCount = 0;
$timeoutCount = 0;
$violationStudentsCount = 0;
$totalViolations = 0;

foreach ($sessions as $s) {
  $st = $s['status'] ?? '';
  if ($st === 'active') {
    $activeCount++;
  } elseif ($st === 'finished') {
    $finishedCount++;
  } else {
    $timeoutCount++;
  }

  $vCount = (int) ($s['violation_count'] ?? 0);
  if ($vCount > 0) {
    $violationStudentsCount++;
    $totalViolations += $vCount;
  }
}

// Fallback aman jika controller belum mengirimkan list kelas / ruang
$availableClasses = $availableClasses ?? [];
$availableRooms = $availableRooms ?? [];
if (empty($availableClasses) && !empty($sessions)) {
  foreach ($sessions as $s) {
    if (!empty($s['class_name']) && !in_array($s['class_name'], $availableClasses)) {
      $availableClasses[] = $s['class_name'];
    }
  }
  sort($availableClasses);
}
if (empty($availableRooms) && !empty($sessions)) {
  foreach ($sessions as $s) {
    if (!empty($s['room']) && !in_array($s['room'], $availableRooms)) {
      $availableRooms[] = $s['room'];
    }
  }
  sort($availableRooms);
}
?>

<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --success-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
    --warning-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    --danger-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    --info-gradient: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
  }

  /* Page Header */
  .page-header {
    background: var(--primary-gradient);
    color: white;
    padding: 1.25rem 1.5rem;
    border-radius: 16px;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25);
    position: relative;
    overflow: hidden;
    width: 100%;
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
    z-index: 1;
  }

  .page-header-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }

  .page-header .icon-wrapper {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    flex-shrink: 0;
  }

  .page-header h4 {
    margin: 0 0 0.15rem 0;
    font-weight: 700;
    font-size: 1.25rem;
    letter-spacing: -0.01em;
  }

  .page-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.85rem;
  }

  .page-header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .btn-header {
    background: rgba(255, 255, 255, 0.18);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(6px);
    padding: 0.5rem 0.85rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
    text-decoration: none;
  }

  .btn-header:hover {
    background: rgba(255, 255, 255, 0.28);
    color: white;
    transform: translateY(-1px);
  }

  /* Info Card */
  .info-bar {
    background: white;
    border-radius: 14px;
    padding: 0.85rem 1.15rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    width: 100%;
  }

  .info-bar-details {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
  }

  .info-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 8px;
    background: #f1f5f9;
    font-size: 0.82rem;
    color: #334155;
    font-weight: 500;
  }

  .info-pill strong {
    color: #0f172a;
  }

  /* Auto-refresh control pill */
  .auto-refresh-box {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 30px;
    padding: 0.25rem 0.75rem;
    font-size: 0.82rem;
    color: #475569;
  }

  .auto-refresh-box select {
    border: none;
    background: transparent;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
    padding: 0.15rem 0.25rem;
  }

  .refresh-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #10b981;
    transition: all 0.2s ease;
  }

  .refresh-indicator.pulse-refresh {
    background-color: #3b82f6;
    box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.45);
    transform: scale(1.2);
  }

  .refresh-indicator.paused {
    background-color: #94a3b8;
  }

  /* Stat Filters Ribbon */
  .stats-filter-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 0.65rem;
    margin-bottom: 1rem;
    width: 100%;
  }

  .stat-filter-card {
    background: white;
    border-radius: 12px;
    padding: 0.75rem 0.9rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    user-select: none;
  }

  .stat-filter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
    border-color: #cbd5e1;
  }

  .stat-filter-card.active {
    border-color: #4f46e5;
    background: #eef2ff;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
  }

  .stat-filter-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 0.2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .stat-filter-card.active .stat-filter-title {
    color: #4338ca;
  }

  .stat-filter-val {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0f172a;
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
  }

  .pulse-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulse-green 1.8s infinite;
  }

  @keyframes pulse-green {
    0% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
      transform: scale(1);
      box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
    }
    100% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
  }

  /* Control & Search Bar */
  .control-card {
    background: white;
    border-radius: 14px;
    padding: 0.85rem 1.15rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
    width: 100%;
  }

  .search-wrapper {
    position: relative;
  }

  .search-wrapper i.fa-search {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
  }

  .search-input-modern {
    padding-left: 2.5rem !important;
    padding-right: 2.25rem !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    height: 42px !important;
    font-size: 0.88rem !important;
    transition: all 0.2s !important;
  }

  .search-input-modern:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
  }

  .btn-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    padding: 4px 8px;
    cursor: pointer;
    display: none;
  }

  .view-toggle-btn {
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.82rem;
    padding: 0.45rem 0.75rem;
  }

  /* ========================================================
     COMPACT STUDENT CARD WITH EXPANDABLE/DROPDOWN ACTIONS
     ======================================================== */
  .student-cards-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
  }

  .student-compact-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    overflow: hidden;
  }

  .student-compact-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  }

  .student-compact-card.has-violation {
    border-left: 4px solid #ef4444 !important;
  }

  .student-compact-card.is-active {
    border-left: 4px solid #10b981 !important;
  }

  .student-compact-card.is-finished {
    border-left: 4px solid #3b82f6 !important;
  }

  .student-compact-card.is-timeout {
    border-left: 4px solid #94a3b8 !important;
  }

  /* Compact Card Main Row */
  .card-main-row {
    padding: 0.75rem 0.9rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    cursor: pointer;
    user-select: none;
  }

  .card-main-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    flex: 1;
  }

  .card-student-details {
    min-width: 0;
    flex: 1;
  }

  .student-name-text {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.15rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .student-sub-info {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    font-size: 0.75rem;
  }

  .badge-tag {
    font-size: 0.72rem;
    padding: 0.15rem 0.45rem;
    border-radius: 5px;
    font-weight: 600;
  }

  .badge-username {
    background: #f1f5f9;
    color: #334155;
    font-family: monospace;
  }

  .badge-class {
    background: #e0e7ff;
    color: #3730a3;
  }

  .badge-room {
    background: #fef3c7;
    color: #92400e;
  }

  .card-main-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  .btn-toggle-actions {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.35rem 0.65rem;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.15s ease;
  }

  .btn-toggle-actions:hover,
  .student-compact-card.expanded .btn-toggle-actions {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
  }

  .toggle-chevron {
    transition: transform 0.2s ease;
  }

  .student-compact-card.expanded .toggle-chevron {
    transform: rotate(180deg);
  }

  /* Expandable Actions Section */
  .card-expanded-body {
    display: none;
    border-top: 1px dashed #e2e8f0;
    background: #f8fafc;
    padding: 0.85rem 0.9rem;
  }

  .student-compact-card.expanded .card-expanded-body {
    display: block;
    animation: fadeInDown 0.2s ease;
  }

  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-5px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .expanded-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 0.5rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
  }

  .expanded-meta-item {
    display: flex;
    flex-direction: column;
  }

  .expanded-meta-label {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
  }

  .expanded-meta-value {
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.3rem;
  }

  /* Action Buttons Grid in Dropdown */
  .expanded-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.45rem;
  }

  @media (min-width: 600px) {
    .expanded-actions-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  .btn-touch-action {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    transition: all 0.15s ease;
    user-select: none;
    padding: 0.35rem 0.5rem;
    text-align: center;
  }

  .btn-touch-action:active {
    transform: scale(0.97);
  }

  .btn-touch-unlock {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
  }

  .btn-touch-unlock:hover {
    background: #fef3c7;
    color: #92400e;
  }

  .btn-touch-time {
    background: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
  }

  .btn-touch-time:hover {
    background: #e0f2fe;
    color: #0284c7;
  }

  .btn-touch-reset {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
  }

  .btn-touch-reset:hover {
    background: #fee2e2;
    color: #991b1b;
  }

  .btn-touch-finish {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
  }

  .btn-touch-finish:hover {
    background: #d1fae5;
    color: #065f46;
  }

  .btn-touch-finish:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    border-color: #e2e8f0;
    cursor: not-allowed;
  }

  .btn-touch-answer {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #cbd5e1;
  }

  .btn-touch-answer:hover {
    background: #e2e8f0;
    color: #1e293b;
  }

  /* Floating Bottom Action Bar */
  .floating-bottom-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(120px);
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 50px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    display: flex;
    align-items: center;
    gap: 1rem;
    z-index: 1040;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    max-width: 90vw;
  }

  .floating-bottom-bar.show {
    transform: translateX(-50%) translateY(0);
  }

  .floating-bar-text {
    font-size: 0.9rem;
    font-weight: 600;
  }

  /* Table View Enhancements */
  .table-card {
    background: white;
    border-radius: 14px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    width: 100%;
  }

  .table-modern thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #475569;
    padding: 0.85rem 0.6rem;
    vertical-align: middle;
  }

  .table-modern tbody td {
    padding: 0.75rem 0.6rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
  }

  .table-modern tbody tr:hover {
    background-color: #f8fafc;
  }

  .btn-table-action {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: none;
    font-size: 0.85rem;
    transition: all 0.15s ease;
  }

  .btn-table-action:hover {
    transform: translateY(-1px);
  }

  /* Responsive Tweaks */
  @media (max-width: 768px) {
    .page-header {
      padding: 1.15rem;
      border-radius: 12px;
    }

    .page-header-content {
      flex-direction: column;
      align-items: stretch;
      gap: 0.85rem;
    }

    .page-header-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
    }

    .page-header-actions .btn-header {
      justify-content: center;
      padding: 0.55rem;
      font-size: 0.82rem;
    }

    .stats-filter-container {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.5rem;
    }

    .stat-filter-card {
      padding: 0.65rem;
    }

    .stat-filter-val {
      font-size: 1.25rem;
    }

    .info-bar {
      flex-direction: column;
      align-items: flex-start;
      padding: 0.85rem;
    }

    .auto-refresh-box {
      width: 100%;
      justify-content: space-between;
    }

    .card-main-row {
      padding: 0.65rem 0.75rem;
    }
  }
</style>

<div class="w-100">
  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-content">
      <div class="page-header-left">
        <div class="icon-wrapper">
          <i class="fas fa-desktop"></i>
        </div>
        <div>
          <h4>Monitoring Ujian CBT</h4>
          <p class="mb-0">Pantau aktivitas peserta & kelola sesi secara real-time</p>
        </div>
      </div>
      <div class="page-header-actions">
        <!-- Tombol Selesaikan Belum Selesai -->
        <button class="btn btn-header" id="btnBelumSelesai" title="Selesaikan Massal Siswa yang Belum Selesai">
          <i class="fas fa-check-double text-warning"></i>
          <span><span id="countBelum"><?= $count_belum ?? 0 ?></span> Belum Selesai</span>
        </button>

        <!-- Tombol Reset Massal -->
        <button id="btnResetMassal" class="btn btn-header" title="Reset Siswa">
          <i class="fas fa-redo-alt text-danger"></i>
          <span>Reset Masal</span>
        </button>

        <!-- Tombol Refresh Manual -->
        <button onclick="location.reload()" class="btn btn-header" title="Segarkan Data Sekarang">
          <i class="fas fa-sync-alt"></i>
          <span>Refresh</span>
        </button>

        <!-- Tombol Kembali -->
        <a href="<?= site_url('admin/cbt/aktivitas') ?>" class="btn btn-header">
          <i class="fas fa-arrow-left"></i>
          <span>Kembali</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Info Bar & Auto Refresh Toggle -->
  <div class="info-bar">
    <div class="info-bar-details">
      <div class="info-pill">
        <i class="fas fa-barcode text-primary"></i>
        Kode: <strong><?= esc($test['code']) ?></strong>
      </div>
      <div class="info-pill">
        <i class="fas fa-book text-indigo"></i>
        <strong><?= esc($test['subject_name']) ?></strong>
      </div>
      <div class="info-pill d-none d-sm-inline-flex">
        <i class="fas fa-hourglass-half text-warning"></i>
        Durasi: <strong><?= esc($test['duration'] ?? 0) ?> Menit</strong>
      </div>
    </div>

    <!-- Auto-Refresh Control -->
    <div class="auto-refresh-box">
      <span class="refresh-indicator" id="autoRefreshIndicator" title="Status Auto-Refresh"></span>
      <span id="autoRefreshCountdownText">Auto-refresh:</span>
      <select id="autoRefreshSelect" aria-label="Pilih Interval Auto Refresh">
        <option value="0">Nonaktif</option>
        <option value="30">30 Detik</option>
        <option value="60" selected>60 Detik</option>
        <option value="120">2 Menit</option>
      </select>
      <button class="btn btn-link btn-sm p-0 text-muted" id="btnToggleAutoRefresh" title="Jeda/Lanjutkan Auto Refresh">
        <i class="fas fa-pause" id="iconAutoRefreshPause"></i>
      </button>
    </div>
  </div>

  <!-- Quick Filter Cards / Stats Ribbon -->
  <div class="stats-filter-container">
    <!-- Filter Semua -->
    <div class="stat-filter-card active" data-filter="all" title="Klik untuk menampilkan semua peserta">
      <div class="stat-filter-title">
        <span>Semua Siswa</span>
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-filter-val" id="statValTotal">
        <?= $totalStudents ?>
      </div>
    </div>

    <!-- Filter Aktif / Sedang Ujian -->
    <div class="stat-filter-card" data-filter="active" title="Klik untuk filter siswa yang sedang ujian">
      <div class="stat-filter-title text-success">
        <span>Sedang Ujian</span>
        <span class="pulse-dot"></span>
      </div>
      <div class="stat-filter-val text-success" id="statValActive">
        <?= $activeCount ?>
      </div>
    </div>

    <!-- Filter Selesai -->
    <div class="stat-filter-card" data-filter="finished" title="Klik untuk filter siswa yang sudah selesai">
      <div class="stat-filter-title text-primary">
        <span>Selesai</span>
        <i class="fas fa-check-circle text-primary"></i>
      </div>
      <div class="stat-filter-val text-primary" id="statValFinished">
        <?= $finishedCount ?>
      </div>
    </div>

    <!-- Filter Waktu Habis / Belum -->
    <div class="stat-filter-card" data-filter="timeout" title="Klik untuk filter siswa waktu habis / belum mulai">
      <div class="stat-filter-title text-secondary">
        <span>Waktu Habis / Belum</span>
        <i class="fas fa-clock text-secondary"></i>
      </div>
      <div class="stat-filter-val text-secondary" id="statValTimeout">
        <?= $timeoutCount ?>
      </div>
    </div>

    <!-- Filter Pelanggaran -->
    <div class="stat-filter-card <?= $violationStudentsCount > 0 ? 'border-danger' : '' ?>" data-filter="violation" title="Klik untuk filter siswa dengan pelanggaran">
      <div class="stat-filter-title text-danger">
        <span>Pelanggaran</span>
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
      <div class="stat-filter-val text-danger" id="statValViolation">
        <?= $violationStudentsCount ?>
        <?php if ($totalViolations > 0): ?>
          <small class="fs-6 fw-normal text-muted">(<?= $totalViolations ?>x)</small>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="control-card">
    <div class="row g-2 align-items-center">
      <!-- Search Input -->
      <div class="col-12 col-md-4">
        <div class="search-wrapper">
          <i class="fas fa-search"></i>
          <input type="text" id="liveSearchInput" class="form-control search-input-modern"
            placeholder="Cari nama, no ujian, kelas..." autocomplete="off">
          <button type="button" class="btn-clear-search" id="btnClearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Filter Kelas -->
      <div class="col-6 col-md-2">
        <select id="filterKelas" class="form-select" style="height: 42px; border-radius: 10px; font-size: 0.85rem;">
          <option value="">Semua Kelas</option>
          <?php foreach ($availableClasses as $kelas): ?>
            <option value="<?= esc($kelas) ?>"><?= esc($kelas) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Filter Ruang -->
      <div class="col-6 col-md-2">
        <select id="filterRuang" class="form-select" style="height: 42px; border-radius: 10px; font-size: 0.85rem;">
          <option value="">Semua Ruang</option>
          <?php foreach ($availableRooms as $ruang): ?>
            <option value="<?= esc($ruang) ?>"><?= esc($ruang) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Actions: Pilih Semua, Buka Semua, Switcher -->
      <div class="col-12 col-md-4 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
          <div class="form-check d-flex align-items-center gap-1 mb-0">
            <input class="form-check-input" type="checkbox" id="checkAllMaster" style="width: 18px; height: 18px;">
            <label class="form-check-label fw-semibold small" for="checkAllMaster">Pilih</label>
          </div>

          <!-- Tombol Buka/Tutup Semua Dropdown Aksi -->
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleAllCards" title="Buka/Tutup semua menu aksi siswa">
            <i class="fas fa-expand-alt me-1"></i> <span id="textToggleAllCards">Aksi</span>
          </button>
        </div>

        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-primary view-toggle-btn active" id="btnViewCards" title="Tampilan Kartu Ringkas (Optimal untuk HP)">
            <i class="fas fa-th-large"></i> <span class="d-none d-sm-inline">Kartu</span>
          </button>
          <button type="button" class="btn btn-outline-primary view-toggle-btn" id="btnViewTable" title="Tampilan Tabel (Desktop)">
            <i class="fas fa-table"></i> <span class="d-none d-sm-inline">Tabel</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTAINER 1: COMPACT CARD VIEW (Ultra-Compact on Mobile with Action Dropdown) -->
  <div id="viewCardsWrapper">
    <div class="student-cards-list" id="studentCardsContainer">
      <?php if (empty($sessions)): ?>
        <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
          <i class="fas fa-user-slash fa-3x mb-3 text-secondary"></i>
          <h5>Belum ada data peserta</h5>
          <p class="mb-0">Tidak ada siswa yang terdaftar pada ujian ini.</p>
        </div>
      <?php else: ?>
        <?php foreach ($sessions as $i => $s): ?>
          <?php
          $status = $s['status'] ?? 'timeout';
          $violations = (int) ($s['violation_count'] ?? 0);
          $remMin = (int) ($s['remaining_minutes'] ?? 0);
          $isStarted = !empty($s['started_at']);
          $startTimeFormatted = $isStarted ? date('H:i:s', $s['started_at']) : 'Belum Mulai';
          $score = $s['display_score'] ?? 0;

          // Status class helper
          $statusClass = 'timeout';
          if ($status === 'active') {
            $statusClass = 'active';
          } elseif ($status === 'finished') {
            $statusClass = 'finished';
          }
          ?>
          <div class="student-compact-card <?= $violations > 0 ? 'has-violation' : '' ?> is-<?= $statusClass ?>"
            id="card-student-<?= $s['id'] ?>"
            data-id="<?= $s['id'] ?>"
            data-student-id="<?= $s['student_id'] ?>"
            data-name="<?= esc(strtolower($s['student_name'])) ?>"
            data-username="<?= esc(strtolower($s['username'])) ?>"
            data-class="<?= esc($s['class_name'] ?? '') ?>"
            data-room="<?= esc($s['room'] ?? '') ?>"
            data-status="<?= $status ?>"
            data-has-violation="<?= $violations > 0 ? '1' : '0' ?>">

            <!-- Compact Main Row: Always visible (~50px height) -->
            <div class="card-main-row" onclick="toggleCardAction('<?= $s['id'] ?>', event)">
              <div class="card-main-left">
                <!-- Checkbox -->
                <div class="card-check" onclick="event.stopPropagation()">
                  <input type="checkbox" class="checkStudent form-check-input" value="<?= $s['id'] ?>"
                    aria-label="Pilih siswa <?= esc($s['student_name']) ?>">
                </div>

                <!-- Nama Siswa & Data Dasar -->
                <div class="card-student-details">
                  <h6 class="student-name-text" title="<?= esc($s['student_name']) ?>">
                    <?= esc($s['student_name']) ?>
                  </h6>
                  <div class="student-sub-info">
                    <span class="badge-tag badge-username" title="Nomor Ujian / Username">
                      <?= esc($s['username']) ?>
                    </span>
                    <span class="badge-tag badge-class" title="Kelas">
                      <?= esc($s['class_name'] ?? '-') ?>
                    </span>
                    <span class="badge-tag badge-room" title="Ruang Ujian">
                      <?= esc($s['room'] ?? '-') ?>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Status Ringkas & Tombol Dropdown Aksi -->
              <div class="card-main-right">
                <!-- Sisa Waktu Badge Ringkas -->
                <?php if ($status === 'finished'): ?>
                  <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;" title="Ujian Selesai">
                    <i class="fas fa-check-circle me-1 text-success"></i>Selesai
                  </span>
                <?php elseif ($status === 'expired'): ?>
                  <span class="badge bg-danger text-white px-2 py-1" style="font-size: 0.75rem;" title="Waktu Habis">
                    <i class="fas fa-hourglass-end me-1"></i>Habis
                  </span>
                <?php else: ?>
                  <?php 
                    $remSec = (int)($s['remaining_seconds'] ?? ($remMin * 60));
                    $badgeCls = $remSec <= 300 ? 'bg-danger text-white' : ($remSec <= 600 ? 'bg-warning text-dark' : 'bg-light text-dark border');
                  ?>
                  <span class="badge <?= $badgeCls ?> px-2 py-1 student-countdown" data-seconds="<?= $remSec ?>" data-status="active" style="font-size: 0.75rem; font-variant-numeric: tabular-nums;" title="Sisa Waktu">
                    <i class="fas fa-stopwatch me-1 <?= $remSec <= 300 ? 'text-white' : 'text-primary' ?>"></i><span class="timer-display"><?= sprintf('%02d:%02d', floor($remSec / 60), $remSec % 60) ?></span>
                  </span>
                <?php endif; ?>

                <!-- Pelanggaran Badge (Hanya tampil jika ada) -->
                <?php if ($violations > 0): ?>
                  <span class="badge bg-danger px-2 py-1" style="font-size: 0.75rem;" title="<?= $violations ?>x Pelanggaran">
                    <i class="fas fa-shield-alt me-1"></i><?= $violations ?>x
                  </span>
                <?php endif; ?>

                <!-- Status Pill -->
                <?php if ($status === 'active'): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.75rem;">
                    <span class="pulse-dot me-1"></span>Aktif
                  </span>
                <?php elseif ($status === 'finished'): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.75rem;">
                    <i class="fas fa-check-circle me-1"></i><?= $score ?>
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.75rem;">
                    Habis
                  </span>
                <?php endif; ?>

                <!-- Tombol Dropdown / Expand Aksi -->
                <button type="button" class="btn-toggle-actions" aria-label="Buka Aksi Siswa">
                  <span>Aksi</span>
                  <i class="fas fa-chevron-down toggle-chevron"></i>
                </button>
              </div>
            </div>

            <!-- Expandable Action & Meta Body (Hidden by default, smooth dropdown) -->
            <div class="card-expanded-body">
              <!-- Grid Detail Tambahan -->
              <div class="expanded-meta-grid">
                <div class="expanded-meta-item">
                  <span class="expanded-meta-label">Mulai Ujian</span>
                  <span class="expanded-meta-value text-muted">
                    <i class="fas fa-clock text-muted"></i> <?= $startTimeFormatted ?>
                  </span>
                </div>
                <div class="expanded-meta-item">
                  <span class="expanded-meta-label">Pelanggaran</span>
                  <span class="expanded-meta-value <?= $violations > 0 ? 'text-danger' : 'text-muted' ?>">
                    <i class="fas fa-shield-alt <?= $violations > 0 ? 'text-danger' : 'text-muted' ?>"></i>
                    <?= $violations > 0 ? $violations . 'x Pelanggaran' : '0 (Bersih)' ?>
                  </span>
                </div>
                <div class="expanded-meta-item">
                  <span class="expanded-meta-label">Nilai Akhir</span>
                  <span class="expanded-meta-value <?= $status === 'finished' ? 'text-success' : 'text-muted' ?>">
                    <i class="fas fa-star <?= $status === 'finished' ? 'text-warning' : 'text-muted' ?>"></i>
                    <?= $status === 'finished' ? $score : 'Belum Selesai' ?>
                  </span>
                </div>
              </div>

              <!-- Tombol Aksi Lengkap & Ramah Sentuh -->
              <div class="expanded-actions-grid">
                <!-- 1. BUKA LOGIN -->
                <button type="button" class="btn btn-touch-action btn-touch-unlock btn-reset-login"
                  data-student-id="<?= $s['student_id'] ?>" data-name="<?= esc($s['student_name']) ?>"
                  title="Buka akses login kembali tanpa menghapus jawaban yang sudah dikerjakan">
                  <i class="fas fa-unlock-alt"></i>
                  <span>Buka Login</span>
                </button>

                <!-- 2. TAMBAH WAKTU -->
                <button type="button" class="btn btn-touch-action btn-touch-time btn-tambah-waktu"
                  data-id="<?= $s['id'] ?>"
                  title="Tambah menit ujian untuk siswa ini">
                  <i class="fas fa-plus-circle"></i>
                  <span>+ Waktu</span>
                </button>

                <!-- 3. PAKSA SELESAI -->
                <?php if ($status === 'active'): ?>
                  <button type="button" class="btn btn-touch-action btn-touch-finish btn-force-finish"
                    data-id="<?= $s['id'] ?>"
                    title="Paksa selesaikan ujian siswa ini">
                    <i class="fas fa-check-circle"></i>
                    <span>Selesaikan</span>
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-touch-action btn-touch-finish" disabled title="Sudah selesai">
                    <i class="fas fa-check"></i>
                    <span>Selesai</span>
                  </button>
                <?php endif; ?>

                <!-- 4. RESET UJIAN -->
                <button type="button" class="btn btn-touch-action btn-touch-reset btn-reset"
                  data-id="<?= $s['id'] ?>"
                  title="Reset ujian dari awal (jawaban akan dihapus)">
                  <i class="fas fa-redo"></i>
                  <span>Reset Ujian</span>
                </button>

                <!-- 5. DETAIL JAWABAN -->
                <button type="button" class="btn btn-touch-action btn-touch-answer btn-detail-jawaban"
                  data-id="<?= $s['id'] ?>"
                  title="Lihat rincian jawaban soal">
                  <i class="fas fa-list-ol"></i>
                  <span>Jawaban</span>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- CONTAINER 2: DESKTOP TABLE VIEW -->
  <div id="viewTableWrapper" class="d-none">
    <div class="table-card">
      <div class="table-responsive">
        <table id="tableAktivitasUser" class="table table-modern w-100">
          <thead>
            <tr>
              <th width="40" class="text-center">
                <input type="checkbox" id="checkAll" class="form-check-input">
              </th>
              <th width="30">No</th>
              <th>Nomor Ujian</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Ruang</th>
              <th>Waktu Mulai</th>
              <th width="100">Jawaban</th>
              <th width="110">Sisa Waktu</th>
              <th width="80">Nilai</th>
              <th width="100">Pelanggaran</th>
              <th width="110">Status</th>
              <th width="220" class="text-center">Aksi Cepat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sessions as $i => $s): ?>
              <?php
              $status = $s['status'] ?? 'timeout';
              $violations = (int) ($s['violation_count'] ?? 0);
              $remMin = (int) ($s['remaining_minutes'] ?? 0);
              $isStarted = !empty($s['started_at']);
              $startTimeFormatted = $isStarted ? date('Y-m-d H:i:s', $s['started_at']) : 'Belum Mulai';
              ?>
              <tr class="row-student" data-id="<?= $s['id'] ?>" data-status="<?= $status ?>" data-has-violation="<?= $violations > 0 ? '1' : '0' ?>">
                <td class="text-center">
                  <input type="checkbox" class="checkStudent form-check-input" value="<?= $s['id'] ?>">
                </td>
                <td class="text-center"><?= $i + 1 ?></td>
                <td class="text-center"><span class="badge-tag badge-username"><?= esc($s['username']) ?></span></td>
                <td><strong><?= esc($s['student_name']) ?></strong></td>
                <td class="text-center"><span class="badge-tag badge-class"><?= esc($s['class_name'] ?? '-') ?></span></td>
                <td class="text-center"><span class="badge-tag badge-room"><?= esc($s['room'] ?? '-') ?></span></td>
                <td class="text-center">
                  <?= $isStarted ? date('H:i:s', $s['started_at']) : '<span class="text-muted small">Belum</span>' ?>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-secondary btn-detail-jawaban" data-id="<?= $s['id'] ?>"
                    title="Lihat Detail Jawaban">
                    <i class="fas fa-list-ol"></i> Cek
                  </button>
                </td>
                <td class="text-center">
                  <?php if ($status === 'finished'): ?>
                    <span class="badge bg-light text-secondary border">
                      <i class="fas fa-check-circle me-1 text-success"></i>Selesai
                    </span>
                  <?php elseif ($status === 'expired'): ?>
                    <span class="badge bg-danger">Habis</span>
                  <?php else: ?>
                    <?php 
                      $remSec = (int)($s['remaining_seconds'] ?? ($remMin * 60));
                      $badgeCls = $remSec <= 300 ? 'bg-danger text-white' : ($remSec <= 600 ? 'bg-warning text-dark' : 'bg-info text-dark');
                    ?>
                    <span class="badge <?= $badgeCls ?> student-countdown" data-seconds="<?= $remSec ?>" data-status="active" style="font-variant-numeric: tabular-nums; min-width: 75px;">
                      <i class="fas fa-stopwatch me-1"></i><span class="timer-display"><?= sprintf('%02d:%02d', floor($remSec / 60), $remSec % 60) ?></span>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?= $status === 'finished' ? '<strong class="text-success">' . ($s['display_score'] ?? 0) . '</strong>' : '<span class="text-muted">-</span>' ?>
                </td>
                <td class="text-center">
                  <?php if ($violations > 0): ?>
                    <span class="badge bg-danger"><?= $violations ?>x</span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($status === 'active'): ?>
                    <span class="badge bg-success"><span class="pulse-dot me-1"></span>Aktif</span>
                  <?php elseif ($status === 'finished'): ?>
                    <span class="badge bg-primary">Selesai</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Habis</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="d-inline-flex gap-1">
                    <!-- Buka Login -->
                    <button class="btn btn-table-action btn-touch-unlock btn-reset-login"
                      data-student-id="<?= $s['student_id'] ?>" data-name="<?= esc($s['student_name']) ?>"
                      title="Buka Blokir Login (Reset Sesi Perangkat)">
                      <i class="fas fa-unlock-alt"></i>
                    </button>

                    <!-- Tambah Waktu -->
                    <button class="btn btn-table-action btn-touch-time btn-tambah-waktu" data-id="<?= $s['id'] ?>"
                      title="Tambah Waktu">
                      <i class="fas fa-clock"></i>
                    </button>

                    <!-- Paksa Selesai -->
                    <?php if ($status === 'active'): ?>
                      <button class="btn btn-table-action btn-touch-finish btn-force-finish" data-id="<?= $s['id'] ?>"
                        title="Paksa Selesai">
                        <i class="fas fa-check-circle"></i>
                      </button>
                    <?php else: ?>
                      <button class="btn btn-table-action btn-touch-finish" disabled title="Sudah Selesai">
                        <i class="fas fa-check"></i>
                      </button>
                    <?php endif; ?>

                    <!-- Reset Ujian -->
                    <button class="btn btn-table-action btn-touch-reset btn-reset" data-id="<?= $s['id'] ?>"
                      title="Reset Ujian Dari Awal">
                      <i class="fas fa-redo"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- FLOATING BOTTOM BAR (When students are selected) -->
<div class="floating-bottom-bar" id="floatingActionBar">
  <span class="floating-bar-text" id="floatingSelectedCount">0 Siswa Dipilih</span>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-sm btn-danger fw-bold rounded-pill px-3" id="btnFloatingReset">
      <i class="fas fa-redo-alt me-1"></i> Reset Terpilih
    </button>
    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" id="btnFloatingCancel">
      Batal
    </button>
  </div>
</div>

<!-- Modal detail jawaban -->
<div class="modal fade" id="modalDetailJawaban" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white rounded-top-4">
        <h5 class="modal-title fs-6 fw-bold">
          <i class="fas fa-list-alt me-2"></i>
          Detail Jawaban Siswa
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3 p-md-4">
        <div id="detailContent" class="text-center text-muted py-4">
          <i class="fas fa-spinner fa-spin fa-2x mb-3 text-primary"></i>
          <p>Memuat data jawaban...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Function to toggle individual card dropdown actions
  window.toggleCardAction = function(id, event) {
    // If the click came from interactive elements like buttons or checkboxes, let them handle it
    if (event && $(event.target).closest('input, button, a').length && !$(event.target).closest('.btn-toggle-actions').length) {
      return;
    }
    const card = $('#card-student-' + id);
    card.toggleClass('expanded');
  };

  $(function () {
    // 1. DataTables Init
    let dataTable = null;
    function initDataTable() {
      if ($.fn.DataTable.isDataTable('#tableAktivitasUser')) {
        dataTable = $('#tableAktivitasUser').DataTable();
        return;
      }
      dataTable = $('#tableAktivitasUser').DataTable({
        responsive: true,
        order: [[3, 'asc']], // Sort by Nama
        pageLength: 25,
        columnDefs: [
          { orderable: false, targets: [0, 7, 12] } // Checkbox, Jawaban, Action non-sortable
        ],
        language: {
          search: "Cari:",
          lengthMenu: "Tampilkan _MENU_ data",
          zeroRecords: "Tidak ada data ditemukan",
          info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
          paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
          }
        }
      });
    }

    // 2. View Mode (Cards vs Table)
    const savedView = localStorage.getItem('cbt_proctor_view_mode');
    const isMobileViewport = window.innerWidth < 992;
    let currentView = savedView ? savedView : (isMobileViewport ? 'cards' : 'cards');

    function applyView(mode) {
      currentView = mode;
      localStorage.setItem('cbt_proctor_view_mode', mode);

      if (mode === 'cards') {
        $('#btnViewCards').addClass('active');
        $('#btnViewTable').removeClass('active');
        $('#viewCardsWrapper').removeClass('d-none');
        $('#viewTableWrapper').addClass('d-none');
      } else {
        $('#btnViewCards').removeClass('active');
        $('#btnViewTable').addClass('active');
        $('#viewCardsWrapper').addClass('d-none');
        $('#viewTableWrapper').removeClass('d-none');
        initDataTable();
      }
    }

    applyView(currentView);

    $('#btnViewCards').on('click', function () {
      applyView('cards');
    });

    $('#btnViewTable').on('click', function () {
      applyView('table');
    });

    // 3. Toggle All Card Dropdowns at Once
    let allCardsExpanded = false;
    $('#btnToggleAllCards').on('click', function () {
      allCardsExpanded = !allCardsExpanded;
      if (allCardsExpanded) {
        $('.student-compact-card').addClass('expanded');
        $('#textToggleAllCards').text('Tutup');
        $(this).find('i').removeClass('fa-expand-alt').addClass('fa-compress-alt');
      } else {
        $('.student-compact-card').removeClass('expanded');
        $('#textToggleAllCards').text('Aksi');
        $(this).find('i').removeClass('fa-compress-alt').addClass('fa-expand-alt');
      }
    });

    // 4. Filtering & Search Synchronization
    let activeStatusFilter = 'all'; // 'all', 'active', 'finished', 'timeout', 'violation'

    $('.stat-filter-card').on('click', function () {
      $('.stat-filter-card').removeClass('active');
      $(this).addClass('active');
      activeStatusFilter = $(this).data('filter');
      applyAllFilters();
    });

    $('#filterKelas, #filterRuang').on('change', function () {
      applyAllFilters();
    });

    $('#liveSearchInput').on('input', function () {
      const q = $(this).val().trim();
      $('#btnClearSearch').toggle(q.length > 0);
      applyAllFilters();
    });

    $('#btnClearSearch').on('click', function () {
      $('#liveSearchInput').val('').trigger('input');
    });

    function applyAllFilters() {
      const query = $('#liveSearchInput').val().toLowerCase().trim();
      const selectedClass = $('#filterKelas').val().toLowerCase().trim();
      const selectedRoom = $('#filterRuang').val().toLowerCase().trim();

      // Filter Cards View
      let visibleCardCount = 0;
      $('.student-compact-card').each(function () {
        const card = $(this);
        const name = (card.data('name') || '').toString();
        const username = (card.data('username') || '').toString();
        const studentClass = (card.data('class') || '').toString().toLowerCase();
        const studentRoom = (card.data('room') || '').toString().toLowerCase();
        const status = (card.data('status') || '').toString();
        const hasViolation = card.data('has-violation') === 1 || card.data('has-violation') === '1';

        // Check text search
        let matchSearch = true;
        if (query) {
          matchSearch = name.includes(query) || username.includes(query) || studentClass.includes(query) || studentRoom.includes(query);
        }

        // Check class filter
        let matchClass = true;
        if (selectedClass) {
          matchClass = studentClass === selectedClass;
        }

        // Check room filter
        let matchRoom = true;
        if (selectedRoom) {
          matchRoom = studentRoom === selectedRoom;
        }

        // Check status ribbon filter
        let matchStatus = true;
        if (activeStatusFilter === 'active') {
          matchStatus = status === 'active';
        } else if (activeStatusFilter === 'finished') {
          matchStatus = status === 'finished';
        } else if (activeStatusFilter === 'timeout') {
          matchStatus = status !== 'active' && status !== 'finished';
        } else if (activeStatusFilter === 'violation') {
          matchStatus = hasViolation;
        }

        if (matchSearch && matchClass && matchRoom && matchStatus) {
          card.show();
          visibleCardCount++;
        } else {
          card.hide();
        }
      });

      // Filter DataTable if initialized
      if (dataTable) {
        $.fn.dataTable.ext.search = [];
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
          const rowNode = dataTable.row(dataIndex).node();
          const rowStatus = $(rowNode).data('status');
          const rowViolation = $(rowNode).data('has-violation');

          let matchStatus = true;
          if (activeStatusFilter === 'active') {
            matchStatus = rowStatus === 'active';
          } else if (activeStatusFilter === 'finished') {
            matchStatus = rowStatus === 'finished';
          } else if (activeStatusFilter === 'timeout') {
            matchStatus = rowStatus !== 'active' && rowStatus !== 'finished';
          } else if (activeStatusFilter === 'violation') {
            matchStatus = rowViolation === 1 || rowViolation === '1';
          }
          return matchStatus;
        });

        // Filter Kelas (col index 4)
        dataTable.column(4).search(selectedClass ? '^' + $.fn.dataTable.util.escapeRegex($('#filterKelas').val()) + '$' : '', true, false);
        // Filter Ruang (col index 5)
        dataTable.column(5).search(selectedRoom ? '^' + $.fn.dataTable.util.escapeRegex($('#filterRuang').val()) + '$' : '', true, false);
        // General Search
        dataTable.search(query).draw();
      }
    }

    // 5. Checkbox & Floating Bar Handlers
    function updateSelectionState() {
      const selectedBoxes = $('.checkStudent:checked');
      const count = selectedBoxes.length;

      if (count > 0) {
        $('#btnResetMassal').html('<i class="fas fa-redo-alt text-warning"></i> <span>Reset Terpilih (' + count + ')</span>');
        $('#floatingSelectedCount').text(count + ' Siswa Dipilih');
        $('#floatingActionBar').addClass('show');
      } else {
        $('#btnResetMassal').html('<i class="fas fa-redo-alt text-danger"></i> <span>Reset Masal</span>');
        $('#floatingActionBar').removeClass('show');
      }
    }

    // Master check all
    $('#checkAllMaster, #checkAll').on('change', function () {
      const isChecked = $(this).is(':checked');
      $('#checkAllMaster, #checkAll').prop('checked', isChecked);
      // Select all visible students only
      $('.student-compact-card:visible .checkStudent, #tableAktivitasUser tbody tr:visible .checkStudent').prop('checked', isChecked);
      updateSelectionState();
    });

    $(document).on('change', '.checkStudent', function () {
      const val = $(this).val();
      const isChecked = $(this).is(':checked');
      $('.checkStudent[value="' + val + '"]').prop('checked', isChecked);
      updateSelectionState();
    });

    $('#btnFloatingCancel').on('click', function () {
      $('.checkStudent, #checkAllMaster, #checkAll').prop('checked', false);
      updateSelectionState();
    });

    $('#btnFloatingReset').on('click', function () {
      $('#btnResetMassal').trigger('click');
    });

    // 6. CSRF Helper
    function getCsrfData() {
      const name = "<?= csrf_token() ?>";
      const value = $("meta[name='" + name + "']").attr("content") || "<?= csrf_hash() ?>";
      return { [name]: value };
    }

    // 7. LIGHTWEIGHT AJAX AUTO-REFRESH CONTROLLER (TANPA FULL PAGE RELOAD)
    let autoRefreshTimer = null;
    let refreshIntervalSeconds = parseInt($('#autoRefreshSelect').val()) || 60;
    let autoRefreshCountdown = refreshIntervalSeconds;
    let isAutoRefreshPaused = false;
    let isPollingInProgress = false;

    function resetCountdown() {
      autoRefreshCountdown = refreshIntervalSeconds;
      updateCountdownDisplay();
    }

    function updateCountdownDisplay() {
      if (refreshIntervalSeconds === 0) {
        $('#autoRefreshCountdownText').text('Auto-refresh: Mati');
        $('#autoRefreshIndicator').addClass('paused');
        return;
      }
      if (isAutoRefreshPaused) {
        $('#autoRefreshCountdownText').text('Auto-refresh: Dijeda');
        $('#autoRefreshIndicator').addClass('paused');
      } else {
        $('#autoRefreshCountdownText').text('Auto-refresh: ' + autoRefreshCountdown + 's');
        $('#autoRefreshIndicator').removeClass('paused');
      }
    }

    function pollActivityStatus() {
      if (isPollingInProgress) return;
      isPollingInProgress = true;

      // Animasi kedip halus pada indikator menandakan background sync sedang berjalan
      $('#autoRefreshIndicator').addClass('pulse-refresh');

      $.ajax({
        url: "<?= site_url('admin/cbt/aktivitas/pollStatus/' . $test['id']) ?>",
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function (res) {
          if (res && res.status === 'success') {
            // 1. Update KPI Ribbon & Header Counter
            if (res.kpi) {
              $('#statValTotal').text(res.kpi.total);
              $('#statValActive').text(res.kpi.active);
              $('#statValFinished').text(res.kpi.finished);
              $('#statValTimeout').text(res.kpi.timeout);
              $('#countBelum').text(res.kpi.active);

              let violText = res.kpi.violation_count;
              if (res.kpi.total_violations > 0) {
                violText += ' <small class="fs-6 fw-normal text-muted">(' + res.kpi.total_violations + 'x)</small>';
              }
              $('#statValViolation').html(violText);
            }

            // 2. Update status tiap siswa di Kartu Mobile & Baris Tabel Desktop
            if (res.students) {
              $.each(res.students, function (sessionId, sData) {
                const card = $('#card-student-' + sessionId);
                const row = $('tr.row-student[data-id="' + sessionId + '"]');

                if (!card.length && !row.length) return;

                const newStatus = sData.status;

                // Sync data attribute
                card.attr('data-status', newStatus);
                card.attr('data-has-violation', sData.violations > 0 ? '1' : '0');
                row.attr('data-status', newStatus);
                row.attr('data-has-violation', sData.violations > 0 ? '1' : '0');

                // Update styling container
                card.removeClass('is-active is-finished is-timeout').addClass('is-' + newStatus);
                if (sData.violations > 0) {
                  card.addClass('has-violation');
                } else {
                  card.removeClass('has-violation');
                }

                // Update countdown element jika aktif
                const cardCountdown = card.find('.student-countdown');
                const rowCountdown = row.find('.student-countdown');

                if (newStatus === 'finished') {
                  // Berubah jadi selesai
                  if (cardCountdown.length) {
                    cardCountdown.replaceWith('<span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;" title="Ujian Selesai"><i class="fas fa-check-circle me-1 text-success"></i>Selesai</span>');
                  }
                  if (rowCountdown.length) {
                    rowCountdown.replaceWith('<span class="badge bg-light text-secondary border"><i class="fas fa-check-circle me-1 text-success"></i>Selesai</span>');
                  }

                  // Update skor
                  card.find('.badge-primary-subtle').html('<i class="fas fa-check-circle me-1"></i>' + sData.score);
                  row.find('td:eq(9)').html('<strong class="text-success">' + sData.score + '</strong>');
                  row.find('td:eq(11)').html('<span class="badge bg-primary">Selesai</span>');
                } else if (newStatus === 'expired') {
                  if (cardCountdown.length) {
                    cardCountdown.replaceWith('<span class="badge bg-danger text-white px-2 py-1" style="font-size: 0.75rem;" title="Waktu Habis"><i class="fas fa-hourglass-end me-1"></i>Habis</span>');
                  }
                  if (rowCountdown.length) {
                    rowCountdown.replaceWith('<span class="badge bg-danger">Habis</span>');
                  }
                  row.find('td:eq(11)').html('<span class="badge bg-secondary">Habis</span>');
                } else if (newStatus === 'active') {
                  // Sinkronkan sisa detik dari server jika aktif
                  cardCountdown.attr('data-seconds', sData.remaining_seconds);
                  rowCountdown.attr('data-seconds', sData.remaining_seconds);
                }
              });
            }
          }
        },
        complete: function () {
          isPollingInProgress = false;
          setTimeout(function () {
            $('#autoRefreshIndicator').removeClass('pulse-refresh');
          }, 800);
        }
      });
    }

    function startAutoRefreshLoop() {
      if (autoRefreshTimer) clearInterval(autoRefreshTimer);
      if (refreshIntervalSeconds <= 0) {
        updateCountdownDisplay();
        return;
      }

      autoRefreshTimer = setInterval(function () {
        // Jangan polling jika proctor sedang berinteraksi aktif:
        if ($('.swal2-container').length > 0) return;
        if ($('#modalDetailJawaban').hasClass('show')) return;
        if ($('.checkStudent:checked').length > 0) return;
        if ($('#liveSearchInput').is(':focus')) return;
        if (isAutoRefreshPaused) return;

        autoRefreshCountdown--;
        if (autoRefreshCountdown <= 0) {
          resetCountdown();
          pollActivityStatus();
        } else {
          updateCountdownDisplay();
        }
      }, 1000);
    }

    $('#autoRefreshSelect').on('change', function () {
      refreshIntervalSeconds = parseInt($(this).val()) || 0;
      resetCountdown();
      startAutoRefreshLoop();
    });

    $('#btnToggleAutoRefresh').on('click', function () {
      isAutoRefreshPaused = !isAutoRefreshPaused;
      if (isAutoRefreshPaused) {
        $('#iconAutoRefreshPause').removeClass('fa-pause').addClass('fa-play');
      } else {
        $('#iconAutoRefreshPause').removeClass('fa-play').addClass('fa-pause');
      }
      updateCountdownDisplay();
    });

    startAutoRefreshLoop();

    // 8. DETAIL JAWABAN
    $(document).on('click', '.btn-detail-jawaban', function () {
      const id = $(this).data('id');
      $('#detailContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x mb-3 text-primary"></i><p class="text-muted fw-semibold">Memuat rincian soal & jawaban siswa...</p></div>');
      $('#modalDetailJawaban').modal('show');
      $.get("<?= site_url('admin/cbt/aktivitas/detail_jawaban/') ?>" + id, function (html) {
        $('#detailContent').html(html);
        if (window.MathJax && window.MathJax.typesetPromise) {
          window.MathJax.typesetPromise([document.getElementById('detailContent')]).catch(function(err){
            console.warn('MathJax typeset error: ', err);
          });
        }
      }).fail(() => {
        $('#detailContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Gagal memuat data jawaban</div>');
      });
    });

    // Navigasi cepat klik nomor soal di modal detail jawaban
    $(document).on('click', '.modal-nav-q-btn', function (e) {
      e.preventDefault();
      const targetId = $(this).data('target');
      const targetEl = document.getElementById(targetId);
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        $(targetEl).addClass('border-primary shadow');
        setTimeout(() => {
          $(targetEl).removeClass('border-primary shadow');
        }, 1500);
      }
    });

    // 8b. ANIMASI HITUNG MUNDUR SISA WAKTU SISWA (COUNTDOWN JAVASCRIPT)
    function startStudentCountdowns() {
      setInterval(function () {
        $('.student-countdown[data-status="active"]').each(function () {
          let el = $(this);
          let sec = parseInt(el.attr('data-seconds'), 10);
          if (isNaN(sec)) return;

          if (sec <= 0) {
            el.find('.timer-display').text('00:00');
            el.removeClass('bg-info bg-warning bg-info-subtle text-dark text-info-emphasis').addClass('bg-danger text-white');
            el.attr('data-status', 'expired');
            return;
          }

          sec--;
          el.attr('data-seconds', sec);

          let m = Math.floor(sec / 60);
          let s = sec % 60;
          let formattedTime = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
          el.find('.timer-display').text(formattedTime);

          // Update warna & visual peringatan secara dinamis
          if (sec <= 300) { // <= 5 Menit: Merah (Bahaya)
            if (!el.hasClass('bg-danger')) {
              el.removeClass('bg-info bg-warning bg-info-subtle text-dark text-info-emphasis')
                .addClass('bg-danger text-white');
              el.find('i').addClass('text-white');
            }
          } else if (sec <= 600) { // <= 10 Menit: Kuning (Peringatan)
            if (!el.hasClass('bg-warning')) {
              el.removeClass('bg-info bg-danger bg-info-subtle text-white text-info-emphasis')
                .addClass('bg-warning text-dark');
              el.find('i').removeClass('text-white');
            }
          }
        });
      }, 1000);
    }
    startStudentCountdowns();

    // 9. BUKA BLOKIR LOGIN (RESET SESI PERANGKAT)
    $(document).on('click', '.btn-reset-login', function () {
      const studentId = $(this).data('student-id');
      const studentName = $(this).data('name');

      Swal.fire({
        title: 'Buka Login Siswa?',
        html: `Apakah Anda ingin membuka akses login untuk <b>${studentName}</b>?<br><br><span class="badge bg-success-subtle text-success p-2 fs-6"><i class="fas fa-check-circle me-1"></i> Jawaban aman & tidak hilang</span><br><small class="text-muted mt-2 d-block">Siswa dapat langsung login kembali di perangkat HP/laptop untuk melanjutkan ujian.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-unlock me-1"></i> Ya, Buka Akses!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          const payload = getCsrfData();
          payload.test_id = "<?= $test['id'] ?>";

          Swal.fire({
            title: 'Memproses...',
            text: 'Membuka blokir login sesi siswa',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          $.post(`<?= site_url('admin/cbt/aktivitas/resetLogin') ?>/${studentId}`, payload)
            .done((res) => {
              Swal.fire({
                title: 'Berhasil Dibuka!',
                text: res.message || 'Siswa sudah dapat login kembali.',
                icon: 'success',
                confirmButtonColor: '#4f46e5'
              }).then(() => location.reload());
            })
            .fail((xhr) => {
              Swal.fire('Gagal!', xhr.responseJSON?.error || 'Terjadi kesalahan sistem', 'error');
            });
        }
      });
    });

    // 10. TAMBAH WAKTU
    $(document).on('click', '.btn-tambah-waktu', function () {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Tambah Waktu Ujian',
        input: 'number',
        inputLabel: 'Masukkan jumlah menit tambahan:',
        inputPlaceholder: 'Contoh: 15',
        inputValue: 15,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-plus-circle me-1"></i> Tambahkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0284c7',
        inputValidator: (value) => {
          if (!value || parseInt(value) <= 0) {
            return 'Masukkan jumlah menit positif!';
          }
        }
      }).then(res => {
        if (!res.isConfirmed || !res.value) return;

        const payload = getCsrfData();
        payload.minutes = res.value;

        Swal.fire({
          title: 'Memproses...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        $.post("<?= site_url('admin/cbt/aktivitas/addTime') ?>/" + id, payload)
          .done(function () {
            Swal.fire('Berhasil', 'Waktu ujian berhasil ditambahkan.', 'success').then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal menambahkan waktu';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });

    // 11. PAKSA SELESAI PER SISWA
    $(document).on('click', '.btn-force-finish', function () {
      const id = $(this).data('id');

      Swal.fire({
        title: 'Paksa Selesaikan Ujian?',
        text: 'Ujian siswa ini akan diselesaikan secara paksa dan nilai akhir akan langsung dihitung otomatis.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
      }).then((r) => {
        if (!r.isConfirmed) return;

        Swal.fire({
          title: 'Memproses...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/forceFinish/') ?>" + id, payload)
          .done(function (res) {
            Swal.fire({
              title: 'Berhasil Diselesaikan!',
              html: 'Ujian telah diselesaikan.<br>Nilai: <strong>' + (res.score || 0) + '</strong>',
              icon: 'success',
              confirmButtonColor: '#4f46e5'
            }).then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });

    // 12. RESET UJIAN (REDO SINGLE)
    $(document).on('click', '.btn-reset', function () {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Reset Ujian Siswa Ini?',
        html: '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> PERHATIAN:</span><br>Seluruh jawaban yang sudah tersimpan akan <b>DIHAPUS</b> dan siswa harus mengulang ujian dari nomor 1.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset Ujian',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
      }).then((r) => {
        if (!r.isConfirmed) return;

        Swal.fire({
          title: 'Memproses Reset...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/resetSession') ?>/" + id, payload)
          .done(function () {
            Swal.fire('Sesi Direset', 'Siswa dapat mengulang ujian dari awal.', 'success').then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });

    // 13. RESET MASAL / RESET TERPILIH
    $(document).on('click', '#btnResetMassal', function () {
      const selectedIds = $('.checkStudent:checked').map(function () {
        return $(this).val();
      }).get();

      const uniqueSelectedIds = [...new Set(selectedIds)];

      let title = 'Reset SELURUH Siswa?';
      let htmlText = 'Semua siswa dalam ujian ini akan direset dari awal. Seluruh jawaban akan <b>DIHAPUS PERMANEN</b>!';

      if (uniqueSelectedIds.length > 0) {
        title = 'Reset ' + uniqueSelectedIds.length + ' Siswa Terpilih?';
        htmlText = uniqueSelectedIds.length + ' siswa terpilih akan direset dari awal. Jawaban mereka akan <b>DIHAPUS</b>!';
      }

      Swal.fire({
        title: title,
        html: htmlText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-redo me-1"></i> Ya, Reset!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
      }).then((r) => {
        if (!r.isConfirmed) return;

        Swal.fire({
          title: 'Memproses Reset Massal...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const payload = getCsrfData();
        if (uniqueSelectedIds.length > 0) {
          payload['session_ids[]'] = uniqueSelectedIds;
        }

        $.ajax({
          url: "<?= site_url('admin/cbt/aktivitas/resetMassal/' . $test['id']) ?>",
          type: 'POST',
          data: payload,
          traditional: true,
          success: function (res) {
            Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
          },
          error: function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal mereset sesi massal';
            Swal.fire('Gagal', msg, 'error');
          }
        });
      });
    });

    // 14. SELESAIKAN MASSAL (SISWA BELUM SELESAI)
    $('#btnBelumSelesai').on('click', function () {
      const count = parseInt($('#countBelum').text()) || 0;
      if (count <= 0) {
        Swal.fire({
          title: 'Info',
          text: 'Tidak ada siswa yang sedang aktif ujian.',
          icon: 'info',
          confirmButtonColor: '#4f46e5'
        });
        return;
      }

      Swal.fire({
        title: 'Selesaikan Massal?',
        html: 'Seluruh siswa (<strong>' + count + '</strong>) yang masih aktif mengerjakan akan dipaksa selesai.<br><br>Nilai akan langsung dihitung otomatis berdasarkan jawaban terakhir yang tersimpan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan Semua!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
      }).then((r) => {
        if (!r.isConfirmed) return;

        Swal.fire({
          title: 'Menyelesaikan Massal...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/forceFinishMassal/' . $test['id']) ?>", payload)
          .done(function (res) {
            let message = res.message || 'Berhasil menyelesaikan ujian siswa.';
            if (res.errors && res.errors.length > 0) {
              message += '<br><br><small class="text-danger">Catatan:<br>' + res.errors.join('<br>') + '</small>';
            }

            Swal.fire({
              title: 'Selesai!',
              html: message,
              icon: 'success',
              confirmButtonColor: '#4f46e5'
            }).then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal menyelesaikan massal';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });
  });
</script>
<?= $this->endSection() ?>