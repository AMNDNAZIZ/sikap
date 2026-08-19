<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<style>
  .autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1050;
    display: none;
    width: 100%;
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 250px;
    overflow-y: auto;
  }
  .autocomplete-suggestions .list-group-item-action {
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .autocomplete-suggestions .list-group-item-action:hover {
    background-color: #f8f9fa;
    color: #0b5ed7;
  }
  /* Prevent clipping of autocomplete suggestions inside tables */
  .table-responsive, 
  #tbl-recipients, 
  #tbl-recipients td, 
  #tbl-recipients th, 
  #tbl-recipients tr {
    overflow: visible !important;
  }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-envelope-plus me-2 text-primary"></i>Buat Surat Keluar</h2>
    <small class="text-muted">Pilih jenis surat dan isi field yang diperlukan</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('admin/surat-keluar/create-eksternal') ?>" class="btn btn-outline-info btn-sm">
      <i class="bi bi-upload me-1"></i>Upload PDF Eksternal
    </a>
    <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>
</div>

<?php if (session()->getFlashdata('errors') || isset($errors)): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <strong>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ((session()->getFlashdata('errors') ?? $errors ?? []) as $err): ?>
        <li><?= esc($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('admin/surat-keluar/store') ?>" id="form-surat-keluar">
  <?= csrf_field() ?>

  <div class="row g-4">
    <!-- Kolom Kiri: Form Utama -->
    <div class="col-lg-8">

      <!-- Pilih Jenis Surat -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-semibold py-2">
          <i class="bi bi-list-ul me-2"></i>1. Pilih Jenis Surat
        </div>
        <div class="card-body">
          <div class="row row-cols-2 row-cols-md-3 g-2" id="letter-type-selector">
            <?php foreach ($letter_types as $code => $label): ?>
              <div class="col">
                <input type="radio" class="btn-check" name="letter_type" id="type_<?= $code ?>"
                       value="<?= $code ?>" autocomplete="off"
                       <?= old('letter_type') === $code ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary w-100 text-start" for="type_<?= $code ?>" style="font-size:0.82rem;">
                  <?= $label ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <div id="no-type-warning" class="text-danger mt-2 small" style="display:none;">
            ⚠️ Pilih jenis surat terlebih dahulu.
          </div>
        </div>
      </div>

      <!-- Field Umum (selalu tampil) -->
      <div class="card border-0 shadow-sm mb-4" id="section-common" style="display:none;">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-card-text me-2"></i>2. Informasi Umum Surat
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="issued_at">Tanggal Surat <span class="text-danger">*</span></label>
              <input type="date" name="issued_at" id="issued_at" class="form-control"
                     value="<?= old('issued_at', date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="sifat">Sifat Surat</label>
              <select name="sifat" id="sifat" class="form-select">
                <option value="Biasa">Biasa</option>
                <option value="Penting">Penting</option>
                <option value="Segera">Segera</option>
                <option value="Rahasia">Rahasia</option>
              </select>
            </div>

            <!-- Penomoran Surat (Auto vs Manual) -->
            <div class="col-12">
              <label class="form-label fw-semibold">Penomoran Surat <span class="text-danger">*</span></label>
              <div class="d-flex flex-wrap gap-3 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="number_mode" id="num_mode_auto" value="auto" checked>
                  <label class="form-check-label small fw-semibold" for="num_mode_auto">
                    <i class="bi bi-hash me-1"></i>Otomatis / Teruskan (Estimasi: <span class="font-monospace text-primary"><?= esc($next_letter_number ?? 'Auto') ?></span>)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="number_mode" id="num_mode_manual" value="manual">
                  <label class="form-check-label small fw-semibold" for="num_mode_manual">
                    <i class="bi bi-pencil me-1"></i>Input Manual
                  </label>
                </div>
              </div>
              <div id="manual-number-wrapper" style="display:none;" class="mt-2">
                <input type="text" name="nomor_surat_manual" id="nomor_surat_manual" class="form-control font-monospace"
                       placeholder="cth: 045.A/PK.01.01/2026" value="<?= old('nomor_surat_manual') ?>">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Ketik nomor surat manual jika ingin menggunakan nomor khusus.</small>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="subject">Perihal / Keperluan <span class="text-danger">*</span></label>
              <input type="text" name="subject" id="subject" class="form-control"
                     placeholder="Keterangan singkat isi surat..." value="<?= old('subject') ?>" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Penerima -->
      <div class="card border-0 shadow-sm mb-4" id="section-recipient" style="display:none;">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-person me-2"></i>3. Penerima Surat
        </div>
        <div class="card-body">
          <input type="hidden" name="recipient_type" id="recipient_type" value="siswa">
          <input type="hidden" name="recipient_ref_id" id="recipient_ref_id">

          <!-- Autocomplete Siswa -->
          <div id="recipient-siswa-section" class="position-relative">
            <label class="form-label fw-semibold" for="recipient_search_siswa">Nama Siswa <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="recipient_search_siswa" class="form-control" placeholder="Ketik nama atau NISN siswa..." autocomplete="off">
            </div>
            <div id="siswa-autocomplete-result" class="autocomplete-suggestions list-group"></div>
            <input type="hidden" name="recipient_name" id="recipient_name">
            <input type="hidden" name="alamat_domisili" id="f_alamat">

            <!-- Detail Data Siswa (Visible & Editable) -->
            <div id="siswa-detail-fields" class="mt-3 p-3 bg-light rounded border" style="display:none;">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-secondary" style="font-size:0.82rem;"><i class="bi bi-card-text me-1"></i>Detail Data Siswa</h6>
                <small class="text-muted" style="font-size: 0.72rem;">(Auto-filled, dapat diedit jika manual)</small>
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">NISN</label>
                  <input type="text" name="nisn" id="f_nisn" class="form-control form-control-sm" placeholder="NISN Siswa">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">NIK</label>
                  <input type="text" name="nik" id="f_nik" class="form-control form-control-sm" placeholder="NIK Siswa">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">Tempat, Tanggal Lahir</label>
                  <input type="text" name="ttl" id="f_ttl" class="form-control form-control-sm" placeholder="Tempat, YYYY-MM-DD">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">Kelas</label>
                  <input type="text" name="kelas" id="f_kelas" class="form-control form-control-sm" placeholder="Kelas Siswa">
                </div>
              </div>
            </div>
          </div>

          <!-- Autocomplete Guru -->
          <div id="recipient-guru-section" class="position-relative" style="display:none;">
            <label class="form-label fw-semibold" for="recipient_search_guru">Nama Guru <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="recipient_search_guru" class="form-control" placeholder="Ketik nama atau NIP guru..." autocomplete="off">
            </div>
            <div id="guru-autocomplete-result" class="autocomplete-suggestions list-group"></div>
            
            <!-- Detail Data Guru (Visible & Editable) -->
            <div id="guru-detail-fields" class="mt-3 p-3 bg-light rounded border" style="display:none;">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-secondary" style="font-size:0.82rem;"><i class="bi bi-card-text me-1"></i>Detail Data Guru</h6>
                <small class="text-muted" style="font-size: 0.72rem;">(Auto-filled, dapat diedit jika manual)</small>
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">NIP</label>
                  <input type="text" name="nip" id="f_nip" class="form-control form-control-sm" placeholder="NIP Guru">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1" style="font-size: 0.75rem;">Jabatan</label>
                  <input type="text" name="jabatan" id="f_jabatan" class="form-control form-control-sm" placeholder="Jabatan Guru">
                </div>
              </div>
            </div>
          </div>

          <!-- Penerima Undangan (5 opsi) -->
          <div id="recipient-undangan-section" style="display:none;">
            <label class="form-label fw-semibold mb-2"><i class="bi bi-people me-1"></i>Pilih Kategori Penerima <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-2 mb-3" id="undangan-recipient-options">
              <input type="radio" class="btn-check" name="undangan_recipient_mode" id="undangan_mode_all" value="all" autocomplete="off" checked>
              <label class="btn btn-outline-primary btn-sm" for="undangan_mode_all" style="font-size:0.82rem;">
                <i class="bi bi-people-fill me-1"></i>Seluruh Guru & Pegawai
              </label>

              <input type="radio" class="btn-check" name="undangan_recipient_mode" id="undangan_mode_guru" value="guru" autocomplete="off">
              <label class="btn btn-outline-primary btn-sm" for="undangan_mode_guru" style="font-size:0.82rem;">
                <i class="bi bi-person-badge me-1"></i>Per Guru
              </label>

              <input type="radio" class="btn-check" name="undangan_recipient_mode" id="undangan_mode_pegawai" value="pegawai" autocomplete="off">
              <label class="btn btn-outline-primary btn-sm" for="undangan_mode_pegawai" style="font-size:0.82rem;">
                <i class="bi bi-person-workspace me-1"></i>Per Pegawai
              </label>

              <input type="radio" class="btn-check" name="undangan_recipient_mode" id="undangan_mode_wali" value="wali_murid" autocomplete="off">
              <label class="btn btn-outline-primary btn-sm" for="undangan_mode_wali" style="font-size:0.82rem;">
                <i class="bi bi-mortarboard me-1"></i>Wali Murid Kelas
              </label>

              <input type="radio" class="btn-check" name="undangan_recipient_mode" id="undangan_mode_dinamis" value="dinamis" autocomplete="off">
              <label class="btn btn-outline-primary btn-sm" for="undangan_mode_dinamis" style="font-size:0.82rem;">
                <i class="bi bi-pencil-square me-1"></i>Dinamis / Manual
              </label>
            </div>

            <!-- Sub: Seluruh Guru & Pegawai -->
            <div id="undangan-sub-all" class="undangan-sub-section">
              <div class="alert alert-info py-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Penerima otomatis: <strong>Guru &amp; Tenaga Kependidikan <?= esc($school['name'] ?? 'SDN Mangga Besar 11 Pagi') ?></strong>
              </div>
            </div>

            <!-- Sub: Per Guru -->
            <div id="undangan-sub-guru" class="undangan-sub-section" style="display:none;">
              <label class="form-label fw-semibold" for="undangan_search_guru">Cari Guru <span class="text-danger">*</span></label>
              <div class="input-group position-relative">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="undangan_search_guru" class="form-control" placeholder="Ketik nama atau NIP guru..." autocomplete="off">
                <div id="undangan-guru-autocomplete" class="autocomplete-suggestions list-group"></div>
              </div>
              <div id="undangan-guru-selected" class="mt-2"></div>
            </div>

            <!-- Sub: Per Pegawai -->
            <div id="undangan-sub-pegawai" class="undangan-sub-section" style="display:none;">
              <label class="form-label fw-semibold" for="undangan_search_pegawai">Cari Pegawai / Tendik <span class="text-danger">*</span></label>
              <div class="input-group position-relative">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="undangan_search_pegawai" class="form-control" placeholder="Ketik nama atau NIP pegawai..." autocomplete="off">
                <div id="undangan-pegawai-autocomplete" class="autocomplete-suggestions list-group"></div>
              </div>
              <div id="undangan-pegawai-selected" class="mt-2"></div>
            </div>

            <!-- Sub: Wali Murid Kelas -->
            <div id="undangan-sub-wali" class="undangan-sub-section" style="display:none;">
              <label class="form-label fw-semibold">Pilih Kelas <span class="text-danger">*</span></label>
              <select id="undangan_wali_class" class="form-select">
                <option value="semua">Seluruh Kelas (Semua Wali Murid)</option>
                <?php foreach ($active_classes ?? [] as $cls): ?>
                  <option value="<?= esc($cls['name']) ?>"><?= esc($cls['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Hanya kelas yang aktif yang ditampilkan.</small>
            </div>

            <!-- Sub: Dinamis / Manual (Multi-Penerima) -->
            <div id="undangan-sub-dinamis" class="undangan-sub-section" style="display:none;">
              <label class="form-label fw-semibold">Daftar Penerima <span class="text-danger">*</span></label>
              <div id="undangan-dinamis-rows">
                <div class="input-group mb-2 undangan-dinamis-row">
                  <span class="input-group-text" style="font-size:0.82rem;">1</span>
                  <input type="text" class="form-control undangan-dinamis-input" placeholder="Ketik nama penerima..." autocomplete="off">
                  <button type="button" class="btn btn-outline-danger btn-sm btn-remove-dinamis" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-undangan-dinamis">
                <i class="bi bi-plus me-1"></i>Tambah Penerima
              </button>
              <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Gunakan format bebas. Semua penerima akan tercetak di surat.</small>
            </div>
          </div>

          <!-- Penerima Internal Legacy (tetap ada untuk kompatibilitas jenis surat lain) -->
          <div id="recipient-internal-section" style="display:none;">
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>
              Penerima otomatis: <strong>Guru &amp; Tenaga Kependidikan SDN Mangga Besar 11 Pagi</strong>
            </p>
          </div>

          <!-- Multi Guru Section (Pilih Lebih Dari 1 Guru) -->
          <div id="recipient-multi-guru-section" class="position-relative" style="display:none;">
            <label class="form-label fw-semibold" for="recipient_search_multi_guru">Cari &amp; Tambah Guru / Tendik <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="recipient_search_multi_guru" class="form-control" placeholder="Ketik nama atau NIP guru untuk menambah penerima..." autocomplete="off">
            </div>
            <div id="multi-guru-autocomplete-result" class="autocomplete-suggestions list-group"></div>
            
            <div class="mt-2 p-2 bg-light rounded border" id="multi-guru-selected-wrapper">
              <small class="fw-semibold text-secondary d-block mb-1"><i class="bi bi-people me-1"></i>Daftar Penerima Guru Ditambahkan:</small>
              <div id="multi-guru-selected-chips" class="d-flex flex-wrap gap-1">
                <span class="text-muted small fst-italic" id="multi-guru-empty-msg">Belum ada guru dipilih. Cari nama guru di atas.</span>
              </div>
            </div>
          </div>

          <!-- Multi Siswa Section (Pilih Lebih Dari 1 Siswa) -->
          <div id="recipient-multi-siswa-section" class="position-relative" style="display:none;">
            <label class="form-label fw-semibold" for="recipient_search_multi_siswa">Cari &amp; Tambah Siswa <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="recipient_search_multi_siswa" class="form-control" placeholder="Ketik nama atau NISN siswa untuk menambah penerima..." autocomplete="off">
            </div>
            <div id="multi-siswa-autocomplete-result" class="autocomplete-suggestions list-group"></div>
            
            <div class="mt-2 p-2 bg-light rounded border" id="multi-siswa-selected-wrapper">
              <small class="fw-semibold text-secondary d-block mb-1"><i class="bi bi-people me-1"></i>Daftar Penerima Siswa Ditambahkan:</small>
              <div id="multi-siswa-selected-chips" class="d-flex flex-wrap gap-1">
                <span class="text-muted small fst-italic" id="multi-siswa-empty-msg">Belum ada siswa dipilih. Cari nama siswa di atas.</span>
              </div>
            </div>
          </div>

          <!-- Penerima Eksternal / Custom Multi-Penerima -->
          <div id="recipient-eksternal-section" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label fw-semibold mb-0"><i class="bi bi-card-checklist me-1"></i>Daftar Nama Penerima <span class="text-danger">*</span></label>
              <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Dapat menambah 1 atau lebih penerima</small>
            </div>
            
            <div id="eksternal-dinamis-rows">
              <div class="input-group mb-2 eksternal-dinamis-row">
                <span class="input-group-text bg-light text-secondary fw-semibold row-idx" style="font-size:0.82rem; min-width:36px; justify-content:center;">1</span>
                <input type="text" class="form-control eksternal-dinamis-input" id="recipient_name_eksternal" placeholder="cth: Kepala Dinas Pendidikan Kota Jakarta" autocomplete="off">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-eksternal" title="Hapus"><i class="bi bi-trash"></i></button>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btn-add-eksternal-row">
              <i class="bi bi-plus-circle me-1"></i>Tambah Baris Penerima Baru
            </button>
            <small class="text-muted mt-1 d-block" style="font-size:0.75rem;"><i class="bi bi-info-circle me-1"></i>Setiap baris penerima akan otomatis dicetak rapi di bagian header "Kepada Yth." surat custom.</small>
          </div>
        </div>
      </div>

      <!-- Field Dinamis Per Jenis Surat -->
      <div id="section-dynamic" style="display:none;">

        <!-- A: Mutasi Masuk -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="keterangan_mutasi_masuk" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-arrow-left-right me-2"></i>Data Mutasi Masuk</div>
          <div class="card-body row g-3">
            <div class="col-12"><label class="form-label fw-semibold">Nama Sekolah Asal</label>
              <input type="text" name="sekolah_asal" class="form-control" placeholder="cth: SDN Srimahi 01">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Alamat Sekolah Asal</label>
              <input type="text" name="alamat_sekolah_asal" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Diterima di Kelas</label>
              <input type="text" name="kelas_diterima" class="form-control" placeholder="cth: 5">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Semester</label>
              <select name="semester" class="form-select"><option value="1">1 (Ganjil)</option><option value="2">2 (Genap)</option></select>
            </div>
          </div>
        </div>

        <!-- B: Keterangan Mengajar -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="keterangan_mengajar" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-person-badge me-2"></i>Data Guru Mengajar</div>
          <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold">NIK</label>
              <input type="text" name="nik_guru" class="form-control" maxlength="16">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Kelas Mengajar</label>
              <input type="text" name="kelas_mengajar" class="form-control" placeholder="cth: II (DUA)">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Satuan Pendidikan</label>
              <input type="text" name="satuan_pendidikan" class="form-control" value="SD NEGERI MANGGA BESAR 11 PAGI">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Alamat Satuan Pendidikan</label>
              <input type="text" name="alamat_satuan" class="form-control" value="Jalan Gedong No. 16 Kecamatan Tamansari Jakarta Barat">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Alamat Tinggal Guru</label>
              <input type="text" name="alamat_tinggal" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">No. HP</label>
              <input type="text" name="no_hp" class="form-control">
            </div>
          </div>
        </div>

        <!-- C: Siswa Aktif -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="keterangan_aktif" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-person-check me-2"></i>Keterangan Siswa Aktif</div>
          <div class="card-body row g-3">
            <div class="col-12"><label class="form-label fw-semibold">Keperluan Tambahan <small class="text-muted">(opsional)</small></label>
              <input type="text" name="keperluan_tambahan" class="form-control" placeholder="Diisi jika ada keterangan tambahan...">
            </div>
          </div>
        </div>

        <!-- D: Lomba (Multi-Penerima) -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="keterangan_lomba" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-trophy me-2"></i>Data Lomba & Daftar Siswa</div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-6"><label class="form-label fw-semibold">Nama Lomba / Kegiatan</label>
                <input type="text" name="event_name" class="form-control">
              </div>
              <div class="col-md-6"><label class="form-label fw-semibold">Penyelenggara</label>
                <input type="text" name="event_organizer" class="form-control">
              </div>
            </div>
            <label class="form-label fw-semibold">Daftar Siswa</label>
            <div class="table-responsive">
              <table class="table table-bordered table-sm" id="tbl-recipients">
                <thead class="table-light">
                  <tr>
                    <th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Tanggal Lahir</th><th>Cabang Lomba</th><th></th>
                  </tr>
                </thead>
                <tbody id="recipient-rows">
                  <tr id="row-template">
                    <td class="row-no">1</td>
                    <td>
                      <div class="position-relative">
                        <input type="text" name="rec_name[]" class="form-control form-control-sm autocomplete-rec-name" required autocomplete="off" placeholder="Ketik nama atau NISN...">
                        <div class="autocomplete-suggestions list-group"></div>
                      </div>
                    </td>
                    <td><input type="text" name="rec_kelas[]" class="form-control form-control-sm"></td>
                    <td><input type="date" name="rec_birth_date[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="rec_cabang[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-recipient">
              <i class="bi bi-plus me-1"></i>Tambah Siswa
            </button>
          </div>
        </div>

        <!-- E: KJP -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="keterangan_kjp" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-card-list me-2"></i>Data KJP / Dokumen Khusus</div>
          <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold">Alamat Domisili</label>
              <input type="text" name="alamat_domisili" class="form-control">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Keperluan Spesifik <span class="text-danger">*</span></label>
              <textarea name="keperluan_detail" class="form-control" rows="2"
                        placeholder="cth: pencetakan Kartu Jakarta Pintar dikarenakan yang lama telah hilang"></textarea>
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Lampiran <small class="text-muted">(opsional)</small></label>
              <input type="text" name="lampiran_keterangan" class="form-control" placeholder="cth: surat kehilangan terlampir">
            </div>
          </div>
        </div>

        <!-- F: Surat Tugas -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="surat_tugas" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-briefcase me-2"></i>Detail Kegiatan / Tugas</div>
          <div class="card-body row g-3">
            <div class="col-12"><label class="form-label fw-semibold">Nama Kegiatan <span class="text-danger">*</span></label>
              <input type="text" name="activity_name" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Pelaksanaan</label>
              <input type="date" name="activity_date" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Waktu Mulai</label>
              <input type="time" name="activity_time" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Tempat</label>
              <input type="text" name="activity_venue" class="form-control">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Alamat Tempat</label>
              <input type="text" name="activity_address" class="form-control">
            </div>
            <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Rujukan Surat Dasar (Opsional)</small></div>
            <div class="col-md-4"><label class="form-label fw-semibold">Nomor Surat Dasar</label>
              <input type="text" name="ref_letter_number" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Surat Dasar</label>
              <input type="date" name="ref_letter_date" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Instansi Pengirim</label>
              <input type="text" name="ref_letter_from" class="form-control">
            </div>
            <div class="col-12"><label class="form-label fw-semibold">Perihal Surat Dasar</label>
              <input type="text" name="ref_letter_subject" class="form-control">
            </div>
          </div>
        </div>

        <!-- G: Undangan -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="undangan" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-calendar-event me-2"></i>Detail Undangan</div>
          <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label fw-semibold">Hari</label>
              <input type="text" name="event_day" class="form-control" placeholder="cth: Kamis">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Tanggal</label>
              <input type="date" name="event_date" class="form-control">
            </div>
            <div class="col-md-4"><label class="form-label fw-semibold">Pukul</label>
              <input type="time" name="event_time" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Tempat</label>
              <input type="text" name="event_venue" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Acara</label>
              <input type="text" name="event_agenda" class="form-control" placeholder="cth: Rapat Koordinasi">
            </div>
          </div>
        </div>

        <!-- H: Surat Custom / Dinamis -->
        <div class="card border-0 shadow-sm mb-4 dynamic-section" data-type="surat_custom" style="display:none;">
          <div class="card-header bg-light fw-semibold py-2"><i class="bi bi-envelope-paper-fill me-2"></i>Isi dan Format Surat Custom</div>
          <div class="card-body row g-3">
            
            <div class="col-md-6">
              <label class="form-label fw-semibold">Gaya Header Kop Surat <span class="text-danger">*</span></label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="header_style" id="style_tengah" value="tengah" checked>
                  <label class="form-check-label small" for="style_tengah">
                    <i class="bi bi-align-center me-1"></i>Tengah (Judul di Tengah)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="header_style" id="style_kiri_kanan" value="kiri_kanan">
                  <label class="form-check-label small" for="style_kiri_kanan">
                    <i class="bi bi-columns-gap me-1"></i>Kiri-Kanan (Nomor & Kepada)
                  </label>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="custom_recipient_type">Tipe Penerima Surat <span class="text-danger">*</span></label>
              <select id="custom_recipient_type" class="form-select">
                <option value="eksternal">Multi-Penerima Custom (Input 1 atau Lebih Baris)</option>
                <option value="multi_guru">Banyak Guru / Tendik (Pilih Beberapa Guru)</option>
                <option value="multi_siswa">Banyak Siswa (Pilih Beberapa Siswa)</option>
                <option value="guru">Guru / Tenaga Kependidikan (Tunggal)</option>
                <option value="siswa">Siswa (Data Siswa Tunggal)</option>
                <option value="undangan">Massal (Seluruh Guru / Wali Murid Kelas)</option>
              </select>
            </div>

            <!-- Placeholders Panel -->
            <div class="col-12">
              <div class="card border border-primary border-opacity-25 bg-light-subtle">
                <div class="card-body py-2 px-3">
                  <small class="fw-bold text-secondary d-block mb-1"><i class="bi bi-patch-question me-1 text-primary"></i>Klik badge untuk menyisipkan variabel data otomatis (Placeholder):</small>
                  <div class="d-flex flex-wrap gap-1" id="placeholder-container">
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nama_penerima}" style="font-size:0.75rem;">{nama_penerima}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nisn}" style="font-size:0.75rem;">{nisn}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{kelas}" style="font-size:0.75rem;">{kelas}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{ttl}" style="font-size:0.75rem;">{ttl}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nip}" style="font-size:0.75rem;">{nip}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{jabatan}" style="font-size:0.75rem;">{jabatan}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nomor_surat}" style="font-size:0.75rem;">{nomor_surat}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{tanggal_surat}" style="font-size:0.75rem;">{tanggal_surat}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{tahun_pelajaran}" style="font-size:0.75rem;">{tahun_pelajaran}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{kepala_sekolah}" style="font-size:0.75rem;">{kepala_sekolah}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nip_kepala_sekolah}" style="font-size:0.75rem;">{nip_kepala_sekolah}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{nama_sekolah}" style="font-size:0.75rem;">{nama_sekolah}</button>
                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 placeholder-btn" data-placeholder="{alamat_sekolah}" style="font-size:0.75rem;">{alamat_sekolah}</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- CKEditor -->
            <div class="col-12">
              <label class="form-label fw-semibold">Isi Surat <span class="text-danger">*</span></label>

              <!-- Toolbar Spasi Paragraf Kustom -->
              <div class="d-flex align-items-center gap-2 flex-wrap mb-2 p-2 bg-light border rounded" id="custom-spacing-toolbar" style="display:none !important;">
                <small class="text-secondary fw-semibold me-1" style="font-size:0.76rem;"><i class="bi bi-arrows-expand me-1"></i>Spasi Paragraf Aktif:</small>
                <div class="btn-group btn-group-sm" role="group" aria-label="Paragraph spacing">
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="0" title="Tanpa Jarak (0px)">0</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="4" title="Sangat Rapat (4px)">4px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="8" title="Rapat (8px)">8px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing active" data-margin="12" title="Normal (12px)">12px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="18" title="Longgar (18px)">18px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="24" title="Sangat Longgar (24px)">24px</button>
                  <button type="button" class="btn btn-outline-secondary btn-spacing" data-margin="36" title="Double Space (36px)">36px</button>
                </div>
                <span class="text-muted" style="font-size:0.72rem; border-left:1px solid #ccc; padding-left:8px;">
                  <i class="bi bi-keyboard me-1"></i><kbd>Enter</kbd> = paragraf baru &nbsp;|&nbsp; <kbd>Shift+Enter</kbd> = pindah baris (tanpa jarak)
                </span>
              </div>

              <!-- Hidden input to hold the actual value on submit -->
              <input type="hidden" name="body_html" id="body_html_hidden">
              <div id="editor-surat-custom-container" style="min-height: 300px;">
                <div id="editor-surat-custom"></div>
              </div>
              <small class="text-muted mt-1 d-block" style="font-size:0.73rem;">
                <i class="bi bi-info-circle me-1"></i>
                Gunakan <kbd>Shift+Enter</kbd> untuk ganti baris tanpa jarak. Gunakan tombol <strong>Spasi Paragraf</strong> di atas untuk mengatur jarak setiap paragraf secara berbeda.
              </small>
            </div>

          </div>
        </div>

      </div><!-- end #section-dynamic -->
    </div>

    <!-- Kolom Kanan: Preview & Submit -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-info-circle me-2"></i>Info Surat
        </div>
        <div class="card-body">
          <div class="mb-2 d-flex justify-content-between">
            <small class="text-muted">Nomor Surat</small>
            <span class="fw-semibold text-primary font-monospace">Auto-generate</span>
          </div>
          <div class="mb-2 d-flex justify-content-between">
            <small class="text-muted">Ditandatangani</small>
            <span class="fw-semibold"><?= esc($principal_name) ?></span>
          </div>
          <div class="mb-3 d-flex justify-content-between">
            <small class="text-muted">NIP</small>
            <span class="fw-semibold font-monospace"><?= esc($principal_nip) ?></span>
          </div>
          <div id="selected-type-badge" class="alert alert-info py-2 small" style="display:none;">
            <i class="bi bi-check-circle me-1"></i><span id="selected-type-label"></span>
          </div>
          <hr>
          <!-- Tombol Preview (hanya untuk surat_custom) -->
          <button type="button" class="btn btn-outline-info w-100 mb-2" id="btn-preview-surat" style="display:none;">
            <i class="bi bi-eye me-2"></i>Preview Surat
          </button>
          <button type="submit" class="btn btn-primary w-100" id="btn-submit-surat" disabled>
            <i class="bi bi-send me-2"></i>Buat Surat &amp; Generate PDF
          </button>
          <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary w-100 mt-2">
            Batal
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Modal Preview Surat -->
<div class="modal fade" id="modal-preview-surat" tabindex="-1" aria-labelledby="modalPreviewLabel">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="modalPreviewLabel">
          <i class="bi bi-eye me-2 text-info"></i>Preview Surat
          <span class="badge bg-warning text-dark ms-2 fw-normal" style="font-size:0.7rem;">PREVIEW — Bukan Dokumen Resmi</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 bg-secondary bg-opacity-10">
        <!-- Letter paper container -->
        <div style="max-width:794px; margin:20px auto; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.15); padding:40px 50px; min-height:400px; font-family:'Times New Roman',Times,serif; font-size:14px; line-height:1.6;" id="preview-letter-paper">
          <div id="preview-kop-area"></div>
          <div id="preview-header-area"></div>
          <div id="preview-body-area"></div>
          <div id="preview-footer-note" style="margin-top:40px; border-top:1px dashed #ccc; padding-top:12px; color:#888; font-size:12px; font-family:Arial,sans-serif; text-align:center;">
            <i>⚠️ QR Code verifikasi dan tanda tangan Kepala Sekolah akan muncul di PDF final</i>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Placeholder telah diisi dengan data form saat ini. Nomor surat digenerate otomatis saat submit.</small>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="btn-from-preview-submit">
            <i class="bi bi-send me-1"></i>Lanjut Generate PDF
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
const LETTER_TYPES    = <?= json_encode($letter_types) ?>;
const SISWA_SEARCH_URL = '<?= base_url('admin/surat-keluar/search-siswa') ?>';
const GURU_SEARCH_URL  = '<?= base_url('admin/surat-keluar/search-guru') ?>';

