<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Transaksi Pos Bayar</h4>

<?php foreach ($posList as $pos): ?>
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between flex-column flex-md-row align-items-start align-items-md-center">
      <strong><?= esc($pos['nama']) ?></strong>

      <?php if ($pos['canManage']): ?>
        <form method="post" action="<?= site_url('admin/transaksi-pos/sinkron/' . $pos['id']) ?>" class="mt-2 mt-md-0">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-success">
            🔄 Sinkron Pemasukan
            <?php if ($pos['unsynced'] > 0): ?>
              <span class="badge bg-light text-dark">
                Rp <?= number_format($pos['unsynced'], 0, ',', '.') ?>
              </span>
            <?php endif ?>
          </button>
        </form>

      <?php endif ?>
    </div>

    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <a href="<?= site_url('admin/transaksi-pos/detail/' . $pos['id']) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-text"></i> Lihat Riwayat</a>
      </div>

      <div class="table-responsive">
        <table class="table w-auto mb-3">
          <thead class="table-light text-center">
            <tr>
              <th>Total Masuk</th>
              <th>Total Keluar</th>
              <th>Saldo</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Rp <?= number_format($pos['masuk'], 0, ',', '.') ?></td>
              <td>Rp <?= number_format($pos['keluar'], 0, ',', '.') ?></td>
              <td>Rp <?= number_format($pos['saldo'], 0, ',', '.') ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php if ($pos['canManage'] && $pos['isBulanan']): ?>
          <form method="post" action="<?= site_url('admin/transaksi-pos/masuk') ?>" class="row g-2 align-items-end mb-3 mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="pos_bayar_id" value="<?= $pos['id'] ?>">
            <div class="col-12 col-md-3">
              <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-12 col-md-3">
              <input type="text" name="jumlah" class="form-control input-angka" placeholder="Jumlah Pemasukan" required>
            </div>
            <div class="col-12 col-md-4">
              <input type="text" name="keterangan" class="form-control" placeholder="Keterangan" required>
            </div>
            <div class="col-12 col-md-2">
              <button class="btn btn-success w-100">Catat Pemasukan</button>
            </div>
          </form>
      <?php endif ?>


      <?php if ($pos['canManage']): ?>
        <form method="post" action="<?= site_url('admin/transaksi-pos/keluar') ?>" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <input type="hidden" name="pos_bayar_id" value="<?= $pos['id'] ?>">
          <div class="col-12 col-md-3">
            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12 col-md-3">
            <input type="text" name="jumlah" class="form-control input-angka" placeholder="Jumlah Pengeluaran" required>
          </div>
          <div class="col-12 col-md-4">
            <input type="text" name="keterangan" class="form-control" placeholder="Keterangan" required>
          </div>
          <div class="col-12 col-md-2">
            <button class="btn btn-danger w-100">Catat Pengeluaran</button>
          </div>
        </form>
      <?php endif ?>
    </div>
  </div>
<?php endforeach ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.input-angka').forEach(input => {
    input.addEventListener('input', () => {
      const raw = input.value.replace(/\D/g, '');
      input.dataset.raw = raw;
      input.value = new Intl.NumberFormat('id-ID').format(raw);
    });
  });

  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('.input-angka').forEach(input => {
        if (input.dataset.raw) input.value = input.dataset.raw;
      });
    });
  });
});
</script>

<?= $this->endSection() ?>
