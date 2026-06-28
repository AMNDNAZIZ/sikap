<div class="position-sticky pt-3 sidebar-mini">
  <ul class="nav flex-column">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
        🏠 <span class="label">Dashboard</span>
      </a>
    </li>

    <!-- Administrasi Guru -->
    <li class="nav-item">
      <a class="nav-link d-flex content-between align-items-left" data-bs-toggle="collapse" href="#administrasiGuruMenu"
        role="button" aria-expanded="<?= url_is('admin/administrasi-guru*') ? 'true' : 'false' ?>">
        📅 <span class="label">Administrasi Guru</span>
        <i class="bi bi-chevron-down chevron label"></i>
      </a>
      <div class="collapse <?= url_is('admin/administrasi-guru*') ? 'show' : '' ?>" id="administrasiGuruMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/cp') ? 'active' : '' ?>"
              href="<?= base_url('admin/administrasi-guru/cp') ?>">📖 <span class="label">Capaian Pembelajaran (CP)</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/tp*') ? 'active' : '' ?>"
              href="<?= base_url('admin/administrasi-guru/tp') ?>">🎯 <span class="label">Tujuan Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/atp*') ? 'active' : '' ?>"
              href="<?= base_url('admin/administrasi-guru/atp') ?>">🛤️ <span class="label">Alur Pembelajaran</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/prota-prosem*') ? 'active' : '' ?>"
              href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>">📅 <span class="label">Prota & Prosem</span></a></li>
          <li><a class="nav-link <?= url_is('admin/administrasi-guru/modul-ajar*') ? 'active' : '' ?>"
              href="<?= base_url('admin/administrasi-guru/modul-ajar') ?>">📂 <span class="label">Modul Ajar</span></a></li>
        </ul>
      </div>
    </li>

    <!-- Kokurikuler -->
    <li class="nav-item">
      <a class="nav-link d-flex content-between align-items-left" data-bs-toggle="collapse" href="#kokurikulerMenu"
        role="button" aria-expanded="<?= url_is('admin/kokurikuler*') ? 'true' : 'false' ?>">
        🎨 <span class="label">Kokurikuler</span>
        <i class="bi bi-chevron-down chevron label"></i>
      </a>
      <div class="collapse <?= url_is('admin/kokurikuler*') ? 'show' : '' ?>" id="kokurikulerMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/kokurikuler') && !url_is('admin/kokurikuler/pelaksanaan*') && !url_is('admin/kokurikuler/penilaian*') && !url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>"
              href="<?= base_url('admin/kokurikuler') ?>">📋 <span class="label">Perencanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaksanaan*') ? 'active' : '' ?>"
              href="<?= base_url('admin/kokurikuler/pelaksanaan') ?>">▶️ <span class="label">Pelaksanaan</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/penilaian*') ? 'active' : '' ?>"
              href="<?= base_url('admin/kokurikuler/penilaian') ?>">📊 <span class="label">Penilaian/Asesmen</span></a></li>
          <li><a class="nav-link <?= url_is('admin/kokurikuler/pelaporan*') ? 'active' : '' ?>"
              href="<?= base_url('admin/kokurikuler/pelaporan') ?>">📄 <span class="label">Pelaporan</span></a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/assessments*') ? 'active' : '' ?>"
        href="<?= base_url('admin/assessments') ?>">
        📝 <span class="label">Input Penilaian</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/erapor*') ? 'active' : '' ?>"
        href="<?= base_url('admin/erapor') ?>">
        📋 <span class="label">Nilai Erapor</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/grades*') && !url_is('admin/grades/rekap*') ? 'active' : '' ?>" href="<?= base_url('admin/grades') ?>">
        📊 <span class="label">Rekap Rapor</span>
      </a>
    </li>

    <?php
    // Menu Rekap Nilai Kelas hanya untuk wali kelas (class_id tersimpan di session saat login)
    $guruUser = session()->get('user');
    $guruClassId = $guruUser['class_id'] ?? null;
    if ($guruClassId):
    ?>
    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/grades/rekap*') ? 'active' : '' ?>"
         href="<?= base_url('admin/grades/rekap?class_id=' . $guruClassId) ?>">
        📋 <span class="label">Rekap Nilai Kelas</span>
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/attendance*') ? 'active' : '' ?>" href="<?= base_url('admin/attendance') ?>">
        🕒 <span class="label">Absensi Siswa</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/teacher-attendance/my*') ? 'active' : '' ?>" href="<?= base_url('admin/teacher-attendance/my') ?>">
        🙋 <span class="label">Kehadiran Saya</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/agendas*') ? 'active' : '' ?>" href="<?= base_url('admin/agendas') ?>">
        📅 <span class="label">Tugas & Agenda</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/teaching-journal*') ? 'active' : '' ?>" href="<?= base_url('admin/teaching-journal') ?>">
        📓 <span class="label">Jurnal Mengajar</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/schedules*') ? 'active' : '' ?>" href="<?= base_url('admin/schedules') ?>">
        🗓️ <span class="label">Jadwal Pelajaran</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/student-notes*') ? 'active' : '' ?>"
        href="<?= base_url('admin/student-notes') ?>">
        🗒️ <span class="label">Catatan Siswa</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/student-map*') ? 'active' : '' ?>"
        href="<?= base_url('admin/student-map') ?>">
        🗺️ <span class="label">Peta Sebaran Siswa</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= url_is('admin/announcements*') ? 'active' : '' ?>"
        href="<?= base_url('admin/announcements') ?>">
        📢 <span class="label">Pengumuman</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex content-between align-items-center <?= url_is('admin/chat*') ? 'active' : '' ?>"
        href="<?= base_url('admin/chat') ?>">
        💬 <span class="label">Obrolan Kelas</span>
        <span id="mentionBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex content-between align-items-center <?= url_is('admin/staff-chat*') ? 'active' : '' ?>"
        href="<?= base_url('admin/staff-chat') ?>">
        🏫 <span class="label">Obrolan Staff</span>
        <span id="staffMentionBadge" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
      </a>
    </li>

    <!-- Profil -->
    <li class="nav-item">
      <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= base_url('profile') ?>">
        🔒 <span class="label">Profilku</span>
      </a>
      <!-- CBT -->
    <li class="nav-item">
      <a class="nav-link d-flex content-between align-items-left" data-bs-toggle="collapse" href="#cbtMenu"
        role="button" aria-expanded="<?= (
          url_is('admin/cbt/examname*') ||
          url_is('admin/cbt/banksoal*') ||
          url_is('admin/cbt/teststatus*') ||
          url_is('admin/cbt/aktivitas*') ||
          url_is('admin/cbt/convertnilai*')
        ) ? 'true' : 'false' ?>">
        💻 <span class="label">CBT</span>
      </a>
      <div class="collapse <?= (
        url_is('admin/cbt/examname*') ||
        url_is('admin/cbt/banksoal*') ||
        url_is('admin/cbt/teststatus*') ||
        url_is('admin/cbt/aktivitas*') ||
        url_is('admin/cbt/reset*') ||
        url_is('admin/cbt/convertnilai*')
      ) ? 'show' : '' ?>" id="cbtMenu">
        <ul class="nav flex-column ms-3">
          <li><a class="nav-link <?= url_is('admin/cbt/examname*') ? 'active' : '' ?>"
              href="<?= base_url('admin/cbt/examname') ?>">📝 <span class="label">Nama Ujian</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/banksoal*') ? 'active' : '' ?>"
              href="<?= base_url('admin/cbt/banksoal') ?>">📘 <span class="label">Bank Soal</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/teststatus*') ? 'active' : '' ?>"
              href="<?= base_url('admin/cbt/teststatus') ?>">✅ <span class="label">Status Tes</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/aktivitas*') ? 'active' : '' ?>"
              href="<?= base_url('admin/cbt/aktivitas') ?>">📅 <span class="label">Aktivitas</span></a></li>
          <li><a class="nav-link <?= url_is('admin/cbt/convertnilai*') ? 'active' : '' ?>"
              href="<?= base_url('admin/cbt/convertnilai') ?>">📊 <span class="label">Konversi Nilai</span></a></li>
        </ul>
      </div>
    </li>

  </ul>
</div>