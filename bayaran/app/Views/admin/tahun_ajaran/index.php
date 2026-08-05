<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen Tahun Ajaran</h4>

<?php if (session()->getFlashdata('msg')) : ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('msg') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')) : ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>

<?php
$edit = $edit ?? null;

$formAction = $edit
    ? site_url('admin/tahun-ajaran/update/' . $edit['id'])
    : site_url('admin/tahun-ajaran/save');
?>

<form action="<?= $formAction ?>" method="post" class="row g-2 mb-4">

    <?= csrf_field(); ?>

    <?php if ($edit) : ?>
        <input type="hidden" name="id" value="<?= esc($edit['id']) ?>">
    <?php endif; ?>

    <div class="col-md-3">
        <input
            type="text"
            name="nama"
            class="form-control"
            placeholder="Contoh: 2025/2026"
            value="<?= old('nama', $edit['nama'] ?? '') ?>"
            required>
    </div>

    <div class="col-md-2">
        <input
            type="date"
            name="mulai"
            class="form-control"
            value="<?= old('mulai', $edit['mulai'] ?? '') ?>"
            required>
    </div>

    <div class="col-md-2">
        <input
            type="date"
            name="selesai"
            class="form-control"
            value="<?= old('selesai', $edit['selesai'] ?? '') ?>"
            required>
    </div>

    <div class="col-md-2">
        <input
            type="number"
            name="urutan"
            class="form-control"
            placeholder="Urutan"
            value="<?= old('urutan', $edit['urutan'] ?? '') ?>"
            required>
    </div>

    <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
            <?= $edit ? 'Update' : 'Tambah' ?>
        </button>

        <?php if ($edit) : ?>
            <a href="<?= site_url('admin/tahun-ajaran') ?>" class="btn btn-secondary w-100 mt-2">
                Batal
            </a>
        <?php endif; ?>
    </div>

</form>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th width="50">#</th>
                <th>Nama</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th width="90">Urutan</th>
                <th width="120">Status</th>
                <th width="180">Aksi</th>
            </tr>
        </thead>

        <tbody>

        <?php if (!empty($list)) : ?>

            <?php $no = 1; ?>
            <?php foreach ($list as $item) : ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td><?= esc($item['nama']) ?></td>

                    <td><?= esc($item['mulai']) ?></td>

                    <td><?= esc($item['selesai']) ?></td>

                    <td><?= esc($item['urutan']) ?></td>

                    <td>
                        <?php if ($item['aktif']) : ?>

                            <span class="badge bg-success">
                                Aktif
                            </span>

                        <?php else : ?>

                            <a href="<?= site_url('admin/tahun-ajaran/set-aktif/' . $item['id']) ?>"
                               class="btn btn-sm btn-outline-primary">
                                Set Aktif
                            </a>

                        <?php endif; ?>
                    </td>

                    <td>

                        <a href="<?= site_url('admin/tahun-ajaran/edit/' . $item['id']) ?>"
                           class="btn btn-sm btn-warning">
                            Edit
                        </a>

                        <a href="<?= site_url('admin/tahun-ajaran/delete/' . $item['id']) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Yakin ingin menghapus tahun ajaran ini?')">
                            Hapus
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else : ?>

            <tr>
                <td colspan="7" class="text-center">
                    Belum ada data tahun ajaran.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>
</div>

<?= $this->endSection() ?>