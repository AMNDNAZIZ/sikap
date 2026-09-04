<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-book me-2 text-primary"></i>Materi Pelajaran
  </h5>
  <?php if ($activeYear): ?>
    <span class="badge bg-secondary"><?= esc($activeYear['year']) ?></span>
  <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($subjects)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-book fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Tidak ada mata pelajaran yang dapat dikelola materinya.</p>
    <p class="small text-muted">Pastikan sudah ada penugasan mengajar di tahun ajaran aktif.</p>
  </div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($subjects as $s): ?>
      <?php
        $totalMateri    = (int) ($s['total_materi']    ?? 0);
        $totalPublished = (int) ($s['total_published'] ?? 0);
        $totalDraft     = $totalMateri - $totalPublished;
        $totalClasses   = (int) ($s['total_classes']   ?? 0);
      ?>
      <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <!-- Nama mapel -->
            <h6 class="fw-bold mb-3">
              <i class="bi bi-journal-text me-2 text-primary"></i>
              <?= esc($s['name']) ?>
            </h6>

            <!-- Statistik -->
            <div class="d-flex gap-3 mb-3" style="font-size:0.8rem;">
              <span class="text-muted">
                <i class="bi bi-files me-1"></i><?= $totalMateri ?> materi
              </span>
              <?php if ($totalPublished > 0): ?>
                <span class="text-success">
                  <i class="bi bi-check-circle me-1"></i><?= $totalPublished ?> published
                </span>
              <?php endif; ?>
              <?php if ($totalDraft > 0): ?>
                <span class="text-warning">
                  <i class="bi bi-pencil me-1"></i><?= $totalDraft ?> draft
                </span>
              <?php endif; ?>
              <span class="text-secondary">
                <i class="bi bi-people me-1"></i><?= $totalClasses ?> kelas
              </span>
            </div>

            <!-- Progress bar materi published -->
            <?php if ($totalMateri > 0): ?>
              <div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.75rem;">
                <div class="progress flex-grow-1" style="height:5px;">
                  <div class="progress-bar bg-success"
                       role="progressbar"
                       style="width:<?= round($totalPublished / $totalMateri * 100) ?>%">
                  </div>
                </div>
                <span class="text-muted">
                  <?= round($totalPublished / $totalMateri * 100) ?>% published
                </span>
              </div>
            <?php endif; ?>
          </div>

          <div class="card-footer bg-transparent border-top py-2 d-flex gap-2">
            <a href="<?= base_url('admin/materials/' . $s['id']) ?>"
               class="btn btn-sm btn-primary flex-grow-1">
              <i class="bi bi-pencil me-1"></i>Kelola Materi
            </a>
            <a href="<?= base_url('admin/materials/progress/' . $s['id']) ?>"
               class="btn btn-sm btn-outline-info" title="Lihat Progress Siswa">
              <i class="bi bi-bar-chart"></i>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?= $this->endSection() ?>
