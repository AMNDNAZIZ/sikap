<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-pencil me-2 text-warning"></i><?= esc($title) ?>
  </h5>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if ($isSubMat): ?>
  <div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-journals me-2"></i>Sub Materi — level dan materi induk tidak bisa diubah.
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/materials/update/' . $material['id']) ?>"
          enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
      <input type="hidden" name="year_id"    value="<?= $activeYear['id'] ?? $material['year_id'] ?>">

      <div class="row g-3">

        <!-- Level (hanya Materi induk) -->
        <?php if (!$isSubMat): ?>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Level Kelas</label>
          <select name="level" class="form-select form-select-sm">
            <option value="0" <?= $material['level'] == 0 ? 'selected' : '' ?>>Semua Level</option>
            <?php foreach ($levels as $lv): ?>
              <option value="<?= $lv ?>" <?= $material['level'] == $lv ? 'selected' : '' ?>>
                Kelas <?= $lv ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="level" value="<?= $material['level'] ?>">
        <?php endif; ?>

        <!-- Semester -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Semester</label>
          <select name="semester" class="form-select form-select-sm">
            <option value="1" <?= $material['semester'] == 1 ? 'selected' : '' ?>>Ganjil</option>
            <option value="2" <?= $material['semester'] == 2 ? 'selected' : '' ?>>Genap</option>
          </select>
        </div>

        <!-- Urutan -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Urutan Tampil</label>
          <input type="number" name="sort_order" class="form-control form-control-sm"
                 min="0" value="<?= esc($material['sort_order'] ?? 0) ?>">
        </div>

        <!-- Judul -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control form-control-sm"
                 value="<?= esc($material['title']) ?>" required>
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi Singkat</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"><?= esc($material['description'] ?? '') ?></textarea>
        </div>

        <?php if ($isSubMat): ?>
        <!-- Konten Sub Materi -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Tipe Konten</label>
          <select name="content_type" id="contentType" class="form-select form-select-sm">
            <option value="text"  <?= ($material['content_type'] ?? 'text') === 'text'  ? 'selected' : '' ?>>Teks HTML</option>
            <option value="pdf"   <?= ($material['content_type'] ?? '') === 'pdf'   ? 'selected' : '' ?>>File PDF</option>
            <option value="video" <?= ($material['content_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
            <option value="link"  <?= ($material['content_type'] ?? '') === 'link'  ? 'selected' : '' ?>>Link</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Estimasi Waktu (menit)</label>
          <input type="number" name="estimated_minutes" class="form-control form-control-sm"
                 min="1" max="300" value="<?= esc($material['estimated_minutes'] ?? '') ?>">
        </div>

        <div class="col-12" id="panelText">
          <label class="form-label small fw-semibold">Konten</label>
          <textarea name="content" id="editorContent" class="form-control" rows="10"><?= esc($material['content'] ?? '') ?></textarea>
        </div>
        <div class="col-12" id="panelPdf" style="display:none;">
          <label class="form-label small fw-semibold">File PDF</label>
          <?php if (!empty($material['file_path'])): ?>
            <div class="mb-2">
              <a href="<?= base_url('uploads/materials/' . $material['file_path']) ?>"
                 target="_blank" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>Lihat File Saat Ini
              </a>
            </div>
          <?php endif; ?>
          <input type="file" name="file_upload" class="form-control form-control-sm" accept=".pdf">
          <div class="form-text">Kosongkan untuk mempertahankan file lama. Maks 10 MB.</div>
        </div>
        <div class="col-12" id="panelVideo" style="display:none;">
          <label class="form-label small fw-semibold">URL Video</label>
          <input type="url" name="video_url" class="form-control form-control-sm"
                 value="<?= esc($material['video_url'] ?? '') ?>"
                 placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="col-12" id="panelLink" style="display:none;">
          <label class="form-label small fw-semibold">URL Eksternal</label>
          <input type="url" name="external_link" class="form-control form-control-sm"
                 value="<?= esc($material['external_link'] ?? '') ?>">
        </div>
        <?php endif; ?>

        <!-- Siap -->
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished"
                   <?= ($material['is_published'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="isPublished">
              <strong>Tandai sebagai siap</strong>
              <?= $isSubMat
                  ? '— sub materi bisa dipublish ke kelas'
                  : '— materi bisa dibuat sub materinya' ?>
            </label>
          </div>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm">
          <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
        <a href="<?= site_url('admin/materials/' . $subject['id']) ?>"
           class="btn btn-secondary btn-sm">Batal</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($isSubMat): ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
(function(){
  var inst      = null;
  var uploadUrl = "<?= site_url('admin/materials/upload-image') ?>";
  var csrfName  = "<?= csrf_token() ?>";
  var csrfHash  = "<?= csrf_hash() ?>";
  var initType  = '<?= $material['content_type'] ?? 'text' ?>';

  // ── Custom upload adapter ─────────────────────────────────────────────
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

  switchPanel(initType);
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
