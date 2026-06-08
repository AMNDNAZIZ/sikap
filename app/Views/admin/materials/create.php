<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>➕ Tambah Materi - <?= esc($subject['name']) ?></h3>

<form method="post" action="<?= site_url('admin/materials/store') ?>">
  <?= csrf_field() ?>
  
  <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">

  <?php if (!empty($returnUrl)): ?>
      <input type="hidden" name="return" value="<?= esc($returnUrl) ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>Mata Pelajaran</label>
    <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
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
      <option value="1">Ganjil</option>
      <option value="2">Genap</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Judul Materi</label>
    <input type="text" name="title" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Deskripsi</label>
    <textarea name="description" class="form-control" rows="4"></textarea>
  </div>

  <button type="submit" class="btn btn-success">💾 Simpan</button>
  <a href="<?= site_url('admin/materials/index/'.$subject['id']) ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>
