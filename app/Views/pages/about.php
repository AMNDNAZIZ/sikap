<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center py-4">
    <div class="col-lg-10 col-xl-9">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold">Tentang ESPATA</h1>
            <p class="lead text-muted">School Management Platform</p>
            <hr class="w-25 mx-auto border-primary border-3">
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="h3 mb-4"><i class="fas fa-rocket text-primary me-2"></i> Mengenal ESPATA</h2>
                <p>ESPATA adalah platform digital terintegrasi yang dirancang untuk mentransformasi manajemen pembelajaran sekolah
                    menjadi lebih efisien, transparan, dan modern. Aplikasi ini mencakup hampir seluruh aspek operasional
                    pembelajaran sekolah, mulai dari pengelolaan data induk, kurikulum, hingga sistem penilaian dan CBT.</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="h5 mb-3 text-primary"><i class="fas fa-check-circle me-2"></i> Fitur Utama</h4>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>Absensi PWA:</strong> 
                                Pencatatan kehadiran digital yang sangat praktis dan dapat diinstal langsung di perangkat mobile (PWA).</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>CBT & Pembuatan Soal AI:</strong> 
                                Pelaksanaan ujian online yang tangguh, dilengkapi dengan fitur cerdas pembuat soal otomatis (Generative AI) berdasarkan TP/ATP materi.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>Administrasi Guru Terstruktur:</strong> 
                                Digitalisasi lengkap penyusunan perangkat ajar seperti CP, TP, ATP, Promes, dan KKTP, terintegrasi penuh untuk Kurikulum Merdeka.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>LMS (Learning Management System):</strong> 
                                Platform pembelajaran interaktif untuk mendistribusikan materi, tugas, dan aktivitas belajar siswa secara online dan terstruktur.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>Manajemen Guru & Staf:</strong> 
                                Pendataan komprehensif mulai dari riwayat pendidikan, pelatihan, penugasan mengajar, dan karier pendidik.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>Pengolahan Nilai & Rapor:</strong> 
                                Kalkulasi nilai akhir otomatis yang terintegrasi dengan sistem pembobotan formatif dan sumatif secara dinamis per tahun ajaran.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <strong>Portal & Komunikasi Interaktif:</strong> 
                                Dilengkapi dengan CMS Website sekolah, sistem pengumuman, serta ruang obrolan (Chat) internal antar warga sekolah.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="h5 mb-3 text-primary"><i class="fas fa-history me-2"></i> Changelog Terbaru</h4>
                        <div class="changelog-scroll" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($changelogs)): ?>
                                <p class="small text-muted text-center py-3 text-start">Belum ada riwayat perubahan.</p>
                            <?php else: ?>
                                <?php foreach ($changelogs as $i => $log): ?>
                                    <div class="mb-3 <?= $i > 0 ? 'border-top pt-3' : '' ?> text-start">
                                        <h6 class="mb-1 fw-bold">
                                            Versi <?= esc($log['version']) ?>
                                            <?php if ($log['is_stable']): ?>
                                                <span class="badge bg-success small">Stable</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?= date('d F Y', strtotime($log['release_date'])) ?></small>
                                        <ul class="small mt-1 mb-0 ps-3 text-muted">
                                            <?php
                                            $features = explode("\n", $log['description']);
                                            foreach ($features as $feature):
                                                if (trim($feature)):
                                                    ?>
                                                    <li><?= esc($feature) ?></li>
                                                <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <p class="text-muted small">ESPATA dikembangkan dengan ❤️ oleh Tim Pengembang SakaSalika.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= base_url() ?>" class="btn btn-outline-primary btn-sm px-4 rounded-pill">Kembali ke
                    Beranda</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>