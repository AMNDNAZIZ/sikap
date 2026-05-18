<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }

    /* JUDUL LAPORAN */
    .judul { text-align: center; margin: 10px 0 6px; }
    .judul h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .judul .periode { font-size: 10px; color: #555; }

    /* TABEL */
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #1a6fc4; color: #fff; padding: 5px 6px; text-align: center; border: 1px solid #1a6fc4; }
    td { padding: 4px 6px; border: 1px solid #ccc; vertical-align: top; }
    tr:nth-child(even) td { background: #f5f8ff; }
    .badge-umum  { background: #1a6fc4; color: #fff; padding: 1px 6px; border-radius: 8px; font-size: 10px; }
    .badge-dinas { background: #d97706; color: #fff; padding: 1px 6px; border-radius: 8px; font-size: 10px; }

    /* FOOTER */
    .footer { margin-top: 14px; font-size: 10px; color: #666; text-align: right; border-top: 1px solid #ccc; padding-top: 6px; }
    .ttd { margin-top: 30px; text-align: right; }
    .ttd .ttd-box { display: inline-block; text-align: center; min-width: 160px; }
    .ttd .ttd-box .nama { margin-top: 50px; font-weight: bold; border-top: 1px solid #333; padding-top: 4px; }
  </style>
</head>
<body>

  <!-- JUDUL -->
  <div class="judul">
    <h2>Laporan Buku Tamu Digital</h2>
    <div class="periode">Periode: <?= esc($periodeLabel) ?> &nbsp;|&nbsp; Dicetak: <?= esc($printDate) ?></div>
  </div>

  <!-- TABEL -->
  <table>
    <thead>
      <tr>
        <th style="width:4%">No</th>
        <th style="width:11%">Tanggal</th>
        <th style="width:8%">Jenis</th>
        <th style="width:16%">Nama</th>
        <th style="width:16%">Instansi / Ket.</th>
        <th style="width:22%">Tujuan Kunjungan</th>
        <th style="width:13%">Bertemu Dengan</th>
        <th style="width:10%">No HP</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($data_tamu)): ?>
        <tr>
          <td colspan="8" style="text-align:center; padding:12px; color:#999;">
            Tidak ada data tamu untuk periode ini.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($data_tamu as $i => $tamu): ?>
          <tr>
            <td style="text-align:center;"><?= $i + 1 ?></td>
            <td style="text-align:center;"><?= date('d/m/Y H:i', strtotime($tamu['created_at'])) ?></td>
            <td style="text-align:center;">
              <?php if ($tamu['guest_type'] === 'dinas'): ?>
                <span class="badge-dinas">Dinas</span>
              <?php else: ?>
                <span class="badge-umum">Umum</span>
              <?php endif; ?>
            </td>
            <td><?= esc($tamu['nama']) ?></td>
            <td>
              <?php if ($tamu['guest_type'] === 'dinas'): ?>
                <?= esc($tamu['instansi'] ?? '-') ?>
              <?php elseif ($tamu['is_ortu_siswa']): ?>
                <em>Orang Tua Siswa</em>
              <?php else: ?>
                <?= esc($tamu['instansi'] ?? '-') ?>
              <?php endif; ?>
            </td>
            <td><?= esc($tamu['tujuan']) ?></td>
            <td><?= esc($tamu['bertemu_dengan'] ?? '-') ?></td>
            <td><?= esc($tamu['no_hp'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- TANDA TANGAN -->
  <div class="ttd">
    <div class="ttd-box">
      <div><?= esc($school['city'] ?? 'Kota') ?>, <?= esc($printDate) ?></div>
      <div>Kepala <?= esc($school['name'] ?? 'Sekolah') ?></div>
      <div class="nama">_____________________________</div>
      <div>NIP. ___________________________</div>
    </div>
  </div>

  <div class="footer">
    Dicetak oleh sistem SIKAP &mdash; <?= date('d/m/Y H:i') ?>
  </div>

</body>
</html>
