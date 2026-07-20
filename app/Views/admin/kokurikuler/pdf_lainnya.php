<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dokumen Rencana Kokurikuler - Kegiatan Lainnya</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16pt;
            font-weight: bold;
        }
        .header h3 {
            margin: 5px 0;
            font-size: 14pt;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 10px;
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-left: 4px solid #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .info-table td:first-child {
            width: 30%;
            font-weight: bold;
        }
        ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .kegiatan-table {
            margin-top: 10px;
        }
        .kegiatan-table th {
            background-color: #d0d0d0;
            text-align: center;
        }
        .kegiatan-table td:first-child {
            text-align: center;
            width: 10%;
        }
        .custom-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #1976d2;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>DOKUMEN RENCANA KOKURIKULER</h2>
        <h3>KEGIATAN LAINNYA</h3>
        <h3><?= esc($document['school_name'] ?? 'Nama Sekolah') ?></h3>
        <p>Tahun Ajaran <?= esc($document['year_name'] ?? '-') ?></p>
    </div>

    <!-- Informasi Dasar -->
    <div class="section">
        <div class="section-title">A. INFORMASI UMUM</div>
        <table class="info-table">
            <tr>
                <td>Tema</td>
                <td><?= esc($document['tema']) ?></td>
            </tr>
            <tr>
                <td>Fase</td>
                <td>Fase <?= esc($document['fase']) ?></td>
            </tr>
            <tr>
                <td>Level Kelas</td>
                <td><?= esc($document['level_kelas']) ?></td>
            </tr>
            <tr>
                <td>Jumlah Pertemuan</td>
                <td><?= esc($document['jumlah_pertemuan']) ?> Pertemuan</td>
            </tr>
            <tr>
                <td>Bentuk Kegiatan</td>
                <td>Kegiatan Lainnya (Nilai-nilai/Kekhasan Sekolah/Keunggulan/Kebijakan Daerah)</td>
            </tr>
        </table>
    </div>

    <!-- Dimensi Profil Lulusan -->
    <div class="section">
        <div class="section-title">B. DIMENSI PROFIL LULUSAN</div>
        <?php 
        $dimensi = json_decode($document['dimensi_profil'], true);
        if ($dimensi && is_array($dimensi)):
        ?>
            <ul>
                <?php foreach ($dimensi as $d): ?>
                    <li><?= esc($d) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Deskripsi Kegiatan Khusus -->
    <div class="section">
        <div class="section-title">C. DESKRIPSI KEGIATAN KHUSUS</div>
        <div class="custom-box">
            <?php 
            $detail = json_decode($document['kegiatan_detail'], true);
            if ($detail && isset($detail['text'])):
            ?>
                <p><?= nl2br(esc($detail['text'])) ?></p>
                <?php if (isset($detail['items']) && !empty($detail['items'])): ?>
                    <p style="margin-top: 10px; font-weight: bold; margin-bottom: 5px;">Sub Dimensi untuk Penilaian:</p>
                    <ul style="margin-top: 0; padding-left: 20px;">
                        <?php foreach ($detail['items'] as $item): ?>
                            <li>
                                <strong><?= esc($item['dimensi_profil']) ?></strong>: <?= esc($item['sub_dimensi']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <p><?= nl2br(esc($document['kegiatan_detail'])) ?></p>
            <?php endif; ?>
        </div>
        <p><em>Kegiatan ini dirancang berdasarkan nilai-nilai khas, keunggulan yang dimiliki sekolah, atau kebijakan daerah setempat untuk memperkaya pengalaman belajar siswa.</em></p>
    </div>

    <!-- Tujuan Pembelajaran -->
    <div class="section">
        <div class="section-title">D. TUJUAN PEMBELAJARAN</div>
        <p><?= nl2br(esc($document['tujuan_pembelajaran'])) ?></p>
    </div>

    <!-- Praktik Pedagogis -->
    <div class="section">
        <div class="section-title">E. PRAKTIK PEDAGOGIS</div>
        <p><?= nl2br(esc($document['praktik_pedagogis'])) ?></p>
    </div>

    <!-- Lingkungan Belajar -->
    <div class="section">
        <div class="section-title">F. LINGKUNGAN BELAJAR</div>
        <p><?= nl2br(esc($document['lingkungan_belajar'])) ?></p>
    </div>

    <!-- Kemitraan -->
    <div class="section">
        <div class="section-title">G. KEMITRAAN PEMBELAJARAN</div>
        <?php 
        $kemitraan = json_decode($document['kemitraan'], true);
        if ($kemitraan && is_array($kemitraan)):
        ?>
            <ul>
                <?php foreach ($kemitraan as $k): ?>
                    <li><?= esc($k) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>-</p>
        <?php endif; ?>
    </div>

    <!-- Teknologi Digital -->
    <div class="section">
        <div class="section-title">H. PEMANFAATAN TEKNOLOGI DIGITAL</div>
        <?php 
        $teknologi = json_decode($document['teknologi_digital'], true);
        if ($teknologi && is_array($teknologi)):
        ?>
            <ul>
                <?php foreach ($teknologi as $t): ?>
                    <li><?= esc($t) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>-</p>
        <?php endif; ?>
    </div>

    <!-- Kegiatan Kokurikuler -->
    <div class="section">
        <div class="section-title">I. RINCIAN KEGIATAN KOKURIKULER</div>
        <?php 
        $kegiatan = json_decode($document['kegiatan_kokurikuler'], true);
        if ($kegiatan && is_array($kegiatan)):
        ?>
            <table class="kegiatan-table">
                <thead>
                    <tr>
                        <th>Pertemuan</th>
                        <th>Kegiatan</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kegiatan as $k): ?>
                        <tr>
                            <td><?= $k['pertemuan'] ?? '-' ?></td>
                            <td><?= esc($k['kegiatan'] ?? '-') ?></td>
                            <td><?= esc($k['deskripsi'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada data kegiatan</p>
        <?php endif; ?>
    </div>

    <!-- Tanda Tangan -->
    <div class="section" style="margin-top: 40px;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: center;">
                    <p>Mengetahui,<br>Kepala Sekolah</p>
                    <br><br><br>
                    <p><strong><?= !empty($document['headmaster']) ? esc($document['headmaster']) : '_______________________' ?></strong></p>
                    <p style="margin-top: 1px;">NIP: <?= !empty($document['principal_nip']) ? esc($document['principal_nip']) : '-' ?></p>
                </td>
                <td style="width: 50%; border: none; text-align: center;">
                    <p>
                        <?= !empty($document['city_regency']) ? esc($document['city_regency']) . ', ' : '' ?><?= date('d F Y') ?><br>
                        Guru Pembuat
                    </p>
                    <br><br><br>
                    <p><strong><?= !empty($document['teacher_name']) ? esc($document['teacher_name']) : '_______________________' ?></strong></p>
                    <p style="margin-top: 1px;">NIP: <?= !empty($document['teacher_nip']) ? esc($document['teacher_nip']) : '-' ?></p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
