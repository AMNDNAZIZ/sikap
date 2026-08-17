<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-3 px-md-4 pb-5">
  <div class="d-flex justify-content-between align-items-center mt-3 mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">📋 Kirim Tugas</h4>
    <a href="<?= site_url('admin/tugas/create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Buat Tugas
    </a>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 shadow-sm"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>

  <?php
    $statusLabel = [
      'belum'    => '<span class="badge bg-secondary">Belum Dimulai</span>',
      'aktif'    => '<span class="badge bg-success">Aktif</span>',
      'terlewat' => '<span class="badge bg-danger">Berakhir</span>',
    ];
  ?>

  <!-- ── TABEL DESKTOP ── -->
  <div class="d-none d-md-block">
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Judul Tugas</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Terkumpul</th>
                <th>Dinilai</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tugasList)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Belum ada tugas.</td></tr>
              <?php else: $no = 1; foreach ($tugasList as $t): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td class="fw-semibold"><?= esc($t['judul']) ?></td>
                <td><?= esc($t['class_name'] ?? '-') ?></td>
                <td><?= esc($t['subject_name'] ?? '-') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($t['mulai_at'])) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($t['selesai_at'])) ?></td>
                <td class="text-center"><?= $t['total_submit'] ?></td>
                <td class="text-center"><?= $t['total_dinilai'] ?></td>
                <td><?= $statusLabel[$t['status']] ?? '-' ?></td>
                <td>
                  <a href="<?= site_url('admin/tugas/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                  <a href="<?= site_url('admin/tugas/edit/' . $t['id']) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="<?= site_url('admin/tugas/delete/' . $t['id']) ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Hapus tugas ini? Semua jawaban siswa akan ikut terhapus.')">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── KARTU MOBILE ── -->
  <div class="d-md-none">
    <?php if (empty($tugasList)): ?>
      <div class="text-center text-muted py-5">Belum ada tugas.</div>
    <?php else: foreach ($tugasList as $t): ?>
    <div class="card mb-3 shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <h6 class="fw-bold mb-0"><?= esc($t['judul']) ?></h6>
          <?= $statusLabel[$t['status']] ?? '' ?>
        </div>
        <div class="text-muted small mb-2">
          <i class="bi bi-people me-1"></i><?= esc($t['class_name'] ?? '-') ?>
          &nbsp;·&nbsp;
          <i class="bi bi-book me-1"></i><?= esc($t['subject_name'] ?? '-') ?>
        </div>
        <div class="small mb-2">
          <i class="bi bi-clock me-1 text-success"></i><?= date('d/m/Y H:i', strtotime($t['mulai_at'])) ?>
          &nbsp;→&nbsp;
          <i class="bi bi-clock me-1 text-danger"></i><?= date('d/m/Y H:i', strtotime($t['selesai_at'])) ?>
        </div>
        <div class="small text-muted mb-3">
          Terkumpul: <strong><?= $t['total_submit'] ?></strong> &nbsp;|&nbsp;
          Dinilai: <strong><?= $t['total_dinilai'] ?></strong>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= site_url('admin/tugas/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
            <i class="bi bi-eye me-1"></i>Detail
          </a>
          <a href="<?= site_url('admin/tugas/edit/' . $t['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i>
          </a>
          <a href="<?= site_url('admin/tugas/delete/' . $t['id']) ?>"
             class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Hapus tugas ini?')">
            <i class="bi bi-trash"></i>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
