<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>⬆️ Kenaikan & Kelulusan - Tahun Ajaran <?= esc($activeYear['year']) ?></h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<?php if (!$prevYear): ?>
  <div class="alert alert-warning">
    <strong>Tidak ada data.</strong> Belum ada tahun ajaran sebelumnya yang bisa diproses untuk kenaikan kelas.
  </div>
<?php elseif (empty($students)): ?>
  <div class="alert alert-info">
    Belum ada data siswa aktif dari tahun ajaran sebelumnya (<strong><?= esc($prevYear['year']) ?></strong>).
  </div>
<?php else: ?>
<p class="text-muted">Memproses siswa dari tahun ajaran: <strong><?= esc($prevYear['year']) ?></strong> → <strong><?= esc($activeYear['year']) ?></strong></p>
<form method="post" action="<?= base_url('admin/promotions/process') ?>">
  <?= csrf_field() ?>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>NIS</th>
        <th>Nama</th>
        <th>Kelas Lama</th>
        <th>Kelas Baru</th>
        <th>Status Baru</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= esc($s['nis']) ?></td>
          <td><?= esc($s['name']) ?></td>
          <td><?= esc($s['class_name']) ?></td>
          <td>
            <input type="hidden" name="student_id[]" value="<?= $s['student_id'] ?>">
            <select name="class_id[]" class="form-select">
              <option value="">-</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
              <?php endforeach ?>
            </select>
          </td>
          <td>
            <select name="status[]" class="form-select">
              <option value="aktif">Naik Kelas</option>
              <option value="lulus">Lulus</option>
              <option value="dropout">Dropout</option>
              <option value="nonaktif">Nonaktif</option>
            </select>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <button class="btn btn-success">🚀 Proses Kenaikan</button>
</form>
<?php endif ?>

<?= $this->endSection() ?>
