<?php
$ld = $letter['letter_data'] ?? [];
$rd = $letter['recipient_detail'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <!-- JUDUL -->
    <div class="surat-judul">
        <div class="judul">SURAT KETERANGAN</div>
        <div class="nomor">Nomor : <?= esc($letter['letter_number']) ?></div>
    </div>

    <div class="pembuka">Yang bertanda tangan di bawah ini :</div>

    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><?= esc($letter['principal_name_snapshot']) ?></td></tr>
        <tr><td class="label">NIP</td><td class="colon">:</td><td><?= esc($letter['principal_nip_snapshot']) ?></td></tr>
        <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
        <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></td></tr>
    </table>

    <div class="pembuka">Dengan ini menerangkan bahwa :</div>

    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($letter['recipient_name']) ?></strong></td></tr>
        <tr><td class="label">NISN</td><td class="colon">:</td><td><?= esc($rd['nisn'] ?? $ld['nisn'] ?? '-') ?></td></tr>
        <tr><td class="label">Tempat/Tanggal Lahir</td><td class="colon">:</td><td><?= esc($ld['ttl'] ?? $rd['ttl'] ?? '-') ?></td></tr>
        <tr><td class="label">Sekolah Asal</td><td class="colon">:</td><td><?= esc($ld['sekolah_asal'] ?? '-') ?><?= !empty($ld['alamat_sekolah_asal']) ? ' – ' . esc($ld['alamat_sekolah_asal']) : '' ?></td></tr>
    </table>

    <div class="isi">
        Nama tersebut di atas telah kami nyatakan <strong>DITERIMA</strong> di Kelas
        <strong><?= esc($ld['kelas_diterima'] ?? '-') ?></strong> Semester <strong><?= esc($ld['semester'] ?? '-') ?></strong>
        Tahun Pelajaran <strong><?= esc($active_year) ?></strong>
        di <?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?>.
    </div>

    <div class="penutup">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</div>

    <!-- QR + TTD -->
    <div class="qr-wrap">
        <img src="<?= $qr_data_url ?>" alt="QR Verifikasi">
        <p>Scan untuk verifikasi</p>
    </div>
    <div class="ttd-wrapper">
        Jakarta, <?= $tgl ?><br>
        Kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?>
        <span class="ttd-line"></span>
        <strong><?= esc($letter['principal_name_snapshot']) ?></strong><br>
        NIP. <?= esc($letter['principal_nip_snapshot']) ?>
    </div>
    <div class="ttd-clearfix"></div>

</div>
</body>
</html>
