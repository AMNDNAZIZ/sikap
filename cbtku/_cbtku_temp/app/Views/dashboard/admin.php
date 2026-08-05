<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Quick fix - Inline styles untuk memastikan warna muncul */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 15px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}

.page-header h1 {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.5rem !important;
}

.page-header p {
    margin: 0 !important;
    opacity: 0.95 !important;
}

.stat-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.25rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    transition: all 0.3s ease !important;
    border-left: 4px solid #667eea !important;
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
}

.stat-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
}

.stat-card-icon {
    width: 50px !important;
    height: 50px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.5rem !important;
    margin-bottom: 0.75rem !important;
}

.stat-card-icon.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.stat-card-icon.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.stat-card-icon.warning {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.stat-card-icon.danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.stat-card-value {
    font-size: 2.25rem !important;
    font-weight: 700 !important;
    color: #212529 !important;
    margin-bottom: 0.25rem !important;
    line-height: 1 !important;
}

.stat-card-label {
    font-size: 0.875rem !important;
    color: #6c757d !important;
    font-weight: 500 !important;
    margin-top: auto !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
    margin-bottom: 1.5rem !important;
}

.modern-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    padding: 1.25rem !important;
    border-bottom: 2px solid #dee2e6 !important;
}

.modern-card-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #212529 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.modern-card-title i {
    color: #667eea !important;
}

.modern-card-body {
    padding: 1.5rem !important;
}

.badge-modern {
    padding: 0.375rem 0.75rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.8rem !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
}

.badge-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border: none !important;
}

.badge-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
    border: none !important;
}

.badge-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
    border: none !important;
}

.badge-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
    border: none !important;
}

.empty-state {
    text-align: center !important;
    padding: 2.5rem 1rem !important;
}

.empty-state-icon {
    font-size: 3.5rem !important;
    color: #dee2e6 !important;
    margin-bottom: 1rem !important;
}

.empty-state-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.empty-state-text {
    color: #6c757d !important;
    font-size: 0.9rem !important;
}

.list-group-item {
    border: none !important;
    border-bottom: 1px solid #f1f3f5 !important;
    padding: 1rem 0 !important;
}

.list-group-item:last-child {
    border-bottom: none !important;
}

.progress {
    height: 8px !important;
    border-radius: 50px !important;
    background-color: #e9ecef !important;
}

.progress-bar {
    border-radius: 50px !important;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
}

.table {
    margin-bottom: 0 !important;
}

.table thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #495057 !important;
    padding: 0.875rem !important;
}

.table tbody tr {
    transition: background-color 0.2s ease !important;
}

.table tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table tbody td {
    padding: 0.875rem !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f3f5 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-card-value {
        font-size: 1.75rem !important;
    }
    
    .stat-card-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.25rem !important;
    }
    
    .page-header h1 {
        font-size: 1.5rem !important;
    }
}

/* Custom scrollbar for activity table */
.table-responsive::-webkit-scrollbar {
    width: 8px !important;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f3f5 !important;
    border-radius: 10px !important;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-radius: 10px !important;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
}

/* Smooth scroll behavior */
.table-responsive {
    scroll-behavior: smooth !important;
}

