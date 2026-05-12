<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-1"><i class="bi bi-megaphone-fill text-primary me-2"></i>Pengumuman</h3>
      <p class="text-muted mb-0">Informasi terbaru untuk Anda di sekolah.</p>
    </div>
    <div class="text-end d-none d-md-block">
      <div class="h5 mb-0 fw-bold realtime-clock"><?= date('H:i:s') ?></div>
      <small class="text-muted"><?= date('d F Y') ?></small>
    </div>
  </div>

  <?php if (empty($announcements)): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-chat-dots text-muted mb-3" style="font-size: 3rem;"></i>
        <h5 class="text-muted">Belum ada pengumuman untuk Anda.</h5>
        <p class="text-muted small">Cek kembali nanti untuk informasi terbaru.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($announcements as $a): ?>
        <div class="col-12 col-xl-6">
          <div class="card border-0 shadow-sm h-100 transition-hover">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span
                  class="badge bg-primary bg-opacity-10 text-primary border-primary border-opacity-10 px-3 py-2 rounded-pill">
                  <i class="bi <?= $a['class_id'] ? 'bi-door-open' : 'bi-globe' ?> me-1"></i>
                  <?= $a['class_id'] ? esc($a['class_name']) : 'Umum' ?>
                </span>
                <small class="text-muted">
                  <i class="bi bi-calendar3 me-1"></i>
                  <?= date('d M Y', strtotime($a['created_at'])) ?>
                </small>
              </div>

              <a href="<?= site_url('siswa/announcement/show/' . $a['id']) ?>" class="text-decoration-none">
                <h5 class="card-title text-dark fw-bold mb-3"><?= esc($a['title']) ?></h5>
              </a>

              <p class="card-text text-muted mb-4"
                style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3rem;">
                <?= character_limiter(strip_tags($a['content']), 150) ?>
              </p>

              <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <div class="small text-muted">
                  <i class="bi bi-person-circle me-1"></i>
                  <?= esc($a['creator_name'] ?? '-') ?>
                </div>
                <a href="<?= site_url('siswa/announcement/show/' . $a['id']) ?>"
                  class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-bold">
                  Baca Selengkapnya <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
  .transition-hover {
    transition: transform 0.2s ease-in-out, shadow 0.2s ease-in-out;
  }

  .transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1) !important;
  }
</style>

<?= $this->endSection() ?>