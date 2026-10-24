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
      min-width: 0;
      width: 100%;
      overflow-x: hidden;
    }

    .navbar {
      min-height: 56px;
      height: auto;
      width: 100%;
    }

    .navbar > .container-fluid {
      display: flex !important;
      flex-wrap: nowrap !important;
      align-items: center !important;
      justify-content: space-between !important;
      width: 100%;
    }

    .navbar-brand {
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
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
        width: 280px !important;
        max-width: 85vw;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
      }

      .offcanvas-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 1rem 1.25rem;
      }

      .offcanvas-header .btn-close {
        filter: brightness(0) invert(1);
      }

      .main-content {
        padding: 0.75rem 0.65rem;
      }
    }

    /* 📱 GLOBAL MOBILE OPTIMIZATIONS FOR ALL ADMIN PAGES */
    @media (max-width: 768px) {
      /* Page Headers on Mobile */
      .page-header {
        padding: 1.15rem 1rem !important;
        border-radius: 12px !important;
        margin-bottom: 1rem !important;
      }

      .page-header h1,
      .page-header h4 {
        font-size: 1.25rem !important;
        letter-spacing: -0.01em !important;
      }

      .page-header p {
        font-size: 0.82rem !important;
      }

      /* Cards & Containers on Mobile */
      .container-fluid {
        padding-left: 0.35rem !important;
        padding-right: 0.35rem !important;
      }

      .card,
      .modern-card,
      .table-card {
        border-radius: 12px !important;
        margin-bottom: 0.85rem !important;
      }

      .card-header,
      .modern-card-header {
        padding: 0.75rem 0.9rem !important;
      }

      .card-body,
      .modern-card-body {
        padding: 0.85rem 0.85rem !important;
      }

      /* DataTables Controls on Mobile */
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter {
        text-align: left !important;
        float: none !important;
        margin-bottom: 0.65rem !important;
        width: 100% !important;
      }

      .dataTables_wrapper .dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
        margin-top: 0.25rem !important;
        height: 40px !important;
        border-radius: 8px !important;
        font-size: 0.88rem !important;
      }

      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        float: none !important;
        margin-top: 0.75rem !important;
        width: 100% !important;
      }

      .dataTables_paginate .pagination {
        justify-content: center !important;
        flex-wrap: wrap !important;
        gap: 0.2rem !important;
      }

      .page-item .page-link {
        padding: 0.35rem 0.65rem !important;
        font-size: 0.8rem !important;
        border-radius: 6px !important;
      }

      /* Responsive Tables */
      .table-responsive {
        border-radius: 10px;
        -webkit-overflow-scrolling: touch;
      }

      .table {
        font-size: 0.82rem;
      }

      .table th,
      .table td {
        padding: 0.55rem 0.45rem !important;
        vertical-align: middle;
      }

      /* Button Groups & Action Buttons */
      .btn-group {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        gap: 2px !important;
      }

      .btn-group > .btn {
        border-radius: 6px !important;
        margin: 1px !important;
      }

      /* Form Controls - Touch Optimized & iOS Safari Zoom Prevention */
      .form-control,
      .form-select {
        font-size: 16px !important; /* Prevents auto-zoom on iOS */
        min-height: 42px !important;
        border-radius: 8px !important;
      }

      /* Modals on Mobile */
      .modal-dialog {
        margin: 0.5rem auto !important;
        max-width: calc(100% - 1rem) !important;
      }

      .modal-content {
        border-radius: 14px !important;
      }

      .modal-header {
        padding: 0.85rem 1rem !important;
      }

      .modal-body {
        padding: 1rem 0.85rem !important;
      }

      .modal-footer {
        padding: 0.75rem 1rem !important;
        flex-wrap: wrap !important;
      }
    }
  </style>
</head>

