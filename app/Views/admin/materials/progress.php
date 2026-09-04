<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-bar-chart me-2 text-info"></i>Progress Materi &mdash; <?= esc($subject['name']) ?>
  </h5>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
  </a>
</div>

<!-- Filter Kelas -->
<?php if (!empty($classes)): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
  <span class="small fw-semibold text-muted">Pilih Kelas:</span>
  <?php foreach ($classes as $c): ?>
    <a href="?class_id=<?= $c['id'] ?>"
       class="btn btn-sm <?= $selectedClassId == $c['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
      <?= esc($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($materials)): ?>
  <div class="alert alert-info py-2 small">
    <?= empty($classes)
      ? 'Tidak ada kelas yang mengajarkan mata pelajaran ini.'
      : 'Belum ada materi yang dipublish untuk kelas ini.' ?>
  </div>
<?php else: ?>

<div class="row g-3">
  <?php foreach ($materials as $m): ?>
    <?php
      $prog      = $summary[$m['id']] ?? ['completed' => 0, 'in_progress' => 0, 'total_students' => 0];
      $total     = $prog['total_students'];
      $done      = $prog['completed'];
      $reading   = $prog['in_progress'];
      $unread    = max(0, $total - $done - $reading);
      $pct       = $total > 0 ? round(($done / $total) * 100) : 0;
      $icon      = \App\Models\SubjectMaterialModel::getContentTypeIcon($m['content_type'] ?? 'text');
      $semLabel  = $m['semester'] == 1 ? 'Ganjil' : 'Genap';
    ?>
    <div class="col-md-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge bg-secondary"><?= $semLabel ?></span>
            <span class="small text-muted"><i class="<?= $icon ?>"></i></span>
          </div>
          <h6 class="fw-semibold mb-1" style="font-size:0.85rem;"><?= esc($m['title']) ?></h6>

          <!-- Progress bar -->
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><?= $done ?>/<?= $total ?> selesai</span>
            <span><?= $pct ?>%</span>
          </div>
          <div class="progress mb-2" style="height:6px;">
            <div class="progress-bar bg-success"
                 role="progressbar"
                 style="width:<?= $pct ?>%"
                 aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <!-- Breakdown -->
          <div class="d-flex gap-2 flex-wrap" style="font-size:0.75rem;">
            <span class="text-success"><i class="bi bi-check2-circle"></i> <?= $done ?> selesai</span>
            <span class="text-warning"><i class="bi bi-eye"></i> <?= $reading ?> dibaca</span>
            <span class="text-secondary"><i class="bi bi-circle"></i> <?= $unread ?> belum</span>
          </div>

        </div>
        <div class="card-footer bg-transparent py-2">
          <a href="<?= site_url('admin/materials/progress-detail/' . $m['id'] . '?class_id=' . $selectedClassId . '&back_url=' . urlencode(site_url('admin/materials/progress/' . $subject['id'] . '?class_id=' . $selectedClassId))) ?>"
             class="btn btn-sm btn-outline-info w-100" style="font-size:0.78rem;">
            <i class="bi bi-table me-1"></i>Lihat per Siswa
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
