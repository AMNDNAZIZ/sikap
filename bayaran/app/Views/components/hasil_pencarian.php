<div class="card mb-4">
  <div class="card-header bg-primary text-white">Data Siswa</div>
  <div class="card-body">
    <table class="table table-borderless w-auto mb-0">
      <tr>
        <th>NIS</th>
        <td>: <?= esc($siswa['nis']) ?></td>
      </tr>
      <tr>
        <th>Nama</th>
        <td>: <?= esc($siswa['nama']) ?></td>
      </tr>
      <tr>
        <th>Kelas</th>
        <td>:
          <?= esc($siswa['nama_kelas'] ?: ucfirst($siswa['status_kelas'] ?? '-')) ?>
        </td>
      </tr>
    </table>
  </div>
</div>

<!-- 💵 Card: Rangkuman Keuangan -->
<div class="card mb-4">
  <div class="card-header bg-info text-white">Rangkuman Keuangan</div>
  <div class="card-body">
    <table class="table table-borderless w-auto mb-0">
      <tr>
        <th>Total Tagihan</th>
        <td>: Rp. <?= number_format($summary['total'], 0, ',', '.') ?></td>
      </tr>
      <tr>
        <th>Dibayarkan</th>
        <td>: Rp. <?= number_format($summary['dibayar'], 0, ',', '.') ?></td>
      </tr>
      <tr>
        <th>Sisa Tunggakan</th>
        <td>: Rp. <?= number_format($summary['sisa'], 0, ',', '.') ?></td>
      </tr>
      <tr>
        <th>Status</th>
        <td>:
          <?php if ($summary['dibayar'] == 0): ?>
            <span class="badge bg-danger">Belum Bayar</span>
          <?php elseif ($summary['sisa'] > 0): ?>
            <span class="badge bg-warning text-dark">Kurang Bayar</span>
          <?php else: ?>
            <span class="badge bg-success">Lunas</span>
          <?php endif ?>
        </td>
      </tr>
    </table>
  </div>
</div>

<!-- 📅 Card: Pembayaran Bulanan -->
<?php if (!empty($bulanan)): ?>
  <div class="card mb-4">
    <div class="card-header bg-success text-white">Pembayaran Bulanan</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered m-0">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Jenis Bayaran</th>
              <th>Kelas</th>
              <th>Tagihan</th>
              <th>Dibayar</th>
              <th>Tunggakan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bulanan as $i => $b): ?>
              <?php
              $statusClass = $b['dibayar'] == 0
                ? 'text-danger'
                : ($b['sisa'] > 0 ? 'text-warning' : 'text-success');

              ?>
              <tr>
                <td class="<?= $statusClass ?>"><?= $i + 1 ?></td>
                <td class="<?= $statusClass ?>"><?= esc($b['nama']) ?></td>
                <td class="<?= $statusClass ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['total'], 0, ',', '.') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['sisa'], 0, ',', '.') ?></td>
                <td>
                  <?= $b['dibayar'] == 0
                    ? '<span class="badge bg-danger">Belum Bayar</span>'
                    : ($b['sisa'] > 0
                      ? '<span class="badge bg-warning text-dark">Kurang Bayar</span>'
                      : '<span class="badge bg-success">Lunas</span>') ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                    data-bs-target="#detail_bulanan_<?= esc($b['id']) ?>">Rincian</button>
                </td>
              </tr>
              <tr class="collapse" id="detail_bulanan_<?= esc($b['id']) ?>">
                <td colspan="7" class="p-0">
                  <table class="table table-sm table-bordered table-striped table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>No</th>
                        <th>Bulan</th>
                        <th>Tagihan</th>
                        <th>Tgl. Bayar</th>
                        <th>Cara Bayar</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $no = 1;
                      foreach ($b['items'] as $item): ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= bulanIndo($item['bulan']) ?></td>
                          <td>Rp. <?= number_format($item['jumlah'], 0, ',', '.') ?></td>
                          <td><?= $item['tanggal_pembayaran'] ? date('d-m-Y', strtotime($item['tanggal_pembayaran'])) : '-' ?>
                          </td>
                          <td><?= esc(ucfirst($item['cara_bayar'] ?? '-')) ?></td>
                          <td>
                            <?= $item['status'] == 'lunas'
                              ? '<span class="badge bg-success">Lunas</span>'
                              : '<span class="badge bg-danger">Belum</span>' ?>
                          </td>
                        </tr>
                      <?php endforeach ?>
                    </tbody>
                  </table>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif ?>

<!-- 💳 Card: Pembayaran Bebas -->
<?php if (!empty($bebas)): ?>
  <div class="card mb-4">
    <div class="card-header bg-warning text-dark">Pembayaran Bebas</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered m-0">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Jenis Bayaran</th>
              <th>Kelas</th>
              <th>Tagihan</th>
              <th>Dibayar</th>
              <th>Tunggakan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bebas as $i => $b): ?>
              <?php
              $statusClass = $b['dibayar'] == 0
                ? 'text-danger'
                : ($b['sisa'] > 0 ? 'text-warning' : 'text-success');
              ?>
              <tr>
                <td class="<?= $statusClass ?>"><?= $i + 1 ?></td>
                <td class="<?= $statusClass ?>"><?= esc($b['nama']) ?></td>
                <td class="<?= $statusClass ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['total'], 0, ',', '.') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['sisa'], 0, ',', '.') ?></td>
                <td>
                  <?= $b['dibayar'] == 0
                    ? '<span class="badge bg-danger">Belum Bayar</span>'
                    : ($b['sisa'] > 0
                      ? '<span class="badge bg-warning text-dark">Kurang Bayar</span>'
                      : '<span class="badge bg-success">Lunas</span>') ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                    data-bs-target="#detail_bebas_<?= esc($b['id']) ?>">Rincian</button>
                </td>
              </tr>
              <tr class="collapse border-top border-2" id="detail_bebas_<?= esc($b['id']) ?>"
                style="border-top: 3px solid #dee2e6;">
                <td colspan="7" class="pb-3 bg-light">
                  <table class="table table-sm table-bordered table-striped table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>No</th>
                        <th>Tgl. Bayar</th>
                        <th>Jml. Bayar</th>
                        <th>Cara Bayar</th>
                        <th>Keterangan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $pembayaran = (new \App\Models\PembayaranModel())
                        ->whereIn('tagihan_id', array_column($b['items'], 'id'))
                        ->orderBy('tanggal', 'asc')
                        ->findAll();
                      ?>
                      <?php if (empty($pembayaran)): ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted">Belum ada riwayat pembayaran</td>
                        </tr>
                      <?php else: ?>
                        <?php $no = 1;
                        foreach ($pembayaran as $p): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d-m-Y', strtotime($p['tanggal'])) ?></td>
                            <td>Rp. <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                            <td><?= ucfirst($p['cara_bayar']) ?></td>
                            <td><?= esc($p['keterangan']) ?></td>
                          </tr>
                        <?php endforeach ?>
                      <?php endif ?>
                    </tbody>
                  </table>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif ?>