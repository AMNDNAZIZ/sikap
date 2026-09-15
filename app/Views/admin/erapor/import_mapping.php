<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">🔗 Mapping Mata Pelajaran</h4>
    <small class="text-muted">Langkah 1 dari 2: Cocokkan Kolom File Leger dengan Mapel SIKAP</small>
  </div>
  <a href="<?= site_url('admin/erapor/import') ?>" class="btn btn-sm btn-secondary">⬅ Kembali ke Upload</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Summary Info Box -->
<div class="card mb-3 bg-light border-info">
  <div class="card-body py-2">
    <div class="row align-items-center text-center text-md-start">
      <div class="col-md-3">
        <span class="text-muted small d-block">Kelas Terdeteksi:</span>
        <strong class="text-primary fs-6"><?= esc($detectedClass ?: 'Tidak terdeteksi') ?></strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted small d-block">Tahun Ajaran / Sem:</span>
        <strong><?= esc($selectedYearText) ?> — <?= ucfirst($semester) ?></strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted small d-block">Jumlah Siswa di File:</span>
        <strong class="badge bg-secondary fs-6"><?= count($parsedStudents) ?> Siswa</strong>
      </div>
      <div class="col-md-3">
        <span class="text-muted small d-block">Rekap Absensi:</span>
        <?php if ($canImportAbsence && $importAbsence): ?>
          <span class="badge bg-success">Aktif (Sakit/Izin/Alpa)</span>
        <?php else: ?>
          <span class="badge bg-secondary">Dilewati / Kolom Tidak Ada</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<form action="<?= site_url('admin/erapor/import-preview') ?>" method="post">
  <?= csrf_field() ?>

  <!-- Hidden state metadata -->
  <input type="hidden" name="students_json" value="<?= esc($studentsJson) ?>">
  <input type="hidden" name="year_id" value="<?= esc($yearId) ?>">
  <input type="hidden" name="semester" value="<?= esc($semester) ?>">
  <input type="hidden" name="class_id" value="<?= esc($classId) ?>">
  <input type="hidden" name="import_absence" value="<?= $importAbsence ? 1 : 0 ?>">

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3"></i> Pemetaan Kolom Mata Pelajaran</h6>
      <small class="text-muted">💡 ✅ = Terdeteksi Otomatis, Anda dapat mengaitkan atau mengabaikan kolom</small>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;" class="text-center">Import</th>
            <th>Kolom di File Leger</th>
            <th style="width: 40px;" class="text-center"></th>
            <th>Mata Pelajaran di System SIKAP</th>
            <th>Status Suggestion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subjectHeaders as $colIdx => $headerName): ?>
            <?php 
              $suggestedId = $suggestedMapping[$colIdx] ?? 0;
              $isSuggested = $suggestedId > 0;
            ?>
            <tr>
              <td class="text-center">
                <input class="form-check-input chk-col" type="checkbox" data-target="select_<?= $colIdx ?>" <?= $isSuggested ? 'checked' : '' ?>>
              </td>
              <td>
                <span class="fw-semibold text-dark"><?= esc($headerName) ?></span>
                <small class="text-muted d-block">Kolom Excel #<?= $colIdx + 1 ?></small>
              </td>
              <td class="text-center text-muted">➡</td>
              <td>
                <select name="mapping[<?= $colIdx ?>]" id="select_<?= $colIdx ?>" class="form-select form-select-sm map-select" <?= !$isSuggested ? 'disabled' : '' ?>>
                  <option value="0">-- Abaikan Kolom Ini --</option>
                  <?php foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $suggestedId ? 'selected' : '' ?>>
                      <?= esc($s['name']) ?> (<?= esc($s['code'] ?? 'ID:'.$s['id']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <?php if ($isSuggested): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle">
                    ✅ Auto-suggested
                  </span>
                <?php else: ?>
                  <span class="badge bg-light text-muted border">
                    Belum Terpetakan
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Options Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-sliders"></i> Opsi Konflik Data</h6>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label fw-bold">Jika Nilai Erapor Sudah Ada di System:</label>
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="overwrite_scores" id="ov_score_1" value="1" checked>
            <label class="form-check-label" for="ov_score_1">
              <strong>Timpa Nilai</strong> — perbarui dengan nilai dari file leger ini
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="overwrite_scores" id="ov_score_0" value="0">
            <label class="form-check-label" for="ov_score_0">
              <strong>Lewati Nilai</strong> — biarkan nilai lama yang sudah ada di DB
            </label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Jika Rekap Absensi Sudah Ada di System:</label>
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="overwrite_absence" id="ov_abs_1" value="1" checked <?= (!$canImportAbsence || !$importAbsence) ? 'disabled' : '' ?>>
            <label class="form-check-label" for="ov_abs_1">
              <strong>Timpa Rekap Absen</strong> — perbarui jumlah Sakit/Izin/Alpa
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="overwrite_absence" id="ov_abs_0" value="0" <?= (!$canImportAbsence || !$importAbsence) ? 'disabled' : '' ?>>
            <label class="form-check-label" for="ov_abs_0">
              <strong>Lewati Rekap Absen</strong> — simpan hanya jika belum ada
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between mb-4">
    <a href="<?= site_url('admin/erapor/import') ?>" class="btn btn-secondary">
      ⬅ Ubah File Upload
    </a>
    <button type="submit" class="btn btn-primary btn-lg">
      ➡ Lihat Preview Data &amp; Siswa
    </button>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle select enable/disable based on checkbox
  document.querySelectorAll('.chk-col').forEach(chk => {
    chk.addEventListener('change', function() {
      const targetId = this.getAttribute('data-target');
      const select = document.getElementById(targetId);
      if (select) {
        select.disabled = !this.checked;
        if (!this.checked) {
          select.value = "0";
        }
      }
    });
  });

  // Toggle checkbox when select changes manually
  document.querySelectorAll('.map-select').forEach(sel => {
    sel.addEventListener('change', function() {
      const selectId = this.id;
      const chk = document.querySelector(`.chk-col[data-target="${selectId}"]`);
      if (chk) {
        if (this.value !== "0") {
          chk.checked = true;
          this.disabled = false;
        }
      }
    });
  });
});
</script>

<?= $this->endSection() ?>
