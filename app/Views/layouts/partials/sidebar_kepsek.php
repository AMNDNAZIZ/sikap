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
                   url_is('admin/student-records*') || url_is('admin/student-map*') ||
                   url_is('admin/alumni*') || url_is('admin/student-mutation*');
    $inTataUsaha = url_is('admin/tata-usaha*') || url_is('admin/surat-masuk*') || url_is('admin/surat-keluar*') || url_is('admin/agenda-surat*') || url_is('admin/buku-tamu*') || url_is('admin/settings/kop-surat*');
    $inKurikulum = url_is('admin/administrasi-guru*') && !url_is('admin/administrasi-guru/cp-master*') && !url_is('admin/administrasi-guru/mapping*');
    $inJadwal    = url_is('admin/schedules*') || url_is('admin/agendas*') || url_is('admin/teaching-journal*');
    $inPresensi  = url_is('admin/attendance*');
    $inNilai     = url_is('admin/assessments*') || url_is('admin/grades*') || url_is('admin/erapor*');
    $inKokuri    = url_is('admin/kokurikuler*');
    $inCbt       = url_is('admin/cbt*') || url_is('admin/exam-schedule*') || url_is('admin/kartu-peserta*');
    $inKonseling = url_is('admin/student-notes*') || url_is('admin/behaviors*');
    $inKomunikasi= url_is('admin/announcements*') || url_is('admin/chat*') || url_is('admin/staff-chat*');
    $inCms       = url_is('admin/cms*') || url_is('admin/changelogs*');
    $inAkun      = url_is('admin/users*') || url_is('profile*');
    ?>

    <!-- ══ SETUP ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Setup</small>
    </li>

    <!-- Data Induk (Read Only) -->
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
          <li><a class="nav-link <?= url_is('admin/mapel-master*') ? 'active' : '' ?>" href="<?= base_url('admin/mapel-master') ?>">📚 <span class="label">Mapel Master (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp-master*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp-master') ?>">📜 <span class="label">Data Master CP</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/mapping*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/mapping') ?>">🔗 <span class="label">Mapping Kurikulum</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ SDM ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">SDM</small>
    </li>

    <!-- Guru & Staff (Read Only) -->
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
        </ul>
      </div>
    </li>

    <!-- Siswa & Kelas (Read Only) -->
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
          <li><a class="nav-link <?= url_is('admin/student-records*') ? 'active' : '' ?>" href="<?= base_url('admin/student-records') ?>">📖 <span class="label">Riwayat Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/student-map') ?>">🗺️ <span class="label">Peta Sebaran Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/alumni*') ? 'active' : '' ?>" href="<?= base_url('admin/alumni') ?>">🎓 <span class="label">Alumni</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-mutation*') ? 'active' : '' ?>" href="<?= base_url('admin/student-mutation') ?>">📋 <span class="label">Buku Mutasi</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Tata Usaha (Read Only) -->
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

    <!-- ══ KURIKULUM ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Kurikulum</small>
    </li>

    <!-- Kurikulum & Adm Guru (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKurikulum"
        role="button" aria-expanded="<?= $inKurikulum ? 'true' : 'false' ?>">
        📖 <span class="label">Kurikulum & Adm Guru</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKurikulum ? 'show' : '' ?>" id="menuKurikulum">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/administrasi-guru') && !url_is('admin/administrasi-guru/*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru') ?>">🏠 <span class="label">Dashboard Adm</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/monitoring*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/monitoring') ?>">📊 <span class="label">Monitoring Adm Guru</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp') && !url_is('admin/administrasi-guru/cp-master*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp') ?>">📖 <span class="label">Lihat Capaian (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/tp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/tp') ?>">🎯 <span class="label">Tujuan Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/atp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/atp') ?>">🛤️ <span class="label">Alur Pembelajaran (ATP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/prota-prosem*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>">📅 <span class="label">Prota & Prosem</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/modul-ajar*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/modul-ajar') ?>">📂 <span class="label">Modul Ajar</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ KEGIATAN HARIAN ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Kegiatan Harian</small>
    </li>

    <!-- Jadwal & Agenda -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuJadwal"
        role="button" aria-expanded="<?= $inJadwal ? 'true' : 'false' ?>">
        📅 <span class="label">Jadwal & Agenda</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inJadwal ? 'show' : '' ?>" id="menuJadwal">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/schedules*') ? 'active' : '' ?>" href="<?= base_url('admin/schedules') ?>">🗓️ <span class="label">Jadwal Pelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agendas*') ? 'active' : '' ?>" href="<?= base_url('admin/agendas') ?>">📅 <span class="label">Agenda Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teaching-journal*') ? 'active' : '' ?>" href="<?= base_url('admin/teaching-journal') ?>">📓 <span class="label">Jurnal Mengajar</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Presensi (Read Only) -->
    <li class="nav-item">
      <a class="nav-link <?= $inPresensi ? 'active' : '' ?>" href="<?= base_url('admin/attendance') ?>">
        🕒 <span class="label">Presensi/Absensi</span>
      </a>
    </li>

    <!-- Penilaian & Nilai (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuNilai"
        role="button" aria-expanded="<?= $inNilai ? 'true' : 'false' ?>">
        📊 <span class="label">Penilaian & Nilai</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inNilai ? 'show' : '' ?>" id="menuNilai">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/erapor*') ? 'active' : '' ?>" href="<?= base_url('admin/erapor') ?>">📋 <span class="label">Nilai Erapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades/rekap') ?>">📋 <span class="label">Rekap Nilai Kelas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades') && !url_is('admin/grades/tracking*') && !url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades') ?>">📊 <span class="label">Laporan Rapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades/tracking*') ? 'active' : '' ?>" href="<?= base_url('admin/grades/tracking') ?>">🕵️ <span class="label">Tracking Nilai</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Kokurikuler (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKokurikuler"
        role="button" aria-expanded="<?= $inKokuri ? 'true' : 'false' ?>">
        🎨 <span class="label">Kokurikuler</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKokuri ? 'show' : '' ?>" id="menuKokurikuler">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/kokurikuler') && !url_is('admin/kokurikuler/pelaksanaan*') && !url_is('admin/kokurikuler/penilaian*') && !url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler') ?>">📋 <span class="label">Perencanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaksanaan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaksanaan') ?>">▶️ <span class="label">Pelaksanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/penilaian*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/penilaian') ?>">📊 <span class="label">Penilaian/Asesmen</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaporan') ?>">📄 <span class="label">Pelaporan</span></a></li>
        </ul>
      </div>
    </li>

    <!-- CBT (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuCbt"
        role="button" aria-expanded="<?= $inCbt ? 'true' : 'false' ?>">
        💻 <span class="label">CBT (Ujian Online)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCbt ? 'show' : '' ?>" id="menuCbt">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>">📝 <span class="label">Nama Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/exam-schedule*') ? 'active' : '' ?>" href="<?= base_url('admin/exam-schedule') ?>">🕒 <span class="label">Jadwal Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/banksoal') ?>">📘 <span class="label">Bank Soal</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kartu-peserta*') ? 'active' : '' ?>" href="<?= base_url('admin/kartu-peserta') ?>">🪪 <span class="label">Kartu Peserta</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/attendance') ?>">📋 <span class="label">Daftar Hadir</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/teststatus') ?>">✅ <span class="label">Status Tes</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/aktivitas') ?>">📅 <span class="label">Aktivitas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/convertnilai*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/convertnilai') ?>">📊 <span class="label">Konversi Nilai</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ KONSELING & KOMUNIKASI ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Konseling & Komunikasi</small>
    </li>

    <!-- Konseling & Perilaku (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKonseling"
        role="button" aria-expanded="<?= $inKonseling ? 'true' : 'false' ?>">
        🗒️ <span class="label">Konseling & Perilaku</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKonseling ? 'show' : '' ?>" id="menuKonseling">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/student-notes*') ? 'active' : '' ?>" href="<?= base_url('admin/student-notes') ?>">🗒️ <span class="label">Catatan Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/behaviors*') ? 'active' : '' ?>" href="<?= base_url('admin/behaviors') ?>">🎭 <span class="label">Kedisiplinan/Perilaku</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Komunikasi (Kepsek dapat Create untuk Agenda & Pengumuman) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuKomunikasi"
        role="button" aria-expanded="<?= $inKomunikasi ? 'true' : 'false' ?>">
        💬 <span class="label">Komunikasi</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKomunikasi ? 'show' : '' ?>" id="menuKomunikasi">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/announcements*') ? 'active' : '' ?>" href="<?= base_url('admin/announcements') ?>">📢 <span class="label">Pengumuman</span></a></li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/chat*') ? 'active' : '' ?>" href="<?= base_url('admin/chat') ?>">
              💬 <span class="label">Obrolan Kelas</span>
              <span id="mentionBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
            </a>
          </li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>" href="<?= base_url('admin/staff-chat') ?>">
              🏫 <span class="label">Obrolan Staff</span>
              <span id="staffMentionBadge" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
            </a>
          </li>
        </ul>
      </div>
    </li>

    <!-- ══ SISTEM ══ -->
    <li class="nav-item mt-2">
      <small class="text-uppercase text-muted px-3 label" style="font-size:.68rem;letter-spacing:.08em;">Sistem</small>
    </li>

    <!-- Konten Website (Read Only) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuCms"
        role="button" aria-expanded="<?= $inCms ? 'true' : 'false' ?>">
        🌐 <span class="label">Konten Website</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCms ? 'show' : '' ?>" id="menuCms">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cms/sliders*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/sliders') ?>">🖼️ <span class="label">Slider Hero</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/articles*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/articles') ?>">📰 <span class="label">Berita & Artikel</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/facilities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/facilities') ?>">🏫 <span class="label">Sarana Prasarana</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/activities*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/activities') ?>">📸 <span class="label">Dokumentasi</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cms/links*') ? 'active' : '' ?>" href="<?= base_url('admin/cms/links') ?>">🔗 <span class="label">Tautan Pintar</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Akun & Sistem (Tanpa Update Sistem) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#menuAkun"
        role="button" aria-expanded="<?= $inAkun ? 'true' : 'false' ?>">
        👤 <span class="label">Akun & Sistem</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inAkun ? 'show' : '' ?>" id="menuAkun">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/users*') ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">👤 <span class="label">User Management</span></a></li>
          <li><a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">⚙️ <span class="label">Pengaturan Profil</span></a></li>
        </ul>
      </div>
    </li>

  </ul>
</div>
