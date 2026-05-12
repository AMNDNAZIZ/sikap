<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; }

        .no-print { padding: 12px; }
        .btn-print {
            display: inline-block; padding: 8px 20px; background: #0d6efd;
            color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 10pt;
        }
        .btn-back {
            display: inline-block; padding: 8px 20px; background: #6c757d; margin-right: 8px;
            color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 10pt;
            text-decoration: none;
        }

        .doc-section { margin-bottom: 30px; }
        .page-break { page-break-after: always; }

        .doc-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
        .doc-header h2 { font-size: 12pt; font-weight: bold; }
        .doc-header p  { font-size: 9pt; margin-top: 2px; }

        .info-table { width: 50%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 2px 5px; font-size: 9pt; }
        .info-table td:first-child { width: 130px; }
        .info-table td:nth-child(2) { width: 8px; }

        .section-label { font-weight: bold; font-size: 9pt; margin: 10px 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 7.5pt; }
        table.data th, table.data td { border: 1px solid #000; padding: 3px 4px; vertical-align: top; }
        table.data thead th { background-color: #e0e0e0; text-align: center; font-weight: bold; }
        table.data td.center { text-align: center; }
        table.data td.deskripsi { font-size: 7pt; line-height: 1.4; }

        .refleksi-box { border: 1px solid #000; padding: 6px 8px; min-height: 50px; font-size: 8.5pt; line-height: 1.5; margin-bottom: 8px; }
        .refleksi-label { font-weight: bold; font-size: 9pt; margin-bottom: 3px; }

        @media print {
            .no-print { display: none; }
            body { margin: 8mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="<?= base_url('admin/kokurikuler/pelaporan') ?>" class="btn-back">← Kembali</a>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
</div>

<?php foreach ($documents as $idx => $doc): ?>
<?php $laporan = $doc['laporan'] ?? []; ?>

<div class="doc-section <?= $idx < count($documents) - 1 ? 'page-break' : '' ?>">

    <!-- Header -->
    <div class="doc-header">
        <h2>LAPORAN KEGIATAN KOKURIKULER</h2>
        <p><?= esc($doc['tema']) ?></p>
        <p>Tahun Ajaran <?= esc($doc['year_name']) ?> &bull; Semester <?= esc($doc['semester']) ?> &bull; Fase <?= esc($doc['fase']) ?> &bull; Kelas <?= esc($doc['level_kelas']) ?></p>
    </div>

    <!-- Info -->
    <table class="info-table">
        <tr><td>Tahun Ajaran</td><td>:</td><td><?= esc($doc['year_name']) ?></td></tr>
        <tr><td>Semester</td><td>:</td><td><?= esc($doc['semester']) ?></td></tr>
        <tr><td>Fase / Kelas</td><td>:</td><td>Fase <?= esc($doc['fase']) ?> / Kelas <?= esc($doc['level_kelas']) ?></td></tr>
        <tr><td>Tema</td><td>:</td><td><?= esc($doc['tema']) ?></td></tr>
        <tr><td>Bentuk Kegiatan</td><td>:</td><td><?= esc($doc['bentuk_kegiatan_konkret'] ?: '-') ?></td></tr>
        <tr><td>Jenis Kokurikuler</td><td>:</td><td><?= esc(ucfirst(str_replace('_', ' ', $doc['jenis_kokurikuler'] ?? ''))) ?></td></tr>
    </table>

    <!-- Tabel Rekap -->
    <div class="section-label">Rekap Penilaian Siswa</div>
    <table class="data">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">NIS</th>
                <th width="20%">Nama Siswa</th>
                <?php foreach ($doc['rubrik'] as $r): ?>
                    <th><?= esc($r['dimensi_profil']) ?><br><small><?= esc($r['sub_dimensi']) ?></small></th>
                <?php endforeach; ?>
                <th width="35%">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doc['students'] as $i => $student): ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td class="center"><?= esc($student['nis']) ?></td>
                <td><?= esc($student['name']) ?></td>
                <?php foreach ($doc['rubrik'] as $r): ?>
                    <td class="center"><?= esc($student['penilaian_detail'][(string)$r['id']] ?? '-') ?></td>
                <?php endforeach; ?>
                <td class="deskripsi"><?= esc($student['deskripsi']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Refleksi & Rekomendasi -->
    <div class="section-label">Refleksi & Rekomendasi</div>
    <div class="row" style="display: flex; gap: 12px;">
        <div style="flex: 1;">
            <div class="refleksi-label">Refleksi Pelaksanaan</div>
            <div class="refleksi-box"><?= nl2br(esc($laporan['refleksi'] ?? '')) ?></div>
        </div>
        <div style="flex: 1;">
            <div class="refleksi-label">Rekomendasi Perbaikan</div>
            <div class="refleksi-box"><?= nl2br(esc($laporan['rekomendasi'] ?? '')) ?></div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: right; font-size: 8.5pt;">
        Dicetak pada: <?= date('d F Y, H:i') ?>
    </div>

</div>
<?php endforeach; ?>

</body>
</html>
