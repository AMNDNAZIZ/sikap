<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">📅 Jadwal Ujian</h4>
      <small class="text-muted">Halaman ini digunakan untuk membuat Jadwal Ujian sebagai pendamping Kartu Peserta
        Ujian.</small>
    </div>
    <a href="<?= site_url('admin/exam-schedule/create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Tambah Jadwal
    </a>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Mata Pelajaran</th>
          <th>Kelas</th>
          <th>Hari & Tanggal</th>
          <th>Waktu</th>
          <th>Keterangan</th>
          <th width="120">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($schedules)): ?>
          <?php foreach ($schedules as $i => $s): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= esc($s['subject_name'] ?? '-') ?></td>
              <td><?= esc($s['class_name'] ?? '-') ?></td>
              <td><?= tanggal_indo($s['exam_date']) ?></td>
              <td><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?></td>
              <td><?= esc($s['description'] ?? '-') ?></td>
              <td>
                <a href="<?= site_url('admin/exam-schedule/edit/' . $s['id']) ?>" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= site_url('admin/exam-schedule/delete/' . $s['id']) ?>"
                  onclick="return confirm('Yakin ingin menghapus jadwal ini?')" class="btn btn-sm btn-danger">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center">Belum ada jadwal ujian</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>