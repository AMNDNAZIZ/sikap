<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Bukti Pembayaran <?= esc($siswa['nama']) ?></title>
  <link href="<?= base_url('public/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <style>
    body {
      font-size: 13px;
      font-family: Arial, sans-serif;
      margin: 2rem;
    }

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

    .kop h5 {
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

    .judul {
      text-align: center;
      font-weight: bold;
      font-size: 0.85rem;
      margin: 0.5rem 0;
      text-transform: uppercase;
    }

    table th,
    table td {
      padding: 0.2rem 0.5rem;
      font-size: 12px;
      line-height: 1.2;
    }

    .garis {
      border-top: 1.5px solid #000;
      margin-top: 0.3rem;
      margin-bottom: 0.8rem;
    }

    .qr {
      text-align: center;
      margin-top: 2rem;
    }

    .table-transaksi th {
      background-color: #f8f9fa;
    }
  </style>

</head>

<body onload="window.print()">

  <div class="kop">
    <table>
      <tr>
        <?php if (!empty($setting['logo'])): ?>
          <td class="logo-cell">
            <img src="<?= base_url($setting['logo']) ?>" alt="Logo">
          </td>
        <?php endif; ?>
        <td>
          <h5><?= strtoupper($setting['nama_sekolah'] ?? 'NAMA SEKOLAH') ?></h5>
          <p><?= $setting['alamat'] ?? '-' ?></p>
          <p>WA: <?= $setting['no_hp'] ?? '-' ?></p>
        </td>
        <?php if (!empty($setting['logo'])): ?>
          <td class="logo-cell"></td>
        <?php endif; ?>
      </tr>
    </table>
  </div>

  <div class="garis"></div>

  <div class="judul">
    BUKTI PEMBAYARAN<br>
    Tanggal: <?= date('d-m-Y', strtotime($tanggal)) ?>
  </div>

  <div class="garis"></div>

  <table class="table table-borderless w-auto">
    <tr>
      <th>Nama</th>
      <td>: <?= esc($siswa['nama']) ?></td>
    </tr>
    <tr>
      <th>NIS</th>
      <td>: <?= esc($siswa['nis']) ?></td>
    </tr>
    <tr>
      <th>Kelas</th>
      <td>: <?= esc($siswa['kelas']) ?></td>
    </tr>
  </table>

  <table class="table table-sm table-bordered mt-3 table-transaksi">
    <thead>
      <tr>
        <th>Jenis Pembayaran</th>
        <th>Tanggal</th>
        <th>Jumlah</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bulanan as $b): ?>
        <tr>
          <td><?= esc($b['nama_jenis']) ?> - Bulan <?= bulanIndo($b['bulan']) ?></td>
          <td><?= date('d-m-Y', strtotime($b['tanggal'])) ?></td>
          <td>Rp <?= number_format($b['jumlah'], 0, ',', '.') ?></td>
          <td>Lunas</td>
        </tr>
      <?php endforeach; ?>

      <?php foreach ($bebas as $b): ?>
        <tr>
          <td><?= esc($b['nama_jenis']) ?></td>
          <td><?= date('d-m-Y', strtotime($b['tanggal'])) ?></td>
          <td>Rp <?= number_format($b['jumlah'], 0, ',', '.') ?></td>
          <td>Angsuran ke-<?= esc($b['angsuran_ke']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="2">Total</th>
        <th colspan="2">Rp <?= number_format($total, 0, ',', '.') ?></th>
      </tr>
    </tfoot>
  </table>

  <p class="mt-3"><small>Rincian transaksi dapat dilihat di https://bayaran.smpistora.sch.id</small></p>

  <?php if (!empty($qr)): ?>
    <div class="qr">
      <img src="<?= $qr ?>" alt="QR Code">
    </div>
  <?php endif; ?>

</body>

</html>