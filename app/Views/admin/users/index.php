<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Manajemen User</h4>
      <small class="text-muted">Kelola akun pengguna berdasarkan peran (role)</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <!-- Search -->
      <form method="get" action="<?= base_url('admin/users') ?>" class="d-flex gap-1">
        <?php if ($activeRole !== 'all'): ?>
          <input type="hidden" name="role" value="<?= esc($activeRole) ?>">
        <?php endif; ?>
        <input type="text" name="keyword" class="form-control form-control-sm"
               value="<?= esc($keyword ?? '') ?>"
               placeholder="🔍 Cari username / nama / email..."
               style="min-width:220px;">
        <button type="submit" class="btn btn-sm btn-outline-primary">Cari</button>
        <?php if (!empty($keyword)): ?>
          <a href="<?= base_url('admin/users') . ($activeRole !== 'all' ? '?role=' . esc($activeRole) : '') ?>"
             class="btn btn-sm btn-outline-secondary">✕</a>
        <?php endif; ?>
      </form>
      <?php if ((session()->get('user')['role_id'] ?? 0) != 2): ?>
        <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-person-plus-fill me-1"></i> Tambah User
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">

      <!-- Tab Nav -->
      <ul class="nav nav-tabs px-3 pt-3 border-bottom" id="userRoleTabs" role="tablist">

        <!-- Tab Semua -->
        <?php
          $totalAll = array_sum($countPerRole);
          $isAll    = ($activeRole === 'all' && empty($keyword)) || ($activeRole === 'all');
          // Jika ada keyword, tetap bisa di semua tab
        ?>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?= $activeRole === 'all' ? 'active' : '' ?> text-nowrap"
             href="<?= base_url('admin/users') . (!empty($keyword) ? '?keyword=' . urlencode($keyword) : '') ?>">
            <i class="bi bi-grid me-1"></i> Semua
            <span class="badge bg-secondary ms-1"><?= $totalAll ?></span>
          </a>
        </li>

        <!-- Tab per Role -->
        <?php foreach ($roles as $role):
          $roleCount = $countPerRole[$role['id']] ?? 0;
          $isActive  = ((string)$activeRole === (string)$role['id']);

          // Icon per role
          $roleIcons = [
            1 => 'bi-shield-fill-check text-danger',
            2 => 'bi-person-badge-fill text-success',
            3 => 'bi-person-workspace text-primary',
            4 => 'bi-people-fill text-warning',
            5 => 'bi-mortarboard-fill text-info',
            6 => 'bi-pencil-square text-secondary',
            7 => 'bi-person-gear text-dark',
          ];
          $icon = $roleIcons[$role['id']] ?? 'bi-person text-muted';

          $href = base_url('admin/users') . '?role=' . $role['id']
                  . (!empty($keyword) ? '&keyword=' . urlencode($keyword) : '');
        ?>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?= $isActive ? 'active' : '' ?> text-nowrap" href="<?= $href ?>">
            <i class="bi <?= $icon ?> me-1"></i>
            <?= esc($role['name']) ?>
            <span class="badge <?= $isActive ? 'bg-primary' : 'bg-light text-dark border' ?> ms-1">
              <?= $roleCount ?>
            </span>
          </a>
        </li>
        <?php endforeach; ?>

      </ul>

      <!-- Tabel User -->
      <div class="p-3">
        <?php if (!empty($keyword)): ?>
          <div class="alert alert-info py-2 mb-3 small">
            <i class="bi bi-search me-1"></i>
            Hasil pencarian untuk: <strong>"<?= esc($keyword) ?>"</strong>
            — ditemukan <strong><?= count($users) ?></strong> user.
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:48px;" class="text-center">#</th>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <?php if ($activeRole === 'all'): ?>
                  <th style="width:130px;">Role</th>
                <?php endif; ?>
                <th style="width:160px;">Status</th>
                <th style="width:180px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($users)): ?>
                <?php $no = 1; foreach ($users as $u): ?>
                  <tr>
                    <td class="text-center text-muted small"><?= $no++ ?></td>
                    <td>
                      <div class="fw-semibold"><?= esc($u['username']) ?></div>
                    </td>
                    <td><?= esc($u['fullname'] ?: '-') ?></td>
                    <td class="text-muted small"><?= esc($u['email'] ?: '-') ?></td>
                    <?php if ($activeRole === 'all'): ?>
                      <td>
                        <?php
                          $roleColors = [1=>'danger',2=>'success',3=>'primary',4=>'warning',5=>'info',6=>'secondary',7=>'dark'];
                          $roleColor = $roleColors[$u['role_id']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $roleColor ?>-subtle text-<?= $roleColor ?> border border-<?= $roleColor ?>-subtle small">
                          <?= esc($u['role_name'] ?? 'Unknown') ?>
                        </span>
                      </td>
                    <?php endif; ?>
                    <td>
                      <?php if (!empty($u['is_active'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">
                          <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Aktif
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border small">
                          <i class="bi bi-circle me-1" style="font-size:0.5rem;"></i>Nonaktif
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if ((session()->get('user')['role_id'] ?? 0) != 2): ?>
                        <div class="btn-group btn-group-sm">
                          <a href="<?= base_url('admin/users/edit/' . $u['id']) ?>"
                             class="btn btn-outline-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                          </a>
                          <a href="<?= base_url('admin/users/reset-password/' . $u['id']) ?>"
                             class="btn btn-outline-secondary" title="Reset Password"
                             onclick="return confirm('Reset password user ini ke 123456?');">
                            <i class="bi bi-key"></i>
                          </a>
                          <a href="<?= base_url('admin/users/delete/' . $u['id']) ?>"
                             class="btn btn-outline-danger" title="Hapus"
                             onclick="return confirm('Yakin hapus user <?= esc(addslashes($u['fullname'] ?: $u['username'])) ?>?');">
                            <i class="bi bi-trash"></i>
                          </a>
                        </div>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border small">Read Only</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                    <?= !empty($keyword) ? 'Tidak ada user yang cocok dengan pencarian.' : 'Belum ada user di role ini.' ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Footer info -->
        <?php if (!empty($users)): ?>
          <div class="mt-3 text-muted small">
            Menampilkan <strong><?= count($users) ?></strong> user
            <?php if ($activeRole !== 'all'): ?>
              — role: <strong>
                <?php foreach ($roles as $r): if ($r['id'] == $activeRole): ?>
                  <?= esc($r['name']) ?>
                <?php endif; endforeach; ?>
              </strong>
            <?php endif; ?>
            <?php if (!empty($keyword)): ?>
              — kata kunci: <em>"<?= esc($keyword) ?>"</em>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div><!-- /p-3 -->
    </div><!-- /card-body -->
  </div><!-- /card -->

</div>

<?= $this->endSection() ?>
