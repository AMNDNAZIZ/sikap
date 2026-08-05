<script>
  window['total_<?= $jenisId ?>'] = <?= (int) $item['total'] ?>;
</script>

<div class="modal fade" id="modal_bebas_<?= $jenisId ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pembayaran Bebas - <?= esc($item['nama_jenis_bayar']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <table class="table table-bordered mb-3">
          <tr><th>Total Tagihan</th><td>: Rp <?= number_format($item['total'], 0, ',', '.') ?></td></tr>
          <tr><th>Sudah Dibayar</th><td>: Rp <?= number_format($item['terbayar'], 0, ',', '.') ?></td></tr>
          <tr><th>Sisa</th><td>: Rp <?= number_format($item['total'] - $item['terbayar'], 0, ',', '.') ?></td></tr>
        </table>

        <form id="form_bebas_<?= $jenisId ?>" action="<?= site_url('admin/pembayaran/bayar') ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
          <input type="hidden" name="jenis_bayar_id" value="<?= $jenisId ?>">

          <div class="row g-2 mb-3">
            <div class="col-md-3">
              <label>Tanggal</label>
              <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label>Jumlah Bayar</label>
              <input type="text" name="jumlah_bayar" class="form-control input-angka" required>
            </div>
            <div class="col-md-3">
              <label>Cara Bayar</label>
              <select name="cara_bayar" class="form-select" required>
                <option value="Tunai">Tunai</option>
                <option value="Transfer">Transfer</option>
                <option value="KJP">KJP</option>
                <option value="BPMS">BPMS</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Angsuran ke</label>
              <input type="number" name="angsuran_ke" class="form-control" min="1">
            </div>
          </div>

          <div class="mb-3">
            <label>Keterangan</label>
            <input type="text" name="keterangan" class="form-control">
          </div>

          <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
        </form>

        <hr>
        <h6>Riwayat Pembayaran</h6>
        <?php if (empty($item['data'])): ?>
          <p class="text-muted">Belum ada riwayat pembayaran.</p>
        <?php else: ?>
          <table class="table table-bordered table-sm">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jumlah</th>
                <th>Cara Bayar</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($item['data'] as $i => $p): ?>
                <tr id="row_bebas_<?= $p['id'] ?>">
                  <td><?= $i + 1 ?></td>
                  <td><?= date('d-m-Y', strtotime($p['tanggal'] ?? '-')) ?></td>
                  <td>Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                  <td><?= esc($p['cara_bayar']) ?></td>
                  <td><?= esc($p['keterangan']) ?></td>
                  <td>
                    <form method="post" action="<?= site_url('admin/pembayaran/delete-bayar/' . $p['id']) ?>" class="form-hapus-bebas d-inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>
