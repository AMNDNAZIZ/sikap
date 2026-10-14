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
     SEGMEN PEMBELAJARAN (MICRO-LEARNING STEPPER / SEGMEN)
════════════════════════════════════════════════════════════════ -->
<?php if (!empty($segments)): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h6 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3-fill"></i>Alur Segmen Pembelajaran
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.75rem;">
          <?= count($segments) ?> Langkah
        </span>
      </h6>
      <div class="text-muted" style="font-size:0.75rem;">
        Siswa akan mempelajari setiap segmen secara berurutan dan menandai selesai tiap langkah.
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddSegment">
      <i class="bi bi-plus-lg me-1"></i>Tambah Segmen
    </button>
  </div>

  <div class="card-body p-0">
    <div class="list-group list-group-flush" id="segmentList">
      <?php foreach ($segments as $sIdx => $seg): ?>
        <?php
          $segType = $seg['content_type'];
          $typeBadge = match($segType) {
            'video' => ['bg-danger-subtle text-danger border-danger-subtle', 'bi-camera-video', 'Video'],
            'html'  => ['bg-info-subtle text-info border-info-subtle', 'bi-code-slash', 'HTML Interaktif'],
            'slide' => ['bg-warning-subtle text-warning-emphasis border-warning-subtle', 'bi-images', 'Slide'],
            'pdf'   => ['bg-danger-subtle text-danger border-danger-subtle', 'bi-file-earmark-pdf', 'PDF'],
            'link'  => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'bi-link-45deg', 'Tautan'],
            default => ['bg-primary-subtle text-primary border-primary-subtle', 'bi-file-text', 'Teks'],
          };
        ?>
        <div class="list-group-item p-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
              <span class="badge bg-secondary rounded-pill px-2 py-1" style="font-size:0.7rem;">
                #<?= $sIdx + 1 ?>
              </span>
              <span class="badge rounded-pill <?= $typeBadge[0] ?> border px-2 py-1" style="font-size:0.7rem;">
                <i class="bi <?= $typeBadge[1] ?> me-1"></i><?= $typeBadge[2] ?>
              </span>
              <strong class="text-dark text-truncate" style="font-size:0.92rem;">
                <?= esc($seg['title']) ?>
              </strong>
              <?php if (!empty($seg['estimated_minutes'])): ?>
                <span class="text-muted small ms-1" style="font-size:0.75rem;">
                  <i class="bi bi-clock me-1"></i><?= $seg['estimated_minutes'] ?> mnt
                </span>
              <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <button class="btn btn-xs btn-outline-secondary py-1 px-2" type="button"
                      data-bs-toggle="collapse" data-bs-target="#collapseSeg<?= $seg['id'] ?>"
                      aria-expanded="false" title="Pratinjau Konten Segmen">
                <i class="bi bi-eye me-1"></i>Pratinjau
              </button>
              <form method="post" action="<?= site_url('admin/materials/segment-delete/' . $seg['id']) ?>"
                    class="d-inline" onsubmit="return confirm('Yakin ingin menghapus segmen ini?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" title="Hapus Segmen">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>

          <!-- Preview Collapse -->
          <div class="collapse mt-3 pt-3 border-top" id="collapseSeg<?= $seg['id'] ?>">
            <div class="p-3 bg-light rounded-3">
              <?php if ($segType === 'text'): ?>
                <div class="material-content bg-white p-3 rounded border">
                  <?= !empty($seg['content']) ? $seg['content'] : '<em class="text-muted">Konten teks belum diisi.</em>' ?>
                </div>

              <?php elseif ($segType === 'video'): ?>
                <?php if (!empty($seg['embed_url'])): ?>
                  <div class="ratio ratio-16x9 mb-2" style="max-height: 400px;">
                    <iframe src="<?= esc($seg['embed_url']) ?>" title="<?= esc($seg['title']) ?>"
                            allowfullscreen class="rounded border"></iframe>
                  </div>
                <?php else: ?>
                  <div class="text-muted small">URL Video: <?= esc($seg['video_url'] ?? '-') ?></div>
                <?php endif; ?>

              <?php elseif ($segType === 'html'): ?>
                <?php if (!empty($seg['content_html'])): ?>
                  <?= view('admin/materials/_html_frame', ['html' => $seg['content_html']]) ?>
                <?php else: ?>
                  <p class="text-muted small">Kode HTML belum diisi.</p>
                <?php endif; ?>

              <?php elseif ($segType === 'slide'): ?>
                <?php if (!empty($seg['slides'])): ?>
                  <?= view('admin/materials/_slide_show', ['slides' => $seg['slides'], 'canComplete' => false]) ?>
                <?php else: ?>
                  <p class="text-muted small">Belum ada slide gambar.</p>
                <?php endif; ?>

              <?php elseif ($segType === 'pdf'): ?>
                <?php if (!empty($seg['file_path'])): ?>
                  <div class="ratio mb-2" style="--bs-aspect-ratio: 65%; min-height: 380px;">
                    <iframe src="<?= site_url('admin/materials/segment-file/' . $seg['id']) ?>" class="rounded border"></iframe>
                  </div>
                <?php else: ?>
                  <p class="text-muted small">File PDF belum tersedia.</p>
                <?php endif; ?>

              <?php elseif ($segType === 'link'): ?>
                <div class="p-3 bg-white rounded border text-center">
                  <i class="bi bi-link-45deg text-primary fs-3"></i>
                  <div class="fw-semibold small my-1"><?= esc($seg['title']) ?></div>
                  <a href="<?= esc($seg['external_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Tautan Luar
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php else: ?>
  <!-- KONTEN SUB MATERI TUNGGAL -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span class="badge bg-secondary-subtle text-secondary border px-3 py-1">
        Konten Pertemuan Tunggal
      </span>
      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddSegment">
        <i class="bi bi-diagram-3 me-1"></i>Pecah Jadi Segmen Bertahap
      </button>
    </div>
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
      <?php elseif ($contentType === 'html'): ?>
        <?php if (!empty($subMat['content'])): ?>
          <?= view('admin/materials/_html_frame', ['html' => $subMat['content']]) ?>
        <?php else: ?>
          <p class="text-muted fst-italic">Konten belum tersedia.</p>
        <?php endif; ?>
      <?php elseif ($contentType === 'slide'): ?>
        <?= view('admin/materials/_slide_show', ['slides' => $slides ?? [], 'canComplete' => false]) ?>
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
        <?php
          $embedUrl = $subMat['embed_url'] ?? '';
          $canEmbed = $embedUrl && (strpos($embedUrl, '/embed/') !== false
                      || strpos($embedUrl, 'player.vimeo.com') !== false);
        ?>
        <?php if ($canEmbed): ?>
          <div class="ratio ratio-16x9 mb-3">
            <iframe src="<?= esc($embedUrl) ?>"
                    title="<?= esc($subMat['title']) ?>" allowfullscreen class="rounded border"
                    referrerpolicy="strict-origin-when-cross-origin"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share">
            </iframe>
          </div>
        <?php endif; ?>
      <?php elseif ($contentType === 'link'): ?>
        <div class="text-center py-4">
          <i class="bi bi-link-45deg text-primary" style="font-size:3rem;"></i>
          <p class="mt-2 mb-1 fw-semibold"><?= esc($subMat['title']) ?></p>
          <a href="<?= esc($subMat['external_link']) ?>" target="_blank" rel="noopener noreferrer"
             class="btn btn-primary">
            <i class="bi bi-box-arrow-up-right me-2"></i>Buka Materi
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

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
     KUIS TERKAIT
