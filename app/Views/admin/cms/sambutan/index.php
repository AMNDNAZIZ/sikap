<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-3 px-md-4 pb-5">
  <!-- Header -->
  <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
      <h2 class="mt-2 fw-bold mb-1">🎙️ Sambutan Kepala Sekolah</h2>
      <p class="text-muted small mb-0">Kelola prakata pimpinan, kutipan pembuka, isi sambutan, dan foto yang tampil di beranda website sekolah.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('/#sambutan') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i> Lihat di Website
      </a>
    </div>
  </div>

  <!-- Alerts -->
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Status Info Banner -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
    <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
      <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
        <i class="bi bi-info-circle-fill fs-5"></i>
      </div>
      <div class="small flex-grow-1">
        <?php if (!empty($school['sambutan_isi']) || !empty($school['sambutan_title'])): ?>
          <strong class="text-success d-block"><i class="bi bi-check-circle me-1"></i> Mode Dinamis Aktif:</strong>
          Sambutan saat ini menggunakan teks dan informasi kustom yang Anda simpan di bawah.
        <?php else: ?>
          <strong class="text-primary d-block"><i class="bi bi-stars me-1"></i> Menggunakan Template Bawaan (Default):</strong>
          Belum ada teks sambutan kustom yang disimpan. Website secara otomatis menampilkan teks sambutan standar bawaan sistem.
        <?php endif; ?>
      </div>
      <?php if (!empty($school['sambutan_isi']) || !empty($school['sambutan_title'])): ?>
        <form action="<?= base_url('admin/cms/sambutan/reset') ?>" method="post" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan teks sambutan dan kembali ke teks bawaan template?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap" title="Reset ke teks standar">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke Bawaan
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <form action="<?= base_url('admin/cms/sambutan/update') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
      <!-- Kolom Kiri: Form Teks Sambutan -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Konten Sambutan</h5>
          </div>
          <div class="card-body p-4">
            <!-- Judul & Subtitle -->
            <div class="row g-3 mb-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">Judul Sambutan <span class="text-muted small">(Opsional)</span></label>
                <input type="text" name="sambutan_title" id="inputTitle" class="form-control" 
                       placeholder="<?= esc($defaultFallback['title']) ?>"
                       value="<?= esc($school['sambutan_title'] ?? '') ?>">
                <div class="form-text">Jika dikosongkan, akan otomatis tampil: <em>"<?= esc($defaultFallback['title']) ?>"</em>.</div>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Tag / Sub-judul <span class="text-muted small">(Opsional)</span></label>
                <input type="text" name="sambutan_subtitle" id="inputSubtitle" class="form-control" 
                       placeholder="<?= esc($defaultFallback['subtitle']) ?>"
                       value="<?= esc($school['sambutan_subtitle'] ?? '') ?>">
                <div class="form-text">Contoh: <em>Prakata Pimpinan</em> atau <em>Kata Sambutan</em>.</div>
              </div>
            </div>

            <!-- Kutipan Pembuka / Salam -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Salam / Kutipan Pembuka <span class="text-muted small">(Opsional)</span></label>
              <textarea name="sambutan_quote" id="inputQuote" class="form-control" rows="2" 
                        placeholder="<?= esc($defaultFallback['quote']) ?>"><?= esc($school['sambutan_quote'] ?? '') ?></textarea>
              <div class="form-text">Tampil sebagai kutipan bergaris tebal di bawah judul.</div>
            </div>

            <!-- Isi Sambutan Lengkap -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-semibold mb-0">Isi Naskah Sambutan</label>
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-primary" id="btnFillDefault">
                  <i class="bi bi-magic me-1"></i>Salin Teks Bawaan ke Editor
                </button>
              </div>
              <textarea name="sambutan_isi" id="inputIsi" class="form-control" rows="9" 
                        placeholder="Tulis naskah sambutan lengkap di sini. Pisahkan antar paragraf dengan baris baru (Enter)..."><?= esc($school['sambutan_isi'] ?? '') ?></textarea>
              <div class="form-text">Gunakan baris baru (Enter) untuk memisahkan paragraf. Jika dibiarkan kosong, sistem akan menampilkan naskah bawaan.</div>
            </div>

            <hr class="my-4">

            <!-- Data Pimpinan / Kepala Sekolah -->
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-vcard me-2 text-primary"></i>Identitas Kepala Sekolah</h6>
            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">Nama Kepala Sekolah</label>
                <input type="text" name="headmaster" id="inputHeadmaster" class="form-control" 
                       placeholder="Nama lengkap beserta gelar"
                       value="<?= esc($school['headmaster'] ?? '') ?>">
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">NIP Kepala Sekolah <span class="text-muted small">(Opsional)</span></label>
                <input type="text" name="principal_nip" id="inputNip" class="form-control" 
                       placeholder="Contoh: 19850110202221001"
                       value="<?= esc($school['principal_nip'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Foto Pimpinan & Preview Mini -->
      <div class="col-lg-4">
        <!-- Card Foto -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-image me-2 text-primary"></i>Foto Kepala Sekolah</h5>
          </div>
          <div class="card-body p-4 text-center">
            <?php
              $currentPhotoUrl = !empty($school['sambutan_image']) && file_exists(UPLOAD_PATH . 'sambutan/' . $school['sambutan_image'])
                ? base_url('uploads/sambutan/' . $school['sambutan_image'])
                : (!empty($school['vision_image']) && file_exists(UPLOAD_PATH . 'vision/' . $school['vision_image'])
                    ? base_url('uploads/vision/' . $school['vision_image'])
                    : 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=600&q=80');
            ?>

            <!-- Preview Image Frame -->
            <div class="position-relative d-inline-block rounded-4 overflow-hidden border shadow-sm mb-3" style="width: 220px; height: 270px; background: #f8fafc;">
              <img id="sambutanImgPreview" src="<?= $currentPhotoUrl ?>" alt="Foto Kepala Sekolah" 
                   class="w-100 h-100 object-fit-cover">
              <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white py-1 small" style="font-size: 0.72rem;">
                <?php if (!empty($school['sambutan_image'])): ?>
                  Foto Khusus Sambutan
                <?php elseif (!empty($school['vision_image'])): ?>
                  Foto Profil Sekolah (Vision)
                <?php else: ?>
                  Foto Default Ilustrasi
                <?php endif; ?>
              </div>
            </div>

            <div class="mb-3 text-start">
              <label class="form-label small fw-semibold">Pilih Foto Baru</label>
              <input type="file" name="sambutan_image" id="sambutanImageInput" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
              <div class="form-text" style="font-size: 0.75rem;">Maksimal 3MB (JPG, PNG, WEBP). Disarankan foto vertikal rasio 3:4.</div>
              <div id="imageError" class="text-danger small mt-1" style="display:none;"></div>
            </div>

            <?php if (!empty($school['sambutan_image'])): ?>
              <div class="form-check text-start pt-2 border-top">
                <input class="form-check-input" type="checkbox" name="remove_sambutan_image" value="1" id="removeImageCheck">
                <label class="form-check-label text-danger small" for="removeImageCheck">
                  🗑️ Hapus foto sambutan kustom (kembali ke foto visi/profil)
                </label>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tombol Simpan -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm mb-2">
              <i class="bi bi-save2 me-2"></i>Simpan Sambutan
            </button>
            <a href="<?= base_url('/#sambutan') ?>" target="_blank" class="btn btn-outline-secondary w-100">
              <i class="bi bi-eye me-1"></i> Pratinjau di Website
            </a>
          </div>
        </div>

      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const defaultIsi = <?= json_encode($defaultFallback['isi']) ?>;
  const btnFillDefault = document.getElementById('btnFillDefault');
  const inputIsi = document.getElementById('inputIsi');

  if (btnFillDefault && inputIsi) {
    btnFillDefault.addEventListener('click', function() {
      if (inputIsi.value.trim() === '' || confirm('Ganti teks saat ini dengan teks bawaan template?')) {
        inputIsi.value = defaultIsi;
        inputIsi.focus();
      }
    });
  }

  // Live Image Preview & Validation
  const imgInput = document.getElementById('sambutanImageInput');
  const imgPreview = document.getElementById('sambutanImgPreview');
  const imgError = document.getElementById('imageError');
  const removeCheck = document.getElementById('removeImageCheck');

  if (imgInput) {
    imgInput.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) return;

      const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        imgError.textContent = 'Format harus berupa gambar (JPG, PNG, WEBP).';
        imgError.style.display = 'block';
        this.value = '';
        return;
      }

      if (file.size > 3 * 1024 * 1024) {
        imgError.textContent = 'Ukuran file foto maksimal 3MB.';
        imgError.style.display = 'block';
        this.value = '';
        return;
      }

      imgError.style.display = 'none';
      if (removeCheck) removeCheck.checked = false;

      const reader = new FileReader();
      reader.onload = function(e) {
        imgPreview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  if (removeCheck) {
    removeCheck.addEventListener('change', function() {
      if (this.checked && imgInput) {
        imgInput.value = '';
        imgError.style.display = 'none';
      }
    });
  }
});
</script>

<?= $this->endSection() ?>
