<?php
// Tentukan status keseluruhan
$statusOverall = 'lunas';
if ($summary['sisa'] > 0 && $summary['dibayar'] == 0) $statusOverall = 'belum';
elseif ($summary['sisa'] > 0) $statusOverall = 'kurang';
?>

<style>
/* ================================================================
   Mobile-first Card Design - Halaman Publik Rincian Pembayaran
   ================================================================ */
.student-profile-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  color: white;
  padding: 1.5rem;
  margin-bottom: 1rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(102,126,234,0.3);
}
.student-profile-card::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 150px; height: 150px;
  background: rgba(255,255,255,0.07);
  border-radius: 50%;
}
.student-profile-card::after {
  content: '';
  position: absolute;
  bottom: -30px; left: -30px;
  width: 100px; height: 100px;
  background: rgba(255,255,255,0.05);
  border-radius: 50%;
}
.student-name {
  font-family: 'Outfit', sans-serif;
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}
.student-meta {
  font-size: 0.82rem;
  opacity: 0.85;
}
.student-nis-badge {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  border-radius: 30px;
  padding: 0.2rem 0.75rem;
  font-size: 0.78rem;
  letter-spacing: 0.5px;
  margin-top: 0.3rem;
}
.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  border-radius: 50px;
  padding: 0.3rem 0.9rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.status-chip.lunas    { background: #dcfce7; color: #15803d; }
.status-chip.kurang   { background: #fef9c3; color: #854d0e; }
.status-chip.belum    { background: #fee2e2; color: #991b1b; }

/* Summary Row */
.summary-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.summary-box {
  background: #fff;
  border-radius: 14px;
  padding: 0.85rem 0.5rem;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.summary-box .s-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #94a3b8;
  font-weight: 600;
}
.summary-box .s-value {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  margin-top: 0.2rem;
  line-height: 1.2;
}
.summary-box.s-total .s-value  { color: #3b82f6; }
.summary-box.s-bayar .s-value  { color: #10b981; }
.summary-box.s-sisa  .s-value  { color: #ef4444; }

/* Section Header */
.section-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 700;
  color: #94a3b8;
  padding: 0.4rem 0;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

/* Payment Item Card */
.pay-item-card {
  background: #fff;
  border-radius: 16px;
  margin-bottom: 0.75rem;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  transition: box-shadow 0.2s;
}
.pay-item-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1rem;
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}
.pay-item-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.pay-item-icon.bulanan { background: #ecfdf5; color: #10b981; }
.pay-item-icon.bebas   { background: #eff6ff; color: #3b82f6; }

.pay-item-info { flex: 1; min-width: 0; }
.pay-item-name {
  font-weight: 700;
  font-size: 0.88rem;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pay-item-kelas {
  font-size: 0.72rem;
  color: #94a3b8;
  margin-top: 0.2rem;
}
.pay-item-right {
  text-align: right;
  flex-shrink: 0;
}
.pay-item-amount {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
}
.pay-item-chevron {
  color: #cbd5e1;
  transition: transform 0.25s ease;
  margin-left: 0.5rem;
  flex-shrink: 0;
}
.pay-item-card.open .pay-item-chevron {
  transform: rotate(180deg);
}

/* Detail Panel */
.pay-detail-panel {
  display: none;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
  padding: 0.75rem 1rem 1rem;
}
.pay-item-card.open .pay-detail-panel { display: block; }

/* Progress Bar */
.progress-custom {
  height: 6px;
  border-radius: 99px;
  background: #e2e8f0;
  overflow: hidden;
  margin: 0.4rem 0;
}
.progress-custom .bar {
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, #10b981, #34d399);
}

/* Month Grid */
.month-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.45rem;
  margin-top: 0.6rem;
}
.month-pill {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 10px;
  padding: 0.45rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
}
.month-pill.lunas  { background: #dcfce7; color: #15803d; }
.month-pill.belum  { background: #fee2e2; color: #991b1b; }

/* History Row */
.riwayat-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.6rem 0;
  border-bottom: 1px dashed #e2e8f0;
  font-size: 0.8rem;
}
.riwayat-item:last-child { border-bottom: none; }
.riwayat-tgl { color: #64748b; }
.riwayat-jumlah { font-weight: 700; color: #10b981; }

/* Mini info row */
.mini-info-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.78rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}
.mini-info-row .mi-val { color: #1e293b; font-weight: 600; }

.empty-msg {
  text-align: center;
  padding: 1rem 0;
  color: #94a3b8;
  font-size: 0.85rem;
}
</style>

<!-- ====================================================
     TAMPILAN MOBILE (< 768px) — kartu accordion
     ==================================================== -->
<div class="d-md-none">

<!-- PROFIL SISWA -->
<div class="student-profile-card mb-3">
  <div style="position:relative;z-index:1;">
    <div class="student-name"><?= esc($siswa['nama']) ?></div>
    <div class="student-meta">
      <?= esc($siswa['nama_kelas'] ?: ucfirst($siswa['status_kelas'] ?? '-')) ?>
    </div>
    <div class="student-nis-badge">
      <i class="bi bi-person-badge me-1"></i> NIS: <?= esc($siswa['nis']) ?>
    </div>
    <div class="mt-2">
      <?php if ($statusOverall === 'lunas'): ?>
        <span class="status-chip lunas"><i class="bi bi-patch-check-fill"></i> LUNAS</span>
      <?php elseif ($statusOverall === 'kurang'): ?>
        <span class="status-chip kurang"><i class="bi bi-exclamation-circle-fill"></i> KURANG BAYAR</span>
      <?php else: ?>
        <span class="status-chip belum"><i class="bi bi-x-circle-fill"></i> BELUM BAYAR</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- RINGKASAN KEUANGAN -->
<div class="summary-row mb-3">
  <div class="summary-box s-total">
    <div class="s-label">Total Tagihan</div>
    <div class="s-value">Rp <?= number_format($summary['total'], 0, ',', '.') ?></div>
  </div>
  <div class="summary-box s-bayar">
    <div class="s-label">Sudah Bayar</div>
    <div class="s-value">Rp <?= number_format($summary['dibayar'], 0, ',', '.') ?></div>
  </div>
  <div class="summary-box s-sisa">
    <div class="s-label">Sisa Tunggakan</div>
    <div class="s-value">Rp <?= number_format($summary['sisa'], 0, ',', '.') ?></div>
  </div>
</div>

<!-- PEMBAYARAN BULANAN -->
<?php if (!empty($bulanan)): ?>
<div class="section-label"><i class="bi bi-calendar-month me-1"></i>Pembayaran Bulanan</div>
<?php foreach ($bulanan as $i => $b): ?>
  <?php
    $pct = $b['total'] > 0 ? round(($b['dibayar'] / $b['total']) * 100) : 0;
    $sChip = $b['dibayar'] == 0 ? 'belum' : ($b['sisa'] > 0 ? 'kurang' : 'lunas');
    $sLabel = $sChip === 'lunas' ? 'Lunas' : ($sChip === 'kurang' ? 'Kurang Bayar' : 'Belum Bayar');
    $sIcon = $sChip === 'lunas' ? 'check-circle-fill' : ($sChip === 'kurang' ? 'dash-circle-fill' : 'x-circle-fill');
  ?>
  <div class="pay-item-card" id="card-b-<?= $b['id'] ?>">
    <div class="pay-item-header" onclick="toggleCard('card-b-<?= $b['id'] ?>')">
      <div class="pay-item-icon bulanan"><i class="bi bi-calendar2-check"></i></div>
      <div class="pay-item-info">
        <div class="pay-item-name"><?= esc($b['nama']) ?></div>
        <div class="pay-item-kelas">
          <span class="status-chip <?= $sChip ?>" style="font-size:0.62rem;padding:0.1rem 0.5rem;">
            <i class="bi bi-<?= $sIcon ?>"></i> <?= $sLabel ?>
          </span>
          <?php if ($b['kelas_tagihan'] && $b['kelas_tagihan'] !== '-'): ?>
            &nbsp;<span style="color:#94a3b8;"><?= esc($b['kelas_tagihan']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="pay-item-right">
        <div class="pay-item-amount">Rp <?= number_format($b['total'], 0, ',', '.') ?></div>
        <?php if ($b['sisa'] > 0): ?>
          <div style="font-size:0.68rem;color:#ef4444;font-weight:600;">
            Sisa: Rp <?= number_format($b['sisa'], 0, ',', '.') ?>
          </div>
        <?php endif; ?>
      </div>
      <i class="bi bi-chevron-down pay-item-chevron"></i>
    </div>
    <div class="pay-detail-panel">
      <div class="mini-info-row">
        <span>Progress Pembayaran</span>
        <span class="mi-val"><?= $pct ?>%</span>
      </div>
      <div class="progress-custom"><div class="bar" style="width:<?= $pct ?>%"></div></div>
      <div class="mini-info-row" style="margin-top:0.5rem;">
        <span>Sudah Dibayar</span>
        <span class="mi-val" style="color:#10b981;">Rp <?= number_format($b['dibayar'], 0, ',', '.') ?></span>
      </div>
      <div class="section-label mt-2" style="font-size:0.65rem;">Rincian Per Bulan</div>
      <div class="month-grid">
        <?php foreach ($b['items'] as $item): ?>
          <?php $lunasItem = ($item['status'] === 'lunas'); ?>
          <div class="month-pill <?= $lunasItem ? 'lunas' : 'belum' ?>">
            <span><?= $lunasItem ? '✅' : '⏳' ?></span>
            <div>
              <div><?= bulanIndo($item['bulan']) ?></div>
              <?php if ($lunasItem && !empty($item['tanggal_pembayaran'])): ?>
                <div style="font-size:0.63rem;font-weight:400;opacity:0.75;">
                  <?= date('d/m/Y', strtotime($item['tanggal_pembayaran'])) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<?php endif; ?>

<!-- PEMBAYARAN LAINNYA (BEBAS) -->
<?php if (!empty($bebas)): ?>
<div class="section-label mt-3"><i class="bi bi-receipt me-1"></i>Pembayaran Lainnya</div>
<?php foreach ($bebas as $i => $b): ?>
  <?php
    $sChip = $b['dibayar'] == 0 ? 'belum' : ($b['sisa'] > 0 ? 'kurang' : 'lunas');
    $sLabel = $sChip === 'lunas' ? 'Lunas' : ($sChip === 'kurang' ? 'Kurang Bayar' : 'Belum Bayar');
    $sIcon = $sChip === 'lunas' ? 'check-circle-fill' : ($sChip === 'kurang' ? 'dash-circle-fill' : 'x-circle-fill');
    $pembayaranBebas = (new \App\Models\PembayaranModel())
      ->whereIn('tagihan_id', array_column($b['items'], 'id'))
      ->orderBy('tanggal', 'asc')
      ->findAll();
  ?>
  <div class="pay-item-card" id="card-f-<?= $b['id'] ?>">
    <div class="pay-item-header" onclick="toggleCard('card-f-<?= $b['id'] ?>')">
      <div class="pay-item-icon bebas"><i class="bi bi-receipt-cutoff"></i></div>
      <div class="pay-item-info">
        <div class="pay-item-name"><?= esc($b['nama']) ?></div>
        <div class="pay-item-kelas">
          <span class="status-chip <?= $sChip ?>" style="font-size:0.62rem;padding:0.1rem 0.5rem;">
            <i class="bi bi-<?= $sIcon ?>"></i> <?= $sLabel ?>
          </span>
          <?php if ($b['kelas_tagihan'] && $b['kelas_tagihan'] !== '-'): ?>
            &nbsp;<span style="color:#94a3b8;"><?= esc($b['kelas_tagihan']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="pay-item-right">
        <div class="pay-item-amount">Rp <?= number_format($b['total'], 0, ',', '.') ?></div>
        <?php if ($b['sisa'] > 0): ?>
          <div style="font-size:0.68rem;color:#ef4444;font-weight:600;">
            Sisa: Rp <?= number_format($b['sisa'], 0, ',', '.') ?>
          </div>
        <?php endif; ?>
      </div>
      <i class="bi bi-chevron-down pay-item-chevron"></i>
    </div>
    <div class="pay-detail-panel">
      <div class="mini-info-row">
        <span>Total Tagihan</span>
        <span class="mi-val">Rp <?= number_format($b['total'], 0, ',', '.') ?></span>
      </div>
      <div class="mini-info-row">
        <span>Sudah Dibayar</span>
        <span class="mi-val" style="color:#10b981;">Rp <?= number_format($b['dibayar'], 0, ',', '.') ?></span>
      </div>
      <div class="mini-info-row">
        <span>Sisa</span>
        <span class="mi-val" style="color:#ef4444;">Rp <?= number_format($b['sisa'], 0, ',', '.') ?></span>
      </div>
      <?php if (!empty($pembayaranBebas)): ?>
        <div class="section-label mt-2" style="font-size:0.65rem;">Riwayat Pembayaran</div>
        <?php foreach ($pembayaranBebas as $p): ?>
          <div class="riwayat-item">
            <div>
              <div class="riwayat-tgl">
                <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($p['tanggal'])) ?>
              </div>
              <div style="color:#94a3b8;font-size:0.72rem;">
                <?= esc(ucfirst($p['cara_bayar'])) ?>
                <?= $p['keterangan'] ? ' &middot; ' . esc($p['keterangan']) : '' ?>
              </div>
            </div>
            <div class="riwayat-jumlah">+Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-msg">
          <i class="bi bi-clock-history me-1"></i>Belum ada riwayat pembayaran
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php endif; ?>

<script>
function toggleCard(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('open');
}
</script>

</div><!-- /.d-md-none -->


<!-- ====================================================
     TAMPILAN DESKTOP (≥ 768px) — tabel klasik
     ==================================================== -->
<div class="d-none d-md-block">

<!-- Data Siswa -->
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
        <td>: <?= esc($siswa['nama_kelas'] ?: ucfirst($siswa['status_kelas'] ?? '-')) ?></td>
      </tr>
    </table>
  </div>
</div>

<!-- Rangkuman Keuangan -->
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

<!-- Pembayaran Bulanan -->
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
                    data-bs-target="#dt_bulanan_<?= esc($b['id']) ?>">Rincian</button>
                </td>
              </tr>
              <tr class="collapse" id="dt_bulanan_<?= esc($b['id']) ?>">
                <td colspan="8" class="p-0">
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
                          <td><?= $item['tanggal_pembayaran'] ? date('d-m-Y', strtotime($item['tanggal_pembayaran'])) : '-' ?></td>
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

<!-- Pembayaran Bebas -->
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
                $pembayaranDesk = (new \App\Models\PembayaranModel())
                  ->whereIn('tagihan_id', array_column($b['items'], 'id'))
                  ->orderBy('tanggal', 'asc')
                  ->findAll();
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
                    data-bs-target="#dt_bebas_<?= esc($b['id']) ?>">Rincian</button>
                </td>
              </tr>
              <tr class="collapse" id="dt_bebas_<?= esc($b['id']) ?>">
                <td colspan="8" class="pb-3 bg-light">
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
                      <?php if (empty($pembayaranDesk)): ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted">Belum ada riwayat pembayaran</td>
                        </tr>
                      <?php else: ?>
                        <?php $no = 1;
                        foreach ($pembayaranDesk as $p): ?>
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

</div><!-- /.d-none.d-md-block -->
