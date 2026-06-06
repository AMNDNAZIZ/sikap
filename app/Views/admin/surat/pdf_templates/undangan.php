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
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <!-- Header surat dinas (Nomor, Sifat, Hal, Kepada) -->
    <table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:14px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <table class="data-tabel">
                    <tr><td class="label">Nomor</td><td class="colon">:</td><td><?= esc($letter['letter_number']) ?></td></tr>
                    <tr><td class="label">Sifat</td><td class="colon">:</td><td><?= esc($letter['sifat'] ?? 'Biasa') ?></td></tr>
                    <tr><td class="label">Hal</td><td class="colon">:</td><td><strong><?= esc($letter['subject']) ?></strong></td></tr>
                    <tr><td class="label">Tanggal</td><td class="colon">:</td><td><?= $tgl ?></td></tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top; padding-left:20px;">
                Kepada<br>
                Yth. <?= esc($letter['recipient_name']) ?><br>
                <?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?><br>
                di –<br>
                <strong>Jakarta</strong>
            </td>
        </tr>
    </table>

    <div class="pembuka">Dengan hormat,</div>

    <div class="isi">
        Bersama ini kami mengundang Bapak / Ibu / Saudara untuk hadir dalam acara yang akan kami selenggarakan pada :
    </div>

    <table class="data-tabel" style="margin: 8px 0 8px 30px; width:auto;">
        <tr><td class="label">Hari, Tanggal</td><td class="colon">:</td><td><?= esc($ld['event_day'] ?? '-') ?>, <?= esc($evDate) ?></td></tr>
        <tr><td class="label">Pukul</td><td class="colon">:</td><td><?= esc($ld['event_time'] ?? '-') ?> s/d selesai</td></tr>
        <tr><td class="label">Tempat</td><td class="colon">:</td><td><?= esc($ld['event_venue'] ?? '-') ?></td></tr>
        <tr><td class="label">Acara</td><td class="colon">:</td><td><strong><?= esc($ld['event_agenda'] ?? '-') ?></strong></td></tr>
    </table>

    <div class="isi">
        Mengingat pentingnya acara tersebut, kami mohon kehadiran Bapak / Ibu / Saudara tepat waktu.
    </div>

    <div class="penutup">
        Atas perhatian dan kehadiran Bapak / Ibu / Saudara, kami sampaikan terima kasih.
    </div>

    <div class="qr-wrap"><img src="<?= $qr_data_url ?>" alt="QR"><p>Scan untuk verifikasi</p></div>
    <div class="ttd-wrapper">
        Jakarta, <?= $tgl ?><br>
        Kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?>
        <span class="ttd-line"></span>
        <strong><?= esc($letter['principal_name_snapshot']) ?></strong><br>
        NIP. <?= esc($letter['principal_nip_snapshot']) ?>
    </div>
    <div class="ttd-clearfix"></div>

</div></body></html>
