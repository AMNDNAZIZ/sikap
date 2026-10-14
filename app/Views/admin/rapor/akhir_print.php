<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rapor Akhir – <?= esc($class['name'] ?? '') ?> – Semester <?= esc($semester) ?></title>
  <style>
    @page { size: portrait; margin: 1.5cm 1.5cm; }
    * { box-sizing: border-box; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 11pt;
      color: #000;
      line-height: 1.4;
      margin: 0;
    }

    /* ── Satu halaman per siswa ── */
    .rapor-page { page-break-after: always; page-break-inside: avoid; }
    .rapor-page:last-child { page-break-after: auto; }

    /* ── KOP Surat ── */
    .kop-surat {
      width: 100%;
      text-align: center;
      margin-bottom: 2px;
    }
    .kop-surat img {
      width: 100%;
      height: auto;
      display: block;
    }
    .kop-fallback {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: center;
      font-size: 9pt;
      color: #888;
      margin-bottom: 4px;
    }
    .kop-divider {
      border-top: 3px double #000;
      margin-bottom: 8px;
    }
    .garis-divider {
      border-top: 2px double #000;
      margin-bottom: 8px;
    }

    /* ── Judul Rapor ── */
    .judul-rapor {
      text-align: center;
      font-size: 13pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 8px 0 10px;
      letter-spacing: 0.5px;
    }

    /* ── Identitas Siswa — 2 kolom ── */
    .identitas-outer {
      border: 0px;
      padding: 7px 10px;
      margin-bottom: 3px;
    }
    .identitas-grid {
      display: table;
      width: 100%;
      border-collapse: collapse;
    }
    .identitas-col {
      display: table-cell;
      width: 50%;
      vertical-align: top;
      padding: 1px 0;
    }
    .pisah-col {
      display: table-cell;
      width: 20%;
      vertical-align: top;
      padding: 1px 0;
    }
    .identitas-col:first-child { padding-right: 14px; }
    .identitas-col:last-child  { padding-left: 14px; }

    .id-row {
      display: table;
      width: 100%;
      margin-bottom: 1px;
    }
    .id-label { display: table-cell; width: 110px; font-size: 10pt; vertical-align: top; }
    .id-sep   { display: table-cell; width: 12px; font-size: 10pt; vertical-align: top; }
    .id-value { display: table-cell; font-size: 10pt; font-weight: bold; vertical-align: top; }

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
    .nilai-table td.angka { text-align: center; width: 60px; }
    .nilai-table td.pred { text-align: center; width: 60px; }
    .nilai-table td.desc { font-size: 9pt; }
    .nilai-table td.kosong { text-align: center; color: #888; font-style: italic; }

    /* ── Ketidakhadiran & Ekskul ── */
    .section-label {
      font-size: 10.5pt;
      font-weight: bold;
      margin-bottom: 4px;
      margin-top: 10px;
    }
    .absen-table { border-collapse: collapse; font-size: 10.5pt; margin-bottom: 14px;}
    .absen-table td, .absen-table th {
      border: 1px solid #000;
      padding: 3px 14px;
      text-align: center;
      min-width: 80px;
    }
    .absen-table th { background: #f0f0f0; font-weight: bold; }

    /* ── Tanda Tangan ── */
    .ttd-section {
      display: table;
      width: 100%;
      margin-top: 22px;
      page-break-inside: avoid;
      clear: both;
    }
    .ttd-col {
      display: table-cell;
      width: 50%;
      text-align: center;
      vertical-align: top;
      padding: 0 16px;
    }
    .ttd-role { font-size: 10.5pt; margin-bottom: 0; }
    .ttd-space { height: 64px; }
    .ttd-nama {
      font-size: 10.5pt;
      font-weight: bold;
      display: inline-block;
      border-bottom: 1px solid #000;
      padding-bottom: 1px;
    }
    .ttd-nip { font-size: 9.5pt; margin-top: 2px; color: #000; }

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
      Rapor Akhir – <?= esc($class['name'] ?? '-') ?> –
      Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?> –
      <?= esc($activeYear['year'] ?? '-') ?> | <?= count($reportData) ?> siswa
    </span>
  </div>

<?php foreach ($reportData as $entry):
    $stu = $entry['student'];
    $att = $entry['attendance'];
    $rows = $entry['rows'];
    $ekskul = $entry['ekskul'] ?? [];
?>
  <div class="rapor-page">

    <!-- ═══ 1. KOP SURAT ═══ -->
    <?php if (!empty($kopBase64)): ?>
      <div class="kop-surat">
        <img src="<?= $kopBase64 ?>" alt="Kop Surat">
      </div>
    <?php else: ?>
      <div class="kop-fallback">[ KOP Surat — <?= esc(strtoupper($school['name'] ?? 'NAMA SEKOLAH')) ?> ]<br>
        <small>Upload kop surat di menu Pengaturan → KOP Surat</small></div>
    <?php endif; ?>
   
    <!-- ═══ 3. IDENTITAS SISWA — 2 kolom ═══ -->
    <div class="identitas-outer">
      <div class="identitas-grid">
        <div class="identitas-col">
          <div class="id-row"><span class="id-label">Nama</span><span class="id-sep">:</span><span class="id-value"><?= esc($stu['name']) ?></span></div>
          <div class="id-row"><span class="id-label">NIS/NISN</span><span class="id-sep">:</span><span class="id-value"><?= esc(($stu['nis'] ?: '-') . ' / ' . ($stu['nisn'] ?: '-')) ?></span></div>
          <div class="id-row"><span class="id-label">Nama Sekolah</span><span class="id-sep">:</span><span class="id-value"><?= esc($school['name'] ?? '-') ?></span></div>
          <div class="id-row"><span class="id-label">Alamat</span><span class="id-sep">:</span><span class="id-value"><?= esc($school['address'] ?? '-') ?></span></div>
        </div>
        <div class="pisah-col"></div>
        <div class="identitas-col">
          <div class="id-row"><span class="id-label">Kelas</span><span class="id-sep">:</span><span class="id-value"><?= esc($class['name']) ?></span></div>
          <div class="id-row"><span class="id-label">Fase</span><span class="id-sep">:</span><span class="id-value"><?= esc($fase) ?></span></div>
          <div class="id-row"><span class="id-label">Semester</span><span class="id-sep">:</span><span class="id-value"><?= $semester ?></span></div>
          <div class="id-row"><span class="id-label">Tahun Pelajaran</span><span class="id-sep">:</span><span class="id-value"><?= esc($activeYear['year'] ?? '-') ?></span></div>
        </div>
      </div>
    </div>
    <div class="garis-divider"></div>
    
    <!-- ═══ 2. JUDUL ═══ -->
    <div class="judul-rapor">Laporan Hasil Belajar (Rapor)</div>

    <!-- ═══ 4. TABEL NILAI AKADEMIK ═══ -->
    <div class="section-label">A. Sikap dan Akademik</div>
    <table class="nilai-table">
      <thead>
        <tr>
          <th class="nomor">No</th>
          <th style="text-align:left;">Mata Pelajaran</th>
          <th>Nilai Akhir</th>
          <th>Predikat</th>
          <th>Deskripsi Capaian Kompetensi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($rows as $row): ?>
          <tr>
            <td class="nomor"><?= $no++ ?></td>
            <td><?= esc($row['subject_name']) ?></td>
            <td class="angka <?= $row['nilai'] === null ? 'kosong' : '' ?>">
              <?= $row['nilai'] !== null ? number_format($row['nilai'], 0) : '-' ?>
            </td>
            <td class="pred"><?= esc($row['predikat']) ?></td>
            <td class="desc"><?= esc($row['deskripsi']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ═══ 5. TABEL EKSTRAKURIKULER ═══ -->
    <div class="section-label">B. Ekstrakurikuler</div>
    <table class="nilai-table">
      <thead>
        <tr>
          <th class="nomor">No</th>
          <th style="text-align:left;">Kegiatan Ekstrakurikuler</th>
          <th>Predikat</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ekskul)): ?>
        <tr>
            <td colspan="4" class="kosong" style="padding:10px;">Tidak mengikuti kegiatan ekstrakurikuler.</td>
        </tr>
        <?php else: ?>
            <?php $ne = 1; foreach ($ekskul as $ek): ?>
            <tr>
                <td class="nomor"><?= $ne++ ?></td>
                <td><?= esc($ek['ekskul_name']) ?></td>
                <td class="pred"><?= esc($ek['predicate']) ?></td>
                <td class="desc"><?= esc($ek['description']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- ═══ 6. KETIDAKHADIRAN ═══ -->
    <div class="section-label">C. Ketidakhadiran</div>
    <table class="absen-table">
      <thead>
        <tr>
          <th>Sakit</th>
          <th>Izin</th>
          <th>Alpa / Tanpa Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= (int) $att['sakit'] ?> hari</td>
          <td><?= (int) $att['izin'] ?> hari</td>
          <td><?= (int) $att['alpa'] ?> hari</td>
        </tr>
      </tbody>
    </table>

    <!-- ═══ 7. TANDA TANGAN ═══ -->
    <div style="clear:both;"></div>
    <div class="ttd-section">
      <div class="ttd-col">
        <div class="ttd-role">Mengetahui,</div>
        <div class="ttd-role">Kepala Sekolah</div>
        <div class="ttd-space"></div>
        <div><span class="ttd-nama"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></span></div>
        <div class="ttd-nip">NIP. <?= esc($school['principal_nip'] ?? '-') ?></div>
      </div>
      <div class="ttd-col">
        <div class="ttd-role"><?= esc($school['city_regency'] ?? 'Kota/Kab.') ?>, <?= date('d F Y') ?></div>
        <div class="ttd-role">Wali Kelas</div>
        <div class="ttd-space"></div>
        <div><span class="ttd-nama"><?= esc($class['wali_name'] ?? 'Wali Kelas') ?></span></div>
        <div class="ttd-nip">NIP. <?= esc($class['wali_nip'] ?? '-') ?></div>
      </div>
    </div>

  </div>
<?php endforeach; ?>

</body>
</html>
