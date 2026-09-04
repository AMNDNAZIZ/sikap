<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $csrfName    = csrf_token();
  $csrfHash    = csrf_hash();
  $userId      = session()->get('user')['id'] ?? 0;
  $contentType = $subMat['content_type'] ?? 'text';
  $estMin      = $subMat['estimated_minutes'] ?? null;
  $isLocked    = (bool)($thread['is_locked'] ?? false);
  $isPinned    = (bool)($thread['is_pinned'] ?? false);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb mb-0" style="font-size:0.8rem;">
    <li class="breadcrumb-item"><a href="<?= site_url('admin/materials') ?>">Materi</a></li>
    <li class="breadcrumb-item">
      <a href="<?= site_url('admin/materials/' . $subject['id']) ?>">
        <?= esc($subject['name'] ?? '') ?>
      </a>
    </li>
    <li class="breadcrumb-item text-muted"><?= esc($parent['title'] ?? '') ?></li>
    <li class="breadcrumb-item active text-truncate" style="max-width:200px;">
      <?= esc($subMat['title']) ?>
    </li>
  </ol>
</nav>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>"
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

  <!-- Aksi sub materi -->
  <div class="d-flex gap-1 flex-shrink-0">
    <?php if ($subMat['is_published']): ?>
      <a href="<?= site_url('admin/materials/publish/' . $subMat['id']) ?>"
         class="btn btn-sm btn-outline-success" title="Kelola Publikasi">
        <i class="bi bi-send me-1"></i><span class="d-none d-md-inline">Publish</span>
      </a>
    <?php endif; ?>
    <a href="<?= site_url('admin/materials/edit/' . $subMat['id']) ?>"
       class="btn btn-sm btn-outline-warning" title="Edit Sub Materi">
      <i class="bi bi-pencil me-1"></i><span class="d-none d-md-inline">Edit</span>
    </a>
  </div>
</div>

<!-- Flash messages -->
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

<!-- ═══════════════════════════════════════════════════════════════
     KONTEN SUB MATERI
════════════════════════════════════════════════════════════════ -->
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
          <iframe src="<?= site_url('admin/materials/file/' . $subMat['id']) ?>"
                  class="rounded border" title="<?= esc($subMat['title']) ?>"></iframe>
        </div>
        <div class="text-center">
          <a href="<?= site_url('admin/materials/file/' . $subMat['id']) ?>"
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
           class="btn btn-primary">
          <i class="bi bi-box-arrow-up-right me-2"></i>Buka Materi
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- ── Progress ringkas ──────────────────────────────────────────── -->
<?php if (!empty($progStats)): ?>
<div class="d-flex gap-2 mb-4 flex-wrap">
  <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
    <i class="bi bi-check2-circle me-1"></i><?= $progStats['completed'] ?> siswa selesai
  </span>
  <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
    <i class="bi bi-eye me-1"></i><?= $progStats['in_progress'] ?> sedang membaca
  </span>
  <a href="<?= site_url('admin/materials/progress-detail/' . $subMat['id'] . '?back_url=' . urlencode(site_url('admin/materials/show/' . $subMat['id']))) ?>"
     class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 text-decoration-none">
    <i class="bi bi-table me-1"></i>Lihat detail per siswa
  </a>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════
     DISKUSI
