<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>✏️ Edit Nilai (<?= ucfirst($type) ?>)</h2>

<form method="post" action="<?= base_url("admin/assessments/update/{$score['id']}/{$type}") ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Siswa</label>
    <input type="text" class="form-control" value="<?= esc($student['name']) ?>" disabled>
  </div>

  <div class="mb-3">
    <label class="form-label">Materi</label>
    <input type="text" class="form-control" value="<?= esc($material['title']) ?>" disabled>
  </div>

  <div class="mb-3">
    <label class="form-label">Nilai</label>
    <input type="number" name="score" class="form-control" 
           value="<?= esc($score['score']) ?>" min="0" max="100" required>
  </div>

  <button type="submit" class="btn btn-success">💾 Simpan Perubahan</button>
  <input type="hidden" name="redirect_url" value="<?= esc($redirect_url) ?>">
  <a href="<?= esc($redirect_url ?: site_url('admin/assessments')) ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>
