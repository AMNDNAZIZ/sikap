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
        $fCount = $maxFormatif[$s] ?? 0;
        $sCount = $maxSumatif[$s] ?? 0;
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
                <th rowspan="2" class="text-center align-middle" style="width: 40px;">#</th>
                <th rowspan="2" class="align-middle" style="min-width: 160px;">Mata Pelajaran</th>

                <?php if ($fCount > 0): ?>
                  <th colspan="<?= $fCount ?>" class="text-center">Formatif</th>
                  <th rowspan="2" class="text-center align-middle">Rerata F</th>
                <?php endif; ?>

                <?php if ($sCount > 0): ?>
                  <th colspan="<?= $sCount ?>" class="text-center">Sumatif</th>
                  <th rowspan="2" class="text-center align-middle">Rerata S</th>
                <?php endif; ?>

                <!-- NILAI AKHIR -->
                <th rowspan="2" class="text-center align-middle">Akhir</th>

                <?php if ($s == 2 && $hasFinal): ?>
                  <th rowspan="2" class="text-center align-middle">Final</th>
                <?php endif; ?>
              </tr>

              <tr>
                <?php for ($i = 1; $i <= $fCount; $i++): ?>
                  <th class="text-center" style="min-width: 38px;"><?= $i ?></th>
                <?php endfor; ?>

                <?php for ($i = 1; $i <= $sCount; $i++): ?>
                  <th class="text-center" style="min-width: 38px;"><?= $i ?></th>
                <?php endfor; ?>
              </tr>
            </thead>

            <!-- ===================== TBODY ======================= -->
            <tbody>
              <?php $no = 1; foreach ($grades[$s] ?? [] as $row): ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td class="fw-semibold"><?= esc($row['subject']) ?></td>

                  <!-- FORMATIF -->
                  <?php for ($i = 0; $i < $fCount; $i++): ?>
                    <?php
                      $fItem = $row['formatif'][$i] ?? null;
                      $fTitle = '';
                      if ($fItem) {
                          $typeStr = !empty($fItem['type']) ? ' (' . ucfirst($fItem['type']) . ')' : '';
                          $fTitle = esc(($fItem['material_title'] ?: 'Materi') . $typeStr);
                      }
                    ?>
                    <td class="text-center" <?= $fTitle ? 'title="' . $fTitle . '"' : '' ?>>
                      <?php if ($fItem !== null && $fItem['score'] !== null && $fItem['score'] !== ''): ?>
                        <?= is_numeric($fItem['score']) ? ((float)$fItem['score'] == (int)$fItem['score'] ? (int)$fItem['score'] : number_format((float)$fItem['score'], 1, ',', '.')) : esc($fItem['score']) ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  <?php endfor; ?>

                  <!-- RATA-RATA FORMATIF -->
                  <?php if ($fCount > 0): ?>
                    <td class="text-center fw-bold">
                      <?= $row['avg_formatif'] !== null 
                            ? number_format($row['avg_formatif'], 2, ',', '.') 
                            : '-' ?>
                    </td>
                  <?php endif; ?>

                  <!-- SUMATIF -->
                  <?php for ($i = 0; $i < $sCount; $i++): ?>
                    <?php
                      $sItem = $row['sumatif'][$i] ?? null;
                      $sTitle = '';
                      if ($sItem) {
                          $typeStr = !empty($sItem['type']) ? ' (' . ucfirst($sItem['type']) . ')' : '';
                          $sTitle = esc('Sumatif' . $typeStr);
                      }
                    ?>
                    <td class="text-center" <?= $sTitle ? 'title="' . $sTitle . '"' : '' ?>>
                      <?php if ($sItem !== null && $sItem['score'] !== null && $sItem['score'] !== ''): ?>
                        <?= is_numeric($sItem['score']) ? ((float)$sItem['score'] == (int)$sItem['score'] ? (int)$sItem['score'] : number_format((float)$sItem['score'], 1, ',', '.')) : esc($sItem['score']) ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  <?php endfor; ?>

                  <!-- RATA-RATA SUMATIF -->
                  <?php if ($sCount > 0): ?>
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
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>

                  <!-- FINAL (Semester 2) -->
                  <?php if ($s == 2 && $hasFinal): ?>
                    <td class="text-center">
                      <?= $row['final'] !== null ? number_format((float)$row['final'], 2, ',', '.') : '-' ?>
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
