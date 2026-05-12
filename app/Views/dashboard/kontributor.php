<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid p-0">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold mb-1">Selamat Datang,
                <?= esc($user['fullname'] ?? $user['username']) ?>!
            </h3>
            <p class="text-muted">Kelola konten berita dan dokumentasi kegiatan sekolah hari ini.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-newspaper fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Total Berita & Artikel</h6>
                            <h3 class="fw-bold mb-0">
                                <?= $stats['total_articles'] ?? 0 ?>
                            </h3>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/cms/articles') ?>"
                        class="btn btn-outline-primary btn-sm w-100 rounded-3">
                        Kelola Artikel <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-camera fs-4 text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Total Dokumentasi</h6>
                            <h3 class="fw-bold mb-0">
                                <?= $stats['total_activities'] ?? 0 ?>
                            </h3>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/cms/activities') ?>"
                        class="btn btn-outline-success btn-sm w-100 rounded-3">
                        Kelola Dokumentasi <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">Aksi Cepat</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <a href="<?= base_url('admin/cms/articles/create') ?>"
                                class="btn btn-light w-100 py-3 rounded-4 border">
                                <i class="bi bi-plus-circle fs-4 d-block mb-1 text-primary"></i>
                                <span class="small fw-bold">Tambah Berita</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4">
                            <a href="<?= base_url('admin/cms/activities/create') ?>"
                                class="btn btn-light w-100 py-3 rounded-4 border">
                                <i class="bi bi-camera-plus fs-4 d-block mb-1 text-success"></i>
                                <span class="small fw-bold">Unggah Foto</span>
                            </a>
                        </div>
                        <div class="col-12 col-md-4">
                            <a href="<?= base_url('/') ?>" target="_blank"
                                class="btn btn-light w-100 py-3 rounded-4 border">
                                <i class="bi bi-globe fs-4 d-block mb-1 text-info"></i>
                                <span class="small fw-bold">Lihat Website</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>