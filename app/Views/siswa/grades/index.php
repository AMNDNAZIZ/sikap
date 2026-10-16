<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">

  <!-- Header -->
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-clipboard-data text-primary fs-5"></i>
    <h5 class="fw-bold mb-0">Nilai Saya</h5>
  </div>

  <!-- Info siswa -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
      <div class="d-flex flex-wrap gap-3 align-items-center">
        <div><span class="text-muted small">Siswa:</span> <span class="fw-semibold"><?= esc($student['name']) ?></span></div>
        <div><span class="text-muted small">Kelas:</span> <span class="badge bg-primary text-white fw-semibold px-2.5 py-1" style="color: #fff !important;"><?= esc($student['class_name']) ?></span></div>
        <div><span class="text-muted small">T.A.:</span> <span class="badge bg-secondary text-white fw-semibold px-2.5 py-1" style="color: #fff !important;"><?= esc($activeYear['year']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Tabs Semester -->
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($semesters as $s): ?>
      <li class="nav-item">
        <a class="nav-link <?= $s == 1 ? 'active' : '' ?>" data-bs-toggle="tab" href="#sem<?= $s ?>">
          Semester <?= $s ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="tab-content">

    <?php foreach ($semesters as $s): ?>
      <?php
        $formatifCount = count($allFormatifCols[$s] ?? []);
        $sumatifCount  = count($allSumatifCols[$s] ?? []);
      ?>

      <div class="tab-pane fade <?= $s == 1 ? 'show active' : '' ?>" id="sem<?= $s ?>">

        <a href="<?= site_url("siswa/grades/pdf/{$student['id']}?semester={$s}") ?>"
           target="_blank"
           class="btn btn-danger mb-3">⬇️ Download PDF Semester <?= $s ?></a>

        <div class="table-responsive">
          <table class="table table-bordered align-middle grades-table">

            <!-- ===================== THEAD ======================= -->
            <thead class="table-light">

              <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Mapel</th>

                <?php if ($formatifCount): ?>
                  <th colspan="<?= $formatifCount ?>">Formatif</th>
                  <th rowspan="2">Rerata F</th>
                <?php endif; ?>

                <?php if ($sumatifCount): ?>
                  <th colspan="<?= $sumatifCount ?>">Sumatif</th>
                  <th rowspan="2">Rerata S</th>
                <?php endif; ?>

                <!-- NILAI AKHIR -->
                <th rowspan="2">Akhir</th>

                <?php if ($s == 2 && $hasFinal): ?>
                  <th rowspan="2">Final</th>
                <?php endif; ?>
              </tr>

              <tr>
                <?php foreach ($allFormatifCols[$s] ?? [] as $meta): ?>
                  <th title="<?= esc($meta['tooltip']) ?>"><?= esc($meta['label']) ?></th>
                <?php endforeach; ?>

                <?php foreach ($allSumatifCols[$s] ?? [] as $meta): ?>
                  <th title="<?= esc($meta['tooltip']) ?>"><?= esc($meta['label']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>

            <!-- ===================== TBODY ======================= -->
            <tbody>
              <?php $no=1; foreach ($grades[$s] ?? [] as $row): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($row['subject']) ?></td>

                  <!-- FORMATIF -->
                  <?php foreach ($allFormatifCols[$s] ?? [] as $colKey => $meta): ?>
                    <td class="text-center">
                      <?= $row['formatif'][$colKey] ?? '-' ?>
                    </td>
                  <?php endforeach; ?>

                  <!-- RATA-RATA FORMATIF -->
                  <?php if ($formatifCount): ?>
                    <td class="text-center fw-bold">
                      <?= $row['avg_formatif'] !== null 
                            ? number_format($row['avg_formatif'], 2, ',', '.') 
                            : '-' ?>
                    </td>
                  <?php endif; ?>

                  <!-- SUMATIF -->
                  <?php foreach ($allSumatifCols[$s] ?? [] as $colKey => $meta): ?>
                    <td class="text-center">
                      <?= $row['sumatif'][$colKey] ?? '-' ?>
                    </td>
                  <?php endforeach; ?>

                  <!-- RATA-RATA SUMATIF -->
                  <?php if ($sumatifCount): ?>
                    <td class="text-center fw-bold">
                      <?= $row['avg_sumatif'] !== null 
                            ? number_format($row['avg_sumatif'], 2, ',', '.') 
                            : '-' ?>
                    </td>
                  <?php endif; ?>

                  <!-- NILAI AKHIR -->
                  <td class="text-center fw-bold">
                    <?php if ($row['nilai_akhir'] !== null): ?>
                      <?= number_format($row['nilai_akhir'], 2, ',', '.') ?>
                      <?php if (($row['erapor'] ?? null) !== null): ?>
                        <br><small class="text-success" style="font-size:.65rem;font-weight:normal;" title="Nilai Erapor (ditetapkan guru)">★ Erapor</small>
                      <?php endif; ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>

                  <!-- FINAL (Semester 2) -->
                  <?php if ($s == 2 && $hasFinal): ?>
                    <td class="text-center">
                      <?= $row['final'] ?? '-' ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>

          </table>
        </div>

      </div>
    <?php endforeach; ?>

  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
/* Sticky kolom pertama (No) dan kedua (Mapel) di tabel nilai */
.grades-table th:nth-child(1),
.grades-table td:nth-child(1),
.grades-table th:nth-child(2),
.grades-table td:nth-child(2) {
  position: sticky;
  background: #fff;
  z-index: 2;
}
.grades-table th:nth-child(1),
.grades-table td:nth-child(1) { left: 0; min-width: 32px; }
.grades-table th:nth-child(2),
.grades-table td:nth-child(2) { left: 32px; min-width: 100px; border-right: 2px solid #dee2e6; }
.grades-table thead th { background: #f8f9fa; z-index: 3; }
@media (max-width: 576px) {
  .grades-table { font-size: 0.72rem; }
  .grades-table th, .grades-table td { padding: 0.25rem 0.35rem; }
}
</style>
<?= $this->endSection() ?>
