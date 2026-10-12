<div class="position-sticky pt-2 sidebar-mini">
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

    <?php
    $inTataUsaha   = url_is('admin/tata-usaha*') || url_is('admin/surat-masuk*') || url_is('admin/surat-keluar*') || url_is('admin/agenda-surat*') || url_is('admin/buku-tamu*') || url_is('admin/settings/kop-surat*');
    $inKesiswaan   = (url_is('admin/students*') && !url_is('admin/student-map*')) || url_is('admin/classes*') || url_is('admin/active-classes*') || url_is('admin/placement*') || url_is('admin/promotions*') || url_is('admin/student-records*') || url_is('admin/student-mutation*') || url_is('admin/alumni*');
    $inKepegawaian = url_is('admin/teachers*') || url_is('admin/teachingassignments*') || url_is('admin/dapodik*') || url_is('admin/teacher-attendance*') || url_is('admin/ekskul-absensi*');
    $inDataInduk   = url_is('admin/school*') || url_is('admin/academic-year*') || url_is('admin/holidays*') || url_is('admin/subjects*') || url_is('admin/mapel-master*');
    ?>

    <!-- ══ TATA USAHA & PERSURATAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Tata Usaha &amp; Surat</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuTataUsahaStaf"
        role="button" aria-expanded="<?= $inTataUsaha ? 'true' : 'false' ?>" title="Tata Usaha">
        <i class="bi bi-folder2-open"></i> <span class="label">Persuratan &amp; TU</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inTataUsaha ? 'show' : '' ?>" id="menuTataUsahaStaf">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/surat-masuk*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-masuk') ?>"><i class="bi bi-inbox me-1"></i> <span class="label">Surat Masuk</span></a></li>
          <li><a class="nav-link <?= url_is('admin/surat-keluar*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-keluar') ?>"><i class="bi bi-send me-1"></i> <span class="label">Surat Keluar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agenda-surat*') ? 'active' : '' ?>" href="<?= base_url('admin/agenda-surat') ?>"><i class="bi bi-journal me-1"></i> <span class="label">Agenda Surat</span></a></li>
          <li><a class="nav-link <?= url_is('admin/buku-tamu*') ? 'active' : '' ?>" href="<?= base_url('admin/buku-tamu') ?>"><i class="bi bi-book-half me-1"></i> <span class="label">Buku Tamu</span></a></li>
          <li><a class="nav-link <?= url_is('admin/tata-usaha/cetak-daftar-hadir*') ? 'active' : '' ?>" href="<?= base_url('admin/tata-usaha/cetak-daftar-hadir') ?>"><i class="bi bi-printer me-1"></i> <span class="label">Cetak Daftar Hadir</span></a></li>
          <li><a class="nav-link <?= url_is('admin/settings/kop-surat*') ? 'active' : '' ?>" href="<?= base_url('admin/settings/kop-surat') ?>"><i class="bi bi-file-earmark-richtext me-1"></i> <span class="label">KOP Surat</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ ADMINISTRASI SISWA ══ -->
    <li class="sidebar-category-header">
      <span class="label">Administrasi Siswa</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuSiswaStaf"
        role="button" aria-expanded="<?= $inKesiswaan ? 'true' : 'false' ?>" title="Kesiswaan & Kelas">
        <i class="bi bi-mortarboard-fill"></i> <span class="label">Kesiswaan &amp; Kelas</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKesiswaan ? 'show' : '' ?>" id="menuSiswaStaf">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/students*') && !url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/students') ?>"><i class="bi bi-people me-1"></i> <span class="label">Data Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/classes*') ? 'active' : '' ?>" href="<?= base_url('admin/classes') ?>"><i class="bi bi-collection me-1"></i> <span class="label">Master Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/active-classes*') ? 'active' : '' ?>" href="<?= base_url('admin/active-classes') ?>"><i class="bi bi-door-open me-1"></i> <span class="label">Kelas Aktif</span></a></li>
          <li><a class="nav-link <?= url_is('admin/placement*') ? 'active' : '' ?>" href="<?= base_url('admin/placement') ?>"><i class="bi bi-pin-angle me-1"></i> <span class="label">Penempatan Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/promotions*') ? 'active' : '' ?>" href="<?= base_url('admin/promotions') ?>"><i class="bi bi-award me-1"></i> <span class="label">Kenaikan &amp; Kelulusan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-mutation*') ? 'active' : '' ?>" href="<?= base_url('admin/student-mutation') ?>"><i class="bi bi-arrow-left-right me-1"></i> <span class="label">Buku Mutasi Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-records*') ? 'active' : '' ?>" href="<?= base_url('admin/student-records') ?>"><i class="bi bi-clock-history me-1"></i> <span class="label">Riwayat Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/alumni*') ? 'active' : '' ?>" href="<?= base_url('admin/alumni') ?>"><i class="bi bi-patch-check me-1"></i> <span class="label">Alumni</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ GURU & KEPEGAWAIAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Kepegawaian</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuGuruStaf"
        role="button" aria-expanded="<?= $inKepegawaian ? 'true' : 'false' ?>" title="Guru & Kepegawaian">
        <i class="bi bi-person-badge-fill"></i> <span class="label">Guru &amp; Pegawai</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKepegawaian ? 'show' : '' ?>" id="menuGuruStaf">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/teachers*') ? 'active' : '' ?>" href="<?= base_url('admin/teachers') ?>"><i class="bi bi-person-badge me-1"></i> <span class="label">Data Guru &amp; Staff</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teachingassignments*') ? 'active' : '' ?>" href="<?= base_url('admin/teachingassignments') ?>"><i class="bi bi-diagram-2 me-1"></i> <span class="label">Plotting Pengajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/dapodik*') ? 'active' : '' ?>" href="<?= base_url('admin/dapodik') ?>"><i class="bi bi-plug me-1"></i> <span class="label">Integrasi Dapodik</span></a></li>
          <?php if (is_school_swasta()): ?>
          <li><a class="nav-link <?= url_is('admin/teacher-attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance') ?>"><i class="bi bi-person-check me-1"></i> <span class="label">Presensi Guru &amp; Staff</span></a></li>
          <?php endif; ?>
          <li><a class="nav-link <?= url_is('admin/ekskul-absensi*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul-absensi') ?>"><i class="bi bi-dribbble me-1"></i> <span class="label">Presensi Pembina Ekskul</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ DATA INDUK SEKOLAH ══ -->
    <li class="sidebar-category-header">
      <span class="label">Data Induk</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuDataIndukStaf"
        role="button" aria-expanded="<?= $inDataInduk ? 'true' : 'false' ?>" title="Data Induk">
        <i class="bi bi-building-gear"></i> <span class="label">Data Induk Sekolah</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inDataInduk ? 'show' : '' ?>" id="menuDataIndukStaf">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/school*') ? 'active' : '' ?>" href="<?= base_url('admin/school') ?>"><i class="bi bi-building me-1"></i> <span class="label">Identitas Sekolah</span></a></li>
          <li><a class="nav-link <?= url_is('admin/academic-year*') ? 'active' : '' ?>" href="<?= base_url('admin/academic-year') ?>"><i class="bi bi-calendar-check me-1"></i> <span class="label">Tahun Ajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/holidays*') ? 'active' : '' ?>" href="<?= base_url('admin/holidays') ?>"><i class="bi bi-calendar-x me-1"></i> <span class="label">Hari Libur</span></a></li>
          <li><a class="nav-link <?= url_is('admin/subjects*') ? 'active' : '' ?>" href="<?= base_url('admin/subjects') ?>"><i class="bi bi-journals me-1"></i> <span class="label">Mata Pelajaran</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ PUSTAKA & KOMUNIKASI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Lainnya</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/ebook*') ? 'active' : '' ?>" href="<?= base_url('admin/ebook') ?>" title="Perpustakaan">
        <i class="bi bi-journal-richtext"></i> <span class="label">Perpustakaan Digital</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>" href="<?= base_url('admin/staff-chat') ?>" title="Obrolan Staff">
        <i class="bi bi-chat-square-quote"></i> <span class="label ms-2">Obrolan Staff</span>
        <span id="staffMentionBadge" class="badge bg-warning text-dark ms-auto" style="display:none;">0</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>" title="Profil">
        <i class="bi bi-person-gear"></i> <span class="label">Pengaturan Profil</span>
      </a>
    </li>

  </ul>
</div>
