<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">
  <h4 class="mt-3 fw-bold mb-3">📚 Tugas Saya</h4>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 shadow-sm"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>

  <?php
    $nilaiInfo = [
      'sangat_bagus' => ['⭐ Sangat Bagus', 'success'],
      'bagus'        => ['👍 Bagus',        'primary'],
      'kurang'       => ['⚠️ Kurang',       'warning'],
      'belajar_lagi' => ['🔄 Belajar Lagi', 'danger'],
    ];
  ?>

  <?php if (empty($tugasList)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
      Belum ada tugas.
    </div>

  <?php else: ?>

  <!-- ── DESKTOP ── -->
  <div class="d-none d-md-block">
    <div class="card shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Judul Tugas</th>
              <th>Mapel</th>
              <th>Batas Waktu</th>
              <th>Status</th>
              <th>Nilai</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($tugasList as $t): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td class="fw-semibold"><?= esc($t['judul']) ?></td>
              <td><?= esc($t['subject_name'] ?? '-') ?></td>
              <td><?= date('d/m/Y H:i', strtotime($t['selesai_at'])) ?></td>
              <td><?php
                if ($t['dikumpul_at']) {
                  echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sudah Dikumpulkan</span>';
                } elseif ($t['status_waktu'] === 'terlewat') {
                  echo '<span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Terlewat</span>';
                } elseif ($t['status_waktu'] === 'belum') {
                  echo '<span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Belum Dimulai</span>';
                } else {
                  echo '<span class="badge bg-warning text-dark"><i class="bi bi-pencil-square me-1"></i>Kerjakan</span>';
                }
              ?></td>
              <td>
                <?php if ($t['nilai']): ?>
                  <span class="badge bg-<?= $nilaiInfo[$t['nilai']][1] ?>"><?= $nilaiInfo[$t['nilai']][0] ?></span>
                <?php elseif ($t['dikumpul_at']): ?>
                  <span class="text-muted small">Menunggu penilaian</span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= site_url('siswa/tugas/' . $t['id']) ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye me-1"></i>Detail
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── MOBILE ── -->
  <div class="d-md-none">
    <?php foreach ($tugasList as $t): ?>
    <div class="card mb-3 shadow-sm border-0 <?= ($t['status_waktu'] === 'aktif' && !$t['dikumpul_at']) ? 'border-start border-4 border-warning' : '' ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="fw-semibold"><?= esc($t['judul']) ?></div>
          <?php
            if ($t['dikumpul_at']) {
              echo '<span class="badge bg-success">Dikumpulkan</span>';
            } elseif ($t['status_waktu'] === 'terlewat') {
              echo '<span class="badge bg-danger">Terlewat</span>';
            } elseif ($t['status_waktu'] === 'belum') {
              echo '<span class="badge bg-secondary">Belum Dimulai</span>';
            } else {
              echo '<span class="badge bg-warning text-dark">Kerjakan</span>';
            }
          ?>
        </div>
        <div class="small text-muted mb-1">
          <i class="bi bi-book me-1"></i><?= esc($t['subject_name'] ?? '-') ?>
        </div>
        <div class="small text-muted mb-2">
          <i class="bi bi-alarm me-1"></i>Batas: <?= date('d/m/Y H:i', strtotime($t['selesai_at'])) ?>
        </div>
        <?php if ($t['nilai']): ?>
        <div class="mb-2">
          <span class="badge bg-<?= $nilaiInfo[$t['nilai']][1] ?>"><?= $nilaiInfo[$t['nilai']][0] ?></span>
          <?php if ($t['catatan_guru']): ?>
            <div class="small text-muted mt-1 fst-italic"><?= esc($t['catatan_guru']) ?></div>
          <?php endif; ?>
        </div>
        <?php elseif ($t['dikumpul_at']): ?>
        <div class="small text-muted mb-2">⏳ Menunggu penilaian guru</div>
        <?php endif; ?>
        <a href="<?= site_url('siswa/tugas/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-arrow-right-circle me-1"></i>Lihat Detail
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<?= $this->endSection() ?>
