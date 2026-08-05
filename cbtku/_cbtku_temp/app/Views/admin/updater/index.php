<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?= session()->getFlashdata('warning') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-times-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('info')): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-info-circle me-2"></i><?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <div>
            <h3 class="mb-0">Update Sistem</h3>
            <p class="text-muted mb-0">Kelola pembaruan, backup, dan restore aplikasi CBT</p>
        </div>
        <div class="ms-auto text-end">
            <span class="badge bg-primary fs-6">v<?= APP_VERSION ?? '1.0.0' ?></span>
            <div class="small text-muted mt-1">Terakhir update: <?= LAST_UPDATE ?? 'Belum pernah' ?></div>
        </div>
    </div>

    <!-- SECTION: Developer Tools (localhost only) -->
    <?php if (defined('IS_DEVELOPER') && IS_DEVELOPER === true): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-tools me-2"></i>Developer Tools
                <span class="badge bg-dark ms-2 small">Localhost Only</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Generate & Push (Primary) -->
                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-success"><i class="fas fa-cloud-upload-alt me-2"></i>Generate Manifest & Push ke GitHub</h6>
                        <p class="small text-muted">Scan semua file, generate <code>update_manifest.json</code>, lalu push ke repo GitHub distribusi sebagai orphan commit.</p>
                        <form action="<?= site_url('admin/updater/generate-manifest') ?>" method="get" id="formManifest">
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-5">
                                    <label class="form-label fw-bold small mb-1">Versi Baru</label>
                                    <input type="text" class="form-control form-control-sm" name="new_version"
                                           value="<?= APP_VERSION ?>" pattern="^\d+\.\d+(\.\d+)?$"
                                           title="Format: X.Y atau X.Y.Z" required>
                                </div>
                                <div class="col-sm-7">
                                    <button type="submit" class="btn btn-success btn-sm w-100" id="btnManifest">
                                        <i class="fas fa-rocket me-1"></i>Generate & Push
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-github me-1"></i>Repo: <code>Sakajaya/update-cbtku</code> (branch: main)
                        </div>
                    </div>
                </div>

                <!-- Generate ZIP (Legacy Fallback) -->
                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-secondary"><i class="fas fa-file-archive me-2"></i>Download Patch ZIP (Legacy)</h6>
                        <p class="small text-muted">Generate file ZIP untuk didistribusikan manual ke pengguna via WhatsApp/email.</p>
                        <form action="<?= site_url('admin/updater/generate-patch') ?>" method="get">
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-5">
                                    <label class="form-label fw-bold small mb-1">Versi</label>
                                    <input type="text" class="form-control form-control-sm" name="new_version"
                                           value="<?= APP_VERSION ?>" pattern="^\d+\.\d+(\.\d+)?$" required>
                                </div>
                                <div class="col-sm-7">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="fas fa-download me-1"></i>Generate & Download ZIP
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION: Update Online (Production) -->
    <?php if (!defined('IS_DEVELOPER') || IS_DEVELOPER !== true): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-cloud-download-alt me-2"></i>Update Online (Delta Update)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Sistem akan mengecek file mana saja yang berubah di server, lalu hanya mendownload file yang berbeda.</p>

            <button type="button" class="btn btn-success btn-lg" id="btnCheckUpdate">
                <i class="fas fa-search me-2"></i>Cek Update
            </button>

            <!-- Result container (diisi via JS) -->
            <div id="updateResult" class="mt-4" style="display:none;"></div>

            <!-- Progress -->
            <div id="updateProgress" class="mt-4" style="display:none;">
                <h6 id="progressTitle">Memproses...</h6>
                <div class="progress" style="height: 28px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         id="progressBar" role="progressbar" style="width:0%">0%</div>
                </div>
                <p class="text-muted small mt-2" id="progressDetail"></p>
            </div>
        </div>
    </div>

    <!-- SECTION: Upload ZIP (Fallback) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Update Manual (Upload ZIP)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Gunakan jika menerima file patch <code>.zip</code> dari developer secara langsung.</p>
            <form action="<?= site_url('admin/updater/patch-files') ?>" method="post"
                  enctype="multipart/form-data" id="patchForm">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <input type="file" class="form-control" name="patch_file" accept=".zip" required>
                        <div class="form-text">ZIP hanya boleh berisi folder <code>app/</code> dan/atau <code>public/</code>. Maks: <?= ini_get('upload_max_filesize') ?></div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-warning w-100" id="btnUploadPatch">
                            <i class="fas fa-upload me-1"></i>Upload & Install
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION: Migrasi Database -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-database me-2"></i>Migrasi Database</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <p class="text-muted mb-2">Jalankan migrasi database manual jika diperlukan.</p>
                    <a href="<?= site_url('admin/updater/run-migrations') ?>" class="btn btn-info text-white"
                       onclick="return confirm('Jalankan migrasi database?')">
                        <i class="fas fa-play-circle me-1"></i>Jalankan Migrasi
                    </a>
                </div>
                <div class="col-lg-5">
                    <?php if (!empty($migrations)): ?>
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#migHistory">
                        <i class="fas fa-history me-1"></i>Riwayat (<?= count($migrations) ?>)
                    </button>
                    <div class="collapse mt-2" id="migHistory">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Version</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach (array_slice($migrations, 0, 10) as $mig): ?>
                                    <tr>
                                        <td><code class="small"><?= esc($mig->version ?? $mig['version'] ?? '-') ?></code></td>
                                        <td><span class="badge bg-success">Applied</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: Backup & Restore -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Backup & Restore Database</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-success h-100">
                        <div class="card-header bg-success text-white py-2">
                            <strong><i class="fas fa-download me-2"></i>Backup</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">Download seluruh database dalam format SQL.</p>
                            <a href="<?= site_url('admin/updater/backup-database') ?>" class="btn btn-success w-100">
                                <i class="fas fa-download me-1"></i>Download Backup
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-danger h-100">
                        <div class="card-header bg-danger text-white py-2">
                            <strong><i class="fas fa-upload me-2"></i>Restore</strong>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger py-1 small mb-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>Akan menimpa semua data!
                            </div>
                            <form action="<?= site_url('admin/updater/restore-database') ?>" method="post"
                                  enctype="multipart/form-data" onsubmit="return confirm('PERINGATAN: Semua data akan ditimpa. Lanjutkan?')">
                                <?= csrf_field() ?>
                                <input type="file" class="form-control form-control-sm mb-2" name="sql_file" accept=".sql" required>
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="fas fa-upload me-1"></i>Restore
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.container-fluid -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Upload patch loading state
    const patchForm = document.getElementById('patchForm');
    const btnUpload = document.getElementById('btnUploadPatch');
    if (patchForm && btnUpload) {
        patchForm.addEventListener('submit', function () {
            btnUpload.disabled = true;
            btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menginstall...';
        });
    }

    // Generate manifest loading state
    const formManifest = document.getElementById('formManifest');
    const btnManifest = document.getElementById('btnManifest');
    if (formManifest && btnManifest) {
        formManifest.addEventListener('submit', function () {
            btnManifest.disabled = true;
            btnManifest.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        });
    }

    // ═══════════ ONLINE UPDATE (Delta) ═══════════
    const btnCheck = document.getElementById('btnCheckUpdate');
    const resultDiv = document.getElementById('updateResult');
    const progressDiv = document.getElementById('updateProgress');

    let updatePayload = null; // store check result for apply

    if (btnCheck) {
        btnCheck.addEventListener('click', function () {
            btnCheck.disabled = true;
            btnCheck.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengecek...';
            if (resultDiv) resultDiv.style.display = 'none';

            fetch('<?= site_url("admin/updater/check-online") ?>', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                btnCheck.disabled = false;
                btnCheck.innerHTML = '<i class="fas fa-search me-2"></i>Cek Update';

                if (!data.success) {
                    showAlert('danger', data.message);
                    return;
                }

                if (!data.has_update) {
                    showAlert('success', `<i class="fas fa-check-circle me-2"></i>Sudah versi terbaru! (v${data.local_version})`);
                    return;
                }

                // Ada update — tampilkan detail
                updatePayload = data;
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = buildUpdateInfo(data);

                // Bind tombol "Update Sekarang"
                const btnApply = document.getElementById('btnApplyUpdate');
                if (btnApply) {
                    btnApply.addEventListener('click', function () {
                        if (!confirm(`Update ke v${data.remote_version}? (${data.total_diff} file akan diperbarui)\n\nBackup otomatis dibuat sebelum update.`)) return;
                        doApplyUpdate(data);
                    });
                }
            })
            .catch(err => {
                btnCheck.disabled = false;
                btnCheck.innerHTML = '<i class="fas fa-search me-2"></i>Cek Update';
                showAlert('danger', 'Error: ' + err.message);
            });
        });
    }

    function doApplyUpdate(data) {
        resultDiv.style.display = 'none';
        progressDiv.style.display = 'block';
        setProgress(10, 'Memulai update...', `Downloading ${data.total_diff} file dari GitHub...`);

        fetch('<?= site_url("admin/updater/apply-online") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({
                changed_files: data.changed_files,
                new_files: data.new_files,
                remote_version: data.remote_version
            })
        })
        .then(r => r.text())
        .then(html => {
            // Response is a full HTML page (result page rendered directly)
            document.open();
            document.write(html);
            document.close();
        })
        .catch(err => {
            progressDiv.style.display = 'none';
            showAlert('danger', 'Update gagal: ' + err.message);
        });

        // Simulate progress while waiting
        let pct = 10;
        const interval = setInterval(() => {
            pct += Math.random() * 8;
            if (pct > 90) { clearInterval(interval); return; }
            setProgress(Math.round(pct), 'Downloading & installing...', `Mohon tunggu, jangan tutup halaman ini.`);
        }, 2000);
    }

    function buildUpdateInfo(data) {
        let fileList = '';
        const maxShow = 20;
        const allFiles = [...(data.changed_files || []), ...(data.new_files || [])];
        const showing = allFiles.slice(0, maxShow);

        showing.forEach(f => {
            const isNew = (data.new_files || []).includes(f);
            const badge = isNew ? '<span class="badge bg-success ms-1">BARU</span>' : '<span class="badge bg-warning ms-1">BERUBAH</span>';
            fileList += `<li class="small"><code>${f}</code>${badge}</li>`;
        });
        if (allFiles.length > maxShow) {
            fileList += `<li class="small text-muted">...dan ${allFiles.length - maxShow} file lainnya</li>`;
        }

        return `
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Update Tersedia!</h5>
                <div class="row g-2 mb-3">
                    <div class="col-sm-4"><strong>Versi Lokal:</strong> <span class="badge bg-secondary">v${data.local_version}</span></div>
                    <div class="col-sm-4"><strong>Versi Remote:</strong> <span class="badge bg-primary">v${data.remote_version}</span></div>
                    <div class="col-sm-4"><strong>File Berbeda:</strong> <span class="badge bg-danger">${data.total_diff}</span></div>
                </div>
                <div class="small mb-2">
                    <strong>Generated:</strong> ${data.generated_at || '-'} &nbsp;|&nbsp;
                    <strong>Total file remote:</strong> ${data.total_remote || '-'}
                </div>
                <hr>
                <div class="mb-3">
                    <strong>Daftar file yang akan diperbarui:</strong>
                    <ul class="mt-1 mb-0 ps-3" style="max-height:250px;overflow-y:auto">${fileList}</ul>
                </div>
                <hr>
                <button type="button" class="btn btn-success btn-lg" id="btnApplyUpdate">
                    <i class="fas fa-download me-2"></i>Update Sekarang
                </button>
                <small class="text-muted ms-2"><i class="fas fa-shield-alt me-1"></i>Backup otomatis dibuat. Session akan di-clear setelah update.</small>
            </div>`;
    }

    function setProgress(pct, title, detail) {
        const bar = document.getElementById('progressBar');
        if (bar) { bar.style.width = pct + '%'; bar.textContent = pct + '%'; }
        const t = document.getElementById('progressTitle');
        if (t) t.textContent = title;
        const d = document.getElementById('progressDetail');
        if (d) d.textContent = detail;
    }

    function showAlert(type, msg) {
        if (resultDiv) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
        }
    }
});
</script>
<?= $this->endSection() ?>
