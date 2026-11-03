<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  @media (max-width: 768px) {
    #editor {
      min-height: 320px !important;
    }
    .ck-editor__editable {
      min-height: 280px !important;
    }
  }
</style>

<div class="container-fluid py-2">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Tambah Soal ke Bank: <span class="text-primary"><?= esc($bank['code']) ?></span></h4>
      <small class="text-muted">
        Mata pelajaran: <strong><?= esc($bank['subject_name'] ?? '-') ?></strong> &bull; Level: <strong><?= esc($bank['level']) ?></strong>
      </small>
    </div>
    <a href="<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
  </div>

  <div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body">
      <form id="formAddSoal" method="post" action="<?= site_url('admin/cbt/banksoal/saveParsedSoal/' . $bank['id']) ?>">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-bold">Paste Soal dari Word</label>
          <textarea id="editor" name="raw_text" style="min-height: 500px;"></textarea>
          <small class="text-muted d-block mt-2">
            Format contoh:
            <pre class="bg-light p-2 rounded mt-2" style="font-size: 0.85rem; overflow-x: auto;">
Soal:1)Warna bendera Indonesia adalah ....
A:Putih Merah
B:Merah Putih
C:Hijau Biru
D:Kuning Kelabu
Kunci:B

Soal:2)Berikut yang merupakan warna primer adalah ....
A:Merah
B:Kuning
C:Hijau
D:Biru
Kunci:A,B,D

Soal:3)Pernyataan berikut benar atau salah?
A:Matahari terbit dari barat
B:Air adalah benda cair
Tipe:BS
Kunci:S,B

Soal:4)Siapakah pencipta lagu Indonesia Raya?
Kunci:esai
            </pre>
          </small>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mt-3">
          <button type="button" id="btnPreview" class="btn btn-info text-white py-2 px-3 shadow-sm">
            <i class="bi bi-eye me-1"></i> Preview Parsing
          </button>
          <button type="submit" id="btnSave" class="btn btn-success py-2 px-4 shadow-sm fw-semibold">
            <i class="bi bi-save me-1"></i> Simpan Semua Soal
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="previewResult" class="mt-4"></div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<!-- 🔹 Load CKEditor Superbuild (Full Featured) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>

