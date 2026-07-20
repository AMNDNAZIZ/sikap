<?php
/**
 * View: admin/teacher_attendance/detail.php
 * Rincian ketidakhadiran satu guru
 */
$monthLabel = date('F Y', strtotime($month . '-01'));
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">📋 Detail Ketidakhadiran Guru</h4>
            <small class="text-muted">
                <?= esc($teacher['name'] ?? '-') ?> — <?= esc($monthLabel) ?>
            </small>
        </div>
        <a href="<?= base_url('admin/teacher-attendance/report?month=' . urlencode($month)) ?>"
            class="btn btn-outline-secondary btn-sm">
            ← Kembali ke Laporan
        </a>
    </div>

    <!-- Info guru -->
    <div class="alert alert-light border mb-3 py-2">
        <strong>Guru:</strong> <?= esc($teacher['name'] ?? '-') ?> &nbsp;|&nbsp;
        <strong>NIP:</strong> <?= esc($teacher['nip'] ?? '-') ?> &nbsp;|&nbsp;
        <strong>Bulan:</strong> <?= esc($monthLabel) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($details)): ?>
                <div class="text-center text-muted py-4">
                    <span class="text-success fs-5">✅</span>
                    <p class="mt-2">Tidak ada ketidakhadiran di bulan ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Waktu</th>
                                <th class="text-center">JP ke</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($details as $d): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-nowrap">
                                        <?= date('d/m/Y', strtotime($d['date'])) ?>
                                    </td>
                                    <td><?= esc($d['nama_hari']) ?></td>
                                    <td><?= esc($d['class_name'] ?? '-') ?></td>
                                    <td><?= esc($d['subject_name'] ?? '-') ?></td>
                                    <td class="text-nowrap">
                                        <?= esc(substr($d['start_time'], 0, 5)) ?> –
                                        <?= esc(substr($d['end_time'], 0, 5)) ?>
                                    </td>
                                    <td class="text-center fw-bold">JP <?= esc($d['jp_ke']) ?></td>
                                    <td><?= esc($d['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary fw-semibold">
                            <tr>
                                <td colspan="6" class="text-end">Total JP Tidak Hadir:</td>
                                <td class="text-center">
                                    <?= count($details) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
