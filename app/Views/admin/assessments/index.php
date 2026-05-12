<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>📋 Daftar Penilaian</h2>

<?php if ($isAdmin): ?>
  <!-- Tampilan untuk admin -->
  <form method="get" class="mb-3">
    <label for="teacher_id" class="form-label">Pilih Guru</label>
    <select name="teacher_id" id="teacher_id" class="form-select" onchange="this.form.submit()">
      <option value="">-- Semua Guru --</option>
      <?php foreach($teachers as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $selected == $t['id'] ? 'selected' : '' ?>>
          <?= esc($t['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </form>
<?php endif; ?>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Kelas</th>
      <th>Mata Pelajaran</th>
      <?php if ($isAdmin): ?><th>Guru</th><?php endif; ?>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($assignments as $a): ?>
      <tr>
        <td><?= esc($a['class_name']) ?></td>
        <td><?= esc($a['subject_name']) ?></td>
        <?php if ($isAdmin): ?><td><?= esc($a['teacher_name']) ?></td><?php endif; ?>
        <td>
          <a href="<?= base_url("admin/assessments/formatifList/{$a['class_id']}/{$a['subject_id']}") ?>" 
             class="btn btn-sm btn-primary">Formatif</a>
          <a href="<?= site_url("admin/assessments/sumatifList/{$a['class_id']}/{$a['subject_id']}") ?>" 
             class="btn btn-sm btn-warning">📝 Sumatif</a>
          <?php
          $maxLevel = db_connect()->table('classes')->selectMax('level')->get()->getRowArray();
          $finalLevel = $maxLevel['level'] ?? null;
          ?>

          <?php if (!empty($a['class_level']) && $a['class_level'] == $finalLevel): ?>
            <a href="<?= site_url("admin/assessments/finalList/{$a['class_id']}/{$a['subject_id']}") ?>"
               class="btn btn-sm btn-success">📊 Ujian Akhir</a>
          <?php endif; ?>

        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
