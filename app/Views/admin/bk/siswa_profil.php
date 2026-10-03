<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="<?= base_url('admin/bk/siswa') ?>" class="btn btn-outline-secondary rounded-pill px-3 me-2 mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Siswa
            </a>
            <h3 class="fw-bold mb-1 d-inline-block text-primary align-middle"><i class="bi bi-person-badge-fill me-2"></i> Profil Komprehensif BK Siswa</h3>
        </div>
        <div>
            <a href="<?= base_url('admin/bk/layanan') ?>" class="btn btn-info text-white rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-journal-plus me-1"></i> Input Layanan BK
            </a>
            <a href="<?= base_url('admin/bk/kasus') ?>" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm ms-2">
                <i class="bi bi-shield-plus me-1"></i> Catat Kasus
            </a>
        </div>
    </div>

    <!-- Student Header Summary Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4"
         style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-1 shadow-sm" style="width: 80px; height: 80px; min-width:80px;">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                </div>
                <div class="col">
                    <h3 class="fw-bold mb-1" style="color:#fff !important;"><?= esc($student['name']) ?></h3>
                    <div class="d-flex flex-wrap gap-3" style="font-size:0.85rem;">
                        <span style="color:rgba(255,255,255,0.85) !important;">
                            <i class="bi bi-card-heading me-1" style="color:#67e8f9 !important;"></i>
                            NISN: <strong style="color:#fff !important;"><?= esc($student['nisn'] ?: '-') ?></strong>
                        </span>
                        <span style="color:rgba(255,255,255,0.85) !important;">
                            <i class="bi bi-building me-1" style="color:#fde68a !important;"></i>
                            Kelas: <strong style="color:#fff !important;"><?= esc($student['class_name'] ?: 'Tanpa Kelas') ?></strong>
                        </span>
                        <span style="color:rgba(255,255,255,0.85) !important;">
                            <i class="bi bi-person-badge me-1" style="color:#6ee7b7 !important;"></i>
                            Wali Kelas: <strong style="color:#fff !important;"><?= esc($student['wali_kelas_name'] ?: '-') ?></strong>
                        </span>
                        <span style="color:rgba(255,255,255,0.85) !important;">
                            <i class="bi bi-gender-ambiguous me-1" style="color:#fca5a5 !important;"></i>
                            Gender: <strong style="color:#fff !important;"><?= $student['gender'] == 'L' ? 'Laki-laki' : ($student['gender'] == 'P' ? 'Perempuan' : '-') ?></strong>
                        </span>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <span class="badge bg-primary-subtle text-primary fs-7 px-3 py-2 rounded-pill fw-bold border border-primary-subtle">
                        <i class="bi bi-layers me-1"></i> Integrasi Data Multimodul ESPATA
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOX INDIKATOR "PERLU PERHATIAN / MONITORING / VERIFIKASI" (DETEKSI POLA DATA READ-ONLY) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-5 <?= !empty($signals) ? 'border-warning bg-warning-subtle' : 'border-success bg-success-subtle' ?>">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-activity me-2 text-danger"></i> Sinyal Deteksi Pola Data & Pemantauan Awal (Early Warning System)
                </h5>
                <div>
                    <?php if (count($signals) >= 2) : ?>
                        <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-exclamation-triangle-fill me-1"></i> Status: PERLU PERHATIAN & VERIFIKASI</span>
                    <?php elseif (count($signals) == 1) : ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-eye-fill me-1"></i> Status: PERLU MONITORING BERKALA</span>
                    <?php else : ?>
                        <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> Status: KONDISI TERPANTAU STABIL</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($signals)) : ?>
                <div class="row g-2 mb-3">
                    <?php foreach ($signals as $sig) : ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border border-<?= $sig['type'] ?> shadow-sm h-100">
                                <div class="fw-bold text-<?= $sig['type'] ?> mb-1 fs-6"><i class="bi bi-dot fs-4 align-middle"></i><?= esc($sig['label']) ?></div>
                                <p class="text-dark mb-0 fs-7"><?= esc($sig['description']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-success fw-semibold mb-2"><i class="bi bi-shield-check me-1"></i> Tidak terdeteksi indikasi penurunan absensi, pencapaian akademik di bawah batas, atau kasus penanganan aktif pada siswa ini saat ini.</p>
            <?php endif; ?>

            <!-- DISCLAIMER PENTING ETIKA BK ESPATA -->
            <div class="p-3 bg-white rounded-3 border text-secondary fs-7 mt-2">
                <i class="bi bi-info-circle-fill text-primary me-2 fs-6"></i>
                <strong>Catatan Etika & Verifikasi Guru BK ESPATA:</strong> 
                <em>Sistem hanya mendeteksi akumulasi pola data kuantitatif dari absensi, nilai, dan catatan observasi guru untuk memberikan sinyal pemantauan awal. Sistem TIDAK memberikan diagnosis psikologis atau menyimpulkan kondisi pribadi siswa. Guru BK tetap melakukan verifikasi faktual, dialog, dan pengamatan langsung secara profesional sebelum menentukan tindakan.</em>
            </div>
        </div>
    </div>

    <!-- Nav Tabs Section Profil Komprehensif -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4">
            <ul class="nav nav-pills nav-fill bg-light p-2 rounded-4 gap-1" id="profilBkTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-3 fw-bold" id="tab-identitas-tab" data-bs-toggle="tab" data-bs-target="#tab-identitas" type="button"><i class="bi bi-person me-1"></i> Identitas & Ortu</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 fw-bold" id="tab-absensi-tab" data-bs-toggle="tab" data-bs-target="#tab-absensi" type="button"><i class="bi bi-calendar-check me-1"></i> Kehadiran</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 fw-bold" id="tab-akademik-tab" data-bs-toggle="tab" data-bs-target="#tab-akademik" type="button"><i class="bi bi-journal-bookmark me-1"></i> Akademik</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 fw-bold" id="tab-perilaku-tab" data-bs-toggle="tab" data-bs-target="#tab-perilaku" type="button"><i class="bi bi-chat-left-text me-1"></i> Catatan Perilaku</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 fw-bold" id="tab-layanan-tab" data-bs-toggle="tab" data-bs-target="#tab-layanan" type="button"><i class="bi bi-people me-1"></i> Layanan BK (<?= count($services) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 fw-bold" id="tab-kasus-tab" data-bs-toggle="tab" data-bs-target="#tab-kasus" type="button"><i class="bi bi-shield-exclamation me-1"></i> Kasus (<?= count($cases) ?>)</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="profilBkTabContent">
                
                <!-- TAB 1: IDENTITAS & ORANG TUA -->
                <div class="tab-pane fade show active" id="tab-identitas" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-text me-2"></i> Identitas Diri Siswa</h6>
                            <table class="table table-sm table-striped border rounded-3">
                                <tbody>
                                    <tr><th width="180">Nama Lengkap</th><td><?= esc($student['name']) ?></td></tr>
                                    <tr><th>NISN / NIS</th><td><?= esc($student['nisn'] ?: '-') ?> / <?= esc($student['nis'] ?: '-') ?></td></tr>
                                    <tr><th>NIK</th><td><?= esc($student['nik'] ?: '-') ?></td></tr>
                                    <tr><th>Tempat, Tgl Lahir</th><td><?= esc($student['birth_place'] ?: '-') ?>, <?= !empty($student['birth_date']) ? date('d M Y', strtotime($student['birth_date'])) : '-' ?></td></tr>
                                    <tr><th>Jenis Kelamin</th><td><?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                                    <tr><th>Agama</th><td><?= esc($student['religion'] ?: '-') ?></td></tr>
                                    <tr><th>Kelas / Rombel</th><td><?= esc($student['class_name'] ?: '-') ?></td></tr>
                                    <tr><th>Alamat Tempat Tinggal</th><td><?= esc($student['address'] ?: '-') ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-info mb-3"><i class="bi bi-people-fill me-2"></i> Data Orang Tua / Wali</h6>
                            <table class="table table-sm table-striped border rounded-3">
                                <tbody>
                                    <tr><th width="180">Nama Ayah</th><td><?= esc($student['father_name'] ?: '-') ?></td></tr>
                                    <tr><th>Pekerjaan Ayah</th><td><?= esc($student['father_job'] ?: '-') ?></td></tr>
                                    <tr><th>Nama Ibu</th><td><?= esc($student['mother_name'] ?: '-') ?></td></tr>
                                    <tr><th>Pekerjaan Ibu</th><td><?= esc($student['mother_job'] ?: '-') ?></td></tr>
                                    <tr><th>Nama Wali</th><td><?= esc($student['guardian_name'] ?: '-') ?></td></tr>
                                    <tr><th>Kontak Wali Kelas</th><td><?= esc($student['wali_kelas_name'] ?: '-') ?> (<?= esc($student['wali_kelas_phone'] ?: '-') ?>)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: KEHADIRAN / ABSENSI -->
                <div class="tab-pane fade" id="tab-absensi" role="tabpanel">
                    <div class="row align-items-center g-4">
                        <div class="col-md-4 text-center">
                            <div class="p-4 bg-light rounded-4 border">
                                <h6 class="fw-bold text-muted mb-2">Persentase Kehadiran</h6>
                                <h1 class="fw-bold <?= $attendancePercentage >= 90 ? 'text-success' : ($attendancePercentage >= 80 ? 'text-warning' : 'text-danger') ?> display-4 mb-2"><?= $attendancePercentage ?>%</h1>
                                <p class="text-muted fs-7 mb-0">
                                    <?= $attendanceSummary['hadir'] ?> hadir dari
                                    <?= $attendanceSummary['hadir'] + $attendanceSummary['sakit'] + $attendanceSummary['izin'] + $attendanceSummary['alpa'] ?> hari sekolah aktif.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart me-2"></i> Rincian Akumulasi Presensi Siswa (READ-ONLY ESPATA)</h6>
                            <div class="row g-3 text-center">
                                <div class="col-3">
                                    <div class="p-3 bg-success-subtle text-success rounded-3 border border-success">
                                        <div class="fs-4 fw-bold"><?= $attendanceSummary['hadir'] ?></div>
                                        <small class="fw-semibold">Hadir</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-3 bg-info-subtle text-info rounded-3 border border-info">
                                        <div class="fs-4 fw-bold"><?= $attendanceSummary['sakit'] ?></div>
                                        <small class="fw-semibold">Sakit</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-3 bg-warning-subtle text-warning rounded-3 border border-warning">
                                        <div class="fs-4 fw-bold"><?= $attendanceSummary['izin'] ?></div>
                                        <small class="fw-semibold">Izin</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-3 bg-danger-subtle text-danger rounded-3 border border-danger">
                                        <div class="fs-4 fw-bold"><?= $attendanceSummary['alpa'] ?></div>
                                        <small class="fw-semibold">Alpa</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: AKADEMIK -->
                <div class="tab-pane fade" id="tab-akademik" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-bar-chart-line me-2"></i> Ringkasan Capaian Nilai Akademik Siswa</h6>
                        <span class="badge bg-primary fs-7 px-3 py-2 rounded-pill">Rata-rata Nilai: <?= $avgScore ?></span>
                    </div>

                    <?php if (!empty($academicScores)) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle border rounded-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th class="text-center">Formatif</th>
                                        <th class="text-center">Sumatif</th>
                                        <th class="text-center">Ujian</th>
                                        <th class="text-center">Nilai Rapor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($academicScores as $sc) : ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= esc($sc['subject_name'] ?: 'Mata Pelajaran') ?></td>
                                            <td class="text-center"><?= $sc['formatif_score'] ?: '-' ?></td>
                                            <td class="text-center"><?= $sc['sumatif_score'] ?: '-' ?></td>
                                            <td class="text-center"><?= $sc['final_exam_score'] ?: '-' ?></td>
                                            <td class="text-center fw-bold <?= ($sc['report_score'] && $sc['report_score'] < 75) ? 'text-danger' : 'text-success' ?>">
                                                <?= $sc['report_score'] ?: '-' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-muted italic py-3 text-center"><i class="bi bi-inbox fs-4 d-block mb-1"></i> Belum ada entri data nilai akademik pada tahun ajaran aktif.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 4: CATATAN PERILAKU GURU -->
                <div class="tab-pane fade" id="tab-perilaku" role="tabpanel">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-chat-square-quote me-2"></i> Catatan Observasi Perilaku dari Guru & Wali Kelas</h6>
                    <?php if (!empty($studentNotes)) : ?>
                        <div class="list-group">
                            <?php foreach ($studentNotes as $note) : ?>
                                <div class="list-group-item border rounded-3 mb-2 shadow-sm">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-badge me-1"></i><?= esc($note['teacher_name'] ?: 'Guru/Wali Kelas') ?></h6>
                                        <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y H:i', strtotime($note['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 text-dark"><?= esc($note['note']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="text-muted italic py-3 text-center"><i class="bi bi-chat-left-dots fs-4 d-block mb-1"></i> Belum ada catatan observasi perilaku dari guru mapel / wali kelas.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 5: RIWAYAT LAYANAN BK -->
                <div class="tab-pane fade" id="tab-layanan" role="tabpanel">
                    <h6 class="fw-bold text-info mb-3"><i class="bi bi-journal-text me-2"></i> Riwayat Pelaksanaan Layanan Bimbingan Konseling</h6>
                    <?php if (!empty($services)) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle border rounded-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis Layanan</th>
                                        <th>Bidang</th>
                                        <th>Topik</th>
                                        <th>Konselor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $srv) : ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($srv['service_date'])) ?></td>
                                            <td><span class="badge bg-info-subtle text-info fw-bold"><?= esc($srv['service_type']) ?></span></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?= esc($srv['field']) ?></span></td>
                                            <td class="fw-bold text-dark"><?= esc($srv['topic']) ?></td>
                                            <td><?= esc($srv['counselor_name'] ?: 'Guru BK') ?></td>
                                            <td><span class="badge bg-success"><?= esc($srv['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-muted italic py-3 text-center"><i class="bi bi-journal-x fs-4 d-block mb-1"></i> Belum ada riwayat layanan BK untuk siswa ini.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 6: PENANGANAN KASUS -->
                <div class="tab-pane fade" id="tab-kasus" role="tabpanel">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-shield-exclamation me-2"></i> Riwayat & Status Penanganan Kasus Siswa</h6>
                    <?php if (!empty($cases)) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle border rounded-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode Kasus</th>
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th>Bobot</th>
                                        <th>Status Workflow</th>
                                        <th>Proteksi BK</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cases as $c) : ?>
                                        <tr>
                                            <td class="fw-bold text-danger"><?= esc($c['case_code']) ?></td>
                                            <td><?= date('d M Y', strtotime($c['incident_date'])) ?></td>
                                            <td><span class="badge bg-primary-subtle text-primary"><?= esc($c['category']) ?></span></td>
                                            <td>
                                                <span class="badge <?= $c['severity'] == 'Berat' ? 'bg-danger' : ($c['severity'] == 'Sedang' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>">
                                                    <?= esc($c['severity']) ?>
                                                </span>
                                            </td>
                                            <td><span class="badge bg-dark"><?= esc($c['status']) ?></span></td>
                                            <td>
                                                <?php if ($c['is_confidential']) : ?>
                                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-lock-fill me-1"></i> Rahasia BK</span>
                                                <?php else : ?>
                                                    <span class="badge bg-light text-muted">Umum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-muted italic py-3 text-center"><i class="bi bi-shield-check fs-4 text-success d-block mb-1"></i> Tidak ada catatan penanganan kasus untuk siswa ini.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
