<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">🔗 Mapping Mata Pelajaran</h1>
        <small class="text-muted">Jenjang Sekolah: <span class="badge bg-primary"><?= esc($school_level_name ?? 'SD / Sederajat') ?></span></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('admin/administrasi-guru/cp') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-book me-1"></i> Buka Halaman CP</a>
        <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="m-0 font-weight-bold text-primary">Hubungkan Mata Pelajaran Sekolah dengan Master CP</h6>
            <small class="text-muted">Pemetaan ini menentukan master kurikulum dan Capaian Pembelajaran (CP) yang muncul pada modul administrasi guru, TP, ATP, dan KKTP.</small>
        </div>
    </div>
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/mapping/update') ?>" method="post">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="mappingTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">No</th>
                            <th width="35%">Mata Pelajaran Sekolah</th>
                            <th>Master Mata Pelajaran (CP)</th>
                            <th width="140" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($subjects as $s): 
                            $isMapped = !empty($s['mapel_master_id']);
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($s['name']) ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary border small">Kode: <?= esc($s['code']) ?></span>
                                </td>
                                <td>
                                    <select name="mapping[<?= $s['id'] ?>]" class="form-select select2-master" <?= $isReadOnly ? 'disabled' : '' ?> data-placeholder="-- Pilih Master Mapel --">
                                        <option value="">-- Pilih Master Mapel --</option>
                                        <?php foreach ($mapel_master as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= ((string)$s['mapel_master_id'] === (string)$m['id']) ? 'selected' : '' ?>>
                                                <?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <?php if ($isMapped): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i> Terhubung
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Belum Di-mapping
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$isReadOnly): ?>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Mapping
                </button>
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Pastikan semua mata pelajaran sekolah telah terhubung dengan master mapel yang sesuai.</small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-master').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
</script>

<?= $this->endSection() ?>
