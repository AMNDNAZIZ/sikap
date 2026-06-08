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
          <textarea id="editor" name="raw_text"></textarea>
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

<!-- 🔹 Load CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>

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
    let editorInstance;

    ClassicEditor
      .create(document.querySelector('#editor'), {
        toolbar: {
          items: [
            'undo', 'redo',
            '|', 'heading',
            '|', 'bold', 'italic', 'underline', 'strikethrough',
            '|', 'link', 'uploadImage', 'insertTable', 'blockQuote',
            '|', 'bulletedList', 'numberedList', 'outdent', 'indent',
            '|', 'alignment',
            '|', 'removeFormat'
          ]
        },
        image: {
          toolbar: [
            'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline',
            'imageStyle:block', 'imageStyle:side'
          ]
        },
        table: {
          contentToolbar: [
            'tableColumn', 'tableRow', 'mergeTableCells'
          ]
        },
        height: '500px'
      })
      .then(editor => {
        editorInstance = editor;
        editor.editing.view.change(writer => {
          writer.setStyle('height', '500px', editor.editing.view.document.getRoot());
        });

        // 🔥 PENTING: Custom Upload Adapter untuk handle paste gambar dari Word
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
          return {
            upload: () => {
              return loader.file.then(file => {
                return new Promise((resolve, reject) => {
                  // Get fresh CSRF token before upload
                  $.get('<?= site_url('admin/cbt/csrf/refresh') ?>', function(csrfData) {
                    const formData = new FormData();
                    formData.append('upload', file);
                    formData.append(csrfData.token_name, csrfData.token_hash);

                    $.ajax({
                      url: '<?= site_url('admin/cbt/banksoal/uploadImage') ?>',
                      type: 'POST',
                      data: formData,
                      processData: false,
                      contentType: false,
                      success: function(response) {
                        console.log('Upload success:', response);
                        if (response.url) {
                          resolve({ default: response.url });
                        } else if (response.location) {
                          resolve({ default: response.location });
                        } else {
                          reject(response.error || 'Upload failed: response tidak memiliki URL');
                        }
                      },
                      error: function(xhr, status, error) {
                        console.error('Upload error:', xhr.responseText);
                        reject('Upload failed: ' + error);
                      }
                    });
                  }).fail(function() {
                    reject('Failed to get CSRF token');
                  });
                });
              });
            },
            abort: () => {
              console.log('Upload aborted');
            }
          };
        };
      })
      .catch(error => {
        console.error('CKEditor initialization error:', error);
      });

    // 🔹 Preview parsing
    $('#btnPreview').on('click', function () {
      if (!editorInstance) return Swal.fire('Error', 'Editor belum siap.', 'error');
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
      if (!editorInstance) return Swal.fire('Error', 'Editor belum siap.', 'error');
      const html = editorInstance.getData().trim();
      if (!html) return Swal.fire('Kosong', 'Silakan tempelkan soal terlebih dahulu.', 'warning');

      $('#btnSave').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

      // Refresh CSRF token sebelum submit
      $.get('<?= site_url('admin/cbt/csrf/refresh') ?>', function(csrfData) {
        const formData = new FormData();
        formData.set('raw_text', html);
        formData.set(csrfData.token_name, csrfData.token_hash);

        $.ajax({
          url: $('#formAddSoal').attr('action'),
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
            console.error('Error:', xhr.responseText);
            let errorMsg = 'Terjadi kesalahan saat menyimpan soal.';
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.error) errorMsg = response.error;
            } catch(e) {}
            Swal.fire('Gagal', errorMsg, 'error');
          },
          complete: function () {
            $('#btnSave').prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Semua Soal');
          }
        });
      });
    });
  });
</script>

<?= $this->endSection() ?>