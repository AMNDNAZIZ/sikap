<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .header {
    text-align: center;
    margin-bottom: 10px;
    position: relative;
  }

  .header img {
    position: absolute;
    left: 25px;
    top: 0;
    width: 90px;
    height: 90px;
    object-fit: contain;
  }

  .header h4,
  .header h5 {
    margin: 0;
    line-height: 1.4;
  }

  hr {
    border: 1px solid #000;
    margin-top: 5px;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
  }

  th,
  td {
    border: 1px solid #000;
    padding: 6px;
  }

  th {
    background: #f5f5f5;
    text-align: center;
  }

  .no-border td {
    border: none;
    padding: 3px 0;
  }

  .signature {
    margin-top: 30px;
    text-align: right;
  }

  @media print {
    .no-print {
      display: none;
    }
  }
</style>

<div class="header">
  <?php if (!empty($school['logo'])): ?>
    <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo Sekolah">
  <?php endif; ?>
  <h4>DAFTAR HADIR <?= strtoupper($examName ?? 'PENILAIAN AKHIR SEMESTER') ?></h4>
  <h4><?= strtoupper($school['name'] ?? 'SMP ISLAM TAMBORA') ?></h4>
  <h5>TAHUN PELAJARAN <?= esc($academicYear ?? '2025/2026') ?></h5>
  <hr>
</div>

<table class="no-border">
  <tr>
    <td width="25%">Kelas</td>
    <td>: <?= esc($room) ?></td>
  </tr>
  <tr>
    <td>Mata Pelajaran</td>
    <td>: <?= esc($subject ?? '...........................................') ?></td>
  </tr>
  <tr>
    <td>Hari / Tanggal</td>
    <td>: <?= tanggal_indo($exam_date ?? date('Y-m-d')) ?></td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style="width:5%;">NO</th>
      <th style="width:15%;">NIS</th>
      <th>NAMA SISWA</th>
      <th style="width:7%;">L/P</th>
      <th style="width:15%;">KELAS</th>
      <th style="width:25%;">TANDA TANGAN</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1;
    foreach ($students as $s): ?>
      <tr>
        <td class="text-center"><?= $no ?></td>
        <td class="text-center"><?= esc($s['nis']) ?></td>
        <td><?= esc(strtoupper($s['name'])) ?></td>
        <td class="text-center"><?= esc($s['gender']) ?></td>
        <td class="text-center"><?= esc($s['class_name']) ?></td>
        <td><?= $no++ ?>.</td>
      </tr>
    <?php endforeach; ?>

    <?php for ($i = $no; $i <= 5; $i++): ?>
      <tr>
        <td class="text-center"><?= $i ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
    <?php endfor; ?>
  </tbody>
</table>

<div class="signature">
  <p>Jakarta, <?= strftime('%d %B %Y', strtotime(date('Y-m-d'))) ?></p>
  <p>Pengawas Kelas <?= esc($room) ?></p>
  <br><br><br>
  <p>..........................................</p>
</div>

<div class="text-center mt-3 no-print">
  <a href="<?= site_url('admin/cbt/attendance/printPdf/' . $examId . '/' . $class_id) ?>" class="btn btn-danger"
    target="_blank">
    <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
  </a>
</div>


<?= $this->endSection() ?>