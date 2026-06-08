<!doctype html>
<html lang="id" translate="no">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0d6efd">
  
  <!-- Prevent Auto-Translate -->
  <meta name="google" content="notranslate">
  <meta http-equiv="content-language" content="id">

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= base_url('assets/images/logo-192.png') ?>">

  <title><?= $title ?? 'Mode CBT' ?> - SIKAP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
  <div class="container-fluid">
    <nav class="navbar navbar-light bg-light sticky-top shadow-sm" style="height: 56px;">
      <div class="container-fluid">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-outline-primary" title="Kembali ke Dashboard">
            <i class="bi bi-arrow-left"></i> Dashboard
          </a>
          <span class="navbar-brand fw-bold mb-0">MODE CBT</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary">
            <i class="bi bi-person-circle"></i> <?= esc(session()->get('user')['fullname'] ?? 'Siswa') ?>
          </span>
        </div>
      </div>
    </nav>

    <main class="py-3">
      <?= $this->renderSection('content') ?>
    </main>
  </div>

  <!-- JS libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Section untuk skrip tiap view -->
  <?= $this->renderSection('scripts') ?>

  <!-- PWA Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
          .then(registration => {
            console.log('[PWA] Service Worker registered:', registration.scope);
          })
          .catch(error => {
            console.log('[PWA] Service Worker registration failed:', error);
          });
      });
    }
  </script>
</body>

</html>