<div class="position-sticky pt-3 sidebar-mini">
  <ul class="nav flex-column">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
        🏠 <span class="label">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>">📝 <span class="label">Nama Ujian</span></a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/banksoal') ?>">📘 <span class="label">Bank Soal</span></a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/teststatus') ?>">✅ <span class="label">Status Tes</span></a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/aktivitas') ?>">📅 <span class="label">Aktivitas</span></a>
    </li>    
  </ul>
</div>
