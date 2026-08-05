<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Pembayaran Siswa</h4>

<!-- 🔍 Pencarian Siswa -->
<form method="get" action="<?= site_url('admin/pembayaran') ?>" class="mb-4">
  <div class="row g-2">
    <div class="col-12 col-md-6">
      <label for="siswa_id">Cari Siswa</label>
      <select name="siswa_id" id="siswa_id" class="form-select" required>
        <?php if (!empty($siswa)): ?>
          <option value="<?= esc($siswa['id']) ?>" selected>
            <?= esc($siswa['nis'] . ' - ' . $siswa['nama']) ?>
          </option>
        <?php endif ?>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
      <button class="btn btn-primary w-100">Tampilkan</button>
    </div>
  </div>
</form>

<?php if (!empty($notfound)): ?>
  <div class="alert alert-warning">Siswa tidak ditemukan.</div>
<?php endif ?>

<?php if (!empty($siswa)): ?>

  <div class="row">
    <!-- 📘 Data Siswa (Kiri) -->
    <div class="col-md-6 mb-4">
      <div class="card h-100">
        <div class="card-header bg-primary text-white">Data Siswa</div>
        <div class="card-body">
          <div class="table-responsive">
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
                <th>Kelas / Status</th>
                <td>: <?= esc($siswa['nama_kelas'] ?: ucfirst($siswa['status_kelas'] ?? '-')) ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 💰 Ringkasan Keuangan (Kanan) -->
    <div class="col-md-6 mb-4">
      <div class="card h-100">
        <div class="card-header bg-info text-white">Ringkasan Keuangan</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-borderless w-auto mb-0">
              <tr>
                <th>Total Tagihan</th>
                <td>: Rp <?= number_format($summary['total'], 0, ',', '.') ?></td>
              </tr>
              <tr>
                <th>Total Dibayar</th>
                <td>: Rp <?= number_format($summary['dibayar'], 0, ',', '.') ?></td>
              </tr>
              <tr>
                <th>Sisa</th>
                <td>: Rp <?= number_format($summary['total'] - $summary['dibayar'], 0, ',', '.') ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <form method="get" action="<?= base_url('admin/pembayaran/cetak-bukti') ?>" target="_blank"
    class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <input type="hidden" name="siswa_id" value="<?= esc($siswa['id']) ?>">

    <div class="input-group input-group-sm" style="max-width: 200px;">
      <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">
      Cetak Bukti Per Tanggal
    </button>
  </form>

  <!-- 📅 Tagihan Bulanan -->
  <?php if (!empty($bulanan)): ?>
    <div class="card mb-4">
      <div class="card-header bg-success text-white">Tagihan Bulanan</div>
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
                <?php $cls = ($b['total'] == $b['dibayar']) ? 'text-success' : 'text-danger'; ?>
                <tr data-ringkasan="bulanan-<?= $b['id'] ?>">
                  <td class="<?= $cls ?>"><?= $i + 1 ?></td>
                  <td class="<?= $cls ?>"><?= esc($b['nama']) ?></td>
                  <td class="<?= $cls ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                  <td class="<?= $cls ?>">Rp <?= number_format($b['total'], 0, ',', '.') ?></td>
                  <td class="<?= $cls ?>">Rp <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                  <td class="<?= $cls ?>">Rp <?= number_format($b['total'] - $b['dibayar'], 0, ',', '.') ?></td>
                  <td>
                    <?php if ($b['dibayar'] == 0): ?>
                      <span class="badge bg-danger">Belum Bayar</span>
                    <?php elseif ($b['total'] > $b['dibayar']): ?>
                      <span class="badge bg-warning text-dark">Kurang Bayar</span>
                    <?php else: ?>
                      <span class="badge bg-success">Lunas</span>
                    <?php endif ?>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                      data-bs-target="#detail_bulanan_<?= $b['id'] ?>">Rincian</button>
                  </td>
                </tr>

                <tr class="collapse" id="detail_bulanan_<?= $b['id'] ?>">
                  <td colspan="7" class="p-0">
                    <form class="form-cetak-bulanan border-bottom p-2" method="post"
                      action="<?= site_url('admin/pembayaran/cetak-banyak') ?>" target="_blank">
                      <?= csrf_field() ?>
                      <input type="hidden" name="pembayaran_ids" class="input-pembayaran-ids">
                      <button type="submit" class="btn btn-sm btn-secondary btn-cetak-banyak">🖨 Cetak Banyak</button>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-sm table-bordered table-striped mb-0">
                        <thead class="table-light">
                          <tr>
                            <th>Pilih</th>
                            <th colspan="2">Bulan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Cara Bayar</th>
                            <th>Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; ?>
                          <?php foreach ($b['items'] as $item): ?>
                            <?php
                            if ($item['jumlah'] == 0) {
                              $c = ''; // warna default (hitam)
                            } elseif ($item['status'] === 'lunas') {
                              $c = 'text-success';
                            } else {
                              $c = 'text-danger';
                            }
                            ?>
                            <tr data-id="<?= $item['id'] ?>" data-siswa="<?= $siswa['id'] ?>" data-jenis="<?= $b['id'] ?>">
                              <td class="col-check <?= $c ?>">
                                <?php if ($item['status'] === 'lunas' && !empty($item['pembayaran_id'])): ?>
                                  <input type="checkbox" class="check-cetak-bulanan" value="<?= $item['pembayaran_id'] ?>">
                                <?php endif ?>
                              </td>
                              <td class="<?= $c ?> text-end"><?= $no++ ?></td>
                              <td class="<?= $c ?>"><?= bulanIndo($item['bulan']) ?></td>
                              <td class="<?= $c ?>">Rp <?= number_format($item['jumlah'], 0, ',', '.') ?></td>
                              <td class="status">
                                <?php if ($item['jumlah'] == 0): ?>
                                  <span class="badge bg-secondary">Tidak Berlaku</span>
                                <?php elseif ($item['status'] === 'lunas'): ?>
                                  <span class="badge bg-success">Lunas</span>
                                <?php else: ?>
                                  <span class="badge bg-danger">Belum</span>
                                <?php endif ?>
                              </td>
                              <td class="tanggal <?= $c ?>">
                                <?php if ($item['status'] === 'lunas' && !empty($item['tanggal'])): ?>
                                  <?= date('d-m-Y', strtotime($item['tanggal'])) ?>
                                <?php elseif ($item['status'] === 'lunas'): ?>
                                  <span class="text-muted">-</span>
                                <?php else: ?>
                                  <input type="date" name="tanggal" class="form-control form-control-sm"
                                    value="<?= date('Y-m-d') ?>">
                                <?php endif ?>
                              </td>
                              <td class="cara <?= $c ?>">
                                <?= $item['status'] === 'lunas'
                                  ? esc(ucfirst($item['cara_bayar'] ?? '-'))
                                  : '<select name="cara_bayar" class="form-select form-select-sm"><option>Tunai</option><option>Transfer</option><option>KJP</option><option>BPMS</option></select>' ?>
                              </td>
                              <td class="aksi">
                                <?php if ($item['jumlah'] == 0): ?>
                                  <span class="text-muted small">Tidak ditagih</span>
                                <?php elseif ($item['status'] === 'lunas' && !empty($item['pembayaran_id'])): ?>
                                  <a href="<?= site_url("admin/pembayaran/cetak/" . esc($item['pembayaran_id'])) ?>"
                                    target="_blank" class="btn btn-sm btn-info">Cetak</a>
                                  <button class="btn btn-sm btn-danger btn-hapus"
                                    data-id="<?= esc($item['pembayaran_id']) ?>">Hapus</button>
                                <?php else: ?>
                                  <form class="form-bayar d-flex gap-1 align-items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                                    <input type="hidden" name="jenis_bayar_id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-sm btn-success">Bayar</button>
                                  </form>
                                <?php endif ?>
                              </td>
                            </tr>
                          <?php endforeach ?>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif ?>

  <!-- 💳 Tagihan Bebas -->
  <?php if (!empty($bebas)): ?>
    <div class="card mb-4">
      <div class="card-header bg-warning text-dark">Tagihan Bebas</div>
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
                <?php $bb = $b['total'] === $b['dibayar'] ? 'text-success' : 'text-danger'; ?>
                <tr data-ringkasan="bebas-<?= $b['id'] ?>">
                  <td class="<?= $bb ?>"><?= $i + 1 ?></td>
                  <td class="<?= $bb ?>"><?= esc($b['nama']) ?></td>
                  <td class="<?= $bb ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                  <td class="<?= $bb ?>">Rp. <?= number_format($b['total'], 0, ',', '.') ?></td>
                  <td class="<?= $bb ?>">Rp. <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                  <td class="<?= $bb ?>">Rp. <?= number_format($b['sisa'], 0, ',', '.') ?></td>
                  <td>
                    <?php if ($b['dibayar'] == 0): ?>
                      <span class="badge bg-danger">Belum Bayar</span>
                    <?php elseif ($b['sisa'] > 0): ?>
                      <span class="badge bg-warning text-dark">Kurang Bayar</span>
                    <?php else: ?>
                      <span class="badge bg-success">Lunas</span>
                    <?php endif ?>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                      data-bs-target="#detail_bebas_<?= $b['id'] ?>">Rincian</button>
                  </td>
                </tr>

                <tr class="collapse border-top border-2" id="detail_bebas_<?= $b['id'] ?>">
                  <td colspan="7" class="p-3 bg-light">
                    <form class="form-bebas row g-2 align-items-end mb-3" id="form_bebas_<?= $b['id'] ?>" method="post"
                      action="<?= site_url('admin/pembayaran/bayar') ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                      <input type="hidden" name="tagihan_id" value="<?= $b['items'][0]['id'] ?>">
                      <input type="hidden" name="jenis_bayar_id" value="<?= $b['id'] ?>">

                      <div class="col-12 col-md-2">
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"
                          required>
                      </div>
                      <div class="col-12 col-md-2">
                        <input type="text" name="jumlah_bayar" class="form-control form-control-sm input-angka"
                          placeholder="Jumlah" required>
                      </div>
                      <div class="col-12 col-md-2">
                        <select name="cara_bayar" class="form-select form-select-sm" required>
                          <option value="Tunai">Tunai</option>
                          <option value="Transfer">Transfer</option>
                          <option value="KJP">KJP</option>
                          <option value="BPMS">BPMS</option>
                        </select>
                      </div>
                      <div class="col-12 col-md-2">
                        <input type="number" name="angsuran_ke" class="form-control form-control-sm" min="1"
                          placeholder="Angsuran ke" required>
                      </div>
                      <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-sm btn-success w-100">Bayar</button>
                      </div>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="table-light text-center">
                          <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Cara</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                          </tr>
                        </thead>
                        <tbody id="riwayat_<?= $b['id'] ?>">
                          <?php
                          $pembayaran = (new \App\Models\PembayaranModel())
                            ->whereIn('tagihan_id', array_column($b['items'], 'id'))
                            ->orderBy('tanggal', 'asc')
                            ->findAll();
                          ?>
                          <?php if (empty($pembayaran)): ?>
                            <tr>
                              <td colspan="6" class="text-center text-muted">Belum ada pembayaran</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($pembayaran as $j => $p): ?>
                              <tr>
                                <td><?= $j + 1 ?></td>
                                <td><?= date('d-m-Y', strtotime($p['tanggal'])) ?></td>
                                <td>Rp. <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                                <td><?= ucfirst($p['cara_bayar']) ?></td>
                                <td><?= esc($p['keterangan']) ?></td>
                                <td>
                                  <form method="post" action="<?= site_url("admin/pembayaran/delete-bayar/" . esc($p['id'])) ?>"
                                    class="form-hapus-bebas d-inline" data-id="<?= esc($p['id']) ?>"
                                    data-jenis="<?= esc($b['id']) ?>">
                                    <?= csrf_field() ?>
                                    <a href="<?= site_url("admin/pembayaran/cetak/" . esc($p['id'])) ?>" target="_blank"
                                      class="btn btn-sm btn-info">Cetak</a>
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                  </form>
                                </td>
                              </tr>
                            <?php endforeach ?>
                          <?php endif ?>
                        </tbody>
                      </table>
                    </div>

                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif ?>
<?php endif ?>

<!-- Select2 & jQuery -->
<link href="<?= base_url('public/assets/css/select2.min.css') ?>" rel="stylesheet" />
<link href="<?= base_url('public/assets/css/responsive-pembayaran.css') ?>" rel="stylesheet" />
<script src="<?= base_url('public/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= base_url('public/assets/js/select2.min.js') ?>"></script>
<script>const BASE_URL = "<?= rtrim(base_url(), '/') ?>";</script>
<script>
  const CSRF_NAME = '<?= csrf_token() ?>';
  let CSRF_HASH = '<?= csrf_hash() ?>';
</script>
<script src="<?= base_url('public/assets/js/pembayaran.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?= $this->endSection() ?>