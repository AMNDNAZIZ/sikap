<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-1">📢 Daftar Pengumuman</h3>
      <p class="text-muted mb-0">Kelola dan pantau semua pengumuman sekolah.</p>
    </div>
    <?php if ($roleId == 1 || $roleId == 2 || $roleId == 3): ?>
      <div>
        <a href="<?= base_url('admin/announcements/create') ?>" class="btn btn-primary shadow-sm">
          <i class="bi bi-plus-lg me-1"></i> Buat Pengumuman
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($roleId == 1 || $roleId == 2): ?>
    <!-- Admin & Kepsek View: All Announcements -->
    <div class="row g-4">
      <?php if (empty($announcements)): ?>
        <div class="col-12 text-center py-5">
          <div class="mb-3">
            <i class="bi bi-megaphone text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
          </div>
          <h5 class="text-muted">Belum ada pengumuman yang dibuat.</h5>
          <p class="text-muted">Klik tombol "Buat Pengumuman" untuk memulai.</p>
        </div>
      <?php else: ?>
        <?php foreach ($announcements as $a): ?>
          <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 announcement-card">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="card-title fw-bold text-dark mb-0"><?= esc($a['title']) ?></h5>
                  <div class="dropdown">
                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                      <li><a class="dropdown-item" href="<?= base_url('admin/announcements/edit/' . $a['id']) ?>"><i
                            class="bi bi-pencil me-2"></i> Edit</a></li>
                      <li>
                        <hr class="dropdown-divider">
                      </li>
                      <li><a class="dropdown-item text-danger"
                          href="<?= base_url('admin/announcements/delete/' . $a['id']) ?>"
                          onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"><i class="bi bi-trash me-2"></i>
                          Hapus</a></li>
                    </ul>
                  </div>
                </div>
                <p class="card-text text-muted mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                  <?= nl2br(esc($a['content'])) ?>
                </p>

                <div class="mb-3">
                  <?php
                  $targets = explode(',', $a['target']);
                  foreach ($targets as $target):
                    $badgeClass = match ($target) {
                      'guru' => 'bg-info',
                      'siswa' => 'bg-success',
                      'ortu' => 'bg-warning',
                      'kepala' => 'bg-primary',
                      default => 'bg-secondary'
                    };
                    ?>
                    <span
                      class="badge <?= $badgeClass ?> bg-opacity-10 text-<?= str_replace('bg-', '', $badgeClass) ?> border border-<?= str_replace('bg-', '', $badgeClass) ?> border-opacity-25 px-2 py-1 me-1 mb-1">
                      <?= ucfirst($target) ?>
                    </span>
                  <?php endforeach; ?>

                  <?php if ($a['class_name']): ?>
                    <span
                      class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 px-2 py-1 me-1 mb-1">
                      <i class="bi bi-door-open me-1"></i> <?= esc($a['class_name']) ?>
                    </span>
                  <?php endif; ?>

                  <?php if ($a['is_public']): ?>
                    <span
                      class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 me-1 mb-1">
                      <i class="bi bi-globe me-1"></i> Publik
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                <hr class="my-3 opacity-5">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="text-truncate" style="max-width: 100px;"><?= $a['creator_name'] ?? 'System' ?></span>
                  </div>
                  <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d M Y, H:i', strtotime($a['created_at'])) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <?php elseif ($roleId == 3): ?>
    <!-- Guru View -->

    <!-- 1. From Admin/Principal -->
    <h5 class="mb-3 fw-bold"><i class="bi bi-pin-angle-fill text-danger me-2"></i>Pesan dari Manajemen</h5>
    <div class="row g-4 mb-5">
      <?php if (empty($adminAnnouncements)): ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center text-muted">
              Belum ada pengumuman dari Manajemen.
            </div>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($adminAnnouncements as $a): ?>
          <div class="col-md-6">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
              <div class="card-body p-4">
                <h6 class="fw-bold mb-2"><?= esc($a['title']) ?></h6>
                <p class="text-muted small mb-3"><?= nl2br(esc($a['content'])) ?></p>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <span class="small text-muted"><i class="bi bi-person me-1"></i> <?= $a['creator_name'] ?? 'Admin' ?></span>
                  <span class="small text-muted"><i class="bi bi-clock me-1"></i>
                    <?= date('d/m/Y', strtotime($a['created_at'])) ?></span>
                  <?php if ($a['is_public']): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 ms-2">
                        <i class="bi bi-globe me-1"></i> Publik
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- 2. My Announcements -->
    <h5 class="mb-3 fw-bold"><i class="bi bi-list-task text-primary me-2"></i>Pengumuman Saya</h5>
    <div class="row g-4">
      <?php if (empty($myAnnouncements)): ?>
        <div class="col-12 text-center py-5">
          <div class="mb-3">
            <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
          </div>
          <h5 class="text-muted">Anda belum membuat pengumuman khusus.</h5>
        </div>
      <?php else: ?>
        <?php foreach ($myAnnouncements as $a): ?>
          <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h6 class="fw-bold text-dark mb-0"><?= esc($a['title']) ?></h6>
                  <div class="dropdown">
                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                      <li><a class="dropdown-item" href="<?= base_url('admin/announcements/edit/' . $a['id']) ?>"><i
                            class="bi bi-pencil me-2"></i> Edit</a></li>
                      <li>
                        <hr class="dropdown-divider">
                      </li>
                      <li><a class="dropdown-item text-danger"
                          href="<?= base_url('admin/announcements/delete/' . $a['id']) ?>"
                          onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"><i class="bi bi-trash me-2"></i>
                          Hapus</a></li>
                    </ul>
                  </div>
                </div>
                <p class="small text-muted mb-3"><?= nl2br(esc($a['content'])) ?></p>
                <div>
                  <span
                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 mb-2">
                    <i class="bi bi-door-open me-1"></i> <?= esc($a['class_name'] ?? 'Semua Kelas') ?>
                  </span>
                  <?php if ($a['is_public']): ?>
                    <span
                      class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 mb-2 ms-1">
                      <i class="bi bi-globe me-1"></i> Publik
                    </span>
                  <?php endif; ?>
                </div>
                <hr class="my-3 opacity-5">
                <div class="text-muted" style="font-size: 0.8rem;">
                  <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($a['created_at'])) ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<style>
  .announcement-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .announcement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
  }

  .card-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .badge {
    font-weight: 500;
    letter-spacing: 0.3px;
  }
</style>

<?= $this->endSection() ?>