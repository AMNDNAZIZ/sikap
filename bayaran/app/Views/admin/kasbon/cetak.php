<!DOCTYPE html>
<html>
<head>
  <title>Bukti Pembayaran Kasbon</title>
  <style>
    body { font-family: Arial; font-size: 14px; }
    .container { width: 600px; margin: auto; padding: 20px; border: 1px solid #aaa; }
    h3 { text-align: center; }
    table { width: 100%; margin-top: 20px; }
  </style>
</head>
<body onload="window.print()">
  <div class="container">
    <h3>Bukti Pembayaran Kasbon</h3>
    <table>
      <tr><td>Nama Guru</td><td>: <?= esc($guru['nama']) ?></td></tr>
      <tr><td>Tanggal Bayar</td><td>: <?= date('d-m-Y', strtotime($cicilan['tanggal'])) ?></td></tr>
      <tr><td>Jumlah</td><td>: Rp <?= number_format($cicilan['jumlah'], 0, ',', '.') ?></td></tr>
      <tr><td>Keterangan</td><td>: <?= esc($cicilan['keterangan'] ?? '-') ?></td></tr>
    </table>

    <p style="margin-top: 50px">Tanda tangan,<br><br><br>____________________</p>
  </div>
</body>
</html>
