<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; margin: 0; }
    .kop-wrapper { width: 100%; margin-bottom: 4px; text-align: center; }
    .kop-wrapper img { width: 100%; height: auto; max-width: 100%; display: block; margin: 0 auto; }
    .garis-pembatas { border-top: 2px solid #000; margin-top: 5px; margin-bottom: 15px; }
    .content { padding: 0 4px; }
  </style>
</head>
<body>
  <?php if (!empty($kop_base64)): ?>
  <div class="kop-wrapper">
    <img src="<?= $kop_base64 ?>" alt="Kop Surat" />
  </div>
  <div class="garis-pembatas"></div>
  <?php endif; ?>

  <div class="content">
    <?= $content ?? '' ?>
  </div>
</body>
</html>
