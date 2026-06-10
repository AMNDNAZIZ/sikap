<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>Rekap Absensi Kelas <?= esc($class['name']) ?></h4>
<p>
  Tahun Ajaran: <b><?= esc($activeYear['year']) ?></b><br>
  Periode: 
  <?php if ($periode === 'semester1'): ?>
    Semester 1 (Juli – Desember <?= date('Y', strtotime($activeYear['start_date'])) ?>)
  <?php elseif ($periode === 'semester2'): ?>
    Semester 2 (Januari – Juni <?= date('Y', strtotime($activeYear['end_date'])) ?>)
  <?php else: ?>
    Satu Tahun (<?= date('d M Y', strtotime($activeYear['start_date'])) ?> – <?= date('d M Y', strtotime($activeYear['end_date'])) ?>)
  <?php endif; ?>
</p>

<div class="mb-3">
  <a href="<?= base_url('admin/attendance') ?>" class="btn btn-secondary">Kembali</a>
  <a href="<?= base_url("admin/attendance/rekapPdf?class_id={$class['id']}&periode={$periode}") ?>" class="btn btn-danger" target="_blank">Export PDF</a>
  <a href="<?= base_url("admin/attendance/rekapExcel?class_id={$class['id']}&periode={$periode}") ?>" class="btn btn-success">Export Excel</a>
</div>

<table class="table table-bordered table-striped">
  <thead class="table-dark">
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>JK</th>
      <th>H</th>
      <th>I</th>
      <th>S</th>
      <th>A</th>
      <th>% Hadir</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; foreach ($rekapSiswa as $r): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= esc($r['student']['nis'] ?? '-') ?></td>
        <td><?= esc($r['student']['name']) ?></td>
        <td><?= esc($r['student']['gender']) ?></td>
        <td><?= $r['H'] ?></td>
        <td><?= $r['I'] ?></td>
        <td><?= $r['S'] ?></td>
        <td><?= $r['A'] ?></td>
        <td><?= $r['percent'] ?>%</td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
