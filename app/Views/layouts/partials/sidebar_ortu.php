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
            <a class="nav-link <?= url_is('siswa/attendance*') ? 'active' : '' ?>"
                href="<?= site_url('siswa/attendance') ?>">
                🕒 <span class="label">Absensi</span>
            </a>
        </li>

        <!-- Tugas & Agenda -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/agendas*') ? 'active' : '' ?>" href="<?= site_url('siswa/agendas') ?>">
                📅 <span class="label">Tugas & Agenda</span>
            </a>
        </li>

        <!-- Catatan Siswa -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/student-notes*') ? 'active' : '' ?>"
                href="<?= site_url('siswa/student-notes') ?>">
                🗒️ <span class="label">Catatan Siswa</span>
            </a>
        </li>

        <!-- Pengumuman -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/announcement*') ? 'active' : '' ?>"
                href="<?= site_url('siswa/announcement') ?>">
                📢 <span class="label">Pengumuman</span>
            </a>
        </li>

        <!-- CBT 
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/cbt*') ? 'active' : '' ?>" href="<?= site_url('siswa/cbt') ?>">
                💻 <span class="label">CBT</span>
            </a>
        </li>-->

        <!-- Ganti Password / Profil -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= site_url('profile') ?>">
                🔒 <span class="label">Ganti Password</span>
            </a>
        </li>

    </ul>
</div>