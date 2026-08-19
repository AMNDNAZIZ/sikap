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
    $action   = $isEdit
        ? site_url('admin/tugas/update/' . $tugas['id'])
        : site_url('admin/tugas/store');
    $mulai    = $isEdit ? date('Y-m-d\TH:i', strtotime($tugas['mulai_at']))   : old('mulai_at', '');
    $selesai  = $isEdit ? date('Y-m-d\TH:i', strtotime($tugas['selesai_at'])) : old('selesai_at', '');

    // Kelas & mapel terpilih (untuk old() setelah validasi gagal atau mode edit)
    $selectedClassId   = $isEdit ? $tugas['class_id']   : old('class_id', '');
    $selectedSubjectId = $isEdit ? $tugas['subject_id'] : old('subject_id', '');
  ?>

  <form action="<?= $action ?>" method="post" id="formTugas">
    <?= csrf_field() ?>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white fw-semibold border-0 pt-3">Informasi Tugas</div>
      <div class="card-body">

        <!-- Judul -->
        <div class="mb-3">
          <label class="form-label fw-semibold">
            Judul Tugas <span class="text-danger">*</span>
          </label>
          <input type="text" name="judul" class="form-control"
                 value="<?= esc($isEdit ? $tugas['judul'] : old('judul', '')) ?>"
                 placeholder="Contoh: Latihan Soal Bab 3" required>
        </div>

        <!-- Kelas & Mapel -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Kelas <span class="text-danger">*</span>
            </label>
            <select name="class_id" id="selectKelas" class="form-select" required>
              <option value="">-- Pilih Kelas --</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"
                  <?= ($selectedClassId == $c['id']) ? 'selected' : '' ?>>
                  <?= esc($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Mata Pelajaran <span class="text-danger">*</span>
            </label>
            <select name="subject_id" id="selectMapel" class="form-select" required>
              <option value="">-- Pilih Kelas dulu --</option>
              <?php
                // Jika ada kelas terpilih (edit / old), render opsi mapel langsung
                if ($selectedClassId && !empty($subjects)):
                  foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>"
                      <?= ($selectedSubjectId == $s['id']) ? 'selected' : '' ?>>
                      <?= esc($s['name']) ?>
                    </option>
                  <?php endforeach;
                endif; ?>
            </select>
            <div id="mapelLoading" class="form-text text-primary" style="display:none;">
              <span class="spinner-border spinner-border-sm me-1" role="status"></span>Memuat mapel...
            </div>
          </div>
        </div>

        <!-- Waktu -->
        <div class="row g-3 mb-1">
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Waktu Mulai <span class="text-danger">*</span>
            </label>
            <input type="datetime-local" name="mulai_at" id="mulaiAt"
                   class="form-control" value="<?= $mulai ?>" required>
            <div class="form-text">Tugas mulai bisa dikerjakan siswa</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Batas Waktu Selesai <span class="text-danger">*</span>
            </label>
            <input type="datetime-local" name="selesai_at" id="selesaiAt"
                   class="form-control" value="<?= $selesai ?>" required>
            <div class="form-text">Siswa tidak bisa mengumpulkan setelah waktu ini</div>
          </div>
        </div>

      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white fw-semibold border-0 pt-3">
        Deskripsi Tugas
        <small class="text-muted fw-normal">— opsional, bisa memuat teks dan gambar</small>
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

<!-- CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ─── Dynamic Mapel berdasarkan Kelas ────────────────────────────────────────
(function () {
  const selectKelas  = document.getElementById('selectKelas');
  const selectMapel  = document.getElementById('selectMapel');
  const mapelLoading = document.getElementById('mapelLoading');

  // Simpan selected value dari server (edit / old)
  const preselectedSubject = '<?= $selectedSubjectId ?>';
  const preselectedClass   = '<?= $selectedClassId ?>';

  // assignmentMap dari server (hanya diisi untuk guru, null untuk admin)
  // Format: { "classId": [ {id, name}, ... ], ... }
  const assignmentMap = <?= isset($assignmentMap) && $assignmentMap !== null
    ? json_encode($assignmentMap)
    : 'null' ?>;

  // ── Admin: gunakan AJAX seperti sebelumnya ──────────────────────────────
  const baseUrl    = '<?= site_url('admin/tugas/subjects-by-class') ?>';
  const csrfHeader = '<?= csrf_token() ?>';
  const csrfValue  = '<?= csrf_hash() ?>';

  function renderOptions(subjects, preselectId) {
    selectMapel.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
    if (subjects && subjects.length) {
      subjects.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        if (String(preselectId) === String(s.id)) opt.selected = true;
        selectMapel.appendChild(opt);
      });
      selectMapel.disabled = false;
    } else {
      selectMapel.innerHTML = '<option value="">Tidak ada mapel tersedia</option>';
      selectMapel.disabled = true;
    }
  }

  function loadMapelFromMap(classId, preselectId) {
    if (!classId) {
      selectMapel.innerHTML = '<option value="">-- Pilih Kelas dulu --</option>';
      selectMapel.disabled  = true;
      return;
    }
    const subjects = assignmentMap[classId] || [];
    renderOptions(subjects, preselectId);
  }

  function loadMapelFromAjax(classId, preselectId) {
    if (!classId) {
      selectMapel.innerHTML = '<option value="">-- Pilih Kelas dulu --</option>';
      selectMapel.disabled  = true;
      return;
    }
    mapelLoading.style.display = '';
    selectMapel.disabled = true;

    fetch(baseUrl + '?class_id=' + classId, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        [csrfHeader]: csrfValue
      }
    })
    .then(r => r.json())
    .then(data => renderOptions(data.success ? data.subjects : [], preselectId))
    .catch(() => {
      selectMapel.innerHTML = '<option value="">Gagal memuat mapel</option>';
    })
    .finally(() => {
      mapelLoading.style.display = 'none';
    });
  }

  const loadMapel = assignmentMap !== null ? loadMapelFromMap : loadMapelFromAjax;

  // Load saat kelas berubah
  selectKelas.addEventListener('change', function () {
    loadMapel(this.value, '');
  });

  // Pre-populate saat halaman pertama load (mode edit / validasi gagal)
  if (preselectedClass) {
    loadMapel(preselectedClass, preselectedSubject);
  } else {
    selectMapel.disabled = true;
  }
})();

