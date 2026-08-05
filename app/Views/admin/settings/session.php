<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">⚙️ Pengaturan Cache CBT</h4>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Status Cache -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="bi bi-speedometer2 me-1"></i> Status Cache</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="fw-bold">Handler Aktif:</td>
                        <td><code><?= esc(basename(str_replace('\\', '/', $activeHandler))) ?></code></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Driver Dipilih:</td>
                        <td><span class="badge bg-primary"><?= esc($config['driver']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">TTL:</td>
                        <td><?= $config['cache_ttl'] ?> detik</td>
                    </tr>
                    <?php if ($cacheStats !== null): ?>
                    <tr>
                        <td class="fw-bold">Items Cached:</td>
                        <td><?= $cacheStats ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <hr>
                <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btn-flush">
                    <i class="bi bi-trash me-1"></i> Hapus Semua Cache
                </button>
            </div>
        </div>
    </div>

    <!-- Form Konfigurasi -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-gear me-1"></i> Konfigurasi</h6>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/settings/session/update') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cache Driver</label>
                        <select name="driver" class="form-select" id="driver-select">
                            <option value="file" <?= $config['driver'] == 'file' ? 'selected' : '' ?>>
                                📁 File (Default — cocok untuk server kecil)
                            </option>
                            <option value="database" <?= $config['driver'] == 'database' ? 'selected' : '' ?>>
                                🗄️ Database (Stabil — gunakan tabel ci_cache)
                            </option>
                            <option value="redis" <?= $config['driver'] == 'redis' ? 'selected' : '' ?>>
                                🚀 Redis (Tercepat — perlu Redis server)
                            </option>
                        </select>
                        <small class="text-muted">
                            <strong>Rekomendasi:</strong> File untuk &lt;50 siswa, Database untuk 50-200 siswa, Redis untuk &gt;200 siswa bersamaan.
                        </small>
                    </div>

                    <!-- Redis Config -->
                    <div id="redis-config" class="<?= $config['driver'] != 'redis' ? 'd-none' : '' ?>">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small">Redis Host</label>
                                <input type="text" name="redis_host" class="form-control form-control-sm" value="<?= esc($config['redis_host']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Port</label>
                                <input type="number" name="redis_port" class="form-control form-control-sm" value="<?= $config['redis_port'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Database</label>
                                <input type="number" name="redis_database" class="form-control form-control-sm" value="<?= $config['redis_database'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Password (kosongkan jika tidak ada)</label>
                                <input type="password" name="redis_password" class="form-control form-control-sm" value="<?= esc($config['redis_password']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cache TTL (Time to Live)</label>
                        <div class="input-group">
                            <input type="number" name="cache_ttl" class="form-control" value="<?= $config['cache_ttl'] ?>" min="60" max="86400">
                            <span class="input-group-text">detik</span>
                        </div>
                        <small class="text-muted">Berapa lama data soal di-cache sebelum diambil ulang dari database. Default: 3600 (1 jam).</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                    </button>
                </form>
            </div>
        </div>

        <!-- Info -->
        <div class="alert alert-info mt-3">
            <h6 class="fw-bold"><i class="bi bi-info-circle me-1"></i> Panduan Cache untuk CBT</h6>
            <ul class="mb-0 small">
                <li><strong>File:</strong> Paling mudah, tidak perlu setup tambahan. Cocok untuk ujian kecil.</li>
                <li><strong>Database:</strong> Perlu tabel <code>ci_cache</code>. Lebih stabil untuk multi-server.</li>
                <li><strong>Redis:</strong> Performa terbaik. Perlu install Redis di server. Ideal untuk ujian massal (>200 siswa).</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('driver-select').addEventListener('change', function() {
    document.getElementById('redis-config').classList.toggle('d-none', this.value !== 'redis');
});

document.getElementById('btn-flush').addEventListener('click', async function() {
    if (!confirm('Hapus semua data cache? Ini tidak mempengaruhi data ujian yang tersimpan di database.')) return;
    this.disabled = true;
    try {
        const res = await fetch('<?= base_url("admin/settings/session/flush") ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest', '<?= csrf_token() ?>': '<?= csrf_hash() ?>'}
        });
        const data = await res.json();
        alert(data.message);
        location.reload();
    } catch (e) {
        alert('Error: ' + e.message);
    }
    this.disabled = false;
});
</script>

<?= $this->endSection() ?>
