<?php
$db = \Config\Database::connect();
$query = $db->query("SELECT logo, name FROM school_profile LIMIT 1");
$row = $query->getRow();

// Siapkan fallback logo jika data kosong
$logo = base_url('uploads/logo/default.png'); // file default.png harus ada
if ($row && !empty($row->logo)) {
  $logo = base_url('uploads/logo/' . $row->logo);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $title ?? 'Dashboard' ?> - Computer Base Test</title>
  <link rel="icon" type="image/png" href="<?= esc($logo) ?>">

  <!-- ✅ Bootstrap & Icon -->
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" />
  <link href="<?= base_url('assets/css/select2.min.css') ?>" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/dataTables.bootstrap5.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/responsive.bootstrap5.min.css') ?>">

  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <meta name="theme-color" content="#667eea">

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
    body {
      font-size: 0.875rem;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background-color: #f5f7fa;
    }

    .layout-wrapper {
      display: flex;
      flex: 1;
      transition: all 0.2s ease-in-out;
    }

    .sidebar {
      background-color: #f8f9fa;
      border-right: 1px solid #dee2e6;
      width: 220px;
      transition: width 0.2s ease-in-out;
      overflow-x: hidden;
      white-space: nowrap;
    }

    .sidebar.minimized {
      width: 60px;
    }

    .sidebar .nav-link {
      display: flex;
      align-items: center;
      gap: .5rem;
      padding: 0.75rem;
      color: #333;
    }

    .sidebar .nav-link.active {
      background-color: #0d6efd;
      color: #fff !important;
    }

    .sidebar.minimized .nav-link span.label {
      display: none;
    }

    .main-content {
      flex: 1;
      transition: all 0.2s ease-in-out;
      padding: 1rem 1.25rem;
    }

    .navbar {
      height: 56px;
    }

    footer {
      background-color: #f8f9fa;
      border-top: 1px solid #dee2e6;
      padding: 0.75rem;
      text-align: center;
      font-size: 0.875rem;
      color: #6c757d;
    }

    @media (max-width: 991.98px) {
      .sidebar {
        display: none;
      }

      .offcanvas {
        width: 220px;
      }
    }
  </style>
</head>

<body>
  <!-- 🔹 NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <div class="container-fluid">
      <button class="btn btn-outline-primary d-lg-none me-2" data-bs-toggle="offcanvas"
        data-bs-target="#sidebarMenu">☰</button>
      <button id="sidebarToggle" class="btn btn-outline-secondary d-none d-lg-inline me-2">⇔</button>

      <a class="navbar-brand fw-semibold" href="<?= base_url('/dashboard') ?>">
        📘 CBT <?= esc($school['name'] ?? 'Sekolah') ?></a>
      <div class="ms-auto">
        <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-danger">Logout</a>
      </div>
    </div>
  </nav>

  <!-- 🔹 LAYOUT WRAPPER -->
  <div class="layout-wrapper">
    <!-- Sidebar desktop -->
    <nav id="sidebarDesktop" class="sidebar">
      <?php $user = session()->get('user'); ?>
      <?php if ($user['role_id'] == 1): ?>
        <?= $this->include('layouts/partials/sidebar_admin') ?>
      <?php elseif ($user['role_id'] == 2): ?>
        <?= $this->include('layouts/partials/sidebar_guru') ?>
      <?php elseif ($user['role_id'] == 3): ?>
        <?= $this->include('layouts/partials/sidebar_siswa') ?>
      <?php endif; ?>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
      <?= $this->renderSection('content') ?>
    </main>
  </div>

  <!-- 🔹 FOOTER -->
  <footer>
    &copy; <?= date('Y') ?> SakaSalika CBT &mdash; v<?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?>
  </footer>

  <!-- 🔹 Sidebar mobile offcanvas -->
  <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Menu</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
      <?php if ($user['role_id'] == 1): ?>
        <?= $this->include('layouts/partials/sidebar_admin') ?>
      <?php elseif ($user['role_id'] == 2): ?>
        <?= $this->include('layouts/partials/sidebar_guru') ?>
      <?php elseif ($user['role_id'] == 3): ?>
        <?= $this->include('layouts/partials/sidebar_siswa') ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- JS -->
  <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/dataTables.bootstrap5.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/dataTables.responsive.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/responsive.bootstrap5.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/select2.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

  <script>
    // Global DataTable Responsive Configuration
    $.extend(true, $.fn.dataTable.defaults, {
      responsive: true,
      autoWidth: false,
      language: {
        emptyTable: "Tidak ada data tersedia",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(difilter dari _MAX_ total data)",
        lengthMenu: "Tampilkan _MENU_ data",
        loadingRecords: "Memuat...",
        processing: "Memproses...",
        search: "Cari:",
        zeroRecords: "Data tidak ditemukan",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        },
        aria: {
          sortAscending: ": aktifkan untuk mengurutkan kolom ascending",
          sortDescending: ": aktifkan untuk mengurutkan kolom descending"
        }
      }
    });

    $(function () {
      $(".select2").select2({
        placeholder: "Pilih opsi",
        allowClear: true,
        width: "100%",
      });
    });

    document.addEventListener("DOMContentLoaded", function () {
      const sidebar = document.getElementById("sidebarDesktop");
      const toggleBtn = document.getElementById("sidebarToggle");

      if (localStorage.getItem("sidebar") === "minimized") {
        sidebar.classList.add("minimized");
      }

      toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("minimized");

        if (sidebar.classList.contains("minimized")) {
          localStorage.setItem("sidebar", "minimized");
        } else {
          localStorage.setItem("sidebar", "expanded");
        }
      });

      // 🔔 Global Flashdata Alerts (SweetAlert2)
      <?php if (session()->getFlashdata('success')): ?>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: '<?= session()->getFlashdata('success') ?>',
          timer: 3000,
          showConfirmButton: false
        });
      <?php endif; ?>

      <?php if (session()->getFlashdata('error')): ?>
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan',
          text: '<?= session()->getFlashdata('error') ?>'
        });
      <?php endif; ?>

      <?php if (session()->getFlashdata('warning')): ?>
        Swal.fire({
          icon: 'warning',
          title: 'Peringatan',
          text: '<?= session()->getFlashdata('warning') ?>'
        });
      <?php endif; ?>

      <?php if (session()->getFlashdata('license_warning')): ?>
        Swal.fire({
          icon: 'warning',
          title: 'Lisensi',
          text: '<?= session()->getFlashdata('license_warning') ?>',
          footer: '<a href="<?= base_url('activate') ?>">Cek Status Lisensi</a>'
        });
      <?php endif; ?>
    });
  </script>

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
  <!-- 💓 SESSION HEARTBEAT -->
  <script>
    (function () {
      // Heartbeat every 5 minutes (300,000 ms)
      const heartbeatInterval = 300000;
      const heartbeatUrl = '<?= base_url('admin/cbt/heartbeat') ?>';

      function doHeartbeat() {
        $.get(heartbeatUrl)
          .done(function (data) {
            // Update CSRF meta tag if hash is provided
            if (data && data.csrf_hash) {
              $('meta[name="<?= csrf_token() ?>"]').attr('content', data.csrf_hash);
              // Also update any hidden CSRF inputs in the page
              $('input[name="<?= csrf_token() ?>"]').val(data.csrf_hash);
            }
            console.log('💓 Heartbeat: Session kept alive (' + new Date().toLocaleTimeString() + ')');
          })
          .fail(function () {
            console.warn('💔 Heartbeat failed. Session might expire.');
          });
      }

      // Initial delay set to interval to avoid immediate ping after page load
      setInterval(doHeartbeat, heartbeatInterval);
    })();
  </script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>