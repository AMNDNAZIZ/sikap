<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= base_url() ?>">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profil Sekolah</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #0a1d37 0%, #1e40af 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <?php if (!empty($school['logo'])): ?>
                        <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" height="90" alt="Logo" class="rounded bg-white p-2 shadow-sm">
                    <?php else: ?>
                        <img src="<?= base_url('uploads/logo/espata.png') ?>" alt="ESPATA" height="70" class="p-2 bg-white rounded">
                    <?php endif; ?>
                </div>
                <div class="col-md-10 text-center text-md-start">
                    <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-bold text-uppercase mb-2">Profil Resmi Institusi</span>
                    <h1 class="h2 fw-bold mb-1"><?= esc($school['name'] ?? 'ESPATA') ?></h1>
                    <p class="text-light opacity-75 mb-0 fs-6">
                        <?= ($school['level'] ?? 1) == 1 ? 'Sekolah Dasar' : (($school['level'] ?? 1) == 2 ? 'Sekolah Menengah Pertama' : 'Sekolah Menengah Atas') ?>
                        • Status: <?= ucfirst(esc($school['status'] ?? 'Swasta')) ?>
                        • <?= esc($school['city_regency'] ?? '') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Sambutan & Visi Misi -->
        <div class="col-lg-8">
            <!-- Sambutan Kepala Sekolah -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-person-badge fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-primary">Prakata Kepala Sekolah</h4>
                            <small class="text-muted">Pesan pimpinan kepada seluruh sivitas akademika dan masyarakat</small>
                        </div>
                    </div>

                    <div class="row g-4 align-items-center mb-3">
                        <?php if (!empty($school['vision_image'])): ?>
                            <div class="col-md-4 text-center">
                                <img src="<?= base_url('uploads/vision/' . $school['vision_image']) ?>" 
                                     class="img-fluid rounded-4 shadow-sm object-fit-cover w-100" style="max-height: 250px;" alt="Kepala Sekolah">
                            </div>
                        <?php endif; ?>
                        <div class="<?= !empty($school['vision_image']) ? 'col-md-8' : 'col-12' ?>">
                            <p class="lead text-dark fw-semibold mb-2">
                                "Assalamu’alaikum Warahmatullahi Wabarakatuh,"
                            </p>
                            <p class="text-secondary small">
                                Selamat datang di <?= esc($school['name'] ?? 'sekolah kami') ?>. Kami meyakini bahwa setiap anak memiliki potensi unik yang dapat berkembang optimal bila didukung oleh lingkungan pembelajaran yang aman, bermutu, dan mengedepankan nilai-nilai budi pekerti luhur.
                            </p>
                            <div class="border-top pt-2">
                                <div class="fw-bold text-dark"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></div>
                                <small class="text-muted">Kepala Sekolah <?= !empty($school['principal_nip']) ? '• NIP: ' . esc($school['principal_nip']) : '' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visi & Misi Sekolah -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-bullseye fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-success">Visi & Misi Sekolah</h4>
                            <small class="text-muted">Arah strategis dan komitmen pendidikan</small>
                        </div>
                    </div>

                    <div class="mb-4 p-4 rounded-4" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 5px solid #2563eb;">
                        <h6 class="fw-bold text-primary mb-2 text-uppercase small"><i class="bi bi-star-fill me-1"></i> Visi:</h6>
                        <p class="fs-5 text-dark fw-bold mb-0">
                            "<?= esc($school['vision'] ?? 'Menjadi sekolah unggul dalam prestasi dan berkarakter mulia.') ?>"
                        </p>
                    </div>

                    <div>
                        <h6 class="fw-bold text-success mb-3 text-uppercase small"><i class="bi bi-check2-circle me-1"></i> Misi:</h6>
                        <ul class="list-unstyled mb-0">
                            <?php
                            $missions = explode("\n", $school['mission'] ?? "Memberikan layanan pendidikan terbaik.\nMembangun karakter siswa yang religius dan berbudi luhur.\nMengembangkan potensi akademik dan non-akademik.");
                            $no = 1;
                            foreach ($missions as $m):
                                if (trim($m)):
                            ?>
                                <li class="mb-3 d-flex align-items-start">
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-circle me-3 mt-1 p-2 fw-bold" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                        <?= $no++ ?>
                                    </span>
                                    <span class="text-dark"><?= esc(trim($m)) ?></span>
                                </li>
                            <?php endif; endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Identitas & Kontak -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i> Identitas Sekolah</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <small class="text-muted d-block">Nama Resmi:</small>
                        <strong class="text-dark"><?= esc($school['name'] ?? 'ESPATA') ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Jenjang & Status:</small>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">
                            <?= ($school['level'] ?? 1) == 1 ? 'SD' : (($school['level'] ?? 1) == 2 ? 'SMP' : 'SMA') ?>
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold">
                            <?= ucfirst(esc($school['status'] ?? 'Swasta')) ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Kepala Sekolah:</small>
                        <strong class="text-dark"><?= esc($school['headmaster'] ?? '-') ?></strong>
                    </div>
                    <?php if (!empty($school['principal_nip'])): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">NIP Kepala Sekolah:</small>
                            <span class="text-dark font-monospace"><?= esc($school['principal_nip']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Alamat Lengkap:</small>
                        <span class="text-dark small"><?= esc($school['address'] ?? '-') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Kabupaten / Kota:</small>
                        <span class="text-dark"><?= esc($school['city_regency'] ?? '-') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Telepon / Kontak:</small>
                        <span class="text-dark"><?= esc($school['phone'] ?? '-') ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Surat Elektronik (Email):</small>
                        <span class="text-dark"><?= esc($school['email'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <!-- Tautan Buku Tamu -->
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-light">
                <i class="bi bi-journal-check display-6 text-warning mb-2"></i>
                <h6 class="fw-bold text-dark mb-1">Kunjungan Tamu / Kedinasan</h6>
                <p class="text-muted small mb-3">Silakan mengisi buku tamu digital kami sebelum atau saat bertamu ke sekolah.</p>
                <a href="<?= base_url('buku-tamu') ?>" class="btn btn-warning rounded-pill fw-bold text-dark">
                    <i class="bi bi-pencil-square me-1"></i> Isi Buku Tamu
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
