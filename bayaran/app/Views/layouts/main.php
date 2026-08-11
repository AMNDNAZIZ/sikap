<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title><?= esc($title ?? 'Dashboard Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" href="<?= base_url('public/favicon.ico') ?>" type="image/ico">
  <link rel="manifest" href="<?= base_url('manifest.json') ?>">
  <meta name="theme-color" content="#667eea">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap"
    rel="stylesheet">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --sidebar-width: 260px;
      --sidebar-slim-width: 80px;

      --primary: #667eea;
      --secondary: #764ba2;

      --sidebar-bg: #ffffff;
      --sidebar-text: #4b4b4b;
      --sidebar-hover: #f8f9fa;
      --sidebar-active-bg: rgba(102, 126, 234, 0.1);
      --sidebar-active-text: #667eea;

      --content-bg: #f5f7fb;
      --border-radius: 12px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--content-bg);
      padding-top: 70px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ================= NAVBAR ================= */
    .navbar {
      height: 70px;
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95) !important;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      z-index: 1050;
      /* Ensure it's above the sidebar (default 1045) */
    }

    .navbar-brand {
      font-family: 'Outfit', sans-serif;
      font-weight: 700;
      color: var(--secondary) !important;
    }

    /* ================= SIDEBAR ================= */
    .offcanvas {
      --bs-offcanvas-width: 280px;
    }

    .sidebar {
      background: var(--sidebar-bg);
      padding: 1.25rem;
      border-right: 1px solid rgba(0, 0, 0, 0.05);
      overflow-y: auto;
    }

    .sidebar a {
      color: var(--sidebar-text);
      padding: 0.75rem 1rem;
      border-radius: var(--border-radius);
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
      font-weight: 500;
      text-decoration: none;
      transition: 0.2s ease;
      margin-bottom: 4px;
    }

    .sidebar a:hover {
      background: var(--sidebar-hover);
      color: var(--primary);
      transform: translateX(3px);
    }

    .sidebar a.active {
      background: var(--sidebar-active-bg);
      color: var(--sidebar-active-text);
      font-weight: 600;
    }

    .nav-icon {
      width: 20px;
      text-align: center;
    }

    /* Slim mode desktop */
    .sidebar.slim {
      width: var(--sidebar-slim-width) !important;
      padding: 1rem 0.5rem;
    }

    .sidebar.slim span {
      display: none;
    }

    .sidebar.slim .dropdown-toggle::after,
    .sidebar.slim .collapse {
      display: none !important;
    }

    /* Desktop behavior */
    @media (min-width: 768px) {
      .offcanvas-md {
        position: sticky !important;
        transform: none !important;
        visibility: visible !important;
        display: block !important;
        height: calc(100vh - 70px) !important;
        top: 70px;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
        z-index: 1040;
        /* Lower than navbar (1050) */
      }

      .offcanvas-md .offcanvas-header {
        display: none;
      }
    }

    /* Main */
    main {
      flex-grow: 1;
      padding: 2rem;
    }

    footer {
      background: #fff;
      text-align: center;
      padding: 1rem;
      font-size: 0.85rem;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
  </style>
</head>

<body>

  <!-- ================= NAVBAR ================= -->
  <nav class="navbar fixed-top shadow-sm">
    <div class="container-fluid px-4">

      <div class="d-flex align-items-center">
        <!-- Mobile toggle -->
        <button class="btn btn-outline-secondary d-md-none me-3" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
          <i class="bi bi-list"></i>
        </button>

        <!-- Desktop toggle -->
        <button class="btn btn-outline-secondary d-none d-md-block" id="toggleSidebar">
          <i class="bi bi-text-indent-left"></i>
        </button>

        <span class="navbar-brand ms-3"><?= esc($title ?? 'Admin Panel') ?></span>
      </div>

    </div>
  </nav>

  <!-- ================= LAYOUT ================= -->
  <div class="d-flex flex-grow-1">

    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start sidebar offcanvas-md" tabindex="-1" id="sidebarMenu">
      <div class="offcanvas-header d-md-none border-bottom px-4">
        <h5 class="offcanvas-title font-outfit" style="font-weight: 700; color: var(--secondary);">MENU</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body px-3 py-4">
        <ul class="nav flex-column">
          <?php
          $role = session()->get('role') ?? 'guest';

          function canAccess($item, $role)
          {
            return !isset($item['allowed_roles']) || in_array($role, $item['allowed_roles']) || $role === 'superadmin';
          }

          $menus = [
            ['icon' => 'bi-grid-1x2', 'text' => 'Dashboard', 'url' => 'admin/dashboard'],

            [
              'icon' => 'bi-layers',
              'text' => 'Data Master',
              'id' => 'masterMenu',
              'children' => [
                ['text' => '🏫 Sekolah', 'url' => 'admin/setting'],
                ['text' => '📅 Tahun Ajaran', 'url' => 'admin/tahun-ajaran'],
                ['text' => '🏫 Kelas', 'url' => 'admin/kelas'],
                ['text' => '👨‍🎓 Siswa', 'url' => 'admin/siswa'],
                ['text' => '➕ Penempatan', 'url' => 'admin/penempatan'],
                ['text' => '📘 Kelas Aktif', 'url' => 'admin/kelas-aktif'],
                ['text' => '⬆️ Kenaikan/Lulus', 'url' => 'admin/kenaikan'],
                ['text' => '📁 Arsip Siswa', 'url' => 'admin/arsip-siswa'],
              ],
              'allowed_roles' => ['admin']
            ],

            [
              'icon' => 'bi-gear',
              'text' => 'Keuangan',
              'id' => 'keuanganMenu',
              'children' => [
                ['text' => '💳 Pos Bayar', 'url' => 'admin/pos-bayar'],
                ['text' => '💰 Jenis Bayar', 'url' => 'admin/jenis-bayar'],
              ],
              'allowed_roles' => ['admin']
            ],

            ['icon' => 'bi-wallet2', 'text' => 'Pembayaran', 'url' => 'admin/pembayaran', 'allowed_roles' => ['admin', 'operator']],
            ['icon' => 'bi-journal-check', 'text' => 'Utang Piutang', 'url' => 'admin/kasbon', 'allowed_roles' => ['admin', 'operator', 'staf']],
            ['icon' => 'bi-broadcast-pin', 'text' => 'Pengumuman', 'url' => 'admin/pengumuman', 'allowed_roles' => ['admin']],
            ['icon' => 'bi-arrow-left-right', 'text' => 'Arus Kas', 'url' => 'admin/transaksi-pos', 'allowed_roles' => ['admin', 'operator', 'staf']],

            [
              'icon' => 'bi-graph-up-arrow',
              'text' => 'Pelaporan',
              'id' => 'laporanMenu',
              'allowed_roles' => ['admin', 'operator', 'staf'],
              'children' => [
                ['text' => '📆 Harian', 'url' => 'admin/laporan/harian'],
                ['text' => '🗓️ Periode', 'url' => 'admin/laporan/periode'],
                ['text' => '📄 Tagihan Siswa', 'url' => 'admin/laporan/tagihan'],
                ['text' => '🏢 SPP Yayasan', 'url' => 'admin/laporan/yayasan'],
                ['text' => '📄 Tagihan Filter', 'url' => 'admin/laporan/filter-tagihan'],
                ['text' => '📊 Rekap Tunggakan', 'url' => 'admin/laporan/rekap-tunggakan'],
                ['text' => '🗓️ Per Tahun', 'url' => 'admin/laporanlain/per-tahun'],
                ['text' => '📅 Per Bulan', 'url' => 'admin/laporanlain/per-bulan'],
                ['text' => '📊 Rentang', 'url' => 'admin/laporanlain/rentang'],
              ]
            ],

            ['icon' => 'bi-people', 'text' => 'User Manajemen', 'url' => 'admin/user', 'allowed_roles' => ['admin']],
            ['icon' => 'bi-shield-check', 'text' => 'Database', 'url' => 'admin/backup-restore', 'allowed_roles' => ['admin']],
          ];

          foreach ($menus as $menu):
            if (!canAccess($menu, $role))
              continue;

            $menuUrl = $menu['url'] ?? null;
            $hasChildren = isset($menu['children']);
            $isActive = (($menuUrl && uri_string() === $menuUrl) || ($hasChildren && array_search(uri_string(), array_column($menu['children'], 'url')) !== false));

            if ($hasChildren): ?>
              <li class="nav-item">
                <a class="nav-link dropdown-toggle <?= $isActive ? 'active' : '' ?>" data-bs-toggle="collapse"
                  href="#<?= $menu['id'] ?>" role="button" aria-expanded="<?= $isActive ? 'true' : 'false' ?>">
                  <i class="bi <?= $menu['icon'] ?> nav-icon"></i> <span><?= $menu['text'] ?></span>
                </a>
                <div class="collapse <?= $isActive ? 'show' : '' ?> ms-3" id="<?= $menu['id'] ?>">
                  <?php foreach ($menu['children'] as $child): ?>
                    <a class="nav-link py-1 mb-1 <?= uri_string() === $child['url'] ? 'active' : '' ?>"
                      href="<?= site_url($child['url']) ?>"
                      style="font-size: 0.85rem; border-left: 2px solid transparent; border-radius: 0 8px 8px 0;">
                      <span><?= $child['text'] ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link <?= $menuUrl && uri_string() === $menuUrl ? 'active' : '' ?>"
                  href="<?= site_url($menuUrl ?? '#') ?>">
                  <i class="bi <?= $menu['icon'] ?> nav-icon"></i> <span><?= $menu['text'] ?></span>
                </a>
              </li>
            <?php endif;
          endforeach;
          ?>

          <li class="nav-item mt-4 pt-3 border-top">
            <a class="nav-link text-danger" href="<?= site_url('logout') ?>">
              <i class="bi bi-power nav-icon"></i> <span>Keluar</span>
            </a>
          </li>
        </ul>
      </div>
    </div>


    <!-- Main -->
    <main class="w-100">
      <div class="container-fluid">
        <?= $this->renderSection('content') ?>
      </div>
    </main>

  </div>

  <footer class="mt-auto">
    &copy; <?= date('Y') ?> <strong>SakaSalika™</strong>
  </footer>

  <!-- ================= SCRIPTS ================= -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');

    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('slim');
        const isSlim = sidebar.classList.contains('slim');
        localStorage.setItem('sidebarSlim', isSlim);
        toggleBtn.querySelector('i').className =
          isSlim ? 'bi bi-text-indent-right' : 'bi bi-text-indent-left';
      });

      if (window.innerWidth >= 768 && localStorage.getItem('sidebarSlim') === 'true') {
        sidebar.classList.add('slim');
      }
    }

    // Auto close offcanvas on mobile link click
    document.querySelectorAll('#sidebarMenu .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 768) {
          const instance = bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu'));
          if (instance) instance.hide();
        }
      });
    });
  </script>

</body>

</html>