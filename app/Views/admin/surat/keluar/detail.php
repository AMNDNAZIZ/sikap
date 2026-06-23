<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$typeName = $letter_types[$letter['letter_type']] ?? $letter['letter_type'];
$ld = $letter['letter_data'] ?? [];
$isRevoked = $letter['status'] === 'revoked';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0">
      <i class="bi bi-envelope-paper me-2 text-primary"></i><?= esc($letter['letter_number']) ?>
    </h2>
    <small class="text-muted"><?= esc($typeName) ?></small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <?php if (!$isRevoked): ?>
      <a href="<?= base_url('admin/surat-keluar/download/' . $letter['id']) ?>"
         class="btn btn-success btn-sm" id="btn-download-pdf">
        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
      </a>
      <button type="button" class="btn btn-danger btn-sm" id="btn-revoke-open"
              data-bs-toggle="modal" data-bs-target="#modal-revoke">
        <i class="bi bi-x-circle me-1"></i>Cabut Surat
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($isRevoked): ?>
  <div class="alert alert-danger d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-x-octagon-fill fs-3"></i>
    <div>
      <strong>SURAT INI TELAH DICABUT</strong><br>
      <small>Alasan: <?= esc($letter['revoke_reason']) ?></small><br>
      <small>Dicabut pada: <?= date('d/m/Y H:i', strtotime($letter['revoked_at'])) ?></small>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- Info Surat -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-primary text-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>Informasi Surat
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><th width="160">Nomor Surat</th><td class="font-monospace fw-bold"><?= esc($letter['letter_number']) ?></td></tr>
          <tr><th>Tanggal Surat</th><td><?= date('d F Y', strtotime($letter['issued_at'])) ?></td></tr>
          <tr><th>Jenis Surat</th><td><span class="badge bg-secondary"><?= esc($typeName) ?></span></td></tr>
          <tr><th>Sifat</th><td><?= esc($letter['sifat']) ?></td></tr>
          <tr><th>Perihal</th><td><?= esc($letter['subject']) ?></td></tr>
          <tr><th>Penerima</th><td class="fw-semibold"><?= esc($letter['recipient_name']) ?></td></tr>
          <tr><th>Status</th><td>
            <?php if ($isRevoked): ?>
              <span class="badge bg-danger">Dicabut</span>
            <?php else: ?>
              <span class="badge bg-success">Aktif ✓</span>
            <?php endif; ?>
          </td></tr>
        </table>
      </div>
    </div>

    <!-- Data Spesifik Surat -->
    <?php if ($letter['letter_type'] === 'surat_eksternal'): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-info bg-opacity-10 fw-semibold py-2">
        <i class="bi bi-file-earmark-pdf me-2"></i>Surat Eksternal / Upload
        <span class="badge bg-info ms-2">PDF Upload</span>
      </div>
      <div class="card-body">
        <?php
        $origFilename = $letter['letter_data']['original_filename'] ?? '-';
        $fileSize     = !empty($letter['file_size_bytes']) ? round($letter['file_size_bytes'] / 1024) . ' KB' : '-';
        $nomorManual  = $letter['letter_data']['nomor_manual'] ?? '';
        $catatan      = $letter['letter_data']['catatan'] ?? '';
        ?>
        <table class="table table-sm table-borderless mb-3">
          <tr><th width="160" class="text-muted">File Asli</th><td><i class="bi bi-file-earmark-pdf text-danger me-1"></i><?= esc($origFilename) ?></td></tr>
          <tr><th class="text-muted">Ukuran PDF</th><td><?= $fileSize ?></td></tr>
          <?php if ($nomorManual): ?>
          <tr><th class="text-muted">Nomor Manual</th><td class="font-monospace"><?= esc($nomorManual) ?></td></tr>
          <?php endif; ?>
          <?php if ($catatan): ?>
          <tr><th class="text-muted">Catatan</th><td><?= esc($catatan) ?></td></tr>
          <?php endif; ?>
        </table>

        <?php if (!empty($letter['pdf_path'])): ?>
        <div class="border rounded overflow-hidden">
          <iframe src="<?= base_url('admin/surat-keluar/view-pdf/' . $letter['id']) ?>"
                  width="100%" height="500" style="border:none;" loading="lazy"></iframe>
        </div>
        <small class="text-muted mt-2 d-block">
          <i class="bi bi-info-circle me-1"></i>Preview PDF final dengan QR Code verifikasi di pojok kiri bawah.
        </small>
        <?php else: ?>
        <div class="alert alert-warning py-2 small mb-0">
          <i class="bi bi-exclamation-triangle me-1"></i>File PDF belum berhasil digenerate. Silakan coba download ulang.
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php elseif ($letter['letter_type'] === 'surat_custom' && !empty($processed_body)): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-light fw-semibold py-2">
        <i class="bi bi-file-richtext me-2"></i>Preview Isi Surat Custom
        <span class="badge bg-secondary ms-2 fw-normal" style="font-size:0.75rem;">
          <?= ($letter['letter_data']['header_style'] ?? 'tengah') === 'tengah' ? '📄 Header Tengah' : '📋 Header Kiri-Kanan' ?>
        </span>
      </div>
      <div class="card-body">
        <div class="border rounded p-3 bg-white" style="font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6;">
          <?= $processed_body ?>
        </div>
        <small class="text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>Placeholder telah digantikan dengan data penerima asli. Tampilan final ada di PDF.</small>
      </div>
    </div>
    <?php elseif (!empty($ld)): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-card-list me-2"></i>Detail Isi Surat</div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <?php foreach ($ld as $key => $val): ?>
            <?php if (!empty($val)): ?>
            <tr>
              <th width="200" class="text-muted" style="font-size:0.85rem;"><?= esc(ucwords(str_replace('_', ' ', $key))) ?></th>
              <td><?= esc($val) ?></td>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Penerima Multiple (Lomba) -->
    <?php if ($letter['is_multi_recipient'] && !empty($letter['recipients'])): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-people me-2"></i>Daftar Siswa</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr><th>No</th><th>Nama</th><th>Kelas</th><th>Tanggal Lahir</th><th>Cabang</th></tr>
            </thead>
            <tbody>
              <?php foreach ($letter['recipients'] as $i => $r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= esc($r['name']) ?></td>
                <td><?= esc($r['kelas']) ?></td>
                <td><?= !empty($r['birth_date']) ? date('d/m/Y', strtotime($r['birth_date'])) : '-' ?></td>
                <td><?= esc($r['cabang']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Kolom Kanan: QR & Kepala Sekolah -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-4 text-center">
      <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-qr-code me-2"></i>Kode Verifikasi QR</div>
      <div class="card-body">
        <p class="text-muted small">Scan QR ini untuk memverifikasi keaslian surat</p>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode(base_url('verify/' . $letter['qr_code_id'])) ?>"
             alt="QR Code Verifikasi" class="img-fluid rounded" style="max-width:160px;">
        <div class="mt-2">
          <small class="text-muted font-monospace" style="font-size:0.7rem;"><?= esc($letter['qr_code_id']) ?></small>
        </div>
        <a href="<?= base_url('verify/' . $letter['qr_code_id']) ?>" target="_blank"
           class="btn btn-outline-primary btn-sm mt-2">
          <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Verifikasi
        </a>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-person-circle me-2"></i>Penandatangan</div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><th class="text-muted">Nama</th><td class="fw-semibold"><?= esc($letter['principal_name_snapshot']) ?></td></tr>
          <tr><th class="text-muted">NIP</th><td class="font-monospace"><?= esc($letter['principal_nip_snapshot']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cabut Surat -->
<div class="modal fade" id="modal-revoke" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= base_url('admin/surat-keluar/revoke/' . $letter['id']) ?>">
        <?= csrf_field() ?>
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-x-octagon me-2"></i>Cabut Surat</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Surat <strong><?= esc($letter['letter_number']) ?></strong> akan dicabut dan status verifikasi QR akan berubah menjadi <strong>DICABUT</strong>.</p>
          <label class="form-label fw-semibold">Alasan Pencabutan <span class="text-danger">*</span></label>
          <textarea name="revoke_reason" class="form-control" rows="3" minlength="10" required
                    placeholder="Jelaskan alasan pencabutan surat (minimal 10 karakter)..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Cabut Surat</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
