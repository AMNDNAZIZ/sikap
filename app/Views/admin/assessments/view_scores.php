<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
// Helper Function untuk generate URL tab (didefinisikan di atas agar bisa dipakai segera)
function buildTabUrl($type, $entityId, $method, $semester = null, $classId = null) {
    $base = "admin/assessments/viewScores/{$type}/{$entityId}";
    if ($type === 'sumatif' && $semester) {
        $base .= "/{$semester}";
    }
    $base .= "/{$method}";
    if (!empty($classId)) {
        $base .= "?class_id={$classId}";
    }
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
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <table class="table table-sm table-bordered w-100 mb-0">
        <tbody>
          <?php if ($type === 'formatif' && isset($material)): ?>
            <tr>
              <th style="width:200px;">Mata Pelajaran</th>
              <td><?= esc($subject['name'] ?? '-') ?></td>
            </tr>
            <tr>
              <th>Kelas</th>
              <td>
                <span class="badge bg-primary fs-6"><?= esc($currentClass['name'] ?? '-') ?></span>
              </td>
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
              <th>Kelas</th>
              <td>
                <span class="badge bg-primary fs-6"><?= esc($currentClass['name'] ?? '-') ?></span>
              </td>
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
              <th>Kelas</th>
              <td>
                <span class="badge bg-primary fs-6"><?= esc($currentClass['name'] ?? '-') ?></span>
              </td>
            </tr>
            <tr>
              <th>Tahun Ajaran</th>
              <td><?= esc($activeYear['name'] ?? $activeYear['year'] ?? '-') ?></td>
            </tr>
          <?php elseif ($type === 'pts' && isset($subject)): ?>
            <tr>
              <th style="width:200px;">Mata Pelajaran</th>
              <td><?= esc($subject['name']) ?></td>
            </tr>
            <tr>
              <th>Kelas</th>
              <td>
                <span class="badge bg-primary fs-6"><?= esc($currentClass['name'] ?? '-') ?></span>
              </td>
            </tr>
            <tr>
              <th>Semester</th>
              <td>Semester <?= esc($semester ?? '-') ?> <span class="badge bg-warning text-dark ms-1">PTS</span></td>
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

  <!-- Form Hapus Masal (Pilih Banyak) -->
  <form id="bulkDeleteForm" action="<?= site_url("admin/assessments/deleteSelected/{$type}") ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect_url" value="<?= esc(current_url(true)) ?>">

    <!-- Tabel Nilai -->
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <h5 class="mb-0">Daftar Nilai Siswa (Urut A-Z)</h5>
          <span class="badge bg-info text-dark">Jumlah: <?= count($scores ?? []) ?> siswa</span>
          <span id="selectedCountBadge" class="badge bg-warning text-dark d-none">
            <span id="selectedCount">0</span> dipilih
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" id="btnDeleteSelected" class="btn btn-sm btn-danger d-none" onclick="confirmBulkDelete()">
            🗑️ Hapus Terpilih (<span id="btnSelectedCount">0</span>)
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;" class="text-center">
                  <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Pilih Semua">
                </th>
                <th style="width:50px;">#</th>
                <th>Nama Siswa</th>
                <?php if ($selected_method === 'all'): ?>
                  <th style="width:120px;">Metode</th>
                <?php endif; ?>
                <th style="width:100px;">Nilai</th>
                <th style="width:190px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($scores)): ?>
                <?php $no = 1; foreach ($scores as $s): ?>
                  <tr>
                    <td class="text-center">
                      <input type="checkbox" name="ids[]" value="<?= esc($s['id']) ?>" class="form-check-input score-checkbox">
                    </td>
                    <td><?= $no++ ?></td>
                    <td class="fw-medium"><?= esc($s['student_name'] ?? 'N/A') ?></td>
                    <?php if ($selected_method === 'all'): ?>
                      <td><span class="badge bg-secondary"><?= esc(ucfirst($s['type'] ?? '-')) ?></span></td>
                    <?php endif; ?>
                    <td class="text-center fw-bold"><?= esc($s['score']) ?></td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                          <?php
                            // current_url(true) menyertakan query string (termasuk class_id)
                            // sehingga setelah simpan, redirect kembali ke kelas yang benar
                            $editRedirectUrl = current_url(true);
                            // Pastikan class_id ikut jika belum ada di URL saat ini
                            if (!empty($class_id) && strpos($editRedirectUrl, 'class_id') === false) {
                                $editRedirectUrl .= (strpos($editRedirectUrl, '?') !== false ? '&' : '?')
                                                  . 'class_id=' . urlencode($class_id);
                            }
                          ?>
                          <a href="<?= site_url("admin/assessments/edit/{$s['id']}/{$type}?redirect_url=" . urlencode($editRedirectUrl)) ?>"
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
                  <td colspan="<?= ($selected_method === 'all') ? '6' : '5' ?>" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3 d-block text-secondary"></i>
                    <strong>Belum ada nilai untuk kelas dan kombinasi ini.</strong><br>
                    <small>
                      Jika baru disimpan, pastikan:<br>
                      • Siswa sudah terdaftar aktif di kelas <?= esc($currentClass['name'] ?? '-') ?>.<br>
                      • Metode penilaian dan materi yang dipilih sesuai.
                    </small>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </form>

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
        <?php elseif ($type === 'pts' && isset($subject, $class_id)): ?>
          <a href="<?= site_url("admin/assessments/ptsList/{$class_id}/{$subject['id']}") ?>" class="btn btn-secondary">
            ⬅️ Kembali ke Daftar PTS
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
               onclick="return confirm('Yakin ingin menghapus semua nilai formatif untuk materi dan metode ini di kelas <?= esc($currentClass['name'] ?? '') ?>?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php elseif ($type === 'sumatif'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/sumatif/{$subject['id']}/{$semester}/{$selected_method}" . ($class_id ? "?class_id={$class_id}" : "")) ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai sumatif untuk semester dan metode ini di kelas <?= esc($currentClass['name'] ?? '') ?>?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php elseif ($type === 'final'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/final/{$subject['id']}" . ($class_id ? "?class_id={$class_id}" : "")) ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai ujian akhir di kelas <?= esc($currentClass['name'] ?? '') ?>?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php elseif ($type === 'pts'): ?>
            <a href="<?= site_url("admin/assessments/deleteBatch/pts/{$subject['id']}/{$semester}" . ($class_id ? "?class_id={$class_id}" : "")) ?>"
               class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus semua nilai PTS semester ini di kelas <?= esc($currentClass['name'] ?? '') ?>?')">
              🗑 Hapus Semua (<?= count($scores) ?>)
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>

<!-- Script Tambahan -->
<?= $this->section('scripts') ?>
<script>
  // Auto-dismiss alerts setelah 5 detik
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);

  // Checkbox Select All & Bulk Action
  document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.score-checkbox');
    const badge = document.getElementById('selectedCountBadge');
    const countSpan = document.getElementById('selectedCount');
    const btnCountSpan = document.getElementById('btnSelectedCount');
    const btnDelete = document.getElementById('btnDeleteSelected');

    function updateBulkState() {
      const checked = document.querySelectorAll('.score-checkbox:checked');
      const count = checked.length;
      if (countSpan) countSpan.textContent = count;
      if (btnCountSpan) btnCountSpan.textContent = count;

      if (count > 0) {
        if (badge) badge.classList.remove('d-none');
        if (btnDelete) btnDelete.classList.remove('d-none');
      } else {
        if (badge) badge.classList.add('d-none');
        if (btnDelete) btnDelete.classList.add('d-none');
      }

      if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
      }
    }

    if (selectAll) {
      selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
          cb.checked = selectAll.checked;
        });
        updateBulkState();
      });
    }

    checkboxes.forEach(cb => {
      cb.addEventListener('change', updateBulkState);
    });

    window.confirmBulkDelete = function() {
      const checked = document.querySelectorAll('.score-checkbox:checked');
      if (checked.length === 0) {
        alert('Silakan pilih minimal satu nilai siswa untuk dihapus.');
        return;
      }
      if (confirm(`Apakah Anda yakin ingin menghapus ${checked.length} nilai siswa terpilih?`)) {
        document.getElementById('bulkDeleteForm').submit();
      }
    };
  });
</script>
<?= $this->endSection() ?>
