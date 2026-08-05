<?php
$transitions = ['fade', 'slide', 'zoom', 'rotate', 'flip']; // efek acak
shuffle($transitions);
?>

<div id="carouselDekstop" class="carousel slide d-none d-lg-block" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <?php foreach ($carousel_desktop ?? [] as $i => $img): ?>
      <button type="button" data-bs-target="#carouselDekstop" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>"></button>
    <?php endforeach ?>
  </div>
  <div class="carousel-inner">
    <?php foreach ($carousel_desktop ?? [] as $i => $img): ?>
      <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
        <img src="<?= base_url('uploads/carousels/'.$img['image']) ?>" class="d-block w-100" alt="slide">
      </div>
    <?php endforeach ?>
  </div>
</div>


<div id="carouselMobile" class="carousel slide d-lg-none" data-bs-ride="carousel">
  <div class="carousel-inner">
    <?php foreach ($carousel_mobile ?? [] as $i => $img): ?>
      <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
        <img src="<?= base_url('uploads/carousels/'.$img['image']) ?>" class="d-block w-100" alt="mobile slide">
      </div>
    <?php endforeach ?>
  </div>
</div>
<div class="container py-5">
  <div class="row py-3">
    <div class="col-lg-7 mb-4">
      <h1><?= esc($school_name ?? 'SD Modern Indonesia') ?></h1>
      <h4><?= esc($school_tagline ?? 'Sekolah yang menyenangkan dan inovatif') ?></h4>
      <p><?= esc($school_description ?? 'Tempat terbaik untuk tumbuh dan belajar.') ?></p>
      <p>
        <a href="<?= base_url('ppdb') ?>" class="btn btn-dark">Info PPDB</a>
        <a href="<?= base_url('profil') ?>" class="btn btn-outline-dark">Profil Sekolah</a>
      </p>
    </div>
    <div class="col-lg-5 mb-4">
      <?php 
      // Gunakan school_image dari database, fallback ke default
      $schoolImg = $school_image ?? 'default-school.jpg';
      ?>
      <img src="<?= base_url('uploads/' . $schoolImg) ?>" 
           class="d-block w-100 rounded" 
           alt="School Image"
           onerror="this.src='<?= base_url('assets/img/school.jpg') ?>'">
    </div>
  </div>
</div>

<!-- CDN untuk animasi -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script>
  const effects = ['fadeIn', 'zoomIn', 'slideInRight', 'flipInX', 'rotateIn'];
const slides = document.querySelectorAll('.carousel-item');

slides.forEach(slide => {
  const effect = effects[Math.floor(Math.random() * effects.length)];
  slide.classList.add('animate__animated', `animate__${effect}`);
});
</script>
