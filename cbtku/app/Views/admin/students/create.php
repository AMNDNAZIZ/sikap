<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 15px !important;
    margin-bottom: 2rem !important;
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2) !important;
  }
  
  .page-header h4 {
    margin: 0 !important;
    font-weight: 600 !important;
    font-size: 1.75rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
  }
  
  .page-header .icon-wrapper {
    width: 48px !important;
    height: 48px !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.5rem !important;
  }
  
  .form-card {
    background: white !important;
    border-radius: 15px !important;
    padding: 2rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    border: 1px solid #e9ecef !important;
  }
  
  .form-section {
    margin-bottom: 2rem !important;
    padding-bottom: 2rem !important;
    border-bottom: 2px solid #f1f3f5 !important;
  }
  
  .form-section:last-of-type {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
  
  .form-section-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 1.25rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }
  
  .form-section-title i {
    color: #667eea !important;
    font-size: 1.2rem !important;
  }
  
  .form-label {
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
    font-size: 0.95rem !important;
  }
  
  .form-control, .form-select {
    border: 2px solid #e9ecef !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
    font-size: 0.95rem !important;
    transition: all 0.2s !important;
  }
  
  .form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
  }
  
  .form-text {
    color: #6c757d !important;
    font-size: 0.875rem !important;
    margin-top: 0.375rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
  }
  
  .form-text i {
    color: #667eea !important;
  }
  
  .required-mark {
    color: #dc3545 !important;
    margin-left: 0.25rem !important;
  }
  
  .btn-action-group {
    display: flex !important;
    gap: 0.75rem !important;
    padding-top: 1.5rem !important;
    border-top: 2px solid #f1f3f5 !important;
    margin-top: 2rem !important;
  }
  
  .btn {
    padding: 0.625rem 1.5rem !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.2s !important;
    font-size: 0.95rem !important;
  }
  
  .btn-secondary {
    background: #6c757d !important;
    color: white !important;
  }
  
  .btn-secondary:hover {
    background: #5a6268 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3) !important;
  }
  
  .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
  }
  
  .btn-primary:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4) !important;
  }
  
  .info-box {
    background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%) !important;
    border-left: 4px solid #667eea !important;
    padding: 1rem !important;
    border-radius: 8px !important;
    margin-bottom: 1.5rem !important;
  }
  
  .info-box-title {
    font-weight: 600 !important;
    color: #1e40af !important;
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }
  
  .info-box-text {
    color: #1e3a8a !important;
    font-size: 0.9rem !important;
    margin: 0 !important;
  }
  
  @media (max-width: 768px) {
    .page-header {
      padding: 1.5rem !important;
    }
    
    .page-header h4 {
      font-size: 1.5rem !important;
    }
    
    .form-card {
      padding: 1.5rem !important;
    }
    
    .btn-action-group {
      flex-direction: column !important;
    }
    
    .btn {
      width: 100% !important;
      justify-content: center !important;
    }
  }
</style>

<div class="container-fluid px-4 py-4">
  <!-- Page Header -->
  <div class="page-header">
    <h4>
      <div class="icon-wrapper">
        <i class="fas fa-user-plus"></i>
      </div>
      Tambah Siswa
    </h4>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <form action="<?= base_url('admin/students/store') ?>" method="post" id="studentForm">
      <?= csrf_field() ?>

      <!-- Info Box -->
      <div class="info-box">
        <div class="info-box-title">
          <i class="fas fa-info-circle"></i>
          Informasi
        </div>
        <p class="info-box-text">
          Username dan password akan digunakan siswa untuk login ke sistem CBT. 
          Password akan digenerate otomatis secara acak, namun dapat diubah sesuai kebutuhan.
        </p>
      </div>

      <!-- Section 1: Akun Login -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-key"></i>
          Akun Login
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="username" class="form-label">
              Username<span class="required-mark">*</span>
            </label>
            <input type="text" 
                   name="username" 
                   id="username" 
                   class="form-control" 
                   placeholder="Contoh: rina07, ahmad123"
                   required>
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Username harus unik dan digunakan untuk login
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="plain_password" class="form-label">Password</label>
            <?php
              $rand = '';
              for ($i=0; $i<5; $i++) { $rand .= random_int(1,9); }
              $defaultPassword = $rand . '*';
            ?>
            <input type="text" 
                   name="plain_password" 
                   id="plain_password" 
                   class="form-control" 
                   value="<?= esc($defaultPassword) ?>">
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Password digenerate otomatis, dapat diubah
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Data Pribadi -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-user"></i>
          Data Pribadi
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="nis" class="form-label">NIS</label>
            <input type="text" 
                   name="nis" 
                   id="nis" 
                   class="form-control" 
                   placeholder="Nomor Induk Siswa">
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Nomor Induk Siswa (opsional)
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">
              Nama Lengkap<span class="required-mark">*</span>
            </label>
            <input type="text" 
                   name="name" 
                   id="name" 
                   class="form-control" 
                   placeholder="Nama lengkap siswa"
                   required>
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Nama lengkap sesuai identitas resmi
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="gender" class="form-label">
              Jenis Kelamin<span class="required-mark">*</span>
            </label>
            <select name="gender" id="gender" class="form-select" required>
              <option value="">-- Pilih Jenis Kelamin --</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="religion" class="form-label">
              Agama<span class="required-mark">*</span>
            </label>
            <select name="religion" id="religion" class="form-select" required>
              <option value="">-- Pilih Agama --</option>
              <option value="Islam">Islam</option>
              <option value="Kristen">Kristen</option>
              <option value="Katolik">Katolik</option>
              <option value="Hindu">Hindu</option>
              <option value="Budha">Budha</option>
              <option value="Konghucu">Konghucu</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 3: Data Kelas -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-school"></i>
          Data Kelas
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="class_id" class="form-label">
              Kelas<span class="required-mark">*</span>
            </label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">-- Pilih Kelas --</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Kelas tempat siswa belajar
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="room" class="form-label">
              Ruang<span class="required-mark">*</span>
            </label>
            <input type="text" 
                   name="room" 
                   id="room" 
                   class="form-control" 
                   placeholder="Contoh: Ruang 1, Lab Komputer"
                   required>
            <div class="form-text">
              <i class="fas fa-info-circle"></i>
              Ruang ujian untuk siswa
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="btn-action-group">
        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary">
          <i class="fas fa-times"></i>
          Batal
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i>
          Simpan Siswa
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Form validation
document.getElementById('studentForm').addEventListener('submit', function(e) {
  const username = document.getElementById('username').value.trim();
  const name = document.getElementById('name').value.trim();
  
  if (!username) {
    e.preventDefault();
    alert('Username wajib diisi dan harus unik!');
    return false;
  }
  
  if (!name) {
    e.preventDefault();
    alert('Nama lengkap harus diisi!');
    return false;
  }
});
</script>

<?= $this->endSection() ?>
