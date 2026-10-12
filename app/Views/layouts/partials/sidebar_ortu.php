<div class="position-sticky pt-2 sidebar-mini">
  <ul class="nav flex-column">

    <!-- ══ BERANDA ══ -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>" title="Dashboard">
        <i class="bi bi-speedometer2"></i> <span class="label">Dashboard</span>
      </a>
    </li>

    <!-- ══ PERKEMBANGAN ANAK ══ -->
    <li class="sidebar-category-header">
      <span class="label">Perkembangan Anak</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/grades*') ? 'active' : '' ?>" href="<?= site_url('siswa/grades') ?>" title="Daftar Nilai">
        <i class="bi bi-clipboard-data-fill"></i> <span class="label">Daftar Nilai &amp; Rapor</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/attendance*') ? 'active' : '' ?>" href="<?= site_url('siswa/attendance') ?>" title="Presensi">
        <i class="bi bi-check2-square"></i> <span class="label">Presensi Kehadiran</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/agendas*') ? 'active' : '' ?>" href="<?= site_url('siswa/agendas') ?>" title="Tugas & Agenda">
        <i class="bi bi-calendar2-event"></i> <span class="label">Tugas &amp; Agenda Siswa</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/student-notes*') ? 'active' : '' ?>" href="<?= site_url('siswa/student-notes') ?>" title="Catatan Siswa">
        <i class="bi bi-card-text"></i> <span class="label">Catatan Perkembangan</span>
      </a>
    </li>

    <!-- ══ INFORMASI & LAYANAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Informasi Sekolah</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/announcement*') ? 'active' : '' ?>" href="<?= site_url('siswa/announcement') ?>" title="Pengumuman">
        <i class="bi bi-megaphone"></i> <span class="label">Pengumuman</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>" title="Kalender">
        <i class="bi bi-calendar3"></i> <span class="label">Kalender Akademik</span>
      </a>
    </li>

    <?php if (is_school_swasta()): ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/payment*') ? 'active' : '' ?>" href="<?= site_url('siswa/payment') ?>" title="Pembayaran">
        <i class="bi bi-credit-card"></i> <span class="label">Pembayaran SPP</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ══ AKUN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Akun</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= site_url('profile') ?>" title="Ganti Password">
        <i class="bi bi-key"></i> <span class="label">Ganti Password</span>
      </a>
    </li>

  </ul>
</div>