<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">📥 Import Leger Nilai Erapor</h4>
    <small class="text-muted">Langkah 0 dari 2: Upload File Leger</small>
  </div>
  <a href="<?= site_url('admin/erapor') ?>" class="btn btn-sm btn-secondary">⬅ Kembali</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-primary text-white py-2">
    <h6 class="mb-0 text-white"><i class="bi bi-file-earmark-excel"></i> Upload File Leger Nilai Rapor</h6>
  </div>
  <div class="card-body">
    <div class="alert alert-info">
      <div class="fw-bold mb-1">💡 Petunjuk Upload:</div>
      <ul class="mb-0 ps-3">
        <li>File harus berformat Excel (<code>.xlsx</code> atau <code>.xls</code>), ukuran maksimal 10 MB.</li>
        <li>Sistem akan otomatis mendeteksi Nama Kelas, Tahun Ajaran, Semester, daftar Mapel, dan Rekap Absensi dari file leger.</li>
        <li>Pada langkah berikutnya, Anda dapat melakukan <strong>mapping manual</strong> untuk mencocokkan kolom mata pelajaran.</li>
      </ul>
    </div>

    <form action="<?= site_url('admin/erapor/import-parse') ?>" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Tahun Ajaran <span class="text-danger">*</span></label>
          <select name="year_id" class="form-select" required>
            <?php foreach ($years as $y): ?>
              <option value="<?= $y['id'] ?>" <?= $y['id'] == $yearId ? 'selected' : '' ?>>
                <?= esc($y['year']) ?> <?= $y['is_active'] ? '(Aktif)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
          <div class="pt-2">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="semester" id="sem_ganjil" value="ganjil" <?= $semester == 'ganjil' || $semester == '1' ? 'checked' : '' ?> required>
              <label class="form-check-input-label" for="sem_ganjil">Ganjil (Semester 1)</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="semester" id="sem_genap" value="genap" <?= $semester == 'genap' || $semester == '2' ? 'checked' : '' ?> required>
              <label class="form-check-input-label" for="sem_genap">Genap (Semester 2)</label>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Pilih File Excel Leger <span class="text-danger">*</span></label>
        <input type="file" name="file_leger" class="form-control form-control-lg" accept=".xlsx,.xls" required>
        <div class="form-text">Contoh file: <code>Leger_20241_Kelas 1B.xlsx</code> atau <code>f_leger_Kelas 1A.xlsx</code></div>
      </div>

      <div class="mb-4 p-3 bg-light rounded border">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="import_absence" value="1" id="chkImportAbsence" checked>
          <label class="form-check-label fw-bold" for="chkImportAbsence">
            Import Rekap Ketidakhadiran (Sakit / Izin / Alpa)
          </label>
        </div>
        <small class="text-muted d-block mt-1">
          Jika dicentang, data rekap absensi semester dari file leger akan disimpan ke rekap absensi siswa.
        </small>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= site_url('admin/erapor') ?>" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
          🔍 Proses File &amp; Lanjut ke Mapping ➡
        </button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
