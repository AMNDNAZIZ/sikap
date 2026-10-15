<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h4 class="fw-bold mb-1">📋 Kelola Rapor Kelas: <?= esc($class['name']) ?></h4>
      <p class="text-muted mb-0">
        Wali Kelas: <strong><?= esc($class['wali_name'] ?? '-') ?></strong> |
        Tahun Ajaran: <strong><?= esc($activeYear['year'] ?? '-') ?></strong> |
        Semester: <strong><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></strong>
      </p>
    </div>
    <div class="d-flex gap-2">
      <!-- Semester switcher -->
      <div class="btn-group" role="group">
        <a href="<?= site_url("admin/rapor-semester/kelas/{$class['id']}?semester=1") ?>"
           class="btn btn-sm <?= $semester === '1' ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 1
        </a>
        <a href="<?= site_url("admin/rapor-semester/kelas/{$class['id']}?semester=2") ?>"
           class="btn btn-sm <?= $semester === '2' ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 2
        </a>
      </div>

      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalConfig">
        ⚙️ Titimangsa
      </button>

      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          🖨️ Cetak Seluruh Kelas
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li>
            <a class="dropdown-item py-2" href="<?= site_url("admin/rapor-semester/print/{$class['id']}/{$semester}") ?>" target="_blank">
              <i class="bi bi-file-earmark-text text-success me-2"></i><strong>Cetak Nilai Semester</strong><br>
              <small class="text-muted ps-4">Lembar 1 (Nilai) & Lembar 2 (Kokurikuler, Ekskul, Catatan)</small>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item py-2" href="<?= site_url("admin/rapor-semester/print-awal/{$class['id']}") ?>" target="_blank">
              <i class="bi bi-journal-album text-primary me-2"></i><strong>Cetak Kelengkapan Rapor</strong><br>
              <small class="text-muted ps-4">Cover Depan, Petunjuk, Profil, Biodata & Lembar Mutasi Seluruh Siswa</small>
            </a>
          </li>
        </ul>
      </div>

      <a href="<?= site_url('admin/rapor-semester') ?>" class="btn btn-sm btn-secondary">
        ⬅ Kembali
      </a>
    </div>
  </div>

  <!-- Info Kokurikuler Kelas -->
  <div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body py-2">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <span class="badge bg-primary me-2"><i class="bi bi-stars"></i> Kokurikuler Semester Ini</span>
          <?php if (!empty($kokuDocs)): ?>
            <?php foreach ($kokuDocs as $kd): ?>
              <span class="fw-bold text-dark me-2">Tema: <?= esc($kd['tema']) ?> (<?= esc($kd['bentuk_kegiatan_konkret'] ?: '-') ?>)</span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="text-muted small">Belum ada dokumen rencana kokurikuler untuk kelas/level ini pada semester <?= $semester ?>.</span>
          <?php endif; ?>
        </div>
        <div>
          <a href="<?= site_url('admin/kokurikuler') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.8rem;">
            Buka Modul Kokurikuler ➜
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel Siswa -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Daftar Siswa (<?= count($students) ?> Siswa)</h6>
      <span class="small text-muted">Klik tombol "Edit Catatan" untuk mengisi narasi wali kelas & keputusan kenaikan kelas.</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;" class="text-center">#</th>
              <th style="width:130px;">NIS / NISN</th>
              <th style="min-width:180px;">Nama Siswa</th>
              <th>Catatan Wali Kelas</th>
              <?php if ($semester === '2'): ?>
                <th style="width:180px;">Status Kenaikan</th>
              <?php endif; ?>
              <th style="width:200px;" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="<?= $semester === '2' ? '6' : '5' ?>" class="text-center py-4 text-muted">
                  Belum ada siswa aktif terdaftar di kelas ini pada tahun ajaran aktif.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $i => $stu):
                $sid      = (int) $stu['id'];
                $noteItem = $notesMap[$sid] ?? null;
                $tNote    = $noteItem['teacher_note'] ?? '';
                $pStatus  = $noteItem['promotion_status'] ?? 'naik';
                $pClass   = $noteItem['promotion_class_name'] ?? '';
              ?>
                <tr>
                  <td class="text-center"><?= $i + 1 ?></td>
                  <td class="small text-muted"><?= esc($stu['nis'] ?: '-') ?> / <?= esc($stu['nisn'] ?: '-') ?></td>
                  <td>
                    <strong><?= esc($stu['name']) ?></strong>
                  </td>
                  <td>
                    <?php if (!empty($tNote)): ?>
                      <span class="small text-dark" id="display-note-<?= $sid ?>">
                        <?= esc(mb_substr($tNote, 0, 95)) ?><?= mb_strlen($tNote) > 95 ? '...' : '' ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning small" id="display-note-<?= $sid ?>">
                        Belum Diisi
                      </span>
                    <?php endif; ?>
                  </td>
                  <?php if ($semester === '2'): ?>
                    <td>
                      <?php if ($pStatus === 'naik'): ?>
                        <span class="badge bg-success-subtle text-success" id="display-prom-<?= $sid ?>">
                          <i class="bi bi-arrow-up-circle me-1"></i>Naik ke <?= esc($pClass ?: 'Kelas Berikutnya') ?>
                        </span>
                      <?php elseif ($pStatus === 'tinggal'): ?>
                        <span class="badge bg-danger-subtle text-danger" id="display-prom-<?= $sid ?>">
                          <i class="bi bi-x-circle me-1"></i>Tinggal di Kelas Ini
                        </span>
                      <?php elseif ($pStatus === 'lulus'): ?>
                        <span class="badge bg-primary-subtle text-primary" id="display-prom-<?= $sid ?>">
                          <i class="bi bi-mortarboard me-1"></i>Lulus Satuan Pendidikan
                        </span>
                      <?php else: ?>
                        <span class="text-muted small" id="display-prom-<?= $sid ?>">-</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <button type="button"
                              class="btn btn-sm btn-outline-primary btn-edit-note"
                              data-student-id="<?= $sid ?>"
                              data-student-name="<?= esc($stu['name']) ?>"
                              data-teacher-note="<?= esc($tNote) ?>"
                              data-promotion-status="<?= esc($pStatus) ?>"
                              data-promotion-class="<?= esc($pClass) ?>"
                              title="Edit Catatan & Kenaikan">
                        ✏️ Catatan
                      </button>
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih Format Cetak">
                          🖨️ Cetak
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                          <li>
                            <a class="dropdown-item py-1" href="<?= site_url("admin/rapor-semester/print-student/{$class['id']}/{$semester}/{$sid}") ?>" target="_blank">
                              <i class="bi bi-file-earmark-text text-success me-2"></i>Cetak Nilai Semester
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item py-1" href="<?= site_url("admin/rapor-semester/print-awal-student/{$class['id']}/{$sid}") ?>" target="_blank">
                              <i class="bi bi-journal-album text-primary me-2"></i>Cetak Kelengkapan Rapor
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Catatan Wali Kelas & Kenaikan Kelas -->
<div class="modal fade" id="modalEditNote" tabindex="-1" aria-labelledby="modalEditNoteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditNoteLabel">✏️ Catatan Wali Kelas & Keputusan Kenaikan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-note">
        <div class="modal-body">
          <input type="hidden" name="student_id" id="note_student_id">
          <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
          <input type="hidden" name="year_id" value="<?= $activeYear['id'] ?? '' ?>">
          <input type="hidden" name="semester" value="<?= $semester ?>">
          <?= csrf_field() ?>

          <div class="alert alert-info py-2 small mb-3">
            Siswa: <strong id="modal_student_name" class="fs-6">-</strong>
          </div>

          <!-- Catatan Wali Kelas -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label fw-bold mb-0">Catatan Wali Kelas</label>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" style="font-size:0.8rem;">
                  ✨ Pilihan Kalimat Cepat
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                  <li><a class="dropdown-item small preset-note" href="javascript:void(0)" data-text="Ananda menunjukkan perkembangan karakter, kedisiplinan, dan motivasi belajar yang sangat baik. Pertahankan dan terus tingkatkan prestasimu!">🌟 Sangat Baik & Motivasi Tinggi</a></li>
                  <li><a class="dropdown-item small preset-note" href="javascript:void(0)" data-text="Ananda memiliki keaktifan belajar yang baik. Terus kembangkan rasa percaya diri dan ketekunan dalam menyelesaikan setiap tantangan belajar.">👍 Baik & Keaktifan Bagus</a></li>
                  <li><a class="dropdown-item small preset-note" href="javascript:void(0)" data-text="Tingkatkan terus konsentrasi, kedisiplinan, dan keaktifan dalam proses belajar agar potensi yang dimiliki dapat berkembang lebih optimal.">💪 Butuh Peningkatan Disiplin</a></li>
                  <li><a class="dropdown-item small preset-note" href="javascript:void(0)" data-text="Tetap semangat dan rajin mengulang pelajaran di rumah. Jangan ragu untuk berdiskusi dengan guru dan teman saat menemukan kesulitan.">🤝 Pendampingan & Belajar Giat</a></li>
                </ul>
              </div>
            </div>
            <textarea name="teacher_note" id="note_teacher_note" class="form-control" rows="3"
                      placeholder="Tuliskan catatan reflektif, apresiasi proses, dan pesan motivasi untuk ananda..."></textarea>
            <small class="text-muted">Catatan ini akan dicetak pada bagian Catatan Wali Kelas di rapor akhir siswa.</small>
          </div>

          <?php if ($semester === '2'): ?>
            <!-- Keputusan Kenaikan Kelas (Hanya Semester 2) -->
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-award me-1"></i> Keputusan Kenaikan / Kelulusan (Semester Genap)</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold small">Status Keputusan</label>
                  <select name="promotion_status" id="note_promotion_status" class="form-select">
                    <option value="naik">Naik Kelas</option>
                    <option value="tinggal">Tinggal di Kelas Ini</option>
                    <option value="lulus">Lulus Satuan Pendidikan</option>
                  </select>
                </div>
                <div class="col-md-6" id="wrapper_promotion_class">
                  <label class="form-label fw-bold small">Nama Kelas Tujuan (Jika Naik)</label>
                  <input type="text" name="promotion_class_name" id="note_promotion_class_name"
                         class="form-control" placeholder="Contoh: VIII-A / IX-B">
                </div>
              </div>
            </div>
          <?php endif; ?>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btn-save-note">💾 Simpan Catatan Siswa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Pengaturan Titimangsa & Tampilan Cetak -->
