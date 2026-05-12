<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <h3 class="mb-4">📊 Penilaian Sumatif - <?= esc($class['name'] ?? '-') ?> / <?= esc($subject['name'] ?? '-') ?></h3>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:120px;">Semester</th>
            <th>Status</th>
            <th style="width:200px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ([1,2] as $sem): ?>
            <tr>
              <td>Semester <?= esc($sem) ?></td>
              <td>
                <?php 
                  // bangun keterangan metode yang ada untuk semester ini
                  $parts = [];
                  foreach (['tulis','penugasan'] as $m) {
                    $count = $status[$sem][$m] ?? 0;
                    if ($count > 0) {
                      $parts[] = ucfirst($m) . " ✅ ({$count} siswa)";
                    }
                  }
                  echo !empty($parts) ? implode(', ', $parts) : '⏳ Belum ada nilai';
                ?>
              </td>
              <td>
                <?php if (!empty($parts)): ?>
                  <!-- tombol lihat nilai untuk semester ini (menampilkan semua metode atau pilih metode di viewScores) -->
                  <a href="<?= site_url("admin/assessments/viewScores/sumatif/{$subjectId}/{$sem}/all") ?>"
                     class="btn btn-sm btn-info">📋 Lihat Nilai</a>
                <?php endif; ?>

                <!-- tombol input nilai: kirim semester via query string -->
                <a href="<?= site_url("admin/assessments/input/{$classId}/{$subjectId}/sumatif?semester={$sem}") ?>"
                   class="btn btn-sm btn-success">➕ Input Nilai</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    <a href="<?= site_url('admin/assessments') ?>" class="btn btn-secondary">⬅️ Kembali</a>
  </div>
</div>

<?= $this->endSection() ?>
