<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Master Data Ekstrakurikuler</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Ekskul
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Ekskul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ekskul as $i => $e) : ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($e['kode']) ?></td>
                                <td><?= esc($e['name']) ?></td>
                                <td>
                                    <?php if ($e['category'] == 'wajib'): ?>
                                        <span class="badge bg-danger">Wajib</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Pilihan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($e['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/ekskul/pembina/' . $e['id']) ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-users"></i> Pembina
                                    </a>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $e['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form action="<?= base_url('admin/ekskul/delete/' . $e['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus ekskul ini?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
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

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Ekstrakurikuler</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/ekskul/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Ekskul</label>
                        <input type="text" name="kode" class="form-control" required placeholder="Contoh: EKS-01">
                    </div>
                    <div class="form-group">
                        <label>Nama Ekskul</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" class="form-control" required>
                            <option value="pilihan">Pilihan</option>
                            <option value="wajib">Wajib</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals Edit -->
<?php foreach ($ekskul as $e) : ?>
<div class="modal fade" id="editModal<?= $e['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Ekstrakurikuler</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/ekskul/update/' . $e['id']) ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Ekskul</label>
                        <input type="text" name="kode" class="form-control" value="<?= esc($e['kode']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Ekskul</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($e['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" class="form-control" required>
                            <option value="pilihan" <?= $e['category'] == 'pilihan' ? 'selected' : '' ?>>Pilihan</option>
                            <option value="wajib" <?= $e['category'] == 'wajib' ? 'selected' : '' ?>>Wajib</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"><?= esc($e['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="switchStatus<?= $e['id'] ?>" name="is_active" value="1" <?= $e['is_active'] ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="switchStatus<?= $e['id'] ?>">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?= $this->endSection() ?>
