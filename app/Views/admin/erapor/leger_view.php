<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">📊 Leger Nilai &amp; Rekap Absensi — <?= esc($class['name']) ?></h4>
    <small class="text-muted">Tahun Ajaran <?= esc($year['year']) ?> | Semester <?= esc($semesterLabel) ?></small>
  </div>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
      🖨️ Cetak Leger
    </button>
    <a href="<?= site_url('admin/erapor') ?>" class="btn btn-sm btn-secondary">⬅ Kembali</a>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<!-- Filter Semester & Action Bar -->
<div class="card mb-3 shadow-sm">
  <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <span class="fw-bold text-secondary">Semester:</span>
      <div class="btn-group btn-group-sm" role="group">
        <a href="<?= site_url("admin/erapor/leger/{$class['id']}/1?year_id={$year['id']}") ?>" 
           class="btn <?= $semester == 1 ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 1 (Ganjil)
        </a>
        <a href="<?= site_url("admin/erapor/leger/{$class['id']}/2?year_id={$year['id']}") ?>" 
           class="btn <?= $semester == 2 ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 2 (Genap)
        </a>
      </div>
    </div>

    <div>
      <a href="<?= site_url("admin/erapor/import?year_id={$year['id']}") ?>" class="btn btn-sm btn-success">
        📥 Import Leger Excel
      </a>
    </div>
  </div>
</div>

<?php if (empty($students)): ?>
  <div class="alert alert-warning">Tidak ada data siswa di kelas ini.</div>
<?php else: ?>
  <div class="card shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap-fill"></i> Leger Rapor Nilai Akhir</h6>
      <small class="text-muted">Total: <?= count($students) ?> Siswa | <?= count($subjects) ?> Mata Pelajaran</small>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-bordered table-hover align-middle text-nowrap mb-0" style="font-size: 0.85rem;">
        <thead class="table-light text-center align-middle">
          <tr>
            <th rowspan="2" style="width: 40px;">#</th>
            <th rowspan="2" class="text-start">Nama Siswa</th>
            <th rowspan="2">NISN</th>
            <th rowspan="2">NIS</th>
            <th colspan="<?= count($subjects) ?>" class="bg-primary-subtle border-primary-subtle text-primary fw-bold">
              Mata Pelajaran (Nilai Rapor Erapor)
            </th>
            <th colspan="3" class="bg-warning-subtle border-warning-subtle text-warning-emphasis fw-bold">
              Ketidakhadiran (Hari)
            </th>
          </tr>
          <tr>
            <?php foreach ($subjects as $sub): ?>
              <th class="bg-light">
                <span title="<?= esc($sub['name']) ?>"><?= esc($sub['name']) ?></span>
              </th>
            <?php endforeach; ?>
            <th class="bg-light text-danger" title="Sakit">S</th>
            <th class="bg-light text-warning" title="Izin">I</th>
            <th class="bg-light text-dark" title="Alpa">A</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $idx => $st): ?>
            <tr>
              <td class="text-center text-muted"><?= $idx + 1 ?></td>
              <td class="fw-semibold text-dark"><?= esc($st['name']) ?></td>
              <td class="text-center font-monospace"><small><?= esc($st['nisn'] ?: '—') ?></small></td>
              <td class="text-center font-monospace"><small><?= esc($st['nis'] ?: '—') ?></small></td>

              <?php foreach ($subjects as $sub): ?>
                <?php
                  $scoreVal = $scores[$st['id']][$sub['id']] ?? null;
                ?>
                <td class="text-center">
                  <?php if ($scoreVal !== null && $scoreVal !== ''): ?>
                    <span class="fw-bold <?= ($scoreVal < 70) ? 'text-danger' : 'text-dark' ?>">
                      <?= esc(number_format((float)$scoreVal, 0)) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>

              <?php
                $recap = $attendanceRecaps[$st['id']] ?? null;
              ?>
              <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['sick']) : '0' ?></td>
              <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['permitted']) : '0' ?></td>
              <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['absent']) : '0' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<style>
@media print {
  body { background: white !important; }
  .btn, .card-header, .alert, header, sidebar, .d-flex.justify-content-between.align-items-center.mb-3 {
    display: none !important;
  }
  .card { border: none !important; shadow: none !important; }
  .table-responsive { overflow: visible !important; }
}
</style>

<?= $this->endSection() ?>
