<div class="modal fade" id="modal_bulanan_<?= $jenisId ?>" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Bayar Bulanan - <?= esc($item['nama_jenis_bayar']) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3 d-flex justify-content-end gap-2">
          <a href="<?= site_url("admin/pembayaran/cetak-bulanan/{$siswa['id']}/{$jenisId}?mode=hari") ?>" class="btn btn-secondary btn-sm" target="_blank">Cetak Hari Ini</a>
          <a href="<?= site_url("admin/pembayaran/cetak-bulanan/{$siswa['id']}/{$jenisId}") ?>" class="btn btn-dark btn-sm" target="_blank">Cetak Semua</a>
        </div>

        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Bulan</th>
              <th>Jumlah</th>
              <th>Status</th>
              <th>Tanggal Bayar</th>
              <th>Cara Bayar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($item['data'] as $i => $row): ?>
              <tr id="row_<?= $row['id'] ?>">
                <td><?= $i + 1 ?></td>
                <td><?= bulanIndo($row['bulan']) ?></td>
                <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                <td class="status">
                  <?= $row['status'] === 'lunas'
                    ? '<span class="badge bg-success">Lunas</span>'
                    : '<span class="badge bg-danger">Belum</span>' ?>
                </td>
                <td class="tanggal">
                  <?= $row['status'] === 'belum'
                    ? '<input type="date" name="tanggal" class="form-control form-control-sm" value="' . date('Y-m-d') . '" required>'
                    : date('d-m-Y', strtotime($row['updated_at'] ?? $row['created_at'])) ?>
                </td>
                <td class="cara-bayar">
                  <?php if ($row['status'] === 'belum'): ?>
                    <select name="cara_bayar" class="form-select form-select-sm" required>
                      <option value="Tunai">Tunai</option>
                      <option value="Transfer">Transfer</option>
                      <option value="KJP">KJP</option>
                      <option value="BPMS">BPMS</option>
                    </select>
                  <?php else: ?>
                    <?= esc(ucfirst($row['cara_bayar'] ?? '-')) ?>
                  <?php endif ?>
                </td>
                <td class="aksi">
                  <?php if ($row['status'] === 'belum'): ?>
                    <form class="form-bayar d-flex gap-2 align-items-center" data-id="<?= $row['id'] ?>">
                      <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                      <input type="hidden" name="bulan" value="<?= $row['id'] ?>">
                      <input type="hidden" name="jenis_bayar_id" value="<?= $jenisId ?>">
                      <button class="btn btn-sm btn-success">Bayar</button>
                    </form>
                  <?php else: ?>
                    <button class="btn btn-sm btn-danger btn-hapus" data-id="<?= $row['id'] ?>">Hapus</button>
                  <?php endif ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
