<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Laporan Kasbon</h4>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-3">
    <select name="bulan" class="form-select">
      <option value="">- Semua Bulan -</option>
      <?php for ($i = 1; $i <= 12; $i++): ?>
        <option value="<?= $i ?>" <?= $bulan == $i ? 'selected' : '' ?>>
          <?= bulanIndo($i) ?>
        </option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-md-3">
    <input type="text" name="nama" class="form-control" placeholder="Cari Nama" value="<?= esc($nama ?? '') ?>">
  </div>

  <div class="col-md-2">
    <button class="btn btn-primary w-100">Filter</button>
  </div>
</form>

<table class="table table-bordered table-sm">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Tanggal</th>
      <th>Nama</th>
      <th>Jumlah</th>
      <th>Dibayar</th>
      <th>Sisa</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($kasbon as $i => $k): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= date('d-m-Y', strtotime($k['tanggal'])) ?></td>
      <td><?= esc($k['nama']) ?></td>
      <td>Rp <?= number_format($k['jumlah'], 0, ',', '.') ?></td>
      <td>Rp <?= number_format($k['dibayar'], 0, ',', '.') ?></td>
      <td>Rp <?= number_format($k['jumlah'] - $k['dibayar'], 0, ',', '.') ?></td>
      <td>
        <span class="badge <?= $k['status'] === 'lunas' ? 'bg-success' : 'bg-danger' ?>">
          <?= ucfirst($k['status']) ?>
        </span>
      </td>
    </tr>
    <?php endforeach ?>
    <?php if (!$kasbon): ?>
      <tr><td colspan="7" class="text-center">Data tidak ditemukan</td></tr>
    <?php endif ?>
  </tbody>
</table>

<?= $this->endSection() ?>
