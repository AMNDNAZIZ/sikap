<?php
// Template F — Surat Tugas
$ld    = $letter['letter_data'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

$actDate = '';
if (!empty($ld['activity_date'])) {
    $actDate = date('d', strtotime($ld['activity_date'])) . ' ' . $bulan[(int)date('m', strtotime($ld['activity_date']))] . ' ' . date('Y', strtotime($ld['activity_date']));
}
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <!-- Nomor & Sifat (gaya surat dinas) -->
    <table class="data-tabel" style="margin-bottom:16px; width:100%;">
        <tr>
            <td class="label">Nomor</td><td class="colon">:</td>
            <td><?= esc($letter['letter_number']) ?></td>
        </tr>
        <tr>
            <td class="label">Sifat</td><td class="colon">:</td>
            <td><?= esc($letter['sifat'] ?? 'Biasa') ?></td>
        </tr>
        <tr>
            <td class="label">Hal</td><td class="colon">:</td>
            <td><strong>Surat Tugas</strong></td>
        </tr>
    </table>

    <?php if (!empty($ld['ref_letter_number'])): ?>
    <div class="isi" style="margin-bottom:10px;">
        Menindaklanjuti surat dari <strong><?= esc($ld['ref_letter_from'] ?? '-') ?></strong>
        Nomor <strong><?= esc($ld['ref_letter_number']) ?></strong>
        <?php if (!empty($ld['ref_letter_date'])): ?>
        tanggal <?php
            $rd = strtotime($ld['ref_letter_date']);
            echo date('d', $rd) . ' ' . $bulan[(int)date('m', $rd)] . ' ' . date('Y', $rd);
        ?>
        <?php endif; ?>
        tentang <strong><?= esc($ld['ref_letter_subject'] ?? '-') ?></strong>,
    </div>
    <?php endif; ?>

    <div class="isi">
        Yang bertanda tangan di bawah ini, Kepala <?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?>,
        dengan ini menugaskan :
    </div>

    <table class="data-tabel" style="margin: 8px 0;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($letter['recipient_name']) ?></strong></td></tr>
        <tr><td class="label">NIP</td><td class="colon">:</td><td><?= esc($ld['nip'] ?? '-') ?></td></tr>
        <tr><td class="label">Jabatan</td><td class="colon">:</td><td><?= esc($ld['jabatan'] ?? 'Guru') ?></td></tr>
        <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></td></tr>
        <tr><td class="label">Alamat Tempat Tugas</td><td class="colon">:</td><td><?= esc($school['address'] ?? 'Jalan Gedong No. 16 Kecamatan Tamansari') ?></td></tr>
    </table>

    <div class="isi">Untuk mengikuti / melaksanakan :</div>

    <table class="data-tabel" style="margin: 8px 0;">
        <tr><td class="label">Kegiatan</td><td class="colon">:</td><td><strong><?= esc($ld['activity_name'] ?? '-') ?></strong></td></tr>
        <tr><td class="label">Tanggal</td><td class="colon">:</td><td><?= esc($actDate ?: '-') ?></td></tr>
        <tr><td class="label">Pukul</td><td class="colon">:</td><td><?= esc($ld['activity_time'] ?? '-') ?> s/d selesai</td></tr>
        <tr><td class="label">Tempat</td><td class="colon">:</td><td><?= esc($ld['activity_venue'] ?? '-') ?></td></tr>
        <tr><td class="label">Alamat</td><td class="colon">:</td><td><?= esc($ld['activity_address'] ?? '-') ?></td></tr>
    </table>

    <div class="penutup">
        Demikian Surat Tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab and
        melaporkan hasilnya kepada pimpinan.
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
