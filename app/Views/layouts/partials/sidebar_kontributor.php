<div class="position-sticky pt-2 sidebar-mini">
<?php
  $user = session()->get('user');
  $isPembina = false;
  $activeYearId = null;
  try {
      $activeYearRow = \Config\Database::connect()
          ->table('academic_years')
          ->where('is_active', 1)
          ->get()->getRowArray();
      $activeYearId = $activeYearRow['id'] ?? null;
  } catch (\Throwable $e) {}

  if ($activeYearId && !empty($user['id'])) {
      $isPembina = (bool) \Config\Database::connect()
          ->table('ekskul_pembina')
          ->where('user_id', (int) $user['id'])
          ->where('academic_year_id', $activeYearId)
          ->countAllResults();
  }
  $inCms = url_is('admin/cms*');
?>
  <ul class="nav flex-column">

    <!-- ══ BERANDA ══ -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>" title="Dashboard">
        <i class="bi bi-speedometer2"></i> <span class="label">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link text-info" href="<?= base_url('/') ?>" target="_blank" title="Lihat Website">
        <i class="bi bi-globe2"></i> <span class="label">Lihat Website</span>
      </a>
    </li>

    <!-- ══ KONTEN WEBSITE (CMS) ══ -->
    <li class="sidebar-category-header">
      <span class="label">Publikasi &amp; Web</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#cmsMenu"
        role="button" aria-expanded="<?= $inCms ? 'true' : 'false' ?>" title="Konten Website">
        <i class="bi bi-globe"></i> <span class="label">Konten Website</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCms ? 'show' : '' ?>" id="cmsMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cms/articles*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/articles') ?>"><i class="bi bi-newspaper me-1"></i> <span class="label">Berita &amp; Artikel</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/sliders*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/sliders') ?>"><i class="bi bi-images me-1"></i> <span class="label">Slider Hero</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/facilities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/facilities') ?>"><i class="bi bi-buildings me-1"></i> <span class="label">Sarana Prasarana</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/activities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/activities') ?>"><i class="bi bi-camera me-1"></i> <span class="label">Dokumentasi</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/links*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/links') ?>"><i class="bi bi-link-45deg me-1"></i> <span class="label">Tautan Pintar</span></a></li>
        </ul>
      </div>
    </li>

    <?php if ($isPembina): ?>
    <!-- ══ EKSTRAKURIKULER ══ -->
    <li class="sidebar-category-header">
      <span class="label">Ekstrakurikuler</span>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/ekskul-pembina*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul-pembina') ?>" title="Dashboard Ekskul">
        <i class="bi bi-dribbble"></i> <span class="label">Dashboard Ekskul</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ══ AKUN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Akun</span>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>" title="Profil">
        <i class="bi bi-person-gear"></i> <span class="label">Pengaturan Profil</span>
      </a>
    </li>

  </ul>
</div>
