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
      <h4 class="mb-0 fw-bold">Edit Soal Bank: <span class="text-primary"><?= esc($bank['code']) ?></span></h4>
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
      <form id="formEditSoal"
        action="<?= site_url('admin/cbt/banksoal/update_soal/' . $bank['id'] . '/' . $soal['id']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="row g-3 mb-3 align-items-center">
          <div class="col-md-5">
            <label class="form-label fw-bold mb-1"><i class="bi bi-tag me-1"></i> Jenis Soal</label>
            <select name="question_type" id="questionTypeSelect" class="form-select shadow-sm fw-medium">
              <option value="pg" <?= ($soal['question_type'] ?? '') === 'pg' ? 'selected' : '' ?>>Pilihan Ganda (PG Tunggal)</option>
              <option value="pg_kompleks" <?= ($soal['question_type'] ?? '') === 'pg_kompleks' ? 'selected' : '' ?>>PG Kompleks (Bisa >1 Jawaban Benar)</option>
              <option value="benar_salah" <?= ($soal['question_type'] ?? '') === 'benar_salah' ? 'selected' : '' ?>>Benar / Salah (B/S)</option>
              <option value="esai" <?= ($soal['question_type'] ?? '') === 'esai' ? 'selected' : '' ?>>Esai / Uraian</option>
            </select>
          </div>
          <div class="col-md-7">
            <div class="alert alert-light border py-2 px-3 mb-0 small text-muted">
              <i class="bi bi-info-circle text-primary me-1"></i>
              Jika soal merupakan <strong>pilihan ganda biasa</strong>, pastikan jenis soal diatur ke <strong>Pilihan Ganda (PG Tunggal)</strong>.
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Edit Soal (lengkap beserta opsi dan gambar)</label>
          <textarea id="editor" name="raw_text" style="min-height: 600px;"><?= esc($soal['editor_html'] ?? $soal['raw_text'] ?? $soal['question_text'] ?? '') ?></textarea>
          <div class="form-text text-muted mt-1">
            Format standar: tulis teks/wacana soal di bagian atas, diikuti baris opsi <code>A: ...</code> s.d. <code>E: ...</code> (untuk soal objektif), lalu baris <code>Kunci: ...</code> (atau <code>Jawaban: ...</code> untuk Esai).
          </div>
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

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mt-3">
          <button type="button" id="btnPreview" class="btn btn-info text-white py-2 px-3 shadow-sm">
            <i class="bi bi-eye me-1"></i> Preview Parsing
          </button>
          <button type="submit" id="btnSave" class="btn btn-success py-2 px-4 shadow-sm fw-semibold">
            <i class="bi bi-save me-1"></i> Simpan Perubahan
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
    const bankId = <?= (int) $bank['id'] ?>;
    const draftKey = 'cbt_edit_soal_' + bankId + '_<?= (int) $soal['id'] ?>';
    const oldContent = <?= json_encode($soal['editor_html'] ?? $soal['raw_text'] ?? $soal['question_text'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    const csrfTokenName = '<?= csrf_token() ?>';
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

        // Bersihkan draft lawas agar data terbaru dari database selalu menjadi acuan utama saat halaman dibuka
        localStorage.removeItem(draftKey);
        if (oldContent && oldContent.trim() !== '') {
          editor.setData(oldContent);
        }
      })
      .catch(error => {
        console.error('Error loading CKEditor:', error);
        Swal.fire('Error', 'Gagal memuat editor. Silakan refresh halaman.', 'error');
      });

    function getCurrentCsrfHash() {
      return $('input[name="' + csrfTokenName + '"]').val() || '<?= csrf_hash() ?>';
    }

    function updateCsrfHash(newHash) {
      if (newHash) {
        $('input[name="' + csrfTokenName + '"]').val(newHash);
      }
    }

    // 🔍 Preview Parsing (menggunakan parser server tunggal yang sama persis dengan proses Simpan)
    $('#btnPreview').on('click', function () {
      if (!editorInstance) {
        Swal.fire('Error', 'Editor belum siap', 'error');
        return;
      }
      const content = editorInstance.getData().trim();
      const plain = $('<div>').html(content).text().trim();
      const hasImg = /<img\b/i.test(content);

      if (!plain && !hasImg) {
        return Swal.fire('Kosong', 'Silakan isi teks soal terlebih dahulu.', 'warning');
      }

      const $btn = $(this);
      $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Memproses Preview...');

      const postData = {
        text: content,
        bank_id: bankId,
        single: 1,
        question_type: $('#questionTypeSelect').val()
      };
      postData[csrfTokenName] = getCurrentCsrfHash();

      $.ajax({
        url: '<?= site_url("admin/cbt/banksoal/parseSoal") ?>',
        type: 'POST',
        dataType: 'json',
        data: postData,
        success: function (res) {
          if (res && res.csrf) {
            updateCsrfHash(res.csrf);
          }

          if (!res || !res.success || !res.preview || !res.preview.length) {
            Swal.fire('Format Belum Sesuai', (res && res.error) ? res.error : 'Gagal membaca format soal.', 'warning');
            return;
          }

          const item = res.preview[0];
          const typeLabels = {
            pg: '<span class="badge bg-primary">Pilihan Ganda (PG)</span>',
            pg_kompleks: '<span class="badge bg-info text-dark">PG Kompleks</span>',
            benar_salah: '<span class="badge bg-warning text-dark">Benar / Salah</span>',
            esai: '<span class="badge bg-secondary">Esai / Uraian</span>'
          };
          const badgeHtml = typeLabels[item.type] || `<span class="badge bg-primary">${item.type}</span>`;

          let optionsHtml = '';
          const opts = item.options || {};
          const optKeys = ['A', 'B', 'C', 'D', 'E'].filter(k => opts[k] !== undefined && String(opts[k]).trim() !== '');

          if (item.type === 'esai') {
            const rubric = item.essay_answer || item.answer || '';
            optionsHtml = rubric
              ? `<div class="p-2 border border-success border-opacity-25 rounded bg-success bg-opacity-10 mt-2">
                   <div class="small fw-bold text-success mb-1">Kunci / Pedoman Jawaban Esai:</div>
                   <div>${rubric}</div>
                 </div>`
              : `<div class="alert alert-info py-2 mb-0 mt-2 small">Soal Esai tanpa kunci spesifik (dinilai manual).</div>`;
          } else if (item.type === 'benar_salah') {
            const ansArr = String(item.answer || '').split(',').map(s => s.trim());
            if (optKeys.length === 0) {
              optionsHtml = `<div class="alert alert-warning py-2 mb-0 mt-2 small"><i class="bi bi-exclamation-triangle me-1"></i>Pernyataan A, B, C... belum terdeteksi! Pastikan ditulis dengan awalan <code>A: ...</code> di baris baru.</div>`;
            } else {
              let rows = '';
              optKeys.forEach((k, idx) => {
                rows += `<tr><td>${opts[k]}</td><td class="text-center fw-bold text-primary">${ansArr[idx] || '-'}</td></tr>`;
              });
              optionsHtml = `<div class="table-responsive mt-2"><table class="table table-bordered table-sm mb-0"><thead class="table-light"><tr><th>Pernyataan</th><th width="90" class="text-center">Kunci</th></tr></thead><tbody>${rows}</tbody></table></div>`;
            }
          } else {
            const ansArr = String(item.answer || '').split(',').map(s => s.trim().toUpperCase());
            if (optKeys.length === 0) {
              optionsHtml = `<div class="alert alert-warning py-2 mb-0 mt-2 small"><i class="bi bi-exclamation-triangle me-1"></i>Opsi jawaban A–E belum terdeteksi! Pastikan setiap opsi diawali <code>A: ...</code>, <code>B: ...</code> pada baris tersendiri.</div>`;
            } else {
              optKeys.forEach(k => {
                const isKey = ansArr.includes(k);
                optionsHtml += `<div class="p-2 mb-1 border rounded d-flex align-items-start ${isKey ? 'bg-primary text-white fw-semibold' : 'bg-light'}">
                  <span class="me-2 fw-bold">${k}.</span>
                  <div>${opts[k]}</div>
                </div>`;
              });
              optionsHtml += `<div class="small text-muted mt-2">Kunci Terdeteksi: <strong>${item.answer || '(Belum diatur - kunci lama akan dipertahankan)'}</strong></div>`;
            }
          }

          $('#previewResult').html(`
            <div class="card shadow-sm border-0">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-eye me-1"></i> Hasil Preview Parsing Server</span>
                ${badgeHtml}
              </div>
              <div class="card-body">
                <div class="p-3 mb-3 border rounded bg-white">${item.text || '<em class="text-muted">Teks soal kosong</em>'}</div>
                <h6 class="fw-bold mb-2">Opsi & Kunci Jawaban</h6>
                ${optionsHtml}
              </div>
            </div>
          `);

          if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
            MathJax.typesetPromise([document.getElementById('previewResult')]).catch((err) => console.warn('MathJax error:', err));
          }
        },
        error: function () {
          Swal.fire('Gagal', 'Tidak dapat menghubungi server untuk memproses preview.', 'error');
        },
        complete: function () {
          $btn.prop('disabled', false).html('<i class="bi bi-eye me-1"></i> Preview Parsing');
        }
      });
    });

    // 💾 Simpan Perubahan
    $('#formEditSoal').on('submit', function (e) {
      e.preventDefault();

      if (!editorInstance) {
        Swal.fire('Error', 'Editor belum siap', 'error');
        return;
      }
      const html = editorInstance.getData().trim();
      if (!html) return Swal.fire('Kosong', 'Silakan isi soal terlebih dahulu.', 'warning');

      $('#btnSave').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

      const formData = new FormData(this);
      formData.set('raw_text', html);

      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
          if (typeof res === 'string') {
            try { res = JSON.parse(res); } catch (err) { res = null; }
          }
          if (res && res.csrf) {
            updateCsrfHash(res.csrf);
          }

          if (res && res.success) {
            localStorage.removeItem(draftKey);
            Swal.fire('Berhasil', res.message || 'Soal berhasil diperbarui!', 'success').then(() => {
              window.location.href = "<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>";
            });
          } else {
            Swal.fire(
              'Gagal Menyimpan',
              (res && res.error) ? res.error : 'Format soal belum sesuai. Pastikan opsi jawaban (A: ... s.d. D/E: ...) tertulis lengkap.',
              'warning'
            );
          }
        },
        error: function (xhr) {
          let errMsg = 'Terjadi kesalahan saat menyimpan soal.';
          if (xhr.status === 403) {
            errMsg = 'Sesi keamanan (CSRF) kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.';
          } else if (xhr.responseJSON && xhr.responseJSON.error) {
            errMsg = xhr.responseJSON.error;
          }
          Swal.fire('Gagal', errMsg, 'error');
        },
        complete: function () {
          $('#btnSave').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Perubahan');
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
          type: "POST",
          data: {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
          },
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