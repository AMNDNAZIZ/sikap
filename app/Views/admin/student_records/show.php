<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-0">Riwayat: <?= esc($student['name']) ?></h2>
    <span class="text-muted">NIS: <?= esc($student['nis']) ?></span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('admin/student-records') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar
    </a>
    <a href="<?= site_url('admin/grades/tracking?student_id=' . $student['id']) ?>" class="btn btn-outline-primary">
      <i class="bi bi-activity me-1"></i> Tracking Semua Tahun
    </a>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>Tahun Ajaran</th>
        <th>Kelas</th>
        <th>Status</th>
        <th>Catatan</th>
        <th class="text-center" style="width: 180px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>
      <?php else: ?>
        <?php foreach ($records as $rec): ?>
          <tr>
            <td><strong><?= esc($rec['year']) ?></strong></td>
            <td><?= esc($rec['class_name'] ?? '-') ?></td>
            <td><span class="badge bg-<?= $rec['status'] === 'aktif' ? 'success' : ($rec['status']==='lulus' ? 'primary' : 'secondary') ?>">
              <?= esc(ucfirst($rec['status'])) ?></span>
            </td>
            <td><?= esc($rec['note'] ?? '-') ?></td>
            <td class="text-center">
              <a href="<?= site_url('admin/grades/tracking?student_id=' . $student['id'] . '&year_id=' . $rec['academic_year_id']) ?>"
                 class="btn btn-sm btn-outline-success"
                 title="Tracking Nilai Semester T.A. <?= esc($rec['year']) ?>">
                <i class="bi bi-activity me-1"></i> Tracking Nilai
              </a>
            </td>
          </tr>
        <?php endforeach ?>
      <?php endif ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>
