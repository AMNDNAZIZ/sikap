<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">🪜 Jenjang Master</h1>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#jenjangModal">
        <i class="bi bi-plus-lg"></i> Tambah Jenjang
    </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Kode</th>
                        <th>Nama Jenjang</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($jenjang as $j): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-secondary"><?= esc($j['kode']) ?></span></td>
                            <td><?= esc($j['nama']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white btn-edit" 
                                    data-id="<?= $j['id'] ?>" 
                                    data-kode="<?= $j['kode'] ?>" 
                                    data-nama="<?= $j['nama'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="<?= base_url('admin/jenjang-master/delete/'.$j['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus jenjang ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="jenjangModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('admin/jenjang-master/store') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="jenjang_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Jenjang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Jenjang:</label>
                        <input type="text" name="kode" id="kode" class="form-control" required placeholder="MISAL: SD, SMP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Jenjang:</label>
                        <input type="text" name="nama" id="nama" class="form-control" required placeholder="MISAL: Sekolah Dasar">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('.btn-edit').click(function() {
        const id = $(this).data('id');
        const kode = $(this).data('kode');
        const nama = $(this).data('nama');
        
        $('#jenjang_id').val(id);
        $('#kode').val(kode);
        $('#nama').val(nama);
        $('#modalTitle').text('Edit Jenjang');
        $('#jenjangModal').modal('show');
    });

    $('#jenjangModal').on('hidden.bs.modal', function () {
        $('#jenjang_id').val('');
        $('#kode').val('');
        $('#nama').val('');
        $('#modalTitle').text('Tambah Jenjang');
    });
});
</script>
<?= $this->endSection() ?>
