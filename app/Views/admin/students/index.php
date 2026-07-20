<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between mb-3">
  <h4>👩‍🎓 Manajemen Siswa</h4>
  <?php if (session()->get('user')['role_id'] != 2): ?>
    <div>
      <a href="<?= base_url('admin/students/create') ?>" class="btn btn-primary btn-sm">➕ Tambah Siswa</a>
      <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
        📂 Impor Siswa
      </button>
    </div>
  <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php elseif (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('import_success')): ?>
  <div class="alert alert-success">
    <strong>✅ Berhasil diimpor:</strong>
    <ul>
      <?php foreach (session()->getFlashdata('import_success') as $s): ?>
        <li><?= esc($s) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('import_failed')): ?>
  <div class="alert alert-danger">
    <strong>⚠️ Gagal diimpor:</strong>
    <ul>
      <?php foreach (session()->getFlashdata('import_failed') as $f): ?>
        <li><?= esc($f) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
<?php endif; ?>


<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <select name="academic_year_id" class="form-select">
          <option value="all">Semua Tahun Ajaran</option>
          <?php foreach ($academicYears as $year): ?>
            <option value="<?= $year['id'] ?>" <?= $selectedYear == $year['id'] ? 'selected' : '' ?>>
              <?= esc($year['year']) ?> <?= $year['is_active'] ? '✅' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="class_id" class="form-select">
          <option value="">Semua Kelas</option>
          <?php foreach ($classes as $class): ?>
            <option value="<?= $class['id'] ?>" <?= $selectedClass == $class['id'] ? 'selected' : '' ?>>
              <?= esc($class['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
          <option value="aktif" <?= $selectedStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="lulus" <?= $selectedStatus === 'lulus' ? 'selected' : '' ?>>Lulus</option>
          <option value="pindah" <?= $selectedStatus === 'pindah' ? 'selected' : '' ?>>Pindah</option>
          <option value="dropout" <?= $selectedStatus === 'dropout' ? 'selected' : '' ?>>Drop Out</option>
          <option value="nonaktif" <?= $selectedStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Cari nama/NIS/NISN..."
          value="<?= esc($search) ?>">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100" title="Cari">🔍</button>
      </div>
      <div class="col-md-1">
        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary w-100" title="Reset">🔄</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>NISN</th>
          <th>NIS</th>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Tahun Ajaran</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($students)): ?>
          <?php $no = 1;
          foreach ($students as $student): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($student['nisn']) ?></td>
              <td><?= esc($student['nis']) ?></td>
              <td><?= esc($student['name']) ?></td>
              <td><?= esc($student['class_name'] ?? '-') ?></td>
              <td><?= esc($student['academic_year'] ?? '-') ?></td>
              <td>
                <?php
                $status = $student['status'] ?? 'aktif';
                $badgeClass = [
                  'aktif' => 'success',
                  'nonaktif' => 'secondary',
                  'dropout' => 'danger',
                  'lulus' => 'primary'
                ][$status] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
              </td>
              <td>
                <?php if (session()->get('user')['role_id'] != 2): ?>
                  <a href="<?= base_url('admin/students/edit/' . $student['id']) ?>" class="btn btn-warning btn-sm">✏️</a>
                  <form action="<?= base_url('admin/students/delete/' . $student['id']) ?>" method="post" class="d-inline"
                    onsubmit="return confirm('Yakin hapus siswa ini?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                  </form>
                <?php endif; ?>
                <a href="<?= base_url('admin/student-records/' . $student['id']) ?>" class="btn btn-info btn-sm">📚
                  Riwayat</a>
              </td>
            </tr>
          <?php endforeach ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center">Belum ada data siswa</td>
          </tr>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-center mt-3">
  <?= $pager->links('default', 'bootstrap') ?>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="<?= base_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">📂 Impor Data Siswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i>
            Gunakan template resmi untuk memastikan format data sesuai.
            <a href="<?= base_url('admin/students/download-template') ?>" class="fw-bold ms-1">
              📥 Unduh Template Excel
            </a>
          </div>

          <!-- Info kolom -->
          <div class="mb-3">
            <p class="small fw-bold mb-1">Format kolom Excel (wajib sesuai urutan):</p>
            <table class="table table-sm table-bordered small mb-0">
              <thead class="table-light">
                <tr>
                  <th>Kolom</th><th>Nama</th><th>Wajib</th><th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>A</td><td>NISN</td><td><span class="text-warning">*</span></td><td>10 digit, unik nasional</td></tr>
                <tr><td>B</td><td>NIS</td><td><span class="text-warning">*</span></td><td>Nomor induk lokal sekolah</td></tr>
                <tr><td>C</td><td>Nama Lengkap</td><td><span class="text-danger">✓</span></td><td>Otomatis jadi huruf kapital</td></tr>
                <tr><td>D</td><td>Jenis Kelamin</td><td></td><td><code>L</code> atau <code>P</code></td></tr>
                <tr><td>E</td><td>Tempat Lahir</td><td></td><td>Kota/kabupaten</td></tr>
                <tr><td>F</td><td>Tanggal Lahir</td><td></td><td>Format: <code>YYYY-MM-DD</code></td></tr>
                <tr class="table-success"><td>G</td><td>Agama</td><td></td><td>Islam / Kristen / Katholik / Hindu / Budha / Khonghucu</td></tr>
              </tbody>
            </table>
            <small class="text-muted">* Minimal salah satu dari NISN atau NIS harus diisi. Akun siswa &amp; orang tua dibuat otomatis.</small>
          </div>

          <div class="mb-3">
            <label for="file" class="form-label fw-semibold">Pilih File Excel (.xlsx / .xls)</label>
            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">🚀 Impor Data</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>