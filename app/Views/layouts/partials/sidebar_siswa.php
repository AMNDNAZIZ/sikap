<div class="position-sticky pt-3">
  <ul class="nav flex-column">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
        🏠 <span class="label">Dashboard</span>
      </a>
    </li>

    <!-- Nilai -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/grades*') ? 'active' : '' ?>" href="<?= site_url('siswa/grades') ?>">
        🎓 <span class="label">Daftar Nilai</span>
      </a>
    </li>

    <!-- Absensi -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/attendance*') ? 'active' : '' ?>" href="<?= site_url('siswa/attendance') ?>">
        🕒 <span class="label">Absensi</span>
      </a>
    </li>

    <!-- Tugas & Agenda -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/agendas*') ? 'active' : '' ?>" href="<?= site_url('siswa/agendas') ?>">
        📅 <span class="label">Tugas & Agenda</span>
      </a>
    </li>

    <!-- Kirim Tugas -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/tugas*') ? 'active' : '' ?>" href="<?= site_url('siswa/tugas') ?>">
        📋 <span class="label">Kirim Tugas</span>
      </a>
    </li>

    <!-- Proses Belajar (Materi + Sub Materi + Diskusi + Kuis) -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/belajar*') ? 'active' : '' ?>" href="<?= site_url('siswa/belajar') ?>">
        <i class="bi bi-mortarboard"></i> <span class="label">Proses Belajar</span>
      </a>
    </li>

    <!-- Analitik Pembelajaran -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('learning*') ? 'active' : '' ?>" href="<?= base_url('learning') ?>">
        <i class="bi bi-graph-up"></i> <span class="label">Analitik Belajar</span>
      </a>
    </li>

    <!-- Kalender Akademik -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>">
        <i class="bi bi-calendar3"></i> <span class="label">Kalender Akademik</span>
      </a>
    </li>

    <!-- Forum Diskusi dihapus — diskusi ada di bawah setiap sub materi -->
    <!-- Catatan Siswa -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/student-notes*') ? 'active' : '' ?>"
        href="<?= site_url('siswa/student-notes') ?>">
        🗒️ <span class="label">Catatanku</span>
      </a>
    </li>

    <!-- Pengumuman -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/announcement*') ? 'active' : '' ?>"
        href="<?= site_url('siswa/announcement') ?>">
        📢 <span class="label">Pengumuman</span>
      </a>
    </li>

    <!-- Obrolan -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/chat*') ? 'active' : '' ?>" href="<?= site_url('siswa/chat') ?>">
        💬 <span class="label">Obrolan</span>
        <span id="mentionBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
      </a>
    </li>

    <!-- Perpustakaan Digital -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/ebook*') ? 'active' : '' ?>" href="<?= site_url('siswa/ebook') ?>">
        📚 <span class="label">Perpustakaan Digital</span>
      </a>
    </li>

    <!-- CBT -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/cbt*') ? 'active' : '' ?>" href="<?= site_url('siswa/cbt') ?>">
        💻 <span class="label">CBT</span>
      </a>
    </li>

    <!-- Profil Saya -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/profile*') ? 'active' : '' ?>" href="<?= site_url('siswa/profile') ?>">
        👤 <span class="label">Profil Saya</span>
      </a>
    </li>

    <!-- Pembayaran (hanya sekolah swasta) -->
    <?php if (is_school_swasta()): ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/payment*') ? 'active' : '' ?>" href="<?= site_url('siswa/payment') ?>">
        💳 <span class="label">Pembayaran</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- Lokasi Rumah -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('siswa/location*') ? 'active' : '' ?>" href="<?= site_url('siswa/location') ?>">
        📍 <span class="label">Lokasi Rumah Saya</span>
      </a>
    </li>

    <!-- Ganti Password -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= site_url('profile') ?>">
        🔒 <span class="label">Ganti Password</span>
      </a>
    </li>

  </ul>
</div>