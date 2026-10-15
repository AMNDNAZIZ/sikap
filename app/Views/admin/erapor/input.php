<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">✏️ Input Nilai Erapor</h4>
  <a href="javascript:history.back()" class="btn btn-sm btn-secondary">⬅ Kembali</a>
</div>

<!-- Info -->
<table class="mb-3" style="font-size:.9rem;">
  <tr><td style="width:130px;" class="text-muted">Tahun Ajaran</td><td>: <strong><?= esc($year['year']) ?></strong></td></tr>
  <tr><td class="text-muted">Kelas</td><td>: <strong><?= esc($class['name']) ?></strong></td></tr>
  <tr><td class="text-muted">Mata Pelajaran</td><td>: <strong><?= esc($subject['name']) ?></strong></td></tr>
  <tr><td class="text-muted">Semester</td><td>: <strong><?= $semester == 1 ? 'Ganjil (1)' : 'Genap (2)' ?></strong></td></tr>
  <tr><td class="text-muted">Bobot Acuan</td><td>: <span class="badge bg-success"><?= $fWeight ?>% Formatif</span> + <span class="badge bg-warning text-dark"><?= $sWeight ?>% Sumatif</span></td></tr>
</table>

<!-- Tabs semester -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $semester == 1 ? 'active' : '' ?>"
       href="<?= site_url("admin/erapor/input/{$class['id']}/{$subject['id']}/1?year_id={$year['id']}") ?>">
      Semester 1
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $semester == 2 ? 'active' : '' ?>"
       href="<?= site_url("admin/erapor/input/{$class['id']}/{$subject['id']}/2?year_id={$year['id']}") ?>">
      Semester 2
    </a>
  </li>
</ul>

<div class="alert alert-info py-2 small">
  <i class="fas fa-info-circle"></i>
  <strong>Nilai Acuan</strong> adalah hasil perhitungan sistem (<?= $fWeight ?>% formatif + <?= $sWeight ?>% sumatif).
  <strong>Nilai Erapor</strong> adalah nilai final yang Anda tentukan — bisa sama atau berbeda dari acuan.
  Kosongkan kolom Erapor jika belum ingin mengisi.
</div>

<!-- Alert hasil simpan -->
<div id="save-alert" class="d-none"></div>

