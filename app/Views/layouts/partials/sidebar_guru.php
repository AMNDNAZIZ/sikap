<div class="position-sticky pt-2 sidebar-mini">
<?php
  // Deteksi apakah guru ini wali kelas
  $guruIsWali = false;
  $guruUser   = session()->get('user');
  if (($guruUser['role_id'] ?? null) == 3 && !empty($guruUser['related_id'])) {
      $guruIsWali = (bool) \Config\Database::connect()
          ->table('classes')
          ->where('teacher_id', (int) $guruUser['related_id'])
          ->countAllResults();
  }

  // Deteksi apakah user ini pembina ekskul di tahun ajaran aktif
  $guruIsPembina = false;
  $activeYearId  = null;
  try {
      $activeYearRow = \Config\Database::connect()
          ->table('academic_years')
          ->where('is_active', 1)
          ->get()->getRowArray();
      $activeYearId = $activeYearRow['id'] ?? null;
  } catch (\Throwable $e) {}

  if ($activeYearId && !empty($guruUser['id'])) {
      $guruIsPembina = (bool) \Config\Database::connect()
          ->table('ekskul_pembina')
          ->where('user_id', (int) $guruUser['id'])
          ->where('academic_year_id', $activeYearId)
          ->countAllResults();
  }

  // Deteksi koordinator ekskul
  $isKoordEkskul = false;
  if (($guruUser['role_id'] ?? null) == 3 && !empty($guruUser['related_id'])) {
      $isKoordEkskul = (bool) \Config\Database::connect()
          ->table('teachers')
          ->where('id', (int) $guruUser['related_id'])
          ->where('is_koordinator_ekskul', 1)
          ->countAllResults();
  }

  // Active state detectors
  $inAdmGuru    = url_is('admin/administrasi-guru*');
  $inLearning   = url_is('admin/materials*') || url_is('admin/tugas*') || url_is('admin/quiz*') || url_is('learning*');
  $inKbm        = url_is('admin/attendance*') || url_is('admin/teacher-attendance*') || url_is('admin/teaching-journal*') || url_is('admin/agendas*');
  $inPenilaian  = url_is('admin/assessments*') || url_is('admin/erapor*') || url_is('admin/grades*') || url_is('admin/rapor*');
  $inCbt        = url_is('admin/cbt*');
  $inKokuri     = url_is('admin/kokurikuler*');
  $inBimbingan  = url_is('admin/student-notes*') || url_is('admin/bk*') || url_is('admin/counseling-appointment*') || url_is('admin/counseling-summon*') || url_is('admin/student-map*');
  $inKomunikasi = url_is('admin/announcements*') || url_is('admin/chat*') || url_is('admin/staff-chat*');
