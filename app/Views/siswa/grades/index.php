<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
  <h3>📊 Nilai Siswa</h3>

  <table class="table table-sm w-auto mb-3">
    <tr><th>Nama Siswa</th><td><?= esc($student['name']) ?></td></tr>
    <tr><th>Kelas</th><td><?= esc($student['class_name']) ?></td></tr>
    <tr><th>Tahun Ajaran</th><td><?= esc($activeYear['year']) ?></td></tr>
  </table>

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
          <table class="table table-bordered align-middle">

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
