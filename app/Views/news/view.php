<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($article['title']) ?> - <?= $school['name'] ?? 'SIKAP' ?></title>

    <!-- SEO Meta Tags -->
    <?php $description = strip_tags(character_limiter($article['content'], 160)); ?>
    <meta name="description" content="<?= esc($description) ?>">
    <meta name="keywords"
        content="<?= esc($article['category']) ?>, berita, artikel, <?= esc($school['name'] ?? '') ?>">
    <meta name="author" content="Administrator">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= current_url() ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="<?= esc($article['title']) ?> - <?= $school['name'] ?? 'SIKAP' ?>">
    <meta property="og:description" content="<?= esc($description) ?>">
    <meta property="og:image"
        content="<?= $article['image'] ? base_url('uploads/articles/' . $article['image']) : base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= current_url() ?>">
    <meta property="twitter:title" content="<?= esc($article['title']) ?> - <?= $school['name'] ?? 'SIKAP' ?>">
    <meta property="twitter:description" content="<?= esc($description) ?>">
    <meta property="twitter:image"
        content="<?= $article['image'] ? base_url('uploads/articles/' . $article['image']) : base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

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
            background-color: #fff;
            color: var(--dark-color);
            line-height: 1.8;
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

        .article-header {
            padding: 60px 0;
            background-color: #f8f9fa;
            margin-bottom: 40px;
        }

        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 30px;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .article-content {
            font-size: 1.1rem;
            color: #444;
        }

        .sidebar-card {
            border: none;
            border-radius: 20px;
            background-color: #f8f9fa;
            padding: 25px;
            position: sticky;
            top: 100px;
        }

        .latest-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            text-decoration: none;
            color: inherit;
            transition: opacity 0.2s;
        }

        .latest-item:hover {
            opacity: 0.7;
        }

        .latest-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
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
                <a href="<?= base_url('berita') ?>" class="btn btn-outline-primary rounded-pill px-4">Kembali ke
                    Daftar</a>
            </div>
        </div>
    </nav>

    <article>
        <div class="article-header">
            <div class="container text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-3">
                    <?= esc($article['category']) ?>
                </span>
                <h1 class="display-5 fw-bold mb-3">
                    <?= esc($article['title']) ?>
                </h1>
                <div class="text-muted small">
                    <span class="me-3"><i class="bi bi-calendar3 me-1"></i>
                        <?= date('d M Y', strtotime($article['created_at'])) ?>
                    </span>
                    <span><i class="bi bi-person me-1"></i> Administrator</span>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <img src="<?= $article['image'] ? base_url('uploads/articles/' . $article['image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1200&q=80' ?>"
                        class="main-image shadow" alt="<?= esc($article['title']) ?>">

                    <div class="article-content">
                        <?= $article['content'] ?>
                    </div>

                    <div class="mt-5 pt-5 border-top">
                        <h5 class="fw-bold mb-3">Bagikan Berita Ini:</h5>
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-light rounded-circle"
                                style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;"><i
                                    class="bi bi-facebook"></i></a>
                            <a href="#" class="btn btn-light rounded-circle"
                                style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;"><i
                                    class="bi bi-twitter-x"></i></a>
                            <a href="#" class="btn btn-light rounded-circle"
                                style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;"><i
                                    class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mt-5 mt-lg-0">
                    <div class="sidebar-card shadow-sm">
                        <h5 class="fw-bold mb-4">Berita Terbaru</h5>
                        <?php if (empty($latestArticles)): ?>
                            <p class="text-muted small">Tidak ada berita lainnya.</p>
                        <?php else: ?>
                            <?php foreach ($latestArticles as $la): ?>
                                <a href="<?= base_url('berita/' . $la['slug']) ?>" class="latest-item">
                                    <img src="<?= $la['image'] ? base_url('uploads/articles/' . $la['image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=200&q=60' ?>"
                                        class="latest-img" alt="<?= esc($la['title']) ?>">
                                    <div>
                                        <h6 class="fw-bold mb-1"
                                            style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3;">
                                            <?= esc($la['title']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('d M', strtotime($la['created_at'])) ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </article>

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