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
  
  .time-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 1rem !important;
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
        <i class="fas fa-calendar-plus"></i>
      </div>
      Tambah Jadwal Ujian
    </h4>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <form action="<?= site_url('admin/exam-schedule/store') ?>" method="post" id="scheduleForm">
      <?= csrf_field() ?>

      <!-- Section 1: Informasi Ujian -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-info-circle"></i>
          Informasi Ujian
        </div>
        
        <div class="mb-3">
          <label for="subject_id" class="form-label">
            Mata Pelajaran<span class="required-mark">*</span>
          </label>
          <select name="subject_id" id="subject_id" class="form-select" required>
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= $sub['id'] ?>"><?= esc($sub['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="class_id" class="form-label">Kelas</label>
          <select name="class_id" id="class_id" class="form-select">
            <option value="">-- Semua Kelas --</option>
            <?php foreach ($classes as $cls): ?>
              <option value="<?= $cls['id'] ?>"><?= esc($cls['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Kosongkan jika jadwal berlaku untuk semua kelas
          </div>
        </div>
      </div>

      <!-- Section 2: Waktu Pelaksanaan -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-clock"></i>
          Waktu Pelaksanaan
        </div>
        
        <div class="mb-3">
          <label for="exam_date" class="form-label">
            Tanggal Ujian<span class="required-mark">*</span>
          </label>
          <input type="date" name="exam_date" id="exam_date" class="form-control" required>
        </div>

        <div class="time-grid">
          <div>
            <label for="start_time" class="form-label">
              Jam Mulai<span class="required-mark">*</span>
            </label>
            <input type="time" name="start_time" id="start_time" class="form-control" required>
          </div>
          <div>
            <label for="end_time" class="form-label">
              Jam Selesai<span class="required-mark">*</span>
            </label>
            <input type="time" name="end_time" id="end_time" class="form-control" required>
          </div>
        </div>
      </div>

      <!-- Section 3: Keterangan Tambahan -->
      <div class="form-section">
        <div class="form-section-title">
          <i class="fas fa-file-alt"></i>
          Keterangan Tambahan
        </div>
        
        <div class="mb-3">
          <label for="description" class="form-label">Keterangan</label>
          <textarea name="description" id="description" rows="3" class="form-control" placeholder="Contoh: Sesi pagi, ruang 1, bawa kalkulator"></textarea>
          <div class="form-text">
            <i class="fas fa-info-circle"></i>
            Informasi tambahan yang perlu diketahui peserta ujian
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="btn-action-group">
        <a href="<?= site_url('admin/exam-schedule') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i>
          Kembali
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i>
          Simpan Jadwal
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Form validation
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
  const startTime = document.getElementById('start_time').value;
  const endTime = document.getElementById('end_time').value;
  
  if (startTime && endTime && startTime >= endTime) {
    e.preventDefault();
    alert('Jam selesai harus lebih besar dari jam mulai!');
    return false;
  }
});

// Set minimum date to today
document.getElementById('exam_date').min = new Date().toISOString().split('T')[0];
</script>

<?= $this->endSection() ?>
