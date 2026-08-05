<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  /* Screen-only styles */
  @media screen {
    .page-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
      padding: 1.5rem 2rem !important;
      border-radius: 15px !important;
      margin-bottom: 1.5rem !important;
      box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2) !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
    }
    
    .page-header h4 {
      margin: 0 !important;
      font-weight: 600 !important;
      font-size: 1.5rem !important;
      display: flex !important;
      align-items: center !important;
      gap: 0.75rem !important;
    }
    
    .page-header .icon-wrapper {
      width: 40px !important;
      height: 40px !important;
      background: rgba(255, 255, 255, 0.2) !important;
      border-radius: 10px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 1.25rem !important;
    }
    
    .action-buttons {
      display: flex !important;
      gap: 0.75rem !important;
    }
    
    .btn {
      padding: 0.5rem 1.25rem !important;
      border-radius: 8px !important;
      font-weight: 500 !important;
      border: none !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 0.5rem !important;
      transition: all 0.2s !important;
      font-size: 0.9rem !important;
    }
    
    .btn-secondary {
      background: rgba(255, 255, 255, 0.2) !important;
      color: white !important;
    }
    
    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.3) !important;
      transform: translateY(-1px) !important;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
      color: white !important;
    }
    
    .btn-danger:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 12px rgba(245, 87, 108, 0.4) !important;
    }
    
    .preview-container {
      background: #f8f9fa !important;
      padding: 2rem !important;
      border-radius: 15px !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }
  }
  
  /* Print and display styles */
  .page {
    background: #fff !important;
    box-shadow: 0 0 10px rgba(0,0,0,0.1) !important;
    padding: 20px !important;
    margin-bottom: 30px !important;
    border-radius: 8px !important;
  }

  .page-break {
    page-break-after: always !important;
  }

  .card-wrapper {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: space-between !important;
    gap: 15px !important;
  }

  .card {
    width: 48% !important;
    border: 3px solid #000 !important;
    border-radius: 12px !important;
    margin-bottom: 20px !important;
    padding: 15px !important;
    background: #fff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
    min-height: 420px !important;
    display: flex !important;
    flex-direction: column !important;
  }

  .header {
    text-align: center !important;
    border-bottom: 3px solid #000 !important;
    margin-bottom: 12px !important;
    padding-bottom: 12px !important;
  }

  .header img {
    width: 60px !important;
    height: 60px !important;
    object-fit: contain !important;
    margin-bottom: 6px !important;
  }

  .header h3 {
    margin: 0 !important;
    font-size: 13pt !important;
    font-weight: bold !important;
    color: #1a1a1a !important;
    line-height: 1.2 !important;
  }

  .header h4 {
    margin: 3px 0 !important;
    font-size: 11pt !important;
    font-weight: bold !important;
    color: #333 !important;
  }

  .sub-title {
    font-size: 10pt !important;
    margin-top: 3px !important;
    margin-bottom: 0 !important;
    font-weight: bold !important;
    color: #444 !important;
  }

  .info-table {
    width: 100% !important;
    font-size: 9.5pt !important;
    border-collapse: collapse !important;
  }

  .info-table td {
    padding: 3px 4px !important;
    vertical-align: middle !important;
    line-height: 1.4 !important;
  }

  .photo-box {
    width: 80px !important;
    text-align: center !important;
    border: 2px solid #333 !important;
    background: #f5f5f5 !important;
    height: 100px !important;
    font-size: 9pt !important;
    color: #666 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
  }

  .signature {
    margin-top: 12px !important;
    text-align: center !important;
    font-size: 10.5pt !important;
  }

  .signature p {
    margin: 3px 0 !important;
  }

  .schedule-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 9.5pt !important;
    margin-top: 0 !important;
  }

  .schedule-table th, .schedule-table td {
    border: 2px solid #000 !important;
    padding: 6px 8px !important;
    text-align: left !important;
    line-height: 1.4 !important;
  }

  .schedule-table th {
    text-align: center !important;
    background: #e9ecef !important;
    font-weight: bold !important;
    color: #000 !important;
    font-size: 10pt !important;
  }

  .center {
    text-align: center !important;
  }
  
  /* Print-specific styles */
  @media print {
    .page-header, .action-buttons, .preview-container {
      display: none !important;
    }
    
    .page {
      box-shadow: none !important;
      margin-bottom: 0 !important;
      padding: 10px !important;
    }
    
    .card {
      box-shadow: none !important;
    }
  }
  
  @media screen and (max-width: 768px) {
    .card {
      width: 100% !important;
    }
    
    .action-buttons {
      flex-direction: column !important;
      width: 100% !important;
    }
    
    .btn {
      width: 100% !important;
      justify-content: center !important;
    }
  }
</style>

<?php
function hariTanggalIndo($dateStr) {
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
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($dateStr);
    return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
}
?>

