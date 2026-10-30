<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Laporan Supervisi Keterlaksanaan Pembelajaran') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #222;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .no-print {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-secondary {
            color: #333;
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        /* ── Kop Surat ── */
        .kop-container {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .kop-logo {
            width: 70px;
            text-align: center;
        }
        .kop-logo img {
            max-width: 65px;
            max-height: 65px;
        }
        .kop-content {
            text-align: center;
            padding: 0 10px;
        }
        .kop-school-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-school-npsn {
            font-size: 10pt;
            font-weight: bold;
            margin: 2px 0;
        }
        .kop-school-address {
            font-size: 8.5pt;
            color: #333;
            margin: 0;
        }
        .kop-image-banner {
            width: 100%;
            text-align: center;
        }
        .kop-image-banner img {
            width: 100%;
            max-height: 110px;
            display: block;
        }

        /* ── Header Dokumen ── */
        .doc-title-area {
            text-align: center;
            margin: 14px 0 16px 0;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0 0 4px 0;
        }
        .doc-subtitle {
            font-size: 9.5pt;
            color: #555;
            margin: 0;
        }

        /* ── Metadata Grid ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10.5px;
        }
        .meta-table td {
            border: none;
            padding: 3px 6px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            width: 18%;
        }
        .meta-sep {
            width: 2%;
            text-align: center;
        }
        .meta-val {
            width: 30%;
        }

        /* ── KPI Summary Box ── */
        .kpi-container {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-card {
            display: table-cell;
            border: 1px solid #bbb;
            text-align: center;
            padding: 8px 4px;
            background: #fafafa;
            vertical-align: middle;
        }
        .kpi-card-num {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .kpi-card-label {
            font-size: 8.5pt;
            text-transform: uppercase;
            color: #555;
            font-weight: 600;
        }
        .kpi-progress {
            background: #eef7ee;
            border: 1.5px solid #28a745;
        }
        .kpi-progress .kpi-card-num {
            color: #1e7e34;
        }

        /* ── Tabel Matriks ── */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 14px;
        }
        .table-data th, .table-data td {
            border: 1px solid #333;
            padding: 5px 6px;
            vertical-align: top;
        }
        .table-data th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 9.5pt;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        /* ── Badges ── */
        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .st-terlaksana { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .st-sebagian { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .st-tertunda { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .st-belum { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .st-verifikasi { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* ── Catatan Integritas ── */
        .disclaimer-box {
            border: 1px dashed #777;
            background: #fdfdfd;
            padding: 6px 10px;
            font-size: 8pt;
            color: #555;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        /* ── Tanda Tangan Ganda ── */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
            font-size: 10pt;
        }
        .signature-table td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }
        .sig-space {
            height: 60px;
            position: relative;
        }
        .sig-img {
            max-height: 55px;
            max-width: 140px;
            display: block;
            margin: 0 auto;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: none;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Kontrol Layar (Tersembunyi saat Cetak) -->
    <div class="no-print">
        <div>
            <strong>🖨️ Pratinjau Dokumen Cetak Supervisi Pembelajaran</strong>
            <span style="font-size: 11px; color: #666; margin-left: 10px;">Format: A4 Portrait</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Sekarang</button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>

    <!-- ═══ 1. KOP SURAT RESMI SEKOLAH ═══ -->
    <div class="kop-container">
        <?php if (!empty($kop_base64)): ?>
            <div class="kop-image-banner">
                <img src="<?= $kop_base64 ?>" alt="KOP Surat">
            </div>
        <?php else: ?>
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        <?php if (!empty($school_logo_base64)): ?>
                            <img src="<?= $school_logo_base64 ?>" alt="Logo">
                        <?php endif; ?>
                    </td>
                    <td class="kop-content">
                        <div class="kop-school-name"><?= esc($school['name'] ?? 'SEKOLAH INDONESIA') ?></div>
                        <div class="kop-school-npsn">NPSN: <?= esc($school['npsn'] ?? '-') ?> <?= !empty($school['status']) ? ' &bull; Status: ' . esc(ucfirst($school['status'])) : '' ?></div>
                        <div class="kop-school-address">
                            <?= esc($school['address'] ?? '') ?> 
                            <?= !empty($school['district']) ? 'Kec. ' . esc($school['district']) : '' ?> 
                            <?= !empty($school['city_regency']) ? esc($school['city_regency']) : (!empty($school['city']) ? esc($school['city']) : '') ?>
                            <?= !empty($school['phone']) ? ' | Telp: ' . esc($school['phone']) : '' ?>
                            <?= !empty($school['email']) ? ' | Email: ' . esc($school['email']) : '' ?>
                        </div>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <!-- ═══ 2. JUDUL DOKUMEN ═══ -->
    <div class="doc-title-area">
        <div class="doc-title">
            <?= !empty($selected_modul_id) ? 'LAPORAN HASIL SUPERVISI REALISASI PEMBELAJARAN' : 'REKAPITULASI SUPERVISI KETERLAKSANAAN PEMBELAJARAN' ?>
        </div>
        <div class="doc-subtitle">
            Kesesuaian Rencana Modul Ajar Kurikulum Merdeka terhadap Realisasi Jurnal Mengajar
        </div>
    </div>

    <!-- ═══ 3. IDENTITAS & METADATA SUPERVISI ═══ -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Tahun Pelajaran</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><?= esc($selected_academic_year['year'] ?? '-') ?></td>
            <td class="meta-label">Guru Pengampu</td>
            <td class="meta-sep">:</td>
            <td class="meta-val fw-bold"><?= esc($selected_teacher['name'] ?? ($modul_info['teacher_name'] ?? 'Semua Guru / Tim Guru')) ?></td>
        </tr>
        <tr>
            <td class="meta-label">Semester</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><?= $selected_semester ? 'Semester ' . $selected_semester . ($selected_semester == 1 ? ' (Ganjil)' : ' (Genap)') : 'Semester 1 & 2' ?></td>
            <td class="meta-label">NIP Guru</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><?= esc($selected_teacher['nip'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Kelas / Fase</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><?= esc($selected_class['name'] ?? 'Semua Kelas') ?></td>
            <td class="meta-label">Mata Pelajaran</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><?= esc($selected_subject['name'] ?? 'Semua Mata Pelajaran') ?></td>
        </tr>
        <?php if (!empty($selected_modul_id)): ?>
        <tr>
            <td class="meta-label">Lingkup Materi</td>
            <td class="meta-sep">:</td>
            <td class="meta-val fw-bold" colspan="4"><?= esc($modul_info['materi'] ?? '-') ?> (Tipe: <?= esc(strtoupper($modul_info['tipe'] ?? 'MANUAL')) ?>)</td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ═══ 4. RINGKASAN KPI KETERLAKSANAAN (FORMULA RESMI) ═══ -->
    <?php 
        $activeSummary = !empty($selected_modul_id) ? $summary : [
            'n_total'         => $rekap_kumulatif['n_total'],
            'p_terlaksana'    => $rekap_kumulatif['p_terlaksana'],
            'p_sebagian'      => $rekap_kumulatif['p_sebagian'],
            'p_tertunda'      => $rekap_kumulatif['p_tertunda'],
            'p_belum'         => $rekap_kumulatif['p_belum'],
            'p_verifikasi'    => $rekap_kumulatif['p_verifikasi'],
            'progres_persen'  => $rekap_kumulatif['progres_persen'],
        ];
    ?>
    <div class="kpi-container">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-card-num"><?= $activeSummary['n_total'] ?></div>
                <div class="kpi-card-label">Rencana (N)</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-num" style="color: #198754;"><?= $activeSummary['p_terlaksana'] ?></div>
                <div class="kpi-card-label">Terlaksana Penuh</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-num" style="color: #fd7e14;"><?= $activeSummary['p_sebagian'] ?></div>
                <div class="kpi-card-label">Terlaksana Sebagian</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-num" style="color: #dc3545;"><?= $activeSummary['p_tertunda'] ?></div>
                <div class="kpi-card-label">Tertunda</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card-num" style="color: #6c757d;"><?= $activeSummary['p_belum'] ?></div>
                <div class="kpi-card-label">Belum Tercatat</div>
            </div>
            <div class="kpi-card kpi-progress">
                <div class="kpi-card-num"><?= $activeSummary['progres_persen'] ?></div>
                <div class="kpi-card-label">Progres Efektif</div>
            </div>
        </div>
    </div>
    <div style="font-size: 8pt; color: #555; text-align: right; margin-top: -12px; margin-bottom: 14px;">
        * Formula Progres: ((Terlaksana + 0.5 &times; Terlaksana Sebagian) / Total Rencana) &times; 100%
    </div>

    <!-- ═══ 5. TABEL UTAMA (MODE A: DETAIL PERTEMUAN / MODE B: REKAPITULASI) ═══ -->
    <?php if (!empty($selected_modul_id)): ?>
        <!-- Mode A: Matriks Rincian Pertemuan -->
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th style="width: 220px;" class="text-left">Rencana Pertemuan (Modul Ajar)</th>
                    <th style="width: 75px;">Tgl Realisasi</th>
                    <th class="text-left">Catatan Aktual & Refleksi Pembelajaran</th>
                    <th style="width: 95px;">Status Realisasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meeting_matrix)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 15px; color: #777;">
                            Tidak ada rincian pertemuan terdeteksi dalam Modul Ajar ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($meeting_matrix as $row): ?>
                        <?php
                            $badgeCls = 'st-belum';
                            if ($row['status'] === 'terlaksana') $badgeCls = 'st-terlaksana';
                            elseif ($row['status'] === 'sebagian') $badgeCls = 'st-sebagian';
                            elseif ($row['status'] === 'tertunda') $badgeCls = 'st-tertunda';
                            elseif ($row['status'] === 'perlu_verifikasi') $badgeCls = 'st-verifikasi';
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $row['pertemuan_ke'] ?></td>
                            <td>
                                <div class="fw-bold"><?= esc($row['judul']) ?></div>
                                <div style="font-size: 8pt; color: #555;">
                                    <?php if (!empty($row['model']) && $row['model'] !== '-'): ?>
                                        Model: <?= esc($row['model']) ?> &bull; 
                                    <?php endif; ?>
                                    Durasi: <?= esc($row['durasi']) ?>
                                </div>
                            </td>
                            <td class="text-center"><?= esc($row['dates_str']) ?></td>
                            <td>
                                <?php if (!empty($row['journals'])): ?>
                                    <?php foreach ($row['journals'] as $j): ?>
                                        <div style="margin-bottom: 4px;">
                                            &bull; <?= esc($j['notes'] ?? '-') ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Belum ada catatan jurnal mengajar untuk pertemuan ini.</span>
                                <?php endif; ?>
                                <?php if ($row['has_conflict']): ?>
                                    <div style="color: #0c5460; font-size: 8pt; font-weight: bold; margin-top: 3px;">
                                        ⚠️ <?= esc($row['conflict_note']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge-status <?= $badgeCls ?>">
                                    <?= esc($row['status_label']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Suplemen: Jurnal Mandiri Tanpa Modul -->
        <?php if (!empty($independent_journals)): ?>
            <div style="margin-top: 14px; page-break-inside: avoid;">
                <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 4px;">
                    Catatan Pembelajaran Mandiri (Jurnal Tanpa Rujukan Modul Ajar)
                </div>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th style="width: 25px;">No</th>
                            <th style="width: 80px;">Tanggal</th>
                            <th class="text-left">Catatan & Uraian Aktivitas Pembelajaran</th>
                            <th style="width: 95px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($independent_journals as $i => $ij): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="text-center"><?= !empty($ij['date']) ? date('d/m/Y', strtotime($ij['date'])) : '-' ?></td>
                                <td><?= esc($ij['notes'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge-status st-sebagian"><?= esc(ucfirst($ij['status_realisasi'] ?? 'Terlaksana')) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Mode B: Tabel Rekapitulasi Supervisi Multi-Modul -->
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <?php if (in_array($role_id, [1, 2])): ?>
                        <th class="text-left">Guru Pengampu</th>
                    <?php endif; ?>
                    <th style="width: 50px;">Kelas</th>
                    <th style="width: 100px;">Mata Pelajaran</th>
                    <th class="text-left">Lingkup Materi Modul Ajar</th>
                    <th style="width: 35px;">N</th>
                    <th style="width: 35px;">Penuh</th>
                    <th style="width: 35px;">Sebagian</th>
                    <th style="width: 35px;">Tunda</th>
                    <th style="width: 35px;">Belum</th>
                    <th style="width: 65px;">Progres</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap_modul_list)): ?>
                    <tr>
                        <td colspan="<?= in_array($role_id, [1, 2]) ? '11' : '10' ?>" class="text-center" style="padding: 15px; color: #777;">
                            Tidak ada data Modul Ajar yang sesuai dengan filter supervisi saat ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rekap_modul_list as $idx => $rm): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <?php if (in_array($role_id, [1, 2])): ?>
                                <td><?= esc($rm['teacher_name']) ?></td>
                            <?php endif; ?>
                            <td class="text-center"><?= esc($rm['class_name']) ?></td>
                            <td class="text-center"><?= esc($rm['subject_name']) ?></td>
                            <td>
                                <div class="fw-bold"><?= esc($rm['materi']) ?></div>
                                <span style="font-size: 8pt; color: #555;">Tipe: <?= esc($rm['tipe']) ?> <?= !empty($rm['semester']) ? '&bull; Sem ' . esc($rm['semester']) : '' ?></span>
                            </td>
                            <td class="text-center fw-bold"><?= $rm['n_total'] ?></td>
                            <td class="text-center" style="color: #198754; font-weight: bold;"><?= $rm['p_terlaksana'] ?></td>
                            <td class="text-center" style="color: #fd7e14;"><?= $rm['p_sebagian'] ?></td>
                            <td class="text-center" style="color: #dc3545;"><?= $rm['p_tertunda'] ?></td>
                            <td class="text-center" style="color: #6c757d;"><?= $rm['p_belum'] ?></td>
                            <td class="text-center fw-bold"><?= $rm['progres_persen'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="<?= in_array($role_id, [1, 2]) ? '5' : '4' ?>" class="text-right">Total Kumulatif Berbobot:</td>
                    <td class="text-center"><?= $rekap_kumulatif['n_total'] ?></td>
                    <td class="text-center" style="color: #198754;"><?= $rekap_kumulatif['p_terlaksana'] ?></td>
                    <td class="text-center" style="color: #fd7e14;"><?= $rekap_kumulatif['p_sebagian'] ?></td>
                    <td class="text-center" style="color: #dc3545;"><?= $rekap_kumulatif['p_tertunda'] ?></td>
                    <td class="text-center" style="color: #6c757d;"><?= $rekap_kumulatif['p_belum'] ?></td>
                    <td class="text-center" style="color: #0d6efd;"><?= $rekap_kumulatif['progres_persen'] ?></td>
                </tr>
            </tfoot>
        </table>
        <div style="font-size: 8pt; color: #555; margin-top: -8pt; margin-bottom: 14pt;">
            * Progres kumulatif dihitung berdasarkan total capaian berbobot seluruh pertemuan yang valid, bukan rata-rata sederhana persentase setiap modul.
        </div>
    <?php endif; ?>

    <!-- ═══ 6. CATATAN INTEGRITAS & SUPERVISI ═══ -->
    <div class="disclaimer-box">
        <strong>Catatan Integritas Supervisi:</strong>
        Laporan ini menyajikan perbandingan antara rencana kegiatan yang dirumuskan pada Modul Ajar dan catatan riil jurnal mengajar guru. Snapshot rencana merekam kondisi saat jurnal disimpan untuk menjamin konsistensi historis pembelajaran. Jurnal mandiri dihitung terpisah dan tidak mengurangi denominator capaian target kurikulum.
    </div>

    <!-- ═══ 7. TITIMANGSA & TANDA TANGAN GANDA ═══ -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <div class="sig-space">
                    <?php if (!empty($principal_sig_base64)): ?>
                        <img src="<?= $principal_sig_base64 ?>" alt="Tanda Tangan Kepala Sekolah" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></u></strong><br>
                NIP. <?= esc($school['principal_nip'] ?? '-') ?>
            </td>
            <td>
                <?= esc($titimangsa_city) ?>, <?= esc($titimangsa_date) ?><br>
                <strong>Guru Pengampu / Supervisor</strong>
                <div class="sig-space">
                    <?php if (!empty($teacher_sig_base64)): ?>
                        <img src="<?= $teacher_sig_base64 ?>" alt="Tanda Tangan Guru" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= esc($selected_teacher['name'] ?? ($modul_info['teacher_name'] ?? 'Guru Pengampu')) ?></u></strong><br>
                NIP. <?= esc($selected_teacher['nip'] ?? '-') ?>
            </td>
        </tr>
    </table>

</body>
</html>
