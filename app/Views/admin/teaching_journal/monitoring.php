<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h3 class="mb-1">📊 Monitoring Keterlaksanaan Pembelajaran</h3>
        <p class="text-muted mb-0 small">Supervisi kesesuaian antara rencana Modul Ajar dan realisasi Jurnal Pembelajaran aktual.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('admin/teaching-journal/monitoring-print?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
            <span class="me-1">🖨️</span> Cetak Laporan
        </a>
        <a href="<?= base_url('admin/teaching-journal/monitoring-pdf?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-danger btn-sm d-flex align-items-center">
            <span class="me-1">📄</span> Unduh PDF
        </a>
        <a href="<?= base_url('admin/teaching-journal') ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
            <span class="me-1">⬅️</span> Jurnal Mengajar
        </a>
    </div>
</div>

<!-- Panel Filter Monitoring -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-light py-2">
        <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-1"></i> Filter Supervisi Keterlaksanaan</h6>
    </div>
    <div class="card-body">
        <form action="<?= base_url('admin/teaching-journal/monitoring') ?>" method="get" class="row g-3" id="formMonitoringFilter">
            <!-- Filter Tahun Ajaran -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Tahun Ajaran</label>
                <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($academic_years as $ay): ?>
                        <option value="<?= $ay['id'] ?>" <?= $selected_academic_year_id == $ay['id'] ? 'selected' : '' ?>>
                            <?= esc($ay['year']) ?> <?= !empty($ay['is_active']) ? '(Aktif)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Semester -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Semester --</option>
                    <option value="1" <?= $selected_semester === 1 ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
                    <option value="2" <?= $selected_semester === 2 ? 'selected' : '' ?>>Semester 2 (Genap)</option>
                </select>
            </div>

            <!-- Filter Guru (Khusus Admin / Kepala Sekolah) -->
            <?php if (!empty($teachers)): ?>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Guru Pengampu</label>
                <select name="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selected_teacher_id == $t['id'] ? 'selected' : '' ?>>
                            <?= esc($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Filter Kelas -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Kelas</label>
                <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class_id == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Mata Pelajaran -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Mata Pelajaran</label>
                <select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject_id == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Modul Ajar -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold">Modul Ajar Rujukan</label>
                <select name="modul_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Modul Ajar --</option>
                    <?php foreach ($moduls as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $selected_modul_id == $m['id'] ? 'selected' : '' ?>>
                            <?= esc($m['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 pt-1">
                <a href="<?= base_url('admin/teaching-journal/monitoring') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!$selected_modul_id): ?>
    <!-- Panduan jika belum memilih modul spesifik -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
        <div>
            <h6 class="alert-heading fw-bold mb-1">Pilih Modul Ajar untuk Rincian Pertemuan</h6>
            <p class="mb-0 small">Silakan pilih <strong>Kelas</strong>, <strong>Mata Pelajaran</strong>, atau <strong>Modul Ajar Rujukan</strong> pada filter di atas untuk menganalisis kesesuaian antara rencana pertemuan dengan jurnal mengajar aktual.</p>
        </div>
    </div>

    <?php if (!empty($rekap_modul_list)): ?>
        <!-- Tabel Rekapitulasi Supervisi Multi-Modul (Mode B) -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold">📋 Rekapitulasi Supervisi Keterlaksanaan Pembelajaran</h5>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('admin/teaching-journal/monitoring-print?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                        🖨️ Cetak Rekap
                    </a>
                    <a href="<?= base_url('admin/teaching-journal/monitoring-pdf?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                        📄 Unduh PDF
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center small">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <?php if (in_array($role_id, [1, 2])): ?>
                                    <th class="text-start">Guru Pengampu</th>
                                <?php endif; ?>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-start">Lingkup Materi Modul</th>
                                <th style="width: 50px;">Total (N)</th>
                                <th class="text-success" style="width: 60px;">Penuh</th>
                                <th class="text-warning" style="width: 60px;">Sebagian</th>
                                <th class="text-danger" style="width: 60px;">Tertunda</th>
                                <th class="text-secondary" style="width: 60px;">Belum</th>
                                <th style="width: 140px;">Progres Efektif</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($rekap_modul_list as $idx => $rm): ?>
                                <tr>
                                    <td class="text-center"><?= $idx + 1 ?></td>
                                    <?php if (in_array($role_id, [1, 2])): ?>
                                        <td class="fw-semibold"><?= esc($rm['teacher_name']) ?></td>
                                    <?php endif; ?>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= esc($rm['class_name']) ?></span></td>
                                    <td class="text-center"><?= esc($rm['subject_name']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= esc($rm['materi']) ?></div>
                                        <span class="badge bg-secondary badge-sm" style="font-size: 10px;"><?= esc($rm['tipe']) ?></span>
                                        <?php if (!empty($rm['semester'])): ?>
                                            <span class="text-muted" style="font-size: 11px;">Sem <?= esc($rm['semester']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= $rm['n_total'] ?></td>
                                    <td class="text-center text-success fw-semibold"><?= $rm['p_terlaksana'] ?></td>
                                    <td class="text-center text-warning fw-semibold"><?= $rm['p_sebagian'] ?></td>
                                    <td class="text-center text-danger fw-semibold"><?= $rm['p_tertunda'] ?></td>
                                    <td class="text-center text-secondary"><?= $rm['p_belum'] ?></td>
                                    <td>
                                        <?php if ($rm['n_total'] > 0): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?= min(100, $rm['progres_raw']) ?>%"></div>
                                                </div>
                                                <span class="fw-bold"><?= $rm['progres_persen'] ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/teaching-journal/monitoring?academic_year_id=' . $selected_academic_year_id . '&semester=' . $selected_semester . '&teacher_id=' . $selected_teacher_id . '&class_id=' . $selected_class_id . '&subject_id=' . $selected_subject_id . '&modul_id=' . $rm['id']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 12px;">
                                            Detail &raquo;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold small">
                            <tr>
                                <td colspan="<?= in_array($role_id, [1, 2]) ? '5' : '4' ?>" class="text-end">Total Kumulatif Berbobot:</td>
                                <td class="text-center"><?= $rekap_kumulatif['n_total'] ?></td>
                                <td class="text-center text-success"><?= $rekap_kumulatif['p_terlaksana'] ?></td>
                                <td class="text-center text-warning"><?= $rekap_kumulatif['p_sebagian'] ?></td>
                                <td class="text-center text-danger"><?= $rekap_kumulatif['p_tertunda'] ?></td>
                                <td class="text-center text-secondary"><?= $rekap_kumulatif['p_belum'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= min(100, $rekap_kumulatif['progres_raw']) ?>%"></div>
                                        </div>
                                        <span class="fw-bold text-primary"><?= $rekap_kumulatif['progres_persen'] ?></span>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-3 py-2 bg-light border-top text-muted small" style="font-size: 0.8rem;">
                    <i class="bi bi-info-circle me-1"></i> Progres kumulatif dihitung berdasarkan total capaian berbobot seluruh pertemuan yang valid, bukan rata-rata sederhana persentase setiap modul.
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>

    <!-- Identitas Modul Ajar Terpilih -->
    <div class="card mb-4 border-0 shadow-sm bg-white">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="badge bg-primary text-uppercase mb-1"><?= esc($modul_info['tipe'] ?? 'MANUAL') ?></span>
                    <h5 class="fw-bold mb-1 text-primary">
                        📖 <?= esc($modul_info['materi'] ?? 'Modul Ajar') ?>
                    </h5>
                    <div class="text-muted small">
                        <span><strong>Mata Pelajaran:</strong> <?= esc($subjects[array_search($selected_subject_id, array_column($subjects, 'id'))]['name'] ?? '-') ?></span> &bull; 
                        <span><strong>Kelas:</strong> <?= esc($classes[array_search($selected_class_id, array_column($classes, 'id'))]['name'] ?? '-') ?></span> &bull; 
                        <?php if (!empty($modul_info['semester'])): ?>
                            <span><strong>Semester:</strong> <?= esc($modul_info['semester']) ?></span> &bull; 
                        <?php endif; ?>
                        <span><strong>Terakhir Diperbarui:</strong> <?= !empty($modul_info['updated_at']) ? date('d/m/Y H:i', strtotime($modul_info['updated_at'])) : '-' ?></span>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex flex-wrap justify-content-md-end gap-2">
                    <a href="<?= base_url('admin/teaching-journal/monitoring-print?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                        🖨️ Cetak
                    </a>
                    <a href="<?= base_url('admin/teaching-journal/monitoring-pdf?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                        📄 Unduh PDF
                    </a>
                    <a href="<?= base_url('admin/administrasi-guru/modul-ajar?class_id=' . $selected_class_id . '&subject_id=' . $selected_subject_id) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-text me-1"></i> Buka Modul
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Peringatan jika Modul Ajar tidak dapat diparsing -->
    <?php if (!$summary['is_parsable']): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Rencana Pertemuan Tidak Dapat Diverifikasi</h6>
                    <p class="mb-0 small"><?= esc($summary['status_catatan']) ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- Kartu Metrik Ringkasan KPI -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-light text-center p-3 h-100">
                    <div class="text-muted small fw-bold">TOTAL RENCANA</div>
                    <div class="fs-2 fw-bold text-dark mt-1"><?= (int)$summary['n_total'] ?></div>
                    <div class="text-muted smaller">Pertemuan</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-success bg-opacity-10 text-center p-3 h-100">
                    <div class="text-success small fw-bold">TERLAKSANA</div>
                    <div class="fs-2 fw-bold text-success mt-1"><?= (int)$summary['p_terlaksana'] ?></div>
                    <div class="text-success smaller">Pertemuan Penuh</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-warning bg-opacity-10 text-center p-3 h-100">
                    <div class="text-warning-emphasis small fw-bold">SEBAGIAN</div>
                    <div class="fs-2 fw-bold text-warning-emphasis mt-1"><?= (int)$summary['p_sebagian'] ?></div>
                    <div class="text-warning-emphasis smaller">Perlu Penyesuaian</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-danger bg-opacity-10 text-center p-3 h-100">
                    <div class="text-danger small fw-bold">TERTUNDA</div>
                    <div class="fs-2 fw-bold text-danger mt-1"><?= (int)$summary['p_tertunda'] ?></div>
                    <div class="text-danger smaller">Ada Kendala KBM</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-secondary bg-opacity-10 text-center p-3 h-100">
                    <div class="text-secondary small fw-bold">BELUM TERCATAT</div>
                    <div class="fs-2 fw-bold text-secondary mt-1"><?= (int)$summary['p_belum'] ?></div>
                    <div class="text-secondary smaller">Belum Ada Jurnal</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-info bg-opacity-10 text-center p-3 h-100">
                    <div class="text-info-emphasis small fw-bold">VERIFIKASI</div>
                    <div class="fs-2 fw-bold text-info-emphasis mt-1"><?= (int)$summary['p_verifikasi'] ?></div>
                    <div class="text-info-emphasis smaller">Ada Konflik / Multi</div>
                </div>
            </div>
        </div>

        <!-- Kartu Progres Berbobot Efektif -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-speedometer2 me-1 text-primary"></i> Progres Keterlaksanaan Efektif
                    </h6>
                    <span class="fs-4 fw-bold <?= is_numeric($summary['progres_raw']) && $summary['progres_raw'] >= 75 ? 'text-success' : 'text-primary' ?>">
                        <?= esc($summary['progres_persen']) ?>
                    </span>
                </div>
                <div class="progress mb-2" style="height: 12px;">
                    <?php $pct = is_numeric($summary['progres_raw']) ? max(0, min(100, $summary['progres_raw'])) : 0; ?>
                    <div class="progress-bar progress-bar-striped progress-bar-animated <?= $pct >= 75 ? 'bg-success' : ($pct >= 40 ? 'bg-primary' : 'bg-warning') ?>" 
                         role="progressbar" 
                         style="width: <?= $pct ?>%;" 
                         aria-valuenow="<?= $pct ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <div class="alert alert-light border small text-muted mb-0 py-2">
                    <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                    <strong>Indikator Progres Berbobot:</strong> Dihitung dengan rumus <code>((P_terlaksana + 0.5 × P_sebagian) / N_total) × 100%</code>. Pertemuan tertunda, belum tercatat, atau berstatus perlu verifikasi tidak dimasukkan ke dalam pembilang. Persentase ini adalah indikator administratif keterlaksanaan sesi KBM berbobot, <em>bukan ukuran ketercapaian kompetensi atau hasil belajar siswa</em>.
                </div>
            </div>
        </div>

        <!-- Matriks Realisasi Pertemuan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-table me-1 text-primary"></i> Matriks Realisasi Rencana Pertemuan
                </h6>
                <span class="badge bg-light text-dark border"><?= count($meeting_matrix) ?> Pertemuan Terdeteksi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 32%;">Rencana Modul Ajar</th>
                                <th style="width: 18%;">Status Realisasi</th>
                                <th style="width: 15%;">Tanggal Jurnal</th>
                                <th style="width: 20%;">Refleksi Guru</th>
                                <th style="width: 10%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meeting_matrix as $row): ?>
                            <tr>
                                <td class="text-center fw-bold">
                                    <?= (int)$row['pertemuan_ke'] ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($row['judul']) ?></div>
                                    <div class="text-muted smaller">
                                        <?php if (!empty($row['model']) && $row['model'] !== '-'): ?>
                                            <span class="badge bg-light text-secondary border me-1"><?= esc($row['model']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['durasi']) && $row['durasi'] !== '-'): ?>
                                            <span>⏱️ <?= esc($row['durasi']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= esc($row['badge_class']) ?> mb-1">
                                        <?= esc($row['status_label']) ?>
                                    </span>
                                    <?php if (!empty($row['has_conflict'])): ?>
                                        <div class="text-danger smaller fw-bold">
                                            <i class="bi bi-exclamation-circle-fill"></i> <?= esc($row['conflict_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= esc($row['dates_str']) ?>
                                </td>
                                <td class="small text-muted text-break">
                                    <?= esc($row['notes_preview']) ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm btn-detail-meeting" 
                                            data-modul-id="<?= (int)$selected_modul_id ?>" 
                                            data-pertemuan-ke="<?= (int)$row['pertemuan_ke'] ?>"
                                            title="Lihat Bukti Jurnal">
                                        <i class="bi bi-search"></i> Bukti
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- Bagian Jurnal Mandiri (Tanpa Modul Ajar) -->
    <?php if (!empty($independent_journals)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-secondary">
                <i class="bi bi-journal-text me-1"></i> Jurnal Mandiri (Tanpa Rujukan Modul Ajar)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="alert alert-light border-0 mb-0 small text-muted px-3 py-2">
                <i class="bi bi-info-circle me-1"></i>
                Jurnal di bawah ini dicatat guru tanpa merujuk ke Modul Ajar manapun, sehingga dipisahkan dari perhitungan progres rencana Modul Ajar.
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light smaller text-secondary">
                        <tr>
                            <th>Tanggal</th>
                            <th>Status Realisasi</th>
                            <th>Catatan Pembelajaran / Materi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($independent_journals as $ij): ?>
                        <tr>
                            <td class="small fw-semibold"><?= !empty($ij['date']) ? date('d/m/Y', strtotime($ij['date'])) : '-' ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= esc(ucfirst($ij['status_realisasi'] ?? 'Terlaksana')) ?></span>
                            </td>
                            <td class="small text-muted"><?= esc(strip_tags($ij['notes'] ?? '-')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<!-- Modal Detail Bukti Jurnal -->
<div class="modal fade" id="modalMonitoringDetail" tabindex="-1" aria-labelledby="modalMonitoringDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="modalMonitoringDetailLabel">
                        🔍 Bukti Realisasi Pertemuan
                    </h5>
                    <div class="text-muted smaller" id="modalSubtitle">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="modalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Memuat rincian dan bukti jurnal...</div>
                </div>

                <!-- Content Container -->
                <div id="modalContent" style="display: none;">
                    <!-- Bagian Snapshot Rencana Modul Ajar -->
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="bi bi-file-earmark-ruled me-1"></i> Rencana Modul Ajar
                            </h6>
                            <div class="row g-2 mb-2 small text-muted">
                                <div class="col-md-6"><strong>Model:</strong> <span id="modalModelText">-</span></div>
                                <div class="col-md-6"><strong>Alokasi Waktu:</strong> <span id="modalDurasiText">-</span></div>
                            </div>
                            <div class="p-2 bg-white rounded border small" id="modalRencanaSnapshotContainer">
                                <pre id="modalRencanaSnapshotText" class="mb-0 text-dark" style="white-space: pre-wrap; font-family: inherit; font-size: 0.85rem;">-</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Bukti Jurnal & Catatan Refleksi Aktual -->
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-journal-check me-1 text-success"></i> Catatan Realisasi Guru Aktual
                    </h6>
                    <div id="modalJournalsContainer">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Disclaimer Integritas -->
                    <div class="alert alert-secondary border-0 small mt-3 mb-0 p-2 text-muted">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        <span id="modalDisclaimerText">Catatan Integritas: Snapshot Rencana merekam rencana saat jurnal disimpan. Catatan Aktual merekam realisasi riil pembelajaran di kelas.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailModalEl = document.getElementById('modalMonitoringDetail');
    const detailModal = new bootstrap.Modal(detailModalEl);

    const modalLoading = document.getElementById('modalLoading');
    const modalContent = document.getElementById('modalContent');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalModelText = document.getElementById('modalModelText');
    const modalDurasiText = document.getElementById('modalDurasiText');
    const modalRencanaSnapshotText = document.getElementById('modalRencanaSnapshotText');
    const modalJournalsContainer = document.getElementById('modalJournalsContainer');
    const modalDisclaimerText = document.getElementById('modalDisclaimerText');

    document.querySelectorAll('.btn-detail-meeting').forEach(btn => {
        btn.addEventListener('click', function() {
            const modulId = this.getAttribute('data-modul-id');
            const pertemuanKe = this.getAttribute('data-pertemuan-ke');

            // Reset modal state
            modalLoading.style.display = 'block';
            modalContent.style.display = 'none';
            modalSubtitle.textContent = 'Memuat data...';
            detailModal.show();

            fetch(`<?= base_url('admin/teaching-journal/monitoring-detail') ?>?modul_id=${modulId}&pertemuan_ke=${pertemuanKe}`)
                .then(res => res.json())
                .then(data => {
                    modalLoading.style.display = 'none';

                    if (!data.success) {
                        modalContent.style.display = 'block';
                        modalSubtitle.textContent = 'Gagal memuat rincian';
                        modalJournalsContainer.innerHTML = `<div class="alert alert-danger mb-0">${data.message || 'Terjadi kesalahan saat memuat data.'}</div>`;
                        return;
                    }

                    modalContent.style.display = 'block';
                    modalSubtitle.textContent = `${data.subject_name} • Kelas ${data.class_name} • Pertemuan ke-${data.pertemuan_ke}`;
                    modalModelText.textContent = data.meeting.model || '-';
                    modalDurasiText.textContent = data.meeting.durasi || '-';

                    // Rencana snapshot
                    const rencanaTeks = data.meeting.rencana_teks || (data.journals.length > 0 && data.journals[0].rencana_snapshot ? data.journals[0].rencana_snapshot : 'Rencana pertemuan tidak memiliki teks langkah terformat.');
                    modalRencanaSnapshotText.textContent = rencanaTeks;

                    if (data.disclaimer) {
                        modalDisclaimerText.textContent = data.disclaimer;
                    }

                    // Render Journals List
                    modalJournalsContainer.innerHTML = '';
                    if (data.journals.length === 0) {
                        modalJournalsContainer.innerHTML = `
                            <div class="alert alert-light border text-muted small p-3 text-center mb-0">
                                <i class="bi bi-clock-history fs-4 d-block mb-1"></i>
                                Belum ada jurnal yang dicatat oleh guru untuk pertemuan ini.
                            </div>
                        `;
                    } else {
                        data.journals.forEach((j, idx) => {
                            let badgeClass = 'bg-success';
                            if (j.status_realisasi === 'sebagian') badgeClass = 'bg-warning text-dark';
                            if (j.status_realisasi === 'tertunda') badgeClass = 'bg-danger';

                            const jCard = document.createElement('div');
                            jCard.className = 'card border mb-2 shadow-sm';
                            jCard.innerHTML = `
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold">
                                        📅 ${j.date} <span class="text-muted fw-normal">(${j.created_at})</span>
                                    </span>
                                    <span class="badge ${badgeClass} text-uppercase">${j.status_realisasi}</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="small fw-semibold text-muted mb-1">Catatan Refleksi / Keterangan:</div>
                                    <div class="small text-dark p-2 bg-light rounded" style="white-space: pre-wrap;">${j.notes ? j.notes : '<em class="text-muted">(Tidak ada catatan tambahan)</em>'}</div>
                                    ${j.rencana_snapshot && j.rencana_snapshot !== rencanaTeks ? `
                                        <details class="mt-2 small text-muted">
                                            <summary class="cursor-pointer">Lihat snapshot rencana saat jurnal ini disimpan</summary>
                                            <pre class="mt-1 p-2 bg-light rounded text-dark" style="white-space: pre-wrap; font-size: 0.8rem;">${j.rencana_snapshot}</pre>
                                        </details>
                                    ` : ''}
                                </div>
                            `;
                            modalJournalsContainer.appendChild(jCard);
                        });
                    }
                })
                .catch(err => {
                    modalLoading.style.display = 'none';
                    modalContent.style.display = 'block';
                    modalJournalsContainer.innerHTML = `<div class="alert alert-danger mb-0">Koneksi gagal atau sesi telah berakhir.</div>`;
                });
        });
    });
});
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}
.smaller {
    font-size: 0.75rem;
}
@media (max-width: 767.98px) {
    .table-responsive table thead {
        display: none;
    }
    .table-responsive table, 
    .table-responsive tbody, 
    .table-responsive tr, 
    .table-responsive td {
        display: block;
        width: 100% !important;
    }
    .table-responsive tr {
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: #fff;
        padding: 0.5rem;
    }
    .table-responsive td {
        padding: 0.35rem 0.5rem;
        border: none;
    }
    .table-responsive td:last-child {
        text-align: right !important;
        padding-top: 0.5rem;
    }
}
</style>

<?= $this->endSection() ?>

