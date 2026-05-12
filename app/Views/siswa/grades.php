<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📊 Nilai Saya - <?= esc($student['name']) ?> (<?= esc($student['class_name']) ?>)</h3>
<p>Tahun Ajaran: <?= esc($activeYear['year']) ?></p>

<ul class="nav nav-tabs mb-3" id="semesterTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#sem1">Semester 1</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#sem2">Semester 2</a>
  </li>
</ul>

<div class="tab-content">
  <?php foreach ([1,2] as $sem): ?>
    <div class="tab-pane fade <?= $sem==1?'show active':'' ?>" id="sem<?= $sem ?>">
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Mata Pelajaran</th>
              <th>Formatif</th>
              <th>Sumatif</th>
              <?php if ($isFinalClass && $sem==2): ?>
                <th>Nilai Akhir</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($gradesData as $g): ?>
              <tr>
                <td><?= esc($g['subject']) ?></td>
                <td>
                  <?php 
                    $fScores = array_filter($g['formatif'], fn($f) => $f['semester']==$sem);
                    if ($fScores): 
                      foreach ($fScores as $fs): ?>
                        <span class="badge bg-primary me-1"><?= esc($fs['score'] ?? '-') ?></span>
                      <?php endforeach;
                    else: echo '<span class="text-muted">-</span>'; endif;
                  ?>
                </td>
                <td><?= $g['sumatif'][$sem] ?? '-' ?></td>
                <?php if ($isFinalClass && $sem==2): ?>
                  <td><?= $g['final'] ?? '-' ?></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
