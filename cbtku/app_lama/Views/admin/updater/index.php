<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Updater page */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 12px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

.page-header h1 {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

.page-header p {
    margin: 0 !important;
    opacity: 0.95 !important;
    font-size: 0.95rem !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
    height: 100% !important;
}

.modern-card-header {
    padding: 1.25rem !important;
    border-bottom: 1px solid #e9ecef !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.modern-card-header.danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
    border-bottom: none !important;
}

.modern-card-header.warning {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
    border-bottom: none !important;
}

.modern-card-header.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-bottom: none !important;
}

.modern-card-header.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
    border-bottom: none !important;
}

.modern-card-header.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
    border-bottom: none !important;
}

.modern-card-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.modern-card-body {
    padding: 1.5rem !important;
}

.alert-modern {
    border: none !important;
    border-radius: 10px !important;
    padding: 1rem 1.25rem !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 0.75rem !important;
    margin-bottom: 1.5rem !important;
}

.alert-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.alert-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.alert-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.alert-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
}

.btn-modern {
    padding: 0.625rem 1.5rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
}

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.btn-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.btn-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
}

.btn-dark-gradient {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    color: white !important;
}

.badge-modern {
    padding: 0.375rem 0.75rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.8rem !important;
}

.badge-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.badge-dark-gradient {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    color: white !important;
}

