<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-envelope-open me-2 text-success"></i>Detail Surat Masuk</h2>
    <small class="text-muted">Diterima: <?= date('d F Y', strtotime($letter['received_at'])) ?></small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= base_url('admin/surat-masuk') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <a href="<?= base_url('admin/surat-masuk/edit/' . $letter['id']) ?>" class="btn btn-warning btn-sm">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <?php if (!empty($letter['scan_path'])): ?>
      <a href="<?= base_url('admin/surat-masuk/scan/' . $letter['id']) ?>" target="_blank" class="btn btn-info btn-sm">
        <i class="bi bi-eye me-1"></i>Lihat Scan
      </a>
    <?php endif; ?>
    <a href="<?= base_url('admin/surat-masuk/delete/' . $letter['id']) ?>"
       class="btn btn-outline-danger btn-sm"
       onclick="return confirm('Hapus surat ini?')">
      <i class="bi bi-trash me-1"></i>Hapus
    </a>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-7">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-success text-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>Informasi Surat Masuk
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><th width="160">Tanggal Diterima</th><td><?= date('d F Y', strtotime($letter['received_at'])) ?></td></tr>
          <?php if (!empty($letter['letter_date'])): ?>
            <tr><th>Tanggal Surat</th><td><?= date('d F Y', strtotime($letter['letter_date'])) ?></td></tr>
          <?php endif; ?>
          <tr><th>Nomor Surat</th><td class="font-monospace fw-bold"><?= esc($letter['letter_number'] ?? '—') ?></td></tr>
          <tr><th>Penanda Tangan</th><td class="fw-semibold"><?= esc($letter['sender_name']) ?></td></tr>
          <tr><th>Instansi</th><td><?= esc($letter['sender_agency'] ?? '—') ?></td></tr>
          <tr><th>Perihal</th><td><?= esc($letter['subject']) ?></td></tr>
          <tr><th>Kategori</th><td>
            <?php if (!empty($letter['letter_category'])): ?>
              <span class="badge bg-light text-dark border">
                <?= esc($categories[$letter['letter_category']] ?? $letter['letter_category']) ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td></tr>
          <?php if (!empty($letter['disposition'])): ?>
            <tr><th>Disposisi</th><td><?= esc($letter['disposition']) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <?php if ($letter['ocr_processed']): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold py-2">
        <i class="bi bi-cpu me-2"></i>Hasil OCR
        <span class="badge bg-success ms-2"><?= number_format($letter['ocr_confidence'], 0) ?>% akurasi</span>
      </div>
      <div class="card-body">
        <pre class="small text-muted border rounded p-2" style="max-height:200px;overflow-y:auto;font-size:0.78rem;"><?= esc($letter['ocr_raw_text'] ?? '—') ?></pre>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-md-5">
    <?php if (!empty($letter['scan_path'])): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-file-earmark me-2"></i>File Scan</div>
      <div class="card-body text-center">
        <?php if (strtolower($letter['file_type'] ?? '') === 'pdf'): ?>
          <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem;"></i>
          <p class="mt-2 small text-muted">File PDF</p>
        <?php else: ?>
          <img src="<?= base_url('admin/surat-masuk/scan/' . $letter['id']) ?>"
               alt="Scan Surat" class="img-fluid rounded border" style="max-height:300px;">
        <?php endif; ?>
        <?php if (!empty($letter['file_size_bytes'])): ?>
          <div class="text-muted small mt-2"><?= number_format($letter['file_size_bytes'] / 1024, 1) ?> KB</div>
        <?php endif; ?>
        <a href="<?= base_url('admin/surat-masuk/scan/' . $letter['id']) ?>" target="_blank"
           class="btn btn-outline-info btn-sm mt-2">
          <i class="bi bi-box-arrow-up-right me-1"></i>Buka File
        </a>
      </div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center text-muted py-5">
        <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
        Tidak ada file scan
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
