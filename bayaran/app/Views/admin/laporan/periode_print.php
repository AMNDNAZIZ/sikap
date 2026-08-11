<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>

<h4 class="text-center mb-4">Laporan Penerimaan Periode</h4>
<p>Periode: <?= date('d-m-Y', strtotime($start)) ?> s/d <?= date('d-m-Y', strtotime($end)) ?></p>

<?php $totalKeseluruhan = 0; ?>

<?php foreach ($data as $kelas => $siswaData): ?>
  <h6 class="mt-4">Kelas: <?= esc($kelas) ?></h6>
  <table class="table table-bordered table-sm mb-3">
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
      <?php $i = 1; $subtotal = 0; ?>
      <?php foreach ($siswaData as $nama => $jenisList): ?>
        <?php foreach ($jenisList as $jenis => $jumlah): ?>
          <tr>
            <td class="text-center"><?= $i++ ?></td>
            <td>-</td>
            <td><?= esc($nama) ?></td>
            <td><?= esc($jenis) ?></td>
            <td class="text-end">Rp <?= number_format($jumlah, 0, ',', '.') ?></td>
          </tr>
          <?php 
            $subtotal += $jumlah; 
            $totalKeseluruhan += $jumlah;
          ?>
        <?php endforeach ?>
      <?php endforeach ?>
      <tr class="fw-bold table-light">
        <td colspan="4" class="text-end">Total Kelas</td>
        <td class="text-end">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
      </tr>
    </tbody>
  </table>
<?php endforeach ?>

<div class="mt-4 border-top pt-2">
  <h5 class="text-end fw-bold">Total Keseluruhan: Rp <?= number_format($totalKeseluruhan, 0, ',', '.') ?></h5>
</div>

<?= $this->endSection() ?>
