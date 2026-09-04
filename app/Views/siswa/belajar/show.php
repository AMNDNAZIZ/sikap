<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $csrfName    = csrf_token();
  $csrfHash    = csrf_hash();
  $userId      = session()->get('user')['id'] ?? 0;
  $isCompleted = ($progress['status'] ?? '') === 'completed';
  $contentType = $subMat['content_type'] ?? 'text';
  $estMin      = $subMat['estimated_minutes'] ?? null;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb mb-0" style="font-size:0.8rem;">
    <li class="breadcrumb-item"><a href="<?= base_url('siswa/belajar') ?>">Proses Belajar</a></li>
    <li class="breadcrumb-item">
      <a href="<?= base_url('siswa/belajar/' . $subMat['subject_id']) ?>">
        <?= esc($subject['name'] ?? '') ?>
      </a>
    </li>
    <li class="breadcrumb-item text-muted"><?= esc($parent['title'] ?? '') ?></li>
    <li class="breadcrumb-item active text-truncate" style="max-width:180px;">
      <?= esc($subMat['title']) ?>
    </li>
  </ol>
</nav>

<!-- Header Sub Materi -->
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('siswa/belajar/' . $subMat['subject_id']) ?>"
     class="btn btn-sm btn-outline-secondary flex-shrink-0">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div class="flex-grow-1 overflow-hidden">
    <h5 class="fw-bold mb-0 text-truncate">
      Pertemuan <?= $currentIdx + 1 ?>: <?= esc($subMat['title']) ?>
    </h5>
    <div class="text-muted" style="font-size:0.75rem;">
      <?= esc($subject['name'] ?? '') ?>
      &nbsp;&middot;&nbsp;<?= esc($parent['title'] ?? '') ?>
      <?php if ($estMin): ?>
        &nbsp;&middot;&nbsp;<i class="bi bi-clock"></i> <?= $estMin ?> menit
      <?php endif; ?>
    </div>
  </div>
  <?php if ($isCompleted): ?>
    <span class="badge bg-success flex-shrink-0">
      <i class="bi bi-check2-circle me-1"></i>Selesai
    </span>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     KONTEN SUB MATERI
════════════════════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3 p-md-4">

    <?php if ($contentType === 'text'): ?>
      <div class="material-content">
        <?php if (!empty($subMat['content'])): ?>
          <?= $subMat['content'] ?>
        <?php elseif (!empty($subMat['description'])): ?>
          <p class="text-muted"><?= nl2br(esc($subMat['description'])) ?></p>
        <?php else: ?>
          <p class="text-muted fst-italic">Konten belum tersedia.</p>
        <?php endif; ?>
      </div>

    <?php elseif ($contentType === 'pdf'): ?>
      <?php if (!empty($subMat['file_path'])): ?>
        <div class="ratio mb-3" style="--bs-aspect-ratio: 75%; min-height: 420px;">
          <iframe src="<?= base_url('siswa/belajar/sub/' . $subMat['id'] . '/file') ?>"
                  class="rounded border" title="<?= esc($subMat['title']) ?>"></iframe>
        </div>
        <div class="text-center">
          <a href="<?= base_url('siswa/belajar/sub/' . $subMat['id'] . '/file') ?>"
             target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Buka di Tab Baru
          </a>
        </div>
      <?php else: ?>
        <p class="text-muted fst-italic">File PDF belum tersedia.</p>
      <?php endif; ?>

    <?php elseif ($contentType === 'video'): ?>
      <?php if (!empty($subMat['embed_url'])): ?>
        <div class="ratio ratio-16x9 mb-3">
          <iframe src="<?= esc($subMat['embed_url']) ?>"
                  title="<?= esc($subMat['title']) ?>" allowfullscreen class="rounded border"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
          </iframe>
        </div>
      <?php else: ?>
        <p class="text-muted fst-italic">URL video tidak valid.</p>
      <?php endif; ?>
      <?php if (!empty($subMat['description'])): ?>
        <div class="text-muted small mt-2"><?= nl2br(esc($subMat['description'])) ?></div>
      <?php endif; ?>

    <?php elseif ($contentType === 'link'): ?>
      <div class="text-center py-4">
        <i class="bi bi-link-45deg text-primary" style="font-size:3rem;"></i>
        <p class="mt-2 mb-1 fw-semibold"><?= esc($subMat['title']) ?></p>
        <?php if (!empty($subMat['description'])): ?>
          <p class="text-muted small mb-3"><?= nl2br(esc($subMat['description'])) ?></p>
        <?php endif; ?>
        <a href="<?= esc($subMat['external_link']) ?>" target="_blank" rel="noopener noreferrer"
           class="btn btn-primary" id="btnOpenLink">
          <i class="bi bi-box-arrow-up-right me-2"></i>Buka Materi
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- ── Tombol Tandai Selesai ─────────────────────────────────────────── -->
<?php if (!$isCompleted): ?>
<div class="card border-0 shadow-sm mb-4 bg-light-subtle">
  <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="fw-semibold small">Sudah selesai membaca?</div>
      <div class="text-muted" style="font-size:0.78rem;">
        Tandai selesai sebelum lanjut ke sub materi berikutnya.
      </div>
    </div>
    <button class="btn btn-success" id="btnMarkComplete">
      <i class="bi bi-check2-circle me-1"></i>Tandai Selesai
    </button>
  </div>
