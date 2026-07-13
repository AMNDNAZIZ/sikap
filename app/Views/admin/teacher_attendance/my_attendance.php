<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">🕒 Kehadiran Saya</h4>
        <?php if (!empty($teacher)): ?>
            <small class="text-muted"><?= esc($teacher['name']) ?></small>
        <?php endif; ?>
    </div>
</div>

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

<div class="alert alert-info py-2 mb-3">
    <strong><?= esc($namaHari ?? '-') ?></strong>, <?= date('d F Y', strtotime($date)) ?>
</div>

<?php
$isHoliday   = $isHoliday ?? false;
$sessionDone = $sessionDone ?? false;
$holidayName = $holidayName ?? 'Hari Libur';
?>

<?php if ($isHoliday): ?>
    <div class="alert alert-secondary d-flex align-items-center gap-2">
        <i class="bi bi-calendar-x fs-4"></i>
        <div><strong>Hari Libur</strong> — <?= esc($holidayName) ?></div>
    </div>

<?php elseif (empty($schedules)): ?>
    <div class="alert alert-light border d-flex align-items-center gap-2">
        <i class="bi bi-calendar-minus fs-4 text-muted"></i>
        <div class="text-muted">Anda tidak mempunyai jadwal mengajar pada hari ini.</div>
    </div>

<?php elseif (!$sessionDone): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-hourglass-split fs-4"></i>
        <div>
            <strong>Absensi belum dilakukan.</strong><br>
            <small>Hubungi admin atau staf untuk melakukan pencatatan absensi hari ini.</small>
        </div>
    </div>

<?php else: ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:50px">JP</th>
                        <th style="width:110px">Waktu</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center" style="width:150px">Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                        <?php foreach ($s['jp_rows'] as $jpKe): ?>
                            <?php
                            $key  = $s['id'] . '_' . $jpKe;
                            $att  = $attendances[$key] ?? null;
                            $isTH = $att && $att['status'] === 'TH';
                            $ket  = $att ? ($att['keterangan'] ?? '') : '';
                            ?>
                            <tr class="<?= $isTH ? 'table-danger' : '' ?>">
                                <td class="text-center fw-bold text-muted">JP <?= $jpKe ?></td>
                                <td class="text-nowrap small">
                                    <?= esc(substr($s['start_time'], 0, 5)) ?>–<?= esc(substr($s['end_time'], 0, 5)) ?>
                                </td>
                                <td class="small"><?= esc($s['class_name'] ?? '-') ?></td>
                                <td class="small"><?= esc($s['subject_name'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php if ($isTH): ?>
                                        <span class="badge bg-danger">❌ Tidak Hadir</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">✅ Hadir</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?= $isTH && $ket ? esc($ket) : '<span class="text-muted">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
