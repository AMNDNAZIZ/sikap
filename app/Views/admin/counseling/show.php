<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/counseling') ?>">Jurnal Konseling</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Detail Sesi Konseling</h4>
                <div>
                    <a href="<?= base_url('admin/counseling/edit/' . $journal['id']) ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Ubah
                    </a>
                    <a href="<?= base_url('admin/counseling') ?>" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Nama Siswa</th>
                                <td>: <strong><?= esc($journal['student_name']) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Kelas / NISN</th>
                                <td>: <?= esc($journal['class_name']) ?> / <?= esc($journal['nisn']) ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Sesi</th>
                                <td>: <?= date('d F Y', strtotime($journal['session_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>Guru Konselor</th>
                                <td>: <?= esc($journal['counselor_name'] ?? 'Tidak diketahui') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Jenis Layanan</th>
                                <td>: <span class="badge bg-info"><?= esc($journal['counseling_type']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Sifat Kerahasiaan</th>
                                <td>: 
                                    <?php if ($journal['is_confidential'] == 1): ?>
                                        <span class="badge bg-danger">Sangat Rahasia</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Biasa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Status Kasus</th>
                                <td>: 
                                    <?php if ($journal['status'] == 'Closed'): ?>
                                        <span class="badge bg-success">Selesai (Closed)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Masih Berjalan (Open)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="mt-4">
                    <h5>Deskripsi Masalah</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(esc($journal['problem_description'])) ?>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Diagnosa (Akar Masalah)</h5>
                    <div class="p-3 bg-light rounded">
                        <?= $journal['diagnosis'] ? nl2br(esc($journal['diagnosis'])) : '<em class="text-muted">Belum ada diagnosa</em>' ?>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Penanganan (Treatment)</h5>
                    <div class="p-3 bg-light rounded">
                        <?= $journal['treatment'] ? nl2br(esc($journal['treatment'])) : '<em class="text-muted">Belum ada tindakan</em>' ?>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Tindak Lanjut (Follow Up)</h5>
                    <div class="p-3 bg-light rounded">
                        <?= $journal['follow_up'] ? nl2br(esc($journal['follow_up'])) : '<em class="text-muted">Belum ada tindak lanjut</em>' ?>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
