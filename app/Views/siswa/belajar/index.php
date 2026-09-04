<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-mortarboard me-2 text-primary"></i>Proses Belajar
  </h5>
  <?php if ($activeYear): ?>
    <span class="badge bg-secondary"><?= esc($activeYear['year']) ?></span>
  <?php endif; ?>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-warning py-2 small"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (empty($subjects)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada mata pelajaran yang tersedia.</p>
  </div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($subjects as $sub): ?>
      <?php
        $pct   = (int) $sub['progress_pct'];
        $color = $pct >= 100 ? 'success' : ($pct >= 50 ? 'primary' : 'secondary');
      ?>
      <div class="col-sm-6 col-xl-4">
        <a href="<?= base_url('siswa/belajar/' . $sub['id']) ?>"
           class="card border-0 shadow-sm h-100 text-decoration-none text-dark belajar-card">
          <div class="card-body pb-2">

            <!-- Nama Mapel -->
            <h6 class="fw-bold mb-3">
              <i class="bi bi-journal-text me-2 text-primary"></i>
              <?= esc($sub['name']) ?>
            </h6>

            <!-- Statistik -->
            <div class="d-flex gap-3 mb-3" style="font-size:0.8rem;">
              <span class="text-muted">
                <i class="bi bi-folder2 me-1"></i><?= $sub['total_parents'] ?> Materi
              </span>
              <span class="text-muted">
                <i class="bi bi-journals me-1"></i><?= $sub['total_sub'] ?> Sub Materi
              </span>
            </div>

            <!-- Progress bar -->
            <?php if ($sub['total_sub'] > 0): ?>
              <div class="d-flex align-items-center gap-2" style="font-size:0.75rem;">
                <div class="progress flex-grow-1" style="height:6px;">
                  <div class="progress-bar bg-<?= $color ?>"
                       style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-<?= $color ?> fw-semibold flex-shrink-0">
                  <?= $sub['completed_sub'] ?>/<?= $sub['total_sub'] ?> selesai
                </span>
              </div>
            <?php else: ?>
              <div class="text-muted small">Belum ada sub materi.</div>
            <?php endif; ?>

          </div>
          <div class="card-footer bg-transparent border-top py-2">
            <span class="small text-primary fw-semibold">
              <i class="bi bi-arrow-right-circle me-1"></i>Mulai Belajar
            </span>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
.belajar-card { transition: transform .15s, box-shadow .15s; }
.belajar-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12) !important; }
</style>
<?= $this->endSection() ?>
