<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title><?= esc(strtoupper("BUKTI BAYAR $nama_jenis_bayar $siswa[nama]")) ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      padding: 40px;
    }

    /* KOP SEKOLAH */
    .kop {
      width: 100%;
      margin-bottom: 0.5rem;
    }

    .kop table {
      width: 100%;
      border: none !important;
    }

    .kop td {
      border: none !important;
      padding: 0 !important;
      vertical-align: middle !important;
      text-align: center;
    }

    .kop .logo-cell {
      width: 80px;
      text-align: left;
    }

    .kop img {
      height: 70px;
      width: auto;
    }

    .kop h2 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: bold;
      line-height: 1.2;
    }

    .kop p {
      margin: 2px 0 0 0;
      font-size: 0.85rem;
      line-height: 1.2;
      color: #333;
    }

    /* Tabel umum */
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 16px;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 4px 6px;
      line-height: 1.2;
      font-size: 11px;
    }

    .info-table {
      margin-top: 20px;
    }

    .info-table td {
      border: none;
      padding: 2px 0;
    }

    .qr {
      margin-top: 16px;
      text-align: right;
    }

    .qr img {
      width: 140px;
      height: 140px;
    }

    .footer {
      font-size: 10px;
      margin-top: 20px;
    }

    .garis {
      border-top: 1.5px solid #000;
      margin-top: 0.3rem;
      margin-bottom: 0.8rem;
    }
  </style>
</head>

<body onload="window.print()">

  <!-- KOP SEKOLAH -->
  <div class="kop">
    <table>
      <tr>
        <?php if (!empty($setting['logo'])): ?>
          <td class="logo-cell">
            <img src="<?= base_url($setting['logo']) ?>" alt="Logo Sekolah">
          </td>
        <?php endif ?>
        <td>
          <h2><?= strtoupper($setting['nama_sekolah'] ?? 'NAMA SEKOLAH') ?></h2>
          <p><?= $setting['alamat'] ?? 'Alamat belum diatur' ?></p>
          <p>WA: <?= $setting['no_hp'] ?? '-' ?></p>
        </td>
        <?php if (!empty($setting['logo'])): ?>
          <td class="logo-cell"></td>
        <?php endif ?>
      </tr>
    </table>
  </div>

  <div class="garis"></div>

  <!-- JUDUL -->
  <h3 style="text-align:center; margin: 0; font-size: 0.85rem;">BUKTI PEMBAYARAN <br><?= esc($nama_jenis_bayar) ?></h3>

  <!-- IDENTITAS SISWA -->
  <table class="info-table">
    <tr>
      <td>Nama Siswa</td>
      <td>: <?= esc($siswa['nama']) ?></td>
    </tr>
    <tr>
      <td>NIS</td>
      <td>: <?= esc($siswa['nis']) ?></td>
    </tr>
    <tr>
      <td>Kelas</td>
      <td>: <?= esc($siswa['kelas']) ?></td>
    </tr>
  </table>

  <!-- TABEL PEMBAYARAN -->
  <table>
    <thead>
      <tr>
        <th>Tanggal</th>
        <th>Bulan</th>
        <th>Jumlah</th>
        <th>Cara Bayar</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($transaksi as $row): ?>
        <tr>
          <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
          <td><?= bulanIndo($row['bulan']) ?></td>
          <td>Rp. <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
          <td><?= ucfirst($row['cara_bayar']) ?></td>
          <td><?= $row['status'] ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <p class="footer">Rincian pembayaran bisa dilihat di: https://bayaran.smpistora.sch.id</p>

  <!-- QR CODE -->
  <div class="qr">
    <img src="data:image/svg+xml;base64,<?= $qrCode ?>" alt="QR Code">
  </div>

</body>

</html>