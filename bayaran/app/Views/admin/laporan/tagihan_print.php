<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>

<h4 class="text-center">Laporan Tagihan Siswa - Kelas <?= esc($kelas['nama_kelas']) ?></h4>

<table class="table table-bordered table-sm mt-4">
  <thead class="table-light">
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th class="text-end">Total</th>
      <th class="text-end">Dibayar</th>
      <th class="text-end">Sisa</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1; $total = $dibayar = $sisa = 0; ?>
    <?php foreach ($data as $row): ?>
      <?php
        $total += $row['total'];
        $dibayar += $row['dibayar'];
        $sisa += $row['sisa'];
      ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($row['siswa']['nis']) ?></td>
        <td><?= esc($row['siswa']['nama']) ?></td>
        <td class="text-end">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
        <td class="text-end">Rp <?= number_format($row['dibayar'], 0, ',', '.') ?></td>
        <td class="text-end">Rp <?= number_format($row['sisa'], 0, ',', '.') ?></td>
        <td><?= $row['status'] ?></td>
      </tr>
    <?php endforeach ?>
    <tr class="fw-bold table-light">
      <td colspan="3" class="text-end">Total</td>
      <td class="text-end">Rp <?= number_format($total, 0, ',', '.') ?></td>
      <td class="text-end">Rp <?= number_format($dibayar, 0, ',', '.') ?></td>
      <td class="text-end">Rp <?= number_format($sisa, 0, ',', '.') ?></td>
      <td></td>
    </tr>
  </tbody>
</table>

<?= $this->endSection() ?>
