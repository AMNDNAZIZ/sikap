<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>Pilih Kelas & Periode Laporan Absensi</h4>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="get" id="form-laporan" action="<?= base_url('admin/attendance/view') ?>" class="mt-3 row g-3">

  <?php if (!empty($classes)): ?>
    <div class="col-md-4">
      <label for="class_id" class="form-label">Kelas</label>
      <select name="class_id" id="class_id" class="form-select" required>
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div class="col-md-4">
    <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
    <select name="jenis_laporan" id="jenis_laporan" class="form-select" required>
      <option value="bulan">Bulanan</option>
      <option value="semester1">Rekap Semester 1 (Juli – Desember)</option>
      <option value="semester2">Rekap Semester 2 (Januari – Juni)</option>
      <option value="tahun">Rekap Tahunan (Juli – Juni)</option>
    </select>
  </div>

  <div class="col-md-4" id="bulan-group">
    <label for="month" class="form-label">Bulan</label>
    <input type="month" name="month" id="month" value="<?= date('Y-m') ?>" class="form-control">
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-laporan');
    const jenisSelect = document.getElementById('jenis_laporan');
    const bulanGroup = document.getElementById('bulan-group');

    function toggleBulan() {
      const isRekap = ['semester1', 'semester2', 'tahun'].includes(jenisSelect.value);
      bulanGroup.style.display = isRekap ? 'none' : 'block';

      // Arahkan form ke endpoint yang sesuai
      if (jenisSelect.value === 'semester1' || jenisSelect.value === 'semester2') {
        form.action = '<?= base_url('admin/attendance/rekap') ?>';
        // Ganti nama field agar sesuai dengan parameter rekap()
        jenisSelect.name = 'periode';
      } else {
        form.action = '<?= base_url('admin/attendance/view') ?>';
        jenisSelect.name = 'jenis_laporan';
      }
    }

    jenisSelect.addEventListener('change', toggleBulan);
    toggleBulan(); // jalankan saat load pertama
  });
</script>

<?= $this->endSection() ?>