<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><?= esc($pos['nama']) ?></strong>
    <form method="post" action="<?= site_url("admin/transaksi-pos/sinkron/{$pos['id']}") ?>">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-success">🔄 Sinkron</button>
    </form>
  </div>

  <div class="card-body">
    <table class="table table-borderless w-auto mb-4">
      <tr><th>Total Masuk</th><td>: Rp <?= number_format($pos['masuk'], 0, ',', '.') ?></td></tr>
      <tr><th>Total Keluar</th><td>: Rp <?= number_format($pos['keluar'], 0, ',', '.') ?></td></tr>
      <tr><th>Saldo</th><td>: Rp <?= number_format($pos['saldo'], 0, ',', '.') ?></td></tr>
    </table>

    <form method="post" action="<?= site_url('admin/transaksi-pos/keluar') ?>" class="row g-2 align-items-end mb-4">
      <?= csrf_field() ?>
      <input type="hidden" name="pos_bayar_id" value="<?= $pos['id'] ?>">
      <div class="col-md-3">
        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-3">
        <input type="number" name="jumlah" class="form-control" placeholder="Jumlah Pengeluaran" required>
      </div>
      <div class="col-md-4">
        <input type="text" name="keterangan" class="form-control" placeholder="Keterangan" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-danger w-100">Catat</button>
      </div>
    </form>

    <table class="table table-sm table-bordered table-striped">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Tipe</th>
          <th>Jumlah</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pos['riwayat'] as $t): ?>
          <tr>
            <td><?= date('d-m-Y', strtotime($t['tanggal'])) ?></td>
            <td><?= ucfirst($t['tipe']) ?></td>
            <td>Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></td>
            <td><?= esc($t['keterangan']) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
