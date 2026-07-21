<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
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

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Dashboard Kepala Sekolah</h3>
            <p class="text-muted mb-0">Selamat datang kembali,
                <strong><?= esc($user['fullname'] ?? $user['username']) ?></strong>!
            </p>
        </div>
        <div class="text-end">
            <div class="h5 mb-0 fw-bold realtime-clock"><?= date('H:i:s') ?></div>
            <small class="text-muted"><?= date('d F Y') ?></small>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-people-fill text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-subtitle text-muted mb-1">Total Siswa</h6>
                            <h4 class="card-title mb-0 fw-bold"><?= number_format($stats['total_students']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-person-badge-fill text-info fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-subtitle text-muted mb-1">Total Guru</h6>
                            <h4 class="card-title mb-0 fw-bold"><?= number_format($stats['total_teachers']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-door-open-fill text-warning fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-subtitle text-muted mb-1">Total Kelas</h6>
                            <h4 class="card-title mb-0 fw-bold"><?= number_format($stats['total_classes']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Schedule & Absence Rekap -->
        <div class="col-lg-7">
            <!-- Modified Schedule Card with Class Filter -->
            <div class="card border-0 shadow-sm mb-4 modern-card schedule-card teaching-schedule">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center modern-card-header" style="border-top: 4px solid #667eea;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary">
                                <i class="bi bi-person-badge me-1"></i> Jadwal Mengajar
                            </span>
                        </div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2 text-primary"></i> 
                            Jadwal Hari Ini (Seluruh Kelas)
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
                    <div class="d-flex align-items-center gap-2">
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
                              
                              $start = strtotime($item['start_time']);
                              $end = strtotime($item['end_time']);
                              $itemJP = hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                              
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
                        <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-eye me-1"></i> Lihat Jadwal
                        </a>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Kepsek - View Only
                        </small>
                    </div>
                </div>
            </div>
            <?= $this->include('dashboard/_absence_rekap') ?>
        </div>

        <!-- Right Column: Agendas -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-dark"><i class="bi bi-calendar-event-fill me-2 text-primary"></i>Agenda
                        Terdekat</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcomingAgendas)): ?>
                        <div class="p-4 text-center text-muted">Belum ada agenda terdekat.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingAgendas as $agenda): ?>
                                <div class="list-group-item py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <h6 class="mb-1 fw-bold"><?= esc($agenda['title']) ?></h6>
                                        <small
                                            class="badge bg-light text-dark border"><?= date('d/m', strtotime($agenda['date'])) ?></small>
                                    </div>
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-clock me-1"></i> <?= $agenda['start_time'] ?> -
                                        <?= $agenda['end_time'] ?>
                                    </div>
                                    <div class="small">
                                        <span
                                            class="badge bg-primary bg-opacity-10 text-primary border-primary border-opacity-10">
                                            <?= $agenda['class_id'] ? esc($agenda['class_name']) : 'Semua Kelas' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white border-0 py-3 text-center">
                    <a href="<?= site_url('admin/agendas') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua
                        Agenda</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

  <!-- Statistik Siswa per Kelas -->
  <?= $this->include('dashboard/_student_stats_table') ?>

<?= $this->endSection() ?>