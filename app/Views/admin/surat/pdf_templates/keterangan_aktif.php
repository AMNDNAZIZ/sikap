<?php
$ld  = $letter['letter_data'] ?? [];
$rd  = $letter['recipient_detail'] ?? [];
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl   = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));

if (!function_exists('spellClass')) {
    function spellClass($kelas) {
        $k = strtolower(trim($kelas));
        if ($k == '1' || $k == 'i' || str_contains($k, 'satu')) return 'Satu';
        if ($k == '2' || $k == 'ii' || str_contains($k, 'dua')) return 'Dua';
        if ($k == '3' || $k == 'iii' || str_contains($k, 'tiga')) return 'Tiga';
        if ($k == '4' || $k == 'iv' || str_contains($k, 'empat')) return 'Empat';
        if ($k == '5' || $k == 'v' || str_contains($k, 'lima')) return 'Lima';
        if ($k == '6' || $k == 'vi' || str_contains($k, 'enam')) return 'Enam';
        return $kelas;
    }
}
if (!function_exists('romanClass')) {
    function romanClass($kelas) {
        $k = strtolower(trim($kelas));
        if ($k == '1' || $k == 'i' || str_contains($k, 'satu')) return 'I';
        if ($k == '2' || $k == 'ii' || str_contains($k, 'dua')) return 'II';
        if ($k == '3' || $k == 'iii' || str_contains($k, 'tiga')) return 'III';
        if ($k == '4' || $k == 'iv' || str_contains($k, 'empat')) return 'IV';
        if ($k == '5' || $k == 'v' || str_contains($k, 'lima')) return 'V';
        if ($k == '6' || $k == 'vi' || str_contains($k, 'enam')) return 'VI';
        return $kelas;
    }
}

$kelasRaw = $ld['kelas'] ?? $rd['kelas'] ?? '-';
$kelasRoman = romanClass($kelasRaw);
$kelasSpelled = spellClass($kelasRaw);
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
        <tr><td class="label">Tempat, tanggal lahir</td><td class="colon">:</td><td><?= esc($ld['ttl'] ?? $rd['ttl'] ?? '-') ?></td></tr>
        <tr><td class="label">Asal Sekolah</td><td class="colon">:</td><td><?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?></td></tr>
        <tr><td class="label">Alamat Sekolah</td><td class="colon">:</td><td><?= esc($school['address'] ?? 'Jalan Gedong No. 16 Kecamatan Tamansari') ?></td></tr>
    </table>

    <div class="isi">
        adalah benar-benar tercatat sebagai siswa aktif di
        <strong><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></strong>
        yang saat ini duduk di kelas <strong><?= esc($kelasRoman) ?> (<?= esc($kelasSpelled) ?>)</strong> pada Tahun Pelajaran <strong><?= esc($active_year) ?></strong> dan masih aktif terdaftar sebagai siswa hingga saat ini.
        <?php if (!empty($ld['keperluan_tambahan'])): ?>
            <br>Surat keterangan ini diberikan untuk keperluan <strong><?= esc($ld['keperluan_tambahan']) ?></strong>.
        <?php endif; ?>
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
