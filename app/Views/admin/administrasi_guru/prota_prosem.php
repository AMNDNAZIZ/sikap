<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">🗓️ Program Tahunan & Semester</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/prota-prosem') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Kelas:</label>
                <select name="class_id" class="form-select border-primary" onchange="this.form.submit()" <?= $auto_class ? 'disabled' : '' ?>>
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
                <select name="subject_id" class="form-select border-primary" onchange="this.form.submit()" <?= empty($selected_class) ? 'disabled' : '' ?>>
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
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Perhatian!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> belum di-mapping ke Mapel Master.
            <br>
            Silakan mapping mata pelajaran ini terlebih dahulu di menu <a href="<?= base_url('admin/subjects') ?>" class="alert-link">Mata Pelajaran</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <strong>Kesalahan Mapping!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> di-mapping ke Mapel Master untuk jenjang <strong><?= esc($old_jenjang) ?></strong>, 
            tetapi sekolah Anda adalah <strong><?= esc($current_jenjang) ?></strong>.
            <br>
            Silakan perbaiki mapping di menu <a href="<?= base_url('admin/subjects') ?>" class="alert-link">Mata Pelajaran</a> atau hubungi administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php else: ?>
    <div class="row">
        <!-- Program Tahunan (PROTA) -->
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">PROGRAM TAHUNAN (PROTA)</h6>
                    <a href="<?= base_url('admin/administrasi-guru/prota/print/' . $selected_class . '/'  . $selected_subject) ?>" target="_blank" class="btn btn-sm btn-light">
                        <i class="bi bi-printer"></i> Cetak Prota
                    </a>
                </div>
                <div class="card-body bg-white p-5">
                    <!-- Document Header -->
                    <div class="text-center mb-4">
                        <h5 class="fw-bold mb-0">PROGRAM TAHUNAN (PROTA)</h5>
                        <h5 class="fw-bold">TAHUN PELAJARAN <?= date('Y') ?>/<?= date('Y') + 1 ?></h5>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td width="120">Satuan Pendidikan</td>
                                    <td width="10">:</td>
                                    <td class="fw-bold"><?= esc($school['name'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td>Mata Pelajaran</td>
                                    <td>:</td>
                                    <td class="fw-bold"><?= esc($subject['name']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <table class="table table-sm table-borderless mb-0 small ml-auto" style="width: auto; margin-left: auto;">
                                <tr>
                                    <td width="100">Kelas / Fase</td>
                                    <td width="10">:</td>
                                    <td class="fw-bold text-start"><?= esc($kelas) ?> / <?= esc($fase) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <table class="table table-bordered border-dark main-prota-table">
                        <thead class="text-center align-middle bg-light text-uppercase small fw-bold">
                            <tr>
                                <th width="60">No</th>
                                <th>Lingkup Materi / Tujuan Pembelajaran</th>
                                <th width="120">Alokasi Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $semesters = [1 => 'GANJIL', 2 => 'GENAP'];
                            $totalJp = 0;
                            foreach ($semesters as $semKey => $semName): 
                                $atpsInSem = array_filter($prota, fn($a) => $a['semester'] == $semKey);
                            ?>
                                <tr class="bg-light">
                                    <td colspan="3" class="text-center py-2 fw-bold text-primary bg-gray-100">
                                        SEMESTER <?= $semKey ?> (<?= $semName ?>)
                                    </td>
                                </tr>

                                <?php if (!empty($atpsInSem)): ?>
                                    <?php $no = 1; foreach ($atpsInSem as $atp): ?>
                                        <?php 
                                        $tps = $atp['tps'] ?? [];
                                        $totalJp += $atp['alokasi_waktu'];
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle"><?= $no++ ?></td>
                                            <td class="p-0">
                                                <div class="px-3 py-2 fw-bold border-bottom bg-white" style="font-size: 1.05rem;">
                                                    <?= esc($atp['lingkup_materi']) ?>
                                                </div>
                                                <div class="p-3">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <?php foreach ($tps as $tp): ?>
                                                            <tr>
                                                                <td width="50" class="align-top fw-bold text-muted"><?= esc($tp['kode_tp']) ?></td>
                                                                <td class="align-top"><?= esc($tp['deskripsi']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <?php if(empty($tps)): ?><tr><td>-</td></tr><?php endif; ?>
                                                    </table>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle h5 mb-0 fw-bold border-start-0">
                                                <?= $atp['alokasi_waktu'] ?> <small class="text-muted">JP</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted small italic">
                                            <i class="bi bi-info-circle"></i> Belum ada alur tujuan pembelajaran yang disusun untuk semester ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-primary text-white">
                            <tr>
                                <th colspan="2" class="text-end py-3 px-4 h6 mb-0">TOTAL ALOKASI WAKTU TAHUNAN</th>
                                <th class="text-center py-3 h5 mb-0 fw-bold"><?= $totalJp ?> JP</th>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Signature Section -->
                    <div class="row mt-5 pt-3">
                        <div class="col-6 text-center">
                            <p class="mb-0">Mengetahui,</p>
                            <p class="mb-5 pb-4">Kepala Sekolah</p>
                            <p class="mb-0 fw-bold text-decoration-underline"><?= esc($school['headmaster'] ?? '-') ?></p>
                            <p class="small">NIP. <?= esc($school['principal_nip'] ?? '-') ?></p>
                        </div>
                        <div class="col-6 text-center">
                            <p class="mb-0"><?= esc($school['city_regency'] ?? 'Indramayu') ?>, <?= date('d F Y') ?></p>
                            <p class="mb-5 pb-4">Guru Mata Pelajaran</p>
                            <p class="mb-0 fw-bold text-decoration-underline"><?= esc($teacher['name'] ?? '-') ?></p>
                            <p class="small">NIP. <?= esc($teacher['nip'] ?? '-') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Program Semester (PROSEM) -->
        <div class="col-12">
            <div class="card shadow border-left-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">PROGRAM SEMESTER (PROSEM)</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Distribusikan alokasi waktu tahunan ke dalam jadwal mingguan per semester.</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="border rounded p-4 text-center hover-shadow transition">
                                <i class="bi bi-calendar3-range text-primary mb-3" style="font-size: 3rem;"></i>
                                <h5>Semester 1 (Ganjil)</h5>
                                <p class="small text-muted mb-4">Juli - Desember</p>
                                <div class="d-flex gap-2">
                                    <?php if (!$readonly): ?>
                                    <a href="<?= base_url('admin/administrasi-guru/prosem/input/' . $selected_class . '/' . $selected_subject . '/1') ?>" class="btn btn-primary flex-grow-1">
                                        Isi Distribusi JP <i class="bi bi-arrow-right"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary flex-grow-1" disabled>Mode Baca Saja</button>
                                    <?php endif; ?>
                                    <a href="<?= base_url('admin/administrasi-guru/prosem/print/' . $selected_class . '/' . $selected_subject . '/1') ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-4 text-center hover-shadow transition">
                                <i class="bi bi-calendar3-range text-success mb-3" style="font-size: 3rem;"></i>
                                <h5>Semester 2 (Genap)</h5>
                                <p class="small text-muted mb-4">Januari - Juni</p>
                                <div class="d-flex gap-2">
                                    <?php if (!$readonly): ?>
                                    <a href="<?= base_url('admin/administrasi-guru/prosem/input/' . $selected_class . '/' . $selected_subject . '/2') ?>" class="btn btn-success flex-grow-1">
                                        Isi Distribusi JP <i class="bi bi-arrow-right"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary flex-grow-1" disabled>Mode Baca Saja</button>
                                    <?php endif; ?>
                                    <a href="<?= base_url('admin/administrasi-guru/prosem/print/' . $selected_class . '/' . $selected_subject . '/2') ?>" target="_blank" class="btn btn-outline-success">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="bi bi-calendar-event text-gray-300" style="font-size: 5rem;"></i>
        </div>
        <h5 class="text-gray-500">Silakan pilih mata pelajaran untuk menyusun Program Tahunan & Semester.</h5>
    </div>
<?php endif; ?>

<style>
.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    border-color: #4e73df!important;
}
.transition {
    transition: all 0.2s ease-in-out;
}
</style>

<?= $this->endSection() ?>
