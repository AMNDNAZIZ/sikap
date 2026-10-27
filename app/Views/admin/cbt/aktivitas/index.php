<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
// Hitung jumlah status ujian
$totalTests = is_array($sessions) ? count($sessions) : 0;
$ongoingCount = 0;
$finishedCount = 0;
$upcomingCount = 0;

foreach ($sessions as $s) {
    if ($s['status'] === 'ongoing') {
        $ongoingCount++;
    } elseif ($s['status'] === 'finished') {
        $finishedCount++;
    } else {
        $upcomingCount++;
    }
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    }

    /* Live Monitoring Badge Animation */
    .pulse-live {
        animation: pulseLiveBlink 1.8s infinite ease-in-out;
    }
    .live-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #ffffff;
    }
    @keyframes pulseLiveBlink {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.65; transform: scale(1.06); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* Modern Page Header */
    .page-header {
        background: var(--primary-gradient) !important;
        color: white !important;
        padding: 1.25rem 1.5rem !important;
        border-radius: 14px !important;
        margin-bottom: 1.25rem !important;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25) !important;
        width: 100% !important;
    }

    .page-header h1 {
        font-size: 1.35rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.2rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.65rem !important;
    }

    .page-header p {
        margin: 0 !important;
        opacity: 0.9 !important;
        font-size: 0.88rem !important;
    }

    /* KPI / Stat Filter Ribbon */
    .stats-filter-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.65rem;
        margin-bottom: 1rem;
        width: 100%;
    }

    .stat-filter-card {
        background: white;
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        user-select: none;
    }

    .stat-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }

    .stat-filter-card.active {
        border-color: #4f46e5;
        background: #eef2ff;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
    }

    .stat-filter-title {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 0.2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-filter-card.active .stat-filter-title {
        color: #4338ca;
    }

    .stat-filter-val {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
        color: #0f172a;
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 1.8s infinite;
    }

    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* Control Card */
    .control-card {
        background: white;
        border-radius: 14px;
        padding: 0.85rem 1.15rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        width: 100%;
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i.fa-search {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-input-modern {
        padding-left: 2.5rem !important;
        padding-right: 2.25rem !important;
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        height: 42px !important;
        font-size: 0.88rem !important;
        transition: all 0.2s !important;
    }

    .search-input-modern:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
    }

    .btn-clear-search {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        padding: 4px 8px;
        cursor: pointer;
        display: none;
    }

    .view-toggle-btn {
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.82rem;
        padding: 0.45rem 0.75rem;
    }

    /* Exam Cards (Mobile Optimized) */
    .exam-cards-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        width: 100%;
    }

    .exam-card-item {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        padding: 1rem 1.15rem;
        transition: all 0.2s ease;
    }

    .exam-card-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .exam-card-item.status-ongoing {
        border-left: 5px solid #10b981;
    }

    .exam-card-item.status-finished {
        border-left: 5px solid #64748b;
    }

    .exam-card-item.status-upcoming {
        border-left: 5px solid #f59e0b;
    }

    .exam-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .exam-subject-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
    }

    .exam-meta-text {
        font-size: 0.82rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.85rem;
    }

    .exam-card-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .btn-exam-main {
        padding: 0.65rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-decoration: none;
        background: var(--primary-gradient);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        transition: all 0.2s ease;
    }

    .btn-exam-main:hover {
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
    }

    .exam-sub-actions-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.35rem;
    }

    .btn-sub-action {
        padding: 0.5rem 0.25rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer !important;
        touch-action: manipulation;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }

    .btn-sub-action * {
        pointer-events: none !important;
    }

    .btn-sub-action:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-sub-action i {
        font-size: 0.95rem;
    }

    /* Table Card */
    .table-card {
        background: white;
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        width: 100%;
    }

    .table-modern thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        padding: 0.85rem 0.6rem;
        vertical-align: middle;
    }

    .table-modern tbody td {
        padding: 0.75rem 0.6rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
    }

    .badge-modern {
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.78rem;
    }

    @media (max-width: 768px) {
        .page-header {
            padding: 1rem 1.15rem !important;
        }

        .stats-filter-container {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .stat-filter-card {
            padding: 0.65rem;
        }

        .stat-filter-val {
            font-size: 1.25rem;
        }
    }
</style>

<div class="w-100">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1><i class="fas fa-chart-line"></i> Aktivitas Ujian CBT</h1>
                <p>Pilih jadwal ujian untuk memantau aktivitas, unduh nilai, atau cetak laporan</p>
            </div>
            <div>
                <button onclick="location.reload()" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- KPI / Status Filter Ribbon -->
    <div class="stats-filter-container">
        <!-- Semua -->
        <div class="stat-filter-card active" data-filter="all" title="Tampilkan semua jadwal ujian">
            <div class="stat-filter-title">
                <span>Semua Ujian</span>
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-filter-val" id="statValTotal">
                <?= $totalTests ?>
            </div>
        </div>

        <!-- Berlangsung -->
        <div class="stat-filter-card" data-filter="ongoing" title="Filter ujian yang sedang berlangsung">
            <div class="stat-filter-title text-success">
                <span>Berlangsung</span>
                <span class="pulse-dot"></span>
            </div>
            <div class="stat-filter-val text-success" id="statValOngoing">
                <?= $ongoingCount ?>
            </div>
        </div>

        <!-- Selesai -->
        <div class="stat-filter-card" data-filter="finished" title="Filter ujian yang telah selesai">
            <div class="stat-filter-title text-primary">
                <span>Selesai</span>
                <i class="fas fa-check-circle text-primary"></i>
            </div>
            <div class="stat-filter-val text-primary" id="statValFinished">
                <?= $finishedCount ?>
            </div>
        </div>

        <!-- Akan Datang -->
        <div class="stat-filter-card" data-filter="upcoming" title="Filter ujian yang akan datang">
            <div class="stat-filter-title text-warning">
                <span>Akan Datang</span>
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div class="stat-filter-val text-warning" id="statValUpcoming">
                <?= $upcomingCount ?>
            </div>
        </div>
    </div>

    <!-- Search & View Toggle -->
    <div class="control-card">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="examSearchInput" class="form-control search-input-modern"
                        placeholder="Cari mata pelajaran, kode tes, atau tanggal..." autocomplete="off">
                    <button type="button" class="btn-clear-search" id="btnClearExamSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 col-md-4 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-2">
                <span class="text-muted small d-none d-sm-inline" id="searchResultCount"><?= $totalTests ?> Ujian</span>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary view-toggle-btn active" id="btnExamCards" title="Tampilan Kartu (Optimal HP)">
                        <i class="fas fa-th-large"></i> <span class="d-none d-sm-inline">Kartu</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary view-toggle-btn" id="btnExamTable" title="Tampilan Tabel (Desktop)">
                        <i class="fas fa-table"></i> <span class="d-none d-sm-inline">Tabel</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW 1: EXAM CARDS (MOBILE OPTIMIZED) -->
    <div id="viewExamCardsWrapper">
        <div class="exam-cards-list" id="examCardsContainer">
            <?php if (empty($sessions)): ?>
                <div class="text-center py-5 text-muted bg-white rounded-3 border">
                    <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary"></i>
                    <h5>Belum ada jadwal aktivitas ujian</h5>
                    <p class="mb-0">Silakan buat status tes pada menu CBT untuk memulai.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sessions as $i => $s): ?>
                    <?php
                    $status = $s['status'] ?? 'upcoming';
                    $startFormatted = date('d M Y, H:i', strtotime($s['start_time']));
                    $endFormatted = date('H:i', strtotime($s['end_time']));
                    ?>
                    <div class="exam-card-item status-<?= $status ?>"
                        data-status="<?= $status ?>"
                        data-code="<?= esc(strtolower($s['test_code'])) ?>"
                        data-subject="<?= esc(strtolower($s['subject_name'])) ?>"
                        data-date="<?= esc(strtolower($startFormatted)) ?>">

                        <!-- Top Info: Status Badge & Code -->
                        <div class="exam-card-top">
                            <div>
                                <?php if ($status === 'ongoing'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <span class="pulse-dot me-1"></span>Berlangsung
                                    </span>
                                <?php elseif ($status === 'finished'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="fas fa-check-circle me-1"></i>Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="fas fa-clock me-1"></i>Akan Datang
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                <?= esc($s['test_code']) ?>
                            </span>
                        </div>

                        <!-- Subject Title -->
                        <h5 class="exam-subject-title">
                            <i class="fas fa-book text-primary me-2"></i><?= esc($s['subject_name']) ?>
                        </h5>

                        <!-- Meta: Jadwal Waktu -->
                        <div class="exam-meta-text">
                            <i class="far fa-calendar-alt text-primary"></i>
                            <span><?= $startFormatted ?> &ndash; <?= $endFormatted ?></span>
                        </div>

                        <!-- Actions for Proctor -->
                        <div class="exam-card-actions">
                            <!-- Tombol Utama: Pantau Aktivitas Siswa Langsung -->
                            <a href="<?= site_url('admin/cbt/aktivitas/detail/' . $s['test_id']) ?>" class="btn-exam-main">
                                <i class="fas fa-chart-line"></i>
                                <span>Pantau Aktivitas Siswa</span>
                            </a>

                            <!-- 4 Tombol Aksi Tambahan Ramah Jari -->
                            <div class="exam-sub-actions-grid">
                                <!-- Belum Tes -->
                                <button type="button" class="btn-sub-action btn-belum-tes"
                                    data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                    title="Daftar Siswa Belum Tes">
                                    <i class="fas fa-user-times text-warning"></i>
                                    <span>Belum Tes</span>
                                </button>

                                <!-- Unduh Nilai -->
                                <button type="button" class="btn-sub-action btn-unduh-nilai"
                                    data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                    data-classes="<?= esc($s['class_codes'] ?? '[]') ?>"
                                    title="Unduh Nilai Siswa">
                                    <i class="fas fa-file-download text-success"></i>
                                    <span>Nilai</span>
                                </button>

                                <!-- Analisis -->
                                <a href="<?= site_url('admin/cbt/aktivitas/analisis/' . $s['test_id']) ?>"
                                    class="btn-sub-action" title="Lihat Analisis Soal">
                                    <i class="fas fa-chart-pie text-info"></i>
                                    <span>Analisis</span>
                                </a>

                                <!-- Laporan -->
                                <a href="<?= site_url('admin/cbt/aktivitas/laporan/' . $s['test_id']) ?>"
                                    class="btn-sub-action" title="Cetak Laporan Jawaban">
                                    <i class="fas fa-print text-primary"></i>
                                    <span>Laporan</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- VIEW 2: EXAM TABLE (DESKTOP) -->
    <div id="viewExamTableWrapper" class="d-none">
        <div class="table-card">
            <div class="table-responsive">
                <table id="tableAktivitas" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th width="120">Status</th>
                            <th>Kode Tes</th>
                            <th>Mata Pelajaran</th>
                            <th width="120" class="text-center">Aktivitas</th>
                            <th width="70" class="text-center">Nilai</th>
                            <th width="110" class="text-center">Analisis</th>
                            <th width="70" class="text-center">Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $i => $s): ?>
                            <?php $status = $s['status'] ?? 'upcoming'; ?>
                            <tr class="row-exam" data-status="<?= $status ?>">
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <?php if ($status === 'ongoing'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <span class="pulse-dot me-1"></span>Berlangsung
                                        </span>
                                    <?php elseif ($status === 'finished'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="fas fa-check-circle me-1"></i>Selesai
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="fas fa-clock me-1"></i>Akan Datang
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= esc($s['test_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-book text-primary"></i>
                                        <strong><?= esc($s['subject_name']) ?></strong>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('d M Y, H:i', strtotime($s['start_time'])) ?> -
                                        <?= date('H:i', strtotime($s['end_time'])) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= site_url('admin/cbt/aktivitas/detail/' . $s['test_id']) ?>"
                                            class="btn btn-sm btn-primary" title="Lihat Aktivitas">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <button class="btn btn-sm btn-outline-warning btn-belum-tes"
                                            data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                            title="Siswa Belum Tes">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-success btn-unduh-nilai"
                                        data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                        data-classes="<?= esc($s['class_codes'] ?? '[]') ?>"
                                        title="Unduh Nilai">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= site_url('admin/cbt/aktivitas/analisis/' . $s['test_id']) ?>"
                                            class="btn btn-sm btn-outline-info" title="Lihat Analisis">
                                            <i class="fas fa-chart-pie"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary btn-analisis-download"
                                            data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                            data-classes="<?= esc($s['class_codes'] ?? '[]') ?>"
                                            title="Unduh Analisis">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="<?= site_url('admin/cbt/aktivitas/laporan/' . $s['test_id']) ?>"
                                        class="btn btn-sm btn-outline-dark" title="Laporan Jawaban">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Belum Tes -->
<div class="modal fade" id="modalBelumTes" tabindex="-1" data-bs-backdrop="true" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4 d-flex align-items-center justify-content-between py-3 px-4">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="modal-title fs-6 fw-bold mb-0">
                        <i class="fas fa-user-times me-2"></i> Daftar Siswa Belum Tes
                    </h5>
                    <span class="badge bg-danger text-white rounded-pill pulse-live d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <span class="live-dot"></span> LIVE
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-dark text-white rounded-pill px-3 py-1 shadow-sm" id="btnRefreshBelumTes" title="Segarkan Data Sekarang">
                        <i class="fas fa-sync-alt me-1" id="iconRefreshBelumTes"></i> <span>Segarkan</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4" style="min-height: 250px;">
                <div id="belumTesContent" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3 text-warning"></i>
                    <p class="mt-2 mb-0">Memuat data siswa belum tes...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unduh Nilai -->
<div class="modal fade" id="modalUnduhNilai" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white rounded-top-4">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-download me-2"></i> Unduh Nilai
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-muted">Pilih kelas untuk mengunduh rekap nilai:</p>
                <form id="formUnduhNilai" method="get">
                    <?= csrf_field() ?>
                    <input type="hidden" name="test_id" id="nilaiTestId">
                    <div class="mb-3">
                        <label for="classSelect" class="form-label fw-bold">Pilih Kelas:</label>
                        <select id="classSelect" name="class" class="form-select" style="height: 44px; border-radius: 10px;">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= esc($cls['id']) ?>"><?= esc($cls['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3 rounded-3">
                        <i class="fas fa-file-excel me-1"></i> Unduh File Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unduh Analisis -->
<div class="modal fade" id="modalUnduhAnalisis" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-download me-2"></i> Download Analisis Jawaban
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-4">Anda bisa mengunduh seluruh analisis gabungan atau memilih per kelas:</p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <button id="btnAnalisisTotal" class="btn btn-success fw-bold px-4 py-2 rounded-3">
                        <i class="fas fa-file-excel me-1"></i> Total (Semua)
                    </button>
                    <button id="btnAnalisisPerKelas" class="btn btn-outline-primary fw-bold px-4 py-2 rounded-3">
                        <i class="fas fa-filter me-1"></i> Per Kelas
                    </button>
                </div>

                <div id="analisisClassSection" class="d-none text-start mt-3 p-3 bg-light rounded-3 border">
                    <label class="form-label fw-bold">Pilih Kelas:</label>
                    <select id="analisisClassSelect" class="form-select mb-3" style="height: 44px; border-radius: 10px;">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= esc($cls['id']) ?>"><?= esc($cls['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnDownloadAnalisisKelas" class="btn btn-primary w-100 fw-bold py-2 rounded-3">
                        <i class="fas fa-download me-1"></i> Unduh Analisis Kelas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(function () {
        // 1. DataTables Init
        let examDataTable = null;
        function initExamTable() {
            if ($.fn.DataTable.isDataTable('#tableAktivitas')) {
                examDataTable = $('#tableAktivitas').DataTable();
                return;
            }
            examDataTable = $('#tableAktivitas').DataTable({
                responsive: true,
                order: [],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    zeroRecords: "Tidak ada jadwal ujian ditemukan",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ ujian",
                    infoEmpty: "Tidak ada data tersedia"
                }
            });
        }

        // 2. View Mode (Cards vs Table)
        const savedExamView = localStorage.getItem('cbt_exam_view_mode');
        const isMobileViewport = window.innerWidth < 992;
        let currentExamView = savedExamView ? savedExamView : (isMobileViewport ? 'cards' : 'cards');

        function applyExamView(mode) {
            currentExamView = mode;
            localStorage.setItem('cbt_exam_view_mode', mode);

            if (mode === 'cards') {
                $('#btnExamCards').addClass('active');
                $('#btnExamTable').removeClass('active');
                $('#viewExamCardsWrapper').removeClass('d-none');
                $('#viewExamTableWrapper').addClass('d-none');
            } else {
                $('#btnExamCards').removeClass('active');
                $('#btnExamTable').addClass('active');
                $('#viewExamCardsWrapper').addClass('d-none');
                $('#viewExamTableWrapper').removeClass('d-none');
                initExamTable();
            }
        }

        applyExamView(currentExamView);

        $('#btnExamCards').on('click', function () {
            applyExamView('cards');
        });

        $('#btnExamTable').on('click', function () {
            applyExamView('table');
        });

        // 3. Status Filters & Search Synchronization
        let activeStatusFilter = 'all';

        $('.stat-filter-card').on('click', function () {
            $('.stat-filter-card').removeClass('active');
            $(this).addClass('active');
            activeStatusFilter = $(this).data('filter');
            applyExamFilters();
        });

        $('#examSearchInput').on('input', function () {
            const q = $(this).val().trim();
            $('#btnClearExamSearch').toggle(q.length > 0);
            applyExamFilters();
        });

        $('#btnClearExamSearch').on('click', function () {
            $('#examSearchInput').val('').trigger('input');
        });

        function applyExamFilters() {
            const query = $('#examSearchInput').val().toLowerCase().trim();

            let visibleCount = 0;
            $('.exam-card-item').each(function () {
                const card = $(this);
                const code = (card.data('code') || '').toString();
                const subject = (card.data('subject') || '').toString();
                const date = (card.data('date') || '').toString();
                const status = (card.data('status') || '').toString();

                let matchSearch = true;
                if (query) {
                    matchSearch = code.includes(query) || subject.includes(query) || date.includes(query);
                }

                let matchStatus = true;
                if (activeStatusFilter === 'ongoing') {
                    matchStatus = status === 'ongoing';
                } else if (activeStatusFilter === 'finished') {
                    matchStatus = status === 'finished';
                } else if (activeStatusFilter === 'upcoming') {
                    matchStatus = status !== 'ongoing' && status !== 'finished';
                }

                if (matchSearch && matchStatus) {
                    card.show();
                    visibleCount++;
                } else {
                    card.hide();
                }
            });

            $('#searchResultCount').text(visibleCount + ' Ujian');

            // Sync with DataTable
            if (examDataTable) {
                $.fn.dataTable.ext.search = [];
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    const rowNode = examDataTable.row(dataIndex).node();
                    const rowStatus = $(rowNode).data('status');

                    let matchStatus = true;
                    if (activeStatusFilter === 'ongoing') {
                        matchStatus = rowStatus === 'ongoing';
                    } else if (activeStatusFilter === 'finished') {
                        matchStatus = rowStatus === 'finished';
                    } else if (activeStatusFilter === 'upcoming') {
                        matchStatus = rowStatus !== 'ongoing' && rowStatus !== 'finished';
                    }
                    return matchStatus;
                });

                examDataTable.search(query).draw();
            }
        }

        // Helper to safely open Bootstrap 5 modal without clipping / stacking context issues
        function openModalSafe(modalId) {
            const el = document.getElementById(modalId);
            if (!el) return;
            if (el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
            if (window.bootstrap && window.bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            } else if (window.jQuery && typeof $('#' + modalId).modal === 'function') {
                $('#' + modalId).modal('show');
            }
        }

        // 4. Modal Siswa Belum Tes (Realtime Live Polling)
        let belumTesTimer = null;
        let currentBelumTesTestId = null;
        let isBelumTesFetching = false;

        function getBelumTesUrl(testId) {
            let base = '<?= site_url('admin/cbt/aktivitas/belumTes') ?>';
            try {
                // Selaraskan protocol dan host (termasuk port seperti :8080) dengan browser yang aktif
                const parsed = new URL(base, window.location.href);
                parsed.protocol = window.location.protocol;
                parsed.host = window.location.host;
                return parsed.toString().replace(/\/+$/, '') + '/' + encodeURIComponent(testId);
            } catch (e) {
                return base.replace(/\/+$/, '') + '/' + encodeURIComponent(testId);
            }
        }

        function fetchBelumTes(testId, isSilent) {
            if (!testId || isBelumTesFetching) return;
            isBelumTesFetching = true;

            const $iconRefresh = $('#iconRefreshBelumTes');
            $iconRefresh.addClass('fa-spin');

            const modalBody = document.querySelector('#modalBelumTes .modal-body');
            const previousScrollTop = modalBody ? modalBody.scrollTop : 0;
            const currentSearch = $('#searchBelumTes').val() || '';

            if (!isSilent) {
                $('#belumTesContent').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-2x mb-3 text-warning"></i><p class="mt-2 mb-0">Menghubungkan & memuat data terbaru...</p></div>');
            }

            const requestUrl = getBelumTesUrl(testId);

            $.ajax({
                url: requestUrl,
                type: 'GET',
                data: { _t: Date.now() },
                cache: false,
                success: function (html) {
                    $('#belumTesContent').html(html);

                    if (currentSearch) {
                        $('#searchBelumTes').val(currentSearch);
                        applyBelumTesFilter(currentSearch);
                    }

                    if (isSilent && modalBody) {
                        modalBody.scrollTop = previousScrollTop;
                    }
                },
                error: function (xhr, status, error) {
                    if (!isSilent) {
                        let detail = '';
                        if (xhr.status === 404) {
                            detail = 'Data ujian tidak ditemukan (404).';
                        } else if (xhr.status === 403) {
                            detail = 'Akses ditolak atau sesi login telah kedaluwarsa (403). Silakan refresh halaman utama.';
                        } else if (xhr.status === 500) {
                            detail = 'Terjadi kendala pemrosesan data di server (500).';
                        } else if (xhr.status === 0) {
                            detail = 'Gagal tersambung ke server (Koneksi terputus atau beda port: ' + window.location.host + ').';
                        } else {
                            detail = 'Status error: ' + (xhr.status || status || 'Gagal tersambung');
                        }

                        $('#belumTesContent').html(`
                            <div class="alert alert-danger py-3 shadow-sm rounded-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-exclamation-triangle text-danger fa-lg"></i>
                                    <strong class="fs-6">Gagal Memuat Data Siswa Belum Tes</strong>
                                </div>
                                <p class="small text-muted mb-3">${detail}</p>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="fetchBelumTes(${testId}, false)">
                                    <i class="fas fa-sync-alt me-1"></i> Coba Segarkan Lagi
                                </button>
                            </div>
                        `);
                    }
                },
                complete: function () {
                    isBelumTesFetching = false;
                    $iconRefresh.removeClass('fa-spin');
                }
            });
        }

        function applyBelumTesFilter(keyword) {
            const term = (keyword || '').toLowerCase().trim();
            let matchCount = 0;
            $('.belum-tes-row').each(function () {
                const text = $(this).text().toLowerCase();
                if (!term || text.indexOf(term) !== -1) {
                    $(this).show();
                    matchCount++;
                } else {
                    $(this).hide();
                }
            });

            if (term && matchCount === 0) {
                $('#noSearchResultRow').show();
            } else {
                $('#noSearchResultRow').hide();
            }
        }

        $(document).on('input', '#searchBelumTes', function () {
            applyBelumTesFilter($(this).val());
        });

        $(document).on('click', '.btn-belum-tes', function (e) {
            e.preventDefault();
            const btn = $(this).closest('.btn-belum-tes');
            const id = btn.attr('data-id') || btn.data('id');
            const name = btn.attr('data-name') || btn.data('name') || '';

            currentBelumTesTestId = id;
            $('#modalBelumTes .modal-title').html('<i class="fas fa-user-times me-2"></i> Belum Tes: ' + name);

            openModalSafe('modalBelumTes');
            fetchBelumTes(id, false);

            if (belumTesTimer) {
                clearInterval(belumTesTimer);
            }

            // Realtime polling otomatis setiap 4 detik saat modal terbuka
            belumTesTimer = setInterval(function () {
                const $modal = $('#modalBelumTes');
                if ($modal.hasClass('show') && currentBelumTesTestId) {
                    fetchBelumTes(currentBelumTesTestId, true);
                } else {
                    clearInterval(belumTesTimer);
                    belumTesTimer = null;
                }
            }, 4000);
        });

        $(document).on('click', '#btnRefreshBelumTes', function (e) {
            e.preventDefault();
            if (currentBelumTesTestId) {
                fetchBelumTes(currentBelumTesTestId, false);
            }
        });

        $('#modalBelumTes').on('hidden.bs.modal', function () {
            if (belumTesTimer) {
                clearInterval(belumTesTimer);
                belumTesTimer = null;
            }
            currentBelumTesTestId = null;
        });


        // Helper untuk filter dropdown kelas sesuai peserta ujian
        function filterClassOptions($select, rawClasses, defaultAllValue) {
            let classCodes = [];
            try {
                classCodes = typeof rawClasses === 'string' ? JSON.parse(rawClasses) : (Array.isArray(rawClasses) ? rawClasses : []);
            } catch (e) {
                classCodes = [];
            }

            $select.find('option').each(function () {
                const optVal = $(this).val();
                const optText = $(this).text().trim().toLowerCase();
                if (!optVal || optVal === defaultAllValue) {
                    $(this).show();
                    return;
                }
                if (Array.isArray(classCodes) && classCodes.length > 0) {
                    const match = classCodes.some(c => {
                        const cStr = String(c).trim().toLowerCase();
                        return cStr === String(optVal).toLowerCase() || 
                               cStr === optText || 
                               optText.includes(cStr) || 
                               cStr.includes(optText);
                    });
                    $(this).toggle(match);
                } else {
                    $(this).show();
                }
            });

            const visible = $select.find('option:not([value=""]):not([value="' + defaultAllValue + '"]):visible').length;
            if (visible === 0) {
                $select.find('option').show();
            }
        }

        // 5. Modal Unduh Nilai
        $(document).on('click', '.btn-unduh-nilai', function (e) {
            e.preventDefault();
            const btn = $(this).closest('.btn-unduh-nilai');
            const id = btn.attr('data-id') || btn.data('id');
            const name = btn.attr('data-name') || btn.data('name') || '';
            const rawClasses = btn.attr('data-classes') || btn.data('classes') || '[]';
            $('#nilaiTestId').val(id);
            $('#modalUnduhNilai .modal-title').html('<i class="fas fa-download me-2"></i> Unduh Nilai: ' + name);
            filterClassOptions($('#classSelect'), rawClasses, 'all');
            $('#classSelect').val('all');
            openModalSafe('modalUnduhNilai');
        });

        $('#formUnduhNilai').on('submit', function (e) {
            e.preventDefault();
            const id = $('#nilaiTestId').val();
            const kelas = $('#classSelect').val();
            window.location.href = `<?= site_url('admin/cbt/aktivitas/unduhNilai/') ?>${id}?class=${kelas}`;
        });

        window.exportUntukEspata = function() {
            const id = $('#nilaiTestId').val();
            if (!id) { alert('Test ID tidak ditemukan.'); return; }
            window.location.href = `<?= site_url('admin/cbt/aktivitas/exportNilai/') ?>${id}`;
        };

        // 6. Modal Unduh Analisis
        $(document).on('click', '.btn-analisis-download', function (e) {
            e.preventDefault();
            const btn = $(this).closest('.btn-analisis-download');
            const id = btn.attr('data-id') || btn.data('id');
            const name = btn.attr('data-name') || btn.data('name') || '';
            const rawClasses = btn.attr('data-classes') || btn.data('classes') || '[]';
            $('#modalUnduhAnalisis .modal-title').html('<i class="fas fa-download me-2"></i> Download Analisis: ' + name);
            $('#modalUnduhAnalisis').data('test-id', id);
            $('#analisisClassSection').addClass('d-none');
            filterClassOptions($('#analisisClassSelect'), rawClasses, null);
            $('#analisisClassSelect').val('');
            openModalSafe('modalUnduhAnalisis');
        });

        $('#btnAnalisisTotal').on('click', function () {
            const id = $('#modalUnduhAnalisis').data('test-id');
            const url = `<?= site_url('admin/cbt/aktivitas/analisisjawaban/download/') ?>${id}?mode=total`;
            window.location.href = url;
        });

        $('#btnAnalisisPerKelas').on('click', function () {
            $('#analisisClassSection').removeClass('d-none');
        });

        $('#btnDownloadAnalisisKelas').on('click', function () {
            const id = $('#modalUnduhAnalisis').data('test-id');
            const classId = $('#analisisClassSelect').val();
            if (!classId) {
                alert('Silakan pilih kelas terlebih dahulu.');
                return;
            }
            const url = `<?= site_url('admin/cbt/aktivitas/analisisjawaban/download/') ?>${id}?mode=kelas&class=${classId}`;
            window.location.href = url;
        });
    });
</script>
<?= $this->endSection() ?>