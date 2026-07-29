<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Filter Tagihan</h4>

<form method="get" class="row g-2 mb-4">
  <!-- Tahun Ajaran -->
  <div class="col-md-3">
    <select name="tahun_ajaran_id" class="form-select">
      <option value="">-- Semua Tahun Ajaran --</option>
      <?php foreach ($tahunAjaran as $ta): ?>
        <option value="<?= $ta['id'] ?>" <?= ($filter['tahun_ajaran_id'] == $ta['id']) ? 'selected' : '' ?>>
          <?= esc($ta['nama']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <!-- Kelas -->
  <div class="col-md-3">
    <select name="kelas_id" class="form-select">
      <option value="">-- Semua Kelas --</option>
      <?php foreach ($kelas as $k): ?>
        <option value="<?= $k['id'] ?>" <?= ($filter['kelas_id'] == $k['id']) ? 'selected' : '' ?>>
          <?= esc($k['nama_kelas']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <!-- Tipe -->
  <div class="col-md-2">
    <select name="tipe" class="form-select">
      <option value="">-- Semua Tipe --</option>
      <option value="bulanan" <?= ($filter['tipe'] == 'bulanan') ? 'selected' : '' ?>>Bulanan</option>
      <option value="bebas" <?= ($filter['tipe'] == 'bebas') ? 'selected' : '' ?>>Bebas</option>
    </select>
  </div>

  <!-- Jenis Bayar -->
  <div class="col-md-3">
    <select name="jenis_bayar_id" class="form-select">
      <option value="">-- Semua Jenis Bayar --</option>
      <?php foreach ($jenisBayar as $jb): ?>
        <option value="<?= $jb['id'] ?>" <?= ($filter['jenis_bayar_id'] == $jb['id']) ? 'selected' : '' ?>>
          <?= esc($jb['nama_jenis_bayar']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <!-- Tombol -->
  <div class="col-md-1">
    <button class="btn btn-outline-primary w-100">Filter</button>
  </div>
</form>

<div class="mb-3">
    <a href="<?= site_url('admin/laporan/filter-tagihan/print?' . http_build_query($filter)) ?>" target="_blank" class="btn btn-outline-secondary">
      🖨️ Cetak PDF
    </a>
  <a href="<?= site_url('admin/laporan/filter-tagihan/excel?' . http_build_query($filter)) ?>" class="btn btn-outline-success">
    📥 Export Excel
  </a>
</div>

<table class="table table-bordered">
    <thead>
      <tr>
        <th>#</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>Kelas</th>
        <th>Tahun Ajaran</th>
        <th>Jenis Bayar</th>
        <th>Jenis</th>
        <th>Jumlah Tagihan</th>
        <th>Sudah Dibayar</th>
        <th>Belum Dibayar</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($tagihan)): ?>
        <?php $no = 1 ?>
        <?php foreach ($tagihan as $row): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($row['nis']) ?></td>
            <td><?= esc($row['siswa_nama']) ?></td>
            <td><?= esc($row['kelas_nama']) ?></td>
            <td><?= esc($row['tahun_ajaran_nama']) ?></td>
            <td><?= esc($row['jenis_bayar_nama']) ?></td>
            <td><?= ucfirst(esc($row['tipe'])) ?></td>
            <td><?= number_format($row['total_jumlah'] ?? $row['jumlah']) ?></td>
            <td><?= number_format($row['total_terbayar'] ?? $row['terbayar']) ?></td>
            <td><?= number_format($row['sisa']) ?></td>
            <td><?= ($row['sisa'] <= 0) ? 'Lunas' : 'Belum' ?></td>
          </tr>
        <?php endforeach ?>
      <?php else: ?>
        <tr>
          <td colspan="10" class="text-center">Data tidak ditemukan.</td>
        </tr>
      <?php endif ?>
    </tbody>
</table>

<?= $pager->links('default', 'bootstrap') ?>

<?= $this->endSection() ?>
