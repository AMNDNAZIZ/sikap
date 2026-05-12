<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .page {
    background: #fff;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    padding: 15px;
    margin-bottom: 30px;
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
    margin-bottom: 15px;
    padding: 6px;
    background: #fff;
  }

  .header {
    text-align: center;
    border-bottom: 1px solid #000;
    margin-bottom: 4px;
  }

  .header img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 6px;
  }

  .header h3 {
    margin: 0;
    font-size: 14pt;
    font-weight: bold;
  }

  .header h4 {
    margin: 0;
    font-size: 12pt;
    font-weight: bold;
  }

  .sub-title {
    font-size: 11pt;
    margin-top: 2px;
    margin-bottom: 4px;
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
    width: 20%;
    text-align: center;
    border: 1px solid #000;
    background: #e9edf1;
    height: 90px;
    font-size: 9pt;
    color: #777;
  }

  .signature {
    margin-top: 10px;
    text-align: center;
    font-size: 10pt;
  }

  .signature p {
    margin: 2px 0;
  }

  .schedule-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9.5pt;
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
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
  ];
  $timestamp = strtotime($dateStr);
  return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[(int) date('m', $timestamp)] . ' ' . date('Y', $timestamp);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0">📘 Pratinjau Kartu Peserta Ujian</h4>
  <a href="<?= base_url('admin/kartu-peserta') ?>" class="btn btn-secondary">⬅ Kembali</a>
  <a href="<?= site_url('admin/cbt/kartu-peserta/pdf/' . urlencode($examName) . '/' . $students[0]['class_id']) ?>"
    class="btn btn-danger btn-sm" target="_blank">
    <i class="bi bi-file-pdf"></i> Cetak ke PDF
  </a>
</div>

<div class="page">
  <div class="card-wrapper">
    <?php
    $counter = 0;
    foreach ($students as $student):
      $counter++;
      ?>
      <div class="card col-md-6">
        <div class="header">
          <table width="100%" style="border-collapse: collapse;">
            <tr>
              <td width="70" style="text-align:center; vertical-align:middle;">
                <?php if (!empty($school['logo'])): ?>
                  <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo"
                    style="width:60px;height:60px;object-fit:contain;">
                <?php endif; ?>
              </td>
              <td style="text-align:center; vertical-align:middle;">
                <h3 style="margin:0; font-size:14pt; font-weight:bold;">
                  <?= strtoupper($school['name'] ?? 'NAMA SEKOLAH') ?>
                </h3>
                <h4 style="margin:0; font-size:12pt; font-weight:bold;">
                  <?= strtoupper($examName) ?> BERBASIS KOMPUTER
                </h4>
                <div class="sub-title" style="font-size:11pt; margin-top:2px;">
                  <strong>KARTU PESERTA</strong>
                </div>
              </td>
            </tr>
          </table>
        </div>


        <table class="info-table">
          <tr>
            <td rowspan="5" class="photo-box">Foto<br>3x4</td>
            <td style="width:15%;">NIS</td>
            <td>: <?= esc($student['nis']) ?></td>
            <td></td>
          </tr>
          <tr>
            <td>Nama</td>
            <td colspan="2">: <?= esc(strtoupper($student['name'])) ?></td>
          </tr>
          <tr>
            <td>Kelas</td>
            <td colspan="2">: <?= esc($student['class_name'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Username</td>
            <td colspan="2">: <?= esc($student['username']) ?></td>
          </tr>
          <tr>
            <td>Password</td>
            <td colspan="2">: ******</td>
          </tr>

          <tr>
            <td></td>
            <td></td>
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
            <td style="text-align:center; padding-top:6px;"><br><br>
              <p><strong><?= esc($school['headmaster'] ?? 'Nama Kepala Sekolah') ?></strong></p>
            </td>
          </tr>
        </table>
      </div>
      <div class="card col-md-6">
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
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1;
            foreach ($schedules as $sch): ?>
              <tr>
                <td class="center"><?= $no++ ?></td>
                <td><?= hariTanggalIndo($sch['exam_date']) ?></td>
                <td><?= esc($sch['subject_name']) ?></td>
                <td><?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?></td>
                <td><?= esc($sch['note'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($counter % 4 == 0): ?>
        <div class="page-break"></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<?= $this->endSection() ?>