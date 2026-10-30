<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Laporan Supervisi Keterlaksanaan Pembelajaran') ?></title>
    <style>
        @page {
            margin: 12mm 10mm 12mm 10mm;
            size: A4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #111;
            margin: 0;
            padding: 0;
        }

        /* ── Kop Surat ── */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5pt double #000;
            margin-bottom: 10pt;
            padding-bottom: 5pt;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .kop-logo {
            width: 55pt;
            text-align: center;
        }
        .kop-logo img {
            max-width: 50pt;
            max-height: 50pt;
        }
        .kop-text {
            text-align: center;
            padding: 0 5pt;
        }
        .kop-school-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .kop-school-npsn {
            font-size: 8.5pt;
            font-weight: bold;
            margin: 1pt 0;
        }
        .kop-school-address {
            font-size: 7.5pt;
            color: #333;
            margin: 0;
        }
        .kop-banner {
            width: 100%;
            text-align: center;
            border-bottom: 2pt solid #000;
            margin-bottom: 10pt;
            padding-bottom: 4pt;
        }
        .kop-banner img {
            width: 100%;
            max-height: 75pt;
        }

        /* ── Judul Dokumen ── */
        .doc-title-area {
            text-align: center;
            margin: 6pt 0 10pt 0;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0 0 2pt 0;
        }
        .doc-subtitle {
            font-size: 8pt;
            color: #444;
            margin: 0;
        }

        /* ── Metadata Table ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
            font-size: 8pt;
        }
        .meta-table td {
            border: none;
            padding: 2pt 4pt;
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

        /* ── KPI Box Table ── */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
            page-break-inside: avoid;
        }
        .kpi-table td {
            border: 1pt solid #aaa;
            text-align: center;
            padding: 5pt 2pt;
            background-color: #fafafa;
            vertical-align: middle;
        }
        .kpi-num {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 1pt;
        }
        .kpi-lbl {
            font-size: 7pt;
            text-transform: uppercase;
            color: #555;
            font-weight: bold;
        }
        .kpi-eff {
            background-color: #eef7ee;
            border: 1.5pt solid #28a745;
        }
        .kpi-eff .kpi-num {
            color: #1e7e34;
        }

        /* ── Data Tables ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 10pt;
        }
        .data-table th, .data-table td {
            border: 0.6pt solid #333;
            padding: 3.5pt 4pt;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 7.5pt;
        }
        .data-table tr {
            page-break-inside: avoid;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        /* ── Status Badges ── */
        .st-badge {
            font-size: 6.8pt;
            font-weight: bold;
            padding: 1pt 3pt;
            border-radius: 2pt;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-terlaksana { background-color: #d4edda; color: #155724; }
        .badge-sebagian { background-color: #fff3cd; color: #856404; }
        .badge-tertunda { background-color: #f8d7da; color: #721c24; }
        .badge-belum { background-color: #e2e3e5; color: #383d41; }
        .badge-verifikasi { background-color: #d1ecf1; color: #0c5460; }

        /* ── Disclaimer Box ── */
        .disclaimer-box {
            border: 0.8pt dashed #777;
            background-color: #fcfcfc;
            padding: 4pt 6pt;
            font-size: 6.8pt;
            color: #444;
            margin-bottom: 12pt;
            page-break-inside: avoid;
        }

        /* ── Signature Table ── */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
            page-break-inside: avoid;
            font-size: 8pt;
        }
        .signature-table td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10pt;
        }
        .sig-space {
            height: 48pt;
            text-align: center;
            vertical-align: middle;
        }
        .sig-img {
            max-height: 44pt;
            max-width: 120pt;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>

    <!-- ═══ 1. KOP SURAT ═══ -->
    <?php if (!empty($kop_base64)): ?>
        <div class="kop-banner">
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
                <td class="kop-text">
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

    <!-- ═══ 4. RINGKASAN KPI (FORMULA RESMI) ═══ -->
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
    <table class="kpi-table">
        <tr>
            <td style="width: 16%;">
                <div class="kpi-num"><?= $activeSummary['n_total'] ?></div>
                <div class="kpi-lbl">Rencana (N)</div>
            </td>
            <td style="width: 17%;">
                <div class="kpi-num" style="color: #198754;"><?= $activeSummary['p_terlaksana'] ?></div>
                <div class="kpi-lbl">Terlaksana Penuh</div>
            </td>
            <td style="width: 17%;">
                <div class="kpi-num" style="color: #fd7e14;"><?= $activeSummary['p_sebagian'] ?></div>
                <div class="kpi-lbl">Sebagian</div>
            </td>
            <td style="width: 16%;">
                <div class="kpi-num" style="color: #dc3545;"><?= $activeSummary['p_tertunda'] ?></div>
                <div class="kpi-lbl">Tertunda</div>
            </td>
            <td style="width: 16%;">
                <div class="kpi-num" style="color: #6c757d;"><?= $activeSummary['p_belum'] ?></div>
                <div class="kpi-lbl">Belum Tercatat</div>
            </td>
            <td style="width: 18%;" class="kpi-eff">
                <div class="kpi-num"><?= $activeSummary['progres_persen'] ?></div>
                <div class="kpi-lbl">Progres Efektif</div>
            </td>
        </tr>
    </table>
    <div style="font-size: 6.8pt; color: #555; text-align: right; margin-top: -8pt; margin-bottom: 10pt;">
        * Formula: ((Terlaksana + 0.5 &times; Terlaksana Sebagian) / Total Rencana) &times; 100%
    </div>

    <!-- ═══ 5. DATA SUPERVISI (MODE A: DETAIL PERTEMUAN / MODE B: REKAPITULASI) ═══ -->
    <?php if (!empty($selected_modul_id)): ?>
        <!-- Mode A: Matriks Rincian Pertemuan -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20pt;">No</th>
                    <th style="width: 160pt;" class="text-left">Rencana Pertemuan (Modul Ajar)</th>
                    <th style="width: 55pt;">Tgl Realisasi</th>
                    <th class="text-left">Catatan Aktual & Refleksi Pembelajaran</th>
                    <th style="width: 70pt;">Status Realisasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meeting_matrix)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 10pt; color: #777;">
                            Tidak ada rincian pertemuan terdeteksi dalam Modul Ajar ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($meeting_matrix as $row): ?>
                        <?php
                            $badgeCls = 'badge-belum';
                            if ($row['status'] === 'terlaksana') $badgeCls = 'badge-terlaksana';
                            elseif ($row['status'] === 'sebagian') $badgeCls = 'badge-sebagian';
                            elseif ($row['status'] === 'tertunda') $badgeCls = 'badge-tertunda';
                            elseif ($row['status'] === 'perlu_verifikasi') $badgeCls = 'badge-verifikasi';
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $row['pertemuan_ke'] ?></td>
                            <td>
                                <div class="fw-bold"><?= esc($row['judul']) ?></div>
                                <div style="font-size: 6.8pt; color: #555;">
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
                                        <div style="margin-bottom: 2pt;">
                                            &bull; <?= esc($j['notes'] ?? '-') ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Belum ada catatan jurnal mengajar untuk pertemuan ini.</span>
                                <?php endif; ?>
                                <?php if ($row['has_conflict']): ?>
                                    <div style="color: #0c5460; font-size: 6.8pt; font-weight: bold; margin-top: 2pt;">
                                        ⚠️ <?= esc($row['conflict_note']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="st-badge <?= $badgeCls ?>">
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
            <div style="margin-top: 8pt; page-break-inside: avoid;">
                <div style="font-weight: bold; font-size: 8pt; margin-bottom: 3pt;">
                    Catatan Pembelajaran Mandiri (Jurnal Tanpa Rujukan Modul Ajar)
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20pt;">No</th>
                            <th style="width: 60pt;">Tanggal</th>
                            <th class="text-left">Catatan & Uraian Aktivitas Pembelajaran</th>
                            <th style="width: 70pt;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($independent_journals as $i => $ij): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="text-center"><?= !empty($ij['date']) ? date('d/m/Y', strtotime($ij['date'])) : '-' ?></td>
                                <td><?= esc($ij['notes'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="st-badge badge-sebagian"><?= esc(ucfirst($ij['status_realisasi'] ?? 'Terlaksana')) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Mode B: Tabel Rekapitulasi Supervisi Multi-Modul -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 18pt;">No</th>
                    <?php if (in_array($role_id, [1, 2])): ?>
                        <th class="text-left">Guru Pengampu</th>
                    <?php endif; ?>
                    <th style="width: 35pt;">Kelas</th>
                    <th style="width: 75pt;">Mata Pelajaran</th>
                    <th class="text-left">Lingkup Materi Modul Ajar</th>
                    <th style="width: 25pt;">N</th>
                    <th style="width: 25pt;">Penuh</th>
                    <th style="width: 25pt;">Sebagian</th>
                    <th style="width: 25pt;">Tunda</th>
                    <th style="width: 25pt;">Belum</th>
                    <th style="width: 45pt;">Progres</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap_modul_list)): ?>
                    <tr>
                        <td colspan="<?= in_array($role_id, [1, 2]) ? '11' : '10' ?>" class="text-center" style="padding: 10pt; color: #777;">
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
                                <span style="font-size: 6.8pt; color: #555;">Tipe: <?= esc($rm['tipe']) ?> <?= !empty($rm['semester']) ? '&bull; Sem ' . esc($rm['semester']) : '' ?></span>
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
                <tr style="background-color: #f0f0f0; font-weight: bold;">
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
        <div style="font-size: 6.8pt; color: #555; margin-top: -6pt; margin-bottom: 10pt;">
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
