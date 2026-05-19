<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Surat Masuk</h2>
    <small class="text-muted">ID #<?= $letter['id'] ?></small>
  </div>
  <a href="<?= base_url('admin/surat-masuk/detail/' . $letter['id']) ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Batal
  </a>
</div>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <strong>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach (session()->getFlashdata('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-light fw-semibold py-2">
    <i class="bi bi-pencil me-2"></i>Data Surat Masuk
  </div>
  <div class="card-body">
    <form method="POST" action="<?= base_url('admin/surat-masuk/update/' . $letter['id']) ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="received_at">Tanggal Diterima <span class="text-danger">*</span></label>
          <input type="date" name="received_at" id="received_at" class="form-control"
                 value="<?= old('received_at', $letter['received_at']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="letter_date">Tanggal Surat</label>
          <input type="date" name="letter_date" id="letter_date" class="form-control"
                 value="<?= old('letter_date', $letter['letter_date']) ?>">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" for="letter_number">Nomor Surat</label>
          <input type="text" name="letter_number" id="letter_number" class="form-control"
                 value="<?= old('letter_number', $letter['letter_number']) ?>" placeholder="cth: 045/PK.01.01/2026">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="sender_name">Nama Penanda Tangan <span class="text-danger">*</span></label>
          <input type="text" name="sender_name" id="sender_name" class="form-control" required
                 value="<?= old('sender_name', $letter['sender_name']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="sender_agency">Instansi Pengirim</label>
          <input type="text" name="sender_agency" id="sender_agency" class="form-control"
                 list="agency_options" value="<?= old('sender_agency', $letter['sender_agency']) ?>" placeholder="Pilih atau ketik instansi...">
          <datalist id="agency_options">
            <option value="Dinas Pendidikan"></option>
            <option value="Sudindik JB 1"></option>
          </datalist>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" for="subject">Perihal <span class="text-danger">*</span></label>
          <input type="text" name="subject" id="subject" class="form-control" required
                 value="<?= old('subject', $letter['subject']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="letter_category">Kategori</label>
          <select name="letter_category" id="letter_category" class="form-select">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($categories as $key => $label): ?>
              <option value="<?= $key ?>"
                <?= (old('letter_category', $letter['letter_category']) === $key) ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="disposition">Disposisi</label>
          <input type="text" name="disposition" id="disposition" class="form-control"
                 value="<?= old('disposition', $letter['disposition']) ?>" placeholder="Untuk...">
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-warning" id="btn-update">
          <i class="bi bi-save me-2"></i>Simpan Perubahan
        </button>
        <a href="<?= base_url('admin/surat-masuk/detail/' . $letter['id']) ?>" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
