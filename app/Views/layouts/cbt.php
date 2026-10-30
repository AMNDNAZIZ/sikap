<!doctype html>
<html lang="id" translate="no">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover" />
  <meta name="theme-color" content="#0d6efd">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="screen-orientation" content="portrait">
  <meta name="x5-orientation" content="portrait">
  <meta name="mobile-web-app-capable" content="yes">
  
  <!-- Prevent Auto-Translate -->
  <meta name="google" content="notranslate">
  <meta http-equiv="content-language" content="id">

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= base_url('assets/images/logo-192.png') ?>">

  <title><?= $title ?? 'Mode CBT' ?> - <?= defined('APP_NAME') ? APP_NAME : 'CBT Siswa' ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --app-font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      --cbt-primary: #0d6efd;
      --cbt-primary-dark: #0b5ed7;
      --cbt-bg: #f8fafc;
    }

    body {
      font-family: var(--app-font);
      background-color: var(--cbt-bg);
      -webkit-font-smoothing: antialiased;
      -webkit-tap-highlight-color: transparent;
      padding-top: env(safe-area-inset-top);
      padding-bottom: env(safe-area-inset-bottom);
      padding-left: env(safe-area-inset-left);
      padding-right: env(safe-area-inset-right);
    }

    .cbt-navbar {
      height: 56px;
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(0, 0, 0, 0.06);
      transition: all 0.2s ease;
    }

    .cbt-brand-badge {
      font-weight: 800;
      letter-spacing: 0.5px;
      color: #0f172a;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 1.05rem;
    }

    .cbt-brand-badge i {
      color: var(--cbt-primary);
    }

    .btn-cbt-back {
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.85rem;
      padding: 6px 14px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      border: 1px solid #e2e8f0;
      color: #475569;
      background: #ffffff;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-cbt-back:hover {
      background: #f1f5f9;
      color: #0f172a;
    }

    .user-pill {
      background: #eff6ff;
      color: #1d4ed8;
      font-weight: 600;
      font-size: 0.8rem;
      padding: 5px 12px;
      border-radius: 50px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      max-width: 160px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      border: 1px solid #dbeafe;
    }

    @media (max-width: 576px) {
      .cbt-navbar {
        height: 52px;
        padding-left: 12px !important;
        padding-right: 12px !important;
      }
      .cbt-brand-badge {
        font-size: 0.95rem;
      }
      .btn-cbt-back {
        padding: 5px 10px;
        font-size: 0.78rem;
      }
      .btn-cbt-back span {
        display: none;
      }
      .user-pill {
        max-width: 120px;
        font-size: 0.75rem;
        padding: 4px 10px;
      }
    }
  </style>
</head>

<body>
  <?php
  $user = session()->get('user') ?? [];
  $studentName = $user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'Siswa';
  ?>

  <div class="container-fluid <?= !empty($fluidNoPadding) ? 'p-0' : '' ?>">
    <?php if (empty($hideNavbar)): ?>
    <nav class="navbar cbt-navbar sticky-top shadow-sm px-3">
      <div class="container-fluid d-flex justify-content-between align-items-center p-0">
        <div class="d-flex align-items-center gap-2">
          <?php if (empty($hideBack)): ?>
          <a href="<?= base_url('dashboard') ?>" class="btn-cbt-back" title="Kembali ke Dashboard">
            <i class="bi bi-arrow-left"></i> <span>Dashboard</span>
          </a>
          <?php endif; ?>
          <span class="cbt-brand-badge">
            <i class="bi bi-laptop"></i> CBT SISWA
          </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
          <span class="user-pill" title="<?= esc($studentName) ?>">
            <i class="bi bi-person-circle"></i> <?= esc($studentName) ?>
          </span>
          <?php if (empty($hideLogout)): ?>
          <a href="<?= site_url('logout') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Keluar">
            <i class="bi bi-box-arrow-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
    <?php endif; ?>

    <main class="<?= !empty($noMainPadding) ? 'p-0' : 'py-3' ?>">
      <?= $this->renderSection('content') ?>
    </main>
  </div>

  <!-- JS libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- PWA Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
          .then(registration => {
            // Service worker registered
          })
          .catch(error => {
            console.log('[PWA] Service Worker registration failed:', error);
          });
      });
    }
  </script>

  <!-- 💓 SESSION HEARTBEAT (KEEPS STUDENT SESSION ALIVE) -->
  <script>
    (function () {
      const pingUrl = '<?= site_url('siswa/cbt/ping') ?>';
      function keepSessionAlive() {
        fetch(pingUrl, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ t: Date.now() })
        }).catch(e => console.warn('[CBT] Heartbeat ping failed:', e));
      }
      // Send every 4 minutes (240,000 ms)
      setInterval(keepSessionAlive, 240000);
    })();
  </script>

  <!-- Section untuk skrip tiap view -->
  <?= $this->renderSection('scripts') ?>
</body>

</html>