<div class="modal fade" id="modalConfig" tabindex="-1" aria-labelledby="modalConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalConfigLabel">⚙️ Pengaturan Penerbitan & Cetak Rapor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-config">
        <div class="modal-body">
          <input type="hidden" name="year_id" value="<?= $activeYear['id'] ?? '' ?>">
          <input type="hidden" name="semester" value="<?= $semester ?>">
          <?= csrf_field() ?>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Tempat / Kota Penerbitan</label>
              <input type="text" name="place_city" id="place_city" class="form-control"
                     value="<?= esc($config['place_city'] ?? '') ?>" placeholder="Contoh: Jakarta / Bandung / Surabaya">
              <small class="text-muted">Nama kota/kabupaten di atas tanda tangan rapor.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Tanggal Pembagian Rapor</label>
              <input type="date" name="report_date" id="report_date" class="form-control"
                     value="<?= esc($config['report_date'] ?? date('Y-m-d')) ?>">
              <small class="text-muted">Tanggal resmi penyerahan buku rapor hasil belajar.</small>
            </div>
          </div>

          <hr class="my-3">

          <!-- Alternatif Tata Letak Tanda Tangan -->
          <div class="mb-3">
            <label class="form-label fw-bold d-block"><i class="bi bi-pen me-1"></i> Alternatif Tata Letak Tanda Tangan</label>
            <div class="row g-2">
              <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100 bg-light">
                  <input class="form-check-input" type="radio" name="ttd_layout" id="ttd_1_baris" value="1_baris"
                         <?= ($config['ttd_layout'] ?? '1_baris') === '1_baris' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold" for="ttd_1_baris">
                    1 Baris (3 Kolom Sejajar)
                  </label>
                  <div class="small text-muted mt-1">
                    Orang Tua/Wali (Kiri), Kepala Sekolah (Tengah), dan Wali Kelas (Kanan) sejajar dalam satu baris.
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100 bg-light">
                  <input class="form-check-input" type="radio" name="ttd_layout" id="ttd_2_baris" value="2_baris"
                         <?= ($config['ttd_layout'] ?? '') === '2_baris' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold" for="ttd_2_baris">
                    2 Baris (Segitiga / Tengah Bawah)
                  </label>
                  <div class="small text-muted mt-1">
                    Orang Tua/Wali (Kiri) sejajar Wali Kelas (Kanan), kemudian Kepala Sekolah di bagian tengah bawah.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Opsi Cetak Tambahan -->
          <div class="mb-3">
            <label class="form-label fw-bold d-block"><i class="bi bi-sliders me-1"></i> Opsi Tampilan Lembar Cetak</label>
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" name="show_signature" id="show_signature" value="1"
                     <?= !empty($config['show_signature']) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="show_signature">
                Tampilkan Foto / Gambar Tanda Tangan Digital (Guru Wali Kelas & Kepala Sekolah)
              </label>
              <div class="small text-muted">Bila dicentang, gambar tanda tangan yang telah diunggah akan otomatis tercetak di atas nama.</div>
            </div>

            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" name="show_watermark" id="show_watermark" value="1"
                     <?= !empty($config['show_watermark']) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="show_watermark">
                Tampilkan Watermark Logo Sekolah di Tiap Lembar Cetak Rapor
              </label>
              <div class="small text-muted">Mencetak logo sekolah transparan elegan di latar tengah lembar cetak rapor.</div>
            </div>
          </div>

          <hr class="my-3">

          <!-- Upload TTD Wali Kelas -->
          <div class="border rounded p-3 bg-light mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-pen-fill me-1 text-primary"></i> Unggah Tanda Tangan Digital Wali Kelas (<?= esc($class['wali_name'] ?? 'Wali Kelas') ?>)</h6>
            <div class="d-flex align-items-center gap-3">
              <div class="flex-grow-1">
                <input type="file" id="teacher_signature_file" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                <div class="small text-muted mt-1">Format PNG transparan / JPG, maks. 2MB.</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-upload-teacher-sig" data-teacher-id="<?= (int) ($class['teacher_id'] ?? 0) ?>">
                ⬆ Upload TTD Wali Kelas
              </button>
            </div>
            <div id="teacher_sig_preview" class="mt-2 <?= empty($class['wali_signature']) ? 'd-none' : '' ?>">
              <span class="small text-success"><i class="bi bi-check-circle me-1"></i>TTD Wali Kelas tersimpan:</span>
              <img src="<?= base_url($class['wali_signature'] ?? '') ?>" alt="TTD Wali Kelas" style="max-height:45px;" class="border p-1 bg-white rounded ms-2">
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="btn-save-config">💾 Simpan Pengaturan Cetak</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
  // Buka modal edit catatan
  $('.btn-edit-note').on('click', function() {
    var sid     = $(this).data('student-id');
    var sname   = $(this).data('student-name');
    var note    = $(this).data('teacher-note');
    var pStatus = $(this).data('promotion-status') || 'naik';
    var pClass  = $(this).data('promotion-class') || '';

    $('#note_student_id').val(sid);
    $('#modal_student_name').text(sname);
    $('#note_teacher_note').val(note);
    $('#note_promotion_status').val(pStatus);
    $('#note_promotion_class_name').val(pClass);

    $('#modalEditNote').modal('show');
  });

  // Preset catatan cepat
  $('.preset-note').on('click', function() {
    var text = $(this).data('text');
    $('#note_teacher_note').val(text);
  });

  // Handle status kenaikan toggle
  $('#note_promotion_status').on('change', function() {
    if ($(this).val() === 'naik') {
      $('#wrapper_promotion_class').show();
    } else {
      $('#wrapper_promotion_class').hide();
    }
  });

  // Simpan catatan via AJAX
  $('#form-note').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#btn-save-note').prop('disabled', true).text('Menyimpan...');

    $.ajax({
      url: "<?= site_url('admin/rapor-semester/save-note') ?>",
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          var sid   = $('#note_student_id').val();
          var note  = $('#note_teacher_note').val();
          var pStat = $('#note_promotion_status').val();
          var pCls  = $('#note_promotion_class_name').val();

          // Update data attributes pada tombol
          var rowBtn = $('button[data-student-id="' + sid + '"]');
          rowBtn.data('teacher-note', note);
          rowBtn.data('promotion-status', pStat);
          rowBtn.data('promotion-class', pCls);

          // Update tampilan inline
          $('#display-note-' + sid).text(note.length > 95 ? note.substring(0, 95) + '...' : (note || 'Belum Diisi'));

          $('#modalEditNote').modal('hide');
        } else {
          alert(res.message || 'Gagal menyimpan.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan koneksi.');
      },
      complete: function() {
        btn.prop('disabled', false).text('💾 Simpan Catatan Siswa');
      }
    });
  });

  // Simpan titimangsa
  $('#form-config').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#btn-save-config').prop('disabled', true).text('Menyimpan...');

    $.ajax({
      url: "<?= site_url('admin/rapor-semester/save-config') ?>",
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert('Pengaturan cetak & titimangsa berhasil disimpan.');
          location.reload();
        } else {
          alert(res.message || 'Gagal menyimpan pengaturan.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan koneksi.');
      },
      complete: function() {
        btn.prop('disabled', false).text('💾 Simpan Pengaturan Cetak');
      }
    });
  });

  // Upload TTD Wali Kelas
  $('#btn-upload-teacher-sig').on('click', function() {
    var teacherId = $(this).data('teacher-id');
    var fileInput = $('#teacher_signature_file')[0];
    if (!fileInput.files.length) {
      alert('Silakan pilih file gambar tanda tangan wali kelas terlebih dahulu.');
      return;
    }

    var formData = new FormData();
    formData.append('type', 'teacher');
    formData.append('teacher_id', teacherId);
    formData.append('signature_file', fileInput.files[0]);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    var btn = $(this).prop('disabled', true).text('Mengunggah...');

    $.ajax({
      url: "<?= site_url('admin/rapor-semester/upload-signature') ?>",
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert('Tanda tangan wali kelas berhasil diperbarui!');
          $('#teacher_sig_preview').removeClass('d-none').find('img').attr('src', res.filepath);
        } else {
          alert(res.message || 'Gagal mengunggah tanda tangan.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan saat mengunggah.');
      },
      complete: function() {
        btn.prop('disabled', false).text('⬆ Upload TTD Wali Kelas');
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
