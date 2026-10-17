<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  /* Modern Dashboard Styles */
  .dashboard-modern {
    --primary-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --info-gradient: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    overflow-x: hidden;
    max-width: 100%;
    width: 100%;
  }

  /* ── Welcome Section: Proportional & Sleek with Navbar ── */
  .welcome-section {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #0284c7 100%);
    border-radius: 18px;
    padding: 1.25rem 1.75rem;
    color: white;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.16);
  }

  .welcome-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .welcome-section::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -5%;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.22) 0%, rgba(6, 182, 212, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .welcome-content {
    position: relative;
    z-index: 2;
  }

  .welcome-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #ffffff !important;
    letter-spacing: -0.02em;
    margin-bottom: 0.2rem;
  }

  .welcome-subtitle {
    font-size: 0.88rem;
    color: rgba(255, 255, 255, 0.92) !important;
    margin-bottom: 0;
  }

  .clock-display {
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 14px;
    padding: 0.6rem 1.1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  .clock-display .realtime-clock {
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.5px;
    line-height: 1.1;
  }

  .clock-display .clock-date {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.88);
    font-weight: 500;
  }

  /* ── Admin Quick Stat Cards: Crystal Clear, High Contrast & Resilient ── */
  .admin-stat-card {
    background: #ffffff !important;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    position: relative;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .admin-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.09);
    border-color: rgba(14, 165, 233, 0.35);
  }

  .admin-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3.5px;
    background: var(--stat-gradient);
  }

  .admin-stat-body {
    padding: 1.15rem 1.2rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    justify-content: space-between;
  }

  .admin-stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
  }

  .admin-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #ffffff !important;
    background: var(--stat-gradient);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
  }

  .admin-stat-badge {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    background: var(--badge-bg);
    color: var(--badge-color) !important;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .admin-stat-value {
    font-size: 1.85rem;
    font-weight: 800;
    color: #0f172a !important;
    line-height: 1.1;
    letter-spacing: -0.02em;
    margin-bottom: 0.25rem;
  }

  .admin-stat-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
    margin-bottom: 0.4rem;
  }

  .admin-stat-footer {
    display: flex;
    align-items: center;
    font-size: 0.74rem;
    color: #64748b !important;
    font-weight: 500;
    gap: 0.35rem;
  }

  /* Specific Color Themes */
  .admin-stat-card.siswa {
    --stat-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    --badge-bg: rgba(2, 132, 199, 0.1);
    --badge-color: #0284c7;
  }
  .admin-stat-card.guru {
    --stat-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --badge-bg: rgba(16, 185, 129, 0.1);
    --badge-color: #059669;
  }
  .admin-stat-card.kelas {
    --stat-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --badge-bg: rgba(245, 158, 11, 0.1);
    --badge-color: #d97706;
  }
  .admin-stat-card.sesi {
    --stat-gradient: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    --badge-bg: rgba(139, 92, 246, 0.1);
    --badge-color: #7c3aed;
  }

  /* ── Akses Cepat Admin ── */
  .admin-action-tile {
    display: flex;
    align-items: center;
    padding: 0.75rem 0.9rem;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid rgba(14, 165, 233, 0.12);
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none !important;
  }
  .admin-action-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(14, 165, 233, 0.12);
    border-color: rgba(14, 165, 233, 0.35);
  }
  .admin-action-tile:active {
    transform: scale(0.98);
  }
  .admin-action-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    color: white;
    margin-right: 0.75rem;
    flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
  }
  .admin-action-icon.jadwal   { background: linear-gradient(135deg, #0284c7, #0369a1); }
  .admin-action-icon.presensi { background: linear-gradient(135deg, #06b6d4, #0d9488); }
  .admin-action-icon.notif    { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .admin-action-icon.laporan  { background: linear-gradient(135deg, #10b981, #059669); }

  .admin-action-text {
    flex-grow: 1;
    min-width: 0;
  }
  .admin-action-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
    margin-bottom: 2px;
  }
  .admin-action-desc {
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .admin-action-arrow {
    color: #94a3b8;
    font-size: 1rem;
    margin-left: 0.4rem;
    transition: transform 0.2s ease, color 0.2s ease;
  }
  .admin-action-tile:hover .admin-action-arrow {
    transform: translateX(3px);
    color: #0ea5e9;
  }

  .modern-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
  }

  .modern-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  }

  .modern-card-header {
    background: white;
    border-bottom: 1px solid #f0f0f0;
    padding: 1.25rem 1.5rem;
    border-radius: 16px 16px 0 0;
  }

  .agenda-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
  }

  .agenda-item:hover {
    background: #f8f9fa;
  }

  .agenda-item:last-child {
    border-bottom: none;
  }

  .time-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .class-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
  }

  /* ── Mobile Responsive (HP) - Compact & Harmonious with 52px Navbar ── */
  @media (max-width: 767.98px) {
    .dashboard-modern {
      overflow-x: hidden !important;
      max-width: 100vw !important;
    }

    .welcome-section {
      padding: 0.7rem 0.85rem !important;
      border-radius: 14px !important;
      margin-bottom: 0.75rem !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }

    .welcome-title {
      font-size: 1.05rem !important;
      margin-bottom: 0.1rem !important;
    }

    .welcome-subtitle {
      font-size: 0.74rem !important;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 180px;
    }

    .clock-display {
      padding: 0.28rem 0.55rem !important;
      border-radius: 10px !important;
      white-space: nowrap;
    }

    .clock-display .realtime-clock {
      font-size: 0.9rem !important;
      line-height: 1.1 !important;
    }

    .clock-display .clock-date {
      font-size: 0.62rem !important;
    }

    /* Admin Stat Card Mobile Proportions */
    .admin-stat-card {
      border-radius: 13px !important;
    }

    .admin-stat-body {
      padding: 0.7rem 0.75rem !important;
    }

    .admin-stat-header {
      margin-bottom: 0.35rem !important;
    }

    .admin-stat-icon {
      width: 34px !important;
      height: 34px !important;
      font-size: 1.05rem !important;
      border-radius: 9px !important;
    }

    .admin-stat-badge {
      font-size: 0.62rem !important;
      padding: 0.18rem 0.45rem !important;
    }

    .admin-stat-value {
      font-size: 1.35rem !important;
      margin-top: 0.25rem !important;
      margin-bottom: 0.1rem !important;
    }

    .admin-stat-label {
      font-size: 0.68rem !important;
      margin-bottom: 0.2rem !important;
    }

    .admin-stat-footer {
      font-size: 0.65rem !important;
    }

    .modern-card-header {
      padding: 0.85rem 1rem !important;
    }

    .agenda-item {
      padding: 0.75rem 0.9rem !important;
    }
  }

  @media (max-width: 575.98px) {
    .admin-stat-card {
      margin-bottom: 0;
    }
  }

  /* Schedule Card Scrollable Container */
  .schedule-card .list-group-flush {
    max-height: 600px;
    overflow-y: auto;
    overflow-x: hidden;
  }

  /* Custom Scrollbar for Schedule */
  .schedule-card .list-group-flush::-webkit-scrollbar {
    width: 8px;
  }

  .schedule-card .list-group-flush::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .schedule-card .list-group-flush::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
  }

  .schedule-card .list-group-flush::-webkit-scrollbar-thumb:hover {
    background: #555;
  }

  /* Firefox Scrollbar */
  .schedule-card .list-group-flush {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
  }

  /* Smooth scroll behavior */
  .schedule-card .list-group-flush {
    scroll-behavior: smooth;
  }

  /* Fade effect at bottom when scrollable */
  .schedule-card .card-body {
    position: relative;
  }

  .schedule-card .list-group-flush::after {
    content: '';
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.9));
    pointer-events: none;
    display: none;
  }

  .schedule-card .list-group-flush.scrollable::after {
    display: block;
  }

