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

<div class="alert alert-info py-2 small mb-3">
  <i class="bi bi-info-circle me-2"></i>
  Sub Materi adalah <strong>pertemuan</strong> dari sebuah Materi. Setiap Sub Materi akan otomatis
  memiliki Forum Diskusi, dan bisa dilengkapi Kuis Mandiri.
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/materials/store') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
      <input type="hidden" name="year_id"    value="<?= $activeYear['id'] ?? '' ?>">
      <?php if (!empty($returnUrl)): ?>
        <input type="hidden" name="return" value="<?= esc($returnUrl) ?>">
      <?php endif; ?>

      <div class="row g-3">

        <!-- Pilih Materi Induk (dari ATP) -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Materi <span class="text-danger">*</span></label>
          <select name="parent_id" class="form-select form-select-sm" required>
            <option value="">-- Pilih Materi --</option>
            <?php foreach ($parents as $p): ?>
              <?php
                $pSem = $p['semester'] == 1 ? 'Ganjil' : 'Genap';
                $pLvl = \App\Models\SubjectMaterialModel::getLevelLabel((int)$p['level']);
              ?>
              <option value="<?= $p['id'] ?>"
                      <?= ($parentId == $p['id'] || old('parent_id') == $p['id']) ? 'selected' : '' ?>>
                <?= esc($p['title']) ?> (<?= $pLvl ?> · <?= $pSem ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Materi diambil otomatis dari ATP. Pilih materi tempat sub materi ini berada.</div>
        </div>

        <!-- Urutan -->
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Urutan Pertemuan</label>
          <input type="number" name="sort_order" class="form-control form-control-sm"
                 min="0" value="<?= old('sort_order', 0) ?>" placeholder="cth: 1">
          <div class="form-text">Urutan tampil (1 = pertemuan pertama).</div>
        </div>

        <!-- Estimasi Waktu -->
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Estimasi Waktu (menit)</label>
          <input type="number" name="estimated_minutes" class="form-control form-control-sm"
                 min="1" max="300" value="<?= old('estimated_minutes') ?>" placeholder="cth: 30">
        </div>

        <!-- Judul Sub Materi -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Judul Sub Materi <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control form-control-sm"
                 value="<?= old('title') ?>" required
                 placeholder="cth: Pertemuan 1 — Pengenalan Konsep">
        </div>

        <!-- Deskripsi singkat -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi Singkat</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"
                    placeholder="Ringkasan singkat (tampil di daftar)"><?= old('description') ?></textarea>
        </div>

        <!-- Konten Sub Materi -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Tipe Konten <span class="text-danger">*</span></label>
          <select name="content_type" id="contentType" class="form-select form-select-sm" required>
            <option value="text"  <?= old('content_type') === 'text'  ? 'selected' : '' ?>>Teks (Editor)</option>
            <option value="html"  <?= old('content_type') === 'html'  ? 'selected' : '' ?>>Halaman HTML Interaktif</option>
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
        <div class="col-12" id="panelHtml" style="display:none;">
          <label class="form-label small fw-semibold">Kode HTML Interaktif</label>
          <textarea name="content_html" id="htmlContent" class="form-control" rows="16" spellcheck="false"
                    style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:0.82rem;line-height:1.5;"
                    placeholder="<!DOCTYPE html>&#10;<html>&#10;<head><style> /* CSS Anda */ </style></head>&#10;<body>&#10;  <!-- HTML & JavaScript interaktif Anda -->&#10;</body>&#10;</html>"><?= old('content_html') ?></textarea>
          <div class="alert alert-warning border-0 py-2 small mt-2 mb-0">
            <i class="bi bi-shield-lock me-1"></i>
            Tempelkan HTML lengkap (boleh berisi CSS &amp; JavaScript). Halaman ini akan
            dijalankan dalam <strong>iframe terisolasi (sandbox)</strong> demi keamanan —
            script hanya berjalan di dalam materi, tidak dapat mengakses sesi/aplikasi utama.
          </div>
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

        <!-- Publikasi status sub materi (is_published = sub materi "siap") -->
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished"
                   <?= old('is_published') ? 'checked' : '' ?>>
            <label class="form-check-label small" for="isPublished">
              <strong>Tandai sebagai siap</strong> — sub materi bisa dipublikasikan ke kelas
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
    html:  document.getElementById('panelHtml'),
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

  function destroyEditor() {
    if (inst) {
      try { inst.destroy(); } catch (e) {}
      inst = null;
    }
  }

  function switchPanel(type) {
    Object.keys(panels).forEach(function(k) {
      if (panels[k]) panels[k].style.display = (k === type) ? 'block' : 'none';
    });
    if (type === 'text') {
      initEditor();
    } else {
      // Non-text: pastikan CKEditor tidak aktif agar tidak menimpa field content
      destroyEditor();
    }
  }

  var contentTypeSel = document.getElementById('contentType');
  contentTypeSel.addEventListener('change', function() {
    switchPanel(this.value);
  });

  // Saat submit: jika tipe HTML, salin isi editor kode ke field 'content'
  // dan nonaktifkan textarea CKEditor agar tidak menimpa. Sebaliknya untuk text.
  var form           = contentTypeSel.closest('form');
  // Cegah CKEditor mengganggu konten HTML: saat tipe html, kosongkan field
  // 'content' (CKEditor) agar tidak terkirim; controller membaca 'content_html'.
  var editorContent  = document.getElementById('editorContent'); // name="content"
  form.addEventListener('submit', function() {
    if (contentTypeSel.value === 'html' && editorContent) {
      editorContent.value = '';
    }
  });

  // Aktifkan panel sesuai pilihan awal (mendukung old() saat validasi gagal)
  switchPanel(contentTypeSel.value || 'text');
})();
</script>
<?= $this->endSection() ?>
