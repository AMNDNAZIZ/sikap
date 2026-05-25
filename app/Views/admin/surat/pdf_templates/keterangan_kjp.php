<?php
// Template E — Keterangan KJP / Dokumen Khusus
$ld  = $letter['letter_data'] ?? [];
$rd  = $letter['recipient_detail'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <div class="surat-judul">
        <div class="judul">SURAT KETERANGAN</div>
        <div class="nomor">Nomor : <?= esc($letter['letter_number']) ?></div>
    </div>

    <div class="pembuka">Yang bertanda tangan di bawah ini :</div>
    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><?= esc($letter['principal_name_snapshot']) ?></td></tr>
        <tr><td class="label">NIP</td><td class="colon">:</td><td><?= esc($letter['principal_nip_snapshot']) ?></td></tr>
        <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
        <tr><td class="label">Unit Kerja</td><td class="colon">:</td><td><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></td></tr>
    </table>

    <div class="pembuka">Menerangkan bahwa :</div>
    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($letter['recipient_name']) ?></strong></td></tr>
        <tr><td class="label">NISN</td><td class="colon">:</td><td><?= esc($rd['nisn'] ?? $ld['nisn'] ?? '-') ?></td></tr>
        <tr><td class="label">NIK</td><td class="colon">:</td><td><?= esc($rd['nik'] ?? $ld['nik'] ?? '-') ?></td></tr>
        <tr><td class="label">Tempat/Tanggal Lahir</td><td class="colon">:</td><td><?= esc($ld['ttl'] ?? $rd['ttl'] ?? '-') ?></td></tr>
        <tr><td class="label">Alamat Domisili</td><td class="colon">:</td><td><?= esc($ld['alamat_domisili'] ?? '-') ?></td></tr>
    </table>

    <div class="isi">
        adalah benar-benar tercatat sebagai siswa aktif di
        <strong><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></strong>
        yang saat ini duduk di kelas <strong><?= esc($ld['kelas'] ?? $rd['kelas'] ?? '-') ?></strong> pada Tahun Pelajaran <strong><?= esc($active_year) ?></strong> dan ingin melakukan <strong><?= esc($ld['keperluan_detail'] ?? '-') ?></strong><?= !empty($ld['lampiran']) ? ' (' . esc($ld['lampiran']) . ')' : '' ?>.
    </div>

    <div class="penutup">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</div>

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
