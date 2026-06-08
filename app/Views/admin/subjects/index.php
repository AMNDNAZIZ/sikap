<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h1 class="h3 mb-4">📘 Daftar Mata Pelajaran</h1>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<?php if (session()->get('user')['role_id'] != 2): ?>
  <a href="<?= base_url('admin/subjects/create') ?>" class="btn btn-primary mb-3">➕ Tambah Mata Pelajaran</a>
<?php endif; ?>

<div class="table-responsive">
  <table class="table table-bordered table-striped">
    <thead class="table-light">
      <tr>
        <th>No</th>
        <th>Kode</th>
        <th>Nama</th>
        <th>Kelompok</th>
        <th>Urutan</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1;
      foreach ($subjects as $s): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= esc($s['code']) ?></td>
          <td><?= esc($s['name']) ?></td>
          <td><?= esc($s['subject_group']) ?></td>
          <td><?= esc($s['sort_order']) ?></td>
          <td>
            <?php if ($s['is_active']): ?>
              <span class="badge bg-success">Aktif</span>
            <?php else: ?>
              <span class="badge bg-secondary">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (session()->get('user')['role_id'] != 2): ?>
              <a href="<?= base_url('admin/subjects/edit/' . $s['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
              <form action="<?= base_url('admin/subjects/delete/' . $s['id']) ?>" method="post" class="d-inline"
                onsubmit="return confirm('Yakin ingin menghapus?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-danger">🗑 Hapus</button>
              </form>
            <?php else: ?>
              <span class="badge bg-secondary">Read Only</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>