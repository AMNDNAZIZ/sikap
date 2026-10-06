<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Ekstrakurikuler Saya</h1>
            <p class="text-muted">Tahun Ajaran: <?= $activeYear ? esc($activeYear['year']) : '-' ?></p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Ekskul Saya -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ekstrakurikuler Yang Diikuti</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (empty($myEkskuls)): ?>
                    <div class="col-12 text-center text-muted my-3">
                        Anda belum tergabung dalam ekstrakurikuler apapun.
                    </div>
                <?php else: ?>
                    <?php foreach ($myEkskuls as $e): ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                <?= esc($e['kode']) ?> 
                                                (<?= esc($e['category']) ?>)
                                            </div>
                                            <div class="h5 mb-1 font-weight-bold text-gray-800">
                                                <?= esc($e['name']) ?>
                                            </div>
                                            <div class="mb-2">
                                                Status: 
                                                <?php if ($e['status'] == 'aktif'): ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php elseif ($e['status'] == 'pending'): ?>
                                                    <span class="badge badge-warning">Menunggu Persetujuan</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?= esc($e['status']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($e['status'] == 'aktif'): ?>
                                                <a href="<?= base_url('siswa/ekskul/detail/' . $e['ekskul_id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    Lihat Progress & Nilai
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-certificate fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Katalog Ekskul -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilihan Ekstrakurikuler Lainnya</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Ekstrakurikuler</th>
                            <th>Kategori</th>
                            <th>Deskripsi Singkat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($availableEkskuls)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada ekstrakurikuler lain yang tersedia.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($availableEkskuls as $i => $a): ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $i + 1 ?></td>
                                    <td class="align-middle font-weight-bold"><?= esc($a['name']) ?></td>
                                    <td class="align-middle">
                                        <span class="badge badge-<?= $a['category'] == 'wajib' ? 'danger' : 'info' ?>">
                                            <?= esc($a['category']) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle"><?= esc($a['description']) ?></td>
                                    <td class="align-middle text-center">
                                        <form action="<?= base_url('siswa/ekskul/daftar/' . $a['id']) ?>" method="post" onsubmit="return confirm('Daftar ke ekstrakurikuler <?= esc($a['name']) ?>?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sign-in-alt"></i> Daftar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
