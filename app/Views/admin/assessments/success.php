<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📊 Rekap Nilai</h3>

<!-- Info Penilaian -->
<table class="table table-sm table-bordered w-auto mb-4">
  <tr>
    <th style="width:200px;">Mata Pelajaran</th>
    <td><?= esc($info['subject']) ?></td>
  </tr>
  <tr>
    <th>Materi</th>
    <td><?= esc($info['title']) ?></td>
  </tr>
  <tr>
    <th>Semester</th>
    <td><?= esc($info['semester']) ?></td>
  </tr>
  <tr>
    <th>Metode</th>
    <td><?= esc($info['method'] ?? '-') ?></td>
  </tr>
</table>

<!-- Tabel Nilai -->
<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:50px;">#</th>
        <th>Nama Siswa</th>
        <th style="width:120px;">Nilai</th>
        <th style="width:150px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($scores)): ?>
        <?php $no=1; foreach ($scores as $s): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($s['student_name']) ?></td>
            <td class="text-center"><?= esc($s['score']) ?></td>
            <td>
              <a href="<?= site_url("admin/assessments/edit/{$s['student_id']}/{$type}?id={$info['id']}&semester={$info['semester']}&method={$info['method']}") ?>"
                 class="btn btn-sm btn-warning">✏️ Edit</a>
            </td>
          </tr>
        <?php endforeach ?>
      <?php else: ?>
        <tr>
          <td colspan="4" class="text-center text-muted">Belum ada nilai disimpan.</td>
        </tr>
      <?php endif ?>
    </tbody>
  </table>
</div>

<!-- Tombol Aksi -->
<div class="mt-3">
  <a href="<?= site_url('admin/assessments') ?>" class="btn btn-secondary">⬅️ Kembali ke Daftar Penilaian</a>

  <?php if ($type === 'formatif'): ?>
    <a href="<?= site_url("admin/assessments/deleteBatch/formatif/{$info['id']}/{$info['method']}") ?>"
       class="btn btn-danger"
       onclick="return confirm('Yakin ingin menghapus semua nilai formatif ini?')">
      🗑 Hapus Semua
    </a>
  <?php elseif ($type === 'sumatif'): ?>
    <a href="<?= site_url("admin/assessments/deleteBatch/sumatif/{$info['id']}/{$info['semester']}/{$info['method']}") ?>"
       class="btn btn-danger"
       onclick="return confirm('Yakin ingin menghapus semua nilai sumatif ini?')">
      🗑 Hapus Semua
    </a>
  <?php elseif ($type === 'final'): ?>
    <a href="<?= site_url("admin/assessments/deleteBatch/final/{$info['id']}/{$info['semester']}") ?>"
       class="btn btn-danger"
       onclick="return confirm('Yakin ingin menghapus semua nilai ujian akhir ini?')">
      🗑 Hapus Semua
    </a>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
