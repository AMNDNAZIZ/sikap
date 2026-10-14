<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">📝 Nilai Erapor — <?= esc($teacher['name']) ?></h4>
  <a href="<?= site_url($isAdmin ? 'admin/erapor' : 'admin/erapor') ?>" class="btn btn-sm btn-secondary">⬅ Kembali</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<!-- Filter Tahun Ajaran -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="row g-2 align-items-center">
      <?php if ($isAdmin): ?>
        <input type="hidden" name="teacher_id" value="<?= $teacher['id'] ?>">
      <?php endif; ?>
      <div class="col-auto">
        <label class="col-form-label fw-semibold">Tahun Ajaran:</label>
      </div>
      <div class="col-auto">
        <select name="year_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($years as $y): ?>
            <option value="<?= $y['id'] ?>" <?= $y['id'] == $yearId ? 'selected' : '' ?>>
              <?= esc($y['year']) ?> <?= $y['is_active'] ? '(Aktif)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>


<?php
// Collect unique classes for leger view section
$uniqueClasses = [];
foreach ($assignments as $a) {
    $cid = $a['class_id'];
    if (!isset($uniqueClasses[$cid])) {
        $uniqueClasses[$cid] = $a['class_name'];
    }
}
?>

<!-- Banner Trigger Import Leger -->
<div class="alert alert-info d-flex align-items-center justify-content-between py-2 mb-0 shadow-sm flex-wrap gap-2"
     style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
  <div>
    <strong>💡 Punya file leger kelas?</strong> Import nilai erapor &amp; rekap absensi sekaligus dari file Excel leger.
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url("admin/erapor/import?year_id={$yearId}" . ($isAdmin ? "&teacher_id={$teacher['id']}" : '')) ?>" class="btn btn-primary btn-sm text-nowrap">
      📥 Import dari Leger
    </a>
  </div>
</div>

<?php if (!empty($uniqueClasses)): ?>
<!-- Lihat Leger Per Kelas -->
<div class="alert alert-secondary d-flex align-items-center justify-content-between py-2 mb-3 shadow-sm flex-wrap gap-2"
     style="border-top-left-radius:0;border-top-right-radius:0;border-top:1px solid rgba(0,0,0,.1);">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="fw-semibold text-secondary">📊 Lihat Leger:</span>
    <?php foreach ($uniqueClasses as $cid => $cname): ?>
      <span class="d-flex align-items-center gap-1">
        <span class="text-dark fw-semibold small"><?= esc($cname) ?></span>
        <a href="<?= site_url("admin/erapor/leger/{$cid}/1?year_id={$yearId}") ?>" class="btn btn-sm btn-outline-info py-0 px-2">
          Sem 1
        </a>
        <a href="<?= site_url("admin/erapor/leger/{$cid}/2?year_id={$yearId}") ?>" class="btn btn-sm btn-outline-info py-0 px-2">
          Sem 2
        </a>
      </span>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="mb-3"></div>
<?php endif; ?>

<?php if (empty($assignments)): ?>
  <div class="alert alert-warning">
    Tidak ada penugasan mengajar untuk guru ini pada tahun ajaran yang dipilih.
  </div>
<?php else: ?>
  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold py-2">Daftar Kelas &amp; Mata Pelajaran yang Diampu</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;">#</th>
            <th>Kelas</th>
            <th>Mata Pelajaran</th>
            <th class="text-center">Semester 1</th>
            <th class="text-center">Semester 2</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $i => $a): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td class="fw-bold text-dark"><?= esc($a['class_name']) ?></td>
              <td><?= esc($a['subject_name']) ?></td>
              <td class="text-center">
                <a href="<?= site_url("admin/erapor/input/{$a['class_id']}/{$a['subject_id']}/1?year_id={$yearId}") ?>"
                   class="btn btn-sm btn-outline-primary">
                  ✏️ Edit / Input
                </a>
              </td>
              <td class="text-center">
                <a href="<?= site_url("admin/erapor/input/{$a['class_id']}/{$a['subject_id']}/2?year_id={$yearId}") ?>"
                   class="btn btn-sm btn-outline-primary">
                  ✏️ Edit / Input
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
<?php endif; ?>



<?= $this->endSection() ?>
