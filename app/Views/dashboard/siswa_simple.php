<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .dashboard-siswa {
    --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  }

  .welcome-header {
    background: var(--primary-gradient);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    color: white;
    margin-bottom: 1rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.18);
  }

  .welcome-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 250px;
    height: 250px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    pointer-events: none;
  }

  .welcome-header-content {
    position: relative;
    z-index: 1;
  }

  .clock-box {
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 0.5rem 0.85rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .student-info-card,
  .attendance-summary {
    border-radius: 14px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    background: white;
  }

  /* Quick Actions Grid for Phone & Desktop */
  .quick-menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.65rem;
    margin-bottom: 1rem;
  }
  @media (min-width: 992px) {
    .quick-menu-grid {
      grid-template-columns: repeat(8, 1fr);
      gap: 0.75rem;
    }
  }

  .quick-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 0.75rem 0.35rem 0.6rem;
    color: #1e293b;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
    user-select: none;
    -webkit-tap-highlight-color: transparent;
  }
  .quick-menu-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
    color: #0f172a;
  }
  .quick-menu-item:active {
    transform: scale(0.96);
  }
  .quick-menu-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 0.4rem;
    transition: transform 0.15s ease;
  }
  .quick-menu-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
    color: #334155;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  @media (max-width: 767.98px) {
    .welcome-header {
      padding: 1rem 1.15rem;
      border-radius: 14px;
      margin-bottom: 0.75rem;
    }
    .welcome-header h2 {
      font-size: 1.15rem !important;
    }
    .clock-box {
      padding: 0.35rem 0.65rem;
    }
    .quick-menu-icon {
      width: 40px;
      height: 40px;
      font-size: 1.2rem;
      border-radius: 10px;
    }
  }
</style>

