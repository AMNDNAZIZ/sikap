<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-<?= $parent ? 'journals' : 'folder-plus' ?> me-2 text-primary"></i>
    <?= esc($title) ?> &mdash; <?= esc($subject['name'] ?? '') ?>
  </h5>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger py-2 small"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if ($parent): ?>
  <div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-folder2-open me-2"></i>
    Sub Materi dari: <strong><?= esc($parent['title']) ?></strong>
    (Level <?= $parent['level'] ?> &middot; Sem <?= $parent['semester'] == 1 ? 'Ganjil' : 'Genap' ?>)
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/materials/store') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
      <input type="hidden" name="year_id"    value="<?= $activeYear['id'] ?? '' ?>">
      <?php if ($parent): ?>
        <input type="hidden" name="parent_id" value="<?= $parent['id'] ?>">
        <input type="hidden" name="level"     value="<?= $parent['level'] ?>">
      <?php endif; ?>
      <?php if (!empty($returnUrl)): ?>
        <input type="hidden" name="return" value="<?= esc($returnUrl) ?>">
      <?php endif; ?>

      <div class="row g-3">

        <!-- Level (hanya untuk Materi induk) -->
        <?php if (!$parent): ?>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Level Kelas <span class="text-danger">*</span></label>
          <select name="level" class="form-select form-select-sm" required>
            <option value="0">Semua Level</option>
            <?php foreach ($levels as $lv): ?>
              <option value="<?= $lv ?>" <?= old('level') == $lv ? 'selected' : '' ?>>
                Kelas <?= $lv ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Level kelas yang menggunakan materi ini.</div>
        </div>
        <?php endif; ?>

        <!-- Semester -->
        <div class="col-md-<?= $parent ? '4' : '4' ?>">
          <label class="form-label small fw-semibold">Semester <span class="text-danger">*</span></label>
          <select name="semester" class="form-select form-select-sm" required>
            <option value="1" <?= old('semester') == '1' ? 'selected' : '' ?>>Ganjil</option>
            <option value="2" <?= old('semester') == '2' ? 'selected' : '' ?>>Genap</option>
          </select>
        </div>

        <!-- Urutan -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Urutan Tampil</label>
          <input type="number" name="sort_order" class="form-control form-control-sm"
                 min="0" value="<?= old('sort_order', 0) ?>">
        </div>

        <!-- Judul -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Judul <?= $parent ? 'Sub ' : '' ?>Materi <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control form-control-sm"
                 value="<?= old('title') ?>" required
                 placeholder="<?= $parent ? 'cth: Pertemuan 1 — Pengenalan Variabel' : 'cth: Persamaan Linear Satu Variabel' ?>">
        </div>

        <!-- Deskripsi singkat -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi Singkat</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"
                    placeholder="Ringkasan singkat (tampil di daftar)"><?= old('description') ?></textarea>
        </div>

        <?php if ($parent): ?>
        <!-- Konten (hanya untuk Sub Materi) -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Tipe Konten <span class="text-danger">*</span></label>
          <select name="content_type" id="contentType" class="form-select form-select-sm" required>
            <option value="text"  <?= old('content_type') === 'text'  ? 'selected' : '' ?>>Teks HTML</option>
            <option value="pdf"   <?= old('content_type') === 'pdf'   ? 'selected' : '' ?>>File PDF</option>
            <option value="video" <?= old('content_type') === 'video' ? 'selected' : '' ?>>Video YouTube/Vimeo</option>
            <option value="link"  <?= old('content_type') === 'link'  ? 'selected' : '' ?>>Link Eksternal</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold">Estimasi Waktu (menit)</label>
          <input type="number" name="estimated_minutes" class="form-control form-control-sm"
                 min="1" max="300" value="<?= old('estimated_minutes') ?>" placeholder="cth: 30">
        </div>

        <!-- Panel konten -->
        <div class="col-12" id="panelText">
          <label class="form-label small fw-semibold">Konten Materi</label>
          <textarea name="content" id="editorContent" class="form-control" rows="10"><?= old('content') ?></textarea>
        </div>
        <div class="col-12" id="panelPdf" style="display:none;">
          <label class="form-label small fw-semibold">Upload PDF <span class="text-danger">*</span></label>
          <input type="file" name="file_upload" class="form-control form-control-sm" accept=".pdf">
          <div class="form-text">Maks 10 MB.</div>
        </div>
        <div class="col-12" id="panelVideo" style="display:none;">
          <label class="form-label small fw-semibold">URL Video</label>
          <input type="url" name="video_url" class="form-control form-control-sm"
                 value="<?= old('video_url') ?>" placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="col-12" id="panelLink" style="display:none;">
          <label class="form-label small fw-semibold">URL Eksternal</label>
          <input type="url" name="external_link" class="form-control form-control-sm"
                 value="<?= old('external_link') ?>" placeholder="https://...">
        </div>
        <?php endif; ?>

        <!-- Publikasi status materi (is_published = materi "siap") -->
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished"
                   <?= old('is_published') ? 'checked' : '' ?>>
            <label class="form-check-label small" for="isPublished">
              <strong>Tandai sebagai siap</strong>
              <?php if ($parent): ?>
                — sub materi bisa dipublish ke kelas
              <?php else: ?>
                — materi bisa dibuat sub materinya
              <?php endif; ?>
            </label>
          </div>
        </div>

      </div><!-- /.row -->

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm">
          <i class="bi bi-save me-1"></i>Simpan
        </button>
        <a href="<?= site_url('admin/materials/' . $subject['id']) ?>"
           class="btn btn-secondary btn-sm">Batal</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($parent): ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
