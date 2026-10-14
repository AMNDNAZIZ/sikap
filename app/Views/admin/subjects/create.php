<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>➕ Tambah Mata Pelajaran</h4>

<form method="post" action="<?= base_url('admin/subjects/store') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Kode</label>
    <input type="text" name="code" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Nama Mata Pelajaran</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Kelompok</label>
    <input type="text" name="subject_group" class="form-control">
    <label class="form-label">Urutan</label>
    <input type="number" name="sort_order" class="form-control" value="0">
  </div>
  <div class="mb-3">
    <label class="form-label">Kelompok Mapel</label>
    <select name="subject_group" class="form-select">
      <option value="A">Kelompok A (Wajib)</option>
      <option value="B">Kelompok B (Wajib)</option>
      <option value="C">Kelompok C (Peminatan)</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Agama (Khusus Mapel Agama)</label>
    <select name="religion" class="form-select">
      <option value="">- Umum (Semua Agama) -</option>
      <option value="Islam">Islam</option>
      <option value="Kristen">Kristen</option>
      <option value="Katholik">Katholik</option>
      <option value="Hindu">Hindu</option>
      <option value="Budha">Budha</option>
      <option value="Khonghucu">Khonghucu</option>
    </select>
    <div class="form-text">Biarkan kosong jika mapel ini untuk semua siswa.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="is_active" class="form-select">
      <option value="1">Aktif</option>
      <option value="0">Nonaktif</option>
    </select>
  </div>
  <div class="mb-3">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="is_bk" id="is_bk" value="1">
      <label class="form-check-label" for="is_bk">
        🧠 Mapel BP/BK (Bimbingan Penyuluhan/Konseling)
      </label>
    </div>
    <div class="form-text text-info">
      Jika dicentang, mapel ini <strong>dikecualikan</strong> dari modul Materi, Penilaian, Administrasi Guru, dan Tugas — tetapi tetap dapat digunakan di <strong>Penjadwalan</strong>.
    </div>
  </div>
  <button type="submit" class="btn btn-primary">💾 Simpan</button>
  <a href="<?= base_url('admin/subjects') ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>