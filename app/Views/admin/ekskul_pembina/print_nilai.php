<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nilai Ekskul — <?= esc($ekskul['name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm 20mm 20mm 25mm;
        }

        /* KOP SEKOLAH */
        .kop {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 6px;
        }
        .kop img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .kop-text { text-align: center; flex: 1; }
        .kop-text .sekolah-name {
            font-size: 16pt;
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
            margin: 14px 0 4px;
        }
        .doc-title h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .doc-title .sub {
            font-size: 11pt;
            margin-top: 3px;
        }

        /* INFO TABEL */
        .info-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 4px;
            font-size: 11pt;
            vertical-align: top;
        }
        .info-table td:first-child { width: 38%; }
        .info-table td:nth-child(2) { width: 4%; text-align: center; }

        /* DATA TABEL */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 11pt;
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
            padding: 1px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10.5pt;
        }
        .pred-sb   { background:#d4edda; }
        .pred-b    { background:#cce5ff; }
        .pred-c    { background:#fff3cd; }
        .pred-k    { background:#f8d7da; }
        .pred-none { color: #888; font-style: italic; font-weight: normal; }

        /* TANDA TANGAN */
        .sign-area {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
        }
        .sign-box {
            text-align: center;
            min-width: 180px;
        }
        .sign-box .sign-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 11pt;
        }
        .sign-box .sign-desc {
            font-size: 10pt;
            font-style: italic;
        }

        /* FOOTER */
        .print-footer {
            margin-top: 20px;
            font-size: 9pt;
            color: #555;
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        /* TOOLBAR (layar saja) */
        .no-print {
            text-align: center;
            padding: 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .no-print button {
            padding: 8px 24px;
            margin: 0 6px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
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
    <button class="btn-print" onclick="window.print()">&#128424; Cetak</button>
    <button class="btn-back"  onclick="history.back()">&#8592; Kembali</button>
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
        <h2>Daftar Nilai Ekstrakurikuler</h2>
        <div class="sub"><?= esc($ekskul['name']) ?> &mdash; Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></div>
    </div>

    <!-- INFO -->
    <table class="info-table">
        <tr>
            <td>Ekstrakurikuler</td>
            <td>:</td>
            <td><strong><?= esc($ekskul['name']) ?></strong> (<?= esc($ekskul['kode']) ?>)</td>
        </tr>
        <tr>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td><?= esc($activeYear['year']) ?></td>
        </tr>
        <tr>
            <td>Semester</td>
            <td>:</td>
            <td>Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></td>
        </tr>
        <?php if (!empty($pembinaList)): ?>
        <tr>
            <td>Pembina / Pelatih</td>
            <td>:</td>
            <td>
                <?php foreach ($pembinaList as $pb): ?>
                    <?= esc($pb['pembina_name']) ?><?= $pb !== end($pembinaList) ? ', ' : '' ?>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <td>Jumlah Anggota</td>
            <td>:</td>
            <td><?= count($members) ?> siswa</td>
        </tr>
    </table>

    <!-- TABEL NILAI -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:12%">NIS</th>
                <th style="width:28%">Nama Siswa</th>
                <th style="width:14%">Kelas</th>
                <th style="width:14%">Predikat</th>
                <th style="width:28%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#888; font-style:italic;">Belum ada anggota.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $i => $m):
                    $s    = $scores[$m['student_id']] ?? null;
                    $pred = $s['predicate']   ?? '';
                    $desc = $s['description'] ?? '';
                    $cls  = '';
                    if ($pred === 'Sangat Baik') $cls = 'pred-sb';
                    elseif ($pred === 'Baik')    $cls = 'pred-b';
                    elseif ($pred === 'Cukup')   $cls = 'pred-c';
                    elseif ($pred === 'Kurang')  $cls = 'pred-k';
                    else                          $cls = 'pred-none';
                ?>
                <tr>
                    <td class="center"><?= $i + 1 ?></td>
                    <td class="center"><?= esc($m['nis']) ?></td>
                    <td><?= esc($m['student_name']) ?></td>
                    <td class="center"><?= esc($m['class_name'] ?? '-') ?></td>
                    <td class="center">
                        <?php if ($pred): ?>
                            <span class="predicate <?= $cls ?>"><?= esc($pred) ?></span>
                        <?php else: ?>
                            <span class="predicate pred-none">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($desc) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

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
            <div>Pembina / Pelatih,</div>
            <div>&nbsp;</div>
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
        Dicetak: <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp; SIAKAD
    </div>

</div>
</body>
</html>
