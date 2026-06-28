<?php
/**
 * View: admin/teacher_attendance/index.php
 * Input absensi guru harian (Admin / Staf)
 */
$session = session();
$user    = $session->get('user');
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h4 class="mb-0">📋 Absensi Guru Harian</h4>
        <a href="<?= base_url('admin/teacher-attendance/report') ?>" class="btn btn-outline-secondary btn-sm">
            📊 Lihat Laporan Bulanan
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

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
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="document.querySelector('[name=date]').value='<?= date('Y-m-d') ?>';this.closest('form').submit()">
                    Hari Ini
                </button>
            </form>
        </div>
    </div>

    <!-- Info hari -->
    <div class="alert alert-info py-2 mb-3">
        <strong><?= esc($namaHari ?? '-') ?></strong>,
        <?= date('d F Y', strtotime($date)) ?>
        <?php if (!empty($activeYear)): ?>
            &mdash; Tahun Ajaran: <strong><?= esc($activeYear['year']) ?></strong>
        <?php endif; ?>
    </div>

    <!-- Tabel jadwal & absensi -->
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
                                <th style="width:60px">No</th>
                                <th>Waktu</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                                <th style="width:60px">JP</th>
                                <th style="width:160px">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $i => $s):
                                $att     = $attendances[$s['id']] ?? null;
                                $status  = $att ? $att['status'] : 'H';
                                $ket     = $att ? ($att['keterangan'] ?? '') : '';
                                $isTH    = ($status === 'TH');
                            ?>
                                <tr id="row-<?= $s['id'] ?>" class="<?= $isTH ? 'table-danger' : '' ?>">
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td class="text-nowrap">
                                        <?= esc(substr($s['start_time'], 0, 5)) ?> –
                                        <?= esc(substr($s['end_time'], 0, 5)) ?>
                                    </td>
                                    <td><?= esc($s['class_name'] ?? '-') ?></td>
                                    <td><?= esc($s['subject_name'] ?? '-') ?></td>
                                    <td><?= esc($s['teacher_name'] ?? '-') ?></td>
                                    <td class="text-center"><?= $s['jp'] ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button"
                                                class="btn btn-status <?= !$isTH ? 'btn-success' : 'btn-outline-success' ?>"
                                                data-schedule="<?= $s['id'] ?>"
                                                data-status="H"
                                                onclick="setStatus(<?= $s['id'] ?>, 'H')">
                                                ✅ Hadir
                                            </button>
                                            <button type="button"
                                                class="btn btn-status <?= $isTH ? 'btn-danger' : 'btn-outline-danger' ?>"
                                                data-schedule="<?= $s['id'] ?>"
                                                data-status="TH"
                                                onclick="setStatus(<?= $s['id'] ?>, 'TH')">
                                                ❌ TH
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text"
                                            id="ket-<?= $s['id'] ?>"
                                            class="form-control form-control-sm ket-input"
                                            placeholder="Keterangan ketidakhadiran"
                                            value="<?= esc($ket) ?>"
                                            <?= $isTH ? '' : 'disabled' ?>
                                            data-schedule="<?= $s['id'] ?>"
                                            onchange="saveKeterangan(<?= $s['id'] ?>)">
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

<script>
const SAVE_URL = '<?= base_url('admin/teacher-attendance/save') ?>';
const DATE     = '<?= esc($date) ?>';

function setStatus(scheduleId, status) {
    const ket = document.getElementById('ket-' + scheduleId)?.value ?? '';
    sendAbsensi(scheduleId, status, ket);
}

function saveKeterangan(scheduleId) {
    // Hanya simpan jika status TH
    const btnTH = document.querySelector(`.btn-status[data-schedule="${scheduleId}"][data-status="TH"]`);
    if (btnTH && btnTH.classList.contains('btn-danger')) {
        const ket = document.getElementById('ket-' + scheduleId)?.value ?? '';
        sendAbsensi(scheduleId, 'TH', ket);
    }
}

function sendAbsensi(scheduleId, status, keterangan) {
    const body = new FormData();
    body.append('date', DATE);
    body.append('schedule_id', scheduleId);
    body.append('status', status);
    body.append('keterangan', keterangan);
    body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(SAVE_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                updateRowUI(scheduleId, status);
            } else {
                alert('Gagal menyimpan: ' + (res.message ?? ''));
            }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
}

function updateRowUI(scheduleId, status) {
    const row   = document.getElementById('row-' + scheduleId);
    const ketEl = document.getElementById('ket-' + scheduleId);
    const btnH  = document.querySelector(`.btn-status[data-schedule="${scheduleId}"][data-status="H"]`);
    const btnTH = document.querySelector(`.btn-status[data-schedule="${scheduleId}"][data-status="TH"]`);

    if (status === 'H') {
        row.classList.remove('table-danger');
        btnH.className  = 'btn btn-status btn-success';
        btnTH.className = 'btn btn-status btn-outline-danger';
        if (ketEl) { ketEl.value = ''; ketEl.disabled = true; }
    } else {
        row.classList.add('table-danger');
        btnH.className  = 'btn btn-status btn-outline-success';
        btnTH.className = 'btn btn-status btn-danger';
        if (ketEl) { ketEl.disabled = false; ketEl.focus(); }
    }
}
</script>
<?= $this->endSection() ?>
