<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR Code Buku Tamu — <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- qrcodejs: library QR khusus browser, tidak butuh server-side -->
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .qr-card { box-shadow: none !important; }
    }
    body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    .qr-card {
      max-width: 420px;
      margin: 40px auto;
      background: white;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      padding: 2rem;
      text-align: center;
    }
    .school-logo { width: 70px; height: 70px; object-fit: contain; }
    .qr-wrapper {
      background: white;
      border: 3px solid #1a6fc4;
      border-radius: 16px;
      padding: 1rem;
      margin: 1.5rem auto;
      display: inline-block;
      line-height: 0; /* remove gap below canvas */
    }
    /* qrcodejs renders canvas + img (fallback) — hide img to avoid double */
    #qrcode img  { display: none !important; }
    #qrcode canvas { display: block !important; }
    .url-box {
      background: #f0f7ff;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
      padding: 0.5rem 1rem;
      font-size: 0.85rem;
      word-break: break-all;
      color: #1e40af;
    }
    .instruction { font-size: 0.9rem; color: #555; line-height: 1.6; }
  </style>
</head>
<body>

  <div class="qr-card">

    <!-- Logo & Nama Sekolah -->
    <?php if (!empty($school['logo'])): ?>
      <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo" class="school-logo mb-2">
    <?php endif; ?>
    <h1 class="h5 fw-bold text-primary mb-0"><?= esc($school['name'] ?? 'Buku Tamu Digital') ?></h1>
    <p class="text-muted mb-3" style="font-size:0.85rem;">Scan untuk mengisi buku tamu</p>

    <!-- QR Code — di-render oleh qrcodejs di browser -->
    <div class="qr-wrapper">
      <div id="qrcode"></div>
    </div>

    <!-- URL Manual -->
    <div class="url-box mb-3">
      <i class="bi bi-link-45deg me-1"></i>
      <?= esc($url) ?>
    </div>

    <p class="instruction">
      📱 Arahkan kamera smartphone Anda ke QR Code di atas,<br>
      atau ketik alamat URL di browser untuk mengisi buku tamu.
    </p>

    <!-- Tombol Print -->
    <button onclick="window.print()" class="btn btn-primary mt-3 no-print" id="btn-print">
      <i class="bi bi-printer me-1"></i>Print Halaman Ini
    </button>
    <a href="<?= base_url('admin/buku-tamu') ?>" class="btn btn-outline-secondary mt-3 ms-2 no-print" id="btn-back">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>

  <script>
    // qrcodejs API: new QRCode(element, options)
    new QRCode(document.getElementById('qrcode'), {
      text:         <?= json_encode($url) ?>,
      width:        220,
      height:       220,
      colorDark:    '#000000',
      colorLight:   '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>

</body>
</html>