</div>
<?php else: ?>
<div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-4 py-2">
  <i class="bi bi-check-circle-fill fs-5"></i>
  <div>
    <strong>Selesai!</strong>
    <?php if (!empty($progress['completed_at'])): ?>
      Ditandai selesai pada <?= date('d M Y H:i', strtotime($progress['completed_at'])) ?>.
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     KUIS (jika ada)
════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($quizzes)): ?>
<div class="mb-4" id="kuis">
  <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-pencil-square text-success"></i>Kuis
    <span class="badge bg-success" style="font-size:0.65rem;"><?= count($quizzes) ?></span>
  </h6>

  <?php if (!$isCompleted): ?>
  <!-- Kuis terkunci sampai sub materi diselesaikan -->
  <div class="card border-0 border-start border-4 border-warning shadow-sm">
    <div class="card-body d-flex align-items-center gap-3 py-3">
      <div class="rounded-circle bg-warning bg-opacity-15 d-flex align-items-center justify-content-center flex-shrink-0"
           style="width:44px;height:44px;">
        <i class="bi bi-lock-fill text-warning fs-5"></i>
      </div>
      <div>
        <div class="fw-semibold small">
          <?= count($quizzes) == 1 ? 'Ada 1 kuis' : 'Ada ' . count($quizzes) . ' kuis' ?>
          untuk pertemuan ini
        </div>
        <div class="text-muted" style="font-size:0.78rem;">
          Selesaikan membaca materi ini terlebih dahulu, lalu tandai selesai untuk membuka kuis.
        </div>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- Sub materi sudah selesai — tampilkan kuis -->
  <div class="d-flex flex-column gap-3">
    <?php foreach ($quizzes as $q): ?>
      <?php
        $done      = $q['attempts_done'];
        $best      = $q['best_score'];
        $canRetry  = $q['can_retry'];
        $hasActive = !empty($q['active_session']);
        $scoreColor= $best === null ? 'secondary'
                     : ($best >= 80 ? 'success' : ($best >= 60 ? 'warning' : 'danger'));
        $totalSoal = ((int)$q['show_pg_count']) + ((int)$q['show_pgk_count'])
                   + ((int)$q['show_bs_count']) + ((int)$q['show_esai_count']);
      ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-start gap-3 flex-wrap">
          <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center
                       justify-content-center flex-shrink-0"
               style="width:44px; height:44px;">
            <i class="bi bi-pencil-square text-success fs-5"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold"><?= esc($q['title']) ?></div>
            <?php if (!empty($q['description'])): ?>
              <div class="text-muted small"><?= esc($q['description']) ?></div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:0.73rem; color:#6c757d;">
              <span><i class="bi bi-question-circle me-1"></i><?= $totalSoal ?> soal</span>
              <?php if ($q['duration'] > 0): ?>
                <span><i class="bi bi-clock me-1"></i><?= $q['duration'] ?> menit</span>
              <?php endif; ?>
              <?php if ($done > 0): ?>
                <span><i class="bi bi-arrow-repeat me-1"></i>Dikerjakan <?= $done ?>x</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 flex-shrink-0">
            <?php if ($best !== null): ?>
              <div class="text-center">
                <div class="fw-bold text-<?= $scoreColor ?>" style="font-size:1.3rem; line-height:1.1;">
                  <?= number_format($best, 1) ?>
                </div>
                <div class="text-muted" style="font-size:0.68rem;">nilai terbaik</div>
              </div>
            <?php endif; ?>
            <?php if ($hasActive): ?>
              <form method="post" action="<?= base_url('siswa/quiz/' . $q['id'] . '/mulai') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="bi bi-play-circle me-1"></i>Lanjutkan
                </button>
              </form>
            <?php elseif ($canRetry): ?>
              <form method="post" action="<?= base_url('siswa/quiz/' . $q['id'] . '/mulai') ?>">
                <?= csrf_field() ?>
                <button type="submit"
                        class="btn <?= $done > 0 ? 'btn-outline-primary' : 'btn-primary' ?> btn-sm">
                  <i class="bi bi-<?= $done > 0 ? 'arrow-repeat' : 'play-circle' ?> me-1"></i>
                  <?= $done > 0 ? 'Kerjakan Ulang' : 'Kerjakan Kuis' ?>
                </button>
              </form>
            <?php else: ?>
              <span class="badge bg-secondary">Batas Habis</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     DISKUSI (langsung di bawah materi, tanpa tab)
