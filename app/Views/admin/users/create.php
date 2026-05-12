<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>➕ Tambah User</h2>

<div class="card shadow-sm">
  <div class="card-body">
    <form action="<?= base_url('admin/users/store') ?>" method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" name="username" id="username" required>
      </div>

      <div class="mb-3">
        <label for="fullname" class="form-label">Nama Lengkap</label>
        <input type="text" class="form-control" name="fullname" id="fullname" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" name="email" id="email" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" name="password" id="password" required>
      </div>

      <div class="mb-3">
        <label for="role_id" class="form-label">Role</label>
        <select class="form-select" name="role_id" id="role_id" required>
          <?php foreach ($roles as $id => $roleName): ?>
            <option value="<?= $id ?>"><?= $roleName ?></option>
          <?php endforeach; ?>
        </select>
      </div>


      <button type="submit" class="btn btn-success">💾 Simpan</button>
      <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">↩️ Kembali</a>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
