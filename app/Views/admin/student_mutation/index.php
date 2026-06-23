<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>📋 Buku Mutasi Siswa</h4>
  <a href="<?= base_url('admin/student-mutation/create') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle"></i> Tambah Mutasi
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<div class="card">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-2">
        <select name="type" class="form-select">
          <option value="">Semua Jenis</option>
          <option value="masuk" <?= ($filters['type'] ?? '') == 'masuk' ? 'selected' : '' ?>>Masuk</option>
          <option value="keluar" <?= ($filters['type'] ?? '') == 'keluar' ? 'selected' : '' ?>>Keluar</option>
          <option value="pindah_kelas" <?= ($filters['type'] ?? '') == 'pindah_kelas' ? 'selected' : '' ?>>Pindah Kelas</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">Semua Status</option>
          <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= ($filters['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Disetujui</option>
          <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Ditolak</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>" placeholder="Dari">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>" placeholder="Sampai">
      </div>
      <div class="col-md-2">
        <input type="text" name="search" class="form-control" placeholder="Cari nama/sekolah..."
               value="<?= esc($filters['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="table-light">
          <tr>
            <th width="4%">No</th>
            <th width="10%">Tanggal</th>
            <th>Nama Siswa</th>
            <th width="10%">Jenis</th>
            <th>Dari / Ke</th>
            <th width="9%">Status</th>
            <th width="14%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($mutations)): ?>
            <?php $no = 1; foreach ($mutations as $m): ?>
              <?php
                $typeLabel = ['masuk' => 'Masuk', 'keluar' => 'Keluar', 'pindah_kelas' => 'Pindah Kelas'];
                $typeBadge = ['masuk' => 'success', 'keluar' => 'warning', 'pindah_kelas' => 'info'];
                $statusBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                $statusLabel = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

                $direction = '';
                if ($m['type'] === 'masuk') {
                    $direction = ($m['from_school'] ?? '-') . ' &rarr; ' . ($m['to_class_name'] ?? '-');
                } elseif ($m['type'] === 'keluar') {
                    $direction = ($m['from_class_name'] ?? '-') . ' &rarr; ' . ($m['to_school'] ?? '-');
                } else {
                    $direction = ($m['from_class_name'] ?? '-') . ' &rarr; ' . ($m['to_class_name'] ?? '-');
                }
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d/m/Y', strtotime($m['mutation_date'])) ?></td>
                <td>
                  <?php if ($m['student_id'] && $m['student_name']): ?>
                    <?= esc($m['student_name']) ?>
                    <br><small class="text-muted"><?= esc($m['nisn'] ?? '') ?></small>
                  <?php else: ?>
                    <span class="text-muted">Siswa baru (pending)</span>
                  <?php endif ?>
                </td>
                <td><span class="badge bg-<?= $typeBadge[$m['type']] ?>"><?= $typeLabel[$m['type']] ?></span></td>
                <td><?= $direction ?></td>
                <td><span class="badge bg-<?= $statusBadge[$m['status']] ?>"><?= $statusLabel[$m['status']] ?></span></td>
                <td>
                  <a href="<?= base_url('admin/student-mutation/show/' . $m['id']) ?>"
                     class="btn btn-info btn-sm" title="Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($m['status'] === 'approved'): ?>
                    <a href="<?= base_url('admin/student-mutation/print/' . $m['id']) ?>"
                       class="btn btn-danger btn-sm" target="_blank" title="Cetak Surat">
                      <i class="bi bi-printer"></i>
                    </a>
                  <?php endif ?>
                  <?php if ($m['status'] !== 'approved'): ?>
                    <form action="<?= base_url('admin/student-mutation/delete/' . $m['id']) ?>" method="post"
                          class="d-inline" onsubmit="return confirm('Yakin hapus mutasi ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  <?php endif ?>
                </td>
              </tr>
            <?php endforeach ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted">Belum ada data mutasi.</td>
            </tr>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="d-flex justify-content-center mt-3">
  <?= $pager->links('default', 'bootstrap') ?>
</div>

<?= $this->endSection() ?>
