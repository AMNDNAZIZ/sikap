<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .dashboard-guru {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

  .wali-kelas-card {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border-radius: 20px;
    border: none;
    color: white;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
  }

  .wali-kelas-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
  }

  .teacher-stats-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid;
  }

  .teacher-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  }

  .quick-action-btn {
    border-radius: 12px;
    padding: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    text-align: left;
  }

  .quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
  }

  .quick-action-btn i {
    font-size: 1.25rem;
  }

  .announcement-content {
    font-size: 0.9rem;
    line-height: 1.6;
    color: #212529 !important;
  }

  .announcement-content::-webkit-scrollbar {
    width: 6px;
  }

  .announcement-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .announcement-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
  }

  .announcement-content::-webkit-scrollbar-thumb:hover {
    background: #555;
  }

  .bg-gradient {
    position: relative;
    overflow: hidden;
  }

  .bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }
  
  .bg-gradient h5,
  .bg-gradient h5 *,
  .bg-gradient i {
    color: #ffffff !important;
  }
  
  .card-header[style*="gradient"] h5,
  .card-header[style*="gradient"] h5 span,
  .card-header[style*="gradient"] i {
    color: #ffffff !important;
  }
  
  .card-body.bg-white {
    background-color: #ffffff !important;
  }
  
  .card-body.bg-white * {
    color: inherit;
  }
  
  .card-body.bg-white h6 {
    color: #212529 !important;
  }

  @media (max-width: 767.98px) {
    .welcome-header {
      padding: 1.5rem;
      border-radius: 16px;
    }

    .clock-box {
      padding: 0.75rem 1rem;
    }

    .teacher-stats-card {
      margin-bottom: 1rem;
    }
    
    .announcement-content {
      max-height: 150px !important;
    }
  }
</style>

