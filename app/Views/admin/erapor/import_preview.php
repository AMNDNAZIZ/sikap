<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">👁️ Preview Data Import Leger</h4>
    <small class="text-muted">Langkah 2 dari 2: Verifikasi Data Sebelum Disimpan</small>
  </div>
  <a href="<?= site_url('admin/erapor/import') ?>" class="btn btn-sm btn-secondary">⬅ Batal &amp; Mulai Ulang</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Summary Stat Cards -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-success bg-success-subtle shadow-sm">
      <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
        <div>
          <small class="text-success fw-semibold">Siswa Cocok di DB</small>
          <h4 class="mb-0 text-success fw-bold"><?= $matchedCount ?> Siswa</h4>
        </div>
        <i class="bi bi-person-check fs-1 text-success opacity-75"></i>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-danger bg-danger-subtle shadow-sm">
      <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
        <div>
          <small class="text-danger fw-semibold">Siswa Tidak Ditemukan</small>
          <h4 class="mb-0 text-danger fw-bold"><?= $notFoundCount ?> Siswa</h4>
        </div>
        <i class="bi bi-person-x fs-1 text-danger opacity-75"></i>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-primary bg-primary-subtle shadow-sm">
      <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
        <div>
          <small class="text-primary fw-semibold">Mapel Di-mapping</small>
          <h4 class="mb-0 text-primary fw-bold"><?= count($activeMapping) ?> Mapel</h4>
        </div>
        <i class="bi bi-journal-bookmark fs-1 text-primary opacity-75"></i>
      </div>
    </div>
  </div>
</div>

<?php if (!$isWaliKelas && $importAbsence && $canImportAbsence): ?>
  <div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
    <div>
      <strong>Perhatian Wali Kelas:</strong> Anda terdeteksi sebagai Guru (bukan Wali Kelas untuk kelas ini). Rekap absensi (Sakit/Izin/Alpa) <strong>tidak akan diimport</strong>.
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($skippedSubjectNamesTeacher)): ?>
  <div class="alert alert-info d-flex align-items-center" role="alert">
    <i class="bi bi-info-circle-fill fs-4 me-2"></i>
    <div>
      <strong>Penugasan Mengajar Guru:</strong> Kolom mapel berikut akan dilewati karena bukan merupakan tugas mengajar Anda:
      <strong><?= esc(implode(', ', $skippedSubjectNamesTeacher)) ?></strong>.
    </div>
  </div>
<?php endif; ?>

