<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>
<h5 class="text-center mb-3">Laporan Penerimaan Harian</h5>
<p>Tanggal: <?= date('d-m-Y', strtotime($tanggal)) ?></p>

<table class="table table-bordered table-sm">
  <thead class="table-light text-center">
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Jenis Bayaran</th>
      <th>Jumlah</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1 ?>
    <?php foreach ($list as $row): ?>
      <tr>
        <td class="text-center"><?= $i++ ?></td>
        <td><?= esc($row['nis']) ?></td>
        <td><?= esc($row['nama']) ?></td>
        <td><?= esc($row['jenis']) ?></td>
        <td class="text-end">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
      </tr>
    <?php endforeach ?>
    <tr class="table-light fw-bold">
      <td colspan="4" class="text-end">Total</td>
      <td class="text-end">Rp <?= number_format($total, 0, ',', '.') ?></td>
    </tr>
  </tbody>
</table>
<?= $this->endSection() ?>
