<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-2">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
    <div>
      <h4 class="mb-0 fw-bold"><?= esc($title) ?></h4>
      <small class="text-muted">Kelola data master bank soal CBT</small>
    </div>
    <a href="<?= site_url('admin/cbt/banksoal') ?>" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?= form_open('admin/cbt/banksoal/create') ?>

        <div class="mb-3">
          <label for="title" class="form-label fw-bold">Judul Bank Soal</label>
          <input type="text" name="title" id="title" class="form-control"
                 placeholder="Contoh: Ujian Tengah Semester 2025" required>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="subject_id" class="form-label fw-bold">Mata Pelajaran</label>
            <select name="subject_id" id="subject_id" class="form-select" required>
              <option value="">-- Pilih Mapel --</option>
              <?php foreach (getSubjects() as $mapel): ?>
                <option value="<?= $mapel['id'] ?>"><?= esc($mapel['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label for="teacher_id" class="form-label fw-bold">Guru Pengampu</label>
            <select name="teacher_id" id="teacher_id" class="form-select" required>
              <option value="">-- Pilih Guru --</option>
              <?php foreach (getTeachers() as $guru): ?>
                <option value="<?= $guru['id'] ?>"><?= esc($guru['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="question_type" class="form-label fw-bold">Jenis Soal</label>
            <select name="question_type" id="question_type" class="form-select">
              <option value="pilgan">Pilihan Ganda</option>
              <option value="esai">Esai</option>
              <option value="campuran">Campuran</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="description" class="form-label fw-bold">Keterangan</label>
            <input type="text" name="description" id="description" class="form-control"
                   placeholder="Contoh: Soal kelas 9 semester genap">
          </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn btn-primary w-100 w-sm-auto py-2 px-4 shadow-sm">
            <i class="bi bi-save me-1"></i> Simpan Bank Soal
          </button>
        </div>

      <?= form_close() ?>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
