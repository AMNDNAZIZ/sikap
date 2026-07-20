<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $school['name'] ?? 'SIKAP' ?> - <?= $title ?? 'Beranda' ?></title>

  <!-- SEO Meta Tags -->
  <meta name="description"
    content="<?= esc(strip_tags($school['vision'] ?? 'Sistem Informasi Kelas Akademik dan Penilaian (SIKAP)')) ?>">
  <meta name="keywords"
    content="siakad, sekolah, pendidikan, portal sekolah, sistem informasi akademik, <?= esc($school['name'] ?? '') ?>">
  <meta name="author" content="<?= esc($school['name'] ?? 'SakaSalika') ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= current_url() ?>">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= current_url() ?>">
  <meta property="og:title" content="<?= $school['name'] ?? 'SIKAP' ?> - <?= $title ?? 'Beranda' ?>">
  <meta property="og:description"
    content="<?= esc(strip_tags($school['vision'] ?? 'Sistem Informasi Kelas Akademik dan Penilaian (SIKAP)')) ?>">
  <meta property="og:image" content="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="<?= current_url() ?>">
  <meta property="twitter:title" content="<?= $school['name'] ?? 'SIKAP' ?> - <?= $title ?? 'Beranda' ?>">
  <meta property="twitter:description"
    content="<?= esc(strip_tags($school['vision'] ?? 'Sistem Informasi Kelas Akademik dan Penilaian (SIKAP)')) ?>">
  <meta property="twitter:image" content="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">

  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

  <style>
    :root {
      --primary-color: #0d6efd;
      --secondary-color: #6c757d;
      --dark-color: #1e293b;
      --light-color: #f8f9fa;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark-color);
      overflow-x: hidden;
      background-color: #fff;
    }

    /* Navbar Custom */
    .navbar {
      transition: all 0.3s ease;
      padding: 1rem 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }

    .navbar.scrolled {
      padding: 0.5rem 0;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-weight: 800;
      font-size: 1.5rem;
      color: var(--primary-color) !important;
    }

    .nav-link {
      font-weight: 600;
      padding: 0.5rem 1rem !important;
      color: var(--dark-color) !important;
      transition: color 0.3s;
    }

    .nav-link:hover {
      color: var(--primary-color) !important;
    }

    /* Hero Slider */
    .hero-section {
      height: 90vh;
      position: relative;
      overflow: hidden;
    }

    .hero-swiper {
      width: 100%;
      height: 100%;
    }

    .swiper-slide {
      position: relative;
      background-size: cover;
      background-position: center;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to top right, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.1));
      display: flex;
      align-items: flex-end;
      padding-bottom: 80px;
    }

    .hero-content {
      color: white;
      max-width: 800px;
    }

    .hero-content h1 {
      font-size: 4rem;
      font-weight: 800;
      margin-bottom: 1.5rem;
      line-height: 1.1;
    }

    @media (max-width: 768px) {
      .hero-content h1 {
        font-size: 2.5rem;
      }
    }

    /* Sections */
    .section-padding {
      padding: 100px 0;
    }

    .section-title {
      margin-bottom: 60px;
    }

    .section-title h2 {
      font-weight: 800;
      font-size: 2.5rem;
      color: var(--dark-color);
      position: relative;
      display: inline-block;
    }

    .section-title h2::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -10px;
      width: 60px;
      height: 5px;
      background: var(--primary-color);
      border-radius: 5px;
    }

    /* Sliders / Carousels */
    .teacher-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      margin: 15px;
      height: 100%;
    }

    .teacher-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .teacher-img {
      width: 100%;
      height: 350px;
      object-fit: cover;
    }

    /* Footer */
    .footer {
      background: #0f172a;
      /* Deeper dark for premium feel */
      color: rgba(255, 255, 255, 0.9);
      padding: 80px 0 30px;
    }

    .footer-logo {
      font-weight: 800;
      font-size: 1.8rem;
      margin-bottom: 1.5rem;
      display: block;
      color: white;
      text-decoration: none;
    }

    .footer h5 {
      color: white;
      font-weight: 700;
    }

    .footer-text-muted {
      color: rgba(255, 255, 255, 0.65) !important;
    }

    .footer-links a {
      color: rgba(255, 255, 255, 0.65);
      text-decoration: none;
      transition: all 0.3s;
    }

    .footer-links a:hover {
      color: var(--primary-color);
      padding-left: 5px;
    }

    /* Swiper Fix */
    .swiper-button-next,
    .swiper-button-prev {
      color: white;
      background: rgba(255, 255, 255, 0.2);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      backdrop-filter: blur(5px);
    }

    .swiper-button-next::after,
    .swiper-button-prev::after {
      font-size: 20px;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <?php if (!empty($school['logo'])): ?>
          <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" height="40" class="me-2" alt="Logo">
        <?php else: ?>
          <i class="bi bi-book-half me-2"></i>
        <?php endif; ?>
        <?= $school['name'] ?? 'SIKAP' ?>
      </a>
      <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
          <li class="nav-item"><a class="nav-link" href="#profil">Visi Misi</a></li>
          <li class="nav-item"><a class="nav-link" href="#berita">Berita</a></li>
          <li class="nav-item"><a class="nav-link" href="#fasilitas">Fasilitas</a></li>
          <li class="nav-item"><a class="nav-link" href="#kegiatan">Kegiatan</a></li>
          <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
            <a href="<?= base_url('login') ?>" class="btn btn-primary px-4 rounded-pill fw-bold">Login</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Slider -->
  <section id="home" class="hero-section">
    <div class="swiper hero-swiper">
      <div class="swiper-wrapper">
        <?php if (empty($sliders)): ?>
          <div class="swiper-slide"
            style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80');">
            <div class="hero-overlay">
              <div class="container">
                <div class="hero-content" data-aos="fade-right">
                  <h1 class="display-3 fw-bold">Selamat Datang di
                    <?= esc($school['name'] ?? 'SIKAP') ?>
                  </h1>
                  <p class="lead mb-4">Membangun generasi cerdas, berkarakter, dan siap menghadapi tantangan masa depan
                    dengan teknologi dan inovasi.</p>
                  <a href="#profil" class="btn btn-primary btn-lg px-5 rounded-pill shadow">Jelajahi Sekarang</a>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($sliders as $s): ?>
            <div class="swiper-slide" style="background-image: url('<?= base_url('uploads/sliders/' . $s['image']) ?>');">
              <div class="hero-overlay">
                <div class="container">
                  <div class="hero-content">
                    <h1 class="display-3 fw-bold" data-aos="fade-up">
                      <?= esc($s['title']) ?>
                    </h1>
                    <p class="lead mb-4" data-aos="fade-up" data-aos-delay="200">
                      <?= esc($s['description']) ?>
                    </p>
                    <?php if ($s['link']): ?>
                      <a href="<?= esc($s['link']) ?>" class="btn btn-primary btn-lg px-5 rounded-pill shadow"
                        data-aos="fade-up" data-aos-delay="400">Selengkapnya</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>
  </section>

  <!-- Visi Misi Section -->
  <section id="profil" class="section-padding bg-light">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
          <img
            src="<?= !empty($school['vision_image']) ? base_url('uploads/vision/' . $school['vision_image']) : (!empty($school['logo']) ? base_url('uploads/logo/' . $school['logo']) : 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&w=800&q=80') ?>"
            class="img-fluid rounded-4 shadow-lg" alt="Profile">
        </div>
        <div class="col-lg-6 ps-lg-5" data-aos="fade-left">
          <div class="section-title mb-4">
            <h2>Visi & Misi</h2>
          </div>
          <div class="mb-4">
            <h4 class="fw-bold text-primary mb-3"><i class="bi bi-bullseye me-2"></i>Visi Kami</h4>
            <p class="lead italic">"
              <?= esc($school['vision'] ?? 'Menjadi sekolah unggul dalam prestasi dan berkarakter islami/nasional.') ?>"
            </p>
          </div>
          <div>
            <h4 class="fw-bold text-primary mb-3"><i class="bi bi-journal-check me-2"></i>Misi Kami</h4>
            <ul class="list-unstyled">
              <?php
              $missions = explode("\n", $school['mission'] ?? "Memberikan layanan pendidikan terbaik.\nMembangun karakter siswa.\nInovasi dalam pembelajaran.");
              foreach ($missions as $m):
                if (trim($m)):
                  ?>
                  <li class="mb-2 d-flex align-items-start">
                    <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                    <span>
                      <?= esc($m) ?>
                    </span>
                  </li>
                <?php endif; endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Links Section -->
  <?php if (!empty($links)): ?>
    <section id="links" class="section-padding" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
      <div class="container">
        <div class="section-title text-center mb-5" data-aos="fade-up">
          <h2>Tautan Pintar</h2>
          <p class="text-muted">Akses cepat ke berbagai portal dan informasi eksternal pendukung.</p>
        </div>
        <div class="row g-4 justify-content-center">
          <?php foreach ($links as $link): ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
              <a href="<?= esc($link['url']) ?>" target="_blank" class="text-decoration-none h-100 d-block">
                <div class="card h-100 border-0 shadow-sm rounded-4 transition-hover text-center p-4">
                  <div class="icon-box-link mb-3 mx-auto">
                    <i class="bi <?= esc($link['icon'] ?: 'bi-link-45deg') ?> fs-2 text-primary"></i>
                  </div>
                  <h6 class="fw-bold mb-1 text-dark"><?= esc($link['title']) ?></h6>
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

    <style>
      .icon-box-link {
        width: 60px;
        height: 60px;
        background: rgba(13, 110, 253, 0.1);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }

      .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
      }

      .transition-hover:hover .icon-box-link {
        background: var(--primary-color);
        color: white !important;
      }

      .transition-hover:hover .icon-box-link i {
        color: white !important;
      }
    </style>
  <?php endif; ?>

  <!-- News & Info Grid -->
  <section id="berita" class="section-padding">
    <div class="container">
      <div class="row">
        <!-- Latest News -->
        <div class="col-lg-8" data-aos="fade-up">
          <div class="section-title d-flex justify-content-between align-items-end">
            <h2>Berita & Artikel</h2>
            <a href="<?= base_url('berita') ?>"
              class="btn btn-link text-primary fw-bold text-decoration-none p-0 mb-3">Semua Berita <i
                class="bi bi-arrow-right"></i></a>
          </div>
          <div class="row g-4">
            <?php if (empty($articles)): ?>
              <div class="col-12">
                <p class="text-muted">Belum ada berita terbaru.</p>
              </div>
            <?php else:
              foreach ($articles as $a): ?>
                <div class="col-md-12 mb-3">
                  <div class="card border-0 shadow-sm overflow-hidden h-100 flex-md-row">
                    <img
                      src="<?= $a['image'] ? base_url('uploads/articles/' . $a['image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=500&q=60' ?>"
                      class="col-md-4 object-fit-cover" style="height: 200px;" alt="<?= esc($a['title']) ?>">
                    <div class="card-body p-4">
                      <div class="d-flex gap-2 mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">
                          <?= esc($a['category']) ?>
                        </span>
                        <small class="text-muted">
                          <?= date('d M Y', strtotime($a['created_at'])) ?>
                        </small>
                      </div>
                      <h4 class="card-title fw-bold mb-3">
                        <?= esc($a['title']) ?>
                      </h4>
                      <p class="card-text text-muted small">
                        <?= strip_tags(character_limiter($a['content'], 120)) ?>
                      </p>
                      <a href="<?= base_url('berita/' . $a['slug']) ?>"
                        class="btn btn-sm btn-outline-primary rounded-pill mt-2">Baca Selengkapnya</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Agendas & Announcements -->
        <div class="col-lg-4 mt-5 mt-lg-0" data-aos="fade-up" data-aos-delay="200">
          <div class="section-title mb-4">
            <h2>Agenda & Info</h2>
          </div>

          <!-- Agendas -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white p-3 border-0">
              <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-event me-2"></i>Agenda Terdekat</h5>
            </div>
            <div class="card-body p-0">
              <?php if (empty($agendas)): ?>
                <div class="p-4 text-center text-muted">Belum ada agenda terdekat.</div>
              <?php else:
                foreach ($agendas as $ag): ?>
                  <div class="d-flex align-items-center p-3 border-bottom hover-bg-light transition">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded text-center me-3"
                      style="min-width: 60px;">
                      <span class="d-block h5 fw-bold mb-0">
                        <?= date('d', strtotime($ag['date'])) ?>
                      </span>
                      <small class="fw-bold">
                        <?= date('M', strtotime($ag['date'])) ?>
                      </small>
                    </div>
                    <div>
                      <h6 class="mb-0 fw-bold">
                        <?= esc($ag['title']) ?>
                      </h6>
                      <small class="text-muted">
                        <?= esc($ag['location'] ?? 'Di Sekolah') ?>
                      </small>
                    </div>
                  </div>
                <?php endforeach; endif; ?>
            </div>
          </div>

          <!-- Announcements -->
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-warning text-dark p-3 border-0">
              <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>Pengumuman</h5>
            </div>
            <div class="card-body p-3">
              <?php if (empty($announcements)): ?>
                <div class="text-center text-muted py-3">Tidak ada pengumuman.</div>
              <?php else:
                foreach ($announcements as $ann): ?>
                  <div class="alert alert-warning border-0 bg-opacity-25 rounded-3 mb-2 p-3">
                    <h6 class="fw-bold mb-1">
                      <?= esc($ann['title']) ?>
                    </h6>
                    <p class="mb-0 small">
                      <?= strip_tags(character_limiter($ann['content'], 80)) ?>
                    </p>
                    <small class="text-muted">
                      <?= date('d M Y', strtotime($ann['created_at'])) ?>
                    </small>
                  </div>
                <?php endforeach; endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Teachers Slider -->
  <section class="section-padding bg-dark text-white">
    <div class="container">
      <div class="section-title text-center mb-5" data-aos="fade-up">
        <h2 class="text-white">Tenaga Pengajar</h2>
        <p class="text-white-50">Guru-guru profesional yang berdedikasi tinggi bagi siswa-siswi.</p>
      </div>
      <div class="swiper teacher-swiper" data-aos="zoom-in">
        <div class="swiper-wrapper">
          <?php if (empty($teachers)): ?>
            <div class="swiper-slide">
              <div class="teacher-card mx-auto" style="max-width: 300px; ">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=500"
                  class="teacher-img">
                <div class="p-3 text-dark text-center">
                  <h5 class="fw-bold mb-1">Nama Guru</h5>
                  <small class="text-primary fw-bold">Wali Kelas</small>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($teachers as $t): ?>
              <div class="swiper-slide">
                <div class="teacher-card text-dark text-center shadow-sm border-0">
                  <?php if (!empty($t['photo'])): ?>
                    <img src="<?= base_url('uploads/teachers/' . $t['photo']) ?>" class="teacher-img"
                      alt="<?= esc($t['name']) ?>">
                  <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($t['name']) ?>&background=random&size=200"
                      class="teacher-img" alt="<?= esc($t['name']) ?>">
                  <?php endif; ?>
                  <div class="p-3">
                    <h6 class="fw-bold mb-1">
                      <?= esc($t['name']) ?>
                    </h6>
                    <small class="text-primary fw-bold">
                      <?php
                        $roleId      = $t['role_id'] ?? null;
                        $rolesList   = $t['roles_list'] ?? '';
                        $subjectList = $t['subjects_list'] ?? '';
                        $waliClass   = $t['wali_class_name'] ?? '';
                        $schoolLvl   = $school['level'] ?? 1;
                        $isWali      = strpos($rolesList, 'guru_kelas') !== false;
                        $isMapel     = strpos($rolesList, 'guru_mapel') !== false;

                        if ($roleId == 7) {
                            echo 'Staf';
                        } elseif ($roleId == 6) {
                            echo 'Kontributor';
                        } elseif ($schoolLvl == 1) {
                            // SD: guru_kelas → "Guru Kelas [nama kelas]", guru_mapel → "Guru [kode mapel]"
                            if ($isWali) {
                                echo 'Mengajar: Guru Kelas' . ($waliClass ? ' ' . esc($waliClass) : '');
                            } elseif ($isMapel && !empty($subjectList)) {
                                echo 'Mengajar: Guru ' . esc($subjectList);
                            } else {
                                echo 'Mengajar: Guru';
                            }
                        } else {
                            // SMP / SMA: wali kelas + mapel → "Wali Kelas [nama], Guru [kode]"
                            //            mapel saja → "Guru [kode]"
                            if ($isWali && $isMapel && !empty($subjectList)) {
                                $label = 'Wali Kelas' . ($waliClass ? ' ' . esc($waliClass) : '');
                                $label .= ', Guru ' . esc($subjectList);
                                echo 'Mengajar: ' . $label;
                            } elseif ($isWali) {
                                echo 'Mengajar: Wali Kelas' . ($waliClass ? ' ' . esc($waliClass) : '');
                            } elseif ($isMapel && !empty($subjectList)) {
                                echo 'Mengajar: Guru ' . esc($subjectList);
                            } else {
                                echo 'Mengajar: Guru';
                            }
                        }
                      ?>
                    </small>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <!-- Pagination -->
        <div class="swiper-pagination mt-4"></div>
      </div>
    </div>
  </section>

  <!-- Facilities Grid -->
  <section id="fasilitas" class="section-padding">
    <div class="container">
      <div class="section-title text-center mb-5" data-aos="fade-up">
        <h2>Sarana & Prasarana</h2>
        <p class="text-muted">Fasilitas lengkap untuk menunjang kegiatan belajar mengajar.</p>
      </div>
      <div class="swiper facility-swiper" data-aos="fade-up">
        <div class="swiper-wrapper py-3">
          <?php if (empty($facilities)): ?>
            <div class="swiper-slide">
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <img src="https://images.unsplash.com/photo-1497633272928-50506af0f41c?auto=format&fit=crop&w=500"
                  class="card-img-top" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                  <h5 class="fw-bold">Ruang Kelas Digital</h5>
                  <p class="text-muted small mb-0">Dilengkapi dengan Smart TV dan AC untuk kenyamanan belajar.</p>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($facilities as $f): ?>
              <div class="swiper-slide">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 transition-hover">
                  <img
                    src="<?= $f['image'] ? base_url('uploads/facilities/' . $f['image']) : 'https://images.unsplash.com/photo-1497633272928-50506af0f41c?auto=format&fit=crop&w=500' ?>"
                    class="card-img-top" style="height: 220px; object-fit: cover;">
                  <div class="card-body p-4 text-center">
                    <h5 class="fw-bold">
                      <?= esc($f['name']) ?>
                    </h5>
                    <p class="text-muted small mb-0">
                      <?= esc($f['description']) ?>
                    </p>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <!-- Pagination -->
        <div class="swiper-pagination mt-4"></div>
      </div>
    </div>
  </section>

  <!-- Documentation Slider -->
  <section id="kegiatan" class="section-padding bg-light">
    <div class="container">
      <div class="section-title mb-5" data-aos="fade-up">
        <h2>Dokumentasi Kegiatan</h2>
        <p class="text-muted">Momen-momen berharga dalam berbagai kegiatan sekolah kami.</p>
      </div>
      <div class="swiper activity-swiper" data-aos="zoom-in-up">
        <div class="swiper-wrapper">
          <?php if (empty($activities)): ?>
            <div class="swiper-slide">
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden m-2">
                <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800"
                  class="card-img-top" style="height: 300px; object-fit: cover;">
                <div class="card-body p-3">
                  <h6 class="fw-bold mb-1">Pentas Seni Tahunan</h6>
                  <small class="text-muted">Januari 2026</small>
                </div>
              </div>
            </div>
          <?php else:
            foreach ($activities as $act): ?>
              <div class="swiper-slide">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden m-2 h-100 transition-hover">
                  <a href="<?= base_url('uploads/activities/' . $act['image']) ?>" class="glightbox"
                    data-gallery="activities">
                    <img src="<?= base_url('uploads/activities/' . $act['image']) ?>" class="card-img-top"
                      style="height: 280px; object-fit: cover;">
                  </a>
                  <div class="card-body p-4 text-center">
                    <h6 class="fw-bold mb-1">
                      <?= esc($act['title']) ?>
                    </h6>
                    <p class="text-muted small mb-2">
                      <?= esc($act['description']) ?>
                    </p>
                    <small class="text-primary fw-bold"><i class="bi bi-calendar-check me-1"></i>
                      <?= date('d M Y', strtotime($act['date'])) ?>
                    </small>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="swiper-pagination mt-5"></div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-4">
          <a href="#" class="footer-logo">
            <i class="bi bi-book-half me-2"></i>
            <?= $school['name'] ?? 'SIKAP' ?>
          </a>
          <p class="footer-text-muted mb-4">
            <?= esc($school['address'] ?? 'Alamat sekolah belum diatur di menu Identitas Sekolah.') ?>
          </p>
          <div class="d-flex gap-3">
            <?php if (!empty($school['tiktok'])): ?>
              <a href="<?= esc($school['tiktok']) ?>" target="_blank"
                class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;"><i class="bi bi-tiktok"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['instagram'])): ?>
              <a href="<?= esc($school['instagram']) ?>" target="_blank"
                class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;"><i class="bi bi-instagram"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['youtube'])): ?>
              <a href="<?= esc($school['youtube']) ?>" target="_blank"
                class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;"><i class="bi bi-youtube"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['facebook'])): ?>
              <a href="<?= esc($school['facebook']) ?>" target="_blank"
                class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;"><i class="bi bi-facebook"></i></a>
            <?php endif; ?>
            <?php if (!empty($school['twitter'])): ?>
              <a href="<?= esc($school['twitter']) ?>" target="_blank"
                class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;"><i class="bi bi-twitter-x"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-2 col-md-4">
          <h5 class="fw-bold mb-4">Tautan Cepat</h5>
          <ul class="list-unstyled footer-links">
            <li class="mb-2"><a href="#home">Beranda</a></li>
            <li class="mb-2"><a href="#profil">Profil Sekolah</a></li>
            <li class="mb-2"><a href="#berita">Berita Terbaru</a></li>
            <li class="mb-2"><a href="<?= base_url('login') ?>">Login Sistem</a>
            <li class="mb-2"><a href="<?= base_url('tentang') ?>">Tentang
              SIKAP</a></li>
            </li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-4">
          <h5 class="fw-bold mb-4">Fasilitas</h5>
          <ul class="list-unstyled footer-links">
            <li class="mb-2 text-white-50">Laboratorium Komputer</li>
            <li class="mb-2 text-white-50">Perpustakaan Digital</li>
            <li class="mb-2 text-white-50">Sarana Olahraga</li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-4">
          <h5 class="fw-bold mb-4">Hubungi Kami</h5>
          <ul class="list-unstyled">
            <li class="mb-3 d-flex align-items-start footer-text-muted">
              <i class="bi bi-telephone-fill text-primary me-3 mt-1"></i>
              <span>
                <?= esc($school['phone'] ?? '-') ?>
              </span>
            </li>
            <li class="mb-3 d-flex align-items-start footer-text-muted">
              <i class="bi bi-envelope-at-fill text-primary me-3 mt-1"></i>
              <span>
                <?= esc($school['email'] ?? '-') ?>
              </span>
            </li>
          </ul>
        </div>
      </div>
      <hr class="mt-5 border-secondary border-opacity-25">
      <div class="text-center footer-text-muted small mt-4">
        &copy;
        <?= date('Y') ?>
        <?= esc($school['name'] ?? 'SIKAP') ?>. All rights reserved. Powered by <a href="https://sakasalika.com">SakaSalika</a>.
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>

  <script>
    // Init AOS
    AOS.init({
      duration: 800,
      once: true
    });

    // Navbar Scroll Effect
    window.addEventListener('scroll', function () {
      if (window.scrollY > 50) {
        document.querySelector('.navbar').classList.add('scrolled');
      } else {
        document.querySelector('.navbar').classList.remove('scrolled');
      }
    });

    // Hero Slider Swiper
    const heroSwiper = new Swiper('.hero-swiper', {
      loop: true,
      autoplay: {
        delay: 6000,
        disableOnInteraction: false,
      },
      effect: 'creative', // Bisa dirubah ke 'fade' atau 'creative' untuk random feel
      creativeEffect: {
        prev: {
          shadow: true,
          translate: [0, 0, -400],
        },
        next: {
          translate: ['100%', 0, 0],
        },
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      on: {
        slideChangeTransitionStart: function () {
          AOS.refresh();
        }
      }
    });

    // Teacher Swiper
    new Swiper('.teacher-swiper', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: false,
      autoplay: {
        delay: 4000,
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

    // Activity Swiper
    new Swiper('.activity-swiper', {
      slidesPerView: 1,
      spaceBetween: 25,
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

    // Facility Swiper
    new Swiper('.facility-swiper', {
      slidesPerView: 1,
      spaceBetween: 25,
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
      selector: '.glightbox'
    });

    // Smooth Scrolling for Nav Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
  </script>
</body>

</html>