<body>
  <!-- 🔹 NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm w-100">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-nowrap px-3">
      <div class="d-flex align-items-center overflow-hidden me-2" style="min-width: 0;">
        <button class="btn btn-outline-primary d-lg-none me-2 flex-shrink-0" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu" aria-label="Buka Menu">☰</button>
        <button id="sidebarToggle" class="btn btn-outline-secondary d-none d-lg-inline me-2 flex-shrink-0" aria-label="Toggle Sidebar">⇔</button>

        <a class="navbar-brand fw-semibold text-truncate mb-0 fs-6" href="<?= base_url('/dashboard') ?>" title="CBT <?= esc($school['name'] ?? 'Sekolah') ?>">
          📘 CBT <?= esc($school['name'] ?? 'Sekolah') ?>
        </a>
      </div>
      <div class="flex-shrink-0 ms-auto">
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
      <h5 class="offcanvas-title fs-6 fw-bold mb-0">
        <i class="fas fa-th-large me-2"></i> Menu Aplikasi
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
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

      // Start heartbeat
      setInterval(doHeartbeat, heartbeatInterval);
      // Run immediately on page load
      doHeartbeat();
    })();

    // Ensure Bootstrap modals are attached directly to body to prevent stacking context clipping
    $(document).on('show.bs.modal', '.modal', function () {
      if (this.parentElement !== document.body) {
        document.body.appendChild(this);
      }
    });
  </script>

  <!-- 📱 MASTER MOBILE RESPONSIVE OVERRIDE (Ensures 100% of admin pages are mobile optimized) -->
  <style>
    @media (max-width: 768px) {
      /* Page Header across all admin views */
      .page-header {
        padding: 1.15rem 1rem !important;
        border-radius: 12px !important;
        margin-top: 0.5rem !important;
        margin-bottom: 0.85rem !important;
      }

      .page-header h1,
      .page-header h4 {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.25rem !important;
      }

      .page-header p {
        font-size: 0.82rem !important;
        margin-bottom: 0.75rem !important;
        opacity: 0.9 !important;
      }

      .page-header-actions {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 0.4rem !important;
        width: 100% !important;
      }

      .page-header-actions .btn,
      .page-header-actions .btn-modern {
        flex: 1 1 calc(50% - 0.4rem) !important;
        min-height: 40px !important;
        padding: 0.5rem 0.65rem !important;
        font-size: 0.82rem !important;
        justify-content: center !important;
        text-align: center !important;
      }

      /* Container padding */
      .container-fluid {
        padding-left: 0.35rem !important;
        padding-right: 0.35rem !important;
      }

      /* Card styling */
      .card,
      .modern-card,
      .table-card {
        border-radius: 12px !important;
        margin-bottom: 0.85rem !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
      }

      .card-header,
      .modern-card-header {
        padding: 0.75rem 0.85rem !important;
      }

      .card-title,
      .modern-card-title {
        font-size: 0.95rem !important;
      }

      .card-body,
      .modern-card-body {
        padding: 0.85rem 0.85rem !important;
      }

      /* Table & DataTables polish */
      .table-responsive {
        border-radius: 8px !important;
        margin-bottom: 0.5rem !important;
        -webkit-overflow-scrolling: touch !important;
      }

      .table,
      .table-modern {
        font-size: 0.82rem !important;
      }

      .table thead th,
      .table-modern thead th {
        padding: 0.6rem 0.45rem !important;
        font-size: 0.75rem !important;
        white-space: nowrap !important;
      }

      .table tbody td,
      .table-modern tbody td {
        padding: 0.55rem 0.45rem !important;
        vertical-align: middle !important;
      }

      /* DataTables Search & Pagination */
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter {
        text-align: left !important;
        float: none !important;
        margin-bottom: 0.5rem !important;
        width: 100% !important;
      }

      .dataTables_wrapper .dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
        margin-top: 0.25rem !important;
        height: 40px !important;
        border-radius: 8px !important;
        font-size: 0.88rem !important;
      }

      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        float: none !important;
        margin-top: 0.65rem !important;
        width: 100% !important;
        font-size: 0.8rem !important;
      }

      .dataTables_paginate .pagination {
        justify-content: center !important;
        flex-wrap: wrap !important;
        gap: 2px !important;
      }

      .page-item .page-link {
        padding: 0.35rem 0.65rem !important;
        font-size: 0.8rem !important;
        border-radius: 6px !important;
      }

      /* Buttons & Button Groups inside tables */
      .btn-group {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        gap: 3px !important;
      }

      .btn-group > .btn,
      .btn-action {
        border-radius: 6px !important;
        min-height: 34px !important;
        padding: 0.35rem 0.55rem !important;
        font-size: 0.8rem !important;
      }

      .btn-icon-only {
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
      }

      /* Form controls - 16px to prevent iOS auto-zoom */
      .form-control,
      .form-select {
        font-size: 16px !important;
        min-height: 42px !important;
        border-radius: 8px !important;
      }

      /* Modals on Mobile */
      .modal-dialog {
        margin: 0.5rem auto !important;
        max-width: calc(100% - 1rem) !important;
      }

      .modal-content {
        border-radius: 14px !important;
        border: none !important;
      }

      .modal-header {
        padding: 0.85rem 1rem !important;
      }

      .modal-body {
        padding: 1rem 0.85rem !important;
      }

      .modal-footer {
        padding: 0.75rem 1rem !important;
        gap: 0.4rem !important;
      }

      .modal-footer > * {
        flex: 1 1 calc(50% - 0.4rem) !important;
        margin: 0 !important;
      }

      /* SweetAlert on Mobile */
      .swal2-popup {
        width: 90% !important;
        padding: 1rem !important;
        border-radius: 16px !important;
      }
    }
  </style>

  <?= $this->renderSection('scripts') ?>
</body>

</html>