<div class="position-sticky pt-3 sidebar-mini">
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

        <!-- Pengaturan Profil -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">🔒 <span
                    class="label">Pengaturan Profil</span></a>
        </li>

    </ul>
</div>
