<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-3">Peta Kerawanan Siswa <span class="badge bg-danger">Early Warning System</span></h1>
            <p class="text-muted">Dashboard ini mendeteksi secara otomatis siswa-siswa yang berpotensi memiliki masalah akademik atau kedisiplinan, berdasarkan data ketidakhadiran (Alpa), poin pelanggaran, dan riwayat jurnal konseling.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0 text-white"><i class="bi bi-exclamation-triangle-fill"></i> Daftar Siswa Dalam Pengawasan (At Risk)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th class="text-center">Indikator Kerawanan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($students as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= esc($row['student_name']) ?></strong><br>
                                            <small class="text-muted">NIS: <?= esc($row['nis']) ?></small>
                                        </td>
                                        <td><?= esc($row['class_name']) ?></td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if($row['negative_points'] >= 20): ?>
                                                    <span class="badge bg-danger">Pelanggaran: <?= $row['negative_points'] ?> Poin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pelanggaran: <?= $row['negative_points'] ?> Poin</span>
                                                <?php endif; ?>

                                                <?php if($row['total_alpha'] >= 3): ?>
                                                    <span class="badge bg-warning text-dark">Alpa: <?= $row['total_alpha'] ?> Kali</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Alpa: <?= $row['total_alpha'] ?> Kali</span>
                                                <?php endif; ?>

                                                <?php if($row['total_journals'] >= 2): ?>
                                                    <span class="badge bg-info text-dark">Konseling: <?= $row['total_journals'] ?> Riwayat</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="<?= base_url('admin/counseling/create?student_id='.$row['student_id']) ?>" class="btn btn-primary mb-1"><i class="bi bi-journal-plus"></i> Jurnal Baru</a>
                                                <a href="<?= base_url('admin/counseling-summon/create?student_id='.$row['student_id']) ?>" class="btn btn-warning mb-1"><i class="bi bi-envelope-exclamation"></i> Panggil Ortu</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($students)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                                            <p class="mt-2 text-muted">Aman. Tidak ada siswa yang terdeteksi memiliki masalah kerawanan tinggi.</p>
                                        </td>
                                    </tr>
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
