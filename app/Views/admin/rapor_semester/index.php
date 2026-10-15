<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h3 class="fw-bold mb-1">📘 Rapor Semester (Kurikulum Merdeka)</h3>
      <p class="text-muted mb-0">Laporan Hasil Belajar Akhir Semester Terpadu (Intrakurikuler, Kokurikuler, & Ekstrakurikuler).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <!-- Semester Switcher -->
      <div class="btn-group shadow-sm" role="group">
        <a href="<?= site_url('admin/rapor-semester?semester=1') ?>"
           class="btn btn-sm <?= $selectedSemester === '1' ? 'btn-primary active' : 'btn-outline-primary' ?>">
          Semester 1 (Ganjil)
        </a>
        <a href="<?= site_url('admin/rapor-semester?semester=2') ?>"
           class="btn btn-sm <?= $selectedSemester === '2' ? 'btn-primary active' : 'btn-outline-primary' ?>">
          Semester 2 (Genap)
        </a>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalConfig">
        ⚙️ Titimangsa Rapor
      </button>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Alert Info Standar Kurikulum Merdeka -->
  <div class="alert alert-light border shadow-sm mb-4">
    <div class="d-flex gap-3 align-items-center">
      <div class="display-6 text-primary"><i class="bi bi-info-circle-fill"></i></div>
      <div class="small">
        <strong>Standar Format Rapor Kurikulum Merdeka (PPA Kemendikbudristek):</strong>
        <ul class="mb-0 ps-3 mt-1">
          <li><strong>Intrakurikuler:</strong> Nilai Akhir (0–100) dan Deskripsi Capaian Kompetensi (tanpa predikat huruf A/B/C/D).</li>
          <li><strong>Kokurikuler:</strong> Terintegrasi langsung di lembaran rapor (Tema Projek & Deskripsi Capaian Karakter Profil Lulusan).</li>
          <li><strong>Catatan Wali Kelas & Kenaikan:</strong> Memuat catatan refleksi wali kelas serta status kenaikan kelas (Semester 2).</li>
        </ul>
      </div>
    </div>
  </div>

  <?php if (empty($classes)): ?>
    <div class="alert alert-warning">
      ⚠️ Tidak ada kelas yang tersedia untuk dikelola.
      <?php if (($roleId ?? 0) == 3): ?>
        Anda belum ditugaskan sebagai Wali Kelas pada tahun ajaran aktif ini. Hubungi Administrator.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-mortarboard-fill me-2 text-primary"></i>Daftar Kelas – Tahun Pelajaran <?= esc($activeYear['year'] ?? '-') ?> (Semester <?= $selectedSemester == '1' ? '1 / Ganjil' : '2 / Genap' ?>)</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:50px;" class="text-center">#</th>
                <th>Nama Kelas</th>
                <th>Tingkat / Fase</th>
                <th>Wali Kelas</th>
                <th class="text-center" style="width:110px;">Siswa Aktif</th>
                <th>Kesiapan Rapor</th>
                <th class="text-center" style="min-width:260px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($classes as $cls):
                $cid   = (int) $cls['id'];
                $st    = $classStats[$cid] ?? ['students' => 0, 'subjects' => 0, 'notes' => 0, 'has_koku' => false];
                $faseL = ($cls['level'] <= 2 ? 'Fase A' : ($cls['level'] <= 4 ? 'Fase B' : ($cls['level'] <= 6 ? 'Fase C' : ($cls['level'] <= 8 ? 'Fase D' : ($cls['level'] == 9 ? 'Fase E' : 'Fase F')))));
              ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td>
                    <span class="fw-bold fs-6 text-primary"><?= esc($cls['name']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-secondary-subtle text-secondary">Tingkat <?= esc($cls['level']) ?> (<?= $faseL ?>)</span>
                  </td>
                  <td>
                    <i class="bi bi-person-circle me-1 text-muted"></i><?= esc($cls['wali_name'] ?? 'Belum Ditentukan') ?>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-info text-dark"><?= $st['students'] ?> Siswa</span>
                  </td>
                  <td>
                    <div class="small">
                      <span class="me-2"><i class="bi <?= $st['has_koku'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle text-warning' ?>"></i> Kokurikuler: <?= $st['has_koku'] ? 'Tersedia' : 'Belum Ada' ?></span>
                      <span><i class="bi <?= ($st['notes'] >= $st['students'] && $st['students'] > 0) ? 'bi-check-circle-fill text-success' : 'bi-pencil-square text-secondary' ?>"></i> Catatan Wali: <?= $st['notes'] ?>/<?= $st['students'] ?></span>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <a href="<?= site_url("admin/rapor-semester/kelas/{$cls['id']}?semester={$selectedSemester}") ?>"
                         class="btn btn-sm btn-primary" title="Kelola Catatan & Kenaikan">
                        📂 Kelola Kelas
                      </a>
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                          🖨️ Cetak
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                          <li>
                            <a class="dropdown-item py-2" href="<?= site_url("admin/rapor-semester/print/{$cls['id']}/{$selectedSemester}") ?>" target="_blank">
                              <i class="bi bi-file-earmark-text text-success me-2"></i><strong>Cetak Nilai Semester</strong><br>
                              <small class="text-muted ps-4">Lembar 1 (Nilai) & Lembar 2 (Kokurikuler & Catatan)</small>
                            </a>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item py-2" href="<?= site_url("admin/rapor-semester/print-awal/{$cls['id']}") ?>" target="_blank">
                              <i class="bi bi-journal-album text-primary me-2"></i><strong>Cetak Bagian Awal</strong><br>
                              <small class="text-muted ps-4">Cover Depan, Petunjuk, Profil & Biodata Siswa</small>
                            </a>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item py-2" href="<?= site_url("admin/rapor-semester/print-mutasi/{$cls['id']}") ?>" target="_blank">
                              <i class="bi bi-arrow-left-right text-warning me-2"></i><strong>Cetak Catatan Mutasi</strong><br>
                              <small class="text-muted ps-4">Lembar Pindah Masuk & Pindah Keluar</small>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
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
          <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
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

          <?php if (in_array($roleId, [1, 2], true)): ?>
            <hr class="my-3">
            <!-- Upload TTD Kepala Sekolah -->
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-bold mb-2"><i class="bi bi-cloud-arrow-up me-1"></i> Unggah Tanda Tangan Digital Kepala Sekolah</h6>
              <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                  <input type="file" id="principal_signature_file" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                  <div class="small text-muted mt-1">Format PNG transparan / JPG, maks. 2MB.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-upload-principal-sig">
                  ⬆ Upload TTD Kepsek
                </button>
              </div>
              <div id="principal_sig_preview" class="mt-2 <?= empty($school['principal_signature']) ? 'd-none' : '' ?>">
                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>TTD Kepala Sekolah tersimpan:</span>
                <img src="<?= base_url($school['principal_signature'] ?? '') ?>" alt="TTD Kepsek" style="max-height:45px;" class="border p-1 bg-white rounded ms-2">
              </div>
            <!-- Upload Logo Daerah -->
            <div class="border rounded p-3 bg-light mt-3">
              <h6 class="fw-bold mb-2"><i class="bi bi-shield-check me-1"></i> Unggah Logo Daerah (Cover Depan Rapor)</h6>
              <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                  <input type="file" id="regional_logo_file" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                  <div class="small text-muted mt-1">Dicetak di bagian atas cover depan rapor. Format PNG transparan / JPG, maks. 2MB.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-upload-regional-logo">
                  ⬆ Upload Logo Daerah
                </button>
              </div>
              <div id="regional_logo_preview" class="mt-2 <?= empty($school['regional_logo']) ? 'd-none' : '' ?>">
                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>Logo Daerah tersimpan:</span>
                <img src="<?= base_url($school['regional_logo'] ?? 'uploads/logo/logo_daerah_default.png') ?>" alt="Logo Daerah" style="max-height:45px;" class="border p-1 bg-white rounded ms-2">
              </div>
            </div>
          <?php endif; ?>

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

  // Upload TTD Kepala Sekolah
  $('#btn-upload-principal-sig').on('click', function() {
    var fileInput = $('#principal_signature_file')[0];
    if (!fileInput.files.length) {
      alert('Silakan pilih file gambar tanda tangan terlebih dahulu.');
      return;
    }

    var formData = new FormData();
    formData.append('type', 'principal');
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
          alert('Tanda tangan kepala sekolah berhasil diperbarui!');
          $('#principal_sig_preview').removeClass('d-none').find('img').attr('src', res.filepath);
        } else {
          alert(res.message || 'Gagal mengunggah tanda tangan.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan saat mengunggah.');
      },
      complete: function() {
        btn.prop('disabled', false).text('⬆ Upload TTD Kepsek');
      }
    });
  });

  // Upload Logo Daerah
  $('#btn-upload-regional-logo').on('click', function() {
    var fileInput = $('#regional_logo_file')[0];
    if (!fileInput.files.length) {
      alert('Silakan pilih file logo daerah terlebih dahulu.');
      return;
    }

    var formData = new FormData();
    formData.append('regional_logo_file', fileInput.files[0]);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    var btn = $(this).prop('disabled', true).text('Mengunggah...');

    $.ajax({
      url: "<?= site_url('admin/rapor-semester/upload-regional-logo') ?>",
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert('Logo daerah berhasil diperbarui!');
          $('#regional_logo_preview').removeClass('d-none').find('img').attr('src', res.filepath);
        } else {
          alert(res.message || 'Gagal mengunggah logo daerah.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan saat mengunggah.');
      },
      complete: function() {
        btn.prop('disabled', false).text('⬆ Upload Logo Daerah');
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
