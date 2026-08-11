<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4><?= $title ?></h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form class="mb-3 d-flex gap-2">
  <input type="text" name="q" class="form-control" placeholder="Cari nama" value="<?= esc($q ?? '') ?>">
  <button class="btn btn-secondary">Filter</button>
</form>

<?php if ($canEdit): ?>
<form method="post" action="<?= site_url('admin/kasbon/save') ?>" class="row g-2 mb-3">
  <?= csrf_field() ?>
  <div class="col-md-3"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  <div class="col-md-3"><input type="text" name="nama" class="form-control" placeholder="Nama Peminjam" required></div>
  <div class="col-md-3"><input type="text" name="jumlah" class="form-control input-angka" placeholder="Nominal" required></div>
  <div class="col-md-3"><button class="btn btn-primary w-100">Tambah Kasbon</button></div>
</form>
<?php endif; ?>

<?php if (isset($totalTunggakan)): ?>
<div class="alert alert-info">
  Total Sisa Pinjaman Seluruh Kasbon: <strong>Rp <?= number_format($totalTunggakan, 0, ',', '.') ?></strong>
</div>
<?php endif; ?>


<table class="table table-bordered table-sm">
  <thead class="table-light">
    <tr>
      <th>No</th>
      <th>Tanggal</th>
      <th>Nama</th>
      <th>Jumlah</th>
      <th>Dibayar</th>
      <th>Sisa</th>
      <th>Status</th>
      <?php if ($canEdit): ?><th>Aksi</th><?php endif; ?>
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
      <?php if ($canEdit): ?>
      <td>
        <?php if ($k['status'] === 'belum'): ?>
          <a href="<?= site_url('admin/kasbon/riwayat/' . $k['id']) ?>" class="btn btn-sm btn-warning">Bayar</a>
        <?php endif ?>
        <?php if ($k['status'] === 'lunas'): ?>
          <a href="<?= site_url('admin/kasbon/riwayat/' . $k['id']) ?>" class="btn btn-sm btn-info">Rincian</a>
          <form method="post" action="<?= site_url('admin/kasbon/delete/' . $k['id']) ?>" style="display:inline" onsubmit="return confirm('Hapus kasbon?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-danger">Hapus</button>
          </form>
        <?php endif ?>
      </td>
      <?php endif; ?>

    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<script>
document.querySelectorAll('.input-angka').forEach(input => {
  input.addEventListener('input', e => {
    const raw = input.value.replace(/\D/g, '');
    input.value = Number(raw).toLocaleString('id-ID');
  });
});
</script>

<?= $this->endSection() ?>
