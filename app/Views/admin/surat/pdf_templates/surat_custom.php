<?php
// Template Custom / Dinamis
$ld    = $letter['letter_data'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

$headerStyle = $ld['header_style'] ?? 'tengah';
$useKop      = (int) ($letter['use_kop'] ?? 1);

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
    /* Jika cetak pada kertas fisik tanpa KOP digital, beri ruang atas */
    <?php if ($useKop === 0): ?>
    .page {
        padding-top: 3.5cm !important;
    }
    <?php endif; ?>

    /* Styling khusus untuk isi surat dinamis */
    .surat-custom-body {
        line-height: 1.5;
        font-size: 13.5px;
        text-align: justify;
    }
    .surat-custom-body table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 10px 0 !important;
    }
    .surat-custom-body table.tbl-bordered th,
    .surat-custom-body table.tbl-bordered td {
        border: 1px solid #000 !important;
        padding: 5px 7px !important;
        font-size: 13px;
        vertical-align: top;
    }
    .surat-custom-body table.tbl-bordered th {
        background-color: #f2f2f2 !important;
        font-weight: bold;
        text-align: center;
    }
    /* Paragraf naskah dinas: margin terkontrol */
    .surat-custom-body p {
        margin-top: 0;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    .surat-custom-body ul, .surat-custom-body ol {
        margin: 6px 0;
        padding-left: 20px;
    }
    .surat-custom-body li {
        margin-bottom: 3px;
    }
    /* Page Break untuk multi-halaman */
    .surat-custom-body .page-break {
        page-break-after: always;
        break-after: page;
        height: 0;
        display: block;
    }
    /* Subscript & Superscript di PDF */
    .surat-custom-body sub {
        vertical-align: sub;
        font-size: 0.8em;
    }
    .surat-custom-body sup {
        vertical-align: super;
        font-size: 0.8em;
    }
    /* Todo list / checklist */
    .surat-custom-body .todo-list {
        list-style-type: none;
        padding-left: 5px;
    }
    .surat-custom-body .todo-list li {
        margin-bottom: 4px;
    }
</style>

<?php if ($headerStyle === 'tengah'): ?>
    <!-- Header Gaya Tengah -->
    <div class="surat-judul" style="margin-bottom: 20px;">
        <div class="judul" style="font-size: 17px; font-weight: bold; text-decoration: underline; text-transform: uppercase; letter-spacing: 0.5px;"><?= esc($letter['subject']) ?></div>
        <div class="nomor" style="font-size: 15px; margin-top: 4px;">Nomor: <?= esc($letter['letter_number']) ?></div>
    </div>
<?php else: ?>
    <!-- Header Gaya Kiri-Kanan -->
    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; margin-bottom: 20px;">
        <tr>
            <!-- Kolom Kiri: Nomor, Sifat, Hal -->
            <td style="width: 55%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
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
                    <?= esc($school['city'] ?? 'Jakarta') ?>, <?= $tgl ?><br><br>
                    Kepada<br>
                    Yth. <?php if (!empty($recipientLines)): ?>
                        <?php foreach ($recipientLines as $line): ?>
                            <?= esc($line) ?><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                    di -<br>
                    <strong><?= (str_contains(strtolower($letter['recipient_name']), 'guru') || str_contains(strtolower($letter['recipient_name']), 'kependidikan') || str_contains(strtolower($letter['recipient_name']), 'tendik')) ? esc($school['city'] ?? 'Jakarta') : 'Tempat' ?></strong>
                </div>
            </td>
        </tr>
    </table>
<?php endif; ?>

<!-- Isi Surat Custom -->
<div class="surat-custom-body">
    <?= $processed_body ?>
</div>

<!-- Footer Tanda Tangan & QR (Tabel 2-Kolom anti-pecah halaman) -->
<table style="width: 100%; margin-top: 24px; border-collapse: collapse; page-break-inside: avoid;">
    <tr>
        <td style="width: 45%; vertical-align: top; text-align: left; padding: 0;">
            <div class="qr-wrap" style="text-align: center; display: inline-block;">
                <img src="<?= $qr_data_url ?>" alt="QR" style="width: 65px; height: 65px; display: block; margin: 0 auto;">
                <p style="font-size: 8.5px; margin: 3px 0 0 0; color: #444;">Scan untuk verifikasi</p>
            </div>
        </td>
        <td style="width: 55%; vertical-align: top; text-align: center; padding: 0;">
            <div style="font-size: 13.5px; line-height: 1.4;">
                <?php if ($headerStyle === 'tengah'): ?>
                    <?= esc($school['city'] ?? 'Jakarta') ?>, <?= $tgl ?><br>
                <?php endif; ?>
                Kepala <?= esc($school['name'] ?? 'Sekolah') ?>
                <div style="height: 52px;"></div>
                <strong style="text-decoration: underline;"><?= esc((!empty($letter['principal_name_snapshot']) && $letter['principal_name_snapshot'] !== '-') ? $letter['principal_name_snapshot'] : ($school['headmaster'] ?? '-')) ?></strong><br>
                NIP. <?= esc((!empty($letter['principal_nip_snapshot']) && $letter['principal_nip_snapshot'] !== '-') ? $letter['principal_nip_snapshot'] : ($school['principal_nip'] ?? '-')) ?>
            </div>
        </td>
    </tr>
</table>

</div></body></html>
