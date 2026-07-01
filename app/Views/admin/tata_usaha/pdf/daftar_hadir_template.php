<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir</title>
    <style>
        body        { font-family: Arial, sans-serif; font-size: 14px; color: #000; margin: 0; }
        h3          { text-align: center; font-size: 18px; margin: 2px 0 1px 0; text-transform: uppercase; font-weight: bold; }
        .sub-title  { text-align: center; font-size: 16px; color: #444; margin-bottom: 6px; }

        table { border-collapse: collapse; width: 100%; font-size: 13px; }
        th, td { border: 1px solid #000; padding: 2px 4px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { text-align: left; }
        td.center { text-align: center; }
        td.ttd     { height: 28px; min-width: 70px; }

        /* Tanda tangan kepala sekolah — pojok kanan bawah */
        .ttd-wrapper { margin-top: 12px; width: 220px; float: right; text-align: center; page-break-inside: avoid; font-size: 14px; }
        .ttd-wrapper .ttd-line { margin-top: 40px; border-top: 1px solid #000; width: 100%; display: block; }
        .ttd-clearfix { clear: both; }

        /* Pemisah antar kelas */
        .kelas-section { margin-bottom: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

<?php
/**
 * Label kolom standar yang diketahui
 */
$labelMap = [
    'no'           => 'No',
    'nis'          => 'NIS',
    'nisn'         => 'NISN',
    'name'         => 'Nama Siswa',
    'gender'       => 'L/P',
    'birth_place'  => 'Tempat Lahir',
    'birth_date'   => 'Tanggal Lahir',
    'religion'     => 'Agama',
    'address'      => 'Alamat',
    'father_name'  => 'Nama Ayah',
    'mother_name'  => 'Nama Ibu',
    'Tanda Tangan' => 'Tanda Tangan',
];

$totalKelas = count($siswaPerKelas);

foreach ($siswaPerKelas as $kelasIdx => $kelasData):
    $namaKelas = $kelasData['namaKelas'];
    $siswa     = $kelasData['siswa'];
    $isLast    = ($kelasIdx === $totalKelas - 1);
?>

<div class="kelas-section">

    <?php if (!empty($kop_base64)): ?>
        <div style="width:auto; margin:0 -30px 4px -30px; text-align:center;">
            <img src="<?= $kop_base64 ?>" style="width:100%; height:auto; display:block; margin:0 auto;" />
        </div>
        <div style="border-top:2px solid #000; margin:5px -30px 12px -30px;"></div>
    <?php endif; ?>

    <h3>Daftar Hadir Kegiatan</h3>
    <?php if (!empty($namaKegiatan)): ?>
        <h3 style="font-size:18px; margin: 0 0 2px 0;"><?= esc(strtoupper($namaKegiatan)) ?></h3>
    <?php endif; ?>
    <div class="sub-title">
        Kelas: <strong><?= esc($namaKelas) ?></strong>
        &nbsp;|&nbsp; Tanggal: <?= esc($tanggalPdf) ?>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ($kolom as $k):
                    // Kolom kustom diawali __custom__
                    if (str_starts_with($k, '__custom__')) {
                        $label = substr($k, strlen('__custom__'));
                    } else {
                        $label = $labelMap[$k] ?? esc($k);
                    }
                ?>
                    <th><?= esc($label) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($siswa)): ?>
                <tr>
                    <td colspan="<?= count($kolom) ?>" style="text-align:center; padding:14px; color:#666;">
                        Tidak ada data siswa untuk kelas ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($siswa as $idx => $s): ?>
                    <tr>
                        <?php foreach ($kolom as $k): ?>

                            <?php if ($k === 'no'): ?>
                                <td class="center" style="width:26px;"><?= $idx + 1 ?></td>

                            <?php elseif ($k === 'nis'): ?>
                                <td class="center" style="width:65px;"><?= esc($s['nis'] ?? '-') ?></td>

                            <?php elseif ($k === 'nisn'): ?>
                                <td class="center" style="width:75px;"><?= esc($s['nisn'] ?? '-') ?></td>

                            <?php elseif ($k === 'name'): ?>
                                <td><?= esc($s['name'] ?? '-') ?></td>

                            <?php elseif ($k === 'gender'): ?>
                                <td class="center" style="width:28px;"><?= esc($s['gender'] ?? '-') ?></td>

                            <?php elseif ($k === 'birth_date'): ?>
                                <td class="center">
                                    <?= !empty($s['birth_date']) ? date('d-m-Y', strtotime($s['birth_date'])) : '-' ?>
                                </td>

                            <?php elseif ($k === 'Tanda Tangan'): ?>
                                <td class="ttd"></td>

                            <?php elseif (str_starts_with($k, '__custom__')): ?>
                                <!-- Kolom kustom — kosong, untuk diisi manual -->
                                <td class="ttd"></td>

                            <?php else: ?>
                                <td><?= esc($s[$k] ?? '-') ?></td>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan — pojok kanan bawah -->
    <?php if (!empty($tampilTtd)): ?>
    <div class="ttd-wrapper">
        <?= esc($sekolah['city_regency'] ?? 'Kota') ?>, <?= esc($tanggalPdf) ?><br>
        Kepala <?= esc($sekolah['name'] ?? 'Sekolah') ?>
        <span class="ttd-line"></span>
        <strong><?= esc($sekolah['headmaster'] ?? '______________________') ?></strong><br>
        NIP. <?= esc($sekolah['principal_nip'] ?? '-') ?>
    </div>
    <div class="ttd-clearfix"></div>
    <?php endif; ?>
</div>

<?php if (!$isLast): ?>
    <!-- Ganti halaman setelah setiap kelas kecuali yang terakhir -->
    <div class="page-break"></div>
<?php endif; ?>

<?php endforeach; ?>

</body>
</html>
