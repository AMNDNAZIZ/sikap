<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .drop-zone {
    border: 2px dashed #adb5bd;
    border-radius: 0.5rem;
    cursor: pointer;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: #f8f9fa;
  }
  .drop-zone:hover, .drop-zone.drag-over {
    border-color: #0d6efd;
    background: #e7f1ff;
  }
  .drop-zone.has-file {
    border-color: #198754;
    background: #f0fdf4;
  }
  .autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1050;
    display: none;
    width: 100%;
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 250px;
    overflow-y: auto;
  }
  .autocomplete-suggestions .list-group-item-action {
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .autocomplete-suggestions .list-group-item-action:hover {
    background-color: #f8f9fa;
    color: #0b5ed7;
  }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-upload me-2 text-primary"></i>Upload Surat Eksternal</h2>
    <small class="text-muted">Upload PDF surat yang dibuat di luar sistem (Word, dll) untuk diregistrasi dan diberi QR Code verifikasi</small>
  </div>
  <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if (session()->getFlashdata('errors') || isset($errors)): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <strong>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ((session()->getFlashdata('errors') ?? $errors ?? []) as $err): ?>
        <li><?= is_array($err) ? implode(', ', $err) : esc($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('admin/surat-keluar/store-eksternal') ?>" enctype="multipart/form-data" id="form-eksternal">
  <?= csrf_field() ?>
  <input type="hidden" name="recipient_ref_id" id="recipient_ref_id" value="<?= old('recipient_ref_id') ?>">

  <div class="row g-4">
    <!-- Kolom Kiri: Form -->
    <div class="col-lg-8">

      <!-- Card 1: Upload File -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-semibold py-2">
          <i class="bi bi-file-earmark-pdf me-2"></i>Upload File PDF
        </div>
        <div class="card-body">
          <div id="drop-zone" class="drop-zone mb-3">
            <i class="bi bi-file-earmark-arrow-up fs-1 text-muted mb-2" id="drop-zone-icon"></i>
            <p class="mb-1 fw-semibold" id="drop-zone-text">Drag & drop file PDF di sini</p>
            <p class="text-muted small mb-2">atau klik untuk memilih file (maks. 5 MB, hanya .pdf)</p>
            <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" class="d-none">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-browse">
              <i class="bi bi-folder2-open me-1"></i>Pilih File
            </button>
          </div>

          <div id="file-info" style="display:none;">
            <div class="alert alert-success d-flex align-items-center gap-2 py-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              <div>
                <strong id="file-name-display"></strong>
                <br><small class="text-muted" id="file-size-display"></small>
              </div>
              <button type="button" class="btn-close ms-auto" id="btn-remove-file"></button>
            </div>
          </div>

          <div class="alert alert-info py-2 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            File PDF akan digabungkan dengan QR Code verifikasi otomatis dari SIKAP di pojok kiri bawah halaman pertama.
          </div>
        </div>
      </div>

      <!-- Card 2: Informasi Umum -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-card-heading me-2"></i>Informasi Surat
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tanggal Surat <span class="text-danger">*</span></label>
              <input type="date" name="issued_at" class="form-control" id="issued_at"
                     value="<?= old('issued_at', date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Sifat Surat <span class="text-danger">*</span></label>
              <select name="sifat" class="form-select" required>
                <option value="Biasa" <?= old('sifat') === 'Biasa' ? 'selected' : '' ?>>Biasa</option>
                <option value="Penting" <?= old('sifat') === 'Penting' ? 'selected' : '' ?>>Penting</option>
                <option value="Segera" <?= old('sifat') === 'Segera' ? 'selected' : '' ?>>Segera</option>
                <option value="Rahasia" <?= old('sifat') === 'Rahasia' ? 'selected' : '' ?>>Rahasia</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Perihal / Keperluan <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" id="subject"
                     value="<?= old('subject') ?>" placeholder="Contoh: Keterangan Domisili Siswa"
                     minlength="5" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Penerima -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-person-lines-fill me-2"></i>Penerima Surat
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Tipe Penerima <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 flex-wrap">
              <?php foreach (['siswa' => 'Siswa', 'guru' => 'Guru', 'eksternal' => 'Eksternal', 'internal' => 'Internal'] as $val => $label): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="recipient_type"
                         id="rtype-<?= $val ?>" value="<?= $val ?>"
                         <?= old('recipient_type', 'eksternal') === $val ? 'checked' : '' ?>
                         onchange="toggleRecipientSection()">
                  <label class="form-check-label" for="rtype-<?= $val ?>"><?= $label ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Siswa -->
          <div id="recipient-siswa-section" style="display:none;">
            <label class="form-label fw-semibold">Cari Siswa</label>
            <div class="position-relative">
              <input type="text" class="form-control" id="search-siswa-input"
                     placeholder="Ketik nama atau NISN..." autocomplete="off">
              <div class="autocomplete-suggestions" id="siswa-suggestions"></div>
            </div>
            <input type="hidden" name="recipient_name" id="recipient_name_siswa" value="<?= old('recipient_name') ?>">
            <input type="hidden" name="nisn" id="f_nisn" value="<?= old('nisn') ?>">
            <input type="hidden" name="nik" id="f_nik" value="<?= old('nik') ?>">
            <input type="hidden" name="ttl" id="f_ttl" value="<?= old('ttl') ?>">
            <input type="hidden" name="kelas" id="f_kelas" value="<?= old('kelas') ?>">
            <div id="siswa-selected-info" class="mt-2 small text-success" style="display:none;"></div>
          </div>

          <!-- Guru -->
          <div id="recipient-guru-section" style="display:none;">
            <label class="form-label fw-semibold">Cari Guru</label>
            <div class="position-relative">
              <input type="text" class="form-control" id="search-guru-input"
                     placeholder="Ketik nama atau NIP..." autocomplete="off">
              <div class="autocomplete-suggestions" id="guru-suggestions"></div>
            </div>
            <input type="hidden" name="recipient_name" id="recipient_name_guru" value="<?= old('recipient_name') ?>">
            <input type="hidden" name="nip" id="f_nip" value="<?= old('nip') ?>">
            <input type="hidden" name="jabatan" id="f_jabatan" value="<?= old('jabatan') ?>">
            <div id="guru-selected-info" class="mt-2 small text-success" style="display:none;"></div>
          </div>

          <!-- Eksternal -->
          <div id="recipient-eksternal-section">
            <label class="form-label fw-semibold">Nama Penerima <span class="text-danger">*</span></label>
            <input type="text" name="recipient_name" id="recipient_name_eksternal" class="form-control"
                   value="<?= old('recipient_name') ?>" placeholder="Nama lengkap penerima"
                   minlength="3" required>
          </div>

          <!-- Internal -->
          <div id="recipient-internal-section" style="display:none;">
            <input type="hidden" name="recipient_name" id="recipient_name_internal"
                   value="<?= old('recipient_name', 'Guru & Tenaga Kependidikan ' . ($school['name'] ?? '')) ?>">
            <div class="alert alert-light border py-2 small mb-0">
              <i class="bi bi-people me-1"></i>
              Surat ditujukan kepada <strong>Guru & Tenaga Kependidikan <?= esc($school['name'] ?? '') ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4: Nomor Surat -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-hash me-2"></i>Nomor Surat
        </div>
        <div class="card-body">
          <div class="alert alert-light border py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Nomor surat akan digenerate otomatis: <strong class="font-monospace"><?= esc($preview_number) ?></strong>
            <br><small class="text-muted">Kosongkan field di bawah untuk menggunakan nomor otomatis, atau isi manual jika nomor sudah tertera di dokumen Word.</small>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="chk-nomor-manual" onchange="toggleNomorManual()">
            <label class="form-check-label fw-semibold" for="chk-nomor-manual">Gunakan nomor surat manual</label>
          </div>
          <input type="text" name="nomor_surat_manual" id="nomor_surat_manual" class="form-control"
                 value="<?= old('nomor_surat_manual') ?>" placeholder="Contoh: 421/001/VI/2026"
                 disabled>
        </div>
      </div>

      <!-- Card 5: Catatan -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-journal-text me-2"></i>Catatan (Opsional)
        </div>
        <div class="card-body">
          <textarea name="catatan_eksternal" class="form-control" rows="2"
                    placeholder="Catatan tambahan tentang surat ini..."><?= old('catatan_eksternal') ?></textarea>
        </div>
      </div>

    </div>

    <!-- Kolom Kanan: Sidebar -->
    <div class="col-lg-4">
      <div class="sticky-top" style="top: 80px;">
        <!-- Info -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-light fw-semibold py-2">
            <i class="bi bi-info-circle me-2"></i>Informasi
          </div>
          <div class="card-body small">
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <th class="text-muted" width="120">Kepala Sekolah</th>
                <td><?= esc($principal_name) ?></td>
              </tr>
              <tr>
                <th class="text-muted">NIP</th>
                <td class="font-monospace"><?= esc($principal_nip) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Tahun Ajaran</th>
                <td><?= esc($active_year) ?></td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary w-100 mb-3" id="btn-submit" disabled>
          <i class="bi bi-upload me-1"></i>Upload & Registrasi Surat
        </button>
        <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary w-100">
          <i class="bi bi-x-circle me-1"></i>Batal
        </a>
      </div>
    </div>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dropZone    = document.getElementById('drop-zone');
  const fileInput   = document.getElementById('pdf_file');
  const btnBrowse   = document.getElementById('btn-browse');
  const fileInfo    = document.getElementById('file-info');
  const fileNameEl  = document.getElementById('file-name-display');
  const fileSizeEl  = document.getElementById('file-size-display');
  const btnRemove   = document.getElementById('btn-remove-file');
  const btnSubmit   = document.getElementById('btn-submit');
  const form        = document.getElementById('form-eksternal');
  const chkManual   = document.getElementById('chk-nomor-manual');
  const nomorInput  = document.getElementById('nomor_surat_manual');
  let fileSelected  = false;

  // Drop zone
  btnBrowse.addEventListener('click', () => fileInput.click());
  dropZone.addEventListener('click', (e) => {
    if (e.target !== btnBrowse && !btnBrowse.contains(e.target)) fileInput.click();
  });

  ['dragover', 'dragenter'].forEach(evt => {
    dropZone.addEventListener(evt, (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  });
  ['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, (e) => { e.preventDefault(); dropZone.classList.remove('drag-over'); });
  });

  dropZone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      fileInput.files = files;
      handleFileSelect(files[0]);
    }
  });

  fileInput.addEventListener('change', function () {
    if (this.files.length > 0) handleFileSelect(this.files[0]);
  });

  function handleFileSelect(file) {
    if (file.type !== 'application/pdf') {
      alert('Hanya file PDF yang diizinkan.');
      fileInput.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file maksimum 5 MB.');
      fileInput.value = '';
      return;
    }

    fileSelected = true;
    fileNameEl.textContent = file.name;
    fileSizeEl.textContent = formatSize(file.size);
    fileInfo.style.display = 'block';
    dropZone.style.display = 'none';
    document.getElementById('drop-zone-icon').style.display = 'none';
    document.getElementById('drop-zone-text').textContent = 'File dipilih';
    dropZone.classList.add('has-file');
    checkSubmit();
  }

  btnRemove.addEventListener('click', function (e) {
    e.stopPropagation();
    fileInput.value = '';
    fileSelected = false;
    fileInfo.style.display = 'none';
    dropZone.style.display = 'flex';
    dropZone.classList.remove('has-file');
    checkSubmit();
  });

  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  // Nomor manual toggle
  window.toggleNomorManual = function () {
    nomorInput.disabled = !chkManual.checked;
    if (!chkManual.checked) nomorInput.value = '';
  };

  // Recipient section toggle
  window.toggleRecipientSection = function () {
    const type = document.querySelector('input[name="recipient_type"]:checked').value;
    ['siswa', 'guru', 'eksternal', 'internal'].forEach(t => {
      document.getElementById('recipient-' + t + '-section').style.display = (t === type) ? 'block' : 'none';
    });
    checkSubmit();
  };

  // Enable/disable required based on active section
  function updateRequiredFields() {
    const type = document.querySelector('input[name="recipient_type"]:checked')?.value || 'eksternal';
    const extInput = document.getElementById('recipient_name_eksternal');
    extInput.required = (type === 'eksternal');
  }

  // Submit validation
  function checkSubmit() {
    btnSubmit.disabled = !fileSelected;
  }

  form.addEventListener('submit', function (e) {
    if (!fileSelected) {
      e.preventDefault();
      alert('Silakan pilih file PDF terlebih dahulu.');
      return false;
    }
    const type = document.querySelector('input[name="recipient_type"]:checked')?.value;
    // Sync hidden recipient_name fields
    if (type === 'siswa') {
      const val = document.getElementById('recipient_name_siswa').value;
      if (!val) { e.preventDefault(); alert('Pilih siswa penerima.'); return false; }
    } else if (type === 'guru') {
      const val = document.getElementById('recipient_name_guru').value;
      if (!val) { e.preventDefault(); alert('Pilih guru penerima.'); return false; }
    } else if (type === 'eksternal') {
      const val = document.getElementById('recipient_name_eksternal').value;
      if (!val || val.length < 3) { e.preventDefault(); alert('Nama penerima minimal 3 karakter.'); return false; }
    }
  });

  // Autocomplete siswa
  let siswaTimer;
  const siswaInput = document.getElementById('search-siswa-input');
  const siswaSuggestions = document.getElementById('siswa-suggestions');

  siswaInput.addEventListener('input', function () {
    clearTimeout(siswaTimer);
    const q = this.value.trim();
    if (q.length < 2) { siswaSuggestions.style.display = 'none'; return; }

    siswaTimer = setTimeout(() => {
      fetch('<?= base_url("admin/surat-keluar/search-siswa") ?>?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
          if (!data.length) { siswaSuggestions.style.display = 'none'; return; }
          siswaSuggestions.innerHTML = data.map(s =>
            `<div class="list-group-item list-group-item-action py-2 small"
                  data-id="${s.id}" data-name="${s.name}" data-nisn="${s.nisn}"
                  data-nik="${s.nik || ''}" data-ttl="${s.ttl || ''}" data-kelas="${s.kelas || ''}">
              <strong>${s.name}</strong><br>
              <span class="text-muted">NISN: ${s.nisn} | Kelas: ${s.kelas || '-'}</span>
            </div>`
          ).join('');
          siswaSuggestions.style.display = 'block';
        });
    }, 300);
  });

  siswaSuggestions.addEventListener('click', function (e) {
    const item = e.target.closest('.list-group-item-action');
    if (!item) return;
    document.getElementById('recipient_name_siswa').value = item.dataset.name;
    document.getElementById('recipient_ref_id').value = item.dataset.id;
    document.getElementById('f_nisn').value = item.dataset.nisn;
    document.getElementById('f_nik').value = item.dataset.nik;
    document.getElementById('f_ttl').value = item.dataset.ttl;
    document.getElementById('f_kelas').value = item.dataset.kelas;
    siswaInput.value = '';
    siswaSuggestions.style.display = 'none';
    const info = document.getElementById('siswa-selected-info');
    info.innerHTML = '<i class="bi bi-check-circle me-1"></i>Dipilih: <strong>' + item.dataset.name + '</strong> (NISN: ' + item.dataset.nisn + ', Kelas: ' + (item.dataset.kelas || '-') + ')';
    info.style.display = 'block';
  });

  // Autocomplete guru
  let guruTimer;
  const guruInput = document.getElementById('search-guru-input');
  const guruSuggestions = document.getElementById('guru-suggestions');

  guruInput.addEventListener('input', function () {
    clearTimeout(guruTimer);
    const q = this.value.trim();
    if (q.length < 2) { guruSuggestions.style.display = 'none'; return; }

    guruTimer = setTimeout(() => {
      fetch('<?= base_url("admin/surat-keluar/search-guru") ?>?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
          if (!data.length) { guruSuggestions.style.display = 'none'; return; }
          guruSuggestions.innerHTML = data.map(t =>
            `<div class="list-group-item list-group-item-action py-2 small"
                  data-id="${t.id}" data-name="${t.name}" data-nip="${t.nip || ''}" data-jabatan="${t.jabatan || ''}">
              <strong>${t.name}</strong><br>
              <span class="text-muted">NIP: ${t.nip || '-'} | ${t.jabatan || 'Guru'}</span>
            </div>`
          ).join('');
          guruSuggestions.style.display = 'block';
        });
    }, 300);
  });

  guruSuggestions.addEventListener('click', function (e) {
    const item = e.target.closest('.list-group-item-action');
    if (!item) return;
    document.getElementById('recipient_name_guru').value = item.dataset.name;
    document.getElementById('recipient_ref_id').value = item.dataset.id;
    document.getElementById('f_nip').value = item.dataset.nip;
    document.getElementById('f_jabatan').value = item.dataset.jabatan;
    guruInput.value = '';
    guruSuggestions.style.display = 'none';
    const info = document.getElementById('guru-selected-info');
    info.innerHTML = '<i class="bi bi-check-circle me-1"></i>Dipilih: <strong>' + item.dataset.name + '</strong> (NIP: ' + (item.dataset.nip || '-') + ')';
    info.style.display = 'block';
  });

  // Close suggestions on outside click
  document.addEventListener('click', function (e) {
    if (!siswaInput.contains(e.target) && !siswaSuggestions.contains(e.target)) siswaSuggestions.style.display = 'none';
    if (!guruInput.contains(e.target) && !guruSuggestions.contains(e.target)) guruSuggestions.style.display = 'none';
  });

  // Initial state
  toggleRecipientSection();
  checkSubmit();
});
</script>
<?= $this->endSection() ?>
