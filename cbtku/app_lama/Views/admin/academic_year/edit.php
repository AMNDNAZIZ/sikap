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
  
  .form-control {
    border: 2px solid #e9ecef !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
    font-size: 0.95rem !important;
    transition: all 0.2s !important;
  }
  
  .form-control:focus {
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
  
  .form-check {
    padding: 1rem !important;
    background: #f8f9fa !important;
    border-radius: 8px !important;
    border: 2px solid #e9ecef !important;
    transition: all 0.2s !important;
  }
  
  .form-check:hover {
    border-color: #667eea !important;
    background: #f0f2ff !important;
  }
  
  .form-check-input {
    width: 1.25rem !important;
    height: 1.25rem !important;
    margin-top: 0.125rem !important;
    cursor: pointer !important;
  }
  
  .form-check-input:checked {
    background-color: #667eea !important;
    border-color: #667eea !important;
  }
  
  .form-check-label {
    font-weight: 600 !important;
    color: #495057 !important;
    cursor: pointer !important;
    margin-left: 0.5rem !important;
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
  
  .date-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
    gap: 1rem !important;
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
      Edit Tahun Ajaran
    </h4>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <form action="<?= base_url('admin/academic-year/update/'.$year['id']) ?>" method="post" id="academicYearForm">
      <?= csrf_field() ?>

      <!-- Info Box -->
      <div class="info-box">
        <div class="info-box-title">
          <i class="fas fa-info-circle"></i>
          Informasi
        </div>
        <p class="info-box-text">
          Perubahan pada tahun ajaran akan mempengaruhi data yang terkait dengan periode akademik ini. 
          Pastikan data yang diubah sudah benar sebelum menyimpan.
        </p>
      </div>

      <!-- Section 1: Informasi Tahun Ajaran -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-calendar-alt"></i>
          Informasi Tahun Ajaran
        </div>
        
        <div class="mb-3">
          <label for="year" class="form-label">
            Tahun Ajaran<span class="required-mark">*</span>
          </label>
          <input type="text" 
                 name="year" 
                 id="year" 
                 class="form-control" 
                 value="<?= esc($year['year']) ?>"
                 placeholder="Contoh: 2024/2025" 
                 pattern="\d{4}/\d{4}"
                 required>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Format: YYYY/YYYY (contoh: 2024/2025)
          </div>
        </div>
      </div>

      <!-- Section 2: Periode Waktu -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-clock"></i>
          Periode Waktu
        </div>
        
        <div class="date-grid">
          <div>
            <label for="start_date" class="form-label">
              Tanggal Mulai<span class="required-mark">*</span>
            </label>
            <input type="date" 
                   name="start_date" 
                   id="start_date" 
                   class="form-control" 
                   value="<?= esc($year['start_date']) ?>"
                   required>
          </div>
          <div>
            <label for="end_date" class="form-label">
              Tanggal Berakhir<span class="required-mark">*</span>
            </label>
            <input type="date" 
                   name="end_date" 
                   id="end_date" 
                   class="form-control" 
                   value="<?= esc($year['end_date']) ?>"
                   required>
          </div>
        </div>
        <div class="form-text mt-2">
          <i class="fas fa-info-circle"></i>
          Pastikan tanggal berakhir lebih besar dari tanggal mulai
        </div>
      </div>

      <!-- Section 3: Status Aktif -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-toggle-on"></i>
          Status Aktif
        </div>
        
        <div class="form-check">
          <input type="checkbox" 
                 class="form-check-input" 
                 name="is_active" 
                 id="is_active" 
                 value="1"
                 <?= $year['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_active">
            Jadikan Tahun Ajaran Aktif
          </label>
          <div class="form-text mt-2">
            <i class="fas fa-exclamation-triangle"></i>
            Jika dicentang, tahun ajaran lain akan otomatis dinonaktifkan
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="btn-action-group">
        <a href="<?= base_url('admin/academic-year') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i>
          Kembali
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save"></i>
          Update Tahun Ajaran
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Form validation
document.getElementById('academicYearForm').addEventListener('submit', function(e) {
  const startDate = document.getElementById('start_date').value;
  const endDate = document.getElementById('end_date').value;
  
  if (startDate && endDate && startDate >= endDate) {
    e.preventDefault();
    alert('Tanggal berakhir harus lebih besar dari tanggal mulai!');
    return false;
  }
  
  const year = document.getElementById('year').value;
  const yearPattern = /^\d{4}\/\d{4}$/;
  if (!yearPattern.test(year)) {
    e.preventDefault();
    alert('Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025)');
    return false;
  }
});

// Auto-format year input
document.getElementById('year').addEventListener('input', function(e) {
  let value = e.target.value.replace(/[^\d]/g, '');
  if (value.length > 4) {
    value = value.substring(0, 4) + '/' + value.substring(4, 8);
  }
  e.target.value = value;
});
</script>

<?= $this->endSection() ?>
