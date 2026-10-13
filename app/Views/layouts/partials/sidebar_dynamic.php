<div class="position-sticky pt-2 sidebar-mini">
<?php
  use App\Libraries\MenuRegistry;
  $user = session()->get('user');
  $userMenu = MenuRegistry::getUserMenu($user);
?>
  <ul class="nav flex-column">

    <!-- ══ BERANDA & UTAMA ══ -->
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

    <!-- ══ DYNAMIC PERMISSION MENUS ══ -->
    <?php foreach ($userMenu as $groupKey => $group): 
      $isGroupActive = false;
      foreach ($group['active_urls'] as $pattern) {
          if (url_is($pattern)) {
              $isGroupActive = true;
              break;
          }
      }
      $collapseId = 'dynGroup_' . $groupKey;
    ?>
      <li class="sidebar-category-header">
        <span class="label"><?= esc($group['title']) ?></span>
      </li>

      <?php if (count($group['items']) === 1): 
        $single = $group['items'][0];
        $isActive = url_is($single['active_rule'] ?? $single['url']);
      ?>
        <li class="nav-item">
          <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= base_url($single['url']) ?>" title="<?= esc($single['label']) ?>">
            <i class="<?= esc($single['icon']) ?>"></i> <span class="label"><?= esc($single['label']) ?></span>
          </a>
        </li>
      <?php else: ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#<?= $collapseId ?>"
            role="button" aria-expanded="<?= $isGroupActive ? 'true' : 'false' ?>" title="<?= esc($group['title']) ?>">
            <i class="<?= esc($group['icon']) ?>"></i> <span class="label"><?= esc($group['title']) ?></span>
            <i class="bi bi-chevron-down chevron label ms-auto"></i>
          </a>
          <div class="collapse <?= $isGroupActive ? 'show' : '' ?>" id="<?= $collapseId ?>">
            <ul class="nav flex-column ms-3">
              <?php foreach ($group['items'] as $item): 
                $isActive = url_is($item['active_rule'] ?? $item['url']);
              ?>
                <li>
                  <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= base_url($item['url']) ?>">
                    <i class="<?= esc($item['icon']) ?>"></i> <span class="label"><?= esc($item['label']) ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>

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
