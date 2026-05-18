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
      background: linear-gradient(135deg, #f0fdf4 0%, #e8f5e9 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .form-card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(21, 128, 61, 0.12);
    }
    .form-control, .form-select {
      min-height: 48px;
      font-size: 16px;
      border-radius: 10px;
    }
    .btn-submit {
      min-height: 52px;
      font-size: 1.05rem;
      border-radius: 12px;
      background: #15803d;
      border: none;
    }
    .btn-submit:hover { background: #166534; }
    .badge-type {
      background: #dcfce7;
      color: #15803d;
      border-radius: 20px;
      padding: 0.35rem 0.9rem;
      font-size: 0.85rem;
    }
    label.required::after {
      content: ' *';
      color: #dc3545;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">

        <!-- Header -->
        <div class="text-center mb-3">
          <span class="badge-type"><i class="bi bi-building-fill me-1"></i>Tamu Dinas</span>
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
          <form action="<?= base_url('buku-tamu/dinas/store') ?>" method="POST" id="form-dinas">
            <?= csrf_field() ?>

            <!-- Nama Lengkap -->
            <div class="mb-3">
              <label class="form-label fw-semibold required" for="nama">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" class="form-control"
                     placeholder="Nama lengkap sesuai ID" value="<?= old('nama') ?>" required autocomplete="name">
            </div>

            <!-- NIP -->
            <div class="mb-3">
              <label class="form-label fw-semibold" for="nip">NIP <span class="text-muted fw-normal">(opsional)</span></label>
              <input type="text" id="nip" name="nip" class="form-control"
                     placeholder="Nomor Induk Pegawai" value="<?= old('nip') ?>">
            </div>

            <!-- Instansi -->
            <div class="mb-3">
              <label class="form-label fw-semibold required" for="instansi">Instansi / Lembaga</label>
              <input type="text" id="instansi" name="instansi" class="form-control"
                     placeholder="Nama instansi atau lembaga" value="<?= old('instansi') ?>" required>
            </div>

            <!-- No HP -->
            <div class="mb-3">
              <label class="form-label fw-semibold" for="no_hp">Nomor HP / Telepon</label>
              <input type="tel" id="no_hp" name="no_hp" class="form-control"
                     placeholder="Contoh: 08123456789" value="<?= old('no_hp') ?>" autocomplete="tel">
            </div>

            <!-- Tujuan Dinas -->
            <div class="mb-3">
              <label class="form-label fw-semibold required" for="tujuan">Tujuan Kunjungan Dinas</label>
              <textarea id="tujuan" name="tujuan" class="form-control" rows="2"
                        placeholder="Jelaskan tujuan kunjungan dinas Anda" required><?= old('tujuan') ?></textarea>
            </div>

            <!-- Bertemu Dengan -->
            <div class="mb-4">
              <label class="form-label fw-semibold" for="bertemu_dengan">Bertemu Dengan</label>
              <select id="bertemu_dengan" name="bertemu_dengan" class="form-select">
                <option value="">-- Pilih Guru / Pejabat --</option>
                <?php foreach ($teachers as $teacher): ?>
                  <option value="<?= esc($teacher['name']) ?>"
                    <?= old('bertemu_dengan') === $teacher['name'] ? 'selected' : '' ?>>
                    <?= esc($teacher['name']) ?>
                    <?= !empty($teacher['position']) ? '— ' . esc($teacher['position']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-submit btn-success w-100" id="btn-kirim">
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
    document.getElementById('form-dinas').addEventListener('submit', function () {
      document.getElementById('btn-kirim').disabled = true;
      document.getElementById('btn-kirim').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    });
  </script>
</body>
</html>
