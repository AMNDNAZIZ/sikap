<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">🔗 Mapping Mata Pelajaran</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Hubungkan Mata Pelajaran Sekolah dengan Master CP</h6>
    </div>
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/mapping/update') ?>" method="post">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="mappingTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Mata Pelajaran Sekolah</th>
                            <th>Master Mata Pelajaran (CP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($subjects as $s): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= esc($s['name']) ?></strong><br>
                                    <small class="text-muted">Kode: <?= esc($s['code']) ?></small>
                                </td>
                                <td>
                                    <select name="mapping[<?= $s['id'] ?>]" class="form-select form-select-sm select2-master" <?= $isReadOnly ? 'disabled' : '' ?>>
                                        <option value="">-- Pilih Master Mapel --</option>
                                        <?php foreach ($mapel_master as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $s['mapel_master_id'] == $m['id'] ? 'selected' : '' ?>>
                                                <?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$isReadOnly): ?>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Simpan Mapping</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Optional: add select2 if available
    if ($.fn.select2) {
        $('.select2-master').select2({
            theme: 'bootstrap-5'
        });
    }
});
</script>

<?= $this->endSection() ?>
