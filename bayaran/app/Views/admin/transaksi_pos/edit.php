<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit Transaksi: <?= esc($pos['nama_pos']) ?></h4>

<form method="post" action="<?= site_url("admin/transaksi-pos/update/{$transaksi['id']}") ?>" class="row g-3 w-50">
  <?= csrf_field() ?>
  <input type="hidden" name="pos_bayar_id" value="<?= $transaksi['pos_bayar_id'] ?>">

  <div class="col-12">
    <label for="tanggal">Tanggal</label>
    <input type="date" name="tanggal" value="<?= esc($transaksi['tanggal']) ?>" class="form-control" required>
  </div>

  <div class="col-12">
    <label for="jumlah">Jumlah</label>
    <input type="text" name="jumlah" value="<?= esc($transaksi['jumlah']) ?>" class="form-control input-angka" required>
  </div>

  <div class="col-12">
    <label for="keterangan">Keterangan</label>
    <textarea name="keterangan" class="form-control" rows="3"><?= esc($transaksi['keterangan']) ?></textarea>
  </div>

  <div class="col-12">
    <button class="btn btn-primary">Simpan</button>
    <a href="<?= site_url("admin/transaksi-pos/detail/{$transaksi['pos_bayar_id']}") ?>" class="btn btn-secondary">Kembali</a>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.input-angka').forEach(input => {
    input.addEventListener('input', function () {
      const raw = this.value.replace(/\D/g, '');
      this.dataset.raw = raw;
      this.value = new Intl.NumberFormat('id-ID').format(raw);
    });
  });

  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function () {
      form.querySelectorAll('.input-angka').forEach(input => {
        if (input.dataset.raw) input.value = input.dataset.raw;
      });
    });
  });
});
</script>
<?= $this->endSection() ?>
