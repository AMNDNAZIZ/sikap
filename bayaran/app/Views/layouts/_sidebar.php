<?php
$menus = [
  [
    'icon' => 'bi-archive',
    'text' => 'Data Master',
    'id' => 'masterMenu',
    'children' => [
      ['text' => '🏫 Sekolah', 'url' => 'admin/setting'],
      ['text' => '📅 Tahun Ajaran', 'url' => 'admin/tahun-ajaran'],
      ['text' => '🏫 Kelas', 'url' => 'admin/kelas'],
      ['text' => '👨‍🎓 Siswa', 'url' => 'admin/siswa'],
      ['text' => '➕ Kelas Siswa Baru', 'url' => 'admin/penempatan'],
      ['text' => '📘 Kelas Aktif', 'url' => 'admin/kelas-aktif'],
      ['text' => '⬆️ Kenaikan/Kelulusan', 'url' => 'admin/kenaikan'],
    ]
  ],
  [
    'icon' => 'bi-cash-coin',
    'text' => 'Setting Keuangan',
    'id' => 'keuanganMenu',
    'children' => [
      ['text' => '💳 Pos Bayar', 'url' => 'admin/pos-bayar'],
      ['text' => '💰 Jenis Bayar', 'url' => 'admin/jenis-bayar'],
    ]
  ],
  [
    'icon' => 'bi-wallet',
    'text' => 'Pembayaran Siswa',
    'url' => 'admin/pembayaran',
  ],
  [
    'icon' => 'bi-journal-minus',
    'text' => 'Utang Piutang',
    'url' => 'admin/kasbon',
  ],
  [
    'icon' => 'bi-megaphone',
    'text' => 'Pengumuman',
    'url' => 'admin/pengumuman',
  ],
  [
    'icon' => 'bi-currency-exchange',
    'text' => 'Arus Kas Pos Bayar',
    'url' => 'admin/transaksi-pos',
  ],
  [
    'icon' => 'bi-bar-chart-line',
    'text' => 'Pelaporan',
    'id' => 'laporanMenu',
    'children' => [
      ['text' => '📆 Harian', 'url' => 'admin/laporan/harian'],
      ['text' => '🗓️ Periode', 'url' => 'admin/laporan/periode'],
      ['text' => '📄 Tagihan Siswa', 'url' => 'admin/laporan/tagihan'],
      ['text' => '🏢 SPP Yayasan', 'url' => 'admin/laporan/yayasan'],
    ]
  ],
  [
    'icon' => 'bi-people',
    'text' => 'User',
    'url' => 'admin/user',
  ],
];
?>


<div class="offcanvas offcanvas-start sidebar offcanvas-md p-3" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header d-md-none">
    <h5 class="offcanvas-title">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= uri_string() === 'admin/dashboard' ? 'active' : '' ?>" href="<?= site_url('admin/dashboard') ?>">
          <i class="bi bi-house nav-icon"></i> Dashboard
        </a>
      </li>
      <?php foreach ($menus as $menu): ?>
        <?php if (isset($menu['children'])): ?>
          <li class="nav-item dropdown mt-2">
            <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#<?= $menu['id'] ?>" role="button">
              <i class="bi <?= $menu['icon'] ?> nav-icon"></i> <?= $menu['text'] ?>
            </a>
            <div class="collapse ms-3" id="<?= $menu['id'] ?>">
              <?php foreach ($menu['children'] as $child): ?>
                <a class="nav-link <?= uri_string() === $child['url'] ? 'active' : '' ?>" href="<?= site_url($child['url']) ?>">
                  <?= $child['text'] ?>
                </a>
              <?php endforeach; ?>
            </div>
          </li>
        <?php else: ?>
          <li class="nav-item mt-2">
            <a class="nav-link <?= uri_string() === $menu['url'] ? 'active' : '' ?>" href="<?= site_url($menu['url']) ?>">
              <i class="bi <?= $menu['icon'] ?> nav-icon"></i> <?= $menu['text'] ?>
            </a>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>

      <li class="nav-item mt-3">
        <a class="nav-link text-danger" href="<?= site_url('logout') ?>">
          <i class="bi bi-box-arrow-right nav-icon"></i> Logout
        </a>
      </li>
    </ul>
  </div>
</div>
