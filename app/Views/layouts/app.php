<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $title ?? 'Dashboard' ?> - <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

  <!-- PWA Meta Tags -->
  <meta name="theme-color" content="#0d6efd">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="SIKAP">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= base_url('assets/images/apple-touch-icon.png') ?>">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  
  <!-- CKEditor Custom Styles -->
  <link href="<?= base_url('css/ckeditor-custom.css') ?>" rel="stylesheet" />

  <style>
    body {
      font-size: 0.875rem;
    }

    .nav-link.active {
      background-color: #0d6efd;
      color: #fff !important;
    }

    .sidebar-heading {
      font-size: 1rem;
      font-weight: 600;
    }

    @media (max-width: 767.98px) {
      .h2 {
        font-size: 1.25rem;
      }
    }

    /* Sidebar desktop */
    @media (min-width: 992px) {
      .layout-wrapper {
        display: flex;
        height: calc(100vh - 56px);
        overflow: hidden;
      }

      .sidebar {
        width: 60px;
        transition: width 0.2s ease-in-out;
        overflow-x: hidden;
        white-space: nowrap;
        min-height: 100%;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
      }

      .sidebar .nav-link {
        padding: 0.75rem;
        display: flex;
        align-items: center;
        gap: .5rem;
      }

      .sidebar .nav-link span.label {
        display: none;
      }

      .sidebar.sidebar-expanded {
        width: 220px;
      }

      .sidebar.sidebar-expanded .nav-link span.label {
        display: inline;
      }

      #mainContent {
        flex-grow: 1;
        transition: all 0.2s ease-in-out;
        overflow-y: auto;
      }
    }

    @media (min-width: 992px) {
      .layout-wrapper {
        display: flex;
        height: calc(100vh - 56px);
        overflow: hidden;
      }

      .sidebar {
        width: 60px;
        transition: width 0.2s ease-in-out;
        overflow-x: hidden;
        white-space: nowrap;
        min-height: 100%;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
        flex-shrink: 0;
        /* 🟢 tambahkan ini: cegah sidebar mengecil */
      }

      .sidebar.sidebar-expanded {
        width: 220px;
      }

      .sidebar .nav-link {
        padding: 0.75rem;
        display: flex;
        align-items: center;
        gap: .5rem;
      }

      .sidebar .nav-link span.label {
        display: none;
      }

      .sidebar.sidebar-expanded .nav-link span.label {
        display: inline;
      }

      #mainContent {
        flex-grow: 1;
        transition: all 0.2s ease-in-out;
        overflow-y: auto;
        overflow-x: hidden;
        /* 🟢 cegah konten dorong ke samping */
        min-width: 0;
        /* 🟢 penting agar flex item bisa menyusut sesuai viewport */
      }
    }

    footer {
      background-color: #f8f9fa;
      border-top: 1px solid #dee2e6;
      padding: 1rem 0;
      text-align: center;
      margin-top: auto;
    }

    footer p {
      margin-bottom: 0.5rem;
    }

    footer .social a {
      margin: 0 0.5rem;
      text-decoration: none;
      font-size: 1.2rem;
    }

    /* ========== MOBILE OPTIMIZATIONS ========== */

    /* Responsive Tables */
    .table-responsive {
      -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 767.98px) {

      /* Larger touch targets */
      .btn {
        min-height: 44px;
        padding: 0.5rem 1rem;
      }

      .btn-sm {
        min-height: 38px;
      }

      /* Better form inputs */
      .form-control,
      .form-select {
        min-height: 44px;
        font-size: 16px;
        /* Prevents zoom on iOS */
      }

      /* Card spacing */
      .card {
        margin-bottom: 1rem;
      }

      .card-body {
        padding: 1rem;
      }

      /* Table improvements */
      table {
        font-size: 0.85rem;
      }

      table th,
      table td {
        padding: 0.5rem 0.25rem;
      }

      /* Alert improvements */
      .alert {
        font-size: 0.875rem;
        padding: 0.75rem;
      }

      /* Modal improvements */
      .modal-dialog {
        margin: 0.5rem;
      }

      /* Offcanvas width */
      .offcanvas {
        max-width: 280px;
      }

      /* Better spacing for main content */
      #mainContent {
        padding: 1rem !important;
      }

      /* Stack columns on mobile */
      .row>[class*="col-"] {
        margin-bottom: 1rem;
      }

      /* Navbar adjustments */
      .navbar-brand {
        font-size: 1rem;
      }

      /* DataTables mobile */
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter {
        margin-bottom: 0.75rem;
      }

      .dataTables_wrapper .dataTables_paginate {
        margin-top: 0.75rem;
      }

      /* Select2 mobile */
      .select2-container {
        font-size: 16px;
      }

      .select2-container .select2-selection--single {
        min-height: 44px;
      }

      .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
      }

      .select2-container .select2-selection--single .select2-selection__arrow {
        height: 42px;
      }
    }

    /* Tablet optimizations */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .card-body {
        padding: 1.25rem;
      }

      #mainContent {
        padding: 1.5rem !important;
      }
    }

    /* Touch-friendly improvements for all devices */
    a,
    button {
      -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    }

    /* Prevent text selection on double-tap */
    .btn,
    .nav-link {
      -webkit-user-select: none;
      user-select: none;
    }

    /* Sidebar Improvements */
    .sidebar .nav-link[data-bs-toggle="collapse"] {
      position: relative;
      font-weight: 500;
    }

    .sidebar .nav-link .label {
      flex-grow: 1;
    }

    .sidebar .nav-link .chevron {
      transition: transform 0.2s ease-in-out;
      font-size: 0.75rem;
      opacity: 0.7;
    }

    .sidebar .nav-link[aria-expanded="true"] .chevron {
      transform: rotate(180deg);
    }

    .sidebar .nav-link[data-bs-toggle="collapse"]:not(.active) {
      background-color: rgba(0, 0, 0, 0.02);
    }
  </style>