<div class="container-fluid p-0 dashboard-siswa">
  <!-- Welcome Hero Header -->
  <div class="welcome-header">
    <div class="row align-items-center welcome-header-content g-2">
      <div class="col-8 col-md-8">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-white bg-opacity-25 text-white py-1 px-2 fw-normal" style="font-size: 0.72rem;">
            <?= $user['role_id'] == 5 ? '🎓 Siswa' : '👨‍👩‍👧‍👦 Orang Tua' ?>
          </span>
          <?php if (!empty($className)): ?>
            <span class="badge bg-white bg-opacity-25 text-white py-1 px-2 fw-normal" style="font-size: 0.72rem;">
              Kelas: <?= esc($className) ?>
            </span>
          <?php endif; ?>
        </div>
        <h2 class="mb-1 fw-bold text-white">
          Halo, <?= esc($student['name'] ?? $user['username']) ?>! 👋
        </h2>
        <?php if (!empty($student)): ?>
          <div class="small opacity-90 mt-1 d-flex flex-wrap gap-2" style="font-size: 0.78rem;">
            <span><i class="bi bi-person-vcard me-1"></i>NIS: <strong><?= $student['nis'] ?? '-' ?></strong></span>
            <span>&bull;</span>
            <span><i class="bi bi-gender-ambiguous me-1"></i><?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-4 col-md-4 text-end">
        <div class="clock-box d-inline-block text-center">
          <div class="fw-bold realtime-clock" style="font-size: 0.95rem; letter-spacing: 0.05em;"><?= date('H:i:s') ?></div>
          <div class="text-white-50" style="font-size: 0.68rem;"><?= date('d M Y') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Menu Launcher (Aplikasi Cepat / 1-Tap Access) -->
  <div class="quick-menu-grid">
    <a href="<?= base_url('siswa/belajar') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #e0f2fe; color: #0284c7;">
        <i class="bi bi-mortarboard-fill"></i>
      </div>
      <span class="quick-menu-label">Belajar</span>
    </a>

    <a href="<?= base_url('siswa/tugas') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #fef3c7; color: #d97706;">
        <i class="bi bi-clipboard-check-fill"></i>
      </div>
      <span class="quick-menu-label">Tugas</span>
    </a>

    <a href="<?= base_url('siswa/cbt') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #ede9fe; color: #7c3aed;">
        <i class="bi bi-pencil-square"></i>
      </div>
      <span class="quick-menu-label">Ujian CBT</span>
    </a>

    <a href="<?= base_url('siswa/grades') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #dcfce7; color: #16a34a;">
        <i class="bi bi-award-fill"></i>
      </div>
      <span class="quick-menu-label">Rapor & Nilai</span>
    </a>

    <a href="#jadwalSection" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #fce7f3; color: #db2777;">
        <i class="bi bi-calendar-week-fill"></i>
      </div>
      <span class="quick-menu-label">Jadwal</span>
    </a>

    <a href="<?= base_url('siswa/attendance') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #ccfbf1; color: #0d9488;">
        <i class="bi bi-calendar-check-fill"></i>
      </div>
      <span class="quick-menu-label">Presensi</span>
    </a>

    <a href="<?= base_url('siswa/ebook') ?>" class="quick-menu-item">
      <div class="quick-menu-icon" style="background: #ffedd5; color: #ea580c;">
        <i class="bi bi-book-half"></i>
      </div>
      <span class="quick-menu-label">Perpustakaan</span>
    </a>

    <?php if (is_school_swasta()): ?>
      <a href="<?= base_url('siswa/payment') ?>" class="quick-menu-item">
        <div class="quick-menu-icon" style="background: #f1f5f9; color: #475569;">
          <i class="bi bi-wallet2"></i>
        </div>
        <span class="quick-menu-label">SPP Sekolah</span>
      </a>
    <?php else: ?>
      <a href="<?= base_url('siswa/counseling') ?>" class="quick-menu-item">
        <div class="quick-menu-icon" style="background: #f1f5f9; color: #475569;">
          <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <span class="quick-menu-label">Layanan BK</span>
      </a>
    <?php endif; ?>
  </div>

  <!-- Ringkasan Kehadiran Cepat (Mobile & Desktop) -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
            <i class="bi bi-calendar-check"></i>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 0.85rem;">Kehadiran Bulan Ini</div>
            <div class="text-muted" style="font-size: 0.72rem;"><?= date('F Y') ?></div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3">
          <div class="text-center">
            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1" style="font-size:0.75rem;">
              <?= $attSummary['H'] ?? 0 ?> Hadir
            </span>
          </div>
          <div class="text-center">
            <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-2 py-1" style="font-size:0.75rem;">
              <?= ($attSummary['S'] ?? 0) + ($attSummary['I'] ?? 0) ?> Izin/Sakit
            </span>
          </div>
          <div class="text-center">
            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1" style="font-size:0.75rem;">
              <?= $attSummary['A'] ?? 0 ?> Alpa
            </span>
          </div>
          <div class="text-end ps-2 border-start">
            <div class="fw-bold text-primary" style="font-size: 0.95rem;"><?= $attendanceRate ?? 100 ?>%</div>
            <div class="text-muted" style="font-size: 0.65rem;">Tingkat Hadir</div>
          </div>
        </div>
      </div>
      <div class="progress mt-2" style="height: 5px; border-radius: 4px;">
        <div class="progress-bar bg-success" style="width: <?= $attendanceRate ?? 100 ?>%"></div>
      </div>
    </div>
  </div>

  <div class="row g-3" id="jadwalSection">
    <!-- Left Column: Schedule & Learning -->
    <div class="col-lg-8">
      <!-- Date Filter Bar -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 p-md-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <!-- Form Pilih Tanggal -->
            <form action="" method="get" class="d-flex align-items-center gap-1 flex-grow-1" style="max-width: 320px;">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">
                  <i class="bi bi-calendar3 text-primary"></i>
                </span>
                <input type="date" name="date" class="form-control border-start-0 ps-1" 
                       value="<?= $rekapDate ?? date('Y-m-d') ?>" 
                       onchange="this.form.submit()"
                       max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                <button class="btn btn-sm btn-primary" type="submit">
                  Cari
                </button>
              </div>
            </form>
            
            <!-- Quick Date Pills -->
            <div class="d-flex align-items-center gap-1">
              <?php if (($rekapDate ?? date('Y-m-d')) != date('Y-m-d')): ?>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.75rem;">
                  <i class="bi bi-arrow-clockwise me-1"></i>Hari Ini
                </a>
              <?php endif; ?>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2 fw-medium" style="font-size:0.75rem;">
                <?php 
                helper('holiday');
                $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
                         'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                $months = ['January' => 'Jan', 'February' => 'Feb', 'March' => 'Mar', 'April' => 'Apr',
                          'May' => 'Mei', 'June' => 'Jun', 'July' => 'Jul', 'August' => 'Agt',
                          'September' => 'Sep', 'October' => 'Okt', 'November' => 'Nov', 'December' => 'Des'];
                $date = $rekapDate ?? date('Y-m-d');
                echo $days[date('l', strtotime($date))] . ', ' . date('d', strtotime($date)) . ' ' . 
                     $months[date('F', strtotime($date))] . ' ' . date('Y', strtotime($date));
                ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Holiday Notice -->
      <?php if (!empty($holidayInfo) && $holidayInfo['is_holiday']): ?>
        <div class="alert alert-<?= $holidayInfo['color'] ?> border-0 shadow-sm mb-3" role="alert">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <i class="bi <?= $holidayInfo['icon'] ?> fs-3"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="alert-heading mb-0 fw-bold">
                <?= $holidayInfo['type'] == 'holiday' ? 'Hari Libur' : 'Akhir Pekan' ?>: <?= esc($holidayInfo['description']) ?>
              </h6>
              <small class="opacity-75">Tidak ada jadwal KBM pada hari ini.</small>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Class Schedule Card -->
      <?php if (!empty($todaySchedule)): ?>
        <?= view('dashboard/_today_schedule', [
            'schedules' => $todaySchedule, 
            'scheduleTitle' => ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'Jadwal Pelajaran Hari Ini' : 'Jadwal Pelajaran', 
            'rekapDate' => $rekapDate,
            'holidayInfo' => $holidayInfo ?? null
        ]) ?>
      <?php else: ?>
        <?php if (empty($holidayInfo) || !$holidayInfo['is_holiday']): ?>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4 text-center">
              <i class="bi bi-calendar-x text-muted" style="font-size: 2.5rem;"></i>
              <h6 class="mt-2 text-muted fw-bold">Tidak ada jadwal pelajaran</h6>
              <p class="text-muted small mb-0">Untuk <?= ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'hari ini' : 'tanggal ini' ?>.</p>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Announcement Card -->
      <?php if (!empty($latestAnnouncement)): ?>
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark" style="font-size: 0.88rem;">
              <i class="bi bi-megaphone me-1 text-warning"></i>
              Pengumuman Terkini
            </span>
            <small class="text-muted" style="font-size: 0.72rem;">
              <?= date('d M Y', strtotime($latestAnnouncement['created_at'])) ?>
            </small>
          </div>
          <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;"><?= esc($latestAnnouncement['title']) ?></h6>
            <div class="text-muted small mb-2" style="line-height: 1.5; font-size: 0.82rem;">
              <?= nl2br(esc($latestAnnouncement['content'])) ?>
            </div>
            <div class="text-muted" style="font-size: 0.72rem;">
              <i class="bi bi-person-circle me-1"></i>
              Oleh: <strong><?= esc($latestAnnouncement['creator_name']) ?></strong>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: Info & Agendas -->
    <div class="col-lg-4">
      <!-- Student Info Card -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3">
          <span class="fw-bold text-dark" style="font-size: 0.88rem;">
            <i class="bi bi-info-circle me-1 text-primary"></i>
            Informasi <?= $user['role_id'] == 5 ? 'Siswa' : 'Anak' ?>
          </span>
        </div>
        <div class="card-body p-3">
          <?php if (!empty($student)): ?>
            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
              <span class="text-muted small">Nama</span>
              <span class="fw-semibold small text-end"><?= esc($student['name']) ?></span>
            </div>
            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
              <span class="text-muted small">Kelas</span>
              <span class="fw-semibold small text-end"><?= esc($className ?? 'Belum ditentukan') ?></span>
            </div>
            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
              <span class="text-muted small">NIS / NISN</span>
              <span class="fw-semibold small text-end"><?= esc($student['nis'] ?? '-') ?></span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted small">Status Belajar</span>
              <span class="badge bg-success bg-opacity-10 text-success small">
                <i class="bi bi-check-circle me-1"></i>Aktif
              </span>
            </div>
          <?php else: ?>
            <div class="text-center py-2 text-muted small">
              Data tidak ditemukan
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Upcoming Agendas -->
      <?php if (!empty($upcomingAgendas)): ?>
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-white py-2 px-3">
            <span class="fw-bold text-dark" style="font-size: 0.88rem;">
              <i class="bi bi-calendar-event me-1 text-primary"></i>
              Agenda Terdekat
            </span>
          </div>
          <div class="card-body p-0">
            <?php foreach (array_slice($upcomingAgendas, 0, 3) as $agenda): ?>
              <div class="border-bottom p-2 px-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <h6 class="mb-0 fw-semibold flex-grow-1 me-2" style="font-size: 0.82rem;"><?= esc($agenda['title']) ?></h6>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">
                    <?= date('d/m', strtotime($agenda['date'])) ?>
                  </span>
                </div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  <i class="bi bi-clock me-1"></i>
                  <?= $agenda['start_time'] ?> - <?= $agenda['end_time'] ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function updateCurrentTime() {
  const now = new Date();
  const timeString = now.toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit',
    second: '2-digit'
  });
  
  document.querySelectorAll('.realtime-clock').forEach(clock => {
    clock.textContent = timeString;
  });
}
updateCurrentTime();
setInterval(updateCurrentTime, 1000);
</script>

<?= $this->endSection() ?>