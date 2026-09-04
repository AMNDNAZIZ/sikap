<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-chat-dots me-2 text-primary"></i>Forum Diskusi
    </h5>
    <div class="text-muted small">
      <?= esc($class['name'] ?? '') ?>
      <?php if (!empty($subject['name'])): ?>
        &nbsp;·&nbsp; <?= esc($subject['name']) ?>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($isMod): ?>
    <a href="<?= base_url('forum/kelas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Ganti Kelas
    </a>
  <?php endif; ?>
</div>

<!-- Tab navigasi mapel -->
<?php if (!empty($allSubjects)): ?>
  <div class="d-flex flex-wrap gap-1 mb-3 pb-1 border-bottom overflow-auto" style="scrollbar-width:thin;">
    <?php foreach ($allSubjects as $sub): ?>
      <a href="<?= base_url('forum/' . $classId . '/' . $sub['id']) ?>"
         class="btn btn-sm <?= (int)$sub['id'] === (int)$subjectId ? 'btn-primary' : 'btn-outline-secondary' ?>"
         style="font-size:0.78rem; white-space:nowrap;">
        <?= esc($sub['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 small">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2 small">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Form buat thread baru -->
<?php if ($subjectId > 0): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-2"
       style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#formNewThread">
    <span class="fw-semibold small">
      <i class="bi bi-plus-circle me-2 text-success"></i>Buat Topik Diskusi Baru
    </span>
    <i class="bi bi-chevron-down text-muted float-end small mt-1"></i>
  </div>
  <div class="collapse" id="formNewThread">
    <div class="card-body">
      <form method="post"
            action="<?= base_url('forum/' . $classId . '/' . $subjectId . '/store') ?>">
        <?= csrf_field() ?>
        <div class="mb-2">
          <input type="text" name="title" class="form-control form-control-sm"
                 placeholder="Judul topik diskusi *" required minlength="5"
                 value="<?= old('title') ?>">
        </div>
        <div class="mb-2">
          <textarea name="body" class="form-control form-control-sm" rows="3"
                    placeholder="Deskripsikan pertanyaan atau topikmu (opsional)..."><?= old('body') ?></textarea>
        </div>
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-send me-1"></i>Buat Diskusi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Daftar thread -->
<?php if ($subjectId == 0): ?>
  <div class="alert alert-info py-2 small">Pilih mata pelajaran di atas untuk melihat diskusi.</div>
<?php elseif (empty($threads)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-chat-square fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-1">Belum ada topik diskusi.</p>
    <p class="small">Jadilah yang pertama memulai diskusi!</p>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
      <?php foreach ($threads as $t): ?>
        <?php
          $isPinned   = (bool)($t['is_pinned']   ?? false);
          $isLocked   = (bool)($t['is_locked']   ?? false);
          $isAnswered = (bool)($t['is_answered'] ?? false);
          $isNew      = (bool)($t['is_new']      ?? false);
          $replyCount = (int)($t['reply_count']  ?? 0);
          $viewCount  = (int)($t['view_count']   ?? 0);

          $badgeBg = 'secondary';
          $authorRole = (int)($t['author_role'] ?? 0);
          if ($authorRole == 3) $badgeBg = 'success';
          elseif (in_array($authorRole, [1,2])) $badgeBg = 'danger';

          $lastActivity = $t['last_reply_at'] ?: $t['created_at'];
          $diff = time() - strtotime($lastActivity);
          if ($diff < 3600)      $timeAgo = floor($diff/60) . ' mnt lalu';
          elseif ($diff < 86400) $timeAgo = floor($diff/3600) . ' jam lalu';
          else                   $timeAgo = date('d M Y', strtotime($lastActivity));
        ?>
        <a href="<?= base_url('forum/thread/' . $t['id']) ?>"
           class="list-group-item list-group-item-action px-4 py-3
                  <?= $isPinned ? 'bg-warning-subtle' : '' ?>
                  <?= $isNew    ? 'border-start border-3 border-primary' : '' ?>">
          <div class="d-flex align-items-start gap-3">

            <!-- Ikon status -->
            <div class="flex-shrink-0 mt-1" style="width:28px; text-align:center;">
              <?php if ($isAnswered): ?>
                <i class="bi bi-check-circle-fill text-success fs-5" title="Terjawab"></i>
              <?php elseif ($isLocked): ?>
                <i class="bi bi-lock-fill text-secondary fs-5" title="Dikunci"></i>
              <?php elseif ($isPinned): ?>
                <i class="bi bi-pin-angle-fill text-warning fs-5" title="Disematkan"></i>
              <?php else: ?>
                <i class="bi bi-chat-square-text text-primary fs-5"></i>
              <?php endif; ?>
            </div>

            <!-- Konten -->
            <div class="flex-grow-1 overflow-hidden">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="fw-semibold" style="font-size:0.88rem;">
                  <?= esc($t['title']) ?>
                </span>
                <?php if ($isNew): ?>
                  <span class="badge bg-primary" style="font-size:0.65rem;">BARU</span>
                <?php endif; ?>
                <?php if ($isPinned): ?>
                  <span class="badge bg-warning text-dark" style="font-size:0.65rem;">📌 Sematkan</span>
                <?php endif; ?>
                <?php if ($isLocked): ?>
                  <span class="badge bg-secondary" style="font-size:0.65rem;">🔒 Terkunci</span>
                <?php endif; ?>
                <?php if ($isAnswered): ?>
                  <span class="badge bg-success" style="font-size:0.65rem;">✓ Terjawab</span>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-3 text-muted" style="font-size:0.73rem;">
                <span>
                  <span class="badge bg-<?= $badgeBg ?> me-1" style="font-size:0.62rem;">
                    <?= $authorRole == 3 ? 'Guru' : ($authorRole <= 2 ? 'Admin' : 'Siswa') ?>
                  </span>
                  <?= esc($t['author_name'] ?? 'Anonim') ?>
                </span>
                <span><i class="bi bi-chat me-1"></i><?= $replyCount ?></span>
                <span><i class="bi bi-eye me-1"></i><?= $viewCount ?></span>
                <span><i class="bi bi-clock me-1"></i><?= $timeAgo ?></span>
              </div>
            </div>

            <!-- Panah -->
            <i class="bi bi-chevron-right text-muted mt-1 flex-shrink-0"></i>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