════════════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <?php if (!empty($relatedQuizzes)): ?>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h6 class="fw-bold mb-0">
          <i class="bi bi-patch-question me-2 text-success"></i>Kuis Terkait Materi Ini
        </h6>
        <a href="<?= site_url('admin/quiz/create?material_id=' . $subMat['id']) ?>"
           class="btn btn-sm btn-outline-success">
          <i class="bi bi-plus-lg me-1"></i>Tambah Kuis
        </a>
      </div>
      <div class="alert alert-success-subtle border border-success-subtle small py-2 mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Materi ini memiliki <strong><?= count($relatedQuizzes) ?> kuis</strong> yang terkait.
        Siswa dapat mengerjakannya dari halaman materi.
      </div>
      <div class="list-group">
        <?php foreach ($relatedQuizzes as $qz): ?>
          <a href="<?= site_url('admin/quiz/edit/' . $qz['id']) ?>"
             class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
            <div class="me-2">
              <div class="fw-semibold small">
                <i class="bi bi-journal-check me-1 text-success"></i><?= esc($qz['title']) ?>
              </div>
              <div class="text-muted" style="font-size:0.72rem;">
                <?php if (!empty($qz['bank_code'])): ?>
                  Bank: <?= esc($qz['bank_code']) ?> &middot;
                <?php endif; ?>
                <?= (int)$qz['duration'] > 0 ? (int)$qz['duration'] . ' menit' : 'Tanpa batas waktu' ?> &middot;
                <?= (int)$qz['max_attempts'] > 0 ? (int)$qz['max_attempts'] . 'x percobaan' : 'Percobaan bebas' ?>
              </div>
            </div>
            <?php if ((int)$qz['is_published'] === 1): ?>
              <span class="badge bg-success">Terbit</span>
            <?php else: ?>
              <span class="badge bg-secondary">Draf</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-3">
        <i class="bi bi-patch-question text-muted" style="font-size:2.2rem;"></i>
        <p class="mb-1 mt-2 fw-semibold small">Belum ada kuis untuk materi ini</p>
        <p class="text-muted small mb-3">
          Buat kuis mandiri agar siswa bisa berlatih setelah mempelajari materi ini.
        </p>
        <a href="<?= site_url('admin/quiz/create?material_id=' . $subMat['id']) ?>"
           class="btn btn-success btn-sm">
          <i class="bi bi-plus-circle me-1"></i>Buat Kuis Mandiri
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

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

