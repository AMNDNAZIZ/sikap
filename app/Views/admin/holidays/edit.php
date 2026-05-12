<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>Edit Hari Libur</h4>

<form method="post" action="<?= base_url('admin/holidays/update/'.$holiday['id']) ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label>Tanggal</label>
    <input type="date" name="date" class="form-control" value="<?= $holiday['date'] ?>" required>
  </div>
  <div class="mb-3">
    <label>Keterangan</label>
    <input type="text" name="description" class="form-control" value="<?= esc($holiday['description']) ?>" required>
  </div>
  <button type="submit" class="btn btn-primary">Update</button>
  <a href="<?= base_url('admin/holidays') ?>" class="btn btn-secondary">Kembali</a>
</form>

<?= $this->endSection() ?>
