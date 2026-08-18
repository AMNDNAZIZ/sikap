<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">Manajemen Guru</h1>

  <?php if (session()->get('user')['role_id'] != 2): ?>
    <div class="mb-3 d-flex flex-wrap gap-2">
      <a href="<?= base_url('admin/teachers/create') ?>" class="btn btn-primary">➕ Tambah Guru</a>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
        📥 Import Guru
      </button>
    </div>
  <?php endif; ?>

  <?php if (session()->get('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= session()->get('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->get('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= session()->get('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filter Tab Aktif / Nonaktif / Semua -->
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= ($statusFilter === 'active') ? 'active' : '' ?>"
         href="<?= base_url('admin/teachers?status=active') ?>">
        <span class="text-success">●</span> Aktif
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($statusFilter === 'inactive') ? 'active' : '' ?>"
         href="<?= base_url('admin/teachers?status=inactive') ?>">
        <span class="text-danger">●</span> Tidak Aktif
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($statusFilter === 'all') ? 'active' : '' ?>"
         href="<?= base_url('admin/teachers?status=all') ?>">
        Semua
      </a>
    </li>
  </ul>

  <!-- Tabel Guru -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="40">#</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>Telepon</th>
            <th>Email</th>
            <th width="120" class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teachers)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Tidak ada data guru.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($teachers as $i => $t): ?>
              <tr class="<?= !$t['is_active'] ? 'table-secondary text-muted' : '' ?>">
                <td><?= $i + 1 ?></td>
                <td><?= esc($t['nip'] ?? '-') ?></td>
                <td>
                  <?= esc($t['name']) ?>
                  <?php if (!$t['is_active']): ?>
                    <span class="badge bg-secondary ms-1">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td><?= $t['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                <td><?= esc($t['phone'] ?? '-') ?></td>
                <td><?= esc($t['email'] ?? '-') ?></td>
                <td class="text-center">
                  <?php if ($t['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if (session()->get('user')['role_id'] != 2): ?>
                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                      <a href="<?= base_url('admin/teachers/edit/' . $t['id']) ?>"
                         class="btn btn-sm btn-warning">✏️ Edit</a>

                      <!-- Tombol Toggle Aktif/Nonaktif -->
                      <button type="button"
                              class="btn btn-sm <?= $t['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-toggle-active"
                              data-id="<?= $t['id'] ?>"
                              data-name="<?= esc($t['name']) ?>"
                              data-active="<?= $t['is_active'] ?>"
                              title="<?= $t['is_active'] ? 'Nonaktifkan guru ini' : 'Aktifkan guru ini' ?>">
                        <?= $t['is_active'] ? '🚫 Nonaktifkan' : '✅ Aktifkan' ?>
                      </button>

                      <a href="<?= base_url('admin/teachers/delete/' . $t['id']) ?>"
                         class="btn btn-sm btn-danger"
                         onclick="return confirm('Yakin hapus guru <?= esc($t['name'], 'js') ?>? Akun login terkait juga akan dihapus.')">
                        🗑 Hapus
                      </a>
                    </div>
                  <?php else: ?>
                    <span class="badge bg-secondary">Read Only</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Toggle -->
<div class="modal fade" id="modalToggleActive" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="toggleModalTitle">Konfirmasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="toggleModalBody">
        Apakah Anda yakin?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form id="formToggleActive" method="POST" action="">
          <?= csrf_field() ?>
          <button type="submit" class="btn" id="btnToggleConfirm">Ya, Lanjutkan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">📥 Import Data Guru & Tendik</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Pilihan Format -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Format File</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="import_format" id="formatBiasa" value="biasa" checked>
              <label class="form-check-label" for="formatBiasa">
                📋 Template Sederhana (NIP, Nama, Email, JK)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="import_format" id="formatDapodik" value="dapodik">
              <label class="form-check-label" for="formatDapodik">
                🏫 Tarikan Dapodik (Lengkap)
              </label>
            </div>
          </div>
        </div>

        <!-- Form untuk Format Biasa -->
        <div id="panelFormatBiasa">
          <form id="formImportBiasa" action="<?= base_url('admin/teachers/import') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Pilih File Excel (.xlsx / .xls)</label>
              <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls">
              <div class="form-text">
                Format: kolom A=NIP, B=Nama, C=Email, D=JK (L/P).
                <a href="<?= base_url('admin/teachers/downloadTemplate') ?>" class="fw-bold">Download Template.</a>
              </div>
            </div>
            <button type="submit" class="btn btn-success w-100">Mulai Import</button>
          </form>
        </div>

        <!-- Panel untuk Format Dapodik -->
        <div id="panelFormatDapodik" style="display:none;">
          <div id="stepUpload">
            <div class="mb-3">
              <label class="form-label">Pilih File Tarikan Dapodik (.xlsx / .xls)</label>
              <input type="file" id="fileDapodik" class="form-control" accept=".xlsx, .xls">
              <div class="form-text">
                File dari: <strong>Dapodik → Data PTK → Daftar Guru / Daftar Tenaga Kependidikan → Ekspor</strong>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Jika data sudah ada (duplikat NUPTK/NIP)</label>
              <select id="onDuplicateSelect" class="form-select">
                <option value="skip">Lewati (Skip) — data lama tidak berubah</option>
                <option value="update">Perbarui (Update) — data lama ditimpa</option>
              </select>
            </div>
            <button type="button" id="btnPreview" class="btn btn-primary w-100" onclick="previewDapodik()">
              🔍 Preview Data
            </button>
            <div id="previewLoading" class="text-center mt-3" style="display:none;">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="mt-2 text-muted">Memproses file...</p>
            </div>
          </div>

          <div id="stepPreview" style="display:none;">
            <div class="alert alert-info mb-3" id="previewInfo"></div>
            <div class="mb-2 d-flex gap-2">
              <span class="badge bg-success fs-6" id="badgeInsert">0 baru</span>
              <span class="badge bg-warning text-dark fs-6" id="badgeUpdate">0 diperbarui</span>
              <span class="badge bg-secondary fs-6" id="badgeSkip">0 dilewati</span>
            </div>
            <p class="text-muted small mb-2">Preview 10 baris pertama:</p>
            <div class="table-responsive" style="max-height:260px; overflow-y:auto;">
              <table class="table table-sm table-bordered table-striped" id="tablePreview">
                <thead class="table-dark">
                  <tr>
                    <th>Nama</th><th>NIP</th><th>NUPTK</th><th>JK</th><th>Jenis PTK</th><th>Status</th><th>Action</th>
                  </tr>
                </thead>
                <tbody id="previewBody"></tbody>
              </table>
            </div>

            <form id="formImportDapodik" action="<?= base_url('admin/teachers/import-dapodik') ?>" method="POST" enctype="multipart/form-data" class="mt-3">
              <?= csrf_field() ?>
              <input type="file" name="file_excel" id="fileInputHidden" style="display:none;">
              <input type="hidden" name="on_duplicate" id="onDuplicateHidden">
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="resetDapodikPanel()">← Kembali</button>
                <button type="submit" class="btn btn-success flex-grow-1" id="btnKonfirmasi">
                  ✅ Konfirmasi & Import Sekarang
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
// ── Toggle Aktif/Nonaktif ────────────────────────────────────────────────────
document.querySelectorAll('.btn-toggle-active').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id     = this.dataset.id;
    var name   = this.dataset.name;
    var active = parseInt(this.dataset.active);
    var action = active ? 'nonaktifkan' : 'aktifkan';
    var btnClass = active ? 'btn-danger' : 'btn-success';

    document.getElementById('toggleModalTitle').textContent =
      active ? '🚫 Nonaktifkan Guru' : '✅ Aktifkan Guru';
    document.getElementById('toggleModalBody').textContent =
      'Yakin ingin ' + action + ' guru "' + name + '"?' +
      (active ? ' Akun login guru ini juga akan dinonaktifkan.' : ' Akun login guru ini juga akan diaktifkan kembali.');

    var btnConfirm = document.getElementById('btnToggleConfirm');
    btnConfirm.textContent = 'Ya, ' + action.charAt(0).toUpperCase() + action.slice(1);
    btnConfirm.className = 'btn ' + btnClass;

    document.getElementById('formToggleActive').action =
      '<?= base_url('admin/teachers/toggle-active/') ?>' + id;

    var modal = new bootstrap.Modal(document.getElementById('modalToggleActive'));
    modal.show();
  });
});

// ── Import Format Toggle ────────────────────────────────────────────────────
document.querySelectorAll('input[name="import_format"]').forEach(function(el) {
  el.addEventListener('change', function() {
    document.getElementById('panelFormatBiasa').style.display   = this.value === 'biasa'   ? '' : 'none';
    document.getElementById('panelFormatDapodik').style.display = this.value === 'dapodik' ? '' : 'none';
  });
});

function previewDapodik() {
  var fileInput = document.getElementById('fileDapodik');
  if (!fileInput.files || !fileInput.files[0]) {
    alert('Pilih file terlebih dahulu.');
    return;
  }

  document.getElementById('previewLoading').style.display = '';
  document.getElementById('btnPreview').disabled = true;

  var formData = new FormData();
  formData.append('file_excel', fileInput.files[0]);
  var csrfInput = document.querySelector('#formImportBiasa input[type="hidden"]');
  formData.append(csrfInput.name, csrfInput.value);

  fetch('<?= base_url('admin/teachers/import-dapodik/preview') ?>', {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    document.getElementById('previewLoading').style.display = 'none';
    document.getElementById('btnPreview').disabled = false;

    if (!data || data.status !== 'ok' || !data.summary) {
      alert('❌ ' + (data && data.message ? data.message : 'Format response tidak valid.'));
      return;
    }

    var tipe = data.tipe_ptk === 'guru' ? '👩‍🏫 Daftar Guru' : '🗂️ Daftar Tenaga Kependidikan';
    document.getElementById('previewInfo').innerHTML =
      '<strong>' + tipe + '</strong> · ' + data.sekolah_nama +
      ' · <strong>' + data.total_rows + ' PTK</strong> ditemukan';

    document.getElementById('badgeInsert').textContent = data.summary.akan_insert + ' baru';
    document.getElementById('badgeUpdate').textContent = data.summary.akan_update + ' diperbarui';
    document.getElementById('badgeSkip').textContent   = data.summary.akan_skip   + ' dilewati';

    var tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';
    data.preview.forEach(function(row) {
      var actionBadge = row.action === 'INSERT'
        ? '<span class="badge bg-success">INSERT</span>'
        : '<span class="badge bg-warning text-dark">UPDATE</span>';
      tbody.innerHTML +=
        '<tr><td>' + (row.nama||'-') + '</td><td>' + (row.nip||'-') + '</td>' +
        '<td>' + (row.nuptk||'-') + '</td><td>' + (row.jk||'-') + '</td>' +
        '<td>' + (row.jenis_ptk||'-') + '</td><td>' + (row.status_kepegawaian||'-') + '</td>' +
        '<td>' + actionBadge + '</td></tr>';
    });

    var dt = new DataTransfer();
    dt.items.add(fileInput.files[0]);
    document.getElementById('fileInputHidden').files = dt.files;
    document.getElementById('onDuplicateHidden').value = document.getElementById('onDuplicateSelect').value;

    document.getElementById('stepUpload').style.display  = 'none';
    document.getElementById('stepPreview').style.display = '';
  })
  .catch(function(err) {
    document.getElementById('previewLoading').style.display = 'none';
    document.getElementById('btnPreview').disabled = false;
    alert('Terjadi kesalahan: ' + err.message);
  });
}

function resetDapodikPanel() {
  document.getElementById('stepUpload').style.display  = '';
  document.getElementById('stepPreview').style.display = 'none';
  document.getElementById('fileDapodik').value = '';
}
</script>

<?= $this->endSection() ?>
