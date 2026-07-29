<!-- views/admin/laporan/print_tagihan.php -->
<!DOCTYPE html>
<html>
<head>
  <title><?= $title ?></title>
  <style>
    body { font-family: Arial; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 5px; }
  </style>
</head>
<body onload="window.print()">
  <h4><?= esc($title) ?></h4>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>Kelas</th>
        <th>Tahun Ajaran</th>
        <th>Jenis</th>
        <th>Jumlah Tagihan</th>
        <th>Sudah Dibayar</th>
        <th>Belum Dibayar</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1; foreach ($tagihan as $row): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= esc($row['nis']) ?></td>
        <td><?= esc($row['siswa_nama']) ?></td>
        <td><?= esc($row['kelas_nama']) ?></td>
        <td><?= esc($row['tahun_ajaran_nama']) ?></td>
        <td><?= ucfirst(esc($row['tipe'])) ?></td>
        <td><?= number_format($row['total_jumlah'] ?? $row['jumlah']) ?></td>
        <td><?= number_format($row['total_terbayar'] ?? $row['terbayar']) ?></td>
        <td><?= number_format($row['sisa']) ?></td>
        <td><?= ($row['sisa'] <= 0) ? 'Lunas' : 'Belum' ?></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</body>
</html>
