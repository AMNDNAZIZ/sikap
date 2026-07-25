<style>
/* Modern Sidebar Styles */
.sidebar-modern {
  padding: 1rem 0;
}

.sidebar-modern .nav-item {
  margin-bottom: 0.25rem;
}

.sidebar-modern .nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: #495057;
  border-radius: 8px;
  margin: 0 0.5rem;
  transition: all 0.3s ease;
  font-weight: 500;
  font-size: 0.95rem;
}

.sidebar-modern .nav-link:hover {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  color: #667eea;
  transform: translateX(3px);
}

.sidebar-modern .nav-link.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white !important;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.sidebar-modern .nav-link.active i {
  color: white !important;
}

.sidebar-modern .nav-link i {
  font-size: 1.1rem;
  width: 24px;
  margin-right: 0.75rem;
  transition: all 0.3s ease;
}

.sidebar-modern .nav-link .label {
  flex: 1;
}

.sidebar-modern .nav-link .badge {
  margin-left: auto;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
}

/* Collapse Menu */
.sidebar-modern .nav-link[data-bs-toggle="collapse"] {
  position: relative;
}

.sidebar-modern .nav-link[data-bs-toggle="collapse"]::after {
  content: "\f107";
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
  margin-left: auto;
  transition: transform 0.3s ease;
}

.sidebar-modern .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after {
  transform: rotate(180deg);
}

/* Submenu */
.sidebar-modern .collapse .nav {
  padding: 0.5rem 0;
}

.sidebar-modern .collapse .nav-link {
  padding: 0.5rem 1rem 0.5rem 3rem;
  font-size: 0.9rem;
  font-weight: 400;
}

.sidebar-modern .collapse .nav-link i {
  font-size: 0.9rem;
  width: 20px;
  margin-right: 0.5rem;
}

/* Menu Section Title */
.menu-section-title {
  padding: 1rem 1rem 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
}

/* Minimized Sidebar */
.sidebar.minimized .sidebar-modern .nav-link .label,
.sidebar.minimized .sidebar-modern .nav-link .badge,
.sidebar.minimized .sidebar-modern .nav-link::after,
.sidebar.minimized .menu-section-title {
  display: none;
}

.sidebar.minimized .sidebar-modern .nav-link {
  justify-content: center;
  padding: 0.75rem;
}

.sidebar.minimized .sidebar-modern .nav-link i {
  margin-right: 0;
}

.sidebar.minimized .sidebar-modern .collapse {
  display: none !important;
}

/* Hover effect for icons */
.sidebar-modern .nav-link:hover i {
  transform: scale(1.1);
}
</style>

<div class="sidebar-modern">
  <ul class="nav flex-column">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
        <i class="fas fa-tachometer-alt text-primary"></i>
        <span class="label">Dashboard</span>
      </a>
    </li>

    <!-- Master Data Section -->
    <div class="menu-section-title">Master Data</div>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/school*') ? 'active' : '' ?>" href="<?= base_url('admin/school') ?>">
        <i class="fas fa-school text-info"></i>
        <span class="label">Identitas Sekolah</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/academic-year*') ? 'active' : '' ?>" href="<?= base_url('admin/academic-year') ?>">
        <i class="fas fa-calendar-alt text-success"></i>
        <span class="label">Tahun Ajaran</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/teachers*') ? 'active' : '' ?>" href="<?= base_url('admin/teachers') ?>">
        <i class="fas fa-chalkboard-teacher text-warning"></i>
        <span class="label">Guru</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/classes*') ? 'active' : '' ?>" href="<?= base_url('admin/classes') ?>">
        <i class="fas fa-door-open text-primary"></i>
        <span class="label">Kelas</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/students*') ? 'active' : '' ?>" href="<?= base_url('admin/students') ?>">
        <i class="fas fa-user-graduate text-success"></i>
        <span class="label">Siswa</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/subjects*') ? 'active' : '' ?>" href="<?= base_url('admin/subjects') ?>">
        <i class="fas fa-book text-danger"></i>
        <span class="label">Mata Pelajaran</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/users*') ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">
        <i class="fas fa-users text-info"></i>
        <span class="label">Users</span>
      </a>
    </li>

    <!-- CBT Section -->
    <div class="menu-section-title">CBT Management</div>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>">
        <i class="fas fa-clipboard-list text-primary"></i>
        <span class="label">Nama Ujian</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/exam-schedule*') ? 'active' : '' ?>" href="<?= base_url('admin/exam-schedule') ?>">
        <i class="fas fa-calendar-check text-success"></i>
        <span class="label">Jadwal Ujian</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/kartu-peserta*') ? 'active' : '' ?>" href="<?= base_url('admin/kartu-peserta') ?>">
        <i class="fas fa-id-card text-warning"></i>
        <span class="label">Kartu Peserta</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/attendance') ?>">
        <i class="fas fa-user-check text-info"></i>
        <span class="label">Daftar Hadir</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/banksoal') ?>">
        <i class="fas fa-folder-open text-danger"></i>
        <span class="label">Bank Soal</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/teststatus') ?>">
        <i class="fas fa-toggle-on text-success"></i>
        <span class="label">Status Tes</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/aktivitas') ?>">
        <i class="fas fa-chart-line text-primary"></i>
        <span class="label">Aktivitas</span>
      </a>
    </li>

    <!-- System Section -->
    <div class="menu-section-title">System</div>

    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">
        <i class="fas fa-key text-warning"></i>
        <span class="label">Ganti Password</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/updater*') ? 'active' : '' ?>" href="<?= base_url('admin/updater') ?>">
        <i class="fas fa-sync-alt text-info"></i>
        <span class="label">Update Sistem</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/data-cleanup*') ? 'active' : '' ?>" href="<?= base_url('admin/data-cleanup') ?>">
        <i class="fas fa-broom text-danger"></i>
        <span class="label">Pembersihan Data</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/settings/session*') ? 'active' : '' ?>" href="<?= base_url('admin/settings/session') ?>">
        <i class="fas fa-server text-secondary"></i>
        <span class="label">Sesi &amp; Cache</span>
      </a>
    </li>

  </ul>
</div>
