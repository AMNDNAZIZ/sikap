<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
  <meta name="google" content="notranslate">
  <meta name="screen-orientation" content="portrait">
  <meta name="x5-orientation" content="portrait">
  <meta name="mobile-web-app-capable" content="yes">
  <title><?= $title ?? 'Mode CBT' ?> - CBT</title>

  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <meta name="theme-color" content="#0d6efd">



  <style>
    .navbar-brand {
      font-weight: 700;
      color: #004a4f !important;
      letter-spacing: 0.5px;
    }

    .navbar {
      background-color: #e9f5f5 !important;
    }

    .nav-logout {
      color: #fff !important;
      background-color: #dc3545;
      border: none;
      padding: 6px 14px;
      border-radius: 5px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-right: 1rem;
    }

    .nav-logout:hover {
      background-color: #bb2d3b;
    }

    .navbar-user {
      font-weight: 600;
      margin-right: 1rem;
      color: #0d3a3a;
    }
  </style>
</head>

<body class="notranslate" translate="no">
  <?php
  $user = session()->get('user');
  $studentName = $user['fullname'] ?? $user['username'] ?? 'Siswa';
  ?>

  <div class="container-fluid">
    <nav class="navbar navbar-light sticky-top shadow-sm px-3" style="height: 56px;">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand">CBT V.2.1.1</a>
        <div class="d-flex align-items-center">
          <!--<span class="navbar-user"><?= esc($studentName) ?></span>-->
          <?php if (empty($hideLogout)): ?>
          <a href="<?= site_url('logout') ?>" class="nav-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <main class="py-3">
      <?= $this->renderSection('content') ?>
    </main>
  </div>

  <!-- JS libraries -->
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
          .then(reg => {
            // Service worker registered successfully
          })
          .catch(err => console.error('SW Registration failed:', err));
      });
    }
  </script>
  <!-- 💓 SESSION HEARTBEAT (KEEPS STUDENT LOGGED IN DURING EXAM) -->
  <script>
    (function () {
      // Heartbeat every 5 minutes (300,000 ms)
      const heartbeatInterval = 300000;
      const heartbeatUrl = '<?= site_url('siswa/cbt/heartbeat') ?>';

      function doHeartbeat() {
        fetch(heartbeatUrl)
          .then(r => r.json())
          .then(data => {
            // Update CSRF meta tag if hash is provided
            if (data && data.csrf_hash) {
              const meta = document.querySelector('meta[name="<?= csrf_token() ?>"]');
              if (meta) meta.setAttribute('content', data.csrf_hash);

              // Also update any hidden CSRF inputs in the page (if any)
              document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(inp => {
                inp.value = data.csrf_hash;
              });
            }
            console.log('💓 Heartbeat: Student session kept alive (' + new Date().toLocaleTimeString() + ')');
          })
          .catch(err => {
            console.warn('💔 Heartbeat failed. Session might expire.', err);
          });
      }

      // Start initial heartbeat after interval
      setInterval(doHeartbeat, heartbeatInterval);
    })();
  </script>
  <!-- Section untuk skrip tiap view -->
  <?= $this->renderSection('scripts') ?>
</body>

</html>