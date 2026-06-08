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

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="erapor-table">
          <thead class="table-light text-center">
            <tr>
              <th style="width:40px;">#</th>
              <th class="text-start" style="min-width:200px;">Nama Siswa</th>
              <th style="width:110px;" class="bg-success-subtle">
                Rerata<br><small class="fw-normal">Formatif</small>
              </th>
              <th style="width:110px;" class="bg-warning-subtle">
                Rerata<br><small class="fw-normal">Sumatif</small>
              </th>
              <th style="width:110px;" class="bg-info-subtle">
                Nilai Acuan<br><small class="fw-normal">(Sistem)</small>
              </th>
              <th style="width:130px;" class="bg-primary text-white">
                Nilai Erapor<br><small class="fw-normal">(Input Guru)</small>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $stu): ?>
              <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td><?= esc($stu['name']) ?></td>

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
                    <span class="text-muted small">Belum ada data</span>
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
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-fill-acuan">
          📋 Isi dari Nilai Acuan
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-all">
          🗑️ Kosongkan Semua
        </button>
      </div>
      <button type="submit" class="btn btn-primary" id="btn-save">
        💾 Simpan Nilai Erapor
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
        btn.prop('disabled', false).text('💾 Simpan Nilai Erapor');
        // Sembunyikan alert setelah 4 detik
        setTimeout(function() { $('#save-alert').addClass('d-none'); }, 4000);
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
