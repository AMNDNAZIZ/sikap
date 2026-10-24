<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .card-kartu {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    background: #ffffff;
  }
  @media (max-width: 576px) {
    .card-kartu {
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
  }
</style>

<div class="container-fluid py-3 py-md-4">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="card card-kartu">
        <div class="card-body p-4">
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 58px; height: 58px; font-size: 26px;">
              <i class="bi bi-person-badge"></i>
            </div>
            <h4 class="fw-bold mb-1">Pratinjau Kartu Peserta</h4>
            <p class="text-muted small mb-0">Pilih ujian dan kelas untuk mencetak kartu peserta</p>
          </div>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 small"><?= session()->getFlashdata('error') ?></div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success py-2 small"><?= session()->getFlashdata('success') ?></div>
          <?php endif; ?>

          <form id="kartuForm">
            <!-- PILIH NAMA UJIAN -->
            <div class="mb-3">
              <label for="examId" class="form-label fw-semibold small">Pilih Nama Ujian</label>
              <select name="examId" id="examId" class="form-select py-2" required>
                <option value="">-- Pilih Ujian --</option>
                <?php foreach ($exams as $exam): ?>
                  <option value="<?= esc($exam['id']) ?>"><?= esc($exam['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- PILIH KELAS -->
            <div class="mb-4">
              <label for="classId" class="form-label fw-semibold small">Pilih Kelas</label>
              <select name="classId" id="classId" class="form-select py-2" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= esc($class['id']) ?>"><?= esc($class['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="button" id="previewBtn" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
              <i class="bi bi-eye me-1"></i> Pratinjau Kartu Peserta
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
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
