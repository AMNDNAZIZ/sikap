<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">
    <!-- Top Action / Back Button -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 no-print">
                <i class="bi bi-printer me-1"></i> Cetak Jadwal
            </button>
        </div>
    </div>

    <!-- Banner / Header Card -->
    <div class="card border-0 shadow-sm mb-4 schedule-banner overflow-hidden">
        <div class="card-body p-4 text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill">
                        <i class="bi bi-calendar-week me-1"></i> Jadwal Belajar Sepekan
                    </span>
                    <h3 class="fw-bold mb-1">
                        Jadwal Pelajaran Kelas <?= esc($className) ?>
                    </h3>
                    <p class="mb-3 text-white-50">
                        Tahun Ajaran <?= esc($activeYear['year'] ?? '-') ?> <?= !empty($activeYear['semester']) ? '• Semester ' . esc($activeYear['semester']) : '' ?>
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($homeroomTeacher)): ?>
                            <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill">
                                <i class="bi bi-person-badge text-primary me-1"></i> Wali Kelas: <?= esc($homeroomTeacher) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($student['name'])): ?>
                            <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill">
                                <i class="bi bi-person-fill text-success me-1"></i> Siswa: <?= esc($student['name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3 backdrop-blur">
                                <h4 class="fw-bold mb-0 text-white"><?= $totalSessions ?></h4>
                                <small class="text-white-50">Sesi Belajar</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 rounded-3 p-3 backdrop-blur">
                                <h4 class="fw-bold mb-0 text-white"><?= $totalJP ?></h4>
                                <small class="text-white-50">Total JP</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($displayDays) || $totalSessions === 0): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">Belum Ada Jadwal Pelajaran</h5>
                <p class="text-muted mb-4">
                    Jadwal pelajaran untuk Kelas <?= esc($className) ?> pada Tahun Ajaran <?= esc($activeYear['year'] ?? '-') ?> belum disusun atau belum dipublikasikan oleh pihak sekolah.
                </p>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Nav Tabs for Filtering Days -->
        <ul class="nav nav-pills mb-4 flex-nowrap overflow-auto pb-2 gap-2 no-print" id="scheduleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 active" id="tab-all-tab" data-bs-toggle="pill" data-bs-target="#tab-all" type="button" role="tab">
                    <i class="bi bi-grid-fill me-1"></i> Semua Hari
                </button>
            </li>
            <?php foreach ($displayDays as $dayNum => $dayData): ?>
                <?php 
                    $isToday = ($dayNum === $todayDayNum);
                    $itemCount = count($dayData['items']);
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-3 <?= $isToday ? 'border-primary fw-semibold' : '' ?>" 
                            id="tab-<?= $dayNum ?>-tab" 
                            data-bs-toggle="pill" 
                            data-bs-target="#tab-<?= $dayNum ?>" 
                            type="button" 
                            role="tab">
                        <?= esc($dayData['name']) ?>
                        <?php if ($isToday): ?>
                            <span class="badge bg-success ms-1">Hari Ini</span>
                        <?php endif; ?>
                        <span class="badge bg-secondary bg-opacity-25 text-dark ms-1"><?= $itemCount ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="scheduleTabContent">
            <!-- TAB: SEMUA HARI -->
            <div class="tab-pane fade show active" id="tab-all" role="tabpanel">
                <div class="row g-4">
                    <?php 
                    $currentTime = date('H:i:s');
                    foreach ($displayDays as $dayNum => $dayData): 
                        $isToday = ($dayNum === $todayDayNum);
                        $items = $dayData['items'];
                    ?>
                        <div class="col-12 col-lg-6 col-xxl-4">
                            <div class="card h-100 border-0 shadow-sm day-card <?= $isToday ? 'border-start border-4 border-primary' : '' ?>">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="day-icon-box rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="bi bi-calendar3"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= esc($dayData['name']) ?></h6>
                                            <small class="text-muted"><?= count($items) ?> Sesi • <?= $dayData['total_jp'] ?> JP</small>
                                        </div>
                                    </div>
                                    <?php if ($isToday): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Hari Ini
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($items)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-cup-hot opacity-50 fs-3 d-block mb-1"></i>
                                            <small>Tidak ada KBM pada hari ini</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($items as $session): 
                                                $isCurrentSession = ($isToday && $currentTime >= $session['start_time'] && $currentTime <= $session['end_time']);
                                            ?>
                                                <div class="list-group-item p-3 session-item <?= $isCurrentSession ? 'bg-primary bg-opacity-10 border-start border-3 border-primary' : '' ?>">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-dark bg-opacity-75 font-monospace px-2 py-1">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?= substr($session['start_time'], 0, 5) ?> - <?= substr($session['end_time'], 0, 5) ?>
                                                            </span>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">
                                                                <?= $session['jp'] ?? 1 ?> JP
                                                            </span>
                                                        </div>
                                                        <?php if ($isCurrentSession): ?>
                                                            <span class="badge bg-danger rounded-pill px-2 py-1 pulse-badge">
                                                                <i class="bi bi-broadcast me-1"></i> Sedang Berlangsung
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <h6 class="fw-bold text-dark mb-1">
                                                        <i class="bi bi-book-half text-primary me-1"></i>
                                                        <?= esc($session['subject_name'] ?? 'Mata Pelajaran') ?>
                                                    </h6>

                                                    <div class="d-flex align-items-center text-muted small mt-1">
                                                        <i class="bi bi-person-badge me-1"></i>
                                                        <span><?= esc($session['teacher_name'] ?? 'Guru Pengampu') ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TABS INDIVIDUAL TIAP HARI -->
            <?php foreach ($displayDays as $dayNum => $dayData): 
                $isToday = ($dayNum === $todayDayNum);
                $items = $dayData['items'];
            ?>
                <div class="tab-pane fade" id="tab-<?= $dayNum ?>" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="fw-bold text-dark mb-0">
                                    Jadwal Hari <?= esc($dayData['name']) ?>
                                </h5>
                                <small class="text-muted">Total <?= count($items) ?> Sesi • <?= $dayData['total_jp'] ?> Jam Pelajaran (JP)</small>
                            </div>
                            <?php if ($isToday): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2">
                                    <i class="bi bi-calendar-check me-1"></i> Hari Ini
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($items)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 opacity-50 d-block mb-2"></i>
                                    <h6>Tidak ada sesi belajar pada hari <?= esc($dayData['name']) ?></h6>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="70">#</th>
                                                <th width="180">Waktu</th>
                                                <th width="100">Beban</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Guru Pengampu</th>
                                                <th width="150" class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            foreach ($items as $session): 
                                                $isCurrentSession = ($isToday && $currentTime >= $session['start_time'] && $currentTime <= $session['end_time']);
                                            ?>
                                                <tr class="<?= $isCurrentSession ? 'table-primary' : '' ?>">
                                                    <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                                                    <td>
                                                        <span class="badge bg-dark bg-opacity-75 font-monospace px-2 py-1">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?= substr($session['start_time'], 0, 5) ?> - <?= substr($session['end_time'], 0, 5) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                            <?= $session['jp'] ?? 1 ?> JP
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold text-dark">
                                                        <i class="bi bi-book-half text-primary me-2"></i>
                                                        <?= esc($session['subject_name'] ?? 'Mata Pelajaran') ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-person-badge text-muted me-2"></i>
                                                            <span><?= esc($session['teacher_name'] ?? 'Guru Pengampu') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($isCurrentSession): ?>
                                                            <span class="badge bg-danger rounded-pill px-3 py-1 pulse-badge">
                                                                <i class="bi bi-broadcast me-1"></i> Sedang Berlangsung
                                                            </span>
                                                        <?php elseif ($isToday && $currentTime > $session['end_time']): ?>
                                                            <span class="badge bg-secondary bg-opacity-25 text-muted rounded-pill px-2 py-1">
                                                                <i class="bi bi-check me-1"></i> Selesai
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                                                Terjadwal
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PRINT ONLY VIEW (FORMAT TABEL PEKAN LENGKAP) -->
        <div class="d-none d-print-block mt-4">
            <div class="text-center mb-3">
                <h4 class="fw-bold mb-1">JADWAL PELAJARAN KELAS <?= strtoupper(esc($className)) ?></h4>
                <p class="mb-0">Tahun Ajaran <?= esc($activeYear['year'] ?? '-') ?> • Wali Kelas: <?= esc($homeroomTeacher ?? '-') ?></p>
            </div>
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th width="120">Hari</th>
                        <th width="140">Waktu</th>
                        <th width="70">JP</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru Pengampu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayDays as $dayNum => $dayData): 
                        $items = $dayData['items'];
                        if (empty($items)) continue;
                        $rowCount = count($items);
                        $first = true;
                        foreach ($items as $s):
                    ?>
                        <tr>
                            <?php if ($first): ?>
                                <td rowspan="<?= $rowCount ?>" class="fw-bold text-center align-middle bg-light">
                                    <?= esc($dayData['name']) ?>
                                </td>
                                <?php $first = false; ?>
                            <?php endif; ?>
                            <td class="text-center font-monospace">
                                <?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>
                            </td>
                            <td class="text-center"><?= $s['jp'] ?? 1 ?></td>
                            <td class="fw-bold"><?= esc($s['subject_name'] ?? '-') ?></td>
                            <td><?= esc($s['teacher_name'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.day-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.day-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important;
}
.session-item {
    transition: background-color 0.2s ease;
}
.session-item:hover {
    background-color: rgba(0,0,0,0.02);
}
.pulse-badge {
    animation: pulseAnimation 2s infinite;
}
@keyframes pulseAnimation {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
@media print {
    .no-print, header, nav, .sidebar, footer {
        display: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    body {
        background: #fff !important;
        font-size: 12px;
    }
}
</style>

<?= $this->endSection() ?>