<!-- 🔹 Load MathJax untuk Formula Matematika -->
<script>
window.MathJax = {
  tex: {
    inlineMath: [['$', '$'], ['\\(', '\\)']],
    displayMath: [['$$', '$$'], ['\\[', '\\]']],
    processEscapes: true,
    processEnvironments: true
  },
  options: {
    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
  },
  startup: {
    pageReady: () => {
      return MathJax.startup.defaultPageReady().catch((err) => {
        // Suppress MathJax warnings
        return Promise.resolve();
      });
    }
  }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>

<script>
  $(function () {
    const draftKey = 'cbt_soal_draft_' + <?= $bank['id'] ?>;
    let editorInstance = null;

    // Inisialisasi CKEditor Superbuild dengan custom upload adapter
    CKEDITOR.ClassicEditor
      .create(document.querySelector('#editor'), {
        toolbar: {
          items: [
            'undo', 'redo', '|',
            'heading', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
            'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
            'link', 'uploadImage', 'insertTable', 'blockQuote', 'specialCharacters', '|',
            'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
            'alignment', '|',
            'removeFormat', 'sourceEditing'
          ],
          shouldNotGroupWhenFull: true
        },
        image: {
          toolbar: [
            'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline',
            'imageStyle:block', 'imageStyle:side'
          ]
        },
        table: {
          contentToolbar: [
            'tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'
          ]
        },
        removePlugins: [
          // These are various CKEditor 5 premium features that require a license key
          'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit', 'CKBoxImageEditEditing', 
          'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage', 'Comments', 'CommentsRepository', 
          'RealTimeCollaborativeComments', 'TrackChanges', 'TrackChangesEditing', 'TrackChangesData', 
          'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'RealTimeCollaborativeRevisionHistory', 
          'PresenceList', 'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType', 'ChemType', 
          'Mentions', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents', 
          'PasteFromOfficeEnhanced', 'CaseChange', 'WideSidebar', 'ExportPdf', 'ExportWord'
        ],
        fontSize: {
          options: [ 9, 11, 13, 'default', 17, 19, 21 ],
          supportAllValues: true
        },
        fontFamily: {
          options: [
            'default',
            'Arial, Helvetica, sans-serif',
            'Courier New, Courier, monospace',
            'Georgia, serif',
            'Lucida Sans Unicode, Lucida Grande, sans-serif',
            'Tahoma, Geneva, sans-serif',
            'Times New Roman, Times, serif',
            'Trebuchet MS, Helvetica, sans-serif',
            'Verdana, Geneva, sans-serif'
          ],
          supportAllValues: true
        },
        fontColor: {
          columns: 5,
          documentColors: 10
        },
        fontBackgroundColor: {
          columns: 5,
          documentColors: 10
        },
        htmlSupport: {
          allow: [
            {
              name: /.*/,
              attributes: true,
              classes: true,
              styles: true
            }
          ]
        }
      })
      .then(editor => {
        editorInstance = editor;

        // Set custom CSS untuk tinggi
        editor.editing.view.change(writer => {
          writer.setStyle('height', '500px', editor.editing.view.document.getRoot());
        });

        // Custom upload adapter
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
          return {
            upload: () => {
              return loader.file.then(file => {
                return new Promise((resolve, reject) => {
                  const formData = new FormData();
                  formData.append('file', file);
                  // Add CSRF token for security
                  const csrfName = '<?= csrf_token() ?>';
                  const csrfHash = '<?= csrf_hash() ?>';
                  formData.append(csrfName, csrfHash);
                  
                  console.log('Upload with CSRF:', csrfName, '=', csrfHash);

                  $.ajax({
                    url: '<?= site_url("admin/cbt/banksoal/uploadImage") ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                      console.log('Upload success:', response);
                      if (response.url || response.location) {
                        resolve({
                          default: response.url || response.location
                        });
                      } else {
                        reject('Upload gagal: No URL in response');
                      }
                    },
                    error: function (xhr) {
                      console.error('Upload error:', xhr);
                      console.error('Status:', xhr.status, xhr.statusText);
                      console.error('Response:', xhr.responseText);
                      
                      let errorMsg = 'Upload gagal';
                      if (xhr.status === 403) {
                        errorMsg = 'Upload gagal: CSRF token invalid atau session expired. Silakan refresh halaman.';
                      } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                      } else if (xhr.responseText) {
                        errorMsg = xhr.responseText.substring(0, 200);
                      }
                      reject(errorMsg);
                    }
                  });
                });
              });
            }
          };
        };

        // Load draft dari localStorage
        const savedDraft = localStorage.getItem(draftKey);
        if (savedDraft && savedDraft.trim() !== '') {
          editor.setData(savedDraft);
        }

        // Auto-save draft
        editor.model.document.on('change:data', () => {
          localStorage.setItem(draftKey, editor.getData());
        });
      })
      .catch(error => {
        console.error('Error loading CKEditor:', error);
        Swal.fire('Error', 'Gagal memuat editor. Silakan refresh halaman.', 'error');
      });

    // 🔹 Preview parsing
    $('#btnPreview').on('click', function () {
      if (!editorInstance) {
        Swal.fire('Error', 'Editor belum siap', 'error');
        return;
      }
      const html = editorInstance.getData().trim();
      if (!html) return Swal.fire('Kosong', 'Silakan tempelkan soal terlebih dahulu.', 'warning');

      const btn = $(this);
      const oldHtml = btn.html();
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengolah Preview...');

      $.ajax({
        url: '<?= site_url("admin/cbt/banksoal/parseSoal") ?>',
        type: 'POST',
        data: {
          text: html,
          bank_id: <?= (int)$bank['id'] ?>,
          '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(res) {
          if (!res.success || !res.preview || res.preview.length === 0) {
            Swal.fire('Perhatian', res.error || 'Format tidak dikenali.', 'warning');
            return;
          }

          let pgCount = 0, pgkCount = 0, bsCount = 0, esaiCount = 0;
          let previewSoals = '';

          res.preview.forEach((q, idx) => {
            if (q.type === 'pg') pgCount++;
            else if (q.type === 'pg_kompleks') pgkCount++;
            else if (q.type === 'benar_salah') bsCount++;
            else esaiCount++;

            let optList = '';
            if (q.options && Object.keys(q.options).length > 0) {
              optList = '<div class="mt-2 ps-2 border-start border-2 border-primary-subtle small">';
              $.each(q.options, function(k, v) {
                const isKey = (q.answer && q.answer.split(',').includes(k)) ? 'fw-bold text-success' : 'text-muted';
                optList += `<div class="${isKey} mb-0.5"><strong>${k}:</strong> ${v}</div>`;
              });
              optList += '</div>';
            }

            const badgeType = q.type === 'pg' ? '<span class="badge bg-primary">PG</span>' :
                             (q.type === 'pg_kompleks' ? '<span class="badge bg-info">PGK</span>' :
                             (q.type === 'benar_salah' ? '<span class="badge bg-warning text-dark">BS</span>' :
                             '<span class="badge bg-secondary">Esai</span>'));

            const keyBadge = q.answer ? `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Kunci: ${q.answer}</span>` : '';

            previewSoals += `
              <div class="mb-2.5 p-3 border rounded bg-white shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-1">
                  <strong class="text-primary">Soal #${idx + 1}</strong>
                  <div>${badgeType} ${keyBadge}</div>
                </div>
                <div class="text-dark small fw-medium">${q.text}</div>
                ${optList}
              </div>`;
          });

          const previewHtml = `
          <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white py-2.5 d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold"><i class="bi bi-eye me-1"></i> Hasil Preview Parsing (${res.count} Soal Terdeteksi)</h6>
            </div>
            <div class="card-body">
              <div class="row text-center mb-3 g-2">
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${pgCount}</strong><br><small class="text-muted">Pilihan Ganda</small></div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${pgkCount}</strong><br><small class="text-muted">PG Kompleks</small></div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${bsCount}</strong><br><small class="text-muted">Benar / Salah</small></div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${esaiCount}</strong><br><small class="text-muted">Esai / Uraian</small></div></div>
              </div>
              <div style="max-height: 480px; overflow-y: auto;" class="p-1">
                ${previewSoals}
              </div>
            </div>
          </div>`;

          $('#previewResult').html(previewHtml);

          if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
            MathJax.typesetPromise([document.getElementById('previewResult')]).catch(err => console.log('MathJax error:', err));
          }
        },
        error: function(xhr) {
          Swal.fire('Error', 'Gagal memproses preview parsing dari server.', 'error');
        },
        complete: function() {
          btn.prop('disabled', false).html(oldHtml);
        }
      });
    });

    // 🔹 Submit (Simpan Soal)
    $('#formAddSoal').on('submit', function (e) {
      e.preventDefault();
      if (!editorInstance) {
        Swal.fire('Error', 'Editor belum siap', 'error');
        return;
      }
      const html = editorInstance.getData().trim();
      if (!html) return Swal.fire('Kosong', 'Silakan tempelkan soal terlebih dahulu.', 'warning');

      $('#btnSave').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

      const formData = new FormData(this);
      formData.set('raw_text', html);

      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
          localStorage.removeItem(draftKey);
          Swal.fire('Berhasil', 'Soal berhasil disimpan!', 'success').then(() => {
            window.location.href = "<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>";
          });
        },
        error: function (xhr) {
          Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan soal.', 'error');
        },
        complete: function () {
          $('#btnSave').prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Semua Soal');
        }
      });
    });
  });
</script>

<?= $this->endSection() ?>