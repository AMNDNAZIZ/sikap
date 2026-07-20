<?php
// Load helpers
helper(['url', 'form']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Detail Siswa - <?php echo htmlspecialchars($student['name']); ?></title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
  
  <style>
    body {
      font-size: 0.875rem;
    }
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
</head>
<body>
  <div class="container-fluid p-0">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm" style="height: 56px;">
      <div class="container-fluid">
        <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
          ☰
        </button>
        <button id="sidebarToggle" class="btn btn-outline-secondary d-none d-lg-inline me-2">
          ⇔
        </button>
        <a class="navbar-brand" href="<?= base_url('/dashboard') ?>">📘 SIKAP</a>
        <div class="ms-auto">
          <?php if (session()->has('user')): ?>
            <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-danger">Logout</a>
          <?php else: ?>
            <a href="<?= base_url('login') ?>" class="btn btn-sm btn-primary">Login SIKAP</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <!-- Layout utama -->
    <div class="layout-wrapper" style="display: flex; height: calc(100vh - 56px); overflow: hidden;">
      <!-- Sidebar desktop -->
      <nav id="sidebarDesktop" class="sidebar d-none d-lg-block" style="width: 60px; transition: width 0.2s ease-in-out; overflow-x: hidden; white-space: nowrap; min-height: 100%; background-color: #f8f9fa; border-right: 1px solid #dee2e6;">
        <?php $currentUser = session()->get('user'); ?>
        <?php if ($currentUser && isset($currentUser['role_id']) && $currentUser['role_id'] == 3): ?>
          <?= view('layouts/partials/sidebar_guru') ?>
        <?php endif; ?>
      </nav>

      <!-- Sidebar mobile offcanvas -->
      <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
          <?php if ($currentUser && isset($currentUser['role_id']) && $currentUser['role_id'] == 3): ?>
            <?= view('layouts/partials/sidebar_guru') ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Main Content -->
      <main id="mainContent" class="px-3 px-md-4 py-3" style="flex-grow: 1; transition: all 0.2s ease-in-out; overflow-y: auto; overflow-x: hidden; min-width: 0;">
        
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

        <form action="<?= base_url('admin/wali-kelas-students/update/' . $student['id']) ?>" method="post">
          <?= csrf_field() ?>
        
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-primary">
              <i class="fas fa-user me-2"></i>Data Siswa - <?php echo htmlspecialchars($student['name']); ?>
            </h5>
            <a href="<?= base_url('admin/wali-kelas-students') ?>" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-arrow-left me-1"></i> Kembali
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
                      <i class="fas fa-user fa-3x text-secondary"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-5">
                  <label class="form-label fw-bold">Foto Siswa</label>
                  <p class="text-muted">
                    <?php if (!empty($student['photo'])): ?>
                      <?php echo htmlspecialchars($student['photo']); ?>
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
                    <p><?php echo htmlspecialchars($student['name']); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">NISN</label>
                    <p><?php echo htmlspecialchars($student['nisn']); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">NIS</label>
                    <p><?php echo htmlspecialchars($student['nis']); ?></p>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold">NIK Siswa</label>
                    <p><?php echo htmlspecialchars($student['nik'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Jenis Kelamin</label>
                    <p><?php echo $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Agama</label>
                    <p><?php echo htmlspecialchars($student['religion'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Tempat Lahir</label>
                    <p><?php echo htmlspecialchars($student['birth_place'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Tanggal Lahir</label>
                    <p><?php echo htmlspecialchars($student['birth_date'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Kewarganegaraan</label>
                    <p><?php echo htmlspecialchars($student['nationality'] ?? 'WNI'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Anak ke</label>
                    <input type="number" name="child_order" class="form-control" value="<?= esc($student['child_order'] ?? '') ?>" min="1">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Jenis Pendaftaran</label>
                    <p><?php echo htmlspecialchars($student['registration_type'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Tanggal Masuk</label>
                    <p><?php echo htmlspecialchars($student['admission_date'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Diterima di Kelas</label>
                    <p><?php echo htmlspecialchars($student['admission_class'] ?? '-'); ?></p>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold">Kebutuhan Khusus</label>
                    <input type="text" name="special_needs" class="form-control" value="<?= esc($student['special_needs'] ?? '') ?>" placeholder="Contoh: Tidak ada">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold">Email</label>
                    <p><?php echo htmlspecialchars($student['email'] ?? '-'); ?></p>
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
                  <h6 class="fw-bold mb-3">DATA AYAH</h6>
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
                  <h6 class="fw-bold mb-3">DATA IBU</h6>
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
                  <h6 class="fw-bold mb-3">DATA WALI</h6>
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

            <div class="card-footer bg-white p-4 d-flex justify-content-between border-top shadow-sm">
              <a href="<?= base_url('admin/wali-kelas-students') ?>" class="btn btn-secondary px-4 py-2">
                <i class="fas fa-arrow-left me-1"></i> Kembali
              </a>
              <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                <i class="fas fa-save me-1"></i> Simpan Perubahan
              </button>
            </div>
          </div>
        </div>

        </form>

      </main>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Sidebar toggle
    document.addEventListener("DOMContentLoaded", function () {
      const sidebar = document.querySelector("#sidebarDesktop");
      const toggleBtn = document.getElementById("sidebarToggle");

      if (sidebar && toggleBtn) {
        if (localStorage.getItem("sidebar") === "expanded") {
          sidebar.classList.add("sidebar-expanded");
          sidebar.style.width = "220px";
        }

        toggleBtn.addEventListener("click", function () {
          sidebar.classList.toggle("sidebar-expanded");
          if (sidebar.classList.contains("sidebar-expanded")) {
            sidebar.style.width = "220px";
            localStorage.setItem("sidebar", "expanded");
          } else {
            sidebar.style.width = "60px";
            localStorage.setItem("sidebar", "mini");
          }
        });
      }
    });
    
    // Initialize Bootstrap tabs
    document.addEventListener('DOMContentLoaded', function() {
      const triggerTabList = document.querySelectorAll('#detailTab button');
      triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', event => {
          event.preventDefault();
          tabTrigger.show();
        });
      });
    });
  </script>
</body>
</html>
