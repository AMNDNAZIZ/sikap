<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Riwayat: <?= esc($student['name']) ?> (NIS: <?= esc($student['nis']) ?>)</h2>

<a href="<?= base_url('admin/student-records') ?>" class="btn btn-light mb-3">← Kembali ke daftar</a>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <th>Tahun Ajaran</th>
        <th>Kelas</th>
        <th>Status</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
        <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat.</td></tr>
      <?php else: ?>
        <?php foreach ($records as $rec): ?>
          <tr>
            <td><?= esc($rec['year']) ?></td>
            <td><?= esc($rec['class_name'] ?? '-') ?></td>
            <td><span class="badge bg-<?= $rec['status'] === 'aktif' ? 'success' : ($rec['status']==='lulus' ? 'primary' : 'secondary') ?>">
              <?= esc(ucfirst($rec['status'])) ?></span>
            </td>
            <td><?= esc($rec['note'] ?? '') ?></td>
          </tr>
        <?php endforeach ?>
      <?php endif ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>
