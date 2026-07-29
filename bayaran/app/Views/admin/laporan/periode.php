<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Laporan Penerimaan Periode</h4>

<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-md-3">
    <label>Dari Tanggal</label>
    <input type="date" name="start" value="<?= esc($start) ?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label>Sampai Tanggal</label>
    <input type="date" name="end" value="<?= esc($end) ?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label>Kelas</label>
    <select name="kelas_id" class="form-select">
      <option value="">Semua Kelas</option>
      <?php foreach ($kelasList as $k): ?>
        <option value="<?= $k['kelas_id'] ?>" <?= $kelasId == $k['kelas_id'] ? 'selected' : '' ?>>
          <?= $k['nama_kelas'] ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Tampilkan</button>
  </div>
</form>

<?php if (empty($data)): ?>
  <div class="alert alert-info">Tidak ada data untuk periode ini.</div>
<?php else: ?>
  <?php foreach ($data as $kelas => $siswaList): ?>
    <div class="card mb-4">
      <div class="card-header bg-light fw-bold">Kelas: <?= esc($kelas) ?></div>
      <div class="card-body p-0">
        <table class="table table-bordered table-sm m-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>NIS - Nama</th>
              <th>Jenis Bayaran</th>
              <th>Jumlah</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; $subTotal = 0; ?>
            <?php foreach ($siswaList as $siswa => $transList): ?>
              <?php foreach ($transList as $jenis => $row): ?>
                <?php $subTotal += $row['jumlah']; ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= esc($siswa) ?></td>
                  <td><?= esc(is_array($row) && isset($row['jenis']) ? $row['jenis'] : $jenis) ?> (<?= esc($row['tipe']) ?>)</td>
                  <td class="text-end">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                  <td><?= esc($row['keterangan'] ?? '-') ?></td>
                </tr>
              <?php endforeach ?>
            <?php endforeach ?>
          </tbody>
        </table>
        <div class="p-2 text-end fw-bold">
          Total Kelas <?= esc($kelas) ?>: Rp <?= number_format($subTotal, 0, ',', '.') ?>
        </div>
      </div>
    </div>
  <?php endforeach ?>
<?php endif ?>

<div class="d-flex justify-content-end gap-2 mb-3">
  <a href="<?= site_url("admin/laporan/periode/print?start={$start}&end={$end}&kelas_id={$kelasId}") ?>" class="btn btn-warning button-sm" target="_blank">🖨️ Cetak</a>
  <a href="<?= site_url("admin/laporan/periode/export?start={$start}&end={$end}&kelas_id={$kelasId}") ?>" class="btn btn-success button-sm">📥 Excel</a>
</div>

<?= $this->endSection() ?>
