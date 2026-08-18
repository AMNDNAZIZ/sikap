<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-3 px-md-4 pb-5">
  <div class="d-flex align-items-center gap-2 mt-3 mb-3">
    <a href="<?= site_url('admin/tugas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
  </div>

  <?php if (!empty(session()->getFlashdata('error'))): ?>
    <div class="alert alert-danger border-0"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>
  <?php $errors = session()->getFlashdata('errors') ?? []; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger border-0"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <?php
    $isEdit   = isset($tugas);
    $action   = $isEdit ? site_url('admin/tugas/update/' . $tugas['id']) : site_url('admin/tugas/store');
    $mulai    = $isEdit ? date('Y-m-d\TH:i', strtotime($tugas['mulai_at'])) : '';
    $selesai  = $isEdit ? date('Y-m-d\TH:i', strtotime($tugas['selesai_at'])) : '';
  ?>

  <form action="<?= $action ?>" method="post">
    <?= csrf_field() ?>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white fw-semibold border-0 pt-3">Informasi Tugas</div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label fw-semibold">Judul Tugas <span class="text-danger">*</span></label>
          <input type="text" name="judul" class="form-control"
                 value="<?= esc($isEdit ? $tugas['judul'] : old('judul')) ?>"
                 placeholder="Contoh: Latihan Soal Bab 3" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
            <select name="class_id" class="form-select" required>
              <option value="">-- Pilih Kelas --</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"
                  <?= ($isEdit && $tugas['class_id'] == $c['id']) ? 'selected' : '' ?>>
                  <?= esc($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" class="form-select" required>
              <option value="">-- Pilih Mapel --</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>"
                  <?= ($isEdit && $tugas['subject_id'] == $s['id']) ? 'selected' : '' ?>>
                  <?= esc($s['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Waktu Mulai <span class="text-danger">*</span></label>
            <input type="datetime-local" name="mulai_at" class="form-control"
                   value="<?= $mulai ?>" required>
            <div class="form-text">Tugas mulai bisa dikerjakan siswa</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Batas Waktu Selesai <span class="text-danger">*</span></label>
            <input type="datetime-local" name="selesai_at" class="form-control"
                   value="<?= $selesai ?>" required>
            <div class="form-text">Siswa tidak bisa mengumpulkan setelah waktu ini</div>
          </div>
        </div>

      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white fw-semibold border-0 pt-3">
        Deskripsi Tugas
        <small class="text-muted fw-normal">— bisa memuat teks dan gambar</small>
      </div>
      <div class="card-body">
        <textarea id="deskripsi" name="deskripsi"><?= $isEdit ? $tugas['deskripsi'] : '' ?></textarea>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Tugas' ?>
      </button>
      <a href="<?= site_url('admin/tugas') ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<!-- CKEditor 5 Superbuild -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
  CKEDITOR.ClassicEditor.create(document.querySelector('#deskripsi'), {
    toolbar: {
      items: [
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'fontSize', 'fontColor', '|',
        'link', 'uploadImage', 'insertTable', 'blockQuote', '|',
        'bulletedList', 'numberedList', 'outdent', 'indent', '|',
        'alignment', '|',
        'removeFormat'
      ],
      shouldNotGroupWhenFull: true
    },
    image: {
      toolbar: ['imageTextAlternative', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side']
    },
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
    },
    removePlugins: [
      'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit',
      'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage',
      'Comments', 'RealTimeCollaborativeComments', 'TrackChanges',
      'RealTimeCollaborativeTrackChanges', 'RevisionHistory',
      'RealTimeCollaborativeRevisionHistory', 'PresenceList',
      'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType',
      'ChemType', 'Mentions', 'SlashCommand', 'Template', 'DocumentOutline',
      'FormatPainter', 'TableOfContents', 'PasteFromOfficeEnhanced',
      'CaseChange', 'ExportPdf', 'ExportWord'
    ],
  })
  .then(editor => {
    editor.editing.view.change(writer => {
      writer.setStyle('min-height', '250px', editor.editing.view.document.getRoot());
    });

    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => ({
      upload: () => loader.file.then(file => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        $.ajax({
          url: '<?= site_url('admin/tugas/upload-image') ?>',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: (r) => r.success ? resolve({ default: r.url }) : reject(r.message),
          error: (xhr) => reject('Upload gagal: ' + xhr.statusText)
        });
      }))
    });
  })
  .catch(err => console.error('CKEditor error:', err));
});
</script>
