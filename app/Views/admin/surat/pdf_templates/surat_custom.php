<?php
// Template Custom / Dinamis
$ld    = $letter['letter_data'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

$headerStyle = $ld['header_style'] ?? 'tengah';

// Format recipient lines dynamically for the kiri_kanan layout
$recipientLines = [];
if (!empty($letter['recipient_name'])) {
    $rawLines = preg_split('/[;\n|]+/', $letter['recipient_name']);
    foreach ($rawLines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '') {
            $recipientLines[] = $trimmed;
        }
    }
}
?>
<?= view('admin/surat/pdf_templates/_header') ?>

<style>
    /* Styling khusus untuk tabel di dalam isi surat dinamis */
    .surat-custom-body {
        line-height: 1.6;
        font-size: 14px;
        text-align: justify;
    }
    .surat-custom-body table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 12px 0 !important;
    }
    .surat-custom-body table th,
    .surat-custom-body table td {
        border: 1px solid #000 !important;
        padding: 6px 8px !important;
        font-size: 14px;
        vertical-align: top;
    }
    .surat-custom-body table th {
        background-color: #f2f2f2 !important;
        font-weight: bold;
        text-align: center;
    }
    .surat-custom-body p {
        margin: 8px 0;
        line-height: 1.6;
    }
    .surat-custom-body ul, .surat-custom-body ol {
        margin: 8px 0;
        padding-left: 20px;
    }
    .surat-custom-body li {
        margin-bottom: 4px;
    }
</style>

<?php if ($headerStyle === 'tengah'): ?>
    <!-- Header Gaya Tengah -->
    <div class="surat-judul" style="margin-bottom: 24px;">
        <div class="judul" style="font-size: 18px; font-weight: bold; text-decoration: underline; text-transform: uppercase; letter-spacing: 0.5px;"><?= esc($letter['subject']) ?></div>
        <div class="nomor" style="font-size: 16px; margin-top: 4px;">Nomor: <?= esc($letter['letter_number']) ?></div>
    </div>
<?php else: ?>
    <!-- Header Gaya Kiri-Kanan -->
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 24px;">
        <tr>
            <!-- Kolom Kiri: Nomor, Sifat, Lampiran, Hal -->
            <td style="width: 55%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="width: 70px; padding: 2px 0; vertical-align: top;">Nomor</td>
                        <td style="width: 14px; text-align: center; padding: 2px 0; vertical-align: top;">:</td>
                        <td style="padding: 2px 0; vertical-align: top;"><?= esc($letter['letter_number']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0; vertical-align: top;">Sifat</td>
                        <td style="text-align: center; padding: 2px 0; vertical-align: top;">:</td>
                        <td style="padding: 2px 0; vertical-align: top;"><?= esc($letter['sifat'] ?? 'Biasa') ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0; vertical-align: top;">Hal</td>
                        <td style="text-align: center; padding: 2px 0; vertical-align: top;">:</td>
                        <td style="padding: 2px 0; vertical-align: top;"><strong><?= esc($letter['subject']) ?></strong></td>
                    </tr>
                </table>
            </td>
            <!-- Kolom Kanan: Tanggal & Penerima -->
            <td style="width: 45%; vertical-align: top; padding: 0 0 0 20px;">
                <div style="line-height: 1.5;">
                    <?= $tgl ?><br><br>
                    Kepada<br>
                    Yth. <?php if (!empty($recipientLines)): ?>
                        <?php foreach ($recipientLines as $idx => $line): ?>
                            <?php if ($idx === 0): ?>
                                <strong><?= esc($line) ?></strong><br>
                            <?php else: ?>
                                <?= esc($line) ?><br>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                    di -<br>
                    <strong><?= (str_contains(strtolower($letter['recipient_name']), 'guru') || str_contains(strtolower($letter['recipient_name']), 'kependidikan') || str_contains(strtolower($letter['recipient_name']), 'tendik')) ? 'Jakarta' : 'Tempat' ?></strong>
                </div>
            </td>
        </tr>
    </table>
<?php endif; ?>

<!-- Isi Surat Custom -->
<div class="surat-custom-body">
    <?= $processed_body ?>
</div>

<!-- Footer Tanda Tangan & QR -->
<div style="margin-top: 30px;">
    <div class="qr-wrap">
        <img src="<?= $qr_data_url ?>" alt="QR">
        <p>Scan untuk verifikasi</p>
    </div>
    
    <div class="ttd-wrapper">
        <?php if ($headerStyle === 'tengah'): ?>
            Jakarta, <?= $tgl ?><br>
        <?php endif; ?>
        Kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?>
        <span class="ttd-line" style="margin-top: 56px;"></span>
        <strong><?= esc($letter['principal_name_snapshot']) ?></strong><br>
        NIP. <?= esc($letter['principal_nip_snapshot']) ?>
    </div>
    <div class="ttd-clearfix"></div>
</div>

</div></body></html>
