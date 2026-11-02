<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Breadcrumb & Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/cbt/review') ?>">Review Queue</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page"><?= esc($case['case_id']) ?></li>
            </ol>
        </nav>
        <a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean
        </a>
    </div>

    <!-- Case Header Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary px-2 py-1 fw-bold"><?= esc($case['case_id']) ?></span>
                        <span class="badge bg-light text-dark border"><?= esc($case['category']) ?></span>
                        <span class="badge bg-warning text-dark"><?= esc($case['review_status']) ?></span>
                        <?php 
                            $readiness = $case['evidence_readiness']['readiness_status'] ?? 'PENDING';
                            $readinessBadge = match($readiness) {
                                'READY_FOR_AUTHORIZED_REVIEW' => 'bg-success',
                                'INSUFFICIENT_EVIDENCE'       => 'bg-danger',
                                'INVALID_EVIDENCE'            => 'bg-danger',
                                'UNAUTHORIZED_ACTOR'          => 'bg-dark',
                                'POLICY_VIOLATION'            => 'bg-danger',
                                default                       => 'bg-secondary',
                            };
                        ?>
                        <span class="badge <?= $readinessBadge ?>"><i class="bi bi-shield-lock me-1"></i> <?= esc($readiness) ?></span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark"><?= esc($case['subject_name'] ?? 'Ujian') ?></h4>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-person me-1"></i> Siswa: <span class="fw-mono fw-semibold"><?= esc($case['pseudonymous_student_id']) ?></span>
                        <span class="mx-2">·</span>
                        <i class="bi bi-journal-check me-1"></i> Test ID: <span class="fw-semibold"><?= esc($case['test_id']) ?></span>
                        <span class="mx-2">·</span>
                        <i class="bi bi-clock-history me-1"></i> Sesi: <span class="fw-semibold"><?= esc($case['session_id'] ?? 'Tidak Tercatat') ?></span>
                    </p>
                </div>
                <div class="text-md-end">
                    <span class="text-muted small d-block">Pihak Bertanggung Jawab:</span>
                    <span class="badge bg-info text-dark fs-6 px-3 py-1 mt-1">
                        <i class="bi bi-person-badge me-1"></i> <?= esc($case['responsible_party'] ?? 'Reviewer') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Metrics, Policy & Evidence -->
        <div class="col-12 col-lg-7">

            <!-- Comparative Score Metrics Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-line me-1 text-primary"></i> Metrik Komparasi Nilai</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted text-uppercase d-block mb-1">Skor Legacy</small>
                                <span class="fs-4 fw-bold text-dark">
                                    <?= $case['legacy_score'] !== null ? esc((string)$case['legacy_score']) : '<span class="text-muted small">NULL</span>' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted text-uppercase d-block mb-1">Skor V3</small>
                                <span class="fs-4 fw-bold text-primary">
                                    <?= $case['v3_score'] !== null ? esc((string)$case['v3_score']) : '<span class="text-muted small">HOLD (NULL)</span>' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted text-uppercase d-block mb-1">Delta (V3 - Leg)</small>
                                <span class="fs-4 fw-bold text-secondary">
                                    <?= $case['delta'] !== null ? esc((string)$case['delta']) : '<span class="text-muted small">N/A</span>' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-2 bg-light rounded small text-muted">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Status Komparabilitas:</span>
                            <span class="fw-semibold <?= !empty($case['is_comparable']) ? 'text-success' : 'text-danger' ?>">
                                <?= !empty($case['is_comparable']) ? 'KOMPARABEL (NUMERIK)' : 'NON-KOMPARABEL (SEMANTIK / HOLD)' ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Relevant Status (V3):</span>
                            <span class="fw-semibold text-dark"><?= esc($case['relevant_status'] ?? 'UNKNOWN') ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Alasan Masuk Review:</span>
                            <span class="fw-semibold text-dark"><?= esc($case['evidence_reference'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stage F3H / Iteration 2: Side-by-Side Telemetry vs Proctor Digital BAP Card -->
            <?php if (!empty($case['bap_evidence']) || !empty($case['telemetry_evidence'])): ?>
                <div class="card border-0 shadow-sm mb-4 border-start border-4 border-warning" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-layout-split me-1 text-warning"></i> Komparasi Side-by-Side: Telemetri CBT vs Berita Acara (BAP)
                        </h6>
                        <span class="badge bg-warning text-dark"><i class="bi bi-shield-lock me-1"></i> OVERRIDE-SYNC-POLICY</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <!-- Left: Server Telemetry -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary text-white"><i class="bi bi-hdd-network me-1"></i> Telemetri Server</span>
                                        <small class="text-muted fw-mono">Sesi #<?= esc($case['telemetry_evidence']['session_id'] ?? 'Tidak Tercatat') ?></small>
                                    </div>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-1"><strong>Status Sesi:</strong> <span class="badge bg-secondary"><?= esc($case['telemetry_evidence']['status'] ?? 'NONE') ?></span></li>
                                        <li class="mb-1"><strong>Jawaban Terekam:</strong> <span class="fw-bold text-dark"><?= esc($case['telemetry_evidence']['answer_count'] ?? 0) ?> Soal</span></li>
                                        <li class="mb-1"><strong>Skor Terekam:</strong> <?= isset($case['telemetry_evidence']['score']) ? esc((string)$case['telemetry_evidence']['score']) : '<span class="text-muted">NULL / HOLD</span>' ?></li>
                                        <li class="mb-1"><strong>Alamat IP:</strong> <span class="fw-mono"><?= esc($case['telemetry_evidence']['ip_address'] ?? '-') ?></span></li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Right: Proctor Physical/Digital BAP -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-check me-1"></i> BAP Digital Pengawas</span>
                                        <small class="text-muted fw-mono"><?= esc($case['bap_evidence']['room_code'] ?? 'Ruang') ?></small>
                                    </div>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-1"><strong>No BAP:</strong> <span class="fw-mono"><?= esc($case['bap_evidence']['bap_number'] ?? '-') ?></span></li>
                                        <li class="mb-1"><strong>Pengawas Ruang:</strong> <?= esc($case['bap_evidence']['proctor_name'] ?? '-') ?></li>
                                        <li class="mb-1"><strong>Status Dilaporkan:</strong> <span class="badge bg-danger"><?= esc($case['bap_evidence']['reported_status'] ?? 'ABSENT') ?></span></li>
                                        <li class="mb-1"><strong>Catatan Pengawas:</strong> <span class="text-muted fst-italic"><?= esc($case['bap_evidence']['proctor_notes'] ?? 'Tanpa catatan khusus') ?></span></li>
                                        <li class="mb-1"><strong>Waktu Penandatanganan:</strong> <?= esc($case['bap_evidence']['signed_at'] ?? '-') ?></li>
                                        <li><strong>Hash SHA-256:</strong> <span class="fw-mono small text-truncate d-block" style="max-width: 200px;" title="<?= esc($case['bap_evidence']['document_sha256'] ?? '-') ?>"><?= esc($case['bap_evidence']['document_sha256'] ?? '-') ?></span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 px-3 small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i> <strong>Kebijakan Sinkronisasi Non-Destruktif:</strong> Konflik ini tidak ditimpa secara otomatis. Riwayat telemetri server dan berkas BAP fisik pengawas dipelihara berdampingan secara utuh untuk ajudikasi tim verifikator.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stage F3H / Iteration 2: Candidate Retake Sessions Table (DEC-RET-01) -->
            <?php if (!empty($case['retake_sessions'])): ?>
                <div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Daftar Sesi Ujian Siswa (Kandidat Retake)
                        </h6>
                        <span class="badge bg-primary px-2 py-1">DEC-RET-01</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Sesi</th>
                                        <th>Mulai Ujian</th>
                                        <th>Selesai Ujian</th>
                                        <th>Durasi</th>
                                        <th>Status Sesi</th>
                                        <th>Skor</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($case['retake_sessions'] as $s): ?>
                                        <tr>
                                            <td class="fw-mono fw-bold">#<?= esc($s['session_id']) ?></td>
                                            <td><?= esc($s['start_time'] ?? '-') ?></td>
                                            <td><?= esc($s['submit_time'] ?? '-') ?></td>
                                            <td><?= esc($s['duration'] ?? '-') ?></td>
                                            <td><span class="badge bg-secondary"><?= esc($s['status'] ?? 'FINISHED') ?></span></td>
                                            <td class="fw-bold text-primary"><?= esc((string)($s['score'] ?? 'NULL')) ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-select-session" data-session-id="<?= esc($s['session_id']) ?>">
                                                    Pilih Sesi Ini
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block">
                            <i class="bi bi-shield-lock me-1"></i> Sesuai PODR DEC-RET-01, pemilihan otomatis (skor tertinggi atau tanggal terakhir) dilarang. Keputusan wajib memilih sesi secara eksplisit dan menyertakan nomor persetujuan resmi.
                        </small>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PODR Policy Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-shield-check me-1 text-success"></i> Kebijakan Resmi Product Owner (PODR)
                    </h6>
                    <span class="badge bg-success px-2 py-1"><?= esc($case['applicable_decision_id']) ?></span>
                </div>
                <div class="card-body p-3">
                    <div class="alert alert-light border mb-3">
                        <h6 class="fw-bold mb-1 text-dark"><?= esc($case['podr_policy']['title'] ?? '') ?></h6>
                        <span class="badge bg-secondary mb-2">Mode: <?= esc($case['podr_policy']['policy_mode'] ?? '') ?></span>
                        <p class="small text-muted mb-0">
                            <?= esc($case['podr_policy']['summary'] ?? '') ?>
                        </p>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-lock me-1"></i> Pihak Berwenang: 
                        <strong class="text-dark"><?= implode(', ', $case['podr_policy']['authorized_roles'] ?? ['Reviewer', 'Admin']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Evidence Breakdown & Verification Level -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-search me-1 text-info"></i> Bukti Pendukung &amp; Provenance Data</h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <h6 class="small fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-1"></i> Fakta Terverifikasi:</h6>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($case['evidence_breakdown']['available'] as $ev): ?>
                                <li class="list-group-item px-0 py-1 text-muted border-0">
                                    <i class="bi bi-arrow-right-short text-success me-1"></i> <?= esc($ev) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php if (!empty($case['evidence_breakdown']['missing'])): ?>
                        <div class="mb-3">
                            <h6 class="small fw-bold text-warning mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Bukti Belum Tersedia / Aksi Diperlukan:</h6>
                            <ul class="list-group list-group-flush small">
                                <?php foreach ($case['evidence_breakdown']['missing'] as $gap): ?>
                                    <li class="list-group-item px-0 py-1 text-danger border-0">
                                        <i class="bi bi-x-circle me-1"></i> <?= esc($gap) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Stage F3D Authentic Evidence Registry Table -->
                    <div class="mt-3 mb-3">
                        <h6 class="small fw-bold text-dark mb-2"><i class="bi bi-shield-check me-1 text-primary"></i> Registri Bukti Otentik (Provenance Records):</h6>
                        <?php if (empty($case['authentic_evidence'])): ?>
                            <div class="alert alert-danger py-2 px-3 small mb-2">
                                <i class="bi bi-x-octagon me-1"></i> Tidak ditemukan catatan bukti otentik dalam registri untuk kasus ini.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered small align-middle mb-2">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID Bukti</th>
                                            <th>Tipe Bukti</th>
                                            <th>Sistem Sumber</th>
                                            <th>Status</th>
                                            <th>Verifikator</th>
                                            <th>Waktu Bukti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($case['authentic_evidence'] as $ev): ?>
                                            <tr>
                                                <td class="fw-mono"><?= esc($ev['evidence_id']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= esc($ev['evidence_type']) ?></span></td>
                                                <td class="fw-mono small"><?= esc($ev['source_system']) ?> (<?= esc($ev['source_record_id']) ?>)</td>
                                                <td>
                                                    <?php 
                                                        $evStatus = $ev['verification_status'] ?? 'UNVERIFIED';
                                                        $isAuthoritative = (($ev['auth_semantic_level'] ?? '') === \App\Services\Cbt\Review\ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED)
                                                            && (!empty($ev['is_authoritative_verified']) && !empty($ev['server_infrastructure_verified']));
                                                    ?>
                                                    <?php if ($isAuthoritative): ?>
                                                        <span class="badge bg-success">AUTHORITATIVE_VERIFIED</span>
                                                    <?php elseif ($evStatus === 'VERIFICATION_LOGIC_PASSED'): ?>
                                                        <span class="badge bg-info text-dark" title="Verifikasi logika query lolos di test harness; verifikasi live MariaDB belum dilakukan">VERIFICATION_LOGIC_PASSED</span>
                                                    <?php elseif ($evStatus === 'VERIFIED' || $evStatus === 'VALIDATION_PASSED'): ?>
                                                        <span class="badge bg-info text-dark" title="Format dan aturan logika lolos verifikasi internal shadow fixture">VALIDATION_PASSED (FIXTURE)</span>
                                                    <?php elseif (in_array($evStatus, ['BAP_REVOKED', 'BAP_INVALID', 'GRADER_PROVENANCE_CONFLICT', 'CONFLICT'], true)): ?>
                                                        <span class="badge bg-danger"><?= esc($evStatus) ?></span>
                                                    <?php elseif (in_array($evStatus, ['BAP_NOT_FOUND', 'GRADER_PROVENANCE_UNAVAILABLE', 'NOT_FOUND'], true)): ?>
                                                        <span class="badge bg-warning text-dark"><?= esc($evStatus) ?></span>
                                                    <?php elseif (in_array($evStatus, ['SOURCE_UNAVAILABLE', 'BAP_SOURCE_UNAVAILABLE', 'UNAVAILABLE'], true)): ?>
                                                        <span class="badge bg-secondary"><?= esc($evStatus) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><?= esc($evStatus) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($ev['verified_by']) ?></td>
                                                <td class="text-muted small"><?= esc($ev['evidence_timestamp']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-2 bg-light rounded small text-muted border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Tingkat Verifikasi:</span>
                            <span class="badge bg-secondary"><?= esc($case['verification']['level'] ?? 'CODE_LEVEL_VERIFIED') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Sumber Data:</span>
                            <span class="fw-mono"><?= esc($case['verification']['source_path'] ?? 'shadow_deviations.csv') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Zero-DML Enforcement:</span>
                            <span class="text-success fw-semibold"><?= esc($case['verification']['zero_dml_status'] ?? 'VERIFIED_IN_SANDBOX') ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Database Live Verification:</span>
                            <span class="text-muted fw-semibold"><?= esc($case['verification']['database_verification'] ?? 'MOCK_SANDBOX_ONLY') ?></span>
                        </div>
                    </div>

                    <!-- Privacy & Pseudonymization Notice -->
                    <div class="alert alert-secondary py-2 px-3 small mb-0 border-0">
                        <i class="bi bi-person-lock me-1"></i> <strong>Privasi Data Siswa:</strong> Identitas siswa dipseudonimkan menggunakan HMAC-SHA256 ber-salt. Tautan identitas asli dilindungi secara kriptografis dan tidak dapat dibuka tanpa otorisasi DPO.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Interactive Decision Preview Form -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Decision Preview (Simulasi)</h6>
                    <span class="badge bg-warning text-dark"><i class="bi bi-eye me-1"></i> NON-MUTATING</span>
                </div>
                <div class="card-body p-3">
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Mode Preview:</strong> Evaluasi konsekuensi dan validasi tata kelola tanpa mengubah database atau queue operasional.
                    </div>

                    <form id="previewForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="case_id" value="<?= esc($case['case_id']) ?>">
                        <input type="hidden" name="decision_id" value="<?= esc($case['applicable_decision_id']) ?>">
                        <input type="hidden" name="actor_role" value="<?= esc($currentUser['actor_role']) ?>">

                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Rencana Tindakan (Sanctioned Action)</label>
                            <select name="action" id="actionSelect" class="form-select form-select-sm" required>
                                <option value="">Pilih Tindakan...</option>
                                <?php foreach ($case['allowed_actions'] as $act): ?>
                                    <option value="<?= esc($act) ?>"><?= esc($act) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dynamic Param: Retake selection -->
                        <?php if ($case['applicable_decision_id'] === 'DEC-RET-01'): ?>
                            <div class="mb-3 p-2 bg-light rounded border">
                                <label class="form-label small text-dark fw-semibold mb-1">Parameter DEC-RET-01</label>
                                <input type="text" name="selected_session_id" class="form-control form-control-sm mb-2" placeholder="ID Sesi Ujian Terpilih (e.g. 1008)">
                                <input type="text" name="owner_approval_ref" class="form-control form-control-sm" placeholder="Nomor SK/Ref Persetujuan Owner (e.g. SK-KUR-2026-001)">
                                <small class="text-muted d-block mt-1">Pemilihan otomatis dilarang. Wajib melampirkan owner_approval_ref.</small>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Param: Absence verification notice -->
                        <?php if ($case['applicable_decision_id'] === 'DEC-ABS-01'): ?>
                            <div class="mb-3 p-2 bg-light rounded border">
                                <div class="small fw-semibold text-dark mb-1">
                                    <i class="bi bi-shield-exclamation text-warning me-1"></i> Kebijakan Absensi DEC-ABS-01
                                </div>
                                <small class="text-muted d-block">
                                    Konfirmasi absensi wajib ditopang bukti berita acara absensi fisik pengawas dalam registri. Checklist manual dari browser tidak dapat menggantikan bukti registri.
                                </small>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Param: OVERRIDE-SYNC-POLICY -->
                        <?php if ($case['applicable_decision_id'] === 'OVERRIDE-SYNC-POLICY'): ?>
                            <div class="mb-3 p-2 bg-light rounded border">
                                <div class="small fw-semibold text-dark mb-1">
                                    <i class="bi bi-shield-check text-success me-1"></i> Kebijakan OVERRIDE-SYNC-POLICY
                                </div>
                                <input type="text" name="owner_approval_ref" class="form-control form-control-sm mb-1" placeholder="Ref Persetujuan (wajib jika GRANT_RETEST)">
                                <small class="text-muted d-block">
                                    CONFIRM_ABSENCE mempertahankan nilai NULL (DEC-ABS-01). RATIFY_SESSION meratifikasi sesi telemetri siswa. Data kedua pihak tetap dipertahankan non-destruktif.
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Identitas Reviewer</label>
                            <input type="text" name="reviewer_id" value="<?= esc($currentUser['username']) ?>" class="form-control form-control-sm bg-light" readonly>
                            <small class="text-muted">Aktor: <?= esc($currentUser['actor_role']) ?> (Role ID: <?= esc($currentUser['role_id']) ?>)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Alasan / Justifikasi Reviewer <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Tuliskan justifikasi formal yang akan dicatat pada audit trail..." required></textarea>
                        </div>

                        <button type="submit" id="btnRunPreview" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">
                            <i class="bi bi-play-circle me-1"></i> Simulasikan Keputusan
                        </button>
                    </form>

                    <!-- Preview Outcome Container (Dynamically rendered) -->
                    <div id="previewOutcomeContainer" class="mt-4" style="display: none;">
                        <hr class="text-muted">
                        <h6 class="fw-bold mb-2 small text-uppercase" id="previewTitle">Hasil Simulasi</h6>
                        
                        <div id="previewAlert" class="alert py-2 px-3 small mb-2"></div>

                        <div id="previewDetailsCard" class="card border bg-light p-2 mb-2 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Status Sekarang:</span>
                                <span class="fw-semibold" id="prevCurrentStatus">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Status Proyeksi:</span>
                                <span class="fw-semibold text-success" id="prevProjectedStatus">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Pihak Bertanggung Jawab:</span>
                                <span class="fw-semibold" id="prevProjectedParty">-</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Decision ID:</span>
                                <span class="fw-semibold text-primary" id="prevDecisionId">-</span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <h6 class="small fw-bold text-dark mb-1">Konsekuensi Semantik:</h6>
                            <ul id="prevConsequencesList" class="list-group list-group-flush small bg-transparent"></ul>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="small fw-bold text-dark mb-0">Simulasi Payload Audit Trail:</h6>
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">SHA-256 Chained</span>
                            </div>
                            <pre id="prevAuditPayload" class="bg-dark text-light p-2 rounded small fw-mono mb-0" style="font-size: 0.75rem; max-height: 180px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Audit Records for this Case -->
            <?php if (!empty($case['audit_history'])): ?>
                <div class="card border-0 shadow-sm mt-4" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-1 text-secondary"></i> Riwayat Audit Kasus Ini</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline small">
                            <?php foreach ($case['audit_history'] as $entry): ?>
                                <div class="border-start border-2 border-primary ps-3 pb-3">
                                    <span class="text-muted d-block mb-1" style="font-size: 0.75rem;"><?= esc($entry['timestamp_iso']) ?> · Seq #<?= esc($entry['sequence_number']) ?></span>
                                    <strong class="text-dark d-block"><?= esc($entry['decision']) ?> by <?= esc($entry['reviewer_id']) ?></strong>
                                    <span class="text-muted d-block"><?= esc($entry['reason']) ?></span>
                                    <span class="badge bg-light text-muted border mt-1 fw-mono" style="font-size: 0.65rem;">Hash: <?= substr(esc($entry['record_hash']), 0, 16) ?>...</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewForm = document.getElementById('previewForm');
    const outcomeContainer = document.getElementById('previewOutcomeContainer');
    const previewAlert = document.getElementById('previewAlert');
    const prevCurrentStatus = document.getElementById('prevCurrentStatus');
    const prevProjectedStatus = document.getElementById('prevProjectedStatus');
    const prevProjectedParty = document.getElementById('prevProjectedParty');
    const prevDecisionId = document.getElementById('prevDecisionId');
    const prevConsequencesList = document.getElementById('prevConsequencesList');
    const prevAuditPayload = document.getElementById('prevAuditPayload');
    const btnRunPreview = document.getElementById('btnRunPreview');

    previewForm.addEventListener('submit', function (e) {
        e.preventDefault();

        btnRunPreview.disabled = true;
        btnRunPreview.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengevaluasi Tata Kelola...';

        const formData = new FormData(previewForm);

        fetch('<?= base_url('admin/cbt/review/preview') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(result => {
            btnRunPreview.disabled = false;
            btnRunPreview.innerHTML = '<i class="bi bi-play-circle me-1"></i> Simulasikan Keputusan';

            outcomeContainer.style.display = 'block';

            const data = result.body;
            if (data.success && data.preview && data.preview.is_valid) {
                previewAlert.className = 'alert alert-success py-2 px-3 small mb-2';
                previewAlert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> <strong>Valid:</strong> ' + escapeHtml(data.message);

                const prev = data.preview;
                prevCurrentStatus.textContent = prev.current_status;
                prevProjectedStatus.textContent = prev.projected_status;
                prevProjectedParty.textContent = prev.projected_responsible_party;
                prevDecisionId.textContent = prev.decision_id || '-';

                prevConsequencesList.innerHTML = '';
                (prev.consequences || []).forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item px-0 py-1 text-muted border-0';
                    li.innerHTML = '<i class="bi bi-arrow-right-circle text-primary me-1"></i> ' + escapeHtml(item);
                    prevConsequencesList.appendChild(li);
                });

                prevAuditPayload.textContent = JSON.stringify(prev.simulated_audit_entry, null, 2);
            } else {
                previewAlert.className = 'alert alert-danger py-2 px-3 small mb-2';
                const errMsg = (data.preview && data.preview.error) ? data.preview.error : (data.message || 'Validasi tata kelola gagal.');
                previewAlert.innerHTML = '<i class="bi bi-shield-x me-1"></i> <strong>Ditolak:</strong> ' + escapeHtml(errMsg);

                if (data.preview) {
                    prevCurrentStatus.textContent = data.preview.current_status || '-';
                    prevProjectedStatus.textContent = '(Tidak Berubah)';
                    prevProjectedParty.textContent = data.preview.current_responsible_party || '-';
                    prevDecisionId.textContent = '-';
                    prevConsequencesList.innerHTML = '<li class="list-group-item px-0 py-1 text-danger border-0">' + escapeHtml(errMsg) + '</li>';
                    prevAuditPayload.textContent = 'Tidak ada payload audit yang dihasilkan karena validasi gagal.';
                }
            }
        })
        .catch(err => {
            btnRunPreview.disabled = false;
            btnRunPreview.innerHTML = '<i class="bi bi-play-circle me-1"></i> Simulasikan Keputusan';
            outcomeContainer.style.display = 'block';
            previewAlert.className = 'alert alert-danger py-2 px-3 small mb-2';
            previewAlert.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-1"></i> Terjadi kesalahan jaringan: ' + escapeHtml(err.message);
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-fill selected session input when clicking "Pilih Sesi Ini"
    document.querySelectorAll('.btn-select-session').forEach(btn => {
        btn.addEventListener('click', function () {
            const sessId = this.getAttribute('data-session-id');
            const inputSess = document.querySelector('input[name="selected_session_id"]');
            if (inputSess) {
                inputSess.value = sessId;
                inputSess.classList.add('is-valid');
                inputSess.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            const actSelect = document.getElementById('actionSelect');
            if (actSelect) {
                for (let i = 0; i < actSelect.options.length; i++) {
                    if (actSelect.options[i].value === 'SELECT_ATTEMPT' || actSelect.options[i].value === 'RATIFY_SESSION') {
                        actSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>

