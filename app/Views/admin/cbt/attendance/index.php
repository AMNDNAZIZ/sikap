<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .container {
    max-width: 700px;
    margin-top: 80px;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  h4 {
    font-weight: bold;
    text-align: center;
    margin-bottom: 25px;
  }
</style>

<div class="col-md-4">
  <h4 class="text-center fw-bold mb-3">📃 Cetak Daftar Hadir Penilaian</h4>

  <form id="attendanceForm" class="mx-auto">
    <!-- Pilihan Nama Ujian -->
    <div class="mb-3">
      <label for="exam_id" class="form-label">Pilih Nama Ujian</label>
      <select id="exam_id" name="exam_id" class="form-select" required>
        <option value="">-- Pilih Ujian --</option>
        <?php foreach ($exams as $e): ?>
          <option value="<?= esc($e['id']) ?>"><?= esc($e['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Pilihan Kelas -->
    <div class="mb-3">
      <label for="class_id" class="form-label">Pilih Kelas</label>
      <select id="class_id" name="class_id" class="form-select" required>
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="text-center mt-3">
      <button type="button" id="printPdfBtn" class="btn btn-danger" target="_blank">
        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
      </button>
      <button type="button" id="printHalBtn" class="btn btn-primary">
        <i class="bi bi-printer"></i> Pratinjau Cetak
      </button>
    </div>
  </form>
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