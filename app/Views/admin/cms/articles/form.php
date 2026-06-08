<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $title ?>
    </h1>

    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">
            <form
                action="<?= isset($article) ? base_url('admin/cms/articles/update/' . $article['id']) : base_url('admin/cms/articles/store') ?>"
                method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Judul Artikel</label>
                            <input type="text" name="title" class="form-control form-control-lg"
                                value="<?= $article['title'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten</label>
                            <textarea id="editor" name="content" class="form-control"><?= $article['content'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Gambar Unggulan</label>
                                    <?php if (isset($article['image']) && $article['image']): ?>
                                        <div class="mb-2">
                                            <img src="<?= base_url('uploads/articles/' . $article['image']) ?>"
                                                class="img-thumbnail w-100">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="image" class="form-control">
                                    <small class="text-muted">Rekomendasi: 800x450 px</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="category" class="form-select">
                                        <option value="Berita" <?= (isset($article) && $article['category'] == 'Berita') ? 'selected' : '' ?>>Berita</option>
                                        <option value="Artikel" <?= (isset($article) && $article['category'] == 'Artikel') ? 'selected' : '' ?>>Artikel</option>
                                        <option value="Edukasi" <?= (isset($article) && $article['category'] == 'Edukasi') ? 'selected' : '' ?>>Edukasi</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="is_published" class="form-select">
                                        <option value="1" <?= (isset($article) && $article['is_published'] == 1) ? 'selected' : '' ?>>Publikasikan</option>
                                        <option value="0" <?= (isset($article) && $article['is_published'] == 0) ? 'selected' : '' ?>>Simpan Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success w-100 mb-2">💾 Simpan Artikel</button>
                            <a href="<?= base_url('admin/cms/articles') ?>" class="btn btn-secondary w-100">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<!-- Load CKEditor 5 Superbuild (Full Featured) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>

<!-- Load MathJax untuk Formula Matematika (optional, untuk artikel ilmiah) -->
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
        return Promise.resolve();
      });
    }
  }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>

<script>
  $(function () {
    let editorInstance = null;

    // Inisialisasi CKEditor 5 Superbuild
    CKEDITOR.ClassicEditor
      .create(document.querySelector('#editor'), {
        toolbar: {
          items: [
            'undo', 'redo', '|',
            'heading', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
            'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
            'link', 'uploadImage', 'insertTable', 'blockQuote', 'specialCharacters', 'horizontalLine', '|',
            'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
            'alignment', '|',
            'code', 'codeBlock', '|',
            'removeFormat', 'sourceEditing'
          ],
          shouldNotGroupWhenFull: true
        },
        image: {
          toolbar: [
            'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline',
            'imageStyle:block', 'imageStyle:side', 'linkImage'
          ],
          resizeOptions: [
            {
              name: 'resizeImage:original',
              label: 'Original',
              value: null
            },
            {
              name: 'resizeImage:50',
              label: '50%',
              value: '50'
            },
            {
              name: 'resizeImage:75',
              label: '75%',
              value: '75'
            }
          ]
        },
        table: {
          contentToolbar: [
            'tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'
          ]
        },
        heading: {
          options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
          ]
        },
        removePlugins: [
          // Remove premium features that require license
          'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit', 'CKBoxImageEditEditing', 
          'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage', 'Comments', 'CommentsRepository', 
          'RealTimeCollaborativeComments', 'TrackChanges', 'TrackChangesEditing', 'TrackChangesData', 
          'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'RealTimeCollaborativeRevisionHistory', 
          'PresenceList', 'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType', 'ChemType', 
          'Mentions', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents', 
          'PasteFromOfficeEnhanced', 'CaseChange', 'WideSidebar', 'ExportPdf', 'ExportWord'
        ],
        fontSize: {
          options: [ 9, 11, 13, 'default', 17, 19, 21, 24, 28, 32 ],
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
        },
        link: {
          decorators: {
            openInNewTab: {
              mode: 'manual',
              label: 'Open in a new tab',
              attributes: {
                target: '_blank',
                rel: 'noopener noreferrer'
              }
            }
          }
        }
      })
      .then(editor => {
        editorInstance = editor;

        // Set custom CSS untuk tinggi editor
        editor.editing.view.change(writer => {
          writer.setStyle('min-height', '500px', editor.editing.view.document.getRoot());
        });

        // Custom upload adapter untuk gambar
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
          return {
            upload: () => {
              return loader.file.then(file => {
                return new Promise((resolve, reject) => {
                  const formData = new FormData();
                  formData.append('file', file);
                  
                  // Add CSRF token
                  const csrfName = '<?= csrf_token() ?>';
                  const csrfHash = '<?= csrf_hash() ?>';
                  formData.append(csrfName, csrfHash);

                  $.ajax({
                    url: '<?= base_url('admin/cms/articles/upload-image') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                      if (response.success) {
                        resolve({
                          default: response.url
                        });
                      } else {
                        reject(response.message || 'Upload gagal');
                      }
                    },
                    error: function(xhr) {
                      reject('Upload gagal: ' + xhr.statusText);
                    }
                  });
                });
              });
            }
          };
        };

        console.log('CKEditor 5 initialized successfully');
      })
      .catch(error => {
        console.error('Error loading CKEditor:', error);
        alert('Gagal memuat editor. Silakan refresh halaman.');
      });
  });
</script>

<?= $this->endSection() ?>