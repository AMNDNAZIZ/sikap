<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-primary"><i class="bi bi-journal-check me-2"></i> Fitur Layanan BP/BK</h3>
            <p class="text-muted mb-0">Manajemen Layanan Bimbingan & Konseling sesuai Alur BK: Rencana → Penjadwalan → Peserta → Pelaksanaan → Catatan → Evaluasi → Tindak lanjut</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLayanan" onclick="resetFormLayanan()">
                <i class="bi bi-plus-circle me-1"></i> Tambah Layanan BK
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

    <!-- Alur Layanan Indicator Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);">
        <div class="card-body p-4">
            <h6 class="fw-bold text-white mb-3 text-uppercase tracking-wider"><i class="bi bi-diagram-3 me-2"></i> Standar Alur Pelayanan BK ESPATA:</h6>
            <div class="row g-2 text-center align-items-center fs-7 fw-semibold">
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">1. Rencana</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">2. Penjadwalan</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">3. Peserta</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">4. Pelaksanaan</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">5. Catatan</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">6. Evaluasi</span></div>
                <div class="col-auto"><i class="bi bi-chevron-right text-white"></i></div>
                <div class="col"><span class="badge bg-white text-primary w-100 py-2 rounded-pill shadow-sm">7. Tindak Lanjut</span></div>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Box -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="get" action="<?= base_url('admin/bk/layanan') ?>">
                <div class="row g-3">
                    <!-- Pencarian Siswa / Topik / NISN -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-search me-1"></i> Pencarian Siswa / Topik</label>
                        <input type="text" name="search" class="form-control rounded-3" placeholder="Cari nama, NISN, atau topik..." value="<?= esc($filters['search'] ?? '') ?>">
                    </div>

                    <!-- Filter Kelas/Rombel Selector Existing ESPATA -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-building me-1"></i> Filter Kelas</label>
                        <select name="class_id" class="form-select rounded-3">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $cl) : ?>
                                <option value="<?= $cl['id'] ?>" <?= ($filters['class_id'] == $cl['id']) ? 'selected' : '' ?>>
                                    <?= esc($cl['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran Selector Existing ESPATA -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-calendar3 me-1"></i> Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select rounded-3">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($academicYears as $ay) : ?>
                                <option value="<?= $ay['id'] ?>" <?= ($filters['academic_year_id'] == $ay['id'] || (empty($filters['academic_year_id']) && $activeYear && $activeYear['id'] == $ay['id'])) ? 'selected' : '' ?>>
                                    <?= esc($ay['year']) ?> <?= ($ay['is_active'] ? '(Aktif)' : '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Konselor Selector Existing ESPATA -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-person-badge me-1"></i> Konselor / Guru</label>
                        <select name="counselor_id" class="form-select rounded-3">
                            <option value="">Semua Guru/BK</option>
                            <?php foreach ($teachers as $t) : ?>
                                <option value="<?= $t['id'] ?>" <?= ($filters['counselor_id'] == $t['id']) ? 'selected' : '' ?>>
                                    <?= esc($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Jenis Layanan -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-grid-3x3-gap me-1"></i> Jenis Layanan</label>
                        <select name="service_type" class="form-select rounded-3">
                            <option value="">Semua Jenis Layanan</option>
                            <option value="Bimbingan Klasikal" <?= ($filters['service_type'] == 'Bimbingan Klasikal') ? 'selected' : '' ?>>1. Bimbingan Klasikal</option>
                            <option value="Bimbingan Kelompok" <?= ($filters['service_type'] == 'Bimbingan Kelompok') ? 'selected' : '' ?>>2. Bimbingan Kelompok</option>
                            <option value="Konseling Individual" <?= ($filters['service_type'] == 'Konseling Individual') ? 'selected' : '' ?>>3. Konseling Individual</option>
                            <option value="Konseling Kelompok" <?= ($filters['service_type'] == 'Konseling Kelompok') ? 'selected' : '' ?>>4. Konseling Kelompok</option>
                            <option value="Konsultasi" <?= ($filters['service_type'] == 'Konsultasi') ? 'selected' : '' ?>>5. Konsultasi</option>
                            <option value="Rujukan" <?= ($filters['service_type'] == 'Rujukan') ? 'selected' : '' ?>>6. Rujukan (Referral)</option>
                            <option value="Home Visit" <?= ($filters['service_type'] == 'Home Visit') ? 'selected' : '' ?>>7. Home Visit (Kunjungan)</option>
                            <option value="Orientasi" <?= ($filters['service_type'] == 'Orientasi') ? 'selected' : '' ?>>8. Orientasi</option>
                            <option value="Informasi" <?= ($filters['service_type'] == 'Informasi') ? 'selected' : '' ?>>9. Informasi</option>
                            <option value="Penempatan" <?= ($filters['service_type'] == 'Penempatan') ? 'selected' : '' ?>>10. Penempatan / Penyaluran</option>
                            <option value="Lainnya" <?= ($filters['service_type'] == 'Lainnya') ? 'selected' : '' ?>>11. Layanan Lainnya</option>
                        </select>
                    </div>

                    <!-- Filter Tanggal -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-calendar-event me-1"></i> Tanggal</label>
                        <input type="date" name="date" class="form-control rounded-3" value="<?= esc($filters['date'] ?? '') ?>">
                    </div>

                    <!-- Filter Status Layanan -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-flag me-1"></i> Status Layanan</label>
                        <select name="status" class="form-select rounded-3">
                            <option value="">Semua Status Alur</option>
                            <option value="Rencana" <?= ($filters['status'] == 'Rencana') ? 'selected' : '' ?>>Rencana</option>
                            <option value="Penjadwalan" <?= ($filters['status'] == 'Penjadwalan') ? 'selected' : '' ?>>Penjadwalan</option>
                            <option value="Pelaksanaan" <?= ($filters['status'] == 'Pelaksanaan') ? 'selected' : '' ?>>Pelaksanaan</option>
                            <option value="Evaluasi" <?= ($filters['status'] == 'Evaluasi') ? 'selected' : '' ?>>Evaluasi</option>
                            <option value="Tindak Lanjut" <?= ($filters['status'] == 'Tindak Lanjut') ? 'selected' : '' ?>>Tindak Lanjut</option>
                            <option value="Selesai" <?= ($filters['status'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                            <option value="Batal" <?= ($filters['status'] == 'Batal') ? 'selected' : '' ?>>Batal</option>
                        </select>
                    </div>

                    <!-- Tombol Aksi Filter -->
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                            <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                        </button>
                        <a href="<?= base_url('admin/bk/layanan') ?>" class="btn btn-light rounded-3 px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Tabel Layanan BK -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-table text-primary me-2"></i> Daftar Jurnal & Realisasi Layanan BK</h5>
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">Total Data: <?= count($services) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tanggal & Jam</th>
                            <th>Jenis & Bidang Layanan</th>
                            <th>Status Alur</th>
                            <th>Topik / Judul</th>
                            <th>Sasaran / Siswa / Kelas</th>
                            <th>Konselor</th>
                            <th class="text-center">Presensi / Peserta</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)) : ?>
                            <?php foreach ($services as $srv) : ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><i class="bi bi-calendar-event me-1 text-primary"></i><?= date('d M Y', strtotime($srv['service_date'])) ?></div>
                                        <?php if (!empty($srv['start_time'])) : ?>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= substr($srv['start_time'], 0, 5) ?> <?= $srv['end_time'] ? '- ' . substr($srv['end_time'], 0, 5) : '' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info fw-bold d-block mb-1 text-wrap"><?= esc($srv['service_type']) ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= esc($srv['field']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'Rencana'     => 'bg-warning text-dark',
                                            'Penjadwalan' => 'bg-info text-white',
                                            'Pelaksanaan' => 'bg-primary text-white',
                                            'Evaluasi'    => 'bg-purple text-white',
                                            'Tindak Lanjut' => 'bg-dark text-white',
                                            'Selesai'     => 'bg-success text-white',
                                            'Batal'       => 'bg-danger text-white',
                                        ];
                                        $badgeClass = $statusBadges[$srv['status']] ?? 'bg-secondary text-white';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-arrow-repeat me-1"></i><?= esc($srv['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?= esc($srv['topic']) ?></div>
                                        <?php if (!empty($srv['purpose'])) : ?>
                                            <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= esc($srv['purpose']) ?>"><i class="bi bi-bullseye me-1 text-danger"></i>Tujuan: <?= esc($srv['purpose']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($srv['service_type'] === 'Bimbingan Klasikal') : ?>
                                            <span class="badge bg-primary-subtle text-primary fw-bold fs-7"><i class="bi bi-building me-1"></i>Kelas <?= esc($srv['class_name'] ?: 'Semua Kelas') ?></span>
                                        <?php elseif (!empty($srv['primary_student_name'])) : ?>
                                            <div class="fw-bold text-dark"><i class="bi bi-person-fill text-info me-1"></i><?= esc($srv['primary_student_name']) ?></div>
                                            <small class="text-muted">NISN: <?= esc($srv['primary_student_nisn'] ?: '-') ?> (Kelas <?= esc($srv['class_name'] ?: '-') ?>)</small>
                                        <?php else : ?>
                                            <span class="text-muted fw-semibold"><i class="bi bi-people me-1"></i><?= esc($srv['class_name'] ?: 'Multiple Participants') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><i class="bi bi-person-badge text-secondary me-1"></i><?= esc($srv['counselor_name'] ?: 'Guru BK') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                                            <i class="bi bi-person-check-fill text-success me-1"></i><?= $srv['total_participants'] ?> Siswa
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info rounded-start-pill px-3" onclick="showDetailLayanan(<?= $srv['id'] ?>)">
                                                <i class="bi bi-eye me-1"></i> Detail Alur
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editLayanan(<?= $srv['id'] ?>)">
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <form action="<?= base_url('admin/bk/layanan/delete/' . $srv['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data layanan ini?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-end-pill px-2">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-journal-x fs-1 text-secondary mb-3 d-block"></i>
                                    <h5>Belum ada data Layanan BK yang cocok dengan filter.</h5>
                                    <p class="text-muted">Klik tombol "Tambah Layanan BK" untuk menambah data layanan baru.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL TAMBAH / EDIT LAYANAN BK (ALUR 7 TAHAP) ==================== -->
<div class="modal fade" id="modalLayanan" tabindex="-1" aria-labelledby="modalLayananLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-gradient text-white border-0 px-4 py-3 rounded-top-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);">
                <h5 class="modal-title fw-bold" id="modalLayananLabel"><i class="bi bi-journal-plus me-2"></i> Form Input & Edit Layanan BK (Alur Komprehensif)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formLayanan" action="<?= base_url('admin/bk/layanan/store') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" id="layanan_id" name="id" value="">

                <div class="modal-body p-4">
                    <!-- Nav Tabs Workflow (Alur Layanan) -->
                    <ul class="nav nav-pills nav-fill mb-4 bg-light p-2 rounded-4 gap-1" id="layananTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-3 fw-bold" id="tab-1-tab" data-bs-toggle="tab" data-bs-target="#tab-1" type="button" role="tab"><i class="bi bi-1-circle me-1"></i> Rencana & Jadwal</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 fw-bold" id="tab-2-tab" data-bs-toggle="tab" data-bs-target="#tab-2" type="button" role="tab"><i class="bi bi-2-circle me-1"></i> Peserta & Sasaran</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 fw-bold" id="tab-3-tab" data-bs-toggle="tab" data-bs-target="#tab-3" type="button" role="tab"><i class="bi bi-3-circle me-1"></i> Pelaksanaan & Catatan</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 fw-bold" id="tab-4-tab" data-bs-toggle="tab" data-bs-target="#tab-4" type="button" role="tab"><i class="bi bi-4-circle me-1"></i> Evaluasi & Tindak Lanjut</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="layananTabContent">
                        <!-- TAB 1: RENCANA & PENJADWALAN -->
                        <div class="tab-pane fade show active" id="tab-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">1. Jenis Layanan BK <span class="text-danger">*</span></label>
                                    <select name="service_type" id="service_type" class="form-select rounded-3" onchange="toggleFormByServiceType()" required>
                                        <option value="Bimbingan Klasikal">1. Bimbingan Klasikal (Masuk Kelas)</option>
                                        <option value="Bimbingan Kelompok">2. Bimbingan Kelompok</option>
                                        <option value="Konseling Individual">3. Konseling Individual</option>
                                        <option value="Konseling Kelompok">4. Konseling Kelompok</option>
                                        <option value="Konsultasi">5. Konsultasi (Orang Tua/Guru)</option>
                                        <option value="Rujukan">6. Rujukan / Alih Tangan Kasus (Referral)</option>
                                        <option value="Home Visit">7. Home Visit (Kunjungan Rumah)</option>
                                        <option value="Orientasi">8. Orientasi</option>
                                        <option value="Informasi">9. Informasi</option>
                                        <option value="Penempatan">10. Penempatan / Penyaluran</option>
                                        <option value="Lainnya">11. Layanan Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">2. Bidang BK <span class="text-danger">*</span></label>
                                    <select name="field" id="field" class="form-select rounded-3" required>
                                        <option value="Pribadi">Pribadi</option>
                                        <option value="Sosial">Sosial</option>
                                        <option value="Belajar">Belajar</option>
                                        <option value="Karir">Karir</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">3. Status Alur Layanan <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select rounded-3" required>
                                        <option value="Rencana">1. Rencana</option>
                                        <option value="Penjadwalan">2. Penjadwalan</option>
                                        <option value="Pelaksanaan" selected>3. Pelaksanaan</option>
                                        <option value="Evaluasi">4. Evaluasi</option>
                                        <option value="Tindak Lanjut">5. Tindak Lanjut</option>
                                        <option value="Selesai">6. Selesai</option>
                                        <option value="Batal">7. Batal</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">4. Konselor / Guru BK <span class="text-danger">*</span></label>
                                    <select name="counselor_id" id="counselor_id" class="form-select rounded-3" required>
                                        <option value="">-- Pilih Guru BK --</option>
                                        <?php foreach ($teachers as $t) : ?>
                                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (NIP: <?= esc($t['nip'] ?: '-') ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">5. Tahun Ajaran</label>
                                    <select name="academic_year_id" id="academic_year_id" class="form-select rounded-3">
                                        <?php foreach ($academicYears as $ay) : ?>
                                            <option value="<?= $ay['id'] ?>" <?= ($activeYear && $activeYear['id'] == $ay['id']) ? 'selected' : '' ?>>
                                                <?= esc($ay['year']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">6. Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                    <input type="date" name="service_date" id="service_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Jam Mulai</label>
                                    <input type="time" name="start_time" id="start_time" class="form-control rounded-3" value="08:00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Jam Selesai</label>
                                    <input type="time" name="end_time" id="end_time" class="form-control rounded-3" value="09:00">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">7. Topik / Tema Layanan <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" id="topic" class="form-control rounded-3" placeholder="Contoh: Manajeman Stres Belajar / Penanganan Kedisiplinan Siswa" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">8. Tujuan Layanan</label>
                                    <textarea name="purpose" id="purpose" class="form-control rounded-3" rows="2" placeholder="Tujuan yang ingin dicapai melalui layanan bimbingan/konseling ini..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: PESERTA & SASARAN (SELECTOR ESPATA) -->
                        <div class="tab-pane fade" id="tab-2" role="tabpanel">
                            <div class="row g-3">
                                <!-- Selector Kelas (Bimbingan Klasikal / Kelompok) -->
                                <div class="col-md-6" id="box-class-selector">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label fw-bold text-primary"><i class="bi bi-building me-1"></i> Kelas Sasaran (Bimbingan Klasikal)</label>
                                        <select name="class_id" id="class_id" class="form-select rounded-3" onchange="filterStudentListByClass()">
                                            <option value="">-- Pilih Kelas Sasaran --</option>
                                            <?php foreach ($classes as $cl) : ?>
                                                <option value="<?= $cl['id'] ?>"><?= esc($cl['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted mt-1 d-block">Memilih kelas otomatis mengaitkan peserta bimbingan klasikal.</small>
                                    </div>
                                </div>

                                <!-- Selector Siswa Individual (Konseling Individual / Consultation) -->
                                <div class="col-md-6" id="box-student-selector">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label fw-bold text-info"><i class="bi bi-person-circle me-1"></i> Pilih Siswa (Konseling Individual)</label>
                                        <select name="student_id" id="student_id" class="form-select rounded-3 select2-siswa">
                                            <option value="">-- Pilih Siswa --</option>
                                            <?php foreach ($students as $st) : ?>
                                                <option value="<?= $st['id'] ?>" data-class="<?= $st['class_id'] ?>">
                                                    <?= esc($st['name']) ?> - NISN: <?= esc($st['nisn'] ?: '-') ?> (<?= esc($st['class_name'] ?: 'Tanpa Kelas') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted mt-1 d-block">Filter & cari siswa berdasarkan nama atau NISN.</small>
                                    </div>
                                </div>

                                <!-- Multi-select Peserta Siswa -->
                                <div class="col-12">
                                    <label class="form-label fw-bold"><i class="bi bi-people-fill me-1"></i> Daftar Peserta Layanan / Anggota Kelompok</label>
                                    <div class="table-responsive border rounded-3 style-scroll" style="max-height: 250px;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th width="40" class="text-center">Pilih</th>
                                                    <th>Nama Siswa</th>
                                                    <th>NISN</th>
                                                    <th>Kelas</th>
                                                    <th width="150">Status Presensi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="student-attendance-list">
                                                <?php foreach ($students as $st) : ?>
                                                    <tr class="student-row class-row-<?= $st['class_id'] ?>">
                                                        <td class="text-center">
                                                            <input type="checkbox" name="student_ids[]" value="<?= $st['id'] ?>" class="form-check-input student-checkbox" id="chk_st_<?= $st['id'] ?>">
                                                        </td>
                                                        <td><label for="chk_st_<?= $st['id'] ?>" class="fw-semibold text-dark cursor-pointer"><?= esc($st['name']) ?></label></td>
                                                        <td><?= esc($st['nisn'] ?: '-') ?></td>
                                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= esc($st['class_name'] ?: '-') ?></span></td>
                                                        <td>
                                                            <select name="attendance_status[<?= $st['id'] ?>]" class="form-select form-select-sm rounded-2">
                                                                <option value="Hadir" selected>Hadir</option>
                                                                <option value="Izin">Izin</option>
                                                                <option value="Alpa">Alpa</option>
                                                                <option value="Sakit">Sakit</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: PELAKSANAAN & CATATAN (Dua Tampilan Sesuai Jenis Layanan) -->
                        <div class="tab-pane fade" id="tab-3" role="tabpanel">
                            <!-- Khusus Bimbingan Klasikal -->
                            <div id="section-klasikal">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-easel me-2"></i> Detail Pelaksanaan Bimbingan Klasikal</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Materi Layanan Klasikal</label>
                                        <textarea name="material" id="material" class="form-control rounded-3" rows="3" placeholder="Ringkasan atau poin-poin materi yang disampaikan saat bimbingan di kelas..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Aktivitas & Process Bimbingan</label>
                                        <textarea name="activity_summary" id="activity_summary" class="form-control rounded-3" rows="3" placeholder="Ringkasan alur kegiatan, diskusi kelas, tanya jawab, atau simulasi..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Khusus Konseling Individual / Konsultasi / Rujukan -->
                            <div id="section-individual" style="display: none;">
                                <h6 class="fw-bold text-info mb-3"><i class="bi bi-person-badge me-2"></i> Detail Catatan Konseling Individual / Rujukan</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">Kebutuhan / Keluhan Siswa</label>
                                        <textarea name="needs_complaint" id="needs_complaint" class="form-control rounded-3" rows="3" placeholder="Masalah, kebutuhan, atau indikasi awal yang dikeluhkan/dibahas..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">Hasil Asesmen / Diagnosis</label>
                                        <textarea name="assessment_result" id="assessment_result" class="form-control rounded-3" rows="3" placeholder="Hasil analisa konselor, pemetaan faktor penyebab, atau catatan asesmen BK..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">Catatan Sesi Konseling</label>
                                        <textarea name="session_notes" id="session_notes" class="form-control rounded-3" rows="3" placeholder="Proses wawancara konseling, respon emosional, dinamika pembicaraan..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark">Kesepakatan / Kontrak Perilaku</label>
                                        <textarea name="agreement" id="agreement" class="form-control rounded-3" rows="3" placeholder="Komitmen, kesepakatan perbaikan perilaku, atau langkah perbaikan yang disetujui siswa..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: EVALUASI & TINDAK LANJUT -->
                        <div class="tab-pane fade" id="tab-4" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold"><i class="bi bi-check2-circle text-success me-1"></i> Catatan Evaluasi (Evaluasi Proses & Hasil)</label>
                                    <textarea name="evaluation_notes" id="evaluation_notes" class="form-control rounded-3" rows="3" placeholder="Hasil evaluasi ketercapaian tujuan, tingkat pemahaman siswa, atau perubahan respon..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold"><i class="bi bi-arrow-right-circle text-primary me-1"></i> Rencana Tindak Lanjut</label>
                                    <textarea name="follow_up" id="follow_up" class="form-control rounded-3" rows="3" placeholder="Rencana konseling lanjutan, rujukan eksternal, kunjungan rumah (home visit), atau pemantauan berkala..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold"><i class="bi bi-save me-1"></i> Simpan Layanan BK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL DETAIL ALUR LAYANAN BK ==================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalDetailLabel"><i class="bi bi-card-heading me-2 text-info"></i> Detail Lengkap Alur Layanan BK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detail-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Memuat data alur layanan BK...</p>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3">
                <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleFormByServiceType() {
        const type = document.getElementById('service_type').value;
        const klasikalSec = document.getElementById('section-klasikal');
        const indivSec = document.getElementById('section-individual');

        if (type === 'Bimbingan Klasikal') {
            klasikalSec.style.display = 'block';
            indivSec.style.display = 'none';
        } else {
            klasikalSec.style.display = 'none';
            indivSec.style.display = 'block';
        }
    }

    function filterStudentListByClass() {
        const classId = document.getElementById('class_id').value;
        const rows = document.querySelectorAll('.student-row');

        rows.forEach(row => {
            if (!classId || row.classList.contains('class-row-' + classId)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function resetFormLayanan() {
        document.getElementById('formLayanan').action = '<?= base_url('admin/bk/layanan/store') ?>';
        document.getElementById('layanan_id').value = '';
        document.getElementById('formLayanan').reset();
        toggleFormByServiceType();
        filterStudentListByClass();
    }

    function editLayanan(id) {
        resetFormLayanan();
        fetch('<?= base_url('admin/bk/layanan/detail/') ?>' + id)
            .then(res => res.json())
            .then(res => {
                if (res.status) {
                    const data = res.data;
                    document.getElementById('formLayanan').action = '<?= base_url('admin/bk/layanan/update/') ?>' + id;
                    document.getElementById('layanan_id').value = data.id;

                    document.getElementById('service_type').value = data.service_type || 'Bimbingan Klasikal';
                    document.getElementById('field').value = data.field || 'Pribadi';
                    document.getElementById('status').value = data.status || 'Pelaksanaan';
                    document.getElementById('counselor_id').value = data.counselor_id || '';
                    document.getElementById('academic_year_id').value = data.academic_year_id || '';
                    document.getElementById('class_id').value = data.class_id || '';
                    document.getElementById('student_id').value = data.student_id || '';
                    document.getElementById('service_date').value = data.service_date || '';
                    document.getElementById('start_time').value = data.start_time || '';
                    document.getElementById('end_time').value = data.end_time || '';
                    document.getElementById('topic').value = data.topic || '';
                    document.getElementById('purpose').value = data.purpose || '';
                    document.getElementById('material').value = data.material || '';
                    document.getElementById('needs_complaint').value = data.needs_complaint || '';
                    document.getElementById('assessment_result').value = data.assessment_result || '';
                    document.getElementById('activity_summary').value = data.activity_summary || '';
                    document.getElementById('session_notes').value = data.session_notes || '';
                    document.getElementById('agreement').value = data.agreement || '';
                    document.getElementById('evaluation_notes').value = data.evaluation_notes || '';
                    document.getElementById('follow_up').value = data.follow_up || '';

                    // Centang peserta jika ada
                    if (res.participants && res.participants.length > 0) {
                        res.participants.forEach(p => {
                            const chk = document.getElementById('chk_st_' + p.student_id);
                            if (chk) chk.checked = true;
                        });
                    }

                    toggleFormByServiceType();
                    filterStudentListByClass();

                    const modal = new bootstrap.Modal(document.getElementById('modalLayanan'));
                    modal.show();
                }
            });
    }

    function showDetailLayanan(id) {
        const body = document.getElementById('detail-modal-body');
        body.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat alur layanan BK...</p></div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalDetail'));
        modal.show();

        fetch('<?= base_url('admin/bk/layanan/detail/') ?>' + id)
            .then(res => res.json())
            .then(res => {
                if (res.status) {
                    const d = res.data;
                    const parts = res.participants || [];

                    let partHtml = '';
                    if (parts.length > 0) {
                        partHtml = `<table class="table table-sm table-bordered mt-2"><thead class="table-light"><tr><th>No</th><th>Nama Siswa</th><th>NISN</th><th>Kelas</th><th>Status Presensi</th></tr></thead><tbody>`;
                        parts.forEach((p, idx) => {
                            partHtml += `<tr><td>${idx+1}</td><td><strong>${p.student_name}</strong></td><td>${p.nisn||'-'}</td><td>${p.class_name||'-'}</td><td><span class="badge bg-success">${p.attendance_status}</span></td></tr>`;
                        });
                        partHtml += `</tbody></table>`;
                    } else {
                        partHtml = `<p class="text-muted italic">Tidak ada daftar siswa khusus.</p>`;
                    }

                    body.innerHTML = `
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary fs-7">${d.service_type}</span>
                                    <span class="badge bg-success">${d.status}</span>
                                </div>
                                <h4 class="fw-bold text-dark mb-1">${d.topic}</h4>
                                <p class="text-muted mb-0"><i class="bi bi-calendar-event me-1"></i>${d.service_date} | Bidang: <strong>${d.field}</strong> | Konselor: <strong>${d.counselor_name || '-'}</strong></p>
                            </div>
                        </div>

                        <div class="accordion" id="accAlur">
                            <div class="accordion-item border-0 shadow-sm mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#acc1">
                                        1. Rencana & Penjadwalan
                                    </button>
                                </h2>
                                <div id="acc1" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <p><strong>Tujuan Layanan:</strong> ${d.purpose || '-'}</p>
                                        <p><strong>Tahun Ajaran:</strong> ${d.academic_year_name || '-'}</p>
                                        <p class="mb-0"><strong>Waktu:</strong> ${d.start_time || '-'} s/d ${d.end_time || '-'}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 shadow-sm mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-info" type="button" data-bs-toggle="collapse" data-bs-target="#acc2">
                                        2. Peserta & Sasaran (${parts.length} Siswa)
                                    </button>
                                </h2>
                                <div id="acc2" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p><strong>Kelas Sasaran:</strong> ${d.class_name || 'Individual / Bebas'}</p>
                                        ${partHtml}
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 shadow-sm mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#acc3">
                                        3. Pelaksanaan & Catatan Sesi
                                    </button>
                                </h2>
                                <div id="acc3" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        ${d.service_type === 'Bimbingan Klasikal' ? `
                                            <p><strong>Materi Bimbingan:</strong> ${d.material || '-'}</p>
                                            <p><strong>Aktivitas & Ringkasan Proses:</strong> ${d.activity_summary || '-'}</p>
                                        ` : `
                                            <p><strong>Kebutuhan / Keluhan:</strong> ${d.needs_complaint || '-'}</p>
                                            <p><strong>Hasil Asesmen:</strong> ${d.assessment_result || '-'}</p>
                                            <p><strong>Catatan Sesi Konseling:</strong> ${d.session_notes || '-'}</p>
                                            <p><strong>Kesepakatan / Kontrak Perilaku:</strong> ${d.agreement || '-'}</p>
                                        `}
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 shadow-sm mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-success" type="button" data-bs-toggle="collapse" data-bs-target="#acc4">
                                        4. Evaluasi & Tindak Lanjut
                                    </button>
                                </h2>
                                <div id="acc4" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p><strong>Catatan Evaluasi:</strong> ${d.evaluation_notes || '-'}</p>
                                        <p class="mb-0"><strong>Rencana Tindak Lanjut:</strong> ${d.follow_up || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleFormByServiceType();
    });
</script>
<?= $this->endSection() ?>
