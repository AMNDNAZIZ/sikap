<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <h3 class="mb-4">📊 Penilaian Final - <?= esc($class['name'] ?? '-') ?> / <?= esc($subject['name'] ?? '-') ?></h3>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Status</th>
            <th style="width:250px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <?php if (!empty($status) && $status['jumlah'] > 0): ?>
                ✅ Nilai terisi: <?= esc($status['siswa_terisi']) ?> siswa
              <?php else: ?>
                ⏳ Belum ada nilai final
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($status) && $status['jumlah'] > 0): ?>
                <a href="<?= site_url("admin/assessments/viewScores/final/{$subjectId}") ?>" class="btn btn-sm btn-info">📋 Lihat Nilai</a>
              <?php endif; ?>
              <a href="<?= site_url("admin/assessments/input/{$classId}/{$subjectId}/final") ?>" class="btn btn-sm btn-success">➕ Input Nilai</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    <a href="<?= site_url('admin/assessments') ?>" class="btn btn-secondary">⬅️ Kembali</a>
  </div>
</div>

<?= $this->endSection() ?>
