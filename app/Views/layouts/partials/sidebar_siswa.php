<div class="position-sticky pt-2 sidebar-mini">
  <ul class="nav flex-column">

    <!-- ══ BERANDA & UTAMA ══ -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>" title="Dashboard">
        <i class="bi bi-speedometer2"></i> <span class="label">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/profile*') ? 'active' : '' ?>" href="<?= site_url('siswa/profile') ?>" title="Profil Saya">
        <i class="bi bi-person-circle"></i> <span class="label">Profil Saya</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>" title="Kalender Akademik">
        <i class="bi bi-calendar3"></i> <span class="label">Kalender Akademik</span>
      </a>
    </li>

    <!-- ══ PEMBELAJARAN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Pembelajaran</span>
    </li>

    <!-- Proses Belajar (LMS: Materi, Segmen & Kuis) -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/belajar*') ? 'active' : '' ?>" href="<?= site_url('siswa/belajar') ?>" title="Proses Belajar">
        <i class="bi bi-mortarboard-fill text-primary"></i> <span class="label fw-semibold">Proses Belajar</span>
      </a>
    </li>

    <!-- Tugas & Agenda -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/tugas*') || url_is('siswa/agendas*') ? 'active' : '' ?>" href="<?= site_url('siswa/tugas') ?>" title="Tugas & Agenda">
        <i class="bi bi-pencil-square"></i> <span class="label">Tugas &amp; Agenda</span>
      </a>
    </li>

    <!-- CBT (Ujian Online) -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/cbt*') ? 'active' : '' ?>" href="<?= site_url('siswa/cbt') ?>" title="CBT Ujian">
        <i class="bi bi-pc-display-horizontal"></i> <span class="label">CBT Ujian Online</span>
      </a>
    </li>

    <!-- Daftar Nilai -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/grades*') ? 'active' : '' ?>" href="<?= site_url('siswa/grades') ?>" title="Daftar Nilai">
        <i class="bi bi-clipboard-data"></i> <span class="label">Daftar Nilai</span>
      </a>
    </li>

    <!-- Analitik Belajar -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('learning*') ? 'active' : '' ?>" href="<?= base_url('learning') ?>" title="Analitik Belajar">
        <i class="bi bi-graph-up"></i> <span class="label">Analitik Belajar</span>
      </a>
    </li>

    <!-- ══ KEGIATAN & PRESENSI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Aktivitas &amp; Ekskul</span>
    </li>

    <!-- Presensi Kehadiran -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/attendance*') ? 'active' : '' ?>" href="<?= site_url('siswa/attendance') ?>" title="Presensi">
        <i class="bi bi-check2-square"></i> <span class="label">Presensi Kehadiran</span>
      </a>
    </li>

    <!-- Ekstrakurikuler -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/ekskul*') ? 'active' : '' ?>" href="<?= site_url('siswa/ekskul') ?>" title="Ekstrakurikuler">
        <i class="bi bi-dribbble"></i> <span class="label">Ekstrakurikuler</span>
      </a>
    </li>

    <!-- Perpustakaan Digital -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/ebook*') ? 'active' : '' ?>" href="<?= site_url('siswa/ebook') ?>" title="Perpustakaan">
        <i class="bi bi-journal-richtext"></i> <span class="label">Perpustakaan Digital</span>
      </a>
    </li>

    <!-- ══ LAYANAN SISWA ══ -->
    <li class="sidebar-category-header">
      <span class="label">Layanan Siswa</span>
    </li>

    <!-- Bimbingan Konseling -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/counseling') && !url_is('siswa/counseling/career*') ? 'active' : '' ?>" href="<?= site_url('siswa/counseling') ?>" title="Bimbingan Konseling">
        <i class="bi bi-heart-pulse"></i> <span class="label">Bimbingan Konseling</span>
      </a>
    </li>

    <!-- Angket Minat & Bakat -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/counseling/career*') ? 'active' : '' ?>" href="<?= site_url('siswa/counseling/career') ?>" title="Angket Minat & Bakat">
        <i class="bi bi-bullseye"></i> <span class="label"><?= is_school_sd() ? 'Angket Minat &amp; Bakat' : 'Angket Karir &amp; Bakat' ?></span>
      </a>
    </li>

    <!-- Catatan Siswa -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/student-notes*') ? 'active' : '' ?>" href="<?= site_url('siswa/student-notes') ?>" title="Catatanku">
        <i class="bi bi-card-text"></i> <span class="label">Catatanku</span>
      </a>
    </li>

    <!-- Lokasi Rumah -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/location*') ? 'active' : '' ?>" href="<?= site_url('siswa/location') ?>" title="Lokasi Rumah">
        <i class="bi bi-geo-alt"></i> <span class="label">Lokasi Rumah</span>
      </a>
    </li>

    <?php if (is_school_swasta()): ?>
    <!-- Pembayaran (Sekolah Swasta) -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/payment*') ? 'active' : '' ?>" href="<?= site_url('siswa/payment') ?>" title="Pembayaran">
        <i class="bi bi-credit-card"></i> <span class="label">Pembayaran SPP</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ══ KOMUNIKASI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Komunikasi</span>
    </li>

    <!-- Pengumuman -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/announcement*') ? 'active' : '' ?>" href="<?= site_url('siswa/announcement') ?>" title="Pengumuman">
        <i class="bi bi-megaphone"></i> <span class="label">Pengumuman</span>
      </a>
    </li>

    <!-- Obrolan Kelas -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center <?= url_is('siswa/chat*') ? 'active' : '' ?>" href="<?= site_url('siswa/chat') ?>" title="Obrolan Kelas">
        <i class="bi bi-chat-dots"></i> <span class="label ms-2">Obrolan Kelas</span>
        <span id="mentionBadge" class="badge bg-danger ms-auto" style="display:none;">0</span>
      </a>
    </li>

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