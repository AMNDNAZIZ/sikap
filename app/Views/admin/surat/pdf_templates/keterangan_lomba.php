<?php
// Template D — Keterangan Siswa Lomba (Multi-Recipient)
$ld        = $letter['letter_data'] ?? [];
$recipients = $letter['recipients'] ?? [];
$bulan     = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl       = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));
?>
<?= view('admin/surat/pdf_templates/_header') ?>

    <div class="surat-judul" style="margin-bottom: 24px;">
        <div class="judul" style="font-size: 18px; font-weight: bold; text-decoration: underline; letter-spacing: 1px;">SURAT KETERANGAN</div>
        <div class="nomor" style="font-size: 16px; margin-top: 4px;">Nomor : <?= esc($letter['letter_number']) ?></div>
    </div>

    <div class="pembuka" style="line-height: 1.6; margin-bottom: 12px; text-align: justify; text-indent: 0;">
        Yang bertanda tangan di bawah ini kepala <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?> menerangkan
        bahwa Daftar Siswa (terlampir).
    </div>

    <!-- Daftar Siswa -->
    <table class="tbl-bordered" style="margin: 12px 0 16px 0; width: 100%;">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center; text-transform: uppercase;">NO</th>
                <th style="text-align: left; text-transform: uppercase;">NAMA</th>
                <th style="width: 110px; text-align: center; text-transform: uppercase;">TANGGAL LAHIR</th>
                <th style="width: 60px; text-align: center; text-transform: uppercase;">KELAS</th>
                <th style="text-align: left; text-transform: uppercase;">CABANG</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recipients)): ?>
                <?php foreach ($recipients as $i => $r): ?>
                    <tr>
                        <td style="text-align: center;"><?= $i + 1 ?>.</td>
                        <td><?= esc($r['name'] ?? '') ?></td>
                        <td style="text-align: center;"><?= !empty($r['birth_date']) ? date('d-m-Y', strtotime($r['birth_date'])) : '-' ?></td>
                        <td style="text-align: center;"><?= esc($r['kelas'] ?? '') ?></td>
                        <td><?= esc($r['cabang'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; color: #666;">-</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="isi" style="line-height: 1.6; margin-bottom: 12px; text-align: justify; text-indent: 0;">
        adalah benar-benar tercatat sebagai siswa <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?> pada tahun
        pelajaran <?= esc($active_year) ?>.
    </div>

    <div class="penutup" style="line-height: 1.6; margin-bottom: 24px; text-align: justify; text-indent: 0;">
        Demikian keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.
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