// ─── Validasi waktu selesai > waktu mulai ───────────────────────────────────
document.getElementById('formTugas').addEventListener('submit', function (e) {
  const mulai   = document.getElementById('mulaiAt').value;
  const selesai = document.getElementById('selesaiAt').value;
  if (mulai && selesai && selesai <= mulai) {
    e.preventDefault();
    alert('Waktu selesai harus setelah waktu mulai.');
    document.getElementById('selesaiAt').focus();
  }
});

// ─── CKEditor 5 ─────────────────────────────────────────────────────────────
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
    'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit', 'CKBoxImageEditEditing',
    'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage', 'Comments', 'CommentsRepository',
    'RealTimeCollaborativeComments', 'TrackChanges', 'TrackChangesEditing', 'TrackChangesData',
    'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'RealTimeCollaborativeRevisionHistory',
    'PresenceList', 'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType', 'ChemType',
    'Mentions', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents',
    'PasteFromOfficeEnhanced', 'CaseChange', 'WideSidebar', 'ExportPdf', 'ExportWord'
  ],
})
.then(editor => {
  editor.editing.view.change(writer => {
    writer.setStyle('min-height', '250px', editor.editing.view.document.getRoot());
  });

  // Custom upload adapter
  editor.plugins.get('FileRepository').createUploadAdapter = (loader) => ({
    upload: () => loader.file.then(file => new Promise((resolve, reject) => {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

      const xhr = new XMLHttpRequest();
      xhr.open('POST', '<?= site_url('admin/tugas/upload-image') ?>', true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.onload = function () {
        if (xhr.status === 200) {
          const r = JSON.parse(xhr.responseText);
          r.success ? resolve({ default: r.url }) : reject(r.message);
        } else {
          reject('Upload gagal: ' + xhr.statusText);
        }
      };
      xhr.onerror = () => reject('Upload gagal: network error');
      xhr.send(formData);
    }))
  });
})
.catch(err => console.error('CKEditor error:', err));
</script>
<?= $this->endSection() ?>
