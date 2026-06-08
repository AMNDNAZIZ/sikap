<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h1>
            <?= $title ?>
        </h1>
        <a href="<?= base_url('admin/cms/articles/create') ?>" class="btn btn-primary">+ Tambah Artikel</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="120">Gambar</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Tanggal Buat</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($articles as $a): ?>
                            <tr>
                                <td>
                                    <?= $no++ ?>
                                </td>
                                <td>
                                    <?php if ($a['image']): ?>
                                        <img src="<?= base_url('uploads/articles/' . $a['image']) ?>" class="img-thumbnail"
                                            style="height: 50px; width: 70px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>
                                        <?= esc($a['title']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="text-muted small"><?= esc($a['author_name'] ?? 'System') ?></span>
                                </td>
                                <td>
                                    <?= esc($a['category']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $a['is_published'] ? 'success' : 'secondary' ?>">
                                        <?= $a['is_published'] ? 'Publish' : 'Draft' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($a['created_at'])) ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/cms/articles/edit/' . $a['id']) ?>"
                                        class="btn btn-sm btn-warning">✏️</a>
                                    <a href="<?= base_url('admin/cms/articles/delete/' . $a['id']) ?>"
                                        class="btn btn-sm btn-danger" onclick="return confirm('Hapus artikel ini?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>