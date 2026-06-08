<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center">
                <div class="mb-4">
                    <?php if (!empty($student['photo']) && file_exists(FCPATH . 'uploads/students/' . $student['photo'])): ?>
                        <img src="<?= base_url('uploads/students/' . $student['photo']) ?>"
                            class="rounded-circle img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;"
                            alt="Foto Siswa">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto"
                            style="width: 200px; height: 200px;">
                            <i class="fas fa-user-graduate fa-6x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="mb-1 text-primary">
                    <?= esc($student['name']) ?>
                </h4>
                <p class="text-muted mb-3">
                    <?= esc($student['nis']) ?> /
                    <?= esc($student['nisn']) ?>
                </p>
                <div
                    class="badge bg-<?= ($record['status'] ?? 'aktif') == 'aktif' ? 'success' : 'secondary' ?> fs-6 mb-3">
                    <?= ucfirst($record['status'] ?? 'aktif') ?>
                </div>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/students/edit/' . $student['id']) ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit Data
                    </a>
                    <a href="<?= base_url('admin/students') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 text-primary"><i class="fas fa-school me-2"></i>Informasi Akademik</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">Kelas</span>
                        <span class="fw-bold">
                            <?= esc($record['class_name'] ?? '-') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">NIS</span>
                        <span class="fw-bold">
                            <?= esc($student['nis'] ?? '-') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">NISN</span>
                        <span class="fw-bold">
                            <?= esc($student['nisn'] ?? '-') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">Jenjang/Diterima di Kelas</span>
                        <span class="fw-bold">
                            <?= esc($student['admission_class'] ?? '-') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">Jenis Pendaftaran</span>
                        <span class="badge bg-info">
                            <?= esc($student['registration_type'] ?? 'Siswa Baru') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted">Tanggal Masuk</span>
                        <span class="fw-bold">
                            <?= !empty($student['admission_date']) ? date('d/m/Y', strtotime($student['admission_date'])) : '-' ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-tabs card-header-tabs" id="studentTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="personal-tab" data-bs-toggle="tab"
                            data-bs-target="#personal" type="button" role="tab">Pribadi</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="address-tab" data-bs-toggle="tab" data-bs-target="#address"
                            type="button" role="tab">Alamat</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="family-tab" data-bs-toggle="tab" data-bs-target="#family"
                            type="button" role="tab">Keluarga</button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="studentTabContent">
                    <!-- Data Pribadi -->
                    <div class="tab-pane fade show active" id="personal" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">NIK</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['nik'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Jenis
                                    Kelamin</label>
                                <p class="mb-0 fs-5">
                                    <?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Tempat Lahir</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['birth_place'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Tanggal
                                    Lahir</label>
                                <p class="mb-0 fs-5">
                                    <?= !empty($student['birth_date']) ? date('d/m/Y', strtotime($student['birth_date'])) : '-' ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Anak ke</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['child_order'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Agama</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['religion'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label
                                    class="text-muted small d-block mb-1 text-uppercase fw-bold">Kewarganegaraan</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['nationality'] ?? 'WNI') ?>
                                </p>
                            </div>
                            <div class="col-sm-12">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Kebutuhan
                                    Khusus</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['special_needs'] ?? 'Tidak Ada') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat & Transportasi -->
                    <div class="tab-pane fade" id="address" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Alamat
                                    Lengkap</label>
                                <p class="mb-0 fs-5">
                                    <?= nl2br(esc($student['address'] ?? '-')) ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Tempat
                                    Tinggal</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['residence_type'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Moda
                                    Transportasi</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['transportation'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Jarak ke
                                    Sekolah</label>
                                <p class="mb-0 fs-5">
                                    <?= esc($student['distance'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block mb-1 text-uppercase fw-bold">Koordinat</label>
                                <p class="mb-0 fs-5 text-truncate">
                                    <?= esc($student['latitude'] ?? '-') ?>,
                                    <?= esc($student['longitude'] ?? '-') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Orang Tua/Wali -->
                    <div class="tab-pane fade" id="family" role="tabpanel">
                        <div class="row g-4">
                            <!-- Ayah -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-male me-2"></i>Data
                                    Ayah</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Nama Lengkap</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_name'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">NIK Ayah</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_nik'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Tahun Lahir</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_birth_year'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Pendidikan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_education'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Penghasilan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_income'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-12">
                                        <label class="text-muted small d-block">Pekerjaan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['father_job'] ?? '-') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ibu -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-female me-2"></i>Data
                                    Ibu</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Nama Lengkap</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_name'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">NIK Ibu</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_nik'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Tahun Lahir</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_birth_year'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Pendidikan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_education'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small d-block">Penghasilan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_income'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-12">
                                        <label class="text-muted small d-block">Pekerjaan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['mother_job'] ?? '-') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Wali -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-shield me-2"></i>Data Wali (Opsional)</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Nama Lengkap</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['guardian_name'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Pendidikan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['guardian_education'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Pekerjaan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['guardian_job'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block">Penghasilan</label>
                                        <p class="mb-0 fw-bold">
                                            <?= esc($student['guardian_income'] ?? '-') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

  /* Responsive */
  @media (max-width: 768px) {
    .nav-tabs .nav-link {
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
    }
  }
</style>

<?= $this->endSection() ?>