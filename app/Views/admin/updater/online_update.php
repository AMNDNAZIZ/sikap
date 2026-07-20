<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>🌐 Update Online</h4>
    <a href="<?= base_url('admin/updater') ?>" class="btn btn-outline-secondary btn-sm">
        ← Kembali
    </a>
</div>

<!-- Info Versi -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <small class="text-muted d-block">Versi Lokal (Terpasang)</small>
                <h4 class="fw-bold text-secondary mb-0"><?= esc($localVersion) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-primary">
            <div class="card-body text-center">
                <small class="text-muted d-block">Versi Terbaru (Server)</small>
                <h4 class="fw-bold text-primary mb-0"><?= esc($remoteVersion) ?></h4>
                <small class="text-muted"><?= esc($remoteDate) ?></small>
            </div>
        </div>
    </div>
</div>
<p class="text-muted small mb-4">
    <i class="bi bi-info-circle me-1"></i>
    Pengecekan update berdasarkan perbandingan isi file (hash), bukan nomor versi.
    Jumlah file yang perlu diperbarui bisa berbeda tergantung kondisi aplikasi Anda saat ini.
</p>

<?php if ($totalChanges === 0): ?>
    <div class="alert alert-success text-center py-4">
        <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
        <h5>Aplikasi sudah versi terbaru!</h5>
        <p class="mb-0 text-muted">Semua file sudah sesuai dengan versi <?= esc($remoteVersion) ?>. Tidak ada yang perlu diperbarui.</p>
    </div>
<?php else: ?>
    <?php if ($localVersion === $remoteVersion): ?>
        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Versi sama (<?= esc($localVersion) ?>)</strong> tetapi <?= $totalChanges ?> file berbeda.
            Kemungkinan ada file yang diubah manual di server atau update sebelumnya tidak lengkap.
        </div>
    <?php endif; ?>
    <!-- Ringkasan -->
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
        <div>
            <strong><?= $totalChanges ?> file</strong> perlu diperbarui
            (<?= count($changedFiles) ?> berubah, <?= count($newFiles) ?> baru).
            <br><small class="text-muted">Hanya file yang berbeda yang akan didownload — hemat bandwidth dan waktu.</small>
        </div>
    </div>

    <!-- Daftar File Berubah -->
    <?php if (!empty($changedFiles)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-warning bg-opacity-10 py-2">
                <h6 class="mb-0"><i class="bi bi-pencil-square me-1"></i> File Berubah (<?= count($changedFiles) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                    <?php foreach ($changedFiles as $f): ?>
                        <div class="list-group-item py-1 px-3 small font-monospace"><?= esc($f) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($newFiles)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success bg-opacity-10 py-2">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i> File Baru (<?= count($newFiles) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($newFiles as $f): ?>
                        <div class="list-group-item py-1 px-3 small font-monospace"><?= esc($f) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tombol Apply -->
    <form action="<?= base_url('admin/updater/apply-online') ?>" method="post" id="apply-form">
        <?= csrf_field() ?>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4" id="btn-apply">
                <i class="bi bi-cloud-download me-1"></i> Update Sekarang (<?= $totalChanges ?> file)
            </button>
            <a href="<?= base_url('admin/updater') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

    <script>
    document.getElementById('apply-form').addEventListener('submit', function(e) {
        if (!confirm('Mulai update online? Pastikan sudah backup database.\n\nProses akan mendownload ' + <?= $totalChanges ?> + ' file dari server.')) {
            e.preventDefault();
            return;
        }
        var btn = document.getElementById('btn-apply');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengunduh & memperbarui... Harap tunggu';
    });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>
