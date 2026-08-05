<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Users page */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 12px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

.page-header h1 {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

.page-header p {
    margin: 0 0 1rem 0 !important;
    opacity: 0.95 !important;
}

.page-header-actions {
    display: flex !important;
    gap: 0.75rem !important;
    flex-wrap: wrap !important;
}

.btn-modern {
    padding: 0.625rem 1.5rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
}

/* Fix untuk tombol aksi - gunakan padding lebih kecil */
.btn-action {
    padding: 0.375rem 0.75rem !important;
    font-size: 0.875rem !important;
    border-radius: 6px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    white-space: nowrap !important;
}

.btn-action:hover {
    transform: translateY(-1px) !important;
}

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-primary-gradient:hover {
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    color: white !important;
}

.btn-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.btn-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
}

.modern-card-body {
    padding: 1.5rem !important;
}

.alert-modern {
    border: none !important;
    border-radius: 10px !important;
    padding: 1rem 1.25rem !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 0.75rem !important;
    margin-bottom: 1.5rem !important;
}

.alert-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.badge-modern {
    padding: 0.375rem 0.75rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.8rem !important;
}

.badge-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.badge-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.badge-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.badge-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.table-modern thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #495057 !important;
    padding: 0.875rem !important;
}

.table-modern tbody tr {
    transition: background-color 0.2s ease !important;
}

.table-modern tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table-modern tbody td {
    padding: 0.875rem !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f3f5 !important;
}

.empty-state {
    text-align: center !important;
    padding: 2.5rem 1rem !important;
}

.empty-state-icon {
    font-size: 3.5rem !important;
    color: #dee2e6 !important;
    margin-bottom: 1rem !important;
}

.empty-state-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.empty-state-text {
    color: #6c757d !important;
    font-size: 0.9rem !important;
    margin-bottom: 1.5rem !important;
}

.input-group-text {
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 8px 0 0 8px !important;
}

.form-control {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.pagination {
    margin-top: 1.5rem !important;
    justify-content: center !important;
}

.pagination .page-link {
    border: 1px solid #dee2e6 !important;
    color: #667eea !important;
    padding: 0.5rem 0.875rem !important;
    margin: 0 0.25rem !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
}

.pagination .page-link:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-color: #667eea !important;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-color: #667eea !important;
    color: white !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-users"></i> Manajemen User</h1>
        <p>Kelola akun pengguna sistem CBT</p>
        <div class="page-header-actions">
            <a href="<?= base_url('admin/users/create') ?>" class="btn btn-modern btn-primary-gradient">
                <i class="fas fa-plus"></i> Tambah User
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="modern-card">
        <div class="modern-card-body">
            <!-- Alerts -->
            <?php if(session()->getFlashdata('success')): ?>
                <div class="alert alert-modern alert-success-gradient">
                    <i class="fas fa-check-circle"></i>
                    <div><?= session()->getFlashdata('success') ?></div>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <form method="get" action="<?= base_url('admin/users') ?>" class="row g-3 mb-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="keyword" class="form-control" 
                               placeholder="Cari username atau nama..."
                               value="<?= esc($keyword ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-modern btn-primary-gradient w-100">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-modern btn-secondary w-100">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-muted">
                        Total: <strong><?= count($users) ?></strong> user
                    </span>
                </div>
            </form>

            <!-- Table -->
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th width="150">Role</th>
                                <th width="240" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1 + (10 * ((service('request')->getGet('page') ?? 1) - 1)); ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-user-circle text-primary"></i>
                                        <strong><?= esc($user['username']) ?></strong>
                                    </div>
                                </td>
                                <td><?= esc($user['fullname']) ?></td>
                                <td>
                                    <?php 
                                    $roleName = $user['role_name'] ?? 'Unknown';
                                    $badgeClass = 'badge-primary-gradient';
                                    if ($roleName === 'Admin') {
                                        $badgeClass = 'badge-danger-gradient';
                                    } elseif ($roleName === 'Guru') {
                                        $badgeClass = 'badge-success-gradient';
                                    } elseif ($roleName === 'Siswa') {
                                        $badgeClass = 'badge-warning-gradient';
                                    }
                                    ?>
                                    <span class="badge badge-modern <?= $badgeClass ?>">
                                        <i class="fas fa-user-tag"></i> <?= esc($roleName) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('admin/users/edit/'.$user['id']) ?>" 
                                       class="btn btn-action btn-warning-gradient"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url('admin/users/reset-password/'.$user['id']) ?>"
                                       class="btn btn-action btn-info-gradient"
                                       onclick="return confirm('Reset password user ini ke 123456?');"
                                       title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a href="<?= base_url('admin/users/delete/'.$user['id']) ?>" 
                                       class="btn btn-action btn-danger-gradient"
                                       onclick="return confirm('Yakin ingin menghapus user ini?');"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    <?= $pager->links('users', 'bootstrap') ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Data User</h3>
                    <p class="empty-state-text">
                        Mulai tambahkan user untuk mengelola akses sistem.
                    </p>
                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-modern btn-primary-gradient">
                        <i class="fas fa-plus"></i> Tambah User Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