<!-- Modal Tambah Segmen -->
<div class="modal fade" id="modalAddSegment" tabindex="-1" aria-labelledby="modalAddSegmentLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= site_url('admin/materials/segment-store/' . $subMat['id']) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h6 class="modal-title fw-bold" id="modalAddSegmentLabel">
            <i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Segmen Pembelajaran
          </h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Judul Segmen <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control form-control-sm" required
                     placeholder="cth: Langkah 1: Pengantar Konsep">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Tipe Konten <span class="text-danger">*</span></label>
              <select name="content_type" id="modalSegType" class="form-select form-select-sm" required>
                <option value="text">Teks &amp; Penjelasan (Editor)</option>
                <option value="video">Video YouTube / Vimeo</option>
                <option value="html">HTML Interaktif (Simulasi/Praktik)</option>
                <option value="slide">Slide Gambar</option>
                <option value="pdf">Dokumen PDF</option>
                <option value="link">Tautan Eksternal</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-semibold">Estimasi (menit)</label>
              <input type="number" name="estimated_minutes" class="form-control form-control-sm"
                     min="1" max="180" value="15">
            </div>

            <!-- Dynamic panels -->
            <div class="col-12 modal-seg-panel" id="modalSegPanelText">
              <label class="form-label small fw-semibold">Penjelasan Teks</label>
              <textarea name="content" class="form-control" rows="8" placeholder="Tuliskan uraian materi di sini..."></textarea>
            </div>
            <div class="col-12 modal-seg-panel" id="modalSegPanelVideo" style="display:none;">
              <label class="form-label small fw-semibold">URL Video YouTube / Vimeo</label>
              <input type="url" name="video_url" class="form-control form-control-sm" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="col-12 modal-seg-panel" id="modalSegPanelHtml" style="display:none;">
              <label class="form-label small fw-semibold">Kode HTML / JS Interaktif</label>
              <textarea name="content_html" class="form-control font-monospace form-control-sm" rows="10" placeholder="<!DOCTYPE html>... Kode interaktif"></textarea>
              <div class="form-text">Dijalankan dalam iframe sandbox yang terisolasi.</div>
            </div>
            <div class="col-12 modal-seg-panel" id="modalSegPanelSlide" style="display:none;">
              <label class="form-label small fw-semibold">Unggah Gambar Slide</label>
              <input type="file" name="slide_images[]" class="form-control form-control-sm" accept="image/*" multiple>
              <div class="form-text">Maks 5 MB per gambar.</div>
            </div>
            <div class="col-12 modal-seg-panel" id="modalSegPanelPdf" style="display:none;">
              <label class="form-label small fw-semibold">Unggah File PDF</label>
              <input type="file" name="file_upload" class="form-control form-control-sm" accept=".pdf">
              <div class="form-text">Maks 10 MB.</div>
            </div>
            <div class="col-12 modal-seg-panel" id="modalSegPanelLink" style="display:none;">
              <label class="form-label small fw-semibold">URL Tautan Eksternal</label>
              <input type="url" name="external_link" class="form-control form-control-sm" placeholder="https://...">
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-save me-1"></i>Simpan Segmen
          </button>
        </div>
      </form>
    </div>
  </div>
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
          <!-- Upvote (bisa diklik) -->
          <button type="button"
                  class="btn-upvote d-flex align-items-center gap-1 text-<?= $upvoted ? 'primary' : 'muted' ?>"
                  data-reply-id="<?= $rid ?>"
                  title="Suka"
                  style="font-size:0.72rem; border:none; background:none; cursor:pointer; padding:0 4px;">
            <i class="bi bi-hand-thumbs-up<?= $upvoted ? '-fill' : '' ?>"></i>
            <span class="uv-count"><?= $upvotes ?></span>
          </button>

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