════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($thread)): ?>
<div class="card border-0 shadow-sm mb-4" id="diskusi">

  <!-- Header -->
  <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
    <span class="fw-semibold small">
      <i class="bi bi-chat-square-text me-2 text-primary"></i>Diskusi Pertemuan
    </span>
    <span class="text-muted small">
      <?= $thread['reply_count'] ?? 0 ?> pesan
      &nbsp;&middot;&nbsp;<?= $thread['view_count'] ?? 0 ?> dilihat
    </span>
  </div>

  <!-- Deskripsi thread (jika ada) -->
  <?php if (!empty($thread['body'])): ?>
    <div class="card-body pb-2 pt-2 text-muted small border-bottom">
      <?= nl2br(esc($thread['body'])) ?>
    </div>
  <?php endif; ?>

  <!-- Daftar pesan -->
  <div class="card-body p-0">
    <?php if (empty($replyTree)): ?>
      <div class="text-center py-5 text-muted small" id="emptyDiscussion">
        <i class="bi bi-chat-square fs-3 d-block mb-2 opacity-40"></i>
        Belum ada diskusi. Jadilah yang pertama!
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush" id="replyList">
        <?php foreach ($replyTree as $reply): ?>
          <?= renderReplyItem($reply, (int)$userId, (int)$threadId, $csrfName, $csrfHash, false) ?>
          <?php foreach (($reply['children'] ?? []) as $child): ?>
            <?= renderReplyItem($child, (int)$userId, (int)$threadId, $csrfName, $csrfHash, true) ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Form kirim pesan baru -->
  <div class="card-footer bg-white border-top pt-3 pb-3" id="mainReplyBox">
    <div class="text-muted small mb-2 fw-semibold">
      <i class="bi bi-pencil me-1"></i>Tulis Pesan
    </div>
    <form method="post" action="<?= base_url('forum/thread/' . $threadId . '/reply') ?>"
          id="formReplyMain">
      <?= csrf_field() ?>
      <input type="hidden" name="parent_id" id="mainParentId" value="">
      <input type="hidden" name="redirect_to" value="<?= base_url('siswa/belajar/sub/' . $subMat['id']) ?>#diskusi">
      <div id="replyingToLabel" class="text-primary small mb-1" style="display:none;">
        <i class="bi bi-reply me-1"></i>
        Membalas: <span id="replyingToName"></span>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted" id="btnCancelReply"
                style="font-size:0.75rem; vertical-align:baseline;">
          &times; Batal
        </button>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <textarea name="body" id="mainReplyBody" class="form-control form-control-sm flex-grow-1"
                  rows="2" placeholder="Tulis pertanyaan atau komentarmu..." required
                  style="resize:none;"></textarea>
        <button type="submit" class="btn btn-primary btn-sm px-3">
          <i class="bi bi-send"></i>
        </button>
      </div>
    </form>
  </div>

