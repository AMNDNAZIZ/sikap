<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-danger"><i class="bi bi-shield-exclamation me-2"></i> Penanganan Kasus BP/BK</h3>
            <p class="text-muted mb-0">Alur Workflow Penanganan Kasus, Audit Log, Timeline Kronologis, & Catatan Konseling Rahasia BK</p>
        </div>
        <div>
            <button class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKasusBaru">
                <i class="bi bi-plus-circle me-1"></i> Catat Kasus Baru
            </button>
        </div>
    </div>

    <!-- Alert Flash Status -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Banner Standard Workflow Penanganan Kasus -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient text-white" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
        <div class="card-body p-4">
            <h6 class="fw-bold text-white mb-3 text-uppercase tracking-wider"><i class="bi bi-diagram-3 me-2"></i> Standard Operating Procedure (SOP) Penanganan Kasus ESPATA:</h6>
            <div class="row g-2 text-center align-items-center fs-7 fw-semibold">
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">1. Laporan</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">2. Verifikasi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">3. Identifikasi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">4. Asesmen</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">5. Rencana</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">6. Intervensi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">7. Kolaborasi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">8. Monitoring</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">9. Evaluasi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-danger w-100 py-2 rounded-pill shadow-sm">10. Selesai / Rujukan</span></div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fs-7 fw-bold">Total Kasus</div>
                        <h3 class="fw-bold mb-0 text-primary"><?= $stats['total'] ?></h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4"><i class="bi bi-folder-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fs-7 fw-bold">Dalam Penanganan</div>
                        <h3 class="fw-bold mb-0 text-warning"><?= $stats['active'] ?></h3>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-4"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-purple">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fs-7 fw-bold">Dirujuk Eksternal</div>
                        <h3 class="fw-bold mb-0 text-purple"><?= $stats['referral'] ?></h3>
                    </div>
                    <div class="bg-purple-subtle text-purple p-3 rounded-circle fs-4"><i class="bi bi-hospital-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fs-7 fw-bold">Kasus Selesai</div>
                        <h3 class="fw-bold mb-0 text-success"><?= $stats['closed'] ?></h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle fs-4"><i class="bi bi-check-circle-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Box -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="get" action="<?= base_url('admin/bk/kasus') ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-search me-1"></i> Pencarian Siswa / Kode Kasus</label>
                        <input type="text" name="search" class="form-control rounded-3" placeholder="Nama, NISN, atau Kode Kasus..." value="<?= esc($filters['search'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-building me-1"></i> Filter Kelas</label>
                        <select name="class_id" class="form-select rounded-3">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $cl) : ?>
                                <option value="<?= $cl['id'] ?>" <?= ($filters['class_id'] == $cl['id']) ? 'selected' : '' ?>><?= esc($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-flag me-1"></i> Status Workflow</label>
                        <select name="status" class="form-select rounded-3">
                            <option value="">Semua Status Status</option>
                            <option value="DRAFT" <?= ($filters['status'] == 'DRAFT') ? 'selected' : '' ?>>1. DRAFT</option>
                            <option value="REPORTED" <?= ($filters['status'] == 'REPORTED') ? 'selected' : '' ?>>2. REPORTED (Laporan)</option>
                            <option value="VERIFIED" <?= ($filters['status'] == 'VERIFIED') ? 'selected' : '' ?>>3. VERIFIED (Terverifikasi)</option>
                            <option value="IN_ASSESSMENT" <?= ($filters['status'] == 'IN_ASSESSMENT') ? 'selected' : '' ?>>4. IN_ASSESSMENT (Asesmen)</option>
                            <option value="IN_PROGRESS" <?= ($filters['status'] == 'IN_PROGRESS') ? 'selected' : '' ?>>5. IN_PROGRESS (Intervensi)</option>
                            <option value="MONITORING" <?= ($filters['status'] == 'MONITORING') ? 'selected' : '' ?>>6. MONITORING (Monitoring)</option>
                            <option value="REFERRED" <?= ($filters['status'] == 'REFERRED') ? 'selected' : '' ?>>7. REFERRED (Rujukan)</option>
                            <option value="RESOLVED" <?= ($filters['status'] == 'RESOLVED') ? 'selected' : '' ?>>8. RESOLVED (Selesai)</option>
                            <option value="CLOSED" <?= ($filters['status'] == 'CLOSED') ? 'selected' : '' ?>>9. CLOSED (Ditutup)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-speedometer2 me-1"></i> Bobot Severity</label>
                        <select name="severity" class="form-select rounded-3">
                            <option value="">Semua Bobot</option>
                            <option value="Ringan" <?= ($filters['severity'] == 'Ringan') ? 'selected' : '' ?>>Ringan</option>
                            <option value="Sedang" <?= ($filters['severity'] == 'Sedang') ? 'selected' : '' ?>>Sedang</option>
                            <option value="Berat" <?= ($filters['severity'] == 'Berat') ? 'selected' : '' ?>>Berat</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-tags me-1"></i> Kategori Kasus</label>
                        <select name="category" class="form-select rounded-3">
                            <option value="">Semua Kategori</option>
                            <option value="Kedisiplinan" <?= ($filters['category'] == 'Kedisiplinan') ? 'selected' : '' ?>>Kedisiplinan</option>
                            <option value="Bullying" <?= ($filters['category'] == 'Bullying') ? 'selected' : '' ?>>Bullying / Perundungan</option>
                            <option value="Akademik/Belajar" <?= ($filters['category'] == 'Akademik/Belajar') ? 'selected' : '' ?>>Akademik / Belajar</option>
                            <option value="Gadget/Game" <?= ($filters['category'] == 'Gadget/Game') ? 'selected' : '' ?>>Gadget / Game</option>
                            <option value="Sosialisasi" <?= ($filters['category'] == 'Sosialisasi') ? 'selected' : '' ?>>Sosialisasi / Pergaulan</option>
                            <option value="Keluarga" <?= ($filters['category'] == 'Keluarga') ? 'selected' : '' ?>>Masalah Keluarga</option>
                            <option value="Perilaku/Emosi" <?= ($filters['category'] == 'Perilaku/Emosi') ? 'selected' : '' ?>>Perilaku / Emosi</option>
                            <option value="Lainnya" <?= ($filters['category'] == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold"><i class="bi bi-funnel-fill me-1"></i> Filter Kasus</button>
                        <a href="<?= base_url('admin/bk/kasus') ?>" class="btn btn-light rounded-3 px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Tabel Penanganan Kasus -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-table text-danger me-2"></i> Daftar Kasus & Timeline Penanganan</h5>
            <span class="badge bg-danger-subtle text-danger fw-bold px-3 py-2 rounded-pill">Total: <?= count($cases) ?> Kasus</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Kasus & Tgl</th>
                            <th>Siswa & Kelas</th>
                            <th>Sumber & Pihak Terkait</th>
                            <th>Kategori & Severity</th>
                            <th>Status Workflow</th>
                            <th>Kerahasiaan</th>
                            <th class="text-center pe-4">Aksi Penanganan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cases)) : ?>
                            <?php foreach ($cases as $c) : ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-danger"><i class="bi bi-hash me-1"></i><?= esc($c['case_code'] ?: 'KASUS-' . $c['id']) ?></div>
                                        <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($c['incident_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><i class="bi bi-person-fill text-info me-1"></i><?= esc($c['student_name']) ?></div>
                                        <small class="text-muted">NISN: <?= esc($c['nisn'] ?: '-') ?> | Kelas: <span class="badge bg-secondary-subtle text-secondary"><?= esc($c['class_name'] ?: '-') ?></span></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><i class="bi bi-person-up me-1 text-primary"></i><?= esc($c['source']) ?> (<?= esc($c['reporter_type']) ?>)</div>
                                        <?php if (!empty($c['related_parties'])) : ?>
                                            <small class="text-muted d-block text-truncate" style="max-width: 180px;" title="<?= esc($c['related_parties']) ?>"><i class="bi bi-people me-1"></i><?= esc($c['related_parties']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-bold d-block mb-1 text-wrap"><?= esc($c['category']) ?></span>
                                        <?php if ($c['severity'] == 'Berat') : ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i> Berat</span>
                                        <?php elseif ($c['severity'] == 'Sedang') : ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i> Sedang</span>
                                        <?php else : ?>
                                            <span class="badge bg-info text-dark">Ringan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'DRAFT'         => 'bg-secondary text-white',
                                            'REPORTED'      => 'bg-info text-white',
                                            'VERIFIED'      => 'bg-primary text-white',
                                            'IN_ASSESSMENT' => 'bg-warning text-dark',
                                            'IN_PROGRESS'   => 'bg-primary text-white',
                                            'MONITORING'    => 'bg-purple text-white',
                                            'REFERRED'      => 'bg-dark text-warning',
                                            'RESOLVED'      => 'bg-success text-white',
                                            'CLOSED'        => 'bg-dark text-white',
                                        ];
                                        $bgClass = $statusBadges[$c['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $bgClass ?> rounded-pill px-3 py-1 fw-bold">
                                            <i class="bi bi-arrow-repeat me-1"></i><?= esc($c['status']) ?>
                                        </span>
                                        <small class="d-block text-muted mt-1 fs-7"><i class="bi bi-diagram-2 me-1"></i><?= esc($c['workflow_step'] ?: 'Laporan') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($c['is_confidential'] == 1) : ?>
                                            <span class="badge bg-danger-subtle text-danger fw-bold" title="Catatan Rahasia Guru BK"><i class="bi bi-lock-fill me-1"></i> Rahasia BK</span>
                                        <?php else : ?>
                                            <span class="badge bg-light text-muted border"><i class="bi bg-unlock me-1"></i> Umum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-danger rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                                Aksi & Timeline
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                <li><a class="dropdown-item py-2" href="#" onclick="showDetailKasus(<?= $c['id'] ?>)"><i class="bi bi-clock-history text-primary me-2"></i> Timeline & Detail</a></li>
                                                <li><a class="dropdown-item py-2" href="#" onclick="openUpdateStatusModal(<?= $c['id'] ?>, '<?= $c['status'] ?>')"><i class="bi bi-arrow-left-right text-warning me-2"></i> Ganti Status Workflow</a></li>
                                                <li><a class="dropdown-item py-2" href="#" onclick="openAddActionModal(<?= $c['id'] ?>)"><i class="bi bi-plus-circle text-success me-2"></i> Catat Tindakan / Sesi</a></li>
                                                <li><a class="dropdown-item py-2" href="#" onclick="openAddReferralModal(<?= $c['id'] ?>)"><i class="bi bi-hospital text-purple me-2"></i> Rujukan Eksternal</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="<?= base_url('admin/bk/kasus/delete/' . $c['id']) ?>" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh data kasus ini?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-trash me-2"></i> Hapus Kasus</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-shield-check fs-1 text-success mb-3 d-block"></i>
                                    <h5>Belum ada kasus penanganan siswa yang dicatat.</h5>
                                    <p class="text-muted">Klik "Catat Kasus Baru" untuk menambahkan pengaduan / penanganan kasus.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL CATAT KASUS BARU ==================== -->
<div class="modal fade" id="modalKasusBaru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-danger text-white border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i> Input Penanganan Kasus BK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/bk/kasus/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Pilih Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Siswa (ESPATA) --</option>
                                <?php foreach ($students as $st) : ?>
                                    <option value="<?= $st['id'] ?>">
                                        <?= esc($st['name']) ?> - NISN: <?= esc($st['nisn'] ?: '-') ?> (<?= esc($st['class_name'] ?: 'Tanpa Kelas') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sumber Laporan <span class="text-danger">*</span></label>
                            <select name="source" class="form-select rounded-3" required>
                                <option value="Rujukan Internal">Rujukan Internal (Guru/Wali Kelas)</option>
                                <option value="Pengaduan">Pengaduan Siswa / Teman</option>
                                <option value="Observasi BK">Observasi Mandiri Guru BK</option>
                                <option value="Laporan Orang Tua">Laporan Orang Tua / Wali</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Pelapor / Pihak Terkait</label>
                            <input type="text" name="related_parties" class="form-control rounded-3" placeholder="Contoh: Wali Kelas 8A, Guru Piket, Orang Tua">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori Kasus <span class="text-danger">*</span></label>
                            <select name="category" class="form-select rounded-3" required>
                                <option value="Kedisiplinan">Kedisiplinan / Ketertiban</option>
                                <option value="Bullying">Bullying / Perundungan</option>
                                <option value="Akademik/Belajar">Akademik / Motivasi Belajar</option>
                                <option value="Gadget/Game">Kecanduan Gadget / Game Online</option>
                                <option value="Sosialisasi">Sosialisasi / Konflik Teman</option>
                                <option value="Keluarga">Keluarga / Broken Home</option>
                                <option value="Perilaku/Emosi">Emosi / Perilaku Agresif</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bobot Penanganan <span class="text-danger">*</span></label>
                            <select name="severity" class="form-select rounded-3" required>
                                <option value="Ringan">Ringan (Bimbingan Rutin)</option>
                                <option value="Sedang">Sedang (Konseling Khusus)</option>
                                <option value="Berat">Berat (Panggilan OT / Rujukan)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status Awal Workflow</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="REPORTED" selected>REPORTED (Laporan Masuk)</option>
                                <option value="VERIFIED">VERIFIED (Terverifikasi)</option>
                                <option value="IN_ASSESSMENT">IN_ASSESSMENT (Asesmen)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Laporan / Kejadian</label>
                            <input type="date" name="incident_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Deskripsi Kasus / Kejadian Umum <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Rincian kronologi kejadian atau isi pengaduan umum..." required></textarea>
                        </div>

                        <!-- Catatan Konseling Rahasia BK (Confidentiality) -->
                        <div class="col-12 bg-light p-3 rounded-3 border border-danger-subtle">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_confidential" id="chkConfidential" value="1" checked>
                                <label class="form-check-input-label fw-bold text-danger" for="chkConfidential">
                                    <i class="bi bi-lock-fill me-1"></i> Aktifkan Proteksi Kerahasiaan Catatan Konseling BK
                                </label>
                            </div>
                            <label class="form-label fw-bold text-dark fs-7">Catatan Konseling Rahasia Guru BK (Hanya Bisa Dibaca Konselor/Guru BK)</label>
                            <textarea name="confidential_notes" class="form-control rounded-3" rows="3" placeholder="Tuliskan catatan khusus, diagnosis emosional, atau latar belakang keluarga yang bersifat rahasia..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold"><i class="bi bi-save me-1"></i> Simpan Kasus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL TRANSISI STATUS WORKFLOW ==================== -->
<div class="modal fade" id="modalStatusWorkflow" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> Transisi Status Workflow Kasus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formStatusWorkflow" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Status Baru (SOP Workflow ESPATA) <span class="text-danger">*</span></label>
                        <select name="status" id="status_new" class="form-select rounded-3" required>
                            <option value="DRAFT">1. DRAFT (Draft Laporan)</option>
                            <option value="REPORTED">2. REPORTED (Laporan Masuk)</option>
                            <option value="VERIFIED">3. VERIFIED (Kasus Terverifikasi)</option>
                            <option value="IN_ASSESSMENT">4. IN_ASSESSMENT (Asesmen & Identifikasi)</option>
                            <option value="IN_PROGRESS">5. IN_PROGRESS (Intervensi & Pendampingan)</option>
                            <option value="MONITORING">6. MONITORING (Monitoring & Evaluasi)</option>
                            <option value="REFERRED">7. REFERRED (Rujukan Eksternal)</option>
                            <option value="RESOLVED">8. RESOLVED (Kasus Selesai Ditangani)</option>
                            <option value="CLOSED">9. CLOSED (Kasus Ditutup Permanen)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Perubahan Status (Masuk Audit Log)</label>
                        <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Alasan perubahan status atau simpulan hasil penanganan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold"><i class="bi bi-check-circle me-1"></i> Update Status & Record Audit Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL TAMBAH TINDAKAN / TIMELINE ==================== -->
<div class="modal fade" id="modalAddAction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Tambah Catatan Tindakan (Timeline Kronologis)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddAction" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Tindakan <span class="text-danger">*</span></label>
                            <input type="date" name="action_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jenis Tindakan <span class="text-danger">*</span></label>
                            <select name="action_type" class="form-select rounded-3" required>
                                <option value="Konseling Individual">Konseling Individual</option>
                                <option value="Konseling Kelompok">Konseling Kelompok</option>
                                <option value="Panggilan OT">Panggilan Orang Tua / Wali</option>
                                <option value="Home Visit">Home Visit (Kunjungan Rumah)</option>
                                <option value="Surat Peringatan">Surat Peringatan / Perjanjian</option>
                                <option value="Pendampingan">Pendampingan Psikologis</option>
                                <option value="Monitoring">Monitoring Kejadian</option>
                                <option value="Penutupan Kasus">Penutupan Kasus</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Rincian Kejadian / Tindakan yang Dilakukan <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Uraian tindakan yang dilakukan oleh petugas..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hasil Tindakan</label>
                            <textarea name="result_notes" class="form-control rounded-3" rows="2" placeholder="Hasil respon siswa/orang tua..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tindak Lanjut</label>
                            <textarea name="next_step" class="form-control rounded-3" rows="2" placeholder="Rencana tindakan berikutnya..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Petugas Pelaksana</label>
                            <select name="performed_by" class="form-select rounded-3">
                                <?php foreach ($teachers as $t) : ?>
                                    <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-save me-1"></i> Simpan Catatan Tindakan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL RUJUKAN EKSTERNAL ==================== -->
<div class="modal fade" id="modalAddReferral" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-purple text-white border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-hospital me-2"></i> Input Rujukan Eksternal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddReferral" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tujuan Rujukan <span class="text-danger">*</span></label>
                            <select name="referral_target" class="form-select rounded-3" required>
                                <option value="Psikolog">Psikolog Klinik</option>
                                <option value="Psikiater">Psikiater / Rumah Sakit</option>
                                <option value="Kepolisian">Kepolisian / PPA</option>
                                <option value="Rumah Sakit">Fasilitas Kesehatan / Rumah Sakit</option>
                                <option value="Dinas Sosial">Dinas Sosial / UPTD PPA</option>
                                <option value="Pihak Profesional Lain">Pihak Profesional Lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Lembaga / Profesional <span class="text-danger">*</span></label>
                            <input type="text" name="target_name" class="form-control rounded-3" placeholder="Contoh: RSUD Kota - Poliklinik Jiwa / Klinik Psikologi X" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Rujukan</label>
                            <input type="date" name="referral_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status Rujukan</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="Proses" selected>Proses Rujukan</option>
                                <option value="Aktif">Aktif Penanganan Eksternal</option>
                                <option value="Selesai">Selesai Ditangani</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Alasan Rujukan <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control rounded-3" rows="3" placeholder="Jelaskan indikasi medis/psikologis yang membutuhkan penanganan pihak profesional..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Rekomendasi / Hasil yang Diterima</label>
                            <textarea name="recommendation_received" class="form-control rounded-3" rows="2" placeholder="Catatan rekomendasi profesional setelah rujukan dilakukan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-purple rounded-pill px-4 fw-bold text-white"><i class="bi bi-send me-1"></i> Kirim Rujukan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL DETAIL & TIMELINE KRONOLOGIS KASUS ==================== -->
<div class="modal fade" id="modalDetailKasus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history text-danger me-2"></i> Timeline Kronologis & Audit Log Kasus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detail-kasus-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">Memuat timeline kronologis kasus...</p>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3">
                <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openUpdateStatusModal(caseId, currentStatus) {
        document.getElementById('formStatusWorkflow').action = '<?= base_url('admin/bk/kasus/update-status/') ?>' + caseId;
        document.getElementById('status_new').value = currentStatus;
        const modal = new bootstrap.Modal(document.getElementById('modalStatusWorkflow'));
        modal.show();
    }

    function openAddActionModal(caseId) {
        document.getElementById('formAddAction').action = '<?= base_url('admin/bk/kasus/add-action/') ?>' + caseId;
        const modal = new bootstrap.Modal(document.getElementById('modalAddAction'));
        modal.show();
    }

    function openAddReferralModal(caseId) {
        document.getElementById('formAddReferral').action = '<?= base_url('admin/bk/kasus/add-referral/') ?>' + caseId;
        const modal = new bootstrap.Modal(document.getElementById('modalAddReferral'));
        modal.show();
    }

    function showDetailKasus(caseId) {
        const body = document.getElementById('detail-kasus-modal-body');
        body.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-danger" role="status"></div><p class="mt-2 text-muted">Memuat timeline kronologis kasus...</p></div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalDetailKasus'));
        modal.show();

        fetch('<?= base_url('admin/bk/kasus/detail/') ?>' + caseId)
            .then(res => res.json())
            .then(res => {
                if (res.status) {
                    const d = res.data;
                    const actions = res.actions || [];
                    const referrals = res.referrals || [];
                    const logs = res.logs || [];
                    const isGuruBk = res.isGuruBk;

                    // Timeline items HTML
                    let timelineHtml = '';
                    if (actions.length > 0) {
                        actions.forEach((act, idx) => {
                            timelineHtml += `
                                <div class="position-relative ps-4 pb-4 border-start border-3 border-danger">
                                    <div class="position-absolute top-0 start-0 translate-middle bg-danger rounded-circle text-white p-1" style="width: 24px; height: 24px; font-size: 10px; text-align: center;">${idx+1}</div>
                                    <div class="card border-0 shadow-sm rounded-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-danger-subtle text-danger fw-bold">${act.action_type}</span>
                                                <small class="text-muted"><i class="bi bi-calendar-event me-1"></i>${act.action_date}</small>
                                            </div>
                                            <p class="mb-2 text-dark"><strong>Kejadian / Tindakan:</strong> ${act.description}</p>
                                            <div class="row g-2 fs-7">
                                                <div class="col-md-4"><strong>Petugas:</strong> ${act.performer_name || act.performer_username || 'Guru BK'}</div>
                                                <div class="col-md-4"><strong>Hasil:</strong> ${act.result_notes || '-'}</div>
                                                <div class="col-md-4"><strong>Tindak Lanjut:</strong> ${act.next_step || '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        timelineHtml = `<p class="text-muted italic">Belum ada kronologi tindakan tambahan.</p>`;
                    }

                    // Audit Log HTML
                    let logHtml = '';
                    if (logs.length > 0) {
                        logHtml = `<table class="table table-sm table-bordered mt-2 fs-7"><thead class="table-light"><tr><th>Waktu</th><th>Status Lama</th><th>Status Baru</th><th>Petugas</th><th>Catatan Audit Log</th></tr></thead><tbody>`;
                        logs.forEach(l => {
                            logHtml += `<tr>
                                <td>${l.created_at}</td>
                                <td><span class="badge bg-secondary">${l.old_status || 'Draft'}</span></td>
                                <td><span class="badge bg-primary">${l.new_status}</span></td>
                                <td>${l.teacher_name || l.username || 'Sistem'}</td>
                                <td>${l.notes || '-'}</td>
                            </tr>`;
                        });
                        logHtml += `</tbody></table>`;
                    } else {
                        logHtml = `<p class="text-muted italic">Belum ada log perubahan status.</p>`;
                    }

                    // Referral HTML
                    let refHtml = '';
                    if (referrals.length > 0) {
                        refHtml = `<div class="p-3 bg-purple-subtle rounded-3 border border-purple mb-3"><h6 class="fw-bold text-purple"><i class="bi bi-hospital me-1"></i> Rujukan Eksternal</h6>`;
                        referrals.forEach(r => {
                            refHtml += `<p class="mb-1"><strong>Target:</strong> ${r.referral_target} - ${r.target_name} (${r.referral_date})</p><p class="mb-1"><strong>Alasan:</strong> ${r.reason}</p><p class="mb-0"><strong>Status:</strong> <span class="badge bg-purple text-white">${r.status}</span></p>`;
                        });
                        refHtml += `</div>`;
                    }

                    body.innerHTML = `
                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-danger fs-5"><i class="bi bi-hash"></i>${d.case_code}</span>
                                    <div>
                                        <span class="badge bg-primary me-1">${d.status}</span>
                                        <span class="badge bg-danger">${d.severity}</span>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-dark mb-1">${d.student_name} (${d.class_name || 'Tanpa Kelas'})</h4>
                                <p class="text-muted mb-2"><i class="bi bi-calendar-event me-1"></i>Tgl Kejadian: <strong>${d.incident_date}</strong> | Kategori: <strong>${d.category}</strong> | Pelapor: <strong>${d.reporter_name || d.reporter_type || 'Internal'}</strong></p>
                                <p class="mb-0"><strong>Deskripsi Kejadian Utama:</strong> ${d.description}</p>
                            </div>
                        </div>

                        ${refHtml}

                        <!-- Section Catatan Konseling Rahasia BK -->
                        <div class="card border border-danger-subtle bg-danger-subtle mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-lock-fill me-1"></i> Catatan Konseling Rahasia BK</h6>
                                <p class="mb-0 fw-semibold text-dark">${d.confidential_notes || 'Tidak ada catatan rahasia khusus.'}</p>
                            </div>
                        </div>

                        <!-- Workflow Stepper Status Visual -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3 me-2 text-danger"></i> Tampilan Timeline Penanganan Kronologis: Tanggal ↓ Kejadian ↓ Tindakan ↓ Petugas ↓ Hasil ↓ Tindak Lanjut</h6>
                            <div class="timeline-container ps-2">
                                ${timelineHtml}
                            </div>
                        </div>

                        <!-- Section Audit Log Perubahan Status -->
                        <div class="mt-4">
                            <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-file-earmark-lock me-1"></i> Audit Log Perubahan Status Workflow</h6>
                            ${logHtml}
                        </div>
                    `;
                }
            });
    }
</script>
<?= $this->endSection() ?>
