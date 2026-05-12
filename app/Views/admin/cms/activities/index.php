<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h1>
            <?= $title ?>
        </h1>
        <a href="<?= base_url('admin/cms/activities/create') ?>" class="btn btn-primary">+ Tambah Dokumentasi</a>
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
                            <th>Judul Kegiatan</th>
                            <th>Pengunggah</th>
                            <th>Tanggal</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($activities as $a): ?>
                            <tr>
                                <td>
                                    <?= $no++ ?>
                                </td>
                                <td>
                                    <img src="<?= base_url('uploads/activities/' . $a['image']) ?>" class="img-thumbnail"
                                        style="height: 60px; width: 100px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong>
                                        <?= esc($a['title']) ?>
                                    </strong>
                                    <p class="text-muted small mb-0">
                                        <?= esc($a['description']) ?>
                                    </p>
                                </td>
                                <td>
                                    <span class="text-muted small"><?= esc($a['uploader_name'] ?? 'System') ?></span>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($a['date'])) ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/cms/activities/edit/' . $a['id']) ?>"
                                        class="btn btn-sm btn-warning">✏️</a>
                                    <a href="<?= base_url('admin/cms/activities/delete/' . $a['id']) ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Hapus dokumentasi ini?')">🗑️</a>
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