<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit Tahun Ajaran</h4>

<form action="<?= site_url('admin/tahun-ajaran/update/'.$item['id']) ?>" method="post" class="row g-2 mb-4">
  <?= csrf_field() ?>
    <input type="text" name="nama" class="form-control" value="<?= esc($item['nama']) ?>" required>
  </div>
  <div class="col-md-2">
    <input type="date" name="mulai" class="form-control" value="<?= esc($item['mulai']) ?>" required>
  </div>
  <div class="col-md-2">
    <input type="date" name="selesai" class="form-control" value="<?= esc($item['selesai']) ?>" required>
  </div>
  <div class="col-md-2">
    <input type="number" name="urutan" class="form-control" value="<?= esc($item['urutan']) ?>" required>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Update</button>
    <a href="<?= site_url('admin/tahun-ajaran') ?>" class="btn btn-secondary w-100 mt-2">Batal</a>
  </div>
</form>

<?= $this->endSection() ?>
