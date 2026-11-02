<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Header & Notification Banner -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white;">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="mb-3 mb-md-0">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-primary px-2 py-1 fw-bold"><i class="bi bi-shield-check me-1"></i> ESPATA ASSESSMENT INTELLIGENCE V3</span>
                    <span class="badge bg-success ms-2 px-2 py-1 fw-bold"><i class="bi bi-check-circle me-1"></i> PILOT VALIDATION ACTIVE</span>
                </div>
                <h4 class="fw-bold mb-1">Peninjauan Ujian &amp; Pemeriksaan Kasus <small class="text-white-50 fs-6 d-block d-md-inline">(CBT Reviewer Workflow &amp; Verification Gate)</small></h4>
                <p class="mb-0 text-white-50 small">
                    Pusat peninjauan anomali, ujian susulan, dan validasi data nilai siswa sebelum pengesahan hasil akademik sekolah.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/cbt/review/audit-trail') ?>" class="btn btn-light btn-sm text-primary fw-semibold shadow-sm">
                    <i class="bi bi-journal-text me-1"></i> Riwayat Keputusan Peninjau
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-muted small text-uppercase fw-semibold">Total Kasus</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= esc($summary['total_cases']) ?></h3>
                    <small class="text-muted"><?= esc($summary['pending_cases']) ?> belum selesai</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-warning small text-uppercase fw-semibold">Tindakan Pengawas</span>
                    <h3 class="fw-bold text-warning mt-1 mb-0"><?= esc($summary['reviewer_action_required']) ?></h3>
                    <small class="text-muted">Perlu diperiksa</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-info small text-uppercase fw-semibold">Tindakan Guru</span>
                    <h3 class="fw-bold text-info mt-1 mb-0"><?= esc($summary['teacher_action_required']) ?></h3>
                    <small class="text-muted">Koreksi esai</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-primary small text-uppercase fw-semibold">Keputusan Kepala Sekolah</span>
                    <h3 class="fw-bold text-primary mt-1 mb-0"><?= esc($summary['owner_decision_required']) ?></h3>
                    <small class="text-muted">Ujian susulan</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-secondary small text-uppercase fw-semibold">Konfirmasi Catatan</span>
                    <h3 class="fw-bold text-secondary mt-1 mb-0"><?= esc($summary['acknowledgement_required']) ?></h3>
                    <small class="text-muted">Ketidakhadiran</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                <div class="card-body p-3 text-center">
                    <span class="text-success small text-uppercase fw-semibold">Terselesaikan</span>
                    <h3 class="fw-bold text-success mt-1 mb-0"><?= esc($summary['resolved_cases']) ?></h3>
                    <small class="text-muted">Selesai diverifikasi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('admin/cbt/review') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Kategori Masalah</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        <option value="ROUNDING_DIFFERENCE" <?= ($filters['category'] ?? '') === 'ROUNDING_DIFFERENCE' ? 'selected' : '' ?>>Selisih Pembulatan Nilai</option>
                        <option value="LEGACY_ZERO_FALLBACK" <?= ($filters['category'] ?? '') === 'LEGACY_ZERO_FALLBACK' ? 'selected' : '' ?>>Catatan Ketidakhadiran Siswa</option>
                        <option value="PENDING_ESSAY" <?= ($filters['category'] ?? '') === 'PENDING_ESSAY' ? 'selected' : '' ?>>Esai Belum Dinilai</option>
                        <option value="AMBIGUOUS_RETAKE" <?= ($filters['category'] ?? '') === 'AMBIGUOUS_RETAKE' ? 'selected' : '' ?>>Ujian Susulan / Remedial</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Status Penanganan</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="REVIEWER_ACTION_REQUIRED" <?= ($filters['review_status'] ?? '') === 'REVIEWER_ACTION_REQUIRED' ? 'selected' : '' ?>>Perlu Tindakan Pengawas</option>
                        <option value="TEACHER_ACTION_REQUIRED" <?= ($filters['review_status'] ?? '') === 'TEACHER_ACTION_REQUIRED' ? 'selected' : '' ?>>Perlu Penilaian Guru</option>
                        <option value="OWNER_DECISION_REQUIRED" <?= ($filters['review_status'] ?? '') === 'OWNER_DECISION_REQUIRED' ? 'selected' : '' ?>>Perlu Keputusan Kepala Sekolah</option>
                        <option value="ACKNOWLEDGEMENT_REQUIRED" <?= ($filters['review_status'] ?? '') === 'ACKNOWLEDGEMENT_REQUIRED' ? 'selected' : '' ?>>Perlu Konfirmasi Catatan</option>
                        <option value="RESOLVED" <?= ($filters['review_status'] ?? '') === 'RESOLVED' ? 'selected' : '' ?>>Selesai Ditindaklanjuti</option>
                        <option value="BLOCKED" <?= ($filters['review_status'] ?? '') === 'BLOCKED' ? 'selected' : '' ?>>Ditangguhkan</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Penanggung Jawab</label>
                    <select name="responsible_party" class="form-select form-select-sm">
                        <option value="">Semua Pihak</option>
                        <option value="Reviewer" <?= ($filters['responsible_party'] ?? '') === 'Reviewer' ? 'selected' : '' ?>>Pengawas / Peninjau</option>
                        <option value="Teacher" <?= ($filters['responsible_party'] ?? '') === 'Teacher' ? 'selected' : '' ?>>Guru</option>
                        <option value="Product Owner" <?= ($filters['responsible_party'] ?? '') === 'Product Owner' ? 'selected' : '' ?>>Kepala Sekolah</option>
                        <option value="System/Admin" <?= ($filters['responsible_party'] ?? '') === 'System/Admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Pencarian</label>
                    <input type="text" name="q" value="<?= esc($filters['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="ID Kasus, Ujian, Siswa...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-outline-secondary btn-sm" title="Atur Ulang Pencarian">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Queue Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-list-task me-1 text-primary"></i> Daftar Kasus Peninjauan
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                    <?= esc($pagination['total_items']) ?> kasus ditemukan
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-text me-1"></i> Halaman <?= esc($pagination['page']) ?> dari <?= esc($pagination['total_pages']) ?>
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-3">Nomor Kasus</th>
                            <th>Ujian &amp; Mapel</th>
                            <th>ID Siswa</th>
                            <th>Kategori</th>
                            <th>Status Penanganan</th>
                            <th>Penanggung Jawab</th>
                            <th>No. Keputusan</th>
                            <th>Kelengkapan Bukti</th>
                            <th class="text-end pe-3">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queue)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-check fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada kasus peninjauan yang sesuai dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($queue as $case): ?>
                                <tr>
                                    <td class="ps-3 fw-mono small fw-bold">
                                        <a href="<?= base_url('admin/cbt/review/detail/' . esc($case['case_id'])) ?>" class="text-decoration-none text-primary">
                                            <?= esc($case['case_id']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= esc($case['subject_name'] ?? 'Ujian') ?></span>
                                        <small class="text-muted d-block">Test ID: <?= esc($case['test_id']) ?> <?= !empty($case['session_id']) ? '· Sesi: ' . esc($case['session_id']) : '' ?></small>
                                    </td>
                                    <td class="small fw-mono text-muted">
                                        <?= esc($case['pseudonymous_student_id']) ?>
                                    </td>
                                    <td>
                                        <?php
                                            $catBadge = match($case['category']) {
                                                'ROUNDING_DIFFERENCE' => 'bg-secondary',
                                                'LEGACY_ZERO_FALLBACK' => 'bg-dark',
                                                'PENDING_ESSAY' => 'bg-info text-dark',
                                                'AMBIGUOUS_RETAKE' => 'bg-warning text-dark',
                                                default => 'bg-secondary',
                                            };
                                            $catLabel = match($case['category']) {
                                                'ROUNDING_DIFFERENCE' => 'Selisih Nilai',
                                                'LEGACY_ZERO_FALLBACK' => 'Ketidakhadiran',
                                                'PENDING_ESSAY' => 'Esai Belum Dinilai',
                                                'AMBIGUOUS_RETAKE' => 'Ujian Susulan',
                                                default => $case['category'],
                                            };
                                        ?>
                                        <span class="badge <?= $catBadge ?> px-2 py-1 small"><?= esc($catLabel) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $stBadge = match($case['review_status']) {
                                                'RESOLVED' => 'bg-success',
                                                'BLOCKED' => 'bg-danger',
                                                'TEACHER_ACTION_REQUIRED' => 'bg-info text-dark',
                                                'OWNER_DECISION_REQUIRED' => 'bg-primary',
                                                'ACKNOWLEDGEMENT_REQUIRED' => 'bg-secondary',
                                                default => 'bg-warning text-dark',
                                            };
                                            $stLabel = match($case['review_status']) {
                                                'RESOLVED' => 'Selesai',
                                                'BLOCKED' => 'Ditangguhkan',
                                                'TEACHER_ACTION_REQUIRED' => 'Perlu Penilaian Guru',
                                                'OWNER_DECISION_REQUIRED' => 'Perlu Keputusan Kepsek',
                                                'ACKNOWLEDGEMENT_REQUIRED' => 'Perlu Konfirmasi',
                                                'REVIEWER_ACTION_REQUIRED' => 'Perlu Tindakan Pengawas',
                                                default => $case['review_status'],
                                            };
                                        ?>
                                        <span class="badge <?= $stBadge ?> px-2 py-1 small"><?= esc($stLabel) ?></span>
                                    </td>
                                    <td class="small">
                                        <i class="bi bi-person-badge text-muted me-1"></i> <?= esc($case['responsible_party'] ?? 'Pengawas') ?>
                                    </td>
                                    <td class="small fw-mono">
                                        <?php if (!empty($case['applicable_decision_id'])): ?>
                                            <span class="badge bg-light text-primary border border-primary px-2 py-1">
                                                <?= esc($case['applicable_decision_id']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php
                                            $evStatus = $case['evidence_status']['status'] ?? 'INSPECTION_REQUIRED';
                                            $evBadge = match($evStatus) {
                                                'SUFFICIENT' => 'text-success',
                                                'EVIDENCE_REQUIRED', 'ACTION_REQUIRED' => 'text-warning',
                                                default => 'text-muted',
                                            };
                                        ?>
                                        <span class="<?= $evBadge ?> fw-semibold">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem; vertical-align: middle;"></i> <?= esc($evStatus) ?>
                                        </span>
                                        <?php if (isset($case['authentic_evidence_count'])): ?>
                                            <small class="d-block text-muted"><?= esc($case['authentic_evidence_count']) ?> bukti otentik</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= base_url('admin/cbt/review/detail/' . esc($case['case_id'])) ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="Periksa Detail">
                                            <i class="bi bi-search me-1"></i> Periksa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan <?= count($queue) ?> dari <?= esc($pagination['total_items']) ?> kasus
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                            <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/cbt/review?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

