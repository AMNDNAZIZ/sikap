<div class="position-sticky pt-2 sidebar-mini">
  <ul class="nav flex-column">

    <!-- ══ DASHBOARD & BERANDA ══ -->
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
    $inDataInduk   = url_is('admin/school*') || url_is('admin/academic-year*') || url_is('admin/holidays*') || url_is('admin/subjects*') || url_is('admin/ekskul*');
    $inKesiswaan   = (url_is('admin/students*') && !url_is('admin/student-map*')) || url_is('admin/classes*') || url_is('admin/active-classes*') || url_is('admin/placement*') || url_is('admin/promotions*') || url_is('admin/student-records*') || url_is('admin/student-mutation*') || url_is('admin/student-map*') || url_is('admin/alumni*');
    $inKepegawaian = url_is('admin/teachers*') || url_is('admin/teachingassignments*') || url_is('admin/dapodik*');
    $inKurikulum   = url_is('admin/administrasi-guru*') || url_is('admin/mapel-master*');
    $inJadwal      = url_is('admin/schedules*') || url_is('admin/agendas*') || url_is('admin/teaching-journal*') || url_is('kalender*');
    $inPresensi    = url_is('admin/attendance*') || url_is('admin/teacher-attendance*') || url_is('admin/ekskul-absensi*');
    $inNilai       = url_is('admin/assessments*') || url_is('admin/grades*') || url_is('admin/erapor*') || url_is('admin/rapor*');
    $inKokuri      = url_is('admin/kokurikuler*');
    $inLearning    = url_is('admin/materials*') || url_is('admin/tugas*') || url_is('admin/quiz*') || url_is('learning*') || url_is('admin/ebook*');
    $inCbt         = url_is('admin/cbt*') || url_is('admin/exam-schedule*') || url_is('admin/kartu-peserta*');
    $inKonseling   = url_is('admin/bk*') || url_is('admin/counseling*') || url_is('admin/counseling-appointment*') || url_is('admin/counseling-summon*') || url_is('admin/student-notes*') || url_is('admin/behaviors*');
    $inTataUsaha   = url_is('admin/tata-usaha*') || url_is('admin/surat-masuk*') || url_is('admin/surat-keluar*') || url_is('admin/agenda-surat*') || url_is('admin/buku-tamu*') || url_is('admin/settings/kop-surat*');
    $inKomunikasi  = url_is('admin/announcements*') || url_is('admin/chat*') || url_is('admin/staff-chat*');
    $inCms         = url_is('admin/cms*') || url_is('admin/changelogs*');
    $inSistem      = url_is('admin/users*') || url_is('admin/audit-logs*') || url_is('admin/role-permission*') || url_is('profile*') || url_is('admin/updater*') || url_is('admin/settings/session*') || url_is('admin/settings/payment*');
    ?>

    <!-- ══ DATA MASTER & KESISWAAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Data &amp; Kesiswaan</span>
    </li>

    <!-- Data Induk Sekolah -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuDataInduk"
        role="button" aria-expanded="<?= $inDataInduk ? 'true' : 'false' ?>" title="Data Induk">
        <i class="bi bi-building-gear"></i> <span class="label">Data Induk</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inDataInduk ? 'show' : '' ?>" id="menuDataInduk">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/school*') ? 'active' : '' ?>" href="<?= base_url('admin/school') ?>"><i class="bi bi-building me-1"></i> <span class="label">Identitas Sekolah</span></a></li>
          <li><a class="nav-link <?= url_is('admin/academic-year*') ? 'active' : '' ?>" href="<?= base_url('admin/academic-year') ?>"><i class="bi bi-calendar-check me-1"></i> <span class="label">Tahun Ajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/holidays*') ? 'active' : '' ?>" href="<?= base_url('admin/holidays') ?>"><i class="bi bi-calendar-x me-1"></i> <span class="label">Hari Libur</span></a></li>
          <li><a class="nav-link <?= url_is('admin/subjects*') ? 'active' : '' ?>" href="<?= base_url('admin/subjects') ?>"><i class="bi bi-journals me-1"></i> <span class="label">Mata Pelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/ekskul*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul') ?>"><i class="bi bi-dribbble me-1"></i> <span class="label">Master Ekskul</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Kesiswaan & Kelas -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuSiswa"
        role="button" aria-expanded="<?= $inKesiswaan ? 'true' : 'false' ?>" title="Kesiswaan & Kelas">
        <i class="bi bi-mortarboard"></i> <span class="label">Kesiswaan &amp; Kelas</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKesiswaan ? 'show' : '' ?>" id="menuSiswa">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/students*') && !url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/students') ?>"><i class="bi bi-people me-1"></i> <span class="label">Data Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/classes*') ? 'active' : '' ?>" href="<?= base_url('admin/classes') ?>"><i class="bi bi-collection me-1"></i> <span class="label">Master Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/active-classes*') ? 'active' : '' ?>" href="<?= base_url('admin/active-classes') ?>"><i class="bi bi-door-open me-1"></i> <span class="label">Kelas Aktif</span></a></li>
          <li><a class="nav-link <?= url_is('admin/placement*') ? 'active' : '' ?>" href="<?= base_url('admin/placement') ?>"><i class="bi bi-pin-angle me-1"></i> <span class="label">Penempatan Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/promotions*') ? 'active' : '' ?>" href="<?= base_url('admin/promotions') ?>"><i class="bi bi-award me-1"></i> <span class="label">Kenaikan &amp; Kelulusan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-records*') ? 'active' : '' ?>" href="<?= base_url('admin/student-records') ?>"><i class="bi bi-clock-history me-1"></i> <span class="label">Riwayat Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-mutation*') ? 'active' : '' ?>" href="<?= base_url('admin/student-mutation') ?>"><i class="bi bi-arrow-left-right me-1"></i> <span class="label">Buku Mutasi</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/student-map') ?>"><i class="bi bi-geo-alt me-1"></i> <span class="label">Peta Sebaran Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/alumni*') ? 'active' : '' ?>" href="<?= base_url('admin/alumni') ?>"><i class="bi bi-patch-check me-1"></i> <span class="label">Alumni</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Guru & Kepegawaian -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuGuru"
        role="button" aria-expanded="<?= $inKepegawaian ? 'true' : 'false' ?>" title="Guru & Staff">
        <i class="bi bi-person-badge"></i> <span class="label">Guru &amp; Kepegawaian</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKepegawaian ? 'show' : '' ?>" id="menuGuru">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/teachers*') ? 'active' : '' ?>" href="<?= base_url('admin/teachers') ?>"><i class="bi bi-person-badge me-1"></i> <span class="label">Data Guru &amp; Staff</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teachingassignments*') ? 'active' : '' ?>" href="<?= base_url('admin/teachingassignments') ?>"><i class="bi bi-diagram-2 me-1"></i> <span class="label">Plotting Pengajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/dapodik*') ? 'active' : '' ?>" href="<?= base_url('admin/dapodik') ?>"><i class="bi bi-plug me-1"></i> <span class="label">Integrasi Dapodik</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ AKADEMIK & KURIKULUM ══ -->
    <li class="sidebar-category-header">
      <span class="label">Akademik &amp; KBM</span>
    </li>

    <!-- Kurikulum & Administrasi Guru -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKurikulum"
        role="button" aria-expanded="<?= $inKurikulum ? 'true' : 'false' ?>" title="Kurikulum">
        <i class="bi bi-journal-bookmark-fill"></i> <span class="label">Kurikulum &amp; Adm Guru</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKurikulum ? 'show' : '' ?>" id="menuKurikulum">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/administrasi-guru') && !url_is('admin/administrasi-guru/*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru') ?>"><i class="bi bi-house me-1"></i> <span class="label">Dashboard Adm Guru</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/monitoring*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/monitoring') ?>"><i class="bi bi-graph-up-arrow me-1"></i> <span class="label">Monitoring Adm Guru</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp-master*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp-master') ?>"><i class="bi bi-file-earmark-ruled me-1"></i> <span class="label">Data Master CP</span></a></li>
          <li><a class="nav-link <?= url_is('admin/mapel-master*') ? 'active' : '' ?>" href="<?= base_url('admin/mapel-master') ?>"><i class="bi bi-book-half me-1"></i> <span class="label">Mapel Master (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/mapping*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/mapping') ?>"><i class="bi bi-link-45deg me-1"></i> <span class="label">Mapping Kurikulum</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp') && !url_is('admin/administrasi-guru/cp-master*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp') ?>"><i class="bi bi-book me-1"></i> <span class="label">Capaian Pembelajaran (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/tp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/tp') ?>"><i class="bi bi-bullseye me-1"></i> <span class="label">Tujuan Pembelajaran (TP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/atp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/atp') ?>"><i class="bi bi-signpost-split me-1"></i> <span class="label">Alur Pembelajaran (ATP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/kktp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/kktp') ?>"><i class="bi bi-card-checklist me-1"></i> <span class="label">KKTP</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/prota-prosem*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>"><i class="bi bi-calendar-range me-1"></i> <span class="label">Prota &amp; Prosem</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/modul-ajar*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/modul-ajar') ?>"><i class="bi bi-journal-text me-1"></i> <span class="label">Modul Ajar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/soal-ai*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/soal-ai') ?>"><i class="bi bi-cpu me-1"></i> <span class="label">Generate Soal AI</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Jadwal & Agenda -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuJadwal"
        role="button" aria-expanded="<?= $inJadwal ? 'true' : 'false' ?>" title="Jadwal & Agenda">
        <i class="bi bi-calendar-week"></i> <span class="label">Jadwal &amp; Agenda</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inJadwal ? 'show' : '' ?>" id="menuJadwal">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/schedules*') ? 'active' : '' ?>" href="<?= base_url('admin/schedules') ?>"><i class="bi bi-calendar2-range me-1"></i> <span class="label">Jadwal Pelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agendas*') ? 'active' : '' ?>" href="<?= base_url('admin/agendas') ?>"><i class="bi bi-calendar2-event me-1"></i> <span class="label">Agenda Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teaching-journal*') ? 'active' : '' ?>" href="<?= base_url('admin/teaching-journal') ?>"><i class="bi bi-journal-check me-1"></i> <span class="label">Jurnal Mengajar</span></a></li>
          <li><a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>"><i class="bi bi-calendar3 me-1"></i> <span class="label">Kalender Akademik</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Presensi / Absensi -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuPresensi"
        role="button" aria-expanded="<?= $inPresensi ? 'true' : 'false' ?>" title="Presensi">
        <i class="bi bi-check2-square"></i> <span class="label">Presensi Kehadiran</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inPresensi ? 'show' : '' ?>" id="menuPresensi">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/attendance') ?>"><i class="bi bi-person-check me-1"></i> <span class="label">Presensi Siswa</span></a></li>
          <?php if (is_school_swasta()): ?>
          <li><a class="nav-link <?= url_is('admin/teacher-attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance') ?>"><i class="bi bi-person-badge-fill me-1"></i> <span class="label">Presensi Guru &amp; Staff</span></a></li>
          <?php endif; ?>
          <li><a class="nav-link <?= url_is('admin/ekskul-absensi*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul-absensi') ?>"><i class="bi bi-dribbble me-1"></i> <span class="label">Presensi Pembina Ekskul</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Penilaian & Rapor -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuNilai"
        role="button" aria-expanded="<?= $inNilai ? 'true' : 'false' ?>" title="Penilaian & Rapor">
        <i class="bi bi-clipboard-data-fill"></i> <span class="label">Penilaian &amp; Rapor</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inNilai ? 'show' : '' ?>" id="menuNilai">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/assessments*') ? 'active' : '' ?>" href="<?= base_url('admin/assessments') ?>"><i class="bi bi-pencil-square me-1"></i> <span class="label">Input Nilai</span></a></li>
          <li><a class="nav-link <?= url_is('admin/erapor*') ? 'active' : '' ?>" href="<?= base_url('admin/erapor') ?>"><i class="bi bi-clipboard-data me-1"></i> <span class="label">Nilai Erapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades/rekap') ?>"><i class="bi bi-table me-1"></i> <span class="label">Rekap Nilai Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades') && !url_is('admin/grades/tracking*') && !url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades') ?>"><i class="bi bi-file-earmark-bar-graph me-1"></i> <span class="label">Laporan Rapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades/tracking*') ? 'active' : '' ?>" href="<?= base_url('admin/grades/tracking') ?>"><i class="bi bi-activity me-1"></i> <span class="label">Tracking Nilai</span></a></li>
          <li><a class="nav-link <?= url_is('admin/rapor*') ? 'active' : '' ?>" href="<?= base_url('admin/rapor') ?>"><i class="bi bi-printer me-1"></i> <span class="label">Cetak Rapor</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Kokurikuler (P5) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKokurikuler"
        role="button" aria-expanded="<?= $inKokuri ? 'true' : 'false' ?>" title="Kokurikuler">
        <i class="bi bi-palette-fill"></i> <span class="label">Kokurikuler (P5)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKokuri ? 'show' : '' ?>" id="menuKokurikuler">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/kokurikuler') && !url_is('admin/kokurikuler/pelaksanaan*') && !url_is('admin/kokurikuler/penilaian*') && !url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler') ?>"><i class="bi bi-clipboard-check me-1"></i> <span class="label">Perencanaan P5</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaksanaan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaksanaan') ?>"><i class="bi bi-play-circle me-1"></i> <span class="label">Pelaksanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/penilaian*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/penilaian') ?>"><i class="bi bi-bar-chart-steps me-1"></i> <span class="label">Penilaian / Asesmen</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaporan') ?>"><i class="bi bi-file-earmark-text me-1"></i> <span class="label">Pelaporan</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ E-LEARNING & CBT ══ -->
    <li class="sidebar-category-header">
      <span class="label">E-Learning &amp; CBT</span>
    </li>

    <!-- Learning (LMS) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuLearning"
        role="button" aria-expanded="<?= $inLearning ? 'true' : 'false' ?>" title="Learning (LMS)">
        <i class="bi bi-laptop"></i> <span class="label">Learning (LMS)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inLearning ? 'show' : '' ?>" id="menuLearning">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/materials*') ? 'active' : '' ?>" href="<?= base_url('admin/materials') ?>"><i class="bi bi-book me-1"></i> <span class="label">Materi Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/tugas*') ? 'active' : '' ?>" href="<?= base_url('admin/tugas') ?>"><i class="bi bi-pencil me-1"></i> <span class="label">Kirim Tugas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/quiz*') ? 'active' : '' ?>" href="<?= base_url('admin/quiz') ?>"><i class="bi bi-patch-question me-1"></i> <span class="label">Kuis Mandiri</span></a></li>
          <li><a class="nav-link <?= url_is('learning*') ? 'active' : '' ?>" href="<?= base_url('learning/class') ?>"><i class="bi bi-graph-up me-1"></i> <span class="label">Analitik Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/ebook*') ? 'active' : '' ?>" href="<?= base_url('admin/ebook') ?>"><i class="bi bi-journal-richtext me-1"></i> <span class="label">Perpustakaan Digital</span></a></li>
        </ul>
      </div>
    </li>

    <!-- CBT (Ujian Online) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuCbt"
        role="button" aria-expanded="<?= $inCbt ? 'true' : 'false' ?>" title="CBT (Ujian Online)">
        <i class="bi bi-pc-display-horizontal"></i> <span class="label">CBT (Ujian Online)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCbt ? 'show' : '' ?>" id="menuCbt">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>"><i class="bi bi-card-text me-1"></i> <span class="label">Nama Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/exam-schedule*') ? 'active' : '' ?>" href="<?= base_url('admin/exam-schedule') ?>"><i class="bi bi-clock-history me-1"></i> <span class="label">Jadwal Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/banksoal') ?>"><i class="bi bi-folder-check me-1"></i> <span class="label">Bank Soal</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kartu-peserta*') ? 'active' : '' ?>" href="<?= base_url('admin/kartu-peserta') ?>"><i class="bi bi-person-vcard me-1"></i> <span class="label">Kartu Peserta</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/attendance') ?>"><i class="bi bi-card-list me-1"></i> <span class="label">Daftar Hadir Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/teststatus') ?>"><i class="bi bi-display me-1"></i> <span class="label">Status &amp; Pengawasan Tes</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/aktivitas') ?>"><i class="bi bi-lightning me-1"></i> <span class="label">Aktivitas Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/convertnilai*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/convertnilai') ?>"><i class="bi bi-calculator me-1"></i> <span class="label">Konversi Nilai</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ LAYANAN & PERSURATAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Layanan &amp; Tata Usaha</span>
    </li>

    <!-- Bimbingan Konseling (BP/BK) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKonseling"
        role="button" aria-expanded="<?= $inKonseling ? 'true' : 'false' ?>" title="Bimbingan Konseling">
        <i class="bi bi-heart-pulse-fill"></i> <span class="label">Bimbingan Konseling</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKonseling ? 'show' : '' ?>" id="menuKonseling">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/bk/dashboard*') || url_is('admin/bk') ? 'active' : '' ?>" href="<?= base_url('admin/bk/dashboard') ?>"><i class="bi bi-bar-chart me-1"></i> <span class="label">Dashboard BP/BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/pemetaan*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/pemetaan') ?>"><i class="bi bi-diagram-3 me-1"></i> <span class="label">Pemetaan &amp; Asesmen</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/program*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/program') ?>"><i class="bi bi-calendar4-week me-1"></i> <span class="label">Program &amp; RPL BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/layanan*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/layanan') ?>"><i class="bi bi-people me-1"></i> <span class="label">Layanan BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/kasus*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/kasus') ?>"><i class="bi bi-exclamation-octagon me-1"></i> <span class="label">Penanganan Kasus</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/kolaborasi*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/kolaborasi') ?>"><i class="bi bi-share me-1"></i> <span class="label">Kolaborasi Multi-Role</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/perencanaan-individual*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/perencanaan-individual') ?>"><i class="bi bi-person-lines-fill me-1"></i> <span class="label">Perencanaan Individual</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/laporan*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/laporan') ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i> <span class="label">Laporan &amp; Rekap BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk/settings*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/settings') ?>"><i class="bi bi-gear me-1"></i> <span class="label">Pengaturan Guru BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/counseling-appointment*') ? 'active' : '' ?>" href="<?= base_url('admin/counseling-appointment') ?>"><i class="bi bi-calendar-plus me-1"></i> <span class="label">Janji Temu Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/counseling-summon*') ? 'active' : '' ?>" href="<?= base_url('admin/counseling-summon') ?>"><i class="bi bi-envelope-exclamation me-1"></i> <span class="label">Surat Panggilan Ortu</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Tata Usaha & Persuratan -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuTataUsaha"
        role="button" aria-expanded="<?= $inTataUsaha ? 'true' : 'false' ?>" title="Tata Usaha">
        <i class="bi bi-folder2-open"></i> <span class="label">Tata Usaha &amp; Surat</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inTataUsaha ? 'show' : '' ?>" id="menuTataUsaha">
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

    <!-- ══ KOMUNIKASI & PUBLIKASI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Komunikasi &amp; Web</span>
    </li>

    <!-- Komunikasi -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKomunikasi"
        role="button" aria-expanded="<?= $inKomunikasi ? 'true' : 'false' ?>" title="Komunikasi">
        <i class="bi bi-chat-square-dots"></i> <span class="label">Komunikasi</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKomunikasi ? 'show' : '' ?>" id="menuKomunikasi">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/announcements*') ? 'active' : '' ?>" href="<?= base_url('admin/announcements') ?>"><i class="bi bi-megaphone me-1"></i> <span class="label">Pengumuman</span></a></li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/chat*') ? 'active' : '' ?>" href="<?= base_url('admin/chat') ?>">
              <i class="bi bi-chat-dots me-1"></i> <span class="label">Obrolan Kelas</span>
              <span id="mentionBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
            </a>
          </li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>"
               href="<?= base_url('admin/staff-chat') ?>"
               onclick="if(window.location.href.includes('staff-chat')){event.preventDefault();window.location.reload();}">
              <i class="bi bi-chat-square-quote me-1"></i> <span class="label">Obrolan Staff</span>
              <span id="staffMentionBadge" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
            </a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Website Sekolah (CMS) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuCms"
        role="button" aria-expanded="<?= $inCms ? 'true' : 'false' ?>" title="Konten Website">
        <i class="bi bi-globe"></i> <span class="label">Konten Website (CMS)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCms ? 'show' : '' ?>" id="menuCms">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cms/articles*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/articles') ?>"><i class="bi bi-newspaper me-1"></i> <span class="label">Berita &amp; Artikel</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/sliders*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/sliders') ?>"><i class="bi bi-images me-1"></i> <span class="label">Slider Hero</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/facilities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/facilities') ?>"><i class="bi bi-buildings me-1"></i> <span class="label">Sarana Prasarana</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/activities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/activities') ?>"><i class="bi bi-camera me-1"></i> <span class="label">Dokumentasi</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/links*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/links') ?>"><i class="bi bi-link-45deg me-1"></i> <span class="label">Tautan Pintar</span></a></li>
          <?php if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost'): ?>
            <li><a class="nav-link <?= url_is('admin/changelogs*') ? 'active' : '' ?>" href="<?= base_url('admin/changelogs') ?>"><i class="bi bi-code-square me-1"></i> <span class="label">Changelog Pengembang</span></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </li>

    <!-- ══ PENGATURAN & SISTEM ══ -->
    <li class="sidebar-category-header">
      <span class="label">Pengaturan &amp; Sistem</span>
    </li>

    <!-- Akun & Konfigurasi Sistem -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuAkun"
        role="button" aria-expanded="<?= $inSistem ? 'true' : 'false' ?>" title="Akun & Sistem">
        <i class="bi bi-gear-wide-connected"></i> <span class="label">Akun &amp; Sistem</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inSistem ? 'show' : '' ?>" id="menuAkun">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/users*') ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>"><i class="bi bi-people-fill me-1"></i> <span class="label">Manajemen Pengguna</span></a></li>
          <li><a class="nav-link <?= url_is('admin/role-permission*') ? 'active' : '' ?>" href="<?= base_url('admin/role-permission') ?>"><i class="bi bi-shield-lock me-1"></i> <span class="label">Role &amp; Hak Akses</span></a></li>
          <li><a class="nav-link <?= url_is('admin/audit-logs*') ? 'active' : '' ?>" href="<?= base_url('admin/audit-logs') ?>"><i class="bi bi-fingerprint me-1"></i> <span class="label">Log Aktivitas</span></a></li>
          <li><a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>"><i class="bi bi-person-gear me-1"></i> <span class="label">Pengaturan Profil</span></a></li>
          <li><a class="nav-link <?= url_is('admin/settings/session*') ? 'active' : '' ?>" href="<?= base_url('admin/settings/session') ?>"><i class="bi bi-hdd-network me-1"></i> <span class="label">Cache &amp; Sesi CBT</span></a></li>
          <?php if (is_school_swasta()): ?>
          <li><a class="nav-link <?= url_is('admin/settings/payment*') ? 'active' : '' ?>" href="<?= base_url('admin/settings/payment') ?>"><i class="bi bi-credit-card me-1"></i> <span class="label">Integrasi Pembayaran</span></a></li>
          <?php endif; ?>
          <li><a class="nav-link <?= url_is('admin/updater*') ? 'active' : '' ?>" href="<?= base_url('admin/updater') ?>"><i class="bi bi-arrow-repeat me-1"></i> <span class="label">Pembaruan Sistem</span></a></li>
        </ul>
      </div>
    </li>

  </ul>
</div>
