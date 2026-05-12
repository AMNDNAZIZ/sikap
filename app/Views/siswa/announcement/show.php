<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
  <div class="mb-4">
    <a href="<?= site_url('siswa/announcement') ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
  </div>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-primary bg-opacity-10 border-0 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-primary px-3 py-2 rounded-pill">
          <i class="bi <?= $announcement['class_id'] ? 'bi-door-open' : 'bi-globe' ?> me-1"></i>
          <?= $announcement['class_id'] ? esc($announcement['class_name']) : 'Umum' ?>
        </span>
        <small class="text-muted fw-bold">
          <i class="bi bi-calendar3 me-1"></i>
          <?= date('d F Y', strtotime($announcement['created_at'])) ?>
        </small>
      </div>
      <h3 class="card-title fw-bold mb-0 text-dark"><?= esc($announcement['title']) ?></h3>
    </div>

    <div class="card-body p-4 p-md-5">
      <div class="mb-4 pb-3 border-bottom d-flex align-items-center text-muted">
        <i class="bi bi-person-circle fs-4 me-2 text-primary"></i>
        <div>
          <span class="d-block fw-bold text-dark"
            style="font-size: 0.9rem;"><?= esc($announcement['creator_name'] ?? '-') ?></span>
          <small style="font-size: 0.75rem;">Penulis Pengumuman</small>
        </div>
      </div>

      <div class="announcement-content fs-6 text-dark lh-lg">
        <?= $announcement['content'] ?>
      </div>
    </div>

    <div class="card-footer bg-light border-0 p-4 text-center">
      <p class="text-muted small mb-0">Demikian pengumuman ini disampaikan untuk menjadi perhatian.</p>
    </div>
  </div>
</div>

<style>
  .announcement-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
  }

  .announcement-content p {
    margin-bottom: 1.25rem;
  }
</style>

<?= $this->endSection() ?>