(function(){
  var inst      = null;
  var uploadUrl = "<?= site_url('admin/materials/upload-image') ?>";
  var csrfName  = "<?= csrf_token() ?>";
  var csrfHash  = "<?= csrf_hash() ?>";

  // ── Custom upload adapter — tidak butuh plugin tambahan ──────────────
  function ImageUploadAdapter(loader) {
    this.loader = loader;
  }
  ImageUploadAdapter.prototype.upload = function() {
    var loader = this.loader;
    return loader.file.then(function(file) {
      return new Promise(function(resolve, reject) {
        var data = new FormData();
        data.append('upload', file);
        data.append(csrfName, csrfHash);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = function(e) {
          if (e.lengthComputable) {
            loader.uploadTotal = e.total;
            loader.uploaded    = e.loaded;
          }
        };
        xhr.onload = function() {
          if (xhr.status < 200 || xhr.status > 299) {
            return reject('Upload gagal (HTTP ' + xhr.status + ')');
          }
          var res = JSON.parse(xhr.responseText);
          if (res.error) return reject(res.error.message || 'Upload gagal');
          resolve({ default: res.url });
        };
        xhr.onerror = function() { reject('Upload gagal — periksa koneksi.'); };
        xhr.send(data);
      });
    });
  };
  ImageUploadAdapter.prototype.abort = function() {};

  function uploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
      return new ImageUploadAdapter(loader);
    };
  }

  var panels = {
    text:  document.getElementById('panelText'),
    pdf:   document.getElementById('panelPdf'),
    video: document.getElementById('panelVideo'),
    link:  document.getElementById('panelLink')
  };

  function initEditor() {
    if (inst) return;
    ClassicEditor.create(document.querySelector('#editorContent'), {
      extraPlugins: [uploadAdapterPlugin],
      toolbar: [
        'heading', '|',
        'bold', 'italic', '|',
        'bulletedList', 'numberedList', '|',
        'outdent', 'indent', '|',
        'blockQuote', 'insertTable', '|',
        'uploadImage', '|',
        'link', '|',
        'undo', 'redo'
      ],
      image: {
        toolbar: [
          'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
          'toggleImageCaption', 'imageTextAlternative'
        ]
      },
      language: 'id'
    }).then(function(editor) {
      inst = editor;
      editor.model.document.on('change:data', function() {
        document.querySelector('#editorContent').value = editor.getData();
      });
    }).catch(console.error);
  }

  function switchPanel(type) {
    Object.keys(panels).forEach(function(k) {
      panels[k].style.display = (k === type) ? 'block' : 'none';
    });
    if (type === 'text') initEditor();
  }

  document.getElementById('contentType').addEventListener('change', function() {
    switchPanel(this.value);
  });

  switchPanel('text');
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
