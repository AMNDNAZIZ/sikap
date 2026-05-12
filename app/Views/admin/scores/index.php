<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h1 class="h3 mb-4">📊 Rekap Nilai Siswa</h1>

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

<?php if (!empty($grades)): ?>
  <h4>Nilai <?= esc($subject['name']) ?> (<?= ucfirst($semester) ?>)</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>Nama Siswa</th>
          <th>Formatif</th>
          <th>Sumatif</th>
          <?php if ($finalExamUsed): ?>
            <th>Ujian Akhir</th>
          <?php endif; ?>
          <th>Nilai Rapot</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($grades as $g): ?>
          <tr>
            <td><?= esc($g['student_name']) ?></td>
            <td><?= $g['formative_score'] ?></td>
            <td><?= $g['summative_score'] ?></td>
            <?php if ($finalExamUsed): ?>
              <td><?= $g['final_exam'] ?></td>
            <?php endif; ?>
            <td><strong><?= $g['report_score'] ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
