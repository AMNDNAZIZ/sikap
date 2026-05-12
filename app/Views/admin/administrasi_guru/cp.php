<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">📘 Capaian Pembelajaran (CP)</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/cp') ?>" method="get" class="row g-3 align-items-end">
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
                        Capaian Pembelajaran (CP) tidak dapat ditampilkan karena belum ada pemetaan kurikulum.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Pembelajaran/Mapping Kurikulum</strong> terlebih dahulu untuk menghubungkan 
                        mata pelajaran lokal dengan Mapel Master.
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
                    <p class="mb-2">
                        Hal ini terjadi karena level sekolah telah diubah, sehingga mapping lama tidak valid lagi.
                        Capaian Pembelajaran (CP) tidak dapat ditampilkan karena ketidaksesuaian jenjang.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Ulang</strong> untuk menghubungkan mata pelajaran dengan 
                        Mapel Master yang sesuai dengan level sekolah saat ini (<strong><?= esc($current_jenjang) ?></strong>).
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-danger">
                            <i class="bi bi-arrow-repeat me-1"></i> Pemetaan Ulang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (empty($cp_list)): ?>
        <div class="alert alert-info border-left-info shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Belum Ada Data CP</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> sudah di-mapping, 
                        tetapi belum ada Capaian Pembelajaran (CP) yang tersedia di Master CP.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan tambahkan data CP di <strong>Master CP</strong> untuk Mapel Master yang terkait.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/cp-master') ?>" class="btn btn-info text-white">
                            <i class="bi bi-plus-lg me-1"></i> Ke Halaman Master CP
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow mb-4 border-bottom-info">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-info"><i class="bi bi-list-ul me-1"></i> Daftar Capaian Pembelajaran (CP)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0 align-middle text-sm" style="font-size: 0.95rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th width="5%" class="text-dark">No</th>
                                <th width="15%" class="text-dark">Elemen</th>
                                <th width="10%" class="text-dark">Fase</th>
                                <th width="15%" class="text-dark">No. SK</th>
                                <th width="45%" class="text-dark">Deskripsi CP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($cp_list as $cp): ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold text-gray-600"><?= $no++ ?></td>
                                    <td class="align-middle">
                                        <span class="font-weight-bold text-info">
                                            <i class="bi bi-tag-fill me-1 small"></i> 
                                            <?= esc($cp['elemen'] ?: 'Umum') ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge rounded-pill bg-info text-white border px-3 py-2">Fase <?= esc($cp['fase']) ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="text-gray-800 small font-weight-bold"><?= esc($cp['nomor_sk']) ?></div>
                                        <div class="badge bg-light text-secondary border mt-1">TA <?= esc($cp['tahun']) ?></div>
                                    </td>
                                    <td class="align-middle p-3">
                                        <div class="text-gray-800" style="white-space: pre-wrap; line-height: 1.6; text-align: justify;"><?= esc($cp['deskripsi']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="bi bi-book text-gray-300" style="font-size: 5rem;"></i>
        </div>
        <h5 class="text-gray-500">Silakan pilih mata pelajaran untuk melihat Capaian Pembelajaran.</h5>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
