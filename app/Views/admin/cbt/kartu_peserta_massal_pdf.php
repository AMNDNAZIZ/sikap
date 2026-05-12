<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Kartu Peserta Ujian</title>
  <style>
    @page {
      margin: 20px;
    }

    body {
      font-family: "Times New Roman", Times, serif;
      font-size: 11pt;
      color: #000;
    }

    .page-break {
      page-break-after: always;
    }

    .card-wrapper {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
    }

    .card {
      width: 48%;
      border: 1px solid #000;
      border-radius: 4px;
      margin-bottom: 12px;
      padding: 6px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
    }

    .row-card {
      display: table;
      width: 100%;
      border-spacing: 10px 0;
      margin-bottom: 12px;
    }

    .card-student,
    .card-schedule {
      display: table-cell;
      width: 50%;
      vertical-align: top;
    }

    .header {
      width: 100%;
      border-bottom: 1px solid #000;
      margin-bottom: 6px;
    }

    .header-table {
      width: 100%;
      border-collapse: collapse;
    }

    .header-table td {
      vertical-align: middle;
      text-align: center;
    }

    .header-table .logo-cell {
      width: 80px;
      text-align: center;
    }

    .header-table img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }

    .header-table .text-cell h3 {
      margin: 0;
      font-size: 14pt;
      font-weight: bold;
    }

    .header-table .text-cell h4 {
      margin: 0;
      font-size: 12pt;
      font-weight: bold;
    }

    .header-table .text-cell .sub-title {
      font-size: 11pt;
      margin-top: 2px;
      font-weight: bold;
    }


    .sub-title {
      font-size: 11pt;
      margin-top: 2px;
      margin-bottom: 4px;
      text-align: center;
    }

    .info-table {
      width: 100%;
      font-size: 10.5pt;
      border-collapse: collapse;
    }

    .info-table td {
      padding: 2px 3px;
      vertical-align: top;
    }

    .photo-box {
      width: 18%;
      text-align: center;
      border: 1px solid #000;
      background: #e9edf1;
      height: 90px;
      font-size: 9pt;
      color: #777;
    }

    .detail-box {
      width: 65%;
      padding-left: 6px;
    }

    .signature {
      margin-top: 10px;
      text-align: center;
      font-size: 10pt;
    }

    .signature p {
      margin: 2px 0;
    }

    /* Tabel Jadwal */
    .schedule-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8.5pt;
      margin-top: 6px;
    }

    .schedule-table th,
    .schedule-table td {
      border: 1px solid #000;
      padding: 3px 4px;
      text-align: left;
    }

    .schedule-table th {
      text-align: center;
      background: #f1f1f1;
    }

    .center {
      text-align: center;
    }
  </style>
</head>

<body>

  <?php
  function hariTanggalIndo($dateStr)
  {
    $hari = [
      'Sunday' => 'Minggu',
      'Monday' => 'Senin',
      'Tuesday' => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday' => 'Kamis',
      'Friday' => 'Jumat',
      'Saturday' => 'Sabtu'
    ];
    $bulan = [
      1 => 'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des'
    ];
    $timestamp = strtotime($dateStr);
    return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[(int) date('m', $timestamp)] . ' ' . date('Y', $timestamp);
  }
  ?>

  <div class="card-wrapper">
    <?php
    $counter = 0;
    foreach ($students as $student):
      $counter++;
      ?>
      <div class="row-card">
        <!-- 🧍 Kartu Data Siswa -->
        <div class="card card-student">
          <div class="header">
            <table class="header-table">
              <tr>
                <td class="logo-cell">
                  <?php
                  $logoPath = FCPATH . 'uploads/logo/' . $school['logo'];
                  if (!empty($school['logo']) && file_exists($logoPath)):
                    ?>
                    <img src="<?= $logoPath ?>" alt="Logo">
                  <?php endif; ?>
                </td>
                <td class="text-cell">
                  <h3><?= strtoupper($school['name'] ?? 'NAMA SEKOLAH') ?></h3>
                  <h4><?= strtoupper($examName) ?> BERBASIS KOMPUTER</h4>
                  <div class="sub-title">KARTU PESERTA</div>
                </td>
              </tr>
            </table>
          </div>

          <table class="info-table">
            <tr>
              <td rowspan="5" class="photo-box">Foto<br>2x3</td>
              <td style="width:15%;">NIS</td>
              <td>: <?= esc($student['nis']) ?></td>
            </tr>
            <tr>
              <td>Nama</td>
              <td>:
                <?php
                $nama = strtoupper($student['name']);
                if (strlen($nama) > 40) {
                  $nama = substr($nama, 0, strrpos(substr($nama, 0, 40), ' ')) . '--';
                }
                echo esc($nama);
                ?>
              </td>
            </tr>
            <tr>
              <td>Kelas</td>
              <td>: <?= esc($student['class_name'] ?? '-') ?></td>
            </tr>
            <tr>
              <td>Username</td>
              <td>: <?= esc($student['username']) ?></td>
            </tr>
            <tr>
              <td>Password</td>
              <td>: ******</td>
            </tr>

            <tr>
              <td colspan="2"></td>
              <td style="text-align:center; padding-top:6px;">
                Jakarta, <?= date('d-m-Y') ?>
              </td>
            </tr>
            <tr>
              <td colspan="2"></td>
              <td style="text-align:center; padding-top:6px;">
                Kepala <?= esc($school['name'] ?? '') ?>
              </td>
            </tr>
            <tr>
              <td colspan="2"></td>
              <td style="text-align:center; padding-top:6px;"><br>
                <p><strong><?= esc($school['headmaster'] ?? 'Nama Kepala Sekolah') ?></strong></p>
              </td>
            </tr>
          </table>
        </div>

        <!-- 📅 Kartu Jadwal -->
        <div class="card card-schedule">
          <div class="header">
            <div class="sub-title"><strong>JADWAL</strong></div>
          </div>
          <table class="schedule-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Hari / Tgl</th>
                <th>Mata Pelajaran</th>
                <th>Waktu</th>
                <th>Ket</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1;
              foreach ($schedules as $sch): ?>
                <tr>
                  <td class="center"><?= $no++ ?></td>
                  <td><?= hariTanggalIndo($sch['exam_date']) ?></td>
                  <td><?= esc($sch['subject_name']) ?></td>
                  <td><?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?>
                  </td>
                  <td><?= esc($sch['note'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($counter % 4 == 0): ?>
        <div class="page-break"></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>


</body>

</html>