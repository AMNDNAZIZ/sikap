<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-table me-2 text-info"></i>Detail Progress: <?= esc($material['title'] ?? '') ?>
  </h5>
  <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<!-- Filter kelas (tampil jika ada lebih dari 1 kelas publish) -->
<?php if (!empty($publishedClasses) && count($publishedClasses) > 1): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
  <span class="small fw-semibold text-muted">Pilih Kelas:</span>
  <?php foreach ($publishedClasses as $c): ?>
    <a href="?class_id=<?= $c['id'] ?>&back_url=<?= urlencode($backUrl) ?>"
       class="btn btn-sm <?= $classId == $c['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
      <?= esc($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>
<?php elseif (!empty($publishedClasses)): ?>
<div class="mb-3 small text-muted">
  <i class="bi bi-building me-1"></i>Kelas: <strong><?= esc($publishedClasses[0]['name']) ?></strong>
</div>
<?php endif; ?>

<?php if (empty($publishedClasses)): ?>
  <div class="alert alert-warning border-0 py-2 small">
    Sub materi ini belum dipublish ke kelas manapun.
  </div>
<?php else: ?>

<?php
  $done    = count(array_filter($students, fn($s) => $s['status'] === 'completed'));
  $reading = count(array_filter($students, fn($s) => $s['status'] === 'in_progress'));
  $total   = count($students);
  $unread  = $total - $done - $reading;
  $pct     = $total > 0 ? round(($done / $total) * 100) : 0;
?>

<!-- Ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 bg-light text-center py-3">
      <div class="fw-bold fs-4 text-success"><?= $done ?></div>
      <div class="small text-muted">Selesai</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 bg-light text-center py-3">
      <div class="fw-bold fs-4 text-warning"><?= $reading ?></div>
      <div class="small text-muted">Sedang Baca</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 bg-light text-center py-3">
      <div class="fw-bold fs-4 text-secondary"><?= $unread ?></div>
      <div class="small text-muted">Belum Buka</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 bg-light text-center py-3">
      <div class="fw-bold fs-4 text-primary"><?= $pct ?>%</div>
      <div class="small text-muted">Selesai Baca</div>
    </div>
  </div>
</div>

<!-- Tabel per siswa -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th width="40">#</th>
            <th>Nama Siswa</th>
            <th width="70">NIS</th>
            <th width="110" class="text-center">Status</th>
            <th width="140">Pertama Dibuka</th>
            <th width="140">Selesai Pada</th>
            <th width="70" class="text-center">Dibuka</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <?= $classId ? 'Tidak ada data siswa untuk kelas ini.' : 'Pilih kelas di atas untuk melihat data.' ?>
              </td>
            </tr>
          <?php else: ?>
            <?php $no = 1; foreach ($students as $s): ?>
              <tr>
                <td class="text-muted"><?= $no++ ?></td>
                <td><?= esc($s['student_name']) ?></td>
                <td><?= esc($s['nis'] ?? '-') ?></td>
                <td class="text-center">
                  <?php if ($s['status'] === 'completed'): ?>
                    <span class="badge bg-success">Selesai</span>
                  <?php elseif ($s['status'] === 'in_progress'): ?>
                    <span class="badge bg-warning text-dark">Dibaca</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Belum</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted">
                  <?= $s['opened_at'] ? date('d M Y H:i', strtotime($s['opened_at'])) : '—' ?>
                </td>
                <td class="text-muted">
                  <?= $s['completed_at'] ? date('d M Y H:i', strtotime($s['completed_at'])) : '—' ?>
                </td>
                <td class="text-center">
                  <?= $s['view_count'] > 0 ? $s['view_count'] . 'x' : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