<form action="<?= site_url('admin/erapor/import-confirm') ?>" method="post">
  <?= csrf_field() ?>

  <!-- Pass all hidden state data -->
  <input type="hidden" name="students_json" value="<?= esc($studentsJson) ?>">
  <input type="hidden" name="year_id" value="<?= esc($yearId) ?>">
  <input type="hidden" name="semester" value="<?= esc($semester) ?>">
  <input type="hidden" name="class_id" value="<?= esc($classId) ?>">
  <input type="hidden" name="import_absence" value="<?= $importAbsence ? 1 : 0 ?>">
  <input type="hidden" name="overwrite_scores" value="<?= $overwriteScores ? 1 : 0 ?>">
  <input type="hidden" name="overwrite_absence" value="<?= $overwriteAbsence ? 1 : 0 ?>">

  <?php foreach ($mapping as $colIdx => $subId): ?>
    <input type="hidden" name="mapping[<?= $colIdx ?>]" value="<?= esc($subId) ?>">
  <?php endforeach; ?>

  <!-- Table 1: Preview Nilai Erapor -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bi bi-table"></i> Preview Nilai Rapor Per Mapel</h6>
      <small class="text-muted">Header kolom menggunakan nama Mata Pelajaran SIKAP</small>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-bordered table-hover align-middle text-nowrap mb-0" style="font-size: 0.9rem;">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width: 40px;">#</th>
            <th>Nama Siswa di File</th>
            <th>NISN / NIS</th>
            <th class="text-center">Match Status</th>
            <?php foreach ($activeMapping as $colIdx => $subId): ?>
              <th class="text-center bg-primary-subtle border-primary-subtle">
                <div><?= esc($subjectNames[$subId] ?? 'Mapel #'.$subId) ?></div>
                <small class="fw-normal text-muted" style="font-size: 0.75rem;">
                  <?= esc($subjectHeaders[$colIdx] ?? '') ?>
                </small>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parsedStudents as $idx => $st): ?>
            <?php
              $statusClass = '';
              $badgeText = '';
              $badgeBg = 'bg-secondary';
              if ($st['match_status'] === 'not_found') {
                $statusClass = 'table-danger';
                $badgeText = 'Tidak Ditemukan';
                $badgeBg = 'bg-danger';
              } elseif ($st['match_status'] === 'nisn_match') {
                $badgeText = 'NISN Match';
                $badgeBg = 'bg-success';
              } elseif ($st['match_status'] === 'nis_match') {
                $badgeText = 'NIS Match';
                $badgeBg = 'bg-info text-dark';
              } elseif ($st['match_status'] === 'name_match') {
                $statusClass = 'table-warning';
                $badgeText = 'Nama Match';
                $badgeBg = 'bg-warning text-dark';
              }
            ?>
            <tr class="<?= $statusClass ?>">
              <td class="text-center"><?= $idx + 1 ?></td>
              <td class="fw-semibold">
                <?= esc($st['file_name']) ?>
                <?php if ($st['db_student_id'] && $st['match_status'] === 'name_match'): ?>
                  <small class="d-block text-muted">→ DB: <?= esc($st['db_student_name']) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <div><small>NISN: <?= esc(($st['nisn'] ?? '') ?: '—') ?></small></div>
                <div><small class="text-muted">NIS: <?= esc(($st['nis'] ?? $st['file_nis'] ?? '') ?: '—') ?></small></div>
              </td>
              <td class="text-center">
                <span class="badge <?= $badgeBg ?>"><?= $badgeText ?></span>
              </td>

              <?php if ($st['match_status'] === 'not_found'): ?>
                <td colspan="<?= count($activeMapping) ?>" class="text-center text-muted fst-italic">
                  Data siswa tidak ditemukan di kelas database SIKAP (akan dilewati)
                </td>
              <?php else: ?>
                <?php foreach ($activeMapping as $colIdx => $subId): ?>
                  <?php
                    $val = $st['scores'][$colIdx] ?? null;
                    $isExisting = !empty($existingScores[$st['db_student_id']][$subId]);
                  ?>
                  <td class="text-center">
                    <?php if ($val === null || $val === '' || $val === '-'): ?>
                      <span class="text-muted">—</span>
                    <?php else: ?>
                      <span class="fw-bold <?= ($val < 70) ? 'text-danger' : 'text-dark' ?>">
                        <?= esc($val) ?>
                      </span>
                      <?php if ($isExisting): ?>
                        <small class="d-block text-warning-emphasis" style="font-size: 0.7rem;">(akan ditimpa)</small>
                      <?php else: ?>
                        <small class="d-block text-success" style="font-size: 0.7rem;">(baru)</small>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Table 2: Preview Rekap Absensi -->
  <?php if ($canImportAbsence && $importAbsence): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white py-2">
        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check"></i> Preview Rekap Ketidakhadiran Siswa</h6>
      </div>
      <div class="card-body p-0 table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.9rem;">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width: 40px;">#</th>
              <th>Nama Siswa</th>
              <th class="text-center">Sakit (S)</th>
              <th class="text-center">Izin (I)</th>
              <th class="text-center">Alpa (A)</th>
              <th>Keterangan Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($parsedStudents as $idx => $st): ?>
              <tr class="<?= $st['match_status'] === 'not_found' ? 'table-danger' : '' ?>">
                <td class="text-center"><?= $idx + 1 ?></td>
                <td class="fw-semibold"><?= esc($st['file_name']) ?></td>
                <?php if ($st['match_status'] === 'not_found'): ?>
                  <td colspan="4" class="text-center text-muted fst-italic">Dilewati (Siswa tidak ditemukan)</td>
                <?php else: ?>
                  <td class="text-center font-monospace"><?= esc($st['sick'] ?? 0) ?> hari</td>
                  <td class="text-center font-monospace"><?= esc($st['permitted'] ?? 0) ?> hari</td>
                  <td class="text-center font-monospace"><?= esc($st['absent'] ?? 0) ?> hari</td>
                  <td>
                    <?php if ($isWaliKelas): ?>
                      <span class="badge bg-success">Siap Import Absen</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Dilewati (Bukan Wali Kelas)</span>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between mb-4">
    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
      ⬅ Ubah Mapping Mapel
    </button>

    <button type="submit" class="btn btn-success btn-lg" <?= $matchedCount == 0 ? 'disabled' : '' ?>>
      ✅ Konfirmasi &amp; Simpan Data Import
    </button>
  </div>
</form>

<?= $this->endSection() ?>