</style>

<div class="container-fluid p-0 dashboard-modern">
  <!-- Welcome Section -->
  <div class="welcome-section">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 welcome-content">
      <div class="min-w-0">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge rounded-pill px-2.5 py-1" style="background: #ffffff !important; color: #1e40af !important; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            <i class="bi bi-shield-lock-fill" style="color: #1e40af; font-size: 0.72rem;"></i> ADMINISTRATOR
          </span>
        </div>
        <h2 class="welcome-title mb-0">Dashboard Admin</h2>
        <p class="welcome-subtitle text-truncate">Selamat datang kembali, <strong><?= esc($user['fullname'] ?? $user['username']) ?></strong>! 👋</p>
      </div>
      <div class="ms-auto">
        <div class="clock-display text-end">
          <div class="realtime-clock"><?= date('H:i:s') ?></div>
          <div class="clock-date"><?= date('d F Y') ?></div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($updateInfo['has_update'])): ?>
    <!-- Alert Pembaruan Aplikasi Tersedia -->
    <div class="alert border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3 text-white" 
         style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border-radius: 14px; padding: 1rem 1.25rem;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
          <i class="bi bi-cloud-arrow-down-fill fs-4 text-white"></i>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="badge bg-danger rounded-pill px-2 py-0.5 update-pulse" style="font-size:0.65rem;">UPDATE BARU</span>
            <strong class="fs-6">Versi <?= esc($updateInfo['remote_version']) ?> Siap Diinstal!</strong>
            <?php if (!empty($updateInfo['release_date'])): ?>
              <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5" style="font-size:0.65rem;">Rilis: <?= esc(substr($updateInfo['release_date'], 0, 10)) ?></span>
            <?php endif; ?>
          </div>
          <p class="mb-0 small text-white text-opacity-90">
            Aplikasi Anda saat ini berjalan pada versi <code>v<?= esc($updateInfo['current_version']) ?></code>. Terapkan pembaruan sekarang untuk mendapatkan fitur terbaru dan peningkatan performa.
          </p>
        </div>
      </div>
      <div>
        <a href="<?= base_url('admin/updater') ?>" class="btn btn-light text-primary fw-bold btn-sm px-3 py-2 shadow-sm rounded-3">
          <i class="bi bi-arrow-repeat me-1"></i> Buka Pembaruan Sistem
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Quick Stats Row -->
  <div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- 1. Siswa Aktif -->
    <div class="col-6 col-xl-3">
      <div class="admin-stat-card siswa">
        <div class="admin-stat-body">
          <div class="admin-stat-header">
            <div class="admin-stat-icon">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="admin-stat-badge">
              <i class="bi bi-person-check me-1"></i>Siswa
            </span>
          </div>
          <div>
            <div class="admin-stat-value"><?= number_format($stats['active_students'] ?? $stats['total_students']) ?></div>
            <div class="admin-stat-label">Siswa Aktif</div>
            <div class="admin-stat-footer text-truncate">
              <i class="bi bi-info-circle text-primary"></i>
              <span>Total: <strong><?= number_format($stats['total_students']) ?></strong></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Guru Aktif -->
    <div class="col-6 col-xl-3">
      <div class="admin-stat-card guru">
        <div class="admin-stat-body">
          <div class="admin-stat-header">
            <div class="admin-stat-icon">
              <i class="bi bi-person-badge-fill"></i>
            </div>
            <span class="admin-stat-badge">
              <i class="bi bi-mortarboard me-1"></i>Pendidik
            </span>
          </div>
          <div>
            <div class="admin-stat-value"><?= number_format($stats['active_teachers'] ?? $stats['total_teachers']) ?></div>
            <div class="admin-stat-label">Guru Aktif</div>
            <div class="admin-stat-footer text-truncate">
              <i class="bi bi-info-circle text-success"></i>
              <span>Total: <strong><?= number_format($stats['total_teachers']) ?></strong></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Total Kelas -->
    <div class="col-6 col-xl-3">
      <div class="admin-stat-card kelas">
        <div class="admin-stat-body">
          <div class="admin-stat-header">
            <div class="admin-stat-icon">
              <i class="bi bi-door-open-fill"></i>
            </div>
            <span class="admin-stat-badge">
              <i class="bi bi-grid me-1"></i>Rombel
            </span>
          </div>
          <div>
            <div class="admin-stat-value"><?= number_format($stats['total_classes']) ?></div>
            <div class="admin-stat-label">Total Kelas</div>
            <div class="admin-stat-footer text-truncate">
              <i class="bi bi-book text-warning"></i>
              <span><strong><?= number_format($stats['total_subjects'] ?? 0) ?></strong> Mapel Aktif</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Sesi Hari Ini -->
    <?php 
      $totalJP = 0;
      if (!empty($todaySchedule)) {
        foreach ($todaySchedule as $item) {
          if (function_exists('hitung_jp_dari_waktu')) {
            $totalJP += hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
          }
        }
      }
    ?>
    <div class="col-6 col-xl-3">
      <div class="admin-stat-card sesi">
        <div class="admin-stat-body">
          <div class="admin-stat-header">
            <div class="admin-stat-icon">
              <i class="bi bi-calendar-check-fill"></i>
            </div>
            <span class="admin-stat-badge">
              <i class="bi bi-clock me-1"></i>KBM
            </span>
          </div>
          <div>
            <div class="admin-stat-value"><?= number_format(count($todaySchedule ?? [])) ?></div>
            <div class="admin-stat-label">Sesi Hari Ini</div>
            <div class="admin-stat-footer text-truncate">
              <i class="bi bi-hourglass-split text-info"></i>
              <span><strong><?= $totalJP ?></strong> JP Terjadwal</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- System Status & Quick Actions -->
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="modern-card h-100">
        <div class="modern-card-header">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-speedometer2 me-2 text-primary"></i>
            Status Sistem & Aktivitas
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <!-- Kolom Kiri: Status Server & Lingkungan Sistem -->
            <div class="col-md-6 border-end">
              <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between">
                <span><i class="bi bi-hdd-network me-2 text-primary"></i>Kondisi Server & Database</span>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1" style="font-size: 0.7rem;">
                  <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Optimal
                </span>
              </h6>
              
              <!-- Web Server & PHP -->
              <div class="p-2.5 rounded-3 bg-light bg-opacity-60 mb-2.5 border border-light">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                      <i class="bi bi-cpu fs-6"></i>
                    </div>
                    <div>
                      <div class="fw-bold small text-dark">Engine & Web Server</div>
                      <small class="text-muted" style="font-size: 0.72rem;"><?= esc($systemStatus['server_soft'] ?? 'Apache/PHP') ?></small>
                    </div>
                  </div>
                  <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill font-monospace" style="font-size: 0.7rem;">PHP <?= esc($systemStatus['php_version'] ?? PHP_VERSION) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light-subtle small text-muted" style="font-size: 0.75rem;">
                  <span>Memory RAM Terpakai:</span>
                  <span class="fw-semibold text-dark"><?= esc($systemStatus['memory_usage'] ?? '-') ?> <span class="text-muted fw-normal">/ <?= esc($systemStatus['memory_limit'] ?? '-') ?></span></span>
                </div>
              </div>

              <!-- Database -->
              <div class="p-2.5 rounded-3 bg-light bg-opacity-60 mb-2.5 border border-light">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                      <i class="bi bi-database-check fs-6"></i>
                    </div>
                    <div>
                      <div class="fw-bold small text-dark">Database Server</div>
                      <small class="text-muted" style="font-size: 0.72rem;"><?= esc($systemStatus['db_version'] ?? 'MariaDB/MySQL') ?></small>
                    </div>
                  </div>
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.7rem;">
                    <i class="bi bi-check2 me-1"></i><?= esc($systemStatus['db_status'] ?? 'Terhubung') ?>
                  </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light-subtle small text-muted" style="font-size: 0.75rem;">
                  <span>Ukuran Basis Data:</span>
                  <span class="fw-semibold text-dark"><?= esc($systemStatus['db_size'] ?? '-') ?></span>
                </div>
              </div>

              <!-- Disk Storage -->
              <div class="d-flex align-items-center justify-content-between px-2 pt-1 small text-muted" style="font-size: 0.75rem;">
                <span><i class="bi bi-hdd me-1"></i>Ruang Disk Server Bebas:</span>
                <span class="fw-bold text-dark"><?= esc($systemStatus['free_disk'] ?? '-') ?></span>
              </div>
            </div>
            
            <!-- Kolom Kanan: Aktivitas Sistem & Pengguna Real-Time -->
            <div class="col-md-6">
              <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between">
                <span><i class="bi bi-activity me-2 text-info"></i>Aktivitas Nyata Pengguna</span>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1" style="font-size: 0.7rem;">
                  <i class="bi bi-lightning-charge me-1"></i> Real-time
                </span>
              </h6>

              <!-- 2 Stat mini: Sesi Aktif & Transaksi Hari Ini -->
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="p-2.5 rounded-3 bg-light bg-opacity-70 border border-light text-center">
                    <div class="fw-bold fs-5 text-primary mb-0" id="dashOnlineUsers"><?= (int)($systemActivity['online_users'] ?? 1) ?></div>
                    <small class="text-muted" style="font-size: 0.72rem;">Sesi Pengguna Aktif</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2.5 rounded-3 bg-light bg-opacity-70 border border-light text-center">
                    <div class="fw-bold fs-5 text-success mb-0"><?= number_format((int)($systemActivity['today_requests'] ?? 0)) ?></div>
                    <small class="text-muted" style="font-size: 0.72rem;">Operasi Data Hari Ini</small>
                  </div>
                </div>
              </div>

              <!-- Feed Aktivitas Nyata Terakhir -->
              <div class="small fw-semibold text-muted mb-1 d-flex align-items-center justify-content-between" style="font-size: 0.75rem;">
                <span><i class="bi bi-clock-history me-1"></i>Riwayat Log Audit Terakhir:</span>
                <span class="text-muted fw-normal" style="font-size: 0.7rem;">Login Anda: <?= esc($systemActivity['last_login'] ?? date('H:i')) ?></span>
              </div>
              
              <div class="list-group list-group-flush border rounded-3 bg-white" style="font-size: 0.78rem;">
                <?php if (!empty($systemActivity['recent_activities'])): ?>
                  <?php foreach ($systemActivity['recent_activities'] as $act): ?>
                    <div class="list-group-item py-2 px-3 border-light d-flex align-items-center justify-content-between">
                      <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                        <i class="bi bi-dot text-primary fs-5 lh-1"></i>
                        <span class="fw-semibold text-dark text-truncate" style="max-width: 140px;"><?= esc($act['actor']) ?></span>
                        <span class="text-muted text-truncate"><?= esc($act['action']) ?></span>
                      </div>
                      <span class="text-muted flex-shrink-0 font-monospace" style="font-size: 0.7rem;"><?= esc($act['time']) ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="list-group-item py-3 px-3 border-0 text-center text-muted">
                    <i class="bi bi-shield-check text-success d-block mb-1 fs-5"></i>
                    Belum ada riwayat audit terbaru hari ini
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="modern-card h-100">
        <div class="modern-card-header">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-lightning-fill me-2 text-warning"></i>
            Akses Cepat Admin
          </h5>
        </div>
        <div class="card-body p-3">
          <div class="d-grid gap-2">
            <a href="<?= site_url('admin/schedules') ?>" class="admin-action-tile">
              <div class="admin-action-icon jadwal">
                <i class="bi bi-calendar-plus"></i>
              </div>
              <div class="admin-action-text">
                <div class="admin-action-title">Kelola Jadwal</div>
                <div class="admin-action-desc">Atur jadwal KBM & jam pelajaran</div>
              </div>
              <i class="bi bi-chevron-right admin-action-arrow"></i>
            </a>

            <a href="<?= site_url('admin/attendance') ?>" class="admin-action-tile">
              <div class="admin-action-icon presensi">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div class="admin-action-text">
                <div class="admin-action-title">Presensi Harian</div>
                <div class="admin-action-desc">Monitoring kehadiran siswa & guru</div>
              </div>
              <i class="bi bi-chevron-right admin-action-arrow"></i>
            </a>

            <a href="<?= site_url('admin/announcements') ?>" class="admin-action-tile">
              <div class="admin-action-icon notif">
                <i class="bi bi-megaphone"></i>
              </div>
              <div class="admin-action-text">
                <div class="admin-action-title">Buat Pengumuman</div>
                <div class="admin-action-desc">Publikasikan info ke guru & siswa</div>
              </div>
              <i class="bi bi-chevron-right admin-action-arrow"></i>
            </a>

            <a href="<?= site_url('admin/reports') ?>" class="admin-action-tile">
              <div class="admin-action-icon laporan">
                <i class="bi bi-graph-up"></i>
              </div>
              <div class="admin-action-text">
                <div class="admin-action-title">Laporan & Analisis</div>
                <div class="admin-action-desc">Statistik akademik & rekap data</div>
              </div>
              <i class="bi bi-chevron-right admin-action-arrow"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Schedule & Absence Rekap -->
    <div class="col-lg-8">
      <!-- Modified Schedule Card with Class Filter -->
      <div class="card border-0 shadow-sm mb-4 modern-card schedule-card teaching-schedule">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 modern-card-header" style="border-top: 4px solid #667eea;">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-primary">
                <i class="bi bi-person-badge me-1"></i> Jadwal Mengajar
              </span>
            </div>
            <h5 class="mb-0 fw-bold text-dark">
              <i class="bi bi-clock-history me-2 text-primary"></i> 
              Jadwal Pelajaran Hari Ini
            </h5>
            <?php if (!empty($todaySchedule)): ?>
              <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Total <?= count($todaySchedule) ?> sesi pelajaran
                <?php 
                  $totalJP = 0;
                  foreach ($todaySchedule as $item) {
                    $totalJP += hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                  }
                  if ($totalJP > 0): 
                ?>
                  • <span class="text-success fw-semibold"><?= $totalJP ?> JP</span>
                <?php endif; ?>
              </small>
            <?php endif; ?>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Class Filter Dropdown -->
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="dropdown" title="Filter Kelas">
                <i class="bi bi-funnel"></i> Kelas
              </button>
              <ul class="dropdown-menu dropdown-menu-end" style="min-width: 250px;">
                <li class="px-3 py-2">
                  <small class="text-muted fw-bold">PILIH KELAS</small>
                </li>
                <li>
                  <a class="dropdown-item <?= !($selectedClassId ?? null) ? 'active' : '' ?>" href="<?= site_url('dashboard') ?>">
                    <i class="bi bi-check-circle me-2"></i> Semua Kelas
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php 
                  $db = \Config\Database::connect();
                  $classes = $db->table('classes')->orderBy('name', 'ASC')->get()->getResultArray();
                  foreach ($classes as $class): 
                ?>
                  <li>
                    <a class="dropdown-item <?= ($selectedClassId ?? null) == $class['id'] ? 'active' : '' ?>" 
                       href="<?= site_url('dashboard?class_id=' . $class['id'] . '&date=' . $rekapDate) ?>">
                      <i class="bi bi-door-closed me-2"></i> <?= esc($class['name']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            
            <!-- Date Picker -->
            <form action="" method="get" class="me-2">
              <div class="input-group input-group-sm">
                <input type="hidden" name="class_id" value="<?= $selectedClassId ?? '' ?>">
                <input type="date" name="date" class="form-control form-control-sm rounded-start-pill ps-3" 
                       value="<?= $rekapDate ?? date('Y-m-d') ?>" onchange="this.form.submit()">
                <button class="btn btn-primary btn-sm rounded-end-pill px-3" type="submit">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </form>
            
            <!-- Date Badge -->
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill h6 mb-0 shadow-sm">
              <?= date('l, d M Y', strtotime($rekapDate ?? date('Y-m-d'))) ?>
            </span>
          </div>
        </div>
        <div class="card-body p-0">
          <?php 
          // Check if it's a holiday
          $isHoliday = isset($holidayInfo) && $holidayInfo['is_holiday'];
          ?>
          
          <?php if ($isHoliday): ?>
            <!-- Holiday Notice -->
            <div class="p-5 text-center">
              <div class="mb-4">
                <i class="<?= $holidayInfo['icon'] ?> text-<?= $holidayInfo['color'] ?>" style="font-size: 4rem; opacity: 0.3;"></i>
              </div>
              <h4 class="fw-bold text-<?= $holidayInfo['color'] ?> mb-3">
                <?= esc($holidayInfo['description']) ?>
              </h4>
              <p class="text-muted mb-4">
                <?php 
                helper('holiday');
                echo format_holiday_date($rekapDate ?? date('Y-m-d')); 
                ?>
              </p>
              <div class="alert alert-<?= $holidayInfo['color'] ?> alert-dismissible fade show d-inline-block" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <?php if ($holidayInfo['type'] == 'holiday'): ?>
                  <strong>Tidak ada kegiatan belajar mengajar</strong> pada hari ini.
                <?php else: ?>
                  <strong>Akhir pekan</strong> - Tidak ada jadwal pelajaran.
                <?php endif; ?>
              </div>
              
              <?php if (!empty($todaySchedule)): ?>
                <div class="mt-4 pt-4 border-top">
                  <p class="text-muted mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Meskipun hari libur, terdapat <?= count($todaySchedule) ?> jadwal yang terdaftar di sistem.
                  </p>
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#holidayScheduleCollapse">
                    <i class="bi bi-eye me-1"></i> Lihat Jadwal
                  </button>
                </div>
              <?php endif; ?>
            </div>
            
            <?php if (!empty($todaySchedule)): ?>
              <!-- Collapsible schedule list for holidays -->
              <div class="collapse" id="holidayScheduleCollapse">
                <div class="border-top">
            <?php endif; ?>
          <?php elseif (empty($todaySchedule)): ?>
            <div class="p-5 text-center text-muted">
              <i class="bi bi-calendar-x fs-1 opacity-50 mb-3 d-block"></i>
              <h6 class="fw-semibold mb-2">Tidak ada jadwal pelajaran</h6>
              <p class="mb-0">Untuk <?= ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'hari ini' : 'tanggal ini' ?>.</p>
              <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-primary mt-3 rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Buat Jadwal
              </a>
            </div>
          <?php else: ?>
            <!-- Timeline Indicator -->
            <?php 
              $currentTime = date('H:i');
              $currentSessions = []; // Array untuk menyimpan semua sesi yang sedang berlangsung
              
              // Only check for current session if the selected date is today
              if (($rekapDate ?? date('Y-m-d')) == date('Y-m-d')) {
                foreach ($todaySchedule as $item) {
                  if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
                    $currentSessions[] = $item; // Simpan semua sesi yang sedang berlangsung
                  }
                }
              }
            ?>
            
            <?php if (!empty($currentSessions) && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
              <div class="alert alert-info alert-dismissible fade show m-3 mb-0 rounded-3" role="alert">
                <div class="d-flex align-items-center">
                  <i class="bi bi-bell-fill fs-5 me-2"></i>
                  <div class="flex-grow-1">
                    <strong>Sesi Berlangsung:</strong> 
                    <?php if (count($currentSessions) == 1): ?>
                      <?= esc($currentSessions[0]['subject_name']) ?> - <?= esc($currentSessions[0]['class_name']) ?>
                      (<?= substr($currentSessions[0]['start_time'], 0, 5) ?> - <?= substr($currentSessions[0]['end_time'], 0, 5) ?>)
                    <?php else: ?>
                      <?= count($currentSessions) ?> sesi sedang berlangsung
                    <?php endif; ?>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              </div>
            <?php endif; ?>
            
            <div class="list-group list-group-flush">
              <?php 
              $sessionCount = 0;
              foreach ($todaySchedule as $item): 
                $sessionCount++;
                
                // Check if this session is currently running
                $isCurrent = false;
                foreach ($currentSessions as $currentSession) {
                  if ($item['id'] == $currentSession['id']) {
                    $isCurrent = true;
                    break;
                  }
                }
                
                // Get user info from session
                $userRole = session()->get('user')['role_id'] ?? null;
                $userId = session()->get('user')['id'] ?? null;
                $relatedId = session()->get('user')['related_id'] ?? null;
                
                // Calculate session JP
                $start = strtotime($item['start_time']);
                $end = strtotime($item['end_time']);
                $itemJP = hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                
                // Determine session status (only for today)
                $now = strtotime($currentTime);
                $sessionStatus = '';
                
                if (($rekapDate ?? date('Y-m-d')) == date('Y-m-d')) {
                  if ($isCurrent) {
                    $sessionStatus = 'current';
                  } elseif ($now < $start) {
                    $sessionStatus = 'upcoming';
                  } elseif ($now > $end) {
                    $sessionStatus = 'completed';
                  }
                }
              ?>
                <div class="list-group-item py-3 agenda-item <?= $isCurrent ? 'current-session' : '' ?>" 
                     data-session-status="<?= $sessionStatus ?>">
                  <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="d-flex align-items-start flex-grow-1">
                      <!-- Session Number -->
                      <div class="session-number me-3 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" 
                             style="width: 36px; height: 36px; font-weight: 600;">
                          <?= $sessionCount ?>
                        </div>
                        <?php if ($isCurrent): ?>
                          <div class="mt-1">
                            <span class="badge bg-danger animate-pulse" style="font-size: 0.6rem;">
                              LIVE
                            </span>
                          </div>
                        <?php endif; ?>
                      </div>
                      
                      <!-- Session Details -->
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                          <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold">
                              <?= esc($item['subject_name']) ?>
                            </h6>
                            <div class="d-flex align-items-center gap-2 mt-1">
                              <span class="badge bg-primary py-1 px-2 shadow-sm font-monospace" style="font-size: 0.8rem;">
                                <i class="bi bi-clock me-1"></i>
                                <?= substr($item['start_time'], 0, 5) ?> - <?= substr($item['end_time'], 0, 5) ?>
                              </span>
                              <span class="badge bg-secondary bg-opacity-10 text-secondary py-1 px-2">
                                <i class="bi bi-hourglass-split me-1"></i>
                                <?= $itemJP ?> JP
                              </span>
                            </div>
                          </div>
                          
                          <!-- Quick Actions -->
                          <div class="dropdown ms-2">
                            <button class="btn btn-sm btn-outline-secondary border-0 p-1" type="button" data-bs-toggle="dropdown" title="Aksi">
                              <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                              <li>
                                <a class="dropdown-item" href="<?= site_url('admin/schedules/edit/' . $item['id']) ?>">
                                  <i class="bi bi-pencil me-2"></i> Edit Jadwal
                                </a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="<?= site_url('admin/teaching-journal?class_id=' . $item['class_id'] . '&subject_id=' . $item['subject_id']) ?>">
                                  <i class="bi bi-eye me-2"></i> Lihat Jurnal
                                </a>
                              </li>
                              <li><hr class="dropdown-divider"></li>
                              <li>
                                <a class="dropdown-item" href="<?= site_url('admin/attendance?class_id=' . $item['class_id'] . '&date=' . ($rekapDate ?? date('Y-m-d'))) ?>">
                                  <i class="bi bi-calendar-check me-2"></i> Presensi Kelas
                                </a>
                              </li>
                            </ul>
                          </div>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="row mt-2">
                          <div class="col-md-6">
                            <small class="text-muted d-block">
                              <i class="bi bi-door-closed me-1"></i> 
                              <strong>Kelas:</strong> <?= esc($item['class_name']) ?>
                            </small>
                            <small class="text-muted d-block mt-1">
                              <i class="bi bi-person me-1"></i> 
                              <strong>Guru:</strong> <?= esc($item['teacher_name']) ?>
                            </small>
                          </div>
                          <div class="col-md-6 text-md-end">
                            <?php if ($sessionStatus == 'upcoming' && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
                              <?php
                                $timeDiff = ($start - $now) / 60;
                                if ($timeDiff > 0 && $timeDiff <= 30):
                              ?>
                                <span class="badge bg-warning text-dark animate-pulse">
                                  <i class="bi bi-alarm me-1"></i>
                                  Dimulai dalam <?= round($timeDiff) ?> menit
                                </span>
                              <?php endif; ?>
                            <?php elseif ($sessionStatus == 'completed'): ?>
                              <span class="badge bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle me-1"></i>
                                Selesai
                              </span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <!-- Summary Footer -->
            <div class="border-top p-3 bg-light bg-opacity-10">
              <div class="row text-center">
                <div class="col-4">
                  <div class="h5 mb-0 fw-bold text-primary"><?= count($todaySchedule) ?></div>
                  <small class="text-muted">Total Sesi</small>
                </div>
                <div class="col-4">
                  <div class="h5 mb-0 fw-bold text-success"><?= $totalJP ?></div>
                  <small class="text-muted">Total JP</small>
                </div>
                <div class="col-4">
                  <div class="h5 mb-0 fw-bold text-info">
                    <?= count($currentSessions) ?>
                  </div>
                  <small class="text-muted">Sesi Berlangsung</small>
                </div>
              </div>
            </div>
            
            <?php if ($isHoliday && !empty($todaySchedule)): ?>
                  </div>
                </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0 py-3">
          <div class="d-flex justify-content-between align-items-center">
            <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">
              <i class="bi bi-gear me-1"></i> Kelola Semua Jadwal
            </a>
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Admin - Semua Kelas
            </small>
          </div>
        </div>
      </div>
      
      <?= $this->include('dashboard/_absence_rekap') ?>
    </div>

    <!-- Right Column: Agendas & Notifications -->
    <div class="col-lg-4">
      <div class="modern-card mb-4">
        <div class="modern-card-header">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-calendar-event-fill me-2 text-primary"></i>
            Agenda Terdekat
          </h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($upcomingAgendas)): ?>
            <div class="p-4 text-center">
              <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
              <p class="text-muted mt-3 mb-0">Belum ada agenda terdekat</p>
              <a href="<?= site_url('admin/agendas/create') ?>" class="btn btn-sm btn-outline-primary mt-3 rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Buat Agenda
              </a>
            </div>
          <?php else: ?>
            <?php foreach ($upcomingAgendas as $agenda): 
              $daysDiff = floor((strtotime($agenda['date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
              $isToday = $daysDiff == 0;
              $isTomorrow = $daysDiff == 1;
            ?>
              <div class="agenda-item">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h6 class="mb-0 fw-bold flex-grow-1 me-2"><?= esc($agenda['title']) ?></h6>
                  <span class="time-badge <?= $isToday ? 'bg-warning' : ($isTomorrow ? 'bg-info' : 'bg-primary') ?>">
                    <?php if ($isToday): ?>
                      HARI INI
                    <?php elseif ($isTomorrow): ?>
                      BESOK
                    <?php else: ?>
                      <?= date('d/m', strtotime($agenda['date'])) ?>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="small text-muted mb-2">
                  <i class="bi bi-clock me-1"></i>
                  <?= $agenda['start_time'] ?> - <?= $agenda['end_time'] ?>
                  <?php if ($daysDiff > 0): ?>
                    <span class="ms-2">
                      <i class="bi bi-calendar me-1"></i>
                      <?= $daysDiff ?> hari lagi
                    </span>
                  <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="class-badge">
                    <?= $agenda['class_id'] ? esc($agenda['class_name']) : 'Semua Kelas' ?>
                  </span>
                  <a href="<?= site_url('admin/agendas/edit/' . $agenda['id']) ?>" 
                     class="btn btn-sm btn-outline-secondary border-0">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0 py-3 px-3" style="border-radius: 0 0 16px 16px;">
          <div class="d-flex justify-content-between gap-2">
            <a href="<?= site_url('admin/agendas') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-4 flex-grow-1">
              Lihat Semua
            </a>
            <a href="<?= site_url('admin/agendas/create') ?>" class="btn btn-primary btn-sm rounded-pill px-4 flex-grow-1">
              <i class="bi bi-plus me-1"></i> Baru
            </a>
          </div>
        </div>
      </div>

      <!-- System Notifications -->
      <div class="modern-card">
        <div class="modern-card-header">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-bell-fill me-2 text-warning"></i>
            Notifikasi Sistem
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (!empty($systemNotifications)): ?>
              <?php foreach ($systemNotifications as $notification): ?>
                <?php
                  // Map notification type to Bootstrap color
                  $colorMap = [
                    'success' => 'success',
                    'info' => 'info',
                    'warning' => 'warning',
                    'danger' => 'danger',
                  ];
                  $color = $colorMap[$notification['type']] ?? 'secondary';
                ?>
                <div class="list-group-item py-3 px-4 border-0">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> p-2">
                        <i class="bi bi-<?= $notification['icon'] ?>"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <h6 class="mb-1 fw-bold"><?= esc($notification['title']) ?></h6>
                      <p class="mb-0 small text-muted"><?= esc($notification['message']) ?></p>
                      <?php if (!empty($notification['url'])): ?>
                        <div class="mt-2">
                          <a href="<?= esc($notification['url']) ?>" class="btn btn-sm btn-<?= $color ?> py-1 px-3 text-white" style="font-size: 0.78rem;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Sekarang
                          </a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="list-group-item py-3 px-4 border-0">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-2">
                      <i class="bi bi-check-circle"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-1 fw-bold">Sistem Berjalan Normal</h6>
                    <p class="mb-0 small text-muted">Tidak ada notifikasi sistem saat ini</p>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              <i class="bi bi-clock-history me-1"></i>
              Diperbarui: <?= date('H:i') ?>
            </small>
          </div>
        </div>
      </div>

      <!-- Session Manager Widget -->
      <div class="modern-card mt-4">
        <div class="modern-card-header d-flex justify-content-between align-items-center py-3 px-4">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-database-fill me-2 text-info"></i>
            Manajemen Sesi
          </h5>
          <button class="btn btn-outline-secondary btn-sm" id="btnRefreshSession" title="Refresh">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        </div>
        <div class="card-body px-4 py-3">
          <div class="row g-3 mb-3">
            <div class="col-4 text-center">
              <div class="fw-bold fs-5 text-primary" id="statActive">-</div>
              <div class="small text-muted">Aktif</div>
            </div>
            <div class="col-4 text-center">
              <div class="fw-bold fs-5 text-danger" id="statExpired">-</div>
              <div class="small text-muted">Expired</div>
            </div>
            <div class="col-4 text-center">
              <div class="fw-bold fs-5 text-secondary" id="statSize">-</div>
              <div class="small text-muted">Ukuran</div>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
              <span>Proporsi expired</span>
              <span id="statPercent">-</span>
            </div>
            <div class="progress" style="height:6px;">
              <div id="sessionProgressBar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
          </div>
          <div id="sessionAlert" class="alert py-2 px-3 small d-none mb-3"></div>
          <button class="btn btn-danger btn-sm w-100" id="btnCleanSession" disabled>
            <i class="bi bi-trash3 me-1"></i> Bersihkan Sesi Expired
          </button>
          <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">
            <i class="bi bi-info-circle me-1"></i>Hanya menghapus sesi tidak aktif (&gt;<?= config('Session')->expiration / 60 ?> menit). Pengguna aktif tidak terpengaruh.
          </p>
        </div>
      </div>

    </div><!-- end col-lg-4 -->
  </div><!-- end row -->

  <!-- Statistik Siswa per Kelas -->
  <?= $this->include('dashboard/_student_stats_table') ?>

  <!-- JavaScript for Charts -->
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard every 10 minutes
    setTimeout(function() {
      window.location.reload();
    }, 600000);

    // Check if schedule list is scrollable and add fade effect
    const scheduleList = document.querySelector('.schedule-card .list-group-flush');
    if (scheduleList) {
      function checkScrollable() {
        if (scheduleList.scrollHeight > scheduleList.clientHeight) {
          scheduleList.classList.add('scrollable');
        } else {
          scheduleList.classList.remove('scrollable');
        }
      }
      
      checkScrollable();
      window.addEventListener('resize', checkScrollable);
      
      // Add scroll indicator
      scheduleList.addEventListener('scroll', function() {
        const isAtBottom = scheduleList.scrollHeight - scheduleList.scrollTop <= scheduleList.clientHeight + 10;
        if (isAtBottom) {
          scheduleList.classList.remove('scrollable');
        } else {
          scheduleList.classList.add('scrollable');
        }
      });
    }
  });
  </script>

<script>
$(function() {
  const sessionInfoUrl  = '<?= base_url('admin/session-info') ?>';
  const sessionCleanUrl = '<?= base_url('admin/session-clean') ?>';
  const csrfName = '<?= csrf_token() ?>';
  let csrfHash   = '<?= csrf_hash() ?>';

  function loadSessionStats() {
    $('#btnRefreshSession').prop('disabled', true).find('i').addClass('spin');
    $.getJSON(sessionInfoUrl, function(d) {
      $('#statActive').text(d.active);
      $('#dashOnlineUsers').text(d.active);
      $('#statExpired').text(d.expired);
      $('#statSize').text(d.size_label);
      const pct = d.total > 0 ? Math.round((d.expired / d.total) * 100) : 0;
      $('#statPercent').text(pct + '%');
      const bar = $('#sessionProgressBar');
      bar.css('width', pct + '%').removeClass('bg-success bg-warning bg-danger');
      if (pct >= 70) bar.addClass('bg-danger');
      else if (pct >= 40) bar.addClass('bg-warning');
      else bar.addClass('bg-success');
      const $alert = $('#sessionAlert');
      if (d.expired === 0) {
        $alert.removeClass('alert-warning alert-danger d-none').addClass('alert-success')
              .html('<i class="bi bi-check-circle"></i> Tidak ada sesi expired. Database bersih.');
        $('#btnCleanSession').prop('disabled', true);
      } else if (pct >= 70) {
        $alert.removeClass('alert-success alert-warning d-none').addClass('alert-danger')
              .html('<i class="bi bi-exclamation-triangle"></i> <strong>' + d.expired + ' sesi expired</strong> (' + pct + '%). Disarankan segera dibersihkan.');
        $('#btnCleanSession').prop('disabled', false);
      } else {
        $alert.removeClass('alert-success alert-danger d-none').addClass('alert-warning')
              .html('<i class="bi bi-info-circle"></i> Terdapat <strong>' + d.expired + ' sesi expired</strong> (' + pct + '%). Bisa dibersihkan kapan saja.');
        $('#btnCleanSession').prop('disabled', false);
      }
    }).always(function() {
      $('#btnRefreshSession').prop('disabled', false).find('i').removeClass('spin');
    });
  }

  $('#btnRefreshSession').on('click', loadSessionStats);

  $('#btnCleanSession').on('click', function() {
    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Membersihkan...');
    const data = {};
    data[csrfName] = csrfHash;
    $.ajax({
      url: sessionCleanUrl, type: 'POST', data: data, dataType: 'json',
      success: function(res) {
        csrfHash = res.csrf_hash || csrfHash;
        $('#sessionAlert').removeClass('alert-warning alert-danger d-none').addClass('alert-success')
          .html('<i class="bi bi-check-circle"></i> ' + res.message);
        $btn.html('<i class="bi bi-trash3"></i> Bersihkan Sesi Expired');
        loadSessionStats();
      },
      error: function() {
        $('#sessionAlert').removeClass('d-none').addClass('alert-danger')
          .html('<i class="bi bi-x-circle"></i> Gagal membersihkan sesi. Coba lagi.');
        $btn.prop('disabled', false).html('<i class="bi bi-trash3"></i> Bersihkan Sesi Expired');
      }
    });
  });

  loadSessionStats();
});
</script>
<style>
.spin { animation: spin 0.8s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<?= $this->endSection() ?>
