<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h1 class="h3 mb-4">📘 Input Nilai Per Materi</h1>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Tahun Ajaran</label>
        <select name="year_id" class="form-select" required>
          <?php foreach ($years as $y): ?>
            <option value="<?= $y['id'] ?>" <?= ($yearId ?? '') == $y['id'] ? 'selected' : '' ?>>
              <?= esc($y['year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Semester</label>
        <select name="semester" class="form-select" required>
          <option value="ganjil" <?= ($semester ?? '') == 'ganjil' ? 'selected' : '' ?>>Ganjil</option>
          <option value="genap" <?= ($semester ?? '') == 'genap' ? 'selected' : '' ?>>Genap</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Mata Pelajaran</label>
        <select name="subject_id" class="form-select" required>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($subjectId ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= esc($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">🔍 Tampilkan</button>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($materials)): ?>
  <h4>Materi: <?= esc($subject['name']) ?> (<?= ucfirst($semester) ?>)</h4>

  <?php foreach ($materials as $material): ?>
    <div class="card mb-3">
      <div class="card-header">
        <strong><?= esc($material['title']) ?></strong> - <?= esc($material['description']) ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?= base_url('admin/scores/save-material/' . $material['id']) ?>">
          <?= csrf_field() ?>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th>Nama Siswa</th>
                  <th>Tulis</th>
                  <th>Lisan</th>
                  <th>Projek</th>
                  <th>Observasi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $student): ?>
                  <tr>
                    <td><?= esc($student['name']) ?></td>
                    <?php foreach (['tulis','lisan','projek','observasi'] as $type): ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="100"
                          name="scores[<?= $student['id'] ?>][<?= $type ?>]"
                          class="form-control form-control-sm"
                          value="<?= $existingScores[$material['id']][$student['id']][$type] ?? '' ?>">
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button type="submit" class="btn btn-success btn-sm mt-2">💾 Simpan Nilai</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
