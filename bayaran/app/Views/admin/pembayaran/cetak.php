<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>BUKTI BAYAR <?= esc($data['nama_jenis_bayar']) ?> <?= esc($data['nama_siswa']) ?></title>
  <link href="<?= base_url('public/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <style>
    body {
      font-size: 13px;
      font-family: Arial, sans-serif;
      margin: 2rem;
      line-height: 1;
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
      font-size: 1rem;
      font-weight: bold;
      line-height: 1.2;
    }

    .kop p {
      margin: 2px 0 0 0;
      font-size: 0.75rem;
      line-height: 1.2;
      color: #333;
    }

    .judul {
      text-align: center;
      font-weight: bold;
      font-size: 0.75rem;
      margin: 0.5rem 0;
      text-transform: uppercase;
    }

    @media print {
      .nama-siswa {
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    table th,
    table td {
      padding: 0.5px 1px;
      line-height: 0.5;
      font-size: 12px;
    }

    table th {
      width: 100px;
      vertical-align: top;
    }

    .qr {
      margin-top: 1.5rem;
    }

    .garis {
      border-top: 1.5px solid #000;
      margin-top: 0.3rem;
      margin-bottom: 0.8rem;
    }
  </style>
</head>

<body onload="window.print()">

 <!-- 🏫 KOP SEKOLAH -->
<div class="kop" style="text-align: center;">
  
  <?php if (!empty($setting['logo'])): ?>
    <div class="logo">
      <img src="<?= base_url($setting['logo']) ?>" alt="Logo" style="max-height: 70px;">
    </div>
  <?php endif ?>

  <div class="nama-sekolah">
    <h5><?= strtoupper($setting['nama_sekolah'] ?? 'NAMA SEKOLAH') ?></h5>
  </div>

  <div class="alamat">
    <p><?= $setting['alamat'] ?? 'Alamat belum diatur' ?></p>
  </div>

  <div class="kontak">
    <p>WA: <?= $setting['no_hp'] ?? '-' ?></p>
  </div>

</div>

  <div class="garis"></div>

  <!-- 🧾 Judul -->
  <div class="judul">
    BUKTI PEMBAYARAN <br>
    <?= esc($data['nama_jenis_bayar']) ?>
  </div>
  <div class="garis"></div>

  <!-- 📄 Tabel Data -->
  <table class="table table-borderless w-auto">
    <tr>
      <th colspan="2" class="nama-siswa">
        <?= esc($data['nama_siswa']) ?>
      </th>
    </tr>
    <tr>
      <th>NIS</th>
      <td>: <?= esc($data['nis']) ?></td>
    </tr>
    <tr>
      <th>Kelas</th>
      <td>: <?= esc($data['nama_kelas'] ?? '-') ?></td>
    </tr>

    <?php if ($data['tipe'] === 'bulanan'): ?>
      <tr>
        <th>Bayar Bulan</th>
        <td>: <?= bulanIndo($data['bulan']) ?></td>
      </tr>
    <?php endif ?>

    <tr>
      <th>Tgl. Bayar</th>
      <td>: <?= date('d-m-Y', strtotime($data['tanggal'])) ?></td>
    </tr>
    <tr>
      <th>Cara Bayar</th>
      <td>: <?= ucfirst($data['cara_bayar']) ?></td>
    </tr>
    <tr>
      <th>Jumlah </th>
      <td>: Rp <?= number_format($data['jumlah_bayar'], 0, ',', '.') ?></td>
    </tr>

    <?php if ($data['tipe'] === 'bebas'): ?>
      <tr>
        <th>Keterangan</th>
        <td>: <?= esc($data['keterangan'] ?: '-') ?></td>
      </tr>
      <tr>
        <th>Sisa</th>
        <td>: Rp <?= number_format(max(0, $data['total_tagihan'] - $data['terbayar']), 0, ',', '.') ?></td>
      </tr>
    <?php endif ?>

    <tr>
      <th>Status</th>
      <td>: <?= $data['status_tagihan'] === 'lunas' ? 'Lunas' : 'Belum Lunas' ?></td>
    </tr>
  </table>
  <p class="mt-3"><small>Rincian pembayaran bisa dilihat di https://bayaran.smpistora.sch.id</small></p>
  <!-- 🔲 QR Code -->
  <?php if (!empty($qrCode)): ?>
    <div class="qr text-center">
      <img src="data:image/svg+xml;base64,<?= $qrCode ?>" alt="QR Code">
    </div>
  <?php endif ?>

  <!-- 🌐 Footer -->


</body>

</html>