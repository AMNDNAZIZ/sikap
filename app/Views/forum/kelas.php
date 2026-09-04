<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-chat-dots me-2 text-primary"></i>Forum Diskusi — Pilih Kelas
  </h5>
  <?php if ($activeYear): ?>
    <span class="badge bg-secondary"><?= esc($activeYear['year']) ?></span>
  <?php endif; ?>
</div>

<?php if (empty($classes)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Tidak ada kelas yang dapat diakses.</p>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($classes as $c): ?>
      <div class="col-sm-6 col-lg-4">
        <a href="<?= base_url('forum/' . $c['id'] . '/0') ?>"
           class="card border-0 shadow-sm h-100 text-decoration-none text-dark forum-card">
          <div class="card-body py-3 px-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="fw-bold mb-0"><?= esc($c['name']) ?></h6>
              <span class="badge bg-primary-subtle text-primary"><?= $c['total_students'] ?> siswa</span>
            </div>
          </div>
          <div class="card-footer bg-transparent py-2 border-top">
            <span class="small text-primary fw-semibold">
              <i class="bi bi-chat-dots me-1"></i>Buka Forum
            </span>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
.forum-card { transition: transform .15s, box-shadow .15s; }
.forum-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12) !important; }
</style>
<?= $this->endSection() ?>