.badge-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.badge-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.form-control, .form-select {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.form-label {
    font-weight: 500 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.info-box {
    background: #f8f9fa !important;
    border-left: 4px solid #667eea !important;
    padding: 1rem !important;
    border-radius: 8px !important;
    margin-bottom: 1rem !important;
}

.migration-list {
    max-height: 200px !important;
    overflow-y: auto !important;
    border: 1px solid #e9ecef !important;
    border-radius: 8px !important;
    padding: 0.5rem !important;
}

.migration-item {
    padding: 0.75rem !important;
    border-bottom: 1px solid #f1f3f5 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.migration-item:last-child {
    border-bottom: none !important;
}

.divider {
    text-align: center !important;
    padding: 1rem 0 !important;
    color: #6c757d !important;
    font-size: 0.9rem !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-sync-alt"></i> System Updater</h1>
        <p>Kelola update sistem, backup database, dan migrasi dengan aman</p>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-modern alert-success-gradient">
            <i class="fas fa-check-circle"></i>
            <div><?= session()->getFlashdata('success') ?></div>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
        <div class="alert alert-modern alert-warning-gradient">
            <i class="fas fa-exclamation-triangle"></i>
            <div><?= session()->getFlashdata('warning') ?></div>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-modern alert-danger-gradient">
            <i class="fas fa-times-circle"></i>
            <div><?= session()->getFlashdata('error') ?></div>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('info')): ?>
        <div class="alert alert-modern alert-info-gradient">
            <i class="fas fa-info-circle"></i>
            <div><?= session()->getFlashdata('info') ?></div>
        </div>
    <?php endif; ?>

    <?php
    // Detect if running on localhost
    $isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    ?>

    <!-- Backup & Restore Card -->
    <div class="modern-card mb-4">
        <div class="modern-card-header danger">
            <h5 class="modern-card-title">
                <i class="fas fa-database"></i> Backup & Restore Database
            </h5>
            <span class="badge badge-modern badge-danger-gradient">PENTING</span>
        </div>
        <div class="modern-card-body">
            <div class="row g-4">
                <!-- Backup Section -->
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-2">Backup Database</h6>
                            <p class="text-muted small mb-3">
                                Selalu backup sebelum update! File backup berformat <code>.sql</code> untuk restore jika terjadi masalah.
                            </p>
                            <a href="<?= base_url('admin/updater/backup-database') ?>" class="btn btn-modern btn-danger-gradient w-100">
                                <i class="fas fa-download"></i> Download Backup
                            </a>
                            <div class="info-box mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Disimpan di <code>writable/backups/</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restore Section -->
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="text-warning" style="font-size: 2rem;">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-2">Restore Database</h6>
                            <p class="text-muted small mb-3">
                                <strong class="text-danger">⚠️ Hati-hati!</strong> Restore akan <strong>menimpa</strong> database saat ini.
                            </p>
                            <form action="<?= base_url('admin/updater/restore-database') ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label for="sql_file" class="form-label">Pilih File Backup (.sql)</label>
                                    <input class="form-control" type="file" id="sql_file" name="sql_file" accept=".sql" required>
                                </div>
                                <button type="submit" class="btn btn-modern btn-warning-gradient w-100"
                                    onclick="return confirm('PERINGATAN: Ini akan menimpa database saat ini! Yakin ingin restore?')">
                                    <i class="fas fa-exclamation-triangle"></i> Restore Database
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isLocalhost): ?>
        <!-- Generator Card (Localhost Only) -->
        <div class="modern-card mb-4">
            <div class="modern-card-header warning">
                <h5 class="modern-card-title">
                    <i class="fas fa-box"></i> Langkah 1: Buat Paket Update (Localhost)
                </h5>
                <span class="badge badge-modern badge-dark-gradient">DEV ONLY</span>
            </div>
            <div class="modern-card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-3">
                            Gunakan fitur ini di <strong>komputer Localhost</strong> setelah selesai coding.
                            Sistem akan membuat file <code>.zip</code> berisi source code.
                        </p>
                        
                        <div class="alert alert-modern alert-success-gradient mb-3">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Otomatis Aman:</strong> File sensitif seperti <code>.htaccess</code>, <code>.env</code>, dan <code>composer.json</code> otomatis dikecualikan!
                            </div>
                        </div>
                        
                        <details class="info-box">
                            <summary style="cursor: pointer;">
                                <i class="fas fa-info-circle"></i> File yang dikecualikan (klik untuk lihat)
                            </summary>
                            <div class="mt-2">
                                <strong>File Sensitif:</strong><br>
                                <code>.htaccess</code>, <code>.env*</code>, <code>composer.json</code>, <code>spark</code>, <code>phpunit.xml</code>
                                
                                <hr class="my-2">
                                
                                <strong>Folder Berat:</strong><br>
                                <code>.git</code>, <code>node_modules</code>, <code>vendor</code>, <code>tests</code>
                                
                                <hr class="my-2">
                                
                                <strong>Writable Content:</strong><br>
                                <code>writable/cache</code>, <code>writable/logs</code>, <code>writable/session</code>, <code>writable/uploads</code>
                            </div>
                        </details>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= base_url('admin/updater/generate-patch') ?>" class="btn btn-modern btn-dark-gradient btn-lg w-100 mb-2">
                            <i class="fas fa-download"></i> Download Zip
                        </a>
                        <small class="text-muted d-block text-center">
                            <i class="fas fa-clock"></i> Proses ~5-10 detik
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="divider">
            <i class="fas fa-arrow-down"></i> Setelah mendapat file .zip, upload ke server menggunakan fitur di bawah <i class="fas fa-arrow-down"></i>
        </div>
    <?php endif; ?>

    <?php if (!$isLocalhost): ?>
        <!-- Info: Download Update -->
        <div class="alert alert-modern alert-info-gradient mb-4">
            <i class="fas fa-cloud-download-alt" style="font-size: 2rem;"></i>
            <div class="flex-grow-1">
                <strong>Butuh File Update Terbaru?</strong><br>
                <small>Download file update (.zip) dari Google Drive resmi aplikasi SIKAP</small>
                <div class="mt-2">
                    <a href="https://s.id/cbt-ku" target="_blank" class="btn btn-modern btn-info-gradient btn-sm">
                        <i class="fab fa-google-drive"></i> Buka Google Drive
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- File Updater -->
        <div class="col-md-6">
            <div class="modern-card">
                <div class="modern-card-header primary">
                    <h5 class="modern-card-title">
                        <i class="fas fa-file-archive"></i> <?= $isLocalhost ? 'Langkah 2' : 'Langkah 1' ?>: Update File
                    </h5>
                    <span class="badge badge-modern badge-primary-gradient">SERVER</span>
                </div>
                <div class="modern-card-body">
                    <p class="text-muted mb-3">
                        Upload file <code>.zip</code> <?= $isLocalhost ? 'dari Langkah 1' : 'yang berisi source code' ?>.
                        File di server akan ditimpa.
                    </p>

                    <div class="alert alert-modern alert-info-gradient mb-3">
                        <i class="fas fa-magic"></i>
                        <div>
                            <strong>Auto-Migration:</strong> Jika ZIP berisi migration files, database akan otomatis di-update setelah extract!
                        </div>
                    </div>

                    <div class="alert alert-modern alert-warning-gradient mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div><strong>Backup dulu!</strong></div>
                    </div>

                    <form action="<?= base_url('admin/updater/patch-files') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="patch_file" class="form-label">File Patch (.zip)</label>
                            <input class="form-control" type="file" id="patch_file" name="patch_file" accept=".zip" required>
                        </div>
                        <button type="submit" class="btn btn-modern btn-primary-gradient w-100" onclick="return confirm('Yakin menimpa file?')">
                            <i class="fas fa-rocket"></i> Upload & Update (Auto-Migrate)
                        </button>
                    </form>
                    
                    <div class="info-box mt-3">
                        <small>
                            <strong>Proses otomatis:</strong><br>
                            1️⃣ Extract files<br>
                            2️⃣ Detect migration files<br>
                            3️⃣ Run migrations (jika ada)<br>
                            4️⃣ Selesai! ✅
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Migrator -->
        <div class="col-md-6">
            <div class="modern-card">
                <div class="modern-card-header success">
                    <h5 class="modern-card-title">
                        <i class="fas fa-database"></i> <?= $isLocalhost ? 'Langkah 3' : 'Langkah 2' ?>: Update Database
                    </h5>
                    <span class="badge badge-modern badge-success-gradient">OPSIONAL</span>
                </div>
                <div class="modern-card-body">
                    <p class="text-muted mb-3">
                        <strong>Biasanya tidak perlu!</strong> Migration sudah otomatis saat upload file.<br>
                        Gunakan ini hanya jika auto-migration gagal atau untuk testing.
                    </p>

                    <a href="<?= base_url('admin/updater/run-migrations') ?>" class="btn btn-modern btn-success-gradient w-100 mb-3">
                        <i class="fas fa-bolt"></i> Jalankan Migrasi Manual
                    </a>

                    <div class="border-top pt-3">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-history"></i> Riwayat Migrasi:
                        </h6>
                        <?php if (!empty($migrations)): ?>
                            <div class="migration-list">
                                <?php foreach (array_reverse($migrations) as $mig): ?>
                                    <div class="migration-item">
                                        <div style="flex: 1; min-width: 0;">
                                            <div class="fw-bold text-truncate small"><?= esc($mig->class) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem"><?= esc($mig->version) ?></div>
                                        </div>
                                        <span class="badge badge-modern badge-primary-gradient" style="font-size: 0.7rem">
                                            <?= date('d/m H:i', $mig->time) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted fst-italic mb-0 small">Belum ada riwayat.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="modern-card">
        <div class="modern-card-header info">
            <h5 class="modern-card-title">
                <i class="fas fa-info-circle"></i> Tentang Safe Update System
            </h5>
        </div>
        <div class="modern-card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <div class="text-info" style="font-size: 2.5rem;">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                    </div>
                    <h6 class="text-info text-center mb-2">Auto-Migration</h6>
                    <p class="small text-center mb-0">
                        Sistem otomatis mendeteksi migration files di dalam ZIP dan menjalankannya setelah extract. 
                        Tidak perlu manual jalankan migration lagi!
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <div class="text-success" style="font-size: 2.5rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <h6 class="text-success text-center mb-2">Safe Migration</h6>
                    <p class="small text-center mb-0">
                        Migration menggunakan safe functions yang bisa dijalankan berkali-kali tanpa error. 
                        Jika kolom/table sudah ada, akan di-skip otomatis.
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <h6 class="text-warning text-center mb-2">Error Handling</h6>
                    <p class="small text-center mb-0">
                        Jika migration gagal, file tetap ter-update dan Anda akan mendapat pesan error yang jelas. 
                        Bisa jalankan migration manual jika perlu.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
