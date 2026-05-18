<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }

    h3 { text-align: center; font-size: 13px; margin-bottom: 4px; }
    .sub-title { text-align: center; font-size: 10px; color: #444; margin-bottom: 8px; }

    table { border-collapse: collapse; width: 100%; font-size: 11px; }
    th, td { border: 1px solid #000; padding: 4px 5px; text-align: center; }
    th { background-color: #f0f0f0; font-weight: bold; }
    td.text-left { text-align: left; }
    tfoot td { font-weight: bold; background-color: #e0e0e0; }

    .badge-umum  { background: #2563eb; color: #fff; padding: 1px 5px; border-radius: 6px; font-size: 10px; }
    .badge-dinas { background: #d97706; color: #fff; padding: 1px 5px; border-radius: 6px; font-size: 10px; }

    .footer-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .footer-table td { border: none; width: 50%; text-align: center; vertical-align: top; padding: 0; }
    .signature-space { height: 70px; }
  </style>
</head>
<body>

  <h3>Laporan Buku Tamu Digital — <?= esc($school['name'] ?? 'Sekolah') ?></h3>
  <div class="sub-title">Periode: <?= esc($periodeLabel) ?> &nbsp;|&nbsp; Dicetak: <?= esc($printDate) ?></div>

  <table>
    <thead>
      <tr>
        <th style="width:4%">No</th>
        <th style="width:10%">Tanggal</th>
        <th style="width:6%">Jenis</th>
        <th style="width:14%">Nama</th>
        <?php if ($filter_type !== 'umum'): ?>
          <th style="width:10%">NIP</th>
          <th style="width:14%">Instansi / Ket.</th>
          <th style="width:20%">Tujuan Kunjungan</th>
          <th style="width:12%">Bertemu Dengan</th>
          <th style="width:10%">No HP</th>
        <?php else: ?>
          <th style="width:16%">Instansi / Ket.</th>
          <th style="width:22%">Tujuan Kunjungan</th>
          <th style="width:14%">Bertemu Dengan</th>
          <th style="width:14%">No HP</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($data_tamu)): ?>
        <tr>
          <td colspan="<?= $filter_type !== 'umum' ? '9' : '8' ?>" style="text-align:center; padding:10px; color:#666;">
            Tidak ada data tamu untuk periode ini.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($data_tamu as $i => $tamu): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= date('d/m/Y H:i', strtotime($tamu['created_at'])) ?></td>
            <td>
              <?php if ($tamu['guest_type'] === 'dinas'): ?>
                <span class="badge-dinas">Dinas</span>
              <?php else: ?>
                <span class="badge-umum">Umum</span>
              <?php endif; ?>
            </td>
            <td class="text-left"><?= esc($tamu['nama']) ?></td>
            <?php if ($filter_type !== 'umum'): ?>
              <td><?= esc($tamu['nip'] ?: '-') ?></td>
            <?php endif; ?>
            <td class="text-left">
              <?php if ($tamu['is_ortu_siswa']): ?>
                <em>Orang Tua Siswa</em>
              <?php else: ?>
                <?= esc($tamu['instansi'] ?? '-') ?>
              <?php endif; ?>
            </td>
            <td class="text-left"><?= esc($tamu['tujuan']) ?></td>
            <td><?= esc($tamu['bertemu_dengan'] ?? '-') ?></td>
            <td><?= esc($tamu['no_hp'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="<?= $filter_type !== 'umum' ? '9' : '8' ?>" style="text-align:right; padding: 4px 6px;">
          Total: <?= count($data_tamu) ?> tamu
        </td>
      </tr>
    </tfoot>
  </table>

  <!-- TANDA TANGAN -->
  <table class="footer-table">
    <tr>
      <td></td>
      <td>
        <?= esc($school['city_regency'] ?? 'Kota') ?>, <?= esc($printDate) ?><br>
        Kepala <?= esc($school['name'] ?? 'Sekolah') ?>
        <div class="signature-space"></div>
        <strong><?= esc($school['headmaster'] ?? '______________________') ?></strong><br>
        NIP. <?= esc($school['principal_nip'] ?? '-') ?>
      </td>
    </tr>
  </table>

</body>
</html>
