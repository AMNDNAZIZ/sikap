<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Detail Kasbon</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif ?>

<table class="table table-borderless w-auto small">
  <tr><th>Guru</th><td>: <?= esc($guru['nama']) ?></td></tr>
  <tr><th>Tanggal</th><td>: <?= date('d-m-Y', strtotime($kasbon['tanggal'])) ?></td></tr>
  <tr><th>Jumlah Kasbon</th><td>: Rp <?= number_format($kasbon['jumlah'], 0, ',', '.') ?></td></tr>
  <tr><th>Terbayar</th><td>: Rp <?= number_format($kasbon['terbayar'], 0, ',', '.') ?></td></tr>
  <tr><th>Status</th><td>: <span class="badge <?= $kasbon['status'] === 'lunas' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($kasbon['status']) ?></span></td></tr>
</table>

<hr>

<!-- ➕ Form Tambah Cicilan -->
<h5>Tambah Cicilan</h5>
<form method="post" action="<?= site_url('admin/kasbon/bayar/' . $kasbon['id']) ?>" class="row g-2 mb-3">
  <?= csrf_field() ?>
  <div class="col-md-3">
    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
  </div>
  <div class="col-md-3">
    <input type="number" name="jumlah" class="form-control" placeholder="Jumlah Bayar" required>
  </div>
  <div class="col-md-4">
    <input type="text" name="keterangan" class="form-control" placeholder="Keterangan (opsional)">
  </div>
  <div class="col-md-2">
    <button class="btn btn-success w-100">Simpan</button>
  </div>
</form>

<!-- 📄 Riwayat Cicilan -->
<h5>Riwayat Pembayaran</h5>
<table class="table table-bordered table-sm">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Tanggal</th>
      <th>Jumlah</th>
      <th>Keterangan</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($cicilan as $i => $c): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= date('d-m-Y', strtotime($c['tanggal'])) ?></td>
        <td>Rp <?= number_format($c['jumlah'], 0, ',', '.') ?></td>
        <td><?= esc($c['keterangan'] ?? '-') ?></td>
        <td>
          <a href="<?= site_url('admin/kasbon/hapus-cicilan/' . $c['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus cicilan ini?')">Hapus</a>
          <a href="<?= site_url('admin/kasbon/cetak/' . $c['id']) ?>" target="_blank" class="btn btn-sm btn-secondary">Cetak</a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<a href="<?= site_url('admin/kasbon') ?>" class="btn btn-sm btn-secondary mt-3">← Kembali</a>

<?= $this->endSection() ?>
