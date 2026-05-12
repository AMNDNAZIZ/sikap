<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen User</h1>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-1"></i> Daftar User</span>
            <div>
                <form class="d-inline" method="get" action="<?= base_url('admin/users') ?>">
                    <input type="text" name="keyword" class="form-control form-control-sm d-inline-block"
                        value="<?= esc($keyword ?? '') ?>" placeholder="🔍 Cari user..." style="width:200px;">
                </form>
                <?php if (session()->get('user')['role_id'] != 2): ?>
                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm ms-2">
                        <i class="fas fa-plus"></i> Tambah User
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php $no = 1 + (10 * ((service('request')->getGet('page') ?? 1) - 1)); ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($user['username']) ?></td>
                                    <td><?= esc($user['fullname']) ?></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td><?= esc($user['role_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <?php if (session()->get('user')['role_id'] != 2): ?>
                                            <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?= base_url('admin/users/delete/' . $user['id']) ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin ingin menghapus user ini?');">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                            <a href="<?= base_url('admin/users/reset-password/' . $user['id']) ?>"
                                                class="btn btn-sm btn-secondary"
                                                onclick="return confirm('Reset password user ini ke 123456?');">
                                                🔄 Reset
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Read Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data user</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 🔹 Tampilkan pagination -->
            <div class="mt-3">
                <?= $pager->links('users', 'bootstrap') ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>