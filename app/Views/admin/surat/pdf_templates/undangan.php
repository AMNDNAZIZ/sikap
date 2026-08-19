<?php
// Template G — Undangan
$ld    = $letter['letter_data'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

$evDate = '';
if (!empty($ld['event_date'])) {
    $ts = strtotime($ld['event_date']);
    $evDate = date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

// Format principal name & NIP with robust fallbacks
$principalName = trim($letter['principal_name_snapshot'] ?? '');
if ($principalName === '' || $principalName === '-') {
    $principalName = trim($school['headmaster'] ?? ($school['principal_name'] ?? ''));
}
if ($principalName === '' || $principalName === '-') {
    $principalName = (new \App\Models\SettingsModel())->getValue('principal_name') ?? '-';
}

$principalNip = trim($letter['principal_nip_snapshot'] ?? '');
if ($principalNip === '' || $principalNip === '-') {
    $principalNip = trim($school['principal_nip'] ?? '');
}
if ($principalNip === '' || $principalNip === '-') {
    $principalNip = (new \App\Models\SettingsModel())->getValue('principal_nip') ?? '-';
}

// Format recipient lines dynamically (supporting pipes, wali murid, guru/tendik, or custom)
$recipientLines = [];
$recipientName = $letter['recipient_name'] ?? '';
$schoolName = $school['name'] ?? 'SDN Mangga Besar 11 Pagi';

// Detect recipient type to format header and body text properly
$isGuruTendik = str_contains(strtolower($recipientName), 'guru') && str_contains(strtolower($recipientName), 'tenaga kependidikan');
$isWaliMurid  = str_contains(strtolower($recipientName), 'wali murid');

if ($isGuruTendik) {
    // Mode: Seluruh Guru & Pegawai
    $recipientLines = [
        'Guru & Tendik',
        esc($schoolName)
    ];
} elseif ($isWaliMurid) {
    // Mode: Wali Murid Kelas
    // Extract class name if present (format: "Orang Tua / Wali Murid Kelas 1A SDN ...")
    if (preg_match('/Wali Murid Kelas\s+(.+?)\s+' . preg_quote($schoolName, '/') . '/i', $recipientName, $m)) {
        $recipientLines = [
            'Orang Tua / Wali Murid',
            'Kelas ' . trim($m[1]) . ' ' . esc($schoolName),
        ];
    } else {
        $recipientLines = [
            'Orang Tua / Wali Murid',
            esc($schoolName),
        ];
    }
} elseif (!empty($recipientName)) {
    // Mode: Per Guru, Per Pegawai, atau Dinamis (pipe-delimited multi-recipient)
    $rawLines = preg_split('/[;\n|]+/', $recipientName);
    foreach ($rawLines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '') {
            $recipientLines[] = $trimmed;
        }
    }
}

// Format recipient in the body text
$bodyRecipient = esc($recipientName);
if ($isGuruTendik) {
    $bodyRecipient = 'Bapak/Ibu Guru dan Tenaga Kependidikan ' . esc($schoolName);
} elseif ($isWaliMurid) {
    if (preg_match('/Wali Murid Kelas\s+(.+?)\s+' . preg_quote($schoolName, '/') . '/i', $recipientName, $m)) {
        $bodyRecipient = 'Bapak/Ibu Orang Tua / Wali Murid Siswa/i Kelas ' . esc(trim($m[1])) . ' ' . esc($schoolName);
    } else {
        $bodyRecipient = 'Bapak/Ibu Orang Tua / Wali Murid Siswa/i ' . esc($schoolName);
    }
} elseif (count($recipientLines) > 1) {
    // Multi-recipient dynamic: join with koma
    $bodyRecipient = implode(', ', array_map('esc', $recipientLines));
}

// Determine "di Jakarta" vs "di Tempat"
$diTempat = ($isGuruTendik || str_contains(strtolower($recipientName), 'guru') || str_contains(strtolower($recipientName), 'tendik')) ? 'Jakarta' : 'Tempat';
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <!-- Header surat dinas (Nomor, Sifat, Hal di kiri, Tanggal & Kepada di kanan) -->
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;">
        <tr>
            <!-- Kolom Kiri: Nomor, Sifat, Hal -->
            <td style="width: 55%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="width: 60px; padding: 2px 0;">Nomor</td>
                        <td style="width: 14px; text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;"><?= esc($letter['letter_number']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Sifat</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;"><?= esc($ld['sifat'] ?? $letter['sifat'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Hal</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;"><strong><?= esc($letter['subject']) ?></strong></td>
                    </tr>
                </table>
            </td>
            <!-- Kolom Kanan: Tanggal & Penerima -->
            <td style="width: 45%; vertical-align: top; padding: 0 0 0 20px;">
                <div style="line-height: 1.5;">
                    <?= $tgl ?><br><br>
                    Kepada<br>
                    Yth. <?php if (!empty($recipientLines)): ?>
                        <?php foreach ($recipientLines as $line): ?>
                            <?= esc($line) ?><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                    di -<br>
                    <strong><?= $diTempat ?></strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="pembuka" style="line-height: 1.6; margin-bottom: 12px;">Dengan hormat,</div>

    <div class="isi" style="line-height: 1.6; margin-bottom: 12px; text-align: justify; text-indent: 0;">
        Dengan ini kami mengundang <?= $bodyRecipient ?> untuk hadir pada :
    </div>

    <table class="data-tabel" style="margin: 8px 0 12px 30px; width: auto;">
        <tr>
            <td class="label" style="width: 120px; padding: 2px 0;">Hari, Tanggal</td>
            <td class="colon" style="width: 14px; text-align: center; padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($ld['event_day'] ?? '-') ?>, <?= esc($evDate) ?></td>
        </tr>
        <?php if (!empty($ld['event_time'])): ?>
        <tr>
            <td class="label" style="padding: 2px 0;">Pukul</td>
            <td class="colon" style="text-align: center; padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($ld['event_time']) ?> s/d selesai</td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="label" style="padding: 2px 0;">Tempat</td>
            <td class="colon" style="text-align: center; padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($ld['event_venue'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label" style="padding: 2px 0;">Acara</td>
            <td class="colon" style="text-align: center; padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($ld['event_agenda'] ?? '-') ?></td>
        </tr>
    </table>

    <div class="penutup" style="line-height: 1.6; margin-top: 16px; margin-bottom: 24px; text-align: justify; text-indent: 0;">
        Demikian undangan ini kami sampaikan, Atas perhatiannya diucapkan terima kasih.
    </div>

    <div class="qr-wrap">
        <img src="<?= $qr_data_url ?>" alt="QR">
        <p>Scan untuk verifikasi</p>
    </div>
    
    <div class="ttd-wrapper">
        Kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?>
        <span class="ttd-line" style="margin-top: 56px;"></span>
        <strong><?= esc($principalName) ?></strong><br>
        NIP. <?= esc($principalNip) ?>
    </div>
    <div class="ttd-clearfix"></div>

</div></body></html>