?>
  <ul class="nav flex-column">

    <!-- ══ DASHBOARD & UTAMA ══ -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>" title="Dashboard">
        <i class="bi bi-speedometer2"></i> <span class="label">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/schedules*') ? 'active' : '' ?>" href="<?= base_url('admin/schedules') ?>" title="Jadwal Mengajar">
        <i class="bi bi-calendar-week"></i> <span class="label">Jadwal Pelajaran</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>" title="Kalender Akademik">
        <i class="bi bi-calendar3"></i> <span class="label">Kalender Akademik</span>
      </a>
    </li>

    <!-- ══ PERENCANAAN / ADMINISTRASI GURU ══ -->
    <li class="sidebar-category-header">
      <span class="label">Perencanaan Pembelajaran</span>
    </li>

    <!-- Administrasi Guru -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#administrasiGuruMenu"
        role="button" aria-expanded="<?= $inAdmGuru ? 'true' : 'false' ?>" title="Administrasi Guru">
        <i class="bi bi-journal-bookmark-fill"></i> <span class="label">Administrasi Guru</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inAdmGuru ? 'show' : '' ?>" id="administrasiGuruMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/cp') ?>"><i class="bi bi-book me-1"></i> <span class="label">Capaian Pembelajaran (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/tp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/tp') ?>"><i class="bi bi-bullseye me-1"></i> <span class="label">Tujuan Pembelajaran (TP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/atp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/atp') ?>"><i class="bi bi-signpost-split me-1"></i> <span class="label">Alur Pembelajaran (ATP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/kktp*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/kktp') ?>"><i class="bi bi-card-checklist me-1"></i> <span class="label">KKTP</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/prota-prosem*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>"><i class="bi bi-calendar-range me-1"></i> <span class="label">Prota &amp; Prosem</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/modul-ajar*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/modul-ajar') ?>"><i class="bi bi-journal-text me-1"></i> <span class="label">Modul Ajar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/soal-ai*') ? 'active' : '' ?>" href="<?= base_url('admin/administrasi-guru/soal-ai') ?>"><i class="bi bi-cpu me-1"></i> <span class="label">Generate Soal AI</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ KEGIATAN KBM & E-LEARNING ══ -->
    <li class="sidebar-category-header">
      <span class="label">Pelaksanaan KBM</span>
    </li>

    <!-- Learning (LMS) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#learningMenu"
        role="button" aria-expanded="<?= $inLearning ? 'true' : 'false' ?>" title="Learning (LMS)">
        <i class="bi bi-laptop"></i> <span class="label">E-Learning (LMS)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inLearning ? 'show' : '' ?>" id="learningMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/materials*') ? 'active' : '' ?>" href="<?= base_url('admin/materials') ?>"><i class="bi bi-book me-1"></i> <span class="label">Materi Pelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/tugas*') ? 'active' : '' ?>" href="<?= base_url('admin/tugas') ?>"><i class="bi bi-pencil me-1"></i> <span class="label">Kirim Tugas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/quiz*') ? 'active' : '' ?>" href="<?= base_url('admin/quiz') ?>"><i class="bi bi-patch-question me-1"></i> <span class="label">Kuis Mandiri</span></a></li>
          <li><a class="nav-link <?= url_is('learning*') ? 'active' : '' ?>" href="<?= base_url('learning/class') ?>"><i class="bi bi-graph-up me-1"></i> <span class="label">Analitik Pembelajaran</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Aktivitas Kelas -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#aktivitasKelasMenu"
        role="button" aria-expanded="<?= $inKbm ? 'true' : 'false' ?>" title="Aktivitas Kelas">
        <i class="bi bi-clipboard-check"></i> <span class="label">Aktivitas Kelas</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKbm ? 'show' : '' ?>" id="aktivitasKelasMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/attendance') ?>"><i class="bi bi-check2-square me-1"></i> <span class="label">Presensi Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/teaching-journal*') ? 'active' : '' ?>" href="<?= base_url('admin/teaching-journal') ?>"><i class="bi bi-journal-check me-1"></i> <span class="label">Jurnal Mengajar</span></a></li>
          <li><a class="nav-link <?= url_is('admin/agendas*') ? 'active' : '' ?>" href="<?= base_url('admin/agendas') ?>"><i class="bi bi-calendar2-event me-1"></i> <span class="label">Tugas &amp; Agenda Kelas</span></a></li>
          <?php if (is_school_swasta()): ?>
          <li><a class="nav-link <?= url_is('admin/teacher-attendance/my*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance/my') ?>"><i class="bi bi-person-check me-1"></i> <span class="label">Kehadiran Saya</span></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </li>

    <!-- ══ EVALUASI & NILAI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Evaluasi &amp; Penilaian</span>
    </li>

    <!-- Penilaian & Rapor -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#penilaianMenu"
        role="button" aria-expanded="<?= $inPenilaian ? 'true' : 'false' ?>" title="Penilaian & Rapor">
        <i class="bi bi-clipboard-data-fill"></i> <span class="label">Penilaian &amp; Rapor</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inPenilaian ? 'show' : '' ?>" id="penilaianMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/assessments*') ? 'active' : '' ?>" href="<?= base_url('admin/assessments') ?>"><i class="bi bi-pencil-square me-1"></i> <span class="label">Input Nilai Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/erapor*') ? 'active' : '' ?>" href="<?= base_url('admin/erapor') ?>"><i class="bi bi-clipboard-data me-1"></i> <span class="label">Nilai Erapor</span></a></li>
          <li><a class="nav-link <?= url_is('admin/grades*') && !url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades') ?>"><i class="bi bi-file-earmark-bar-graph me-1"></i> <span class="label">Rekap Rapor</span></a></li>
          <?php
            $guruClassId = $guruUser['class_id'] ?? null;
            if ($guruClassId):
          ?>
          <li><a class="nav-link <?= url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades/rekap?class_id=' . $guruClassId) ?>"><i class="bi bi-table me-1"></i> <span class="label">Rekap Nilai Kelas</span></a></li>
          <?php endif; ?>
          <?php if ($guruIsWali): ?>
          <li><a class="nav-link <?= url_is('admin/rapor*') ? 'active' : '' ?>" href="<?= base_url('admin/rapor') ?>"><i class="bi bi-printer me-1"></i> <span class="label">Cetak Rapor Kelas</span></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </li>

    <!-- CBT (Ujian Online) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#cbtMenu"
        role="button" aria-expanded="<?= $inCbt ? 'true' : 'false' ?>" title="CBT (Ujian Online)">
        <i class="bi bi-pc-display-horizontal"></i> <span class="label">CBT (Ujian Online)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inCbt ? 'show' : '' ?>" id="cbtMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/examname') ?>"><i class="bi bi-card-text me-1"></i> <span class="label">Nama Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/banksoal') ?>"><i class="bi bi-folder-check me-1"></i> <span class="label">Bank Soal</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/teststatus') ?>"><i class="bi bi-display me-1"></i> <span class="label">Status &amp; Pengawasan Tes</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/aktivitas') ?>"><i class="bi bi-lightning me-1"></i> <span class="label">Aktivitas Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/convertnilai*') ? 'active' : '' ?>" href="<?= base_url('admin/cbt/convertnilai') ?>"><i class="bi bi-calculator me-1"></i> <span class="label">Konversi Nilai</span></a></li>
        </ul>
      </div>
    </li>

    <!-- ══ P5 & EKSKUL ══ -->
    <li class="sidebar-category-header">
      <span class="label">P5 &amp; Ekstrakurikuler</span>
    </li>

    <!-- Kokurikuler (P5) -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#kokurikulerMenu"
        role="button" aria-expanded="<?= $inKokuri ? 'true' : 'false' ?>" title="Kokurikuler (P5)">
        <i class="bi bi-palette-fill"></i> <span class="label">Kokurikuler (P5)</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKokuri ? 'show' : '' ?>" id="kokurikulerMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/kokurikuler') && !url_is('admin/kokurikuler/pelaksanaan*') && !url_is('admin/kokurikuler/penilaian*') && !url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler') ?>"><i class="bi bi-clipboard-check me-1"></i> <span class="label">Perencanaan P5</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaksanaan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaksanaan') ?>"><i class="bi bi-play-circle me-1"></i> <span class="label">Pelaksanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/penilaian*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/penilaian') ?>"><i class="bi bi-bar-chart-steps me-1"></i> <span class="label">Penilaian / Asesmen</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>" href="<?= base_url('admin/kokurikuler/pelaporan') ?>"><i class="bi bi-file-earmark-text me-1"></i> <span class="label">Pelaporan</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Ekstrakurikuler (Pembina / Koordinator) -->
    <?php if ($guruIsPembina): ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/ekskul-pembina*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul-pembina') ?>" title="Dashboard Ekskul">
        <i class="bi bi-dribbble"></i> <span class="label">Dashboard Ekskul</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if ($isKoordEkskul): ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/ekskul-absensi*') ? 'active' : '' ?>" href="<?= base_url('admin/ekskul-absensi') ?>" title="Verifikasi Absensi Ekskul">
        <i class="bi bi-check2-circle"></i> <span class="label">Verifikasi Absensi Ekskul</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- ══ BIMBINGAN SISWA & KOMUNIKASI ══ -->
    <li class="sidebar-category-header">
      <span class="label">Bimbingan &amp; Komunikasi</span>
    </li>

    <!-- Bimbingan & Catatan Siswa -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#bimbinganMenu"
        role="button" aria-expanded="<?= $inBimbingan ? 'true' : 'false' ?>" title="Bimbingan Siswa">
        <i class="bi bi-heart-pulse-fill"></i> <span class="label">Bimbingan &amp; BK</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inBimbingan ? 'show' : '' ?>" id="bimbinganMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/student-notes*') ? 'active' : '' ?>" href="<?= base_url('admin/student-notes') ?>"><i class="bi bi-card-text me-1"></i> <span class="label">Catatan Siswa</span></a></li>
          <li><a class="nav-link <?= url_is('admin/bk*') ? 'active' : '' ?>" href="<?= base_url('admin/bk/dashboard') ?>"><i class="bi bi-bar-chart me-1"></i> <span class="label">Modul BP/BK Terpadu</span></a></li>
          <li><a class="nav-link <?= url_is('admin/counseling-appointment*') ? 'active' : '' ?>" href="<?= base_url('admin/counseling-appointment') ?>"><i class="bi bi-calendar-plus me-1"></i> <span class="label">Janji Temu BK</span></a></li>
          <li><a class="nav-link <?= url_is('admin/counseling-summon*') ? 'active' : '' ?>" href="<?= base_url('admin/counseling-summon') ?>"><i class="bi bi-envelope-exclamation me-1"></i> <span class="label">Surat Panggilan Ortu</span></a></li>
          <li><a class="nav-link <?= url_is('admin/student-map*') ? 'active' : '' ?>" href="<?= base_url('admin/student-map') ?>"><i class="bi bi-geo-alt me-1"></i> <span class="label">Peta Sebaran Siswa</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Komunikasi -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#komunikasiMenu"
        role="button" aria-expanded="<?= $inKomunikasi ? 'true' : 'false' ?>" title="Komunikasi">
        <i class="bi bi-chat-square-dots"></i> <span class="label">Komunikasi</span>
        <i class="bi bi-chevron-down chevron label ms-auto"></i>
      </a>
      <div class="collapse <?= $inKomunikasi ? 'show' : '' ?>" id="komunikasiMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/announcements*') ? 'active' : '' ?>" href="<?= base_url('admin/announcements') ?>"><i class="bi bi-megaphone me-1"></i> <span class="label">Pengumuman</span></a></li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/chat*') ? 'active' : '' ?>" href="<?= base_url('admin/chat') ?>">
              <i class="bi bi-chat-dots me-1"></i> <span class="label">Obrolan Kelas</span>
              <span id="mentionBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
            </a>
          </li>
          <li>
            <a class="nav-link d-flex align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>" href="<?= base_url('admin/staff-chat') ?>">
              <i class="bi bi-chat-square-quote me-1"></i> <span class="label">Obrolan Staff</span>
              <span id="staffMentionBadge" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
            </a>
          </li>
        </ul>
      </div>
    </li>

    <!-- ══ PENGATURAN & AKUN ══ -->
    <li class="sidebar-category-header">
      <span class="label">Lainnya</span>
    </li>

    <?php
    $guruClassIdEbook = $guruUser['class_id'] ?? null;
    if ($guruClassIdEbook):
    ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/ebook*') ? 'active' : '' ?>" href="<?= base_url('admin/ebook') ?>" title="Perpustakaan Digital">
        <i class="bi bi-journal-richtext"></i> <span class="label">Perpustakaan Digital</span>
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>" title="Profil Saya">
        <i class="bi bi-person-gear"></i> <span class="label">Profilku</span>
      </a>
    </li>

  </ul>
</div>
