<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Laporan Tagihan Siswa per Kelas</h4>
<?php if ($tahun === 'Belum diset'): ?>
  <div class="alert alert-warning">
    Belum ada tahun ajaran aktif. Silakan atur tahun ajaran terlebih dahulu.
  </div>
<?php endif ?>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-6">
    <label>Pilih Kelas</label>
    <select name="kelas_id" class="form-select" required>
      <option value="">Pilih Kelas</option>
      <?php foreach ($kelasList as $k): ?>
        <option value="<?= $k['kelas_id'] ?>" <?= $kelasId == $k['kelas_id'] ? 'selected' : '' ?>>
          <?= $k['nama_kelas'] ?>
        </option>
      <?php endforeach ?>
    </select>

  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary w-100">Tampilkan</button>
  </div>
</form>
<?php if ($kelasId): ?>
  <div class="d-flex justify-content-end gap-2 mb-3">
    <a href="<?= site_url("admin/laporan/tagihan-detail-kelas/{$kelasId}") ?>" class="btn btn-primary button-sm"
      target="_blank">🖨️ Cetak Detail Kelas</a>
    <a href="<?= site_url("admin/laporan/tagihan/print?kelas_id={$kelasId}") ?>" class="btn btn-warning button-sm"
      target="_blank">🖨️ Cetak Ringkasan</a>
    <a href="<?= site_url("admin/laporan/tagihan/export?kelas_id={$kelasId}") ?>" class="btn btn-success button-sm">📥
      Excel</a>
  </div>
<?php endif ?>


<?php if (!empty($data)): ?>
  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr>
        <th>No</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>Status Siswa</th>
        <th>Total Tagihan</th>
        <th>Total Dibayar</th>
        <th>Sisa</th>
        <th>Status</th>
        <th>Aksi</th> <!-- Perubahan di sini -->
      </tr>
    </thead>
    <tbody>
      <?php
      $totalTagihan = 0;
      $totalBayar = 0;
      $totalSisa = 0;
      ?>
      <?php foreach ($data as $i => $row): ?>
        <?php
        $totalTagihan += $row['total'];
        $totalBayar += $row['dibayar'];
        $totalSisa += $row['sisa'];
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= esc($row['siswa']['nis']) ?></td>
          <td><?= esc($row['siswa']['nama']) ?></td>
          <td><?= esc($row['siswa']['status']) ?></td>
          <td class="text-end">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
          <td class="text-end">Rp <?= number_format($row['dibayar'], 0, ',', '.') ?></td>
          <td class="text-end">Rp <?= number_format($row['sisa'], 0, ',', '.') ?></td>
          <td>
            <?php if ($row['status'] === 'Lunas'): ?>
              <span class="badge bg-success">Lunas</span>
            <?php elseif ($row['status'] === 'Kurang Bayar'): ?>
              <span class="badge bg-warning text-dark">Kurang Bayar</span>
            <?php else: ?>
              <span class="badge bg-danger">Belum Bayar</span>
            <?php endif ?>
          </td>
          <td>
            <a href="<?= site_url('admin/laporan/tagihan-detail/' . $row['siswa']['siswa_id']) ?>" target="_blank"
              class="btn btn-sm btn-primary">Detail</a>
          </td>
        </tr>
      <?php endforeach ?>
      <tr class="table-light fw-bold">
        <td colspan="3">TOTAL</td>
        <td class="text-end">Rp <?= number_format($totalTagihan, 0, ',', '.') ?></td>
        <td class="text-end">Rp <?= number_format($totalBayar, 0, ',', '.') ?></td>
        <td class="text-end">Rp <?= number_format($totalSisa, 0, ',', '.') ?></td>
        <td></td>
      </tr>

    </tbody>

  </table>
<?php endif ?>
<?= $this->endSection() ?>