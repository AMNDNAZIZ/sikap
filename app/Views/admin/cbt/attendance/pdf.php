<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Daftar Hadir <?= esc($examName) ?></title>
  <style>
    body {
      font-family: "Times New Roman", Times, serif;
      font-size: 11pt;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 4px;
    }

    th {
      background: #f2f2f2;
      text-align: center;
    }

    .no-border,
    .no-border td {
      border: none !important;
    }

    .header-table td {
      vertical-align: middle;
      text-align: center;
    }

    .header-table img {
      width: 70px;
      height: 70px;
    }

    .title {
      text-align: center;
    }

    .title h3 {
      margin: 0;
      font-size: 16pt;
      line-height: 1.4;
    }

    .title p {
      margin: 2px 0;
    }

    hr {
      border: 1px solid #000;
      margin: 8px 0 12px;
    }

    .info td {
      border: none;
      padding: 2px 4px;
    }

    .sign {
      margin-top: 30px;
      text-align: right;
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <table class="header-table no-border">
    <tr>
      <td width="80" align="center">
        <?php if (!empty($logoBase64)): ?>
          <img src="<?= $logoBase64 ?>" alt="Logo Sekolah">
        <?php elseif (!empty($school['logo']) && file_exists(FCPATH . 'uploads/logo/' . $school['logo'])): ?>
          <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo Sekolah">
        <?php endif; ?>
      </td>
      <td>
        <div class="title">
          <h3>DAFTAR HADIR <?= strtoupper(esc($examName)) ?></h3>
          <p><strong><?= strtoupper(esc($school['name'])) ?></strong></p>
          <p>TAHUN PELAJARAN <?= esc($academicYear) ?></p>
        </div>
      </td>
    </tr>
  </table>

  <hr>

  <!-- INFO -->
  <table class="info">
    <tr>
      <td width="20%">Kelas</td>
      <td>: <?= esc($room) ?></td>
    </tr>
    <tr>
      <td>Mata Pelajaran</td>
      <td>: ...................................................</td>
    </tr>
    <tr>
      <td>Hari / Tanggal</td>
      <td>: <?= tanggal_indo($exam_date ?? date('Y-m-d')) ?></td>
    </tr>
  </table>

  <br>

  <!-- DAFTAR SISWA -->
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>NIS</th>
        <th>Nama Siswa</th>
        <th>L/P</th>
        <th>Kelas</th>
        <th>Tanda Tangan</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1;
      foreach ($students as $s): ?>
        <tr>
          <td style="text-align:center;"><?= $no ?></td>
          <td style="text-align:center;"><?= esc($s['nis']) ?></td>
          <td><?= strtoupper(esc($s['name'])) ?></td>
          <td style="text-align:center;"><?= esc($s['gender']) ?></td>
          <td style="text-align:center;"><?= esc($s['class_name']) ?></td>
          <td><?= $no++ ?>.</td>
        </tr>
      <?php endforeach; ?>

      <?php for ($i = $no; $i <= 5; $i++): ?>
        <tr>
          <td><?= $i ?></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <!-- TANDA TANGAN -->
  <div class="sign">
    <p>Jakarta, ...............................</p>
    <p>Pengawas Kelas <?= esc($room) ?></p>
    <br><br><br>
    <p>........................................</p>
  </div>

</body>

</html>