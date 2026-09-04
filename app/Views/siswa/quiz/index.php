<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-pencil-square me-2 text-primary"></i>Kuis Mandiri
  </h5>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-warning py-2 small alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('info')): ?>
  <div class="alert alert-info py-2 small alert-dismissible fade show">
    <?= esc(session()->getFlashdata('info')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($grouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-pencil-square fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada kuis yang tersedia untuk kelasmu.</p>
  </div>

<?php else: ?>

  <?php foreach ($grouped as $subjectName => $quizzes): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0">
          <i class="bi bi-journal-bookmark me-2 text-primary"></i><?= esc($subjectName) ?>
        </h6>
      </div>

      <div class="list-group list-group-flush">
        <?php foreach ($quizzes as $q): ?>
          <?php
            $done        = $q['attempts_done'];
            $best        = $q['best_score'];
            $canRetry    = $q['can_retry'];
            $hasActive   = !empty($q['active_session']);
            $maxAttempts = (int) $q['max_attempts'];
            $totalSoal   = ((int)$q['show_pg_count'])
                         + ((int)$q['show_pgk_count'])
                         + ((int)$q['show_bs_count'])
                         + ((int)$q['show_esai_count']);

            // Warna nilai terbaik
            $scoreColor = 'secondary';
            if ($best !== null) {
              $scoreColor = $best >= 80 ? 'success' : ($best >= 60 ? 'warning' : 'danger');
            }
          ?>
          <div class="list-group-item px-4 py-3">
            <div class="d-flex align-items-start gap-3 flex-wrap flex-md-nowrap">

              <!-- Ikon status -->
              <span class="flex-shrink-0 mt-1" style="width:28px; text-align:center;">
                <?php if ($hasActive): ?>
                  <i class="bi bi-play-circle-fill text-primary fs-5" title="Sedang dikerjakan"></i>
                <?php elseif ($best !== null): ?>
                  <i class="bi bi-check-circle-fill text-<?= $scoreColor ?> fs-5" title="Sudah dikerjakan"></i>
                <?php else: ?>
                  <i class="bi bi-circle text-secondary fs-5" title="Belum dikerjakan"></i>
                <?php endif; ?>
              </span>

              <!-- Info kuis -->
              <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold" style="font-size:0.875rem;"><?= esc($q['title']) ?></div>
                <?php if (!empty($q['description'])): ?>
                  <div class="text-muted small text-truncate" style="max-width:420px;">
                    <?= esc($q['description']) ?>
                  </div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.73rem; color:#6c757d;">
                  <span><i class="bi bi-question-circle me-1"></i><?= $totalSoal ?> soal</span>
                  <?php if ((int)$q['duration'] > 0): ?>
                    <span><i class="bi bi-clock me-1"></i><?= $q['duration'] ?> menit</span>
                  <?php else: ?>
                    <span><i class="bi bi-infinity me-1"></i>Tidak terbatas</span>
                  <?php endif; ?>
                  <?php if ($maxAttempts > 0): ?>
                    <span><i class="bi bi-arrow-repeat me-1"></i>Maks <?= $maxAttempts ?>x pengulangan</span>
                  <?php endif; ?>
                  <?php if ($done > 0): ?>
                    <span><i class="bi bi-check2 me-1"></i>Sudah dikerjakan <?= $done ?>x</span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Nilai terbaik + tombol -->
              <div class="d-flex align-items-center gap-3 flex-shrink-0">
                <?php if ($best !== null): ?>
                  <div class="text-center">
                    <div class="fw-bold text-<?= $scoreColor ?>" style="font-size:1.25rem; line-height:1.2;">
                      <?= number_format($best, 1) ?>
                    </div>
                    <div class="text-muted" style="font-size:0.7rem;">nilai terbaik</div>
                  </div>
                <?php endif; ?>

                <?php if ($hasActive): ?>
                  <!-- Lanjutkan sesi aktif -->
                  <form method="post" action="<?= base_url('siswa/quiz/' . $q['id'] . '/mulai') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                      <i class="bi bi-play-circle me-1"></i>Lanjutkan
                    </button>
                  </form>
                <?php elseif ($canRetry): ?>
                  <form method="post" action="<?= base_url('siswa/quiz/' . $q['id'] . '/mulai') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn <?= $done > 0 ? 'btn-outline-primary' : 'btn-primary' ?> btn-sm">
                      <i class="bi bi-<?= $done > 0 ? 'arrow-repeat' : 'play-circle' ?> me-1"></i>
                      <?= $done > 0 ? 'Kerjakan Ulang' : 'Kerjakan' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-secondary">Batas Habis</span>
                <?php endif; ?>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div><!-- /.list-group -->
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
