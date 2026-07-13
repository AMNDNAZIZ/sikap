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
      <a class="nav-link text-info" href="<?= base_url('/') ?>" target="_blank">
        🌐 <span class="label">Lihat Website</span>
      </a>
    </li>

    <?php
    $inDataInduk = url_is('admin/school*') || url_is('admin/academic-year*') || url_is('admin/holidays*') ||
                   url_is('admin/subjects*') || url_is('admin/mapel-master*') ||
                   url_is('admin/administrasi-guru/cp-master*') || url_is('admin/administrasi-guru/mapping*');
    $inGuru      = url_is('admin/teachers*') || url_is('admin/teachingassignments*') || url_is('admin/dapodik*');
    $inSiswa     = (url_is('admin/students*') && !url_is('admin/student-map*')) || url_is('admin/classes*') ||
                   url_is('admin/active-classes*') || url_is('admin/placement*') || url_is('admin/promotions*') ||
                   url_is('admin/student-records*') ||
                   url_is('admin/alumni*') || url_is('admin/student-mutation*');
    $inTataUsaha = url_is('admin/tata-usaha*') || url_is('admin/surat-masuk*') || url_is('admin/surat-keluar*') || url_is('admin/agenda-surat*') || url_is('admin/buku-tamu*') || url_is('admin/settings/kop-surat*');
    ?>

    <!-- ══ SETUP ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Setup</small>
    </li>

    <!-- Data Induk -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuDataInduk"
        role="button" aria-expanded="<?= $inDataInduk ? 'true' : 'false' ?>">
        ⚙️ <span class="label">Data Induk</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inDataInduk ? 'show' : '' ?>" id="menuDataInduk">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/school*') ? 'active' : '' ?>" href="<?= base_url('admin/school') ?>">🏫 <span class="label">Identitas Sekolah</span></a></li>
          <li><a class="nav-link <?= url_is('admin/academic-year*') ? 'active' : '' ?>" href="<?= base_url('admin/academic-year') ?>">📅 <span class="label">Tahun Ajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/holidays*') ? 'active' : '' ?>" href="<?= base_url('admin/holidays') ?>">🌴 <span class="label">Hari Libur</span></a></li>
          <li><a class="nav-link <?= url_is('admin/subjects*') ? 'active' : '' ?>" href="<?= base_url('admin/subjects') ?>">📘 <span class="label">Mata Pelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/mapel-master*') ? 'active' : '' ?>" href="<?= base_url('admin/mapel-master') ?>">📚 <span class="label">Mapel Master</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp-master*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp-master') ?>">📜 <span class="label">Data Master CP</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/mapping*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/mapping') ?>">🔗 <span class="label">Mapping Kurikulum</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ SDM ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">SDM</small>
    </li>

    <!-- Guru & Staff -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuGuru"
        role="button" aria-expanded="<?= $inGuru ? 'true' : 'false' ?>">
        👨‍🏫 <span class="label">Guru & Staff</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inGuru ? 'show' : '' ?>" id="menuGuru">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/teachers*') ? 'active' : '' ?>" href="<?= base_url('admin/teachers') ?>">👨‍🏫 <span class="label">Data Guru & Staff</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teachingassignments*') ? 'active' : '' ?>" href="<?= base_url('admin/teachingassignments') ?>">🗂️ <span class="label">Plotting Pengajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/dapodik*') ? 'active' : '' ?>" href="<?= base_url('admin/dapodik') ?>">🔌 <span class="label">Integrasi Dapodik</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Siswa & Kelas -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuSiswa"
        role="button" aria-expanded="<?= $inSiswa ? 'true' : 'false' ?>">
        🎓 <span class="label">Siswa & Kelas</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inSiswa ? 'show' : '' ?>" id="menuSiswa">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/students*') && !url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/students') ?>">🎓 <span class="label">Data Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/classes*') ? 'active' : '' ?>" href="<?= base_url('admin/classes') ?>">🏷️ <span class="label">Master Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/active-classes*') ? 'active' : '' ?>" href="<?= base_url('admin/active-classes') ?>">🏫 <span class="label">Kelas Aktif</span></a></li>
          <li><a class="nav-link <?= url_is('admin/placement*') ? 'active' : '' ?>" href="<?= base_url('admin/placement') ?>">📌 <span class="label">Penempatan Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/promotions*') ? 'active' : '' ?>" href="<?= base_url('admin/promotions') ?>">🚀 <span class="label">Kenaikan & Kelulusan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-records*') ? 'active' : '' ?>" href="<?= base_url('admin/student-records') ?>">📖 <span class="label">Riwayat Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/alumni*') ? 'active' : '' ?>" href="<?= base_url('admin/alumni') ?>">🎓 <span class="label">Alumni</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-mutation*') ? 'active' : '' ?>" href="<?= base_url('admin/student-mutation') ?>">📋 <span class="label">Buku Mutasi</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Tata Usaha -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuTataUsaha"
        role="button" aria-expanded="<?= $inTataUsaha ? 'true' : 'false' ?>">
        📁 <span class="label">Tata Usaha</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inTataUsaha ? 'show' : '' ?>" id="menuTataUsaha">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/tata-usaha/cetak-daftar-hadir*') ? 'active' : '' ?>" href="<?= base_url('admin/tata-usaha/cetak-daftar-hadir') ?>">📋 <span class="label">Cetak Daftar Hadir</span></a></li>
          <li><a class="nav-link <?= url_is('admin/surat-masuk*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-masuk') ?>">📥 <span class="label">Surat Masuk</span></a></li>
          <li><a class="nav-link <?= url_is('admin/surat-keluar*') ? 'active' : '' ?>" href="<?= base_url('admin/surat-keluar') ?>">📤 <span class="label">Surat Keluar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agenda-surat*') ? 'active' : '' ?>" href="<?= base_url('admin/agenda-surat') ?>">📓 <span class="label">Agenda Surat</span></a></li>
          <li><a class="nav-link <?= url_is('admin/buku-tamu*') ? 'active' : '' ?>" href="<?= base_url('admin/buku-tamu') ?>">📖 <span class="label">Buku Tamu</span></a></li>
          <li><a class="nav-link <?= url_is('admin/settings/kop-surat*') ? 'active' : '' ?>" href="<?= base_url('admin/settings/kop-surat') ?>">📄 <span class="label">KOP Surat</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Pengaturan Profil -->
    <li class="nav-item mt-2">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">
        ⚙️ <span class="label">Pengaturan Profil</span>
      </a>
    </li>

    <!-- Absensi Guru -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuAbsensiGuru"
        role="button" aria-expanded="<?= url_is('admin/teacher-attendance*') ? 'true' : 'false' ?>">
        👨‍🏫 <span class="label">Absensi Guru</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= url_is('admin/teacher-attendance*') ? 'show' : '' ?>" id="menuAbsensiGuru">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/teacher-attendance') && !url_is('admin/teacher-attendance/report*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance') ?>">📋 <span class="label">Input Harian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teacher-attendance/report*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance/report') ?>">📊 <span class="label">Laporan Bulanan</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Obrolan Staff -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>"
         href="<?= base_url('admin/staff-chat') ?>">
        🏫 <span class="label">Obrolan Staff</span>
        <span id="staffMentionBadge" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
      </a>
    </li>

  </ul>
</div>
