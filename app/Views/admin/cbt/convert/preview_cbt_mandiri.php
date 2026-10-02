<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex align-items-center mb-4 gap-2 flex-wrap">
    <a href="<?= site_url('admin/cbt/convertnilai?tab=mandiri') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <div>
      <h5 class="mb-0 fw-bold">
        <i class="bi bi-box-arrow-in-down me-2 text-primary"></i>
        Preview Import Nilai — CBT Mandiri
      </h5>
      <small class="text-muted">
        Bank Soal: <strong><?= esc($payload['bank_code']) ?></strong> &bull;
        <?= esc($payload['exam_name'] ?? '-') ?> &bull;
        Mapel CBT: <strong><?= esc($payload['subject_name']) ?></strong>
        (<?= esc($payload['subject_code'] ?? '-') ?>)
      </small>
    </div>
  </div>

  <!-- Ringkasan -->
  <div class="row g-3 mb-4">
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-3 fw-bold text-primary"><?= count($results) ?></div>
        <div class="small text-muted">Total Siswa di File</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-3 fw-bold text-success"><?= $matchedCount ?></div>
        <div class="small text-muted">NIS Ditemukan di ESPATA</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-3 fw-bold text-warning"><?= $unmatchedCount ?></div>
        <div class="small text-muted">NIS Tidak Ditemukan</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-3 fw-bold text-info">
          <?= $ya == 100 && $yb == 0 ? 'Tanpa' : $yb.'–'.$ya ?>
        </div>
        <div class="small text-muted">Rentang Konversi</div>
      </div>
    </div>
  </div>

  <?php if ($matchedCount === 0): ?>
    <div class="alert alert-danger">
      <i class="bi bi-x-circle me-2"></i>
      <strong>Tidak ada siswa yang cocok.</strong>
      Pastikan data NIS di CBT Mandiri sama dengan NIS di ESPATA.
    </div>
  <?php else: ?>

  <div class="row g-4">

    <!-- Kiri: Tabel Siswa -->
    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <span class="fw-bold"><i class="bi bi-people me-2"></i>Daftar Nilai Siswa</span>
          <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success">
              ✓ <?= $matchedCount ?> cocok
            </span>
            <?php if ($unmatchedCount > 0): ?>
            <span class="badge bg-warning-subtle text-warning border border-warning">
              ⚠ <?= $unmatchedCount ?> tidak cocok
            </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:460px; overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:36px;" class="text-center">#</th>
                  <th>Nama (CBT Mandiri)</th>
                  <th class="text-center" style="width:80px;">NIS</th>
                  <th class="text-center" style="width:75px;">Nilai Asli</th>
                  <th class="text-center" style="width:85px;">Nilai Konversi</th>
                  <th class="text-center" style="width:60px;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $no=1; foreach ($results as $r): ?>
                  <tr class="<?= $r['matched'] ? '' : 'table-warning' ?>">
                    <td class="text-center text-muted small"><?= $no++ ?></td>
                    <td>
                      <div class="fw-semibold small"><?= esc($r['name']) ?></div>
                      <?php if ($r['matched']): ?>
                        <small class="text-muted"><?= esc($r['espata_name']) ?></small>
                      <?php else: ?>
                        <small class="text-danger">NIS tidak ditemukan di ESPATA</small>
                      <?php endif; ?>
                    </td>
                    <td class="text-center small text-muted"><?= esc($r['nis']) ?></td>
                    <td class="text-center fw-semibold <?= $r['ikut_ujian'] ? 'text-dark' : 'text-muted' ?>">
                      <?= $r['ikut_ujian'] ? number_format($r['raw_score'], 1) : '-' ?>
                    </td>
                    <td class="text-center fw-bold <?= $r['matched'] && $r['ikut_ujian'] ? 'text-primary' : 'text-muted' ?>">
                      <?php if ($r['matched'] && $r['ikut_ujian']): ?>
                        <?= number_format($r['converted_score'], 1) ?>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if (!$r['ikut_ujian']): ?>
                        <span class="badge bg-secondary" title="Tidak mengikuti ujian">Absen</span>
                      <?php elseif ($r['matched']): ?>
                        <span class="badge bg-success">✓</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">?</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer bg-light small text-muted py-2">
          Rentang nilai asli (XA–XB): <strong><?= $xa ?> – <?= $xb ?></strong>
          &nbsp;|&nbsp; Target konversi (YA–YB): <strong><?= $ya ?> – <?= $yb ?></strong>
        </div>
      </div>
    </div>

    <!-- Kanan: Form Penyimpanan -->
    <div class="col-lg-5">
      <div class="card shadow-sm border-success">
        <div class="card-header bg-success text-white fw-bold py-3">
          <i class="bi bi-save me-2"></i> Konfigurasi & Simpan Nilai
        </div>
        <div class="card-body">

          <form action="<?= site_url('admin/cbt/import-mandiri/save') ?>" method="post"
                onsubmit="return konfirmasiSimpan()">
            <?= csrf_field() ?>

            <!-- Hidden: nilai per siswa yang matched + ikut ujian -->
            <?php foreach ($results as $r): ?>
              <?php if ($r['matched'] && $r['ikut_ujian'] && $r['espata_id']): ?>
                <input type="hidden"
                       name="student_scores[<?= $r['espata_id'] ?>]"
                       value="<?= $r['converted_score'] ?>">
              <?php endif; ?>
            <?php endforeach; ?>

            <input type="hidden" name="year_id" value="<?= $activeYear['id'] ?? 0 ?>">

            <!-- Info sumber -->
            <div class="alert alert-light border small mb-4">
              <div><i class="bi bi-info-circle me-1"></i>
                <strong>Sumber:</strong> <?= esc($payload['exam_name'] ?? $payload['bank_code']) ?>
              </div>
              <div class="mt-1">
                <strong>Mapel di CBT Mandiri:</strong>
                <span class="badge bg-secondary"><?= esc($payload['subject_name']) ?></span>
                (<?= esc($payload['subject_code'] ?? '-') ?>)
              </div>
              <div class="mt-1">
                <strong>Tahun Ajaran:</strong> <?= esc($activeYear['year'] ?? '-') ?>
              </div>
            </div>

            <!-- ── MAPPING MAPEL ── -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Mapel Tujuan di ESPATA
                <span class="text-danger">*</span>
                <i class="bi bi-question-circle text-muted ms-1"
                   title="Pilih mapel di ESPATA yang akan menerima nilai ini. Bisa berbeda dengan nama mapel di CBT Mandiri."></i>
              </label>
              <?php if (empty($espataSubjects)): ?>
                <div class="alert alert-danger small py-2 mb-0">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Tidak ada mapel yang dapat dipilih. Guru hanya bisa mengimpor ke mapel yang diampu.
                  Hubungi Admin untuk mengatur penugasan mengajar.
                </div>
              <?php else: ?>
              <select name="subject_id" class="form-select" id="subjectSelect" required
                      onchange="updateAtpOptions()">
                <option value="">-- Pilih Mapel ESPATA --</option>
                <?php foreach ($espataSubjects as $s): ?>
                  <option value="<?= $s['id'] ?>"
                    <?php
                      $autoSelect = (
                        strcasecmp(trim($s['code'] ?? ''), trim($payload['subject_code'] ?? '')) === 0
                        || strcasecmp(trim($s['name']), trim($payload['subject_name'])) === 0
                      );
                    ?>
                    <?= $autoSelect ? 'selected' : '' ?>>
                    <?= esc($s['name']) ?>
                    <?php if (!empty($s['code'])): ?>
                      (<?= esc($s['code']) ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">
                Mapel CBT Mandiri: <em><?= esc($payload['subject_name']) ?></em> —
                sistem mencoba mencocokkan otomatis, periksa kembali sebelum menyimpan.
              </div>
              <?php endif; ?>
            </div>

            <!-- ── TIPE TUJUAN ── -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Simpan Sebagai <span class="text-danger">*</span>
              </label>
              <select name="dest_type" id="destType" class="form-select" required
                      onchange="toggleSemester()">
                <option value="">-- Pilih Tipe --</option>
                <option value="pts">📝 Nilai PTS (tidak masuk rapor akhir)</option>
                <option value="sumatif">📊 Nilai Sumatif</option>
                <option value="formatif">📋 Nilai Formatif (per Materi)</option>
                <option value="final">🎯 Nilai Ujian Akhir</option>
              </select>
            </div>

            <!-- Semester (muncul untuk PTS & Sumatif) -->
            <div class="mb-3 d-none" id="semesterSection">
              <label class="form-label fw-semibold">
                Semester <span class="text-danger">*</span>
              </label>
              <select name="semester" id="semesterSelect" class="form-select">
                <option value="1">Semester 1 (Ganjil)</option>
                <option value="2">Semester 2 (Genap)</option>
              </select>
            </div>

            <!-- Lingkup Materi / ATP (muncul untuk Formatif) -->
            <div class="mb-3 d-none" id="atpSection">
              <label class="form-label fw-semibold">
                Lingkup Materi (ATP) <span class="text-danger">*</span>
              </label>
              <select name="material_id" id="materialSelect" class="form-select">
                <option value="">-- Pilih dulu Mapel & Tipe Formatif --</option>
              </select>
              <div class="form-text">
                Daftar ATP akan otomatis dimuat setelah mapel dipilih.
              </div>
            </div>

            <!-- Ringkasan yang akan disimpan -->
            <div class="alert alert-info small mb-3">
              <i class="bi bi-check2-circle me-1"></i>
              Yang akan disimpan: <strong><?= $matchedCount ?> siswa</strong>
              (hanya yang NIS-nya ditemukan & ikut ujian).
              Nilai lebih besar dari yang sudah ada akan menggantikan nilai lama.
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold"
                    <?= empty($espataSubjects) ? 'disabled' : '' ?>>
              <i class="bi bi-save me-1"></i> Simpan Nilai ke ESPATA
            </button>
          </form>

        </div>
      </div>
    </div>

  </div><!-- /row -->
  <?php endif; ?>

</div>

<script>
function toggleSemester() {
    const destType = document.getElementById('destType').value;
    const semSection = document.getElementById('semesterSection');
    const atpSection = document.getElementById('atpSection');
    const matSel     = document.getElementById('materialSelect');
    const semSel     = document.getElementById('semesterSelect');

    semSection.classList.add('d-none');
    atpSection.classList.add('d-none');
    semSel.removeAttribute('required');
    matSel.removeAttribute('required');

    if (destType === 'sumatif' || destType === 'pts') {
        semSection.classList.remove('d-none');
        semSel.setAttribute('required', 'required');
    } else if (destType === 'formatif') {
        semSection.classList.add('d-none');  // formatif tidak perlu semester
        atpSection.classList.remove('d-none');
        matSel.setAttribute('required', 'required');
        updateAtpOptions();
    }
}

function updateAtpOptions() {
    const destType  = document.getElementById('destType').value;
    if (destType !== 'formatif') return;

    const subjectId = document.getElementById('subjectSelect').value;
    const matSel    = document.getElementById('materialSelect');
    if (!subjectId) {
        matSel.innerHTML = '<option value="">-- Pilih mapel terlebih dahulu --</option>';
        return;
    }

    matSel.innerHTML = '<option value="">⏳ Memuat...</option>';

    fetch(`<?= site_url('admin/cbt/import-mandiri/atp/') ?>${subjectId}`)
        .then(r => r.json())
        .then(data => {
            if (!data || !data.length) {
                matSel.innerHTML = '<option value="">Tidak ada ATP untuk mapel ini</option>';
                return;
            }
            matSel.innerHTML = '<option value="">-- Pilih Lingkup Materi --</option>';
            data.forEach(atp => {
                const opt = document.createElement('option');
                opt.value = atp.id;
                opt.textContent = `SM${atp.semester}: ${atp.title}`;
                matSel.appendChild(opt);
            });
        })
        .catch(() => {
            matSel.innerHTML = '<option value="">Gagal memuat ATP — pilih manual</option>';
        });
}

function konfirmasiSimpan() {
    const destType  = document.getElementById('destType').value;
    const subjectEl = document.getElementById('subjectSelect');
    const subjectNm = subjectEl.options[subjectEl.selectedIndex]?.text || '?';
    const label     = {'pts':'PTS','sumatif':'Sumatif','formatif':'Formatif','final':'Ujian Akhir'}[destType] || destType;
    return confirm(
        `Simpan nilai sebagai ${label} untuk mapel:\n"${subjectNm}"?\n\n` +
        `Nilai lama hanya diganti jika nilai baru lebih besar.`
    );
}

// Saat halaman load, trigger toggle agar state awal benar
document.addEventListener('DOMContentLoaded', function () {
    toggleSemester();
});
</script>

<?= $this->endSection() ?>
