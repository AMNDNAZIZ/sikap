<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <div>
            <h3 class="mb-0"><i class="fas fa-cloud-download-alt me-2 text-success"></i>Update Online</h3>
            <p class="text-muted mb-0">Hasil pengecekan update dari server GitHub</p>
        </div>
        <div class="ms-auto">
            <a href="<?= site_url('admin/updater') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <?php if (!empty($has_update) && $has_update): ?>
    <!-- UPDATE TERSEDIA -->
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Update Tersedia!</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3">
                        <div class="small text-muted">Versi Lokal</div>
                        <span class="badge bg-secondary fs-6">v<?= esc($local_version ?? '?') ?></span>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <i class="fas fa-arrow-right fa-2x text-muted"></i>
                </div>
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3 border-primary">
                        <div class="small text-muted">Versi Remote</div>
                        <span class="badge bg-primary fs-6">v<?= esc($remote_version ?? '?') ?></span>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="border rounded p-3">
                        <div class="small text-muted">File Berubah</div>
                        <span class="badge bg-danger fs-6"><?= count($changed_files ?? []) ?></span>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="border rounded p-3">
                        <div class="small text-muted">File Baru</div>
                        <span class="badge bg-success fs-6"><?= count($new_files ?? []) ?></span>
                    </div>
                </div>
            </div>

            <!-- Daftar file -->
            <div class="mb-4">
                <h6>File yang akan diperbarui:</h6>
                <div class="bg-light rounded p-3" style="max-height:300px;overflow-y:auto">
                    <ul class="mb-0 ps-3 small">
                        <?php foreach (($changed_files ?? []) as $f): ?>
                        <li><code><?= esc($f) ?></code> <span class="badge bg-warning">BERUBAH</span></li>
                        <?php endforeach; ?>
                        <?php foreach (($new_files ?? []) as $f): ?>
                        <li><code><?= esc($f) ?></code> <span class="badge bg-success">BARU</span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Tombol Update -->
            <form action="<?= site_url('admin/updater/apply-online') ?>" method="post" id="formApply">
                <?= csrf_field() ?>
                <input type="hidden" name="remote_version" value="<?= esc($remote_version ?? '') ?>">
                <?php foreach (($changed_files ?? []) as $i => $f): ?>
                <input type="hidden" name="changed_files[]" value="<?= esc($f) ?>">
                <?php endforeach; ?>
                <?php foreach (($new_files ?? []) as $i => $f): ?>
                <input type="hidden" name="new_files[]" value="<?= esc($f) ?>">
                <?php endforeach; ?>

                <button type="submit" class="btn btn-success btn-lg"
                        onclick="return confirm('Update ke v<?= esc($remote_version ?? '') ?>?\n\nBackup dibuat otomatis. Session akan di-clear.')">
                    <i class="fas fa-download me-2"></i>Update Sekarang
                </button>
                <small class="text-muted ms-2">
                    <i class="fas fa-shield-alt me-1"></i>Backup otomatis. Migrasi DB dijalankan otomatis.
                </small>
            </form>
        </div>
    </div>

    <?php elseif (isset($has_update) && !$has_update): ?>
    <!-- SUDAH TERBARU -->
    <div class="card shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
            <h4 class="text-success">Aplikasi Sudah Versi Terbaru!</h4>
            <p class="text-muted">Versi saat ini: <strong>v<?= esc($local_version ?? '?') ?></strong></p>
            <p class="small text-muted">Versi remote: v<?= esc($remote_version ?? '?') ?> | Generated: <?= esc($generated_at ?? '-') ?></p>
        </div>
    </div>

    <?php else: ?>
    <!-- ERROR / NO DATA -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Belum ada data. Klik "Cek Update" dari halaman updater.
    </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
