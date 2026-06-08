<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
  <h4 class="mb-3">➕ Tambah Jadwal Ujian</h4>

  <form action="<?= site_url('admin/exam-schedule/store') ?>" method="post" class="card shadow-sm p-4">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label for="subject_id" class="form-label fw-semibold">Mata Pelajaran</label>
      <select name="subject_id" id="subject_id" class="form-select" required>
        <option value="">-- Pilih Mata Pelajaran --</option>
        <?php foreach ($subjects as $sub): ?>
          <option value="<?= $sub['id'] ?>"><?= esc($sub['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="class_id" class="form-label fw-semibold">Kelas</label>
      <select name="class_id" id="class_id" class="form-select">
        <option value="">-- Semua Kelas --</option>
        <?php foreach ($classes as $cls): ?>
          <option value="<?= $cls['id'] ?>"><?= esc($cls['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Kosongkan jika jadwal berlaku untuk semua kelas.</div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label for="exam_date" class="form-label fw-semibold">Tanggal Ujian</label>
        <input type="date" name="exam_date" id="exam_date" class="form-control" required>
      </div>
      <div class="col-md-4 mb-3">
        <label for="start_time" class="form-label fw-semibold">Jam Mulai</label>
        <input type="time" name="start_time" id="start_time" class="form-control" required>
      </div>
      <div class="col-md-4 mb-3">
        <label for="end_time" class="form-label fw-semibold">Jam Selesai</label>
        <input type="time" name="end_time" id="end_time" class="form-control" required>
      </div>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label fw-semibold">Keterangan</label>
      <textarea name="description" id="description" rows="2" class="form-control" placeholder="Contoh: Sesi pagi, ruang 1"></textarea>
    </div>

    <div class="mt-3">
      <a href="<?= site_url('admin/exam-schedule') ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> Simpan Jadwal
      </button>
    </div>
  </form>
</div>

<?= $this->endSection() ?>