</head>

<body>
  <div class="container-fluid p-0">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm" style="height: 56px;">
      <div class="container-fluid">
        <!-- Tombol toggle untuk mobile -->
        <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
          ☰
        </button>

        <!-- Tombol toggle untuk desktop -->
        <button id="sidebarToggle" class="btn btn-outline-secondary d-none d-lg-inline me-2">
          ⇔
        </button>

        <a class="navbar-brand" href="<?= base_url('/dashboard') ?>">📘 SIKAP</a>
        <div class="ms-auto">
          <?php if (session()->has('user')): ?>
            <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-danger">Logout</a>
          <?php else: ?>
            <a href="<?= base_url('login') ?>" class="btn btn-sm btn-primary">Login SIKAP</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <!-- Layout utama -->
    <div class="layout-wrapper">
      <!-- Sidebar desktop -->
      <nav id="sidebarDesktop" class="sidebar d-none d-lg-block">
        <?php $user = session()->get('user'); ?>
        <?php if ($user && isset($user['role_id'])): ?>
          <?php if ($user['role_id'] == 1): ?>
            <?= $this->include('layouts/partials/sidebar_admin') ?>
          <?php elseif ($user['role_id'] == 2): ?>
            <?= $this->include('layouts/partials/sidebar_kepsek') ?>
          <?php elseif ($user['role_id'] == 3): ?>
            <?= $this->include('layouts/partials/sidebar_guru') ?>
          <?php elseif ($user['role_id'] == 4): ?>
            <?= $this->include('layouts/partials/sidebar_ortu') ?>
          <?php elseif ($user['role_id'] == 5): ?>
            <?= $this->include('layouts/partials/sidebar_siswa') ?>
          <?php elseif ($user['role_id'] == 6): ?>
            <?= $this->include('layouts/partials/sidebar_kontributor') ?>
          <?php elseif ($user['role_id'] == 7): ?>
            <?= $this->include('layouts/partials/sidebar_staf') ?>
          <?php endif; ?>
        <?php else: ?>
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link text-dark" href="<?= base_url('/') ?>">
                <i class="bi bi-house-door"></i> <span class="label">Beranda</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-dark active" href="<?= base_url('tentang') ?>">
                <i class="bi bi-info-circle"></i> <span class="label">Tentang SIKAP</span>
              </a>
            </li>
            <li class="nav-item mt-3 px-3">
              <a class="btn btn-primary btn-sm w-100 rounded-pill" href="<?= base_url('login') ?>">
                <i class="bi bi-box-arrow-in-right"></i> <span class="label">Login</span>
              </a>
            </li>
          </ul>
        <?php endif; ?>
      </nav>

      <!-- Sidebar mobile offcanvas -->
      <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMenu"
        aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu Publik</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
          <?php if ($user && isset($user['role_id'])): ?>
            <?php if ($user['role_id'] == 1): ?>
              <?= $this->include('layouts/partials/sidebar_admin') ?>
            <?php elseif ($user['role_id'] == 2): ?>
              <?= $this->include('layouts/partials/sidebar_kepsek') ?>
            <?php elseif ($user['role_id'] == 3): ?>
              <?= $this->include('layouts/partials/sidebar_guru') ?>
            <?php elseif ($user['role_id'] == 4): ?>
              <?= $this->include('layouts/partials/sidebar_ortu') ?>
            <?php elseif ($user['role_id'] == 5): ?>
              <?= $this->include('layouts/partials/sidebar_siswa') ?>
            <?php elseif ($user['role_id'] == 6): ?>
              <?= $this->include('layouts/partials/sidebar_kontributor') ?>
            <?php elseif ($user['role_id'] == 7): ?>
              <?= $this->include('layouts/partials/sidebar_staf') ?>
            <?php endif; ?>
          <?php else: ?>
            <ul class="nav flex-column p-3">
              <li class="nav-item mb-2">
                <a class="nav-link text-dark" href="<?= base_url('/') ?>">
                  <i class="bi bi-house-door me-2"></i> Beranda
                </a>
              </li>
              <li class="nav-item mb-2">
                <a class="nav-link text-dark active" href="<?= base_url('tentang') ?>">
                  <i class="bi bi-info-circle me-2"></i> Tentang SIKAP
                </a>
              </li>
              <li class="nav-item mt-4">
                <a class="btn btn-primary w-100 rounded-pill" href="<?= base_url('login') ?>">
                  Login SIKAP
                </a>
              </li>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <!-- Main Content -->
      <main id="mainContent" class="px-3 px-md-4 py-3">
        <?= $this->renderSection('content') ?>
      </main>
    </div>

    <?= $this->include('layouts/footer') ?>
  </div>

  <!-- JS libraries -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

  <script>
    // Realtime Clock
    function updateClock() {
      const now = new Date();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const timeString = `${hours}:${minutes}:${seconds}`;

      const clockElements = document.querySelectorAll('.realtime-clock');
      clockElements.forEach(el => {
        el.textContent = timeString;
      });
    }

    setInterval(updateClock, 1000);
    updateClock();

    $(function () {
      // Select2 with mobile optimization
      $(".select2").select2({
        placeholder: "Pilih opsi",
        allowClear: true,
        width: "100%",
        dropdownAutoWidth: true,
        minimumResultsForSearch: 5 // Hide search on mobile for small lists
      });

      // DataTables responsive configuration
      if ($.fn.DataTable) {
        $.extend($.fn.dataTable.defaults, {
          responsive: true,
          language: {
            lengthMenu: "_MENU_",
            search: "_INPUT_",
            searchPlaceholder: "Cari...",
            info: "_START_-_END_ dari _TOTAL_",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(filter dari _MAX_)",
            paginate: {
              first: "«",
              last: "»",
              next: "›",
              previous: "‹"
            }
          },
          pageLength: 25,
          lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
        });
      }
    });
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const sidebar = document.querySelector("#sidebarDesktop");
      const toggleBtn = document.getElementById("sidebarToggle");

      if (localStorage.getItem("sidebar") === "expanded") {
        sidebar.classList.add("sidebar-expanded");
      }

      toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("sidebar-expanded");
        localStorage.setItem("sidebar", sidebar.classList.contains("sidebar-expanded") ? "expanded" : "mini");
      });
    });
  </script>

  <!-- PWA Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
          .then(registration => {
            console.log('[PWA] Service Worker registered:', registration.scope);
          })
          .catch(error => {
            console.log('[PWA] Service Worker registration failed:', error);
          });
      });
    }
  </script>

  <!-- Session Keep-Alive Script -->
  <?php if (session()->get('logged_in')): ?>
  <script src="<?= base_url('assets/js/session-keepalive.js') ?>"></script>
  <?php endif; ?>

  <!-- Chat Mention Badge Polling -->
  <?php
  $user = session()->get('user');
  $roleId = $user['role_id'] ?? 0;
  $isStaffRole = in_array($roleId, [1, 2, 3, 7]);
  $isClassChatRole = in_array($roleId, [1, 2, 3]);
  ?>
  <?php if ($isStaffRole || $isClassChatRole): ?>
  <script>
    (function() {
      // Fungsi notif suara — digunakan oleh kedua badge
      function playNotifSound() {
        try {
          var ctx = new (window.AudioContext || window.webkitAudioContext)();
          var osc = ctx.createOscillator();
          var gain = ctx.createGain();
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.frequency.setValueAtTime(880, ctx.currentTime);
          osc.frequency.setValueAtTime(660, ctx.currentTime + 0.1);
          gain.gain.setValueAtTime(0.25, ctx.currentTime);
          gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
          osc.start(ctx.currentTime);
          osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}
      }

      <?php if ($isClassChatRole): ?>
      // Badge obrolan kelas (mentionBadge) — hanya mention dari room kelas
      var prevClassCount = -1; // -1 = belum diinisialisasi
      function updateClassMentionBadge() {
        $.getJSON("<?= base_url('admin/chat/mentions') ?>", function(res) {
          var badge = document.getElementById('mentionBadge');
          if (!badge) return;
          var count = res.count || 0;
          // Bunyikan suara hanya jika count naik SETELAH inisialisasi pertama
          if (prevClassCount >= 0 && count > prevClassCount && !window.location.href.includes('staff-chat')) {
            playNotifSound();
          }
          prevClassCount = count;
          badge.textContent = count;
          badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
      }
      updateClassMentionBadge();
      setInterval(updateClassMentionBadge, 15000); // setiap 15 detik
      <?php endif; ?>

      <?php if ($isStaffRole): ?>
      // Badge obrolan staff (staffMentionBadge) — hanya mention dari staff room
      var prevStaffCount = -1; // -1 = belum diinisialisasi
      function updateStaffMentionBadge() {
        $.getJSON("<?= base_url('admin/staff-chat/mentions') ?>", function(res) {
          var badge = document.getElementById('staffMentionBadge');
          if (!badge) return;
          var count = res.count || 0;
          // Bunyikan suara hanya jika count naik SETELAH inisialisasi pertama
          if (prevStaffCount >= 0 && count > prevStaffCount && !window.location.href.includes('staff-chat')) {
            playNotifSound();
          }
          prevStaffCount = count;
          badge.textContent = count;
          badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
      }
      updateStaffMentionBadge();
      setInterval(updateStaffMentionBadge, 15000); // setiap 15 detik
      <?php endif; ?>
    })();
  </script>
  <?php endif; ?>

  <?= $this->renderSection('scripts') ?>

</body>

</html>