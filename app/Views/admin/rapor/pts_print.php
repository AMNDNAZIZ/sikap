<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rapor PTS – <?= esc($class['name'] ?? '') ?> – Semester <?= esc($semester) ?></title>
  <style>
    @page {
      size: portrait;
      margin: 1.8cm 1.5cm;
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 11pt;
      color: #000;
      line-height: 1.4;
      margin: 0;
    }

    /* ── Satu halaman per siswa ── */
    .rapor-page {
      page-break-after: always;
      page-break-inside: avoid;
    }
    .rapor-page:last-child { page-break-after: auto; }

    /* ── Kop / Header ── */
    .kop {
      display: flex;
      align-items: center;
      border-bottom: 3px double #000;
      padding-bottom: 8px;
      margin-bottom: 10px;
    }
    .kop-logo {
      width: 64px;
      height: 64px;
      object-fit: contain;
      margin-right: 12px;
    }
    .kop-logo-placeholder {
      width: 64px;
      height: 64px;
      border: 1px solid #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      font-size: 8pt;
      color: #888;
      flex-shrink: 0;
    }
    .kop-text { text-align: center; flex: 1; }
    .kop-text .sekolah-nama { font-size: 14pt; font-weight: bold; }
    .kop-text .sekolah-alamat { font-size: 9pt; }

    .judul-rapor {
      text-align: center;
      font-size: 13pt;
      font-weight: bold;
      text-decoration: underline;
      margin: 10px 0 6px;
      text-transform: uppercase;
    }
    .sub-judul {
      text-align: center;
      font-size: 10pt;
      margin-bottom: 12px;
    }

    /* ── Identitas Siswa ── */
    .identitas-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    .identitas-table td {
      padding: 2px 4px;
      vertical-align: top;
      font-size: 10.5pt;
    }
    .identitas-table td:first-child { width: 170px; font-weight: normal; }
    .identitas-table td:nth-child(2) { width: 12px; }

    /* ── Tabel Nilai ── */
    .nilai-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
      font-size: 10.5pt;
    }
    .nilai-table th, .nilai-table td {
      border: 1px solid #000;
      padding: 4px 6px;
      vertical-align: middle;
    }
    .nilai-table thead th {
      background: #dde8f0;
      text-align: center;
      font-size: 10pt;
    }
    .nilai-table td.nomor { text-align: center; width: 32px; }
    .nilai-table td.angka { text-align: center; width: 110px; }
    .nilai-table td.nilai-kosong { text-align: center; color: #888; font-style: italic; }

    /* ── Ketidakhadiran ── */
    .absen-section {
      margin-bottom: 12px;
    }
    .absen-section h5 {
      font-size: 10.5pt;
      margin: 0 0 4px;
      font-weight: bold;
    }
    .absen-table {
      border-collapse: collapse;
      font-size: 10.5pt;
    }
    .absen-table td, .absen-table th {
      border: 1px solid #000;
      padding: 3px 10px;
      text-align: center;
      min-width: 70px;
    }
    .absen-table th { background: #f0f0f0; }

    /* ── Tanda Tangan ── */
    .ttd-section {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      page-break-inside: avoid;
      clear: both;
    }
    .ttd-block {
      width: 44%;
      text-align: center;
    }
    .ttd-space { height: 65px; }
    .ttd-nama { font-weight: bold; border-top: 1px solid #000; padding-top: 2px; }
    .ttd-nip  { font-size: 9.5pt; }

    /* ── Utilitas ── */
    .text-center { text-align: center; }
    .fw-bold { font-weight: bold; }
    .small { font-size: 9pt; }

    /* ── Print control ── */
    .no-print {
      background: #f8f9fa;
      padding: 10px 14px;
      border-bottom: 1px solid #ddd;
      margin-bottom: 18px;
      font-family: Arial, sans-serif;
      font-size: 10pt;
    }
    @media print {
      .no-print { display: none; }
      body { margin: 0; }
    }
  </style>
</head>
<body onload="window.print()">

  <div class="no-print">
    <button onclick="window.print()" style="padding:5px 15px;cursor:pointer;">🖨️ Cetak Sekarang</button>
    <button onclick="window.close()" style="padding:5px 15px;cursor:pointer;margin-left:10px;">❌ Tutup</button>
    <span style="margin-left:16px;color:#555;">
      Rapor PTS – <?= esc($class['name'] ?? '-') ?> –
      Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?> –
      <?= esc($activeYear['year'] ?? '-') ?> |
      <?= count($reportData) ?> siswa
    </span>
  </div>

<?php foreach ($reportData as $entry):
    $stu = $entry['student'];
    $att = $entry['attendance'];
    $rows = $entry['rows'];
?>
  <div class="rapor-page">

    <!-- ═══════════ KOP ═══════════ -->
    <div class="kop">
      <?php if (!empty($school['logo'])): ?>
        <img src="<?= base_url('public/uploads/' . $school['logo']) ?>"
             alt="Logo" class="kop-logo">
      <?php else: ?>
        <div class="kop-logo-placeholder">LOGO</div>
      <?php endif; ?>
      <div class="kop-text">
        <div class="sekolah-nama"><?= esc(strtoupper($school['name'] ?? 'NAMA SEKOLAH')) ?></div>
        <div class="sekolah-alamat"><?= esc($school['address'] ?? '') ?></div>
        <div class="sekolah-alamat">
          Kota/Kab. <?= esc($school['city_regency'] ?? '') ?>
          <?php if (!empty($school['phone'])): ?>| Telp. <?= esc($school['phone']) ?><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════ JUDUL ═══════════ -->
    <div class="judul-rapor">Rapor Penilaian Tengah Semester (PTS)</div>
    <div class="sub-judul">
      Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?> –
      Tahun Pelajaran <?= esc($activeYear['year'] ?? '-') ?>
    </div>

    <!-- ═══════════ IDENTITAS ═══════════ -->
    <table class="identitas-table">
      <tr>
        <td>Nama Peserta Didik</td><td>:</td>
        <td class="fw-bold"><?= esc($stu['name']) ?></td>
      </tr>
      <tr>
        <td>NIS / NISN</td><td>:</td>
        <td><?= esc($stu['nis'] ?: '-') ?> / <?= esc($stu['nisn'] ?: '-') ?></td>
      </tr>
      <tr>
        <td>Kelas</td><td>:</td>
        <td><?= esc($class['name']) ?></td>
      </tr>
      <tr>
        <td>Wali Kelas</td><td>:</td>
        <td><?= esc($class['wali_name'] ?? '-') ?></td>
      </tr>
    </table>

    <!-- ═══════════ TABEL NILAI ═══════════ -->
    <table class="nilai-table">
      <thead>
        <tr>
          <th class="nomor">No</th>
          <th style="text-align:left;">Mata Pelajaran</th>
          <th>Nilai Materi<br><span class="small">(Rata-rata Formatif)</span></th>
          <th>Nilai PTS</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($rows as $row): ?>
          <tr>
            <td class="nomor"><?= $no++ ?></td>
            <td><?= esc($row['subject_name']) ?></td>
            <td class="angka <?= $row['nilai_materi'] === null ? 'nilai-kosong' : '' ?>">
              <?= $row['nilai_materi'] !== null ? number_format($row['nilai_materi'], 1) : '-' ?>
            </td>
            <td class="angka <?= $row['nilai_pts'] === null ? 'nilai-kosong' : '' ?>">
              <?= $row['nilai_pts'] !== null ? number_format($row['nilai_pts'], 1) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ═══════════ KETIDAKHADIRAN ═══════════ -->
    <div class="absen-section">
      <h5>Ketidakhadiran</h5>
      <table class="absen-table">
        <thead>
          <tr>
            <th>Sakit (hari)</th>
            <th>Izin (hari)</th>
            <th>Tanpa Keterangan / Alpa (hari)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= $att['sakit'] ?></td>
            <td><?= $att['izin'] ?></td>
            <td><?= $att['alpa'] ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══════════ TANDA TANGAN ═══════════ -->
    <div style="clear:both;"></div>
    <div class="ttd-section">
      <div class="ttd-block">
        Mengetahui,<br>
        Kepala Sekolah
        <div class="ttd-space"></div>
        <div class="ttd-nama"><?= esc($school['headmaster'] ?? '-') ?></div>
        <div class="ttd-nip">NIP. <?= esc($school['principal_nip'] ?? '-') ?></div>
      </div>
      <div class="ttd-block">
        <?= esc($school['city_regency'] ?? 'Indramayu') ?>, <?= date('d F Y') ?><br>
        Wali Kelas
        <div class="ttd-space"></div>
        <div class="ttd-nama"><?= esc($class['wali_name'] ?? '-') ?></div>
        <div class="ttd-nip">NIP. <?= esc($class['wali_nip'] ?? '-') ?></div>
      </div>
    </div>

  </div><!-- /.rapor-page -->
<?php endforeach; ?>

</body>
</html>
