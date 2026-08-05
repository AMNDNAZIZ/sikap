<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit Pengumuman</h4>

<form method="post" action="<?= site_url('admin/pengumuman/update/' . $pengumuman['id']) ?>">
  <?= csrf_field() ?>
  <div class="mb-2">
    <input type="text" name="judul" class="form-control" value="<?= esc($pengumuman['judul']) ?>" required>
  </div>
  <div class="mb-2">
    <textarea name="isi" class="form-control" rows="5" required><?= esc($pengumuman['isi']) ?></textarea>
  </div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="aktif" value="1" <?= $pengumuman['aktif'] ? 'checked' : '' ?>>
    <label class="form-check-label">Aktif</label>
  </div>
  <button class="btn btn-success">Simpan Perubahan</button>
  <a href="<?= site_url('admin/pengumuman') ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
