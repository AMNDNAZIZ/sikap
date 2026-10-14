<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title) ?> — <?= esc($school['name'] ?? 'ESPATA') ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= !empty($school['logo']) ? base_url('uploads/logo/' . $school['logo']) : base_url('uploads/logo/espata.png') ?>">

  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    body { 
      background: #f0f4f8; 
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
      color: #1e293b;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px 15px;
    }
    .verify-card { 
      max-width: 540px; 
      margin: 0 auto; 
      width: 100%;
    }
    .badge-status { 
      font-size: 1.05rem; 
      padding: 0.65rem 1.4rem; 
      border-radius: 50px; 
      font-weight: 700;
    }
    .school-header { 
      background: linear-gradient(135deg, #0a1d37 0%, #1e3a5f 100%); 
      color: white; 
      padding: 1.25rem 1.5rem; 
      border-radius: 1rem 1rem 0 0; 
      border-bottom: 3px solid #f59e0b;
    }
    .card-verify-body {
      border-radius: 0 0 1rem 1rem;
    }
  </style>
</head>
<body>
<div class="verify-card">

  <!-- Header Dinamis Sekolah Mengikuti Settingan Profil Sekolah -->
  <div class="school-header text-center">
    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
      <?php if (!empty($school['logo'])): ?>
        <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" 
             alt="Logo" height="46" class="rounded bg-white p-1">
      <?php else: ?>
        <img src="<?= base_url('uploads/logo/espata.png') ?>" 
             alt="ESPATA" height="42" class="rounded bg-white p-1">
      <?php endif; ?>
      <div class="text-start">
        <?php if (!empty($school['city_regency'])): ?>
          <div class="fw-semibold text-light opacity-75 text-uppercase" style="font-size:0.75rem; letter-spacing: 0.5px;">
            DINAS PENDIDIKAN <?= strtoupper(esc($school['city_regency'])) ?>
          </div>
        <?php endif; ?>
        <div class="fw-bold text-white fs-6 text-uppercase" style="line-height: 1.2;">
          <?= esc($school['name'] ?? 'ESPATA SCHOOL') ?>
        </div>
      </div>
    </div>

    <!-- Alamat Dinamis dari Settingan Sekolah -->
    <div style="font-size:0.75rem; opacity:0.85; line-height: 1.4;" class="text-light">
      <?= esc($school['address'] ?? 'Alamat sekolah belum diatur.') ?>
      <?php if (!empty($school['city_regency'])): ?>
        , <?= esc($school['city_regency']) ?>
      <?php endif; ?>
      <?php if (!empty($school['phone'])): ?>
        <br><span>Telp: <?= esc($school['phone']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-lg card-verify-body">
    <div class="card-body p-4 text-center">

      <div class="mb-3">
        <i class="bi bi-shield-check" style="font-size: 3rem; color: #1e3a5f;"></i>
      </div>
      <h5 class="fw-bold mb-3 text-dark">Verifikasi Keaslian Dokumen &amp; Surat</h5>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small mb-3 border-0 rounded-3">
          <i class="bi bi-exclamation-triangle-fill me-1"></i><?= session()->getFlashdata('error') ?>
        </div>
      <?php endif; ?>

      <?php if ($status === 'valid'): ?>
        <!-- STATUS 1: VALID & ASLI -->
        <div class="badge bg-success badge-status mb-4 d-inline-block shadow-sm">
          <i class="bi bi-patch-check-fill me-1"></i> SURAT VALID &amp; ASLI
        </div>
        <div class="card bg-light border-0 text-start mb-4 rounded-3">
          <div class="card-body p-3">
            <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem;">
              <tr>
                <th width="130" class="text-muted">Nomor Surat</th>
                <td class="fw-bold font-monospace text-primary"><?= esc($letter['letter_number']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Tanggal Surat</th>
                <td class="fw-medium"><?= date('d F Y', strtotime($letter['issued_at'])) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Penerima</th>
                <td class="fw-semibold"><?= esc($letter['recipient_name']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Perihal / Hal</th>
                <td><?= esc($letter['subject']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Diterbitkan</th>
                <td class="small text-secondary"><?= date('d F Y, H:i', strtotime($letter['created_at'])) ?> WIB</td>
              </tr>
            </table>
          </div>
        </div>
        <p class="text-muted small mb-3">
          Surat ini diterbitkan secara resmi oleh <strong><?= esc($school['name'] ?? 'pihak sekolah') ?></strong>
          dan keaslian isinya terverifikasi dalam pangkalan data resmi.
        </p>
        <?php if (($letter['letter_type'] ?? '') === 'surat_eksternal'): ?>
        <p class="text-muted small fst-italic mb-3">
          <i class="bi bi-info-circle me-1"></i>Surat ini diunggah dari dokumen eksternal dan telah diverifikasi oleh sistem ESPATA.
        </p>
        <?php endif; ?>

      <?php elseif ($status === 'revoked'): ?>
        <!-- STATUS 2: TELAH DICABUT -->
        <div class="badge bg-danger badge-status mb-4 d-inline-block shadow-sm">
          <i class="bi bi-x-circle-fill me-1"></i> SURAT TELAH DICABUT
        </div>
        <div class="card bg-light border-0 text-start mb-3 rounded-3">
          <div class="card-body p-3">
            <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem;">
              <tr>
                <th width="130" class="text-muted">Nomor Surat</th>
                <td class="fw-bold font-monospace text-danger"><?= esc($letter['letter_number']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Penerima</th>
                <td><?= esc($letter['recipient_name']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Perihal</th>
                <td><?= esc($letter['subject']) ?></td>
              </tr>
            </table>
          </div>
        </div>
        <div class="alert alert-danger text-start py-2.5 rounded-3 mb-3 small">
          <strong>⚠️ Surat ini tidak berlaku lagi.</strong><br>
          Dokumen telah dicabut status aktifnya oleh pihak <strong><?= esc($school['name'] ?? 'sekolah') ?></strong> dan tidak dapat digunakan sebagai dokumen resmi.
        </div>

      <?php elseif ($status === 'not_found'): ?>
        <!-- STATUS 3: TIDAK DITEMUKAN -->
        <div class="badge bg-secondary badge-status mb-4 d-inline-block">
          <i class="bi bi-question-circle-fill me-1"></i> DOKUMEN TIDAK DITEMUKAN
        </div>
        <p class="text-muted small mb-3">
          Kode QR atau ID Dokumen ini tidak terdaftar dalam pangkalan data resmi <strong><?= esc($school['name'] ?? 'sekolah kami') ?></strong>.<br>
          Kemungkinan surat bukan diterbitkan oleh sekolah kami atau dokumen tersebut tidak asli.
        </p>

      <?php elseif ($status === 'invalid'): ?>
        <!-- STATUS 4: FORMAT TIDAK VALID -->
        <div class="badge bg-warning text-dark badge-status mb-4 d-inline-block">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> FORMAT TIDAK VALID
        </div>
        <p class="text-muted small mb-3">
          Kode atau tautan verifikasi yang Anda masukkan tidak memiliki format yang dikenali oleh sistem.
        </p>

      <?php else: ?>
        <!-- STATUS 5: FORM PENCARIAN / CEK MANUAL (/verify/cek) -->
        <div class="badge bg-primary bg-opacity-10 text-primary badge-status mb-3 d-inline-block border border-primary border-opacity-25">
          <i class="bi bi-search me-1"></i> Pengecekan Dokumen
        </div>
        <p class="text-muted small mb-4">
          Masukkan <strong>ID Dokumen (UUID)</strong> atau <strong>Nomor Surat</strong> resmi yang tertera pada dokumen untuk memeriksa keabsahannya.
        </p>
      <?php endif; ?>

      <!-- Form Input Pencarian Dokumen (Ditampilkan di mode cek, invalid, atau not_found) -->
      <?php if (in_array($status, ['cek', 'invalid', 'not_found'])): ?>
        <form action="<?= base_url('verify/cek') ?>" method="post" class="mb-3 text-start">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">ID Dokumen / Nomor Surat / URL QR:</label>
            <div class="input-group">
              <input type="text" name="code" class="form-control rounded-start-3" 
                     placeholder="Contoh: 123/SD/2026 atau kode UUID" 
                     value="<?= esc($qrCodeId) ?>" required>
              <button class="btn btn-primary px-3 rounded-end-3" type="submit">
                <i class="bi bi-search me-1"></i> Periksa
              </button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <hr class="my-3">
      <div class="text-muted" style="font-size: 0.78rem;">
        <i class="bi bi-clock me-1"></i>Waktu Pengecekan: <?= date('d F Y, H:i') ?> WIB
        <?php if (!empty($qrCodeId) && $qrCodeId !== 'cek'): ?>
          <br><i class="bi bi-qr-code me-1"></i>ID Dokumen: <span class="font-monospace fw-semibold"><?= esc($qrCodeId) ?></span>
        <?php endif; ?>
      </div>

      <div class="d-flex justify-content-center gap-2 mt-3 pt-2 border-top">
        <a href="<?= base_url() ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
          <i class="bi bi-house-door me-1"></i> Beranda Sekolah
        </a>
        <?php if ($status !== 'cek'): ?>
          <a href="<?= base_url('verify/cek') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1">
            <i class="bi bi-arrow-repeat me-1"></i> Cek Dokumen Lain
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <div class="text-center text-muted mt-3" style="font-size:0.75rem;">
    <i class="bi bi-shield-lock me-1"></i>Sistem Verifikasi Dokumen &amp; Surat Resmi — ESPATA <?= esc($school['name'] ?? '') ?>
  </div>
</div>
</body>
</html>
