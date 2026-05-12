<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= $school['name'] ?? 'SIKAP' ?></title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Kumpulan berita, artikel, dan pengumuman terbaru dari <?= esc($school['name'] ?? 'Sekolah Kami') ?>.">
    <meta name="keywords" content="berita, artikel, pengumuman, <?= esc($school['name'] ?? '') ?>">
    <meta name="author" content="<?= esc($school['name'] ?? 'SakaSalika') ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= current_url() ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="<?= $title ?> - <?= $school['name'] ?? 'SIKAP' ?>">
    <meta property="og:description"
        content="Kumpulan berita, artikel, dan pengumuman terbaru dari <?= esc($school['name'] ?? 'Sekolah Kami') ?>.">
    <meta property="og:image" content="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?= current_url() ?>">
    <meta property="twitter:title" content="<?= $title ?> - <?= $school['name'] ?? 'SIKAP' ?>">
    <meta property="twitter:description"
        content="Kumpulan berita, artikel, dan pengumuman terbaru dari <?= esc($school['name'] ?? 'Sekolah Kami') ?>.">
    <meta property="twitter:image" content="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

    <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --dark-color: #1e293b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f9fa;
            color: var(--dark-color);
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--primary-color) !important;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #0a58ca);
            padding: 80px 0;
            color: white;
            margin-bottom: 60px;
        }

        .card {
            border: none;
            border-radius: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .article-img {
            height: 250px;
            object-fit: cover;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .category-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        footer {
            background: #0f172a;
            color: white;
            padding: 40px 0;
            margin-top: 80px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= base_url('/') ?>">
                <?php if (!empty($school['logo'])): ?>
                    <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" height="40" class="me-2" alt="Logo">
                <?php else: ?>
                    <i class="bi bi-book-half me-2"></i>
                <?php endif; ?>
                <?= $school['name'] ?? 'SIKAP' ?>
            </a>
            <div class="ms-auto">
                <a href="<?= base_url('/') ?>" class="btn btn-outline-primary rounded-pill px-4">Kembali ke Beranda</a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="page-header">
        <div class="container">
            <h1 class="display-4 fw-bold mb-2">Berita & Artikel</h1>
            <p class="lead opacity-75">Informasi terbaru seputar kegiatan dan prestasi sekolah kami.</p>
        </div>
    </header>

    <main class="container">
        <div class="row g-4">
            <?php if (empty($articles)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-newspaper text-muted" style="font-size: 4rem; opacity: 0.2;"></i>
                    <h4 class="text-muted mt-3">Belum ada berita yang diterbitkan.</h4>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $a): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="position-relative">
                                <img src="<?= $a['image'] ? base_url('uploads/articles/' . $a['image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=500&q=60' ?>"
                                    class="card-img-top article-img" alt="<?= esc($a['title']) ?>">
                                <span class="category-badge">
                                    <?= esc($a['category']) ?>
                                </span>
                            </div>
                            <div class="card-body p-4">
                                <div class="text-muted small mb-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y', strtotime($a['created_at'])) ?>
                                </div>
                                <h5 class="card-title fw-bold mb-3">
                                    <?= esc($a['title']) ?>
                                </h5>
                                <p class="card-text text-muted small mb-4">
                                    <?= strip_tags(explode("\n", wordwrap($a['content'], 120))[0]) ?>...
                                </p>
                                <a href="<?= base_url('berita/' . $a['slug']) ?>"
                                    class="btn btn-primary w-100 rounded-pill">Baca Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy;
                <?= date('Y') ?>
                <?= $school['name'] ?? 'SIKAP' ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>