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
    $inSupervisi  = url_is('admin/administrasi-guru*') || url_is('admin/teachers*') || url_is('admin/teachingassignments*') || url_is('admin/teaching-journal*') || url_is('admin/schedules*') || url_is('admin/teacher-attendance*');
    $inKesiswaan  = url_is('admin/students*') || url_is('admin/classes*') || url_is('admin/attendance*') || url_is('admin/grades*') || url_is('admin/erapor*') || url_is('admin/cbt*') || url_is('admin/kokurikuler*') || url_is('admin/bk*') || url_is('admin/student-map*') || url_is('admin/alumni*') || url_is('admin/student-mutation*');
    $inLearning   = url_is('admin/materials*') || url_is('admin/tugas*') || url_is('learning*') || url_is('kalender*') || url_is('admin/ebook*');
    $inTataUsaha  = url_is('admin/tata-usaha*') || url_is('admin/surat-masuk*') || url_is('admin/surat-keluar*') || url_is('admin/agenda-surat*') || url_is('admin/buku-tamu*');
    $inKomunikasi = url_is('admin/announcements*') || url_is('admin/chat*') || url_is('admin/staff-chat*');
    $inCms        = url_is('admin/cms*');
    ?>

    <!-- ══ SUPERVISI GURU & KURIKULUM ══ -->
    <li class="sidebar-category-header">
      <span class="label">Supervisi &amp; Manajerial</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuSupervisi"
        role="button" aria-expanded="<?= $inSupervisi ? 'true' : 'false' ?>" title="Supervisi Guru">
        <i class="bi bi-person-check-fill"></i> <span class="label">Supervisi Guru &amp; KBM</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inSupervisi ? 'show' : '' ?>" id="menuSupervisi">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/monitoring*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/monitoring') ?>"><i class="bi bi-graph-up-arrow me-1"></i> <span class="label">Monitoring Adm Guru</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru') && !url_is('admin/administrasi-guru/monitoring*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru') ?>"><i class="bi bi-journal-bookmark me-1"></i> <span class="label">Kurikulum &amp; Modul Ajar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teaching-journal*') ? 'active' : '' ?>" href="<?= base_url('admin/teaching-journal') ?>"><i class="bi bi-journal-check me-1"></i> <span class="label">Jurnal Mengajar Guru</span></a></li>
          <li><a class="nav-link <?= url_is('admin/schedules*') ? 'active' : '' ?>" href="<?= base_url('admin/schedules') ?>"><i class="bi bi-calendar-week me-1"></i> <span class="label">Jadwal Pelajaran</span></a></li>
          <?php if (is_school_swasta()): ?>
          <li><a class="nav-link <?= url_is('admin/teacher-attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance') ?>"><i class="bi bi-person-badge-fill me-1"></i> <span class="label">Presensi Guru &amp; Staff</span></a></li>
          <?php endif; ?>
          <li><a class="nav-link <?= url_is('admin/teachers*') ? 'active' : '' ?>" href="<?= base_url('admin/teachers') ?>"><i class="bi bi-people me-1"></i> <span class="label">Data Guru &amp; Staff</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teachingassignments*') ? 'active' : '' ?>" href="<?= base_url('admin/teachingassignments') ?>"><i class="bi bi-diagram-2 me-1"></i> <span class="label">Plotting Pengajaran</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ KESISWAAN & AKADEMIK ══ -->
    <li class="sidebar-category-header">
      <span class="label">Kesiswaan &amp; Akademik</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKesiswaanKepsek"
        role="button" aria-expanded="<?= $inKesiswaan ? 'true' : 'false' ?>" title="Kesiswaan & Akademik">
        <i class="bi bi-mortarboard-fill"></i> <span class="label">Kesiswaan &amp; Akademik</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKesiswaan ? 'show' : '' ?>" id="menuKesiswaanKepsek">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/students*') && !url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/students') ?>"><i class="bi bi-people me-1"></i> <span class="label">Data Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/classes*') ? 'active' : '' ?>" href="<?= base_url('admin/classes') ?>"><i class="bi bi-collection me-1"></i> <span class="label">Daftar Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/attendance') ?>"><i class="bi bi-check2-square me-1"></i> <span class="label">Presensi Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades*') || url_is('admin/erapor*') ? 'active' : '' ?>" href="<?= base_url('admin/grades') ?>"><i class="bi bi-clipboard-data me-1"></i> <span class="label">Rekap Nilai &amp; Rapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>"><i class="bi bi-pc-display-horizontal me-1"></i> <span class="label">CBT Ujian Online</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler') ?>"><i class="bi bi-palette me-1"></i> <span class="label">Kokurikuler (P5)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/dashboard') ?>"><i class="bi bi-heart-pulse me-1"></i> <span class="label">Layanan BP/BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/student-map') ?>"><i class="bi bi-geo-alt me-1"></i> <span class="label">Peta Sebaran Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-mutation*') ? 'active' : '' ?>" href="<?= base_url('admin/student-mutation') ?>"><i class="bi bi-arrow-left-right me-1"></i> <span class="label">Buku Mutasi Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/alumni*') ? 'active' : '' ?>" href="<?= base_url('admin/alumni') ?>"><i class="bi bi-patch-check me-1"></i> <span class="label">Alumni</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ E-LEARNING & PERPUSTAKAAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">E-Learning &amp; Literasi</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuLearningKepsek"
        role="button" aria-expanded="<?= $inLearning ? 'true' : 'false' ?>" title="E-Learning & Perpustakaan">
        <i class="bi bi-laptop"></i> <span class="label">E-Learning &amp; Pustaka</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inLearning ? 'show' : '' ?>" id="menuLearningKepsek">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/materials*') ? 'active' : '' ?>" href="<?= base_url('admin/materials') ?>"><i class="bi bi-book me-1"></i> <span class="label">Materi Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('learning*') ? 'active' : '' ?>" href="<?= base_url('learning/class') ?>"><i class="bi bi-graph-up me-1"></i> <span class="label">Analitik Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/ebook*') ? 'active' : '' ?>" href="<?= base_url('admin/ebook') ?>"><i class="bi bi-journal-richtext me-1"></i> <span class="label">Perpustakaan Digital</span></a></li>
          <li><a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>"><i class="bi bi-calendar3 me-1"></i> <span class="label">Kalender Akademik</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ TATA USAHA & PERSURATAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Tata Usaha</span>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuTataUsahaKepsek"
        role="button" aria-expanded="<?= $inTataUsaha ? 'true' : 'false' ?>" title="Tata Usaha">
        <i class="bi bi-folder2-open"></i> <span class="label">Tata Usaha &amp; Surat</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inTataUsaha ? 'show' : '' ?>" id="menuTataUsahaKepsek">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/surat-masuk*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-masuk') ?>"><i class="bi bi-inbox me-1"></i> <span class="label">Surat Masuk</span></a></li>
          <li><a class="nav-link <?= url_is('admin/surat-keluar*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-keluar') ?>"><i class="bi bi-send me-1"></i> <span class="label">Surat Keluar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agenda-surat*') ? 'active' : '' ?>" href="<?= base_url('admin/agenda-surat') ?>"><i class="bi bi-journal me-1"></i> <span class="label">Agenda Surat</span></a></li>
          <li><a class="nav-link <?= url_is('admin/buku-tamu*') ? 'active' : '' ?>" href="<?= base_url('admin/buku-tamu') ?>"><i class="bi bi-book-half me-1"></i> <span class="label">Buku Tamu</span></a></li>
          <li><a class="nav-link <?= url_is('admin/tata-usaha/cetak-daftar-hadir*') ? 'active' : '' ?>" href="<?= base_url('admin/tata-usaha/cetak-daftar-hadir') ?>"><i class="bi bi-printer me-1"></i> <span class="label">Cetak Daftar Hadir</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ KOMUNIKASI & PUBLIKASI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Komunikasi &amp; Web</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/announcements*') ? 'active' : '' ?>" href="<?= base_url('admin/announcements') ?>" title="Pengumuman">
        <i class="bi bi-megaphone"></i> <span class="label">Pengumuman</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/staff-chat*') ? 'active' : '' ?>" href="<?= base_url('admin/staff-chat') ?>" title="Obrolan Staff">
        <i class="bi bi-chat-square-quote"></i> <span class="label">Obrolan Staff</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuCmsKepsek"
        role="button" aria-expanded="<?= $inCms ? 'true' : 'false' ?>" title="Konten Website">
        <i class="bi bi-globe"></i> <span class="label">Website Sekolah (CMS)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCms ? 'show' : '' ?>" id="menuCmsKepsek">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cms/articles*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/articles') ?>"><i class="bi bi-newspaper me-1"></i> <span class="label">Berita &amp; Artikel</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/sambutan*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/sambutan') ?>"><i class="bi bi-person-badge me-1"></i> <span class="label">Sambutan Kepsek</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/sliders*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/sliders') ?>"><i class="bi bi-images me-1"></i> <span class="label">Slider Hero</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/facilities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/facilities') ?>"><i class="bi bi-buildings me-1"></i> <span class="label">Sarana Prasarana</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/activities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/activities') ?>"><i class="bi bi-camera me-1"></i> <span class="label">Dokumentasi</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ AKUN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Akun</span>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>" title="Profil Saya">
        <i class="bi bi-person-gear"></i> <span class="label">Pengaturan Profil</span>
      </a>
    </li>

  </ul>
</div>