// Data sekolah & KOP untuk preview client-side
const KOP_BASE64      = <?= json_encode($kop_base64 ?? null) ?>;
const SCHOOL_NAME     = <?= json_encode($school['name'] ?? '') ?>;
const SCHOOL_ADDRESS  = <?= json_encode($school['address'] ?? '') ?>;
const PRINCIPAL_NAME  = <?= json_encode($principal_name ?? '') ?>;
const PRINCIPAL_NIP   = <?= json_encode($principal_nip ?? '') ?>;
const ACTIVE_YEAR     = <?= json_encode($active_year ?? '') ?>;
const NEXT_LETTER_NUMBER = <?= json_encode($next_letter_number ?? '') ?>;

// Mapping: jenis surat → tipe penerima
const RECIPIENT_MAP = {
  'keterangan_mutasi_masuk': 'siswa',
  'keterangan_mengajar':     'guru',
  'keterangan_aktif':        'siswa',
  'keterangan_lomba':        'multi',
  'keterangan_kjp':          'siswa',
  'surat_tugas':             'guru',
  'undangan':                'undangan',
  'surat_custom':            'custom',
};

// CKEditor instance
let editorInstance = null;

function initCKEditor() {
  if (editorInstance) return;
  CKEDITOR.ClassicEditor
    .create(document.querySelector('#editor-surat-custom'), {
      toolbar: {
        items: [
          'undo', 'redo', '|',
          'heading', '|',
          'bold', 'italic', 'underline', 'strikethrough', '|',
          'fontSize', 'fontColor', 'fontBackgroundColor', '|',
          'insertTable', 'blockQuote', 'horizontalLine', '|',
          'bulletedList', 'numberedList', 'outdent', 'indent', '|',
          'alignment', '|',
          'removeFormat'
        ],
        shouldNotGroupWhenFull: true
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
          { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
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
        options: [9, 11, 13, 'default', 17, 19, 21, 24],
        supportAllValues: true
      },
      fontFamily: {
        options: [
          'default',
          'Arial, Helvetica, sans-serif',
          'Times New Roman, Times, serif',
          'Courier New, Courier, monospace'
        ],
        supportAllValues: true
      },
      htmlSupport: {
        allow: [{ name: /.*/, attributes: true, classes: true, styles: true }]
      }
    })
    .then(editor => {
      editorInstance = editor;
      editor.editing.view.change(writer => {
        writer.setStyle('min-height', '320px', editor.editing.view.document.getRoot());
      });

      // Show custom spacing toolbar once editor is ready
      document.getElementById('custom-spacing-toolbar').style.removeProperty('display');
      document.getElementById('custom-spacing-toolbar').style.display = 'flex';

      // Track cursor/selection change to reflect current paragraph's margin in toolbar
      editor.model.document.selection.on('change', () => {
        const pos = editor.model.document.selection.getFirstPosition();
        if (!pos) return;
        const block = pos.parent;
        // Read current margin-bottom from the DOM view element
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
}

// ─── Custom Paragraph Spacing Toolbar Handlers ───────────────────────────────
$(document).on('click', '.btn-spacing', function() {
  if (!editorInstance) return;

  const marginPx = $(this).data('margin') + 'px';

  editorInstance.model.change(writer => {
    const selection = editorInstance.model.document.selection;
    const ranges    = [...selection.getRanges()];

    // Collect all block elements in selection
    const blocks = new Set();
    for (const range of ranges) {
      for (const item of range.getItems()) {
        if (item.is('element')) {
          blocks.add(item);
        }
      }
      // Always include anchor/focus block
      const start = range.start.parent;
      const end   = range.end.parent;
      if (start.is('element')) blocks.add(start);
      if (end.is('element'))   blocks.add(end);
    }

    // If nothing, apply to current paragraph
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

  // Update active button
  document.querySelectorAll('.btn-spacing').forEach(btn => btn.classList.remove('active'));
  this.classList.add('active');
});

// Update recipient section based on selected type
function updateRecipientSection() {
  let rtype = RECIPIENT_MAP[selectedType] || 'siswa';
  if (rtype === 'custom') {
    rtype = $('#custom_recipient_type').val();
  }

  $('#recipient-siswa-section').toggle(rtype === 'siswa');
  $('#recipient-guru-section').toggle(rtype === 'guru');
  $('#recipient-multi-guru-section').toggle(rtype === 'multi_guru');
  $('#recipient-multi-siswa-section').toggle(rtype === 'multi_siswa');
  $('#recipient-internal-section').toggle(rtype === 'internal');
  $('#recipient-eksternal-section').toggle(rtype === 'eksternal');
  $('#recipient-undangan-section').toggle(rtype === 'undangan');

  if (rtype === 'siswa') {
    $('#recipient_type').val('siswa');
    $('#siswa-detail-fields').show();
    $('#guru-detail-fields').hide();
  } else if (rtype === 'guru') {
    $('#recipient_type').val('guru');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').show();
  } else if (rtype === 'multi_guru') {
    $('#recipient_type').val('guru');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
    syncMultiGuruRecipientName();
  } else if (rtype === 'multi_siswa') {
    $('#recipient_type').val('siswa');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
    syncMultiSiswaRecipientName();
  } else if (rtype === 'eksternal') {
    $('#recipient_type').val('eksternal');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
    syncEksternalRecipientName();
  } else if (rtype === 'internal') {
    $('#recipient_type').val('internal');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
    $('#recipient_name').val('Guru & Tenaga Kependidikan ' + SCHOOL_NAME);
  } else if (rtype === 'undangan') {
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
    updateUndanganRecipient();
  } else {
    $('#recipient_type').val('siswa');
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
  }
}

// Handle undangan sub-mode recipient logic
function updateUndanganRecipient() {
  const mode = $('input[name="undangan_recipient_mode"]:checked').val() || 'all';
  $('.undangan-sub-section').hide();
  $(`#undangan-sub-${mode === 'wali_murid' ? 'wali' : mode}`).show();

  switch (mode) {
    case 'all':
      $('#recipient_type').val('internal');
      $('#recipient_name').val('Guru & Tenaga Kependidikan ' + SCHOOL_NAME);
      break;
    case 'guru':
      $('#recipient_type').val('guru');
      // Will be set by autocomplete selection
      break;
    case 'pegawai':
      $('#recipient_type').val('guru');
      // Will be set by autocomplete selection
      break;
    case 'wali_murid':
      $('#recipient_type').val('internal');
      const kelasVal = $('#undangan_wali_class').val();
      if (kelasVal === 'semua') {
        $('#recipient_name').val('Orang Tua / Wali Murid ' + SCHOOL_NAME);
      } else {
        $('#recipient_name').val('Orang Tua / Wali Murid Kelas ' + kelasVal + ' ' + SCHOOL_NAME);
      }
      break;
    case 'dinamis':
      $('#recipient_type').val('eksternal');
      updateUndanganDinamisRecipientName();
      break;
  }
}

function updateUndanganDinamisRecipientName() {
  const names = [];
  $('.undangan-dinamis-input').each(function() {
    const v = $(this).val().trim();
    if (v) names.push(v);
  });
  $('#recipient_name').val(names.join(' | '));
}

let selectedType = null;

// Saat pilih jenis surat
$('input[name="letter_type"]').on('change', function() {
  const isInitialLoad = (selectedType === null);
  selectedType = this.value;
  $('#section-common').show();
  $('#section-recipient').show();
  $('#section-dynamic').show();
  $('#btn-submit-surat').prop('disabled', false);
  $('#selected-type-badge').show();
  $('#selected-type-label').text(LETTER_TYPES[selectedType]);

  if (!isInitialLoad) {
    // Reset fields on change to avoid stale data
    $('#recipient_name').val('');
    $('#recipient_ref_id').val('');
    $('#f_nisn').val('');
    $('#f_nik').val('');
    $('#f_ttl').val('');
    $('#f_kelas').val('');
    $('#f_alamat').val('');
    $('#f_nip').val('');
    $('#f_jabatan').val('');
    $('#recipient_search_siswa').val('');
    $('#recipient_search_guru').val('');
    $('#siswa-autocomplete-result').empty().hide();
    $('#guru-autocomplete-result').empty().hide();
    $('#siswa-detail-preview').empty();
    $('#guru-detail-preview').empty();
    $('input[name="nik_guru"]').val('');
    $('input[name="nip"]').val('');
    $('input[name="jabatan"]').val('');
  }

  // Tampilkan field penerima yang sesuai
  updateRecipientSection();

  // Set recipient_name untuk multi
  if (RECIPIENT_MAP[selectedType] === 'multi') {
    $('#recipient_name').val('Daftar Terlampir');
  }

  // Inisialisasi CKEditor jika surat_custom
  if (selectedType === 'surat_custom') {
    setTimeout(initCKEditor, 100);
  }

  // Tampilkan section dinamis
  $('.dynamic-section').hide();
  $('.dynamic-section').find('.autocomplete-rec-name').prop('required', false);

  if (selectedType === 'keterangan_lomba') {
    $(`.dynamic-section[data-type="keterangan_lomba"]`).find('.autocomplete-rec-name').prop('required', true);
  }
  $(`.dynamic-section[data-type="${selectedType}"]`).show();
});

// Autocomplete Siswa
let siswaTimer;
$('#recipient_search_siswa').on('input', function() {
  clearTimeout(siswaTimer);
  const q = $(this).val();
  
  // Set recipient_name to the custom text entered
  $('#recipient_name').val(q);
  $('#recipient_ref_id').val('');
  $('#f_nisn').val('');
  $('#f_nik').val('');
  $('#f_ttl').val('');
  $('#f_kelas').val('');
  $('#siswa-detail-preview').empty();
  
  if (q.length < 2) {
    $('#siswa-autocomplete-result').empty().hide();
    return;
  }
  
  siswaTimer = setTimeout(() => {
    $.getJSON(SISWA_SEARCH_URL, { q }, function(data) {
      const list = $('#siswa-autocomplete-result').empty();
      if (data.length > 0) {
        data.forEach(s => {
          list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small siswa-item"
            data-id="${s.id}" data-name="${s.name}" data-nisn="${s.nisn}"
            data-nik="${s.nik}" data-ttl="${s.ttl}" data-kelas="${s.kelas}" data-alamat="${s.alamat}">
            <i class="bi bi-person me-2"></i><strong>${s.name}</strong> <span class="text-muted ms-1">(NISN: ${s.nisn} - Kelas: ${s.kelas})</span>
          </a>`);
        });
        list.show();
      } else {
        list.hide();
      }
    });
  }, 300);
});

$(document).on('click', '.siswa-item', function(e) {
  e.preventDefault();
  const item = $(this);
  const d = item.data();
  
  const name = d.name || item.attr('data-name');
  const id = d.id || item.attr('data-id');
  const nisn = d.nisn || item.attr('data-nisn') || '';
  const nik = d.nik || item.attr('data-nik') || '';
  const ttl = d.ttl || item.attr('data-ttl') || '';
  const kelas = d.kelas || item.attr('data-kelas') || '';
  const alamat = d.alamat || item.attr('data-alamat') || '';

  $('#recipient_name').val(name);
  $('#recipient_ref_id').val(id);
  $('#recipient_search_siswa').val(name);
  $('#siswa-autocomplete-result').empty().hide();
  
  $('#f_nisn').val(nisn);
  $('#f_nik').val(nik);
  $('#f_ttl').val(ttl);
  $('#f_kelas').val(kelas);
  $('#f_alamat').val(alamat);
  
  // Prefill dynamic sections if present
  $('input[name="alamat_domisili"]').val(alamat);

  $('#siswa-detail-preview').html(`<span class="badge bg-light text-dark border">NISN: ${nisn}</span>
    <span class="badge bg-light text-dark border ms-1">Kelas: ${kelas}</span>`);
});

// Autocomplete Guru
let guruTimer;
$('#recipient_search_guru').on('input', function() {
  clearTimeout(guruTimer);
  const q = $(this).val();
  
  // Set recipient_name to the custom text entered
  $('#recipient_name').val(q);
  $('#recipient_ref_id').val('');
  $('#f_nip').val('');
  $('#f_jabatan').val('');
  $('input[name="nik_guru"]').val('');
  $('input[name="nip"]').val('');
  $('input[name="jabatan"]').val('');
  $('input[name="alamat_tinggal"]').val('');
  $('input[name="no_hp"]').val('');
  $('#guru-detail-preview').empty();
  
  if (q.length < 2) {
    $('#guru-autocomplete-result').empty().hide();
    return;
  }
  
  guruTimer = setTimeout(() => {
    $.getJSON(GURU_SEARCH_URL, { q }, function(data) {
      const list = $('#guru-autocomplete-result').empty();
      if (data.length > 0) {
        data.forEach(t => {
          list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small guru-item"
            data-id="${t.id}" data-name="${t.name}" data-nip="${t.nip}"
            data-nik="${t.nik}" data-jabatan="${t.jabatan}" data-phone="${t.phone}" data-address="${t.address}">
            <i class="bi bi-person-badge me-2"></i><strong>${t.name}</strong> <span class="text-muted ms-1">(NIP: ${t.nip || '-'} - ${t.jabatan})</span>
          </a>`);
        });
        list.show();
      } else {
        list.hide();
      }
    });
  }, 300);
});

$(document).on('click', '.guru-item', function(e) {
  e.preventDefault();
  const item = $(this);
  const d = item.data();
  
  const name = d.name || item.attr('data-name');
  const id = d.id || item.attr('data-id');
  const nip = d.nip || item.attr('data-nip') || '';
  const nik = d.nik || item.attr('data-nik') || '';
  const jabatan = d.jabatan || item.attr('data-jabatan') || 'Guru';
  const phone = d.phone || item.attr('data-phone') || '';
  const address = d.address || item.attr('data-address') || '';

  $('#recipient_name').val(name);
  $('#recipient_ref_id').val(id);
  $('#recipient_search_guru').val(name);
  $('#guru-autocomplete-result').empty().hide();
  
  $('#f_nip').val(nip);
  $('#f_jabatan').val(jabatan);

  // Auto pre-fill sub-form fields for Keterangan Mengajar
  $('input[name="nik_guru"]').val(nik);
  $('input[name="alamat_tinggal"]').val(address);
  $('input[name="no_hp"]').val(phone);
  
  // Auto pre-fill sub-form fields for Surat Tugas
  $('input[name="nip"]').val(nip);
  $('input[name="jabatan"]').val(jabatan);

  $('#guru-detail-preview').html(`<span class="badge bg-light text-dark border">NIP: ${nip}</span>
    <span class="badge bg-light text-dark border ms-1">${jabatan}</span>`);
});

// Autocomplete Lomba (Multi-Siswa)
let recTimer;
$(document).on('input', '.autocomplete-rec-name', function() {
  clearTimeout(recTimer);
  const input = $(this);
  const q = input.val();
  const list = input.siblings('.autocomplete-suggestions').empty();
  
  if (q.length < 2) {
    list.hide();
    return;
  }
  
  recTimer = setTimeout(() => {
    $.getJSON(SISWA_SEARCH_URL, { q }, function(data) {
      list.empty();
      if (data.length > 0) {
        data.forEach(s => {
          list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small rec-siswa-item"
            data-name="${s.name}" data-birth-date="${s.birth_date}" data-birth_date="${s.birth_date}" data-kelas="${s.kelas}">
            <i class="bi bi-person me-2"></i><strong>${s.name}</strong> <span class="text-muted ms-1">(NISN: ${s.nisn} - Kelas: ${s.kelas})</span>
          </a>`);
        });
        list.show();
      } else {
        list.hide();
      }
    });
  }, 300);
});

$(document).on('click', '.rec-siswa-item', function(e) {
  e.preventDefault();
  const item = $(this);
  const d = item.data();
  const row = item.closest('tr');
  
  const name = d.name || item.attr('data-name') || '';
  const kelas = d.kelas || item.attr('data-kelas') || '';
  const birthDate = d.birth_date || d.birthDate || item.attr('data-birth-date') || item.attr('data-birth_date') || '';
  
  row.find('.autocomplete-rec-name').val(name);
  row.find('input[name="rec_kelas[]"]').val(kelas);
  row.find('input[name="rec_birth_date[]"]').val(birthDate);
  
  item.closest('.autocomplete-suggestions').empty().hide();
});

// Close suggestions on outside click
$(document).on('click', function(e) {
  if (!$(e.target).closest('#recipient-siswa-section').length) {
    $('#siswa-autocomplete-result').hide();
  }
  if (!$(e.target).closest('#recipient-guru-section').length) {
    $('#guru-autocomplete-result').hide();
  }
  if (!$(e.target).closest('.position-relative').length) {
    $('.autocomplete-suggestions').hide();
  }
});

// Tambah baris siswa (lomba)
let rowCount = 1;
$('#btn-add-recipient').on('click', function() {
  rowCount++;
  const newRow = `<tr>
    <td class="row-no">${rowCount}</td>
    <td>
      <div class="position-relative">
        <input type="text" name="rec_name[]" class="form-control form-control-sm autocomplete-rec-name" required autocomplete="off" placeholder="Ketik nama atau NISN...">
        <div class="autocomplete-suggestions list-group"></div>
      </div>
    </td>
    <td><input type="text" name="rec_kelas[]" class="form-control form-control-sm"></td>
    <td><input type="date" name="rec_birth_date[]" class="form-control form-control-sm"></td>
    <td><input type="text" name="rec_cabang[]" class="form-control form-control-sm"></td>
    <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td>
  </tr>`;
  $('#recipient-rows').append(newRow);
});

$(document).on('click', '.btn-remove-row', function() {
  if ($('#recipient-rows tr').length > 1) {
    $(this).closest('tr').remove();
    $('#recipient-rows tr').each((i, tr) => $(tr).find('.row-no').text(i + 1));
    rowCount = $('#recipient-rows tr').length;
  }
});

// Trigger change on page load if a type is already pre-selected
$('input[name="letter_type"]:checked').trigger('change');

// Saat tipe penerima custom diubah (untuk surat_custom)
$(document).on('change', '#custom_recipient_type', function() {
  updateRecipientSection();
});

// Sinkronisasi nama penerima eksternal ke field hidden
function syncEksternalRecipientName() {
  const names = [];
  $('.eksternal-dinamis-input').each(function() {
    const val = $(this).val().trim();
    if (val) names.push(val);
  });
  $('#recipient_name').val(names.join(' | '));
}

let eksternalRowCount = 1;
$('#btn-add-eksternal-row').on('click', function() {
  eksternalRowCount++;
  const rowHtml = `<div class="input-group mb-2 eksternal-dinamis-row">
    <span class="input-group-text bg-light text-secondary fw-semibold row-idx" style="font-size:0.82rem; min-width:36px; justify-content:center;">${eksternalRowCount}</span>
    <input type="text" class="form-control eksternal-dinamis-input" placeholder="cth: Kepala Suku Dinas Pendidikan" autocomplete="off">
    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-eksternal" title="Hapus"><i class="bi bi-trash"></i></button>
  </div>`;
  $('#eksternal-dinamis-rows').append(rowHtml);
});

$(document).on('click', '.btn-remove-eksternal', function() {
  if ($('.eksternal-dinamis-row').length > 1) {
    $(this).closest('.eksternal-dinamis-row').remove();
    $('.eksternal-dinamis-row').each((idx, el) => {
      $(el).find('.row-idx').text(idx + 1);
    });
    eksternalRowCount = $('.eksternal-dinamis-row').length;
    syncEksternalRecipientName();
  }
});

$(document).on('input', '.eksternal-dinamis-input', function() {
  syncEksternalRecipientName();
});

// ─── Multi Guru Handlers ────────────────────────────────────────────────
let multiGuruTimer;
$('#recipient_search_multi_guru').on('input', function() {
  clearTimeout(multiGuruTimer);
  const q = $(this).val();
  if (q.length < 2) { $('#multi-guru-autocomplete-result').empty().hide(); return; }
  multiGuruTimer = setTimeout(() => {
    $.getJSON(GURU_SEARCH_URL, { q }, function(data) {
      const list = $('#multi-guru-autocomplete-result').empty();
      data.forEach(t => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small multi-guru-item"
          data-name="${t.name}">
          <i class="bi bi-person-badge me-2"></i><strong>${t.name}</strong> <span class="text-muted ms-1">(NIP: ${t.nip || '-'} - ${t.jabatan})</span>
        </a>`);
      });
      list.toggle(data.length > 0);
    });
  }, 300);
});

$(document).on('click', '.multi-guru-item', function(e) {
  e.preventDefault();
  const name = $(this).data('name');
  $('#recipient_search_multi_guru').val('');
  $('#multi-guru-autocomplete-result').empty().hide();
  $('#multi-guru-empty-msg').hide();
  
  $('#multi-guru-selected-chips').append(
    `<span class="badge bg-primary d-inline-flex align-items-center py-1 px-2 me-1 mb-1 multi-guru-chip" data-name="${name}">
      ${name}
      <button type="button" class="btn-close btn-close-white ms-2 btn-remove-multi-guru-chip" style="font-size:0.65rem;" aria-label="Remove"></button>
    </span>`
  );
  syncMultiGuruRecipientName();
});

$(document).on('click', '.btn-remove-multi-guru-chip', function() {
  $(this).closest('.multi-guru-chip').remove();
  if ($('.multi-guru-chip').length === 0) {
    $('#multi-guru-empty-msg').show();
  }
  syncMultiGuruRecipientName();
});

function syncMultiGuruRecipientName() {
  const names = [];
  $('.multi-guru-chip').each(function() {
    names.push($(this).data('name'));
  });
  $('#recipient_name').val(names.join(' | '));
}

// ─── Multi Siswa Handlers ────────────────────────────────────────────────
let multiSiswaTimer;
$('#recipient_search_multi_siswa').on('input', function() {
  clearTimeout(multiSiswaTimer);
  const q = $(this).val();
  if (q.length < 2) { $('#multi-siswa-autocomplete-result').empty().hide(); return; }
  multiSiswaTimer = setTimeout(() => {
    $.getJSON(SISWA_SEARCH_URL, { q }, function(data) {
      const list = $('#multi-siswa-autocomplete-result').empty();
      data.forEach(s => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small multi-siswa-item"
          data-name="${s.name}">
          <i class="bi bi-person me-2"></i><strong>${s.name}</strong> <span class="text-muted ms-1">(NISN: ${s.nisn} - Kelas: ${s.kelas})</span>
        </a>`);
      });
      list.toggle(data.length > 0);
    });
  }, 300);
});

$(document).on('click', '.multi-siswa-item', function(e) {
  e.preventDefault();
  const name = $(this).data('name');
  $('#recipient_search_multi_siswa').val('');
  $('#multi-siswa-autocomplete-result').empty().hide();
  $('#multi-siswa-empty-msg').hide();
  
  $('#multi-siswa-selected-chips').append(
    `<span class="badge bg-success d-inline-flex align-items-center py-1 px-2 me-1 mb-1 multi-siswa-chip" data-name="${name}">
      ${name}
      <button type="button" class="btn-close btn-close-white ms-2 btn-remove-multi-siswa-chip" style="font-size:0.65rem;" aria-label="Remove"></button>
    </span>`
  );
  syncMultiSiswaRecipientName();
});

$(document).on('click', '.btn-remove-multi-siswa-chip', function() {
  $(this).closest('.multi-siswa-chip').remove();
  if ($('.multi-siswa-chip').length === 0) {
    $('#multi-siswa-empty-msg').show();
  }
  syncMultiSiswaRecipientName();
});

function syncMultiSiswaRecipientName() {
  const names = [];
  $('.multi-siswa-chip').each(function() {
    names.push($(this).data('name'));
  });
  $('#recipient_name').val(names.join(' | '));
}

// ─── Undangan Recipient Sub-Mode Logic ──────────────────────────────────

// Mode change handler
$('input[name="undangan_recipient_mode"]').on('change', function() {
  updateUndanganRecipient();
});

// Wali murid kelas change handler
$('#undangan_wali_class').on('change', function() {
  updateUndanganRecipient();
});

// Undangan Guru autocomplete
let undanganGuruTimer;
$('#undangan_search_guru').on('input', function() {
  clearTimeout(undanganGuruTimer);
  const q = $(this).val();
  if (q.length < 2) { $('#undangan-guru-autocomplete').empty().hide(); return; }
  undanganGuruTimer = setTimeout(() => {
    $.getJSON(GURU_SEARCH_URL, { q }, function(data) {
      const list = $('#undangan-guru-autocomplete').empty();
      data.forEach(t => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small undangan-guru-item"
          data-name="${t.name}" data-nip="${t.nip || '-'}" data-jabatan="${t.jabatan}">
          <i class="bi bi-person-badge me-2"></i><strong>${t.name}</strong> <span class="text-muted ms-1">(NIP: ${t.nip || '-'} - ${t.jabatan})</span>
        </a>`);
      });
      list.toggle(data.length > 0);
    });
  }, 300);
});

$(document).on('click', '.undangan-guru-item', function(e) {
  e.preventDefault();
  const name = $(this).data('name');
  const nip = $(this).data('nip') || '-';
  const jabatan = $(this).data('jabatan') || 'Guru';
  $('#undangan_search_guru').val(name);
  $('#recipient_name').val(name);
  $('#undangan-guru-autocomplete').empty().hide();
  $('#undangan-guru-selected').html(`<span class="badge bg-success"><i class="bi bi-check me-1"></i>${name}</span> <span class="badge bg-light text-dark border ms-1">NIP: ${nip}</span> <span class="badge bg-light text-dark border ms-1">${jabatan}</span>`);
});

// Undangan Pegawai autocomplete (uses same guru search endpoint since tendik are in teachers table)
let undanganPegawaiTimer;
$('#undangan_search_pegawai').on('input', function() {
  clearTimeout(undanganPegawaiTimer);
  const q = $(this).val();
  if (q.length < 2) { $('#undangan-pegawai-autocomplete').empty().hide(); return; }
  undanganPegawaiTimer = setTimeout(() => {
    $.getJSON(GURU_SEARCH_URL, { q }, function(data) {
      const list = $('#undangan-pegawai-autocomplete').empty();
      data.forEach(t => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-2 px-3 small undangan-pegawai-item"
          data-name="${t.name}" data-nip="${t.nip || '-'}" data-jabatan="${t.jabatan}">
          <i class="bi bi-person-workspace me-2"></i><strong>${t.name}</strong> <span class="text-muted ms-1">(NIP: ${t.nip || '-'} - ${t.jabatan})</span>
        </a>`);
      });
      list.toggle(data.length > 0);
    });
  }, 300);
});

$(document).on('click', '.undangan-pegawai-item', function(e) {
  e.preventDefault();
  const name = $(this).data('name');
  const nip = $(this).data('nip') || '-';
  const jabatan = $(this).data('jabatan') || 'Staf';
  $('#undangan_search_pegawai').val(name);
  $('#recipient_name').val(name);
  $('#undangan-pegawai-autocomplete').empty().hide();
  $('#undangan-pegawai-selected').html(`<span class="badge bg-success"><i class="bi bi-check me-1"></i>${name}</span> <span class="badge bg-light text-dark border ms-1">NIP: ${nip}</span> <span class="badge bg-light text-dark border ms-1">${jabatan}</span>`);
});

// Close undangan autocomplete on outside click
$(document).on('click', function(e) {
  if (!$(e.target).closest('#undangan-sub-guru').length) {
    $('#undangan-guru-autocomplete').hide();
  }
  if (!$(e.target).closest('#undangan-sub-pegawai').length) {
    $('#undangan-pegawai-autocomplete').hide();
  }
});

// Dinamis / Manual: add row
let undanganDinamisCount = 1;
$('#btn-add-undangan-dinamis').on('click', function() {
  undanganDinamisCount++;
  const row = `<div class="input-group mb-2 undangan-dinamis-row">
    <span class="input-group-text" style="font-size:0.82rem;">${undanganDinamisCount}</span>
    <input type="text" class="form-control undangan-dinamis-input" placeholder="Ketik nama penerima..." autocomplete="off">
    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-dinamis" title="Hapus"><i class="bi bi-trash"></i></button>
  </div>`;
  $('#undangan-dinamis-rows').append(row);
});

// Dinamis / Manual: remove row
$(document).on('click', '.btn-remove-dinamis', function() {
  if ($('.undangan-dinamis-row').length > 1) {
    $(this).closest('.undangan-dinamis-row').remove();
    // Re-number
    $('.undangan-dinamis-row').each((i, el) => $(el).find('.input-group-text').text(i + 1));
    undanganDinamisCount = $('.undangan-dinamis-row').length;
    updateUndanganDinamisRecipientName();
  }
});

// Dinamis / Manual: sync recipient_name on input
$(document).on('input', '.undangan-dinamis-input', function() {
  updateUndanganDinamisRecipientName();
});

// Klik placeholder → sisipkan ke CKEditor 5
$(document).on('click', '.placeholder-btn', function(e) {
  e.preventDefault();
  const placeholder = $(this).data('placeholder');
  if (editorInstance) {
    editorInstance.model.change(writer => {
      const insertPosition = editorInstance.model.document.selection.getFirstPosition();
      if (insertPosition) {
        writer.insertText(placeholder, insertPosition);
      }
    });
    editorInstance.editing.view.focus();
  } else {
    alert('Editor belum siap. Silakan klik di dalam area editor terlebih dahulu.');
  }
});

// Sebelum submit form, sinkronisasi konten CKEditor ke hidden input
$('#form-surat-keluar').on('submit', function(e) {
  if (selectedType === 'surat_custom' && editorInstance) {
    const bodyData = editorInstance.getData();
    $('#body_html_hidden').val(bodyData);
    if (!bodyData.trim()) {
      alert('Isi surat (body) wajib diisi untuk Surat Custom!');
      e.preventDefault();
      return false;
    }
  }
});

// Toggle manual vs auto letter number input
$('input[name="number_mode"]').on('change', function() {
  const isManual = (this.value === 'manual');
  $('#manual-number-wrapper').toggle(isManual);
  if (isManual) {
    $('#nomor_surat_manual').focus();
  }
});

// ─── Preview Surat ────────────────────────────────────────────────────────────

// Helper: escape HTML untuk teks yang dimasukkan ke dalam HTML
function escHtml(str) {
  if (!str) return '-';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Helper: format tanggal ke format Indonesia
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

// Tampilkan/sembunyikan tombol Preview sesuai tipe surat yang dipilih
$('input[name="letter_type"]').on('change', function() {
  $('#btn-preview-surat').toggle(!!this.value);
});

// Tombol preview diklik
$('#btn-preview-surat').on('click', function() {
  if (!selectedType) {
    alert('Silakan pilih tipe surat terlebih dahulu.');
    return;
  }

  // Kumpulkan nilai dari form
  const recipientName = $('#recipient_name').val() || '-';
  const issuedAt     = $('#issued_at').val();
  const subject      = $('#subject').val() || '-';
  const sifat        = $('#sifat').val() || 'Biasa';

  // Determine letter number display
  const numberMode   = $('input[name="number_mode"]:checked').val() || 'auto';
  const manualNo     = $('#nomor_surat_manual').val().trim();
  const displayLetterNo = (numberMode === 'manual' && manualNo) ? manualNo : (NEXT_LETTER_NUMBER || '[Auto-Generate]');

  // Data penerima
  const nisn    = $('#f_nisn').val()    || '-';
  const kelas   = $('#f_kelas').val()   || '-';
  const ttl     = $('#f_ttl').val()     || '-';
  const nip     = $('#f_nip').val()     || '-';
  const jabatan = $('#f_jabatan').val() || '-';
  const alamat  = $('#f_alamat').val()  || '-';
  const nik     = $('#f_nik').val()     || '-';
  const ekstName = $('#recipient_name_eksternal').val() || recipientName;

  const tanggalSurat = formatIndoDate(issuedAt);

  // ── Render KOP ──
  let kopHtml = '';
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

  let headerHtml = '';
  let bodyHtml = '';

  if (selectedType === 'surat_custom') {
    if (!editorInstance) {
      alert('Silakan tulis isi surat terlebih dahulu.');
      return;
    }
    const rawBodyHtml = editorInstance.getData();
    const headerStyle  = $('input[name="header_style"]:checked').val() || 'tengah';

    let recNameForPlaceholder = (recipientName || ekstName || '-');
    if (recNameForPlaceholder.includes('|')) {
      recNameForPlaceholder = recNameForPlaceholder.split(/\s*\|\s*/).filter(Boolean).join(', ');
    }

    // Ganti semua placeholder
    let processedBody = rawBodyHtml;
    const map = {
      '{nama_penerima}':      recNameForPlaceholder,
      '{nisn}':               nisn,
      '{kelas}':              kelas,
      '{ttl}':                ttl,
      '{nip}':                nip,
      '{jabatan}':            jabatan,
      '{nomor_surat}':        displayLetterNo,
      '{tanggal_surat}':      tanggalSurat,
      '{tahun_pelajaran}':    ACTIVE_YEAR || '-',
      '{kepala_sekolah}':     PRINCIPAL_NAME || '-',
      '{nip_kepala_sekolah}': PRINCIPAL_NIP  || '-',
      '{nama_sekolah}':       SCHOOL_NAME    || '-',
      '{alamat_sekolah}':     SCHOOL_ADDRESS || '-',
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
                <tr>
                  <td style="width:70px;padding:2px 0;">Nomor</td>
                  <td style="width:14px;text-align:center;padding:2px 0;">:</td>
                  <td style="padding:2px 0;">${escHtml(displayLetterNo)}</td>
                </tr>
                <tr>
                  <td style="padding:2px 0;">Sifat</td>
                  <td style="text-align:center;padding:2px 0;">:</td>
                  <td style="padding:2px 0;">${escHtml(sifat)}</td>
                </tr>
                <tr>
                  <td style="padding:2px 0;">Hal</td>
                  <td style="text-align:center;padding:2px 0;">:</td>
                  <td style="padding:2px 0;"><strong>${escHtml(subject)}</strong></td>
                </tr>
              </table>
            </td>
            <td style="width:45%;vertical-align:top;padding-left:20px;">
              <div style="line-height:1.6;">
                ${escHtml(tanggalSurat)}<br><br>
                Kepada<br>
                Yth. ${formatRecipientLinesForPreview(recipientName || ekstName)}<br>
                di &nbsp;-&nbsp; <strong>Tempat</strong>
              </div>
            </td>
          </tr>
        </table>`;
    }
    bodyHtml = processedBody;

  } else if (selectedType === 'keterangan_mutasi_masuk') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT KETERANGAN</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    bodyHtml = `
      <div style="margin-bottom: 12px;">Yang bertanda tangan di bawah ini :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NAME)}</td></tr>
          <tr><td class="label">NIP</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NIP)}</td></tr>
          <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
          <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
      </table>

      <div style="margin-bottom: 12px; margin-top: 12px;">Dengan ini menerangkan bahwa :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
          <tr><td class="label">NISN</td><td class="colon">:</td><td>${escHtml(nisn)}</td></tr>
          <tr><td class="label">Tempat/Tanggal Lahir</td><td class="colon">:</td><td>${escHtml(ttl)}</td></tr>
          <tr><td class="label">Sekolah Asal</td><td class="colon">:</td><td>${escHtml($('input[name="sekolah_asal"]').val() || '-')} ${$('input[name="alamat_sekolah_asal"]').val() ? ' – ' + escHtml($('input[name="alamat_sekolah_asal"]').val()) : ''}</td></tr>
      </table>

      <p style="margin: 12px 0; text-align: justify;">
          Nama tersebut di atas telah kami nyatakan <strong>DITERIMA</strong> di Kelas
          <strong>${escHtml($('input[name="kelas_diterima"]').val() || '-')}</strong> Semester <strong>${escHtml($('select[name="semester"]').val() || '-')}</strong>
          Tahun Pelajaran <strong>${escHtml(ACTIVE_YEAR)}</strong>
          di ${escHtml(SCHOOL_NAME)}.
      </p>

      <p style="margin: 12px 0;">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    `;

  } else if (selectedType === 'keterangan_mengajar') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT KETERANGAN</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    bodyHtml = `
      <div style="margin-bottom: 12px;">
          Yang bertanda tangan di bawah ini kepala ${escHtml(SCHOOL_NAME)} menerangkan bahwa :
      </div>

      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
          <tr><td class="label">NIK</td><td class="colon">:</td><td>${escHtml($('input[name="nik_guru"]').val() || '-')}</td></tr>
          <tr><td class="label">Jabatan</td><td class="colon">:</td><td>${escHtml(jabatan !== '-' ? jabatan : ($('input[name="jabatan"]').val() || 'Guru Kelas'))}</td></tr>
          <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
          <tr><td class="label">Pekerjaan</td><td class="colon">:</td><td>Guru</td></tr>
          <tr><td class="label">No. Telp/HP</td><td class="colon">:</td><td>${escHtml($('input[name="no_hp"]').val() || '-')}</td></tr>
          <tr>
              <td class="label" style="vertical-align:top;">Satuan Pendidikan</td>
              <td class="colon" style="vertical-align:top;">:</td>
              <td style="padding:0;">
                  <table style="width:100%; border:none; border-collapse:collapse; margin:0;">
                      <tr style="border:none;"><td style="width:120px; padding:2px 0; border:none;">a. Nama Satuan</td><td style="width:10px; padding:2px 0; border:none;">:</td><td style="padding:2px 0; border:none;">${escHtml($('input[name="satuan_pendidikan"]').val() || SCHOOL_NAME)}</td></tr>
                      <tr style="border:none;"><td style="padding:2px 0; border:none;">b. Alamat Satuan</td><td style="padding:2px 0; border:none;">:</td><td style="padding:2px 0; border:none;">${escHtml($('input[name="alamat_satuan"]').val() || SCHOOL_ADDRESS)}</td></tr>
                  </table>
              </td>
          </tr>
          <tr><td class="label">Alamat Tinggal</td><td class="colon">:</td><td>${escHtml($('input[name="alamat_tinggal"]').val() || '-')}</td></tr>
      </table>

      <p style="margin: 12px 0; text-align: justify;">
          Nama tersebut di atas adalah benar <strong>Guru</strong> ${escHtml(SCHOOL_NAME)}
          yang saat ini mengajar di kelas <strong>${escHtml($('input[name="kelas_mengajar"]').val() || '-')}</strong>
          pada tahun pelajaran <strong>${escHtml(ACTIVE_YEAR)}</strong>.
      </p>

      <p style="margin: 12px 0;">Demikian keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
    `;

  } else if (selectedType === 'keterangan_aktif') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT KETERANGAN</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    bodyHtml = `
      <div style="margin-bottom: 12px;">Yang bertanda tangan di bawah ini :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NAME)}</td></tr>
          <tr><td class="label">NIP</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NIP)}</td></tr>
          <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
          <tr><td class="label">Unit Kerja</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
      </table>

      <div style="margin-bottom: 12px; margin-top: 12px;">Menerangkan bahwa :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
          <tr><td class="label">NISN</td><td class="colon">:</td><td>${escHtml(nisn)}</td></tr>
          <tr><td class="label">Tempat, tanggal lahir</td><td class="colon">:</td><td>${escHtml(ttl)}</td></tr>
          <tr><td class="label">Asal Sekolah</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
          <tr><td class="label">Alamat Sekolah</td><td class="colon">:</td><td>${escHtml(SCHOOL_ADDRESS)}</td></tr>
      </table>

      <p style="margin: 12px 0; text-align: justify;">
          adalah benar-benar tercatat sebagai siswa aktif di
          <strong>${escHtml(SCHOOL_NAME)}</strong>
          yang saat ini duduk di kelas <strong>${escHtml(kelas)}</strong> pada Tahun Pelajaran <strong>${escHtml(ACTIVE_YEAR)}</strong> dan masih aktif terdaftar sebagai siswa hingga saat ini.
          ${ $('input[name="keperluan_tambahan"]').val() ? `<br>Surat keterangan ini diberikan untuk keperluan <strong>${escHtml($('input[name="keperluan_tambahan"]').val())}</strong>.` : '' }
      </p>

      <p style="margin: 12px 0;">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    `;

  } else if (selectedType === 'keterangan_lomba') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT KETERANGAN</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    let rowsHtml = '';
    $('#recipient-rows tr').each(function(idx) {
      if ($(this).attr('id') === 'row-template') return; // Skip template row
      const name = $(this).find('input[name="rec_name[]"]').val() || '-';
      const birth = $(this).find('input[name="rec_birth_date[]"]').val() || '-';
      const kls = $(this).find('input[name="rec_kelas[]"]').val() || '-';
      const cb = $(this).find('input[name="rec_cabang[]"]').val() || '-';
      
      const birthFormatted = birth !== '-' ? formatIndoDate(birth) : '-';

      rowsHtml += `<tr>
        <td style="text-align: center; padding: 4px 5px;">${idx + 1}.</td>
        <td style="padding: 4px 5px;"><strong>${escHtml(name)}</strong></td>
        <td style="text-align: center; padding: 4px 5px;">${escHtml(birthFormatted)}</td>
        <td style="text-align: center; padding: 4px 5px;">${escHtml(kls)}</td>
        <td style="padding: 4px 5px;">${escHtml(cb)}</td>
      </tr>`;
    });

    bodyHtml = `
      <div style="margin-bottom: 12px;">
          Yang bertanda tangan di bawah ini kepala ${escHtml(SCHOOL_NAME)} menerangkan bahwa Daftar Siswa (terlampir) :
      </div>

      <table style="width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 13px;" border="1">
          <thead>
              <tr style="background:#f0f0f0;">
                  <th style="width: 35px; text-align: center; padding: 4px 5px;">NO</th>
                  <th style="text-align: left; padding: 4px 5px;">NAMA</th>
                  <th style="width: 110px; text-align: center; padding: 4px 5px;">TANGGAL LAHIR</th>
                  <th style="width: 60px; text-align: center; padding: 4px 5px;">KELAS</th>
                  <th style="text-align: left; padding: 4px 5px;">CABANG</th>
              </tr>
          </thead>
          <tbody>
              ${rowsHtml || '<tr><td colspan="5" style="text-align:center;">Belum ada siswa ditambahkan</td></tr>'}
          </tbody>
      </table>

      <p style="margin: 12px 0; text-align: justify;">
          adalah benar-benar tercatat sebagai siswa ${escHtml(SCHOOL_NAME)} pada tahun pelajaran ${escHtml(ACTIVE_YEAR)} untuk mengikuti kegiatan lomba/event <strong>${escHtml($('input[name="event_name"]').val() || '-')}</strong> yang diselenggarakan oleh <strong>${escHtml($('input[name="event_organizer"]').val() || '-')}</strong>.
      </p>

      <p style="margin: 12px 0;">Demikian keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
    `;

  } else if (selectedType === 'keterangan_kjp') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT KETERANGAN</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    bodyHtml = `
      <div style="margin-bottom: 12px;">Yang bertanda tangan di bawah ini :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NAME)}</td></tr>
          <tr><td class="label">NIP</td><td class="colon">:</td><td>${escHtml(PRINCIPAL_NIP)}</td></tr>
          <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
          <tr><td class="label">Unit Kerja</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
      </table>

      <div style="margin-bottom: 12px; margin-top: 12px;">Menerangkan bahwa :</div>
      <table style="margin-bottom:12px; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label">Nama</td><td class="colon">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
          <tr><td class="label">NISN</td><td class="colon">:</td><td>${escHtml(nisn)}</td></tr>
          <tr><td class="label">NIK</td><td class="colon">:</td><td>${escHtml(nik)}</td></tr>
          <tr><td class="label">Tempat/Tanggal Lahir</td><td class="colon">:</td><td>${escHtml(ttl)}</td></tr>
          <tr><td class="label">Alamat Domisili</td><td class="colon">:</td><td>${escHtml($('input[name="alamat_domisili"]').val() || '-')}</td></tr>
      </table>

      <p style="margin: 12px 0; text-align: justify;">
          adalah benar-benar tercatat sebagai siswa aktif di
          <strong>${escHtml(SCHOOL_NAME)}</strong>
          yang saat ini duduk di kelas <strong>${escHtml(kelas)}</strong> pada Tahun Pelajaran <strong>${escHtml(ACTIVE_YEAR)}</strong> dan ingin melakukan <strong>${escHtml($('textarea[name="keperluan_detail"]').val() || '-')}</strong>${ $('input[name="lampiran_keterangan"]').val() ? ` (${escHtml($('input[name="lampiran_keterangan"]').val())})` : '' }.
      </p>

      <p style="margin: 12px 0;">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    `;

  } else if (selectedType === 'surat_tugas') {
    headerHtml = `
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:18px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:0.5px;">SURAT TUGAS</div>
        <div style="font-size:15px;margin-top:5px;">Nomor: ${escHtml(displayLetterNo)}</div>
      </div>`;

    const actDate = $('input[name="activity_date"]').val() ? formatIndoDate($('input[name="activity_date"]').val()) : '-';

    bodyHtml = `
      ${ $('input[name="ref_letter_number"]').val() ? `
      <p style="margin: 12px 0; text-align: justify; line-height: 1.6;">
          Menindaklanjuti surat ${escHtml($('input[name="ref_letter_from"]').val() || '-')} nomor ${escHtml($('input[name="ref_letter_number"]').val())}
          ${ $('input[name="ref_letter_date"]').val() ? `tanggal ${formatIndoDate($('input[name="ref_letter_date"]').val())}` : '' }
          perihal ${escHtml($('input[name="ref_letter_subject"]').val() || '-')}. Dengan ini kepala ${escHtml(SCHOOL_NAME)} memberikan tugas kepada :
      </p>
      ` : `
      <p style="margin: 12px 0; text-align: justify; line-height: 1.6;">
          Dengan ini kepala ${escHtml(SCHOOL_NAME)} memberikan tugas kepada :
      </p>
      ` }

      <table style="margin: 12px 0; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label" style="width: 150px;">Nama</td><td class="colon" style="width: 14px;">:</td><td><strong>${escHtml(recipientName)}</strong></td></tr>
          <tr><td class="label">NIP</td><td class="colon">:</td><td>${escHtml(nip !== '-' ? nip : ($('input[name="nip"]').val() || '-'))}</td></tr>
          <tr><td class="label">Jabatan</td><td class="colon">:</td><td>${escHtml(jabatan !== '-' ? jabatan : ($('input[name="jabatan"]').val() || 'Guru'))}</td></tr>
          <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td>${escHtml(SCHOOL_NAME)}</td></tr>
          <tr><td class="label">Alamat Tempat Tugas</td><td class="colon">:</td><td>${escHtml(SCHOOL_ADDRESS)}</td></tr>
      </table>

      <p style="margin: 12px 0;">Untuk mengikuti kegiatan <strong>${escHtml($('input[name="activity_name"]').val() || '-')}</strong> yang akan dilaksanakan pada :</p>

      <table style="margin: 12px 0; width:100%; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label" style="width: 150px;">Tanggal</td><td class="colon" style="width: 14px;">:</td><td>${escHtml(actDate)}</td></tr>
          <tr><td class="label">Waktu</td><td class="colon">:</td><td>Pukul ${escHtml($('input[name="activity_time"]').val() || '-')} WIB s.d selesai</td></tr>
          <tr><td class="label">Tempat</td><td class="colon">:</td><td>${escHtml($('input[name="activity_venue"]').val() || '-')}</td></tr>
          <tr><td class="label">Alamat</td><td class="colon">:</td><td>${escHtml($('input[name="activity_address"]').val() || '-')}</td></tr>
      </table>

      <p style="margin: 12px 0;">Demikian surat tugas ini dibuat agar dilaksanakan dengan penuh tanggung jawab.</p>
    `;

  } else if (selectedType === 'undangan') {
    // Determine recipient display based on undangan mode
    const undanganMode = $('input[name="undangan_recipient_mode"]:checked').val() || 'all';
    let previewRecipientLines = [];
    let previewBodyRecipient = escHtml(recipientName);
    let previewPlace = 'Tempat';

    if (undanganMode === 'all') {
      previewRecipientLines = ['Guru & Tendik', SCHOOL_NAME];
      previewBodyRecipient = 'Bapak/Ibu Guru dan Tenaga Kependidikan ' + escHtml(SCHOOL_NAME);
      previewPlace = 'Jakarta';
    } else if (undanganMode === 'guru' || undanganMode === 'pegawai') {
      previewRecipientLines = [recipientName || '-'];
      previewBodyRecipient = escHtml(recipientName || '-');
      previewPlace = 'Jakarta';
    } else if (undanganMode === 'wali_murid') {
      const kelasVal = $('#undangan_wali_class').val();
      if (kelasVal === 'semua') {
        previewRecipientLines = ['Orang Tua / Wali Murid', SCHOOL_NAME];
        previewBodyRecipient = 'Bapak/Ibu Orang Tua / Wali Murid Siswa/i ' + escHtml(SCHOOL_NAME);
      } else {
        previewRecipientLines = ['Orang Tua / Wali Murid', 'Kelas ' + kelasVal + ' ' + SCHOOL_NAME];
        previewBodyRecipient = 'Bapak/Ibu Orang Tua / Wali Murid Siswa/i Kelas ' + escHtml(kelasVal) + ' ' + escHtml(SCHOOL_NAME);
      }
      previewPlace = 'Tempat';
    } else if (undanganMode === 'dinamis') {
      // Multi-recipient: split by pipe
      const dynamicNames = recipientName.split('|').map(s => s.trim()).filter(Boolean);
      if (dynamicNames.length > 1) {
        previewRecipientLines = dynamicNames;
        previewBodyRecipient = dynamicNames.map(n => escHtml(n)).join(', ');
      } else {
        previewRecipientLines = [recipientName || '-'];
        previewBodyRecipient = escHtml(recipientName || '-');
      }
      previewPlace = 'Tempat';
    }

    let recipientLinesHtml = '';
    previewRecipientLines.forEach((line, idx) => {
      if (idx === 0) {
        recipientLinesHtml += `<strong>${escHtml(line)}</strong><br>`;
      } else {
        recipientLinesHtml += `${escHtml(line)}<br>`;
      }
    });

    headerHtml = `
      <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:24px;">
        <tr>
          <td style="width:55%;vertical-align:top;">
            <table style="width:100%;border-collapse:collapse;">
              <tr>
                <td style="width:70px;padding:2px 0;">Nomor</td>
                <td style="width:14px;text-align:center;padding:2px 0;">:</td>
                <td style="padding:2px 0;">${escHtml(displayLetterNo)}</td>
              </tr>
              <tr>
                <td style="padding:2px 0;">Sifat</td>
                <td style="text-align:center;padding:2px 0;">:</td>
                <td style="padding:2px 0;">${escHtml(sifat)}</td>
              </tr>
              <tr>
                <td style="padding:2px 0;">Hal</td>
                <td style="text-align:center;padding:2px 0;">:</td>
                <td style="padding:2px 0;"><strong>${escHtml(subject)}</strong></td>
              </tr>
            </table>
          </td>
          <td style="width:45%;vertical-align:top;padding-left:20px;">
            <div style="line-height:1.6;">
              ${escHtml(tanggalSurat)}<br><br>
              Kepada<br>
              Yth. ${recipientLinesHtml}
              di &nbsp;-&nbsp; <strong>${previewPlace}</strong>
            </div>
          </td>
        </tr>
      </table>`;

    bodyHtml = `
      <p style="margin: 12px 0;">Dengan hormat,</p>
      <p style="margin: 12px 0;">
          Dengan ini kami mengundang ${previewBodyRecipient} untuk hadir pada :
      </p>

      <table style="margin: 12px 0 12px 30px; width:auto; border-collapse:collapse;" class="data-tabel">
          <tr><td class="label" style="width: 120px; padding: 2px 0;">Hari, Tanggal</td><td class="colon" style="width: 14px; text-align: center; padding: 2px 0;">:</td><td>${escHtml($('input[name="event_day"]').val() || '-')}, ${formatIndoDate($('input[name="event_date"]').val())}</td></tr>
          ${ $('input[name="event_time"]').val() ? `<tr><td class="label" style="padding: 2px 0;">Pukul</td><td class="colon" style="text-align: center; padding: 2px 0;">:</td><td>${escHtml($('input[name="event_time"]').val())} s/d selesai</td></tr>` : '' }
          <tr><td class="label" style="padding: 2px 0;">Tempat</td><td class="colon" style="text-align: center; padding: 2px 0;">:</td><td>${escHtml($('input[name="event_venue"]').val() || '-')}</td></tr>
          <tr><td class="label" style="padding: 2px 0;">Acara</td><td class="colon" style="text-align: center; padding: 2px 0;">:</td><td>${escHtml($('input[name="event_agenda"]').val() || '-')}</td></tr>
      </table>

      <p style="margin: 16px 0 24px 0; text-align: justify;">
          Demikian undangan ini kami sampaikan, Atas perhatiannya diucapkan terima kasih.
      </p>
    `;
  }

  // ── Render TTD & QR Code ──
  let ttdHtml = '';
  if (selectedType === 'undangan') {
    ttdHtml = `
      <div style="margin-top: 30px;">
          <div style="float: left; text-align: center; margin-top: 12px; border: 1px dashed #ddd; padding: 8px; color: #aaa; font-size: 11px;">
              [QR Code]
          </div>
          <div style="float: right; width: 220px; text-align: center; font-size: 14px;">
              Kepala ${escHtml(SCHOOL_NAME)}<br>
              <div style="margin-top: 52px; border-top: 1px solid #000; width: 100%;"></div>
              <strong>${escHtml(PRINCIPAL_NAME)}</strong><br>
              NIP. ${escHtml(PRINCIPAL_NIP)}
          </div>
          <div style="clear: both;"></div>
      </div>`;
  } else {
    ttdHtml = `
      <div style="margin-top: 30px;">
          <div style="float: left; text-align: center; margin-top: 12px; border: 1px dashed #ddd; padding: 8px; color: #aaa; font-size: 11px;">
              [QR Code]
          </div>
          <div style="float: right; width: 220px; text-align: center; font-size: 14px;">
              Jakarta, ${escHtml(tanggalSurat)}<br>
              Kepala ${escHtml(SCHOOL_NAME)}<br>
              <div style="margin-top: 52px; border-top: 1px solid #000; width: 100%;"></div>
              <strong>${escHtml(PRINCIPAL_NAME)}</strong><br>
              NIP. ${escHtml(PRINCIPAL_NIP)}
          </div>
          <div style="clear: both;"></div>
      </div>`;
  }

  // ── Render Body CSS Styles ──
  const bodyStyle = `
    <style>
      #preview-body-area table { width:100% !important; border-collapse:collapse !important; margin:12px 0 !important; }
      #preview-body-area table th, #preview-body-area table td { border:1px solid #000 !important; padding:6px 8px !important; font-size:14px; }
      #preview-body-area table th { background:#f2f2f2 !important; font-weight:bold; }
      #preview-body-area p { margin:8px 0; line-height:1.6; text-align:justify; }
      #preview-body-area ul, #preview-body-area ol { margin:8px 0; padding-left:20px; }
      #preview-body-area li { margin-bottom:4px; }
      #preview-body-area table.data-tabel, #preview-body-area table.data-tabel td { border: none !important; padding: 2px 0 !important; }
      #preview-body-area table.data-tabel td.label { width: 160px; }
      #preview-body-area table.data-tabel td.colon { width: 14px; text-align: center; }
    </style>`;

  // ── Inject ke modal ──
  $('#preview-kop-area').html(kopHtml);
  $('#preview-header-area').html(headerHtml);
  $('#preview-body-area').html(bodyStyle + bodyHtml + ttdHtml);

  const previewModal = new bootstrap.Modal(document.getElementById('modal-preview-surat'));
  previewModal.show();
});

// Tombol "Lanjut Generate PDF" di modal → trigger submit form
$('#btn-from-preview-submit').on('click', function() {
  $('#btn-submit-surat').trigger('click');
});
</script>
<?= $this->endSection() ?>
