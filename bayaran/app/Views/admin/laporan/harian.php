<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Laporan Penerimaan Harian</h4>

<form method="get" class="row g-2 mb-4 w-50">
  <div class="col">
    <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" class="form-control" required>
  </div>
  <div class="col">
    <button class="btn btn-primary">Tampilkan</button>
  </div>
</form>

<?php if (empty($data)): ?>
  <div class="alert alert-info">Belum ada transaksi pada tanggal ini.</div>
<?php else: ?>
  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>Jenis Bayaran</th>
        <th>Jumlah</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $no=1;
        $total = 0;
        foreach ($data as $row):
          $total += $row['jumlah'];
      ?>
        <tr>
          <td><?= $no++; ?></td>
          <td><?= esc($row['nis']) ?></td>
          <td><?= esc($row['nama']) ?></td>
          <td><?= esc($row['jenis']) ?> (<?= ucfirst($row['tipe']) ?>)</td>
          <td class="text-end">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
          <td><?= esc($row['keterangan'] ?? '-') ?></td>
        </tr>
      <?php endforeach ?>
      <tr class="table-light fw-bold">
        <td colspan="4" class="text-mid">Total</td>
        <td class="text-end">Rp <?= number_format($total, 0, ',', '.') ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>
<?php endif ?>
<div class="d-flex justify-content-end gap-2 mb-3">
  <a href="<?= site_url('admin/laporan/harian/print?date=' . $tanggal) ?>" target="_blank" class="btn btn-secondary btn-sm">🖨️ Cetak</a>
  <a href="<?= site_url('admin/laporan/harian/export?date=' . $tanggal) ?>" class="btn btn-success btn-sm">📥 Excel</a>
</div>

<?= $this->endSection() ?>
