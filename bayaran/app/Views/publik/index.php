<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<style>
  .search-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.3s ease;
  }

  .search-card:hover {
    transform: translateY(-5px);
  }

  .card-header-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.25rem;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    text-align: center;
    border: none;
    letter-spacing: 0.5px;
  }

  .form-control-modern {
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
    transition: all 0.3s ease;
  }

  .form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background-color: #fff;
  }

  .btn-modern {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-search {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    color: white;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
  }

  .btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
    color: white;
  }

  .btn-reset {
    background-color: #f1f5f9;
    color: #64748b;
    border: none;
  }

  .btn-reset:hover {
    background-color: #e2e8f0;
    color: #475569;
  }

  .info-card {
    background-color: #ffffff;
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
  }

  .info-title {
    color: #764ba2;
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
  }
</style>

<div class="row g-4">
  <!-- 📌 Kolom kiri: Form Pencarian -->
  <div class="col-12 col-md-4">
    <div class="search-card mb-4">
      <div class="card-header-modern">RINCIAN TAGIHAN SISWA</div>
      <div class="card-body p-4">
        <form method="get" action="<?= site_url('/') ?>">
          <div class="mb-3">
            <label for="nis" class="form-label fw-bold text-muted small">NOMOR INDUK SISWA (NIS)</label>
            <input type="text" name="nis" id="nis" class="form-control form-control-modern"
              placeholder="Masukkan NIS siswa..." required value="<?= esc($nis ?? '') ?>">
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-modern btn-search"><i class="bi bi-search me-2"></i>Cari Tagihan</button>
            <a href="<?= site_url('/') ?>" class="btn btn-modern btn-reset">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="info-card p-4 text-center">
      <h6 class="info-title mb-3">SISTEM ARSIP</h6>
      <p class="text-muted small">Data Pembayaran Siswa Lulusan 2025 dan sebelumnya dapat diakses melalui portal arsip.
      </p>
      <a href="https://bayaran.smpistora.com/" class="btn btn-modern btn-outline-primary w-100">Portal Arsip 2025</a>
    </div>
  </div>

  <!-- 📌 Kolom kanan: Hasil atau Info Rekening -->
  <div class="col-12 col-md-8">
    <?php if (isset($notfound) && $notfound): ?>
      <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
          Siswa dengan NIS <strong><?= esc($nis) ?></strong> tidak ditemukan. Pastikan NIS yang dimasukkan sudah benar.
        </div>
      </div>
      <div class="search-card p-4">
        <?= view('components/info_rekening') ?>
      </div>

    <?php elseif (isset($siswa)): ?>
      <div class="mb-4">
        <?= view('components/hasil_pencarian', compact('siswa', 'summary', 'bulanan', 'bebas')) ?>
      </div>
      <div class="search-card p-4">
        <?= view('components/info_rekening') ?>
      </div>

    <?php else: ?>
      <div class="search-card p-4">
        <?= view('components/info_rekening') ?>
      </div>
    <?php endif ?>
  </div>
</div>

<?= $this->endSection() ?>