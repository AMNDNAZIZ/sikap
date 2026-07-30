<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📚 Edit Buku Digital</h3>

<a href="<?= base_url('admin/ebook') ?>" class="btn btn-secondary btn-sm mb-3">⬅ Kembali</a>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach (session()->getFlashdata('errors') as $err): ?>
        <li><?= esc($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row">
  <!-- Metadata Form -->
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header">✏️ Edit Metadata</div>
      <div class="card-body">
        <form action="<?= base_url('admin/ebook/update/' . $book['id']) ?>" method="post">
          <?= csrf_field() ?>

          <?php if (!empty($canManageUmum)): ?>
          <div class="mb-3">
            <label class="form-label">Jenis Buku <span class="text-danger">*</span></label>
            <select name="book_type" id="book_type" class="form-select" required>
              <option value="mapel" <?= old('book_type', $book['book_type'] ?? 'mapel') === 'mapel' ? 'selected' : '' ?>>📘 Buku Mata Pelajaran</option>
              <option value="umum" <?= old('book_type', $book['book_type'] ?? 'mapel') === 'umum' ? 'selected' : '' ?>>📖 Buku Umum (Lintas Kelas)</option>
            </select>
            <div class="form-text">Buku Umum dapat diakses oleh semua siswa tanpa batasan kelas.</div>
          </div>
          <?php else: ?>
          <input type="hidden" name="book_type" value="<?= esc($book['book_type'] ?? 'mapel') ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" maxlength="255" required
                   value="<?= old('title', $book['title']) ?>">
          </div>

          <div id="mapelFields">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Level Kelas <span class="text-danger">*</span></label>
                <select name="level" id="levelSelect" class="form-select" required>
                  <option value="">-- Pilih Level --</option>
                  <?php foreach ($allowedLevels as $lvl): ?>
                    <option value="<?= $lvl ?>" <?= old('level', $book['level']) == $lvl ? 'selected' : '' ?>>Kelas <?= $lvl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select" required>
                  <option value="">-- Pilih Mapel --</option>
                  <?php foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= old('subject_id', $book['subject_id']) == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mb-3" id="religionGroup" style="display:none;">
              <label class="form-label">Agama <span class="text-danger">*</span></label>
              <select name="religion" id="religion" class="form-select">
                <option value="">-- Pilih Agama --</option>
                <?php
                $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                foreach ($religions as $r):
                ?>
                  <option value="<?= $r ?>" <?= old('religion', $book['religion']) === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="3" maxlength="1000"><?= old('description', $book['description']) ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-sm">💾 Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>

  <!-- File Replace Form -->
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">📄 File PDF</div>
      <div class="card-body">
        <p class="small text-muted">
          File: <strong><?= esc($book['original_filename'] ?? $book['filename']) ?></strong><br>
          Ukuran: <?= number_format(($book['file_size'] ?? 0) / 1048576, 1) ?> MB
        </p>
        <a href="<?= base_url('admin/ebook/file/read/' . $book['id']) ?>" class="btn btn-info btn-sm mb-3" target="_blank">👁️ Lihat PDF</a>

        <hr>
        <h6>🔄 Ganti File</h6>
        <form action="<?= base_url('admin/ebook/replace/' . $book['id']) ?>" method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <input type="file" name="pdf_file" class="form-control form-control-sm" accept="application/pdf,.pdf" required id="replaceFile">
            <div class="form-text">Format: PDF, Maks: 40MB</div>
            <div id="replaceError" class="text-danger small" style="display:none;"></div>
          </div>
          <button type="submit" class="btn btn-warning btn-sm">🔄 Ganti File PDF</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const bookTypeSelect = document.getElementById('book_type');
  const mapelFields = document.getElementById('mapelFields');
  const levelSelect = document.getElementById('levelSelect');
  const subjectSelect = document.getElementById('subject_id');
  const religionGroup = document.getElementById('religionGroup');
  const religionSelect = document.getElementById('religion');
  const replaceFile = document.getElementById('replaceFile');

  // Toggle mapel fields based on book type
  function toggleBookType() {
    if (!bookTypeSelect) return;
    const isUmum = bookTypeSelect.value === 'umum';
    mapelFields.style.display = isUmum ? 'none' : 'block';
    if (isUmum) {
      levelSelect.removeAttribute('required');
      subjectSelect.removeAttribute('required');
      religionSelect.removeAttribute('required');
    } else {
      levelSelect.setAttribute('required', 'required');
      subjectSelect.setAttribute('required', 'required');
    }
  }

  if (bookTypeSelect) {
    bookTypeSelect.addEventListener('change', toggleBookType);
    toggleBookType(); // init
  }

  function checkReligion(subjectId) {
    if (!subjectId) {
      religionGroup.style.display = 'none';
      religionSelect.removeAttribute('required');
      return;
    }
    fetch('<?= base_url('admin/ebook/get-religion') ?>/' + subjectId)
      .then(r => r.json())
      .then(data => {
        if (data.religion) {
          religionGroup.style.display = 'block';
          religionSelect.setAttribute('required', 'required');
        } else {
          religionGroup.style.display = 'none';
          religionSelect.removeAttribute('required');
          religionSelect.value = '';
        }
      });
  }

  subjectSelect.addEventListener('change', function() {
    checkReligion(this.value);
  });

  // Init on load
  checkReligion(subjectSelect.value);

  // File validation
  replaceFile.addEventListener('change', function() {
    const file = this.files[0];
    const errorDiv = document.getElementById('replaceError');
    if (!file) { errorDiv.style.display = 'none'; return; }
    if (file.type !== 'application/pdf') {
      errorDiv.textContent = 'Format file harus PDF.';
      errorDiv.style.display = 'block';
      this.value = '';
      return;
    }
    if (file.size > 41943040) {
      errorDiv.textContent = 'Ukuran file melebihi 40MB.';
      errorDiv.style.display = 'block';
      this.value = '';
      return;
    }
    errorDiv.style.display = 'none';
  });
});
</script>

<?= $this->endSection() ?>