════════════════════════════════════════════════════════════════ -->
<?php if (!empty($thread)): ?>
<div class="card border-0 shadow-sm mb-4" id="diskusi">

  <!-- Header diskusi -->
  <div class="card-header bg-white border-bottom py-2">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold small">
        <i class="bi bi-chat-square-text me-2 text-primary"></i>Diskusi Pertemuan
        <?php if ($isPinned): ?>
          <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">
            <i class="bi bi-pin-fill"></i> Disematkan
          </span>
        <?php endif; ?>
        <?php if ($isLocked): ?>
          <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">
            <i class="bi bi-lock-fill"></i> Dikunci
          </span>
        <?php endif; ?>
      </span>
      <div class="d-flex gap-1 align-items-center">
        <span class="text-muted small me-2">
          <?= $thread['reply_count'] ?? 0 ?> pesan
          &nbsp;&middot;&nbsp;<?= $thread['view_count'] ?? 0 ?> dilihat
        </span>
        <!-- Tombol moderasi -->
        <form method="post" action="<?= base_url('forum/thread/' . $threadId . '/pin') ?>" class="d-inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-outline-<?= $isPinned ? 'warning' : 'secondary' ?> py-0 px-2"
                  title="<?= $isPinned ? 'Lepas Sematkan' : 'Sematkan' ?>">
            <i class="bi bi-pin<?= $isPinned ? '-fill' : '' ?>"></i>
          </button>
        </form>
        <form method="post" action="<?= base_url('forum/thread/' . $threadId . '/lock') ?>" class="d-inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-outline-<?= $isLocked ? 'danger' : 'secondary' ?> py-0 px-2"
                  title="<?= $isLocked ? 'Buka Kunci' : 'Kunci Thread' ?>">
            <i class="bi bi-lock<?= $isLocked ? '-fill' : '' ?>"></i>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Deskripsi thread -->
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
        Belum ada diskusi dari siswa.
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush" id="replyList">
        <?php foreach ($replyTree as $reply): ?>
          <?= renderAdminReplyItem($reply, (int)$userId, (int)$threadId, (int)$subMat['id'], $csrfName, $csrfHash, false) ?>
          <?php foreach (($reply['children'] ?? []) as $child): ?>
            <?= renderAdminReplyItem($child, (int)$userId, (int)$threadId, (int)$subMat['id'], $csrfName, $csrfHash, true) ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Form kirim pesan (guru ikut berdiskusi) -->
  <?php if (!$isLocked): ?>
  <div class="card-footer bg-white border-top pt-3 pb-3" id="mainReplyBox">
    <div class="text-muted small mb-2 fw-semibold">
      <i class="bi bi-pencil me-1"></i>Tulis Pesan
    </div>
    <form method="post" action="<?= base_url('forum/thread/' . $threadId . '/reply') ?>"
          id="formReplyMain">
      <?= csrf_field() ?>
      <input type="hidden" name="parent_id" id="mainParentId" value="">
      <input type="hidden" name="redirect_to"
             value="<?= site_url('admin/materials/show/' . $subMat['id']) ?>#diskusi">
      <div id="replyingToLabel" class="text-primary small mb-1" style="display:none;">
        <i class="bi bi-reply me-1"></i>
        Membalas: <span id="replyingToName"></span>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted" id="btnCancelReply"
                style="font-size:0.75rem; vertical-align:baseline;">&times; Batal</button>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <textarea name="body" id="mainReplyBody" class="form-control form-control-sm flex-grow-1"
                  rows="2" placeholder="Tulis komentar atau arahan untuk siswa..." required
                  style="resize:none;"></textarea>
        <button type="submit" class="btn btn-primary btn-sm px-3">
          <i class="bi bi-send"></i>
        </button>
      </div>
    </form>
  </div>
  <?php else: ?>
  <div class="card-footer bg-light text-muted small py-2 text-center">
    <i class="bi bi-lock me-1"></i>Thread dikunci — tidak bisa menambah pesan baru.
  </div>
  <?php endif; ?>

</div>
<?php else: ?>
<!-- Tidak ada thread (sub materi belum dipublish ke kelas manapun) -->
<div class="alert alert-warning border-0 d-flex align-items-center gap-2 mb-4 py-2 small">
  <i class="bi bi-exclamation-circle-fill fs-5 text-warning"></i>
  <div>
    Thread diskusi belum tersedia. Publikasikan sub materi ini ke minimal satu kelas agar thread terbuat otomatis.
    <a href="<?= site_url('admin/materials/publish/' . $subMat['id']) ?>" class="fw-semibold ms-1">
      Kelola Publikasi &rarr;
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ── Navigasi Sub Materi ───────────────────────────────────────── -->
<div class="d-flex justify-content-between gap-3 mb-5">
  <?php if ($prevSub): ?>
    <a href="<?= site_url('admin/materials/show/' . $prevSub['id']) ?>"
       class="btn btn-outline-secondary btn-sm flex-grow-1 text-start">
      <i class="bi bi-chevron-left me-1"></i>
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:200px; vertical-align:middle;">
        Pertemuan <?= $currentIdx ?>: <?= esc($prevSub['title']) ?>
      </span>
    </a>
  <?php else: ?>
    <div class="flex-grow-1"></div>
  <?php endif; ?>

  <?php if ($nextSub): ?>
    <a href="<?= site_url('admin/materials/show/' . $nextSub['id']) ?>"
       class="btn btn-outline-primary btn-sm flex-grow-1 text-end">
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:200px; vertical-align:middle;">
        Pertemuan <?= $currentIdx + 2 ?>: <?= esc($nextSub['title']) ?>
      </span>
      <i class="bi bi-chevron-right ms-1"></i>
    </a>
  <?php else: ?>
    <a href="<?= site_url('admin/materials/' . $subject['id']) ?>"
       class="btn btn-outline-secondary btn-sm flex-grow-1 text-center">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
    </a>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
