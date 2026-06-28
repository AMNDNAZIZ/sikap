<?php
/**
 * View: admin/teacher_attendance/my_attendance.php
 * Guru melihat kehadirannya sendiri (read-only)
 */
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">🕒 Kehadiran Saya</h4>
            <?php if (!empty($teacher)): ?>
                <small class="text-muted"><?= esc($teacher['name']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- Filter tanggal -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
                <label class="fw-semibold mb-0">Tanggal:</label>
                <input type="date" name="date" class="form-control form-control-sm" style="width:auto"
                    value="<?= esc($date) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Lihat</button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="document.querySelector('[name=date]').value='<?= date('Y-m-d') ?>';this.closest('form').submit()">
                    Hari Ini
                </button>
            </form>
        </div>
    </div>

    <!-- Info hari -->
    <div class="alert alert-info py-2 mb-3">
        <strong><?= esc($namaHari ?? '-') ?></strong>, <?= date('d F Y', strtotime($date)) ?>
    </div>

    <!-- Tabel jadwal -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($schedules)): ?>
                <div class="text-center text-muted py-4">
                    <p>Tidak ada jadwal untuk hari ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Waktu</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center">JP</th>
                                <th class="text-center">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $i => $s):
                                $att    = $attendances[$s['id']] ?? null;
                                $status = $att ? $att['status'] : 'H';
                                $ket    = $att ? ($att['keterangan'] ?? '') : '';
                                $isTH   = ($status === 'TH');
                            ?>
                                <tr class="<?= $isTH ? 'table-danger' : '' ?>">
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td class="text-nowrap">
                                        <?= esc(substr($s['start_time'], 0, 5)) ?> –
                                        <?= esc(substr($s['end_time'], 0, 5)) ?>
                                    </td>
                                    <td><?= esc($s['class_name'] ?? '-') ?></td>
                                    <td><?= esc($s['subject_name'] ?? '-') ?></td>
                                    <td class="text-center"><?= $s['jp'] ?></td>
                                    <td class="text-center">
                                        <?php if ($isTH): ?>
                                            <span class="badge bg-danger fs-6">❌ Tidak Hadir</span>
                                        <?php else: ?>
                                            <span class="badge bg-success fs-6">✅ Hadir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isTH && $ket): ?>
                                            <span class="text-danger"><?= esc($ket) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
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
