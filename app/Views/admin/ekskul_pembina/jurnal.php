<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Jurnal & Absensi: <?= esc($ekskul['name']) ?></h1>
            <div>
                <a href="<?= base_url('admin/ekskul-pembina') ?>" class="btn btn-secondary mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                </a>
                <a href="<?= base_url('admin/ekskul-pembina/jurnal/create/' . $ekskul['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Isi Jurnal & Absensi
                </a>
            </div>
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Pelaksanaan</th>
                            <th>Materi / Kegiatan</th>
                            <th>Pembina (Pengisi)</th>
                            <th class="text-center">Status Verifikasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jurnals as $i => $j) : 
                            $vStatus = $j['verification_status'] ?? 'pending';
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= date('d M Y', strtotime($j['date'])) ?></td>
                                <td><?= esc($j['materi']) ?></td>
                                <td><?= esc($j['pembina_name']) ?></td>
                                <td class="text-center">
                                    <?php if ($vStatus === 'verified'): ?>
                                        <span class="badge bg-success text-white">
                                            <i class="fas fa-check-circle"></i> Terverifikasi
                                        </span>
                                    <?php elseif ($vStatus === 'rejected'): ?>
                                        <span class="badge bg-danger text-white" title="<?= esc($j['verification_notes'] ?? '') ?>">
                                            <i class="fas fa-times-circle"></i> Ditolak
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock"></i> Menunggu Verifikasi
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/ekskul-pembina/jurnal/edit/' . $j['id']) ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit Absensi
                                    </a>
                                    <!-- Hapus -->
                                    <form action="<?= base_url('admin/ekskul-pembina/jurnal/delete/' . $j['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jurnal ini beserta data absensinya?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        if ($('.datatable').length) {
            $('.datatable').DataTable();
        }
    });
</script>
<?= $this->endSection() ?>
