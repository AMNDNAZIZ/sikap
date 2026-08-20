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

    // Edit: single class. Create: bisa multi-class (old() returns array)
    $selectedClassId   = $isEdit ? $tugas['class_id']   : old('class_id', '');
    $selectedClassIds  = $isEdit ? [$tugas['class_id']] : (old('class_ids') ?: []);
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

          <!-- ── Pemilihan Kelas ───────────────────────────────────────── -->
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="selectKelas">
              Kelas <span class="text-danger">*</span>
            </label>

            <?php if ($isEdit): ?>
              <!-- Mode edit: single select -->
              <select name="class_id" id="selectKelas" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id'] ?>"
                    <?= ($selectedClassId == $c['id']) ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <!-- Mode create: multi select (Select2) -->
              <select name="class_ids[]" id="selectKelas" multiple>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id'] ?>"
                    <?= in_array($c['id'], $selectedClassIds) ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted">Pilih satu atau lebih kelas untuk tugas ini.</div>
            <?php endif; ?>
          </div>

          <!-- ── Pemilihan Mata Pelajaran ──────────────────────────────── -->
          <div class="col-md-6">
            <label class="form-label fw-semibold">
              Mata Pelajaran <span class="text-danger">*</span>
            </label>
            <select name="subject_id" id="selectMapel" class="form-select" required>
              <option value="">-- Pilih Kelas dulu --</option>
              <?php
                // Edit / validasi gagal: render mapel langsung dari server
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
            <div id="mapelInfo" class="form-text text-muted" style="display:none;"></div>
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
// ─── Dynamic Mapel berdasarkan Kelas (Select2 multi) ───────────────────────
$(function () {
  const selectMapel  = document.getElementById('selectMapel');
  const mapelLoading = document.getElementById('mapelLoading');
  const mapelInfo    = document.getElementById('mapelInfo');
  const isEdit       = <?= $isEdit ? 'true' : 'false' ?>;

  // ── Data dari server ──────────────────────────────────────────────────────
  const preselectedSubject  = '<?= $selectedSubjectId ?>';
  const preselectedClassIds = <?= json_encode(array_map('intval', $selectedClassIds)) ?>;

  // assignmentMap: { classId: [{id,name}, ...], ... } — hanya untuk guru
  const assignmentMap = <?= isset($assignmentMap) && $assignmentMap !== null
    ? json_encode($assignmentMap)
    : 'null' ?>;

  const baseUrl    = '<?= site_url('admin/tugas/subjects-by-class') ?>';
  const csrfHeader = '<?= csrf_token() ?>';
  const csrfValue  = '<?= csrf_hash() ?>';

  // ── Init Select2 pada #selectKelas ───────────────────────────────────────
  const $kelasSelect = $('#selectKelas');
  $kelasSelect.select2({
    placeholder   : isEdit ? '-- Pilih Kelas --' : '-- Pilih satu atau lebih kelas --',
    allowClear    : true,
    width         : '100%',
    closeOnSelect : !!isEdit,   // false untuk multi, true untuk single
    language      : { noResults: function () { return 'Tidak ada kelas tersedia'; } }
  });

  // ── Helper: intersection ──────────────────────────────────────────────────
  function intersect(arrays) {
    if (!arrays.length) return [];
    return arrays[0].filter(s =>
      arrays.every(arr => arr.some(x => String(x.id) === String(s.id)))
    );
  }

  // ── Render options dropdown mapel ─────────────────────────────────────────
  function renderOptions(subjects, preselectId, classCount) {
    selectMapel.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
    if (subjects && subjects.length) {
      subjects.forEach(s => {
        const opt       = document.createElement('option');
        opt.value       = s.id;
        opt.textContent = s.name;
        if (String(preselectId) === String(s.id)) opt.selected = true;
        selectMapel.appendChild(opt);
      });
      selectMapel.disabled = false;
      if (mapelInfo && classCount > 1) {
        mapelInfo.textContent = `Menampilkan ${subjects.length} mapel yang tersedia di semua ${classCount} kelas terpilih.`;
        mapelInfo.style.display = '';
      } else if (mapelInfo) { mapelInfo.style.display = 'none'; }
    } else {
      selectMapel.innerHTML = '<option value="">Tidak ada mapel yang sama di semua kelas</option>';
      selectMapel.disabled = true;
      if (mapelInfo) {
        mapelInfo.textContent = 'Pilih kelas yang memiliki mata pelajaran yang sama.';
        mapelInfo.style.display = '';
      }
    }
  }

  // ── Refresh mapel berdasarkan kelas terpilih ──────────────────────────────
  function refreshMapel(ids, preselectId) {
    if (!ids || !ids.length) {
      selectMapel.innerHTML = '<option value="">-- Pilih Kelas dulu --</option>';
      selectMapel.disabled  = true;
      if (mapelInfo) mapelInfo.style.display = 'none';
      return;
    }

    if (assignmentMap !== null) {
      // Guru: hitung intersection dari data lokal
      const arrays = ids.map(id => assignmentMap[id] || []);
      renderOptions(intersect(arrays), preselectId, ids.length);
    } else {
      // Admin: fetch per kelas lalu hitung intersection
      mapelLoading.style.display = '';
      selectMapel.disabled = true;
      Promise.all(
        ids.map(id =>
          fetch(`${baseUrl}?class_id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', [csrfHeader]: csrfValue }
          }).then(r => r.json()).then(d => d.success ? d.subjects : [])
        )
      )
      .then(arrays => renderOptions(intersect(arrays), preselectId, ids.length))
      .catch(() => { selectMapel.innerHTML = '<option value="">Gagal memuat mapel</option>'; })
      .finally(() => { mapelLoading.style.display = 'none'; });
    }
  }

  // ── Dengarkan perubahan Select2 ───────────────────────────────────────────
  $kelasSelect.on('change', function () {
    const val = $(this).val();
    const ids = Array.isArray(val) ? val.filter(Boolean) : (val ? [val] : []);
    refreshMapel(ids, '');
  });

  // ── Pre-populate saat halaman load (edit / validasi gagal) ───────────────
  if (preselectedClassIds.length) {
    refreshMapel(preselectedClassIds.map(String), preselectedSubject);
  } else {
    selectMapel.disabled = true;
  }
});


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
