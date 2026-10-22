<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($school['name'] ?? 'ESPATA') ?> - Portal Resmi Sekolah</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="<?= esc(strip_tags($school['vision'] ?? 'Portal Resmi Sistem Informasi dan Profil Sekolah')) ?>">
  <meta name="keywords" content="sekolah, espata, profil sekolah, pendidikan, <?= esc($school['name'] ?? 'sekolah') ?>, siswa, guru, agenda sekolah">
  <meta name="author" content="<?= esc($school['name'] ?? 'ESPATA') ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= current_url() ?>">

  <!-- Open Graph / Social Media -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= current_url() ?>">
  <meta property="og:title" content="<?= esc($school['name'] ?? 'ESPATA') ?> - Portal Resmi Sekolah">
  <meta property="og:description" content="<?= esc(strip_tags($school['vision'] ?? 'Portal Resmi Sekolah')) ?>">
  <meta property="og:image" content="<?= !empty($school['logo']) ? base_url('uploads/logo/' . $school['logo']) : base_url('uploads/logo/espata.png') ?>">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= !empty($school['logo']) ? base_url('uploads/logo/' . $school['logo']) : base_url('uploads/logo/espata.png') ?>">

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

  <style>
    :root {
      --primary: #1d4ed8;
      --primary-hover: #1e40af;
      --primary-dark: #0a1d37;
      --primary-light: #eff6ff;
      --accent: #f59e0b;
      --accent-green: #10b981;
      --dark: #0f172a;
      --slate: #334155;
      --muted: #64748b;
      --light-bg: #f8fafc;
      --card-border: rgba(226, 232, 240, 0.8);
      --card-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.02);
      --card-hover-shadow: 0 20px 35px -5px rgba(29, 78, 216, 0.12), 0 10px 15px -5px rgba(15, 23, 42, 0.04);
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark);
      background-color: #ffffff;
      overflow-x: hidden;
      line-height: 1.6;
    }

    /* TOP BAR RESMI SEKOLAH */
    .school-topbar {
      background-color: var(--primary-dark);
      color: #cbd5e1;
      font-size: 0.825rem;
      padding: 7px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .school-topbar a {
      color: #e2e8f0;
      text-decoration: none;
      transition: color 0.2s ease;
    }
    .school-topbar a:hover {
      color: #60a5fa;
    }
    .school-topbar .social-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      font-size: 0.75rem;
      transition: all 0.2s;
    }
    .school-topbar .social-link:hover {
      background: var(--primary);
      transform: translateY(-2px);
    }

    /* NAVBAR UTAMA */
    .school-navbar {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(12px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
      padding: 0.75rem 0;
      transition: all 0.3s ease;
    }
    .school-brand-title {
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--primary-dark);
      letter-spacing: -0.5px;
      line-height: 1.2;
    }
    .school-brand-subtitle {
      font-size: 0.75rem;
      color: var(--muted);
      font-weight: 600;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }
    .school-navbar .nav-link {
      font-weight: 600;
      font-size: 0.925rem;
      color: var(--slate) !important;
      padding: 0.5rem 0.9rem !important;
      transition: all 0.2s ease;
      border-radius: 8px;
    }
    .school-navbar .nav-link:hover {
      color: var(--primary) !important;
      background: var(--primary-light);
    }
    .btn-login-espata {
      background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
      color: #ffffff !important;
      font-weight: 700;
      font-size: 0.9rem;
      padding: 0.55rem 1.4rem;
      border-radius: 50px;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
      transition: all 0.3s ease;
      border: none;
    }
    .btn-login-espata:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
      color: #fff !important;
    }

    /* HERO SECTION */
    .hero-section {
      position: relative;
      min-height: 620px;
      background-color: var(--primary-dark);
      overflow: hidden;
    }
    .hero-swiper {
      width: 100%;
      height: 100%;
      min-height: 620px;
    }
    .hero-slide {
      background-size: cover;
      background-position: center;
      position: relative;
      display: flex;
      align-items: center;
      min-height: 620px;
    }
    .hero-overlay {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(135deg, rgba(10, 29, 55, 0.88) 0%, rgba(15, 23, 42, 0.65) 100%);
      display: flex;
      align-items: center;
      padding: 70px 0 110px 0;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 16px;
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50px;
      color: #fcd34d;
      font-weight: 700;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-bottom: 1.25rem;
    }
    .hero-title {
      font-size: 3.2rem;
      font-weight: 800;
      line-height: 1.15;
      color: #ffffff;
      margin-bottom: 1.2rem;
      letter-spacing: -0.5px;
    }
    .hero-desc {
      font-size: 1.15rem;
      color: #e2e8f0;
      max-width: 680px;
      margin-bottom: 2rem;
      line-height: 1.7;
    }
    @media (max-width: 768px) {
      .hero-title { font-size: 2.1rem; }
      .hero-desc { font-size: 1rem; }
      .hero-section, .hero-swiper, .hero-slide { min-height: 540px; }
      .hero-overlay { padding: 40px 0 80px 0; }
    }

    /* QUICK PILLARS (FLOATING ACTION CARDS) */
    .quick-pillars-wrap {
      margin-top: -65px;
      position: relative;
      z-index: 20;
    }
    .quick-pillar-card {
      background: #ffffff;
      border-radius: 18px;
      padding: 22px 20px;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
      border: 1px solid rgba(226, 232, 240, 0.9);
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      display: flex;
      align-items: center;
      gap: 16px;
      text-decoration: none;
      color: inherit;
      height: 100%;
    }
    .quick-pillar-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 18px 36px rgba(29, 78, 216, 0.15);
      border-color: #93c5fd;
      color: inherit;
    }
    .pillar-icon-box {
      width: 54px;
      height: 54px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      flex-shrink: 0;
      transition: transform 0.3s ease;
    }
    .quick-pillar-card:hover .pillar-icon-box {
      transform: scale(1.1);
    }

    /* SECTION HEADERS */
    .section-padding {
      padding: 85px 0;
    }
    .section-tag {
      display: inline-block;
      text-transform: uppercase;
      font-weight: 700;
      font-size: 0.8rem;
      letter-spacing: 1px;
      color: var(--primary);
      margin-bottom: 0.4rem;
    }
    .section-heading {
      font-weight: 800;
      font-size: 2.25rem;
      color: var(--primary-dark);
      letter-spacing: -0.5px;
      line-height: 1.25;
      margin-bottom: 0.75rem;
    }
    .section-subheading {
      color: var(--muted);
      font-size: 1.05rem;
      max-width: 650px;
    }
    @media (max-width: 768px) {
      .section-heading { font-size: 1.75rem; }
      .section-padding { padding: 60px 0; }
    }

    /* STATS COUNTER BAR */
    .stats-bar-section {
      background: linear-gradient(135deg, var(--primary-dark) 0%, #173868 100%);
      color: #ffffff;
      padding: 60px 0;
      position: relative;
    }
    .stat-counter-number {
      font-size: 2.75rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1;
      margin-bottom: 0.4rem;
      letter-spacing: -1px;
    }
    .stat-counter-label {
      font-size: 0.925rem;
      color: #cbd5e1;
      font-weight: 600;
      margin-bottom: 0;
    }

    /* SAMBUTAN KEPALA SEKOLAH */
    .principal-card-box {
      background: #ffffff;
      border-radius: 24px;
      box-shadow: var(--card-shadow);
      border: 1px solid var(--card-border);
      padding: 40px;
    }
    .principal-frame {
      position: relative;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
    }
    .principal-badge {
      position: absolute;
      bottom: 16px;
      left: 16px;
      right: 16px;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(8px);
      border-radius: 12px;
      padding: 10px 14px;
      color: #ffffff;
    }

    /* VISI MISI CARDS */
    .vision-card {
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      border: 1px solid #bfdbfe;
      border-radius: 20px;
      padding: 32px;
      height: 100%;
    }
    .mission-card {
      background: #ffffff;
      border: 1px solid var(--card-border);
      border-radius: 20px;
      padding: 32px;
      box-shadow: var(--card-shadow);
      height: 100%;
    }

    /* CARD HOVER SYSTEM */
    .school-card {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid var(--card-border);
      box-shadow: var(--card-shadow);
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      overflow: hidden;
      height: 100%;
    }
    .school-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--card-hover-shadow);
      border-color: #bfdbfe;
    }

    /* AGENDA & PENGUMUMAN */
    .agenda-item-box {
      border-radius: 14px;
      padding: 14px;
      border: 1px solid #f1f5f9;
      background: #ffffff;
      transition: all 0.2s ease;
    }
    .agenda-item-box:hover {
      background: #f8fafc;
      border-color: #cbd5e1;
      transform: translateX(4px);
    }
    .agenda-date-badge {
      width: 58px;
      height: 58px;
      background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
      color: #ffffff;
      border-radius: 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      line-height: 1.1;
      flex-shrink: 0;
    }

    /* ACTIVITY ALBUM CARDS */
    .activity-album-card {
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      border-radius: 20px;
    }
    .activity-album-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 18px 36px rgba(29, 78, 216, 0.12) !important;
    }
    .activity-album-card .group-hover-zoom img {
      transition: transform 0.6s ease;
    }
    .activity-album-card:hover .group-hover-zoom img {
      transform: scale(1.06);
    }
    .backdrop-blur {
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* GLIGHTBOX CAPTION UPGRADE */
    .gslide-title {
      font-size: 1.2rem !important;
      font-weight: 800 !important;
      color: #ffffff !important;
      margin-bottom: 6px !important;
    }
    .gslide-desc {
      font-size: 0.95rem !important;
      color: #e2e8f0 !important;
      line-height: 1.6 !important;
    }
    .gslide-description.description-bottom {
      background: rgba(10, 29, 55, 0.92) !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      border-radius: 16px 16px 0 0 !important;
      max-width: 850px !important;
      margin: 0 auto !important;
      padding: 18px 28px !important;
      box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.4) !important;
    }

    /* GURU SWIPER */
    .teacher-item-card {
      background: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      border: 1px solid var(--card-border);
      box-shadow: var(--card-shadow);
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      height: 100%;
      max-width: 320px;
      margin-left: auto;
      margin-right: auto;
      display: flex;
      flex-direction: column;
    }
    .teacher-item-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--card-hover-shadow);
    }
    .teacher-item-photo {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      object-position: top center;
      background-color: #f1f5f9;
      display: block;
      transition: transform 0.5s ease;
    }
    .teacher-item-card:hover .teacher-item-photo {
      transform: scale(1.05);
    }
    @supports not (aspect-ratio: 3 / 4) {
      .teacher-item-photo {
        height: 360px;
      }
    }

    /* EKSTRAKURIKULER CARD UPGRADE */
    .ekskul-card-dynamic {
      position: relative;
      overflow: hidden;
      border-radius: 1.25rem;
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.85);
      box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
      transition: transform 0.35s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.35s ease;
      min-height: 200px;
      isolation: isolate;
    }
    .ekskul-card-dynamic:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 35px -5px rgba(29, 78, 216, 0.12), 0 8px 16px -6px rgba(15, 23, 42, 0.04);
      border-color: rgba(191, 219, 254, 0.9);
    }
    .ekskul-bg-img {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center right;
      border-radius: inherit;
      transition: transform 0.5s ease;
      z-index: 1;
    }
    .ekskul-card-dynamic:hover .ekskul-bg-img {
      transform: scale(1.06);
    }
    .ekskul-gradient-overlay {
      position: absolute;
      inset: 0;
      z-index: 2;
      border-radius: inherit;
      /* Gradasi kekanan semakin jelas: kiri solid putih melindungi kejelasan teks, kanan transparan penuh agar foto tampak jelas */
      background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0.98) 35%, rgba(255, 255, 255, 0.82) 55%, rgba(255, 255, 255, 0.28) 82%, rgba(255, 255, 255, 0) 100%);
      pointer-events: none;
    }
    .ekskul-card-content {
      position: relative;
      z-index: 3;
      padding: 1.5rem;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .ekskul-text-body {
      max-width: 78%;
    }
    .ekskul-icon-bubble {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 1.2rem;
      transition: transform 0.3s ease, background 0.3s ease;
      flex-shrink: 0;
    }
    .ekskul-card-dynamic:hover .ekskul-icon-bubble {
      transform: scale(1.1) rotate(6deg);
      background: #ffffff;
      color: var(--primary-hover);
    }
    @media (max-width: 576px) {
      .ekskul-gradient-overlay {
        background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0.98) 45%, rgba(255, 255, 255, 0.75) 75%, rgba(255, 255, 255, 0.2) 100%);
      }
      .ekskul-text-body {
        max-width: 88%;
      }
    }

    /* FOOTER RESMI SEKOLAH */
    .school-footer {
      background-color: var(--primary-dark);
      color: #cbd5e1;
      padding: 70px 0 30px;
      border-top: 5px solid var(--accent);
    }
    .school-footer a {
      color: #cbd5e1;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .school-footer a:hover {
      color: #60a5fa;
      padding-left: 4px;
    }
    .footer-title {
      color: #ffffff;
      font-weight: 700;
      font-size: 1.15rem;
      margin-bottom: 1.4rem;
      position: relative;
    }
    .footer-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 36px;
      height: 3px;
      background: var(--accent);
      border-radius: 2px;
    }
    .footer-contact-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #60a5fa;
      flex-shrink: 0;
      margin-right: 12px;
    }

    /* BUTTONS */
    .btn-rounded {
      border-radius: 50px;
    }
  </style>
