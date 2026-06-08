<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="google" content="notranslate">
  <title><?= $title ?? 'Mode CBT' ?> - SIAKAD</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <meta name="theme-color" content="#0d6efd">

  <!-- MathJax for Mathematical Formulas -->
  <script>
    MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']],
        processEscapes: true,
        processEnvironments: true
      },
      options: {
        skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
      }
    };
  </script>
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>

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
        <a class="navbar-brand">CBT V.2.1</a>
        <div class="d-flex align-items-center">
          <!--<span class="navbar-user"><?= esc($studentName) ?></span>-->
          <a href="<?= site_url('logout') ?>" class="nav-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
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
  <!-- Section untuk skrip tiap view -->
  <?= $this->renderSection('scripts') ?>
</body>

</html>