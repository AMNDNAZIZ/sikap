<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Detail Ekskul: <?= esc($ekskul['name']) ?></h1>
            <a href="<?= base_url('siswa/ekskul') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Informasi & Nilai -->
        <div class="col-md-5">
            <!-- Nilai Akhir Semester -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Nilai Akhir (Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($score)): ?>
                        <div class="alert alert-info mb-0">Belum ada nilai yang diinput oleh pembina.</div>
                    <?php else: ?>
                        <div class="text-center mb-3">
                            <h2 class="display-4 font-weight-bold text-success mb-0"><?= esc($score['predicate']) ?></h2>
                            <span class="text-muted">Predikat</span>
                        </div>
                        <hr>
                        <h6 class="font-weight-bold">Keterangan:</h6>
                        <p class="mb-0 text-justify"><?= esc($score['description'] ?: 'Tidak ada catatan tambahan.') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rekap Kehadiran -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rekap Kehadiran</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Total Pertemuan (Jurnal)
                            <span class="badge badge-primary badge-pill"><?= $totalPertemuan ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Total Hadir
                            <span class="badge badge-success badge-pill"><?= $totalHadir ?></span>
                        </li>
                    </ul>
                    <div class="progress mt-3" style="height: 20px;">
                        <?php 
                        $persen = $totalPertemuan > 0 ? round(($totalHadir / $totalPertemuan) * 100) : 0; 
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $persen ?>%;" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100"><?= $persen ?>% Hadir</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Ketidakhadiran -->
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Ketidakhadiran (Absen)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Materi Kegiatan</th>
                                    <th>Status Absen</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($absences)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-success">
                                            <i class="fas fa-check-circle"></i> Anda tidak pernah absen. (Kehadiran 100%)
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($absences as $i => $abs): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= date('d M Y', strtotime($abs['date'])) ?></td>
                                            <td><?= esc($abs['materi']) ?></td>
                                            <td>
                                                <span class="badge badge-warning text-uppercase"><?= esc($abs['status']) ?></span>
                                            </td>
                                            <td><?= esc($abs['notes']) ?: '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