<div class="container-fluid p-0 dashboard-guru">
  <!-- Welcome Header -->
  <div class="welcome-header">
    <div class="row align-items-center welcome-header-content">
      <div class="col-md-8">
        <h2 class="mb-2 fw-bold">Dashboard Guru</h2>
        <p class="mb-0 opacity-90">Selamat datang, <strong><?= esc($teacher['name'] ?? $user['username']) ?></strong>! 👨‍🏫</p>
        <?php if (!empty($teacher)): ?>
          <div class="mt-2">
            <span class="badge bg-light text-dark me-2">
              <i class="bi bi-person-badge me-1"></i> NIP: <?= $teacher['nip'] ?? '-' ?>
            </span>
            <?php if (!empty($teacher['phone'])): ?>
              <span class="badge bg-light text-dark">
                <i class="bi bi-telephone me-1"></i> <?= $teacher['phone'] ?>
              </span>
            <?php endif; ?>
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

  <!-- Teacher Stats Row -->
  <div class="row g-3 g-md-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="teacher-stats-card" style="border-left-color: #667eea;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-primary bg-opacity-10 p-2 rounded-3 me-3">
              <i class="bi bi-calendar-check text-primary fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="small text-muted mb-1">Sesi Hari Ini</div>
              <div class="h4 mb-0 fw-bold"><?= $teacherStats['total_sessions_today'] ?? 0 ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="teacher-stats-card" style="border-left-color: #11998e;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-success bg-opacity-10 p-2 rounded-3 me-3">
              <i class="bi bi-clock-history text-success fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="small text-muted mb-1">Total JP</div>
              <div class="h4 mb-0 fw-bold"><?= $teacherStats['total_jp_today'] ?? 0 ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="teacher-stats-card" style="border-left-color: #f093fb;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-warning bg-opacity-10 p-2 rounded-3 me-3">
              <i class="bi bi-door-open text-warning fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="small text-muted mb-1">Kelas Diajar</div>
              <div class="h4 mb-0 fw-bold"><?= $teacherStats['unique_classes'] ?? 0 ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="teacher-stats-card" style="border-left-color: #4facfe;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-info bg-opacity-10 p-2 rounded-3 me-3">
              <i class="bi bi-book text-info fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="small text-muted mb-1">Mapel Diajar</div>
              <div class="h4 mb-0 fw-bold"><?= $teacherStats['unique_subjects'] ?? 0 ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
      <!-- Wali Kelas Card (if applicable) -->
      <?php if (!empty($waliClass)): ?>
        <div class="wali-kelas-card mb-4">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
              <div class="flex-grow-1">
                <div class="small opacity-90 text-uppercase mb-2" style="letter-spacing: 1px;">
                  <i class="bi bi-award me-1"></i> Wali Kelas
                </div>
                <h4 class="fw-bold mb-2">
                  <i class="bi bi-person-badge-fill me-2"></i>
                  <?= esc($waliClass['name']) ?>
                </h4>
                <div class="d-flex gap-3 mb-3">
                  <div>
                    <span class="fw-bold fs-5"><?= $waliClassStats['total'] ?? 0 ?></span>
                    <span class="small opacity-90"> Total Siswa</span>
                  </div>
                  <div>
                    <span class="fw-bold fs-5"><?= $waliClassStats['L'] ?? 0 ?></span>
                    <span class="small opacity-90"> Laki-laki</span>
                  </div>
                  <div>
                    <span class="fw-bold fs-5"><?= $waliClassStats['P'] ?? 0 ?></span>
                    <span class="small opacity-90"> Perempuan</span>
                  </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <a href="<?= site_url('admin/attendance/view?class_id=' . $waliClass['id']) ?>" 
                     class="btn btn-light btn-sm rounded-pill px-3">
                    <i class="bi bi-calendar-check me-1"></i> Presensi
                  </a>
                  <a href="<?= site_url('admin/wali-kelas-students') ?>" 
                     class="btn btn-light btn-sm rounded-pill px-3">
                    <i class="bi bi-people me-1"></i> Data Siswa
                  </a>
                  <a href="<?= site_url('admin/grades?class_id=' . $waliClass['id']) ?>" 
                     class="btn btn-light btn-sm rounded-pill px-3">
                    <i class="bi bi-clipboard-data me-1"></i> Nilai
                  </a>
                </div>
              </div>
              <div class="text-center ms-3">
                <div class="rounded-circle bg-white p-3 d-inline-block">
                  <i class="bi bi-person-badge text-success" style="font-size: 2.5rem;"></i>
                </div>
                <div class="mt-2">
                  <small class="opacity-90">Wali Kelas</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Schedules Section -->
      <div class="row g-4 mb-4">
        <!-- Personal Teaching Schedule -->
        <div class="col-12">
          <?= view('dashboard/_today_schedule', [
              'schedules' => $personalSchedule, 
              'scheduleTitle' => 'Jadwal Mengajar Saya', 
              'rekapDate' => $rekapDate,
              'scheduleType' => 'teaching',
              'hideTeacher' => true,  // Sembunyikan kolom guru karena ini jadwal guru itu sendiri
              'holidayInfo' => $holidayInfo ?? null
          ]) ?>
        </div>
        
        <!-- Wali Class Schedule (if applicable) -->
        <?php if (!empty($waliClass) && !empty($waliClassSchedule)): ?>
          <div class="col-12">
            <?= view('dashboard/_today_schedule', [
                'schedules' => $waliClassSchedule, 
                'scheduleTitle' => 'Jadwal Kelas ' . esc($waliClass['name']), 
                'rekapDate' => $rekapDate,
                'scheduleType' => 'homeroom',
                'hideClass' => true,  // Sembunyikan kolom kelas karena sudah jelas dari judul
                'holidayInfo' => $holidayInfo ?? null
            ]) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Absence Rekap -->
      <?= $this->include('dashboard/_absence_rekap') ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
      <!-- Latest Announcement from Admin/Kepsek -->
      <?php if (!empty($latestAnnouncement)): ?>
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header py-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="mb-0 fw-bold" style="color: #ffffff !important;">
              <i class="bi bi-megaphone-fill me-2" style="color: #ffffff !important;"></i>
              <span style="color: #ffffff !important;">Pengumuman Terbaru</span>
            </h5>
          </div>
          <div class="card-body p-4" style="background-color: #ffffff;">
            <div class="mb-3">
              <h6 class="fw-bold mb-2" style="color: #212529 !important;"><?= esc($latestAnnouncement['title']) ?></h6>
              <div class="small text-muted mb-3">
                <i class="bi bi-person-circle me-1"></i>
                <?= esc($latestAnnouncement['creator_name'] ?? 'Admin') ?>
                <span class="mx-2">•</span>
                <i class="bi bi-clock me-1"></i>
                <?php 
                  $createdTime = strtotime($latestAnnouncement['created_at']);
                  $now = time();
                  $diff = $now - $createdTime;
                  
                  if ($diff < 3600) {
                    echo floor($diff / 60) . ' menit yang lalu';
                  } elseif ($diff < 86400) {
                    echo floor($diff / 3600) . ' jam yang lalu';
                  } elseif ($diff < 604800) {
                    echo floor($diff / 86400) . ' hari yang lalu';
                  } else {
                    echo date('d M Y', $createdTime);
                  }
                ?>
              </div>
            </div>
            
            <div class="announcement-content mb-3" style="max-height: 200px; overflow-y: auto; color: #212529 !important;">
              <?php 
                $content = strip_tags($latestAnnouncement['content']);
                if (strlen($content) > 300) {
                  echo nl2br(esc(substr($content, 0, 300))) . '...';
                } else {
                  echo nl2br(esc($content));
                }
              ?>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge bg-primary">
                <i class="bi bi-tag-fill me-1"></i>
                <?php 
                  $targets = explode(',', $latestAnnouncement['target']);
                  $targetLabels = [];
                  foreach ($targets as $t) {
                    $t = trim($t);
                    if ($t == 'all') $targetLabels[] = 'Semua';
                    elseif ($t == 'guru') $targetLabels[] = 'Guru';
                    elseif ($t == 'siswa') $targetLabels[] = 'Siswa';
                    elseif ($t == 'ortu') $targetLabels[] = 'Orang Tua';
                  }
                  echo implode(', ', $targetLabels);
                ?>
              </span>
              <a href="<?= site_url('admin/announcement') ?>" class="btn btn-sm btn-outline-primary">
                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Quick Actions Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-lightning-fill me-2 text-warning"></i>
            Akses Cepat Guru
          </h5>
        </div>
        <div class="card-body p-3">
          <div class="d-grid gap-2">
            <a href="<?= site_url('admin/attendance') ?>" class="quick-action-btn btn btn-primary">
              <i class="bi bi-calendar-check me-2"></i> Input Presensi
            </a>
            <a href="<?= site_url('admin/teaching-journal') ?>" class="quick-action-btn btn btn-info text-white">
              <i class="bi bi-journal-text me-2"></i> Jurnal Mengajar
            </a>
            <a href="<?= site_url('admin/scores') ?>" class="quick-action-btn btn btn-success text-white">
              <i class="bi bi-clipboard-data me-2"></i> Input Nilai
            </a>
            <a href="<?= site_url('admin/administrasi-guru/modul-ajar') ?>" class="quick-action-btn btn btn-secondary text-white">
              <i class="bi bi-folder me-2"></i> Modul Ajar
            </a>
            <a href="<?= site_url('admin/chat') ?>" class="quick-action-btn btn btn-warning text-white">
              <i class="bi bi-chat-dots me-2"></i> Ruang Chat
            </a>
          </div>
        </div>
      </div>

      <!-- Upcoming Agendas -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0 fw-bold">
            <i class="bi bi-calendar-event me-2 text-primary"></i>
            Agenda Terdekat
          </h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($upcomingAgendas)): ?>
            <div class="p-4 text-center text-muted">
              <i class="bi bi-calendar-x opacity-50" style="font-size: 2rem;"></i>
              <p class="mt-2 mb-0">Belum ada agenda terdekat</p>
            </div>
          <?php else: ?>
            <?php foreach ($upcomingAgendas as $agenda): 
              $daysDiff = floor((strtotime($agenda['date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
              $isToday = $daysDiff == 0;
              $isTomorrow = $daysDiff == 1;
              $isMine = $agenda['created_by'] == $user['id'];
            ?>
              <div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h6 class="mb-0 fw-bold flex-grow-1 me-2"><?= esc($agenda['title']) ?></h6>
                  <span class="badge <?= $isToday ? 'bg-warning' : ($isTomorrow ? 'bg-info' : 'bg-primary') ?>">
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
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="badge bg-light text-dark">
                    <?= $agenda['class_id'] ? esc($agenda['class_name']) : 'Semua Kelas' ?>
                  </span>
                  <?php if ($isMine): ?>
                    <small class="text-success">
                      <i class="bi bi-check-circle me-1"></i> Buatan Anda
                    </small>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0 py-3">
          <a href="<?= site_url('admin/agendas') ?>" class="btn btn-outline-primary btn-sm w-100">
            Lihat Semua Agenda
          </a>
        </div>
      </div>
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

// Auto-refresh dashboard every 10 minutes
setTimeout(function() {
  window.location.reload();
}, 600000);
</script>

<?= $this->endSection() ?>