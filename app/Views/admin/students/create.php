<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>➕ Tambah Siswa</h4>

<form action="<?= base_url('admin/students/store') ?>" method="post">
  <?= csrf_field() ?>

  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label">NISN</label>
      <input type="text" name="nisn" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">NIS</label>
      <input type="text" name="nis" class="form-control">
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Nama Lengkap</label>
    <input type="text" name="name" class="form-control" required>
  </div>

  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label">Jenis Kelamin</label>
      <select name="gender" class="form-select" required>
        <option value="">- Pilih -</option>
        <option value="L">Laki-laki</option>
        <option value="P">Perempuan</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Kelas</label>
      <select name="class_id" class="form-select" required>
        <option value="">- Pilih -</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>


  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label">Agama</label>
      <select name="religion" class="form-select" required>
        <option value="">- Pilih Agama -</option>
        <option value="Islam">Islam</option>
        <option value="Kristen">Kristen</option>
        <option value="Katholik">Katholik</option>
        <option value="Hindu">Hindu</option>
        <option value="Budha">Budha</option>
        <option value="Khonghucu">Khonghucu</option>
      </select>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label">Tempat Lahir</label>
      <input type="text" name="birth_place" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" name="birth_date" class="form-control">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">💾 Simpan</button>
  <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">⬅️ Kembali</a>
</form>

<?= $this->endSection() ?>