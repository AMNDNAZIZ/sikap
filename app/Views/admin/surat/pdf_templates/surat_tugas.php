<?php
// Template F — Surat Tugas
$ld    = $letter['letter_data'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

$actDate = '';
if (!empty($ld['activity_date'])) {
    $ts = strtotime($ld['activity_date']);
    $actDate = date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <div class="surat-judul" style="margin-bottom: 24px;">
        <div class="judul" style="font-size: 18px; font-weight: bold; text-decoration: underline; letter-spacing: 1px;">SURAT TUGAS</div>
        <div class="nomor" style="font-size: 16px; margin-top: 4px;">Nomor : <?= esc($letter['letter_number']) ?></div>
    </div>

    <?php if (!empty($ld['ref_letter_number'])): ?>
    <div class="pembuka" style="text-align: justify; text-indent: 0; line-height: 1.6; margin-bottom: 12px;">
        Menindaklanjuti surat <?= esc($ld['ref_letter_from'] ?? '-') ?> nomor <?= esc($ld['ref_letter_number']) ?>
        <?php if (!empty($ld['ref_letter_date'])): ?>
        tanggal <?php
            $rd = strtotime($ld['ref_letter_date']);
            echo date('d', $rd) . ' ' . $bulan[(int)date('m', $rd)] . ' ' . date('Y', $rd);
        ?>
        <?php endif; ?>
        perihal <?= esc($ld['ref_letter_subject'] ?? '-') ?>. Dengan ini kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?> memberikan tugas kepada :
    </div>
    <?php else: ?>
    <div class="pembuka" style="text-align: justify; text-indent: 0; line-height: 1.6; margin-bottom: 12px;">
        Dengan ini kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?> memberikan tugas kepada :
    </div>
    <?php endif; ?>

    <table class="data-tabel" style="margin: 8px 0 16px 0;">
        <tr>
            <td class="label" style="width: 150px;">Nama</td>
            <td class="colon" style="width: 14px;">:</td>
            <td><strong><?= esc($letter['recipient_name']) ?></strong></td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td class="colon">:</td>
            <td><?= esc($ld['nip'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td><?= esc($ld['jabatan'] ?? 'Guru') ?></td>
        </tr>
        <tr>
            <td class="label">Tempat Tugas</td>
            <td class="colon">:</td>
            <td><?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?></td>
        </tr>
        <tr>
            <td class="label">Alamat Tempat Tugas</td>
            <td class="colon">:</td>
            <td><?= esc($school['address'] ?? 'Jalan Gedong No. 16 Kecamatan Tamansari') ?></td>
        </tr>
    </table>

    <div class="isi" style="line-height: 1.6; margin-bottom: 8px;">
        Untuk mengikuti kegiatan <?= esc($ld['activity_name'] ?? '-') ?> yang akan dilaksanakan pada :
    </div>

    <table class="data-tabel" style="margin: 8px 0 16px 0;">
        <tr>
            <td class="label" style="width: 150px; text-transform: lowercase;">tanggal</td>
            <td class="colon" style="width: 14px;">:</td>
            <td><?= esc($actDate ?: '-') ?></td>
        </tr>
        <tr>
            <td class="label" style="text-transform: lowercase;">waktu</td>
            <td class="colon">:</td>
            <td>Pukul <?= esc($ld['activity_time'] ?? '-') ?> WIB s.d selesai</td>
        </tr>
        <tr>
            <td class="label" style="text-transform: lowercase;">tempat</td>
            <td class="colon">:</td>
            <td><?= esc($ld['activity_venue'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td><?= esc($ld['activity_address'] ?? '-') ?></td>
        </tr>
    </table>

    <div class="penutup" style="line-height: 1.6; margin-bottom: 24px;">
        Demikian surat tugas ini dibuat agar dilaksanakan dengan penuh tanggung jawab.
    </div>

    <div class="qr-wrap">
        <img src="<?= $qr_data_url ?>" alt="QR">
        <p>Scan untuk verifikasi</p>
    </div>
    
    <div class="ttd-wrapper">
        Jakarta, <?= $tgl ?><br>
        Kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?>
        <span class="ttd-line" style="margin-top: 56px;"></span>
        <strong><?= esc($letter['principal_name_snapshot']) ?></strong><br>
        NIP. <?= esc($letter['principal_nip_snapshot']) ?>
    </div>
    <div class="ttd-clearfix"></div>

</div></body></html>
