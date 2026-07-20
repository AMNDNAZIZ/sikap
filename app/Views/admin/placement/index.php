<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📌 Penempatan Siswa Baru - Tahun Ajaran <?= esc($activeYear['year']) ?></h4>

<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Halaman ini menampilkan siswa yang <strong>belum pernah ditempatkan</strong> di kelas manapun.
    Untuk siswa naik kelas, gunakan menu <a href="<?= base_url('admin/promotions') ?>">Kenaikan &amp; Kelulusan</a>.
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<form method="post" action="<?= base_url('admin/placement/store') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label>Kelas</label>
    <select name="class_id" class="form-select" required>
      <option value="">-- Pilih Kelas --</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th><input type="checkbox" id="checkAll"></th>
        <th>NISN</th>
        <th>Nama</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($students)): ?>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><input type="checkbox" name="student_id[]" value="<?= $s['id'] ?>"></td>
            <td><?= esc($s['nisn']) ?></td>
            <td><?= esc($s['name']) ?></td>
          </tr>
        <?php endforeach ?>
      <?php else: ?>
        <tr>
          <td colspan="3" class="text-center">Semua siswa sudah ditempatkan 🎉</td>
        </tr>
      <?php endif ?>
    </tbody>
  </table>

  <button class="btn btn-primary mt-2">🚀 Tempatkan</button>
</form>

<script>
document.getElementById('checkAll').addEventListener('click', function(){
  const checkboxes = document.querySelectorAll('input[name="student_id[]"]');
  checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?= $this->endSection() ?>
