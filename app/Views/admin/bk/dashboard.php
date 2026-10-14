<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="rounded-4 shadow-sm border-0 p-4 d-flex justify-content-between align-items-center flex-wrap gap-3"
                 style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%) !important; min-height: 90px;">
                <div>
                    <h2 class="h3 fw-bold mb-1" style="color:#fff !important;">
                        <i class="bi bi-heart-pulse-fill me-2"></i> Dashboard Bimbingan Konseling (BP/BK)
                    </h2>
                    <p class="mb-0" style="color:rgba(255,255,255,0.80) !important;">
                        Sistem Informasi BK ESPATA &mdash; Berorientasi Tindakan, Pemantauan Sinyal &amp; Presisi Data
                        <?php if (!empty($activeYear)) : ?>
                            &bull; <strong style="color:#fff !important;">Tahun Ajaran <?= esc($activeYear['year']) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-light btn-sm rounded-pill fw-bold text-danger shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalQuickKasus">
                        <i class="bi bi-plus-circle-fill me-1"></i> Catat Kasus
                    </button>
                    <button class="btn btn-light btn-sm rounded-pill fw-bold text-primary shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalQuickLayanan">
                        <i class="bi bi-journal-plus me-1"></i> Input Layanan
                    </button>
                    <a href="<?= base_url('admin/bk/siswa') ?>" class="btn btn-dark btn-sm rounded-pill fw-bold shadow-sm px-3">
                        <i class="bi bi-person-lines-fill me-1"></i> Profil Siswa BK
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Success -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 8 ACTION-ORIENTED KPI METRIC CARDS (SETIAP INDIKATOR DAPAT DIKLIK) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Siswa -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/siswa') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">1. Siswa Aktif</span>
                                <h3 class="fw-bold mb-0 text-primary mt-1"><?= number_format($totalStudents) ?></h3>
                                <span class="badge bg-primary-subtle text-primary mt-2">
                                    TA <?= esc($activeYear['year'] ?? '-') ?> <i class="bi bi-arrow-right me-1"></i>
                                </span>
                            </div>
                            <div class="avatar-lg bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Siswa Menerima Layanan -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/layanan') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-info card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">2. Menerima Layanan</span>
                                <h3 class="fw-bold mb-0 text-info mt-1"><?= number_format($servedStudentsCount) ?></h3>
                                <span class="badge bg-info-subtle text-info mt-2">Siswa Terjangkau <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-check-fill fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Layanan Bulan Berjalan -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/layanan') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">3. Layanan Bulan Ini</span>
                                <h3 class="fw-bold mb-0 text-success mt-1"><?= number_format($currentMonthServices) ?></h3>
                                <span class="badge bg-success-subtle text-success mt-2">Realisasi Jurnal <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-journal-check fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. Kasus Aktif -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/kasus?status=IN_PROGRESS') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-danger card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">4. Kasus Aktif</span>
                                <h3 class="fw-bold mb-0 text-danger mt-1"><?= number_format($activeCasesCount) ?></h3>
                                <span class="badge bg-danger-subtle text-danger mt-2">Dalam Intervensi <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-shield-exclamation fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 5. Kasus Dalam Monitoring -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/kasus?status=MONITORING') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">5. Dalam Monitoring</span>
                                <h3 class="fw-bold mb-0 text-warning mt-1"><?= number_format($monitoringCasesCount) ?></h3>
                                <span class="badge bg-warning-subtle text-warning mt-2">Evaluasi Berkala <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-warning-subtle text-warning rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-hourglass-split fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 6. Tindak Lanjut Jatuh Tempo / Belum Selesai -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/kasus?status=IN_PROGRESS') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-dark card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">6. Tindak Lanjut Belum Selesai</span>
                                <h3 class="fw-bold mb-0 text-dark mt-1"><?= number_format($pendingFollowUpsCount) ?></h3>
                                <span class="badge bg-dark-subtle text-dark mt-2">Perlu Follow-up <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-dark-subtle text-dark rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-clock-history fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 7. Rujukan Eksternal -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/kasus?status=REFERRED') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-purple card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">7. Rujukan Eksternal</span>
                                <h3 class="fw-bold mb-0 text-purple mt-1"><?= number_format($referralsCount) ?></h3>
                                <span class="badge bg-purple-subtle text-purple mt-2">Alih Tangan Kasus <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-purple-subtle text-purple rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-hospital-fill fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 8. Program BK Berjalan -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= base_url('admin/bk/program') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">8. Program BK Berjalan</span>
                                <h3 class="fw-bold mb-0 text-primary mt-1"><?= number_format($activeProgramsCount) ?></h3>
                                <span class="badge bg-primary-subtle text-primary mt-2">Prota & Prosem BK <i class="bi bi-arrow-right me-1"></i></span>
                            </div>
                            <div class="avatar-lg bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar3-range-fill fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- ==================== BAGIAN "PERLU PERHATIAN" (ACTION-REQUIRED EARLY WARNING PANEL) ==================== -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-exclamation-octagon-fill text-warning me-2"></i> Bagian "PERLU PERHATIAN" (Deteksi Pola Data ESPATA)</h5>
                <small class="text-muted">Siswa yang membutuhkan verifikasi, monitoring, atau tindak lanjut berdasarkan kombinasi data absensi, nilai, & catatan observasi.</small>
            </div>
            <a href="<?= base_url('admin/bk/siswa') ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-bold">
                <i class="bi bi-search me-1"></i> Kelola Pemantauan Siswa
            </a>
        </div>
        <div class="card-body p-4">
            <!-- Disclaimer Etika Professional BK -->
            <div class="alert alert-light border shadow-sm rounded-3 mb-4 text-dark fs-7">
                <i class="bi bi-info-circle-fill text-primary me-2 fs-6"></i>
                <strong>Prinsip Etika Pendampingan BK:</strong> 
                <em>Sistem tidak memberikan label negatif atau diagnosis terhadap siswa. Indikator di bawah mendeteksi akumulasi pola data objektif untuk memberikan alur aksi cepat bagi Guru BK dalam memverifikasi dan mengambil tindakan yang tepat.</em>
            </div>

            <div class="row g-4">
                <!-- 🟡 Indikator 1: Kehadiran Menurun -->
                <div class="col-lg-6">
                    <div class="card border shadow-sm rounded-4 h-100">
                        <div class="card-header bg-danger-subtle text-danger py-2 fw-bold rounded-top-4 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar-x me-2"></i> Kehadiran Menurun (Akumulasi Alpa $\ge 2$)</span>
                            <span class="badge bg-danger text-white rounded-pill"><?= count($lowAttendanceStudents) ?> Siswa</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="ps-3">Siswa</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Total Alpa</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($lowAttendanceStudents)) : ?>
                                            <?php foreach ($lowAttendanceStudents as $st) : ?>
                                                <tr>
                                                    <td class="ps-3 fw-semibold"><?= esc($st['name']) ?></td>
                                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= esc($st['class_name'] ?: '-') ?></span></td>
                                                    <td class="text-center fw-bold text-danger"><?= $st['total_alpa'] ?> Hari</td>
                                                    <td class="text-center pe-3">
                                                        <a href="<?= base_url('admin/bk/siswa/' . $st['id']) ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1">
                                                            Profil & Aksi
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Tidak terdeteksi siswa dengan penurunan kehadiran signifikan.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🟡 Indikator 2: Capaian Akademik Menurun -->
                <div class="col-lg-6">
                    <div class="card border shadow-sm rounded-4 h-100">
                        <div class="card-header bg-warning-subtle text-dark py-2 fw-bold rounded-top-4 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-bar-chart-down me-2"></i> Capaian Akademik Menurun (Rata-rata $< 75$)</span>
                            <span class="badge bg-warning text-dark rounded-pill"><?= count($lowAcademicStudents) ?> Siswa</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="ps-3">Siswa</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Rata-rata</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($lowAcademicStudents)) : ?>
                                            <?php foreach ($lowAcademicStudents as $st) : ?>
                                                <tr>
                                                    <td class="ps-3 fw-semibold"><?= esc($st['name']) ?></td>
                                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= esc($st['class_name'] ?: '-') ?></span></td>
                                                    <td class="text-center fw-bold text-warning"><?= $st['avg_score'] ?></td>
                                                    <td class="text-center pe-3">
                                                        <a href="<?= base_url('admin/bk/siswa/' . $st['id']) ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1">
                                                            Profil & Aksi
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Tidak terdeteksi siswa dengan penurunan akademik signifikan.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🟡 Indikator 3: Perubahan Perilaku (Catatan Guru) -->
                <div class="col-lg-6">
                    <div class="card border shadow-sm rounded-4 h-100">
                        <div class="card-header bg-info-subtle text-info py-2 fw-bold rounded-top-4 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-chat-left-dots me-2"></i> Catatan Perilaku & Observasi Guru Mapel/Wali</span>
                            <span class="badge bg-info text-white rounded-pill"><?= count($behaviorNotesStudents) ?> Siswa</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="ps-3">Siswa</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Jumlah Catatan</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($behaviorNotesStudents)) : ?>
                                            <?php foreach ($behaviorNotesStudents as $st) : ?>
                                                <tr>
                                                    <td class="ps-3 fw-semibold"><?= esc($st['name']) ?></td>
                                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= esc($st['class_name'] ?: '-') ?></span></td>
                                                    <td class="text-center fw-bold text-info"><?= $st['total_notes'] ?> Entri Catatan</td>
                                                    <td class="text-center pe-3">
                                                        <a href="<?= base_url('admin/bk/siswa/' . $st['id']) ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1">
                                                            Profil & Aksi
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada entri catatan observasi perilaku baru.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🟡 Indikator 4: Tindak Lanjut Belum Selesai / Kasus Aktif -->
                <div class="col-lg-6">
                    <div class="card border shadow-sm rounded-4 h-100">
                        <div class="card-header bg-dark text-white py-2 fw-bold rounded-top-4 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-hourglass-split me-2 text-warning"></i> Tindak Lanjut Belum Selesai & Kasus Aktif</span>
                            <span class="badge bg-warning text-dark rounded-pill"><?= count($activeCaseStudents) ?> Kasus</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="ps-3">Kode Kasus & Siswa</th>
                                            <th>Kategori</th>
                                            <th>Status Workflow</th>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($activeCaseStudents)) : ?>
                                            <?php foreach ($activeCaseStudents as $c) : ?>
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="fw-bold text-danger"><?= esc($c['case_code']) ?></div>
                                                        <small class="text-dark fw-semibold"><?= esc($c['name']) ?></small>
                                                    </td>
                                                    <td><span class="badge bg-primary-subtle text-primary"><?= esc($c['category']) ?></span></td>
                                                    <td><span class="badge bg-warning text-dark"><?= esc($c['status']) ?></span></td>
                                                    <td class="text-center pe-3">
                                                        <a href="<?= base_url('admin/bk/kasus') ?>" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1">
                                                            Workflow
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada kasus aktif / tindak lanjut pending saat ini.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Quick Catat Kasus -->
<div class="modal fade" id="modalQuickKasus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4 px-4 pt-4">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-plus-circle-fill me-2"></i> Input Penanganan Kasus Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/bk/kasus/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" id="selectSiswaKasus" class="form-select rounded-3" required>
                                <option value="">-- Ketik Nama / NISN / Kelas Siswa --</option>
                                <?php foreach ($studentsList as $s) : ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= esc($s['name']) ?> [<?= esc($s['class_name'] ?: 'Tanpa Kelas') ?>] - NISN: <?= esc($s['nisn'] ?: '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori Kasus <span class="text-danger">*</span></label>
                            <select name="category" class="form-select rounded-3" required>
                                <option value="Kedisiplinan">Kedisiplinan</option>
                                <option value="Bullying">Bullying / Perundungan</option>
                                <option value="Akademik/Belajar">Akademik / Belajar</option>
                                <option value="Gadget/Game">Kecanduan Gadget / Game</option>
                                <option value="Sosialisasi">Sosialisasi / Pergaulan</option>
                                <option value="Keluarga">Masalah Keluarga</option>
                                <option value="Perilaku/Emosi">Perilaku / Emosi</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tingkat Bobot <span class="text-danger">*</span></label>
                            <select name="severity" class="form-select rounded-3" required>
                                <option value="Ringan">Ringan (Bimbingan Rutin)</option>
                                <option value="Sedang">Sedang (Perlu Konseling Khusus)</option>
                                <option value="Berat">Berat (Perlu Pemanggilan OT / Rujukan)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Kejadian</label>
                            <input type="date" name="incident_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Kejadian / Keluhan <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Jelaskan ringkasan kasus atau keluhan..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Kasus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Quick Input Layanan -->
<div class="modal fade" id="modalQuickLayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4 px-4 pt-4">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-journal-plus me-2"></i> Input Realisasi Layanan BK</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/bk/layanan/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Layanan <span class="text-danger">*</span></label>
                            <select name="service_type" class="form-select rounded-3" required>
                                <option value="Bimbingan Klasikal">Bimbingan Klasikal (Masuk Kelas)</option>
                                <option value="Bimbingan Kelompok">Bimbingan Kelompok</option>
                                <option value="Konseling Individual">Konseling Individual</option>
                                <option value="Konseling Kelompok">Konseling Kelompok</option>
                                <option value="Konsultasi">Konsultasi (Guru/OT)</option>
                                <option value="Home Visit">Home Visit (Kunjungan Rumah)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bidang BK <span class="text-danger">*</span></label>
                            <select name="field" class="form-select rounded-3" required>
                                <option value="Pribadi">Pribadi</option>
                                <option value="Sosial">Sosial</option>
                                <option value="Belajar">Belajar</option>
                                <option value="Karir">Karir</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Kelas (Jika Klasikal/Kelompok)</label>
                            <select name="class_id" class="form-select rounded-3">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classesList as $cl) : ?>
                                    <option value="<?= $cl['id'] ?>"><?= esc($cl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan</label>
                            <input type="date" name="service_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Topik / Materi Layanan <span class="text-danger">*</span></label>
                            <input type="text" name="topic" class="form-control rounded-3" placeholder="Misal: Cara Efektif Mengatur Waktu Belajar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Ringkasan Kegiatan & Evaluasi</label>
                            <textarea name="activity_summary" class="form-control rounded-3" rows="3" placeholder="Ringkasan proses bimbingan dan hasil yang dicapai..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Layanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
