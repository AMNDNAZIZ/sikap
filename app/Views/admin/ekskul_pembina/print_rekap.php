<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Presensi — <?= esc($ekskul['name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 15mm 15mm 20mm;
        }

        /* KOP SEKOLAH */
        .kop {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .kop img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }
        .kop-text { text-align: center; flex: 1; }
        .kop-text .sekolah-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-text .sekolah-address {
            font-size: 9.5pt;
            margin-top: 2px;
        }

        /* JUDUL DOKUMEN */
        .doc-title {
            text-align: center;
            margin: 10px 0 6px;
        }
        .doc-title h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .doc-title .sub {
            font-size: 10.5pt;
            margin-top: 2px;
        }

        /* INFO TABEL */
        .info-table {
            width: 100%;
            margin: 8px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 4px;
            font-size: 10pt;
            vertical-align: top;
        }
        .info-table td:first-child { width: 25%; }
        .info-table td:nth-child(2) { width: 3%; text-align: center; }

        /* DATA TABEL */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9.5pt;
        }
        .data-table thead th {
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }
        .data-table tbody td.center { text-align: center; }

        /* Badge predikat */
        .predicate {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9pt;
        }
        .pred-sb   { background:#d4edda; }
        .pred-b    { background:#cce5ff; }
        .pred-c    { background:#fff3cd; }
        .pred-k    { background:#f8d7da; }

        /* TANDA TANGAN */
        .sign-area {
            display: flex;
            justify-content: space-between;
            margin-top: 26px;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            min-width: 180px;
        }
        .sign-box .sign-line {
            margin-top: 55px;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 10.5pt;
        }
        .sign-box .sign-desc {
            font-size: 9.5pt;
            font-style: italic;
        }

        /* FOOTER */
        .print-footer {
            margin-top: 18px;
            font-size: 8.5pt;
            color: #555;
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }

        /* TOOLBAR (layar saja) */
        .no-print {
            text-align: center;
            padding: 14px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .no-print button {
            padding: 7px 20px;
            margin: 0 5px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
        }
        .btn-print  { background: #0d6efd; color: #fff; }
        .btn-back   { background: #6c757d; color: #fff; }

        @media print {
            .no-print { display: none !important; }
            .page { padding: 10mm 15mm 15mm 20mm; }
            body  { background: #fff; }
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">&#128424; Cetak Laporan</button>
    <button class="btn-back"  onclick="window.close(); if(!window.closed) history.back();">&#8592; Tutup / Kembali</button>
</div>

<div class="page">

    <!-- KOP SEKOLAH -->
    <div class="kop">
        <?php if (!empty($school['logo'])): ?>
            <img src="<?= base_url('uploads/school/' . $school['logo']) ?>" alt="Logo Sekolah">
        <?php endif; ?>
        <div class="kop-text">
            <div class="sekolah-name"><?= esc($school['name'] ?? 'NAMA SEKOLAH') ?></div>
            <div class="sekolah-address"><?= esc($school['address'] ?? '') ?><?= !empty($school['phone']) ? ' | Telp. ' . esc($school['phone']) : '' ?></div>
            <?php if (!empty($school['email'])): ?>
                <div class="sekolah-address">Email: <?= esc($school['email']) ?></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($school['logo'])): ?>
            <img src="<?= base_url('uploads/school/' . $school['logo']) ?>" alt="" style="visibility:hidden;">
        <?php endif; ?>
    </div>

    <!-- JUDUL -->
    <div class="doc-title">
        <h2>Rekapitulasi Presensi Ekstrakurikuler</h2>
        <div class="sub"><?= esc($ekskul['name']) ?> &mdash; <?= esc($periodLabel) ?></div>
    </div>

    <!-- INFO -->
    <table class="info-table">
        <tr>
            <td>Ekstrakurikuler</td>
            <td>:</td>
            <td><strong><?= esc($ekskul['name']) ?></strong> (<?= esc($ekskul['kode']) ?>)</td>
            <td style="width:20%;">Jumlah Anggota</td>
            <td style="width:3%; text-align:center;">:</td>
            <td style="width:22%;"><strong><?= $totalMembers ?></strong> Siswa</td>
        </tr>
        <tr>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td><?= esc($activeYear['year']) ?></td>
            <td>Total Pertemuan</td>
            <td style="text-align:center;">:</td>
            <td><strong><?= $totalMeetings ?></strong> Kali</td>
        </tr>
        <tr>
            <td>Periode Rekap</td>
            <td>:</td>
            <td><?= esc($periodLabel) ?></td>
            <td>Rata-rata Hadir</td>
            <td style="text-align:center;">:</td>
            <td><strong><?= $avgPercentage ?>%</strong></td>
        </tr>
        <?php if (!empty($pembinaList)): ?>
        <tr>
            <td>Pembina / Pelatih</td>
            <td>:</td>
            <td colspan="4">
                <?php foreach ($pembinaList as $pb): ?>
                    <?= esc($pb['pembina_name']) ?><?= $pb !== end($pembinaList) ? ', ' : '' ?>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- DATA TABEL -->
    <table class="data-table">
        <thead>
            <?php if ($type === 'monthly' && $totalMeetings > 0 && count($jurnals) <= 12): ?>
                <!-- Format Bulanan dengan kolom tanggal -->
                <tr>
                    <th rowspan="2" style="width:4%">No</th>
                    <th rowspan="2" style="width:12%">NIS</th>
                    <th rowspan="2" style="width:24%">Nama Siswa</th>
                    <th rowspan="2" style="width:9%">Kelas</th>
                    <th colspan="<?= count($jurnals) ?>">Tanggal Pertemuan</th>
                    <th colspan="4">Total</th>
                    <th rowspan="2" style="width:7%">% Hadir</th>
                    <th rowspan="2" style="width:12%">Predikat</th>
                </tr>
                <tr>
                    <?php foreach ($jurnals as $j): ?>
                        <th style="font-size: 8pt; width: 22px;"><?= date('d/m', strtotime($j['date'])) ?></th>
                    <?php endforeach; ?>
                    <th style="width:4%">H</th>
                    <th style="width:4%">S</th>
                    <th style="width:4%">I</th>
                    <th style="width:4%">A</th>
                </tr>
            <?php elseif ($type === 'semester'): ?>
                <!-- Format Semester dengan kolom bulan -->
                <tr>
                    <th rowspan="2" style="width:4%">No</th>
                    <th rowspan="2" style="width:12%">NIS</th>
                    <th rowspan="2" style="width:22%">Nama Siswa</th>
                    <th rowspan="2" style="width:8%">Kelas</th>
                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): ?>
                        <th colspan="2" style="font-size: 8.5pt;"><?= esc(substr(explode(' ', $mLabel)[0], 0, 3)) ?></th>
                    <?php endforeach; ?>
                    <th colspan="4">Total</th>
                    <th rowspan="2" style="width:6%">Temu</th>
                    <th rowspan="2" style="width:7%">% Hadir</th>
                    <th rowspan="2" style="width:11%">Predikat</th>
                </tr>
                <tr>
                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): ?>
                        <th style="width: 18px; font-size: 8pt;">H</th>
                        <th style="width: 18px; font-size: 8pt;">T</th>
                    <?php endforeach; ?>
                    <th style="width:4%">H</th>
                    <th style="width:4%">S</th>
                    <th style="width:4%">I</th>
                    <th style="width:4%">A</th>
                </tr>
            <?php else: ?>
                <!-- Format Standar Rekap (Agregat) -->
                <tr>
                    <th style="width:4%">No</th>
                    <th style="width:14%">NIS</th>
                    <th style="width:30%">Nama Siswa</th>
                    <th style="width:12%">Kelas</th>
                    <th style="width:6%">Hadir</th>
                    <th style="width:6%">Sakit</th>
                    <th style="width:6%">Izin</th>
                    <th style="width:6%">Alfa</th>
                    <th style="width:6%">Total</th>
                    <th style="width:8%">% Hadir</th>
                    <th style="width:12%">Predikat</th>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (empty($rekapSiswa)): ?>
                <tr>
                    <td colspan="15" class="center" style="color:#888; font-style:italic; padding: 12px;">
                        Belum ada data anggota atau data presensi pada periode ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rekapSiswa as $idx => $row): 
                    $m = $row['member'];
                    $cls = '';
                    if ($row['predicate'] === 'Sangat Baik') $cls = 'pred-sb';
                    elseif ($row['predicate'] === 'Baik')    $cls = 'pred-b';
                    elseif ($row['predicate'] === 'Cukup')   $cls = 'pred-c';
                    else                                     $cls = 'pred-k';
                ?>
                    <tr>
                        <td class="center"><?= $idx + 1 ?></td>
                        <td class="center"><?= esc($m['nis']) ?></td>
                        <td><?= esc($m['student_name']) ?></td>
                        <td class="center"><?= esc($m['class_name'] ?? '-') ?></td>

                        <?php if ($type === 'monthly' && $totalMeetings > 0 && count($jurnals) <= 12): ?>
                            <?php foreach ($jurnals as $j): 
                                $st = $row['meetingDetails'][$j['id']]['status'] ?? 'hadir';
                                $badgeChar = ($st === 'hadir') ? 'H' : (($st === 'sakit') ? 'S' : (($st === 'izin') ? 'I' : 'A'));
                            ?>
                                <td class="center" style="font-size: 8pt;"><?= $badgeChar ?></td>
                            <?php endforeach; ?>
                        <?php elseif ($type === 'semester'): ?>
                            <?php foreach ($monthsInPeriod as $mNum => $mLabel): 
                                $mo = $row['monthlyDetails'][$mNum] ?? ['h' => 0, 's' => 0, 'i' => 0, 'a' => 0];
                                $nonH = $mo['s'] + $mo['i'] + $mo['a'];
                            ?>
                                <td class="center" style="font-size: 8.5pt;"><?= $mo['h'] ?></td>
                                <td class="center" style="font-size: 8.5pt; color: <?= $nonH > 0 ? '#b00' : '#888' ?>;"><?= $nonH ?></td>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Total H, S, I, A -->
                        <td class="center font-weight-bold"><?= $row['h'] ?></td>
                        <td class="center"><?= $row['s'] ?></td>
                        <td class="center"><?= $row['i'] ?></td>
                        <td class="center"><?= $row['a'] ?></td>

                        <?php if ($type === 'semester'): ?>
                            <td class="center font-weight-bold"><?= $row['total_meetings'] ?></td>
                        <?php elseif (!($type === 'monthly' && count($jurnals) <= 12)): ?>
                            <td class="center font-weight-bold"><?= $row['total_meetings'] ?></td>
                        <?php endif; ?>

                        <td class="center font-weight-bold"><?= $row['percentage'] ?>%</td>
                        <td class="center">
                            <span class="predicate <?= $cls ?>"><?= esc($row['predicate']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f9f9f9; font-weight:bold;">
                <td colspan="4" class="center">Total Keseluruhan</td>
                <?php if ($type === 'monthly' && $totalMeetings > 0 && count($jurnals) <= 12): ?>
                    <?php foreach ($jurnals as $j): 
                        $hCol = 0;
                        foreach ($rekapSiswa as $r) {
                            if (($r['meetingDetails'][$j['id']]['status'] ?? 'hadir') === 'hadir') $hCol++;
                        }
                    ?>
                        <td class="center" style="font-size: 8pt;"><?= $hCol ?></td>
                    <?php endforeach; ?>
                <?php elseif ($type === 'semester'): ?>
                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): 
                        $sumH = 0;
                        $sumNonH = 0;
                        foreach ($rekapSiswa as $r) {
                            $sumH += $r['monthlyDetails'][$mNum]['h'] ?? 0;
                            $sumNonH += ($r['monthlyDetails'][$mNum]['s'] ?? 0) + ($r['monthlyDetails'][$mNum]['i'] ?? 0) + ($r['monthlyDetails'][$mNum]['a'] ?? 0);
                        }
                    ?>
                        <td class="center" style="font-size: 8.5pt;"><?= $sumH ?></td>
                        <td class="center" style="font-size: 8.5pt;"><?= $sumNonH ?></td>
                    <?php endforeach; ?>
                <?php endif; ?>
                <td class="center"><?= $totalAllH ?></td>
                <td class="center"><?= $totalAllS ?></td>
                <td class="center"><?= $totalAllI ?></td>
                <td class="center"><?= $totalAllA ?></td>
                <?php if ($type === 'semester' || !($type === 'monthly' && count($jurnals) <= 12)): ?>
                    <td class="center"><?= $totalMeetings * $totalMembers ?></td>
                <?php endif; ?>
                <td class="center"><?= $avgPercentage ?>%</td>
                <td class="center">-</td>
            </tr>
        </tfoot>
    </table>

    <div style="font-size: 8pt; margin-top: 4px; color: #444;">
        <em>Keterangan: H = Hadir, S = Sakit, I = Izin, A = Alfa/Tanpa Keterangan, T = Tidak Hadir.</em>
    </div>

    <!-- TANDA TANGAN -->
    <div class="sign-area">
        <div class="sign-box">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="sign-line"><strong><?= esc($school['headmaster'] ?? '..................................') ?></strong></div>
            <?php if (!empty($school['principal_nip'])): ?>
                <div class="sign-desc">NIP. <?= esc($school['principal_nip']) ?></div>
            <?php endif; ?>
        </div>

        <div class="sign-box">
            <div><?= esc($school['city'] ?? 'Semarang') ?>, <?= date('d M Y') ?></div>
            <div>Pembina / Pelatih Ekstrakurikuler,</div>
            <?php if (!empty($pembinaList)): ?>
                <div class="sign-line"><strong><?= esc($pembinaList[0]['pembina_name']) ?></strong></div>
            <?php else: ?>
                <div class="sign-line">................................</div>
            <?php endif; ?>
            <div class="sign-desc">&nbsp;</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="print-footer">
        Dicetak: <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp; Rekapitulasi Presensi &bull; ESPATA
    </div>

</div>
</body>
</html>
