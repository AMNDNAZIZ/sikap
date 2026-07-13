<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
// Helper Function untuk generate URL tab (didefinisikan di atas agar bisa dipakai segera)
function buildTabUrl($type, $entityId, $method, $semester = null) {
    $base = "admin/assessments/viewScores/{$type}/{$entityId}";
    if ($type === 'sumatif' && $semester) {
        $base .= "/{$semester}";
    }
    $base .= "/{$method}";
    return site_url($base);
}
?>

<div class="container-fluid">
  <h3 class="mb-4">📊 Rekap Nilai (<?= esc(ucfirst($type)) ?>)</h3>

  <!-- Flash Messages (Success, Error, Info) -->
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
  <?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('info')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Info Penilaian -->
  <div class="card mb-4">
    <div class="card-body">
      <table class="table table-sm table-bordered w-100">
        <tbody>
          <?php if ($type === 'formatif' && isset($material)): ?>
            <tr>
              <th style="width:200px;">Mata Pelajaran</th>
              <td><?= esc($subject['name'] ?? '-') ?></td>
            </tr>
            <tr>
              <th>Materi</th>
              <td><?= esc($material['title']) ?></td>
            </tr>
            <tr>
              <th>Semester</th>
              <td><?= esc(ucfirst($material['semester'] ?? '-')) ?></td>
            </tr>
            <tr>
              <th>Metode</th>
              <td><?= esc(ucfirst($selected_method)) ?></td>
            </tr>
          <?php elseif ($type === 'sumatif' && isset($subject)): ?>
            <tr>
              <th style="width:200px;">Mata Pelajaran</th>
              <td><?= esc($subject['name']) ?></td>
            </tr>
            <tr>
              <th>Semester</th>
              <td><?= esc(ucfirst($semester)) ?></td>
            </tr>
            <tr>
              <th>Metode</th>
              <td><?= esc(ucfirst($selected_method)) ?></td>
            </tr>
            <tr>
              <th>Tahun Ajaran</th>
              <td><?= esc($activeYear['name'] ?? $activeYear['year'] ?? '-') ?></td>
            </tr>
          <?php elseif ($type === 'final' && isset($subject)): ?>
            <tr>
              <th style="width:200px;">Mata Pelajaran</th>
              <td><?= esc($subject['name']) ?></td>
            </tr>
            <tr>
              <th>Tahun Ajaran</th>
              <td><?= esc($activeYear['name'] ?? $activeYear['year'] ?? '-') ?></td>
            </tr>
          <?php else: ?>
            <tr>
              <td colspan="2" class="text-center text-muted">Informasi penilaian tidak lengkap.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab Metode (Hanya jika ada lebih dari 1 type dan bukan final) -->
  <?php if (!empty($types) && $type !== 'final'): ?>
    <ul class="nav nav-tabs mb-3" id="methodTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link <?= ($selected_method === 'all' || empty($selected_method)) ? 'active' : '' ?>"
           href="<?= buildTabUrl($type, ($material['id'] ?? $subject['id']), 'all', $semester) ?>"
           id="all-tab" role="tab">
          Semua (<?= count($types) ?> metode)
        </a>
      </li>
      <?php foreach ($types as $t): ?>
        <li class="nav-item">
          <a class="nav-link <?= $selected_method === $t ? 'active' : '' ?>"
             href="<?= buildTabUrl($type, ($material['id'] ?? $subject['id']), $t, $semester) ?>"
             id="<?= esc($t) ?>-tab" role="tab">
            <?= esc(ucfirst($t)) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <!-- Tabel Nilai -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Daftar Nilai Siswa</h5>
      <small class="text-muted">Jumlah: <?= count($scores ?? []) ?> siswa</small>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:50px;">#</th>
              <th>Nama Siswa</th>
              <th style="width:100px;">Nilai</th>
              <th style="width:190px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($scores)): ?>
              <?php $no = 1; foreach ($scores as $s): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($s['student_name'] ?? 'N/A') ?></td>
                  <td class="text-center fw-bold"><?= esc($s['score']) ?></td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <a href="<?= site_url("admin/assessments/edit/{$s['id']}/{$type}?redirect_url=" . urlencode(current_url())) ?>"
                         class="btn btn-warning" title="Edit Nilai">
                        ✏️ Edit
                      </a>
                      <a href="<?= site_url("admin/assessments/deleteOne/{$s['id']}/{$type}") ?>"
                         class="btn btn-danger"
                         onclick="return confirm('Yakin ingin menghapus nilai siswa <?= esc($s['student_name'] ?? '-') ?>?')"
                         title="Hapus Nilai">
                        🗑 Hapus
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                  <strong>Belum ada nilai untuk kombinasi ini.</strong><br>
                  <small>
                    Jika baru disimpan, cek apakah:<br>
                    • Semua siswa sudah dinilai sebelumnya.<br>
                    • Filter semester/metode/tahun ajaran sesuai.<br>
                    • Data tersimpan di database (hubungi admin jika perlu).
                  </small>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tombol Aksi -->
  <div class="mt-4">
    <div class="d-flex justify-content-between">
      <div>
       <?php if ($type === 'formatif' && isset($class_id, $subject)): ?>
          <a href="<?= site_url("admin/assessments/formatifList/{$class_id}/{$subject['id']}") ?>" class="btn btn-secondary">
            ⬅️ Kembali ke Daftar Materi
          </a>
        <?php elseif ($type === 'sumatif' && isset($subject, $class_id)): ?>
          <a href="<?= site_url("admin/assessments/sumatifList/{$class_id}/{$subject['id']}") ?>" class="btn btn-secondary">
            ⬅️ Kembali ke Daftar Sumatif
          </a>
        <?php elseif ($type === 'final' && isset($subject, $class_id)): ?>
          <a href="<?= site_url("admin/assessments/finalList/{$class_id}/{$subject['id']}") ?>" class="btn btn-secondary">
            ⬅️ Kembali ke Daftar Ujian Akhir
          </a>
        <?php else: ?>
          <a href="<?= site_url('admin/assessments') ?>" class="btn btn-secondary">⬅️ Kembali</a>
        <?php endif; ?>

      </div>

      <div>
        <?php if (!empty($scores)): ?>
          <?php if ($type === 'formatif'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/formatif/{$material['id']}/{$selected_method}") ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai formatif untuk materi dan metode ini?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php elseif ($type === 'sumatif'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/sumatif/{$subject['id']}/{$semester}/{$selected_method}") ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai sumatif untuk semester dan metode ini?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php elseif ($type === 'final'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/final/{$subject['id']}") ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai ujian akhir untuk semester ini?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>

<!-- Script Tambahan (Opsional: Untuk tab JS jika perlu active state dinamis) -->
<?= $this->section('scripts') ?>
<script>
  // Auto-dismiss alerts setelah 5 detik
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);
</script>
<?= $this->endSection() ?>
