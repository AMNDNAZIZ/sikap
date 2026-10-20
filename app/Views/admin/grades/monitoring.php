<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Custom Styling for Tasteful App Screen -->
<style>
.tabular-nums {
    font-variant-numeric: tabular-nums;
}
.kpi-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #ffffff;
    transition: border-color 0.15s ease-in-out;
}
.kpi-card:hover {
    border-color: #cbd5e1;
}
.kpi-label {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}
.kpi-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.kpi-subtext {
    font-size: 0.75rem;
    color: #64748b;
}
.table-dense th {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #475569;
    padding: 0.6rem 0.75rem;
}
.table-dense td {
    font-size: 0.85rem;
    padding: 0.6rem 0.75rem;
    vertical-align: middle;
}
.progress-micro {
    height: 7px;
    border-radius: 9999px;
    background-color: #e2e8f0;
    overflow: hidden;
}
.chart-container-box {
    position: relative;
    width: 170px;
    height: 170px;
    margin: 0 auto;
}
/* Select2 filter styling */
.select2-container--default .select2-results > .select2-results__options {
    max-height: 280px;
    overflow-y: auto;
}
.select2-container .select2-selection--single {
    height: calc(1.5em + 0.5rem + 2px);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(1.5em + 0.5rem);
    font-size: 0.875rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.5rem);
}
.badge-status-complete {
    background-color: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.badge-status-partial {
    background-color: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}
.badge-status-empty {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
</style>

<!-- Header & Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h4 mb-0 text-gray-900 font-weight-bold">Monitoring Input Nilai Guru</h1>
            <span class="badge bg-primary px-2.5 py-1 text-white" style="font-size: 0.75rem;">
                TA <?= esc($active_year['year'] ?? '-') ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            Pantau keterisian asesmen formatif, sumatif, dan nilai rapor oleh dewan guru aktif pada tahun pelajaran berjalan.
        </p>
    </div>

    <!-- Right Controls: Semester Switcher & Back -->
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm p-0.5 bg-light border rounded" role="group" aria-label="Pilih Semester">
            <a href="<?= base_url('admin/grades/monitoring?semester=1' . ($filter_teacher ? '&teacher_id='.$filter_teacher : '') . ($filter_class ? '&class_id='.$filter_class : '') . ($filter_subject ? '&subject_id='.$filter_subject : '')) ?>" 
                class="btn <?= $semester == 1 ? 'btn-white shadow-sm fw-bold text-primary' : 'btn-light text-muted' ?>" style="font-size: 0.8rem;">
                Semester 1 (Ganjil)
            </a>
            <a href="<?= base_url('admin/grades/monitoring?semester=2' . ($filter_teacher ? '&teacher_id='.$filter_teacher : '') . ($filter_class ? '&class_id='.$filter_class : '') . ($filter_subject ? '&subject_id='.$filter_subject : '')) ?>" 
                class="btn <?= $semester == 2 ? 'btn-white shadow-sm fw-bold text-primary' : 'btn-light text-muted' ?>" style="font-size: 0.8rem;">
                Semester 2 (Genap)
            </a>
        </div>
        <a href="<?= base_url('admin/grades') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Rekap Nilai
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- ══ 1. EXECUTIVE OVERVIEW (TYPE-FIRST KPIS) ══ -->
<?php
$totalPlot = $kpi['total_plotting'] ?? 0;
$pctF = $totalPlot > 0 ? round(($kpi['complete_formatif'] / $totalPlot) * 100) : 0;
$pctS = $totalPlot > 0 ? round(($kpi['complete_sumatif'] / $totalPlot) * 100) : 0;
$pctR = $totalPlot > 0 ? round(($kpi['complete_rapor'] / $totalPlot) * 100) : 0;
$pctA = $totalPlot > 0 ? round(($kpi['complete_all'] / $totalPlot) * 100) : 0;
$totTeach = $kpi['total_teachers'] ?? 0;
$pctT = $totTeach > 0 ? round(($kpi['complete_teachers'] / $totTeach) * 100) : 0;
?>

<div class="row g-3 mb-4">
    <!-- KPI 1: Beban Plotting -->
    <div class="col-6 col-lg-auto flex-grow-1">
        <div class="kpi-card p-3 h-100">
            <div class="kpi-label mb-1">Total Plotting KBM</div>
            <div class="kpi-value tabular-nums"><?= $totalPlot ?></div>
            <div class="kpi-subtext mt-1"><?= $totTeach ?> Guru Aktif Mengajar</div>
        </div>
    </div>
    <!-- KPI 2: Formatif -->
    <div class="col-6 col-lg-auto flex-grow-1">
        <div class="kpi-card p-3 h-100">
            <div class="kpi-label mb-1">Formatif Selesai</div>
            <div class="kpi-value tabular-nums text-success"><?= $kpi['complete_formatif'] ?></div>
            <div class="kpi-subtext mt-1"><span class="fw-semibold text-success"><?= $pctF ?>%</span> dari total plotting</div>
        </div>
    </div>
    <!-- KPI 3: Sumatif -->
    <div class="col-6 col-lg-auto flex-grow-1">
        <div class="kpi-card p-3 h-100">
            <div class="kpi-label mb-1">Sumatif Selesai</div>
            <div class="kpi-value tabular-nums text-primary"><?= $kpi['complete_sumatif'] ?></div>
            <div class="kpi-subtext mt-1"><span class="fw-semibold text-primary"><?= $pctS ?>%</span> dari total plotting</div>
        </div>
    </div>
    <!-- KPI 4: Nilai Rapor -->
    <div class="col-6 col-lg-auto flex-grow-1">
        <div class="kpi-card p-3 h-100">
            <div class="kpi-label mb-1">Nilai Rapor Siap</div>
            <div class="kpi-value tabular-nums text-info"><?= $kpi['complete_rapor'] ?></div>
            <div class="kpi-subtext mt-1"><span class="fw-semibold text-info"><?= $pctR ?>%</span> dari total plotting</div>
        </div>
    </div>
    <!-- KPI 5: Guru Tuntas Penuh -->
    <div class="col-12 col-lg-auto flex-grow-1">
        <div class="kpi-card p-3 h-100" style="border-left: 3px solid #10b981;">
            <div class="kpi-label mb-1">Guru Tuntas Penuh</div>
            <div class="kpi-value tabular-nums text-success"><?= $kpi['complete_teachers'] ?> <span class="text-muted fw-normal" style="font-size: 1rem;">/ <?= $totTeach ?></span></div>
            <div class="kpi-subtext mt-1"><span class="fw-semibold text-success"><?= $pctT ?>%</span> dewan guru selesai 100%</div>
        </div>
    </div>
</div>

<!-- ══ 2. DISTRIBUSI CHARTS (4 DONUT METERS) ══ -->
<div class="row g-3 mb-4">
    <!-- Chart 1: Formatif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border h-100 shadow-none">
            <div class="card-header bg-white py-2.5 px-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.85rem;">
                    <i class="bi bi-pie-chart-fill text-success me-1"></i> Nilai Formatif (ATP)
                </h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <?php if ($totalPlot > 0): ?>
                    <div class="chart-container-box">
                        <canvas id="formatifChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center g-1">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success tabular-nums" style="font-size: 1rem;"><?= $formatif_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Selesai</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning tabular-nums" style="font-size: 1rem;"><?= $formatif_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Sebagian</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger tabular-nums" style="font-size: 1rem;"><?= $formatif_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Kosong</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0 small">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart 2: Sumatif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border h-100 shadow-none">
            <div class="card-header bg-white py-2.5 px-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.85rem;">
                    <i class="bi bi-pie-chart-fill text-primary me-1"></i> Nilai Sumatif
                </h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <?php if ($totalPlot > 0): ?>
                    <div class="chart-container-box">
                        <canvas id="sumatifChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center g-1">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success tabular-nums" style="font-size: 1rem;"><?= $sumatif_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Selesai</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning tabular-nums" style="font-size: 1rem;"><?= $sumatif_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Sebagian</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger tabular-nums" style="font-size: 1rem;"><?= $sumatif_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Kosong</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0 small">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart 3: Nilai Rapor -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border h-100 shadow-none">
            <div class="card-header bg-white py-2.5 px-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.85rem;">
                    <i class="bi bi-pie-chart-fill text-info me-1"></i> Nilai Rapor &amp; Deskripsi
                </h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <?php if ($totalPlot > 0): ?>
                    <div class="chart-container-box">
                        <canvas id="raporChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center g-1">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success tabular-nums" style="font-size: 1rem;"><?= $rapor_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Selesai</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning tabular-nums" style="font-size: 1rem;"><?= $rapor_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Sebagian</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger tabular-nums" style="font-size: 1rem;"><?= $rapor_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Kosong</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0 small">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart 4: Overall Progress -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border h-100 shadow-none">
            <div class="card-header bg-white py-2.5 px-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.85rem;">
                    <i class="bi bi-pie-chart-fill text-secondary me-1"></i> Status Tuntas Penuh
                </h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <?php if ($totalPlot > 0): ?>
                    <div class="chart-container-box">
                        <canvas id="overallChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center g-1">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success tabular-nums" style="font-size: 1rem;"><?= $overall_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Tuntas</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning tabular-nums" style="font-size: 1rem;"><?= $overall_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Proses</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger tabular-nums" style="font-size: 1rem;"><?= $overall_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Belum</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted"><p class="mb-0 small">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ 3. RINGKASAN PER GURU AKTIF (TEACHER PROGRESS) ══ -->
<?php if (!empty($teacher_summary)): ?>
<div class="card border mb-4 shadow-none">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="m-0 font-weight-bold text-dark">
                <i class="bi bi-people-fill text-primary me-1"></i> Ringkasan Progres Guru Aktif
            </h6>
            <span class="text-muted text-xs">Diurutkan dari capaian progres tertinggi ke terendah (DESC)</span>
        </div>
        <span class="badge bg-light text-dark border tabular-nums"><?= count($teacher_summary) ?> Guru Aktif</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-dense mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="40" class="text-center">No</th>
                        <th>Nama Guru</th>
                        <th width="115" class="text-center">Beban Mengajar</th>
                        <th width="100" class="text-center">Formatif</th>
                        <th width="100" class="text-center">Sumatif</th>
                        <th width="100" class="text-center">Rapor</th>
                        <th width="160">Progres Total</th>
                        <th width="115" class="text-center">Status</th>
                        <th width="80" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tNo = 1; 
                    foreach ($teacher_summary as $tid => $ts): 
                        $pct = (int) $ts['progress_pct'];
                        $barColor = ($pct >= 100) ? 'bg-success' : (($pct > 0) ? 'bg-warning' : 'bg-danger');
                    ?>
                    <tr>
                        <td class="text-center text-muted tabular-nums"><?= $tNo++ ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($ts['name']) ?></div>
                            <div class="text-muted text-xs">
                                NIP: <?= esc($ts['nip']) ?>
                                <?php if (!empty($ts['phone'])): ?>
                                    &bull; <i class="bi bi-telephone text-xs"></i> <?= esc($ts['phone']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center tabular-nums">
                            <span class="fw-bold text-dark"><?= $ts['total_assignments'] ?> <?= $ts['unit_label'] ?></span>
                            <div class="text-xs text-muted"><?= $ts['role_label'] ?></div>
                        </td>
                        <td class="text-center tabular-nums">
                            <span class="badge <?= $ts['f_complete'] == $ts['total_assignments'] ? 'badge-status-complete' : ($ts['f_complete'] > 0 ? 'badge-status-partial' : 'badge-status-empty') ?> px-2 py-1">
                                <?= $ts['f_complete'] ?> / <?= $ts['total_assignments'] ?>
                            </span>
                        </td>
                        <td class="text-center tabular-nums">
                            <span class="badge <?= $ts['s_complete'] == $ts['total_assignments'] ? 'badge-status-complete' : ($ts['s_complete'] > 0 ? 'badge-status-partial' : 'badge-status-empty') ?> px-2 py-1">
                                <?= $ts['s_complete'] ?> / <?= $ts['total_assignments'] ?>
                            </span>
                        </td>
                        <td class="text-center tabular-nums">
                            <span class="badge <?= $ts['r_complete'] == $ts['total_assignments'] ? 'badge-status-complete' : ($ts['r_complete'] > 0 ? 'badge-status-partial' : 'badge-status-empty') ?> px-2 py-1">
                                <?= $ts['r_complete'] ?> / <?= $ts['total_assignments'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress progress-micro flex-grow-1">
                                    <div class="progress-bar <?= $barColor ?>" style="width: <?= $pct ?>%;"></div>
                                </div>
                                <span class="text-xs fw-bold tabular-nums text-muted" style="min-width: 32px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($pct >= 100): ?>
                                <span class="badge badge-status-complete">✅ Tuntas</span>
                            <?php elseif ($pct === 0): ?>
                                <span class="badge badge-status-empty">❌ Belum Mulai</span>
                            <?php else: ?>
                                <span class="badge badge-status-partial">🔄 Sedang Input</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('admin/grades/monitoring?semester=' . $semester . '&teacher_id=' . $tid) ?>#monitoringGrid" 
                               class="btn btn-xs btn-outline-primary px-2.5 py-1" 
                               title="Lihat rincian plotting guru ini" 
                               style="font-size: 0.75rem;">
                                <i class="bi bi-search me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══ 4. FILTER PLOTTING PENGAJARAN ══ -->
<div class="card border mb-4 shadow-none">
    <div class="card-header bg-white py-2.5 px-3">
        <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.85rem;">
            <i class="bi bi-funnel me-1 text-primary"></i> Filter Plotting Penilaian
        </h6>
    </div>
    <div class="card-body p-3">
        <form action="<?= base_url('admin/grades/monitoring') ?>" method="get" class="row g-2 align-items-end">
            <input type="hidden" name="semester" value="<?= $semester ?>">
            
            <div class="col-md-3">
                <label for="teacher_id" class="form-label text-xs fw-bold text-muted mb-1">Nama Guru Aktif</label>
                <select name="teacher_id" id="teacher_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Guru Aktif --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filter_teacher == $t['id'] ? 'selected' : '' ?>>
                            <?= esc($t['name']) ?> (NIP: <?= esc($t['nip'] ?: '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="class_id" class="form-label text-xs fw-bold text-muted mb-1">Kelas / Rombel</label>
                <select name="class_id" id="class_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filter_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="subject_id" class="form-label text-xs fw-bold text-muted mb-1">Mata Pelajaran (Non-BK)</label>
                <select name="subject_id" id="subject_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="status" class="form-label text-xs fw-bold text-muted mb-1">Status Nilai</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="complete" <?= $filter_status == 'complete' ? 'selected' : '' ?>>✅ Selesai (100%)</option>
                    <option value="partial" <?= $filter_status == 'partial' ? 'selected' : '' ?>>🔄 Sedang Input</option>
                    <option value="empty" <?= $filter_status == 'empty' ? 'selected' : '' ?>>❌ Kosong (0%)</option>
                </select>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
                    <i class="bi bi-search"></i>
                </button>
                <a href="<?= base_url('admin/grades/monitoring?semester=' . $semester) ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ══ 5. STANDAR KELENGKAPAN PENILAIAN (INFO CALLOUT) ══ -->
<div class="card border mb-4 shadow-none bg-light">
    <div class="card-body p-3">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill text-primary mt-0.5"></i>
            <div>
                <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.85rem;">Standar Kriteria Kelengkapan Penginputan Nilai</h6>
                <div class="row g-2 text-muted" style="font-size: 0.8rem;">
                    <div class="col-md-4">
                        <strong class="text-dark">Nilai Formatif (ATP):</strong> Dinyatakan <strong>Selesai</strong> jika materi ATP telah terisi di semester ini dan &ge; 90% siswa aktif telah dinilai.
                    </div>
                    <div class="col-md-4">
                        <strong class="text-dark">Nilai Sumatif:</strong> Dinyatakan <strong>Selesai</strong> jika asesmen sumatif (Tulis, Lisan, Projek, STS, SAS) telah terisi untuk &ge; 90% siswa.
                    </div>
                    <div class="col-md-4">
                        <strong class="text-dark">Nilai Rapor &amp; Deskripsi:</strong> Dinyatakan <strong>Selesai</strong> jika nilai akhir rapor dan deskripsi capaian telah lengkap 100% siswa.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ 6. GRID HASIL MONITORING PLOTTING ══ -->
<div class="card border shadow-none" id="monitoringGrid">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="m-0 font-weight-bold text-dark">
                <i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Rincian Plotting Penilaian Kelas &amp; Mapel
            </h6>
            <span class="text-muted text-xs">Menampilkan <?= count($assignments) ?> penugasan dari filter saat ini</span>
        </div>
        <span class="badge bg-light text-dark border">Semester <?= $semester ?> (<?= $semester == 1 ? 'Ganjil' : 'Genap' ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-dense mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="40" class="text-center">No</th>
                        <th>Guru Pengampu</th>
                        <th width="100" class="text-center">Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th width="190">Formatif (ATP)</th>
                        <th width="170">Sumatif</th>
                        <th width="180">Nilai Rapor</th>
                        <th width="100" class="text-center">Status</th>
                        <th width="80" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x display-6 d-block mb-2 text-gray-400"></i>
                                Tidak ada data plotting pengajaran yang sesuai dengan kriteria filter saat ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($assignments as $row): ?>
                        <tr>
                            <td class="text-center text-muted tabular-nums"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($row['teacher_name']) ?></div>
                                <div class="text-muted text-xs">NIP: <?= esc($row['teacher_nip'] ?: '-') ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-semibold"><?= esc($row['class_name']) ?></span>
                            </td>
                            <td>
                                <span class="fw-medium text-dark"><?= esc($row['subject_name']) ?></span>
                            </td>

                            <!-- Formatif -->
                            <td>
                                <?php if ($row['formatif_status'] === 'complete'): ?>
                                    <span class="badge badge-status-complete d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> <?= esc($row['formatif_info']) ?>
                                    </span>
                                <?php elseif ($row['formatif_status'] === 'partial'): ?>
                                    <span class="badge badge-status-partial d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-hourglass-split me-1"></i> <?= esc($row['formatif_info']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-status-empty d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-x-circle me-1"></i> <?= esc($row['formatif_info']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Sumatif -->
                            <td>
                                <?php if ($row['sumatif_status'] === 'complete'): ?>
                                    <span class="badge badge-status-complete d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> <?= esc($row['sumatif_info']) ?>
                                    </span>
                                <?php elseif ($row['sumatif_status'] === 'partial'): ?>
                                    <span class="badge badge-status-partial d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-hourglass-split me-1"></i> <?= esc($row['sumatif_info']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-status-empty d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-x-circle me-1"></i> <?= esc($row['sumatif_info']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Rapor -->
                            <td>
                                <?php if ($row['rapor_status'] === 'complete'): ?>
                                    <span class="badge badge-status-complete d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> <?= esc($row['rapor_info']) ?>
                                    </span>
                                <?php elseif ($row['rapor_status'] === 'partial'): ?>
                                    <span class="badge badge-status-partial d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-hourglass-split me-1"></i> <?= esc($row['rapor_info']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-status-empty d-inline-block text-start py-1 px-2">
                                        <i class="bi bi-x-circle me-1"></i> <?= esc($row['rapor_info']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Overall Status -->
                            <td class="text-center">
                                <?php if ($row['overall_status'] === 'complete'): ?>
                                    <span class="badge bg-success">Tuntas</span>
                                <?php elseif ($row['overall_status'] === 'partial'): ?>
                                    <span class="badge bg-warning text-dark">Proses</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Kosong</span>
                                <?php endif; ?>
                            </td>

                            <!-- Action -->
                            <td class="text-center">
                                <a href="<?= base_url('admin/grades/show/' . $row['class_id'] . '/' . $row['subject_id'] . '/' . $semester) ?>" 
                                   class="btn btn-xs btn-outline-primary px-2 py-1" 
                                   title="Buka Lembar Nilai Kelas Ini" 
                                   style="font-size: 0.75rem;">
                                    <i class="bi bi-eye"></i> Periksa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-filter').select2({
            width: '100%',
            placeholder: 'Semua / Pilih...',
            allowClear: true
        });
    }

    <?php if ($totalPlot > 0): ?>
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) label += ': ';
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return label + value + ' (' + percentage + '%)';
                    }
                }
            }
        },
        cutout: '70%'
    };

    // 1. Formatif Chart
    const fCtx = document.getElementById('formatifChart');
    if (fCtx) {
        new Chart(fCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Sebagian', 'Kosong'],
                datasets: [{
                    data: [<?= $formatif_stats['complete'] ?>, <?= $formatif_stats['partial'] ?>, <?= $formatif_stats['empty'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverBackgroundColor: ['#059669', '#d97706', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: chartOptions
        });
    }

    // 2. Sumatif Chart
    const sCtx = document.getElementById('sumatifChart');
    if (sCtx) {
        new Chart(sCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Sebagian', 'Kosong'],
                datasets: [{
                    data: [<?= $sumatif_stats['complete'] ?>, <?= $sumatif_stats['partial'] ?>, <?= $sumatif_stats['empty'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverBackgroundColor: ['#059669', '#d97706', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: chartOptions
        });
    }

    // 3. Rapor Chart
    const rCtx = document.getElementById('raporChart');
    if (rCtx) {
        new Chart(rCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Sebagian', 'Kosong'],
                datasets: [{
                    data: [<?= $rapor_stats['complete'] ?>, <?= $rapor_stats['partial'] ?>, <?= $rapor_stats['empty'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverBackgroundColor: ['#059669', '#d97706', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: chartOptions
        });
    }

    // 4. Overall Chart
    const oCtx = document.getElementById('overallChart');
    if (oCtx) {
        new Chart(oCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Tuntas', 'Proses', 'Belum'],
                datasets: [{
                    data: [<?= $overall_stats['complete'] ?>, <?= $overall_stats['partial'] ?>, <?= $overall_stats['empty'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverBackgroundColor: ['#059669', '#d97706', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: chartOptions
        });
    }
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>

