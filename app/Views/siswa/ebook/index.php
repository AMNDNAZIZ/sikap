<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .ebook-card-siswa {
    transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
  }
  .ebook-card-siswa:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12) !important;
  }
  .ebook-cover-wrapper-siswa {
    position: relative;
    width: 100%;
    height: 220px;
    background: #f8fafc;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .ebook-cover-img-siswa {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  .ebook-card-siswa:hover .ebook-cover-img-siswa {
    transform: scale(1.04);
  }
  .ebook-placeholder-cover-siswa {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    text-align: center;
    color: #fff;
    position: relative;
    user-select: none;
  }
  .ebook-placeholder-cover-siswa.mapel-bg {
    background: linear-gradient(145deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
  }
  .ebook-placeholder-cover-siswa.umum-bg {
    background: linear-gradient(145deg, #065f46 0%, #059669 50%, #34d399 100%);
  }
  .text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<div class="mb-3">
  <h3 class="mb-0">📚 Perpustakaan Digital</h3>
  <p class="text-muted small mb-0">Baca dan pelajari buku teks serta bahan bacaan umum secara daring</p>
</div>

<?php if (!empty($noActiveClass)): ?>
  <div class="card border-0 shadow-sm text-center text-muted py-5">
    <div class="card-body">
      <p class="display-3 mb-2">📭</p>
      <h5>Belum Terdaftar di Kelas Aktif</h5>
      <p class="small">Anda belum terdaftar di kelas aktif. Silakan hubungi wali kelas atau pihak kurikulum.</p>
    </div>
  </div>
<?php else: ?>

  <!-- Search & Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="get" action="<?= base_url('siswa/ebook') ?>" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Cari judul buku..." value="<?= esc($search ?? '') ?>">
          </div>
        </div>
        <div class="col-12 col-md-4">
          <select name="subject_id" class="form-select form-select-sm">
            <option value="">Semua Mata Pelajaran</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($subjectFilter ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-secondary btn-sm w-100">
            <i class="bi bi-funnel me-1"></i> Filter
          </button>
        </div>
        <?php if (!empty($search) || !empty($subjectFilter)): ?>
          <div class="col-6 col-md-1">
            <a href="<?= base_url('siswa/ebook') ?>" class="btn btn-outline-danger btn-sm w-100" title="Reset">✕</a>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($books)): ?>
    <div class="card border-0 shadow-sm text-center text-muted py-5">
      <div class="card-body">
        <p class="display-3 mb-2">📭</p>
        <p class="mb-0">Tidak ada buku ditemukan untuk kategori ini.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
      <?php foreach ($books as $book): ?>
        <?php
          $isUmum = ($book['book_type'] ?? 'mapel') === 'umum';
          $hasCover = !empty($book['cover_image']) && file_exists(UPLOAD_PATH . 'ebook_covers/' . $book['cover_image']);
        ?>
        <div class="col">
          <div class="card h-100 ebook-card-siswa shadow-sm d-flex flex-column">
            <!-- Cover Thumbnail -->
            <div class="ebook-cover-wrapper-siswa">
              <div style="position: absolute; top: 8px; left: 8px; z-index: 2;">
                <?php if ($isUmum): ?>
                  <span class="badge bg-success shadow-sm">📖 Umum</span>
                <?php else: ?>
                  <span class="badge bg-info text-dark shadow-sm">Kelas <?= $book['level'] ?></span>
                <?php endif; ?>
              </div>

              <?php if (!empty($book['religion'])): ?>
                <div style="position: absolute; top: 8px; right: 8px; z-index: 2;">
                  <span class="badge bg-secondary shadow-sm"><?= esc($book['religion']) ?></span>
                </div>
              <?php endif; ?>

              <div style="position: absolute; bottom: 8px; right: 8px; z-index: 2;">
                <span class="badge bg-dark bg-opacity-75 shadow-sm">
                  <?= number_format(($book['file_size'] ?? 0) / 1048576, 1) ?> MB
                </span>
              </div>

              <?php if ($hasCover): ?>
                <img src="<?= base_url('uploads/ebook_covers/' . esc($book['cover_image'])) ?>"
                     alt="<?= esc($book['title']) ?>"
                     class="ebook-cover-img-siswa"
                     loading="lazy">
              <?php else: ?>
                <div class="ebook-placeholder-cover-siswa <?= $isUmum ? 'umum-bg' : 'mapel-bg' ?>">
                  <div style="font-size: 2.8rem; margin-bottom: 0.35rem; opacity: 0.9;">
                    <i class="bi <?= $isUmum ? 'bi-journal-richtext' : 'bi-mortarboard' ?>"></i>
                  </div>
                  <div style="font-size: 0.8rem; font-weight: 700; line-height: 1.25; max-height: 3.6em; overflow: hidden;">
                    <?= esc($book['title']) ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Card Body -->
            <div class="card-body p-3 d-flex flex-column flex-grow-1">
              <h6 class="card-title fw-bold text-dark text-truncate-2 mb-1" title="<?= esc($book['title']) ?>">
                <?= esc($book['title']) ?>
              </h6>
              <p class="card-text text-muted small flex-grow-1 mb-3">
                <?php if ($isUmum): ?>
                  <span class="text-success"><i class="bi bi-book me-1"></i>Buku Umum</span>
                <?php else: ?>
                  <span class="text-primary"><i class="bi bi-journal-text me-1"></i><?= esc($book['subject_name'] ?? '-') ?></span>
                <?php endif; ?>
                <?php if (!empty($book['description'])): ?>
                  <br><span style="font-size: 0.78rem;"><?= esc(mb_substr($book['description'], 0, 75)) ?><?= mb_strlen($book['description'] ?? '') > 75 ? '...' : '' ?></span>
                <?php endif; ?>
              </p>
              <div class="d-flex gap-2 mt-auto pt-2 border-top">
                <a href="<?= base_url('siswa/ebook/read/' . $book['id']) ?>" class="btn btn-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1">
                  <i class="bi bi-eye"></i> <span>Baca</span>
                </a>
                <a href="<?= base_url('siswa/ebook/download/' . $book['id']) ?>" class="btn btn-outline-secondary btn-sm" title="Unduh PDF">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
