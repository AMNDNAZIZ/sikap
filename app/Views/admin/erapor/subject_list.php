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

<?php if (empty($assignments)): ?>
  <div class="alert alert-warning">
    Tidak ada penugasan mengajar untuk guru ini pada tahun ajaran yang dipilih.
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-header">Daftar Kelas & Mata Pelajaran yang Diampu</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
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
              <td><?= esc($a['class_name']) ?></td>
              <td><?= esc($a['subject_name']) ?></td>
              <td class="text-center">
                <a href="<?= site_url("admin/erapor/input/{$a['class_id']}/{$a['subject_id']}/1?year_id={$yearId}") ?>"
                   class="btn btn-sm btn-outline-primary">
                  ✏️ Input
                </a>
              </td>
              <td class="text-center">
                <a href="<?= site_url("admin/erapor/input/{$a['class_id']}/{$a['subject_id']}/2?year_id={$yearId}") ?>"
                   class="btn btn-sm btn-outline-primary">
                  ✏️ Input
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
