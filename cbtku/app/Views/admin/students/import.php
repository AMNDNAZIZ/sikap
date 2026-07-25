<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📂 Impor Siswa</h4>

<form action="<?= base_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Pilih File Excel</label>
    <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
  </div>
  <button type="submit" class="btn btn-success">🚀 Impor</button>
  <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>
