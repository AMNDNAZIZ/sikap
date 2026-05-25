<?php
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
    </table>

    <div class="pembuka">Menerangkan dengan sesungguhnya bahwa :</div>

    <table class="data-tabel" style="margin-bottom:8px;">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($letter['recipient_name']) ?></strong></td></tr>
        <tr><td class="label">NIK</td><td class="colon">:</td><td><?= esc($ld['nik'] ?? '-') ?></td></tr>
        <tr><td class="label">Jabatan</td><td class="colon">:</td><td><?= esc($rd['jabatan'] ?? $ld['jabatan'] ?? 'Guru') ?></td></tr>
        <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td><?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?></td></tr>
        <tr><td class="label">Pekerjaan</td><td class="colon">:</td><td>Guru</td></tr>
        <tr><td class="label">No. Telp/HP</td><td class="colon">:</td><td><?= esc($ld['no_hp'] ?? '-') ?></td></tr>
        <tr>
            <td class="label" style="vertical-align: top;">Satuan Pendidikan</td>
            <td class="colon" style="vertical-align: top;">:</td>
            <td style="padding: 0;">
                <table style="width: 100%; border: none; border-collapse: collapse; margin: 0; font-size: 11px;">
                    <tr style="border: none;"><td style="width: 120px; padding: 2px 0; border: none;">a. Nama Satuan</td><td style="width: 10px; padding: 2px 0; border: none;">:</td><td style="padding: 2px 0; border: none;"><?= esc($ld['satuan_pendidikan'] ?? $school['name'] ?? '-') ?></td></tr>
                    <tr style="border: none;"><td style="padding: 2px 0; border: none;">b. Alamat Satuan</td><td style="padding: 2px 0; border: none;">:</td><td style="padding: 2px 0; border: none;"><?= esc($ld['alamat_satuan'] ?? $school['address'] ?? '-') ?></td></tr>
                </table>
            </td>
        </tr>
        <tr><td class="label">Alamat Tinggal</td><td class="colon">:</td><td><?= esc($ld['alamat_tinggal'] ?? '-') ?></td></tr>
    </table>

    <div class="isi">
        Adalah benar-benar <strong>Guru</strong> yang bertugas di
        <strong><?= esc($ld['satuan_pendidikan'] ?? $school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?></strong>
        yang mengajar di kelas <strong><?= esc($ld['kelas_mengajar'] ?? '-') ?></strong> pada Tahun Pelajaran <strong><?= esc($active_year) ?></strong>.
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
