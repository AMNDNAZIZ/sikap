<?php
$ld  = $letter['letter_data'] ?? [];
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
    </table>

    <div class="pembuka">Menerangkan dengan sesungguhnya bahwa :</div>

    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($letter['recipient_name']) ?></strong></td></tr>
        <tr><td class="label">NIK</td><td class="colon">:</td><td><?= esc($ld['nik'] ?? '-') ?></td></tr>
        <tr><td class="label">Jabatan</td><td class="colon">:</td><td><?= esc($ld['jabatan'] ?? 'Guru') ?></td></tr>
        <tr><td class="label">Mengajar Kelas</td><td class="colon">:</td><td><?= esc($ld['kelas_mengajar'] ?? '-') ?></td></tr>
        <tr><td class="label">Satuan Pendidikan</td><td class="colon">:</td><td><?= esc($ld['satuan_pendidikan'] ?? $school['name'] ?? '-') ?></td></tr>
        <tr><td class="label">Alamat Satuan</td><td class="colon">:</td><td><?= esc($ld['alamat_satuan'] ?? '-') ?></td></tr>
        <tr><td class="label">Alamat Tinggal</td><td class="colon">:</td><td><?= esc($ld['alamat_tinggal'] ?? '-') ?></td></tr>
        <tr><td class="label">No. Telp / HP</td><td class="colon">:</td><td><?= esc($ld['no_hp'] ?? '-') ?></td></tr>
    </table>

    <div class="isi">
        Adalah benar-benar <strong>Guru</strong> yang bertugas di
        <?= esc($ld['satuan_pendidikan'] ?? $school['name'] ?? '-') ?>
        Tahun Pelajaran <strong><?= esc($active_year) ?></strong>.
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
