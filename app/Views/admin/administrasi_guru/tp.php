<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">🎯 Tujuan Pembelajaran (TP)</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/tp') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Kelas:</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()" <?= $auto_class ? 'disabled' : '' ?>>
                    <?php if (!$auto_class): ?><option value="">-- Pilih Kelas --</option><?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($auto_class): ?>
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Mata Pelajaran:</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($selected_class) ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_subject): ?>
    <?php if ($subject_not_mapped): ?>
        <div class="alert alert-warning border-left-warning shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Mata Pelajaran Belum Di-mapping</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> belum dihubungkan dengan Mapel Master (CP). 
                        Tujuan Pembelajaran (TP) tidak dapat ditampilkan karena belum ada pemetaan kurikulum.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Pembelajaran/Mapping Kurikulum</strong> terlebih dahulu.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-warning">
                            <i class="bi bi-link-45deg me-1"></i> Ke Halaman Mapping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger border-left-danger shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-x-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Mapping Tidak Sesuai dengan Level Sekolah</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> di-mapping ke Mapel Master dengan jenjang 
                        <strong><?= esc($old_jenjang) ?></strong>, tetapi level sekolah saat ini adalah 
                        <strong><?= esc($current_jenjang) ?></strong>.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Ulang</strong> sesuai level sekolah saat ini.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-danger">
                            <i class="bi bi-arrow-repeat me-1"></i> Pemetaan Ulang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (empty($tp_list)): ?>
        <div class="alert alert-info border-left-info shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Belum Ada Data TP</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> sudah di-mapping, 
                        tetapi belum ada Tujuan Pembelajaran (TP) yang dianalisis.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan mulai analisis TP melalui menu <strong>Analisis ATP</strong>.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/atp?subject_id='.$selected_subject.'&class_id='.$selected_class) ?>" class="btn btn-info text-white">
                            <i class="bi bi-plus-lg me-1"></i> Mulai Analisis di ATP
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
    <div class="row">
        <!-- Pustaka TP (Read-only) -->
        <div class="col-12">
            <div class="card shadow mb-4 border-left-info">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info"><i class="bi bi-journal-bookmark-fill me-2"></i> Pustaka Tujuan Pembelajaran (Reference Only)</h6>
                    <?php if (!$readonly): ?>
                    <a href="<?= base_url('admin/administrasi-guru/atp?subject_id='.$selected_subject.'&class_id='.$selected_class) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Mulai Analisis di ATP
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="bi bi-info-circle me-2"></i> Halaman ini menampilkan kumpulan Target/Tujuan Pembelajaran yang telah dirumuskan. Proses penambahan atau perubahan TP dilakukan secara terpadu melalui menu <strong>Analisis ATP</strong> untuk menjaga kesinambungan alur bahasan.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="80" class="text-center">Kode</th>
                                    <th>Elemen CP</th>
                                    <th>Lingkup Materi</th>
                                    <th width="100" class="text-center">Fase/Lvl</th>
                                    <th>Deskripsi Tujuan Pembelajaran (TP)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tp_list)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data TP yang dianalisis.</td></tr>
                                <?php else: ?>
                                    <?php 
                                    $current_elemen = '';
                                    foreach ($tp_list as $tp): 
                                    ?>
                                        <?php if ($current_elemen != $tp['elemen']): ?>
                                            <tr class="table-secondary">
                                                <td colspan="5" class="fw-bold py-2"><i class="bi bi-tag-fill me-2"></i> ELEMEN: <?= esc($tp['elemen'] ?: 'Umum/Lainnya') ?></td>
                                            </tr>
                                            <?php $current_elemen = $tp['elemen']; ?>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="text-center text-primary fw-bold"><?= esc($tp['kode_tp']) ?></td>
                                            <td class="small text-muted"><?= esc($tp['elemen'] ?: '-') ?></td>
                                            <td><strong><?= esc($tp['lingkup_materi'] ?: '-') ?></strong></td>
                                            <td class="text-center"><?= esc($tp['fase']) ?> / <?= esc($tp['kelas']) ?></td>
                                            <td class="small lh-base"><?= esc($tp['deskripsi']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="text-center py-5 shadow-sm bg-white rounded">
        <div class="mb-3 text-gray-300">
            <i class="bi bi-bullseye" style="font-size: 5rem;"></i>
        </div>
        <h5 class="text-gray-500">Silakan pilih mata pelajaran untuk mulai menyusun Tujuan Pembelajaran.</h5>
    </div>
<?php endif; ?> <!-- End of if selected_subject -->

<?= $this->endSection() ?>
