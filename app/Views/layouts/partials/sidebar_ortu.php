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

        <!-- Pembayaran (hanya sekolah swasta) -->
        <?php if (is_school_swasta()): ?>
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/payment*') ? 'active' : '' ?>" href="<?= site_url('siswa/payment') ?>">
                💳 <span class="label">Pembayaran</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- CBT 
        <li class="nav-item">
            <a class="nav-link <?= url_is('siswa/cbt*') ? 'active' : '' ?>" href="<?= site_url('siswa/cbt') ?>">
                💻 <span class="label">CBT</span>
            </a>
        </li>-->

        <!-- Ganti Password / Profil -->
        <li class="nav-item">
            <a class="nav-link <?= url_is('kalender*') ? 'active' : '' ?>" href="<?= base_url('kalender') ?>">
                <i class="bi bi-calendar3"></i> <span class="label">Kalender Akademik</span>
            </a>
        </li>

        <!-- Forum Diskusi dihapus — diskusi ada di bawah setiap sub materi -->

        <li class="nav-item">
            <a class="nav-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= site_url('profile') ?>">
                🔒 <span class="label">Ganti Password</span>
            </a>
        </li>

    </ul>
</div>