</div>
<?php endif; ?>

<!-- ── Navigasi Sub Materi ───────────────────────────────────────────── -->
<div class="d-flex justify-content-between gap-3 mb-5">
  <?php if ($prevSub): ?>
    <a href="<?= base_url('siswa/belajar/sub/' . $prevSub['id']) ?>"
       class="btn btn-outline-secondary btn-sm flex-grow-1 text-start">
      <i class="bi bi-chevron-left me-1"></i>
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:180px; vertical-align:middle;">
        Pertemuan <?= $currentIdx ?>: <?= esc($prevSub['title']) ?>
      </span>
    </a>
  <?php else: ?>
    <div class="flex-grow-1"></div>
  <?php endif; ?>

  <?php if ($nextSub): ?>
    <a href="<?= base_url('siswa/belajar/sub/' . $nextSub['id']) ?>"
       class="btn btn-outline-primary btn-sm flex-grow-1 text-end">
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:180px; vertical-align:middle;">
        Pertemuan <?= $currentIdx + 2 ?>: <?= esc($nextSub['title']) ?>
      </span>
      <i class="bi bi-chevron-right ms-1"></i>
    </a>
  <?php else: ?>
    <a href="<?= base_url('siswa/belajar/' . $subMat['subject_id']) ?>"
       class="btn btn-success btn-sm flex-grow-1 text-center">
      <i class="bi bi-check2-all me-1"></i>Kembali ke Daftar Materi
    </a>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
