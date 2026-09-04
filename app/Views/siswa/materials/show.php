<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $isCompleted  = ($progress['status'] ?? '') === 'completed';
  $isInProgress = ($progress['status'] ?? '') === 'in_progress';
  $contentType  = $material['content_type'] ?? 'text';
  $estMin       = $material['estimated_minutes'] ?? null;
?>

<!-- Top navigation bar -->
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('siswa/materials') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div class="flex-grow-1 overflow-hidden">
    <h5 class="fw-bold mb-0 text-truncate"><?= esc($material['title']) ?></h5>
    <div class="text-muted" style="font-size:0.75rem;">
      <?= esc($material['subject_name'] ?? '') ?>
      <?php if ($estMin): ?>
        &nbsp;·&nbsp;<i class="bi bi-clock"></i> <?= $estMin ?> menit
      <?php endif; ?>
    </div>
  </div>
  <!-- Status badge -->
  <?php if ($isCompleted): ?>
    <span class="badge bg-success flex-shrink-0">
      <i class="bi bi-check2-circle me-1"></i>Selesai
    </span>
  <?php elseif ($isInProgress): ?>
    <span class="badge bg-primary flex-shrink-0">
      <i class="bi bi-play-circle me-1"></i>Sedang dibaca
    </span>
  <?php endif; ?>
</div>

<!-- ── Konten Materi ─────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3 p-md-4">

    <?php if ($contentType === 'text'): ?>
      <!-- ── Teks HTML ── -->
      <div class="material-content">
        <?php if (!empty($material['content'])): ?>
          <?= $material['content'] ?>
        <?php elseif (!empty($material['description'])): ?>
          <p class="text-muted"><?= nl2br(esc($material['description'])) ?></p>
        <?php else: ?>
          <p class="text-muted fst-italic">Konten materi belum tersedia.</p>
        <?php endif; ?>
      </div>

    <?php elseif ($contentType === 'pdf'): ?>
      <!-- ── File PDF ── -->
      <?php if (!empty($material['file_path'])): ?>
        <div class="ratio mb-3" style="--bs-aspect-ratio: 75%; min-height: 420px;">
          <iframe src="<?= base_url('siswa/materials/' . $material['id'] . '/file') ?>"
                  class="rounded border"
                  title="<?= esc($material['title']) ?>"></iframe>
        </div>
        <div class="text-center">
          <a href="<?= base_url('siswa/materials/' . $material['id'] . '/file') ?>"
             target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Buka di Tab Baru
          </a>
        </div>
      <?php else: ?>
        <p class="text-muted fst-italic">File PDF belum tersedia.</p>
      <?php endif; ?>

    <?php elseif ($contentType === 'video'): ?>
      <!-- ── Video Embed ── -->
      <?php if (!empty($material['embed_url'])): ?>
        <div class="ratio ratio-16x9 mb-3">
          <iframe src="<?= esc($material['embed_url']) ?>"
                  title="<?= esc($material['title']) ?>"
                  allowfullscreen
                  class="rounded border"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
          </iframe>
        </div>
      <?php else: ?>
        <p class="text-muted fst-italic">URL video tidak valid.</p>
      <?php endif; ?>
      <?php if (!empty($material['description'])): ?>
        <div class="text-muted small mt-2"><?= nl2br(esc($material['description'])) ?></div>
      <?php endif; ?>

    <?php elseif ($contentType === 'link'): ?>
      <!-- ── Link Eksternal ── -->
      <div class="text-center py-4">
        <i class="bi bi-link-45deg text-primary" style="font-size:3rem;"></i>
        <p class="mt-2 mb-1 fw-semibold"><?= esc($material['title']) ?></p>
        <?php if (!empty($material['description'])): ?>
          <p class="text-muted small mb-3"><?= nl2br(esc($material['description'])) ?></p>
        <?php endif; ?>
        <a href="<?= esc($material['external_link']) ?>"
           target="_blank" rel="noopener noreferrer"
           class="btn btn-primary"
           id="btnOpenLink">
          <i class="bi bi-box-arrow-up-right me-2"></i>Buka Materi
        </a>
        <div class="text-muted mt-2" style="font-size:0.75rem; word-break:break-all;">
          <?= esc($material['external_link']) ?>
        </div>
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
        Tandai selesai agar guru bisa melihat progres belajarmu.
      </div>
    </div>
    <button class="btn btn-success" id="btnMarkComplete">
      <i class="bi bi-check2-circle me-1"></i>Tandai Sudah Selesai
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

