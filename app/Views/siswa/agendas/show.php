<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">

  <!-- Back button + title -->
  <div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('siswa/agendas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0 flex-grow-1"><?= esc($agenda['title']) ?></h5>
  </div>

  <!-- Info detail agenda -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span class="text-muted small"><i class="bi bi-calendar3 me-2"></i>Tanggal</span>
          <span class="fw-semibold"><?= date('d M Y', strtotime($agenda['date'])) ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span class="text-muted small"><i class="bi bi-clock me-2"></i>Waktu</span>
          <span class="fw-semibold"><?= $agenda['start_time'] ?> — <?= $agenda['end_time'] ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span class="text-muted small"><i class="bi bi-building me-2"></i>Kelas</span>
          <span class="fw-semibold"><?= $agenda['class_id'] ? esc($agenda['class_name']) : 'Semua Kelas' ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span class="text-muted small"><i class="bi bi-eye me-2"></i>Status</span>
          <span class="badge <?= $agenda['is_public'] ? 'bg-success' : 'bg-secondary' ?>">
            <?= $agenda['is_public'] ? 'Publik' : 'Pribadi' ?>
          </span>
        </li>
      </ul>
    </div>
  </div>

  <!-- Deskripsi -->
  <?php if (!empty($agenda['description'])): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold">
      <i class="bi bi-card-text me-1 text-primary"></i> Deskripsi
    </div>
    <div class="card-body">
      <?= $agenda['description'] ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?= $this->endSection() ?>
