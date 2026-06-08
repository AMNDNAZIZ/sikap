<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">Ganti Password - <?= esc($user['fullname']) ?></div>
  <div class="card-body">

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= session('success') ?></div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= session('error') ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('profile/update-password') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Password Lama</label>
        <div class="input-group">
          <input type="password" name="current_password" id="current_password" class="form-control" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password Baru</label>
        <div class="input-group">
          <input type="password" name="new_password" id="new_password" class="form-control" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <div class="input-group">
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Ubah Password</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye');
      icon.classList.add('bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('bi-eye-slash');
      icon.classList.add('bi-eye');
    }
  }
</script>
<?= $this->endSection() ?>