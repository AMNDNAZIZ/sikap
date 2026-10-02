<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #000; margin: 0; padding: 20px; }
        .kop-surat { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat img { max-width: 100%; height: auto; }
        .kop-surat h2, .kop-surat h3, .kop-surat p { margin: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .fw-bold { font-weight: bold; }
        .mt-4 { margin-top: 20px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        table.meta-info { width: 100%; margin-bottom: 20px; }
        table.meta-info td { vertical-align: top; }
        table.details { width: 80%; margin: 0 auto 20px auto; }
        table.details td { padding: 3px; }
        .signature-area { width: 100%; margin-top: 50px; }
        .signature-area td { width: 50%; text-align: center; vertical-align: bottom; }
        .ttd-box { height: 80px; }
    </style>
</head>
<body>

    <?php if (!empty($kop_base64)): ?>
        <div class="kop-surat" style="border: none;">
            <img src="<?= $kop_base64 ?>" alt="Kop Surat">
        </div>
    <?php else: ?>
        <div class="kop-surat">
            <h2><?= esc($school['name'] ?? 'NAMA SEKOLAH') ?></h2>
            <p><?= esc($school['address'] ?? 'Alamat Sekolah') ?></p>
            <p>Telp: <?= esc($school['phone'] ?? '-') ?> | Email: <?= esc($school['email'] ?? '-') ?></p>
        </div>
    <?php endif; ?>

    <table class="meta-info">
        <tr>
            <td width="15%">Nomor</td>
            <td width="2%">:</td>
            <td width="43%">.... / BK / <?= date('m/Y', strtotime($summon['issue_date'])) ?></td>
            <td width="40%" class="text-right"><?= esc($school['city'] ?? 'Kota') ?>, <?= date('d F Y', strtotime($summon['issue_date'])) ?></td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>-</td>
            <td></td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td class="fw-bold">Panggilan Orang Tua / Wali Siswa (<?= esc($summon['level']) ?>)</td>
            <td></td>
        </tr>
    </table>

    <div class="mb-4">
        Yth. Bapak/Ibu/Wali dari Ananda <br>
        <strong><?= esc($summon['student_name']) ?></strong><br>
        Di Tempat
    </div>

    <div class="text-justify mb-4">
        <p>Dengan hormat,</p>
        <p>Segala puji bagi Tuhan Yang Maha Esa, semoga Bapak/Ibu senantiasa dalam keadaan sehat dan sukses dalam menjalankan aktivitas sehari-hari.</p>
        <p>Sehubungan dengan kelancaran proses belajar mengajar dan pembinaan kedisiplinan siswa di sekolah, kami memohon kehadiran Bapak/Ibu/Wali siswa untuk hadir pada:</p>
    </div>

    <table class="details">
        <tr>
            <td width="30%">Hari, Tanggal</td>
            <td width="5%">:</td>
            <td><strong><?= date('l, d F Y', strtotime($summon['summon_date'])) ?></strong></td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td><strong><?= date('H:i', strtotime($summon['summon_date'])) ?> WIB</strong> s.d Selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>Ruang Bimbingan Konseling (BK)</td>
        </tr>
        <tr>
            <td>Menemui</td>
            <td>:</td>
            <td>Guru Bimbingan Konseling / Wali Kelas</td>
        </tr>
        <tr>
            <td>Keperluan</td>
            <td>:</td>
            <td>Membicarakan perkembangan sikap dan perilaku Ananda (<?= esc($summon['reason']) ?>)</td>
        </tr>
    </table>

    <div class="text-justify mb-4">
        <p>Mengingat pentingnya acara tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Demikian surat panggilan ini kami sampaikan, atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
    </div>

    <table class="signature-area">
        <tr>
            <td>Mengetahui,<br>Kepala Sekolah</td>
            <td>Guru Bimbingan Konseling</td>
        </tr>
        <tr>
            <td class="ttd-box"></td>
            <td class="ttd-box"></td>
        </tr>
        <tr>
            <td><strong><?= esc($school['principal_name'] ?? '____________________') ?></strong><br>NIP. <?= esc($school['principal_nip'] ?? '____________________') ?></td>
            <td><strong>____________________</strong><br>NIP. ____________________</td>
        </tr>
    </table>

</body>
</html>
