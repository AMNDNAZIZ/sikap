<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">🎉 Hasil Import Leger Nilai</h4>
    <small class="text-muted">Proses Menyimpan Data Leger Telah Selesai</small>
  </div>
  <a href="<?= site_url('admin/erapor') ?>" class="btn btn-sm btn-secondary">⬅ Kembali ke Erapor</a>
</div>

<?php if (!empty($stats['scores_saved']) && $stats['scores_saved'] > 0): ?>
  <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill fs-3 me-3"></i>
    <div>
      <h5 class="alert-heading mb-1">Import Berhasil Disimpan!</h5>
      <div>Sebanyak <strong><?= $stats['scores_saved'] ?> nilai erapor</strong> berhasil dimasukkan/diperbarui ke dalam sistem.</div>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
    <div>
      <h5 class="alert-heading mb-1">Import Selesai dengan Catatan</h5>
      <div>Tidak ada nilai baru yang diperbarui atau semua data telah terdaftar.</div>
    </div>
  </div>
<?php endif; ?>

<!-- Summary Table Card -->
<div class="card shadow-sm mb-4">
  <div class="card-header bg-white py-2">
    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line"></i> Ringkasan Eksekusi Import</h6>
  </div>
  <div class="card-body p-0">
    <table class="table table-bordered mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Kategori Data</th>
          <th class="text-center text-success">Berhasil Disimpan</th>
          <th class="text-center text-warning-emphasis">Dilewati</th>
          <th class="text-center text-danger">Error / Invalid</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="fw-bold">Nilai Rapor (Erapor Score)</td>
          <td class="text-center fw-bold text-success fs-5"><?= $stats['scores_saved'] ?? 0 ?></td>
          <td class="text-center text-muted"><?= $stats['scores_skipped'] ?? 0 ?></td>
          <td class="text-center text-danger"><?= count($stats['score_errors'] ?? []) ?></td>
        </tr>
        <tr>
          <td class="fw-bold">Rekap Ketidakhadiran (Absensi)</td>
          <td class="text-center fw-bold text-success fs-5"><?= $stats['absence_saved'] ?? 0 ?></td>
          <td class="text-center text-muted"><?= $stats['absence_skipped'] ?? 0 ?></td>
          <td class="text-center text-muted">0</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Warning / Error Log List -->
<?php if (!empty($stats['warnings']) || !empty($stats['score_errors'])): ?>
  <div class="card shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning-subtle text-warning-emphasis py-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-octagon"></i> Catatan &amp; Peringatan Import</h6>
    </div>
    <div class="card-body py-2">
      <ul class="mb-0 ps-3">
        <?php foreach ($stats['score_errors'] as $err): ?>
          <li class="text-danger mb-1"><?= esc($err) ?></li>
        <?php endforeach; ?>
        <?php foreach ($stats['warnings'] as $warn): ?>
          <li class="text-dark mb-1"><?= esc($warn) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between">
  <a href="<?= site_url('admin/erapor') ?>" class="btn btn-secondary">
    🏠 Kembali ke Daftar Mapel Erapor
  </a>

  <?php if (!empty($redirectInputUrl)): ?>
    <a href="<?= site_url($redirectInputUrl) ?>" class="btn btn-primary btn-lg">
      📝 Lihat &amp; Edit Nilai di Menu Input ➡
    </a>
  <?php else: ?>
    <a href="<?= site_url('admin/erapor') ?>" class="btn btn-primary">
      Daftar Nilai Erapor ➡
    </a>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
