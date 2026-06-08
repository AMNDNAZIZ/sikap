<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>✏️ Edit Perilaku</h4>

<form action="<?= base_url('admin/behaviors/update/'.$behavior['id']) ?>" method="post">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Nama Perilaku</label>
    <input type="text" name="name" class="form-control" 
           value="<?= esc($behavior['name']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Poin</label>
    <input type="number" name="points" class="form-control" 
           value="<?= esc($behavior['points']) ?>" required>
    <div class="form-text">
      Gunakan nilai positif (misal: 1–5) untuk perilaku baik  
      dan negatif (misal: -1 sampai -5) untuk perilaku buruk.
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Jenis Perilaku</label>
    <select name="type" class="form-select" required>
      <option value="positive" <?= $behavior['type']=='positive'?'selected':'' ?>>Positif</option>
      <option value="negative" <?= $behavior['type']=='negative'?'selected':'' ?>>Negatif</option>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">💾 Simpan</button>
  <a href="<?= base_url('admin/behaviors') ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
