<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  /* Ebook Card Styles */
  .ebook-card {
    transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
  }
  .ebook-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12) !important;
    border-color: #cbd5e1;
  }
  .ebook-cover-wrapper {
    position: relative;
    width: 100%;
    height: 240px;
    background: #f8fafc;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .ebook-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  .ebook-card:hover .ebook-cover-img {
    transform: scale(1.04);
  }
  /* Fallback placeholder cover */
  .ebook-placeholder-cover {
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
  .ebook-placeholder-cover.mapel-bg {
    background: linear-gradient(145deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
  }
  .ebook-placeholder-cover.umum-bg {
    background: linear-gradient(145deg, #065f46 0%, #059669 50%, #34d399 100%);
  }
  .ebook-placeholder-cover .cover-decor-icon {
    font-size: 3.2rem;
    margin-bottom: 0.5rem;
    opacity: 0.92;
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
  }
  .ebook-placeholder-cover .cover-title-preview {
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.25;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    max-height: 3.8em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }
  .text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  /* Overlay badges on cover */
  .cover-badge-top-left {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
  }
  .cover-badge-top-right {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
  }
  .cover-badge-bottom-right {
    position: absolute;
    bottom: 10px;
    right: 10px;
    z-index: 2;
  }
  .cover-badge-bottom-left {
    position: absolute;
    bottom: 10px;
    left: 10px;
    z-index: 2;
  }
</style>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
  <div>
    <h3 class="mb-0">📚 Perpustakaan Digital</h3>
    <p class="text-muted small mb-0">Kelola buku pelajaran dan koleksi buku bacaan digital sekolah</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <!-- View Switcher -->
    <div class="btn-group btn-group-sm" role="group" aria-label="Tampilan">
      <button type="button" class="btn btn-outline-secondary active" id="btnViewGrid" title="Tampilan Grid">
        <i class="bi bi-grid-fill me-1"></i> Grid
      </button>
      <button type="button" class="btn btn-outline-secondary" id="btnViewTable" title="Tampilan Tabel">
        <i class="bi bi-list-ul me-1"></i> Tabel
      </button>
    </div>
    <a href="<?= base_url('admin/ebook/create') ?>" class="btn btn-primary btn-sm shadow-sm">
      <i class="bi bi-plus-lg me-1"></i> Upload Buku
    </a>
  </div>
</div>

<!-- Filter & Search Card -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3">
    <form method="get" action="<?= base_url('admin/ebook') ?>" class="row g-2 align-items-center">
      <div class="col-12 col-md-4 col-lg-3">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Cari judul atau mapel..." value="<?= esc($search ?? '') ?>">
        </div>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <select name="book_type" class="form-select form-select-sm">
          <option value="">Semua Jenis</option>
          <option value="mapel" <?= ($bookTypeFilter ?? '') === 'mapel' ? 'selected' : '' ?>>📘 Mapel</option>
          <option value="umum" <?= ($bookTypeFilter ?? '') === 'umum' ? 'selected' : '' ?>>📖 Umum</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <select name="level" class="form-select form-select-sm">
          <option value="">Semua Level</option>
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?= $i ?>" <?= ($levelFilter ?? '') == $i ? 'selected' : '' ?>>Kelas <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-md-4 col-lg-3">
        <select name="subject_id" class="form-select form-select-sm">
          <option value="">Semua Mapel</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($subjectFilter ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-auto">
        <button type="submit" class="btn btn-secondary btn-sm w-100">
          <i class="bi bi-funnel me-1"></i> Filter
        </button>
      </div>
      <?php if (!empty($search) || !empty($levelFilter) || !empty($subjectFilter) || !empty($bookTypeFilter)): ?>
        <div class="col-6 col-md-auto">
          <a href="<?= base_url('admin/ebook') ?>" class="btn btn-outline-danger btn-sm w-100" title="Reset Filter">
            <i class="bi bi-x-circle me-1"></i> Reset
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($books)): ?>
  <div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
      <div class="display-3 text-muted mb-3">📭</div>
      <h5 class="fw-bold text-dark">Belum Ada Buku Ditemukan</h5>
      <p class="text-muted small mb-3">
        <?php if (!empty($search) || !empty($levelFilter) || !empty($subjectFilter) || !empty($bookTypeFilter)): ?>
          Tidak ada buku yang sesuai dengan filter pencarian Anda.
        <?php else: ?>
          Perpustakaan digital masih kosong. Klik tombol di bawah untuk mengunggah buku baru.
        <?php endif; ?>
      </p>
      <a href="<?= base_url('admin/ebook/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Buku Sekarang
      </a>
    </div>
  </div>
<?php else: ?>

  <!-- Info Ringkasan -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
      Menampilkan <strong><?= count($books) ?></strong> buku digital
    </div>
  </div>

  <!-- 1. GRID VIEW (DEFAULT) -->
  <div id="ebookGridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3 g-md-4 mb-4">
    <?php foreach ($books as $book): ?>
      <?php
        $isUmum = ($book['book_type'] ?? 'mapel') === 'umum';
        $hasCover = !empty($book['cover_image']) && file_exists(UPLOAD_PATH . 'ebook_covers/' . $book['cover_image']);
      ?>
      <div class="col" id="card-<?= $book['id'] ?>">
        <div class="card h-100 ebook-card shadow-sm d-flex flex-column">
          <!-- Cover Thumbnail -->
          <div class="ebook-cover-wrapper">
            <!-- Badges -->
            <div class="cover-badge-top-left">
              <?php if ($isUmum): ?>
                <span class="badge bg-success shadow-sm"><i class="bi bi-book me-1"></i>Umum</span>
              <?php else: ?>
                <span class="badge bg-primary shadow-sm"><i class="bi bi-journal-bookmark me-1"></i>Mapel</span>
              <?php endif; ?>
            </div>

            <div class="cover-badge-top-right">
              <?php if (!empty($book['level'])): ?>
                <span class="badge bg-dark bg-opacity-75 shadow-sm">Kelas <?= $book['level'] ?></span>
              <?php else: ?>
                <span class="badge bg-secondary bg-opacity-75 shadow-sm">Semua Kelas</span>
              <?php endif; ?>
            </div>

            <?php if (!empty($book['religion'])): ?>
              <div class="cover-badge-bottom-left">
                <span class="badge bg-warning text-dark shadow-sm">
                  <i class="bi bi-moon-stars-fill me-1"></i><?= esc($book['religion']) ?>
                </span>
              </div>
            <?php endif; ?>

            <div class="cover-badge-bottom-right">
              <span class="badge bg-dark bg-opacity-75 shadow-sm">
                <?= number_format(($book['file_size'] ?? 0) / 1048576, 1) ?> MB
              </span>
            </div>

            <?php if ($hasCover): ?>
              <img src="<?= base_url('uploads/ebook_covers/' . esc($book['cover_image'])) ?>"
                   alt="<?= esc($book['title']) ?>"
                   class="ebook-cover-img"
                   loading="lazy">
            <?php else: ?>
              <!-- Fallback Gradient Cover Placeholder -->
              <div class="ebook-placeholder-cover <?= $isUmum ? 'umum-bg' : 'mapel-bg' ?>">
                <div class="cover-decor-icon">
                  <i class="bi <?= $isUmum ? 'bi-journal-richtext' : 'bi-mortarboard' ?>"></i>
                </div>
                <div class="cover-title-preview">
                  <?= esc($book['title']) ?>
                </div>
                <div class="mt-2 text-white-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                  <?= esc($book['subject_name'] ?? ($isUmum ? 'BUKU UMUM' : 'BUKU MAPEL')) ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Card Body -->
          <div class="card-body p-3 d-flex flex-column flex-grow-1">
            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-1" title="<?= esc($book['title']) ?>">
              <?= esc($book['title']) ?>
            </h6>

            <div class="text-primary small fw-semibold mb-2">
              <i class="bi bi-journal-text me-1"></i><?= esc($book['subject_name'] ?? ($isUmum ? 'Buku Bacaan Umum' : '-')) ?>
            </div>

            <?php if (!empty($book['description'])): ?>
              <p class="card-text text-muted small text-truncate-2 mb-3 flex-grow-1" style="font-size: 0.78rem; line-height: 1.4;" title="<?= esc($book['description']) ?>">
                <?= esc($book['description']) ?>
              </p>
            <?php else: ?>
              <p class="card-text text-muted small fst-italic mb-3 flex-grow-1" style="font-size: 0.78rem;">
                Tidak ada deskripsi tambahan.
              </p>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-1 pt-2 border-top mt-auto">
              <a href="<?= base_url('admin/ebook/file/read/' . $book['id']) ?>"
                 class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1"
                 target="_blank"
                 title="Baca Online">
                <i class="bi bi-eye"></i> <span>Baca</span>
              </a>
              <a href="<?= base_url('admin/ebook/edit/' . $book['id']) ?>"
                 class="btn btn-outline-warning btn-sm"
                 title="Edit Metadata & Cover">
                <i class="bi bi-pencil"></i>
              </a>
              <button type="button"
                      class="btn btn-outline-danger btn-sm btn-delete"
                      data-id="<?= $book['id'] ?>"
                      data-title="<?= esc($book['title']) ?>"
                      title="Hapus Buku">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- 2. TABLE VIEW (ALTERNATIF TOGGLE) -->
  <div id="ebookTableView" class="table-responsive shadow-sm rounded mb-4" style="display:none;">
    <table class="table table-bordered table-hover align-middle mb-0 bg-white">
      <thead class="table-light">
        <tr>
          <th style="width: 50px;">#</th>
          <th style="width: 80px;">Cover</th>
          <th>Judul & Deskripsi</th>
          <th>Jenis</th>
          <th>Level</th>
          <th>Mata Pelajaran</th>
          <th>Agama</th>
          <th>Ukuran</th>
          <th style="width: 140px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($books as $book): ?>
          <?php
            $isUmum = ($book['book_type'] ?? 'mapel') === 'umum';
            $hasCover = !empty($book['cover_image']) && file_exists(UPLOAD_PATH . 'ebook_covers/' . $book['cover_image']);
          ?>
          <tr id="row-<?= $book['id'] ?>">
            <td><?= $no++ ?></td>
            <td class="text-center p-2">
              <?php if ($hasCover): ?>
                <img src="<?= base_url('uploads/ebook_covers/' . esc($book['cover_image'])) ?>"
                     alt="Cover"
                     class="rounded border shadow-sm"
                     style="width: 48px; height: 64px; object-fit: cover;">
              <?php else: ?>
                <div class="rounded border shadow-sm d-inline-flex flex-column align-items-center justify-content-center text-white <?= $isUmum ? 'umum-bg' : 'mapel-bg' ?>"
                     style="width: 48px; height: 64px; font-size: 1.2rem;">
                  <i class="bi bi-book"></i>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= esc($book['title']) ?></strong>
              <?php if (!empty($book['description'])): ?>
                <br><small class="text-muted"><?= esc(mb_substr($book['description'], 0, 90)) ?><?= mb_strlen($book['description']) > 90 ? '...' : '' ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isUmum): ?>
                <span class="badge bg-success">📖 Umum</span>
              <?php else: ?>
                <span class="badge bg-primary">📘 Mapel</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($book['level'])): ?>
                <span class="badge bg-info">Kelas <?= $book['level'] ?></span>
              <?php else: ?>
                <span class="text-muted">Semua</span>
              <?php endif; ?>
            </td>
            <td><?= esc($book['subject_name'] ?? '-') ?></td>
            <td><?= esc($book['religion'] ?? '-') ?></td>
            <td><?= number_format(($book['file_size'] ?? 0) / 1048576, 1) ?> MB</td>
            <td>
              <div class="btn-group btn-group-sm">
                <a href="<?= base_url('admin/ebook/file/read/' . $book['id']) ?>" class="btn btn-info text-white" target="_blank" title="Baca Online">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url('admin/ebook/edit/' . $book['id']) ?>" class="btn btn-warning" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-danger btn-delete" data-id="<?= $book['id'] ?>" data-title="<?= esc($book['title']) ?>" title="Hapus">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title text-danger">
          <i class="bi bi-trash3 me-2"></i>Hapus Buku Digital
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3">
        <p class="mb-2">Apakah Anda yakin ingin menghapus buku berikut?</p>
        <div class="p-3 bg-light rounded border mb-2">
          <strong id="deleteBookTitle" class="text-dark"></strong>
        </div>
        <p class="text-danger small mb-0">
          <i class="bi bi-exclamation-circle me-1"></i>File PDF dan gambar cover akan dihapus secara permanen dari server.
        </p>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmDelete">
          <i class="bi bi-trash me-1"></i>Ya, Hapus Buku
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Grid vs Table View Switcher
  const btnViewGrid = document.getElementById('btnViewGrid');
  const btnViewTable = document.getElementById('btnViewTable');
  const ebookGridView = document.getElementById('ebookGridView');
  const ebookTableView = document.getElementById('ebookTableView');

  function setView(view) {
    if (!ebookGridView || !ebookTableView) return;
    if (view === 'table') {
      ebookGridView.style.display = 'none';
      ebookTableView.style.display = 'block';
      btnViewTable.classList.add('active');
      btnViewGrid.classList.remove('active');
      localStorage.setItem('siakad_ebook_view', 'table');
    } else {
      ebookGridView.style.display = 'flex';
      ebookTableView.style.display = 'none';
      btnViewGrid.classList.add('active');
      btnViewTable.classList.remove('active');
      localStorage.setItem('siakad_ebook_view', 'grid');
    }
  }

  if (btnViewGrid && btnViewTable) {
    btnViewGrid.addEventListener('click', () => setView('grid'));
    btnViewTable.addEventListener('click', () => setView('table'));

    // Restore user preference (default: grid)
    const savedView = localStorage.getItem('siakad_ebook_view') || 'grid';
    setView(savedView);
  }

  // 2. Delete Confirmation Handling
  let deleteId = null;
  const deleteModalEl = document.getElementById('deleteModal');
  const modal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      deleteId = this.dataset.id;
      document.getElementById('deleteBookTitle').textContent = this.dataset.title;
      if (modal) modal.show();
    });
  });

  const confirmDeleteBtn = document.getElementById('confirmDelete');
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function() {
      if (!deleteId) return;

      const originalText = confirmDeleteBtn.innerHTML;
      confirmDeleteBtn.disabled = true;
      confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

      fetch('<?= base_url('admin/ebook/delete') ?>/' + deleteId, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        }
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          // Remove from Grid View
          const cardEl = document.getElementById('card-' + deleteId);
          if (cardEl) {
            cardEl.style.transition = 'all 0.3s ease';
            cardEl.style.opacity = '0';
            cardEl.style.transform = 'scale(0.8)';
            setTimeout(() => cardEl.remove(), 300);
          }
          // Remove from Table View
          const rowEl = document.getElementById('row-' + deleteId);
          if (rowEl) rowEl.remove();

          if (modal) modal.hide();
        } else {
          alert(data.message || 'Gagal menghapus buku.');
        }
      })
      .catch(() => alert('Terjadi kesalahan saat menghubungi server.'))
      .finally(() => {
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.innerHTML = originalText;
      });
    });
  }
});
</script>

<?= $this->endSection() ?>
