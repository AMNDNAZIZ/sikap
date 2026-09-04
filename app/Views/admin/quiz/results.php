<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-bar-chart me-2 text-info"></i><?= esc($title) ?>
  </h5>
  <a href="<?= site_url('admin/quiz') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<!-- Info kuis -->
<div class="card border-0 shadow-sm mb-4 bg-light-subtle">
  <div class="card-body py-3">
    <div class="row g-2 small">
      <div class="col-6 col-md-3"><span class="text-muted">Mapel</span><br><strong><?= esc($quiz['subject_name'] ?? '-') ?></strong></div>
      <div class="col-6 col-md-3"><span class="text-muted">Bank Soal</span><br><strong><?= esc($quiz['bank_code'] ?? '-') ?></strong></div>
      <div class="col-6 col-md-3"><span class="text-muted">Durasi</span><br><strong><?= $quiz['duration'] > 0 ? $quiz['duration'].' menit' : 'Tidak terbatas' ?></strong></div>
      <div class="col-6 col-md-3"><span class="text-muted">Max Pengulangan</span><br><strong><?= $quiz['max_attempts'] > 0 ? $quiz['max_attempts'].'x' : 'Tidak terbatas' ?></strong></div>
    </div>
  </div>
</div>

<!-- Statistik agregat -->
<div class="row g-3 mb-4">
  <?php
    $cards = [
      ['label'=>'Siswa Mengerjakan', 'val'=>count($summary),  'color'=>'primary',  'icon'=>'bi-people'],
      ['label'=>'Rata-rata Nilai',   'val'=>$avgScore,        'color'=>'info',     'icon'=>'bi-graph-up'],
      ['label'=>'Nilai Tertinggi',   'val'=>$maxScore,        'color'=>'success',  'icon'=>'bi-trophy'],
      ['label'=>'Nilai Terendah',    'val'=>$minScore,        'color'=>'warning',  'icon'=>'bi-exclamation-circle'],
    ];
  ?>
  <?php foreach ($cards as $c): ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <i class="<?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 mb-1"></i>
        <div class="fw-bold fs-5"><?= $c['val'] ?></div>
        <div class="small text-muted"><?= $c['label'] ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Tabel hasil per siswa -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small" id="resultsTable">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Nama Siswa</th>
            <th width="80">NIS</th>
            <th class="text-center" width="90">Nilai Terbaik</th>
            <th class="text-center" width="80">Rata-rata</th>
            <th class="text-center" width="70">Attempts</th>
            <th width="130">Terakhir Kerjakan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($summary)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada siswa yang mengerjakan kuis ini.</td></tr>
          <?php else: ?>
            <?php $no = 1; foreach ($summary as $s): ?>
              <?php
                $best = (float) ($s['best_score'] ?? 0);
                $color = $best >= 80 ? 'success' : ($best >= 60 ? 'warning' : 'danger');
              ?>
              <tr>
                <td class="text-muted"><?= $no++ ?></td>
                <td class="fw-semibold"><?= esc($s['student_name']) ?></td>
                <td class="text-muted"><?= esc($s['nis'] ?? '-') ?></td>
                <td class="text-center">
                  <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> fw-bold" style="font-size:0.82rem;">
                    <?= number_format($best, 1) ?>
                  </span>
                </td>
                <td class="text-center text-muted"><?= number_format((float)($s['avg_score'] ?? 0), 1) ?></td>
                <td class="text-center"><?= $s['total_attempts'] ?>x</td>
                <td class="text-muted">
                  <?= $s['last_attempt_at']
                      ? date('d M Y H:i', (int)$s['last_attempt_at'])
                      : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
$(function(){
  if($.fn.DataTable){
    $('#resultsTable').DataTable({ order:[[3,'desc']], pageLength:25 });
  }
});
</script>
<?= $this->endSection() ?>
