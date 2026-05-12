<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen Changelog</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
        <li class="breadcrumb-item active">Changelog</li>
    </ol>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i> Daftar Riwayat Perubahan</h5>
            <a href="<?= base_url('admin/changelogs/create') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Baru
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Versi</th>
                            <th>Tanggal Rilis</th>
                            <th>Status</th>
                            <th>Deskripsi</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($changelogs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat perubahan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($changelogs as $i => $log): ?>
                                <tr>
                                    <td>
                                        <?= $i + 1 ?>
                                    </td>
                                    <td><span class="fw-bold">
                                            <?= esc($log['version']) ?>
                                        </span></td>
                                    <td>
                                        <?= date('d M Y', strtotime($log['release_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($log['is_stable']): ?>
                                            <span class="badge bg-success">Stable</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Experimental</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted">
                                            <?= nl2br(esc(mb_strimwidth($log['description'], 0, 100, "..."))) ?>
                                        </small></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= base_url('admin/changelogs/edit/' . $log['id']) ?>"
                                                class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('admin/changelogs/delete/' . $log['id']) ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin ingin menghapus changelog ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>