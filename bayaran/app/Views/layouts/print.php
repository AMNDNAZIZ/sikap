<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= esc($title ?? 'Cetak') ?></title>
  <link rel="stylesheet" href="<?= base_url('public/assets/css/bootstrap.min.css') ?>">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 14px;
      color: #000;
    }

    @media print {
      .no-print {
        display: none !important;
      }
    }

    h5 {
      font-weight: bold;
      margin-top: 20px;
      margin-bottom: 20px;
      text-align: center;
      text-transform: uppercase;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1rem;
    }

    th, td {
      padding: 6px 10px;
      border: 1px solid #999;
      text-align: left;
    }

    .table-bordered th,
    .table-bordered td {
      border: 1px solid #dee2e6 !important;
    }

    .table-borderless td, 
    .table-borderless th, 
    .table-borderless tr {
      border: none !important;
    }

    .table-light {
      background-color: #f8f9fa;
    }
  </style>
</head>
<body>

<?= $this->renderSection('content') ?>

<script>
  window.print(); // Cetak otomatis
</script>

</body>
</html>
