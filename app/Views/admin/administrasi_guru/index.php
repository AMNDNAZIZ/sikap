<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-2 text-gray-800">📅 Administrasi Guru</h1>
        <p class="mb-4">Manajemen dokumen administrasi pembelajaran Kurikulum Merdeka (CP, TP, ATP, Prota, Prosem).</p>
    </div>
</div>

<div class="row">
    <!-- Configuration Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Mapping Kurikulum</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Mapel & CP Master</div>
                        <p class="mt-2 small text-muted">Hubungkan mapel sekolah dengan standar nasional.</p>
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-sm btn-primary mt-2">Buka Mapping</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-link-45deg fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CP Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Referensi Baku</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Capaian Pembelajaran</div>
                        <p class="mt-2 small text-muted">Daftar CP Kurikulum Merdeka per Fase.</p>
                        <a href="<?= base_url('admin/administrasi-guru/cp') ?>" class="btn btn-sm btn-success mt-2">Lihat CP</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-book fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TP Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Perencanaan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Tujuan Pembelajaran (TP)</div>
                        <p class="mt-2 small text-muted">Turunkan TP dari Capaian Pembelajaran.</p>
                        <a href="<?= base_url('admin/administrasi-guru/tp') ?>" class="btn btn-sm btn-info mt-2 text-white">Kelola TP</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-bullseye fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ATP Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Alur & Urutan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Alur Tujuan Pembelajaran</div>
                        <p class="mt-2 small text-muted">Susun urutan TP dan alokasi waktu (ATP).</p>
                        <a href="<?= base_url('admin/administrasi-guru/atp') ?>" class="btn btn-sm btn-warning mt-2 text-white">Kelola ATP</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-signpost-split fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Output Dokumen</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Prota & Prosem</div>
                        <p class="mt-2 small text-muted">Generate Program Tahunan & Semester.</p>
                        <a href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>" class="btn btn-sm btn-secondary mt-2">Buka Laporan</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-pdf fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array(session()->get('user')['role_id'] ?? null, [1, 2])): ?>
    <!-- Monitoring Card -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Supervisi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Monitoring Adm Guru</div>
                        <p class="mt-2 small text-muted">Pantau kelengkapan ATP dan Promes guru.</p>
                        <a href="<?= base_url('admin/administrasi-guru/monitoring') ?>" class="btn btn-sm btn-danger mt-2">Buka Monitoring</a>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-speedometer2 fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
