<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $csrfName  = csrf_token();
  $csrfHash  = csrf_hash();
  $isLocked  = (bool)($thread['is_locked']   ?? false);
  $isPinned  = (bool)($thread['is_pinned']   ?? false);
  $isAnswered= (bool)($thread['is_answered'] ?? false);
  $classId   = $thread['class_id']   ?? 0;
  $subjectId = $thread['subject_id'] ?? 0;
  $authorRole= (int)($thread['author_role'] ?? 0);
  $isOwner   = (int)($thread['user_id'] ?? 0) === (int)$userId;
?>

<!-- Header breadcrumb -->
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb mb-0" style="font-size:0.8rem;">
    <li class="breadcrumb-item">
      <a href="<?= base_url('forum/' . $classId . '/' . $subjectId) ?>">
        <?= esc($thread['subject_name'] ?? 'Forum') ?>
      </a>
    </li>
    <li class="breadcrumb-item active text-truncate" style="max-width:220px;">
      <?= esc($thread['title']) ?>
    </li>
  </ol>
</nav>

<!-- Flash messages -->
<?php foreach (['success','error','info'] as $fl): ?>
  <?php if ($msg = session()->getFlashdata($fl)): ?>
    <div class="alert alert-<?= $fl === 'info' ? 'info' : $fl ?> alert-dismissible fade show py-2 small">
      <?= esc($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<!-- Kartu thread utama -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3 p-md-4">

    <!-- Judul + badge status -->
    <div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap">
      <div>
        <h5 class="fw-bold mb-1"><?= esc($thread['title']) ?></h5>
        <div class="d-flex flex-wrap gap-1">
          <?php if ($isAnswered): ?>
            <span class="badge bg-success">✓ Terjawab</span>
          <?php endif; ?>
          <?php if ($isPinned): ?>
            <span class="badge bg-warning text-dark">📌 Disematkan</span>
          <?php endif; ?>
          <?php if ($isLocked): ?>
            <span class="badge bg-secondary">🔒 Terkunci</span>
          <?php endif; ?>
          <span class="badge bg-<?= $authorRole==3?'success':($authorRole<=2?'danger':'primary') ?>">
            <?= $authorRole==3 ? 'Guru' : ($authorRole<=2 ? 'Admin' : 'Siswa') ?>
          </span>
        </div>
      </div>

      <!-- Tombol moderasi (guru/admin) -->
      <?php if ($isMod || $isOwner): ?>
        <div class="d-flex gap-1 flex-shrink-0">
          <?php if ($isMod): ?>
            <form method="post" action="<?= base_url('forum/thread/' . $thread['id'] . '/pin') ?>" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-warning" title="<?= $isPinned ? 'Hapus Pin' : 'Sematkan' ?>">
                <i class="bi bi-pin-angle<?= $isPinned ? '-fill' : '' ?>"></i>
              </button>
            </form>
            <form method="post" action="<?= base_url('forum/thread/' . $thread['id'] . '/lock') ?>" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-secondary" title="<?= $isLocked ? 'Buka Kunci' : 'Kunci' ?>">
                <i class="bi bi-<?= $isLocked ? 'unlock' : 'lock' ?>"></i>
              </button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= base_url('forum/thread/' . $thread['id'] . '/delete') ?>"
                class="d-inline"
                onsubmit="return confirm('Hapus thread ini beserta semua balasan?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger" title="Hapus Thread">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- Body thread -->
    <?php if (!empty($thread['body'])): ?>
      <div class="forum-body mb-3" style="font-size:0.88rem; line-height:1.7;">
        <?= nl2br(esc($thread['body'])) ?>
      </div>
    <?php endif; ?>

    <!-- Meta: penulis + waktu + stats -->
    <div class="d-flex flex-wrap gap-3 text-muted border-top pt-2" style="font-size:0.75rem;">
      <span><i class="bi bi-person me-1"></i><?= esc($thread['author_name'] ?? 'Anonim') ?></span>
      <span><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($thread['created_at'])) ?></span>
      <span><i class="bi bi-eye me-1"></i><?= (int)$thread['view_count'] ?> dilihat</span>
      <span><i class="bi bi-chat me-1"></i><?= (int)$thread['reply_count'] ?> balasan</span>
    </div>
  </div>
</div>

<!-- ── Balasan ─────────────────────────────────────────────────────────── -->
<h6 class="fw-bold mb-3" id="replies">
  <i class="bi bi-chat-left-text me-2 text-secondary"></i>
  <?= (int)$thread['reply_count'] ?> Balasan
