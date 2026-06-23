<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title) ?> — SDN Mangga Besar 11 Pagi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
    .verify-card { max-width: 480px; margin: 60px auto; }
    .badge-status { font-size: 1.1rem; padding: 0.6rem 1.2rem; border-radius: 50px; }
    .school-header { background: #1e3a5f; color: white; padding: 1rem 1.5rem; border-radius: .5rem .5rem 0 0; }
  </style>
</head>
<body>
<div class="verify-card px-3">

  <!-- Header Sekolah -->
  <div class="school-header text-center">
    <div class="fw-bold" style="font-size:0.85rem;">PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA</div>
    <div class="fw-bold" style="font-size:0.85rem;">DINAS PENDIDIKAN</div>
    <div class="fw-bold mt-1" style="font-size:1rem;">SD NEGERI MANGGA BESAR 11 PAGI</div>
    <div style="font-size:0.75rem; opacity:0.85;">Jalan Gedong No. 16, Tamansari, Jakarta Barat 11180</div>
  </div>

  <div class="card border-0 shadow-lg" style="border-radius: 0 0 .5rem .5rem;">
    <div class="card-body p-4 text-center">

      <div class="mb-3">
        <i class="bi bi-shield-check" style="font-size: 3rem; color: #1e3a5f;"></i>
      </div>
      <h5 class="fw-bold mb-4">Verifikasi Keaslian Surat</h5>

      <?php if ($status === 'valid'): ?>
        <div class="badge bg-success badge-status mb-4 d-inline-block">
          <i class="bi bi-check-circle-fill me-2"></i>SURAT VALID &amp; ASLI
        </div>
        <div class="card bg-light border-0 text-start mb-4">
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem;">
              <tr>
                <th width="130" class="text-muted">Nomor Surat</th>
                <td class="fw-bold font-monospace"><?= esc($letter['letter_number']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Tanggal</th>
                <td><?= date('d F Y', strtotime($letter['issued_at'])) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Penerima</th>
                <td><?= esc($letter['recipient_name']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Keperluan</th>
                <td><?= esc($letter['subject']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Diterbitkan</th>
                <td><?= date('d F Y', strtotime($letter['created_at'])) ?></td>
              </tr>
            </table>
          </div>
        </div>
        <p class="text-muted small">
          Surat ini diterbitkan secara resmi oleh <strong>SDN Mangga Besar 11 Pagi</strong>
          dan dapat dipertanggungjawabkan keasliannya.
        </p>
        <?php if (($letter['letter_type'] ?? '') === 'surat_eksternal'): ?>
        <p class="text-muted small fst-italic">
          <i class="bi bi-info-circle me-1"></i>Surat ini diunggah dari dokumen eksternal dan telah diverifikasi oleh sistem SIKAP.
        </p>
        <?php endif; ?>

      <?php elseif ($status === 'revoked'): ?>
        <div class="badge bg-danger badge-status mb-4 d-inline-block">
          <i class="bi bi-x-circle-fill me-2"></i>SURAT TELAH DICABUT
        </div>
        <div class="card bg-light border-0 text-start mb-4">
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem;">
              <tr>
                <th width="130" class="text-muted">Nomor Surat</th>
                <td class="fw-bold font-monospace"><?= esc($letter['letter_number']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Penerima</th>
                <td><?= esc($letter['recipient_name']) ?></td>
              </tr>
              <tr>
                <th class="text-muted">Keperluan</th>
                <td><?= esc($letter['subject']) ?></td>
              </tr>
            </table>
          </div>
        </div>
        <div class="alert alert-danger text-start py-2">
          <strong>⚠️ Surat ini tidak berlaku.</strong><br>
          <small>Surat telah dicabut oleh pihak sekolah dan <strong>tidak dapat digunakan</strong> sebagai dokumen resmi.</small>
        </div>

      <?php elseif ($status === 'invalid'): ?>
        <div class="badge bg-warning text-dark badge-status mb-4 d-inline-block">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>FORMAT TIDAK VALID
        </div>
        <p class="text-muted">Kode yang Anda scan tidak memiliki format yang dikenali sistem.</p>

      <?php else: ?>
        <div class="badge bg-secondary badge-status mb-4 d-inline-block">
          <i class="bi bi-question-circle-fill me-2"></i>SURAT TIDAK DITEMUKAN
        </div>
        <p class="text-muted">
          Kode QR ini tidak terdaftar dalam sistem kami.<br>
          Kemungkinan surat <strong>bukan diterbitkan</strong> oleh SDN Mangga Besar 11 Pagi,
          atau dokumen ini <strong>tidak asli</strong>.
        </p>
        <div class="alert alert-warning text-start py-2">
          <small>⚠️ Jika Anda merasa ini adalah kesalahan, silakan hubungi Tata Usaha sekolah untuk konfirmasi.</small>
        </div>
      <?php endif; ?>

      <hr>
      <div class="text-muted" style="font-size: 0.75rem;">
        <i class="bi bi-clock me-1"></i>Diverifikasi pada: <?= date('d F Y, H:i') ?> WIB<br>
        <i class="bi bi-globe me-1"></i>ID Dokumen: <span class="font-monospace"><?= esc($qrCodeId) ?></span>
      </div>
    </div>
  </div>

  <div class="text-center text-muted mt-3" style="font-size:0.75rem;">
    <i class="bi bi-shield-lock me-1"></i>Sistem Verifikasi Surat Resmi — SIAKAD SDN Mangga Besar 11 Pagi
  </div>
</div>
</body>
</html>
