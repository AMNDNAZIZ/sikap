<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>🔄 System Updater</h4>
</div>

<!-- Flash Messages -->
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

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('warning') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


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

                        <!-- Deployment Status -->
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">

                                <span class="d-block text-muted small mb-1">
                                    STATUS DEPLOYMENT
                                </span>

                                <strong class="text-success">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Aktif & Berjalan
                                </strong>

                            </div>
                        </div>

                        <!-- Last Deployment -->
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">

                                <span class="d-block text-muted small mb-1">
                                    WAKTU DEPLOY TERAKHIR
                                </span>

                                <strong>
                                    <?= esc($deployStatus['deploy_time']) ?>
                                </strong>

                            </div>
                        </div>

                        <!-- Commit Hash -->
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded text-center h-100">

                                <span class="d-block text-muted small mb-1">
                                    COMMIT HASH
                                </span>

                                <code class="fw-bold fs-6">
                                    <?= esc($deployStatus['commit_hash']) ?>
                                </code>

                            </div>
                        </div>

                        <!-- Commit Message -->
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded h-100">

                                <span class="d-block text-muted small mb-1">
                                    PESAN COMMIT & AUTHOR
                                </span>

                                <div
                                    class="text-truncate fw-bold"
                                    title="<?= esc($deployStatus['message']) ?>"
                                >
                                    <?= esc($deployStatus['message']) ?>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    Oleh:
                                    <?= esc($deployStatus['author']) ?>
                                </small>

                            </div>
                        </div>

                    </div>

                <?php else: ?>

                    <div class="d-flex align-items-center py-2">

                        <i class="bi bi-info-circle-fill text-info fs-3 me-3"></i>

                        <div>
                            <strong>
                                Belum ada riwayat deployment otomatis yang terdeteksi.
                            </strong>

                            <br>

                            <span class="text-muted small">
                                Setelah update dirilis ke repository utama dan dideploy
                                ke server, informasi commit dan waktu deployment
                                terakhir akan muncul di sini secara otomatis.
                            </span>
                        </div>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>


    <!-- Database Backup & Restore -->
    <div class="col-12">

        <div class="card shadow-sm border-danger">

            <div class="card-header bg-danger text-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        💾 Backup & Restore Database
                    </h5>

                    <span class="badge bg-light text-danger">
                        PENTING
                    </span>

                </div>

            </div>

            <div class="card-body">

                <div class="row">

                    <!-- Backup -->
                    <div class="col-md-6 border-end">

                        <h6 class="text-danger mb-3">
                            📥 Backup Database
                        </h6>

                        <p class="small mb-3">

                            <strong>
                                Selalu backup sebelum melakukan update!
                            </strong>

                            <br>

                            File backup berformat
                            <code>.sql</code>
                            dan dapat digunakan untuk restore apabila terjadi masalah.

                        </p>

                        <a
                            href="<?= base_url('admin/updater/backup-database') ?>"
                            class="btn btn-danger w-100"
                        >
                            💾 Download Backup
                        </a>

                        <small class="text-muted d-block mt-2">

                            <i class="bi bi-info-circle"></i>

                            Backup disimpan di
                            <code>writable/backups/</code>

                        </small>

                    </div>


                    <!-- Restore -->
                    <div class="col-md-6">

                        <h6 class="text-warning mb-3">
                            📤 Restore Database
                        </h6>

                        <p class="small mb-3">

                            <strong class="text-danger">
                                ⚠️ Hati-hati!
                            </strong>

                            Restore akan
                            <strong>menimpa</strong>
                            database saat ini.

                        </p>

                        <form
                            action="<?= base_url('admin/updater/restore-database') ?>"
                            method="post"
                            enctype="multipart/form-data"
                        >

                            <?= csrf_field() ?>

                            <div class="mb-3">

                                <label
                                    for="sql_file"
                                    class="form-label small"
                                >
                                    Pilih File Backup (.sql)
                                </label>

                                <input
                                    class="form-control form-control-sm"
                                    type="file"
                                    id="sql_file"
                                    name="sql_file"
                                    accept=".sql"
                                    required
                                >

                            </div>

                            <button
                                type="submit"
                                class="btn btn-warning w-100"
                                onclick="return confirm('PERINGATAN: Ini akan menimpa database saat ini! Yakin ingin restore?')"
                            >
                                ⚠️ Restore Database
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Online Application Update -->
    <div class="col-12">

        <div class="card shadow-sm border-primary">

            <div class="card-header bg-primary text-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        🌐 Update Aplikasi Online
                    </h5>

                    <span class="badge bg-light text-primary">
                        OTOMATIS
                    </span>

                </div>

            </div>

            <div class="card-body">

                <div class="row align-items-center">

                    <div class="col-md-8">

                        <h6 class="fw-bold mb-2">
                            Cek dan terapkan update aplikasi
                        </h6>

                        <p class="mb-2">
                            Sistem akan memeriksa repository resmi untuk mengetahui
                            apakah tersedia versi aplikasi terbaru.
                        </p>

                        <small class="text-muted d-block">
                            <i class="bi bi-check-circle me-1"></i>
                            Tidak perlu upload file ZIP secara manual.
                        </small>

                        <small class="text-muted d-block">
                            <i class="bi bi-cloud-arrow-down me-1"></i>
                            File update diambil dari repository resmi.
                        </small>

                        <small class="text-muted d-block">
                            <i class="bi bi-lightning-charge me-1"></i>
                            Hanya file yang diperlukan yang akan diperbarui.
                        </small>

                    </div>

                    <div class="col-md-4 text-end">

                        <a
                            href="<?= base_url('admin/updater/check-online') ?>"
                            class="btn btn-primary btn-lg"
                            id="btn-check-update"
                        >
                            <i class="bi bi-cloud-arrow-down me-1"></i>
                            Cek Update
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Database Migration -->
    <div class="col-12">

        <div class="card shadow-sm border-success">

            <div class="card-header bg-success text-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        🗄️ Update Database
                    </h5>

                    <span class="badge bg-light text-success">
                        MIGRATION
                    </span>

                </div>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">

                        <p class="text-muted small mb-3">

                            Jalankan database migration setelah aplikasi
                            mendapatkan update yang membutuhkan perubahan
                            struktur database.

                        </p>

                        <div class="alert alert-info py-2 small">

                            <i class="bi bi-info-circle-fill me-1"></i>

                            Migration dirancang untuk menyesuaikan struktur
                            database dengan versi aplikasi terbaru tanpa
                            menghapus data yang sudah ada.

                        </div>

                        <div class="d-grid">

                            <a
                                href="<?= base_url('admin/updater/run-migrations') ?>"
                                class="btn btn-success"
                                id="btn-migration"
                            >
                                ⚡ Jalankan Migrasi Database
                            </a>

                        </div>

                    </div>


                    <!-- Migration History -->
                    <div class="col-md-6 border-start">

                        <h6 class="text-muted small mb-2">
                            Riwayat Migrasi
                        </h6>

                        <?php if (!empty($migrations)): ?>

                            <div
                                class="list-group list-group-flush small"
                                style="max-height: 220px; overflow-y: auto;"
                            >

                                <?php foreach (array_reverse($migrations) as $mig): ?>

                                    <div
                                        class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start"
                                    >

                                        <div
                                            style="flex: 1; min-width: 0;"
                                        >

                                            <div
                                                class="fw-bold text-truncate"
                                                title="<?= esc($mig->class) ?>"
                                            >
                                                <?= esc($mig->class) ?>
                                            </div>

                                            <div
                                                class="text-muted"
                                                style="font-size: 0.75rem;"
                                            >
                                                <?= esc($mig->version) ?>
                                            </div>

                                        </div>

                                        <span
                                            class="badge bg-secondary ms-2"
                                            style="font-size: 0.7rem;"
                                        >
                                            <?= date('d/m H:i', $mig->time) ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <p class="text-muted fst-italic mb-0 small">
                                Belum ada riwayat migrasi.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Update Information -->
    <div class="col-12">

        <div class="alert alert-secondary mb-0">

            <div class="d-flex align-items-start">

                <i class="bi bi-shield-check fs-4 me-3"></i>

                <div>

                    <strong>
                        Prosedur Update yang Disarankan
                    </strong>

                    <ol class="small mb-0 mt-2">

                        <li>
                            Backup database terlebih dahulu.
                        </li>

                        <li>
                            Klik <strong>Cek Update</strong> untuk memeriksa
                            versi terbaru.
                        </li>

                        <li>
                            Terapkan update aplikasi jika tersedia.
                        </li>

                        <li>
                            Jalankan database migration jika diperlukan.
                        </li>

                        <li>
                            Periksa kembali aplikasi setelah proses selesai.
                        </li>

                    </ol>

                </div>

            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // Tombol cek update
    const checkUpdateButton =
        document.getElementById('btn-check-update');

    if (checkUpdateButton) {

        checkUpdateButton.addEventListener('click', function () {

            this.classList.add('disabled');

            this.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Memeriksa Update...';

        });

    }


    // Tombol migration
    const migrationButton =
        document.getElementById('btn-migration');

    if (migrationButton) {

        migrationButton.addEventListener('click', function (e) {

            if (!confirm(
                'Jalankan database migration sekarang?\n\n' +
                'Pastikan Anda sudah melakukan backup database terlebih dahulu.'
            )) {

                e.preventDefault();
                return;

            }

            this.classList.add('disabled');

            this.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Menjalankan Migrasi...';

        });

    }

});

</script>

<?= $this->endSection() ?>