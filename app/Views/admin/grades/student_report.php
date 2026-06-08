<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Raport Siswa - <?= $student['name'] ?> (Semester <?= ucfirst($semester) ?>)</h2>

<table class="table table-striped">
  <thead>
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
      <td><?= $r['subject']['name'] ?></td>
      <td><?= $r['scores']['formative'] ?></td>
      <td><?= $r['scores']['summative'] ?></td>
      <td><?= $r['scores']['final_exam'] ?></td>
      <td><strong><?= $r['scores']['report'] ?></strong></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<button class="btn btn-success">🖨️ Cetak Raport</button>

<?= $this->endSection() ?>
