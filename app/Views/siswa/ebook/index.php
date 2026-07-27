<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📚 Perpustakaan Digital</h3>

<?php if (!empty($noActiveClass)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-1">📭</p>
    <p>Anda belum terdaftar di kelas aktif. Hubungi wali kelas atau admin.</p>
  </div>
<?php else: ?>

  <!-- Search & Filter -->
  <form method="get" action="<?= base_url('siswa/ebook') ?>" class="row g-2 mb-3">
    <div class="col-md-5">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="🔍 Cari buku..." value="<?= esc($search ?? '') ?>">
    </div>
    <div class="col-md-4">
      <select name="subject_id" class="form-select form-select-sm">
        <option value="">Semua Mata Pelajaran</option>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($subjectFilter ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Filter</button>
    </div>
    <?php if (!empty($search) || !empty($subjectFilter)): ?>
      <div class="col-md-1">
        <a href="<?= base_url('siswa/ebook') ?>" class="btn btn-outline-danger btn-sm w-100">✕</a>
      </div>
    <?php endif; ?>
  </form>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>

  <?php if (empty($books)): ?>
    <div class="text-center text-muted py-5">
      <p class="fs-1">📭</p>
      <p>Tidak ada buku ditemukan untuk kategori ini.</p>
    </div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
      <?php foreach ($books as $book): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <?php if (($book['book_type'] ?? 'mapel') === 'umum'): ?>
                  <span class="badge bg-success">📖 Umum</span>
                <?php else: ?>
                  <span class="badge bg-info">Kelas <?= $book['level'] ?></span>
                <?php endif; ?>
                <?php if (!empty($book['religion'])): ?>
                  <span class="badge bg-secondary"><?= esc($book['religion']) ?></span>
                <?php endif; ?>
              </div>
              <h6 class="card-title"><?= esc($book['title']) ?></h6>
              <p class="card-text text-muted small flex-grow-1">
                <?php if (($book['book_type'] ?? 'mapel') === 'umum'): ?>
                  📖 Buku Umum
                <?php else: ?>
                  📘 <?= esc($book['subject_name'] ?? '-') ?>
                <?php endif; ?>
                <?php if (!empty($book['description'])): ?>
                  <br><?= esc(mb_substr($book['description'], 0, 100)) ?><?= mb_strlen($book['description'] ?? '') > 100 ? '...' : '' ?>
                <?php endif; ?>
              </p>
              <div class="d-flex gap-2 mt-auto">
                <a href="<?= base_url('siswa/ebook/read/' . $book['id']) ?>" class="btn btn-primary btn-sm flex-fill">👁️ Baca</a>
                <a href="<?= base_url('siswa/ebook/download/' . $book['id']) ?>" class="btn btn-outline-secondary btn-sm">⬇️</a>
              </div>
            </div>
            <div class="card-footer text-muted small">
              <?= number_format(($book['file_size'] ?? 0) / 1048576, 1) ?> MB
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
