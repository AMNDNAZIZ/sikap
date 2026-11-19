<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h1>
            <?= $title ?>
        </h1>
        <a href="<?= base_url('admin/cms/sliders/create') ?>" class="btn btn-primary">+ Tambah Slider</a>
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
                            <th width="150">Gambar</th>
                            <th>Judul & Deskripsi</th>
                            <th width="100">Urutan</th>
                            <th width="100">Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($sliders as $s): ?>
                            <tr>
                                <td>
                                    <?= $no++ ?>
                                </td>
                                <td>
                                    <img src="<?= base_url('uploads/sliders/' . $s['image']) ?>" class="img-thumbnail"
                                        style="height: 60px; width: 100px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong>
                                        <?= esc($s['title']) ?>
                                    </strong>
                                    <p class="text-muted small mb-0">
                                        <?= esc($s['description']) ?>
                                    </p>
                                </td>
                                <td>
                                    <?= $s['order'] ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $s['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/cms/sliders/edit/' . $s['id']) ?>"
                                        class="btn btn-sm btn-warning">✏️</a>
                                    <form action="<?= base_url('admin/cms/sliders/delete/' . $s['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus slider ini?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                    </form>
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