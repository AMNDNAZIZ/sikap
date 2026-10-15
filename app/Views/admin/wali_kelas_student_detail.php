<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
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
  .tab-content p {
    margin-bottom: 0;
    padding: 0.5rem 0;
    color: #495057;
  }
  .tab-content label {
    margin-bottom: 0.5rem;
    color: #212529;
  }
</style>

<div class="container-fluid">

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('admin/wali-kelas-students') ?>">Data Siswa Kelas</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($student['name']) ?></li>
    </ol>
  </nav>

  <form action="<?= base_url('admin/wali-kelas-students/update/' . $student['id']) ?>" method="post">
    <?= csrf_field() ?>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 text-primary">
          <i class="bi bi-person-fill me-2"></i>Data Siswa – <?= esc($student['name']) ?>
        </h5>
        <a href="<?= base_url('admin/wali-kelas-students') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>

      <div class="card-body p-0">
        <!-- Photo and Status Section -->
        <div class="p-4 border-bottom bg-light">
          <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
              <?php if (!empty($student['photo']) && file_exists(FCPATH . 'uploads/students/' . $student['photo'])): ?>
                <img src="<?= base_url('uploads/students/' . htmlspecialchars($student['photo'])) ?>"
                     class="rounded img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Foto">
              <?php else: ?>
                <div class="bg-white rounded d-flex align-items-center justify-content-center mx-auto border"
                  style="width: 100px; height: 100px;">
                  <i class="bi bi-person fs-1 text-secondary"></i>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-bold">Foto Siswa</label>
              <p class="text-muted">
                <?php if (!empty($student['photo'])): ?>
                  <?= htmlspecialchars($student['photo']) ?>
                <?php else: ?>
                  <em>Tidak ada foto</em>
                <?php endif; ?>
              </p>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-bold">Status Siswa</label>
              <p>
                <?php $status = $record['status'] ?? 'aktif'; ?>
                <?php if ($status == 'aktif'): ?>
                  <span class="badge bg-success">Aktif</span>
                <?php elseif ($status == 'nonaktif'): ?>
                  <span class="badge bg-warning">Nonaktif</span>
                <?php elseif ($status == 'dropout'): ?>
                  <span class="badge bg-danger">Dropout</span>
                <?php elseif ($status == 'lulus'): ?>
                  <span class="badge bg-info">Lulus</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs px-4 pt-3 bg-white" id="detailTab" role="tablist">
          <li class="nav-item">
            <button class="nav-link active fw-bold" id="personal-tab" data-bs-toggle="tab"
                    data-bs-target="#personal" type="button" role="tab">Pribadi</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" id="address-tab" data-bs-toggle="tab"
                    data-bs-target="#address" type="button" role="tab">Alamat</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" id="family-tab" data-bs-toggle="tab"
                    data-bs-target="#family" type="button" role="tab">Keluarga</button>
          </li>
        </ul>

        <div class="tab-content p-4" id="detailTabContent">
          <!-- Tab Pribadi -->
          <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Nama Lengkap</label>
                <p><?= htmlspecialchars($student['name']) ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">NISN</label>
                <p><?= htmlspecialchars($student['nisn']) ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">NIS</label>
                <p><?= htmlspecialchars($student['nis']) ?></p>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">NIK Siswa</label>
                <p><?= htmlspecialchars($student['nik'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Jenis Kelamin</label>
                <p><?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Agama</label>
                <p><?= htmlspecialchars($student['religion'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Tempat Lahir</label>
                <p><?= htmlspecialchars($student['birth_place'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Lahir</label>
                <p><?= htmlspecialchars($student['birth_date'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Kewarganegaraan</label>
                <p><?= htmlspecialchars($student['nationality'] ?? 'WNI') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Anak ke</label>
                <input type="number" name="child_order" class="form-control"
                       value="<?= esc($student['child_order'] ?? '') ?>" min="1">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Jenis Pendaftaran</label>
                <p><?= htmlspecialchars($student['registration_type'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Masuk</label>
                <p><?= htmlspecialchars($student['admission_date'] ?? '-') ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold">Diterima di Kelas</label>
                <p><?= htmlspecialchars($student['admission_class'] ?? '-') ?></p>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Kebutuhan Khusus</label>
                <input type="text" name="special_needs" class="form-control"
                       value="<?= esc($student['special_needs'] ?? '') ?>" placeholder="Contoh: Tidak ada">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <p><?= htmlspecialchars($student['email'] ?? '-') ?></p>
              </div>
            </div>
          </div>

          <!-- Tab Alamat -->
          <div class="tab-pane fade" id="address" role="tabpanel">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="2"><?= esc($student['address'] ?? '') ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Tempat Tinggal</label>
                <select name="residence_type" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php foreach (['Bersama Orang Tua', 'Wali', 'Kos', 'Asrama', 'Panti Asuhan', 'Lainnya'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($student['residence_type'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Moda Transportasi</label>
                <select name="transportation" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php foreach (['Jalan Kaki', 'Sepeda', 'Sepeda Motor', 'Angkutan Umum', 'Mobil Pribadi', 'Ojek', 'Andong', 'Perahu', 'Lainnya'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($student['transportation'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Jarak ke Sekolah</label>
                <select name="distance" class="form-select">
                  <option value="">- Pilih -</option>
                  <?php foreach (['< 1 km', '1-5 km', '5-10 km', '10-20 km', '> 20 km'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($student['distance'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Tab Keluarga -->
          <div class="tab-pane fade" id="family" role="tabpanel">
            <!-- Ayah -->
            <div class="bg-light p-3 rounded mb-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-person-fill me-1 text-primary"></i>DATA AYAH</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Nama Ayah</label>
                  <input type="text" name="father_name" class="form-control" value="<?= esc($student['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">NIK Ayah</label>
                  <input type="text" name="father_nik" class="form-control" value="<?= esc($student['father_nik'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Tahun Lahir</label>
                  <input type="text" name="father_birth_year" class="form-control" value="<?= esc($student['father_birth_year'] ?? '') ?>" placeholder="cth: 1975">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Pendidikan</label>
                  <select name="father_education" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'D1', 'D2', 'D3', 'D4/S1', 'S2', 'S3'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['father_education'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Penghasilan</label>
                  <select name="father_income" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['< Rp 500.000', 'Rp 500.000 - 1.000.000', 'Rp 1.000.000 - 2.000.000', 'Rp 2.000.000 - 5.000.000', '> Rp 5.000.000', 'Tidak Berpenghasilan'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['father_income'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold">Pekerjaan Ayah</label>
                  <input type="text" name="father_job" class="form-control" value="<?= esc($student['father_job'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Ibu -->
            <div class="bg-light p-3 rounded mb-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-person-fill me-1 text-danger"></i>DATA IBU</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Nama Ibu</label>
                  <input type="text" name="mother_name" class="form-control" value="<?= esc($student['mother_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">NIK Ibu</label>
                  <input type="text" name="mother_nik" class="form-control" value="<?= esc($student['mother_nik'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Tahun Lahir</label>
                  <input type="text" name="mother_birth_year" class="form-control" value="<?= esc($student['mother_birth_year'] ?? '') ?>" placeholder="cth: 1978">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Pendidikan</label>
                  <select name="mother_education" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'D1', 'D2', 'D3', 'D4/S1', 'S2', 'S3'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['mother_education'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Penghasilan</label>
                  <select name="mother_income" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['< Rp 500.000', 'Rp 500.000 - 1.000.000', 'Rp 1.000.000 - 2.000.000', 'Rp 2.000.000 - 5.000.000', '> Rp 5.000.000', 'Tidak Berpenghasilan'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['mother_income'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold">Pekerjaan Ibu</label>
                  <input type="text" name="mother_job" class="form-control" value="<?= esc($student['mother_job'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Wali -->
            <div class="bg-light p-3 rounded">
              <h6 class="fw-bold mb-3"><i class="bi bi-person-fill me-1 text-success"></i>DATA WALI</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Nama Wali</label>
                  <input type="text" name="guardian_name" class="form-control" value="<?= esc($student['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Pendidikan Wali</label>
                  <select name="guardian_education" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'D1', 'D2', 'D3', 'D4/S1', 'S2', 'S3'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['guardian_education'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Pekerjaan Wali</label>
                  <input type="text" name="guardian_job" class="form-control" value="<?= esc($student['guardian_job'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Penghasilan Wali</label>
                  <select name="guardian_income" class="form-select">
                    <option value="">- Pilih -</option>
                    <?php foreach (['< Rp 500.000', 'Rp 500.000 - 1.000.000', 'Rp 1.000.000 - 2.000.000', 'Rp 2.000.000 - 5.000.000', '> Rp 5.000.000', 'Tidak Berpenghasilan'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($student['guardian_income'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-footer bg-white p-4 d-flex justify-content-between border-top">
          <a href="<?= base_url('admin/wali-kelas-students') ?>" class="btn btn-secondary px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
            <i class="bi bi-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </div>
    </div>

  </form>
</div>

<?= $this->section('scripts') ?>
<script>
  // Bootstrap tabs already initialized by the layout's JS
  document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = document.querySelectorAll('#detailTab button');
    triggerTabList.forEach(triggerEl => {
      triggerEl.addEventListener('click', function(event) {
        event.preventDefault();
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
      });
    });
  });
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
