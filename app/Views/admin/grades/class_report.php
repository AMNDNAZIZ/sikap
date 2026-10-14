<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Rekap Nilai Raport - <?= $subject['name'] ?> (Semester <?= ucfirst($semester) ?>)</h2>

<div class="table-responsive">
<table class="table table-bordered align-middle">
  <thead class="table-light">
    <tr>
      <th>Siswa</th>
      <th>Formatif</th>
      <th>Sumatif</th>
      <th>Ujian Akhir</th>
      <th>Raport</th>
      <th width="100">Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= esc($r['student']['name']) ?></td>
      <td><?= esc($r['scores']['formative']) ?></td>
      <td><?= esc($r['scores']['summative']) ?></td>
      <td><?= esc($r['scores']['final_exam']) ?></td>
      <td><strong><?= esc($r['scores']['report']) ?></strong></td>
      <td>
        <a href="<?= site_url('admin/grades/student-report/'.$r['student']['id']) ?>" class="btn btn-sm btn-info">📄 Detail</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<?= $this->endSection() ?>
