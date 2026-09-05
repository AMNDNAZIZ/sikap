<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $title ?? 'Dashboard' ?> - <?= esc($school['name'] ?? 'ESPATA') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

  <!-- PWA Meta Tags -->
  <meta name="theme-color" content="#0d6efd">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="ESPATA">
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

    /* ── Notifikasi Dropdown ── */
    #notifList .notif-item {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      padding: 0.65rem 0.85rem;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background 0.15s;
      text-decoration: none;
      color: inherit;
    }
    #notifList .notif-item:hover  { background: #f8f9fa; }
    #notifList .notif-item.unread { background: #eef4ff; }
    #notifList .notif-item.unread:hover { background: #e2ecff; }
    #notifList .notif-icon {
      width: 32px; height: 32px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.9rem;
      flex-shrink: 0;
      margin-top: 2px;
    }
    #notifList .notif-title {
      font-size: 0.8rem;
      font-weight: 600;
      line-height: 1.3;
      margin-bottom: 2px;
      color: #212529;
    }
    #notifList .notif-msg {
      font-size: 0.74rem;
      color: #6c757d;
      line-height: 1.4;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    #notifList .notif-time {
      font-size: 0.68rem;
      color: #adb5bd;
      white-space: nowrap;
      margin-top: 1px;
    }
    /* Badge animasi */
    #notifBadge.pulse {
      animation: notif-pulse 0.6s ease-in-out 3;
    }
    @keyframes notif-pulse {
      0%,100% { transform: translate(-50%,-50%) scale(1); }
      50%      { transform: translate(-50%,-50%) scale(1.3); }
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

        <a class="navbar-brand" href="<?= base_url('/dashboard') ?>">
          <img src="<?= base_url('uploads/logo/espata.png') ?>"
               alt="ESPATA" style="height:32px; width:auto;">
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
          <?php if (session()->has('user')): ?>

            <!-- ── Dropdown Notifikasi ───────────────────────── -->
            <div class="dropdown" id="notifDropdownWrapper">
              <button class="btn btn-sm btn-outline-secondary position-relative"
                      id="notifDropdownBtn"
                      data-bs-toggle="dropdown"
                      data-bs-auto-close="outside"
                      aria-expanded="false"
                      title="Notifikasi">
                <i class="bi bi-bell fs-6"></i>
                <!-- Badge counter — disembunyikan saat 0 -->
                <span id="notifBadge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="display:none; font-size:0.6rem;">
                  0
                </span>
              </button>

              <!-- Panel dropdown -->
              <div class="dropdown-menu dropdown-menu-end shadow-sm p-0"
                   id="notifDropdownMenu"
                   style="width:340px; max-width:95vw;">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light rounded-top">
                  <span class="fw-semibold small">Notifikasi</span>
                  <button class="btn btn-link btn-sm text-muted p-0 text-decoration-none"
                          id="notifMarkAllBtn" style="font-size:0.75rem;">
                    Tandai semua dibaca
                  </button>
                </div>

                <!-- Daftar notifikasi -->
                <div id="notifList"
                     style="max-height:380px; overflow-y:auto;">
                  <div class="text-center text-muted py-4 small" id="notifEmpty">
                    <i class="bi bi-bell-slash fs-4 d-block mb-1 opacity-50"></i>
                    Tidak ada notifikasi baru
                  </div>
                </div>

                <!-- Footer -->
                <div class="border-top text-center py-2 bg-light rounded-bottom">
                  <a href="<?= base_url('notifications') ?>"
                     class="text-decoration-none small text-primary fw-semibold">
                    Lihat semua notifikasi
                  </a>
                </div>
              </div>
            </div>
            <!-- ─────────────────────────────────────────────── -->

            <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-danger">Logout</a>
          <?php else: ?>
            <a href="<?= base_url('login') ?>" class="btn btn-sm btn-primary">Login ESPATA</a>
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
                <i class="bi bi-info-circle"></i> <span class="label">Tentang ESPATA</span>
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
                  <i class="bi bi-info-circle me-2"></i> Tentang ESPATA
                </a>
              </li>
              <li class="nav-item mt-4">
                <a class="btn btn-primary w-100 rounded-pill" href="<?= base_url('login') ?>">
                  Login ESPATA
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

  <!-- ── Notifikasi In-App Polling ─────────────────────────────────────── -->
  <?php if (session()->get('logged_in')): ?>
  <script>
  (function () {
    'use strict';

    var NOTIF_COUNT_URL  = "<?= base_url('notifications/count') ?>";
    var NOTIF_RECENT_URL = "<?= base_url('notifications/recent') ?>";
    var NOTIF_READ_ALL   = "<?= base_url('notifications/read-all') ?>";
    var CSRF_NAME        = "<?= csrf_token() ?>";
    var CSRF_HASH        = "<?= csrf_hash() ?>";

    var prevCount      = -1;
    var dropdownOpen   = false;
    var notifLoaded    = false;   // sudah load isi dropdown setidaknya 1x?

    var $badge    = $('#notifBadge');
    var $list     = $('#notifList');
    var $empty    = $('#notifEmpty');
    var $markAll  = $('#notifMarkAllBtn');
    var $dropdown = $('#notifDropdownMenu');

    /* ── helper: render 1 item notifikasi ── */
    function renderItem(n) {
      var bgClass   = n.is_read == 0 ? 'unread' : '';
      var iconBg    = 'bg-' + n.color + ' bg-opacity-10 text-' + n.color;
      var url       = n.url
        ? "<?= base_url('notifications/read') ?>/" + n.id + "?redirect=" + encodeURIComponent(n.url)
        : "<?= base_url('notifications/read') ?>/" + n.id;

      return '<a href="' + url + '" class="notif-item ' + bgClass + '" data-id="' + n.id + '">' +
        '<span class="notif-icon ' + iconBg + '">' +
          '<i class="' + n.icon + '"></i>' +
        '</span>' +
        '<div class="flex-grow-1 overflow-hidden">' +
          '<div class="notif-title">' + $('<span>').text(n.title).html() + '</div>' +
          (n.message ? '<div class="notif-msg">' + $('<span>').text(n.message).html() + '</div>' : '') +
        '</div>' +
        '<span class="notif-time">' + n.time_ago + '</span>' +
      '</a>';
    }

    /* ── update badge angka di navbar ── */
    function updateBadge(count) {
      if (count > 0) {
        var label = count > 99 ? '99+' : count;
        $badge.text(label).show();
        if (prevCount >= 0 && count > prevCount) {
          $badge.addClass('pulse');
          setTimeout(function() { $badge.removeClass('pulse'); }, 1800);
          // suara notifikasi ringan
          try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator(), g = ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);
            osc.frequency.setValueAtTime(1047, ctx.currentTime);
            osc.frequency.setValueAtTime(784, ctx.currentTime + 0.12);
            g.gain.setValueAtTime(0.15, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc.start(); osc.stop(ctx.currentTime + 0.35);
          } catch(e) {}
        }
      } else {
        $badge.text('0').hide();
      }
      prevCount = count;
    }

    /* ── polling jumlah unread (ringan, setiap 30 detik) ── */
    function pollCount() {
      $.getJSON(NOTIF_COUNT_URL, function(res) {
        updateBadge(res.count || 0);
      });
    }
    pollCount();
    setInterval(pollCount, 30000);

    /* ── muat isi dropdown saat dibuka ── */
    $('#notifDropdownBtn').on('click', function () {
      if (notifLoaded) return; // sudah ada isi, jangan reload terus
      loadRecent();
    });

    function loadRecent() {
      $list.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-secondary"></span></div>');
      $empty.hide();

      $.getJSON(NOTIF_RECENT_URL, function(res) {
        notifLoaded = true;
        var items = res.items || [];
        updateBadge(res.unread || 0);

        if (items.length === 0) {
          $list.html('');
          $empty.show();
          return;
        }

        var html = '';
        items.forEach(function(n) { html += renderItem(n); });
        $list.html(html);

        // Saat dropdown ditutup, izinkan reload fresh berikutnya
      });
    }

    // Reset notifLoaded saat dropdown ditutup agar data selalu fresh
    document.getElementById('notifDropdownBtn').addEventListener('hide.bs.dropdown', function() {
      notifLoaded = false;
    });

    /* ── Tandai semua dibaca ── */
    $markAll.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var data = {};
      data[CSRF_NAME] = CSRF_HASH;
      $.post(NOTIF_READ_ALL, data, function() {
        // Perbarui UI: hapus class unread dari semua item
        $list.find('.notif-item.unread').removeClass('unread');
        updateBadge(0);
        notifLoaded = false; // paksa reload next open
      });
    });

  })();
  </script>
  <?php endif; ?>

  <?= $this->renderSection('scripts') ?>

</body>

</html>