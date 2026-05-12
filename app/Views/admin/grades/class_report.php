<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Rekap Nilai Raport - <?= $subject['name'] ?> (Semester <?= ucfirst($semester) ?>)</h2>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Siswa</th>
      <th>Formatif</th>
      <th>Sumatif</th>
      <th>Ujian Akhir</th>
      <th>Raport</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= $r['student']['name'] ?></td>
      <td><?= $r['scores']['formative'] ?></td>
      <td><?= $r['scores']['summative'] ?></td>
      <td><?= $r['scores']['final_exam'] ?></td>
      <td><strong><?= $r['scores']['report'] ?></strong></td>
      <td>
        <a href="<?= site_url('admin/grades/student-report/'.$r['student']['id']) ?>" class="btn btn-sm btn-info">📄 Detail</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?= $this->endSection() ?>
