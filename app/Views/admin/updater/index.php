<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>🔄 System Updater</h4>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('info') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
// Detect if running on localhost
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
?>

<div class="row g-3">
    <!-- Git Auto Deploy Status -->
    <div class="col-12">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🚀 Status Git Auto Deploy</h5>
                    <span class="badge bg-light text-info">AUTOMATED</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($deployStatus) && $deployStatus): ?>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <span class="d-block text-muted small mb-1">STATUS DEPLOYMENT</span>
                                <strong class="text-success"><i class="bi bi-check-circle-fill"></i> Aktif & Berjalan</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <span class="d-block text-muted small mb-1">WAKTU DEPLOY TERAKHIR</span>
                                <strong><?= esc($deployStatus['deploy_time']) ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <span class="d-block text-muted small mb-1">COMMIT HASH</span>
                                <code class="fw-bold fs-6"><?= esc($deployStatus['commit_hash']) ?></code>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded h-100">
                                <span class="d-block text-muted small mb-1">PESAN COMMIT & AUTHOR</span>
                                <div class="text-truncate fw-bold" title="<?= esc($deployStatus['message']) ?>">
                                    <?= esc($deployStatus['message']) ?>
                                </div>
                                <small class="text-muted d-block mt-1">Oleh: <?= esc($deployStatus['author']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center py-2">
                        <i class="bi bi-info-circle-fill text-info fs-3 me-3"></i>
                        <div>
                            <strong>Belum ada riwayat deployment otomatis yang terdeteksi.</strong><br>
                            <span class="text-muted small">Setelah pull request Anda di-merge ke branch <code>main</code> dan dideploy oleh cPanel, detail komit dan waktu deploy terakhir akan muncul di sini secara otomatis.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Database Backup & Restore (Important!) -->
    <div class="col-12">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">💾 Backup & Restore Database</h5>
                    <span class="badge bg-light text-danger">PENTING</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Backup Section -->
                    <div class="col-md-6 border-end">
                        <h6 class="text-danger mb-3">📥 Backup Database</h6>
                        <p class="small mb-3">
                            <strong>Selalu backup sebelum update!</strong><br>
                            File backup berformat <code>.sql</code> untuk restore jika terjadi masalah.
                        </p>
                        <a href="<?= base_url('admin/updater/backup-database') ?>" class="btn btn-danger w-100">
                            💾 Download Backup
                        </a>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Disimpan di <code>writable/backups/</code>
                        </small>
                    </div>

                    <!-- Restore Section -->
                    <div class="col-md-6">
                        <h6 class="text-warning mb-3">📤 Restore Database</h6>
                        <p class="small mb-3">
                            <strong class="text-danger">⚠️ Hati-hati!</strong> Restore akan <strong>menimpa</strong>
                            database saat ini.
                        </p>
                        <form action="<?= base_url('admin/updater/restore-database') ?>" method="post"
                            enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label for="sql_file" class="form-label small">Pilih File Backup (.sql)</label>
                                <input class="form-control form-control-sm" type="file" id="sql_file" name="sql_file"
                                    accept=".sql" required>
                            </div>
                            <button type="submit" class="btn btn-warning w-100"
                                onclick="return confirm('PERINGATAN: Ini akan menimpa database saat ini! Yakin ingin restore?')">
                                ⚠️ Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <hr class="my-2">
    </div>

    <?php if ($isLocalhost): ?>
        <!-- Generate Manifest (Localhost Only) -->
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">🌐 Generate Manifest Update Online</h5>
                        <span class="badge bg-info">DEV ONLY</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <p class="mb-0">
                                Generate file <code>update_manifest.json</code> dan langsung push ke repo <code>sikap-update</code> di GitHub.
                                Setelah selesai, client bisa cek dan apply update secara online.
                            </p>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?= base_url('admin/updater/generate-manifest') ?>" class="btn btn-info"
                               onclick="this.innerHTML='⏳ Processing...';this.classList.add('disabled')">
                                📋 Generate & Push
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Generator (Localhost Only) -->
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📦 Langkah 1: Buat Paket Update (Localhost)</h5>
                        <span class="badge bg-dark">DEV ONLY</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <p class="mb-0">
                                Gunakan fitur ini di <strong>komputer Localhost</strong> setelah selesai coding.
                                Sistem akan membuat file <code>.zip</code> berisi source code (<code>app</code> &
                                <code>public</code>).
                            </p>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?= base_url('admin/updater/generate-patch') ?>" class="btn btn-dark">
                                ⬇️ Download Zip
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="col-12">
            <div class="text-center text-muted py-2">
                <small>↓ Setelah mendapat file .zip, upload ke server menggunakan fitur di bawah ↓</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Info: Download Update from Google Drive (for other users) -->
    <?php if (!$isLocalhost): ?>
        <!-- Online Update (Primary method) -->
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">🌐 Update Online (Rekomendasi)</h5>
                        <span class="badge bg-light text-primary">OTOMATIS</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-1">
                                <strong>Cek dan terapkan update secara online.</strong> Sistem hanya akan mendownload file yang berubah — lebih cepat dan efisien.
                            </p>
                            <small class="text-muted">Membutuhkan koneksi internet. File diunduh dari repository resmi.</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?= base_url('admin/updater/check-online') ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-cloud-arrow-down me-1"></i> Cek Update
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="alert alert-secondary d-flex align-items-center" role="alert">
                <i class="bi bi-cloud-download fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong>Alternatif: Download Manual</strong><br>
                    <small>Jika update online gagal, download file update (.zip) dari Google Drive</small>
                </div>
                <a href="https://s.id/sikap_app" target="_blank" class="btn btn-outline-secondary btn-sm ms-3">
                    <i class="bi bi-google"></i> Google Drive
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- File Updater -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📁 <?= $isLocalhost ? 'Langkah 2' : 'Langkah 1' ?>: Update File</h5>
                    <span class="badge bg-light text-primary">SERVER</span>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Upload file <code>.zip</code> <?= $isLocalhost ? 'dari Langkah 1' : 'yang berisi source code' ?>.
                    File di server akan ditimpa.
                </p>

                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>Backup dulu!</strong>
                </div>

                <form action="<?= base_url('admin/updater/patch-files') ?>" method="post" enctype="multipart/form-data" id="patch-form">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="patch_file" class="form-label small">File Patch (.zip)</label>
                        <input class="form-control form-control-sm" type="file" id="patch_file" name="patch_file"
                            accept=".zip" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btn-patch">
                        🚀 Upload & Update
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Database Migrator -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-success">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🗄️ <?= $isLocalhost ? 'Langkah 3' : 'Langkah 2' ?>: Update Database</h5>
                    <span class="badge bg-light text-success">SERVER</span>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Sesuaikan struktur database dengan source code baru. Data <strong>aman</strong>.
                </p>

                <div class="d-grid mb-3">
                    <a href="<?= base_url('admin/updater/run-migrations') ?>" class="btn btn-success">
                        ⚡ Jalankan Migrasi
                    </a>
                </div>

                <div class="border-top pt-3">
                    <h6 class="text-muted small mb-2">Riwayat Migrasi:</h6>
                    <?php if (!empty($migrations)): ?>
                        <div class="list-group list-group-flush small" style="max-height: 150px; overflow-y: auto;">
                            <?php foreach (array_reverse($migrations) as $mig): ?>
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="fw-bold text-truncate"><?= esc($mig->class) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem"><?= esc($mig->version) ?></div>
                                    </div>
                                    <span class="badge bg-secondary ms-2" style="font-size: 0.7rem">
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Handle form upload patch dengan progress indicator
document.getElementById('patch-form').addEventListener('submit', function(e) {
    var file = document.getElementById('patch_file').files[0];
    if (!file) return;

    if (!confirm('Yakin menimpa file sistem dengan patch ini?\n\nPastikan sudah backup database terlebih dahulu.')) {
        e.preventDefault();
        return;
    }

    var btn = document.getElementById('btn-patch');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengupload & Memproses... Harap tunggu';

    // Tampilkan pesan progress
    var alert = document.createElement('div');
    alert.className = 'alert alert-info mt-3';
    alert.innerHTML = '<i class="bi bi-hourglass-split me-2"></i><strong>Proses update sedang berjalan.</strong> Jangan tutup halaman ini. Proses bisa memakan waktu beberapa menit tergantung ukuran file.';
    this.appendChild(alert);
});
</script>
<?= $this->endSection() ?>