</h6>

<?php if (empty($replyTree)): ?>
  <div class="text-muted small mb-4 ps-2">Belum ada balasan. Jadilah yang pertama!</div>
<?php endif; ?>

<?php
// ── Helper render satu reply ─────────────────────────────────────────
function renderReply(array $reply, bool $isMod, int $userId, int $threadId, bool $isChild = false): string
{
  $isDeleted  = (bool)($reply['is_deleted']    ?? false);
  $isBest     = (bool)($reply['is_best_answer']?? false);
  $replyRole  = (int)($reply['author_role']    ?? 0);
  $replyOwner = (int)($reply['user_id']        ?? 0);
  $isMyReply  = $replyOwner === $userId;
  $upvoted    = (bool)($reply['viewer_upvoted']?? false);
  $upvotes    = (int)($reply['upvotes']        ?? 0);
  $replyId    = $reply['id'];

  $cs   = csrf_token();
  $ch   = csrf_hash();
  $time = date('d M Y H:i', strtotime($reply['created_at']));

  $borderClass = $isBest ? 'border-success border-2' : 'border-0';
  $bgClass     = $isBest ? 'bg-success-subtle' : '';
  $indent      = $isChild ? 'ms-4 ms-md-5' : '';

  ob_start();
?>
<div class="card shadow-sm mb-3 <?= $borderClass ?> <?= $bgClass ?> <?= $indent ?>"
     id="reply-<?= $replyId ?>">
  <div class="card-body p-3">

    <?php if ($isBest): ?>
      <div class="mb-2">
        <span class="badge bg-success"><i class="bi bi-award-fill me-1"></i>Jawaban Terbaik</span>
      </div>
    <?php endif; ?>

    <!-- Penulis + waktu -->
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
      <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small"><?= esc($reply['author_name'] ?? 'Anonim') ?></span>
        <span class="badge bg-<?= $replyRole==3?'success':($replyRole<=2?'danger':'primary') ?>"
              style="font-size:0.62rem;">
          <?= $replyRole==3?'Guru':($replyRole<=2?'Admin':'Siswa') ?>
        </span>
        <span class="text-muted" style="font-size:0.72rem;"><?= $time ?></span>
      </div>

      <!-- Aksi reply -->
      <div class="d-flex gap-1 align-items-center flex-shrink-0">
        <!-- Upvote (AJAX) -->
        <button class="btn btn-xs btn-upvote d-flex align-items-center gap-1
                       <?= $upvoted ? 'text-primary fw-bold' : 'text-muted' ?>"
                data-reply-id="<?= $replyId ?>"
                style="font-size:0.72rem; border:none; background:none; cursor:pointer; padding:0 4px;">
          <i class="bi bi-hand-thumbs-up<?= $upvoted ? '-fill' : '' ?>"></i>
          <span class="upvote-count"><?= $upvotes ?></span>
        </button>

        <?php if (!$isChild && !$isDeleted): ?>
          <!-- Reply ke reply ini -->
          <button class="btn btn-xs text-muted toggle-reply-form"
                  data-target="form-reply-<?= $replyId ?>"
                  style="font-size:0.72rem; border:none; background:none; cursor:pointer; padding:0 4px;">
            <i class="bi bi-reply"></i> Balas
          </button>
        <?php endif; ?>

        <?php if ($isMod && !$isBest && !$isDeleted): ?>
          <!-- Set best answer -->
          <form method="post" action="<?= base_url('forum/reply/'.$replyId.'/best') ?>" class="d-inline">
            <input type="hidden" name="<?= $cs ?>" value="<?= $ch ?>">
            <button class="btn btn-xs text-success" style="font-size:0.72rem; border:none; background:none;"
                    title="Tandai Jawaban Terbaik">
              <i class="bi bi-award"></i>
            </button>
          </form>
        <?php endif; ?>

        <?php if (($isMyReply || $isMod) && !$isDeleted): ?>
          <!-- Hapus reply -->
          <form method="post" action="<?= base_url('forum/reply/'.$replyId.'/delete') ?>"
                class="d-inline"
                onsubmit="return confirm('Hapus balasan ini?')">
            <input type="hidden" name="<?= $cs ?>" value="<?= $ch ?>">
            <button class="btn btn-xs text-danger" style="font-size:0.72rem; border:none; background:none;"
                    title="Hapus">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Body reply -->
    <div style="font-size:0.875rem; line-height:1.65;" class="<?= $isDeleted ? 'text-muted fst-italic' : '' ?>">
      <?= nl2br(esc($reply['body'])) ?>
    </div>

    <!-- Form reply ke reply ini (tersembunyi, 1 level) -->
    <?php if (!$isChild): ?>
      <div id="form-reply-<?= $replyId ?>" class="mt-3 d-none">
        <form method="post" action="<?= base_url('forum/thread/'.$threadId.'/reply') ?>">
          <input type="hidden" name="<?= $cs ?>" value="<?= $ch ?>">
          <input type="hidden" name="parent_id" value="<?= $replyId ?>">
          <div class="input-group input-group-sm">
            <textarea name="body" class="form-control" rows="2"
                      placeholder="Balas <?= esc($reply['author_name'] ?? '') ?>..."
                      required></textarea>
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-send"></i>
            </button>
          </div>
        </form>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Render children (1 level lebih dalam) -->
<?php if (!empty($reply['children'])): ?>
  <?php foreach ($reply['children'] as $child): ?>
    <?= renderReply($child, $isMod, $userId, $threadId, true) ?>
  <?php endforeach; ?>
<?php endif; ?>
<?php
  return ob_get_clean();
}
?>