// ── Upvote / Suka komentar ────────────────────────────────────────
var UPVOTE_URL = "<?= base_url('forum/reply') ?>/";
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.btn-upvote');
  if (!btn) return;

  var rid  = btn.dataset.replyId;
  var body = new URLSearchParams();
  body.append(CSRF_NAME, CSRF_HASH);

  btn.disabled = true;
  fetch(UPVOTE_URL + rid + '/upvote', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
    body: body.toString()
  }).then(function(r){ return r.json(); }).then(function(d){
    btn.disabled = false;
    if (d && d.success) {
      btn.querySelector('.uv-count').textContent = d.count;
      var icon   = btn.querySelector('i');
      var filled = icon.classList.contains('bi-hand-thumbs-up-fill');
      icon.classList.toggle('bi-hand-thumbs-up',      filled);
      icon.classList.toggle('bi-hand-thumbs-up-fill', !filled);
      btn.classList.toggle('text-primary', !filled);
      btn.classList.toggle('text-muted',    filled);
    }
  }).catch(function(){ btn.disabled = false; });
});

// ── Tipe Konten Modal Tambah Segmen ───────────────────────────────
var modalSegType = document.getElementById('modalSegType');
if (modalSegType) {
  modalSegType.addEventListener('change', function() {
    var val = this.value;
    document.querySelectorAll('.modal-seg-panel').forEach(function(p) {
      p.style.display = 'none';
    });
    var p = document.getElementById('modalSegPanel' + val.charAt(0).toUpperCase() + val.slice(1));
    if (p) p.style.display = 'block';
  });
}

})();
</script>
<?= $this->endSection() ?>
