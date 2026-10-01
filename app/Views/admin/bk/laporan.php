<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                Laporan &amp; Rekapitulasi BK
            </h3>
            <p class="text-muted mb-0">Cetak Rekap Layanan, Rekap Kasus, Laporan Ketercapaian Program, &amp; Laporan Kepala Sekolah</p>
        </div>
        <a href="<?= base_url('bk') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard BK
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i><?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2 small mb-3">
            <i class="bi bi-check-circle me-1"></i><?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- Ringkasan cepat dari tahun ajaran aktif -->
    <?php if (!empty($serviceStats) || !empty($caseStats)): ?>
    <div class="row g-3 mb-4">
        <?php
          $totalLayanan = array_sum(array_column($serviceStats, 'total'));
          $totalKasus   = array_sum(array_column($caseStats,   'total'));
        ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-info-subtle text-info rounded-3 p-3 text-center">
                <div class="fw-bold fs-3"><?= $totalLayanan ?></div>
                <div class="small">Total Layanan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-danger-subtle text-danger rounded-3 p-3 text-center">
                <div class="fw-bold fs-3"><?= $totalKasus ?></div>
                <div class="small">Total Kasus</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-success-subtle text-success rounded-3 p-3 text-center">
                <?php $selesai = 0; foreach($caseStats as $cs) if(in_array($cs['status'],['RESOLVED','CLOSED'])) $selesai += $cs['total']; ?>
                <div class="fw-bold fs-3"><?= $selesai ?></div>
                <div class="small">Kasus Selesai</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-primary-subtle text-primary rounded-3 p-3 text-center">
                <?php $inProses = 0; foreach($caseStats as $cs) if(in_array($cs['status'],['IN_PROGRESS','MONITORING'])) $inProses += $cs['total']; ?>
                <div class="fw-bold fs-3"><?= $inProses ?></div>
                <div class="small">Kasus Berjalan</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Kartu 1: Rekap Layanan BK -->
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar-md bg-info-subtle text-info rounded-circle p-3 mb-3 d-inline-block" style="width:60px;height:60px;display:flex!important;align-items:center;justify-content:center;">
                        <i class="bi bi-journal-check fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Laporan Rekap Layanan BK</h5>
                    <p class="small text-muted flex-grow-1">
                        Cetak jurnal realisasi bimbingan klasikal, kelompok, konseling individual,
                        kunjungan rumah, dan jenis layanan lainnya.<br>
                        <span class="text-info fw-semibold">Filter: tahun ajaran, semester, jenis layanan, kelas, periode.</span>
                    </p>
                    <a href="<?= base_url('admin/bk/laporan/layanan') ?>"
                       class="btn btn-outline-info rounded-pill fw-semibold mt-2">
                        <i class="bi bi-funnel me-1"></i>Filter &amp; Cetak PDF Layanan
                    </a>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Laporan Penanganan Kasus -->
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar-md bg-danger-subtle text-danger rounded-circle p-3 mb-3 d-inline-block" style="width:60px;height:60px;display:flex!important;align-items:center;justify-content:center;">
                        <i class="bi bi-shield-exclamation fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Laporan Penanganan Kasus</h5>
                    <p class="small text-muted flex-grow-1">
                        Rekapitulasi penanganan kasus berdasarkan kategori, bobot/tingkat,
                        dan status penyelesaian. Data sensitif tidak ditampilkan.<br>
                        <span class="text-danger fw-semibold">Filter: kategori, bobot kasus, status, kelas, periode.</span>
                    </p>
                    <a href="<?= base_url('admin/bk/laporan/kasus') ?>"
                       class="btn btn-outline-danger rounded-pill fw-semibold mt-2">
                        <i class="bi bi-funnel me-1"></i>Filter &amp; Cetak PDF Kasus
                    </a>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Laporan Eksekutif Kepsek -->
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar-md bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-block" style="width:60px;height:60px;display:flex!important;align-items:center;justify-content:center;">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Laporan Eksekutif Kepala Sekolah</h5>
                    <p class="small text-muted flex-grow-1">
                        Laporan ringkas pelaksanaan BK (statistik, capaian program, pemetaan
                        kebutuhan) tanpa membuka catatan konseling rahasia.<br>
                        <span class="text-primary fw-semibold">Filter: tahun ajaran, semester, periode.</span>
                    </p>
                    <a href="<?= base_url('admin/bk/laporan/eksekutif') ?>"
                       class="btn btn-primary rounded-pill fw-semibold mt-2">
                        <i class="bi bi-funnel me-1"></i>Filter &amp; Cetak Laporan Kepsek
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /.row -->
</div>
<?= $this->endSection() ?>
