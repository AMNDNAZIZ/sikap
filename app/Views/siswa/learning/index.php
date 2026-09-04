<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $isTeacherView = $viewAsTeacher ?? false;
  $overall       = $overallStats;
  $attRate       = $overall['att_rate'];
  $attColor      = $attRate >= 80 ? 'success' : ($attRate >= 60 ? 'warning' : 'danger');
  $matColor      = fn($v) => $v === null ? 'secondary' : ($v >= 80 ? 'success' : ($v >= 50 ? 'warning' : 'danger'));
  $scoreColor    = fn($v) => $v === null ? 'secondary' : ($v >= 80 ? 'success' : ($v >= 60 ? 'warning' : 'danger'));
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-graph-up me-2 text-primary"></i>
      <?php if ($isTeacherView): ?>
        Analitik: <?= esc($studentInfo['name'] ?? '') ?>
      <?php else: ?>
        Analitik Belajarku
      <?php endif; ?>
    </h5>
    <div class="text-muted small">
      <?= esc($className) ?> &nbsp;·&nbsp; <?= esc($activeYear['year'] ?? '') ?>
      &nbsp;·&nbsp; <?= esc($monthLabel) ?>
    </div>
  </div>
  <?php if ($isTeacherView): ?>
    <a href="<?= base_url('learning/class') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke Kelas
    </a>
  <?php endif; ?>
</div>

<!-- ── 4 Kartu Ringkasan ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

  <!-- Kehadiran -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-<?= $attColor ?>-subtle"
             style="width:52px;height:52px;">
          <i class="bi bi-calendar2-check text-<?= $attColor ?> fs-4"></i>
        </div>
        <div class="fw-bold fs-5 text-<?= $attColor ?>"><?= $attRate ?>%</div>
        <div class="text-muted small">Kehadiran</div>
        <div class="mt-1 d-flex justify-content-center gap-2" style="font-size:0.7rem;">
          <span class="text-success"><?= $overall['att_hadir'] ?>H</span>
          <span class="text-warning"><?= $overall['att_sakit'] ?>S</span>
          <span class="text-info"><?= $overall['att_izin'] ?>I</span>
          <span class="text-danger"><?= $overall['att_alpha'] ?>A</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Materi -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <?php $mc = $matColor($overall['mat_avg_pct']); ?>
        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-<?= $mc ?>-subtle"
             style="width:52px;height:52px;">
          <i class="bi bi-book text-<?= $mc ?> fs-4"></i>
        </div>
        <div class="fw-bold fs-5 text-<?= $mc ?>">
          <?= $overall['mat_avg_pct'] !== null ? $overall['mat_avg_pct'].'%' : '—' ?>
        </div>
        <div class="text-muted small">Materi Dibaca</div>
        <div class="text-muted mt-1" style="font-size:0.7rem;">rata-rata per mapel</div>
      </div>
    </div>
  </div>

  <!-- Nilai Kuis -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <?php $qc = $scoreColor($overall['quiz_avg']); ?>
        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-<?= $qc ?>-subtle"
             style="width:52px;height:52px;">
          <i class="bi bi-pencil-square text-<?= $qc ?> fs-4"></i>
        </div>
        <div class="fw-bold fs-5 text-<?= $qc ?>">
          <?= $overall['quiz_avg'] !== null ? number_format($overall['quiz_avg'],1) : '—' ?>
        </div>
        <div class="text-muted small">Rata-rata Kuis</div>
        <div class="text-muted mt-1" style="font-size:0.7rem;">nilai terbaik per kuis</div>
      </div>
    </div>
  </div>

  <!-- Nilai E-Rapor -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <?php $ec = $scoreColor($overall['erapor_avg']); ?>
        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-<?= $ec ?>-subtle"
             style="width:52px;height:52px;">
          <i class="bi bi-award text-<?= $ec ?> fs-4"></i>
        </div>
        <div class="fw-bold fs-5 text-<?= $ec ?>">
          <?= $overall['erapor_avg'] !== null ? number_format($overall['erapor_avg'],1) : '—' ?>
        </div>
        <div class="text-muted small">Rata-rata Rapor</div>
        <div class="text-muted mt-1" style="font-size:0.7rem;">semester ini</div>
      </div>
    </div>
  </div>

</div>

<!-- ── Trend Kuis (grafik mini) ──────────────────────────────────────── -->
<?php if (!empty($quizTrend)): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
    <span class="fw-semibold small"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Tren Nilai Kuis (10 Terakhir)</span>
  </div>
  <div class="card-body py-3">
    <canvas id="quizTrendChart" height="80"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- ── Tabel per Mapel ────────────────────────────────────────────────── -->
