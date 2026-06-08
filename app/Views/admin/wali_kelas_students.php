<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .wali-kelas-header {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
  }

  .wali-kelas-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }

  .wali-kelas-header-content {
    position: relative;
    z-index: 1;
  }

  .teacher-info {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .teacher-photo {
    width: 100px;
    height: 100px;
    border-radius: 16px;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  .search-box {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
  }

  .search-box:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .table-hover tbody tr:hover {
    background-color: #f8f9fa;
  }

  .btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  @media (max-width: 767.98px) {
    .wali-kelas-header {
      padding: 1.5rem;
      border-radius: 16px;
    }

    .teacher-info {
      padding: 1rem;
    }

    .table-responsive {
      font-size: 0.875rem;
    }

    .table th, .table td {
      padding: 0.5rem;
    }
  }
</style>

<div class="container-fluid p-0">
  <!-- Header Section -->
  <div class="wali-kelas-header">
    <div class="wali-kelas-header-content">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="mb-2 fw-bold">
            <i class="bi bi-people-fill me-2"></i>
            Data Siswa <?= esc($waliClass['name']) ?>
          </h2>
          <p class="mb-0 opacity-90">Kelola dan lihat informasi siswa yang ada di kelas Anda</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <a href="<?= site_url('dashboard') ?>" class="btn btn-light btn-sm rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Teacher Info Card -->
  <div class="teacher-info mb-4">
    <div class="row align-items-center">
      <div class="col-auto">
        <?php if (!empty($teacher['photo']) && file_exists(FCPATH . 'uploads/teachers/' . $teacher['photo'])): ?>
          <img src="<?= base_url('uploads/teachers/' . $teacher['photo']) ?>" alt="<?= esc($teacher['name']) ?>" class="teacher-photo">
        <?php else: ?>
          <div class="teacher-photo d-flex align-items-center justify-content-center bg-white bg-opacity-20">
            <i class="bi bi-person-fill" style="font-size: 2.5rem;"></i>
          </div>
        <?php endif; ?>
      </div>
      <div class="col">
        <h5 class="mb-1 fw-bold">
          <i class="bi bi-person-badge me-2"></i>
          <?= esc($teacher['name']) ?>
        </h5>
        <p class="mb-1 opacity-90">
          <i class="bi bi-card-text me-1"></i>
          NIP: <?= $teacher['nip'] ?? '-' ?>
        </p>
        <?php if (!empty($teacher['phone'])): ?>
          <p class="mb-0 opacity-90">
            <i class="bi bi-telephone me-1"></i>
            <?= $teacher['phone'] ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Statistics Section -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 text-center">
          <div class="h3 fw-bold text-primary mb-2"><?= $stats['total'] ?></div>
          <div class="text-muted">Total Siswa</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 text-center">
          <div class="h3 fw-bold text-info mb-2"><?= $stats['male'] ?></div>
          <div class="text-muted">Laki-laki</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 text-center">
          <div class="h3 fw-bold text-danger mb-2"><?= $stats['female'] ?></div>
          <div class="text-muted">Perempuan</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 text-center">
          <a href="<?= site_url('admin/grades?class_id=' . $waliClass['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-4 w-100">
            <i class="bi bi-graph-up me-1"></i> Lihat Nilai
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Search and Filter Section -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
          <label class="form-label fw-bold mb-2">Cari Siswa</label>
          <input type="text" id="searchInput" class="form-control search-box" placeholder="Nama siswa...">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label fw-bold mb-2">Filter Gender</label>
          <select id="genderFilter" class="form-select search-box">
            <option value="">Semua</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Students Table -->
  <div class="card border-0 shadow-sm">
    <?php if (empty($students)): ?>
      <div class="card-body p-5 text-center">
        <i class="bi bi-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h6 class="fw-semibold mb-2">Tidak ada siswa</h6>
        <p class="text-muted mb-0">Belum ada siswa yang terdaftar di kelas ini</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 5%;">#</th>
              <th style="width: 25%;">Nama Siswa</th>
              <th style="width: 15%;">NISN</th>
              <th style="width: 12%;">Gender</th>
              <th style="width: 18%;">Tanggal Lahir</th>
              <th style="width: 15%;">Email</th>
              <th style="width: 10%; text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($students as $student): ?>
              <tr class="student-item" data-name="<?= strtolower($student['student_name']) ?>" data-gender="<?= $student['gender'] ?>">
                <td class="fw-bold text-muted"><?= $no++ ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <?php if (!empty($student['photo']) && file_exists(FCPATH . 'uploads/students/' . $student['photo'])): ?>
                      <img src="<?= base_url('uploads/students/' . $student['photo']) ?>" alt="<?= esc($student['student_name']) ?>" class="student-avatar me-2" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                    <?php else: ?>
                      <div class="student-avatar-placeholder me-2" style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem;">
                        <i class="bi bi-person-fill"></i>
                      </div>
                    <?php endif; ?>
                    <div>
                      <div class="fw-bold"><?= esc($student['student_name']) ?></div>
                      <small class="text-muted">NISN: <?= $student['nisn'] ?? '-' ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark"><?= $student['nisn'] ?? '-' ?></span>
                </td>
                <td>
                  <?php if ($student['gender'] == 'L'): ?>
                    <span class="badge bg-info">Laki-laki</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Perempuan</span>
                  <?php endif; ?>
                </td>
                <td>
                  <small><?= date('d/m/Y', strtotime($student['birth_date'])) ?></small>
                </td>
                <td>
                  <?php if (!empty($student['email'])): ?>
                    <a href="mailto:<?= $student['email'] ?>" class="text-decoration-none small">
                      <?= $student['email'] ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: center;">
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= site_url('admin/wali-kelas-students/detail/' . $student['student_id']) ?>" 
                       class="btn btn-outline-primary" title="Detail">
                      <i class="bi bi-eye"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const genderFilter = document.getElementById('genderFilter');
  const studentItems = document.querySelectorAll('.student-item');

  function filterStudents() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedGender = genderFilter.value;

    studentItems.forEach(item => {
      const name = item.dataset.name;
      const gender = item.dataset.gender;

      const matchesSearch = name.includes(searchTerm);
      const matchesGender = !selectedGender || gender === selectedGender;

      if (matchesSearch && matchesGender) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  }

  searchInput.addEventListener('keyup', filterStudents);
  genderFilter.addEventListener('change', filterStudents);
});
</script>

<?= $this->endSection() ?>
