<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Header & Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/cbt/review') ?>">Peninjauan Ujian</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Riwayat Keputusan</li>
            </ol>
        </nav>
        <a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean Peninjauan
        </a>
    </div>

    <!-- Integrity Status Banner -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if ($integrity['is_valid']): ?>
                            <span class="badge bg-success px-2 py-1"><i class="bi bi-shield-check me-1"></i> KEUTUHAN DATA TERVERIFIKASI</span>
                        <?php else: ?>
                            <span class="badge bg-danger px-2 py-1"><i class="bi bi-shield-x me-1"></i> DATA PERLU DIPERIKSA</span>
                        <?php endif; ?>
                        <span class="badge bg-light text-dark border">Tersimpan Permanen</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">Catatan Riwayat Resmi Peninjauan Ujian</h5>
                    <p class="text-muted small mb-0">
                        Catatan riwayat keputusan pengawas dan peninjau ujian yang tersimpan secara permanen dan terlindungi dari manipulasi data (arsip resmi sekolah).
                    </p>
                </div>
                <div class="text-md-end">
                    <span class="text-muted small d-block">Total Catatan Keputusan:</span>
                    <h3 class="fw-bold text-dark mb-0"><?= esc($integrity['total_records']) ?></h3>
                </div>
            </div>

            <?php if (!$integrity['is_valid']): ?>
                <div class="alert alert-danger mt-3 mb-0 small">
                    <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perbedaan Data Terdeteksi:</h6>
                    <p class="mb-0"><?= esc($integrity['reason']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-check me-1 text-primary"></i> Riwayat Keputusan Pengawas &amp; Peninjau
            </h6>
            <span class="badge bg-light text-muted border">Arsip Resmi (Hanya Baca)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-3" style="width: 70px;">No.</th>
                            <th>Waktu</th>
                            <th>No. Kasus</th>
                            <th>Petugas Peninjau</th>
                            <th>Keputusan</th>
                            <th>No. Penetapan</th>
                            <th>Perubahan Status</th>
                            <th>Alasan Keputusan</th>
                            <th class="pe-3" style="width: 140px;">Kode Keamanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trail)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-muted"></i>
                                    Belum ada keputusan peninjau yang dicatat pada arsip ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trail as $entry): ?>
                                <tr>
                                    <td class="ps-3 fw-mono small fw-bold">
                                        #<?= esc($entry['sequence_number']) ?>
                                    </td>
                                    <td class="small text-muted" style="white-space: nowrap;">
                                        <?= esc($entry['timestamp_iso']) ?>
                                    </td>
                                    <td class="small fw-mono fw-bold">
                                        <a href="<?= base_url('admin/cbt/review/detail/' . esc($entry['case_id'])) ?>" class="text-decoration-none">
                                            <?= esc($entry['case_id']) ?>
                                        </a>
                                    </td>
                                    <td class="small">
                                        <i class="bi bi-person text-muted me-1"></i> <?= esc($entry['reviewer_id']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary px-2 py-1 small"><?= esc($entry['decision']) ?></span>
                                    </td>
                                    <td class="small fw-mono">
                                        <?php if (!empty($entry['decision_id'])): ?>
                                            <span class="badge bg-light text-primary border border-primary px-2 py-1">
                                                <?= esc($entry['decision_id']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-secondary"><?= esc($entry['status_before']) ?></span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="badge bg-success"><?= esc($entry['status_after']) ?></span>
                                    </td>
                                    <td class="small text-muted" style="max-width: 250px;">
                                        <?= esc($entry['reason']) ?>
                                    </td>
                                    <td class="pe-3 small fw-mono text-muted" title="<?= esc($entry['record_hash']) ?>">
                                        <?= substr(esc($entry['record_hash']), 0, 10) ?>...
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