// ── Helper render reply untuk admin/guru (dengan tombol moderasi) ─
function renderAdminReplyItem(array $r, int $userId, int $threadId, int $subMatId, string $csrfName, string $csrfHash, bool $isChild = false): string
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

    $roleLabel = 'Siswa'; $roleBadge = 'primary';
    if ($roleId == 3) { $roleLabel = 'Guru'; $roleBadge = 'success'; }
    elseif ($roleId <= 2) { $roleLabel = 'Admin'; $roleBadge = 'danger'; }

    ob_start();
    ?>
    <div class="list-group-item px-3 py-2 <?= $indent ?> <?= $bgClass ?>" id="reply-<?= $rid ?>">
      <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-semibold small"><?= $authorName ?></span>
          <span class="badge bg-<?= $roleBadge ?>" style="font-size:0.6rem;"><?= $roleLabel ?></span>
          <?php if ($isBest): ?>
            <span class="badge bg-success" style="font-size:0.6rem;">
              <i class="bi bi-award me-1"></i>Terbaik
            </span>
          <?php endif; ?>
          <span class="text-muted" style="font-size:0.7rem;"><?= $time ?></span>
        </div>

        <!-- Aksi moderasi + upvote -->
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
          <!-- Upvote (read-only info untuk guru) -->
          <span class="d-flex align-items-center gap-1 text-muted" style="font-size:0.72rem;">
            <i class="bi bi-hand-thumbs-up"></i>
            <span><?= $upvotes ?></span>
          </span>

          <?php if (!$isDeleted): ?>
            <!-- Best Answer -->
            <form method="post" action="<?= base_url('forum/reply/' . $rid . '/best') ?>" class="d-inline">
              <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
              <button type="submit"
                      class="btn btn-xs py-0 px-1 btn-outline-<?= $isBest ? 'success' : 'secondary' ?>"
                      title="<?= $isBest ? 'Batalkan Terbaik' : 'Tandai Terbaik' ?>"
                      style="font-size:0.65rem;">
                <i class="bi bi-award<?= $isBest ? '-fill' : '' ?>"></i>
              </button>
            </form>

            <!-- Delete -->
            <form method="post" action="<?= base_url('forum/reply/' . $rid . '/delete') ?>" class="d-inline"
                  onsubmit="return confirm('Hapus pesan ini?')">
              <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
              <input type="hidden" name="redirect_to"
                     value="<?= site_url('admin/materials/show/' . $subMatId) ?>#diskusi">
              <button type="submit" class="btn btn-xs py-0 px-1 btn-outline-danger"
                      title="Hapus Pesan" style="font-size:0.65rem;">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Isi pesan -->
      <div style="font-size:0.875rem;" class="mb-1 <?= $isDeleted ? 'text-muted fst-italic' : '' ?>">
        <?= nl2br(esc($r['body'])) ?>
      </div>

      <!-- Tombol Balas -->
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
.material-content table th,.material-content table td { border:1px solid #dee2e6; padding:.4rem .6rem; }
.material-content table th { background:#f8f9fa; font-weight:600; }
.btn-xs { font-size:0.7rem; line-height:1.4; }
#mainReplyBox.replying { background-color:#f0f7ff; }
</style>
<script>
(function(){
'use strict';
var CSRF_NAME = "<?= csrf_token() ?>";
var CSRF_HASH = "<?= csrf_hash() ?>";

// ── Tombol Balas ─────────────────────────────────────────────────
var parentIdInput  = document.getElementById('mainParentId');
var replyingLabel  = document.getElementById('replyingToLabel');
var replyingName   = document.getElementById('replyingToName');
var btnCancelReply = document.getElementById('btnCancelReply');
var mainReplyBox   = document.getElementById('mainReplyBox');
var mainReplyBody  = document.getElementById('mainReplyBody');

document.addEventListener('click', function(e) {
  var trigger = e.target.closest('.btn-reply-trigger');
  if (!trigger) return;

  var replyId = trigger.dataset.replyId;
  var author  = trigger.dataset.author;

  if (parentIdInput)  parentIdInput.value = replyId;
  if (replyingName)   replyingName.textContent = author;
  if (replyingLabel)  replyingLabel.style.display = '';
  if (mainReplyBox) {
    mainReplyBox.classList.add('replying');
    mainReplyBox.scrollIntoView({ behavior:'smooth', block:'nearest' });
    setTimeout(function(){ if (mainReplyBody) mainReplyBody.focus(); }, 300);
  }
});

if (btnCancelReply) {
  btnCancelReply.addEventListener('click', function() {
    if (parentIdInput)  parentIdInput.value = '';
    if (replyingLabel)  replyingLabel.style.display = 'none';
    if (mainReplyBox)   mainReplyBox.classList.remove('replying');
    if (mainReplyBody)  mainReplyBody.focus();
  });
}

})();
</script>
<?= $this->endSection() ?>
