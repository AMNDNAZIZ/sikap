<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
    h2 { text-align: center; margin-bottom: 5px; font-size: 14px; }
    h3 { text-align: center; margin-top: 0; font-size: 12px; font-weight: normal; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #333; padding: 4px 6px; font-size: 10px; }
    th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
    td { vertical-align: top; }
    .center { text-align: center; }
    .footer { margin-top: 20px; text-align: right; font-size: 10px; }
  </style>
</head>
<body>
  <h2>DAFTAR ALUMNI</h2>
  <h3><?= !empty($yearFilter) ? 'Tahun Ajaran ' . esc($yearFilter) : 'Seluruh Tahun Ajaran' ?></h3>

  <table>
    <thead>
      <tr>
        <th width="4%">No</th>
        <th width="12%">NISN</th>
        <th width="8%">NIS</th>
        <th>Nama Lengkap</th>
        <th width="5%">JK</th>
        <th width="12%">Kelas Terakhir</th>
        <th width="12%">Tahun Ajaran</th>
        <th width="10%">Tanggal Lulus</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1; foreach ($alumni as $a): ?>
        <tr>
          <td class="center"><?= $no++ ?></td>
          <td><?= esc($a['nisn']) ?></td>
          <td><?= esc($a['nis']) ?></td>
          <td><?= esc($a['name']) ?></td>
          <td class="center"><?= $a['gender'] == 'L' ? 'L' : 'P' ?></td>
          <td class="center"><?= esc($a['class_name'] ?? '-') ?></td>
          <td class="center"><?= esc($a['academic_year'] ?? '-') ?></td>
          <td class="center"><?= $a['graduation_date'] ? date('d/m/Y', strtotime($a['graduation_date'])) : '-' ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <div class="footer">
    <p>Dicetak pada: <?= date('d/m/Y H:i') ?></p>
    <p>Total: <?= count($alumni) ?> alumni</p>
  </div>
</body>
</html>
