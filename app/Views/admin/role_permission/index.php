<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-5">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mt-4 fw-bold">Manajemen Role & Permission</h1>
            <p class="text-muted">Kelola peran pengguna dan atur hak akses masing-masing role terhadap modul sistem.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Role & Permission</li>
            </ol>
        </nav>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Daftar Role -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>Daftar Role</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Nama Role</th>
                                    <th>Jumlah User</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <?php
                                        $userCount = db_connect()->table('users')->where('role_id', $role['id'])->countAllResults();
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= $role['id'] ?></span></td>
                                        <td>
                                            <strong><?= esc($role['name']) ?></strong>
                                            <?php if ((int)$role['id'] === 1): ?>
                                                <span class="badge bg-warning text-dark ms-2">Super Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $userCount ?> user</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$role['id'] === 1): ?>
                                                <span class="text-muted small"><i class="bi bi-lock-fill"></i> Akses Penuh</span>
                                            <?php else: ?>
                                                <a href="<?= base_url('admin/role-permission/edit/' . $role['id']) ?>"
                                                   class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="bi bi-shield-lock me-1"></i>Atur Permission
                                                </a>
                                                <?php if ((int)$role['id'] > 7): ?>
                                                    <a href="<?= base_url('admin/role-permission/delete/' . $role['id']) ?>"
                                                       class="btn btn-sm btn-outline-danger rounded-pill ms-1"
                                                       onclick="return confirm('Yakin hapus role ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tambah Role Baru -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Role Baru</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/role-permission/create-role') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Role</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Wali Kelas" required>
                            <small class="text-muted">Nama unik untuk peran baru di sistem.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill">
                            <i class="bi bi-plus-lg me-2"></i>Tambah Role
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info -->
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                    <ul class="small text-muted mb-0">
                        <li class="mb-2"><strong>Admin (Super Admin)</strong> selalu memiliki akses penuh ke semua modul dan tidak dapat diubah.</li>
                        <li class="mb-2">Klik <strong>"Atur Permission"</strong> untuk menentukan modul apa saja yang bisa diakses oleh role tersebut.</li>
                        <li class="mb-2">Role bawaan sistem (ID 1-7) tidak dapat dihapus, namun permission-nya tetap bisa diatur.</li>
                        <li>Anda bisa menambah role baru sesuai kebutuhan sekolah (misal: Wali Kelas, Koordinator, dll).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
