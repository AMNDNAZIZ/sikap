<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">Edit Tahun Ajaran</h1>

  <form action="<?= base_url('admin/academic-year/update/' . $year['id']) ?>" method="post">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Tahun Ajaran</label>
      <input type="text" name="year" value="<?= $year['year'] ?>" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Mulai</label>
      <input type="date" name="start_date" value="<?= $year['start_date'] ?>" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Berakhir</label>
      <input type="date" name="end_date" value="<?= $year['end_date'] ?>" class="form-control" required>
    </div>
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Persentase Formatif (%)</label>
        <input type="number" name="formatif_weight" class="form-control" value="<?= $year['formatif_weight'] ?>" min="0"
          max="100">
      </div>
      <div class="col-md-6">
        <label class="form-label">Persentase Sumatif (%)</label>
        <input type="number" name="sumatif_weight" class="form-control" value="<?= $year['sumatif_weight'] ?>" min="0"
          max="100">
      </div>
      <small class="text-muted mt-1">Persentase ini digunakan untuk menghitung nilai Rapot, Total persentase harus 100%,
        jika tidak diisi maka akan dihitung otomatis, berlaku untuk satu tahun ajaran.</small>
    </div>
    <div class="mb-3">
      <label class="form-label">Jumlah Hari Sekolah per Minggu</label>
      <select name="school_days" class="form-select" required>
        <option value="5" <?= ($year['school_days'] ?? 5) == 5 ? 'selected' : '' ?>>5 Hari (Senin - Jumat)</option>
        <option value="6" <?= ($year['school_days'] ?? 5) == 6 ? 'selected' : '' ?>>6 Hari (Senin - Sabtu)</option>
      </select>
      <small class="text-muted">Pengaturan ini akan mempengaruhi perhitungan hari efektif, grid absensi, dan jadwal pelajaran.</small>
    </div>
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $year['is_active'] ? 'checked' : '' ?>>
      <label class="form-check-label">Jadikan Tahun Ajaran Aktif</label>
    </div>
    <button type="submit" class="btn btn-success">💾 Update</button>
    <a href="<?= base_url('admin/academic-year') ?>" class="btn btn-secondary">⬅ Kembali</a>
  </form>
</div>

<?= $this->endSection() ?>