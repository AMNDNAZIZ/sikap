<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= esc($title) ?> - <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #f0fdf4 0%, #e8f0fe 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .sukses-card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }
    .check-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #d1fae5;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin: 0 auto 1.5rem;
    }
    .btn-back {
      min-height: 48px;
      border-radius: 12px;
    }
    @keyframes scaleIn {
      from { transform: scale(0); opacity: 0; }
      to   { transform: scale(1); opacity: 1; }
    }
    .check-icon { animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-8 col-md-6 col-lg-5">
        <div class="card sukses-card p-4 text-center">

          <div class="check-icon">✅</div>

          <h1 class="h4 fw-bold text-success">Terima Kasih!</h1>

          <?php if ($nama_guest): ?>
            <p class="text-muted mt-2">
              Halo, <strong><?= esc($nama_guest) ?></strong>!<br>
              Kunjungan Anda telah berhasil dicatat.
            </p>
          <?php else: ?>
            <p class="text-muted mt-2">Kunjungan Anda telah berhasil dicatat.</p>
          <?php endif; ?>

          <hr class="my-3">

          <p class="text-muted" style="font-size:0.9rem;">
            <i class="bi bi-clock me-1"></i>
            Waktu kunjungan: <strong><?= date('d M Y, H:i') ?> WIB</strong>
          </p>

          <p class="text-muted" style="font-size:0.85rem;">
            Silakan menunggu di area yang telah disediakan. Staf kami akan segera menemui Anda.
          </p>

          <a href="<?= base_url('buku-tamu') ?>" class="btn btn-outline-primary btn-back mt-2" id="btn-kunjungan-baru">
            <i class="bi bi-arrow-left me-1"></i> Kunjungan Baru
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
