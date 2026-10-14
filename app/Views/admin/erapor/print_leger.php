<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title) ?></title>
  <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      color: #000;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    .print-page {
      background: #fff;
      max-width: 297mm;
      margin: 20px auto;
      padding: 12mm 15mm;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
      border-radius: 4px;
    }

    /* Kop Surat Container */
    .kop-container {
      margin-bottom: 12px;
    }
    .kop-image {
      width: 100%;
      max-height: 120px;
      object-fit: contain;
      display: block;
      margin: 0 auto;
    }
    .kop-html {
      border-bottom: 3px double #000;
      padding-bottom: 8px;
      margin-bottom: 12px;
    }

    /* Document Title */
    .doc-title {
      text-align: center;
      margin-bottom: 10px;
    }
    .doc-title h4 {
      font-size: 11pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 0 0 4px 0;
      letter-spacing: 0.5px;
    }
    .doc-info {
      font-size: 8.5pt;
      color: #333;
    }

    /* Table Styling */
    .leger-table {
      width: 100%;
      border-collapse: collapse !important;
      margin-bottom: 15px;
    }
    .leger-table th,
    .leger-table td {
      border: 1px solid #000 !important;
      padding: 3px 4px !important;
      font-size: 8pt !important;
      vertical-align: middle;
      line-height: 1.15;
    }
    .leger-table th {
      background-color: #f0f0f0 !important;
      font-weight: bold;
      text-align: center;
    }

    /* Signature Section */
    .signature-section {
      margin-top: 15px;
      page-break-inside: avoid;
      break-inside: avoid;
      font-size: 8.5pt;
    }
    .signature-space {
      height: 55px;
    }

    /* Floating Control Bar for Screen */
    .print-control-bar {
      position: sticky;
      top: 0;
      z-index: 9999;
      background: #212529;
      color: #fff;
      padding: 10px 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    @media print {
      @page {
        size: A4 landscape;
        margin: 0;
      }
      body {
        background: #fff !important;
        margin: 0 !important;
        padding: 8mm 10mm !important;
      }
      .print-page {
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      .no-print {
        display: none !important;
      }
      .print-control-bar {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- Floating Control Bar (Web Screen Only) -->
  <div class="print-control-bar no-print d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <span class="fw-bold text-light">🖨️ Cetak Leger Rapor</span>
      <div class="form-check form-switch text-light ms-2">
        <input class="form-check-input" type="checkbox" id="toggleKopLive" <?= $useKop ? 'checked' : '' ?> onchange="toggleKopDisplay()">
        <label class="form-check-label" for="toggleKopLive">Tampilkan Kop Surat</label>
      </div>
      <div class="form-check form-switch text-light">
        <input class="form-check-input" type="checkbox" id="toggleSigLive" <?= $useSignature ? 'checked' : '' ?> onchange="toggleSigDisplay()">
        <label class="form-check-label" for="toggleSigLive">Tampilkan Tanda Tangan</label>
      </div>
    </div>
    <div class="d-flex gap-2">
      <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
        🖨️ Cetak Sekarang
      </button>
      <button onclick="window.close()" class="btn btn-outline-light btn-sm px-3">
        ✖ Tutup Tab
      </button>
    </div>
  </div>

  <div class="print-page">

    <!-- KOP SURAT SECTION -->
    <div id="kop-section" class="kop-container" style="display: <?= $useKop ? 'block' : 'none' ?>;">
      <?php if ($hasKopImage): ?>
        <img src="<?= base_url($kopPath) ?>" class="kop-image" alt="Kop Surat">
      <?php else: ?>
        <div class="kop-html">
          <table style="width: 100%;">
            <tr>
              <?php if (!empty($school['logo']) && file_exists(FCPATH . $school['logo'])): ?>
                <td style="width: 75px; text-align: center; vertical-align: middle;">
                  <img src="<?= base_url($school['logo']) ?>" style="max-height: 65px; width: auto;" alt="Logo">
                </td>
              <?php endif; ?>
              <td style="text-align: center; vertical-align: middle;">
                <h3 style="margin: 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                  <?= esc($school['name'] ?? 'SEKOLAH') ?>
                </h3>
                <p style="margin: 2px 0 0 0; font-size: 9pt;">
                  <?= esc($school['address'] ?? '') ?> <?= esc($school['city_regency'] ?? '') ?>
                </p>
                <p style="margin: 2px 0 0 0; font-size: 8pt; color: #333;">
                  Telp: <?= esc($school['phone'] ?? '-') ?> | Email: <?= esc($school['email'] ?? '-') ?>
                </p>
              </td>
            </tr>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title">
      <h4>LEGER NILAI ERAPOR &amp; REKAP KETIDAKHADIRAN SISWA</h4>
      <div class="doc-info fw-semibold">
        <span>Kelas: <strong><?= esc($class['name']) ?></strong></span>
        <span class="mx-2">•</span>
        <span>Semester: <strong><?= esc($semesterLabel) ?></strong></span>
        <span class="mx-2">•</span>
        <span>Tahun Ajaran: <strong><?= esc($year['year']) ?></strong></span>
        <span class="mx-2">•</span>
        <span>Total Siswa: <strong><?= count($students) ?> Siswa</strong></span>
      </div>
    </div>

    <!-- LEGER TABLE -->
    <table class="table table-bordered align-middle text-nowrap leger-table">
      <thead>
        <tr>
          <th rowspan="2" style="width: 25px;">#</th>
          <th rowspan="2" class="text-start" style="min-width: 140px;">Nama Siswa</th>
          <th rowspan="2" style="width: 70px;">NISN</th>
          <th rowspan="2" style="width: 60px;">NIS</th>
          <th colspan="<?= count($subjects) ?>">
            Nilai Mata Pelajaran (Erapor)
          </th>
          <th rowspan="2" style="width: 40px;" title="Rata-rata Nilai">Rata</th>
          <th colspan="3">Absensi (Hari)</th>
        </tr>
        <tr>
          <?php foreach ($subjects as $sub): ?>
            <th style="font-size: 7.5pt;">
              <span title="<?= esc($sub['name']) ?>"><?= esc($sub['code'] ?? $sub['name']) ?></span>
            </th>
          <?php endforeach; ?>
          <th style="width: 22px;" title="Sakit">S</th>
          <th style="width: 22px;" title="Izin">I</th>
          <th style="width: 22px;" title="Alpa">A</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $idx => $st): ?>
          <?php 
            $studentScores = [];
            foreach ($subjects as $sub) {
                $v = $scores[$st['id']][$sub['id']] ?? null;
                if ($v !== null && $v !== '') {
                    $studentScores[] = (float) $v;
                }
            }
            $avgScore = !empty($studentScores) ? round(array_sum($studentScores) / count($studentScores), 1) : null;
          ?>
          <tr>
            <td class="text-center text-muted"><?= $idx + 1 ?></td>
            <td class="fw-semibold text-start"><?= esc($st['name']) ?></td>
            <td class="text-center font-monospace"><small><?= esc($st['nisn'] ?: '—') ?></small></td>
            <td class="text-center font-monospace"><small><?= esc($st['nis'] ?: '—') ?></small></td>

            <?php foreach ($subjects as $sub): ?>
              <?php $scoreVal = $scores[$st['id']][$sub['id']] ?? null; ?>
              <td class="text-center">
                <?php if ($scoreVal !== null && $scoreVal !== ''): ?>
                  <span class="<?= ((float)$scoreVal < 70) ? 'text-danger fw-bold' : '' ?>">
                    <?= esc(number_format((float)$scoreVal, 0)) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>

            <td class="text-center fw-bold bg-light">
              <?= $avgScore !== null ? esc(number_format($avgScore, 1)) : '—' ?>
            </td>

            <?php $recap = $attendanceRecaps[$st['id']] ?? null; ?>
            <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['sick']) : '0' ?></td>
            <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['permitted']) : '0' ?></td>
            <td class="text-center font-monospace bg-light"><?= $recap ? esc($recap['absent']) : '0' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <?php
      $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      $tglCetak = date('j') . ' ' . $bulanIndo[(int)date('n') - 1] . ' ' . date('Y');
      $kota = !empty($school['city_regency']) ? $school['city_regency'] : 'Kota';
    ?>
    <div id="signature-section" class="signature-section" style="display: <?= $useSignature ? 'block' : 'none' ?>;">
      <table style="width: 100%; text-align: center; border: none;">
        <tr>
          <td style="width: 50%; vertical-align: top; border: none;">
            <p class="mb-1">Mengetahui,</p>
            <p class="fw-bold mb-0">Kepala Sekolah</p>
            <div class="signature-space"></div>
            <p class="fw-bold text-decoration-underline mb-0"><?= esc($principal['name']) ?></p>
            <small class="d-block text-muted">NIP. <?= esc($principal['nip']) ?></small>
          </td>
          <td style="width: 50%; vertical-align: top; border: none;">
            <p class="mb-1"><?= esc($kota) ?>, <?= $tglCetak ?></p>
            <p class="fw-bold mb-0">Wali Kelas <?= esc($class['name']) ?></p>
            <div class="signature-space"></div>
            <p class="fw-bold text-decoration-underline mb-0"><?= esc($waliKelas['name']) ?></p>
            <small class="d-block text-muted">NIP. <?= esc($waliKelas['nip']) ?></small>
          </td>
        </tr>
      </table>
    </div>

  </div>

  <script>
    function toggleKopDisplay() {
      const checked = document.getElementById('toggleKopLive').checked;
      document.getElementById('kop-section').style.display = checked ? 'block' : 'none';
    }

    function toggleSigDisplay() {
      const checked = document.getElementById('toggleSigLive').checked;
      document.getElementById('signature-section').style.display = checked ? 'block' : 'none';
    }

    // Auto print upon loading tab
    window.addEventListener('load', function() {
      setTimeout(function() {
        window.print();
      }, 400);
    });
  </script>

</body>
</html>
