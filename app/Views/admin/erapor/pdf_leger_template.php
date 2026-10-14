<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= esc($title) ?></title>
  <style>
    @page {
      margin: 8mm 8mm 8mm 8mm;
    }
    body {
      font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
      font-size: 8pt;
      color: #000;
      margin: 0;
      padding: 0;
    }

    /* Fallback HTML Kop Surat jika kop_base64 dari PdfGenerator tidak ada */
    .kop-html {
      border-bottom: 2px solid #000;
      padding-bottom: 6px;
      margin-bottom: 10px;
    }
    .kop-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
    }
    .kop-table td {
      border: none !important;
      padding: 0;
    }

    .doc-title {
      text-align: center;
      margin-bottom: 8px;
    }
    .doc-title h4 {
      font-size: 10.5pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 0 0 3px 0;
    }
    .doc-info {
      font-size: 8pt;
      color: #222;
    }

    .leger-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .leger-table th,
    .leger-table td {
      border: 1px solid #000;
      padding: 3px 4px;
      font-size: 7.5pt;
      vertical-align: middle;
    }
    .leger-table th {
      background-color: #eee;
      font-weight: bold;
      text-align: center;
    }
    .text-center { text-align: center; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }

    .signature-section {
      margin-top: 12px;
      page-break-inside: avoid;
      font-size: 8pt;
    }
    .signature-table {
      width: 100%;
      border: none;
      text-align: center;
    }
    .signature-table td {
      border: none !important;
      vertical-align: top;
    }
    .signature-space {
      height: 45px;
    }
  </style>
</head>
<body>

  <!-- Fallback HTML Kop Surat (jika useKop true & kop_base64 tidak disuntikkan/kosong) -->
  <?php if (!empty($useKop) && empty($kop_base64)): ?>
    <div class="kop-html">
      <table class="kop-table">
        <tr>
          <?php if (!empty($logo_base64)): ?>
            <td style="width: 65px; text-align: center; vertical-align: middle;">
              <img src="<?= $logo_base64 ?>" style="max-height: 55px; width: auto;">
            </td>
          <?php endif; ?>
          <td style="text-align: center; vertical-align: middle;">
            <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase;">
              <?= esc($school['name'] ?? 'SEKOLAH') ?>
            </div>
            <div style="font-size: 8.5pt; margin-top: 2px;">
              <?= esc($school['address'] ?? '') ?> <?= esc($school['city_regency'] ?? '') ?>
            </div>
            <div style="font-size: 7.5pt; color: #444; margin-top: 1px;">
              Telp: <?= esc($school['phone'] ?? '-') ?> | Email: <?= esc($school['email'] ?? '-') ?>
            </div>
          </td>
        </tr>
      </table>
    </div>
  <?php endif; ?>

  <!-- DOCUMENT TITLE -->
  <div class="doc-title">
    <h4>LEGER NILAI ERAPOR &amp; REKAP KETIDAKHADIRAN SISWA</h4>
    <div class="doc-info">
      Kelas: <strong><?= esc($class['name']) ?></strong>
      &nbsp;•&nbsp; Semester: <strong><?= esc($semesterLabel) ?></strong>
      &nbsp;•&nbsp; Tahun Ajaran: <strong><?= esc($year['year']) ?></strong>
      &nbsp;•&nbsp; Total Siswa: <strong><?= count($students) ?> Siswa</strong>
    </div>
  </div>

  <!-- LEGER TABLE -->
  <table class="leger-table">
    <thead>
      <tr>
        <th rowspan="2" style="width: 22px;">#</th>
        <th rowspan="2" class="text-start" style="min-width: 130px;">Nama Siswa</th>
        <th rowspan="2" style="width: 65px;">NISN</th>
        <th rowspan="2" style="width: 55px;">NIS</th>
        <th colspan="<?= count($subjects) ?>">Nilai Mata Pelajaran (Erapor)</th>
        <th rowspan="2" style="width: 35px;">Rata</th>
        <th colspan="3">Absensi (Hari)</th>
      </tr>
      <tr>
        <?php foreach ($subjects as $sub): ?>
          <th style="font-size: 7pt;">
            <?= esc($sub['code'] ?? $sub['name']) ?>
          </th>
        <?php endforeach; ?>
        <th style="width: 20px;">S</th>
        <th style="width: 20px;">I</th>
        <th style="width: 20px;">A</th>
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
          <td class="text-center" style="color: #555;"><?= $idx + 1 ?></td>
          <td class="text-start fw-bold"><?= esc($st['name']) ?></td>
          <td class="text-center"><?= esc($st['nisn'] ?: '—') ?></td>
          <td class="text-center"><?= esc($st['nis'] ?: '—') ?></td>

          <?php foreach ($subjects as $sub): ?>
            <?php $scoreVal = $scores[$st['id']][$sub['id']] ?? null; ?>
            <td class="text-center">
              <?php if ($scoreVal !== null && $scoreVal !== ''): ?>
                <span class="<?= ((float)$scoreVal < 70) ? 'fw-bold' : '' ?>" style="<?= ((float)$scoreVal < 70) ? 'color:#c00;' : '' ?>">
                  <?= esc(number_format((float)$scoreVal, 0)) ?>
                </span>
              <?php else: ?>
                <span style="color:#999;">—</span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>

          <td class="text-center fw-bold" style="background-color:#f9f9f9;">
            <?= $avgScore !== null ? esc(number_format($avgScore, 1)) : '—' ?>
          </td>

          <?php $recap = $attendanceRecaps[$st['id']] ?? null; ?>
          <td class="text-center" style="background-color:#f9f9f9;"><?= $recap ? esc($recap['sick']) : '0' ?></td>
          <td class="text-center" style="background-color:#f9f9f9;"><?= $recap ? esc($recap['permitted']) : '0' ?></td>
          <td class="text-center" style="background-color:#f9f9f9;"><?= $recap ? esc($recap['absent']) : '0' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- SIGNATURE SECTION -->
  <?php if (!empty($useSignature)): ?>
    <?php
      $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      $tglCetak = date('j') . ' ' . $bulanIndo[(int)date('n') - 1] . ' ' . date('Y');
      $kota = !empty($school['city_regency']) ? $school['city_regency'] : 'Kota';
    ?>
    <div class="signature-section">
      <table class="signature-table">
        <tr>
          <td style="width: 50%;">
            Mengetahui,<br>
            <strong>Kepala Sekolah</strong>
            <div class="signature-space"></div>
            <strong style="text-decoration: underline;"><?= esc($principal['name']) ?></strong><br>
            NIP. <?= esc($principal['nip']) ?>
          </td>
          <td style="width: 50%;">
            <?= esc($kota) ?>, <?= $tglCetak ?><br>
            <strong>Wali Kelas <?= esc($class['name']) ?></strong>
            <div class="signature-space"></div>
            <strong style="text-decoration: underline;"><?= esc($waliKelas['name']) ?></strong><br>
            NIP. <?= esc($waliKelas['nip']) ?>
          </td>
        </tr>
      </table>
    </div>
  <?php endif; ?>

</body>
</html>
