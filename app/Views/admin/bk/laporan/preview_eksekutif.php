<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$semLabel = match((string)($filter['semester'] ?? '')) {
    '1' => 'Semester 1 (Ganjil)', '2' => 'Semester 2 (Genap)', default => 'Seluruh Tahun',
};
$noData = ($totalLayanan === 0 && $totalKasus === 0 && $totalProgram === 0);
?>
<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('admin/bk/laporan/eksekutif') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Filter
            </a>
            <h5 class="fw-bold mb-0"><i class="bi bi-building text-primary me-1"></i>Preview Laporan Eksekutif BK</h5>
        </div>
        <?php if (!$noData): ?>
        <form method="post" action="<?= base_url('admin/bk/laporan/eksekutif/pdf') ?>">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endforeach; ?>
            <button type="submit" class="btn btn-primary rounded-pill fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak Laporan Kepsek</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- info filter -->
    <div class="card border-0 bg-primary-subtle rounded-3 mb-3 p-3 small">
        <div class="row g-1">
            <div class="col-auto"><span class="fw-semibold">Tahun Ajaran:</span> <?= esc($year['year'] ?? '—') ?></div>
            <div class="col-auto text-muted">·</div>
            <div class="col-auto"><span class="fw-semibold">Periode:</span> <?= $semLabel ?></div>
            <?php if (!empty($filter['date_from'])): ?>
            <div class="col-auto text-muted">·</div>
            <div class="col-auto"><?= esc($filter['date_from']) ?> s.d. <?= esc($filter['date_to'] ?? '—') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($noData): ?>
    <!-- Empty state -->
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-semibold text-muted">Tidak terdapat data BK pada periode yang dipilih</h5>
        <p class="text-muted small mb-4">Pastikan data layanan, kasus, atau program BK sudah diinput terlebih dahulu.</p>
        <a href="<?= base_url('admin/bk/laporan/eksekutif') ?>" class="btn btn-outline-primary rounded-pill"><i class="bi bi-arrow-left me-1"></i>Kembali ke Filter</a>
    </div>
    <?php else: ?>

    <!-- A. Ringkasan eksekutif -->
    <div class="row g-3 mb-4">
        <?php
        $summary = [
            ['Total Program',   $totalProgram,      'dark'],
            ['Total Layanan',   $totalLayanan,       'info'],
            ['Siswa Dilayani',  $totalSiswa,         'primary'],
            ['Total Kasus',     $totalKasus,         'danger'],
            ['Kasus Selesai',   $kasusSelesai,       'success'],
            ['Dalam T.Lanjut',  $kasusProses + ($rekap['totalMonitor'] ?? 0), 'warning'],
            ['Rujukan',         $totalRujukan,       'secondary'],
            ['Tindak Lanjut',   $totalTindakLanjut,  'primary'],
        ];
        foreach ($summary as [$lbl, $val, $col]): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                <div class="fs-3 fw-bold text-<?= $col ?>"><?= $val ?></div>
                <div class="small text-muted" style="font-size:.7rem;"><?= $lbl ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- B & C. Layanan per jenis dan per bidang -->
    <div class="row g-3 mb-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-bar-chart me-1 text-info"></i>Layanan per Jenis</div>
                <div class="card-body p-3">
                    <?php if (empty($byType)): ?>
                        <p class="text-muted small">Belum ada data layanan.</p>
                    <?php else: ?>
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Jenis</th><th class="text-end">Jml</th><th style="width:80px;">Porsi</th></tr></thead>
                        <tbody>
                        <?php $total = array_sum(array_column($byType,'jumlah')); ?>
                        <?php foreach ($byType as $b): ?>
                        <tr>
                            <td style="font-size:.78rem;"><?= esc($b['service_type']) ?></td>
                            <td class="text-end fw-semibold"><?= $b['jumlah'] ?></td>
                            <td>
                                <?php $pct = $total > 0 ? round($b['jumlah']/$total*100) : 0; ?>
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-info" style="width:<?= $pct ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $pct ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-grid me-1 text-info"></i>Layanan per Bidang</div>
                <div class="card-body p-3">
                    <?php if (empty($byField)): ?>
                        <p class="text-muted small">Belum ada data.</p>
                    <?php else: ?>
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Bidang</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($byField as $b): ?>
                        <tr><td><?= esc($b['field']) ?></td><td class="text-end fw-semibold"><?= $b['jumlah'] ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-shield me-1 text-danger"></i>Kasus per Kategori</div>
                <div class="card-body p-3">
                    <?php if (empty($kasusByKategori)): ?>
                        <p class="text-muted small">Belum ada data kasus.</p>
                    <?php else: ?>
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Kategori</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($kasusByKategori as $k): ?>
                        <tr><td><?= esc($k['category']) ?></td><td class="text-end fw-semibold"><?= $k['jumlah'] ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- D. Capaian Program -->
    <?php if ($targetLayanan > 0): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-graph-up me-1 text-success"></i>Capaian Program BK</div>
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-3 text-center">
                    <div class="display-5 fw-bold text-<?= $capaian >= 80 ? 'success' : ($capaian >= 60 ? 'warning' : 'danger') ?>">
                        <?= $capaian ?>%
                    </div>
                    <div class="small text-muted">Ketercapaian</div>
                </div>
                <div class="col-md-9">
                    <div class="mb-2 d-flex justify-content-between small">
                        <span>Target: <strong><?= $targetLayanan ?></strong> layanan (dari RPL)</span>
                        <span>Realisasi: <strong><?= $totalLayanan ?></strong> layanan</span>
                    </div>
                    <div class="progress" style="height:20px;border-radius:10px;">
                        <div class="progress-bar bg-<?= $capaian >= 80 ? 'success' : ($capaian >= 60 ? 'warning' : 'danger') ?>"
                             style="width:<?= min(100, $capaian) ?>%">
                            <?= $capaian ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- E. Pemetaan kebutuhan -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-clipboard-data me-1 text-primary"></i>Pemetaan Kebutuhan Siswa (Asesmen)</div>
        <div class="card-body p-3">
            <?php if (empty($asesmenAgg)): ?>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Belum terdapat data asesmen pada periode yang dipilih.
            </p>
            <?php else: ?>
            <div class="row g-2">
                <?php foreach ($asesmenAgg as $a): ?>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="fs-4 fw-bold text-primary"><?= $a['total_siswa'] ?></div>
                        <div class="small text-muted"><?= esc($a['type']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        <form method="post" action="<?= base_url('admin/bk/laporan/eksekutif/pdf') ?>">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endforeach; ?>
            <button type="submit" class="btn btn-primary rounded-pill fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak Laporan Kepsek</button>
        </form>
    </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>
