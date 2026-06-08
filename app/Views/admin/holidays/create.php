<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>Tambah Hari Libur</h4>

<form method="post" action="<?= base_url('admin/holidays/store') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label>Tanggal</label>
    <input type="date" name="date" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Keterangan</label>
    <input type="text" name="description" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Simpan</button>
  <a href="<?= base_url('admin/holidays') ?>" class="btn btn-secondary">Kembali</a>
</form>

<?= $this->endSection() ?>