// ── Helper render reply ──────────────────────────────────────────────
function renderReplyItem(array $r, int $userId, int $threadId, string $csrfName, string $csrfHash, bool $isChild = false): string
{
    $isDeleted  = (bool)($r['is_deleted'] ?? false);
    $isBest     = (bool)($r['is_best_answer'] ?? false);
    $rUserId    = (int)($r['user_id'] ?? 0);
    $roleId     = (int)($r['author_role'] ?? 0);
    $upvoted    = (bool)($r['viewer_upvoted'] ?? false);
    $upvotes    = (int)($r['upvotes'] ?? 0);
    $time       = date('d M Y H:i', strtotime($r['created_at']));
    $indent     = $isChild ? 'ms-4 border-start border-primary border-opacity-25' : '';
    $bgClass    = $isBest  ? 'bg-success-subtle' : '';
    $rid        = (int)$r['id'];
    $authorName = esc($r['author_name'] ?? 'Anonim');

    // Label role
    $roleLabel = 'Siswa'; $roleBadge = 'primary';
    if ($roleId == 3) { $roleLabel = 'Guru'; $roleBadge = 'success'; }
    elseif ($roleId <= 2) { $roleLabel = 'Admin'; $roleBadge = 'danger'; }

    ob_start();
    ?>
    <div class="list-group-item px-3 py-2 <?= $indent ?> <?= $bgClass ?>" id="reply-<?= $rid ?>">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-semibold small"><?= $authorName ?></span>
          <span class="badge bg-<?= $roleBadge ?>" style="font-size:0.6rem;"><?= $roleLabel ?></span>
          <?php if ($isBest): ?>
            <span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-award me-1"></i>Terbaik</span>
          <?php endif; ?>
          <span class="text-muted" style="font-size:0.7rem;"><?= $time ?></span>
        </div>
        <!-- Upvote -->
        <button class="btn-upvote d-flex align-items-center gap-1 text-<?= $upvoted ? 'primary' : 'muted' ?>"
                data-reply-id="<?= $rid ?>"
                style="font-size:0.72rem; border:none; background:none; cursor:pointer; padding:0 4px;">
          <i class="bi bi-hand-thumbs-up<?= $upvoted ? '-fill' : '' ?>"></i>
          <span class="uv-count"><?= $upvotes ?></span>
        </button>
      </div>

      <!-- Isi pesan -->
      <div style="font-size:0.875rem;" class="mb-1 <?= $isDeleted ? 'text-muted fst-italic' : '' ?>">
        <?= nl2br(esc($r['body'])) ?>
      </div>

      <!-- Tombol Balas (hanya muncul jika tidak deleted) -->
      <?php if (!$isDeleted): ?>
      <div>
        <button type="button"
                class="btn-reply-trigger btn btn-link btn-sm p-0 text-muted"
                data-reply-id="<?= $rid ?>"
                data-author="<?= $authorName ?>"
                style="font-size:0.75rem; text-decoration:none;">
          <i class="bi bi-reply me-1"></i>Balas
        </button>
      </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>
<style>
.material-content { font-size:0.9rem; line-height:1.7; }
.material-content h1,.material-content h2,.material-content h3 { margin-top:1.25rem; font-weight:600; }
.material-content p { margin-bottom:.75rem; }
.material-content ul,.material-content ol { padding-left:1.5rem; margin-bottom:.75rem; }
.material-content blockquote { border-left:4px solid #0d6efd; padding:.5rem 1rem; background:#f8f9ff; }
.material-content img { max-width:100%; height:auto; border-radius:4px; display:block; }
.material-content figure.image { margin:1rem auto; text-align:center; }
.material-content figure.image img { margin:0 auto; max-width:100%; height:auto; }
.material-content figure.image.image_resized { display:block; }
.material-content figure.image.image_resized img { width:100%; }
.material-content figure.image.image-style-side { float:right; margin-left:1.5rem; max-width:50%; }
.material-content figure.image.image-style-align-left { float:left; margin-right:1.5rem; }
.material-content figure.image.image-style-align-right { float:right; margin-left:1.5rem; }
.material-content figcaption { font-size:0.8rem; color:#6c757d; text-align:center; margin-top:0.25rem; }
.material-content table { width:100%; border-collapse:collapse; margin-bottom:1rem; font-size:.85rem; }
.material-content table th, .material-content table td { border:1px solid #dee2e6; padding:.4rem .6rem; }
.material-content table th { background:#f8f9fa; font-weight:600; }
#mainReplyBox { transition: background-color 0.2s; }
#mainReplyBox.replying { background-color: #f0f7ff; }
</style>
<script>
(function(){
'use strict';
var CSRF_NAME  = "<?= csrf_token() ?>";
var CSRF_HASH  = "<?= csrf_hash() ?>";
var COMPLETE_URL = "<?= base_url('siswa/belajar/sub/' . $subMat['id'] . '/complete') ?>";
var LINK_URL   = <?= !empty($subMat['external_link']) ? json_encode($subMat['external_link']) : 'null' ?>;
var UPVOTE_URL = "<?= base_url('forum/reply') ?>/";

// ── Tandai Selesai ────────────────────────────────────────────────────
var btnComplete = document.getElementById('btnMarkComplete');
if (btnComplete) {
  btnComplete.addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
    var body = new URLSearchParams();
    body.append(CSRF_NAME, CSRF_HASH);
    fetch(COMPLETE_URL, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body: body.toString()
    }).then(r=>r.json()).then(function(d){
      if (d.success) {
        var wrap = btn.closest('.card');
        if (wrap) {
          wrap.outerHTML = '<div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-4 py-2">'
            + '<i class="bi bi-check-circle-fill fs-5"></i>'
            + '<div><strong>Selesai!</strong> Sub Materi ini ditandai sudah selesai.</div></div>';
        }
        // Buka kuis yang sebelumnya terkunci
        var kuisSection = document.getElementById('kuis');
        if (kuisSection) {
          // Reload halaman di posisi #kuis agar kuis muncul
          window.location.href = window.location.pathname + '#kuis';
          window.location.reload();
        }
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Tandai Selesai';
      }
    }).catch(function(){
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Tandai Selesai';
    });
  });
}

// ── Auto-mark complete saat buka link eksternal ───────────────────────
var btnLink = document.getElementById('btnOpenLink');
if (btnLink && LINK_URL) {
  btnLink.addEventListener('click', function() {
    var body = new URLSearchParams();
    body.append(CSRF_NAME, CSRF_HASH);
    fetch(COMPLETE_URL, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body: body.toString()
    }).catch(function(){});
  });
}

// ── Tombol Balas — set parent_id di form utama ────────────────────────
var parentIdInput  = document.getElementById('mainParentId');
var replyingLabel  = document.getElementById('replyingToLabel');
var replyingName   = document.getElementById('replyingToName');
var btnCancelReply = document.getElementById('btnCancelReply');
var mainReplyBox   = document.getElementById('mainReplyBox');
var mainReplyBody  = document.getElementById('mainReplyBody');

function resetReplyTarget() {
  if (parentIdInput)  parentIdInput.value = '';
  if (replyingLabel)  replyingLabel.style.display = 'none';
  if (mainReplyBox)   mainReplyBox.classList.remove('replying');
}

document.addEventListener('click', function(e) {
  var trigger = e.target.closest('.btn-reply-trigger');
  if (!trigger) return;

  var replyId = trigger.dataset.replyId;
  var author  = trigger.dataset.author;

  if (parentIdInput)  parentIdInput.value = replyId;
  if (replyingName)   replyingName.textContent = author;
  if (replyingLabel)  replyingLabel.style.display = '';
  if (mainReplyBox)   mainReplyBox.classList.add('replying');

  // Scroll ke form dan fokus
  if (mainReplyBox) {
    mainReplyBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    setTimeout(function() { if (mainReplyBody) mainReplyBody.focus(); }, 300);
  }
});

if (btnCancelReply) {
  btnCancelReply.addEventListener('click', function() {
    resetReplyTarget();
    if (mainReplyBody) mainReplyBody.focus();
  });
}

// Reset setelah form submit (agar tidak double-set)
var formReplyMain = document.getElementById('formReplyMain');
if (formReplyMain) {
  formReplyMain.addEventListener('submit', function() {
    // Biarkan submit normal, tidak perlu reset di sini
  });
}

// ── Upvote ────────────────────────────────────────────────────────────
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.btn-upvote');
  if (!btn) return;

  var rid  = btn.dataset.replyId;
  var body = new URLSearchParams();
  body.append(CSRF_NAME, CSRF_HASH);
  fetch(UPVOTE_URL + rid + '/upvote', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: body.toString()
  }).then(r=>r.json()).then(function(d){
    if (d.success) {
      btn.querySelector('.uv-count').textContent = d.count;
      var icon   = btn.querySelector('i');
      var filled = icon.classList.contains('bi-hand-thumbs-up-fill');
      icon.classList.toggle('bi-hand-thumbs-up',      filled);
      icon.classList.toggle('bi-hand-thumbs-up-fill', !filled);
      btn.classList.toggle('text-primary', !filled);
      btn.classList.toggle('text-muted',    filled);
    }
  }).catch(function(){});
});

})();
</script>
<?= $this->endSection() ?>
