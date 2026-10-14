<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">DAFTAR NILAI</h4>
  <a href="<?= site_url('admin/grades') ?>" class="btn btn-sm btn-secondary">
    ⬅ Kembali
  </a>
</div>
<table class="mb-3">
  <tr>
    <td style="width:150px;">Tahun Ajaran</td>
    <td>: <?= esc($yearName) ?></td>
  </tr>
  <tr>
    <td>Kelas</td>
    <td>: <?= esc($class['name']) ?></td>
  </tr>
  <tr>
    <td>Mata Pelajaran</td>
    <td>: <?= esc($subject['name']) ?></td>
  </tr>
</table>


<!-- Tabs & Action Buttons -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <ul class="nav nav-tabs mb-0 border-bottom-0">
    <li class="nav-item">
      <a class="nav-link <?= $semester == 1 ? 'active' : '' ?>"
         href="<?= site_url("admin/grades/show/$class[id]/$subject[id]/1") ?>">
         Semester 1
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $semester == 2 ? 'active' : '' ?>"
         href="<?= site_url("admin/grades/show/$class[id]/$subject[id]/2") ?>">
         Semester 2
      </a>
    </li>
  </ul>
  <div class="d-flex gap-1 flex-wrap">
    <a class="btn btn-sm btn-outline-primary"
       href="<?= site_url("admin/grades/$class[id]/$subject[id]/$semester/pdf") ?>">
       📄 Cetak PDF
    </a>
    <a class="btn btn-sm btn-outline-success"
       href="<?= site_url("admin/grades/$class[id]/$subject[id]/$semester/excel") ?>">
       📊 Export Excel
    </a>
    <a class="btn btn-sm btn-success"
       href="<?= site_url("admin/erapor/input/$class[id]/$subject[id]/$semester?year_id=" . ($yearId ?? '')) ?>">
       ✏️ Input Erapor
    </a>
  </div>
</div>

<!-- Tabel Nilai -->
<div class="table-responsive shadow-sm rounded-3">
  <table class="table table-bordered table-striped align-middle text-center grades-manage-table mb-0">
    <thead class="table-light">
      <tr>
        <th rowspan="2">No</th>
        <th rowspan="2">Nama Siswa</th>

        <?php
        $totalFormCols = 0;
        foreach ($visibleMaterials as $m) {
          $totalFormCols += count($formatifMethods[$m['id']] ?? []);
        }
        if ($totalFormCols > 0) {
          echo '<th colspan="' . ($totalFormCols + 1) . '">Formatif</th>';
        }

        $totalSumCols = count($sumatifMethods);
        if ($totalSumCols > 0) {
          echo '<th colspan="' . ($totalSumCols + 1) . '">Sumatif</th>';
        }
        ?>
        <th rowspan="2">Nilai Rapor<br><small class="fw-normal text-muted">(Acuan Sistem)</small></th>
        <?php if ($semester == 2 && $hasFinal): ?>
          <th rowspan="2">Nilai Final</th>
        <?php endif; ?>
        <th rowspan="2" class="table-success">Nilai Erapor<br><small class="fw-normal">(Input Guru)</small></th>
      </tr>

      <tr>
        <!-- Formatif sub-columns -->
        <?php foreach ($visibleMaterials as $idx => $m): ?>
          <?php foreach ($formatifMethods[$m['id']] as $method): ?>
            <th>M<?= $idx + 1 ?> (<?= esc($method) ?>)</th>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($totalFormCols > 0): ?>
          <th>Rerata</th>
        <?php endif; ?>

        <!-- Sumatif sub-columns -->
        <?php foreach ($sumatifMethods as $method): ?>
          <th><?= ucfirst(esc($method)) ?></th>
        <?php endforeach; ?>
        <?php if ($totalSumCols > 0): ?>
          <th>Rerata</th>
        <?php endif; ?>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($students as $i => $stu): ?>
        <?php $sid = $stu['id']; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="text-start"><?= esc($stu['name']) ?></td>

          <!-- Formatif -->
          <?php foreach ($visibleMaterials as $m): ?>
            <?php foreach ($formatifMethods[$m['id']] as $method): ?>
              <?php $val = $scores[$sid][$semester]['formatif'][$m['id']][$method] ?? ''; ?>
              <td><?= $val !== '' ? esc(number_format((float)$val, 2)) : '' ?></td>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?php if ($totalFormCols > 0): ?>
            <?php $favg = $scores[$sid][$semester]['formatif_avg'] ?? ''; ?>
            <td class="bg-success-subtle fw-bold">
              <?= $favg !== '' ? esc(number_format((float)$favg, 2)) : '' ?>
            </td>
          <?php endif; ?>

          <!-- Sumatif -->
          <?php foreach ($sumatifMethods as $method): ?>
            <?php $val = $scores[$sid][$semester]['sumatif'][ucfirst($method)] ?? ''; ?>
            <td><?= $val !== '' ? esc(number_format((float)$val, 2)) : '' ?></td>
          <?php endforeach; ?>
          <?php if ($totalSumCols > 0): ?>
            <?php $savg = $scores[$sid][$semester]['sumatif_avg'] ?? ''; ?>
            <td class="bg-warning-subtle fw-bold">
              <?= $savg !== '' ? esc(number_format((float)$savg, 2)) : '' ?>
            </td>
          <?php endif; ?>

          <!-- Rapor -->
          <td class="bg-info-subtle fw-bold">
            <?= $scores[$sid][$semester]['rapor'] !== null ? number_format((float)$scores[$sid][$semester]['rapor'], 2) : '' ?>
          </td>

          <!-- Final -->
          <?php if ($semester == 2 && $hasFinal): ?>
            <td class="bg-danger-subtle fw-bold">
              <?= $scores[$sid][$semester]['final'] ?? '' ?>
            </td>
          <?php endif; ?>

          <!-- Erapor -->
          <?php $erapor = $scores[$sid][$semester]['erapor'] ?? null; ?>
          <td class="table-success fw-bold text-center">
            <?php if ($erapor !== null): ?>
              <?= number_format((float)$erapor, 2) ?>
            <?php else: ?>
              <span class="text-muted small fst-italic">belum</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<style>
.grades-manage-table th:nth-child(1),
.grades-manage-table td:nth-child(1),
.grades-manage-table th:nth-child(2),
.grades-manage-table td:nth-child(2) {
  position: sticky;
  background: #fff;
  z-index: 2;
}
.grades-manage-table th:nth-child(1),
.grades-manage-table td:nth-child(1) { left: 0; min-width: 35px; }
.grades-manage-table th:nth-child(2),
.grades-manage-table td:nth-child(2) { left: 35px; min-width: 140px; text-align: left !important; border-right: 2px solid #dee2e6; }
.grades-manage-table thead th { background: #f8f9fa; z-index: 3; }
@media (max-width: 576px) {
  .grades-manage-table { font-size: 0.75rem; }
  .grades-manage-table th, .grades-manage-table td { padding: 0.35rem 0.4rem; }
}
</style>

<?= $this->endSection() ?>
