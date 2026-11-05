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

        <!-- ═══ ASISTEN PRA-PEMBELAJARAN: RUJUK MODUL AJAR ═══ -->
        <div class="col-12">
          <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 shadow-none">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <span class="fw-bold text-primary small">
                  <i class="bi bi-stars me-1 text-warning"></i> Asisten Desain Instruksional AI: Uraikan Modul Ajar ke Aksi Nyata
                </span>
                <span class="badge bg-primary text-white small">Pra-KBM / Transformasi Digital</span>
              </div>
              <p class="text-muted small mb-2">
                Pilih Modul Ajar yang telah Anda susun. Asisten AI akan menguraikan skenario pembelajaran menjadi <strong>aksi nyata kegiatan siswa</strong> dengan alokasi waktu yang proporsional ke dalam beberapa segmen interaktif (Tayangan Video, Penjelasan Konsep, Simulasi HTML Interaktif, dan Refleksi).
              </p>
              <div class="row g-2 align-items-end">
                <div class="col-md-5">
                  <label class="form-label smaller fw-semibold mb-1">Pilih Dokumen Modul Ajar</label>
                  <select id="modulAjarSelect" class="form-select form-select-sm">
                    <option value="">-- Pilih Dokumen Modul Ajar --</option>
                    <?php if (!empty($availableModuls)): ?>
                      <?php foreach ($availableModuls as $am): ?>
                        <option value="<?= $am['id'] ?>" data-materi="<?= esc($am['materi']) ?>" data-atp="<?= $am['atp_id'] ?>">
                          <?= esc($am['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label smaller fw-semibold mb-1">Pilih Sesi Pertemuan &amp; Durasi</label>
                  <select id="pertemuanSelect" class="form-select form-select-sm" disabled>
                    <option value="">-- Pilih Modul Terlebih Dahulu --</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" id="btnPullModulScenario" disabled>
                    <i class="bi bi-stars me-1 text-warning"></i> Desain Alur dengan AI
                  </button>
                </div>
              </div>
              <div id="modulScenarioAlert" class="alert alert-success py-1 px-2 small mt-2 mb-0" style="display: none;"></div>
            </div>
          </div>
        </div>

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

        <!-- Mode Penyusunan Materi -->
        <div class="col-12">
          <label class="form-label small fw-semibold d-block">Metode Penyusunan Materi</label>
          <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="builder_mode" id="modeMulti" value="multi" checked autocomplete="off">
            <label class="btn btn-outline-primary btn-sm py-2" for="modeMulti">
              <i class="bi bi-diagram-3 me-1"></i><strong>Alur Multi-Segmen</strong> (Langkah demi Langkah — Terarah)
            </label>
            <input type="radio" class="btn-check" name="builder_mode" id="modeSingle" value="single" autocomplete="off">
            <label class="btn btn-outline-secondary btn-sm py-2" for="modeSingle">
              <i class="bi bi-card-text me-1"></i>Konten Tunggal (Satu Jenis Materi)
            </label>
          </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             PANEL MULTI-SEGMEN (MICRO-LEARNING STEPPER)
        ═══════════════════════════════════════════════════════════ -->
        <div class="col-12" id="sectionMultiSegment">
          <div class="alert alert-primary-subtle border border-primary-subtle py-2 small mb-3">
            <i class="bi bi-info-circle-fill me-1 text-primary"></i>
            <strong>Pembelajaran Beragam &amp; Terarah:</strong> Dalam satu pertemuan ini, Anda dapat memecah materi menjadi beberapa langkah/segmen (misal: <em>Langkah 1 Teks, Langkah 2 Video, Langkah 3 Simulasi Interaktif, Langkah 4 PDF</em>). Siswa akan menyelesaikan langkah demi langkah secara berurutan dan teratur sebelum kuis terbuka.
          </div>

          <div id="segmentCardsContainer">
            <!-- Dynamic Segments will be inserted here -->
          </div>

          <div class="text-center mt-2 mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnAddSegment">
              <i class="bi bi-plus-circle me-1"></i>Tambah Segmen Belajar Baru
            </button>
          </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             PANEL KONTEN TUNGGAL (KLASIK)
        ═══════════════════════════════════════════════════════════ -->
        <div class="col-12" id="sectionSingleContent" style="display: none;">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Tipe Konten <span class="text-danger">*</span></label>
              <select name="content_type" id="contentType" class="form-select form-select-sm">
                <option value="text"  <?= old('content_type') === 'text'  ? 'selected' : '' ?>>Teks (Editor)</option>
                <option value="slide" <?= old('content_type') === 'slide' ? 'selected' : '' ?>>Slide Gambar</option>
                <option value="html"  <?= old('content_type') === 'html'  ? 'selected' : '' ?>>Halaman HTML Interaktif</option>
                <option value="pdf"   <?= old('content_type') === 'pdf'   ? 'selected' : '' ?>>File PDF</option>
                <option value="video" <?= old('content_type') === 'video' ? 'selected' : '' ?>>Video YouTube/Vimeo</option>
                <option value="link"  <?= old('content_type') === 'link'  ? 'selected' : '' ?>>Link Eksternal</option>
              </select>
            </div>

            <!-- Panel konten tunggal -->
            <div class="col-12" id="panelText">
              <label class="form-label small fw-semibold">Konten Materi</label>
              <textarea name="content" id="editorContent" class="form-control" rows="10"><?= old('content') ?></textarea>
            </div>
            <div class="col-12" id="panelHtml" style="display:none;">
              <label class="form-label small fw-semibold">Kode HTML Interaktif</label>
              <textarea name="content_html" id="htmlContent" class="form-control" rows="16" spellcheck="false"
                        style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:0.82rem;line-height:1.5;"
                        placeholder="<!DOCTYPE html>&#10;<html>&#10;<head><style> /* CSS */ </style></head>&#10;<body>&#10;  <!-- HTML & JS interaktif -->&#10;</body>&#10;</html>"><?= old('content_html') ?></textarea>
              <div class="alert alert-warning border-0 py-2 small mt-2 mb-0">
                <i class="bi bi-shield-lock me-1"></i>
                Tempelkan HTML lengkap. Dijalankan dalam <strong>iframe terisolasi (sandbox)</strong> demi keamanan.
              </div>
            </div>
            <div class="col-12" id="panelSlide" style="display:none;">
              <label class="form-label small fw-semibold">Upload Gambar Slide</label>
              <input type="file" name="slide_images[]" id="slideInput" class="form-control form-control-sm"
                     accept="image/jpeg,image/png,image/webp,image/gif" multiple>
              <div class="form-text">Pilih beberapa gambar sekaligus (JPG/PNG/WEBP/GIF, maks 5 MB per gambar).</div>
              <div id="slidePreview" class="row g-2 mt-1"></div>
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
          </div>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
    slide: document.getElementById('panelSlide'),
    html:  document.getElementById('panelHtml'),
    pdf:   document.getElementById('panelPdf'),
    video: document.getElementById('panelVideo'),
    link:  document.getElementById('panelLink')
  };

  // ── Preview gambar slide yang dipilih (sebelum upload) & Reordering ──────────────
  var slideInput = document.getElementById('slideInput');
  var currentSlideFiles = [];

  if (slideInput) {
    slideInput.addEventListener('change', function() {
      var box = document.getElementById('slidePreview');
      box.innerHTML = '';
      var files = Array.from(this.files || []);
      if (!files.length) {
        currentSlideFiles = [];
        return;
      }
      
      currentSlideFiles = files.filter(function(f) { return f.type.startsWith('image/'); });
      
      currentSlideFiles.forEach(function(f, idx) {
        var url = URL.createObjectURL(f);
        var col = document.createElement('div');
        col.className = 'col-4 col-md-3 slide-item';
        col.dataset.index = idx; // Simpan index asli
        col.innerHTML =
          '<div class="border rounded p-1 h-100 text-center" style="cursor: move;" title="Geser untuk mengubah urutan">' +
          '<img src="' + url + '" style="width:100%;height:90px;object-fit:cover;border-radius:4px;">' +
          '<div class="text-muted text-truncate slide-label mt-1" style="font-size:0.62rem;">Slide ' + (idx + 1) + '</div>' +
          '</div>';
        box.appendChild(col);
      });

      // Aktifkan SortableJS
      if (typeof Sortable !== 'undefined') {
        new Sortable(box, {
          animation: 150,
          ghostClass: 'opacity-50',
          onEnd: function() {
            // Update label setelah di-reorder
            var items = box.querySelectorAll('.slide-item');
            items.forEach(function(item, i) {
              item.querySelector('.slide-label').innerText = 'Slide ' + (i + 1);
            });
            // Update input files
            updateSlideInputFiles();
          }
        });
      }
    });
  }

  function updateSlideInputFiles() {
    if (!window.DataTransfer) return;
    var box = document.getElementById('slidePreview');
    var items = box.querySelectorAll('.slide-item');
    var dt = new DataTransfer();
    
    items.forEach(function(item) {
      var origIndex = parseInt(item.dataset.index, 10);
      if (currentSlideFiles[origIndex]) {
        dt.items.add(currentSlideFiles[origIndex]);
      }
    });
    
    if (slideInput) {
      slideInput.files = dt.files;
    }
  }

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

  // ══════════════════════════════════════════════════════════════════════
  // BUILDER MODE TOGGLER (MULTI-SEGMEN VS KONTEN TUNGGAL)
  // ══════════════════════════════════════════════════════════════════════
  var modeMultiRadio  = document.getElementById('modeMulti');
  var modeSingleRadio = document.getElementById('modeSingle');
  var secMulti        = document.getElementById('sectionMultiSegment');
  var secSingle       = document.getElementById('sectionSingleContent');
  var segContainer    = document.getElementById('segmentCardsContainer');
  var btnAddSeg       = document.getElementById('btnAddSegment');

  function updateBuilderMode() {
    var isMulti = modeMultiRadio && modeMultiRadio.checked;
    if (secMulti)  secMulti.style.display  = isMulti ? 'block' : 'none';
    if (secSingle) secSingle.style.display = isMulti ? 'none' : 'block';

    if (!isMulti) {
      switchPanel(contentTypeSel.value || 'text');
    }
  }

  if (modeMultiRadio && modeSingleRadio) {
    modeMultiRadio.addEventListener('change', updateBuilderMode);
    modeSingleRadio.addEventListener('change', updateBuilderMode);
  }

  // ══════════════════════════════════════════════════════════════════════
  // DYNAMIC SEGMENT BUILDER
  // ══════════════════════════════════════════════════════════════════════
  var segCounter = 0;

  function createSegmentHtml(idx, title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays) {
    title            = title || ('Langkah ' + (idx + 1) + ' — Materi');
    type             = type || 'text';
    minutes          = minutes || 15;
    hasTask          = !!hasTask;
    taskTitle        = taskTitle || ('Tugas: ' + title);
    taskInstructions = taskInstructions || '';
    taskDeadlineDays = taskDeadlineDays || 7;

    return `
    <div class="card border border-2 border-primary-subtle shadow-sm mb-3 segment-item" data-seg-idx="${idx}">
      <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary seg-badge">Langkah #${idx + 1}</span>
          <span class="fw-semibold small text-truncate seg-label-preview" style="max-width:250px;">${title}</span>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-del-seg" title="Hapus Segmen" style="font-size:0.75rem;">
          <i class="bi bi-trash me-1"></i>Hapus
        </button>
      </div>
      <div class="card-body p-3">
        <div class="row g-2 mb-3">
          <div class="col-md-5">
            <label class="form-label small fw-semibold mb-1">Judul Segmen <span class="text-danger">*</span></label>
            <input type="text" name="segments[${idx}][title]" class="form-control form-control-sm seg-input-title"
                   value="${title}" required placeholder="cth: Pengenalan Konsep">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Tipe Konten <span class="text-danger">*</span></label>
            <select name="segments[${idx}][content_type]" class="form-select form-select-sm seg-select-type">
              <option value="text"  ${type==='text'?'selected':''}>Teks &amp; Penjelasan</option>
              <option value="video" ${type==='video'?'selected':''}>Video YouTube / Vimeo</option>
              <option value="html"  ${type==='html'?'selected':''}>HTML Interaktif (Simulasi/Praktik)</option>
              <option value="slide" ${type==='slide'?'selected':''}>Slide Gambar (Presentasi)</option>
              <option value="pdf"   ${type==='pdf'?'selected':''}>Dokumen PDF</option>
              <option value="link"  ${type==='link'?'selected':''}>Tautan Eksternal</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Estimasi (menit)</label>
            <input type="number" name="segments[${idx}][estimated_minutes]" class="form-control form-control-sm"
                   min="1" max="180" value="${minutes}">
          </div>
        </div>

        <!-- Dynamic Content Body per Tipe -->
        <div class="seg-content-body">
          <div class="seg-panel seg-panel-text" style="${type==='text'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Penjelasan Teks</label>
            <textarea name="segments[${idx}][content]" class="form-control form-control-sm" rows="6"
                      placeholder="Tuliskan uraian materi, pengantar, atau teks bacaan di sini..."></textarea>
          </div>

          <div class="seg-panel seg-panel-video" style="${type==='video'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">URL Video YouTube / Vimeo</label>
            <input type="url" name="segments[${idx}][video_url]" class="form-control form-control-sm"
                   placeholder="https://www.youtube.com/watch?v=...">
            <div class="form-text">Masukkan link lengkap video. Sistem akan menampilkan player secara otomatis.</div>
          </div>

          <div class="seg-panel seg-panel-html" style="${type==='html'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Kode HTML / JS Interaktif</label>
            <textarea name="segments[${idx}][content_html]" class="form-control form-control-sm font-monospace" rows="8"
                      placeholder="<!DOCTYPE html>... Tempelkan kode HTML, simulasi PhET, atau widget interaktif"></textarea>
            <div class="form-text text-muted">Akan dijalankan di iframe sandbox yang aman bagi siswa.</div>
          </div>

          <div class="seg-panel seg-panel-slide" style="${type==='slide'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Unggah Gambar Slide</label>
            <input type="file" name="segment_slides_${idx}[]" class="form-control form-control-sm"
                   accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            <div class="form-text">Pilih beberapa gambar (maks 5MB per gambar).</div>
          </div>

          <div class="seg-panel seg-panel-pdf" style="${type==='pdf'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Unggah File PDF</label>
            <input type="file" name="segment_pdf_${idx}" class="form-control form-control-sm" accept=".pdf">
            <div class="form-text">File dokumen materi berformat PDF (maks 10MB).</div>
          </div>

          <div class="seg-panel seg-panel-link" style="${type==='link'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">URL Tautan Eksternal</label>
            <input type="url" name="segments[${idx}][external_link]" class="form-control form-control-sm"
                   placeholder="https://...">
            <div class="form-text">Link ke artikel, Google Docs, materi referensi luar, dll.</div>
          </div>
        </div>

        <!-- Opsi Tugas / Tagihan Aktivitas Segmen -->
        <div class="mt-3 pt-3 border-top">
          <div class="form-check form-switch">
            <input class="form-check-input seg-task-toggle" type="checkbox" name="segments[${idx}][has_task]" value="1" id="segTask_${idx}" ${hasTask ? 'checked' : ''}>
            <label class="form-check-label fw-semibold small text-primary" for="segTask_${idx}">
              <i class="bi bi-clipboard-check me-1"></i>Sertakan Tugas / Tagihan Aktivitas pada Langkah Ini (Formatif)
            </label>
          </div>
          <div class="seg-task-details mt-2 p-2 rounded bg-light border" style="${hasTask ? '' : 'display:none;'}">
            <div class="small text-muted mb-2">
              <i class="bi bi-info-circle me-1"></i>Siswa wajib mengerjakan &amp; mengumpulkan tugas ini sebelum dapat menyelesaikan langkah dan lanjut ke langkah berikutnya.
            </div>
            <div class="row g-2">
              <div class="col-md-8">
                <label class="form-label small fw-semibold mb-1">Judul Tugas</label>
                <input type="text" name="segments[${idx}][task_title]" class="form-control form-control-sm seg-task-title"
                       value="${taskTitle}" placeholder="cth: Refleksi Video &amp; Lembar Analisis">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Batas Waktu (Hari)</label>
                <input type="number" name="segments[${idx}][task_deadline_days]" class="form-control form-control-sm seg-task-deadline"
                       min="1" max="30" value="${taskDeadlineDays}">
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Petunjuk / Instruksi Tugas</label>
                <textarea name="segments[${idx}][task_instructions]" class="form-control form-control-sm seg-task-instructions" rows="2"
                          placeholder="Tuliskan instruksi pengerjaan yang harus dikerjakan dan dikirimkan oleh siswa...">${taskInstructions}</textarea>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>`;
  }

  function attachSegmentEvents(card) {
    var titleInput = card.querySelector('.seg-input-title');
    var labelPrev  = card.querySelector('.seg-label-preview');
    if (titleInput && labelPrev) {
      titleInput.addEventListener('input', function() {
        labelPrev.textContent = this.value || 'Segmen';
      });
    }

    var typeSel = card.querySelector('.seg-select-type');
    if (typeSel) {
      typeSel.addEventListener('change', function() {
        var t = this.value;
        card.querySelectorAll('.seg-panel').forEach(function(p) {
          p.style.display = 'none';
        });
        var activePanel = card.querySelector('.seg-panel-' + t);
        if (activePanel) activePanel.style.display = 'block';
      });
    }

    var delBtn = card.querySelector('.btn-del-seg');
    if (delBtn) {
      delBtn.addEventListener('click', function() {
        var allItems = segContainer.querySelectorAll('.segment-item');
        if (allItems.length <= 1) {
          alert('Minimal harus ada 1 segmen pembelajaran.');
          return;
        }
        card.remove();
        reindexSegments();
      });
    }

    var taskToggle  = card.querySelector('.seg-task-toggle');
    var taskDetails = card.querySelector('.seg-task-details');
    if (taskToggle && taskDetails) {
      taskToggle.addEventListener('change', function() {
        taskDetails.style.display = this.checked ? 'block' : 'none';
      });
    }
  }

  function reindexSegments() {
    var items = segContainer.querySelectorAll('.segment-item');
    items.forEach(function(item, idx) {
      item.dataset.segIdx = idx;
      var badge = item.querySelector('.seg-badge');
      if (badge) badge.textContent = 'Langkah #' + (idx + 1);

      // Update input names
      var title = item.querySelector('.seg-input-title');
      if (title) title.name = 'segments[' + idx + '][title]';

      var type = item.querySelector('.seg-select-type');
      if (type) type.name = 'segments[' + idx + '][content_type]';

      var est = item.querySelector('input[name*="estimated_minutes"]');
      if (est) est.name = 'segments[' + idx + '][estimated_minutes]';

      var txt = item.querySelector('textarea[name*="[content]"]');
      if (txt) txt.name = 'segments[' + idx + '][content]';

      var html = item.querySelector('textarea[name*="[content_html]"]');
      if (html) html.name = 'segments[' + idx + '][content_html]';

      var vid = item.querySelector('input[name*="[video_url]"]');
      if (vid) vid.name = 'segments[' + idx + '][video_url]';

      var link = item.querySelector('input[name*="[external_link]"]');
      if (link) link.name = 'segments[' + idx + '][external_link]';

      var pdf = item.querySelector('input[type="file"][name*="segment_pdf_"]');
      if (pdf) pdf.name = 'segment_pdf_' + idx;

      var slides = item.querySelector('input[type="file"][name*="segment_slides_"]');
      if (slides) slides.name = 'segment_slides_' + idx + '[]';

      var taskCheck = item.querySelector('.seg-task-toggle');
      if (taskCheck) {
        taskCheck.name = 'segments[' + idx + '][has_task]';
        taskCheck.id = 'segTask_' + idx;
        var taskLbl = item.querySelector('label[for^="segTask_"]');
        if (taskLbl) taskLbl.htmlFor = 'segTask_' + idx;
      }
      var taskTitle = item.querySelector('.seg-task-title');
      if (taskTitle) taskTitle.name = 'segments[' + idx + '][task_title]';

      var taskDays = item.querySelector('.seg-task-deadline');
      if (taskDays) taskDays.name = 'segments[' + idx + '][task_deadline_days]';

      var taskInst = item.querySelector('.seg-task-instructions');
      if (taskInst) taskInst.name = 'segments[' + idx + '][task_instructions]';
    });
  }

  function addSegment(title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays) {
    var temp = document.createElement('div');
    temp.innerHTML = createSegmentHtml(segCounter, title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays).trim();
    var card = temp.firstChild;
    segContainer.appendChild(card);
    attachSegmentEvents(card);
    segCounter++;
    reindexSegments();
  }

  if (btnAddSeg) {
    btnAddSeg.addEventListener('click', function() {
      var nextNum = segContainer.querySelectorAll('.segment-item').length + 1;
      addSegment('Langkah ' + nextNum + ': Materi Baru', 'text', 15);
    });
  }

  // Inisialisasi 2 segmen awal untuk mempermudah guru
  if (segContainer && segContainer.children.length === 0) {
    addSegment('Langkah 1: Pengantar & Apersepsi', 'text', 10);
    addSegment('Langkah 2: Video Penjelasan / Materi Inti', 'video', 20);
  }

  // ══════════════════════════════════════════════════════════════════════
  // ASISTEN PRA-PEMBELAJARAN: TARIK SKENARIO MODUL AJAR
  // ══════════════════════════════════════════════════════════════════════
  var modulAjarSelect      = document.getElementById('modulAjarSelect');
  var pertemuanSelect      = document.getElementById('pertemuanSelect');
  var btnPullModulScenario = document.getElementById('btnPullModulScenario');
  var modulScenarioAlert   = document.getElementById('modulScenarioAlert');

  var getModulMeetingsUrl = '<?= base_url('admin/materials/get-modul-meetings') ?>';
  var getModulDraftUrl    = '<?= base_url('admin/materials/get-modul-draft') ?>';

  if (modulAjarSelect) {
    modulAjarSelect.addEventListener('change', function() {
      var mId = this.value;
      if (!mId) {
        pertemuanSelect.innerHTML = '<option value="">-- Pilih Modul Terlebih Dahulu --</option>';
        pertemuanSelect.disabled = true;
        btnPullModulScenario.disabled = true;
        if (modulScenarioAlert) modulScenarioAlert.style.display = 'none';
        return;
      }

      pertemuanSelect.innerHTML = '<option value="">Memuat pertemuan...</option>';
      pertemuanSelect.disabled = true;
      btnPullModulScenario.disabled = true;
      if (modulScenarioAlert) modulScenarioAlert.style.display = 'none';

      fetch(getModulMeetingsUrl + '?modul_id=' + encodeURIComponent(mId))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success || !data.pertemuan || data.pertemuan.length === 0) {
            pertemuanSelect.innerHTML = '<option value="">-- Tidak ada pertemuan terdeteksi --</option>';
            return;
          }

          pertemuanSelect.innerHTML = '<option value="">-- Pilih Pertemuan --</option>';
          data.pertemuan.forEach(function(p) {
            var opt = document.createElement('option');
            opt.value = p.pertemuan_ke;
            opt.textContent = p.label;
            opt.dataset.judul = p.judul;
            opt.dataset.durasi = p.durasi;
            pertemuanSelect.appendChild(opt);
          });
          pertemuanSelect.disabled = false;
        })
        .catch(function(err) {
          console.error('Error fetching meetings:', err);
          pertemuanSelect.innerHTML = '<option value="">-- Gagal memuat pertemuan --</option>';
        });
    });
  }

  if (pertemuanSelect) {
    pertemuanSelect.addEventListener('change', function() {
      btnPullModulScenario.disabled = !this.value;
    });
  }

  if (btnPullModulScenario) {
    btnPullModulScenario.addEventListener('click', function() {
      var mId = modulAjarSelect ? modulAjarSelect.value : '';
      var pKe = pertemuanSelect ? pertemuanSelect.value : '';

      if (!mId || !pKe) {
        alert('Pilih Modul Ajar dan Pertemuan terlebih dahulu.');
        return;
      }

      btnPullModulScenario.disabled = true;
      btnPullModulScenario.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Merancang dengan AI...';

      fetch(getModulDraftUrl + '?modul_id=' + encodeURIComponent(mId) + '&pertemuan_ke=' + encodeURIComponent(pKe))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          btnPullModulScenario.disabled = false;
          btnPullModulScenario.innerHTML = '<i class="bi bi-stars me-1 text-warning"></i> Desain Alur dengan AI';

          if (!data.success || !data.package) {
            alert(data.message || 'Gagal merancang alur pembelajaran.');
            return;
          }

          var pkg = data.package || {};
          var meeting = data.meeting || {};

          // Ambil ringkasan teks secara aman dari berbagai struktur data
          var summaryRaw = pkg.summary || pkg.summary_html ||
                           (pkg.micro_learning && pkg.micro_learning.summary_html ? pkg.micro_learning.summary_html : '') ||
                           (meeting.raw_text || '');

          // 1. Isi Judul Sub Materi
          var titleInput = document.querySelector('input[name="title"]');
          if (titleInput) {
            titleInput.value = pkg.sub_judul || data.sub_judul || meeting.judul || ('Pertemuan ' + pKe);
          }

          // 2. Isi Urutan
          var orderInput = document.querySelector('input[name="sort_order"]');
          if (orderInput) {
            orderInput.value = pKe;
          }

          // 3. Isi Estimasi Menit (ekstrak angka)
          var estInput = document.querySelector('input[name="estimated_minutes"]');
          var durasiSource = meeting.durasi || (pkg.total_minutes ? (pkg.total_minutes + ' menit') : '70 menit');
          if (estInput && durasiSource) {
            var dm = durasiSource.match(/(\d+)/);
            if (dm) estInput.value = parseInt(dm[1], 10);
          }

          // 4. Isi Deskripsi Singkat
          var descInput = document.querySelector('textarea[name="description"]');
          if (descInput && summaryRaw) {
            var plainText = summaryRaw.replace(/<[^>]*>/g, '').trim();
            descInput.value = plainText.length > 200 ? (plainText.substring(0, 197) + '...') : plainText;
          }

          // 5. Pastikan Multi-Segmen Aktif
          if (modeMultiRadio) {
            modeMultiRadio.checked = true;
            updateBuilderMode();
          }

          // 6. Isi Segmen Pembelajaran Aksi Nyata Beragam Media
          var segmentsToRender = (pkg.segments && Array.isArray(pkg.segments) && pkg.segments.length > 0)
            ? pkg.segments
            : [
                {
                  title: 'Langkah 1: Stimulus Apersepsi Video',
                  content_type: 'video',
                  estimated_minutes: 15,
                  video_url: 'https://www.youtube.com/results?search_query=' + encodeURIComponent(titleInput ? titleInput.value : 'pembelajaran sd')
                },
                {
                  title: 'Langkah 2: Eksplorasi Konsep Utama',
                  content_type: 'text',
                  estimated_minutes: 25,
                  content: summaryRaw ? summaryRaw.replace(/<\/?p>/g, '\n\n').replace(/<[^>]*>/g, '').trim() : ('Materi pembelajaran Pertemuan ' + pKe)
                },
                {
                  title: 'Langkah 3: Simulasi Interaktif & Praktik Digital',
                  content_type: 'html',
                  estimated_minutes: 20,
                  content_html: '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:20px;background:#f8fafc;"><h3>Simulasi Pemahaman</h3><p>Ujilah pemahamanmu dengan menjawab tantangan konsep!</p></body></html>'
                },
                {
                  title: 'Langkah 4: Refleksi & Diskusi Pemantik',
                  content_type: 'text',
                  estimated_minutes: 10,
                  content: 'Pertanyaan Diskusi:\n' + ((pkg.discussion && pkg.discussion.prompt) ? pkg.discussion.prompt : (pkg.discussion_prompt || 'Menurutmu, apa manfaat utama materi ini?'))
                }
              ];

          if (segContainer) {
            segContainer.innerHTML = '';
            segCounter = 0;
            var totalEstimated = 0;

            segmentsToRender.forEach(function(seg, idx) {
              var sTitle = seg.title || ('Langkah ' + (idx + 1));
              var sType  = seg.content_type || 'text';
              var sMin   = parseInt(seg.estimated_minutes, 10) || 15;
              totalEstimated += sMin;

              var sHasTask   = !!seg.has_task;
              var sTaskTitle = seg.task_title || ('Tugas: ' + sTitle);
              var sTaskInst  = seg.task_instructions || '';
              var sTaskDays  = parseInt(seg.task_deadline_days, 10) || 7;

              addSegment(sTitle, sType, sMin, sHasTask, sTaskTitle, sTaskInst, sTaskDays);
              var card = segContainer.querySelector('.segment-item[data-seg-idx="' + idx + '"]');
              if (!card) return;

              // Isi konten sesuai tipe yang dipilih
              if (sType === 'text') {
                var txt = card.querySelector('textarea[name*="[content]"]');
                if (txt) txt.value = seg.content || '';
              } else if (sType === 'video') {
                var vid = card.querySelector('input[name*="[video_url]"]');
                if (vid) vid.value = seg.video_url || '';
              } else if (sType === 'html') {
                var htm = card.querySelector('textarea[name*="[content_html]"]');
                if (htm) htm.value = seg.content_html || '';
              } else if (sType === 'link') {
                var lnk = card.querySelector('input[name*="[external_link]"]');
                if (lnk) lnk.value = seg.external_link || '';
              }
            });

            // Update estimasi waktu total agar pas dengan durasi seluruh segmen
            if (estInput && totalEstimated > 0) {
              estInput.value = totalEstimated;
            }
          }

          if (modulScenarioAlert) {
            var isAi = (pkg.source === 'ai');
            var badgeAi = isAi
              ? '<span class="badge bg-primary text-white me-1"><i class="bi bi-stars text-warning me-1"></i> AI Gemini Copilot</span>'
              : '<span class="badge bg-secondary text-white me-1"><i class="bi bi-magic me-1"></i> Smart Instructional Assistant</span>';

            modulScenarioAlert.className = 'alert alert-success py-2 px-3 small mt-2 mb-0 border border-success border-opacity-25';
            modulScenarioAlert.innerHTML = '<div class="d-flex align-items-center gap-2 mb-1">' + badgeAi + '<strong>Berhasil merancang ' + segmentsToRender.length + ' segmen aksi nyata (' + (totalEstimated || 70) + ' Menit)</strong></div>' +
              '<div class="text-secondary smaller">Modul Ajar telah diuraikan menjadi ragam kegiatan aktif siswa: <strong>Tayangan Video, Penjelasan Teks, Simulasi HTML Interaktif, dan Refleksi</strong>. Anda dapat langsung meninjau, menguji, atau mengubah setiap langkah sebelum menyimpan.</div>';
            modulScenarioAlert.style.display = 'block';
          }
        })
        .catch(function(err) {
          console.error('Error fetching modul draft:', err);
          btnPullModulScenario.disabled = false;
          btnPullModulScenario.innerHTML = '<i class="bi bi-stars me-1 text-warning"></i> Desain Alur dengan AI';
          alert('Terjadi kesalahan saat merancang alur dengan AI.');
        });
    });
  }

  // ══════════════════════════════════════════════════════════════════════
  // FORM SUBMIT HANDLER
  // ══════════════════════════════════════════════════════════════════════
  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function() {
      var isMulti = modeMultiRadio && modeMultiRadio.checked;
      if (isMulti) {
        // Dalam mode multi, pastikan tipe konten utama diisi dari tipe segmen pertama
        var firstType = segContainer.querySelector('.seg-select-type');
        if (firstType && contentTypeSel) {
          contentTypeSel.value = firstType.value;
        }
      } else {
        // Dalam mode single, bersihkan container segmen agar tidak dikirim
        if (segContainer) segContainer.innerHTML = '';
      }
    });
  }

  updateBuilderMode();
})();
</script>
<?= $this->endSection() ?>