<!-- ── Navigasi Prev / Next ──────────────────────────────────────────── -->
<div class="d-flex justify-content-between gap-3 mb-4">
  <?php if ($prevMat): ?>
    <a href="<?= base_url('siswa/materials/' . $prevMat['id']) ?>"
       class="btn btn-outline-secondary btn-sm flex-grow-1 text-start">
      <i class="bi bi-chevron-left me-1"></i>
      <span class="d-none d-sm-inline">Sebelumnya:</span>
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:160px; vertical-align:middle;">
        <?= esc($prevMat['title']) ?>
      </span>
    </a>
  <?php else: ?>
    <div class="flex-grow-1"></div>
  <?php endif; ?>

  <?php if ($nextMat): ?>
    <a href="<?= base_url('siswa/materials/' . $nextMat['id']) ?>"
       class="btn btn-outline-primary btn-sm flex-grow-1 text-end">
      <span class="fw-semibold text-truncate d-inline-block" style="max-width:160px; vertical-align:middle;">
        <?= esc($nextMat['title']) ?>
      </span>
      <span class="d-none d-sm-inline">:Berikutnya</span>
      <i class="bi bi-chevron-right ms-1"></i>
    </a>
  <?php else: ?>
    <a href="<?= base_url('siswa/materials') ?>"
       class="btn btn-success btn-sm flex-grow-1 text-center">
      <i class="bi bi-grid me-1"></i>Kembali ke Daftar Materi
    </a>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
  /* Typography untuk konten materi HTML */
  .material-content {
    font-size: 0.9rem;
    line-height: 1.7;
    color: #212529;
  }
  .material-content h1, .material-content h2, .material-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
  }
  .material-content h1 { font-size: 1.4rem; }
  .material-content h2 { font-size: 1.2rem; }
  .material-content h3 { font-size: 1.05rem; }
  .material-content p  { margin-bottom: 0.75rem; }
  .material-content ul, .material-content ol {
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
  }
  .material-content blockquote {
    border-left: 4px solid #0d6efd;
    padding: 0.5rem 1rem;
    background: #f8f9ff;
    margin: 1rem 0;
    border-radius: 0 0.25rem 0.25rem 0;
  }
  .material-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
    font-size: 0.85rem;
  }
  .material-content table th,
  .material-content table td {
    border: 1px solid #dee2e6;
    padding: 0.4rem 0.6rem;
  }
  .material-content table th { background: #f8f9fa; font-weight: 600; }
  .material-content img { max-width: 100%; height: auto; border-radius: 0.25rem; display: block; }
  .material-content figure.image { margin: 1rem auto; text-align: center; }
  .material-content figure.image img { margin: 0 auto; max-width: 100%; height: auto; }
  .material-content figure.image.image_resized { display: block; }
  .material-content figure.image.image_resized img { width: 100%; }
  .material-content figure.image.image-style-side { float: right; margin-left: 1.5rem; max-width: 50%; }
  .material-content figure.image.image-style-align-left { float: left; margin-right: 1.5rem; }
  .material-content figure.image.image-style-align-right { float: right; margin-left: 1.5rem; }
  .material-content figcaption { font-size: 0.8rem; color: #6c757d; text-align: center; margin-top: 0.25rem; }
  .material-content code {
    background: #f1f3f5;
    padding: 0.1rem 0.35rem;
    border-radius: 0.2rem;
    font-size: 0.85em;
  }
  .material-content pre {
    background: #212529;
    color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.4rem;
    overflow-x: auto;
    font-size: 0.82rem;
  }
</style>

<script>
(function() {
  'use strict';

  var CSRF_NAME   = "<?= csrf_token() ?>";
  var CSRF_HASH   = "<?= csrf_hash() ?>";
  var COMPLETE_URL = "<?= base_url('siswa/materials/' . $material['id'] . '/complete') ?>";
  var LINK_URL    = <?= !empty($material['external_link']) ? json_encode($material['external_link']) : 'null' ?>;

  // ── Tombol tandai selesai ────────────────────────────────────────────
  var btnComplete = document.getElementById('btnMarkComplete');
  if (btnComplete) {
    btnComplete.addEventListener('click', function() {
      var btn = this;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

      var body = {};
      body[CSRF_NAME] = CSRF_HASH;

      fetch(COMPLETE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams(body).toString(),
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          // Ganti tombol dengan alert sukses
          var wrap = btn.closest('.card');
          if (wrap) {
            wrap.outerHTML =
              '<div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-4 py-2">' +
              '<i class="bi bi-check-circle-fill fs-5"></i>' +
              '<div><strong>Selesai!</strong> Materi ini ditandai sudah selesai dibaca.</div>' +
              '</div>';
          }
          // Update badge di navbar notifikasi jika perlu
          if (window.prevCount !== undefined) window.prevCount = -1;
        } else {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Tandai Sudah Selesai';
          alert(data.message || 'Gagal menyimpan. Coba lagi.');
        }
      })
      .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Tandai Sudah Selesai';
      });
    });
  }

  // ── Saat buka link eksternal, otomatis tandai selesai setelah klik ───
  var btnLink = document.getElementById('btnOpenLink');
  if (btnLink && LINK_URL) {
    btnLink.addEventListener('click', function() {
      // Kirim markComplete di background (fire-and-forget)
      var body = {};
      body[CSRF_NAME] = CSRF_HASH;
      fetch(COMPLETE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams(body).toString(),
      }).catch(function(){});
    });
  }

  // ── Auto-scroll ke atas saat halaman load ────────────────────────────
  window.scrollTo({ top: 0, behavior: 'instant' });
})();
</script>
<?= $this->endSection() ?>
