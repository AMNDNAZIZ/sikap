<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen User</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('msg')) ?></div>
<?php endif ?>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <?= implode('<br>', array_map('esc', (array) session('errors'))) ?>
  </div>
<?php endif ?>

<form method="post" action="<?= site_url('admin/user/save') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <div class="col-md-2"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
  <div class="col-md-2"><input type="password" name="password_hash" class="form-control" placeholder="Password"
      required></div>
  <div class="col-md-3"><input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required></div>
  <div class="col-md-2">
    <select name="role" class="form-select">
      <option value="operator">Operator</option>
      <option value="admin">Admin</option>
      <option value="superadmin">Superadmin</option>
      <option value="staf">Staf</option>
    </select>
  </div>
  <div class="col-md-1 d-flex align-items-center">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="aktif" value="1" checked>
      <label class="form-check-label">Aktif</label>
    </div>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Tambah</button>
  </div>
</form>

<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th>#</th>
      <th>Username</th>
      <th>Nama</th>
      <th>Role</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $i => $u): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= esc($u['username']) ?></td>
        <td><?= esc($u['nama']) ?></td>
        <td><span class="badge bg-info"><?= esc($u['role']) ?></span></td>
        <td><?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
        <td>
          <a href="<?= site_url('admin/user/edit/' . $u['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="<?= site_url('admin/user/delete/' . $u['id']) ?>" class="btn btn-sm btn-danger"
            onclick="return confirm('Yakin?')">Hapus</a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>