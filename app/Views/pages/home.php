<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section id="home" class="banner">
  <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1200&q=60');">
    <div class="slide-info"><h2>Selamat Datang di SD Modern Indonesia</h2><p>Mendidik generasi masa depan dengan inovasi & semangat.</p></div>
  </div>
  <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=60');">
    <div class="slide-info"><h2>Lingkungan Belajar Menyenangkan</h2><p>Tempat di mana anak-anak tumbuh dan berkreasi.</p></div>
  </div>
  <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1509062522406-72e1e03c240c?auto=format&fit=crop&w=1200&q=60');">
    <div class="slide-info"><h2>Prestasi Gemilang</h2><p>Siswa kami berprestasi di tingkat nasional & internasional.</p></div>
  </div>
  <button class="prev" onclick="changeSlide(-1)">&#10094;</button>
  <button class="next" onclick="changeSlide(1)">&#10095;</button>
</section>

<section id="about" class="section">
  <h2>Tentang Sekolah</h2>
  <p>SD Modern Indonesia berkomitmen menciptakan lingkungan belajar yang inovatif, ramah, dan berbasis teknologi.</p>
</section>

<section id="news" class="section">
  <h2>Berita & Agenda</h2>
  <div class="news-list">
    <div class="news-item"><h3>Pendaftaran Siswa Baru 2025</h3><p>Segera daftarkan putra-putri Anda.</p></div>
    <div class="news-item"><h3>Lomba Sains Antar Sekolah</h3><p>Siswa kami meraih juara pertama tingkat kota.</p></div>
  </div>
</section>

<section id="gallery" class="section">
  <h2>Galeri</h2>
  <div class="gallery-grid">
    <img src="https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=800&q=60">
    <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=800&q=60">
  </div>
</section>

<section id="contact" class="section">
  <h2>Kontak Kami</h2>
  <p>Alamat: Jl. Pendidikan No.123, Jakarta</p>
  <p>Email: info@sdmodern.sch.id</p>
</section>

<?= $this->endSection() ?>
