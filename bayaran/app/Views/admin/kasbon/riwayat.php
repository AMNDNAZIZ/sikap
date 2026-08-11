<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-warning"><?= session()->getFlashdata('msg') ?></div>
<?php endif ?>


<h4>Riwayat Pembayaran Kasbon</h4>

<a href="<?= site_url('admin/kasbon') ?>" class="btn btn-secondary btn-sm mb-3">← Kembali</a>

<!-- Rincian Kasbon -->
<table class="table table-borderless w-auto">
  <tr><th>Nama</th><td>: <?= esc($kasbon['nama']) ?></td></tr>
  <tr><th>Tanggal Pinjam</th><td>: <?= date('d-m-Y', strtotime($kasbon['tanggal'])) ?></td></tr>
  <tr><th>Nominal Pinjaman</th><td>: Rp <?= number_format($kasbon['jumlah'], 0, ',', '.') ?></td></tr>
  <tr><th>Total Dibayar</th><td>: Rp <?= number_format($kasbon['dibayar'], 0, ',', '.') ?></td></tr>
  <tr><th>Sisa Pinjaman</th><td>: Rp <?= number_format($kasbon['jumlah'] - $kasbon['dibayar'], 0, ',', '.') ?></td></tr>
  <tr><th>Status</th><td>: 
    <span class="badge <?= $kasbon['status'] === 'lunas' ? 'bg-success' : 'bg-danger' ?>">
      <?= ucfirst($kasbon['status']) ?>
    </span>
  </td></tr>
</table>

<hr>

<?php if ($kasbon['status'] !== 'lunas'): ?>
<h5>Tambah Angsuran</h5>
<form method="post" action="<?= site_url('admin/kasbon/bayar') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <input type="hidden" name="kasbon_id" value="<?= $kasbon['id'] ?>">

  <div class="col-md-3">
    <label>Tanggal</label>
    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
  </div>

  <div class="col-md-3">
    <label>Jumlah</label>
    <input type="text" name="jumlah" class="form-control input-angka" required>
  </div>

  <div class="col-md-4">
    <label>Keterangan</label>
    <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
  </div>

  <div class="col-md-2">
    <label>&nbsp;</label>
    <button class="btn btn-success w-100">Bayar</button>
  </div>
</form>
<?php endif; ?>

<!-- Riwayat Angsuran -->
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
    <?php foreach ($bayar as $i => $b): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= date('d-m-Y', strtotime($b['tanggal'])) ?></td>
      <td>Rp <?= number_format($b['jumlah'], 0, ',', '.') ?></td>
      <td><?= esc($b['keterangan']) ?></td>
      <td>
        <form method="post" action="<?= site_url('admin/kasbon/hapus-angsuran/' . $b['id']) ?>" onsubmit="return confirm('Yakin hapus angsuran ini?')">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-danger">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach ?>
    <?php if (!$bayar): ?>
      <tr><td colspan="5" class="text-center">Belum ada pembayaran</td></tr>
    <?php endif ?>
  </tbody>
</table>

<script>
  document.querySelectorAll('.input-angka').forEach(el => {
    el.addEventListener('input', () => {
      let raw = el.value.replace(/\D/g, '');
      el.dataset.raw = raw;
      el.value = Number(raw).toLocaleString('id-ID');
    });
  });

  // submit raw number
  document.querySelector('form').addEventListener('submit', function() {
    const input = this.querySelector('input[name="jumlah"]');
    if (input.dataset.raw) {
      input.value = input.dataset.raw;
    }
  });
</script>

<?= $this->endSection() ?>
