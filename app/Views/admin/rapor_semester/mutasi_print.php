<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lembar Mutasi Siswa – <?= esc($class['name'] ?? '') ?></title>
  <style>
    @page { 
      size: portrait; 
      margin: 1.5cm 1.8cm; 
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 10.5pt;
      color: #000;
      line-height: 1.35;
      margin: 0;
      background-color: #fff;
    }

    .doc-page {
      page-break-after: always;
      page-break-inside: avoid;
      position: relative;
      min-height: 25cm;
    }
    .doc-page:last-child {
      page-break-after: auto;
    }

    /* ── Watermark ── */
    .watermark-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0.08;
      width: 380px;
      pointer-events: none;
      z-index: 0;
      text-align: center;
    }
    .watermark-container img {
      width: 100%;
      height: auto;
      display: block;
    }

    .content-relative {
      position: relative;
      z-index: 1;
    }

    /* ── No Print Toolbar ── */
    .no-print {
      background: #f8f9fa;
      border-bottom: 1px solid #ddd;
      padding: 10px 15px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-print { background: #0d6efd; color: #fff; padding: 6px 16px; border: 1px solid #0d6efd; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-close { background: #6c757d; color: #fff; padding: 6px 16px; border: 1px solid #6c757d; border-radius: 4px; font-weight: bold; cursor: pointer; }

    .page-title {
      text-align: center;
      font-size: 12.5pt;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 15px;
      letter-spacing: 0.5px;
    }

    .identitas-box {
      margin-bottom: 15px;
      font-size: 10.5pt;
    }

    .mutasi-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      font-size: 10pt;
    }
    .mutasi-table th, .mutasi-table td {
      border: 1px solid #000;
      padding: 6px 8px;
      vertical-align: top;
    }
    .mutasi-table th {
      background-color: #f2f2f2;
      text-align: center;
      font-weight: bold;
    }

    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
    }
  </style>
</head>
<body onload="<?= $isSingle ? 'window.print()' : '' ?>">

  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Lembar Mutasi</button>
    <button onclick="window.close()" class="btn-close">❌ Tutup</button>
    <span style="color:#555;font-size:9.5pt;margin-left:10px;">
      Lembar Catatan Mutasi Peserta Didik – Kelas <?= esc($class['name'] ?? '-') ?>
    </span>
  </div>

<?php foreach ($students as $stu): ?>
  <div class="doc-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="page-title">KETERANGAN PINDAH SEKOLAH PESERTA DIDIK</div>

      <div class="identitas-box">
        <table style="width:100%;">
          <tr>
            <td style="width:180px;">Nama Peserta Didik</td>
            <td style="width:12px;">:</td>
            <td><strong><?= esc(strtoupper($stu['name'])) ?></strong></td>
          </tr>
          <tr>
            <td>Nomor Induk / NISN</td>
            <td>:</td>
            <td><?= esc(($stu['nis'] ?: '-') . ' / ' . ($stu['nisn'] ?: '-')) ?></td>
          </tr>
          <tr>
            <td>Nama Satuan Pendidikan</td>
            <td>:</td>
            <td><?= esc($school['name'] ?? '-') ?></td>
          </tr>
        </table>
      </div>

      <!-- Bagian Keluar -->
      <div style="font-weight:bold;margin-bottom:6px;text-transform:uppercase;">I. KELUAR</div>
      <table class="mutasi-table">
        <thead>
          <tr>
            <th style="width:15%;">Tanggal</th>
            <th style="width:15%;">Kelas yang Ditinggalkan</th>
            <th style="width:35%;">Sebab-sebab Keluar dan Sekolah yang Dituju</th>
            <th style="width:35%;">Tanda Tangan Kepala Sekolah, Stempel, & Tanda Tangan Orang Tua/Wali</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($k = 1; $k <= 2; $k++): ?>
            <tr>
              <td style="height:110px;"></td>
              <td></td>
              <td></td>
              <td style="font-size:9.5pt;">
                Kepala Sekolah,<br><br><br><br>
                ( .................................................. )<br>
                NIP. .............................................<br><br>
                Orang Tua / Wali,<br><br><br>
                ( .................................................. )
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

      <!-- Bagian Masuk -->
      <div style="font-weight:bold;margin-bottom:6px;text-transform:uppercase;">II. MASUK</div>
      <table class="mutasi-table">
        <thead>
          <tr>
            <th style="width:15%;">Tanggal</th>
            <th style="width:15%;">Kelas yang Dimasuki</th>
            <th style="width:35%;">Nama Sekolah Asal & Nomor Surat Rekomendasi Pindah</th>
            <th style="width:35%;">Tanda Tangan Kepala Sekolah & Stempel</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($m = 1; $m <= 2; $m++): ?>
            <tr>
              <td style="height:100px;"></td>
              <td></td>
              <td></td>
              <td style="font-size:9.5pt;">
                Kepala Sekolah,<br><br><br><br>
                ( .................................................. )<br>
                NIP. .............................................
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

    </div>
  </div>
<?php endforeach; ?>

</body>
</html>
