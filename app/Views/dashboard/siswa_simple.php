<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .dashboard-siswa {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .welcome-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
  }

  .welcome-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }

  .welcome-header-content {
    position: relative;
    z-index: 1;
  }

  .clock-box {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1rem 1.5rem;
  }

  .student-info-card {
    border-radius: 20px;
    border: none;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    background: white;
  }

  .attendance-summary {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  @media (max-width: 767.98px) {
    .welcome-header {
      padding: 1.5rem;
      border-radius: 16px;
    }

    .clock-box {
      padding: 0.75rem 1rem;
    }
  }
</style>

<div class="container-fluid p-0 dashboard-siswa">
  <!-- Welcome Header -->
  <div class="welcome-header">
    <div class="row align-items-center welcome-header-content">
      <div class="col-md-8">
        <h2 class="mb-2 fw-bold"><?= $user['role_id'] == 5 ? 'Dashboard Siswa' : 'Dashboard Orang Tua' ?></h2>
        <p class="mb-0 opacity-90">Selamat datang, <strong><?= esc($student['name'] ?? $user['username']) ?></strong>! 
          <?= $user['role_id'] == 5 ? '👨‍🎓' : '👨‍👩‍👧‍👦' ?>
        </p>
        <?php if (!empty($student)): ?>
          <div class="mt-2">
            <span class="badge bg-light text-dark me-2">
              <i class="bi bi-person-vcard me-1"></i> NIS: <?= $student['nis'] ?? '-' ?>
            </span>
            <span class="badge bg-light text-dark me-2">
              <i class="bi bi-gender-ambiguous me-1"></i> <?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <div class="clock-box d-inline-block">
          <div class="h4 mb-1 fw-bold realtime-clock"><?= date('H:i:s') ?></div>
          <div class="small opacity-90"><?= date('d F Y') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Schedule -->
    <div class="col-lg-8">
      <!-- Date Picker Section -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Date Picker Form -->
            <form action="" method="get" class="flex-grow-1" style="max-width: 400px;">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-calendar3 text-primary"></i>
                </span>
                <input type="date" name="date" class="form-control border-start-0 ps-0" 
                       value="<?= $rekapDate ?? date('Y-m-d') ?>" 
                       onchange="this.form.submit()"
                       max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-search"></i> Lihat
                </button>
              </div>
            </form>
            
            <!-- Date Badge -->
            <div class="d-flex align-items-center gap-2">
              <?php if (($rekapDate ?? date('Y-m-d')) != date('Y-m-d')): ?>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-arrow-clockwise me-1"></i> Hari Ini
                </a>
              <?php endif; ?>
              <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fs-6 fw-normal">
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
        <div class="alert alert-<?= $holidayInfo['color'] ?> border-0 shadow-sm mb-4" role="alert">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <i class="bi <?= $holidayInfo['icon'] ?> fs-2"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h5 class="alert-heading mb-1 fw-bold">
                <?= $holidayInfo['type'] == 'holiday' ? 'Hari Libur' : 'Akhir Pekan' ?>
              </h5>
              <p class="mb-0"><?= esc($holidayInfo['description']) ?></p>
              <small class="opacity-75">Tidak ada jadwal pelajaran pada hari ini.</small>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Class Schedule -->
      <?php if (!empty($todaySchedule)): ?>
        <?= view('dashboard/_today_schedule', [
            'schedules' => $todaySchedule, 
            'scheduleTitle' => ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'Jadwal Pelajaran Hari Ini' : 'Jadwal Pelajaran', 
            'rekapDate' => $rekapDate,
            'holidayInfo' => $holidayInfo ?? null
        ]) ?>
      <?php else: ?>
        <?php if (empty($holidayInfo) || !$holidayInfo['is_holiday']): ?>
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-5 text-center">
              <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
              <h5 class="mt-3 text-muted">Tidak ada jadwal pelajaran</h5>
              <p class="text-muted mb-0">Untuk <?= ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'hari ini' : 'tanggal ini' ?>.</p>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Announcement -->
      <?php if (!empty($latestAnnouncement)): ?>
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
              <i class="bi bi-megaphone me-2 text-warning"></i>
              Pengumuman Terkini
            </h5>
          </div>
          <div class="card-body">
            <h6 class="fw-bold text-dark mb-2"><?= esc($latestAnnouncement['title']) ?></h6>
            <div class="text-muted mb-3" style="line-height: 1.7;">
              <?= nl2br(esc($latestAnnouncement['content'])) ?>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">
                <i class="bi bi-person-circle me-1"></i>
                Oleh: <strong><?= esc($latestAnnouncement['creator_name']) ?></strong>
              </small>
              <small class="text-muted">
                <i class="bi bi-clock me-1"></i>
                <?= date('d M Y', strtotime($latestAnnouncement['created_at'])) ?>
              </small>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: Info & Agendas -->
    <div class="col-lg-4">
      <!-- Student Info Card -->
      <div class="student-info-card mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3">
            <i class="bi bi-info-circle me-2 text-primary"></i>
            Informasi <?= $user['role_id'] == 5 ? 'Siswa' : 'Anak' ?>
          </h5>
          
          <?php if (!empty($student)): ?>
            <div class="mb-3">
              <div class="small text-muted mb-1">Nama Lengkap</div>
              <div class="fw-bold"><?= esc($student['name']) ?></div>
            </div>
            
            <div class="mb-3">
              <div class="small text-muted mb-1">Kelas</div>
              <div class="fw-bold">
                <?php 
                // Use className from controller
                if (!empty($className)) {
                  echo esc($className);
                } else {
                  echo '<span class="text-muted">Belum ditentukan</span>';
                }
                ?>
              </div>
            </div>
            
            <div class="mb-3">
              <div class="small text-muted mb-1">Status</div>
              <div class="fw-bold text-success">
                <i class="bi bi-check-circle me-1"></i> Aktif
              </div>
            </div>
          <?php else: ?>
            <div class="text-center py-3 text-muted">
              <i class="bi bi-person-x opacity-50" style="font-size: 2rem;"></i>
              <p class="mt-2 mb-0">Data <?= $user['role_id'] == 5 ? 'siswa' : 'anak' ?> tidak ditemukan</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Attendance Summary -->
      <div class="attendance-summary mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3">
            <i class="bi bi-calendar-check me-2 text-success"></i>
            Rekapitulasi Kehadiran
          </h5>
          
          <div class="row text-center">
            <div class="col-4">
              <div class="h4 mb-0 fw-bold text-success"><?= $attSummary['H'] ?? 0 ?></div>
              <small class="text-muted">Hadir</small>
            </div>
            <div class="col-4">
              <div class="h4 mb-0 fw-bold text-warning"><?= ($attSummary['S'] ?? 0) + ($attSummary['I'] ?? 0) + ($attSummary['A'] ?? 0) ?></div>
              <small class="text-muted">Tidak Hadir</small>
            </div>
            <div class="col-4">
              <div class="h4 mb-0 fw-bold text-primary"><?= $attendanceRate ?? 100 ?>%</div>
              <small class="text-muted">Kehadiran</small>
            </div>
          </div>
          
          <div class="progress mt-3" style="height: 8px;">
            <div class="progress-bar bg-success" style="width: <?= $attendanceRate ?? 100 ?>%"></div>
          </div>
          
          <div class="mt-3">
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Bulan <?= date('F Y') ?>
            </small>
          </div>
        </div>
      </div>

      <!-- Upcoming Agendas -->
      <?php if (!empty($upcomingAgendas)): ?>
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
              <i class="bi bi-calendar-event me-2 text-primary"></i>
              Agenda Terdekat
            </h5>
          </div>
          <div class="card-body p-0">
            <?php foreach (array_slice($upcomingAgendas, 0, 3) as $agenda): ?>
              <div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h6 class="mb-0 fw-bold flex-grow-1 me-2" style="font-size: 0.9rem;"><?= esc($agenda['title']) ?></h6>
                  <span class="badge bg-primary">
                    <?= date('d/m', strtotime($agenda['date'])) ?>
                  </span>
                </div>
                <div class="small text-muted">
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
// Update current time every second
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