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
  <meta name="ping-url" content="<?= base_url('auth/ping-session') ?>">
  <meta name="login-url" content="<?= base_url('login') ?>">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  
  <!-- CKEditor Custom Styles -->
  <link href="<?= base_url('css/ckeditor-custom.css') ?>" rel="stylesheet" />

  <!-- Core JavaScript Libraries (Loaded in HEAD to support inline scripts in views) -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

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

    /* ==========================================================================
       SIDEBAR & LAYOUT STYLES
       ========================================================================== */
    html, body {
      overflow-x: hidden !important;
      max-width: 100vw !important;
      width: 100% !important;
    }
    .layout-wrapper {
      display: flex;
      min-height: calc(100vh - 56px);
      width: 100% !important;
      max-width: 100vw !important;
      overflow-x: hidden;
    }
    #mainContent {
      flex: 1 1 0%;
      min-width: 0 !important;
      max-width: 100% !important;
      width: 100% !important;
      overflow-x: hidden;
    }

    /* Category headers for grouping */
    .sidebar-category-header {
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      color: #8c98a4;
      padding: 0.75rem 0.85rem 0.25rem;
      margin-top: 0.35rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    /* Submenu styling (indented under parent dropdowns) */
    .sidebar .sub-menu {
      background-color: rgba(0, 0, 0, 0.02);
      border-left: 2px solid #dee2e6;
      margin-left: 1.35rem;
      padding-left: 0.25rem;
      border-radius: 0 0 0.375rem 0;
    }
    .sidebar .sub-menu .nav-link {
      padding: 0.42rem 0.75rem;
      font-size: 0.84rem;
      margin: 1px 4px;
    }

    /* Navigation links styling */
    .sidebar .nav-link {
      padding: 0.55rem 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      color: #495057;
      border-radius: 0.375rem;
      margin: 1px 6px;
      font-size: 0.875rem;
      font-weight: 500;
      transition: background-color 0.15s ease, color 0.15s ease;
      text-decoration: none;
    }
    .sidebar .nav-link i {
      font-size: 1.1rem;
      min-width: 22px;
      text-align: center;
      color: #6c757d;
      transition: color 0.15s ease;
      flex-shrink: 0;
    }
    .sidebar .nav-link:hover {
      background-color: #e9ecef;
      color: #0d6efd;
    }
    .sidebar .nav-link:hover i {
      color: #0d6efd;
    }
    .sidebar .nav-link.active {
      background-color: #e7f1ff;
      color: #0d6efd;
      font-weight: 600;
    }
    .sidebar .nav-link.active i {
      color: #0d6efd;
    }
    .sidebar .nav-link .chevron {
      font-size: 0.7rem;
      transition: transform 0.2s ease;
      opacity: 0.6;
    }
    .sidebar .nav-link[aria-expanded="true"] .chevron {
      transform: rotate(180deg);
    }

    /* Sidebar Desktop (>= 992px) */
    @media (min-width: 992px) {
      .layout-wrapper {
        height: calc(100vh - 56px);
        overflow: hidden;
      }

      .sidebar {
        width: 62px;
        transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        overflow-y: auto;
        white-space: nowrap;
        min-height: 100%;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
        flex-shrink: 0;
        padding-bottom: 2rem;
      }

      /* Scrollbar minimalis pada desktop sidebar */
      .sidebar::-webkit-scrollbar {
        width: 5px;
      }
      .sidebar::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
      }
      .sidebar::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
      }

      /* Sembunyikan label, badge, dan chevron saat sidebar collapsed (mini mode 62px) */
      .sidebar:not(.sidebar-expanded) .nav-link span.label,
      .sidebar:not(.sidebar-expanded) .nav-link .badge,
      .sidebar:not(.sidebar-expanded) .nav-link .chevron,
      .sidebar:not(.sidebar-expanded) .sub-menu {
        display: none !important;
      }

      /* Saat mini mode, category header berubah jadi garis pemisah tipis */
      .sidebar:not(.sidebar-expanded) .sidebar-category-header {
        font-size: 0;
        height: 1px;
        background-color: #dee2e6;
        padding: 0;
        margin: 0.6rem 0.5rem;
        overflow: hidden;
      }

      /* Sidebar Expanded */
      .sidebar.sidebar-expanded {
        width: 245px;
      }
      .sidebar.sidebar-expanded .nav-link span.label {
        display: inline;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      #mainContent {
        flex-grow: 1;
        transition: all 0.2s ease-in-out;
        overflow-y: auto;
        overflow-x: hidden;
        min-width: 0;
      }
    }

    /* Mobile offcanvas overrides */
    .offcanvas-body .sidebar-category-header {
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #8c98a4;
      padding: 0.85rem 1rem 0.25rem;
    }
    .offcanvas-body .nav-link {
      padding: 0.65rem 1rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: #495057;
      font-size: 0.9rem;
    }
    .offcanvas-body .nav-link i {
      font-size: 1.15rem;
      min-width: 24px;
      text-align: center;
      color: #6c757d;
    }
    .offcanvas-body .nav-link.active {
      background-color: #e7f1ff;
      color: #0d6efd;
      font-weight: 600;
    }
    .offcanvas-body .nav-link.active i {
      color: #0d6efd;
    }
    .offcanvas-body .sub-menu {
      background-color: rgba(0, 0, 0, 0.02);
      border-left: 2px solid #dee2e6;
      margin-left: 1.5rem;
      padding-left: 0.25rem;
    }
    .offcanvas-body .sub-menu .nav-link {
      padding: 0.45rem 0.85rem;
      font-size: 0.85rem;
    }
    .offcanvas-body .nav-link .chevron {
      font-size: 0.7rem;
      transition: transform 0.2s ease;
      opacity: 0.6;
    }
    .offcanvas-body .nav-link[aria-expanded="true"] .chevron {
      transform: rotate(180deg);
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

    /* ========== MOBILE OPTIMIZATIONS & NATIVE APP EXPERIENCE (< 992px) ========== */
    .table-responsive {
      -webkit-overflow-scrolling: touch;
    }

    /* Bottom Navigation Bar (Mobile Native App Bar) */
    .mobile-bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 56px;
      padding-bottom: env(safe-area-inset-bottom, 0px);
      background: rgba(255, 255, 255, 0.97);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-around;
      align-items: center;
      z-index: 1040;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
    }
    .mobile-bottom-nav .nav-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 4px 2px;
      color: #64748b;
      text-decoration: none;
      font-size: 0.67rem;
      font-weight: 500;
      transition: color 0.15s ease, transform 0.15s ease;
      cursor: pointer;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .mobile-bottom-nav .nav-item i {
      font-size: 1.22rem;
      line-height: 1.15;
      margin-bottom: 2px;
      transition: transform 0.15s ease;
    }
    .mobile-bottom-nav .nav-item.active {
      color: #0d6efd;
      font-weight: 600;
    }
    .mobile-bottom-nav .nav-item.active i {
      transform: scale(1.12);
    }
    .mobile-bottom-nav .nav-item:active {
      transform: scale(0.92);
    }

    /* ── Unified Modern Navbar Styles ── */
    .navbar {
      width: 100% !important;
      min-width: 100% !important;
      max-width: 100% !important;
      left: 0 !important;
      right: 0 !important;
      box-sizing: border-box !important;
      height: 56px;
      position: sticky !important;
      top: 0 !important;
      z-index: 1020 !important;
      margin: 0 !important;
    }
    .navbar > .container-fluid,
    .navbar > .container {
      width: 100% !important;
      min-width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }
    .navbar-btn-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: 1px solid rgba(226, 232, 240, 0.85);
      background: #f8fafc;
      color: #334155;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
      cursor: pointer;
      text-decoration: none;
    }
    .navbar-btn-icon:hover {
      background: #f1f5f9;
      color: #0284c7;
      border-color: rgba(14, 165, 233, 0.35);
      transform: translateY(-1px);
    }
    .navbar-btn-icon:active {
      transform: scale(0.95);
    }
    .user-avatar-circle {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      font-weight: 700;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid #ffffff;
      flex-shrink: 0;
    }
    .user-avatar-circle:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
    }

    @media (max-width: 991.98px) {
      /* Navbar Mobile: Sleek & Proportional */
      .navbar {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        left: 0 !important;
        right: 0 !important;
        box-sizing: border-box !important;
        height: 52px !important;
        background: rgba(255, 255, 255, 0.96) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid #e9ecef;
      }
      .navbar-brand img {
        height: 26px !important;
      }
      .navbar-btn-icon {
        width: 34px !important;
        height: 34px !important;
        border-radius: 9px !important;
      }
      .user-avatar-circle {
        width: 34px !important;
        height: 34px !important;
        border-radius: 9px !important;
        font-size: 0.85rem !important;
      }

      /* Ruang bebas untuk Bottom Navigation */
      #mainContent {
        padding: 0.75rem 0.65rem calc(64px + env(safe-area-inset-bottom, 10px)) !important;
        min-height: calc(100vh - 52px);
      }
      footer {
        padding-bottom: calc(68px + env(safe-area-inset-bottom, 10px)) !important;
        padding-top: 1rem;
        font-size: 0.75rem;
      }

      /* Compact Cards on Mobile */
      .card {
        border-radius: 12px;
        margin-bottom: 0.75rem;
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04) !important;
      }
      .card-header {
        padding: 0.65rem 0.85rem;
        font-size: 0.88rem;
        background-color: #fff;
        border-bottom: 1px solid #f1f3f5;
      }
      .card-body {
        padding: 0.75rem 0.85rem;
      }
      .card-footer {
        padding: 0.6rem 0.85rem;
        font-size: 0.82rem;
      }

      /* Compact Typography on Mobile */
      h1, .h1 { font-size: 1.25rem !important; }
      h2, .h2 { font-size: 1.15rem !important; }
      h3, .h3 { font-size: 1.05rem !important; }
      h4, .h4 { font-size: 0.95rem !important; }
      h5, .h5 { font-size: 0.9rem !important; }
      h6, .h6 { font-size: 0.82rem !important; }

      /* Form & Input Sizing */
      .form-control,
      .form-select {
        min-height: 38px;
        padding: 0.38rem 0.65rem;
        font-size: 14px;
        border-radius: 8px;
      }
      .form-control-sm,
      .form-select-sm {
        min-height: 32px;
        font-size: 13px;
        padding: 0.25rem 0.5rem;
      }
      .input-group-text {
        font-size: 14px;
        padding: 0.38rem 0.65rem;
      }

      /* Compact Buttons */
      .btn {
        min-height: 38px;
        padding: 0.38rem 0.85rem;
        font-size: 0.85rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
      }
      .btn-sm {
        min-height: 32px;
        padding: 0.25rem 0.6rem;
        font-size: 0.78rem;
        border-radius: 6px;
      }

      /* Compact Alerts & Badges */
      .alert {
        padding: 0.6rem 0.8rem;
        font-size: 0.82rem;
        border-radius: 10px;
        margin-bottom: 0.75rem;
      }
      .badge {
        font-weight: 500;
      }

      /* Mobile Tables & Scrolling */
      .table-responsive {
        -webkit-overflow-scrolling: touch;
        margin-bottom: 0.75rem;
      }
      .table {
        font-size: 0.8rem;
        margin-bottom: 0;
      }
      .table th,
      .table td {
        padding: 0.45rem 0.5rem;
        vertical-align: middle;
      }
      .table th {
        white-space: nowrap;
      }
      .table td .btn-group,
      .table td .btn-group-sm {
        flex-wrap: wrap;
      }

      /* Offcanvas Navigation */
      .offcanvas {
        max-width: 82vw;
        border-right: 1px solid #dee2e6;
      }
    }

    /* Quick Action Grid Utility */
    .quick-action-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.65rem;
    }
    @media (max-width: 420px) {
      .quick-action-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 0.45rem;
      }
    }
    .quick-action-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      background: #ffffff;
      border: 1px solid #f1f3f5;
      border-radius: 12px;
      padding: 0.65rem 0.25rem 0.5rem;
      color: #334155;
      transition: all 0.15s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .quick-action-btn:active {
      transform: scale(0.95);
      background: #f8fafc;
    }
    .quick-action-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      margin-bottom: 0.35rem;
      transition: transform 0.15s ease;
    }
    .quick-action-label {
      font-size: 0.72rem;
      font-weight: 600;
      text-align: center;
      line-height: 1.15;
      color: #475569;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* Tablet optimizations */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .card-body {
        padding: 1rem;
      }
      #mainContent {
        padding: 1.25rem !important;
      }
    }

    /* Touch-friendly improvements for all devices */
    a,
    button {
      -webkit-tap-highlight-color: rgba(0, 0, 0, 0.05);
    }
    .btn,
    .nav-link {
      -webkit-user-select: none;
      user-select: none;
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
    .update-pulse {
      animation: update-pulse-glow 2s infinite;
    }
    @keyframes update-pulse-glow {
      0% {
        transform: scale(0.96);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
      }
      70% {
        transform: scale(1.04);
        box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
      }
      100% {
        transform: scale(0.96);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
      }
    }
  </style>

  <!-- Fresh & Elegant Theme Stylesheet -->
  <link href="<?= base_url('assets/css/theme.css') ?>" rel="stylesheet" />
</head>

<body>
  <?php 
    $user = session()->get('user'); 
    $roleMap = [
      1 => 'Administrator',
      2 => 'Kepala Sekolah',
      3 => 'Guru',
      4 => 'Orang Tua',
      5 => 'Siswa',
      6 => 'Kontributor',
      7 => 'Staf TU',
    ];
    $roleName = $roleMap[$user['role_id'] ?? 0] ?? 'Pengguna';
    $userInitial = strtoupper(substr($user['fullname'] ?? $user['username'] ?? 'U', 0, 1));
  ?>
  <div class="container-fluid p-0">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
      <div class="container-fluid px-2 px-md-3">
        <!-- Tombol toggle untuk mobile -->
        <button class="navbar-btn-icon d-lg-none me-2"
          type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" title="Menu Samping">
          <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Tombol toggle untuk desktop -->
        <button id="sidebarToggle" class="navbar-btn-icon d-none d-lg-inline-flex me-2" title="Ciutkan/Perluas Menu">
          <i class="bi bi-layout-sidebar-inset"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center py-0" href="<?= base_url('/dashboard') ?>">
          <img src="<?= base_url('uploads/logo/espata.png') ?>"
               alt="ESPATA" style="height:28px; width:auto;">
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
          <?php if ($user): ?>

            <!-- ── Dropdown Notifikasi ───────────────────────── -->
            <div class="dropdown" id="notifDropdownWrapper">
              <button class="navbar-btn-icon position-relative"
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

            <!-- ── User Profile Dropdown ──────────────────────── -->
            <div class="dropdown" id="userProfileDropdown">
              <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2"
                      type="button" id="userMenuBtn"
                      data-bs-toggle="dropdown" aria-expanded="false" title="Profil Akun">
                <div class="user-avatar-circle">
                  <?= $userInitial ?>
                </div>
                <div class="d-none d-md-block text-start" style="line-height: 1.2;">
                  <div class="fw-bold text-dark small text-truncate" style="max-width: 120px;">
                    <?= esc($user['fullname'] ?? ($user['username'] ?? 'Pengguna')) ?>
                  </div>
                  <div class="text-muted" style="font-size: 0.68rem;">
                    <?= $roleName ?>
                  </div>
                </div>
                <i class="bi bi-chevron-down text-muted small d-none d-md-inline" style="font-size: 0.7rem;"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2" aria-labelledby="userMenuBtn" style="min-width: 220px; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.12) !important;">
                <li class="px-3 py-2 border-bottom mb-1 bg-light bg-opacity-50">
                  <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;"><?= esc($user['fullname'] ?? ($user['username'] ?? 'Pengguna')) ?></div>
                  <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.68rem; font-weight: 600;">
                      <?= $roleName ?>
                    </span>
                    <span class="text-muted" style="font-size: 0.72rem;">@<?= esc($user['username'] ?? 'user') ?></span>
                  </div>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="<?= base_url('profile') ?>">
                    <i class="bi bi-person text-primary"></i>
                    <span>Profil Saya</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="<?= base_url('profile/change-password') ?>">
                    <i class="bi bi-shield-lock text-warning"></i>
                    <span>Ubah Password</span>
                  </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2 text-danger fw-semibold" href="<?= base_url('logout') ?>">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar (Logout)</span>
                  </a>
                </li>
              </ul>
            </div>
            <!-- ─────────────────────────────────────────────── -->

          <?php else: ?>
            <a href="<?= base_url('login') ?>" class="btn btn-sm btn-primary rounded-pill px-3">Login ESPATA</a>
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
          <?php else: ?>
            <?= $this->include('layouts/partials/sidebar_dynamic') ?>
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
          <?php
            $mobileRoleTitle = 'Menu Publik';
            if ($user && isset($user['role_id'])) {
                $roleTitles = [
                    1 => 'Menu Administrator',
                    2 => 'Menu Kepala Sekolah',
                    3 => 'Menu Guru',
                    4 => 'Menu Orang Tua',
                    5 => 'Menu Siswa',
                    6 => 'Menu Kontributor',
                    7 => 'Menu Staf & TU'
                ];
                $mobileRoleTitle = $roleTitles[$user['role_id']] ?? 'Menu Aplikasi';
            }
          ?>
          <h5 class="offcanvas-title" id="sidebarMenuLabel"><?= $mobileRoleTitle ?></h5>
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
            <?php else: ?>
              <?= $this->include('layouts/partials/sidebar_dynamic') ?>
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
      <main id="mainContent" class="px-2 px-md-4 py-2 py-md-3">
        <?= $this->renderSection('content') ?>
      </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (Aplikasi Native Experience) -->
    <?php if ($user && isset($user['role_id'])): ?>
      <?php
        $currentUri = uri_string();
        $roleId = (int)$user['role_id'];
      ?>
      <nav class="mobile-bottom-nav d-lg-none" id="mobileBottomNav">
        <?php if ($roleId === 5): /* Siswa */ ?>
          <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $currentUri === 'dashboard' || $currentUri === '' ? 'active' : '' ?>">
            <i class="bi <?= $currentUri === 'dashboard' || $currentUri === '' ? 'bi-house-door-fill' : 'bi-house-door' ?>"></i>
            <span>Beranda</span>
          </a>
          <a href="<?= base_url('siswa/belajar') ?>" class="nav-item <?= str_contains($currentUri, 'siswa/belajar') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'siswa/belajar') ? 'bi-mortarboard-fill' : 'bi-mortarboard' ?>"></i>
            <span>Belajar</span>
          </a>
          <a href="<?= base_url('siswa/tugas') ?>" class="nav-item <?= str_contains($currentUri, 'siswa/tugas') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'siswa/tugas') ? 'bi-clipboard-check-fill' : 'bi-clipboard-check' ?>"></i>
            <span>Tugas</span>
          </a>
          <a href="<?= base_url('siswa/grades') ?>" class="nav-item <?= str_contains($currentUri, 'grades') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'grades') ? 'bi-award-fill' : 'bi-award' ?>"></i>
            <span>Nilai</span>
          </a>
          <div class="nav-item" role="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-grid-fill"></i>
            <span>Menu</span>
          </div>

        <?php elseif ($roleId === 4): /* Orang Tua */ ?>
          <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $currentUri === 'dashboard' || $currentUri === '' ? 'active' : '' ?>">
            <i class="bi <?= $currentUri === 'dashboard' || $currentUri === '' ? 'bi-house-door-fill' : 'bi-house-door' ?>"></i>
            <span>Beranda</span>
          </a>
          <a href="<?= base_url('siswa/grades') ?>" class="nav-item <?= str_contains($currentUri, 'grades') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'grades') ? 'bi-award-fill' : 'bi-award' ?>"></i>
            <span>Nilai</span>
          </a>
          <a href="<?= base_url('siswa/attendance') ?>" class="nav-item <?= str_contains($currentUri, 'attendance') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'attendance') ? 'bi-calendar-check-fill' : 'bi-calendar-check' ?>"></i>
            <span>Presensi</span>
          </a>
          <a href="<?= base_url('siswa/payment') ?>" class="nav-item <?= str_contains($currentUri, 'payment') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'payment') ? 'bi-wallet2' : 'bi-wallet' ?>"></i>
            <span>SPP</span>
          </a>
          <div class="nav-item" role="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-grid-fill"></i>
            <span>Menu</span>
          </div>

        <?php elseif ($roleId === 3): /* Guru */ ?>
          <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $currentUri === 'dashboard' || $currentUri === '' ? 'active' : '' ?>">
            <i class="bi <?= $currentUri === 'dashboard' || $currentUri === '' ? 'bi-house-door-fill' : 'bi-house-door' ?>"></i>
            <span>Beranda</span>
          </a>
          <a href="<?= base_url('admin/materials') ?>" class="nav-item <?= str_contains($currentUri, 'materials') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'materials') ? 'bi-journal-bookmark-fill' : 'bi-journal-bookmark' ?>"></i>
            <span>Materi</span>
          </a>
          <a href="<?= base_url('admin/attendance') ?>" class="nav-item <?= str_contains($currentUri, 'attendance') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'attendance') ? 'bi-person-check-fill' : 'bi-person-check' ?>"></i>
            <span>Presensi</span>
          </a>
          <a href="<?= base_url('admin/grades') ?>" class="nav-item <?= str_contains($currentUri, 'grades') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'grades') ? 'bi-pencil-square' : 'bi-pencil' ?>"></i>
            <span>Nilai</span>
          </a>
          <div class="nav-item" role="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-grid-fill"></i>
            <span>Menu</span>
          </div>

        <?php else: /* Admin / Kepsek / Staf */ ?>
          <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $currentUri === 'dashboard' || $currentUri === '' ? 'active' : '' ?>">
            <i class="bi <?= $currentUri === 'dashboard' || $currentUri === '' ? 'bi-speedometer2' : 'bi-speedometer' ?>"></i>
            <span>Beranda</span>
          </a>
          <a href="<?= base_url('admin/students') ?>" class="nav-item <?= str_contains($currentUri, 'students') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'students') ? 'bi-people-fill' : 'bi-people' ?>"></i>
            <span>Siswa</span>
          </a>
          <a href="<?= base_url('admin/materials') ?>" class="nav-item <?= str_contains($currentUri, 'materials') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'materials') ? 'bi-journal-richtext' : 'bi-journal' ?>"></i>
            <span>KBM</span>
          </a>
          <a href="<?= base_url('admin/attendance') ?>" class="nav-item <?= str_contains($currentUri, 'attendance') ? 'active' : '' ?>">
            <i class="bi <?= str_contains($currentUri, 'attendance') ? 'bi-calendar-check-fill' : 'bi-calendar-check' ?>"></i>
            <span>Presensi</span>
          </a>
          <div class="nav-item" role="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-grid-fill"></i>
            <span>Menu</span>
          </div>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

    <?= $this->include('layouts/footer') ?>
  </div>

  <!-- JS libraries -->
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
      // Auto-wrap any unwrapped tables for mobile responsiveness safety net
      $('table.table').each(function () {
        var $tbl = $(this);
        if (!$tbl.closest('.table-responsive').length && !$tbl.hasClass('no-responsive')) {
          $tbl.wrap('<div class="table-responsive"></div>');
        }
      });

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
        }).fail(function(xhr) {
          if (xhr.status === 401) window.location.href = "<?= base_url('login') ?>";
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
        }).fail(function(xhr) {
          if (xhr.status === 401) window.location.href = "<?= base_url('login') ?>";
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
      }).fail(function(xhr) {
        if (xhr.status === 401) window.location.href = "<?= base_url('login') ?>";
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