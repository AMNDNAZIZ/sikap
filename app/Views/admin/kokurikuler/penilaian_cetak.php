<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; }

        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { font-size: 13pt; font-weight: bold; }
        .header p  { font-size: 10pt; margin-top: 3px; }

        .info-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .info-table td { padding: 2px 6px; font-size: 10pt; }
        .info-table td:first-child { width: 130px; }
        .info-table td:nth-child(2) { width: 10px; }

        .section-title { font-weight: bold; font-size: 10pt; margin: 14px 0 6px; border-bottom: 1px solid #000; padding-bottom: 3px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 8pt; }
        table.data th, table.data td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        table.data thead th { background-color: #e0e0e0; text-align: center; font-weight: bold; font-size: 8pt; }
        table.data td.center { text-align: center; }
        table.data td.deskripsi { font-size: 7.5pt; line-height: 1.4; }

        .page-break { page-break-after: always; }

        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
            .page-break { page-break-after: always; }
        }

        .btn-print { 
            display: inline-block; margin-bottom: 16px; padding: 8px 20px;
            background: #0d6efd; color: #fff; border: none; border-radius: 4px;
            cursor: pointer; font-size: 11pt;
        }
        .btn-back {
            display: inline-block; margin-bottom: 16px; margin-right: 8px; padding: 8px 20px;
            background: #6c757d; color: #fff; border: none; border-radius: 4px;
            cursor: pointer; font-size: 11pt; text-decoration: none;
        }
    </style>
</head>
<body>

<div class="no-print" style="padding: 12px;">
    <a href="<?= base_url('admin/kokurikuler/penilaian/deskripsi/' . $document['id']) ?>" class="btn-back">← Kembali</a>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
</div>

<!-- HEADER -->
<div class="header">
    <h2>LAPORAN PENILAIAN KOKURIKULER</h2>
</div>

<!-- INFO DOKUMEN -->
<table class="info-table">
    <tr>
        <td>Tahun Ajaran</td><td>:</td><td><?= esc($document['year_name']) ?></td>
    </tr>
    <tr>
        <td>Semester</td><td>:</td><td><?= esc($document['semester']) ?></td>
    </tr>
    <tr>
        <td>Fase/Kelas</td><td>:</td><td>Fase <?= esc($document['fase']) ?> / Kelas <?= esc($document['level_kelas']) ?></td>
    </tr>
    <tr>
        <td>Tema</td><td>:</td><td><?= esc($document['tema']) ?></td>
    </tr>
    <tr>
        <td>Bentuk Kegiatan</td><td>:</td><td><?= esc($document['bentuk_kegiatan_konkret'] ?: '-') ?></td>
    </tr>
    <tr>
        <td>Jenis Kokurikuler</td><td>:</td>
        <td><?= esc(ucfirst(str_replace('_', ' ', $document['jenis_kokurikuler'] ?? ''))) ?></td>
    </tr>
</table>

<!-- TABEL PENILAIAN -->
<div class="section-title">Rekap Penilaian Siswa</div>

<table class="data">
    <thead>
        <tr>
            <th width="4%">No</th>
            <th width="5%">NIS</th>
            <th width="22%">Nama Siswa</th>
            <?php foreach ($rubrik as $r): ?>
                <th><?= esc($r['dimensi_profil']) ?><br><small><?= esc($r['sub_dimensi']) ?></small></th>
            <?php endforeach; ?>
            <th width="40%">Deskripsi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $capaianSymbol = ['Berkembang' => 'BB', 'Cakap' => 'C', 'Mahir' => 'M'];
        foreach ($students as $i => $student):
        ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td class="center"><?= esc($student['nis']) ?></td>
            <td><?= esc($student['name']) ?></td>
            <?php foreach ($rubrik as $r): ?>
                <?php $capaian = $student['penilaian_detail'][(string)$r['id']] ?? '-'; ?>
                <td class="center"><?= esc($capaian) ?></td>
            <?php endforeach; ?>
            <td class="deskripsi"><?= esc($student['deskripsi']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<div style="margin-top: 40px; text-align: right; font-size: 10pt;">
    <p>Dicetak pada: <?= date('d F Y, H:i') ?></p>
</div>

</body>
</html>
