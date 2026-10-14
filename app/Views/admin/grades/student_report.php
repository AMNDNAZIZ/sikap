<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Raport Siswa - <?= $student['name'] ?> (Semester <?= ucfirst($semester) ?>)</h2>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
  <thead class="table-light">
    <tr>
      <th>Mata Pelajaran</th>
      <th>Formatif</th>
      <th>Sumatif</th>
      <th>Ujian Akhir</th>
      <th>Nilai Raport</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= esc($r['subject']['name']) ?></td>
      <td><?= esc($r['scores']['formative']) ?></td>
      <td><?= esc($r['scores']['summative']) ?></td>
      <td><?= esc($r['scores']['final_exam']) ?></td>
      <td><strong><?= esc($r['scores']['report']) ?></strong></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<button class="btn btn-success">🖨️ Cetak Raport</button>

<?= $this->endSection() ?>
