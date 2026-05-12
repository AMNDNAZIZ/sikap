<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .container {
    max-width: 700px;
    margin-top: 80px;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  h4 {
    font-weight: bold;
    text-align: center;
    margin-bottom: 25px;
  }
</style>

<div class="col-md-4">
  <h4>🪪 Pratinjau Kartu Peserta Ujian</h4>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>

  <form id="kartuForm">
    <!-- PILIH NAMA UJIAN -->
    <div class="mb-3">
      <label for="examId" class="form-label">Pilih Nama Ujian</label>
      <select name="examId" id="examId" class="form-select" required>
        <option value="">-- Pilih Ujian --</option>
        <?php foreach ($exams as $exam): ?>
          <option value="<?= esc($exam['id']) ?>"><?= esc($exam['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- PILIH KELAS -->
    <div class="mb-3">
      <label for="classId" class="form-label">Pilih Kelas</label>
      <select name="classId" id="classId" class="form-select" required>
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= esc($class['id']) ?>"><?= esc($class['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="text-center mt-4">
      <button type="button" id="previewBtn" class="btn btn-primary">
        <i class="bi bi-eye"></i> Pratinjau Kartu Peserta
      </button>
    </div>
  </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.getElementById('previewBtn').addEventListener('click', function(e) {
    e.preventDefault();

    const examId  = document.getElementById('examId').value;
    const classId = document.getElementById('classId').value;

    if (!examId) {
      alert('Silakan pilih nama ujian terlebih dahulu.');
      return;
    }
    if (!classId) {
      alert('Silakan pilih kelas terlebih dahulu.');
      return;
    }

    // Arahkan langsung ke halaman pratinjau (tanpa membuka tab baru)
    const url = '<?= site_url('admin/cbt/kartu-peserta/cetakMassal/') ?>' + examId + '/' + classId;
    window.location.href = url;
  });
</script>
<?= $this->endSection() ?>
