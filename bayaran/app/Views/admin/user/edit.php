<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit User</h4>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <?= implode('<br>', session('errors')) ?>
  </div>
<?php endif ?>

<form method="post" action="<?= site_url('admin/user/update/' . $user['id']) ?>" class="row g-3">
  <?= csrf_field() ?>

  <div class="col-md-4">
    <label>Nama Lengkap</label>
    <input type="text" name="nama" value="<?= esc($user['nama']) ?>" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label>Password Baru (kosongkan jika tidak diganti)</label>
    <input type="password" name="password_hash" class="form-control">
  </div>
  <div class="col-md-2">
    <label>Role</label>
    <select name="role" class="form-select" required>
      <option value="operator" <?= $user['role'] === 'operator' ? 'selected' : '' ?>>Operator</option>
      <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
      <option value="staf" <?= $user['role'] === 'staf' ? 'selected' : '' ?>>Staf</option>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary w-100">Simpan</button>
  </div>
</form>

<?= $this->endSection() ?>
