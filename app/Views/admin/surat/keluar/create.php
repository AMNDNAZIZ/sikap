<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

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
          <div id="recipient-siswa-section">
            <label class="form-label fw-semibold" for="recipient_search_siswa">Nama Siswa <span class="text-danger">*</span></label>
            <input type="text" id="recipient_search_siswa" class="form-control" placeholder="Ketik nama atau NISN siswa...">
            <input type="hidden" name="recipient_name" id="recipient_name">
            <div id="siswa-autocomplete-result" class="list-group mt-1" style="max-height:200px;overflow-y:auto;"></div>
            <div class="mt-2 text-muted small" id="siswa-detail-preview"></div>
            <!-- Field tersembunyi untuk data siswa -->
            <input type="hidden" name="nisn" id="f_nisn">
            <input type="hidden" name="nik" id="f_nik">
            <input type="hidden" name="ttl" id="f_ttl">
            <input type="hidden" name="kelas" id="f_kelas">
            <input type="hidden" name="alamat_domisili" id="f_alamat">
          </div>

          <!-- Autocomplete Guru -->
          <div id="recipient-guru-section" style="display:none;">
            <label class="form-label fw-semibold" for="recipient_search_guru">Nama Guru <span class="text-danger">*</span></label>
            <input type="text" id="recipient_search_guru" class="form-control" placeholder="Ketik nama atau NIP guru...">
            <div id="guru-autocomplete-result" class="list-group mt-1" style="max-height:200px;overflow-y:auto;"></div>
            <div class="mt-2 text-muted small" id="guru-detail-preview"></div>
            <input type="hidden" name="nip" id="f_nip">
            <input type="hidden" name="jabatan" id="f_jabatan">
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
                    <td><input type="text" name="rec_name[]" class="form-control form-control-sm" required></td>
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
  selectedType = this.value;
  $('#section-common').show();
  $('#section-recipient').show();
  $('#section-dynamic').show();
  $('#btn-submit-surat').prop('disabled', false);
  $('#selected-type-badge').show();
  $('#selected-type-label').text(LETTER_TYPES[selectedType]);

  // Tampilkan field penerima yang sesuai
  const rtype = RECIPIENT_MAP[selectedType] || 'siswa';
  $('#recipient-siswa-section').toggle(rtype === 'siswa');
  $('#recipient-guru-section').toggle(rtype === 'guru');
  $('#recipient-internal-section').toggle(rtype === 'internal');
  $('#recipient_type').val(rtype === 'multi' ? 'siswa' : rtype);

  // Set recipient_name untuk internal
  if (rtype === 'internal') {
    $('#recipient_name').val('Guru & Tenaga Kependidikan SDN Mangga Besar 11 Pagi');
  }

  // Tampilkan section dinamis
  $('.dynamic-section').hide();
  $(`.dynamic-section[data-type="${selectedType}"]`).show();
});

// Autocomplete Siswa
let siswaTimer;
$('#recipient_search_siswa').on('input', function() {
  clearTimeout(siswaTimer);
  const q = $(this).val();
  if (q.length < 2) return;
  siswaTimer = setTimeout(() => {
    $.getJSON(SISWA_SEARCH_URL, { q }, function(data) {
      const list = $('#siswa-autocomplete-result').empty();
      data.forEach(s => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-1 px-2 small siswa-item"
          data-id="${s.id}" data-name="${s.name}" data-nisn="${s.nisn}"
          data-nik="${s.nik}" data-ttl="${s.ttl}" data-kelas="${s.kelas}">${s.text}</a>`);
      });
    });
  }, 400);
});

$(document).on('click', '.siswa-item', function(e) {
  e.preventDefault();
  const d = $(this).data();
  $('#recipient_name').val(d.name);
  $('#recipient_ref_id').val(d.id);
  $('#recipient_search_siswa').val(d.name);
  $('#siswa-autocomplete-result').empty();
  $('#f_nisn').val(d.nisn);
  $('#f_nik').val(d.nik);
  $('#f_ttl').val(d.ttl);
  $('#f_kelas').val(d.kelas);
  $('#siswa-detail-preview').html(`<span class="badge bg-light text-dark border">NISN: ${d.nisn}</span>
    <span class="badge bg-light text-dark border ms-1">Kelas: ${d.kelas}</span>`);
});

// Autocomplete Guru
let guruTimer;
$('#recipient_search_guru').on('input', function() {
  clearTimeout(guruTimer);
  const q = $(this).val();
  if (q.length < 2) return;
  guruTimer = setTimeout(() => {
    $.getJSON(GURU_SEARCH_URL, { q }, function(data) {
      const list = $('#guru-autocomplete-result').empty();
      data.forEach(t => {
        list.append(`<a href="#" class="list-group-item list-group-item-action py-1 px-2 small guru-item"
          data-id="${t.id}" data-name="${t.name}" data-nip="${t.nip}"
          data-nik="${t.nik}" data-jabatan="${t.jabatan}">${t.text}</a>`);
      });
    });
  }, 400);
});

$(document).on('click', '.guru-item', function(e) {
  e.preventDefault();
  const d = $(this).data();
  $('#recipient_name').val(d.name);
  $('#recipient_ref_id').val(d.id);
  $('#recipient_search_guru').val(d.name);
  $('#guru-autocomplete-result').empty();
  $('#f_nip').val(d.nip);
  $('#f_jabatan').val(d.jabatan);
  $('#guru-detail-preview').html(`<span class="badge bg-light text-dark border">NIP: ${d.nip}</span>
    <span class="badge bg-light text-dark border ms-1">${d.jabatan}</span>`);
});

// Tambah baris siswa (lomba)
let rowCount = 1;
$('#btn-add-recipient').on('click', function() {
  rowCount++;
  const newRow = `<tr>
    <td class="row-no">${rowCount}</td>
    <td><input type="text" name="rec_name[]" class="form-control form-control-sm" required></td>
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
</script>
<?= $this->endSection() ?>
