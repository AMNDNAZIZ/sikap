<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-arrow-repeat"></i> System Updater</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Current Version Info -->
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Informasi Versi</h5>
                        <p class="mb-1"><strong>Versi Saat Ini:</strong> <span id="current_version"><?= APP_VERSION ?? '1.0.0' ?></span></p>
                        <p class="mb-0"><strong>Terakhir Update:</strong> <?= LAST_UPDATE ?? 'Belum pernah' ?></p>
                    </div>

                    <!-- Online Update Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-cloud-download"></i> Update Online (Otomatis)</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Cek dan install update terbaru dari server secara otomatis.</p>
                            
                            <!-- Check Update Button -->
                            <button type="button" class="btn btn-primary btn-lg" id="btn_check_update">
                                <i class="bi bi-search"></i> Cek Update Online
                            </button>
                            
                            <!-- Update Info (Hidden by default) -->
                            <div id="update_info" class="mt-4" style="display: none;">
                                <div class="alert alert-warning">
                                    <h5><i class="bi bi-exclamation-triangle"></i> Update Tersedia!</h5>
                                    <p class="mb-2"><strong>Versi Terbaru:</strong> <span id="latest_version"></span></p>
                                    <p class="mb-2"><strong>Tanggal Rilis:</strong> <span id="release_date"></span></p>
                                    <p class="mb-2"><strong>Judul:</strong> <span id="update_title"></span></p>
                                    <p class="mb-2"><strong>Ukuran File:</strong> <span id="file_size"></span></p>
                                    <hr>
                                    <h6>Changelog:</h6>
                                    <div id="changelog" class="small"></div>
                                    <hr>
                                    <button type="button" class="btn btn-success btn-lg" id="btn_install_update">
                                        <i class="bi bi-download"></i> Download & Install Update
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Progress (Hidden by default) -->
                            <div id="update_progress" class="mt-4" style="display: none;">
                                <h6 id="progress_text">Memproses...</h6>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         id="progress_bar" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <p class="text-muted small mt-2" id="progress_detail"></p>
                            </div>
                        </div>
                    </div>



                    <!-- Manual Update Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-upload"></i> Update Manual (Upload ZIP)</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Upload file patch ZIP secara manual jika update online tidak tersedia.</p>
                            
                            <form action="<?= site_url('admin/updater/patchFiles') ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label for="patch_file" class="form-label">Pilih File Patch (ZIP)</label>
                                    <input type="file" class="form-control" id="patch_file" name="patch_file" accept=".zip" required>
                                    <div class="form-text">File harus berformat .zip dan berisi folder app/, public/, atau modules/</div>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-upload"></i> Upload & Install Patch
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Database Migration Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-database"></i> Database Migration</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Jalankan migrasi database jika ada perubahan struktur tabel.</p>
                            
                            <form action="<?= site_url('admin/updater/runMigrations') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-info" onclick="return confirm('Yakin ingin menjalankan migrasi database?')">
                                    <i class="bi bi-play-circle"></i> Jalankan Migrasi Database
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Backup & Restore Section -->
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-shield-check"></i> Backup & Restore</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Backup Database</h6>
                                    <p class="text-muted small">Download backup database dalam format SQL.</p>
                                    <form action="<?= site_url('admin/updater/backupDatabase') ?>" method="post">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-dark">
                                            <i class="bi bi-download"></i> Backup Database
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <h6>Restore Database</h6>
                                    <p class="text-muted small">Upload file SQL untuk restore database.</p>
                                    <form action="<?= site_url('admin/updater/restoreDatabase') ?>" method="post" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <div class="input-group">
                                            <input type="file" class="form-control" name="sql_file" accept=".sql" required>
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('PERINGATAN: Restore akan menimpa database saat ini. Lanjutkan?')">
                                                <i class="bi bi-upload"></i> Restore
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCheckUpdate = document.getElementById('btn_check_update');
    const btnInstallUpdate = document.getElementById('btn_install_update');
    const updateInfo = document.getElementById('update_info');
    const updateProgress = document.getElementById('update_progress');
    
    let updateData = null;
    
    // Check Update
    if (btnCheckUpdate) {
        btnCheckUpdate.addEventListener('click', function() {
            btnCheckUpdate.disabled = true;
            btnCheckUpdate.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengecek...';
            
            fetch('<?= site_url('admin/updater/checkOnlineUpdate') ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                btnCheckUpdate.disabled = false;
                btnCheckUpdate.innerHTML = '<i class="bi bi-search"></i> Cek Update Online';
                
                if (!data.success) {
                    Swal.fire('Error', data.message, 'error');
                    return;
                }
                
                if (!data.data.has_update) {
                    Swal.fire({
                        title: 'Sudah Terbaru',
                        text: data.data.message,
                        icon: 'success'
                    });
                    return;
                }
                
                // Show update info
                updateData = data.data;
                document.getElementById('latest_version').textContent = updateData.latest_version;
                document.getElementById('release_date').textContent = updateData.release_date;
                document.getElementById('update_title').textContent = updateData.title;
                document.getElementById('file_size').textContent = formatBytes(updateData.file_size);
                document.getElementById('changelog').innerHTML = formatChangelog(updateData.changelog);
                
                updateInfo.style.display = 'block';
            })
            .catch(error => {
                btnCheckUpdate.disabled = false;
                btnCheckUpdate.innerHTML = '<i class="bi bi-search"></i> Cek Update Online';
                Swal.fire('Error', 'Gagal menghubungi server: ' + error.message, 'error');
            });
        });
    }
    
    // Install Update
    if (btnInstallUpdate) {
        btnInstallUpdate.addEventListener('click', function() {
            if (!updateData) return;
            
            Swal.fire({
                title: 'Install Update?',
                html: `Versi <b>${updateData.latest_version}</b> akan diinstall.<br>` +
                      `Ukuran: ${formatBytes(updateData.file_size)}<br><br>` +
                      `<small class="text-muted">Backup otomatis akan dibuat sebelum update.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Install!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    installUpdate();
                }
            });
        });
    }
    
    function installUpdate() {
        updateInfo.style.display = 'none';
        updateProgress.style.display = 'block';
        
        // Step 1: Download
        setProgress(10, 'Downloading update...', 'Menghubungi server...');
        
        fetch('<?= site_url('admin/updater/downloadUpdate') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({
                version: updateData.latest_version,
                file_hash: updateData.file_hash
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message);
            }
            
            setProgress(50, 'Download selesai', 'Memulai instalasi...');
            
            // Step 2: Install
            setTimeout(() => {
                installDownloadedUpdate(data.file_path, updateData.latest_version);
            }, 1000);
        })
        .catch(error => {
            updateProgress.style.display = 'none';
            Swal.fire('Error', 'Download gagal: ' + error.message, 'error');
        });
    }
    
    function installDownloadedUpdate(filePath, version) {
        setProgress(60, 'Installing...', 'Backup file lama...');
        
        fetch('<?= site_url('admin/updater/installOnlineUpdate') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({
                file_path: filePath,
                version: version
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message);
            }
            
            setProgress(100, 'Selesai!', data.message);
            
            setTimeout(() => {
                Swal.fire({
                    title: 'Update Berhasil!',
                    html: data.message + '<br><br><small>Halaman akan di-refresh...</small>',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }, 1000);
        })
        .catch(error => {
            updateProgress.style.display = 'none';
            Swal.fire('Error', 'Instalasi gagal: ' + error.message, 'error');
        });
    }
    
    function setProgress(percent, text, detail) {
        document.getElementById('progress_bar').style.width = percent + '%';
        document.getElementById('progress_bar').textContent = percent + '%';
        document.getElementById('progress_text').textContent = text;
        document.getElementById('progress_detail').textContent = detail;
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    function formatChangelog(changelog) {
        if (!changelog) return '<p class="text-muted">Tidak ada changelog</p>';
        
        // If changelog is JSON string, parse it
        try {
            const parsed = JSON.parse(changelog);
            let html = '<ul class="list-unstyled">';
            
            if (parsed.added && parsed.added.length > 0) {
                html += '<li><strong class="text-success">✓ Ditambahkan:</strong><ul>';
                parsed.added.forEach(item => html += `<li>${item}</li>`);
                html += '</ul></li>';
            }
            
            if (parsed.fixed && parsed.fixed.length > 0) {
                html += '<li><strong class="text-primary">🔧 Diperbaiki:</strong><ul>';
                parsed.fixed.forEach(item => html += `<li>${item}</li>`);
                html += '</ul></li>';
            }
            
            if (parsed.changed && parsed.changed.length > 0) {
                html += '<li><strong class="text-warning">⚠ Diubah:</strong><ul>';
                parsed.changed.forEach(item => html += `<li>${item}</li>`);
                html += '</ul></li>';
            }
            
            html += '</ul>';
            return html;
        } catch (e) {
            // If not JSON, return as plain text
            return '<p>' + changelog.replace(/\n/g, '<br>') + '</p>';
        }
    }
});
</script>
<?= $this->endSection() ?>
