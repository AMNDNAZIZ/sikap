<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .transition-all {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
  }
  .hover-translate-y:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.08) !important;
  }
  .btn:active {
    transform: scale(0.98) !important;
  }
  .placeholder-btn {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    background: #ffffff;
    border-color: #cbd5e1 !important;
    color: #334155 !important;
  }
  .placeholder-btn:hover {
    background: #f8fafc !important;
    border-color: #0d6efd !important;
    color: #0d6efd !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.12);
  }
</style>

<?php
$typeName = $letter_types[$letter['letter_type']] ?? $letter['letter_type'];
$ld       = $letter['letter_data'] ?? [];
$rd       = $letter['recipient_detail'] ?? [];
$useKop   = (int)($letter['use_kop'] ?? 1);
$recType  = $letter['recipient_type'] ?? 'siswa';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.75rem;">
        <i class="bi bi-pencil-square me-1"></i>Edit Naskah Keluar
      </span>
      <span class="badge rounded-pill bg-light text-muted border px-2 py-1 font-monospace" style="font-size:0.75rem;">
        <?= esc($letter['letter_number']) ?>
      </span>
    </div>
    <h2 class="h4 fw-bold mb-0 text-dark">
      Edit Surat: <span class="text-primary"><?= esc($letter['subject']) ?></span>
    </h2>
    <small class="text-muted">Perbarui data atau redaksi naskah. Nomor registrasi dan QR Code verifikasi publik tetap dipertahankan.</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('admin/surat-keluar/detail/' . $letter['id']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail
    </a>
    <?php if (!empty($letter['pdf_path'])): ?>
      <a href="<?= base_url('admin/surat-keluar/view-pdf/' . $letter['id']) ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3">
        <i class="bi bi-file-earmark-pdf me-1"></i>Lihat PDF Saat Ini
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Banner Integritas Naskah Dinas & QR Verification -->
<div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 rounded-3 mb-4 p-3 shadow-xs">
  <div class="d-flex align-items-start gap-3 flex-wrap flex-md-nowrap">
    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
      <i class="bi bi-shield-check fs-5"></i>
    </div>
    <div class="flex-grow-1">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="fw-bold text-dark mb-1">Tata Kelola &amp; Integritas Dokumen Dinas</h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace" style="font-size:0.7rem;">
          <i class="bi bi-qr-code me-1"></i>QR UUID: <?= esc(substr($letter['qr_code_id'], 0, 13)) ?>...
        </span>
      </div>
      <p class="text-muted small mb-0" style="font-size:0.8rem; line-height:1.5;">
        Dokumen ini telah terdaftar resmi dengan Nomor <strong><?= esc($letter['letter_number']) ?></strong>. Setelah Anda memperbarui isi surat, sistem akan meregenerasi lembar PDF secara otomatis tanpa mengubah tautan QR verifikasi, memastikan stempel fisik atau tautan yang sudah beredar tetap valid dan autentik.
      </p>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-xs">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Perhatian: Silakan perbaiki kesalahan berikut:</div>
    <ul class="mb-0 ps-3 small">
      <?php foreach (session()->getFlashdata('errors') as $err): ?>
        <li><?= esc($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form action="<?= base_url('admin/surat-keluar/update/' . $letter['id']) ?>" method="POST" id="form-surat-keluar">
  <?= csrf_field() ?>
  <input type="hidden" name="letter_type" value="<?= esc($letter['letter_type']) ?>">
  <input type="hidden" name="recipient_type" id="recipient_type" value="<?= esc($recType) ?>">
  <input type="hidden" name="recipient_ref_id" id="recipient_ref_id" value="<?= esc($letter['recipient_ref_id'] ?? '') ?>">

  <div class="row g-4">
    <!-- Kolom Kiri: Form Utama -->
    <div class="col-lg-8">

      <!-- Section 1: Informasi Pokok Surat -->
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
          <span class="badge rounded-circle bg-primary bg-opacity-10 text-primary p-2">
            <i class="bi bi-card-heading fs-6"></i>
          </span>
          <div>
            <h6 class="fw-bold mb-0 text-dark">Informasi Pokok Naskah</h6>
            <small class="text-muted" style="font-size:0.75rem;">Atribut baku surat keluar sesuai Tata Naskah Dinas</small>
          </div>
        </div>
        <div class="card-body row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="subject">Perihal / Hal Surat <span class="text-danger">*</span></label>
            <input type="text" name="subject" id="subject" class="form-control"
                   value="<?= esc(old('subject', $letter['subject'])) ?>" required>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold" for="issued_at">Tanggal Surat <span class="text-danger">*</span></label>
            <input type="date" name="issued_at" id="issued_at" class="form-control"
                   value="<?= esc(old('issued_at', date('Y-m-d', strtotime($letter['issued_at'])))) ?>" required>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold" for="sifat">Sifat Surat <span class="text-danger">*</span></label>
            <select name="sifat" id="sifat" class="form-select">
              <option value="Biasa" <?= old('sifat', $letter['sifat']) === 'Biasa' ? 'selected' : '' ?>>Biasa</option>
              <option value="Penting" <?= old('sifat', $letter['sifat']) === 'Penting' ? 'selected' : '' ?>>Penting</option>
              <option value="Rahasia" <?= old('sifat', $letter['sifat']) === 'Rahasia' ? 'selected' : '' ?>>Rahasia</option>
              <option value="Segera" <?= old('sifat', $letter['sifat']) === 'Segera' ? 'selected' : '' ?>>Segera</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="classification_code">
              Kode Klasifikasi Arsip
              <small class="text-muted fw-normal">(Opsional)</small>
            </label>
            <input type="text" name="classification_code" id="classification_code" class="form-control font-monospace"
                   placeholder="cth: 421.1 / 421.2" value="<?= esc(old('classification_code', $letter['classification_code'] ?? '')) ?>">
            <small class="text-muted" style="font-size:0.72rem;">Sesuai klasifikasi pola persuratan dinas pendidikan</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Pilihan Cetak Kepala Surat (KOP)</label>
            <div class="form-check form-switch mt-1 p-2 bg-light rounded border">
              <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="use_kop" id="use_kop" value="1" <?= $useKop === 1 ? 'checked' : '' ?>>
              <label class="form-check-label small fw-semibold" for="use_kop">
                <i class="bi bi-image me-1 text-primary"></i>Cetak Gambar KOP Digital
              </label>
            </div>
            <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
              Nonaktifkan switch jika dokumen ini akan dicetak menggunakan <strong>kertas berkop fisik resmi</strong> (margin atas 3.5cm diterapkan otomatis).
            </small>
          </div>
        </div>
      </div>

      <!-- Section 2: Data Penerima Naskah -->
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
          <span class="badge rounded-circle bg-primary bg-opacity-10 text-primary p-2">
            <i class="bi bi-person-lines-fill fs-6"></i>
          </span>
          <div>
            <h6 class="fw-bold mb-0 text-dark">Data Penerima Naskah</h6>
            <small class="text-muted" style="font-size:0.75rem;">Nama individu atau instansi yang dituju</small>
          </div>
        </div>
        <div class="card-body row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold" for="recipient_name">Nama Penerima / Kepada <span class="text-danger">*</span></label>
            <input type="text" name="recipient_name" id="recipient_name" class="form-control"
                   value="<?= esc(old('recipient_name', $letter['recipient_name'])) ?>" required>
            <small class="text-muted" style="font-size:0.72rem;">
              Untuk multi-penerima pada surat eksternal/undangan, pisahkan nama dengan tanda pipa (<code>|</code>) atau baris baru.
            </small>
          </div>

          <!-- Hidden input variables for auto-filling and placeholders -->
          <input type="hidden" name="nisn" id="f_nisn" value="<?= esc($rd['nisn'] ?? '') ?>">
          <input type="hidden" name="nis" id="f_nis" value="<?= esc($rd['nis'] ?? '') ?>">
          <input type="hidden" name="nik" id="f_nik" value="<?= esc($rd['nik'] ?? '') ?>">
          <input type="hidden" name="gender" id="f_gender" value="<?= esc($rd['gender'] ?? '') ?>">
          <input type="hidden" name="kelas" id="f_kelas" value="<?= esc($rd['kelas'] ?? '') ?>">
          <input type="hidden" name="ttl" id="f_ttl" value="<?= esc($rd['ttl'] ?? '') ?>">
          <input type="hidden" name="alamat" id="f_alamat" value="<?= esc($rd['alamat'] ?? '') ?>">
          <input type="hidden" name="nama_ayah" id="f_nama_ayah" value="<?= esc($rd['nama_ayah'] ?? '') ?>">
          <input type="hidden" name="pekerjaan_ayah" id="f_pekerjaan_ayah" value="<?= esc($rd['pekerjaan_ayah'] ?? '') ?>">
          <input type="hidden" name="penghasilan_ayah" id="f_penghasilan_ayah" value="<?= esc($rd['penghasilan_ayah'] ?? '') ?>">
          <input type="hidden" name="nama_ibu" id="f_nama_ibu" value="<?= esc($rd['nama_ibu'] ?? '') ?>">
          <input type="hidden" name="pekerjaan_ibu" id="f_pekerjaan_ibu" value="<?= esc($rd['pekerjaan_ibu'] ?? '') ?>">
          <input type="hidden" name="nama_wali" id="f_nama_wali" value="<?= esc($rd['nama_wali'] ?? '') ?>">
          <input type="hidden" name="alamat_ortu" id="f_alamat_ortu" value="<?= esc($rd['alamat_ortu'] ?? '') ?>">
          <input type="hidden" name="nip" id="f_nip" value="<?= esc($rd['nip'] ?? '') ?>">
          <input type="hidden" name="nuptk" id="f_nuptk" value="<?= esc($rd['nuptk'] ?? '') ?>">
          <input type="hidden" name="jabatan" id="f_jabatan" value="<?= esc($rd['jabatan'] ?? '') ?>">
          <input type="hidden" name="pangkat_golongan" id="f_pangkat_golongan" value="<?= esc($rd['pangkat_golongan'] ?? '') ?>">
          <input type="hidden" name="status_kepegawaian" id="f_status_kepegawaian" value="<?= esc($rd['status_kepegawaian'] ?? '') ?>">
        </div>
      </div>

      <!-- Section 3: Format & Isi Spesifik Surat -->
      <?php if ($letter['letter_type'] === 'surat_custom'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                <i class="bi bi-envelope-paper-fill fs-6"></i>
              </span>
              <div>
                <h6 class="fw-bold mb-0 text-dark">Format &amp; Isi Surat Dinamis</h6>
                <small class="text-muted" style="font-size:0.75rem;">Susun redaksi naskah dengan visual WYSIWYG &amp; bank template naskah dinas</small>
              </div>
            </div>
            
            <!-- Quick Actions Toolbar -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs" id="btn-open-bank-template" data-bs-toggle="modal" data-bs-target="#modal-bank-template">
                <i class="bi bi-collection me-1"></i>Pilih Template Dinas
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btn-open-save-template" data-bs-toggle="modal" data-bs-target="#modal-save-template">
                <i class="bi bi-bookmark-plus me-1"></i>Simpan sbg Template
              </button>
              <div id="draft-status-wrapper" class="d-inline-flex align-items-center">
                <span id="draft-status-pill" class="badge rounded-pill bg-light text-secondary border px-2 py-1" style="font-size:0.72rem;">
                  <i class="bi bi-check2-circle text-success me-1"></i><span id="draft-status-text">Auto-draft siap</span>
                </span>
                <button type="button" class="btn btn-link btn-sm text-decoration-none py-0 px-1 text-primary small" id="btn-restore-draft" style="display:none; font-size:0.72rem;">
                  Pulihkan
                </button>
              </div>
            </div>
          </div>

          <div class="card-body row g-3">
            <input type="hidden" name="template_id" id="selected_template_id" value="<?= esc($letter['template_id'] ?? '') ?>">

            <div class="col-md-6">
              <label class="form-label fw-semibold">Gaya Header Kop Surat <span class="text-danger">*</span></label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="header_style" id="style_tengah" value="tengah" <?= ($ld['header_style'] ?? 'tengah') === 'tengah' ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="style_tengah">
                    <i class="bi bi-align-center me-1 text-primary"></i>Tengah (Judul di Tengah)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="header_style" id="style_kiri_kanan" value="kiri_kanan" <?= ($ld['header_style'] ?? 'tengah') === 'kiri_kanan' ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="style_kiri_kanan">
                    <i class="bi bi-columns-gap me-1 text-primary"></i>Kiri-Kanan (Nomor &amp; Kepada)
                  </label>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="custom_recipient_type">Tipe Penerima Surat</label>
              <select id="custom_recipient_type" class="form-select">
                <option value="eksternal">Multi-Penerima Custom (Input 1 atau Lebih Baris)</option>
                <option value="guru">Guru / Tenaga Kependidikan</option>
                <option value="siswa">Siswa (Data Siswa Tunggal)</option>
                <option value="undangan">Massal (Seluruh Guru / Wali Murid Kelas)</option>
              </select>
            </div>

            <!-- Placeholders Panel (Categorized Tabs) -->
            <div class="col-12">
              <div class="card border border-secondary-subtle bg-light-subtle rounded-3">
                <div class="card-header bg-white py-2 px-3 border-bottom">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="fw-bold text-dark d-flex align-items-center">
                      <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Variabel Data Otomatis (Klik untuk menyisipkan ke kursor editor):
                    </small>
                    <ul class="nav nav-pills nav-pills-sm p-1 bg-slate-100 rounded-pill border border-slate-200/60" id="placeholder-tabs" role="tablist" style="background:#f1f5f9;">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill py-1 px-2.5 d-flex align-items-center gap-1.5 fw-semibold" style="font-size:0.73rem;" id="tab-siswa-pill" data-bs-toggle="pill" data-bs-target="#pill-siswa" type="button" role="tab">
                          <i class="bi bi-mortarboard text-primary"></i> Siswa
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1 px-2.5 d-flex align-items-center gap-1.5 fw-semibold" style="font-size:0.73rem;" id="tab-ortu-pill" data-bs-toggle="pill" data-bs-target="#pill-ortu" type="button" role="tab">
                          <i class="bi bi-people text-warning"></i> Orang Tua
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1 px-2.5 d-flex align-items-center gap-1.5 fw-semibold" style="font-size:0.73rem;" id="tab-guru-pill" data-bs-toggle="pill" data-bs-target="#pill-guru" type="button" role="tab">
                          <i class="bi bi-person-workspace text-success"></i> Guru/Tendik
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1 px-2.5 d-flex align-items-center gap-1.5 fw-semibold" style="font-size:0.73rem;" id="tab-instansi-pill" data-bs-toggle="pill" data-bs-target="#pill-instansi" type="button" role="tab">
                          <i class="bi bi-bank text-secondary"></i> Sekolah
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1 px-2.5 d-flex align-items-center gap-1.5 fw-semibold" style="font-size:0.73rem;" id="tab-surat-pill" data-bs-toggle="pill" data-bs-target="#pill-surat" type="button" role="tab">
                          <i class="bi bi-table text-danger"></i> Surat &amp; Tabel
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="card-body py-2 px-3">
                  <div class="tab-content" id="placeholderTabContent">
                    <!-- Tab Siswa -->
                    <div class="tab-pane fade show active" id="pill-siswa" role="tabpanel">
                      <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_penerima}" style="font-size:0.73rem;">{nama_penerima}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nisn}" style="font-size:0.73rem;">{nisn}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nis}" style="font-size:0.73rem;">{nis}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nik}" style="font-size:0.73rem;">{nik}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{kelas}" style="font-size:0.73rem;">{kelas}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{ttl}" style="font-size:0.73rem;">{ttl}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{jenis_kelamin}" style="font-size:0.73rem;">{jenis_kelamin}</button>
                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{alamat}" style="font-size:0.73rem;">{alamat}</button>
                      </div>
                    </div>
                    <!-- Tab Ortu -->
                    <div class="tab-pane fade" id="pill-ortu" role="tabpanel">
                      <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_ayah}" style="font-size:0.73rem;">{nama_ayah}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{pekerjaan_ayah}" style="font-size:0.73rem;">{pekerjaan_ayah}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{penghasilan_ayah}" style="font-size:0.73rem;">{penghasilan_ayah}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_ibu}" style="font-size:0.73rem;">{nama_ibu}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{pekerjaan_ibu}" style="font-size:0.73rem;">{pekerjaan_ibu}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_wali}" style="font-size:0.73rem;">{nama_wali}</button>
                        <button type="button" class="btn btn-outline-warning text-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{alamat_ortu}" style="font-size:0.73rem;">{alamat_ortu}</button>
                      </div>
                    </div>
                    <!-- Tab Guru -->
                    <div class="tab-pane fade" id="pill-guru" role="tabpanel">
                      <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_penerima}" style="font-size:0.73rem;">{nama_penerima}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nip}" style="font-size:0.73rem;">{nip}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nuptk}" style="font-size:0.73rem;">{nuptk}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{jabatan}" style="font-size:0.73rem;">{jabatan}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{pangkat_golongan}" style="font-size:0.73rem;">{pangkat_golongan}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{status_kepegawaian}" style="font-size:0.73rem;">{status_kepegawaian}</button>
                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{alamat}" style="font-size:0.73rem;">{alamat}</button>
                      </div>
                    </div>
                    <!-- Tab Sekolah -->
                    <div class="tab-pane fade" id="pill-instansi" role="tabpanel">
                      <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nama_sekolah}" style="font-size:0.73rem;">{nama_sekolah}</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{alamat_sekolah}" style="font-size:0.73rem;">{alamat_sekolah}</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{kepala_sekolah}" style="font-size:0.73rem;">{kepala_sekolah}</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nip_kepala_sekolah}" style="font-size:0.73rem;">{nip_kepala_sekolah}</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{tahun_pelajaran}" style="font-size:0.73rem;">{tahun_pelajaran}</button>
                      </div>
                    </div>
                    <!-- Tab Surat & Tabel -->
                    <div class="tab-pane fade" id="pill-surat" role="tabpanel">
                      <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-danger btn-xs py-1 px-2 placeholder-btn rounded-pill fw-semibold" data-placeholder="{tabel_penerima}" style="font-size:0.73rem;" title="Sisipkan tabel multi-penerima otomatis">
                          <i class="bi bi-table me-1"></i>{tabel_penerima}
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{nomor_surat}" style="font-size:0.73rem;">{nomor_surat}</button>
                        <button type="button" class="btn btn-outline-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{tanggal_surat}" style="font-size:0.73rem;">{tanggal_surat}</button>
                        <button type="button" class="btn btn-outline-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{hari_tanggal_kegiatan}" style="font-size:0.73rem;">{hari_tanggal_kegiatan}</button>
                        <button type="button" class="btn btn-outline-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{waktu_kegiatan}" style="font-size:0.73rem;">{waktu_kegiatan}</button>
                        <button type="button" class="btn btn-outline-dark btn-xs py-1 px-2 placeholder-btn rounded-pill" data-placeholder="{tempat_kegiatan}" style="font-size:0.73rem;">{tempat_kegiatan}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- CKEditor Container -->
            <div class="col-12">
              <label class="form-label fw-semibold">Isi Surat <span class="text-danger">*</span></label>

              <!-- Toolbar Spasi Paragraf Kustom -->
              <div class="d-flex align-items-center gap-2 flex-wrap mb-2 p-2 bg-light border rounded" id="custom-spacing-toolbar">
                <small class="text-secondary fw-semibold me-1" style="font-size:0.76rem;"><i class="bi bi-arrows-expand me-1"></i>Spasi Paragraf:</small>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="0" title="Tanpa Jarak (0px)">0</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="4" title="Sangat Rapat (4px)">4px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="8" title="Rapat (8px)">8px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing active" data-margin="12" title="Normal (12px)">12px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="18" title="Longgar (18px)">18px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="24" title="Sangat Longgar (24px)">24px</button>
                </div>
                <span class="text-muted" style="font-size:0.72rem; border-left:1px solid #ccc; padding-left:8px;">
                  <kbd>Enter</kbd> = paragraf baru &nbsp;|&nbsp; <kbd>Shift+Enter</kbd> = pindah baris
                </span>
              </div>

              <input type="hidden" name="body_html" id="body_html_hidden">
              <div id="editor-surat-custom-container" style="min-height: 340px;">
                <div id="editor-surat-custom"><?= $ld['body_html'] ?? '' ?></div>
              </div>
              <small class="text-muted mt-1 d-block" style="font-size:0.73rem;">
                <i class="bi bi-info-circle me-1"></i>Format dan tata letak yang disusun di atas akan direfleksikan secara persis di dokumen PDF resmi.
              </small>
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'keterangan_aktif'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Surat Keterangan Aktif</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold" for="keperluan_tambahan">Keperluan Pembuatan Surat</label>
              <input type="text" name="keperluan_tambahan" id="keperluan_tambahan" class="form-control"
                     value="<?= esc($ld['keperluan_tambahan'] ?? '') ?>" placeholder="cth: Pengurusan Tunjangan Anak / BPJS / Pembukaan Rekening">
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'keterangan_mengajar'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Keterangan Mengajar</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="kelas_mengajar">Kelas / Bidang Studi</label>
              <input type="text" name="kelas_mengajar" id="kelas_mengajar" class="form-control"
                     value="<?= esc($ld['kelas_mengajar'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="no_hp">No. WhatsApp / HP Guru</label>
              <input type="text" name="no_hp" id="no_hp" class="form-control"
                     value="<?= esc($ld['no_hp'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" for="alamat_tinggal">Alamat Tinggal Guru</label>
              <textarea name="alamat_tinggal" id="alamat_tinggal" class="form-control" rows="2"><?= esc($ld['alamat_tinggal'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'surat_tugas'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Penugasan</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="activity_name">Nama Kegiatan / Tugas <span class="text-danger">*</span></label>
              <input type="text" name="activity_name" id="activity_name" class="form-control"
                     value="<?= esc($ld['activity_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="activity_date">Tanggal Pelaksanaan</label>
              <input type="date" name="activity_date" id="activity_date" class="form-control"
                     value="<?= esc($ld['activity_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="activity_time">Waktu Kegiatan</label>
              <input type="text" name="activity_time" id="activity_time" class="form-control"
                     value="<?= esc($ld['activity_time'] ?? '') ?>" placeholder="08.00 WIB s/d selesai">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="activity_venue">Tempat Kegiatan</label>
              <input type="text" name="activity_venue" id="activity_venue" class="form-control"
                     value="<?= esc($ld['activity_venue'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="activity_address">Alamat Tempat Kegiatan</label>
              <input type="text" name="activity_address" id="activity_address" class="form-control"
                     value="<?= esc($ld['activity_address'] ?? '') ?>">
            </div>
            <div class="col-12 border-top pt-3">
              <small class="fw-bold text-muted d-block mb-2"><i class="bi bi-link-45deg me-1"></i>Dasar Surat / Referensi Eksternal (Opsional):</small>
              <div class="row g-2">
                <div class="col-md-4">
                  <input type="text" name="ref_letter_number" class="form-control form-control-sm" placeholder="Nomor Surat Referensi" value="<?= esc($ld['ref_letter_number'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <input type="date" name="ref_letter_date" class="form-control form-control-sm" value="<?= esc($ld['ref_letter_date'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="ref_letter_from" class="form-control form-control-sm" placeholder="Dari Instansi Pengirim" value="<?= esc($ld['ref_letter_from'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'undangan'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Acara Undangan</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="event_agenda">Agenda Acara <span class="text-danger">*</span></label>
              <input type="text" name="event_agenda" id="event_agenda" class="form-control"
                     value="<?= esc($ld['event_agenda'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="event_day">Hari</label>
              <input type="text" name="event_day" id="event_day" class="form-control"
                     value="<?= esc($ld['event_day'] ?? '') ?>" placeholder="Senin">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="event_date">Tanggal Acara</label>
              <input type="date" name="event_date" id="event_date" class="form-control"
                     value="<?= esc($ld['event_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="event_time">Waktu Acara</label>
              <input type="text" name="event_time" id="event_time" class="form-control"
                     value="<?= esc($ld['event_time'] ?? '') ?>" placeholder="09.00 - Selesai">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="event_venue">Tempat Acara</label>
              <input type="text" name="event_venue" id="event_venue" class="form-control"
                     value="<?= esc($ld['event_venue'] ?? '') ?>" placeholder="Aula Pertemuan Sekolah">
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'keterangan_kjp'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Keterangan KJP / Bansos</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="alamat_domisili">Alamat Domisili Siswa</label>
              <input type="text" name="alamat_domisili" id="alamat_domisili" class="form-control"
                     value="<?= esc($ld['alamat_domisili'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="lampiran_keterangan">Lampiran Tambahan</label>
              <input type="text" name="lampiran_keterangan" id="lampiran_keterangan" class="form-control"
                     value="<?= esc($ld['lampiran'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" for="keperluan_detail">Keperluan / Keterangan</label>
              <textarea name="keperluan_detail" id="keperluan_detail" class="form-control" rows="2"><?= esc($ld['keperluan_detail'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

      <?php elseif ($letter['letter_type'] === 'keterangan_mutasi_masuk'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Detail Mutasi Masuk</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="sekolah_asal">Sekolah Asal</label>
              <input type="text" name="sekolah_asal" id="sekolah_asal" class="form-control"
                     value="<?= esc($ld['sekolah_asal'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="alamat_sekolah_asal">Alamat Sekolah Asal</label>
              <input type="text" name="alamat_sekolah_asal" id="alamat_sekolah_asal" class="form-control"
                     value="<?= esc($ld['alamat_sekolah_asal'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="kelas_diterima">Diterima di Kelas</label>
              <input type="text" name="kelas_diterima" id="kelas_diterima" class="form-control"
                     value="<?= esc($ld['kelas_diterima'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="semester">Semester</label>
              <input type="text" name="semester" id="semester" class="form-control"
                     value="<?= esc($ld['semester'] ?? 'Ganjil') ?>">
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div>

    <!-- Kolom Kanan: Summary & Actions -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top:80px;">
        <div class="card-header bg-light fw-semibold py-3 border-bottom">
          <i class="bi bi-info-circle me-2 text-primary"></i>Status &amp; Verifikasi
        </div>
        <div class="card-body">
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <small class="text-muted">Nomor Surat</small>
            <span class="fw-bold text-dark font-monospace" style="font-size:0.85rem;"><?= esc($letter['letter_number']) ?></span>
          </div>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <small class="text-muted">Jenis Naskah</small>
            <span class="badge bg-secondary-subtle text-secondary border"><?= esc($typeName) ?></span>
          </div>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <small class="text-muted">Tanda Tangan</small>
            <span class="fw-semibold small"><?= esc($principal_name) ?></span>
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">NIP</small>
            <span class="font-monospace small"><?= esc($principal_nip) ?></span>
          </div>

          <hr class="my-3">

          <button type="button" class="btn btn-outline-info w-100 mb-2 rounded-pill" id="btn-preview-surat">
            <i class="bi bi-eye me-2"></i>Preview Hasil Cetak
          </button>
          
          <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-xs" id="btn-submit-surat">
            <i class="bi bi-check2-circle me-2"></i>Simpan &amp; Regenerate PDF
          </button>
          
          <a href="<?= base_url('admin/surat-keluar/detail/' . $letter['id']) ?>" class="btn btn-outline-secondary w-100 mt-2 rounded-pill">
            Batal
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Modal Bank Template Surat Dinas -->
<div class="modal fade" id="modal-bank-template" tabindex="-1" aria-labelledby="modalBankTemplateLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom py-3">
        <div>
          <h5 class="modal-title fw-bold" id="modalBankTemplateLabel">
            <i class="bi bi-collection text-primary me-2"></i>Bank Template Surat Dinas
          </h5>
          <small class="text-muted">Pilih template standar naskah dinas sekolah untuk langsung diterapkan ke editor</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3 bg-light">
        <!-- Search & Filter Controls -->
        <div class="row g-2 mb-3">
          <div class="col-md-7">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" id="search-template-input" class="form-control" placeholder="Cari nama template surat...">
            </div>
          </div>
          <div class="col-md-5">
            <div class="btn-group btn-group-sm w-100" role="group">
              <button type="button" class="btn btn-primary btn-template-filter active" data-category="all">Semua</button>
              <button type="button" class="btn btn-outline-secondary btn-template-filter" data-category="siswa">Siswa</button>
              <button type="button" class="btn btn-outline-secondary btn-template-filter" data-category="guru">Guru</button>
              <button type="button" class="btn btn-outline-secondary btn-template-filter" data-category="undangan">Undangan</button>
            </div>
          </div>
        </div>

        <!-- Template Cards Grid -->
        <div class="row g-3" id="template-card-grid">
          <?php if (!empty($templates)): ?>
            <?php foreach ($templates as $tpl): ?>
              <div class="col-md-6 template-item" data-category="<?= esc($tpl['category']) ?>" data-title="<?= esc(strtolower($tpl['title'])) ?>">
                <div class="card h-100 border-0 shadow-xs rounded-3 p-3 bg-white template-card-hover transition-all">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge rounded-pill <?= $tpl['category'] === 'siswa' ? 'bg-primary-subtle text-primary' : ($tpl['category'] === 'guru' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark') ?>" style="font-size:0.7rem;">
                      <?= ucfirst($tpl['category']) ?>
                    </span>
                    <?php if ($tpl['is_system']): ?>
                      <span class="badge rounded-pill bg-light text-muted border" style="font-size:0.68rem;"><i class="bi bi-shield-check me-1"></i>Bawaan Sistem</span>
                    <?php else: ?>
                      <span class="badge rounded-pill bg-info-subtle text-info border" style="font-size:0.68rem;"><i class="bi bi-person-check me-1"></i>Kustom Sekolah</span>
                    <?php endif; ?>
                  </div>
                  <h6 class="fw-bold mb-1 text-dark" style="font-size:0.9rem;"><?= esc($tpl['title']) ?></h6>
                  <p class="text-muted small mb-3" style="font-size:0.75rem;">
                    Perihal: <em><?= esc($tpl['default_subject']) ?></em><br>
                    Header: <?= $tpl['header_style'] === 'tengah' ? '<i class="bi bi-align-center me-1"></i>Judul Tengah' : '<i class="bi bi-columns-gap me-1"></i>Kiri-Kanan' ?>
                  </p>
                  <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                    <small class="text-muted" style="font-size:0.7rem;">
                      <i class="bi bi-check2-circle text-success me-1"></i>Format Resmi
                    </small>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 btn-apply-template"
                            data-id="<?= $tpl['id'] ?>"
                            data-title="<?= esc($tpl['title']) ?>"
                            data-subject="<?= esc($tpl['default_subject']) ?>"
                            data-style="<?= esc($tpl['header_style']) ?>"
                            data-body="<?= esc($tpl['body_html']) ?>">
                      <i class="bi bi-check-lg me-1"></i>Terapkan
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center py-4 text-muted">
              <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
              Belum ada template surat yang tersedia.
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Simpan Template Baru -->
<div class="modal fade" id="modal-save-template" tabindex="-1" aria-labelledby="modalSaveTemplateLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom py-3">
        <div>
          <h5 class="modal-title fw-bold" id="modalSaveTemplateLabel">
            <i class="bi bi-bookmark-plus text-primary me-2"></i>Simpan Naskah Sebagai Template Baru
          </h5>
          <small class="text-muted">Simpan draf teks di editor saat ini agar dapat digunakan kembali oleh staf lain</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form id="form-save-template">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Judul Template <span class="text-danger">*</span></label>
            <input type="text" id="tpl-save-title" class="form-control form-control-sm" placeholder="cth: Surat Rekomendasi Beasiswa Prestasi" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Kategori Template <span class="text-danger">*</span></label>
            <select id="tpl-save-category" class="form-select form-select-sm">
              <option value="siswa">Kesiswaan (Siswa)</option>
              <option value="guru">Kepegawaian (Guru / Tendik)</option>
              <option value="undangan">Undangan / Wali Murid</option>
              <option value="umum">Umum</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Perihal Default <span class="text-danger">*</span></label>
            <input type="text" id="tpl-save-subject" class="form-control form-control-sm" placeholder="cth: SURAT REKOMENDASI PRESTASI" required>
          </div>
          <div class="alert alert-info py-2 small mb-0" style="font-size:0.75rem;">
            <i class="bi bi-info-circle me-1"></i>Isi surat yang sedang aktif di CKEditor beserta format tata letaknya akan disimpan ke database template sekolah.
          </div>
        </form>
      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="btn-submit-save-template">
          <i class="bi bi-check-circle me-1"></i>Simpan Template
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Preview Surat (Realistic Studio A4 Desk) -->
<div class="modal fade" id="modal-preview-surat" tabindex="-1" aria-labelledby="modalPreviewLabel">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
            <i class="bi bi-eye fs-6"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark fs-6 mb-0" id="modalPreviewLabel">Simulasi Pembaruan Naskah Cetak</h5>
            <small class="text-muted">Pratinjau lembar A4 sebelum pembaruan berkas PDF disimpan</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle px-3 py-1 font-monospace" style="font-size:0.72rem;">
            <i class="bi bi-shield-exclamation me-1 text-warning"></i>DRAF PREVIEW
          </span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body p-4" style="background:#0f172a;">
        <div style="max-width:794px; margin:0 auto; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.2), 0 20px 40px -15px rgba(0,0,0,0.5); padding:44px 54px; min-height:480px; font-family:'Times New Roman',Times,serif; font-size:14px; line-height:1.6; border-radius:3px;" id="preview-letter-paper">
          <div id="preview-kop-area"></div>
          <div id="preview-header-area"></div>
          <div id="preview-body-area"></div>
          <div id="preview-footer-note" style="margin-top:40px; border-top:1px dashed #e2e8f0; padding-top:16px; text-align:center;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-light border text-muted" style="font-size:0.75rem; font-family:Arial,sans-serif;">
              <i class="bi bi-shield-check text-success"></i>
              <span>Dokumen final akan memuat QR Code verifikasi publik dan Tanda Tangan resmi Kepala Sekolah.</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between bg-white border-top py-3 px-4">
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor surat dan QR verification UUID tetap dipertahankan.</small>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-xs" data-bs-dismiss="modal" id="btn-from-preview-submit">
            <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>
<script>
const LETTER_TYPE       = <?= json_encode($letter['letter_type']) ?>;
const CURRENT_LETTER_NO = <?= json_encode($letter['letter_number']) ?>;
const KOP_BASE64        = <?= json_encode($kop_base64 ?? null) ?>;
const SCHOOL_NAME       = <?= json_encode($school['name'] ?? '') ?>;
const SCHOOL_ADDRESS    = <?= json_encode($school['address'] ?? '') ?>;
const PRINCIPAL_NAME    = <?= json_encode($principal_name ?? '') ?>;
const PRINCIPAL_NIP     = <?= json_encode($principal_nip ?? '') ?>;
const ACTIVE_YEAR       = <?= json_encode($active_year ?? '') ?>;

let editorInstance = null;

// Helper: Escape HTML
function escHtml(str) {
  if (!str) return '-';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Helper: Format Tanggal Indonesia
function formatIndoDate(dateStr) {
  if (!dateStr) return '-';
  const MONTHS = ['Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];
  const d = new Date(dateStr + 'T00:00:00');
  if (isNaN(d.getTime())) return dateStr;
  return d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
}

function formatRecipientLinesForPreview(str) {
  if (!str || str === '-') return '-';
  const parts = str.split(/\s*\|\s*/).filter(Boolean);
  if (parts.length === 0) return '-';
  return parts.map(line => escHtml(line)).join('<br>');
}

// Toast Notification
function showToastNotification(msg, type = 'success') {
  let toastContainer = document.getElementById('sikap-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'sikap-toast-container';
    toastContainer.style.position = 'fixed';
    toastContainer.style.bottom = '24px';
    toastContainer.style.right = '24px';
    toastContainer.style.zIndex = '99999';
    toastContainer.style.display = 'flex';
    toastContainer.style.flexDirection = 'column';
    toastContainer.style.gap = '8px';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bgClass = type === 'success' ? 'bg-dark text-white' : (type === 'info' ? 'bg-primary text-white' : 'bg-danger text-white');
  toast.className = `p-3 rounded-3 shadow-lg ${bgClass} d-flex align-items-center gap-2`;
  toast.style.fontSize = '0.85rem';
  toast.style.minWidth = '280px';
  toast.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
  const icon = type === 'success' ? 'check-circle-fill text-success' : (type === 'info' ? 'info-circle-fill text-info' : 'exclamation-circle-fill text-warning');
  toast.innerHTML = `<i class="bi bi-${icon} fs-5"></i> <div>${msg}</div>`;

  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(12px)';
    setTimeout(() => toast.remove(), 350);
  }, 3800);
}

// Inisialisasi CKEditor jika surat_custom
if (LETTER_TYPE === 'surat_custom') {
  CKEDITOR.ClassicEditor
    .create(document.querySelector('#editor-surat-custom'), {
      toolbar: {
        items: [
          'undo', 'redo', '|',
          'sourceEditing', '|',
          'heading', '|',
          'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
          'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
          'alignment', '|',
          'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
          'insertTable', 'blockQuote', 'specialCharacters', 'horizontalLine', 'pageBreak', '|',
          'link', 'removeFormat'
        ],
        shouldNotGroupWhenFull: true
      },
      list: {
        properties: {
          styles: true,
          startIndex: true,
          reversed: true
        }
      },
      table: {
        contentToolbar: [
          'tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'
        ]
      },
      heading: {
        options: [
          { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
          { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
        ]
      },
      removePlugins: [
        'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit', 'CKBoxImageEditEditing',
        'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage', 'Comments', 'CommentsRepository',
        'RealTimeCollaborativeComments', 'TrackChanges', 'TrackChangesEditing', 'TrackChangesData',
        'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'RealTimeCollaborativeRevisionHistory',
        'PresenceList', 'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType', 'ChemType',
        'Mentions', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents',
        'PasteFromOfficeEnhanced', 'CaseChange', 'WideSidebar', 'ExportPdf', 'ExportWord'
      ],
      fontSize: {
        options: [ 9, 10, 11, 12, 13, 14, 'default', 16, 18, 20, 22, 24, 28, 32 ],
        supportAllValues: true
      },
      fontFamily: {
        options: [
          'default',
          'Arial, Helvetica, sans-serif',
          'Courier New, Courier, monospace',
          'Georgia, serif',
          'Lucida Sans Unicode, Lucida Grande, sans-serif',
          'Tahoma, Geneva, sans-serif',
          'Times New Roman, Times, serif',
          'Trebuchet MS, Helvetica, sans-serif',
          'Verdana, Geneva, sans-serif'
        ],
        supportAllValues: true
      },
      fontColor: {
        columns: 5,
        documentColors: 10
      },
      fontBackgroundColor: {
        columns: 5,
        documentColors: 10
      },
      link: {
        decorators: {
          openInNewTab: {
            mode: 'manual',
            label: 'Buka di tab baru',
            attributes: {
              target: '_blank',
              rel: 'noopener noreferrer'
            }
          }
        }
      },
      htmlSupport: {
        allow: [{ name: /.*/, attributes: true, classes: true, styles: true }]
      }
    })
    .then(editor => {
      editorInstance = editor;
      editor.editing.view.change(writer => {
        writer.setStyle('min-height', '340px', editor.editing.view.document.getRoot());
      });

      // Track cursor to reflect paragraph margin
      editor.model.document.selection.on('change', () => {
        const pos = editor.model.document.selection.getFirstPosition();
        if (!pos) return;
        const block = pos.parent;
        const viewEl = editor.editing.mapper.toViewElement(block);
        if (!viewEl) return;
        const marginVal = viewEl.getStyle('margin-bottom') || '';
        const numMatch  = marginVal.match(/(\d+)/);
        const curMargin = numMatch ? numMatch[1] : '12';
        document.querySelectorAll('.btn-spacing').forEach(btn => {
          btn.classList.toggle('active', btn.dataset.margin === curMargin);
        });
      });
    })
    .catch(error => console.error(error));

  // Paragraph Spacing Handlers
  $(document).on('click', '.btn-spacing', function() {
    if (!editorInstance) return;
    const marginPx = $(this).data('margin') + 'px';

    editorInstance.model.change(writer => {
      const selection = editorInstance.model.document.selection;
      const ranges    = [...selection.getRanges()];
      const blocks    = new Set();

      for (const range of ranges) {
        for (const item of range.getItems()) {
          if (item.is('element')) blocks.add(item);
        }
        const start = range.start.parent;
        const end   = range.end.parent;
        if (start.is('element')) blocks.add(start);
        if (end.is('element'))   blocks.add(end);
      }

      if (blocks.size === 0) {
        const pos = selection.getFirstPosition();
        if (pos && pos.parent.is('element')) blocks.add(pos.parent);
      }

      blocks.forEach(block => {
        if ($(this).data('margin') === 0 || $(this).data('margin') === '0') {
          writer.removeAttribute('htmlAttributes', block);
          writer.setAttribute('htmlAttributes', {
            styles: { 'margin-bottom': '0px', 'margin-top': '0px' }
          }, block);
        } else {
          writer.setAttribute('htmlAttributes', {
            styles: { 'margin-bottom': marginPx, 'margin-top': '0px' }
          }, block);
        }
      });
    });

    document.querySelectorAll('.btn-spacing').forEach(btn => btn.classList.remove('active'));
    this.classList.add('active');
  });

  // Placeholder Insert
  $(document).on('click', '.placeholder-btn', function(e) {
    e.preventDefault();
    const placeholder = $(this).data('placeholder');
    if (editorInstance) {
      if (editorInstance.plugins.has('SourceEditing') && editorInstance.plugins.get('SourceEditing').isSourceEditingMode) {
        alert('Kembalikan ke mode visual terlebih dahulu (klik tombol </> Source pada toolbar) untuk menyisipkan variabel.');
        return;
      }
      editorInstance.model.change(writer => {
        const insertPosition = editorInstance.model.document.selection.getFirstPosition();
        if (insertPosition) {
          writer.insertText(placeholder, insertPosition);
        }
      });
      editorInstance.editing.view.focus();
    }
  });

  // Template Modal Filter
  $('.btn-template-filter').on('click', function() {
    $('.btn-template-filter').removeClass('active btn-primary').addClass('btn-outline-secondary');
    $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
    const cat = $(this).data('category');
    filterTemplates(cat, $('#search-template-input').val().toLowerCase().trim());
  });

  $('#search-template-input').on('input', function() {
    const cat = $('.btn-template-filter.active').data('category') || 'all';
    filterTemplates(cat, $(this).val().toLowerCase().trim());
  });

  function filterTemplates(cat, search) {
    let visibleCount = 0;
    $('.template-item').each(function() {
      const itemCat = $(this).data('category');
      const itemTitle = $(this).data('title') || '';
      const matchCat = (cat === 'all' || itemCat === cat);
      const matchSearch = (!search || itemTitle.includes(search));
      if (matchCat && matchSearch) {
        $(this).show();
        visibleCount++;
      } else {
        $(this).hide();
      }
    });
  }

  // Apply Template
  $(document).on('click', '.btn-apply-template', function() {
    const id      = $(this).data('id');
    const title   = $(this).data('title');
    const subject = $(this).data('subject');
    const style   = $(this).data('style') || 'tengah';
    const body    = $(this).data('body') || '';

    if (editorInstance && editorInstance.getData().trim().length > 20) {
      if (!confirm(`Terapkan template "${title}"?\nIsi editor saat ini akan ditimpa dengan draf template.`)) {
        return;
      }
    }

    if (subject) $('#subject').val(subject);
    $('#selected_template_id').val(id);
    $(`input[name="header_style"][value="${style}"]`).prop('checked', true);

    if (editorInstance) {
      editorInstance.setData(body);
    }

    const modal = bootstrap.Modal.getInstance(document.getElementById('modal-bank-template'));
    if (modal) modal.hide();
    showToastNotification(`Template <strong>${escHtml(title)}</strong> berhasil diterapkan!`, 'success');
  });

  // Save Template
  $('#btn-submit-save-template').on('click', function() {
    const title    = $('#tpl-save-title').val().trim();
    const category = $('#tpl-save-category').val();
    const subject  = $('#tpl-save-subject').val().trim();
    const style    = $('input[name="header_style"]:checked').val() || 'tengah';
    const body     = editorInstance ? editorInstance.getData() : '';

    if (!title) { alert('Judul template wajib diisi.'); return; }
    if (!subject) { alert('Perihal default wajib diisi.'); return; }
    if (!body.trim()) { alert('Isi surat di editor masih kosong.'); return; }

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');

    $.ajax({
      url: '<?= base_url('admin/surat-keluar/template/save') ?>',
      type: 'POST',
      data: {
        title: title,
        category: category,
        header_style: style,
        default_subject: subject,
        body_html: body,
        [typeof CSRF_NAME !== 'undefined' ? CSRF_NAME : '<?= csrf_token() ?>']: (typeof CSRF_HASH !== 'undefined' ? CSRF_HASH : '<?= csrf_hash() ?>')
      },
      dataType: 'json',
      success: function(res) {
        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan Template');
        if (res.status === 'success') {
          const saveModal = bootstrap.Modal.getInstance(document.getElementById('modal-save-template'));
          if (saveModal) saveModal.hide();
          $('#form-save-template')[0].reset();
          showToastNotification(res.message, 'success');
        } else {
          alert(res.message || 'Gagal menyimpan template.');
        }
      },
      error: function(xhr) {
        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan Template');
        alert('Terjadi kendala saat menyimpan template.');
      }
    });
  });
}

// Sinkronisasi data CKEditor sebelum submit form
$('#form-surat-keluar').on('submit', function(e) {
  if (LETTER_TYPE === 'surat_custom' && editorInstance) {
    const bodyData = editorInstance.getData();
    $('#body_html_hidden').val(bodyData);
    if (!bodyData.trim()) {
      alert('Isi surat (body) wajib diisi untuk Surat Custom!');
      e.preventDefault();
      return false;
    }
  }
});

// Preview Surat (Realistic A4)
$('#btn-preview-surat').on('click', function() {
  const recipientName = $('#recipient_name').val() || '-';
  const issuedAt     = $('#issued_at').val();
  const subject      = $('#subject').val() || '-';
  const sifat        = $('#sifat').val() || 'Biasa';
  const displayLetterNo = CURRENT_LETTER_NO;
  const tanggalSurat = formatIndoDate(issuedAt);

  // Render KOP (Respect use_kop switch)
  const useKop = $('#use_kop').is(':checked');
  let kopHtml = '';
  if (useKop) {
    if (KOP_BASE64) {
      kopHtml = `<div style="width:100%;text-align:center;margin-bottom:4px;">
                   <img src="${KOP_BASE64}" style="width:100%;height:auto;display:block;margin:0 auto;">
                 </div>
                 <div style="border-top:2px solid #000;margin-top:5px;margin-bottom:20px;"></div>`;
    } else {
      kopHtml = `<div style="border:1px dashed #bbb;padding:15px;text-align:center;color:#aaa;margin-bottom:20px;font-family:Arial,sans-serif;font-size:12px;">
                   [KOP Surat — ${escHtml(SCHOOL_NAME)}]<br><small>KOP belum di-upload di pengaturan sekolah</small>
                 </div>`;
    }
  } else {
    kopHtml = `
      <div style="height:3.5cm; border:1px dashed #cbd5e1; background:#f8fafc; border-radius:6px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#64748b; font-size:12px; margin-bottom:24px; font-family:Arial,sans-serif; padding:12px;">
        <div style="font-weight:bold; display:flex; align-items:center; margin-bottom:4px;"><i class="bi bi-printer me-2 fs-5"></i>Margin Atas 3.5 cm (Kertas Kop Cetak Fisik)</div>
        <div style="font-size:11px; opacity:0.85;">KOP digital tidak dicetak agar pas dengan blanko kop naskah dinas resmi sekolah.</div>
      </div>`;
  }

  let headerHtml = '';
  let bodyHtml = '';

  if (LETTER_TYPE === 'surat_custom') {
    if (!editorInstance) {
      alert('Editor belum siap.');
      return;
    }
    const rawBodyHtml = editorInstance.getData();
    const headerStyle = $('input[name="header_style"]:checked').val() || 'tengah';

    let recNameForPlaceholder = recipientName;
    if (recNameForPlaceholder.includes('|')) {
      recNameForPlaceholder = recNameForPlaceholder.split(/\s*\|\s*/).filter(Boolean).join(', ');
    }

    // Extended placeholders
    const nisn       = $('#f_nisn').val() || '-';
    const nis        = $('#f_nis').val() || '-';
    const nik        = $('#f_nik').val() || '-';
    const gender     = $('#f_gender').val() || '-';
    const kelas      = $('#f_kelas').val() || '-';
    const ttl        = $('#f_ttl').val() || '-';
    const alamat     = $('#f_alamat').val() || '-';
    const namaAyah   = $('#f_nama_ayah').val() || '-';
    const pekAyah    = $('#f_pekerjaan_ayah').val() || '-';
    const hasilAyah  = $('#f_penghasilan_ayah').val() || '-';
    const namaIbu    = $('#f_nama_ibu').val() || '-';
    const pekIbu     = $('#f_pekerjaan_ibu').val() || '-';
    const namaWali   = $('#f_nama_wali').val() || '-';
    const alamatOrtu = $('#f_alamat_ortu').val() || alamat;
    const nip        = $('#f_nip').val() || '-';
    const nuptk      = $('#f_nuptk').val() || '-';
    const jabatan    = $('#f_jabatan').val() || '-';
    const pangkatGol = $('#f_pangkat_golongan').val() || '-';
    const statusPeg  = $('#f_status_kepegawaian').val() || '-';

    let processedBody = rawBodyHtml;
    const map = {
      '{nama_penerima}':        recNameForPlaceholder,
      '{nisn}':                 nisn,
      '{nis}':                  nis,
      '{nik}':                  nik,
      '{jenis_kelamin}':        gender,
      '{kelas}':                kelas,
      '{ttl}':                  ttl,
      '{alamat}':               alamat,
      '{nama_ayah}':            namaAyah,
      '{pekerjaan_ayah}':       pekAyah,
      '{penghasilan_ayah}':     hasilAyah,
      '{nama_ibu}':             namaIbu,
      '{pekerjaan_ibu}':        pekIbu,
      '{nama_wali}':            namaWali,
      '{alamat_ortu}':          alamatOrtu,
      '{nip}':                  nip,
      '{nuptk}':                nuptk,
      '{jabatan}':              jabatan,
      '{pangkat_golongan}':     pangkatGol,
      '{status_kepegawaian}':   statusPeg,
      '{nomor_surat}':          displayLetterNo,
      '{tanggal_surat}':        tanggalSurat,
      '{tahun_pelajaran}':      ACTIVE_YEAR || '-',
      '{kepala_sekolah}':       PRINCIPAL_NAME || '-',
      '{nip_kepala_sekolah}':   PRINCIPAL_NIP  || '-',
      '{nama_sekolah}':         SCHOOL_NAME    || '-',
      '{alamat_sekolah}':       SCHOOL_ADDRESS || '-',
      '{sifat}':                 sifat,
    };
    for (const [key, val] of Object.entries(map)) {
      processedBody = processedBody.split(key).join(val);
    }

    if (headerStyle === 'tengah') {
      headerHtml = `
        <div style="text-align:center;margin-bottom:24px;">
          <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">${escHtml(subject)}</div>
          <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
        </div>`;
    } else {
      headerHtml = `
        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:24px;">
          <tr>
            <td style="width:55%;vertical-align:top;">
              <table style="width:100%;border-collapse:collapse;">
                <tr><td style="width:70px;padding:2px 0;">Nomor</td><td style="width:14px;text-align:center;padding:2px 0;">:</td><td style="padding:2px 0;">${escHtml(displayLetterNo)}</td></tr>
                <tr><td style="padding:2px 0;">Sifat</td><td style="text-align:center;padding:2px 0;">:</td><td style="padding:2px 0;">${escHtml(sifat)}</td></tr>
                <tr><td style="padding:2px 0;">Hal</td><td style="text-align:center;padding:2px 0;">:</td><td style="padding:2px 0;"><strong>${escHtml(subject)}</strong></td></tr>
              </table>
            </td>
            <td style="width:45%;vertical-align:top;padding-left:20px;">
              <div style="line-height:1.6;">
                ${escHtml(tanggalSurat)}<br><br>
                Kepada<br>
                Yth. ${formatRecipientLinesForPreview(recipientName)}<br>
                di &nbsp;-&nbsp; <strong>Tempat</strong>
              </div>
            </td>
          </tr>
        </table>`;
    }
    bodyHtml = processedBody;

  } else {
    // Default preview fallback for standard letter types
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;">${escHtml(subject)}</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;
    bodyHtml = `
      <p style="margin:12px 0; line-height:1.6;">
        Yang bertanda tangan di bawah ini Kepala ${escHtml(SCHOOL_NAME)} menerangkan bahwa :
      </p>
      <table style="width:100%; border-collapse:collapse; margin:12px 0;">
        <tr><td style="width:140px; padding:3px 0;">Nama Lengkap</td><td style="width:14px; text-align:center;">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
        <tr><td style="padding:3px 0;">Sifat Surat</td><td style="text-align:center;">:</td><td>${escHtml(sifat)}</td></tr>
      </table>
      <p style="margin:12px 0; line-height:1.6;">
        Demikian surat ini dibuat agar dapat dipergunakan sebagaimana mestinya.
      </p>`;
  }

  // TTD & QR Code simulation
  const ttdHtml = `
    <table style="width:100%; border-collapse:collapse; margin-top:35px; page-break-inside:avoid;">
      <tr>
        <td style="width:50%; vertical-align:bottom; text-align:left;">
          <div style="display:inline-block; border:1px dashed #ced4da; padding:8px 12px; color:#888; font-size:11px; background:#fafafa;">
            [QR Code Verifikasi Resmi]
          </div>
        </td>
        <td style="width:50%; vertical-align:bottom; text-align:center; font-size:14px;">
          ${escHtml(tanggalSurat)}<br>
          Kepala ${escHtml(SCHOOL_NAME)}<br>
          <div style="margin-top: 50px; border-top: 1px solid #000; width: 85%; margin-left:auto; margin-right:auto;"></div>
          <strong>${escHtml(PRINCIPAL_NAME)}</strong><br>
          NIP. ${escHtml(PRINCIPAL_NIP)}
        </td>
      </tr>
    </table>`;

  const bodyStyle = `
    <style>
      #preview-body-area table { width:100% !important; border-collapse:collapse !important; margin:12px 0 !important; }
      #preview-body-area table th, #preview-body-area table td { border:1px solid #000 !important; padding:6px 8px !important; font-size:14px; }
      #preview-body-area table th { background:#f2f2f2 !important; font-weight:bold; }
      #preview-body-area p { margin:8px 0; line-height:1.6; text-align:justify; }
      #preview-body-area ul, #preview-body-area ol { margin:8px 0; padding-left:20px; }
      #preview-body-area li { margin-bottom:4px; }
      #preview-body-area .page-break {
        border-top: 2px dashed #94a3b8;
        margin: 24px 0;
        position: relative;
        text-align: center;
      }
      #preview-body-area .page-break::after {
        content: "--- Batas Halaman Cetak (Page Break) ---";
        font-size: 11px;
        color: #64748b;
        background: #fff;
        padding: 0 10px;
        position: relative;
        top: -9px;
      }
      #preview-body-area sub { vertical-align: sub; font-size: 0.8em; }
      #preview-body-area sup { vertical-align: super; font-size: 0.8em; }
      #preview-body-area .todo-list { list-style-type: none; padding-left: 5px; }
      #preview-body-area .todo-list li { margin-bottom: 4px; }
    </style>`;

  $('#preview-kop-area').html(kopHtml);
  $('#preview-header-area').html(headerHtml);
  $('#preview-body-area').html(bodyStyle + bodyHtml + ttdHtml);

  const previewModal = new bootstrap.Modal(document.getElementById('modal-preview-surat'));
  previewModal.show();
});

$('#btn-from-preview-submit').on('click', function() {
  $('#btn-submit-surat').trigger('click');
});
</script>
<?= $this->endSection() ?>

