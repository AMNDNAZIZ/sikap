<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="mb-3">
  <a href="<?= base_url('admin/student-mutation') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<h4>📋 Tambah Mutasi Siswa</h4>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<form method="post" action="<?= base_url('admin/student-mutation/store') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card mb-3">
    <div class="card-header fw-bold">Jenis Mutasi</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-bold">Tipe Mutasi <span class="text-danger">*</span></label>
        <select name="type" id="mutationType" class="form-select" required onchange="toggleFields()">
          <option value="">-- Pilih Jenis --</option>
          <option value="masuk" <?= old('type') == 'masuk' ? 'selected' : '' ?>>Mutasi Masuk</option>
          <option value="keluar" <?= old('type') == 'keluar' ? 'selected' : '' ?>>Mutasi Keluar</option>
          <option value="pindah_kelas" <?= old('type') == 'pindah_kelas' ? 'selected' : '' ?>>Pindah Kelas</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Tanggal Mutasi <span class="text-danger">*</span></label>
        <input type="date" name="mutation_date" class="form-control" value="<?= old('mutation_date', date('Y-m-d')) ?>" required>
      </div>
    </div>
  </div>

  <!-- Section: Siswa (untuk keluar & pindah_kelas) -->
  <div class="card mb-3" id="sectionStudent">
    <div class="card-header fw-bold">Data Siswa</div>
    <div class="card-body">
      <!-- Pilih siswa existing (keluar & pindah kelas) -->
      <div class="mb-3" id="fieldStudentSelect">
        <label class="form-label">Pilih Siswa <span class="text-danger">*</span></label>
        <select name="student_id" class="form-select" id="studentSelect">
          <option value="">-- Pilih Siswa --</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>" <?= old('student_id') == $s['id'] ? 'selected' : '' ?>>
              <?= esc($s['name']) ?> (<?= esc($s['nisn']) ?>)
            </option>
          <?php endforeach ?>
        </select>
      </div>

      <!-- Data siswa baru (untuk mutasi masuk) -->
      <div id="sectionNewStudent" style="display:none">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">NISN</label>
            <input type="text" name="nisn" class="form-control" value="<?= old('nisn') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">NIS</label>
            <input type="text" name="nis" class="form-control" value="<?= old('nis') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="student_name" class="form-control" value="<?= old('student_name') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Jenis Kelamin</label>
            <select name="gender" class="form-select">
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tempat Lahir</label>
            <input type="text" name="birth_place" class="form-control" value="<?= old('birth_place') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal Lahir</label>
            <input type="date" name="birth_date" class="form-control" value="<?= old('birth_date') ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Section: Sekolah Asal / Tujuan -->
  <div class="card mb-3" id="sectionSchool">
    <div class="card-header fw-bold">Informasi Sekolah & Kelas</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6" id="fieldFromSchool" style="display:none">
          <label class="form-label">Sekolah Asal <span class="text-danger">*</span></label>
          <input type="text" name="from_school" class="form-control" value="<?= old('from_school') ?>">
        </div>
        <div class="col-md-6" id="fieldToSchool" style="display:none">
          <label class="form-label">Sekolah Tujuan <span class="text-danger">*</span></label>
          <input type="text" name="to_school" class="form-control" value="<?= old('to_school') ?>">
        </div>
        <div class="col-md-6" id="fieldFromClass" style="display:none">
          <label class="form-label">Kelas Asal</label>
          <select name="from_class_id" class="form-select">
            <option value="">-- Pilih --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= old('from_class_id') == $c['id'] ? 'selected' : '' ?>>
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-6" id="fieldToClass" style="display:none">
          <label class="form-label">Kelas Tujuan <span id="labelActiveOnly" style="display:none" class="badge bg-success ms-1">Kelas Aktif</span></label>
          <select name="to_class_id" class="form-select" id="toClassSelect">
            <option value="">-- Pilih --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= old('to_class_id') == $c['id'] ? 'selected' : '' ?>
                data-active="<?= $c['is_active'] ?>">
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
          <!-- Dropdown khusus kelas aktif untuk mutasi masuk (disembunyikan, digunakan via JS) -->
          <select id="toClassActiveSelect" style="display:none" class="form-select">
            <option value="">-- Pilih --</option>
            <?php foreach ($activeClasses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= old('to_class_id') == $c['id'] ? 'selected' : '' ?>>
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Section: Keterangan -->
  <div class="card mb-3">
    <div class="card-header fw-bold">Keterangan</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nomor Surat</label>
          <input type="text" name="letter_number" class="form-control" value="<?= old('letter_number') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Alasan Mutasi</label>
          <textarea name="reason" class="form-control" rows="2"><?= old('reason') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Dokumen Pendukung (PDF/Gambar)</label>
          <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
      </div>
    </div>
  </div>

  <div class="mb-3">
    <button type="submit" class="btn btn-success btn-lg">Simpan Mutasi</button>
    <a href="<?= base_url('admin/student-mutation') ?>" class="btn btn-outline-secondary btn-lg">Batal</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function toggleFields() {
  const type = document.getElementById('mutationType').value;

  const toClassSelect       = document.getElementById('toClassSelect');
  const toClassActiveSelect = document.getElementById('toClassActiveSelect');
  const labelActiveOnly     = document.getElementById('labelActiveOnly');

  // Sembunyikan semua section dinamis
  document.getElementById('sectionNewStudent').style.display = 'none';
  document.getElementById('fieldStudentSelect').style.display = 'none';
  document.getElementById('fieldFromSchool').style.display = 'none';
  document.getElementById('fieldToSchool').style.display = 'none';
  document.getElementById('fieldFromClass').style.display = 'none';
  document.getElementById('fieldToClass').style.display = 'none';

  // Reset nama select agar tidak double submit
  toClassSelect.name       = '';
  toClassActiveSelect.name = '';
  labelActiveOnly.style.display = 'none';

  if (type === 'masuk') {
    document.getElementById('sectionNewStudent').style.display = 'block';
    document.getElementById('fieldFromSchool').style.display = 'block';
    document.getElementById('fieldToClass').style.display = 'block';

    // Mutasi masuk: gunakan dropdown kelas aktif saja
    toClassSelect.style.display       = 'none';
    toClassActiveSelect.style.display = 'block';
    toClassActiveSelect.name          = 'to_class_id';
    labelActiveOnly.style.display     = 'inline-block';

  } else if (type === 'keluar') {
    document.getElementById('fieldStudentSelect').style.display = 'block';
    document.getElementById('fieldToSchool').style.display = 'block';

  } else if (type === 'pindah_kelas') {
    document.getElementById('fieldStudentSelect').style.display = 'block';
    document.getElementById('fieldFromClass').style.display = 'block';
    document.getElementById('fieldToClass').style.display = 'block';

    // Pindah kelas: gunakan semua kelas (sudah aktif-aware dari data)
    toClassSelect.style.display       = 'block';
    toClassActiveSelect.style.display = 'none';
    toClassSelect.name                = 'to_class_id';
  }
}
document.addEventListener('DOMContentLoaded', toggleFields);
</script>
<?= $this->endSection() ?>
