<?php
// Template D — Keterangan Siswa Lomba (Multi-Recipient)
$ld        = $letter['letter_data'] ?? [];
$recipients = $letter['recipients'] ?? [];
$bulan     = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl       = date('d', strtotime($letter['issued_at'])) . ' ' . $bulan[(int)date('m', strtotime($letter['issued_at']))] . ' ' . date('Y', strtotime($letter['issued_at']));
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

    <div class="pembuka">
        Menerangkan bahwa nama-nama siswa yang tercantum dalam daftar di bawah ini benar-benar
        siswa <strong><?= esc($school['name'] ?? 'SD Negeri Mangga Besar 11 Pagi') ?></strong>
        Tahun Pelajaran <strong><?= esc($active_year) ?></strong>,
        yang akan mengikuti <strong><?= esc($ld['event_name'] ?? 'kegiatan lomba') ?></strong>
        <?php if (!empty($ld['event_organizer'])): ?>yang diselenggarakan oleh <strong><?= esc($ld['event_organizer']) ?></strong><?php endif; ?>.
    </div>

    <!-- Daftar Siswa -->
    <table class="tbl-bordered" style="margin: 10px 0;">
        <thead>
            <tr>
                <th style="width:30px;">No.</th>
                <th>Nama Siswa</th>
                <th style="width:50px;">Kelas</th>
                <th style="width:80px;">Tanggal Lahir</th>
                <th>Cabang Lomba</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recipients)): ?>
                <?php foreach ($recipients as $i => $r): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= esc($r['name'] ?? '') ?></td>
                        <td style="text-align:center;"><?= esc($r['kelas'] ?? '') ?></td>
                        <td style="text-align:center;"><?= !empty($r['birth_date']) ? date('d/m/Y', strtotime($r['birth_date'])) : '-' ?></td>
                        <td><?= esc($r['cabang'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center; color:#666;">-</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

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
