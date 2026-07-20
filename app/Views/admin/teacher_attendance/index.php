<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0">📋 Absensi Guru Harian</h4>
    <a href="<?= base_url('admin/teacher-attendance/report') ?>" class="btn btn-outline-secondary btn-sm">
        📊 Laporan Bulanan
    </a>
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
                value="<?= esc($date) ?>"
                min="<?= esc($activeYear['start_date'] ?? '') ?>"
                max="<?= date('Y-m-d') ?>">
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
    <strong><?= esc($namaHari ?? '-') ?></strong>, <?= date('d F Y', strtotime($date)) ?>
    <?php if (!empty($activeYear)): ?>
        &mdash; Tahun Ajaran: <strong><?= esc($activeYear['year']) ?></strong>
    <?php endif; ?>
    <?php if ($sessionDone ?? false): ?>
        <span class="badge bg-success ms-2">✅ Absensi sudah disimpan</span>
    <?php else: ?>
        <span class="badge bg-warning text-dark ms-2">⏳ Belum disimpan</span>
    <?php endif; ?>
</div>

<?php if (empty($byTeacher)): ?>
    <div class="alert alert-light border text-center text-muted">
        Tidak ada jadwal untuk hari ini.
    </div>
<?php else: ?>

<?php foreach ($byTeacher as $teacherId => $tData): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header py-2 bg-primary text-white d-flex align-items-center gap-2">
        <i class="bi bi-person-fill"></i>
        <strong><?= esc($tData['teacher_name']) ?></strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:45px" class="text-center">JP</th>
                        <th style="width:110px">Waktu</th>
                        <th style="width:100px">Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th style="width:160px" class="text-center">Status</th>
                        <th style="width:200px">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tData['slots'] as $s): ?>
                        <?php foreach ($s['jp_rows'] as $jpKe): ?>
                            <?php
                            $key  = $s['id'] . '_' . $jpKe;
                            $att  = $attendances[$key] ?? null;
                            $isTH = $att && $att['status'] === 'TH';
                            $ket  = $att ? ($att['keterangan'] ?? '') : '';
                            $uid  = 'jp_' . $s['id'] . '_' . $jpKe;
                            ?>
                            <tr id="row-<?= $uid ?>" class="<?= $isTH ? 'table-danger' : '' ?>">
                                <td class="text-center fw-bold text-muted">JP <?= $jpKe ?></td>
                                <td class="text-nowrap small">
                                    <?= esc(substr($s['start_time'], 0, 5)) ?>–<?= esc(substr($s['end_time'], 0, 5)) ?>
                                </td>
                                <td class="small"><?= esc($s['class_name'] ?? '-') ?></td>
                                <td class="small"><?= esc($s['subject_name'] ?? '-') ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                            class="btn <?= !$isTH ? 'btn-success' : 'btn-outline-success' ?>"
                                            onclick="setStatus(<?= $s['id'] ?>, <?= $jpKe ?>, 'H')">
                                            ✅ Hadir
                                        </button>
                                        <button type="button"
                                            class="btn <?= $isTH ? 'btn-danger' : 'btn-outline-danger' ?>"
                                            onclick="setStatus(<?= $s['id'] ?>, <?= $jpKe ?>, 'TH')">
                                            ❌ TH
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input type="text"
                                        id="ket-<?= $uid ?>"
                                        class="form-control form-control-sm"
                                        placeholder="Keterangan..."
                                        value="<?= esc($ket) ?>"
                                        <?= $isTH ? '' : 'disabled' ?>
                                        onchange="saveKet(<?= $s['id'] ?>, <?= $jpKe ?>)">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Tombol Simpan Absensi -->
<div class="d-flex justify-content-end mt-2 mb-4">
    <?php if ($sessionDone ?? false): ?>
        <div class="d-flex align-items-center gap-2">
            <span class="text-success fw-semibold">✅ Absensi tanggal ini sudah disimpan</span>
            <button class="btn btn-outline-warning btn-sm" onclick="submitSession(true)">
                🔄 Simpan Ulang
            </button>
        </div>
    <?php else: ?>
        <button class="btn btn-success px-4" id="btnSubmit" onclick="submitSession()">
            💾 Simpan Absensi Hari Ini
        </button>
    <?php endif; ?>
</div>

<?php endif; ?>

<script>
const SAVE_URL    = '<?= base_url('admin/teacher-attendance/save') ?>';
const SESSION_URL = '<?= base_url('admin/teacher-attendance/submit-session') ?>';
const DATE        = '<?= esc($date) ?>';
const CSRF_NAME   = '<?= csrf_token() ?>';
let   csrfHash    = '<?= csrf_hash() ?>';

function uid(schedId, jpKe) { return 'jp_' + schedId + '_' + jpKe; }

function setStatus(schedId, jpKe, status) {
    const ket = document.getElementById('ket-' + uid(schedId, jpKe))?.value ?? '';
    send(schedId, jpKe, status, ket);
}

function saveKet(schedId, jpKe) {
    const row = document.getElementById('row-' + uid(schedId, jpKe));
    if (!row.classList.contains('table-danger')) return;
    const ket = document.getElementById('ket-' + uid(schedId, jpKe))?.value ?? '';
    send(schedId, jpKe, 'TH', ket);
}

function send(schedId, jpKe, status, ket) {
    const body = new FormData();
    body.append('date',        DATE);
    body.append('schedule_id', schedId);
    body.append('jp_ke',       jpKe);
    body.append('status',      status);
    body.append('keterangan',  ket);
    body.append(CSRF_NAME,     csrfHash);

    fetch(SAVE_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                csrfHash = res.csrf ?? csrfHash;
                updateUI(schedId, jpKe, status);
            } else {
                alert('Gagal: ' + (res.message ?? ''));
            }
        })
        .catch(() => alert('Error jaringan.'));
}

function submitSession(isResave = false) {
    const label = isResave ? 'Simpan ulang absensi hari ini?' : 'Simpan absensi hari ini?\n\nSetelah disimpan, data kehadiran guru akan tercatat.';
    if (!confirm(label)) return;

    const body = new FormData();
    body.append('date',    DATE);
    body.append(CSRF_NAME, csrfHash);

    const btn = document.getElementById('btnSubmit');
    if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }

    fetch(SESSION_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                csrfHash = res.csrf ?? csrfHash;
                alert('✅ ' + res.message);
                location.reload();
            } else {
                alert('Gagal: ' + (res.message ?? ''));
                if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan Absensi Hari Ini'; }
            }
        })
        .catch(() => {
            alert('Error jaringan.');
            if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan Absensi Hari Ini'; }
        });
}

function updateUI(schedId, jpKe, status) {
    const id    = uid(schedId, jpKe);
    const row   = document.getElementById('row-' + id);
    const ket   = document.getElementById('ket-' + id);
    const btnH  = row.querySelector('.btn-group .btn:nth-child(1)');
    const btnTH = row.querySelector('.btn-group .btn:nth-child(2)');

    if (status === 'H') {
        row.classList.remove('table-danger');
        btnH.className  = 'btn btn-success';
        btnTH.className = 'btn btn-outline-danger';
        if (ket) { ket.value = ''; ket.disabled = true; }
    } else {
        row.classList.add('table-danger');
        btnH.className  = 'btn btn-outline-success';
        btnTH.className = 'btn btn-danger';
        if (ket) { ket.disabled = false; ket.focus(); }
    }
}
</script>

<?= $this->endSection() ?>
