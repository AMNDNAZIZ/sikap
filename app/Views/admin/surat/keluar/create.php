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
  <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
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

          <!-- Penerima Internal (Undangan) -->
          <div id="recipient-internal-section" style="display:none;">
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>
              Penerima otomatis: <strong>Guru &amp; Tenaga Kependidikan SDN Mangga Besar 11 Pagi</strong>
            </p>
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
          <button type="submit" class="btn btn-primary w-100" id="btn-submit-surat" disabled>
            <i class="bi bi-send me-2"></i>Buat Surat & Generate PDF
          </button>
          <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary w-100 mt-2">
            Batal
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const LETTER_TYPES = <?= json_encode($letter_types) ?>;
const SISWA_SEARCH_URL = '<?= base_url('admin/surat-keluar/search-siswa') ?>';
const GURU_SEARCH_URL  = '<?= base_url('admin/surat-keluar/search-guru') ?>';

// Mapping: jenis surat → tipe penerima
const RECIPIENT_MAP = {
  'keterangan_mutasi_masuk': 'siswa',
  'keterangan_mengajar':     'guru',
  'keterangan_aktif':        'siswa',
  'keterangan_lomba':        'multi',
  'keterangan_kjp':          'siswa',
  'surat_tugas':             'guru',
  'undangan':                'internal',
};

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
  const rtype = RECIPIENT_MAP[selectedType] || 'siswa';
  $('#recipient-siswa-section').toggle(rtype === 'siswa');
  $('#recipient-guru-section').toggle(rtype === 'guru');
  $('#recipient-internal-section').toggle(rtype === 'internal');
  $('#recipient_type').val(rtype === 'multi' ? 'siswa' : rtype);

  // Show/hide visible details fields
  if (rtype === 'siswa') {
    $('#siswa-detail-fields').show();
    $('#guru-detail-fields').hide();
  } else if (rtype === 'guru') {
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').show();
  } else {
    $('#siswa-detail-fields').hide();
    $('#guru-detail-fields').hide();
  }

  // Set recipient_name untuk internal / multi
  if (rtype === 'internal') {
    $('#recipient_name').val('Guru & Tenaga Kependidikan SDN Mangga Besar 11 Pagi');
  } else if (rtype === 'multi') {
    $('#recipient_name').val('Daftar Terlampir');
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
</script>
<?= $this->endSection() ?>
