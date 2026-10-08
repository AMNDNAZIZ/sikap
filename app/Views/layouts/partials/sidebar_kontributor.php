<div class="position-sticky pt-3 sidebar-mini">
<?php
  $user = session()->get('user');
  // Deteksi apakah user ini terdaftar sebagai pembina ekskul di tahun ajaran aktif
  $isPembina = false;
  $activeYearId  = null;
  try {
      $activeYearRow = \Config\Database::connect()
          ->table('academic_years')
          ->where('is_active', 1)
          ->get()->getRowArray();
      $activeYearId = $activeYearRow['id'] ?? null;
  } catch (\Throwable $e) { /* ignore */ }

  if ($activeYearId && !empty($user['id'])) {
      $isPembina = (bool) \Config\Database::connect()
          ->table('ekskul_pembina')
          ->where('user_id', (int) $user['id'])
          ->where('academic_year_id', $activeYearId)
          ->countAllResults();
  }
?>
    <ul class="nav flex-column">

        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
                🏠 <span class="label">Dashboard</span>
            </a>
        </li>

        <!-- Lihat Website -->
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/') ?>" target="_blank">
                🌍 <span class="label">Lihat Website</span>
            </a>
        </li>

        <!-- CMS / Konten Website -->
        <li class="nav-item">
            <a class="nav-link d-flex content-between align-items-left" data-bs-toggle="collapse" href="#cmsMenu"
                role="button" aria-expanded="<?= url_is('admin/cms*') ? 'true' : 'false' ?>">
                🌐 <span class="label">Konten Website</span>
            </a>
            <div class="collapse <?= url_is('admin/cms*') ? 'show' : '' ?>" id="cmsMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link <?= url_is('admin/cms/sliders*') ? 'active' : '' ?>"
                            href="<?= base_url('admin/cms/sliders') ?>">🖼️ <span class="label">Slider Hero</span></a></li>
                    <li><a class="nav-link <?= url_is('admin/cms/articles*') ? 'active' : '' ?>"
                            href="<?= base_url('admin/cms/articles') ?>">📰 <span class="label">Berita &amp; Artikel</span></a></li>
                    <li><a class="nav-link <?= url_is('admin/cms/facilities*') ? 'active' : '' ?>"
                            href="<?= base_url('admin/cms/facilities') ?>">🏫 <span class="label">Sarana Prasarana</span></a></li>
                    <li><a class="nav-link <?= url_is('admin/cms/activities*') ? 'active' : '' ?>"
                            href="<?= base_url('admin/cms/activities') ?>">📸 <span class="label">Dokumentasi</span></a></li>
                    <li><a class="nav-link <?= url_is('admin/cms/links*') ? 'active' : '' ?>"
                            href="<?= base_url('admin/cms/links') ?>">🔗 <span class="label">Tautan Pintar</span></a></li>
                </ul>
            </div>
        </li>

        <!-- Ekstrakurikuler — hanya tampil jika user adalah pembina/pelatih ekskul -->
        <?php if ($isPembina): ?>
        <li class="nav-item">
            <a class="nav-link <?= url_is('admin/ekskul-pembina*') ? 'active' : '' ?>"
                href="<?= base_url('admin/ekskul-pembina') ?>">
                ⚽ <span class="label">Dashboard Ekskul</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Pengaturan Profil -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">🔒 <span
                    class="label">Pengaturan Profil</span></a>
        </li>

    </ul>
</div>
