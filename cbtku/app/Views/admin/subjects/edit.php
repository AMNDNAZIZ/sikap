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
        <i class="fas fa-edit"></i>
      </div>
      Edit Mata Pelajaran
    </h4>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <form method="post" action="<?= base_url('admin/subjects/update/'.$subject['id']) ?>" id="subjectForm">
      <?= csrf_field() ?>

      <!-- Info Box -->
      <div class="info-box">
        <div class="info-box-title">
          <i class="fas fa-info-circle"></i>
          Informasi
        </div>
        <p class="info-box-text">
          Perubahan data mata pelajaran akan mempengaruhi bank soal dan jadwal ujian yang terkait. 
          Pastikan data yang diubah sudah benar sebelum menyimpan.
        </p>
      </div>

      <!-- Section: Data Mata Pelajaran -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-book-open"></i>
          Data Mata Pelajaran
        </div>
        
        <div class="mb-3">
          <label for="code" class="form-label">
            Kode<span class="required-mark">*</span>
          </label>
          <input type="text" 
                 name="code" 
                 id="code" 
                 class="form-control" 
                 value="<?= esc($subject['code']) ?>"
                 placeholder="Contoh: MTK, IPA, IPS, BIN"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Kode singkat untuk identifikasi mata pelajaran
          </div>
        </div>

        <div class="mb-3">
          <label for="name" class="form-label">
            Nama Mata Pelajaran<span class="required-mark">*</span>
          </label>
          <input type="text" 
                 name="name" 
                 id="name" 
                 class="form-control" 
                 value="<?= esc($subject['name']) ?>"
                 placeholder="Contoh: Matematika, Ilmu Pengetahuan Alam"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Nama lengkap mata pelajaran
          </div>
        </div>

        <div class="mb-3">
          <label for="subject_type" class="form-label">
            Jenis Mata Pelajaran<span class="required-mark">*</span>
          </label>
          <select name="subject_type" id="subject_type" class="form-select" required>
            <option value="umum" <?= ($subject['subject_type'] ?? 'umum') === 'umum' ? 'selected' : '' ?>>Umum</option>
            <option value="agama" <?= ($subject['subject_type'] ?? '') === 'agama' ? 'selected' : '' ?>>Agama</option>
          </select>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Pilih "Agama" untuk mata pelajaran agama (PAI, PAK, dll)
          </div>
        </div>

        <div class="mb-3" id="religionField" style="display: <?= ($subject['subject_type'] ?? '') === 'agama' ? 'block' : 'none' ?>;">
          <label for="religion" class="form-label">
            Agama<span class="required-mark">*</span>
          </label>
          <select name="religion" id="religion" class="form-select">
            <option value="">-- Pilih Agama --</option>
            <option value="Islam" <?= ($subject['religion'] ?? '') === 'Islam' ? 'selected' : '' ?>>Islam</option>
            <option value="Kristen" <?= ($subject['religion'] ?? '') === 'Kristen' ? 'selected' : '' ?>>Kristen</option>
            <option value="Katolik" <?= ($subject['religion'] ?? '') === 'Katolik' ? 'selected' : '' ?>>Katolik</option>
            <option value="Hindu" <?= ($subject['religion'] ?? '') === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
            <option value="Budha" <?= ($subject['religion'] ?? '') === 'Budha' ? 'selected' : '' ?>>Budha</option>
            <option value="Konghucu" <?= ($subject['religion'] ?? '') === 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
          </select>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Pilih agama untuk mata pelajaran agama
          </div>
        </div>

        <div class="mb-3">
          <label for="is_active" class="form-label">Status</label>
          <select name="is_active" id="is_active" class="form-select">
            <option value="1" <?= $subject['is_active'] ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= !$subject['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
          </select>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Mata pelajaran nonaktif tidak akan muncul di pilihan
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="btn-action-group">
        <a href="<?= base_url('admin/subjects') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i>
          Kembali
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i>
          Update Mata Pelajaran
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Toggle religion field based on subject_type
document.getElementById('subject_type').addEventListener('change', function() {
  const religionField = document.getElementById('religionField');
  const religionSelect = document.getElementById('religion');
  
  if (this.value === 'agama') {
    religionField.style.display = 'block';
    religionSelect.required = true;
  } else {
    religionField.style.display = 'none';
    religionSelect.required = false;
    religionSelect.value = '';
  }
});

// Form validation
document.getElementById('subjectForm').addEventListener('submit', function(e) {
  const code = document.getElementById('code').value.trim();
  const name = document.getElementById('name').value.trim();
  const subjectType = document.getElementById('subject_type').value;
  const religion = document.getElementById('religion').value;
  
  if (!code) {
    e.preventDefault();
    alert('Kode mata pelajaran harus diisi!');
    return false;
  }
  
  if (!name) {
    e.preventDefault();
    alert('Nama mata pelajaran harus diisi!');
    return false;
  }
  
  if (subjectType === 'agama' && !religion) {
    e.preventDefault();
    alert('Agama harus dipilih untuk mata pelajaran agama!');
    return false;
  }
});
</script>

<?= $this->endSection() ?>
