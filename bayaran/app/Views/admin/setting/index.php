<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12 text-center text-md-start">
      <h4 class="font-outfit fw-bold text-dark mb-1">Pengaturan Sekolah</h4>
      <p class="text-muted small">Kelola informasi profil dan identitas sekolah Anda di sini.</p>
    </div>
  </div>

  <?php if (session()->getFlashdata('msg')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert"
      style="border-left: 4px solid #198754 !important;">
      <i class="bi bi-check-circle-fill me-2"></i>
      <?= esc(session()->getFlashdata('msg')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif ?>

  <form method="post" action="<?= site_url('admin/setting/save') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
      <!-- Kolom Kiri: Profil & Logo -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
          <div class="card-header bg-white border-0 py-3" style="border-radius: 15px 15px 0 0;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-bank me-2 text-primary"></i>Profil Sekolah</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Nama Aplikasi</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-cpu text-primary"></i></span>
                  <input type="text" name="nama_aplikasi" value="<?= esc($setting['nama_aplikasi'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="Contoh: Pembayaran Sekolah" required>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Nama Sekolah</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-building text-primary"></i></span>
                  <input type="text" name="nama_sekolah" value="<?= esc($setting['nama_sekolah'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="Nama Sekolah Anda" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Alamat Sekolah</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="bi bi-geo-alt text-primary"></i></span>
                <textarea name="alamat" class="form-control border-light bg-light" rows="3"
                  placeholder="Alamat lengkap sekolah..." required><?= esc($setting['alamat'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
          <div class="card-header bg-white border-0 py-3" style="border-radius: 15px 15px 0 0;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Kontak & Personalia</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">No. HP / WhatsApp</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-whatsapp text-primary"></i></span>
                  <input type="text" name="no_hp" value="<?= esc($setting['no_hp'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="628xxx">
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Email Sekolah</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-envelope text-primary"></i></span>
                  <input type="email" name="email" value="<?= esc($setting['email'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="sekolah@email.com">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Website</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-globe text-primary"></i></span>
                  <input type="text" name="website" value="<?= esc($setting['website'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="www.sekolah.sch.id">
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Nama Kepala Sekolah</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i
                      class="bi bi-person-badge text-primary"></i></span>
                  <input type="text" name="kepala_sekolah" value="<?= esc($setting['kepala_sekolah'] ?? '') ?>"
                    class="form-control border-light bg-light" placeholder="Nama Lengkap & Gelar">
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php if (session()->get('role') === 'superadmin'): ?>
        <div class="card border-0 shadow-sm mt-4" style="border-radius: 15px;">
          <div class="card-header bg-white border-0 py-3" style="border-radius: 15px 15px 0 0;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-percent me-2 text-primary"></i>Pengaturan Khusus Yayasan</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-bold text-muted">Persentase SPP Yayasan (%)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-pie-chart text-primary"></i></span>
                  <input type="number" min="0" max="100" name="persentase_yayasan" value="<?= esc($setting['persentase_yayasan'] ?? '0') ?>"
                    class="form-control border-light bg-light" placeholder="0 - 100">
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Kolom Kanan: Logo & Action -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
          <div class="card-header bg-white border-0 py-3" style="border-radius: 15px 15px 0 0;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Logo Sekolah</h6>
          </div>
          <div class="card-body text-center">
            <div class="mb-4 d-inline-block p-3 bg-light" style="border-radius: 20px; border: 2px dashed #dee2e6;">
              <?php if (!empty($setting['logo'])): ?>
                <img src="<?= base_url($setting['logo']) ?>" alt="Logo" class="img-fluid"
                  style="max-height: 150px; border-radius: 10px;">
              <?php else: ?>
                <div class="py-5 px-4 text-muted">
                  <i class="bi bi-image-fill display-4 d-block mb-2"></i>
                  <small>Belum ada logo</small>
                </div>
              <?php endif ?>
            </div>
            <div class="text-start">
              <label class="form-label small fw-bold text-muted">Upload Logo Baru</label>
              <input type="file" name="logo" class="form-control border-light bg-light" accept="image/*">
              <p class="text-muted extra-small mt-2 icon-link"><i class="bi bi-info-circle"></i> Gunakan file JPG/PNG
                transparan max 2MB.</p>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
          <div class="card-body p-0">
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-none"
              style="border-radius: 0; background: linear-gradient(45deg, var(--primary), var(--secondary)); border: none; transition: 0.3s;">
              <i class="bi bi-save me-2"></i>SIMPAN PERUBAHAN
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<style>
  .input-group-text {
    font-size: 1.1rem;
  }

  .form-control:focus {
    box-shadow: none;
    border-color: var(--primary) !important;
    background-color: #fff !important;
  }

  .extra-small {
    font-size: 0.75rem;
  }

  .icon-link {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-2px);
  }
</style>

<?= $this->endSection() ?>