</head>

<body>

  <!-- 1. TOP BAR INFORMASI SEKOLAH -->
  <div class="school-topbar">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center flex-wrap gap-3">
          <?php if (!empty($school['phone'])): ?>
            <span><i class="bi bi-telephone-fill me-1 text-warning"></i> <?= esc($school['phone']) ?></span>
          <?php endif; ?>
          <?php if (!empty($school['email'])): ?>
            <span class="d-none d-md-inline"><i class="bi bi-envelope-fill me-1 text-warning"></i> <?= esc($school['email']) ?></span>
          <?php endif; ?>
          <?php if (!empty($school['city_regency'])): ?>
            <span class="d-none d-lg-inline"><i class="bi bi-geo-alt-fill me-1 text-warning"></i> <?= esc($school['city_regency']) ?></span>
          <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- Tautan Akses Layanan Publik Cepat -->
          <a href="<?= base_url('buku-tamu') ?>" class="badge bg-warning text-dark text-decoration-none px-2.5 py-1.5 rounded-pill fw-bold me-1">
            <i class="bi bi-book me-1"></i> Buku Tamu Digital
          </a>
          <a href="<?= base_url('verify/cek') ?>" class="badge bg-primary bg-opacity-75 text-white text-decoration-none px-2.5 py-1.5 rounded-pill fw-semibold me-2 d-none d-sm-inline">
            <i class="bi bi-patch-check me-1"></i> Cek Dokumen QR
          </a>

          <!-- Media Sosial -->
          <div class="d-flex align-items-center gap-1 ms-1">
            <?php if (!empty($school['youtube'])): ?>
              <a href="<?= esc($school['youtube']) ?>" target="_blank" class="social-link" title="YouTube"><i class="bi bi-youtube"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['instagram'])): ?>
              <a href="<?= esc($school['instagram']) ?>" target="_blank" class="social-link" title="Instagram"><i class="bi bi-instagram"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['facebook'])): ?>
              <a href="<?= esc($school['facebook']) ?>" target="_blank" class="social-link" title="Facebook"><i class="bi bi-facebook"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['tiktok'])): ?>
              <a href="<?= esc($school['tiktok']) ?>" target="_blank" class="social-link" title="TikTok"><i class="bi bi-tiktok"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. NAVBAR UTAMA SEKOLAH -->
  <nav class="navbar navbar-expand-lg sticky-top school-navbar">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2 py-0" href="<?= base_url() ?>">
        <?php if (!empty($school['logo'])): ?>
          <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" height="48" alt="Logo" class="rounded">
        <?php else: ?>
          <img src="<?= base_url('uploads/logo/espata.png') ?>" alt="ESPATA" height="42">
        <?php endif; ?>
        <div>
          <div class="school-brand-title"><?= esc($school['name'] ?? 'ESPATA SCHOOL') ?></div>
          <div class="school-brand-subtitle">
            <?= ($school['level'] ?? 1) == 1 ? 'Sekolah Dasar' : (($school['level'] ?? 1) == 2 ? 'Sekolah Menengah Pertama' : 'Sekolah Menengah Atas') ?>
            • <?= strtoupper(esc($school['status'] ?? 'Swasta')) ?>
          </div>
        </div>
      </a>

      <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainSchoolNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainSchoolNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
          <li class="nav-item">
            <a class="nav-link" href="#home"><i class="bi bi-house-door me-1"></i> Beranda</a>
          </li>

          <!-- Dropdown Profil Sekolah -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-building me-1"></i> Profil
            </a>
            <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
              <li><a class="dropdown-item py-2" href="#sambutan"><i class="bi bi-person-badge me-2 text-primary"></i> Sambutan Kepala Sekolah</a></li>
              <li><a class="dropdown-item py-2" href="#profil"><i class="bi bi-bullseye me-2 text-primary"></i> Visi & Misi Sekolah</a></li>
              <li><a class="dropdown-item py-2" href="#guru"><i class="bi bi-people me-2 text-primary"></i> Tenaga Pendidik & Staf</a></li>
              <li><a class="dropdown-item py-2" href="#fasilitas"><i class="bi bi-building-check me-2 text-primary"></i> Sarana & Prasarana</a></li>
            </ul>
          </li>

          <!-- Dropdown Kesiswaan -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-mortarboard me-1"></i> Kesiswaan
            </a>
            <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
              <li><a class="dropdown-item py-2" href="#ekskul"><i class="bi bi-trophy me-2 text-primary"></i> Ekstrakurikuler</a></li>
              <li><a class="dropdown-item py-2" href="#kegiatan"><i class="bi bi-images me-2 text-primary"></i> Dokumentasi & Album Kegiatan</a></li>
            </ul>
          </li>

          <!-- Dropdown Warta & Informasi -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-newspaper me-1"></i> Informasi
            </a>
            <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
              <li><a class="dropdown-item py-2" href="#berita"><i class="bi bi-card-text me-2 text-primary"></i> Berita & Artikel</a></li>
              <li><a class="dropdown-item py-2" href="#agenda"><i class="bi bi-calendar-event me-2 text-primary"></i> Agenda Kegiatan</a></li>
              <li><a class="dropdown-item py-2" href="#pengumuman"><i class="bi bi-megaphone me-2 text-primary"></i> Pengumuman Resmi</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="<?= base_url('buku-tamu') ?>"><i class="bi bi-journal-bookmark me-1"></i> Buku Tamu</a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#kontak"><i class="bi bi-telephone me-1"></i> Kontak</a>
          </li>

          <!-- Tombol Login Portal ESPATA -->
          <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
            <a href="<?= base_url('login') ?>" class="btn btn-login-espata d-inline-flex align-items-center gap-2">
              <i class="bi bi-box-arrow-in-right fs-6"></i>
              <span>Portal ESPATA</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- 3. HERO SLIDER BANNER SEKOLAH -->
  <section id="home" class="hero-section">
    <div class="swiper hero-swiper">
      <div class="swiper-wrapper">
        <?php if (empty($sliders)): ?>
          <div class="swiper-slide hero-slide" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80');">
            <div class="hero-overlay">
              <div class="container">
                <div class="row align-items-center">
                  <div class="col-lg-9" data-aos="fade-up">
                    <span class="hero-badge">
                      <i class="bi bi-star-fill text-warning"></i> Selamat Datang di Portal Resmi
                    </span>
                    <h1 class="hero-title"><?= esc($school['name'] ?? 'ESPATA') ?></h1>
                    <p class="hero-desc">
                      Membentuk insan cerdas, religius, berkarakter mulia, serta siap berprestasi dan menghadapi masa depan dengan teknologi dan integritas.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                      <a href="#sambutan" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                        <i class="bi bi-compass me-1"></i> Jelajahi Profil Sekolah
                      </a>
                      <a href="<?= base_url('buku-tamu') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5 fw-semibold">
                        <i class="bi bi-book me-1"></i> Buku Tamu Online
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($sliders as $s): ?>
            <div class="swiper-slide hero-slide" style="background-image: url('<?= base_url('uploads/sliders/' . $s['image']) ?>');">
              <div class="hero-overlay">
                <div class="container">
                  <div class="row align-items-center">
                    <div class="col-lg-9">
                      <span class="hero-badge" data-aos="fade-down">
                        <i class="bi bi-mortarboard-fill text-warning"></i> <?= esc($school['name'] ?? 'Sekolah Kami') ?>
                      </span>
                      <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">
                        <?= esc($s['title']) ?>
                      </h1>
                      <p class="hero-desc" data-aos="fade-up" data-aos-delay="200">
                        <?= esc($s['description']) ?>
                      </p>
                      <div class="d-flex flex-wrap gap-3" data-aos="fade-up" data-aos-delay="300">
                        <?php if (!empty($s['link'])): ?>
                          <a href="<?= esc($s['link']) ?>" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i> Informasi Lengkap
                          </a>
                        <?php else: ?>
                          <a href="#sambutan" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                            <i class="bi bi-compass me-1"></i> Jelajahi Profil
                          </a>
                        <?php endif; ?>
                        <a href="<?= base_url('login') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5 fw-semibold">
                          <i class="bi bi-person-lock me-1"></i> Masuk ESPATA
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="swiper-pagination"></div>
    </div>
  </section>

  <!-- 4. FLOATING QUICK ACTION PILLARS (Pilar Cepat Web Sekolah) -->
  <div class="container quick-pillars-wrap">
    <div class="row g-3">
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <a href="<?= base_url('login') ?>" class="quick-pillar-card">
          <div class="pillar-icon-box bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-laptop"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-dark">ESPATA & CBT</h6>
            <small class="text-muted d-block">Portal Nilai & Ujian Daring</small>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <a href="<?= base_url('buku-tamu') ?>" class="quick-pillar-card">
          <div class="pillar-icon-box bg-success bg-opacity-10 text-success">
            <i class="bi bi-journal-check"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Buku Tamu</h6>
            <small class="text-muted d-block">Layanan Tamu & Orang Tua</small>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <a href="#ekskul" class="quick-pillar-card">
          <div class="pillar-icon-box bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-trophy"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Ekstrakurikuler</h6>
            <small class="text-muted d-block">Pengembangan Minat Siswa</small>
          </div>
        </a>
      </div>

      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <a href="#pengumuman" class="quick-pillar-card">
          <div class="pillar-icon-box bg-info bg-opacity-10 text-info">
            <i class="bi bi-megaphone"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Warta & Info</h6>
            <small class="text-muted d-block">Pengumuman & Agenda</small>
          </div>
        </a>
      </div>
    </div>
  </div>

  <!-- 5. SAMBUTAN KEPALA SEKOLAH -->
  <?php
    $sambutanTitle = !empty($school['sambutan_title']) ? $school['sambutan_title'] : 'Sambutan Kepala Sekolah';
    $sambutanSubtitle = !empty($school['sambutan_subtitle']) ? $school['sambutan_subtitle'] : 'Prakata Pimpinan';
    $sambutanQuote = !empty($school['sambutan_quote']) ? $school['sambutan_quote'] : 'Assalamu’alaikum Warahmatullahi Wabarakatuh, Salam Sejahtera untuk Kita Semua.';

    // Foto pimpinan: prioritas sambutan_image -> vision_image -> unsplash default
    $sambutanPhoto = !empty($school['sambutan_image']) && file_exists(UPLOAD_PATH . 'sambutan/' . $school['sambutan_image'])
      ? base_url('uploads/sambutan/' . $school['sambutan_image'])
      : (!empty($school['vision_image']) && file_exists(UPLOAD_PATH . 'vision/' . $school['vision_image'])
          ? base_url('uploads/vision/' . $school['vision_image'])
          : 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=600&q=80');
  ?>
  <section id="sambutan" class="section-padding bg-white pt-5">
    <div class="container mt-4">
      <div class="principal-card-box">
        <div class="row align-items-center g-5">
          <div class="col-lg-4 text-center" data-aos="fade-right">
            <div class="principal-frame mx-auto" style="max-width: 310px;">
              <img src="<?= $sambutanPhoto ?>" 
                   class="w-100 object-fit-cover" style="height: 380px;" alt="Kepala Sekolah">
              <div class="principal-badge text-start">
                <div class="fw-bold text-white fs-6"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></div>
                <small class="text-warning d-block">Kepala <?= esc($school['name'] ?? 'Sekolah') ?></small>
                <?php if (!empty($school['principal_nip'])): ?>
                  <small class="text-white-50 d-block" style="font-size: 0.75rem;">NIP: <?= esc($school['principal_nip']) ?></small>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-lg-8" data-aos="fade-left">
            <span class="section-tag"><i class="bi bi-quote me-1"></i> <?= esc($sambutanSubtitle) ?></span>
            <h2 class="section-heading"><?= esc($sambutanTitle) ?></h2>
            <div class="text-muted fs-6 mb-4">
              <p class="lead text-dark fw-semibold mb-3">
                "<?= esc(trim($sambutanQuote, '"\'')) ?>"
              </p>
              <?php if (!empty($school['sambutan_isi'])): ?>
                <?php if (strip_tags($school['sambutan_isi']) === $school['sambutan_isi']): ?>
                  <?php 
                    $paragraphs = array_filter(explode("\n", str_replace("\r", "", $school['sambutan_isi'])));
                    foreach ($paragraphs as $p):
                      if (trim($p) === '') continue;
                  ?>
                    <p><?= esc(trim($p)) ?></p>
                  <?php endforeach; ?>
                <?php else: ?>
                  <?= $school['sambutan_isi'] ?>
                <?php endif; ?>
              <?php else: ?>
                <!-- Fallback Template Bawaan -->
                <p>
                  Selamat datang di portal resmi <strong><?= esc($school['name'] ?? 'sekolah kami') ?></strong>. 
                  Pendidikan adalah investasi masa depan peradaban. Di era transformasi digital saat ini, kami berkomitmen untuk menghadirkan ekosistem pembelajaran yang tidak hanya unggul secara akademis, namun juga kokoh dalam penanaman budi pekerti, akhlak mulia, dan keterampilan abad 21.
                </p>
                <p>
                  Melalui website dan sistem informasi terintegrasi ini, kami membuka pintu kolaborasi yang seluas-luasnya bagi seluruh peserta didik, tenaga pendidik, orang tua murid, serta masyarakat luas. Mari bersama bergandengan tangan mewujudkan generasi pembelajar yang berprestasi dan berkarakter.
                </p>
              <?php endif; ?>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-3 border-top flex-wrap gap-2">
              <div>
                <div class="fw-bold text-primary fs-6"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></div>
                <small class="text-muted">Kepala Sekolah <?= esc($school['name'] ?? '') ?></small>
              </div>
              <a href="#profil" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                Pelajari Visi & Misi <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 6. STATISTIK SEKOLAH (COUNTER BAR) -->
  <section class="stats-bar-section">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
          <div class="p-3">
            <i class="bi bi-people-fill text-warning display-6 mb-2 d-block"></i>
            <div class="stat-counter-number"><?= number_format($stats['students'] ?? 0) ?></div>
            <p class="stat-counter-label">Peserta Didik Aktif</p>
          </div>
        </div>

        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
          <div class="p-3">
            <i class="bi bi-mortarboard-fill text-info display-6 mb-2 d-block"></i>
            <div class="stat-counter-number"><?= number_format($stats['teachers'] ?? 0) ?></div>
            <p class="stat-counter-label">Guru & Tenaga Pendidik</p>
          </div>
        </div>

        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
          <div class="p-3">
            <i class="bi bi-door-open-fill text-success display-6 mb-2 d-block"></i>
            <div class="stat-counter-number"><?= number_format($stats['classes'] ?? 0) ?></div>
            <p class="stat-counter-label">Rombongan Belajar (Kelas)</p>
          </div>
        </div>

        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="400">
          <div class="p-3">
            <i class="bi bi-award-fill text-danger display-6 mb-2 d-block"></i>
            <div class="stat-counter-number"><?= number_format($stats['ekskuls'] ?? 0) ?></div>
            <p class="stat-counter-label">Ekstrakurikuler Aktif</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 7. VISI, MISI & NILAI UNGGULAN -->
  <section id="profil" class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-tag"><i class="bi bi-compass me-1"></i> Arah & Tujuan</span>
        <h2 class="section-heading">Visi, Misi & Nilai Karakter</h2>
        <p class="section-subheading mx-auto">Landasan fundamental dalam seluruh proses pembelajaran dan pendidikan budi pekerti di sekolah kami.</p>
      </div>

      <div class="row g-4 align-items-stretch mb-5">
        <!-- Visi Kami -->
        <div class="col-lg-5" data-aos="fade-right">
          <div class="vision-card d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                  <i class="bi bi-eye-fill fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-primary">Visi Sekolah</h4>
              </div>
              <blockquote class="fs-5 text-dark fw-bold mb-4" style="line-height: 1.6;">
                "<?= esc($school['vision'] ?? 'Menjadi sekolah berakhlak mulia, berprestasi unggul, dan berwawasan teknologi masa depan.') ?>"
              </blockquote>
            </div>
            <div class="border-top pt-3 border-primary border-opacity-25 text-primary fw-semibold small">
              <i class="bi bi-patch-check-fill me-1"></i> Pedoman Resmi Pendidikan <?= esc($school['name'] ?? '') ?>
            </div>
          </div>
        </div>

        <!-- Misi Kami -->
        <div class="col-lg-7" data-aos="fade-left">
          <div class="mission-card">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                <i class="bi bi-journal-check fs-5"></i>
              </div>
              <h4 class="fw-bold mb-0 text-success">Misi Sekolah</h4>
            </div>

            <ul class="list-unstyled mb-0">
              <?php
              $missions = explode("\n", $school['mission'] ?? "Memberikan layanan pendidikan terbaik dan bermutu.\nMembangun karakter siswa yang religius dan toleran.\nMenumbuhkan semangat inovasi dan literasi digital.");
              $mIdx = 1;
              foreach ($missions as $m):
                if (trim($m)):
              ?>
                <li class="mb-3 d-flex align-items-start">
                  <span class="badge bg-success bg-opacity-10 text-success rounded-circle me-3 mt-1 p-2 fw-bold" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                    <?= $mIdx++ ?>
                  </span>
                  <span class="text-dark fw-medium fs-6"><?= esc(trim($m)) ?></span>
                </li>
              <?php endif; endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- 3 Nilai Utama Karakter -->
      <div class="row g-4 mt-2">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
          <div class="card h-100 border-0 rounded-4 p-4 shadow-sm bg-white">
            <div class="text-primary fs-1 mb-3"><i class="bi bi-shield-check"></i></div>
            <h5 class="fw-bold mb-2">Religius & Berakhlak</h5>
            <p class="text-muted small mb-0">Menanamkan nilai-nilai keagamaan, kejujuran, dan sopan santun dalam kehidupan sehari-hari.</p>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
          <div class="card h-100 border-0 rounded-4 p-4 shadow-sm bg-white">
            <div class="text-warning fs-1 mb-3"><i class="bi bi-lightbulb"></i></div>
            <h5 class="fw-bold mb-2">Kritis & Berprestasi</h5>
            <p class="text-muted small mb-0">Mendorong rasa ingin tahu, kecerdasan berpikir kritis, dan daya juang meraih prestasi.</p>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
          <div class="card h-100 border-0 rounded-4 p-4 shadow-sm bg-white">
            <div class="text-success fs-1 mb-3"><i class="bi bi-cpu"></i></div>
            <h5 class="fw-bold mb-2">Adaptif & Melek Digital</h5>
            <p class="text-muted small mb-0">Mengintegrasikan literasi teknologi dan kecakapan hidup abad 21 yang bijak dan beretika.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 8. WARTA & INFORMASI TERKINI (BERITA, AGENDA, PENGUMUMAN) -->
  <section id="berita" class="section-padding bg-white">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-5 flex-wrap gap-3">
        <div>
          <span class="section-tag"><i class="bi bi-newspaper me-1"></i> Kabar Sekolah</span>
          <h2 class="section-heading mb-0">Warta & Pengumuman Sekolah</h2>
        </div>
        <a href="<?= base_url('berita') ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
          Semua Berita <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>

      <div class="row g-4">
        <!-- Kolom Berita Utama (8 Kolom) -->
        <div class="col-lg-8" data-aos="fade-up">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-journal-text me-2 text-primary"></i> Berita & Artikel Terbaru</h5>
          <div class="row g-4">
            <?php if (empty($articles)): ?>
              <div class="col-12">
                <div class="p-5 text-center text-muted bg-light rounded-4">
                  <i class="bi bi-newspaper display-5 text-secondary opacity-50 d-block mb-2"></i>
                  Belum ada berita terbaru yang diterbitkan.
                </div>
              </div>
            <?php else: foreach ($articles as $a): ?>
              <div class="col-md-12">
                <div class="school-card p-0">
                  <div class="row g-0 align-items-center">
                    <div class="col-md-5">
                      <img src="<?= $a['image'] ? base_url('uploads/articles/' . $a['image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=600&q=80' ?>"
                           class="w-100 object-fit-cover" style="height: 220px;" alt="<?= esc($a['title']) ?>">
                    </div>
                    <div class="col-md-7 p-4">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 fw-semibold">
                          <?= esc($a['category']) ?>
                        </span>
                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?= date('d M Y', strtotime($a['created_at'])) ?></small>
                      </div>
                      <h5 class="fw-bold text-dark mb-2 line-clamp-2">
                        <a href="<?= base_url('berita/' . $a['slug']) ?>" class="text-dark text-decoration-none hover-primary">
                          <?= esc($a['title']) ?>
                        </a>
                      </h5>
                      <p class="text-muted small mb-3 line-clamp-2">
                        <?= strip_tags(character_limiter($a['content'], 110)) ?>
                      </p>
                      <a href="<?= base_url('berita/' . $a['slug']) ?>" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none p-0">
                        Baca Selengkapnya <i class="bi bi-chevron-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Kolom Samping: Agenda & Pengumuman (4 Kolom) -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <!-- Agenda Terdekat -->
          <div id="agenda" class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white p-3.5 border-0 d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i> Agenda Kegiatan</h6>
              <span class="badge bg-white text-primary rounded-pill small">Terdekat</span>
            </div>
            <div class="card-body p-3">
              <?php if (empty($agendas)): ?>
                <div class="py-4 text-center text-muted small">
                  <i class="bi bi-calendar-x fs-3 d-block text-secondary opacity-50 mb-1"></i>
                  Belum ada agenda terdekat.
                </div>
              <?php else: foreach ($agendas as $ag): ?>
                <div class="agenda-item-box d-flex align-items-center gap-3 mb-2">
                  <div class="agenda-date-badge">
                    <span class="fs-5"><?= date('d', strtotime($ag['date'])) ?></span>
                    <span class="small fw-normal text-uppercase" style="font-size: 0.7rem;"><?= date('M', strtotime($ag['date'])) ?></span>
                  </div>
                  <div>
                    <h6 class="fw-bold mb-1 text-dark fs-7"><?= esc($ag['title']) ?></h6>
                    <small class="text-muted d-block"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= esc($ag['location'] ?? 'Di Lingkungan Sekolah') ?></small>
                  </div>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

          <!-- Pengumuman Resmi -->
          <div id="pengumuman" class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-warning text-dark p-3.5 border-0 d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2"></i> Pengumuman Resmi</h6>
              <span class="badge bg-dark text-white rounded-pill small">Papan Info</span>
            </div>
            <div class="card-body p-3">
              <?php if (empty($announcements)): ?>
                <div class="py-4 text-center text-muted small">
                  <i class="bi bi-bell-slash fs-3 d-block text-secondary opacity-50 mb-1"></i>
                  Tidak ada pengumuman saat ini.
                </div>
              <?php else: foreach ($announcements as $ann): ?>
                <div class="alert alert-warning border-0 bg-opacity-25 rounded-3 mb-2 p-3">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="fw-bold mb-0 text-dark"><?= esc($ann['title']) ?></h6>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= date('d/m/y', strtotime($ann['created_at'])) ?></small>
                  </div>
                  <p class="mb-0 small text-secondary">
                    <?= strip_tags(character_limiter($ann['content'], 85)) ?>
                  </p>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 9. EKSTRAKURIKULER & PENGEMBANGAN DIRI -->
  <?php 
    if (!function_exists('getEkskulBgImage')) {
      function getEkskulBgImage($ek) {
        if (!empty($ek['image'])) {
          return base_url('uploads/ekskul/' . esc($ek['image']));
        }
        $name = strtolower($ek['name'] ?? '');
        $desc = strtolower($ek['description'] ?? '');
        $text = $name . ' ' . $desc;

        if (strpos($text, 'pramuka') !== false || strpos($text, 'scout') !== false) {
          return 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=800&q=80'; // Pramuka & petualangan alam terbuka
        }
        if (strpos($text, 'arab') !== false) {
          return 'https://images.unsplash.com/photo-1585779034823-7e9ac8faec70?auto=format&fit=crop&w=800&q=80'; // Kaligrafi & sastra Arab
        }
        if (strpos($text, 'inggris') !== false || strpos($text, 'english') !== false) {
          return 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&w=800&q=80'; // Bahasa Inggris & literasi
        }
        if (strpos($text, 'bahasa') !== false || strpos($text, 'literasi') !== false) {
          return 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=800&q=80'; // Buku & kajian bahasa
        }
        if (strpos($text, 'futsal') !== false || strpos($text, 'sepak bola') !== false || strpos($text, 'bola') !== false) {
          return 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=800&q=80'; // Olahraga sepak bola / futsal
        }
        if (strpos($text, 'basket') !== false) {
          return 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=80'; // Lapangan basket
        }
        if (strpos($text, 'voli') !== false || strpos($text, 'volley') !== false) {
          return 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=800&q=80'; // Voli
        }
        if (strpos($text, 'badminton') !== false || strpos($text, 'bulu tangkis') !== false || strpos($text, 'tangkis') !== false) {
          return 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80'; // Bulutangkis
        }
        if (strpos($text, 'silat') !== false || strpos($text, 'karate') !== false || strpos($text, 'taekwondo') !== false || strpos($text, 'bela diri') !== false) {
          return 'https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=800&q=80'; // Seni bela diri
        }
        if (strpos($text, 'tari') !== false) {
          return 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=800&q=80'; // Seni tari
        }
        if (strpos($text, 'musik') !== false || strpos($text, 'band') !== false || strpos($text, 'paduan suara') !== false || strpos($text, 'vokal') !== false) {
          return 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80'; // Musik & vokal
        }
        if (strpos($text, 'paskibra') !== false || strpos($text, 'paskibraka') !== false || strpos($text, 'baris') !== false || strpos($text, 'bendera') !== false) {
          return base_url('img/paskibra_indonesia.jpg'); // Bendera Merah Putih Indonesia & Paskibra
        }
        if (strpos($text, 'pmr') !== false || strpos($text, 'uks') !== false || strpos($text, 'palang merah') !== false || strpos($text, 'kesehatan') !== false) {
          return 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80'; // PMR & kesehatan
        }
        if (strpos($text, 'robot') !== false || strpos($text, 'coding') !== false || strpos($text, 'komputer') !== false || strpos($text, 'it') !== false) {
          return 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80'; // Teknologi & robotik
        }
        if (strpos($text, 'sains') !== false || strpos($text, 'kir') !== false || strpos($text, 'olimpiade') !== false || strpos($text, 'ipa') !== false) {
          return 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=800&q=80'; // Laboratorium sains
        }
        if (strpos($text, 'rohis') !== false || strpos($text, 'tahfidz') !== false || strpos($text, 'quran') !== false || strpos($text, 'agama') !== false) {
          return 'https://images.unsplash.com/photo-1609599006353-e629aaabfeae?auto=format&fit=crop&w=800&q=80'; // Tilawah & kajian rohis
        }
        if (strpos($text, 'jurnalistik') !== false || strpos($text, 'fotografi') !== false) {
          return 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80'; // Jurnalistik & foto
        }
        if (strpos($text, 'lukis') !== false || strpos($text, 'rupa') !== false || strpos($text, 'seni') !== false) {
          return 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80'; // Seni lukis
        }
        // Default fallback siswa berprestasi & kreatif
        return 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80';
      }
    }

    if (!function_exists('getEkskulIcon')) {
      function getEkskulIcon($name) {
        $text = strtolower($name ?? '');
        if (strpos($text, 'pramuka') !== false || strpos($text, 'scout') !== false) return 'bi-compass';
        if (strpos($text, 'futsal') !== false || strpos($text, 'sepak bola') !== false || strpos($text, 'bola') !== false) return 'bi-dribbble';
        if (strpos($text, 'basket') !== false) return 'bi-circle';
        if (strpos($text, 'badminton') !== false || strpos($text, 'voli') !== false) return 'bi-activity';
        if (strpos($text, 'silat') !== false || strpos($text, 'karate') !== false || strpos($text, 'bela diri') !== false) return 'bi-shield-check';
        if (strpos($text, 'arab') !== false || strpos($text, 'inggris') !== false || strpos($text, 'bahasa') !== false) return 'bi-translate';
        if (strpos($text, 'tari') !== false || strpos($text, 'lukis') !== false || strpos($text, 'seni') !== false) return 'bi-palette';
        if (strpos($text, 'musik') !== false || strpos($text, 'band') !== false || strpos($text, 'suara') !== false) return 'bi-music-note-beamed';
        if (strpos($text, 'robot') !== false || strpos($text, 'coding') !== false || strpos($text, 'komputer') !== false) return 'bi-cpu';
        if (strpos($text, 'pmr') !== false || strpos($text, 'uks') !== false) return 'bi-heart-pulse';
        if (strpos($text, 'paskibra') !== false || strpos($text, 'paskibraka') !== false || strpos($text, 'bendera') !== false) return 'bi-flag';
        if (strpos($text, 'sains') !== false || strpos($text, 'kir') !== false) return 'bi-lightbulb';
        if (strpos($text, 'rohis') !== false || strpos($text, 'tahfidz') !== false) return 'bi-book';
        if (strpos($text, 'jurnalistik') !== false || strpos($text, 'fotografi') !== false) return 'bi-camera';
        return 'bi-stars';
      }
    }

    $displayEkskuls = !empty($ekskuls) ? $ekskuls : [];
  ?>
  <?php if (!empty($displayEkskuls)): ?>
    <section id="ekskul" class="section-padding bg-light">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag"><i class="bi bi-trophy me-1"></i> Minat & Bakat</span>
          <h2 class="section-heading">Ekstrakurikuler Siswa</h2>
          <p class="section-subheading mx-auto">Wadah bagi peserta didik untuk mengeksplorasi minat, bakat, kepemimpinan, dan kreativitas di luar jam pelajaran formal.</p>
        </div>

        <div class="row g-4 justify-content-center">
          <?php foreach ($displayEkskuls as $ek): 
            $bgPhoto = getEkskulBgImage($ek);
            $icon = getEkskulIcon($ek['name']);
            $isWajib = strtolower($ek['category'] ?? '') === 'wajib';
          ?>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in">
              <div class="ekskul-card-dynamic h-100">
                <!-- Foto Background Menyesuaikan Lengkungan Card -->
                <div class="ekskul-bg-img" style="background-image: url('<?= esc($bgPhoto) ?>');"></div>
                <!-- Lapisan Gradasi ke Kanan Semakin Terang & Jelas -->
                <div class="ekskul-gradient-overlay"></div>
                
                <!-- Konten Card dengan Teks Kontras dan Terjaga Kejelasannya -->
                <div class="ekskul-card-content">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge <?= $isWajib ? 'bg-primary' : 'bg-warning text-dark' ?> rounded-pill px-3 py-1.5 fw-bold text-uppercase small shadow-sm">
                      <?= esc($ek['category'] ?? 'Pilihan') ?>
                    </span>
                    <div class="ekskul-icon-bubble">
                      <i class="bi <?= $icon ?>"></i>
                    </div>
                  </div>
                  
                  <div class="ekskul-text-body mt-auto">
                    <h5 class="fw-bold text-dark mb-2" style="letter-spacing: -0.3px;"><?= esc($ek['name']) ?></h5>
                    <p class="text-secondary small mb-0 line-clamp-3" style="line-height: 1.55;">
                      <?= esc($ek['description'] ?: 'Program pelatihan bakat dan pengembangan karakter siswa di bawah bimbingan pembina ahli.') ?>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- 10. DOKUMENTASI KEGIATAN (ALBUM MULTI-FOTO DENGAN CAPTION) -->
  <section id="kegiatan" class="section-padding bg-white">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-5 flex-wrap gap-3" data-aos="fade-up">
        <div>
          <span class="section-tag"><i class="bi bi-camera me-1"></i> Galeri Album</span>
          <h2 class="section-heading mb-0">Dokumentasi Kegiatan</h2>
          <p class="section-subheading mb-0 mt-1">Momen berharga dalam berbagai aktivitas pembelajaran, peringatan hari besar, dan pentas kreasi sekolah.</p>
        </div>
      </div>

      <div class="swiper activity-swiper" data-aos="zoom-in-up">
        <div class="swiper-wrapper py-2">
          <?php if (empty($activities)): ?>
            <div class="swiper-slide">
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden m-2">
                <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800"
                  class="card-img-top" style="height: 280px; object-fit: cover;">
                <div class="card-body p-4 text-center">
                  <h6 class="fw-bold mb-1">Pentas Kreasi Seni Siswa</h6>
                  <small class="text-muted">Januari 2026</small>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($activities as $act):
              $photos       = $act['photos'] ?? [];
              $totalPhotos  = count($photos);
              $cover        = $act['cover_image'] ?? $act['image'];
              $coverCaption = !empty($photos[0]['caption']) ? $photos[0]['caption'] : $act['description'];
              $galleryName  = 'album-act-' . $act['id'];
            ?>
              <div class="swiper-slide">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden m-2 h-100 transition-hover activity-album-card d-flex flex-column">
                  
                  <!-- Cover Image Link (Trigger GLightbox) -->
                  <div class="position-relative overflow-hidden group-hover-zoom" style="height: 250px;">
                    <a href="<?= base_url('uploads/activities/' . esc($cover)) ?>" 
                       class="glightbox d-block w-100 h-100 position-relative text-decoration-none"
                       data-gallery="<?= $galleryName ?>"
                       data-title="<?= esc($act['title']) ?>"
                       data-description="<?= esc($coverCaption) ?>">
                      
                      <img src="<?= base_url('uploads/activities/' . esc($cover)) ?>"
                        class="w-100 h-100 object-fit-cover"
                        loading="lazy"
                        alt="<?= esc($act['title']) ?>"
                        onerror="this.closest('.swiper-slide').style.display='none'">

                      <!-- Album Badge & Overlay -->
                      <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between align-items-center"
                           style="background: linear-gradient(to top, rgba(10,29,55,0.88) 0%, rgba(10,29,55,0) 100%);">
                        <span class="badge bg-dark bg-opacity-75 text-white rounded-pill px-2.5 py-1.5 shadow-sm small border border-light border-opacity-25 backdrop-blur">
                          <i class="bi bi-images me-1 text-warning"></i> <?= $totalPhotos ?> Foto
                        </span>
                        <span class="badge bg-primary rounded-pill px-2.5 py-1.5 shadow-sm small">
                          <i class="bi bi-arrows-fullscreen me-1"></i> Buka Album
                        </span>
                      </div>
                    </a>

                    <!-- Hidden Links untuk seluruh foto sisa dalam album kegiatan ini -->
                    <?php if ($totalPhotos > 1): ?>
                      <?php foreach (array_slice($photos, 1) as $p): ?>
                        <a href="<?= base_url('uploads/activities/' . esc($p['image'])) ?>" 
                           class="glightbox d-none"
                           data-gallery="<?= $galleryName ?>"
                           data-title="<?= esc($act['title']) ?>"
                           data-description="<?= esc(!empty($p['caption']) ? $p['caption'] : $act['description']) ?>">
                        </a>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>

                  <!-- Keterangan Album -->
                  <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                    <div>
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-primary fw-bold">
                          <i class="bi bi-calendar-check me-1"></i> <?= date('d M Y', strtotime($act['date'])) ?>
                        </small>
                        <span class="badge bg-light text-secondary border rounded-pill small px-2">
                          <i class="bi bi-camera me-1"></i> Album
                        </span>
                      </div>
                      <h6 class="fw-bold mb-2 text-dark line-clamp-2" style="min-height: 2.4rem;">
                        <?= esc($act['title']) ?>
                      </h6>
                      <p class="text-muted small mb-3 line-clamp-2">
                        <?= esc($act['description']) ?>
                      </p>
                    </div>

                    <div class="pt-2 border-top mt-auto d-flex justify-content-between align-items-center">
                      <span class="text-muted small">
                        <i class="bi bi-collection me-1"></i> <?= $totalPhotos ?> Dokumentasi
                      </span>
                      <button type="button" 
                              class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold"
                              onclick="this.closest('.activity-album-card').querySelector('a.glightbox').click()">
                        Lihat Album <i class="bi bi-chevron-right ms-1"></i>
                      </button>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="swiper-pagination mt-4"></div>
      </div>
    </div>
  </section>

  <!-- 11. TENAGA PENDIDIK & KEPENDIDIKAN (GTK) -->
  <section id="guru" class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-tag"><i class="bi bi-people me-1"></i> Dewan Guru & Staf</span>
        <h2 class="section-heading">Tenaga Pendidik & Kependidikan</h2>
        <p class="section-subheading mx-auto">Guru-guru profesional yang berdedikasi tinggi, penuh kasih, dan terus berinovasi dalam membimbing para peserta didik.</p>
      </div>

      <div class="swiper teacher-swiper" data-aos="zoom-in">
        <div class="swiper-wrapper py-3">
          <?php if (empty($teachers)): ?>
            <div class="swiper-slide">
              <div class="teacher-item-card mx-auto text-center" style="max-width: 280px;">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600" class="teacher-item-photo" alt="Nama Pendidik" loading="lazy">
                <div class="p-3">
                  <h6 class="fw-bold mb-1">Nama Pendidik</h6>
                  <small class="text-primary fw-bold">Wali Kelas</small>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($teachers as $t): ?>
              <div class="swiper-slide">
                <div class="teacher-item-card text-center">
                  <?php if (!empty($t['photo'])): ?>
                    <img src="<?= base_url('uploads/teachers/' . $t['photo']) ?>" class="teacher-item-photo" alt="<?= esc($t['name']) ?>" loading="lazy">
                  <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($t['name']) ?>&background=0d6efd&color=fff&size=400" class="teacher-item-photo" alt="<?= esc($t['name']) ?>" loading="lazy">
                  <?php endif; ?>

                  <div class="p-3">
                    <h6 class="fw-bold mb-1 text-dark text-truncate" title="<?= esc($t['name']) ?>">
                      <?= esc($t['name']) ?>
                    </h6>
                    <small class="text-primary fw-semibold d-block text-truncate">
                      <?php
                        $roleId      = $t['role_id'] ?? null;
                        $rolesList   = $t['roles_list'] ?? '';
                        $subjectList = $t['subjects_list'] ?? '';
                        $waliClass   = $t['wali_class_name'] ?? '';
                        $schoolLvl   = $school['level'] ?? 1;
                        $isWali      = strpos($rolesList, 'guru_kelas') !== false;
                        $isMapel     = strpos($rolesList, 'guru_mapel') !== false;

                        if ($roleId == 7) {
                            echo 'Tenaga Kependidikan (Staf)';
                        } elseif ($roleId == 6) {
                            echo 'Kontributor Website';
                        } elseif ($roleId == 2) {
                            echo 'Kepala Sekolah';
                        } elseif ($schoolLvl == 1) {
                            if ($isWali) {
                                echo 'Guru Kelas' . ($waliClass ? ' ' . esc($waliClass) : '');
                            } elseif ($isMapel && !empty($subjectList)) {
                                echo 'Guru ' . esc($subjectList);
                            } else {
                                echo 'Guru';
                            }
                        } else {
                            if ($isWali && $isMapel && !empty($subjectList)) {
                                echo 'Wali ' . esc($waliClass) . ', Guru ' . esc($subjectList);
                            } elseif ($isWali) {
                                echo 'Wali Kelas' . ($waliClass ? ' ' . esc($waliClass) : '');
                            } elseif ($isMapel && !empty($subjectList)) {
                                echo 'Guru ' . esc($subjectList);
                            } else {
                                echo 'Guru';
                            }
                        }
                      ?>
                    </small>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="swiper-pagination mt-4"></div>
      </div>
    </div>
  </section>

  <!-- 12. SARANA & PRASARANA SEKOLAH -->
  <section id="fasilitas" class="section-padding bg-white">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-tag"><i class="bi bi-building me-1"></i> Fasilitas Belajar</span>
        <h2 class="section-heading">Sarana & Prasarana</h2>
        <p class="section-subheading mx-auto">Lingkungan belajar yang aman, bersih, representatif, dan lengkap untuk mendukung segenap proses belajar mengajar.</p>
      </div>

      <div class="swiper facility-swiper" data-aos="fade-up">
        <div class="swiper-wrapper py-2">
          <?php if (empty($facilities)): ?>
            <div class="swiper-slide">
              <div class="school-card">
                <img src="https://images.unsplash.com/photo-1497633272928-50506af0f41c?auto=format&fit=crop&w=600" class="w-100 object-fit-cover" style="height: 200px;">
                <div class="p-4 text-center">
                  <h5 class="fw-bold mb-1">Ruang Kelas Nyaman & Digital</h5>
                  <p class="text-muted small mb-0">Dilengkapi multimedia untuk pembelajaran interaktif.</p>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($facilities as $f): ?>
              <div class="swiper-slide">
                <div class="school-card">
                  <img src="<?= $f['image'] ? base_url('uploads/facilities/' . $f['image']) : 'https://images.unsplash.com/photo-1497633272928-50506af0f41c?auto=format&fit=crop&w=600' ?>"
                       class="w-100 object-fit-cover" style="height: 210px;" alt="<?= esc($f['name']) ?>">
                  <div class="p-4 text-center">
                    <h5 class="fw-bold mb-2 text-dark"><?= esc($f['name']) ?></h5>
                    <p class="text-muted small mb-0"><?= esc($f['description']) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="swiper-pagination mt-4"></div>
      </div>
    </div>
  </section>

  <!-- 13. TAUTAN PINTAR (Jika ada) -->
  <?php if (!empty($links)): ?>
    <section id="links" class="section-padding bg-light">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag"><i class="bi bi-link-45deg me-1"></i> Integrasi</span>
          <h2 class="section-heading">Tautan Portal & Aplikasi Pintar</h2>
          <p class="section-subheading mx-auto">Akses cepat ke portal kementerian dan aplikasi eksternal pendukung.</p>
        </div>
        <div class="row g-4 justify-content-center">
          <?php foreach ($links as $link): ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in">
              <a href="<?= esc($link['url']) ?>" target="_blank" class="text-decoration-none h-100 d-block">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center transition-hover bg-white">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                    <i class="bi <?= esc($link['icon'] ?: 'bi-link-45deg') ?> fs-2"></i>
                  </div>
                  <h6 class="fw-bold text-dark mb-1"><?= esc($link['title']) ?></h6>
                  <?php if ($link['description']): ?>
                    <p class="small text-muted mb-0"><?= esc($link['description']) ?></p>
                  <?php endif; ?>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- 14. LAYANAN PUBLIK & KONTAK INTERAKTIF -->
  <section id="kontak" class="section-padding bg-white">
    <div class="container">
      <div class="row g-5 align-items-center">
        <!-- Kartu Layanan Buku Tamu & Verifikasi -->
        <div class="col-lg-6" data-aos="fade-right">
          <span class="section-tag"><i class="bi bi-shield-check me-1"></i> Pelayanan Publik</span>
          <h2 class="section-heading">Layanan Tamu & Informasi</h2>
          <p class="text-muted mb-4">
            Bagi tamu dinas, orang tua murid, instansi rekanan, maupun masyarakat yang berkunjung ke sekolah, silakan melakukan pencatatan kunjungan melalui <strong>Buku Tamu Digital</strong>.
          </p>

          <div class="card border-0 rounded-4 p-4 mb-3 shadow-sm bg-light">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-warning text-dark rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-journal-text fs-4"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-dark">Buku Tamu Online Sekolah</h6>
                <p class="text-muted small mb-0">Isi data kunjungan dinas atau umum secara mandiri dan cepat.</p>
              </div>
              <a href="<?= base_url('buku-tamu') ?>" class="btn btn-warning rounded-pill px-3 py-1.5 fw-bold text-dark small">
                Isi Sekarang
              </a>
            </div>
          </div>

          <div class="card border-0 rounded-4 p-4 shadow-sm bg-light">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-qr-code-scan fs-4"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-dark">Verifikasi Keaslian Dokumen</h6>
                <p class="text-muted small mb-0">Pindai kode QR pada Surat / Rapor resmi sekolah.</p>
              </div>
              <a href="<?= base_url('verify/cek') ?>" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold small">
                Cek QR
              </a>
            </div>
          </div>
        </div>

        <!-- Kartu Kontak & Alamat Sekolah -->
        <div class="col-lg-6" data-aos="fade-left">
          <div class="card border-0 rounded-4 p-4 shadow-lg text-white" style="background: linear-gradient(135deg, var(--primary-dark) 0%, #173868 100%);">
            <h4 class="fw-bold mb-4 text-warning"><i class="bi bi-geo-alt-fill me-2"></i> Kantor Sekolah</h4>

            <div class="mb-3 d-flex align-items-start">
              <div class="footer-contact-icon"><i class="bi bi-pin-map-fill"></i></div>
              <div>
                <div class="fw-bold text-white">Alamat Lengkap</div>
                <div class="text-light opacity-75 small">
                  <?= esc($school['address'] ?? 'Alamat sekolah belum dikonfigurasi.') ?><br>
                  <?= esc($school['city_regency'] ?? '') ?>
                </div>
              </div>
            </div>

            <div class="mb-3 d-flex align-items-start">
              <div class="footer-contact-icon"><i class="bi bi-telephone-fill"></i></div>
              <div>
                <div class="fw-bold text-white">Telepon / WhatsApp</div>
                <div class="text-light opacity-75 small"><?= esc($school['phone'] ?? '-') ?></div>
              </div>
            </div>

            <div class="mb-4 d-flex align-items-start">
              <div class="footer-contact-icon"><i class="bi bi-envelope-at-fill"></i></div>
              <div>
                <div class="fw-bold text-white">Surat Elektronik (Email)</div>
                <div class="text-light opacity-75 small"><?= esc($school['email'] ?? '-') ?></div>
              </div>
            </div>

            <div class="pt-3 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <span class="small text-light opacity-75">
                <i class="bi bi-clock me-1"></i> Jam Pelayanan: <?= esc($operationalHours['service_text'] ?? '07.00 - 15.00 WIB') ?>
              </span>
              <?php if (!empty($school['latitude']) && !empty($school['longitude'])): ?>
                <a href="https://maps.google.com/?q=<?= $school['latitude'] ?>,<?= $school['longitude'] ?>" target="_blank" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                  <i class="bi bi-map me-1"></i> Buka Google Maps
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 15. FOOTER RESMI SEKOLAH -->
  <footer class="school-footer">
    <div class="container">
      <div class="row g-5">
        <!-- Profil Singkat Footer -->
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <?php if (!empty($school['logo'])): ?>
              <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo" height="44" class="rounded">
            <?php else: ?>
              <img src="<?= base_url('uploads/logo/espata.png') ?>" alt="ESPATA" height="40" style="filter: brightness(0) invert(1);">
            <?php endif; ?>
            <div>
              <h5 class="fw-bold text-white mb-0"><?= esc($school['name'] ?? 'ESPATA') ?></h5>
              <small class="text-warning text-uppercase fw-semibold" style="font-size: 0.75rem;">Portal Resmi Sekolah</small>
            </div>
          </div>

          <p class="text-light opacity-75 small mb-4" style="line-height: 1.7;">
            <?= esc(character_limiter(strip_tags($school['vision'] ?? 'Lembaga pendidikan yang berkomitmen mendidik tunas bangsa berakhlak dan berprestasi unggul.'), 140)) ?>
          </p>

          <div class="d-flex gap-2">
            <?php if (!empty($school['youtube'])): ?>
              <a href="<?= esc($school['youtube']) ?>" target="_blank" class="social-link" title="YouTube"><i class="bi bi-youtube"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['instagram'])): ?>
              <a href="<?= esc($school['instagram']) ?>" target="_blank" class="social-link" title="Instagram"><i class="bi bi-instagram"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['facebook'])): ?>
              <a href="<?= esc($school['facebook']) ?>" target="_blank" class="social-link" title="Facebook"><i class="bi bi-facebook"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['tiktok'])): ?>
              <a href="<?= esc($school['tiktok']) ?>" target="_blank" class="social-link" title="TikTok"><i class="bi bi-tiktok"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['twitter'])): ?>
              <a href="<?= esc($school['twitter']) ?>" target="_blank" class="social-link" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Navigasi Cepat -->
        <div class="col-6 col-lg-2">
          <h6 class="footer-title">Navigasi</h6>
          <ul class="list-unstyled small">
            <li class="mb-2"><a href="#home">Beranda</a></li>
            <li class="mb-2"><a href="#sambutan">Sambutan Kepsek</a></li>
            <li class="mb-2"><a href="#profil">Visi & Misi</a></li>
            <li class="mb-2"><a href="#guru">Dewan Guru</a></li>
            <li class="mb-2"><a href="#fasilitas">Fasilitas</a></li>
          </ul>
        </div>

        <!-- Informasi & Kesiswaan -->
        <div class="col-6 col-lg-3">
          <h6 class="footer-title">Kesiswaan & Info</h6>
          <ul class="list-unstyled small">
            <li class="mb-2"><a href="#ekskul">Ekstrakurikuler</a></li>
            <li class="mb-2"><a href="#kegiatan">Dokumentasi Album</a></li>
            <li class="mb-2"><a href="#berita">Berita & Warta</a></li>
            <li class="mb-2"><a href="#agenda">Agenda Kegiatan</a></li>
            <li class="mb-2"><a href="#pengumuman">Pengumuman</a></li>
          </ul>
        </div>

        <!-- Portal Pelayanan -->
        <div class="col-lg-3">
          <h6 class="footer-title">Layanan Sistem</h6>
          <ul class="list-unstyled small">
            <li class="mb-2">
              <a href="<?= base_url('login') ?>" class="text-warning fw-bold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ESPATA
              </a>
            </li>
            <li class="mb-2"><a href="<?= base_url('buku-tamu') ?>"><i class="bi bi-journal-text me-1"></i> Buku Tamu Digital</a></li>
            <li class="mb-2"><a href="<?= base_url('verify/cek') ?>"><i class="bi bi-qr-code me-1"></i> Verifikasi Ijazah/QR</a></li>
            <li class="mb-2"><a href="<?= base_url('tentang') ?>"><i class="bi bi-info-circle me-1"></i> Tentang Aplikasi</a></li>
          </ul>

          <div class="p-3 rounded-3 mt-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
            <small class="d-block text-white fw-bold mb-1"><i class="bi bi-clock-history me-1 text-warning"></i> Jam Operasional:</small>
            <small class="d-block text-light opacity-75"><?= esc($operationalHours['schedule_text'] ?? 'Senin - Jumat: 07.00 - 15.00 WIB') ?></small>
            <small class="d-block text-light opacity-75"><?= esc($operationalHours['off_days'] ?? 'Sabtu - Minggu: Libur') ?></small>
          </div>
        </div>
      </div>

      <hr class="mt-5 border-secondary border-opacity-25">

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small text-light opacity-75 pt-2">
        <div>
          &copy; <?= date('Y') ?> <strong><?= esc($school['name'] ?? 'ESPATA') ?></strong>. All rights reserved.
        </div>
        <div>
          School Management Platform by <a href="https://sakasalika.com" target="_blank" class="text-warning text-decoration-none fw-bold">SakaSalika</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

  <script>
    // Initialize AOS Animation
    AOS.init({
      duration: 800,
      once: true,
      offset: 60
    });

    // Hero Swiper
    new Swiper('.hero-swiper', {
      slidesPerView: 1,
      loop: <?= count($sliders) > 1 ? 'true' : 'false' ?>,
      autoplay: {
        delay: 6000,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      effect: 'fade',
      fadeEffect: {
        crossFade: true
      }
    });

    // Teacher Swiper
    new Swiper('.teacher-swiper', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: false,
      autoplay: {
        delay: 4500,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      breakpoints: {
        576: { slidesPerView: 2 },
        768: { slidesPerView: 3 },
        1200: { slidesPerView: 4 }
      }
    });

    // Activity Album Swiper
    new Swiper('.activity-swiper', {
      slidesPerView: 1,
      spaceBetween: 24,
      loop: false,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      breakpoints: {
        576: { slidesPerView: 2 },
        768: { slidesPerView: 3 },
        1200: { slidesPerView: 3 }
      }
    });

    // Facility Swiper
    new Swiper('.facility-swiper', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: false,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      breakpoints: {
        576: { slidesPerView: 2 },
        768: { slidesPerView: 3 },
        1200: { slidesPerView: 4 }
      }
    });

    // Lightbox Initialization
    const lightbox = GLightbox({
      selector: '.glightbox',
      touchNavigation: true,
      loop: true,
      zoomable: true
    });

    // Smooth Scroll for Internal Anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId && targetId !== '#') {
          const targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        }
      });
    });
  </script>
</body>

</html>