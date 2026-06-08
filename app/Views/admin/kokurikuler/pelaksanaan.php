<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Kokurikuler - Pelaksanaan</h4>
            <small class="text-muted">Dokumentasi pelaksanaan kegiatan kokurikuler</small>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <h5>Belum ada rencana kokurikuler yang selesai</h5>
                    <p>Selesaikan rencana kokurikuler terlebih dahulu di menu Perencanaan</p>
                    <a href="<?= base_url('admin/kokurikuler') ?>" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Ke Perencanaan
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="datatable" class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tahun Ajaran</th>
                                <th>Semester</th>
                                <th>Fase</th>
                                <th>Level Kelas</th>
                                <th>Tema</th>
                                <th>Total Kegiatan</th>
                                <th>Progress</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($doc['year_name']) ?></td>
                                    <td><span class="badge bg-secondary">Semester <?= esc($doc['semester']) ?></span></td>
                                    <td><span class="badge bg-info">Fase <?= esc($doc['fase']) ?></span></td>
                                    <td><?= esc($doc['level_kelas']) ?></td>
                                    <td><?= esc($doc['tema']) ?></td>
                                    <td class="text-center"><?= $doc['jumlah_pertemuan'] ?> pertemuan</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?= $doc['summary']['persentase_terlaksana'] ?>%">
                                                    <?= $doc['summary']['persentase_terlaksana'] ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= $doc['summary']['terlaksana'] ?>/<?= $doc['summary']['total'] ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle text-success"></i> <?= $doc['summary']['terlaksana'] ?> terlaksana
                                            <i class="bi bi-x-circle text-danger ms-2"></i> <?= $doc['summary']['tidak_terlaksana'] ?> tidak
                                            <i class="bi bi-clock text-warning ms-2"></i> <?= $doc['summary']['belum_dilaksanakan'] ?> belum
                                        </small>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/kokurikuler/pelaksanaan/detail/' . $doc['id']) ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square"></i> Input Pelaksanaan
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    <?php if (!empty($documents)): ?>
    $('#datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        order: [[1, 'desc']]
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
