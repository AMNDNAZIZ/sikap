<?php
$bulanMap = [
  7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
  11 => 'November', 12 => 'Desember',
  1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
  5 => 'Mei', 6 => 'Juni'
];
$grouped = array_column($data, 'total', 'bulan');
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
  <h3>Laporan Penerimaan Pembayaran Bulanan per Tahun Ajaran</h3>
  <form method="GET" class="form-inline mb-3">
    <div class="form-group mr-2">
      <label for="tahun" class="mr-2">Tahun Ajaran:</label>
      <select name="tahun" id="tahun" class="form-control">
        <option value="">-- Pilih Tahun --</option>
        <?php foreach($tahunList as $ta): ?>
          <option value="<?= $ta['id'] ?>" <?= ($tahun == $ta['id']) ? 'selected' : '' ?>>
            <?= $ta['nama'] ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary mr-2 mt-3">Tampilkan</button>
    <?php if(isset($data)): ?>
        <a class="btn btn-success mt-3"
           href="<?= base_url('admin/laporanlain/export-bulanan') . "?tahun=$tahun" ?>">
           Export Excel
        </a>
    <?php endif ?>
  </form>

  <?php if(isset($data)): ?>
    <table class="table table-bordered">
      <thead class="thead-light"><tr><th>Bulan</th><th>Total Penerimaan</th></tr></thead>
      <tbody>
        <?php foreach ($bulanMap as $num => $nama): ?>
          <tr>
            <td><?= $nama ?></td>
            <td>Rp <?= number_format($grouped[$num] ?? 0, 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
