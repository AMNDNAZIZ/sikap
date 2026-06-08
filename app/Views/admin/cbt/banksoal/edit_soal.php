<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Edit Soal Bank: <?= esc($bank['code']) ?></h4>
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
      <form id="formEditSoal"
        action="<?= site_url('admin/cbt/banksoal/update_soal/' . $bank['id'] . '/' . $soal['id']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-bold">Edit Soal (lengkap beserta opsi dan gambar)</label>
          <textarea id="editor" name="raw_text" style="min-height: 600px;"><?= esc($soal['raw_text'] ?? $soal['question_text']) ?></textarea>
        </div>

        <!-- 🎵 AUDIO UPLOAD -->
        <div class="mb-3">
          <label class="form-label fw-bold">File Audio (Listening) - Opsional</label>
          <div class="input-group">
            <input type="file" name="audio_file" class="form-control" accept="audio/*">
          </div>
          <small class="text-muted">Format: MP3, WAV, OGG. Maks 5MB.</small>

          <?php if (!empty($soal['media_audio'])): ?>
            <div class="mt-2 p-2 border rounded bg-light" id="audioPreview">
              <p class="mb-1 text-success"><i class="bi bi-volume-up"></i> Audio Terpasang:</p>
              <audio controls class="w-100 mb-2">
                <source src="<?= base_url('uploads/audio/' . $soal['media_audio']) ?>">
                Browser Anda tidak mendukung elemen audio.
              </audio>
              <button type="button" class="btn btn-sm btn-danger" onclick="deleteAudio(<?= $soal['id'] ?>, 'main')">
                <i class="bi bi-trash"></i> Hapus Audio
              </button>
            </div>
          <?php endif; ?>
        </div>

        <!-- 🎵 OPTION AUDIO UPLOADS -->
        <label class="form-label fw-bold mt-2">Audio Opsi (Opsional)</label>
        <div class="row">
          <?php foreach (['a', 'b', 'c', 'd', 'e'] as $opt):
            $field = 'audio_' . $opt;
            $has = !empty($soal[$field]);
            ?>
            <div class="col-md-6 mb-3">
              <div class="card bg-light border">
                <div class="card-body p-2">
                  <strong>Opsi <?= strtoupper($opt) ?></strong>
                  <input type="file" name="<?= $field ?>" class="form-control form-control-sm mt-1" accept="audio/*">
                  <?php if ($has): ?>
                    <div class="mt-2" id="audioPreview_<?= $opt ?>">
                      <audio controls class="w-100" style="height:30px;">
                        <source src="<?= base_url('uploads/audio/' . $soal[$field]) ?>">
                      </audio>
                      <button type="button" class="btn btn-xs btn-outline-danger w-100 mt-1"
                        onclick="deleteAudio(<?= $soal['id'] ?>, '<?= $opt ?>')">
                        Hapus Audio <?= strtoupper($opt) ?>
                      </button>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <button type="button" id="btnPreview" class="btn btn-info">
            <i class="bi bi-eye"></i> Preview Parsing
          </button>
          <button type="submit" id="btnSave" class="btn btn-success">
            <i class="bi bi-save"></i> Simpan Perubahan
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
    const draftKey = 'cbt_edit_soal_' + <?= $bank['id'] ?> + '_<?= $soal['id'] ?>';
    const oldContent = <?= json_encode($soal['raw_text'] ?? $soal['question_text']) ?>;
    let editorInstance = null;

    // 🔧 Inisialisasi CKEditor Superbuild
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

        // Load konten: prioritas draft > old content
        const savedDraft = localStorage.getItem(draftKey);
        if (savedDraft && savedDraft.trim() !== '') {
          editor.setData(savedDraft);
        } else if (oldContent && oldContent.trim() !== '') {
          editor.setData(oldContent);
        }

        // Auto-save draft saat ada perubahan
        editor.model.document.on('change:data', () => {
          localStorage.setItem(draftKey, editor.getData());
        });
      })
      .catch(error => {
        console.error('Error loading CKEditor:', error);
        Swal.fire('Error', 'Gagal memuat editor. Silakan refresh halaman.', 'error');
      });

    // 🔍 Preview Parsing
    $('#btnPreview').on('click', function () {
      if (!editorInstance) {
        Swal.fire('Error', 'Editor belum siap', 'error');
        return;
      }
      const content = editorInstance.getData();
      const plain = $('<div>').html(content).text().trim();

      if (!plain) return Swal.fire('Kosong', 'Silakan tempelkan soal terlebih dahulu.', 'warning');

      const blocks = plain.split(/Soal:\s*\d+\)/i).filter(b => b.trim() !== '');
      const total = blocks.length;
      const pgCount = (plain.match(/Kunci\s*[:.]?\s*[A-E]/gi) || []).length;
      const bsCount = (plain.match(/Tipe\s*[:.]?\s*bs/gi) || []).length;
      const esaiCount = (plain.match(/Kunci\s*[:.]?\s*esai/gi) || []).length;

      let preview = '';
      blocks.slice(0, 3).forEach((b, i) => {
        const cut = b.length > 200 ? b.substring(0, 200) + '...' : b;
        preview += `<div class="p-2 mb-2 border rounded bg-white">
        <strong>Soal ${i + 1}:</strong> ${cut}
      </div>`;
      });

      $('#previewResult').html(`
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <i class="bi bi-eye"></i> Preview Soal
        </div>
        <div class="card-body">
          <p><strong>Total Soal:</strong> ${total} | PG: ${pgCount} | BS: ${bsCount} | Esai: ${esaiCount}</p>
          ${preview}
        </div>
      </div>
    `);

      // Render MathJax pada preview
      if (typeof MathJax !== 'undefined') {
        MathJax.typesetPromise().catch((err) => console.log('MathJax error:', err));
      }
    });

    // 💾 Simpan Perubahan
    $('#formEditSoal').on('submit', function (e) {
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
          Swal.fire('Berhasil', 'Soal berhasil diperbarui!', 'success').then(() => {
            window.location.href = "<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>";
          });
        },
        error: function () {
          Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan soal.', 'error');
        },
        complete: function () {
          $('#btnSave').prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Perubahan');
        }
      });
    });
  });

  // 🗑️ Fungsi Hapus Audio
  function deleteAudio(id, type = 'main') {
    Swal.fire({
      title: 'Hapus Audio?',
      text: "File audio akan dihapus permanen.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: "<?= site_url('admin/cbt/banksoal/deleteAudio/') ?>" + id + "/" + type,
          type: "GET",
          success: function (response) {
            if (response.success) {
              Swal.fire('Terhapus', 'Audio berhasil dihapus.', 'success');
              if (type === 'main') {
                $('#audioPreview').remove();
              } else {
                $('#audioPreview_' + type).remove();
              }
            } else {
              Swal.fire('Gagal', response.message, 'error');
            }
          }
        });
      }
    });
  }
</script>

<?= $this->endSection() ?>