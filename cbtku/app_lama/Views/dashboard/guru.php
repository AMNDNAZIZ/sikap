<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-chalkboard-teacher"></i> Dashboard Guru</h1>
        <p>Selamat datang, <strong><?= esc($user['name'] ?? 'Guru') ?></strong>! Kelola ujian dan pantau aktivitas siswa Anda.</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon primary">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-card-value"><?= number_format($banks) ?></div>
                <div class="stat-card-label">Bank Soal Saya</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon success">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-card-value"><?= number_format($tests) ?></div>
                <div class="stat-card-label">Total Ujian</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon warning">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-value"><?= number_format($activeSessions) ?></div>
                <div class="stat-card-label">Siswa Aktif</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon danger">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-card-value"><?= number_format($finishedSessions) ?></div>
                <div class="stat-card-label">Ujian Selesai</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-body">
                    <h5 class="mb-3">
                        <i class="fas fa-bolt"></i> Aksi Cepat
                    </h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= base_url('admin/cbt/banksoal/create') ?>" class="btn btn-modern btn-primary-gradient">
                            <i class="fas fa-plus"></i> Buat Bank Soal
                        </a>
                        <a href="<?= base_url('admin/cbt/teststatus/create') ?>" class="btn btn-modern btn-success-gradient">
                            <i class="fas fa-calendar-plus"></i> Jadwalkan Ujian
                        </a>
                        <a href="<?= base_url('admin/cbt/aktivitas') ?>" class="btn btn-modern btn-info-gradient">
                            <i class="fas fa-chart-bar"></i> Lihat Hasil
                        </a>
                        <a href="<?= base_url('admin/cbt/banksoal') ?>" class="btn btn-modern btn-warning-gradient">
                            <i class="fas fa-book"></i> Bank Soal Saya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-3">
        <!-- Recent Activity -->
        <div class="col-md-8">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h3 class="modern-card-title">
                        <i class="fas fa-chart-line"></i> Siswa Sedang Ujian
                    </h3>
                </div>
                <div class="modern-card-body">
                    <?php if (!empty($recentActivity)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Ujian</th>
                                        <th>Progress</th>
                                        <th>Waktu Tersisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= esc($activity['student_name']) ?></div>
                                            <small class="text-muted">
                                                Mulai: <?= date('H:i', is_numeric($activity['started_at']) ? $activity['started_at'] : strtotime($activity['started_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div><?= esc($activity['exam_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: <?= $activity['progress_percent'] ?>%"></div>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $activity['answered_questions'] ?>/<?= $activity['total_questions'] ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-modern badge-warning-gradient">
                                                <i class="fas fa-clock"></i>
                                                <?= sprintf('%02d:%02d', $activity['remaining_minutes'], $activity['remaining_seconds']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h5 class="empty-state-title">Tidak Ada Ujian Aktif</h5>
                            <p class="empty-state-text">
                                Belum ada siswa yang sedang mengerjakan ujian Anda saat ini.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Exams -->
        <div class="col-md-4">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h3 class="modern-card-title">
                        <i class="fas fa-calendar-alt"></i> Ujian Mendatang
                    </h3>
                </div>
                <div class="modern-card-body">
                    <?php if (!empty($upcomingExams)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingExams as $exam): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= esc($exam['exam_name']) ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-book"></i> <?= esc($exam['subject_name']) ?>
                                        </small>
                                    </div>
                                    <?php if ($exam['is_active']): ?>
                                        <span class="badge badge-modern badge-success-gradient">
                                            <i class="fas fa-check-circle"></i> Aktif
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?= date('d M Y, H:i', strtotime($exam['start_time'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?= base_url('admin/cbt/teststatus') ?>" class="btn btn-sm btn-modern btn-primary-gradient">
                                <i class="fas fa-eye"></i> Lihat Semua
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state py-3">
                            <div class="empty-state-icon" style="font-size: 2rem;">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <p class="empty-state-text mb-0">
                                Belum ada ujian yang dijadwalkan.
                            </p>
                            <a href="<?= base_url('admin/cbt/teststatus/create') ?>" 
                               class="btn btn-sm btn-modern btn-primary-gradient mt-2">
                                <i class="fas fa-plus"></i> Jadwalkan Ujian
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="modern-card mt-3">
                <div class="modern-card-header">
                    <h3 class="modern-card-title">
                        <i class="fas fa-info-circle"></i> Informasi
                    </h3>
                </div>
                <div class="modern-card-body">
                    <?php if ($teacher): ?>
                    <div class="mb-3">
                        <small class="text-muted">NIP</small>
                        <div class="fw-bold"><?= esc($teacher['nip'] ?? '-') ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <small class="text-muted">Sekolah</small>
                        <div class="fw-bold"><?= esc($school['name'] ?? '-') ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Role</small>
                        <div>
                            <span class="badge badge-modern badge-primary-gradient">
                                <i class="fas fa-chalkboard-teacher"></i> <?= esc($role) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto refresh aktivitas setiap 30 detik
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?= $this->endSection() ?>