<?php if (!empty($subjects)): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-2">
    <span class="fw-semibold small"><i class="bi bi-table me-2 text-secondary"></i>Ringkasan per Mata Pelajaran</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>Mata Pelajaran</th>
          <th class="text-center" width="120">Progress Materi</th>
          <th class="text-center" width="100">Nilai Kuis</th>
          <th class="text-center" width="100">Nilai Rapor</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $sub): ?>
          <?php
            $sid   = $sub['id'];
            $mat   = $materialsData[$sid]  ?? null;
            $quiz  = $quizSummary[$sid]    ?? null;
            $rapor = $eraporData[$sid]     ?? null;
            $mc2   = $mat  ? $matColor($mat['pct'])           : 'secondary';
            $qc2   = $quiz ? $scoreColor($quiz['best'])        : 'secondary';
            $ec2   = $rapor? $scoreColor((float)$rapor['erapor_score']) : 'secondary';
          ?>
          <tr>
            <td class="fw-semibold"><?= esc($sub['name']) ?></td>

            <!-- Materi -->
            <td>
              <?php if ($mat && $mat['total'] > 0): ?>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:6px;">
                    <div class="progress-bar bg-<?= $mc2 ?>" style="width:<?= $mat['pct'] ?>%"></div>
                  </div>
                  <span class="text-<?= $mc2 ?> fw-semibold" style="font-size:0.75rem; white-space:nowrap;">
                    <?= $mat['completed'] ?>/<?= $mat['total'] ?>
                  </span>
                </div>
                <div class="text-muted" style="font-size:0.68rem; text-align:right;"><?= $mat['pct'] ?>%</div>
              <?php elseif ($mat && $mat['total'] === 0): ?>
                <span class="text-muted small">Belum ada materi</span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <!-- Kuis -->
            <td class="text-center">
              <?php if ($quiz && $quiz['best'] !== null): ?>
                <span class="fw-bold text-<?= $qc2 ?>"><?= number_format($quiz['best'],1) ?></span>
                <div class="text-muted" style="font-size:0.68rem;"><?= $quiz['attempts'] ?>x kerjakan</div>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <!-- Rapor -->
            <td class="text-center">
              <?php if ($rapor && $rapor['erapor_score'] !== null): ?>
                <span class="fw-bold text-<?= $ec2 ?>"><?= number_format((float)$rapor['erapor_score'],1) ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── Detail Materi per Mapel (accordion) ───────────────────────────── -->
<?php foreach ($subjects as $sub):
  $sid  = $sub['id'];
  $mat  = $materialsData[$sid] ?? null;
  if (!$mat || $mat['total'] === 0) continue;
  $colId = 'mat_' . $sid;
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white py-2" style="cursor:pointer;"
       data-bs-toggle="collapse" data-bs-target="#<?= $colId ?>">
    <div class="d-flex justify-content-between align-items-center">
      <span class="fw-semibold small">
        <i class="bi bi-book me-2 text-primary"></i><?= esc($sub['name']) ?>
      </span>
      <div class="d-flex align-items-center gap-3">
        <div class="progress" style="width:80px; height:5px;">
          <div class="progress-bar bg-<?= $matColor($mat['pct']) ?>"
               style="width:<?= $mat['pct'] ?? 0 ?>%"></div>
        </div>
        <span class="small text-muted"><?= $mat['completed'] ?>/<?= $mat['total'] ?></span>
        <i class="bi bi-chevron-down text-muted small"></i>
      </div>
    </div>
  </div>
  <div class="collapse" id="<?= $colId ?>">
    <div class="list-group list-group-flush">
      <?php foreach ($mat['items'] as $m):
        $prog    = $mat['progress_map'][$m['id']] ?? null;
        $status  = $prog['status'] ?? 'unread';
        $icon    = match($status) {
          'completed'  => 'bi-check-circle-fill text-success',
          'in_progress'=> 'bi-play-circle-fill text-primary',
          default      => 'bi-circle text-secondary',
        };
        $label   = match($status) {
          'completed'  => '<span class="badge bg-success-subtle text-success" style="font-size:0.65rem;">Selesai</span>',
          'in_progress'=> '<span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem;">Dibaca</span>',
          default      => '',
        };
      ?>
        <a href="<?= base_url('siswa/materials/' . $m['id']) ?>"
           class="list-group-item list-group-item-action px-4 py-2 d-flex align-items-center gap-3 small">
          <i class="<?= $icon ?> flex-shrink-0"></i>
          <span class="flex-grow-1"><?= esc($m['title']) ?></span>
          <?= $label ?>
          <?php if ($status === 'completed' && !empty($prog['completed_at'])): ?>
            <span class="text-muted flex-shrink-0" style="font-size:0.68rem;">
              <?= date('d M', strtotime($prog['completed_at'])) ?>
            </span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (!empty($quizTrend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
  var labels  = <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['finished_at'])), $quizTrend)) ?>;
  var scores  = <?= json_encode(array_map(fn($r) => round((float)$r['total_score'],1), $quizTrend)) ?>;
  var titles  = <?= json_encode(array_map(fn($r) => $r['quiz_title'].' ('.$r['subject_name'].')', $quizTrend)) ?>;

  new Chart(document.getElementById('quizTrendChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nilai Kuis',
        data: scores,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,0.08)',
        pointBackgroundColor: scores.map(s => s>=80?'#198754':(s>=60?'#ffc107':'#dc3545')),
        pointRadius: 5,
        tension: 0.3,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: (ctx) => titles[ctx[0].dataIndex],
            label:  (ctx) => 'Nilai: ' + ctx.parsed.y
          }
        }
      },
      scales: {
        y: { min:0, max:100, ticks:{ font:{size:11} }, grid:{ color:'#f0f0f0' } },
        x: { ticks:{ font:{size:11} }, grid:{ display:false } }
      }
    }
  });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
