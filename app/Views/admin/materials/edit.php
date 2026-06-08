<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>✏️ Edit Materi</h3>

<form method="post" action="<?= site_url('admin/materials/update/'.$material['id']) ?>">
  <?= csrf_field() ?>

  <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">

  <div class="mb-3">
    <label>Mata Pelajaran</label>
    <input type="text" class="form-control" value="<?= esc($subject['name']) ?>" readonly>
  </div>

  <div class="mb-3">
    <label>Tahun Ajaran</label>
    <input type="hidden" name="year_id" value="<?= $activeYear['id'] ?? '' ?>">
    <input type="text" class="form-control" value="<?= $activeYear['year'] ?? '-' ?>" readonly>
  </div>

  <div class="mb-3">
    <label>Semester</label>
    <select name="semester" class="form-select" required>
      <option value="1" <?= $material['semester']=='1'?'selected':'' ?>>Ganjil</option>
      <option value="2"  <?= $material['semester']=='2'?'selected':'' ?>>Genap</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Judul Materi</label>
    <input type="text" name="title" class="form-control" value="<?= esc($material['title']) ?>" required>
  </div>

  <div class="mb-3">
    <label>Deskripsi</label>
    <textarea name="description" class="form-control" rows="4"><?= esc($material['description']) ?></textarea>
  </div>

  <button type="submit" class="btn btn-success">💾 Update</button>
  <a href="<?= site_url('admin/materials/'.$subject['id']) ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>