<!-- Render semua replies -->
<?php foreach ($replyTree as $reply): ?>
  <?= renderReply($reply, $isMod, $userId, (int)$thread['id']) ?>
<?php endforeach; ?>

<!-- ── Form balas thread ──────────────────────────────────────────────── -->
<?php if (!$isLocked || $isMod): ?>
<div class="card border-0 shadow-sm mt-4" id="replyForm">
  <div class="card-header bg-white border-bottom py-2">
    <span class="fw-semibold small"><i class="bi bi-reply me-2"></i>Tulis Balasan</span>
  </div>
  <div class="card-body">
    <form method="post" action="<?= base_url('forum/thread/' . $thread['id'] . '/reply') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="parent_id" value="">
      <div class="mb-2">
        <textarea name="body" class="form-control" rows="4"
                  placeholder="Tulis balasanmu di sini..." required></textarea>
      </div>
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small">
          <i class="bi bi-markdown me-1"></i>Teks biasa
        </span>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-send me-1"></i>Kirim Balasan
        </button>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="alert alert-secondary py-2 small mt-3">
  <i class="bi bi-lock me-1"></i>Thread ini dikunci. Tidak bisa menambahkan balasan baru.
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.forum-body img { max-width:100%; border-radius:4px; }
.forum-body blockquote {
  border-left: 3px solid #0d6efd;
  padding: 0.4rem 0.8rem;
  background: #f8f9ff;
  margin: 0.5rem 0;
}
</style>
<script>
(function(){
'use strict';

var CSRF_NAME  = "<?= csrf_token() ?>";
var CSRF_HASH  = "<?= csrf_hash() ?>";
var UPVOTE_URL = "<?= base_url('forum/reply') ?>/";

// ── Toggle form reply ke reply ────────────────────────────────────────
document.querySelectorAll('.toggle-reply-form').forEach(function(btn){
  btn.addEventListener('click', function(){
    var target = document.getElementById(this.dataset.target);
    if (!target) return;
    target.classList.toggle('d-none');
    if (!target.classList.contains('d-none')) {
      target.querySelector('textarea')?.focus();
    }
  });
});

// ── Upvote AJAX ───────────────────────────────────────────────────────
document.querySelectorAll('.btn-upvote').forEach(function(btn){
  btn.addEventListener('click', function(){
    var replyId = this.dataset.replyId;
    var self    = this;
    var body = new URLSearchParams();
    body.append(CSRF_NAME, CSRF_HASH);

    fetch(UPVOTE_URL + replyId + '/upvote', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: body.toString()
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (data.success) {
        var countEl = self.querySelector('.upvote-count');
        if (countEl) countEl.textContent = data.count;
        // Toggle ikon
        var icon = self.querySelector('i');
        if (icon) {
          var filled = icon.classList.contains('bi-hand-thumbs-up-fill');
          icon.classList.toggle('bi-hand-thumbs-up',      filled);
          icon.classList.toggle('bi-hand-thumbs-up-fill', !filled);
        }
        self.classList.toggle('text-primary');
        self.classList.toggle('fw-bold');
        self.classList.toggle('text-muted');
      }
    })
    .catch(function(){});
  });
});

// ── Scroll ke #replies jika ada anchor ───────────────────────────────
if (window.location.hash === '#replies') {
  var el = document.getElementById('replies');
  if (el) setTimeout(function(){ el.scrollIntoView({behavior:'smooth'}); }, 300);
}

})();
</script>
<?= $this->endSection() ?>
