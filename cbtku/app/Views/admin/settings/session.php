<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Pengaturan Sesi &amp; Cache</h1>
            <p class="text-muted">Konfigurasikan sistem penyimpanan sesi dan cache aplikasi.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver Sesi Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $driverLabels = ['file' => 'File System', 'database' => 'Database', 'redis' => 'Redis'];
                                echo $driverLabels[$config['driver']] ?? ucfirst($config['driver']);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cache Handler Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $handlerShort = class_basename($activeHandler ?? '');
                                $handlerShort = str_replace('Handler', '', $handlerShort);
                                echo esc($handlerShort ?: 'Unknown');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Item Ter-cache</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $cacheStats !== null ? $cacheStats . ' item' : 'N/A' ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Konfigurasi Sesi & Cache -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>Konfigurasi Sesi &amp; Cache
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('admin/settings/session/update') ?>" method="post" id="settingsForm">
                        <?= csrf_field() ?>

                        <!-- Driver Sesi -->
                        <div class="mb-3">
                            <label for="driver" class="form-label fw-bold">Sistem Penyimpanan (Driver)</label>
                            <select class="form-select" id="driver" name="driver" required>
                                <option value="file" <?= ($config['driver'] == 'file') ? 'selected' : '' ?>>
                                    📁 File System (Default)
                                </option>
                                <option value="database" <?= ($config['driver'] == 'database') ? 'selected' : '' ?>>
                                    🗄️ Database (MySQL/MariaDB)
                                </option>
                                <option value="redis" <?= ($config['driver'] == 'redis') ? 'selected' : '' ?>>
                                    ⚡ Redis (Rekomendasi untuk Performa)
                                </option>
                            </select>
                            <div class="mt-2 p-3 bg-light rounded small">
                                <div id="desc-file" class="driver-desc" style="display: <?= $config['driver'] == 'file' ? 'block' : 'none' ?>;">
                                    <b>File System:</b> Cocok untuk shared hosting tanpa Redis. Kurang stabil jika banyak siswa ujian bersamaan.
                                </div>
                                <div id="desc-database" class="driver-desc" style="display: <?= $config['driver'] == 'database' ? 'block' : 'none' ?>;">
                                    <b>Database:</b> Cukup stabil untuk load sedang, namun dapat memberatkan kerja database utama.
                                </div>
                                <div id="desc-redis" class="driver-desc" style="display: <?= $config['driver'] == 'redis' ? 'block' : 'none' ?>;">
                                    <b>Redis:</b> Sangat cepat dan paling stabil. Sangat direkomendasikan untuk CBT skala besar. Membutuhkan server Redis aktif.
                                </div>
                            </div>
                        </div>

                        <!-- Redis Configuration (only show when redis selected) -->
                        <div id="redis-config" style="display: <?= ($config['driver'] == 'redis') ? 'block' : 'none' ?>;">
                            <hr class="my-3">
                            <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-plug me-2"></i>Koneksi Redis</h6>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="redis_host" class="form-label">Host Redis</label>
                                    <input type="text" class="form-control" id="redis_host" name="redis_host"
                                        value="<?= esc($config['redis_host'] ?? '127.0.0.1') ?>"
                                        placeholder="127.0.0.1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="redis_port" class="form-label">Port</label>
                                    <input type="number" class="form-control" id="redis_port" name="redis_port"
                                        value="<?= esc($config['redis_port'] ?? 6379) ?>"
                                        placeholder="6379" min="1" max="65535">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="redis_password" class="form-label">Password Redis <span class="text-muted">(opsional)</span></label>
                                    <input type="password" class="form-control" id="redis_password" name="redis_password"
                                        value="<?= esc($config['redis_password'] ?? '') ?>"
                                        placeholder="Kosongkan jika tidak ada password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="redis_database" class="form-label">Database Index</label>
                                    <input type="number" class="form-control" id="redis_database" name="redis_database"
                                        value="<?= esc($config['redis_database'] ?? 0) ?>"
                                        placeholder="0" min="0" max="15">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="savePath" class="form-label">Save Path (sesi) <span class="text-muted">— otomatis terisi dari host &amp; port</span></label>
                                <input type="text" class="form-control" id="savePath" name="savePath"
                                    value="<?= esc($config['savePath'] ?? 'tcp://127.0.0.1:6379') ?>"
                                    placeholder="tcp://127.0.0.1:6379">
                                <small class="text-muted">Contoh: <code>tcp://127.0.0.1:6379</code> atau <code>/var/run/redis/redis.sock</code></small>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-clock me-2"></i>Pengaturan Cache</h6>

                        <div class="mb-3">
                            <label for="cache_ttl" class="form-label">Default TTL Cache <span class="text-muted">(detik)</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cache_ttl" name="cache_ttl"
                                    value="<?= esc($config['cache_ttl'] ?? 3600) ?>"
                                    min="60" max="86400" required>
                                <span class="input-group-text" id="ttl-label">detik</span>
                            </div>
                            <small class="text-muted" id="ttl-human">
                                = <?= gmdate('H \j\a\m i \m\e\n\i\t', $config['cache_ttl'] ?? 3600) ?>
                            </small>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm ttl-preset" data-value="1800">30 menit</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ttl-preset" data-value="3600">1 jam</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ttl-preset" data-value="7200">2 jam</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ttl-preset" data-value="21600">6 jam</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ttl-preset" data-value="86400">24 jam</button>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <b>Peringatan:</b> Mengubah driver sesi akan mengeluarkan (logout) semua pengguna yang sedang login, termasuk Anda. Pastikan tidak ada ujian yang sedang berlangsung.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Pengaturan
                            </button>
                            <a href="<?= base_url('admin/settings/session') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel Manajemen Cache -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-trash-alt me-2"></i>Manajemen Cache
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Hapus seluruh data cache aplikasi. Ini akan memaksa aplikasi memuat ulang data dari database pada permintaan berikutnya.</p>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
                        <div>
                            <div class="fw-bold">Total Item Ter-cache</div>
                            <div class="text-muted small">Handler: <?= esc($handlerShort ?? 'Unknown') ?></div>
                        </div>
                        <span class="badge bg-info fs-6" id="cache-count-badge">
                            <?= $cacheStats !== null ? $cacheStats : '?' ?>
                        </span>
                    </div>

                    <button type="button" class="btn btn-danger w-100" id="btnFlushCache">
                        <i class="fas fa-trash me-2"></i>Hapus Semua Cache
                    </button>

                    <div id="flush-result" class="mt-3" style="display:none;"></div>

                    <hr>
                    <h6 class="fw-bold text-secondary">Catatan TTL per Jenis Data</h6>
                    <table class="table table-sm table-borderless small">
                        <tbody>
                            <tr>
                                <td><i class="fas fa-file-alt text-primary me-2"></i>Data Soal (Bank)</td>
                                <td class="text-end text-muted">2 jam</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-vial text-success me-2"></i>Data Tes</td>
                                <td class="text-end text-muted">1 jam</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-user-graduate text-warning me-2"></i>Data Siswa</td>
                                <td class="text-end text-muted">30 menit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panduan Driver -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle me-2"></i>Panduan Pemilihan Driver
                    </h6>
                </div>
                <div class="card-body small">
                    <div class="mb-3">
                        <span class="badge bg-secondary mb-1">Shared Hosting</span>
                        <p class="mb-0">Gunakan <b>File System</b>. Tidak memerlukan konfigurasi tambahan.</p>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-primary mb-1">VPS / Server Sendiri</span>
                        <p class="mb-0">Gunakan <b>Redis</b> untuk performa terbaik, terutama jika ujian diikuti lebih dari 100 siswa serentak.</p>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark mb-1">Database</span>
                        <p class="mb-0">Pilihan tengah jika Redis tidak tersedia. Perhatikan beban database saat ujian berlangsung.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const driverSelect  = document.getElementById('driver');
    const redisConfig   = document.getElementById('redis-config');
    const redisHost     = document.getElementById('redis_host');
    const redisPort     = document.getElementById('redis_port');
    const savePathInput = document.getElementById('savePath');
    const cacheTtlInput = document.getElementById('cache_ttl');
    const ttlHuman      = document.getElementById('ttl-human');

    // ─── Driver toggle ───────────────────────────────────────────────────────
    function updateDriverUI(driver) {
        document.querySelectorAll('.driver-desc').forEach(el => el.style.display = 'none');
        const desc = document.getElementById('desc-' + driver);
        if (desc) desc.style.display = 'block';

        redisConfig.style.display = driver === 'redis' ? 'block' : 'none';
    }

    driverSelect.addEventListener('change', function () {
        updateDriverUI(this.value);
    });

    // ─── Auto-generate savePath from host + port ──────────────────────────
    function syncSavePath() {
        const host = redisHost.value.trim() || '127.0.0.1';
        const port = redisPort.value.trim() || '6379';
        savePathInput.value = 'tcp://' + host + ':' + port;
    }

    if (redisHost && redisPort) {
        redisHost.addEventListener('input', syncSavePath);
        redisPort.addEventListener('input', syncSavePath);
    }

    // ─── TTL human-readable ──────────────────────────────────────────────
    function updateTtlLabel() {
        const secs = parseInt(cacheTtlInput.value, 10);
        if (isNaN(secs) || secs < 0) { ttlHuman.textContent = ''; return; }
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        let label = '= ';
        if (h > 0) label += h + ' jam ';
        if (m > 0) label += m + ' menit ';
        if (s > 0 || (h === 0 && m === 0)) label += s + ' detik';
        ttlHuman.textContent = label.trim();
    }

    cacheTtlInput.addEventListener('input', updateTtlLabel);

    document.querySelectorAll('.ttl-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            cacheTtlInput.value = this.dataset.value;
            updateTtlLabel();
        });
    });

    // ─── Flush Cache ─────────────────────────────────────────────────────
    document.getElementById('btnFlushCache').addEventListener('click', function () {
        if (!confirm('Yakin ingin menghapus seluruh cache aplikasi?')) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';

        const resultDiv = document.getElementById('flush-result');
        resultDiv.style.display = 'none';

        fetch('<?= base_url('admin/settings/cache/flush') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') 
                    ? document.querySelector('meta[name="csrf-token"]').content 
                    : '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
        })
        .then(r => r.json())
        .then(data => {
            resultDiv.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
            resultDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check' : 'times') + '-circle me-2"></i>' + data.message;
            resultDiv.style.display = 'block';

            if (data.success) {
                document.getElementById('cache-count-badge').textContent = '0';
            }
        })
        .catch(err => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<i class="fas fa-times-circle me-2"></i>Terjadi kesalahan: ' + err.message;
            resultDiv.style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash me-2"></i>Hapus Semua Cache';
        });
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
