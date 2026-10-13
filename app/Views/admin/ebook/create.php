<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📚 Upload Buku Digital</h3>

<a href="<?= base_url('admin/ebook') ?>" class="btn btn-secondary btn-sm mb-3">⬅ Kembali</a>

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

<div class="card">
  <div class="card-body">
    <form action="<?= base_url('admin/ebook/store') ?>" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <?php if (!empty($canManageUmum)): ?>
      <div class="mb-3">
        <label class="form-label">Jenis Buku <span class="text-danger">*</span></label>
        <select name="book_type" id="book_type" class="form-select" required>
          <option value="mapel" <?= old('book_type') === 'umum' ? '' : 'selected' ?>>📘 Buku Mata Pelajaran</option>
          <option value="umum" <?= old('book_type') === 'umum' ? 'selected' : '' ?>>📖 Buku Umum (Lintas Kelas)</option>
        </select>
        <div class="form-text">Buku Umum dapat diakses oleh semua siswa tanpa batasan kelas.</div>
      </div>
      <?php else: ?>
      <input type="hidden" name="book_type" value="mapel">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" maxlength="255" required value="<?= old('title') ?>">
      </div>

      <div id="mapelFields">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Level Kelas <span class="text-danger">*</span></label>
            <select name="level" id="levelSelect" class="form-select" required>
              <option value="">-- Pilih Level --</option>
              <?php foreach ($allowedLevels as $lvl): ?>
                <option value="<?= $lvl ?>" <?= old('level') == $lvl ? 'selected' : '' ?>>Kelas <?= $lvl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" id="subject_id" class="form-select" required>
              <option value="">-- Pilih Mapel --</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>" <?= old('subject_id') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3" id="religionGroup" style="display:none;">
          <label class="form-label">Agama <span class="text-danger">*</span></label>
          <select name="religion" id="religion" class="form-select">
            <option value="">-- Pilih Agama --</option>
            <option value="Islam">Islam</option>
            <option value="Kristen">Kristen</option>
            <option value="Katolik">Katolik</option>
            <option value="Hindu">Hindu</option>
            <option value="Buddha">Buddha</option>
            <option value="Konghucu">Konghucu</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control" rows="3" maxlength="1000"><?= old('description') ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">🖼️ Cover Buku (Thumbnail)</label>
          <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" id="coverImage">
          <div class="form-text">Opsional. Format: JPG, PNG, WEBP, GIF (Maks: 3MB). Rasio 3:4 disarankan.</div>
          <div id="coverError" class="text-danger small mt-1" style="display:none;"></div>

          <!-- Live Preview Box -->
          <div id="coverPreviewContainer" class="mt-2" style="display:none;">
            <div class="d-inline-flex flex-column align-items-center p-2 border rounded bg-light shadow-sm">
              <img id="coverPreviewImg" src="" alt="Preview Cover" class="rounded" style="max-height: 180px; max-width: 140px; object-fit: cover;">
              <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 mt-1" id="btnRemoveCoverPreview" style="font-size: 0.75rem;">✕ Batal</button>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">📄 File PDF Buku <span class="text-danger">*</span></label>
          <input type="file" name="pdf_file" class="form-control" accept="application/pdf,.pdf" required id="pdfFile">
          <div class="form-text">Format: PDF, Maksimum: 40MB</div>
          <div id="fileError" class="text-danger small mt-1" style="display:none;"></div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">💾 Upload Buku</button>
    </form>
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
  const pdfFile = document.getElementById('pdfFile');

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

  // Dynamic religion field
  subjectSelect.addEventListener('change', function() {
    const subjectId = this.value;
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
  });

  // Client-side file validation
  pdfFile.addEventListener('change', function() {
    const file = this.files[0];
    const errorDiv = document.getElementById('fileError');
    if (!file) { errorDiv.style.display = 'none'; return; }

    if (file.type !== 'application/pdf') {
      errorDiv.textContent = 'Format file harus PDF.';
      errorDiv.style.display = 'block';
      this.value = '';
      return;
    }
    if (file.size > 41943040) {
      errorDiv.textContent = 'Ukuran file melebihi batas maksimum 40MB.';
      errorDiv.style.display = 'block';
      this.value = '';
      return;
    }
    errorDiv.style.display = 'none';
  });

  // Client-side cover validation & live preview
  const coverImage = document.getElementById('coverImage');
  const coverError = document.getElementById('coverError');
  const coverPreviewContainer = document.getElementById('coverPreviewContainer');
  const coverPreviewImg = document.getElementById('coverPreviewImg');
  const btnRemoveCoverPreview = document.getElementById('btnRemoveCoverPreview');

  if (coverImage) {
    coverImage.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) {
        coverPreviewContainer.style.display = 'none';
        coverError.style.display = 'none';
        return;
      }

      const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
      if (!validTypes.includes(file.type)) {
        coverError.textContent = 'Format cover harus berupa gambar (JPG, PNG, WEBP, GIF).';
        coverError.style.display = 'block';
        coverPreviewContainer.style.display = 'none';
        this.value = '';
        return;
      }

      if (file.size > 3 * 1024 * 1024) {
        coverError.textContent = 'Ukuran file cover melebihi batas maksimum 3MB.';
        coverError.style.display = 'block';
        coverPreviewContainer.style.display = 'none';
        this.value = '';
        return;
      }

      coverError.style.display = 'none';
      const reader = new FileReader();
      reader.onload = function(e) {
        coverPreviewImg.src = e.target.result;
        coverPreviewContainer.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });

    if (btnRemoveCoverPreview) {
      btnRemoveCoverPreview.addEventListener('click', function() {
        coverImage.value = '';
        coverPreviewContainer.style.display = 'none';
        coverError.style.display = 'none';
      });
    }
  }

  // Trigger subject change if old value exists
  if (subjectSelect && subjectSelect.value) {
    subjectSelect.dispatchEvent(new Event('change'));
  }
});
</script>

<?= $this->endSection() ?>
