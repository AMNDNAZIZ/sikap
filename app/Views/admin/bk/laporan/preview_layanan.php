<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
// ── helper label ──────────────────────────────────────────────────────────
$semLabel = match((string)($filter['semester'] ?? '')) {
    '1' => 'Semester 1 (Ganjil)',
    '2' => 'Semester 2 (Genap)',
    default => 'Semua Semester',
};
$statusMap = [
    'Rencana'=>'secondary','Penjadwalan'=>'info','Pelaksanaan'=>'primary',
    'Evaluasi'=>'warning','Tindak Lanjut'=>'warning','Selesai'=>'success','Batal'=>'danger',
];
?>
<div class="container-fluid py-4">

    <!-- ── Header bar ─────────────────────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('admin/bk/laporan/layanan') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Filter
            </a>
            <h5 class="fw-bold mb-0">
                <i class="bi bi-journal-check text-info me-1"></i>
                Preview Rekap Layanan BK
            </h5>
        </div>

        <?php if (!empty($rows)): ?>
        <!-- form meneruskan semua filter ke endpoint PDF -->
        <form method="post" action="<?= base_url('admin/bk/laporan/layanan/pdf') ?>" id="formCetakPdf">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?>
                <input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-info text-white rounded-pill fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- ── Info filter yang aktif ─────────────────────────────────────── -->
    <div class="card border-0 bg-info-subtle rounded-3 mb-3 p-3 small">
        <div class="row g-1">
            <div class="col-auto"><span class="fw-semibold">Tahun Ajaran:</span>
                <?= esc($year['year'] ?? '—') ?></div>
            <div class="col-auto text-muted">·</div>
            <div class="col-auto"><span class="fw-semibold">Semester:</span> <?= $semLabel ?></div>
            <?php if (!empty($filter['service_type'])): ?>
            <div class="col-auto text-muted">·</div>
            <div class="col-auto"><span class="fw-semibold">Jenis:</span> <?= esc($filter['service_type']) ?></div>
            <?php endif; ?>
            <?php if (!empty($filter['date_from']) || !empty($filter['date_to'])): ?>
            <div class="col-auto text-muted">·</div>
            <div class="col-auto">
                <span class="fw-semibold">Periode:</span>
                <?= esc($filter['date_from'] ?? '—') ?> s.d. <?= esc($filter['date_to'] ?? '—') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
    <!-- ── Empty State ─────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-semibold text-muted">Tidak ditemukan data layanan BK</h5>
        <p class="text-muted small mb-4">
            Tidak ada layanan BK yang sesuai dengan filter yang dipilih.<br>
            Coba ubah filter atau perluas periode tanggal.
        </p>
        <div>
            <a href="<?= base_url('admin/bk/laporan/layanan') ?>" class="btn btn-outline-info rounded-pill">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Filter
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- ── A. Ringkasan ────────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                <div class="fs-2 fw-bold text-info"><?= count($rows) ?></div>
                <div class="small text-muted">Total Layanan</div>
            </div>
        </div>
        <?php foreach ($rekap['byType'] as $type => $jml): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                <div class="fs-4 fw-bold text-dark"><?= $jml ?></div>
                <div class="small text-muted" style="font-size:.7rem;"><?= esc($type) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── B. Rekap per Jenis Layanan ─────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small">
                    <i class="bi bi-bar-chart me-1 text-info"></i>Rekap per Jenis Layanan
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Jenis Layanan</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['byType'] as $t => $j): ?>
                        <tr><td><?= esc($t) ?></td><td class="text-end fw-semibold"><?= $j ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr><th>Total</th><th class="text-end"><?= count($rows) ?></th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small">
                    <i class="bi bi-grid me-1 text-info"></i>Rekap per Bidang
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Bidang</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['byField'] as $f_ => $j): ?>
                        <tr><td><?= esc($f_) ?></td><td class="text-end fw-semibold"><?= $j ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small">
                    <i class="bi bi-people me-1 text-info"></i>Rekap per Kelas
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="table-light"><tr><th>Kelas</th><th class="text-end">Jml</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap['byClass'] as $k => $j): ?>
                        <tr><td><?= esc($k) ?></td><td class="text-end fw-semibold"><?= $j ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── C. Tabel Rinci Layanan ──────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-2 px-4 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="bi bi-table me-1 text-info"></i>Rekapitulasi Layanan (<?= count($rows) ?> data)</span>
            <span class="text-muted" style="font-size:.7rem;">*Data sensitif sesi tidak ditampilkan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle" style="font-size:.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3" style="width:35px;">No</th>
                            <th>Tanggal</th>
                            <th>Jenis Layanan</th>
                            <th>Bidang</th>
                            <th>Topik</th>
                            <th>Kelas</th>
                            <th class="text-center">Peserta</th>
                            <th>Konselor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td class="px-3 text-muted"><?= $i + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($r['service_date'])) ?></td>
                        <td><?= esc($r['service_type']) ?></td>
                        <td><span class="badge bg-light text-dark border" style="font-size:.65rem;"><?= esc($r['field']) ?></span></td>
                        <td style="max-width:200px;" class="text-truncate"><?= esc($r['topic']) ?></td>
                        <td><?= esc($r['class_name'] ?: '—') ?></td>
                        <td class="text-center fw-semibold"><?= (int)$r['jumlah_peserta'] ?></td>
                        <td><?= esc($r['counselor_name'] ?: '—') ?></td>
                        <td>
                            <span class="badge bg-<?= $statusMap[$r['status']] ?? 'secondary' ?>" style="font-size:.65rem;">
                                <?= esc($r['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        <form method="post" action="<?= base_url('admin/bk/laporan/layanan/pdf') ?>">
            <?= csrf_field() ?>
            <?php foreach ($filter as $k => $v): ?>
                <input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-info text-white rounded-pill fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF Laporan Ini
            </button>
        </form>
    </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>
