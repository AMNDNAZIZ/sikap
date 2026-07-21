<div class="card border-0 shadow-sm mb-4 modern-card schedule-card <?= ($scheduleType ?? 'teaching') === 'homeroom' ? 'homeroom-schedule' : 'teaching-schedule' ?>">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center modern-card-header" style="border-top: 4px solid <?= ($scheduleType ?? 'teaching') === 'homeroom' ? '#11998e' : '#667eea' ?>;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <?php if (($scheduleType ?? 'teaching') === 'homeroom'): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-house-fill me-1"></i> Kelas Walian
                    </span>
                <?php else: ?>
                    <span class="badge bg-primary">
                        <i class="bi bi-person-badge me-1"></i> Jadwal Mengajar
                    </span>
                <?php endif; ?>
            </div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-clock-history me-2 text-primary"></i> 
                <?= $scheduleTitle ?? 'Jadwal Pelajaran' ?>
            </h5>
            <?php if (!empty($schedules)): ?>
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Total <?= count($schedules) ?> sesi pelajaran
                    <?php 
                        $totalJP = 0;
                        foreach ($schedules as $item) {
                            $totalJP += hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                        }
                        if ($totalJP > 0): 
                    ?>
                        • <span class="text-success fw-semibold"><?= $totalJP ?> JP</span>
                    <?php endif; ?>
                </small>
                <?php if (($scheduleType ?? 'teaching') === 'homeroom'): ?>
                    <div class="mt-2">
                        <small class="text-muted d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Ini adalah jadwal lengkap kelas yang Anda walikan. Anda mengajar pada beberapa mapel saja.
                        </small>
                    </div>
                <?php else: ?>
                    <div class="mt-2">
                        <small class="text-muted d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Ini adalah jadwal mengajar Anda di berbagai kelas.
                        </small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="" method="get" class="me-2">
                <div class="input-group input-group-sm">
                    <input type="date" name="date" class="form-control form-control-sm rounded-start-pill ps-3" 
                           value="<?= $rekapDate ?? date('Y-m-d') ?>" onchange="this.form.submit()">
                    <button class="btn btn-primary btn-sm rounded-end-pill px-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-filter"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?date=<?= date('Y-m-d') ?>">Hari Ini</a></li>
                    <li><a class="dropdown-item" href="?date=<?= date('Y-m-d', strtotime('+1 day')) ?>">Besok</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?date=<?= date('Y-m-d', strtotime('monday this week')) ?>">Minggu Ini</a></li>
                </ul>
            </div>
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
                
                <?php if (!empty($schedules)): ?>
                    <div class="mt-4 pt-4 border-top">
                        <p class="text-muted mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Meskipun hari libur, terdapat <?= count($schedules) ?> jadwal yang terdaftar di sistem.
                        </p>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#holidayScheduleCollapse">
                            <i class="bi bi-eye me-1"></i> Lihat Jadwal
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($schedules)): ?>
                <!-- Collapsible schedule list for holidays -->
                <div class="collapse" id="holidayScheduleCollapse">
                    <div class="border-top">
            <?php endif; ?>
        <?php elseif (empty($schedules)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1 opacity-50 mb-3 d-block"></i>
                <h6 class="fw-semibold mb-2">Tidak ada jadwal pelajaran</h6>
                <p class="mb-0">Untuk <?= ($rekapDate ?? date('Y-m-d')) == date('Y-m-d') ? 'hari ini' : 'tanggal ini' ?>.</p>
                <?php if (session()->get('user')['role_id'] == 1): ?>
                    <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-primary mt-3 rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1"></i> Buat Jadwal
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Timeline Indicator -->
            <?php 
                $currentTime = date('H:i');
                $hasCurrentSession = false;
                
                // Only check for current session if the selected date is today
                if (($rekapDate ?? date('Y-m-d')) == date('Y-m-d')) {
                    foreach ($schedules as $item) {
                        if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
                            $hasCurrentSession = $item;
                            break;
                        }
                    }
                }
            ?>
            
            <?php if ($hasCurrentSession && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
                <div class="alert alert-info alert-dismissible fade show m-3 mb-0 rounded-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-bell-fill fs-5 me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Sesi Berlangsung:</strong> <?= esc($hasCurrentSession['subject_name']) ?> 
                            (<?= substr($hasCurrentSession['start_time'], 0, 5) ?> - <?= substr($hasCurrentSession['end_time'], 0, 5) ?>)
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="list-group list-group-flush">
                <?php 
                $sessionCount = 0;
                foreach ($schedules as $item): 
                    $sessionCount++;
                    $isCurrent = ($hasCurrentSession && $item['id'] == $hasCurrentSession['id']);
                    
                    // Get user info from session
                    $userRole = session()->get('user')['role_id'] ?? null;
                    $userId = session()->get('user')['id'] ?? null;
                    $relatedId = session()->get('user')['related_id'] ?? null; // teacher_id for guru, student_id for siswa
                    
                    // Determine if this schedule item should have journal link
                    $showJournalLink = false;
                    $journalUrl = '#';
                    
                    // Rules for journal link:
                    // 1. Guru (role 3) hanya untuk mapel yang dia ampu (teacher_id matches)
                    // 2. Admin (role 1) dan Kepsek (role 2) tidak ada link jurnal
                    // 3. Siswa (role 5) dan Orang Tua (role 4) tidak ada link jurnal
                    
                    if ($userRole == 3 && isset($item['teacher_id']) && $relatedId == $item['teacher_id']) {
                        // Guru hanya untuk mapel yang dia ampu
                        $showJournalLink = true;
                        $journalUrl = base_url('admin/teaching-journal?class_id=' . $item['class_id'] . '&subject_id=' . $item['subject_id'] . '&date=' . ($rekapDate ?? date('Y-m-d')));
                    }
                    
                    // Calculate session duration
                    $start = strtotime($item['start_time']);
                    $end = strtotime($item['end_time']);
                    $duration = ($end - $start) / 3600; // in hours
                    
                    // Determine session status (only for today)
                    $now = strtotime($currentTime);
                    $sessionStatus = '';
                    
                    // Only calculate current/upcoming/completed status if the selected date is today
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
                                        <div>
                                            <h6 class="mb-0 fw-bold">
                                                <?php if ($showJournalLink): ?>
                                                    <a href="<?= $journalUrl ?>" class="text-decoration-none text-dark hover-primary" 
                                                       title="Buka Jurnal Mengajar untuk <?= esc($item['subject_name']) ?>">
                                                        <?= esc($item['subject_name']) ?> 
                                                        <i class="bi bi-journal-text small ms-1 text-primary"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <?= esc($item['subject_name']) ?>
                                                    <?php if ($userRole == 3 && isset($item['teacher_id']) && $relatedId != $item['teacher_id']): ?>
                                                        <small class="text-muted ms-1" title="Diajar oleh guru lain">
                                                            <i class="bi bi-person-check"></i>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="badge bg-primary py-1 px-2 shadow-sm font-monospace" style="font-size: 0.8rem;">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?= substr($item['start_time'], 0, 5) ?> - <?= substr($item['end_time'], 0, 5) ?>
                                                </span>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary py-1 px-2">
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    <?php
                                                    $itemJP = hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                                                    ?>
                                                    <?= $itemJP ?> JP
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <?php if ($userRole == 1 || $userRole == 3): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <?php if ($showJournalLink): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= $journalUrl ?>">
                                                                <i class="bi bi-journal-text me-2"></i> Jurnal Mengajar
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($userRole == 3 && isset($item['teacher_id']) && $relatedId == $item['teacher_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= site_url('admin/attendance?class_id=' . $item['class_id'] . '&date=' . ($rekapDate ?? date('Y-m-d'))) ?>">
                                                                <i class="bi bi-calendar-check me-2"></i> Presensi Kelas
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($userRole == 1): ?>
                                                        <li><hr class="dropdown-divider"></li>
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
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Additional Info -->
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                <?php if (!($hideClass ?? false) && isset($item['class_name'])): ?>
                                                    <i class="bi bi-door-closed me-1"></i> 
                                                    <strong>Kelas:</strong> <?= esc($item['class_name']) ?>
                                                <?php endif; ?>
                                            </small>
                                            <small class="text-muted d-block mt-1">
                                                <?php if (!($hideTeacher ?? false) && isset($item['teacher_name'])): ?>
                                                    <i class="bi bi-person me-1"></i> 
                                                    <strong>Guru:</strong> <?= esc($item['teacher_name']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <?php if ($sessionStatus == 'upcoming' && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
                                                <?php
                                                    $timeDiff = ($start - $now) / 60; // in minutes
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
                        <div class="h5 mb-0 fw-bold text-primary"><?= count($schedules) ?></div>
                        <small class="text-muted">Total Sesi</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 fw-bold text-success"><?= $totalJP ?></div>
                        <small class="text-muted">Total JP</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 fw-bold text-info">
                            <?= $hasCurrentSession ? '1' : '0' ?>
                        </div>
                        <small class="text-muted">Sesi Berlangsung</small>
                    </div>
                </div>
            </div>
            
            <?php if ($isHoliday): ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Card Footer -->
    <div class="card-footer bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php 
                $userRole = session()->get('user')['role_id'] ?? null;
                $relatedId = session()->get('user')['related_id'] ?? null;
                
                if ($userRole == 1): // Admin ?>
                    <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-gear me-1"></i> Kelola Semua Jadwal
                    </a>
                <?php elseif ($userRole == 2): // Kepsek ?>
                    <a href="<?= site_url('admin/schedules') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-eye me-1"></i> Lihat Jadwal
                    </a>
                <?php elseif ($userRole == 3): // Guru ?>
                    <a href="<?= site_url('admin/schedules?teacher_id=' . $relatedId) ?>" 
                       class="btn btn-sm btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-calendar-week me-1"></i> Jadwal Mengajar Saya
                    </a>
                <?php elseif (in_array($userRole, [4, 5])): // Orang Tua / Siswa ?>
                    <a href="<?= site_url('siswa/schedules') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-calendar3 me-1"></i> Jadwal Lengkap
                    </a>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php 
                    if ($userRole == 1) {
                        echo "Admin - Semua Kelas";
                    } elseif ($userRole == 2) {
                        echo "Kepsek - View Only";
                    } elseif ($userRole == 3) {
                        echo "Guru - Mapel Diajar";
                    } elseif ($userRole == 4) {
                        echo "Orang Tua - Kelas Anak";
                    } elseif ($userRole == 5) {
                        echo "Siswa - Kelas Saya";
                    }
                    ?>
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.schedule-card .current-session {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-left: 4px solid #667eea;
    position: relative;
}

.schedule-card .current-session::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, #667eea, #764ba2);
}

.schedule-card .agenda-item {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

.schedule-card .agenda-item:hover {
    background-color: #f8f9fa;
    transform: translateX(4px);
}

.schedule-card .session-number {
    min-width: 36px;
}

.schedule-card .hover-primary:hover {
    color: #667eea !important;
    text-decoration: underline !important;
}

/* Schedule Type Styling */
.schedule-card.teaching-schedule {
    border-top: 4px solid #667eea;
}

.schedule-card.teaching-schedule .card-header {
    background: linear-gradient(to right, #f8f9ff 0%, #ffffff 100%);
}

.schedule-card.homeroom-schedule {
    border-top: 4px solid #11998e;
}

.schedule-card.homeroom-schedule .card-header {
    background: linear-gradient(to right, #f0fffe 0%, #ffffff 100%);
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.animate-pulse {
    animation: pulse 2s infinite;
}

/* Timeline indicator */
.timeline-indicator {
    position: relative;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin: 0 1rem;
    overflow: hidden;
}

.timeline-progress {
    position: absolute;
    height: 100%;
    background: linear-gradient(to right, #667eea, #764ba2);
    border-radius: 2px;
    transition: width 0.5s ease;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .schedule-card .card-header {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .schedule-card .card-header > div:last-child {
        width: 100%;
        justify-content: space-between;
    }
    
    .schedule-card .agenda-item .row > div {
        margin-bottom: 0.5rem;
    }
    
    .schedule-card .agenda-item .text-md-end {
        text-align: left !important;
    }
}
</style>

<script>
// Auto-refresh schedule every 5 minutes
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes

// Update current time every second
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    
    // Update all clock elements
    document.querySelectorAll('.realtime-clock').forEach(clock => {
        clock.textContent = timeString;
    });
}

// Initialize
updateCurrentTime();
setInterval(updateCurrentTime, 1000);

// Add session status indicators
document.addEventListener('DOMContentLoaded', function() {
    const currentTime = new Date();
    const currentHour = currentTime.getHours();
    const currentMinute = currentTime.getMinutes();
    const currentTotalMinutes = currentHour * 60 + currentMinute;
    
    document.querySelectorAll('.agenda-item').forEach(item => {
        const timeBadge = item.querySelector('.badge.bg-primary');
        if (timeBadge) {
            const timeText = timeBadge.textContent.trim();
            const [startStr, endStr] = timeText.split(' - ');
            
            if (startStr && endStr) {
                const startTime = startStr.split(':');
                const endTime = endStr.split(':');
                
                const startTotalMinutes = parseInt(startTime[0]) * 60 + parseInt(startTime[1]);
                const endTotalMinutes = parseInt(endTime[0]) * 60 + parseInt(endTime[1]);
                
                // Calculate progress percentage for current session
                if (currentTotalMinutes >= startTotalMinutes && currentTotalMinutes <= endTotalMinutes) {
                    const sessionDuration = endTotalMinutes - startTotalMinutes;
                    const elapsed = currentTotalMinutes - startTotalMinutes;
                    const progress = (elapsed / sessionDuration) * 100;
                    
                    // Add progress indicator
                    const progressBar = document.createElement('div');
                    progressBar.className = 'timeline-indicator mt-2';
                    progressBar.innerHTML = `<div class="timeline-progress" style="width: ${progress}%"></div>`;
                    item.querySelector('.flex-grow-1').appendChild(progressBar);
                }
            }
        }
    });
});
</script>
