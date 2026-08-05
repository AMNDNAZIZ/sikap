<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Penempatan Siswa Baru - Tahun Ajaran <?= esc($tahun['nama']) ?></h4>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/penempatan/save') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="tahun_ajaran_id" value="<?= $tahun['id'] ?>">

  <div class="row mb-3">
    <div class="col-md-4">
      <select name="kelas_id" class="form-select" required>
        <option value="">Pilih Kelas Tujuan</option>
        <?php foreach ($kelas as $k): ?>
          <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit">Tempatkan</button>
    </div>
  </div>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th><input type="checkbox" id="check-all"></th>
        <th>NIS</th>
        <th>Nama</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($siswa as $s): ?>
      <tr>
        <td><input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>"></td>
        <td><?= esc($s['nis']) ?></td>
        <td><?= esc($s['nama']) ?></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
   <?= $pager->links('siswa', 'bootstrap') ?>
</form>

<script>
document.getElementById('check-all').addEventListener('click', function(e) {
  const checkboxes = document.querySelectorAll('input[name="siswa_ids[]"]');
  checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?= $this->endSection() ?>
