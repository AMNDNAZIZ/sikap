<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Tambah Soal ke Bank: <?= esc($bank['code']) ?></h4>
      <small class="text-muted">
        Mata pelajaran: <?= esc($bank['subject_name'] ?? '-') ?> | Level: <?= esc($bank['level']) ?>
      </small>
    </div>
    <a href="<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form id="formAddSoal" method="post" action="<?= site_url('admin/cbt/banksoal/saveParsedSoal/' . $bank['id']) ?>">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-bold">Paste Soal dari Word</label>
          <textarea id="editor" name="raw_text" style="min-height: 600px;"></textarea>
          <small class="text-muted d-block mt-2">
            Format contoh:
            <pre class="bg-light p-2 rounded mt-2">
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

        <div class="d-flex justify-content-between align-items-center mt-3">
          <button type="button" id="btnPreview" class="btn btn-info">
            <i class="bi bi-eye"></i> Preview Parsing
          </button>
          <button type="submit" id="btnSave" class="btn btn-success">
            <i class="bi bi-save"></i> Simpan Semua Soal
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
      const text = $('<div>').html(html).text().trim();

      if (!text) return Swal.fire('Kosong', 'Silakan tempelkan soal terlebih dahulu.', 'warning');

      const blocks = text.split(/Soal:\s*\d+\)/i).filter(b => b.trim() !== '');
      const validBlocks = blocks.filter(b => b.trim().length > 30);
      const totalBlocks = validBlocks.length;
      let pgCount = 0, pgkCount = 0, bsCount = 0, esaiCount = 0;

      validBlocks.forEach(block => {
        const isBs = /Tipe\s*[:.)]?\s*(bs|benar\s*salah|benar\/salah)/i.test(block);
        const kunciMatch = block.match(/Kunci\s*[:.)]?\s*([^\r\n<]*)/i);
        const rawKey = kunciMatch ? kunciMatch[1].trim() : '';

        if (isBs) {
          bsCount++;
        } else if (/esai/i.test(rawKey)) {
          esaiCount++;
        } else {
          const keyMatches = rawKey.match(/[A-E]/gi) || [];
          if (keyMatches.length > 1) {
            pgkCount++;
          } else if (keyMatches.length === 1) {
            pgCount++;
          } else if (!/[A-E]\s*[:.)]/i.test(block)) {
            esaiCount++;
          }
        }
      });

      let previewSoals = '';
      validBlocks.slice(0, 100).forEach((block, idx) => {
        const plainBlock = block.replace(/<[^>]*>/g, ' ').trim();
        const soalPreview = plainBlock.substring(0, 200) + (plainBlock.length > 200 ? '...' : '');
        previewSoals += `<div class="mb-2 p-2 border rounded bg-light"><strong>${idx + 1}.</strong> ${soalPreview}</div>`;
      });

      const previewHtml = `
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-eye"></i> Preview Parsing</h6></div>
        <div class="card-body">
          <div class="row text-center mb-3">
            <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${pgCount}</strong><br><small>PG</small></div></div>
            <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${pgkCount}</strong><br><small>PGK</small></div></div>
            <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${bsCount}</strong><br><small>BS</small></div></div>
            <div class="col-3"><div class="p-2 border rounded bg-light"><strong>${esaiCount}</strong><br><small>Esai</small></div></div>
          </div>
          <p class="mb-2"><strong>Total Soal:</strong> ${totalBlocks}</p>
          <div style="max-height: 400px; overflow-y: auto;">
            ${previewSoals}
          </div>
        </div>
      </div>`;
      $('#previewResult').html(previewHtml);

      // Render MathJax pada preview
      if (typeof MathJax !== 'undefined') {
        MathJax.typesetPromise().catch((err) => console.log('MathJax error:', err));
      }
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