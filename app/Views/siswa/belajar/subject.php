<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb mb-0" style="font-size:0.8rem;">
    <li class="breadcrumb-item"><a href="<?= base_url('siswa/belajar') ?>">Proses Belajar</a></li>
    <li class="breadcrumb-item active"><?= esc($subject['name'] ?? '') ?></li>
  </ol>
</nav>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-journal-text me-2 text-primary"></i>
      <?= esc($subject['name'] ?? '') ?>
    </h5>
    <div class="text-muted small"><?= esc($activeYear['year'] ?? '') ?></div>
  </div>
  <!-- Progress keseluruhan -->
  <?php if ($totalSub > 0): ?>
    <div class="d-flex align-items-center gap-2">
      <div class="progress" style="width:120px; height:6px;">
        <div class="progress-bar <?= $progressPct >= 100 ? 'bg-success' : 'bg-primary' ?>"
             style="width:<?= $progressPct ?>%"></div>
      </div>
      <span class="small fw-semibold <?= $progressPct >= 100 ? 'text-success' : 'text-primary' ?>">
        <?= $doneSub ?>/<?= $totalSub ?> selesai
      </span>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($hierarchy)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-book fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada materi yang tersedia.</p>
  </div>
<?php else: ?>

  <?php foreach ($hierarchy as $pIdx => $parent): ?>
    <?php
      $children       = $parent['children'] ?? [];
      $totalChild     = count($children);
      $doneChild      = count(array_filter($children, fn($c) =>
                            ($progressMap[$c['id']]['status'] ?? '') === 'completed'));
      $pct            = $totalChild > 0 ? round($doneChild / $totalChild * 100) : 0;
      $semLabel       = $parent['semester'] == 1 ? 'Ganjil' : 'Genap';
    ?>

    <div class="card border-0 shadow-sm mb-4">

      <!-- ── Header Materi induk ── -->
      <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center
                         justify-content-center flex-shrink-0"
                  style="width:30px; height:30px; font-size:0.8rem;">
              <?= $pIdx + 1 ?>
            </span>
            <div>
              <h6 class="fw-bold mb-0"><?= esc($parent['title']) ?></h6>
              <span class="text-muted" style="font-size:0.73rem;">
                Semester <?= $semLabel ?>
                &nbsp;&middot;&nbsp;<?= $totalChild ?> Sub Materi
              </span>
            </div>
          </div>
          <!-- Progress Materi -->
          <?php if ($totalChild > 0): ?>
            <div class="d-flex align-items-center gap-2">
              <div class="progress" style="width:80px; height:5px;">
                <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>"
                     style="width:<?= $pct ?>%"></div>
              </div>
              <span class="text-muted" style="font-size:0.73rem;"><?= $doneChild ?>/<?= $totalChild ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Daftar Sub Materi (pertemuan) ── -->
      <?php if (empty($children)): ?>
        <div class="card-body text-muted small py-3 text-center">
          Belum ada sub materi untuk materi ini.
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($children as $cIdx => $child): ?>
            <?php
              $prog       = $progressMap[$child['id']] ?? null;
              $status     = $prog['status'] ?? 'unread';
              $isCompleted = $status === 'completed';
              $isReading   = $status === 'in_progress';
              $discCount  = $discussionCounts[$child['id']] ?? 0;
              $quizCount  = $quizCounts[$child['id']] ?? 0;
              $icon       = \App\Models\SubjectMaterialModel::getContentTypeIcon($child['content_type'] ?? 'text');
              $typeLabel  = \App\Models\SubjectMaterialModel::getContentTypeLabel($child['content_type'] ?? 'text');
              $estMin     = $child['estimated_minutes'] ?? null;
            ?>
            <a href="<?= base_url('siswa/belajar/sub/' . $child['id']) ?>"
               class="list-group-item list-group-item-action px-4 py-3
                      <?= $isCompleted ? '' : ($isReading ? 'border-start border-3 border-primary' : '') ?>">
              <div class="d-flex align-items-start gap-3">

                <!-- Status ikon -->
                <span class="flex-shrink-0 mt-1" style="width:24px; text-align:center;">
                  <?php if ($isCompleted): ?>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                  <?php elseif ($isReading): ?>
                    <i class="bi bi-play-circle-fill text-primary fs-5"></i>
                  <?php else: ?>
                    <i class="bi bi-circle text-secondary fs-5"></i>
                  <?php endif; ?>
                </span>

                <!-- Info -->
                <div class="flex-grow-1 overflow-hidden">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-1 mb-1">
                    <span class="fw-semibold" style="font-size:0.875rem;">
                      Pertemuan <?= $cIdx + 1 ?>: <?= esc($child['title']) ?>
                    </span>
                    <div class="d-flex gap-1 flex-shrink-0">
                      <?php if ($isCompleted): ?>
                        <span class="badge bg-success-subtle text-success" style="font-size:0.65rem;">Selesai</span>
                      <?php elseif ($isReading): ?>
                        <span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem;">Sedang dibaca</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Meta info -->
                  <div class="d-flex flex-wrap gap-3" style="font-size:0.72rem; color:#6c757d;">
                    <span><i class="<?= $icon ?> me-1"></i><?= $typeLabel ?></span>
                    <?php if ($estMin): ?>
                      <span><i class="bi bi-clock me-1"></i><?= $estMin ?> menit</span>
                    <?php endif; ?>
                    <!-- Diskusi -->
                    <span class="<?= $discCount > 0 ? 'text-primary' : '' ?>">
                      <i class="bi bi-chat-dots me-1"></i>
                      <?= $discCount > 0 ? $discCount . ' diskusi' : 'Diskusi' ?>
                    </span>
                    <!-- Kuis -->
                    <?php if ($quizCount > 0): ?>
                      <span class="text-success fw-semibold">
                        <i class="bi bi-pencil-square me-1"></i><?= $quizCount ?> Kuis
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <i class="bi bi-chevron-right text-muted mt-1 flex-shrink-0"></i>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php endforeach; ?>

<?php endif; ?>

<div class="mt-2 mb-4">
  <a href="<?= base_url('siswa/belajar') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Mapel
  </a>
</div>

<?= $this->endSection() ?>
