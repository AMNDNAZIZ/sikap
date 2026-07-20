<?php
/**
 * Partial View: Statistik Siswa per Kelas
 * Variables needed:
 *   $studentStatsPerClass   - array of class stats
 *   $academicYears          - list of all academic years for the filter
 *   $statsAcademicYearId    - currently selected academic year id
 *   $statsActiveYear        - currently selected academic year data
 */
$filterYearId = $statsAcademicYearId ?? ($statsActiveYear['id'] ?? null);
?>

<div class="card border-0 shadow-sm rounded-4 mb-4" id="studentStatsCard">
  <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <h5 class="mb-0 fw-bold">
          <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
          Statistik Siswa per Kelas
        </h5>
        <small class="text-muted">
          Tahun Ajaran: <strong><?= esc($statsActiveYear['year'] ?? '-') ?></strong>
          <?php if (!empty($statsActiveYear['semester'])): ?>
            &nbsp;&bull;&nbsp;<?= esc($statsActiveYear['semester']) ?>
          <?php endif; ?>
        </small>
      </div>

      <!-- Academic Year Filter -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <form method="get" action="" class="d-flex align-items-center gap-2">
          <!-- Preserve other GET params -->
          <?php foreach ($_GET as $k => $v): ?>
            <?php if ($k !== 'stats_year_id'): ?>
              <input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>">
            <?php endif; ?>
          <?php endforeach; ?>

          <label class="form-label mb-0 small text-muted fw-semibold text-nowrap">
            <i class="bi bi-funnel me-1"></i>Filter T.A.:
          </label>
          <select name="stats_year_id"
                  class="form-select form-select-sm rounded-pill"
                  style="min-width:170px;"
                  onchange="this.form.submit()">
            <?php foreach ($academicYears as $ay): ?>
              <option value="<?= $ay['id'] ?>"
                <?= ($ay['id'] == $filterYearId) ? 'selected' : '' ?>>
                <?= esc($ay['year']) ?>
                <?= $ay['is_active'] ? ' ✓ (Aktif)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <?php if (!empty($studentStatsPerClass)): ?>
          <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
            Total: <?= number_format(array_sum(array_column($studentStatsPerClass, 'total'))) ?> siswa
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card-body p-0">
    <?php if (empty($studentStatsPerClass)): ?>
      <div class="p-5 text-center text-muted">
        <i class="bi bi-people fs-1 opacity-25 d-block mb-3"></i>
        <h6 class="fw-semibold">Belum ada data siswa</h6>
        <p class="mb-0 small">Tidak ada data siswa aktif untuk tahun ajaran yang dipilih.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tblStudentStats">
          <thead class="table-light">
            <tr>
              <th class="ps-4 py-3" style="width:40px">#</th>
              <th class="py-3">Kelas</th>
              <th class="py-3 text-center">
                <span class="badge bg-primary rounded-pill px-3">Total</span>
              </th>
              <th class="py-3 text-center">
                <span class="badge bg-info rounded-pill px-3">
                  <i class="bi bi-gender-male me-1"></i>L
                </span>
              </th>
              <th class="py-3 text-center">
                <span class="badge bg-pink rounded-pill px-3" style="background:#e91e8c!important">
                  <i class="bi bi-gender-female me-1"></i>P
                </span>
              </th>
              <th class="py-3 text-center pe-4" style="min-width:140px">Komposisi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $grandTotal = 0;
            $grandL = 0;
            $grandP = 0;
            $rowNum = 0;
            foreach ($studentStatsPerClass as $row):
              $rowNum++;
              $total = (int)$row['total'];
              $l     = (int)$row['total_l'];
              $p     = (int)$row['total_p'];
              $pctL  = $total > 0 ? round(($l / $total) * 100) : 0;
              $pctP  = $total > 0 ? round(($p / $total) * 100) : 0;
              $grandTotal += $total;
              $grandL     += $l;
              $grandP     += $p;
            ?>
            <tr>
              <td class="ps-4 text-muted small"><?= $rowNum ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-2 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                       style="width:34px;height:34px;flex-shrink:0">
                    <i class="bi bi-door-closed text-primary small"></i>
                  </div>
                  <div>
                    <span class="fw-semibold"><?= esc($row['class_name']) ?></span>
                    <?php if (!empty($row['teacher_name'])): ?>
                      <br><small class="text-muted"><i class="bi bi-person me-1"></i><?= esc($row['teacher_name']) ?></small>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="text-center">
                <span class="fw-bold fs-6 text-dark"><?= $total ?></span>
              </td>
              <td class="text-center">
                <span class="badge bg-info text-white rounded-pill px-3"><?= $l ?></span>
              </td>
              <td class="text-center">
                <span class="badge rounded-pill px-3 text-white" style="background:#e91e8c"><?= $p ?></span>
              </td>
              <td class="pe-4">
                <?php if ($total > 0): ?>
                  <div class="d-flex align-items-center gap-1 mb-1">
                    <small class="text-info fw-semibold" style="width:32px"><?= $pctL ?>%</small>
                    <div class="progress flex-grow-1" style="height:8px;border-radius:4px;overflow:hidden;">
                      <div class="progress-bar bg-info" style="width:<?= $pctL ?>%"></div>
                      <div class="progress-bar" style="width:<?= $pctP ?>%;background:#e91e8c"></div>
                    </div>
                    <small class="fw-semibold" style="width:32px;color:#e91e8c"><?= $pctP ?>%</small>
                  </div>
                  <div class="d-flex justify-content-between">
                    <small class="text-muted" style="font-size:.7rem">L: <?= $l ?></small>
                    <small class="text-muted" style="font-size:.7rem">P: <?= $p ?></small>
                  </div>
                <?php else: ?>
                  <small class="text-muted">-</small>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light border-top">
            <tr>
              <td class="ps-4" colspan="2">
                <span class="fw-bold text-dark">
                  <i class="bi bi-sigma me-1 text-primary"></i>Total Keseluruhan
                </span>
              </td>
              <td class="text-center">
                <span class="fw-bold fs-6 text-primary"><?= number_format($grandTotal) ?></span>
              </td>
              <td class="text-center">
                <span class="badge bg-info text-white rounded-pill px-3 fw-bold"><?= $grandL ?></span>
              </td>
              <td class="text-center">
                <span class="badge rounded-pill px-3 text-white fw-bold" style="background:#e91e8c"><?= $grandP ?></span>
              </td>
              <td class="pe-4">
                <?php if ($grandTotal > 0):
                  $gPctL = round(($grandL / $grandTotal) * 100);
                  $gPctP = round(($grandP / $grandTotal) * 100);
                ?>
                  <div class="d-flex align-items-center gap-1 mb-1">
                    <small class="text-info fw-bold" style="width:32px"><?= $gPctL ?>%</small>
                    <div class="progress flex-grow-1" style="height:8px;border-radius:4px;overflow:hidden;">
                      <div class="progress-bar bg-info" style="width:<?= $gPctL ?>%"></div>
                      <div class="progress-bar" style="width:<?= $gPctP ?>%;background:#e91e8c"></div>
                    </div>
                    <small class="fw-bold" style="width:32px;color:#e91e8c"><?= $gPctP ?>%</small>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($studentStatsPerClass)): ?>
  <div class="card-footer bg-white border-0 py-3 px-4 rounded-bottom-4">
    <div class="d-flex flex-wrap gap-3 align-items-center">
      <div class="d-flex align-items-center gap-2">
        <span class="d-inline-block rounded-pill bg-info" style="width:14px;height:14px;"></span>
        <small class="text-muted">Laki-laki (L)</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="d-inline-block rounded-pill" style="width:14px;height:14px;background:#e91e8c"></span>
        <small class="text-muted">Perempuan (P)</small>
      </div>
      <div class="ms-auto">
        <small class="text-muted">
          <i class="bi bi-info-circle me-1"></i>
          Data siswa dengan status <strong>aktif</strong> pada tahun ajaran <strong><?= esc($statsActiveYear['year'] ?? '-') ?></strong>
        </small>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
