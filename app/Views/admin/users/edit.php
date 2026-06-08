<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>✏️ Edit User</h2>

<div class="card shadow-sm">
  <div class="card-body">
    <form action="<?= base_url('admin/users/update/'.$user['id']) ?>" method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" name="username" id="username" value="<?= esc($user['username']) ?>" required>
      </div>

      <div class="mb-3">
        <label for="fullname" class="form-label">Nama Lengkap</label>
        <input type="text" class="form-control" name="fullname" id="fullname" value="<?= esc($user['fullname']) ?>" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" name="email" id="email" value="<?= esc($user['email']) ?>" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password (kosongkan jika tidak diubah)</label>
        <input type="password" class="form-control" name="password" id="password">
      </div>

      <div class="mb-3">
        <label for="role_id" class="form-label">Role</label>
        <select class="form-select" name="role_id" id="role_id" required>
          <?php foreach ($roles as $id => $roleName): ?>
            <option value="<?= $id ?>" <?= $user['role_id'] == $id ? 'selected' : '' ?>>
              <?= $roleName ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>


      <button type="submit" class="btn btn-success">💾 Update</button>
      <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">↩️ Kembali</a>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
