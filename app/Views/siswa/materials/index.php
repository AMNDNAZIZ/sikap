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

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-warning py-2 small"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (empty($grouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-book fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada materi yang tersedia.</p>
  </div>

<?php else: ?>

  <?php foreach ($grouped as $subjectId => $group): ?>
    <?php
      $items      = $group['items'];
      $completion = $group['completion'];
      $pct        = $completion['percent'];
      $done       = $completion['completed'];
      $total      = $completion['total'];
    ?>

    <div class="card border-0 shadow-sm mb-4">
      <!-- Header mapel -->
      <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-journal-text me-2 text-primary"></i><?= esc($group['name']) ?>
          </h6>
          <div class="d-flex align-items-center gap-3">
            <span class="small text-muted"><?= $done ?>/<?= $total ?> selesai</span>
            <div class="d-flex align-items-center gap-2">
              <div class="progress" style="width:100px; height:6px;">
                <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>"
                     role="progressbar" style="width:<?= $pct ?>%"></div>
              </div>
              <span class="small fw-semibold <?= $pct >= 100 ? 'text-success' : 'text-primary' ?>">
                <?= $pct ?>%
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Daftar materi -->
      <div class="list-group list-group-flush">
        <?php foreach ($items as $m): ?>
          <?php
            $prog     = $progressMap[$m['id']] ?? null;
            $status   = $prog['status'] ?? 'unread';
            $icon     = \App\Models\SubjectMaterialModel::getContentTypeIcon($m['content_type'] ?? 'text');
            $typeLabel= \App\Models\SubjectMaterialModel::getContentTypeLabel($m['content_type'] ?? 'text');
            $semLabel = $m['semester'] == 1 ? 'Ganjil' : 'Genap';
            $estMin   = $m['estimated_minutes'] ?? null;
          ?>
          <a href="<?= base_url('siswa/materials/' . $m['id']) ?>"
             class="list-group-item list-group-item-action px-4 py-3
                    <?= $status === 'unread' ? 'bg-light-subtle' : '' ?>">
            <div class="d-flex align-items-start gap-3">

              <!-- Status icon -->
              <span class="mt-1 flex-shrink-0" style="width:22px; text-align:center;">
                <?php if ($status === 'completed'): ?>
                  <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <?php elseif ($status === 'in_progress'): ?>
                  <i class="bi bi-play-circle-fill text-primary fs-5"></i>
                <?php else: ?>
                  <i class="bi bi-circle text-secondary fs-5"></i>
                <?php endif; ?>
              </span>

              <!-- Konten item -->
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                  <span class="fw-semibold" style="font-size:0.875rem;">
                    <?= esc($m['title']) ?>
                  </span>
                  <div class="d-flex gap-2 flex-shrink-0">
                    <span class="badge bg-light text-secondary border" style="font-size:0.68rem;">
                      <?= $semLabel ?>
                    </span>
                    <?php if ($status === 'completed'): ?>
                      <span class="badge bg-success-subtle text-success" style="font-size:0.68rem;">Selesai</span>
                    <?php elseif ($status === 'in_progress'): ?>
                      <span class="badge bg-primary-subtle text-primary" style="font-size:0.68rem;">Dibaca</span>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if (!empty($m['description'])): ?>
                  <div class="text-muted small text-truncate mt-1" style="max-width:480px;">
                    <?= esc($m['description']) ?>
                  </div>
                <?php endif; ?>

                <div class="d-flex gap-3 mt-1" style="font-size:0.73rem; color:#868e96;">
                  <span><i class="<?= $icon ?> me-1"></i><?= $typeLabel ?></span>
                  <?php if ($estMin): ?>
                    <span><i class="bi bi-clock me-1"></i><?= $estMin ?> menit</span>
                  <?php endif; ?>
                  <?php if ($prog && $prog['view_count'] > 0): ?>
                    <span><i class="bi bi-eye me-1"></i>Dibuka <?= $prog['view_count'] ?>x</span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Panah -->
              <i class="bi bi-chevron-right text-muted mt-1 flex-shrink-0"></i>

            </div>
          </a>
        <?php endforeach; ?>
      </div>

    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
