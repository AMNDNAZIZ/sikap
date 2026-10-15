<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lembar Mutasi Siswa – <?= esc($class['name'] ?? '') ?></title>
  <style>
    @page { 
      size: A4 portrait; 
      margin: 1.5cm 2cm; 
    }
    * { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 10pt;
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
      padding-top: 5mm;
      box-sizing: border-box;
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

    /* ── Styling Halaman Mutasi Sesuai Format Resmi ── */
    .mutasi-header-title {
      text-align: center;
      font-size: 13pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-top: 5px;
      margin-bottom: 22px;
      text-transform: uppercase;
      color: #000;
    }
    .mutasi-student-bar {
      margin-bottom: 15px;
      font-size: 10.5pt;
      color: #000;
      font-family: Arial, Helvetica, sans-serif !important;
    }
    .mutasi-student-name-val {
      font-weight: bold;
      letter-spacing: 0.5px;
    }

    /* Tabel Mutasi Keluar */
    .table-mutasi-keluar {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 9.5pt;
      color: #000;
    }
    .table-mutasi-keluar th,
    .table-mutasi-keluar td {
      border: 1px solid #000;
      box-sizing: border-box;
    }
    .th-keluar-main {
      font-size: 11pt;
      padding: 7px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      text-align: center;
      font-weight: bold;
    }
    .table-mutasi-keluar thead th {
      text-align: center;
      font-weight: bold;
      vertical-align: middle;
      padding: 8px 4px;
    }
    .td-keluar-empty {
      height: 195px;
      vertical-align: top;
    }
    .td-keluar-ttd {
      padding: 8px 10px;
      font-size: 9pt;
      line-height: 1.3;
      vertical-align: top;
    }
    .ttd-date-dots {
      margin-bottom: 2px;
    }
    .ttd-label {
      margin-bottom: 0;
    }
    .ttd-space-kepsek {
      height: 48px;
    }
    .ttd-space-ortu {
      height: 44px;
    }
    .ttd-line-solid {
      border-bottom: 1px solid #000;
      width: 100%;
    }
    .ttd-nip {
      margin-top: 2px;
      font-weight: bold;
    }
    .ttd-label-ortu {
      margin-top: 8px;
    }

    /* Tabel Mutasi Masuk */
    .table-mutasi-masuk {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 9.5pt;
      color: #000;
    }
    .table-mutasi-masuk th,
    .table-mutasi-masuk td {
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      box-sizing: border-box;
    }
    .th-masuk-no {
      width: 6%;
      text-align: center;
      font-weight: bold;
      padding: 6px 2px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .th-masuk-main {
      font-size: 11pt;
      text-align: center;
      font-weight: bold;
      padding: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .td-masuk-no {
      width: 6%;
      text-align: center;
      vertical-align: middle;
      padding: 4px 2px;
      border-top: none;
      border-bottom: none;
    }
    .td-masuk-lbl {
      width: 30%;
      vertical-align: middle;
      padding: 4px 6px;
      border-top: none;
      border-bottom: none;
    }
    .td-masuk-lbl.sub-indent {
      padding-left: 20px;
    }
    .td-masuk-val {
      width: 29%;
      vertical-align: middle;
      padding: 4px 6px;
      border-top: none;
      border-bottom: none;
    }
    .line-dashed-masuk {
      border-bottom: 1px dashed #666;
      width: 95%;
      height: 10px;
    }
    .td-masuk-ttd {
      width: 35%;
      padding: 10px 10px;
      font-size: 9pt;
      line-height: 1.3;
      vertical-align: top;
      border-bottom: 1px solid #000;
    }
    .ttd-space-kepsek-masuk {
      height: 105px;
    }
    .ttd-line-dotted {
      border-bottom: 1px dotted #000;
      width: 100%;
    }
    .border-top-thick {
      border-top: 1px solid #000 !important;
    }
    .border-bottom-thick {
      border-bottom: 1px solid #000 !important;
    }

    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
    }
  </style>
</head>
<body onload="<?= $isSingle ? 'window.print()' : '' ?>">

  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Lembar Mutasi (Keluar & Masuk)</button>
    <button onclick="window.close()" class="btn-close">❌ Tutup</button>
    <span style="color:#555;font-size:9.5pt;margin-left:10px;">
      Lembar Catatan Mutasi Peserta Didik (Keluar & Masuk Terpisah) – Kelas <?= esc($class['name'] ?? '-') ?>
    </span>
  </div>

<?php foreach ($students as $stu): ?>
  <!-- ══════════════════════════════════════════════
       HALAMAN 1: KETERANGAN PINDAH SEKOLAH (KELUAR)
       ══════════════════════════════════════════════ -->
  <div class="doc-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="mutasi-header-title">KETERANGAN PINDAH SEKOLAH</div>

      <div class="mutasi-student-bar">
        <span>Nama Peserta Didik &nbsp;:&nbsp; </span>
        <span class="mutasi-student-name-val">
          <?php if (!empty($stu['name'])): ?>
            <?= esc(strtoupper($stu['name'])) ?>
          <?php else: ?>
            ........................................................
          <?php endif; ?>
        </span>
      </div>

      <table class="table-mutasi-keluar">
        <thead>
          <tr>
            <th colspan="4" class="th-keluar-main">KELUAR</th>
          </tr>
          <tr>
            <th style="width: 13%;">Tanggal</th>
            <th style="width: 16%;">Kelas yang<br>ditinggalkan</th>
            <th style="width: 35%;">Sebab-sebab Keluar atau<br>Atas Permintaan<br>(Tertulis)</th>
            <th style="width: 36%;">Tanda Tangan Kepala Sekolah,<br>Stempel Sekolah, dan Tanda<br>Tangan Orang Tua/Wali</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($k = 1; $k <= 3; $k++): ?>
            <tr>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-ttd">
                <div class="ttd-date-dots">..................................................</div>
                <div class="ttd-label">Kepala Sekolah,</div>
                <div class="ttd-space-kepsek"></div>
                <div class="ttd-line-solid"></div>
                <div class="ttd-nip">NIP.</div>
                <div class="ttd-label ttd-label-ortu">Orang Tua/Wali,</div>
                <div class="ttd-space-ortu"></div>
                <div class="ttd-line-solid"></div>
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 2: KETERANGAN PINDAH SEKOLAH (MASUK)
       ══════════════════════════════════════════════ -->
  <div class="doc-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="mutasi-header-title">KETERANGAN PINDAH SEKOLAH</div>

      <div class="mutasi-student-bar">
        <span>Nama Peserta Didik &nbsp;:&nbsp; </span>
        <span class="mutasi-student-name-val">
          <?php if (!empty($stu['name'])): ?>
            <?= esc(strtoupper($stu['name'])) ?>
          <?php else: ?>
            ........................................................
          <?php endif; ?>
        </span>
      </div>

      <table class="table-mutasi-masuk">
        <thead>
          <tr>
            <th class="th-masuk-no">NO</th>
            <th colspan="3" class="th-masuk-main">MASUK</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($b = 1; $b <= 3; $b++): ?>
            <!-- 1. Nama Siswa -->
            <tr>
              <td class="td-masuk-no <?= $b > 1 ? 'border-top-thick' : '' ?>">1.</td>
              <td class="td-masuk-lbl <?= $b > 1 ? 'border-top-thick' : '' ?>">Nama Siswa</td>
              <td class="td-masuk-val <?= $b > 1 ? 'border-top-thick' : '' ?>"><div class="line-dashed-masuk"></div></td>
              <td rowspan="7" class="td-masuk-ttd <?= $b > 1 ? 'border-top-thick' : '' ?>">
                <div class="ttd-date-dots">..................................................</div>
                <div class="ttd-label">Kepala Sekolah,</div>
                <div class="ttd-space-kepsek-masuk"></div>
                <div class="ttd-line-dotted"></div>
                <div class="ttd-nip">NIP.</div>
              </td>
            </tr>
            <!-- 2. Nomor Induk -->
            <tr>
              <td class="td-masuk-no">2.</td>
              <td class="td-masuk-lbl">Nomor Induk</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 3. Nama Sekolah -->
            <tr>
              <td class="td-masuk-no">3.</td>
              <td class="td-masuk-lbl">Nama Sekolah</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 4. Masuk di Sekolah ini: -->
            <tr>
              <td class="td-masuk-no">4.</td>
              <td class="td-masuk-lbl">Masuk di Sekolah ini:</td>
              <td class="td-masuk-val"></td>
            </tr>
            <!-- a. Tanggal -->
            <tr>
              <td class="td-masuk-no"></td>
              <td class="td-masuk-lbl sub-indent">a. Tanggal</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- b. Di Kelas -->
            <tr>
              <td class="td-masuk-no"></td>
              <td class="td-masuk-lbl sub-indent">b. Di Kelas</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 5. Tahun Pelajaran -->
            <tr>
              <td class="td-masuk-no border-bottom-thick">5.</td>
              <td class="td-masuk-lbl border-bottom-thick">Tahun Pelajaran</td>
              <td class="td-masuk-val border-bottom-thick"><div class="line-dashed-masuk"></div></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>

</body>
</html>
