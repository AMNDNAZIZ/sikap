<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$isBulanan = in_array($pos['id'], $bulananPosIds ?? []);
$isBebas   = in_array($pos['id'], $bebasPosIds ?? []);
$role      = session('role');
?>

<h4>Riwayat Transaksi: <?= esc($pos['nama_pos']) ?></h4>

<div class="mb-4">
  <a href="<?= site_url('admin/transaksi-pos') ?>" class="btn btn-warning btn-sm">← Kembali</a>
</div>

<!-- Filter -->
<form method="get" class="row g-2 align-items-end mb-3">
  <input type="hidden" name="pos_bayar_id" value="<?= $pos['id'] ?>">
  <div class="col-md-3">
    <label>Tanggal Mulai</label>
    <input type="date" name="start" class="form-control" value="<?= esc($start ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label>Tanggal Akhir</label>
    <input type="date" name="end" class="form-control" value="<?= esc($end ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label>Bulan (Tahun Ajaran)</label>
    <select name="bulan" class="form-select">
      <option value="">-- Semua --</option>
      <?php foreach ($bulanList as $bl): ?>
        <option value="<?= $bl ?>" <?= ($filterBulan === $bl ? 'selected' : '') ?>>
          <?= date('F Y', strtotime($bl . '-01')) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Filter</button>
  </div>
  <div class="col-md-2">
    <a href="<?= current_url() ?>" class="btn btn-secondary w-100">Reset</a>
  </div>
  <div class="col-md-2">
    <?php if (in_array($role, ['superadmin','admin','operator','staf'])): ?>
      <a href="<?= site_url("admin/transaksi-pos/export/{$pos['id']}?start={$start}&end={$end}") ?>" class="btn btn-success w-100">📥 Export Excel</a>
    <?php endif ?>
  </div>
</form>

<!-- Ringkasan Total -->
<div class="card mb-3">
  <div class="card-header">Ringkasan Total</div>
  <div class="card-body">
    <table class="table table-sm w-auto">
      <tr><th>Total Masuk</th><td>: Rp <?= number_format($summary['masuk'] ?? 0, 0, ',', '.') ?></td></tr>
      <tr><th>Total Keluar</th><td>: Rp <?= number_format($summary['keluar'] ?? 0, 0, ',', '.') ?></td></tr>
      <tr><th>Saldo</th><td>: Rp <?= number_format($summary['saldo'] ?? 0, 0, ',', '.') ?></td></tr>
    </table>
  </div>
</div>

<!-- Ringkasan Bulanan -->
<?php if (!empty($ringkasanBulanan)): ?>
<div class="card mb-3">
  <div class="card-header">Ringkasan Bulanan (Sesuai Tahun Ajaran Aktif)</div>
  <div class="card-body">
    <table class="table table-sm table-bordered text-center">
      <thead class="table-light">
        <tr>
          <th>Bulan</th>
          <th>Pemasukan</th>
          <th>Pengeluaran</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ringkasanBulanan as $rb): ?>
          <tr>
            <td><?= date('F Y', strtotime($rb['bulan'] . '-01')) ?></td>
            <td class="text-end">Rp <?= number_format($rb['total_masuk'], 0, ',', '.') ?></td>
            <td class="text-end">Rp <?= number_format($rb['total_keluar'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<!-- Pencarian Berdasarkan Keterangan -->
<form method="get" class="mb-3">
  <input type="hidden" name="start" value="<?= esc($start ?? '') ?>">
  <input type="hidden" name="end" value="<?= esc($end ?? '') ?>">
  <input type="hidden" name="bulan" value="<?= esc($filterBulan ?? '') ?>">
  <div class="input-group">
    <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan keterangan..." value="<?= esc($search ?? '') ?>">
    <button class="btn btn-outline-secondary" type="submit">Cari</button>
  </div>
</form>

<p class="text-danger">Data terbaru berada di paling atas <i class="bi bi-arrow-up"></i></p>

<!-- Tabel Transaksi -->
<table class="table table-bordered table-striped">
  <thead class="table-light text-center">
    <tr>
      <th>Tanggal</th>
      <th>Keterangan</th>
      <th>Pemasukan</th>
      <th>Pengeluaran</th>
      <th>Saldo</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($transaksi as $row): ?>
      <?php
        $isMasuk = $row['tipe'] === 'masuk';
        $jumlah = (int) $row['jumlah'];

        // Hak akses edit/hapus
        $canEditDelete = false;
        if ($role === 'superadmin') {
          $canEditDelete = true;
        } elseif ($role === 'admin' && $isBulanan) {
          $canEditDelete = true;
        } elseif ($role === 'operator' && $isBebas) {
          $canEditDelete = true;
        }
      ?>
      <tr>
        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
        <td>
          <?= esc($row['keterangan']) ?>
          <?php if (!empty($row['sinkronisasi'])): ?>
            <span class="badge bg-info ms-1">Sinkronisasi</span>
          <?php endif ?>
        </td>
        <td class="text-end"><?= $isMasuk ? 'Rp ' . number_format($jumlah, 0, ',', '.') : '' ?></td>
        <td class="text-end"><?= !$isMasuk ? 'Rp ' . number_format($jumlah, 0, ',', '.') : '' ?></td>
        <td class="text-end">Rp <?= number_format($saldoList[$row['id']] ?? 0, 0, ',', '.') ?></td>
        <td class="text-center">
          <?php if ($canEditDelete && empty($row['sinkronisasi'])): ?>
            <a href="<?= site_url("admin/transaksi-pos/edit/{$row['id']}") ?>" class="btn btn-sm btn-warning">Edit</a>
            <form action="<?= site_url("admin/transaksi-pos/delete/{$row['id']}") ?>" method="post" style="display:inline;">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-danger" onclick="return confirm('Yakin Hapus transaksi ini?')">Hapus</button>
            </form>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?php if (isset($pager)): ?>
  <div class="mt-3">
    <?= $pager->links('default', 'bootstrap') ?>
  </div>
<?php endif ?>

<?= $this->endSection() ?>