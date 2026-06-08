<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>✏️ Edit Perilaku</h4>

<form method="post" action="<?= base_url('admin/behaviors/update/'.$behavior['id']) ?>">
  <?= csrf_field() ?>
  
  <div class="mb-3">
    <label>Nama Perilaku</label>
    <input type="text" name="name" value="<?= esc($behavior['name']) ?>" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Poin</label>
    <input type="number" name="points" value="<?= esc($behavior['points']) ?>" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Jenis</label>
    <select name="type" class="form-select" required>
      <option value="positive" <?= $behavior['type']=='positive'?'selected':'' ?>>Positif</option>
      <option value="negative" <?= $behavior['type']=='negative'?'selected':'' ?>>Negatif</option>
    </select>
  </div>

  <button class="btn btn-primary">Update</button>
  <a href="<?= base_url('admin/behaviors') ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
