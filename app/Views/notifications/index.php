<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-bell me-2 text-primary"></i>Semua Notifikasi
  </h5>
  <div class="d-flex gap-2 align-items-center">
    <button class="btn btn-sm btn-outline-secondary" id="btnMarkAllRead">
      <i class="bi bi-check2-all me-1"></i>Tandai semua dibaca
    </button>
    <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-outline-dark">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">

    <?php if (empty($items)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-40"></i>
        <p class="mb-0">Belum ada notifikasi</p>
      </div>

    <?php else: ?>
      <div class="list-group list-group-flush" id="notifFullList">
        <?php foreach ($items as $n): ?>
          <?php
            $bgClass  = $n['is_read'] == 0 ? 'bg-light-blue' : '';
            $icon     = esc($n['icon']);
            $color    = esc($n['color']);
            $readUrl  = base_url('notifications/read/' . $n['id']);
            if (!empty($n['url'])) {
              $readUrl .= '?redirect=' . urlencode($n['url']);
            }
          ?>
          <a href="<?= $readUrl ?>"
             class="list-group-item list-group-item-action px-4 py-3 <?= $n['is_read'] == 0 ? 'notif-unread' : '' ?>"
             data-id="<?= $n['id'] ?>">
            <div class="d-flex align-items-start gap-3">

              <!-- Ikon -->
              <span class="notif-full-icon bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                <i class="<?= $icon ?>"></i>
              </span>

              <!-- Konten -->
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="badge bg-<?= $color ?> bg-opacity-15 text-<?= $color ?> rounded-pill"
                        style="font-size:0.68rem; font-weight:600;">
                    <?= esc($n['label']) ?>
                  </span>
                  <span class="text-muted" style="font-size:0.72rem; white-space:nowrap;">
                    <?= esc($n['time_ago']) ?>
                  </span>
                </div>
                <div class="fw-semibold" style="font-size:0.85rem; line-height:1.3;">
                  <?= esc($n['title']) ?>
                </div>
                <?php if (!empty($n['message'])): ?>
                  <div class="text-muted mt-1" style="font-size:0.8rem; line-height:1.4;">
                    <?= esc($n['message']) ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Titik unread -->
              <?php if ($n['is_read'] == 0): ?>
                <span class="notif-dot bg-primary rounded-circle flex-shrink-0 mt-1"
                      style="width:8px;height:8px;display:inline-block;"></span>
              <?php else: ?>
                <span style="width:8px;"></span>
              <?php endif; ?>

            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Paginasi CI4 -->
      <?php if ($pager): ?>
        <div class="d-flex justify-content-center py-3">
          <?= $pager->links('default', 'bootstrap') ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div><!-- /.card-body -->
</div><!-- /.card -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
  .notif-unread {
    background-color: #f0f6ff !important;
  }
  .notif-unread:hover {
    background-color: #e2ecff !important;
  }
  .notif-full-icon {
    width: 38px; height: 38px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
  }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var CSRF_NAME     = "<?= csrf_token() ?>";
  var CSRF_HASH     = "<?= csrf_hash() ?>";
  var MARK_ALL_URL  = "<?= base_url('notifications/read-all') ?>";

  // Tandai semua dibaca
  document.getElementById('btnMarkAllRead').addEventListener('click', function () {
    var data = {};
    data[CSRF_NAME] = CSRF_HASH;
    $.post(MARK_ALL_URL, data, function (res) {
      if (res.success) {
        // Hapus visual unread dari semua item di halaman ini
        document.querySelectorAll('.notif-unread').forEach(function (el) {
          el.classList.remove('notif-unread');
        });
        document.querySelectorAll('.notif-dot').forEach(function (el) {
          el.style.display = 'none';
        });
        // Reset badge navbar
        var badge = document.getElementById('notifBadge');
        if (badge) { badge.textContent = '0'; badge.style.display = 'none'; }
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
