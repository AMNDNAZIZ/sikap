<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 text-primary"><i class="fas fa-user-edit me-2"></i>Edit Data Siswa</h5>
    <a href="<?= base_url('admin/students/show/' . $student['id']) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-times me-1"></i> Batal
    </a>
  </div>
  <div class="card-body p-0">
    <form action="<?= base_url('admin/students/update/' . $student['id']) ?>" method="post"
      enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="p-4 border-bottom bg-light">
        <div class="row align-items-center">
          <div class="col-md-2 text-center mb-3 mb-md-0">
            <?php if (!empty($student['photo']) && file_exists(FCPATH . 'uploads/students/' . $student['photo'])): ?>
              <img src="<?= base_url('uploads/students/' . $student['photo']) ?>" class="rounded img-thumbnail"
                style="width: 100px; height: 100px; object-fit: cover;" alt="Foto">
            <?php else: ?>
              <div class="bg-white rounded d-flex align-items-center justify-content-center mx-auto border"
                style="width: 100px; height: 100px;">
                <i class="fas fa-user fa-3x text-light"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-bold">Foto Siswa</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
            <small class="text-muted">Format: JPG, PNG. Maks: 2MB</small>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-bold">Status Siswa</label>
            <select name="status" class="form-select">
              <?php $currStatus = $record['status'] ?? 'aktif'; ?>
              <option value="aktif" <?= $currStatus == 'aktif' ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= $currStatus == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
              <option value="dropout" <?= $currStatus == 'dropout' ? 'selected' : '' ?>>Dropout</option>
              <option value="lulus" <?= $currStatus == 'lulus' ? 'selected' : '' ?>>Lulus</option>
            </select>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs px-4 pt-3 bg-white" id="editTab" role="tablist">
        <li class="nav-item">
          <button class="nav-link active fw-bold" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal"
            type="button" role="tab">Pribadi</button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-bold" id="address-tab" data-bs-toggle="tab" data-bs-target="#address" type="button"
            role="tab">Alamat</button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-bold" id="family-tab" data-bs-toggle="tab" data-bs-target="#family" type="button"
            role="tab">Keluarga</button>
        </li>
      </ul>

      <div class="tab-content p-4" id="editTabContent">
        <!-- Tab Pribadi -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="name" class="form-control" value="<?= esc($student['name']) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">NISN</label>
              <input type="text" name="nisn" class="form-control" value="<?= esc($student['nisn']) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">NIS</label>
              <input type="text" name="nis" class="form-control" value="<?= esc($student['nis']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">NIK Siswa</label>
              <input type="text" name="nik" class="form-control" value="<?= esc($student['nik']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Jenis Kelamin</label>
              <select name="gender" class="form-select" required>
                <option value="L" <?= $student['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= $student['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Agama</label>
              <select name="religion" class="form-select" required>
                <?php $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu']; ?>
                <?php foreach ($religions as $r): ?>
                  <option value="<?= $r ?>" <?= ($student['religion'] ?? '') == $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" name="birth_place" class="form-control" value="<?= esc($student['birth_place']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" name="birth_date" class="form-control" value="<?= esc($student['birth_date']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Kewarganegaraan</label>
              <input type="text" name="nationality" class="form-control"
                value="<?= esc($student['nationality'] ?? 'WNI') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Anak ke</label>
              <input type="text" name="child_order" class="form-control" value="<?= esc($student['child_order']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Jenis Pendaftaran</label>
              <select name="registration_type" class="form-select">
                <option value="Siswa Baru" <?= ($student['registration_type'] ?? '') == 'Siswa Baru' ? 'selected' : '' ?>>
                  Siswa Baru</option>
                <option value="Pindahan" <?= ($student['registration_type'] ?? '') == 'Pindahan' ? 'selected' : '' ?>>
                  Pindahan</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal Masuk</label>
              <input type="date" name="admission_date" class="form-control"
                value="<?= esc($student['admission_date']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Diterima di Kelas (Permanen)</label>
              <input type="text" name="admission_class" class="form-control"
                value="<?= esc($student['admission_class']) ?>" placeholder="Misal: Kelas 1, Kelas 7">
            </div>
            <div class="col-md-3">
              <label class="form-label">Kelas Saat Ini (Rombel)</label>
              <select name="class_id" class="form-select">
                <option value="">- Tanpa Kelas -</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= $class['id'] ?>" <?= ($record['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                    <?= esc($class['name']) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kebutuhan Khusus</label>
              <input type="text" name="special_needs" class="form-control" value="<?= esc($student['special_needs']) ?>"
                placeholder="tunarungu, tunanetra, autis, ADHD, dll">
            </div>
          </div>
        </div>

        <!-- Tab Alamat -->
        <div class="tab-pane fade" id="address" role="tabpanel">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Alamat Lengkap</label>
              <textarea name="address" class="form-control" rows="3"><?= esc($student['address']) ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tempat Tinggal</label>
              <select name="residence_type" class="form-select">
                <option value="">- Pilih -</option>
                <option value="Bersama Orang Tua" <?= ($student['residence_type'] ?? '') == 'Bersama Orang Tua' ? 'selected' : '' ?>>Bersama Orang Tua</option>
                <option value="Wali" <?= ($student['residence_type'] ?? '') == 'Wali' ? 'selected' : '' ?>>Wali</option>
                <option value="Asrama" <?= ($student['residence_type'] ?? '') == 'Asrama' ? 'selected' : '' ?>>Asrama
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Moda Transportasi</label>
              <select name="transportation" class="form-select">
                <option value="">- Pilih -</option>
                <option value="Antar-jemput mobil" <?= ($student['transportation'] ?? '') == 'Antar-jemput mobil' ? 'selected' : '' ?>>Antar-jemput mobil</option>
                <option value="Antar-jemput motor" <?= ($student['transportation'] ?? '') == 'Antar-jemput motor' ? 'selected' : '' ?>>Antar-jemput motor</option>
                <option value="Sepeda" <?= ($student['transportation'] ?? '') == 'Sepeda' ? 'selected' : '' ?>>Sepeda
                </option>
                <option value="Jalan kaki" <?= ($student['transportation'] ?? '') == 'Jalan kaki' ? 'selected' : '' ?>>
                  Jalan kaki</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Jarak ke Sekolah</label>
              <input type="text" name="distance" class="form-control" value="<?= esc($student['distance']) ?>"
                placeholder="Contoh: 2 km">
            </div>
            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input type="text" name="latitude" class="form-control" value="<?= esc($student['latitude']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input type="text" name="longitude" class="form-control" value="<?= esc($student['longitude']) ?>">
            </div>
          </div>
        </div>

        <!-- Tab Keluarga -->
        <div class="tab-pane fade" id="family" role="tabpanel">
          <!-- Ayah -->
          <div class="bg-light p-3 rounded mb-4">
            <h6 class="fw-bold mb-3">DATA AYAH</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama Ayah</label>
                <input type="text" name="father_name" class="form-control" value="<?= esc($student['father_name']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">NIK Ayah</label>
                <input type="text" name="father_nik" class="form-control" value="<?= esc($student['father_nik']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Tahun Lahir</label>
                <input type="text" name="father_birth_year" class="form-control"
                  value="<?= esc($student['father_birth_year']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pendidikan</label>
                <select name="father_education" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php $edu = ['SD', 'SLTP', 'SLTA', 'S1', 'S2', 'S3']; ?>
                  <?php foreach ($edu as $e): ?>
                    <option value="<?= $e ?>" <?= ($student['father_education'] ?? '') == $e ? 'selected' : '' ?>><?= $e ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Penghasilan</label>
                <input type="text" name="father_income" class="form-control"
                  value="<?= esc($student['father_income']) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Pekerjaan Ayah</label>
                <input type="text" name="father_job" class="form-control" value="<?= esc($student['father_job']) ?>"
                  placeholder="ASN, TNI/Polri, Wirausaha, dll">
              </div>
            </div>
          </div>

          <!-- Ibu -->
          <div class="bg-light p-3 rounded mb-4">
            <h6 class="fw-bold mb-3">DATA IBU</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama Ibu</label>
                <input type="text" name="mother_name" class="form-control" value="<?= esc($student['mother_name']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">NIK Ibu</label>
                <input type="text" name="mother_nik" class="form-control" value="<?= esc($student['mother_nik']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Tahun Lahir</label>
                <input type="text" name="mother_birth_year" class="form-control"
                  value="<?= esc($student['mother_birth_year']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Pendidikan</label>
                <select name="mother_education" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php foreach ($edu as $e): ?>
                    <option value="<?= $e ?>" <?= ($student['mother_education'] ?? '') == $e ? 'selected' : '' ?>><?= $e ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Penghasilan</label>
                <input type="text" name="mother_income" class="form-control"
                  value="<?= esc($student['mother_income']) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Pekerjaan Ibu</label>
                <input type="text" name="mother_job" class="form-control" value="<?= esc($student['mother_job']) ?>"
                  placeholder="ASN, TNI/Polri, Wirausaha, dll">
              </div>
            </div>
          </div>

          <!-- Wali -->
          <div class="bg-light p-3 rounded">
            <h6 class="fw-bold mb-3">DATA WALI</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama Wali</label>
                <input type="text" name="guardian_name" class="form-control"
                  value="<?= esc($student['guardian_name']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Pendidikan Wali</label>
                <select name="guardian_education" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php foreach ($edu as $e): ?>
                    <option value="<?= $e ?>" <?= ($student['guardian_education'] ?? '') == $e ? 'selected' : '' ?>><?= $e ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Pekerjaan Wali</label>
                <input type="text" name="guardian_job" class="form-control"
                  value="<?= esc($student['guardian_job']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Penghasilan Wali</label>
                <input type="text" name="guardian_income" class="form-control"
                  value="<?= esc($student['guardian_income']) ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer bg-white p-4 text-end border-top shadow-sm">
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
          <i class="fas fa-save me-1"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  /* Tab Navigation Styling - Improved Visibility */
  .nav-tabs {
    border-bottom: 2px solid #e9ecef;
  }

  .nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
    background: transparent;
    font-size: 0.95rem;
  }

  .nav-tabs .nav-link:hover {
    color: #0d6efd;
    background: #f8f9fa;
    border-color: transparent;
    border-bottom-color: #dee2e6;
  }

  .nav-tabs .nav-link.active {
    color: #0d6efd !important;
    background: #f8f9fa;
    border: none;
    border-bottom: 3px solid #0d6efd;
    font-weight: 600;
  }

  /* Form Elements */
  .form-control:focus,
  .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .nav-tabs .nav-link {
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
    }
  }
</style>

<?= $this->endSection() ?>