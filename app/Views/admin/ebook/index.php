<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📚 Perpustakaan Digital</h3>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="<?= base_url('admin/ebook/create') ?>" class="btn btn-primary btn-sm">➕ Upload Buku</a>
</div>

<!-- Filter & Search -->
<form method="get" action="<?= base_url('admin/ebook') ?>" class="row g-2 mb-3">
  <div class="col-md-3">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="🔍 Cari judul atau mapel..." value="<?= esc($search ?? '') ?>">
  </div>
  <div class="col-md-2">
    <select name="book_type" class="form-select form-select-sm">
      <option value="">Semua Jenis</option>
      <option value="mapel" <?= ($bookTypeFilter ?? '') === 'mapel' ? 'selected' : '' ?>>📘 Mapel</option>
      <option value="umum" <?= ($bookTypeFilter ?? '') === 'umum' ? 'selected' : '' ?>>📖 Umum</option>
    </select>
  </div>
  <div class="col-md-2">
    <select name="level" class="form-select form-select-sm">
      <option value="">Semua Level</option>
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <option value="<?= $i ?>" <?= ($levelFilter ?? '') == $i ? 'selected' : '' ?>>Kelas <?= $i ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select name="subject_id" class="form-select form-select-sm">
      <option value="">Semua Mapel</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($subjectFilter ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Filter</button>
  </div>
  <?php if (!empty($search) || !empty($levelFilter) || !empty($subjectFilter) || !empty($bookTypeFilter)): ?>
    <div class="col-md-1">
      <a href="<?= base_url('admin/ebook') ?>" class="btn btn-outline-danger btn-sm w-100">✕</a>
    </div>
  <?php endif; ?>
</form>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($books)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-1">📭</p>
    <p>Belum ada buku digital. Klik "Upload Buku" untuk menambahkan.</p>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Judul</th>
          <th>Jenis</th>
          <th>Level</th>
          <th>Mata Pelajaran</th>
          <th>Agama</th>
          <th>Ukuran</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($books as $book): ?>
          <tr id="row-<?= $book['id'] ?>">
            <td><?= $no++ ?></td>
            <td>
              <strong><?= esc($book['title']) ?></strong>
              <?php if (!empty($book['description'])): ?>
                <br><small class="text-muted"><?= esc(mb_substr($book['description'], 0, 80)) ?><?= mb_strlen($book['description']) > 80 ? '...' : '' ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($book['book_type'] ?? 'mapel') === 'umum'): ?>
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
              <a href="<?= base_url('admin/ebook/edit/' . $book['id']) ?>" class="btn btn-warning btn-sm" title="Edit">✏️</a>
              <a href="<?= base_url('admin/ebook/file/read/' . $book['id']) ?>" class="btn btn-info btn-sm" target="_blank" title="Baca">👁️</a>
              <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $book['id'] ?>" data-title="<?= esc($book['title']) ?>" title="Hapus">🗑️</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">🗑️ Hapus Buku</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Yakin ingin menghapus buku <strong id="deleteBookTitle"></strong>?</p>
        <p class="text-danger small">File PDF juga akan dihapus dari server.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmDelete">Hapus</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let deleteId = null;
  const modal = new bootstrap.Modal(document.getElementById('deleteModal'));

  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      deleteId = this.dataset.id;
      document.getElementById('deleteBookTitle').textContent = this.dataset.title;
      modal.show();
    });
  });

  document.getElementById('confirmDelete').addEventListener('click', function() {
    if (!deleteId) return;
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
        document.getElementById('row-' + deleteId).remove();
        modal.hide();
      } else {
        alert(data.message || 'Gagal menghapus.');
      }
    })
    .catch(() => alert('Terjadi kesalahan.'));
  });
});
</script>

<?= $this->endSection() ?>
