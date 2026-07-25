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
  
  .btn-success {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
  }
  
  .btn-success:hover {
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
      Tambah User
    </h4>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <form action="<?= base_url('admin/users/store') ?>" method="post" id="userForm">
      <?= csrf_field() ?>

      <!-- Info Box -->
      <div class="info-box">
        <div class="info-box-title">
          <i class="fas fa-info-circle"></i>
          Informasi
        </div>
        <p class="info-box-text">
          User adalah akun pengguna sistem dengan hak akses tertentu. 
          Role menentukan fitur dan menu yang dapat diakses oleh user.
        </p>
      </div>

      <!-- Section 1: Akun Login -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-key"></i>
          Akun Login
        </div>
        
        <div class="mb-3">
          <label for="username" class="form-label">
            Username<span class="required-mark">*</span>
          </label>
          <input type="text" 
                 name="username" 
                 id="username" 
                 class="form-control" 
                 placeholder="Username untuk login"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Username harus unik dan digunakan untuk login
          </div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">
            Password<span class="required-mark">*</span>
          </label>
          <input type="password" 
                 name="password" 
                 id="password" 
                 class="form-control" 
                 placeholder="Minimal 6 karakter"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Password minimal 6 karakter untuk keamanan
          </div>
        </div>
      </div>

      <!-- Section 2: Data Pribadi -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-user"></i>
          Data Pribadi
        </div>
        
        <div class="mb-3">
          <label for="fullname" class="form-label">
            Nama Lengkap<span class="required-mark">*</span>
          </label>
          <input type="text" 
                 name="fullname" 
                 id="fullname" 
                 class="form-control" 
                 placeholder="Nama lengkap user"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Nama lengkap yang akan ditampilkan di sistem
          </div>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">
            Email<span class="required-mark">*</span>
          </label>
          <input type="email" 
                 name="email" 
                 id="email" 
                 class="form-control" 
                 placeholder="email@example.com"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Email untuk notifikasi dan pemulihan akun
          </div>
        </div>
      </div>

      <!-- Section 3: Hak Akses -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-shield-alt"></i>
          Hak Akses
        </div>
        
        <div class="mb-3">
          <label for="role_id" class="form-label">
            Role<span class="required-mark">*</span>
          </label>
          <select name="role_id" id="role_id" class="form-select" required>
            <option value="">-- Pilih Role --</option>
            <?php foreach ($roles as $id => $roleName): ?>
              <option value="<?= $id ?>"><?= esc($roleName) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Role menentukan hak akses dan fitur yang tersedia
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="btn-action-group">
        <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
          <i class="fas fa-times"></i>
          Batal
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save"></i>
          Simpan User
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const fullname = document.getElementById('fullname').value.trim();
  const email = document.getElementById('email').value.trim();
  
  if (!username) {
    e.preventDefault();
    alert('Username harus diisi!');
    return false;
  }
  
  if (password.length < 6) {
    e.preventDefault();
    alert('Password minimal 6 karakter!');
    return false;
  }
  
  if (!fullname) {
    e.preventDefault();
    alert('Nama lengkap harus diisi!');
    return false;
  }
  
  if (!email) {
    e.preventDefault();
    alert('Email harus diisi!');
    return false;
  }
});
</script>

<?= $this->endSection() ?>
