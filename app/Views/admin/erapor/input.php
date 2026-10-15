<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
.tp-table-box {
  min-width: 280px;
  max-width: 380px;
  font-size: 0.8rem;
  line-height: 1.25;
}
.tp-inner-table {
  border-collapse: collapse;
  width: 100%;
}
.tp-inner-table th {
  font-size: 0.72rem;
  padding: 3px 2px;
  background-color: #f8fafc;
}
.tp-inner-table td {
  padding: 4px 2px;
  vertical-align: middle;
}
.tp-check {
  cursor: pointer;
  width: 16px;
  height: 16px;
  margin: 0 auto;
}
.tp-desc-truncate {
  display: inline-block;
  max-width: 190px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">✏️ Input Nilai Erapor</h4>
  <a href="javascript:history.back()" class="btn btn-sm btn-secondary">⬅ Kembali</a>
</div>

<!-- Info -->
<table class="mb-3" style="font-size:.9rem;">
  <tr><td style="width:130px;" class="text-muted">Tahun Ajaran</td><td>: <strong><?= esc($year['year']) ?></strong></td></tr>
  <tr><td class="text-muted">Kelas</td><td>: <strong><?= esc($class['name']) ?></strong> <span class="badge bg-secondary-subtle text-secondary ms-1">Level <?= esc($classLevel) ?></span></td></tr>
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

<?php if (empty($activeSelectedTps)): ?>
  <div class="alert alert-warning py-2 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
    <div>
      <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
      <strong>TP Acuan Rapor Belum Dipilih:</strong> Belum ada Tujuan Pembelajaran yang dipilih untuk acuan deskripsi tingkat <strong>Kelas <?= esc($classLevel) ?></strong> semester ini.
    </div>
    <button type="button" class="btn btn-warning btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTpSettings">
      🎯 Pilih TP Acuan Sekarang
    </button>
  </div>
<?php endif; ?>

<div class="alert alert-info py-2 small mb-3">
  <i class="fas fa-info-circle me-1"></i>
  <strong>Panduan Pengisian:</strong> 
  Pilih TP acuan pada tombol <em>Atur TP Acuan</em> (berlaku se-level kelas). Tentukan capaian <strong>Maks</strong> (tertinggi) dan <strong>Min</strong> (perlu bimbingan) per siswa dengan ceklis on/off (otomatis saling meniadakan). Deskripsi capaian kompetensi akan digenerate otomatis sesuai pilihan TP tersebut.
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
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTpSettings">
          🎯 Atur TP Acuan (Level <?= esc($classLevel) ?>)
          <span class="badge bg-primary ms-1" id="badge-tp-count"><?= count($activeSelectedTps) ?> TP Dipilih</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="btn-generate-desc" title="Generate otomatis deskripsi capaian seluruh siswa berdasarkan pilihan TP Maksimal & Minimal">
          ⚡ Generate Deskripsi Otomatis
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="erapor-table">
          <thead class="table-light text-center">
            <tr>
              <th style="width:35px;">#</th>
              <th class="text-start" style="min-width:170px;">Nama Siswa</th>
              <th style="width:85px;" class="bg-success-subtle">
                Rerata<br><small class="fw-normal">Formatif</small>
              </th>
              <th style="width:85px;" class="bg-warning-subtle">
                Rerata<br><small class="fw-normal">Sumatif</small>
              </th>
              <th style="width:85px;" class="bg-info-subtle">
                Acuan<br><small class="fw-normal">(Sistem)</small>
              </th>
              <th style="width:105px;" class="bg-primary text-white">
                Nilai Erapor<br><small class="fw-normal">(Input)</small>
              </th>
              <th style="min-width:280px;" class="bg-primary-subtle text-center">
                Capaian TP Rapor<br>
                <small class="fw-normal text-muted">
                  <span class="badge bg-success-subtle text-success me-1">Maks: Tertinggi</span>
                  <span class="badge bg-warning-subtle text-warning-emphasis">Min: Perlu Bimbingan</span>
                </small>
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

                <!-- Pemilihan TP Maksimal & Minimal (Langkah 2) -->
                <td class="p-1">
                  <?php if (empty($activeSelectedTps)): ?>
                    <div class="text-center text-muted small py-2 fst-italic">
                      <i class="bi bi-info-circle me-1"></i>Belum ada TP dipilih.<br>
                      <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalTpSettings" class="text-primary fw-semibold">Pilih TP Acuan</a>
                    </div>
                  <?php else: ?>
                    <div class="tp-table-box mx-auto">
                      <table class="tp-inner-table">
                        <thead>
                          <tr class="text-muted border-bottom text-center">
                            <th class="text-start">Tujuan Pembelajaran (TP)</th>
                            <th class="text-success fw-bold" style="width:38px;" title="Capaian Tertinggi / Maksimal">Maks</th>
                            <th class="text-warning-emphasis fw-bold" style="width:38px;" title="Perlu Bimbingan / Minimal">Min</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($activeSelectedTps as $tp): 
                            $curType = $studentTpMap[$stu['id']][$tp['id']] ?? '';
                          ?>
                            <tr class="border-bottom border-light">
                              <td class="text-start py-1" title="<?= esc($tp['deskripsi']) ?>">
                                <span class="badge bg-light text-dark border me-1" style="font-size:0.7rem;"><?= esc($tp['kode_tp'] ?: 'TP') ?></span>
                                <span class="tp-desc-truncate"><?= esc($tp['deskripsi']) ?></span>
                              </td>
                              <td class="text-center py-1">
                                <input type="checkbox"
                                       class="form-check-input tp-check tp-check-maks"
                                       name="student_tp[<?= $stu['id'] ?>][<?= $tp['id'] ?>]"
                                       value="maksimal"
                                       data-student="<?= $stu['id'] ?>"
                                       data-tp="<?= $tp['id'] ?>"
                                       data-desc="<?= esc($tp['deskripsi']) ?>"
                                       title="Tandai sebagai capaian tertinggi / maksimal"
                                       <?= $curType === 'maksimal' ? 'checked' : '' ?>>
                              </td>
                              <td class="text-center py-1">
                                <input type="checkbox"
                                       class="form-check-input tp-check tp-check-min"
                                       name="student_tp[<?= $stu['id'] ?>][<?= $tp['id'] ?>]"
                                       value="minimal"
                                       data-student="<?= $stu['id'] ?>"
                                       data-tp="<?= $tp['id'] ?>"
                                       data-desc="<?= esc($tp['deskripsi']) ?>"
                                       title="Tandai sebagai capaian perlu bimbingan / minimal"
                                       <?= $curType === 'minimal' ? 'checked' : '' ?>>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Input Deskripsi Capaian Kompetensi -->
                <td class="p-1">
                  <div class="d-flex flex-column h-100">
                    <textarea name="descriptions[<?= $stu['id'] ?>]"
                              id="desc_<?= $stu['id'] ?>"
                              class="form-control form-control-sm desc-input"
                              rows="3"
                              placeholder="Deskripsi capaian kompetensi..."><?= esc($stu['desc'] ?? '') ?></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                      <small class="text-muted fst-italic" style="font-size:0.72rem;">* Otomatis terupdate saat ceklis TP berubah</small>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none btn-generate-single" data-student="<?= $stu['id'] ?>" title="Generate ulang deskripsi siswa ini">
                        <i class="bi bi-magic me-1"></i>Regenerate
                      </button>
                    </div>
                  </div>
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

<!-- Modal Pengaturan TP Acuan Rapor (Langkah 1: Level Kelas) -->
<div class="modal fade" id="modalTpSettings" tabindex="-1" aria-labelledby="modalTpSettingsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTpSettingsLabel">
          <i class="bi bi-bullseye me-2"></i>Atur TP Acuan Rapor — <?= esc($subject['name']) ?> (Level Kelas <?= esc($classLevel) ?>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 small mb-3">
          <i class="bi bi-info-circle-fill me-1"></i>
          <strong>Ketentuan Kurikulum Merdeka:</strong> Pilih beberapa Tujuan Pembelajaran (TP) yang diajarkan dan diujikan pada semester <?= $semester ?> untuk dijadikan kandidat deskripsi capaian.
          Pilihan ini <strong>berlaku untuk semua kelas pada Tingkat/Level <?= esc($classLevel) ?></strong>.
        </div>

        <?php if (empty($allTps)): ?>
          <div class="alert alert-warning py-3 text-center">
            <i class="bi bi-exclamation-triangle fa-2x mb-2 d-block"></i>
            Belum ada data Tujuan Pembelajaran (TP) untuk mata pelajaran ini pada Tingkat Kelas <?= esc($classLevel) ?>.<br>
            Silakan tambahkan data TP terlebih dahulu di menu <strong>Administrasi Guru &gt; Alur Tujuan Pembelajaran (ATP)</strong>.
          </div>
        <?php else: ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Total TP tersedia: <strong><?= count($allTps) ?> TP</strong></span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-tp-select-all">Centang Semua</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-tp-unselect-all">Hapus Semua</button>
            </div>
          </div>
          <form id="form-tp-settings">
            <input type="hidden" name="year_id" value="<?= $year['id'] ?>">
            <input type="hidden" name="class_level" value="<?= esc($classLevel) ?>">
            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
            <input type="hidden" name="semester" value="<?= $semester ?>">
            <?= csrf_field() ?>

            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
              <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="width: 45px;" class="text-center">Pilih</th>
                    <th style="width: 75px;" class="text-center">Kode</th>
                    <th>Deskripsi Tujuan Pembelajaran (TP)</th>
                    <th style="width: 130px;">Lingkup Materi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($allTps as $idx => $tp): 
                    $isSelected = in_array((int)$tp['id'], $selectedTpIds, true);
                  ?>
                    <tr class="<?= $isSelected ? 'table-primary-subtle' : '' ?>">
                      <td class="text-center text-muted"><?= $idx + 1 ?></td>
                      <td class="text-center">
                        <input type="checkbox"
                               class="form-check-input modal-tp-checkbox"
                               name="tp_ids[]"
                               value="<?= $tp['id'] ?>"
                               <?= $isSelected ? 'checked' : '' ?>>
                      </td>
                      <td class="text-center fw-semibold"><?= esc($tp['kode_tp'] ?: '-') ?></td>
                      <td><?= esc($tp['deskripsi']) ?></td>
                      <td class="small text-muted"><?= esc($tp['lingkup_materi'] ?: ($tp['elemen'] ?: '-')) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </form>
        <?php endif; ?>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <span class="text-muted small">
          <span class="fw-bold text-primary" id="count-checked-tp"><?= count($selectedTpIds) ?></span> TP dipilih
        </span>
        <div>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary btn-sm" id="btn-save-tp-settings" <?= empty($allTps) ? 'disabled' : '' ?>>
            💾 Simpan TP Acuan
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
  // ── Salin semua input dari nilai acuan ──────────────────────────────
  $('#btn-fill-acuan').on('click', function() {
    $('.erapor-input').each(function() {
      var acuan = $(this).data('acuan');
      if (acuan !== '' && acuan !== undefined) {
        $(this).val(parseFloat(acuan).toFixed(2));
      }
    });
  });

  // ── Kosongkan semua nilai ──────────────────────────────────────────
  $('#btn-clear-all').on('click', function() {
    if (confirm('Kosongkan semua nilai erapor?')) {
      $('.erapor-input').val('');
    }
  });

  // ── Highlight jika nilai berbeda dari acuan ────────────────────────
  $(document).on('input', '.erapor-input', function() {
    var val   = parseFloat($(this).val());
    var acuan = parseFloat($(this).data('acuan'));
    if (!isNaN(val) && !isNaN(acuan) && Math.abs(val - acuan) > 0.01) {
      $(this).addClass('border-warning').removeClass('border-success');
    } else {
      $(this).removeClass('border-warning').addClass('border-success');
    }
  });

  // ── Helpers Pembersihan & Pembentukan Kalimat Deskripsi ─────────────
  function cleanTp(text) {
    text = $.trim(text);
    text = text.replace(/^(mampu|siswa dapat|peserta didik dapat|dapat)\s+/i, '');
    text = text.replace(/[\.\s]+$/, '');
    if (text.length > 85) text = text.substring(0, 82) + '...';
    return text ? (text.charAt(0).toLowerCase() + text.slice(1)) : '';
  }

  function updateStudentDesc(stuId) {
    var maksItems = [];
    var minItems  = [];

    $('.tp-check-maks[data-student="' + stuId + '"]:checked').each(function() {
      var d = cleanTp($(this).data('desc'));
      if (d) maksItems.push(d);
    });

    $('.tp-check-min[data-student="' + stuId + '"]:checked').each(function() {
      var d = cleanTp($(this).data('desc'));
      if (d) minItems.push(d);
    });

    if (maksItems.length > 0 || minItems.length > 0) {
      var parts = [];
      if (maksItems.length > 0) {
        parts.push("Menunjukkan penguasaan yang sangat baik dalam " + maksItems.join(' dan ') + ".");
      }
      if (minItems.length > 0) {
        parts.push("Perlu bimbingan dan peningkatan dalam " + minItems.join(' serta ') + ".");
      }
      var target = $('#desc_' + stuId);
      target.val(parts.join(' ')).addClass('border-primary');
      setTimeout(function() { target.removeClass('border-primary'); }, 1200);
    }
  }

  // ── Sifat On/Off & Saling Eksklusif (Maksimal vs Minimal) ───────────
  $(document).on('change', '.tp-check-maks', function() {
    var stuId = $(this).data('student');
    var tpId  = $(this).data('tp');
    if ($(this).is(':checked')) {
      $('.tp-check-min[data-student="' + stuId + '"][data-tp="' + tpId + '"]').prop('checked', false);
    }
    updateStudentDesc(stuId);
  });

  $(document).on('change', '.tp-check-min', function() {
    var stuId = $(this).data('student');
    var tpId  = $(this).data('tp');
    if ($(this).is(':checked')) {
      $('.tp-check-maks[data-student="' + stuId + '"][data-tp="' + tpId + '"]').prop('checked', false);
    }
    updateStudentDesc(stuId);
  });

  // Tombol regenerate per baris siswa
  $(document).on('click', '.btn-generate-single', function(e) {
    e.preventDefault();
    var stuId = $(this).data('student');
    updateStudentDesc(stuId);
  });

  // ── Modal TP Settings: Check All / Uncheck All ──────────────────────
  function updateModalCount() {
    var checked = $('.modal-tp-checkbox:checked').length;
    $('#count-checked-tp').text(checked);
  }

  $('.modal-tp-checkbox').on('change', function() {
    $(this).closest('tr').toggleClass('table-primary-subtle', $(this).is(':checked'));
    updateModalCount();
  });

  $('#btn-tp-select-all').on('click', function() {
    $('.modal-tp-checkbox').prop('checked', true).closest('tr').addClass('table-primary-subtle');
    updateModalCount();
  });

  $('#btn-tp-unselect-all').on('click', function() {
    $('.modal-tp-checkbox').prop('checked', false).closest('tr').removeClass('table-primary-subtle');
    updateModalCount();
  });

  // ── Simpan Pengaturan TP Acuan (AJAX) ───────────────────────────────
  $('#btn-save-tp-settings').on('click', function() {
    var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

    $.ajax({
      url: "<?= site_url('admin/erapor/save-tp-settings') ?>",
      type: 'POST',
      data: $('#form-tp-settings').serialize(),
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          $('#modalTpSettings').modal('hide');
          $('#save-alert')
            .removeClass('d-none alert-danger')
            .addClass('alert-success')
            .html('<i class="fas fa-check-circle me-1"></i> ' + res.message + ' Memuat ulang tampilan kolom...');
          setTimeout(function() {
            window.location.reload();
          }, 800);
        } else {
          alert(res.message || 'Gagal menyimpan TP acuan.');
          btn.prop('disabled', false).html('💾 Simpan TP Acuan');
        }
      },
      error: function() {
        alert('Terjadi kesalahan saat menyimpan pengaturan TP acuan.');
        btn.prop('disabled', false).html('💾 Simpan TP Acuan');
      }
    });
  });

  // ── Generate Deskripsi Otomatis (Global) ────────────────────────────
  $('#btn-generate-desc').on('click', function() {
    var btn = $(this);
    var oldText = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengolah Deskripsi...');

    $.ajax({
      url: "<?= site_url('admin/erapor/generate-desc') ?>",
      type: 'POST',
      data: $('#erapor-form').serialize(),
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

  // ── Submit Simpan Nilai, TP, & Deskripsi via AJAX ───────────────────
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
          .html('<i class="fas fa-check-circle me-1"></i> ' + res.message);
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
        setTimeout(function() { $('#save-alert').addClass('d-none'); }, 5000);
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
