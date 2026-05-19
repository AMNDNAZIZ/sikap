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
<?php if (session()->getFlashdata('import_recap')): ?>
  <?php $recap = session()->getFlashdata('import_recap'); ?>
  <div class="alert alert-success">
    <strong>✅ Rekap Import Data Siswa:</strong>
    <ul class="mb-0 mt-2 list-unstyled">
      <!-- Insert -->
      <li class="mb-2">
        <button class="btn btn-sm btn-outline-success py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInsert" aria-expanded="false" aria-controls="collapseInsert">
          <i class="fas fa-plus"></i> <?= count($recap['insert']) ?> data siswa baru ditambahkan
        </button>
        <div class="collapse mt-2" id="collapseInsert">
          <ul class="mb-0 small text-success">
            <?php foreach ($recap['insert'] as $item): ?>
              <li><?= esc($item) ?></li>
            <?php endforeach; ?>
            <?php if(empty($recap['insert'])) echo '<li>Tidak ada</li>'; ?>
          </ul>
        </div>
      </li>
      
      <!-- Update -->
      <li class="mb-2">
        <button class="btn btn-sm btn-outline-primary py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpdate" aria-expanded="false" aria-controls="collapseUpdate">
          <i class="fas fa-sync"></i> <?= count($recap['update']) ?> data siswa eksisting diperbarui
        </button>
        <div class="collapse mt-2" id="collapseUpdate">
          <ul class="mb-0 small text-primary">
            <?php foreach ($recap['update'] as $item): ?>
              <li><?= esc($item) ?></li>
            <?php endforeach; ?>
            <?php if(empty($recap['update'])) echo '<li>Tidak ada</li>'; ?>
          </ul>
        </div>
      </li>

      <!-- Fail -->
      <li>
        <button class="btn btn-sm btn-outline-danger py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFail" aria-expanded="false" aria-controls="collapseFail">
          <i class="fas fa-times"></i> <?= count($recap['fail']) ?> baris data gagal diproses
        </button>
        <div class="collapse mt-2" id="collapseFail">
          <ul class="mb-0 small text-danger">
            <?php foreach ($recap['fail'] as $item): ?>
              <li><?= esc($item) ?></li>
            <?php endforeach; ?>
            <?php if(empty($recap['fail'])) echo '<li>Tidak ada</li>'; ?>
          </ul>
        </div>
      </li>
    </ul>
  </div>
<?php endif; ?>


<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-2">
        <select name="per_page" class="form-select" onchange="this.form.submit()">
          <?php foreach ([10, 25, 50, 100, 250, 500] as $num): ?>
            <option value="<?= $num ?>" <?= (isset($perPage) && $perPage == $num) ? 'selected' : '' ?>><?= $num ?> Baris</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Cari nama/NIS/NISN..."
          value="<?= esc($search ?? '') ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">🔍 Cari</button>
      </div>
      <div class="col-md-2">
        <a href="<?= base_url('admin/students') ?>" class="btn btn-secondary w-100">🔄 Reset</a>
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
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?= base_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">📂 Impor Data Siswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="file" class="form-label">Pilih File Excel</label>
            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
          </div>
          <div class="alert alert-info py-2">
            <small>
              <i class="fas fa-info-circle"></i> Mendukung dua format Excel:<br>
              1. <b>Format Dapodik</b> (Unduh Daftar Peserta Didik dari Web Dapodik).<br>
              2. <b>Format Template Sederhana</b> (<a href="<?= base_url('admin/students/download-template') ?>" class="fw-bold">Unduh Template Disini</a>).
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">🚀 Impor</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>