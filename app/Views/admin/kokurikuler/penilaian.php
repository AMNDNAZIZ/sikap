<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><?= $title ?></h4>
            <small class="text-muted">Pilih rencana kokurikuler untuk melakukan penilaian siswa</small>
        </div>
        <a href="<?= base_url('admin/kokurikuler') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
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

    <?php if (empty($documents)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Belum ada dokumen kokurikuler yang selesai. Silakan selesaikan perencanaan dan pelaksanaan terlebih dahulu.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Tema</th>
                                <th width="15%">Tahun Ajaran</th>
                                <th width="10%">Semester</th>
                                <th width="10%">Fase/Kelas</th>
                                <th width="15%">Progress Penilaian</th>
                                <th width="10%">Status</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $index => $doc): ?>
                                <?php 
                                $summary = $doc['summary'];
                                $persentase = $summary['persentase'];
                                $progressClass = $persentase == 100 ? 'bg-success' : ($persentase > 0 ? 'bg-warning' : 'bg-secondary');
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= esc($doc['tema']) ?></strong>
                                        <br><small class="text-muted"><?= ucfirst(str_replace('_', ' ', $doc['jenis_kokurikuler'] ?? $doc['bentuk_kegiatan'] ?? '')) ?></small>
                                    </td>
                                    <td><?= esc($doc['year_name']) ?></td>
                                    <td>Semester <?= esc($doc['semester']) ?></td>
                                    <td>Fase <?= esc($doc['fase']) ?> - Kelas <?= esc($doc['level_kelas']) ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?= $progressClass ?>" role="progressbar" 
                                                 style="width: <?= $persentase ?>%;" 
                                                 aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= $persentase ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $summary['sudah_dinilai'] ?> dari <?= $summary['total_students'] ?> siswa
                                            <?php if (isset($summary['total_rubrik']) && $summary['total_rubrik'] > 1): ?>
                                                &bull; <?= $summary['total_rubrik'] ?> dimensi
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($persentase == 100): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php elseif ($persentase > 0): ?>
                                            <span class="badge bg-warning">Proses</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum Mulai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/kokurikuler/penilaian/detail/' . $doc['id']) ?>" 
                                           class="btn btn-primary btn-sm" title="Nilai Siswa">
                                            <i class="bi bi-clipboard-check"></i> Nilai
                                        </a>
                                        <?php if ($persentase == 100): ?>
                                        <a href="<?= base_url('admin/kokurikuler/penilaian/deskripsi/' . $doc['id']) ?>"
                                           class="btn btn-info btn-sm" title="Lihat Deskripsi">
                                            <i class="bi bi-file-text"></i> Deskripsi
                                        </a>
                                        <a href="<?= base_url('admin/kokurikuler/penilaian/cetak/' . $doc['id']) ?>"
                                           class="btn btn-success btn-sm" title="Cetak Hasil" target="_blank">
                                            <i class="bi bi-printer"></i> Cetak
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
