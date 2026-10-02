<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = [
    'DRAFT'=>'Draft','REPORTED'=>'Dilaporkan','VERIFIED'=>'Terverifikasi',
    'IN_ASSESSMENT'=>'Asesmen','IN_PROGRESS'=>'Dalam Penanganan',
    'MONITORING'=>'Monitoring','REFERRED'=>'Dirujuk',
    'RESOLVED'=>'Selesai','CLOSED'=>'Ditutup',
];
$statusBadge  = [
    'DRAFT'=>'secondary','REPORTED'=>'warning','VERIFIED'=>'info',
    'IN_ASSESSMENT'=>'primary','IN_PROGRESS'=>'primary',
    'MONITORING'=>'warning','REFERRED'=>'danger',
    'RESOLVED'=>'success','CLOSED'=>'success',
];
$severityBadge = ['Ringan'=>'success','Sedang'=>'warning','Berat'=>'danger'];
?>
<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('admin/bk/laporan/kasus') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Filter
            </a>
            <h5 class="fw-bold mb-0"><i class="bi bi-shield-exclamation text-danger me-1"></i>Preview Laporan Penanganan Kasus BK</h5>
        </div>
        <?php if (!empty($rows)): ?>
        <form method="post" action="<?= base_url('admin/bk/laporan/kasus/pdf') ?>">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endforeach; ?>
            <button type="submit" class="btn btn-danger rounded-pill fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card border-0 bg-danger-subtle rounded-3 mb-3 p-3 small">
        <div class="row g-1">
            <div class="col-auto"><span class="fw-semibold">Tahun Ajaran:</span> <?= esc($year['year'] ?? '—') ?></div>
            <?php if (!empty($filter['category'])): ?><div class="col-auto text-muted">·</div><div class="col-auto"><span class="fw-semibold">Kategori:</span> <?= esc($filter['category']) ?></div><?php endif; ?>
            <?php if (!empty($filter['severity'])): ?><div class="col-auto text-muted">·</div><div class="col-auto"><span class="fw-semibold">Bobot:</span> <?= esc($filter['severity']) ?></div><?php endif; ?>
            <?php if (!empty($filter['status'])): ?><div class="col-auto text-muted">·</div><div class="col-auto"><span class="fw-semibold">Status:</span> <?= esc($statusLabels[$filter['status']] ?? $filter['status']) ?></div><?php endif; ?>
            <?php if (!empty($filter['date_from'])): ?><div class="col-auto text-muted">·</div><div class="col-auto"><span class="fw-semibold">Periode:</span> <?= esc($filter['date_from']) ?> s.d. <?= esc($filter['date_to'] ?? '—') ?></div><?php endif; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-semibold text-muted">Tidak ditemukan data kasus BK</h5>
        <p class="text-muted small mb-4">Tidak ada kasus yang sesuai dengan filter yang dipilih.</p>
        <a href="<?= base_url('admin/bk/laporan/kasus') ?>" class="btn btn-outline-danger rounded-pill"><i class="bi bi-arrow-left me-1"></i>Kembali ke Filter</a>
    </div>
    <?php else: ?>

    <!-- Ringkasan -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ['Total Kasus', count($rows), 'dark'],
            ['Baru/Aktif', $rekap['totalActive'], 'warning'],
            ['Dalam Penanganan', $rekap['totalProses'], 'primary'],
            ['Monitoring', $rekap['totalMonitor'], 'info'],
            ['Selesai', $rekap['totalSelesai'], 'success'],
            ['Dirujuk', $rekap['totalReferred'], 'danger'],
        ];
        foreach ($stats as [$lbl, $val, $col]): ?>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                <div class="fs-3 fw-bold text-<?= $col ?>"><?= $val ?></div>
                <div class="small text-muted" style="font-size:.7rem;"><?= $lbl ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Rekap per kategori, status, bobot -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-tag me-1 text-danger"></i>Rekap per Kategori</div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Kategori</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['byCategory'] as $cat => $j): ?>
                        <tr><td><?= esc($cat) ?></td><td class="text-end fw-semibold"><?= $j ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-circle-half me-1 text-danger"></i>Rekap per Status</div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Status</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['byStatus'] as $st => $j): ?>
                        <tr><td><?= esc($statusLabels[$st] ?? $st) ?></td><td class="text-end fw-semibold"><?= $j ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small"><i class="bi bi-exclamation-triangle me-1 text-danger"></i>Rekap per Bobot</div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Bobot</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['bySeverity'] as $sv => $j): ?>
                        <tr>
                            <td><span class="badge bg-<?= $severityBadge[$sv] ?? 'secondary' ?>"><?= esc($sv) ?></span></td>
                            <td class="text-end fw-semibold"><?= $j ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel rinci kasus -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white border-bottom py-2 px-4 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="bi bi-table me-1 text-danger"></i>Rekapitulasi Kasus (<?= count($rows) ?> kasus)</span>
            <span class="text-muted" style="font-size:.7rem;"><i class="bi bi-lock-fill me-1"></i>Catatan rahasia tidak ditampilkan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle" style="font-size:.78rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3" style="width:35px;">No</th>
                            <th>Tgl Kejadian</th>
                            <th>No Kasus</th>
                            <th>Kategori</th>
                            <th>Kode Siswa</th>
                            <th>Kelas</th>
                            <th>Bobot</th>
                            <th>Status</th>
                            <th>Tgl Selesai</th>
                            <th class="text-center">T.Lanjut</th>
                            <th class="text-center">Rujukan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td class="px-3 text-muted"><?= $i + 1 ?></td>
                        <td><?= $r['incident_date'] ? date('d/m/Y', strtotime($r['incident_date'])) : '—' ?></td>
                        <td><code style="font-size:.7rem;"><?= esc($r['case_code'] ?: 'N/A') ?></code></td>
                        <td><?= esc($r['category']) ?></td>
                        <td class="text-muted"><?= esc($r['student_nis'] ?: '(NIS tidak ada)') ?></td>
                        <td><?= esc($r['class_name'] ?: '—') ?></td>
                        <td><span class="badge bg-<?= $severityBadge[$r['severity']] ?? 'secondary' ?>" style="font-size:.65rem;"><?= esc($r['severity']) ?></span></td>
                        <td><span class="badge bg-<?= $statusBadge[$r['status']] ?? 'secondary' ?>" style="font-size:.65rem;"><?= esc($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
                        <td><?= $r['closed_at'] ? date('d/m/Y', strtotime($r['closed_at'])) : '—' ?></td>
                        <td class="text-center"><?= (int)$r['total_actions'] ?></td>
                        <td class="text-center"><?= (int)$r['total_referrals'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        <form method="post" action="<?= base_url('admin/bk/laporan/kasus/pdf') ?>">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endforeach; ?>
            <button type="submit" class="btn btn-danger rounded-pill fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF Laporan Ini</button>
        </form>
    </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>
