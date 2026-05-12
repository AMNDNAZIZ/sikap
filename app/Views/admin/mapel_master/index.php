<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📚 Mapel Master (CP)</h1>
        <small class="text-muted">Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span></small>
    </div>
    <?php if (!$isReadOnly): ?>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mapelModal">
        <i class="bi bi-plus-lg"></i> Tambah Mapel Master
    </button>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
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
                        <th>Jenjang</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Kelompok</th>
                        <?php if (!$isReadOnly): ?>
                        <th width="120">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($mapel as $m): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= esc($m['jenjang_nama']) ?></td>
                            <td><span class="badge bg-info text-white"><?= esc($m['kode']) ?></span></td>
                            <td><?= esc($m['nama']) ?></td>
                            <td><?= esc($m['kelompok']) ?></td>
                            <?php if (!$isReadOnly): ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white btn-edit" 
                                    data-id="<?= $m['id'] ?>" 
                                    data-jenjang="<?= $m['jenjang_id'] ?>" 
                                    data-kode="<?= $m['kode'] ?>" 
                                    data-nama="<?= $m['nama'] ?>"
                                    data-kelompok="<?= $m['kelompok'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="<?= base_url('admin/mapel-master/delete/'.$m['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus mapel master ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mapelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('admin/mapel-master/store') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="mapel_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Mapel Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden field for jenjang_id (auto-filled based on school level) -->
                    <input type="hidden" name="jenjang_id" id="jenjang_id" value="<?= $school_level ?>">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Jenjang otomatis disesuaikan dengan level sekolah: <strong><?= esc($school_level_name) ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kode Mapel:</label>
                        <input type="text" name="kode" id="kode" class="form-control" required placeholder="MISAL: MTK, IPA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Pelajaran:</label>
                        <input type="text" name="nama" id="nama" class="form-control" required placeholder="MISAL: Matematika">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelompok:</label>
                        <input type="text" name="kelompok" id="kelompok" class="form-control" placeholder="MISAL: A, B, Pilihan">
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
        const jenjang = $(this).data('jenjang');
        const kode = $(this).data('kode');
        const nama = $(this).data('nama');
        const kelompok = $(this).data('kelompok');
        
        $('#mapel_id').val(id);
        $('#jenjang_id').val(jenjang); // This will be the school level
        $('#kode').val(kode);
        $('#nama').val(nama);
        $('#kelompok').val(kelompok);
        $('#modalTitle').text('Edit Mapel Master');
        $('#mapelModal').modal('show');
    });

    $('#mapelModal').on('hidden.bs.modal', function () {
        $('#mapel_id').val('');
        $('#jenjang_id').val('<?= $school_level ?>'); // Reset to school level
        $('#kode').val('');
        $('#nama').val('');
        $('#kelompok').val('');
        $('#modalTitle').text('Tambah Mapel Master');
    });
});
</script>
<?= $this->endSection() ?>
