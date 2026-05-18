<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= esc($title) ?> - <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #e8f0fe 0%, #f0f7ff 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .form-card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(26, 111, 196, 0.12);
    }
    .form-control, .form-select {
      min-height: 48px;
      font-size: 16px; /* Prevent iOS zoom */
      border-radius: 10px;
    }
    .btn-submit {
      min-height: 52px;
      font-size: 1.05rem;
      border-radius: 12px;
      background: #1a6fc4;
      border: none;
    }
    .btn-submit:hover { background: #145ea8; }
    .badge-type {
      background: #dbeafe;
      color: #1a6fc4;
      border-radius: 20px;
      padding: 0.35rem 0.9rem;
      font-size: 0.85rem;
    }
    label.required::after {
      content: ' *';
      color: #dc3545;
    }
    #instansiGroup { display: none; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">

        <!-- Header -->
        <div class="text-center mb-3">
          <span class="badge-type"><i class="bi bi-people-fill me-1"></i>Tamu Umum</span>
          <h1 class="h5 fw-bold mt-2"><?= esc($school['name'] ?? 'Buku Tamu Digital') ?></h1>
        </div>

        <!-- Error Messages -->
        <?php if (session()->getFlashdata('errors')): ?>
          <div class="alert alert-danger rounded-3">
            <ul class="mb-0 ps-3">
              <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card form-card p-4">
          <form action="<?= base_url('buku-tamu/umum/store') ?>" method="POST" id="form-umum">
            <?= csrf_field() ?>

            <!-- Nama Lengkap -->
            <div class="mb-3">
              <label class="form-label fw-semibold required" for="nama">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" class="form-control"
                     placeholder="Masukkan nama lengkap" value="<?= old('nama') ?>" required autocomplete="name">
            </div>

            <!-- No HP -->
            <div class="mb-3">
              <label class="form-label fw-semibold" for="no_hp">Nomor HP</label>
              <input type="tel" id="no_hp" name="no_hp" class="form-control"
                     placeholder="Contoh: 08123456789" value="<?= old('no_hp') ?>" autocomplete="tel">
            </div>

            <!-- Apakah Orang Tua Siswa? -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Apakah Anda Orang Tua / Wali Siswa?</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="is_ortu_siswa" id="ortu_ya" value="1"
                    <?= old('is_ortu_siswa', '1') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ortu_ya">Ya</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="is_ortu_siswa" id="ortu_tidak" value="0"
                    <?= old('is_ortu_siswa') === '0' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ortu_tidak">Bukan</label>
                </div>
              </div>
            </div>

            <!-- Instansi (conditional — muncul jika bukan ortu) -->
            <div class="mb-3" id="instansiGroup">
              <label class="form-label fw-semibold" for="instansi">Asal Instansi / Lembaga</label>
              <input type="text" id="instansi" name="instansi" class="form-control"
                     placeholder="Nama instansi / lembaga" value="<?= old('instansi') ?>">
            </div>

            <!-- Alamat -->
            <div class="mb-3">
              <label class="form-label fw-semibold" for="alamat">Alamat</label>
              <textarea id="alamat" name="alamat" class="form-control" rows="2"
                        placeholder="Alamat lengkap (opsional)"><?= old('alamat') ?></textarea>
            </div>

            <!-- Tujuan Kunjungan -->
            <div class="mb-3">
              <label class="form-label fw-semibold required" for="tujuan">Tujuan Kunjungan</label>
              <textarea id="tujuan" name="tujuan" class="form-control" rows="2"
                        placeholder="Jelaskan keperluan Anda" required><?= old('tujuan') ?></textarea>
            </div>

            <!-- Bertemu Dengan -->
            <div class="mb-4">
              <label class="form-label fw-semibold" for="bertemu_dengan">Bertemu Dengan</label>
              <select id="bertemu_dengan" name="bertemu_dengan" class="form-select">
                <option value="">-- Pilih Guru / Staf --</option>
                <?php foreach ($teachers as $teacher): ?>
                  <option value="<?= esc($teacher['name']) ?>"
                    <?= old('bertemu_dengan') === $teacher['name'] ? 'selected' : '' ?>>
                    <?= esc($teacher['name']) ?>
                    <?= !empty($teacher['position']) ? '— ' . esc($teacher['position']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-submit btn-primary w-100" id="btn-kirim">
              <i class="bi bi-send-fill me-2"></i>Kirim Data
            </button>
          </form>
        </div>

        <div class="text-center mt-3">
          <a href="<?= base_url('buku-tamu') ?>" class="text-muted text-decoration-none" style="font-size:0.85rem;">
            <i class="bi bi-arrow-left"></i> Kembali ke pilihan jenis tamu
          </a>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Conditional field: tampilkan instansi jika bukan orang tua
    const radios = document.querySelectorAll('input[name="is_ortu_siswa"]');
    const instansiGroup = document.getElementById('instansiGroup');

    function toggleInstansi() {
      const val = document.querySelector('input[name="is_ortu_siswa"]:checked')?.value;
      instansiGroup.style.display = val === '0' ? 'block' : 'none';
    }

    radios.forEach(r => r.addEventListener('change', toggleInstansi));
    toggleInstansi(); // run on load

    // Prevent double submit
    document.getElementById('form-umum').addEventListener('submit', function () {
      document.getElementById('btn-kirim').disabled = true;
      document.getElementById('btn-kirim').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    });
  </script>
</body>
</html>
