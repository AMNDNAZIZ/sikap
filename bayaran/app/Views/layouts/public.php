<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Informasi Pembayaran - SMP Islam Tambora</title>
  <link rel="icon" href="<?= base_url('public/favicon.ico') ?>" type="image/ico">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <meta name="theme-color" content="#667eea">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap"
    rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary: #667eea;
      --secondary: #764ba2;
      --accent: #667eea;
      --bg-light: #f8f9fc;
      --text-dark: #2d3436;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 0;
      margin: 0;
    }

    .public-header {
      background: linear-gradient(180deg, #10b981 0%, #ecfdf5 100%);
      padding: 4rem 0 7rem 0;
      color: #ffffff;
      margin-bottom: -4rem;
      clip-path: ellipse(150% 100% at 50% 0%);
    }

    .logo img {
      height: 110px;
      filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.1));
      margin-bottom: 1.5rem;
    }

    .school-name {
      font-family: 'Outfit', sans-serif;
      font-weight: 700;
      letter-spacing: 0.8px;
      color: #065f46 !important;
      text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
    }

    .app-subtitle {
      color: #047857 !important;
      font-weight: 600;
      opacity: 0.9;
    }

    .announcement-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 700;
      color: #065f46 !important;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
    }

    .main-container {
      padding-bottom: 4rem;
      flex: 1;
      position: relative;
      z-index: 5;
    }

    .alert {
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    .alert-info {
      background-color: #ffffff;
      color: #2d3748;
      border-left: 5px solid var(--primary);
    }

    .alert-warning {
      background-color: #fffbeb;
      border-left: 5px solid #f59e0b;
      color: #92400e;
    }

    footer {
      background: #ffffff;
      padding: 2rem 0;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
      color: #636e72;
    }

    footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>

  <header class="public-header text-center">
    <div class="container logo">
      <img src="<?= base_url($logo) ?>" alt="Logo Sekolah" class="img-fluid">
      <h3 class="school-name mt-2 text-uppercase"><?= esc($school_name) ?></h3>
      <p class="app-subtitle mb-0"><?= esc($app_name) ?></p>
    </div>
  </header>

  <div class="container main-container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="alert alert-info text-justify mb-4">
          <i class="bi bi-info-circle-fill me-2"></i>
          Data yang ditampilkan merupakan data realtime sesuai dengan pembayaran yang telah dilakukan, karena petugas
          pencatat dengan penerima bayaran kadang berbeda maka kadang ada delay pembayaran dengan pencatatan disistem,
          jika dalam waktu 2 x 24 jam data belum berubah silakan konfirmasi dengan menghubungi call center yang tersedia
          dengan menyertakan bukti pembayaran yang sah
        </div>

        <?php if (!empty($pengumuman)): ?>
          <div class="alert alert-warning mb-4 shadow-sm">
            <h6 class="announcement-title text-uppercase mb-2"><i class="bi bi-megaphone-fill me-2"></i> PENGUMUMAN</h6>
            <div class="mt-2">
              <ul class="mb-0 ps-3">
                <?php
                $lines = explode("\n", str_replace("\r", "", strip_tags($pengumuman)));
                foreach ($lines as $line) {
                  $line = trim($line);
                  if ($line !== '') {
                    echo "<li>" . esc($line) . "</li>";
                  }
                }
                ?>
              </ul>
            </div>
          </div>
        <?php endif ?>

        <?= $this->renderSection('content') ?>
      </div>
    </div>
  </div>

  <footer class="text-center">
    <div class="container">
      <small>&copy; <?= date('Y') ?> <strong>SakaSalika™</strong> - Modern Payment System<br>
        Optimized for <a href="https://smpistora.sch.id"><strong>SMP Islam Tambora</strong></a></small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
          .then(reg => console.log('Service Worker registered', reg))
          .catch(err => console.log('Service Worker registration failed', err));
      });
    }
  </script>
</body>

</html>