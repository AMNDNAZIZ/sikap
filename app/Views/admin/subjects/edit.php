<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>✏️ Edit Mata Pelajaran</h4>

<form method="post" action="<?= base_url('admin/subjects/update/' . $subject['id']) ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Kode</label>
    <input type="text" name="code" value="<?= esc($subject['code']) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Nama Mata Pelajaran</label>
    <input type="text" name="name" value="<?= esc($subject['name']) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Kelompok</label>
    <input type="text" name="subject_group" value="<?= esc($subject['subject_group']) ?>" class="form-control">
    <label class="form-label">Urutan</label>
    <input type="number" name="sort_order" value="<?= esc($subject['sort_order']) ?>" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Kelompok Mapel</label>
    <select name="subject_group" class="form-select">
      <option value="A" <?= $subject['subject_group'] == 'A' ? 'selected' : '' ?>>Kelompok A (Wajib)</option>
      <option value="B" <?= $subject['subject_group'] == 'B' ? 'selected' : '' ?>>Kelompok B (Wajib)</option>
      <option value="C" <?= $subject['subject_group'] == 'C' ? 'selected' : '' ?>>Kelompok C (Peminatan)</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Agama (Khusus Mapel Agama)</label>
    <select name="religion" class="form-select">
      <option value="">- Umum (Semua Agama) -</option>
      <option value="Islam" <?= isset($subject['religion']) && $subject['religion'] == 'Islam' ? 'selected' : '' ?>>Islam
      </option>
      <option value="Kristen" <?= isset($subject['religion']) && $subject['religion'] == 'Kristen' ? 'selected' : '' ?>>
        Kristen</option>
      <option value="Katholik" <?= isset($subject['religion']) && $subject['religion'] == 'Katholik' ? 'selected' : '' ?>>
        Katholik</option>
      <option value="Hindu" <?= isset($subject['religion']) && $subject['religion'] == 'Hindu' ? 'selected' : '' ?>>Hindu
      </option>
      <option value="Budha" <?= isset($subject['religion']) && $subject['religion'] == 'Budha' ? 'selected' : '' ?>>Budha
      </option>
      <option value="Khonghucu" <?= isset($subject['religion']) && $subject['religion'] == 'Khonghucu' ? 'selected' : '' ?>>Khonghucu</option>
    </select>
    <div class="form-text">Biarkan kosong jika mapel ini untuk semua siswa.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="is_active" class="form-select">
      <option value="1" <?= $subject['is_active'] ? 'selected' : '' ?>>Aktif</option>
      <option value="0" <?= !$subject['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">💾 Update</button>
  <a href="<?= base_url('admin/subjects') ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>