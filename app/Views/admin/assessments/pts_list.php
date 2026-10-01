<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <h3 class="mb-4">📝 Penilaian Tengah Semester (PTS) - <?= esc($class['name'] ?? '-') ?> / <?= esc($subject['name'] ?? '-') ?></h3>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="alert alert-info">
    📌 Nilai PTS disimpan terpisah dan <strong>tidak</strong> ikut dalam perhitungan nilai rapor akhir
    (formatif/sumatif/ujian akhir). Nilai PTS dipakai untuk Rapor PTS.
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:160px;">Semester</th>
            <th>Status</th>
            <th style="width:320px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (['1' => 'Semester 1 (Ganjil)', '2' => 'Semester 2 (Genap)'] as $sem => $label): ?>
            <?php $info = $status[$sem] ?? ['jumlah' => 0, 'siswa_terisi' => 0]; ?>
            <tr>
              <td><strong><?= esc($label) ?></strong></td>
              <td>
                <?php if (!empty($info['jumlah']) && $info['jumlah'] > 0): ?>
                  ✅ Nilai terisi: <?= esc($info['siswa_terisi']) ?> siswa
                <?php else: ?>
                  ⏳ Belum ada nilai PTS
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($info['jumlah']) && $info['jumlah'] > 0): ?>
                  <a href="<?= site_url("admin/assessments/viewScores/pts/{$subjectId}/{$sem}?class_id={$classId}") ?>"
                     class="btn btn-sm btn-info">📋 Lihat Nilai</a>
                <?php endif; ?>
                <a href="<?= site_url("admin/assessments/input/{$classId}/{$subjectId}/pts") ?>?semester=<?= $sem ?>"
                   class="btn btn-sm btn-success">➕ Input Manual</a>
                <a href="<?= site_url("admin/cbt/pts-import?class_id={$classId}&subject_id={$subjectId}&semester={$sem}") ?>"
                   class="btn btn-sm btn-outline-primary">📥 Impor dari CBT</a>
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
