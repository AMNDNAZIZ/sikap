<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">📋 Pratinjau &amp; Konfirmasi Impor Data Siswa</h4>
    <small class="text-muted">Tipe Impor: <strong><?= $import_type === 'dapodik' ? '🏫 Tarikan Dapodik (.xlsx)' : '📄 Template SIAKAD (Manual)' ?></strong></small>
  </div>
  <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary btn-sm">❌ Batal / Kembali</a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-primary bg-primary bg-opacity-10">
      <div class="card-body py-2 text-center">
        <span class="text-muted small d-block">Total Data Dibaca</span>
        <h3 class="mb-0 text-primary fw-bold"><?= count($students) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-success bg-success bg-opacity-10">
      <div class="card-body py-2 text-center">
        <span class="text-muted small d-block">🟢 Data Siswa Baru (Insert)</span>
        <h3 class="mb-0 text-success fw-bold"><?= $new_count ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-warning bg-warning bg-opacity-10">
      <div class="card-body py-2 text-center">
        <span class="text-muted small d-block">🟡 Data Cocok Ada di DB (Update)</span>
        <h3 class="mb-0 text-warning fw-bold"><?= $update_count ?></h3>
      </div>
    </div>
  </div>
</div>

<form action="<?= base_url('admin/students/confirm-import') ?>" method="post" id="confirm-import-form">
  <?= csrf_field() ?>
  <input type="hidden" name="import_type" value="<?= esc($import_type) ?>">
  <?php
  // Encode data siswa sebagai JSON string agar tidak melebihi max_input_vars PHP
  $studentsJson = json_encode($students, JSON_UNESCAPED_UNICODE);
  ?>
  <input type="hidden" name="students_json" value="<?= esc($studentsJson, 'attr') ?>">

  <!-- Control & Settings Card -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header bg-light">
      <h6 class="mb-0 fw-bold"><i class="fas fa-sliders-h me-1"></i> Pengaturan Sinkronisasi &amp; Kelas</h6>
    </div>
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-md-6">
          <label class="form-label fw-bold">Jika Data Siswa Sudah Ada di SIAKAD:</label>
          <select name="sync_mode" class="form-select" required>
            <option value="update" selected>✏️ Update - Timpa &amp; lengkapi data siswa yang sudah ada</option>
            <option value="merge">🔀 Merge - Hanya isi field yang masih kosong</option>
            <option value="skip">⏭️ Skip - Lewati (hanya tambahkan siswa baru)</option>
          </select>
          <small class="text-muted">Pencocokan bertingkat berdasarkan <code>NIK</code> &rarr; <code>NISN</code> &rarr; <code>NIS</code> &rarr; <code>Nama+Tgl Lahir</code></small>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Kelas Cadangan (Fallback Class):</label>
          <select name="class_id" class="form-select">
            <option value="">-- Otomatis Sesuai Rombel Dapodik --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Digunakan jika kolom Rombel di Excel kosong atau tidak cocok dengan nama kelas di SIAKAD</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <span class="fw-bold">Daftar Siswa Hasil Deteksi Excel</span>
      <div>
        <button type="submit" class="btn btn-success fw-bold btn-sm px-3" id="btn-submit-confirm">
          🚀 Ya, Konfirmasi &amp; Impor Data Terpilih
        </button>
      </div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-striped table-hover align-middle mb-0 small">
        <thead class="table-dark">
          <tr>
            <th width="40" class="text-center"><input type="checkbox" id="check-all" class="form-check-input" checked></th>
            <th width="100">Status Match</th>
            <th>NIK</th>
            <th>NISN</th>
            <th>NIS</th>
            <th>Nama Lengkap</th>
            <th>L/P</th>
            <th>Tempat, Tgl Lahir</th>
            <th>Rombel / Kelas</th>
            <th>Data Orang Tua</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($students)): ?>
            <?php foreach ($students as $index => $s): ?>
              <tr>
                <td class="text-center">
                  <input type="checkbox" name="selected_students[]" value="<?= $index ?>" class="form-check-input student-check" checked>
                </td>
                <td>
                  <?php if (!empty($s['is_existing'])): ?>
                    <span class="badge bg-warning text-dark">✏️ UPDATE</span>
                    <small class="d-block text-muted" style="font-size:10px;">ID: #<?= $s['existing_id'] ?></small>
                  <?php else: ?>
                    <span class="badge bg-success">🟢 BARU</span>
                  <?php endif; ?>
                </td>
                <td><code><?= esc($s['nik'] ?: '-') ?></code></td>
                <td><code><?= esc($s['nisn'] ?: '-') ?></code></td>
                <td><code><?= esc($s['nis'] ?: '-') ?></code></td>
                <td class="fw-bold"><?= esc($s['name']) ?></td>
                <td><?= esc($s['gender']) ?></td>
                <td>
                  <?= esc($s['birth_place'] ?: '-') ?>,<br>
                  <small class="text-muted"><?= esc($s['birth_date'] ?: '-') ?></small>
                </td>
                <td>
                  <?php if (!empty($s['rombel'])): ?>
                    <span class="badge bg-info text-dark"><?= esc($s['rombel']) ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <small class="d-block"><strong>Ayah:</strong> <?= esc($s['father_name'] ?: '-') ?></small>
                  <small class="d-block text-muted"><strong>Ibu:</strong> <?= esc($s['mother_name'] ?: '-') ?></small>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center py-4 text-muted">
                Tidak ada data siswa ditemukan dari file Excel.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-light d-flex justify-content-between align-items-center">
      <span class="small text-muted">Pastikan data terpilih sudah benar sebelum melanjutkan.</span>
      <button type="submit" class="btn btn-success fw-bold" id="btn-submit-confirm-bottom">
        🚀 Ya, Konfirmasi &amp; Impor Data Terpilih
      </button>
    </div>
  </div>
</form>

<script>
  document.getElementById('check-all').addEventListener('change', function () {
    const checks = document.querySelectorAll('.student-check');
    checks.forEach(c => c.checked = this.checked);
  });

  document.getElementById('confirm-import-form').addEventListener('submit', function (e) {
    const checked = document.querySelectorAll('.student-check:checked');
    if (checked.length === 0) {
      e.preventDefault();
      alert('Pilih minimal 1 siswa yang akan diimpor!');
      return false;
    }

    const btn1 = document.getElementById('btn-submit-confirm');
    const btn2 = document.getElementById('btn-submit-confirm-bottom');
    if (btn1) { btn1.disabled = true; btn1.innerHTML = '⏳ Memproses Impor...'; }
    if (btn2) { btn2.disabled = true; btn2.innerHTML = '⏳ Memproses Impor...'; }
  });
</script>

<?= $this->endSection() ?>