/* Sticky header shadow when scrolling */
.table-responsive thead th {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard Administrator</h1>
        <p>Selamat datang kembali, <strong><?= esc($user['name'] ?? 'Admin') ?></strong>! Berikut ringkasan sistem Anda.</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-card-value"><?= number_format($students) ?></div>
                <div class="stat-card-label">Total Siswa</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon success">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-card-value"><?= number_format($banks) ?></div>
                <div class="stat-card-label">Bank Soal</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon warning">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-card-value"><?= number_format($tests) ?></div>
                <div class="stat-card-label">Total Ujian</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-value"><?= number_format($activeUsers) ?></div>
                <div class="stat-card-label">Siswa Aktif</div>
            </div>
        </div>
    </div>

    <!-- License Info -->
    <?php if ($license): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2">
                                <i class="fas fa-key text-primary"></i> Informasi Lisensi
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">License Key</small>
                                    <div class="fw-bold"><?= esc(substr($license['license_key'], 0, 20)) ?>...</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Domain</small>
                                    <div class="fw-bold"><?= esc($license['domain']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Status</small>
                                    <div>
                                        <?php if ($license['status'] === 'active'): ?>
                                            <span class="badge badge-modern badge-success-gradient">
                                                <i class="fas fa-check-circle"></i> Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-modern badge-danger-gradient">
                                                <i class="fas fa-times-circle"></i> Tidak Aktif
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($license['expires_at']): ?>
                                <?php 
                                $expiryDate = strtotime($license['expires_at']);
                                $daysLeft = floor(($expiryDate - time()) / 86400);
                                ?>
                                <small class="text-muted">Berlaku Hingga</small>
                                <div class="fw-bold"><?= date('d M Y', $expiryDate) ?></div>
                                <?php if ($daysLeft > 0 && $daysLeft <= 30): ?>
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?= $daysLeft ?> hari lagi
                                    </small>
                                <?php elseif ($daysLeft <= 0): ?>
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle"></i> Expired
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-modern badge-primary-gradient">
                                    <i class="fas fa-infinity"></i> Lifetime
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="row g-3">
        <!-- Recent Activity -->
        <div class="col-md-8">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h3 class="modern-card-title">
                        <i class="fas fa-chart-line"></i> Aktivitas Ujian Real-Time
                        <?php if (!empty($recentActivity)): ?>
                            <span class="badge badge-modern badge-success-gradient ms-2">
                                <?= count($recentActivity) ?> Siswa Aktif
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="modern-card-body" style="padding: 0;">
                    <?php if (!empty($recentActivity)): ?>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover mb-0">
                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="width: 25%;">Siswa</th>
                                        <th style="width: 30%;">Ujian</th>
                                        <th style="width: 30%;">Progress</th>
                                        <th style="width: 15%;">Waktu Tersisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= esc($activity['student_name']) ?></div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                Mulai: <?= date('H:i', is_numeric($activity['started_at']) ? $activity['started_at'] : strtotime($activity['started_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div><?= esc($activity['exam_name']) ?></div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i>
                                                <?= date('d M Y', strtotime($activity['start_time'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: <?= $activity['progress_percent'] ?>%"></div>
                                                </div>
                                                <small class="text-muted" style="min-width: 45px;">
                                                    <?= $activity['answered_questions'] ?>/<?= $activity['total_questions'] ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?= $activity['progress_percent'] ?>% selesai
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($activity['remaining_minutes'] > 0 || $activity['remaining_seconds'] > 0): ?>
                                                <span class="badge badge-modern badge-warning-gradient">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    <?= sprintf('%02d:%02d', $activity['remaining_minutes'], $activity['remaining_seconds']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-modern badge-danger-gradient">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Habis
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($recentActivity) > 10): ?>
                        <div class="text-center py-2 border-top bg-light">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Scroll untuk melihat lebih banyak siswa
                            </small>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h5 class="empty-state-title">Tidak Ada Ujian Aktif</h5>
                            <p class="empty-state-text">
                                Belum ada siswa yang sedang mengerjakan ujian saat ini.
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="modern-card mt-3">
                <div class="modern-card-header">
                    <h3 class="modern-card-title">
                        <i class="fas fa-server"></i> Informasi Sistem
                    </h3>
                </div>
                <div class="modern-card-body">
                    <div class="mb-2">
                        <small class="text-muted">PHP Version</small>
                        <div class="fw-bold"><?= esc($systemInfo['php']) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">CodeIgniter</small>
                        <div class="fw-bold"><?= esc($systemInfo['ci']) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Database</small>
                        <div class="fw-bold"><?= esc($systemInfo['db']) ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Server</small>
                        <div class="fw-bold text-truncate" title="<?= esc($systemInfo['server']) ?>">
                            <?= esc($systemInfo['server']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Info -->
    <?php if ($school): ?>
    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <?php if (!empty($school['logo'])): ?>
                                <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" 
                                     alt="Logo" class="img-fluid" style="max-height: 80px;">
                            <?php else: ?>
                                <i class="fas fa-school" style="font-size: 3rem; color: var(--gray-300);"></i>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-10">
                            <h5 class="mb-2"><?= esc($school['name']) ?></h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted"><i class="fas fa-map-marker-alt"></i> Alamat</small>
                                    <div><?= esc($school['address']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted"><i class="fas fa-phone"></i> Telepon</small>
                                    <div><?= esc($school['phone']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted"><i class="fas fa-user-tie"></i> Kepala Sekolah</small>
                                    <div><?= esc($school['headmaster']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Auto refresh aktivitas setiap 30 detik
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?= $this->endSection() ?>