<div class="container-fluid px-4 py-4">
  <!-- Page Header -->
  <div class="page-header">
    <h4>
      <div class="icon-wrapper">
        <i class="fas fa-id-card"></i>
      </div>
      Pratinjau Kartu Peserta Ujian
    </h4>
    <div class="action-buttons">
      <a href="<?= base_url('admin/kartu-peserta') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Kembali
      </a>
      <a href="<?= site_url('admin/cbt/kartu-peserta/pdf/' . urlencode($examName) . '/' . $students[0]['class_id']) ?>" 
         class="btn btn-danger" target="_blank">
        <i class="fas fa-file-pdf"></i>
        Cetak ke PDF
      </a>
    </div>
  </div>

  <!-- Preview Container -->
  <div class="preview-container">
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
                  <td width="90" style="text-align:center; vertical-align:middle; padding:8px;">
                    <?php if (!empty($school['logo'])): ?>
                      <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" 
                           alt="Logo" style="width:70px;height:70px;object-fit:contain;">
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center; vertical-align:middle; padding:8px;">
                    <h3 style="margin:0; font-size:16pt; font-weight:bold; line-height:1.3; letter-spacing:0.5px;">
                      <?= strtoupper($school['name'] ?? 'NAMA SEKOLAH') ?>
                    </h3>
                    <h4 style="margin:5px 0 3px 0; font-size:12pt; font-weight:bold; letter-spacing:0.3px;">
                      <?= strtoupper($examName) ?> BERBASIS KOMPUTER
                    </h4>
                    <div style="font-size:11pt; margin-top:3px; font-weight:bold; letter-spacing:1px;">
                      KARTU PESERTA
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <table style="width:100%; border-collapse:collapse; margin-top:8px;">
              <tr>
                <td rowspan="7" style="width:110px; padding:8px; vertical-align:top;">
                  <div style="width:95px; height:120px; border:2px solid #333; background:#f8f9fa; display:flex; align-items:center; justify-content:center; border-radius:4px; font-size:11pt; color:#666; font-weight:500;">
                    Foto<br>3x4
                  </div>
                </td>
                <td colspan="2" style="padding:6px 8px; vertical-align:middle;">
                  <table style="width:100%; border-collapse:collapse;">
                    <tr>
                      <td style="width:90px; font-size:11pt; font-weight:600; color:#333; padding:3px 0;">NIS</td>
                      <td style="font-size:11pt; font-weight:500; padding:3px 0;">: <?= esc($student['nis']) ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="width:95px; font-size:11pt; font-weight:600; color:#333; padding:3px 8px; vertical-align:top;">Nama</td>
                <td style="font-size:11pt; font-weight:500; padding:3px 0; vertical-align:top;">: <?= esc(strtoupper($student['name'])) ?></td>
              </tr>
              <tr>
                <td style="font-size:11pt; font-weight:600; color:#333; padding:3px 8px;">Kelas</td>
                <td style="font-size:11pt; font-weight:500; padding:3px 0;">: <?= esc($student['class_name'] ?? '-') ?></td>
              </tr>
              <tr>
                <td style="font-size:11pt; font-weight:600; color:#333; padding:3px 8px;">Username</td>
                <td style="font-size:11pt; font-weight:500; padding:3px 0;">: <?= esc($student['username']) ?></td>
              </tr>
              <tr>
                <td style="font-size:11pt; font-weight:600; color:#333; padding:3px 8px;">Password</td>
                <td style="font-size:11pt; font-weight:500; padding:3px 0;">: <?= esc($student['plain_password'] ?? '-') ?></td>
              </tr>
              <tr>
                <td style="font-size:11pt; font-weight:600; color:#333; padding:3px 8px;">Ruang</td>
                <td style="font-size:11pt; font-weight:500; padding:3px 0;">: <?= esc($student['room'] ?? '-') ?></td>
              </tr>
              <tr>
                <td colspan="2" style="padding:15px 8px 0 8px;">
                  <div style="text-align:right; font-size:10pt;">
                    <div style="margin-bottom:3px;">Jakarta, <?= date('d-m-Y') ?></div>
                    <div style="margin-bottom:3px;">Kepala <?= esc($school['name'] ?? '') ?></div>
                    <div style="margin-top:40px; font-weight:bold;">
                      <u><?= esc($school['headmaster'] ?? 'Nama Kepala Sekolah') ?></u>
                    </div>
                  </div>
                </td>
              </tr>
            </table>
          </div>
          
          <div class="card col-md-6">
            <div class="header">
              <div style="font-size:12pt; margin-bottom:8px; font-weight:bold; letter-spacing:1px;">JADWAL UJIAN</div>
            </div>
            <table class="schedule-table">
              <thead>
                <tr>
                  <th style="width:7%;">No</th>
                  <th style="width:28%;">Hari / Tanggal</th>
                  <th style="width:30%;">Mata Pelajaran</th>
                  <th style="width:18%;">Waktu</th>
                  <th style="width:17%;">Keterangan</th>
                </tr>
              </thead>
              <tbody>
              <?php 
              $no = 1;
              // 🔹 FIX: Filter jadwal berdasarkan agama siswa
              $studentReligion = strtolower(trim($student['religion'] ?? ''));
              
              foreach ($schedules as $sch): 
                // Skip mata pelajaran agama yang tidak sesuai dengan agama siswa
                $subjectType = strtolower(trim($sch['subject_type'] ?? ''));
                $scheduleReligion = strtolower(trim($sch['religion'] ?? ''));
                
                if ($subjectType === 'agama' && $scheduleReligion !== $studentReligion) {
                  continue; // Skip jadwal agama yang tidak sesuai
                }
              ?>
                <tr>
                  <td class="center" style="font-weight:500;"><?= $no++ ?></td>
                  <td style="font-size:9pt; line-height:1.4;"><?= hariTanggalIndo($sch['exam_date']) ?></td>
                  <td style="font-weight:500;"><?= esc($sch['subject_name']) ?></td>
                  <td class="center" style="white-space:nowrap; font-size:9.5pt;"><?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?></td>
                  <td style="font-size:9pt;"><?= esc($sch['note'] ?? '-') ?></td>
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
  </div>
</div>

<?= $this->endSection() ?>
