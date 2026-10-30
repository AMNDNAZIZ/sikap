<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-2 py-md-4">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-12">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-3 p-md-4">
          <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-2" style="width: 50px; height: 50px; font-size: 1.5rem;">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <h5 class="fw-bold mb-1">Cetak Daftar Hadir Penilaian</h5>
            <p class="text-muted small mb-0">Pilih ujian dan kelas untuk mencetak daftar hadir siswa</p>
          </div>

          <form id="attendanceForm">
            <!-- Pilihan Nama Ujian -->
            <div class="mb-3">
              <label for="exam_id" class="form-label fw-semibold small">Pilih Nama Ujian <span class="text-danger">*</span></label>
              <select id="exam_id" name="exam_id" class="form-select rounded-3 py-2" required>
                <option value="">-- Pilih Ujian --</option>
                <?php foreach ($exams as $e): ?>
                  <option value="<?= esc($e['id']) ?>"><?= esc($e['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Pilihan Kelas -->
            <div class="mb-4">
              <label for="class_id" class="form-label fw-semibold small">Pilih Kelas <span class="text-danger">*</span></label>
              <select id="class_id" name="class_id" class="form-select rounded-3 py-2" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-center">
              <button type="button" id="printPdfBtn" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
              </button>
              <button type="button" id="printHalBtn" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-printer me-1"></i> Pratinjau Cetak
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  document.getElementById('printPdfBtn').addEventListener('click', function (e) {
    e.preventDefault();
    const examId = document.getElementById('exam_id').value;
    const classId = document.getElementById('class_id').value;

    if (!examId) {
      alert('Silakan pilih nama ujian terlebih dahulu.');
      return;
    }
    if (!classId) {
      alert('Silakan pilih kelas terlebih dahulu.');
      return;
    }

    const url = "<?= site_url('admin/cbt/attendance/printPdf/') ?>"
      + encodeURIComponent(examId) + '/' + encodeURIComponent(classId);
    window.open(url, '_blank');
  });

  document.getElementById('printHalBtn').addEventListener('click', function (e) {
    e.preventDefault();
    const examId = document.getElementById('exam_id').value;
    const classId = document.getElementById('class_id').value;

    if (!examId) {
      alert('Silakan pilih nama ujian terlebih dahulu.');
      return;
    }
    if (!classId) {
      alert('Silakan pilih kelas terlebih dahulu.');
      return;
    }

    window.location.href = "<?= site_url('admin/cbt/attendance/printByClass/') ?>"
      + encodeURIComponent(examId) + '/' + encodeURIComponent(classId);
  });
</script>
<?= $this->endSection() ?>