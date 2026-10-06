<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Dashboard Pembina Ekskul</h1>
            <p class="text-muted">Tahun Ajaran: <?= $activeYear ? esc($activeYear['year']) : '-' ?></p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($myEkskuls)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Anda belum ditugaskan sebagai pembina ekstrakurikuler pada tahun ajaran ini.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($myEkskuls as $e): ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        <?= esc($e['kode']) ?>
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= esc($e['name']) ?>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= base_url('admin/ekskul-pembina/members/' . $e['ekskul_id']) ?>" class="btn btn-sm btn-info mb-1">
                                            <i class="fas fa-users"></i> Anggota
                                        </a>
                                        <a href="<?= base_url('admin/ekskul-pembina/jurnal/' . $e['ekskul_id']) ?>" class="btn btn-sm btn-success mb-1">
                                            <i class="fas fa-book"></i> Jurnal & Absen
                                        </a>
                                        <a href="<?= base_url('admin/ekskul-pembina/nilai/' . $e['ekskul_id']) ?>" class="btn btn-sm btn-warning mb-1">
                                            <i class="fas fa-star"></i> Penilaian Akhir
                                        </a>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-futbol fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
