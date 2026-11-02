<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">

    <!-- Header & Breadcrumbs Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%); color: white;">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                    <span class="badge bg-white bg-opacity-20 text-white px-2.5 py-1">
                        <i class="bi bi-bar-chart-steps me-1"></i> ANALISIS HASIL UJIAN
                    </span>
                    <span class="badge bg-success px-2.5 py-1">
                        <i class="bi bi-check2-circle me-1"></i> BERBASIS TUJUAN PEMBELAJARAN (TP)
                    </span>
                </div>
                <h4 class="fw-bold mb-1 text-white"><?= esc($test['name']) ?></h4>
                <p class="mb-0 text-white-50 small">
                    <i class="bi bi-book me-1"></i> Mata Pelajaran: <strong><?= esc($test['subject_name'] ?? 'Mata Pelajaran') ?></strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-upc me-1"></i> Kode Bank: <strong><?= esc($test['bank_code'] ?? '-') ?></strong>
                    <?php if (!empty($test['class_codes'])): ?>
                        <span class="mx-2">·</span>
                        <i class="bi bi-people me-1"></i> Sasaran Kelas: <strong><?= esc($test['class_codes']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2 align-self-start align-self-md-center flex-wrap">
                <a href="<?= base_url('admin/cbt/aktivitas') ?>" class="btn btn-light btn-sm text-primary fw-semibold shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Daftar Ujian
                </a>
                <a href="<?= base_url('admin/cbt/report/detail/' . $test['test_id']) ?>" class="btn btn-outline-light btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-journal-check me-1"></i> Rekap Nilai
                </a>
                <button type="button" class="btn btn-light btn-sm text-dark fw-semibold shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>
        </div>
    </div>

    <!-- Alert: Koreksi Esai Belum Selesai (DEC-ESS-01) -->
    <?php if (!empty($classAnalysis['has_pending_essays'])): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 10px;">
            <i class="bi bi-hourglass-split fs-4 me-3 text-warning"></i>
            <div>
                <strong class="d-block text-dark">Perhatian: Koreksi Esai Belum Selesai</strong>
                <span class="small text-muted">
                    Terdapat <strong><?= esc($classAnalysis['pending_essay_student_count']) ?> siswa</strong> yang masih menunggu penilaian esai oleh guru. 
                    Analisis penguasaan TP objektif tetap ditampilkan, namun status kelayakan akhir akan disahkan setelah seluruh esai selesai dikoreksi.
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigasi Dua Tab Analisis -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom p-2">
            <ul class="nav nav-pills card-header-pills" id="tpAnalysisTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-3 py-2 d-flex align-items-center gap-2" id="tab-siswa-btn" data-bs-toggle="tab" data-bs-target="#tab-siswa" type="button" role="tab" aria-controls="tab-siswa" aria-selected="true">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>1. Penguasaan TP Per Siswa</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= count($studentAnalysis) ?> Siswa</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-3 py-2 d-flex align-items-center gap-2" id="tab-kelas-btn" data-bs-toggle="tab" data-bs-target="#tab-kelas" type="button" role="tab" aria-controls="tab-kelas" aria-selected="false">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>2. Penguasaan TP Per Kelas</span>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill"><?= count($tpDefinitions) ?> TP</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Konten Tab Analisis -->
    <div class="tab-content" id="tpAnalysisTabContent">

        <!-- =========================================================================
             TAB 1: ANALISIS PENGUASAAN TP PER SISWA
             ========================================================================= -->
        <div class="tab-pane fade show active" id="tab-siswa" role="tabpanel" aria-labelledby="tab-siswa-btn">
            
            <!-- Ringkasan Singkat Tab Siswa -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-people fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Dasar Perhitungan Siswa</span>
                                <strong class="fs-6 text-dark"><?= esc($classAnalysis['total_evaluated']) ?> Selesai Dinilai</strong>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">dari total <?= esc($classAnalysis['total_registered']) ?> siswa terdaftar</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-check-circle fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Kriteria Ketuntasan (KKM)</span>
                                <strong class="fs-6 text-success">&ge; <?= esc($classAnalysis['default_threshold']) ?>%</strong>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Batas penguasaan materi minimal</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-3" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bullseye fs-5"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Tujuan Pembelajaran Diujikan</span>
                                <strong class="fs-6 text-dark"><?= count($tpDefinitions) ?> Butir TP</strong>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Diukur dari butir soal objektif valid</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Penguasaan TP Per Siswa -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-table me-2 text-primary"></i>Daftar Penguasaan TP Setiap Siswa
                    </h6>
                    <small class="text-muted">
                        <?= esc($classAnalysis['calculation_base_note']) ?>
                    </small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($studentAnalysis)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            Belum ada data sesi pengerjaan siswa untuk ujian ini.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3" style="width: 50px;">No</th>
                                        <th style="min-width: 180px;">Nama Siswa</th>
                                        <th style="min-width: 100px;">Status Ujian</th>
                                        <?php foreach ($tpDefinitions as $tp): ?>
                                            <th class="text-center" style="min-width: 120px;" title="<?= esc($tp['deskripsi']) ?>">
                                                <div class="fw-bold text-dark"><?= esc($tp['tp_code']) ?></div>
                                                <span class="text-muted" style="font-size: 0.7rem;">(&ge; <?= esc($tp['threshold']) ?>%)</span>
                                            </th>
                                        <?php endforeach; ?>
                                        <th style="min-width: 140px;">TP Dikuasai</th>
                                        <th style="min-width: 140px;">Perlu Peningkatan</th>
                                        <th class="text-end pe-3" style="min-width: 130px;">Tindak Lanjut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($studentAnalysis as $st): ?>
                                        <tr>
                                            <td class="ps-3 fw-mono text-muted"><?= $no++ ?></td>
                                            <td>
                                                <strong class="text-dark d-block"><?= esc($st['nama_siswa']) ?></strong>
                                                <span class="text-muted" style="font-size: 0.7rem;">
                                                    NISN: <?= esc($st['nisn']) ?> <?= !empty($st['kelas']) && $st['kelas'] !== '-' ? '· Kelas: ' . esc($st['kelas']) : '' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$st['has_attempted']): ?>
                                                    <span class="badge bg-light text-muted border">Belum Ujian</span>
                                                <?php elseif ($st['is_essay_pending']): ?>
                                                    <span class="badge bg-warning text-dark" title="Menunggu <?= esc($st['pending_essay_count']) ?> koreksi esai">
                                                        <i class="bi bi-hourglass-split me-1"></i>Menunggu Esai
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                        <i class="bi bi-check2 me-1"></i>Lengkap
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Evaluasi per TP -->
                                            <?php foreach ($tpDefinitions as $tp): ?>
                                                <?php 
                                                    $tpId = $tp['tp_id'];
                                                    $eval = $st['tp_evaluations'][$tpId] ?? null;
                                                ?>
                                                <td class="text-center">
                                                    <?php if (!$st['has_attempted']): ?>
                                                        <span class="text-muted">-</span>
                                                    <?php elseif ($eval && $eval['student_percentage'] !== null && !empty($eval['is_sufficient_evidence'])): ?>
                                                        <strong class="<?= ($eval['student_percentage'] >= ($eval['threshold'] ?? 75)) ? 'text-success' : 'text-danger' ?>">
                                                            <?= number_format($eval['student_percentage'], 1) ?>%
                                                        </strong>
                                                        <span class="badge <?= esc($eval['badge_class']) ?> d-block mx-auto mt-1" style="font-size: 0.65rem; max-width: 110px;">
                                                            <?= esc($eval['status_label']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;" title="Butir soal belum memenuhi batas kecukupan bukti">
                                                            Belum Cukup Data
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>

                                            <!-- TP Dikuasai -->
                                            <td>
                                                <?php if (!empty($st['mastered_tps'])): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                        <?= esc(implode(', ', $st['mastered_tps'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- TP Perlu Ditingkatkan -->
                                            <td>
                                                <?php if (!empty($st['needs_improvement_tps'])): ?>
                                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">
                                                        <?= esc(implode(', ', $st['needs_improvement_tps'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Rekomendasi Tindak Lanjut -->
                                            <td class="text-end pe-3">
                                                <span class="badge <?= esc($st['recommendation_badge']) ?> px-2 py-1">
                                                    <?= esc($st['recommendation']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             TAB 2: ANALISIS PENGUASAAN TP PER KELAS
             ========================================================================= -->
        <div class="tab-pane fade" id="tab-kelas" role="tabpanel" aria-labelledby="tab-kelas-btn">
            
            <!-- Kartu Sorotan Hasil Kelas (Top, Lowest, Tindak Lanjut) -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3">
                            <span class="text-muted small d-block">Siswa Dinilai Lengkap</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= esc($classAnalysis['total_evaluated']) ?></h3>
                            <small class="text-muted">dari <?= esc($classAnalysis['total_registered']) ?> siswa terdaftar</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3">
                            <span class="text-success small d-block fw-semibold">Penguasaan Tertinggi</span>
                            <?php if (!empty($classAnalysis['top_tp'])): ?>
                                <h4 class="fw-bold text-success mt-1 mb-0"><?= esc($classAnalysis['top_tp']['tp_code']) ?></h4>
                                <small class="text-muted d-block text-truncate" title="<?= esc($classAnalysis['top_tp']['deskripsi']) ?>">
                                    Tingkat Ketuntasan: <strong><?= number_format($classAnalysis['top_tp']['percentage'], 1) ?>%</strong>
                                </small>
                            <?php else: ?>
                                <h5 class="text-muted mt-2 mb-0">-</h5>
                                <small class="text-muted">Belum ada data tuntas</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3">
                            <span class="text-danger small d-block fw-semibold">Penguasaan Terendah</span>
                            <?php if (!empty($classAnalysis['lowest_tp'])): ?>
                                <h4 class="fw-bold text-danger mt-1 mb-0"><?= esc($classAnalysis['lowest_tp']['tp_code']) ?></h4>
                                <small class="text-muted d-block text-truncate" title="<?= esc($classAnalysis['lowest_tp']['deskripsi']) ?>">
                                    Tingkat Ketuntasan: <strong><?= number_format($classAnalysis['lowest_tp']['percentage'], 1) ?>%</strong>
                                </small>
                            <?php else: ?>
                                <h5 class="text-muted mt-2 mb-0">-</h5>
                                <small class="text-muted">Belum cukup data</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body p-3">
                            <span class="text-warning small d-block fw-semibold">Perlu Penguatan Kelas</span>
                            <h3 class="fw-bold text-warning mt-1 mb-0"><?= count($classAnalysis['tps_needing_reinforcement']) ?> TP</h3>
                            <small class="text-muted">Belum mencapai KKM kelas</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Identifikasi TP yang Perlu Penguatan Pembelajaran -->
            <?php if (!empty($classAnalysis['tps_needing_reinforcement'])): ?>
                <div class="card border-0 shadow-sm mb-4 border-start border-4 border-danger" style="border-radius: 10px;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-danger fs-4 mt-1">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Identifikasi TP yang Perlu Penguatan Pembelajaran</h6>
                                <p class="text-muted small mb-2">
                                    Berdasarkan hasil ujian, Tujuan Pembelajaran di bawah ini belum mencapai kriteria ketuntasan kelas (&ge; <?= esc($classAnalysis['default_threshold']) ?>%). 
                                    Disarankan untuk memberikan peninjauan materi atau penguatan dalam kegiatan belajar mengajar selanjutnya:
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($classAnalysis['tps_needing_reinforcement'] as $rf): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 small">
                                            <strong><?= esc($rf['tp_code']) ?></strong> (<?= number_format($rf['percentage'], 1) ?>%) — <?= esc($rf['deskripsi'] ?: 'Penguatan materi') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm mb-4 border-start border-4 border-success" style="border-radius: 10px;">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="text-success fs-4">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Seluruh TP Telah Mencapai Ketuntasan Kelas</h6>
                            <p class="text-muted small mb-0">
                                Seluruh Tujuan Pembelajaran yang diujikan telah dikuasai dengan baik oleh mayoritas siswa di kelas ini.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabel Evaluasi Penguasaan TP Per Kelas -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>Tingkat Penguasaan Setiap TP di Tingkat Kelas
                    </h6>
                    <small class="text-muted">
                        <?= esc($classAnalysis['calculation_base_note']) ?>
                    </small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-3" style="width: 120px;">Kode TP</th>
                                    <th style="min-width: 250px;">Deskripsi Tujuan Pembelajaran</th>
                                    <th class="text-center" style="width: 110px;">Soal Valid</th>
                                    <th class="text-center" style="width: 140px;">Penguasaan Kelas</th>
                                    <th class="text-center" style="width: 110px;">Siswa Tuntas</th>
                                    <th class="text-center" style="width: 110px;">Belum Tuntas</th>
                                    <th style="min-width: 160px;">Status Penguasaan</th>
                                    <th class="pe-3" style="min-width: 200px;">Rekomendasi Pembelajaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classAnalysis['tp_metrics'] as $tp): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">
                                            <i class="bi bi-tag text-primary me-1"></i><?= esc($tp['tp_code']) ?>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-semibold d-block"><?= esc($tp['deskripsi'] ?: '-') ?></span>
                                            <small class="text-muted" style="font-size: 0.7rem;">Ambang Ketuntasan: &ge; <?= esc($tp['threshold']) ?>%</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= esc($tp['valid_question_count']) ?> Soal</span>
                                            <small class="text-muted d-block" style="font-size: 0.68rem;">(Bobot <?= esc($tp['s_max']) ?>%)</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($tp['cr_murni'] !== null && !empty($tp['is_sufficient_evidence'])): ?>
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <strong class="fs-6 <?= ($tp['cr_murni'] >= $tp['threshold']) ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($tp['cr_murni'], 1) ?>%
                                                    </strong>
                                                </div>
                                                <div class="progress mt-1 mx-auto" style="height: 5px; max-width: 90px;">
                                                    <div class="progress-bar <?= ($tp['cr_murni'] >= $tp['threshold']) ? 'bg-success' : 'bg-danger' ?>" 
                                                         role="progressbar" 
                                                         style="width: <?= min(100, (float)$tp['cr_murni']) ?>%">
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Belum Cukup Data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-success fw-bold"><?= esc($tp['mastered_count']) ?></span>
                                            <small class="text-muted d-block" style="font-size: 0.68rem;">Siswa</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?= ($tp['not_mastered_count'] > 0) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <?= esc($tp['not_mastered_count']) ?>
                                            </span>
                                            <small class="text-muted d-block" style="font-size: 0.68rem;">Siswa</small>
                                        </td>
                                        <td>
                                            <span class="badge <?= esc($tp['badge_class']) ?> px-2.5 py-1">
                                                <?= esc($tp['status_label']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-muted small">
                                            <?= esc($tp['action_note']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .btn, nav, .card-header-pills, .alert {
        display: none !important;
    }
    .tab-pane {
        display: block !important;
        opacity: 1 !important;
        margin-bottom: 2rem;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<?= $this->endSection() ?>
