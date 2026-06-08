<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h1>
            <?= $title ?>
        </h1>
        <a href="<?= base_url('admin/cms/facilities/create') ?>" class="btn btn-primary">+ Tambah Fasilitas</a>
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
                            <th>Nama Fasilitas</th>
                            <th>Deskripsi</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($facilities as $f): ?>
                            <tr>
                                <td>
                                    <?= $no++ ?>
                                </td>
                                <td>
                                    <?php if ($f['image']): ?>
                                        <img src="<?= base_url('uploads/facilities/' . $f['image']) ?>" class="img-thumbnail"
                                            style="height: 60px; width: 100px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>
                                        <?= esc($f['name']) ?>
                                    </strong></td>
                                <td>
                                    <?= esc($f['description']) ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/cms/facilities/edit/' . $f['id']) ?>"
                                        class="btn btn-sm btn-warning">✏️</a>
                                    <a href="<?= base_url('admin/cms/facilities/delete/' . $f['id']) ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Hapus fasilitas ini?')">🗑️</a>
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