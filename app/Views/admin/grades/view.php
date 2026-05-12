<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
  <h3>📊 Daftar Nilai Siswa</h3>
  <p><strong>Tahun Ajaran:</strong> <?= esc($activeYear['year']) ?></p>

  <!-- Tabs Semester -->
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($semesters as $s): ?>
      <li class="nav-item">
        <a class="nav-link <?= $s == 1 ? 'active' : '' ?>" data-bs-toggle="tab" href="#sem<?= $s ?>">Semester <?= $s ?></a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="tab-content">
    <?php foreach ($semesters as $s): ?>
      <div class="tab-pane fade <?= $s == 1 ? 'show active' : '' ?>" id="sem<?= $s ?>">
        
        <a href="<?= site_url("admin/grades/pdf?semester={$s}&class_id=" . esc($_GET['class_id'] ?? '') . "&subject_id=" . esc($_GET['subject_id'] ?? '')) ?>"
           target="_blank"
           class="btn btn-danger mb-3">
          ⬇️ Download PDF Semester <?= $s ?>
        </a>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Nama Siswa</th>

                <?php if (!empty($allFormatifCols[$s])): ?>
                  <th colspan="<?= count($allFormatifCols[$s]) ?>" class="text-center">Nilai Formatif</th>
                <?php endif; ?>

                <?php if (!empty($allSumatifCols[$s])): ?>
                  <th colspan="<?= count($allSumatifCols[$s]) ?>" class="text-center">Nilai Sumatif</th>
                <?php endif; ?>

                <?php if ($s == 2 && $hasFinal): ?>
                  <th rowspan="2">Final</th>
                <?php endif; ?>
              </tr>
              <tr>
                <?php if (!empty($allFormatifCols[$s])): ?>
                  <?php foreach ($allFormatifCols[$s] as $label): ?>
                    <th><?= esc($label) ?></th>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($allSumatifCols[$s])): ?>
                  <?php foreach ($allSumatifCols[$s] as $label): ?>
                    <th><?= esc($label) ?></th>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tr>
            </thead>

            <tbody>
              <?php $no=1; foreach ($students as $st): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($st['name']) ?></td>

                  <?php if (!empty($allFormatifCols[$s])): ?>
                    <?php foreach ($allFormatifCols[$s] as $colKey => $label): ?>
                      <td class="text-center"><?= esc($grades[$s][$st['id']]['formatif'][$colKey] ?? '-') ?></td>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if (!empty($allSumatifCols[$s])): ?>
                    <?php foreach ($allSumatifCols[$s] as $colKey => $label): ?>
                      <td class="text-center"><?= esc($grades[$s][$st['id']]['sumatif'][$colKey] ?? '-') ?></td>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if ($s == 2 && $hasFinal): ?>
                    <td class="text-center"><?= esc($grades[$s][$st['id']]['final'] ?? '-') ?></td>
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
