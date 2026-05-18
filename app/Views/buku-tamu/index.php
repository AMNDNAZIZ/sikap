<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= esc($title) ?> - <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    :root {
      --primary: #1a6fc4;
      --primary-dark: #145ea8;
    }
    body {
      background: linear-gradient(135deg, #e8f0fe 0%, #f0f7ff 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .hero-card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(26, 111, 196, 0.15);
    }
    .school-logo {
      width: 80px;
      height: 80px;
      object-fit: contain;
    }
    .choice-btn {
      border: 2.5px solid var(--primary);
      border-radius: 16px;
      padding: 1.75rem 1.5rem;
      background: white;
      transition: all 0.2s;
      text-decoration: none;
      color: var(--primary);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.75rem;
      min-height: 160px;
      justify-content: center;
    }
    .choice-btn:hover, .choice-btn:focus {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(26,111,196,0.25);
    }
    .choice-btn .icon {
      font-size: 2.5rem;
    }
    .choice-btn .label {
      font-size: 1.1rem;
      font-weight: 600;
    }
    .choice-btn .sublabel {
      font-size: 0.8rem;
      opacity: 0.7;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">

        <!-- Header Sekolah -->
        <div class="text-center mb-4">
          <?php if (!empty($school['logo'])): ?>
            <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo" class="school-logo mb-3">
          <?php endif; ?>
          <h1 class="h4 fw-bold text-primary"><?= esc($school['name'] ?? 'Buku Tamu Digital') ?></h1>
          <p class="text-muted mb-0">Selamat datang! Silakan catat kunjungan Anda.</p>
        </div>

        <!-- Card Pilih Jenis Tamu -->
        <div class="card hero-card p-4">
          <h2 class="h5 fw-bold text-center mb-4">Pilih Jenis Kunjungan</h2>
          <div class="row g-3">
            <div class="col-6">
              <a href="<?= base_url('buku-tamu/umum') ?>" class="choice-btn w-100" id="btn-tamu-umum">
                <span class="icon">👥</span>
                <span class="label">Tamu Umum</span>
                <span class="sublabel">Orang tua / masyarakat</span>
              </a>
            </div>
            <div class="col-6">
              <a href="<?= base_url('buku-tamu/dinas') ?>" class="choice-btn w-100" id="btn-tamu-dinas">
                <span class="icon">🏛️</span>
                <span class="label">Tamu Dinas</span>
                <span class="sublabel">Instansi / pemerintah</span>
              </a>
            </div>
          </div>
        </div>

        <p class="text-center text-muted mt-4" style="font-size:0.8rem;">
          <i class="bi bi-shield-check"></i> Data Anda aman dan hanya digunakan untuk keperluan administrasi.
        </p>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