<form id="erapor-form">
  <input type="hidden" name="class_id"   value="<?= $class['id'] ?>">
  <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
  <input type="hidden" name="year_id"    value="<?= $year['id'] ?>">
  <input type="hidden" name="semester"   value="<?= $semester ?>">
  <?= csrf_field() ?>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-2 gap-2">
      <span class="fw-bold text-dark"><i class="bi bi-list-check me-1 text-primary"></i> Daftar Nilai & Deskripsi Capaian Siswa</span>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-generate-desc" title="Generate otomatis deskripsi capaian berdasarkan Tujuan Pembelajaran / Materi">
          ⚡ Generate Deskripsi Otomatis
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="erapor-table">
          <thead class="table-light text-center">
            <tr>
              <th style="width:40px;">#</th>
              <th class="text-start" style="min-width:180px;">Nama Siswa</th>
              <th style="width:95px;" class="bg-success-subtle">
                Rerata<br><small class="fw-normal">Formatif</small>
              </th>
              <th style="width:95px;" class="bg-warning-subtle">
                Rerata<br><small class="fw-normal">Sumatif</small>
              </th>
              <th style="width:95px;" class="bg-info-subtle">
                Acuan<br><small class="fw-normal">(Sistem)</small>
              </th>
              <th style="width:110px;" class="bg-primary text-white">
                Nilai Erapor<br><small class="fw-normal">(Input)</small>
              </th>
              <th style="min-width:320px;" class="bg-light text-start">
                Deskripsi Capaian Kompetensi<br><small class="text-muted fw-normal">(Kurikulum Merdeka — Tertinggi & Perlu Bimbingan)</small>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $stu): ?>
              <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td>
                  <strong><?= esc($stu['name']) ?></strong>
                  <?php if (!empty($stu['religion'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary small ms-1"><?= esc($stu['religion']) ?></span>
                  <?php endif; ?>
                </td>

                <!-- Rerata Formatif -->
                <td class="text-center bg-success-subtle">
                  <?php if ($stu['formatif_avg'] !== null): ?>
                    <span class="fw-semibold"><?= number_format($stu['formatif_avg'], 2) ?></span>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>

                <!-- Rerata Sumatif -->
                <td class="text-center bg-warning-subtle">
                  <?php if ($stu['sumatif_avg'] !== null): ?>
                    <span class="fw-semibold"><?= number_format($stu['sumatif_avg'], 2) ?></span>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>

                <!-- Nilai Acuan Sistem -->
                <td class="text-center bg-info-subtle">
                  <?php if ($stu['acuan'] !== null): ?>
                    <span class="fw-bold text-primary"><?= number_format($stu['acuan'], 2) ?></span>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>

                <!-- Input Nilai Erapor -->
                <td class="text-center p-1">
                  <input type="number"
                         name="scores[<?= $stu['id'] ?>]"
                         class="form-control form-control-sm text-center erapor-input"
                         min="0" max="100" step="0.01"
                         placeholder="0–100"
                         value="<?= $stu['erapor'] !== null ? number_format((float)$stu['erapor'], 2, '.', '') : '' ?>"
                         data-acuan="<?= $stu['acuan'] ?? '' ?>"
                         style="max-width:90px; margin:auto;">
                </td>

                <!-- Input Deskripsi Capaian Kompetensi -->
                <td class="p-1">
                  <textarea name="descriptions[<?= $stu['id'] ?>]"
                            id="desc_<?= $stu['id'] ?>"
                            class="form-control form-control-sm desc-input"
                            rows="2"
                            placeholder="Deskripsi capaian kompetensi..."><?= esc($stu['desc'] ?? '') ?></textarea>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-fill-acuan">
          📋 Salin Semua Nilai Acuan
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-all">
          🗑️ Kosongkan Nilai
        </button>
      </div>
      <button type="submit" class="btn btn-primary" id="btn-save">
        💾 Simpan Nilai & Deskripsi Erapor
      </button>
    </div>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
  // Isi semua input dari nilai acuan
  $('#btn-fill-acuan').on('click', function() {
    $('.erapor-input').each(function() {
      var acuan = $(this).data('acuan');
      if (acuan !== '' && acuan !== undefined) {
        $(this).val(parseFloat(acuan).toFixed(2));
      }
    });
  });

  // Kosongkan semua
  $('#btn-clear-all').on('click', function() {
    if (confirm('Kosongkan semua nilai erapor?')) {
      $('.erapor-input').val('');
    }
  });

  // Highlight jika nilai berbeda dari acuan
  $(document).on('input', '.erapor-input', function() {
    var val   = parseFloat($(this).val());
    var acuan = parseFloat($(this).data('acuan'));
    if (!isNaN(val) && !isNaN(acuan) && Math.abs(val - acuan) > 0.01) {
      $(this).addClass('border-warning').removeClass('border-success');
    } else {
      $(this).removeClass('border-warning').addClass('border-success');
    }
  });

  // Generate deskripsi otomatis berbasis ATP / Kurikulum Merdeka
  $('#btn-generate-desc').on('click', function() {
    var btn = $(this);
    var oldText = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengolah Deskripsi...');

    $.ajax({
      url: "<?= site_url('admin/erapor/generate-desc') ?>",
      type: 'POST',
      data: {
        class_id: $('input[name="class_id"]').val(),
        subject_id: $('input[name="subject_id"]').val(),
        year_id: $('input[name="year_id"]').val(),
        semester: $('input[name="semester"]').val(),
        <?= csrf_token() ?>: $('input[name="<?= csrf_token() ?>"]').val()
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success' && res.descriptions) {
          var count = 0;
          $.each(res.descriptions, function(studentId, descText) {
            var target = $('#desc_' + studentId);
            if (target.length) {
              target.val(descText).addClass('border-primary');
              setTimeout(function() { target.removeClass('border-primary'); }, 2000);
              count++;
            }
          });
          $('#save-alert')
            .removeClass('d-none alert-danger')
            .addClass('alert-success')
            .html('<i class="fas fa-magic me-1"></i> Berhasil menghasilkan deskripsi capaian otomatis untuk ' + count + ' siswa! Silakan review dan klik <strong>Simpan Nilai & Deskripsi</strong>.');
          window.scrollTo({top: 0, behavior: 'smooth'});
        } else {
          alert(res.message || 'Gagal menghasilkan deskripsi.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan koneksi saat memproses deskripsi.');
      },
      complete: function() {
        btn.prop('disabled', false).html(oldText);
      }
    });
  });

  // Submit via AJAX
  $('#erapor-form').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#btn-save').prop('disabled', true).text('Menyimpan...');

    $.ajax({
      url: "<?= site_url('admin/erapor/save') ?>",
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(res) {
        var alertClass = res.status === 'success' ? 'alert-success' : 'alert-danger';
        $('#save-alert')
          .removeClass('d-none alert-success alert-danger')
          .addClass(alertClass)
          .html('<i class="fas fa-check-circle"></i> ' + res.message);
        // Scroll ke atas
        window.scrollTo({top: 0, behavior: 'smooth'});
      },
      error: function() {
        $('#save-alert')
          .removeClass('d-none')
          .addClass('alert-danger')
          .html('Gagal menyimpan. Coba lagi.');
      },
      complete: function() {
        btn.prop('disabled', false).text('💾 Simpan Nilai & Deskripsi Erapor');
        // Sembunyikan alert setelah 5 detik
        setTimeout(function() { $('#save-alert').addClass('d-none'); }, 5000);
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
