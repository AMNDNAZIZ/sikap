<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Action Bar (Web View Only) -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <div>
    <h4 class="mb-0">📊 Leger Nilai &amp; Rekap Absensi — <?= esc($class['name']) ?></h4>
    <small class="text-muted">Tahun Ajaran <?= esc($year['year']) ?> | Semester <?= esc($semesterLabel) ?></small>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCetakLeger">
      🖨️ Cetak Leger / PDF
    </button>
    <a href="<?= site_url('admin/erapor') ?>" class="btn btn-sm btn-outline-secondary">⬅ Kembali</a>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger no-print"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<!-- Filter Semester & Action Bar (Web View Only) -->
<div class="card mb-3 shadow-sm no-print">
  <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <span class="fw-bold text-secondary">Semester:</span>
      <div class="btn-group btn-group-sm" role="group">
        <a href="<?= site_url("admin/erapor/leger/{$class['id']}/1?year_id={$year['id']}") ?>" 
           class="btn <?= $semester == 1 ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 1 (Ganjil)
        </a>
        <a href="<?= site_url("admin/erapor/leger/{$class['id']}/2?year_id={$year['id']}") ?>" 
           class="btn <?= $semester == 2 ? 'btn-primary' : 'btn-outline-primary' ?>">
          Semester 2 (Genap)
        </a>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= site_url("admin/erapor/print-leger/{$class['id']}/{$semester}?year_id={$year['id']}&use_kop=1&use_signature=1") ?>" target="_blank" class="btn btn-sm btn-outline-primary">
        📄 Cetak Cepat (Kop + TTD)
      </a>
      <a href="<?= site_url("admin/erapor/import?year_id={$year['id']}") ?>" class="btn btn-sm btn-success">
        📥 Import Leger Excel
      </a>
    </div>
  </div>
</div>

<?php if (empty($students)): ?>
  <div class="alert alert-warning">Tidak ada data siswa di kelas ini.</div>
<?php else: ?>
  <div class="card shadow-sm print-container">
    <!-- Header Kop Cetak Leger -->
    <div class="leger-header text-center mb-2">
      <h5 class="fw-bold mb-0 text-uppercase" style="font-size: 1.1rem; letter-spacing: 0.5px;">
        <?= esc($school['name'] ?? 'SEKOLAH') ?>
      </h5>
      <h6 class="fw-bold mb-1 text-uppercase text-primary print-text-dark" style="font-size: 0.95rem;">
        LEGER NILAI ERAPOR &amp; REKAP KETIDAKHADIRAN SISWA
      </h6>
      <div class="d-flex justify-content-center gap-4 text-secondary print-text-dark fw-semibold style-subinfo" style="font-size: 0.82rem;">
        <span><strong>Kelas:</strong> <?= esc($class['name']) ?></span>
        <span>•</span>
        <span><strong>Semester:</strong> <?= esc($semesterLabel) ?></span>
        <span>•</span>
        <span><strong>Tahun Ajaran:</strong> <?= esc($year['year']) ?></span>
        <span>•</span>
        <span><strong>Total Siswa:</strong> <?= count($students) ?></span>
      </div>
    </div>

    <div class="card-body p-0 table-responsive">
      <table class="table table-bordered align-middle text-nowrap mb-0 leger-table">
        <thead class="table-light text-center align-middle">
          <tr>
            <th rowspan="2" style="width: 30px;">#</th>
            <th rowspan="2" class="text-start" style="min-width: 150px;">Nama Siswa</th>
            <th rowspan="2" style="width: 75px;">NISN</th>
            <th rowspan="2" style="width: 65px;">NIS</th>
            <th colspan="<?= count($subjects) ?>" class="bg-primary-subtle text-primary fw-bold print-bg-light">
              Nilai Mata Pelajaran (Erapor)
            </th>
            <th rowspan="2" style="width: 45px;" class="bg-info-subtle print-bg-light" title="Rata-rata Nilai">Rata</th>
            <th colspan="3" class="bg-warning-subtle text-warning-emphasis fw-bold print-bg-light">
              Absensi (Hari)
            </th>
          </tr>
          <tr>
            <?php foreach ($subjects as $sub): ?>
              <th class="bg-light" style="font-size: 0.78rem;">
                <span title="<?= esc($sub['name']) ?>"><?= esc($sub['code'] ?? $sub['name']) ?></span>
              </th>
            <?php endforeach; ?>
            <th class="bg-light text-danger" style="width: 25px;" title="Sakit">S</th>
            <th class="bg-light text-warning" style="width: 25px;" title="Izin">I</th>
            <th class="bg-light text-dark" style="width: 25px;" title="Alpa">A</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $idx => $st): ?>
            <?php 
              $studentScores = [];
              foreach ($subjects as $sub) {
                  $v = $scores[$st['id']][$sub['id']] ?? null;
                  if ($v !== null && $v !== '') {
                      $studentScores[] = (float) $v;
                  }
              }
              $avgScore = !empty($studentScores) ? round(array_sum($studentScores) / count($studentScores), 1) : null;
            ?>
            <tr>
              <td class="text-center text-muted col-num"><?= $idx + 1 ?></td>
              <td class="fw-semibold text-dark text-start col-name"><?= esc($st['name']) ?></td>
              <td class="text-center font-monospace col-nisn"><small><?= esc($st['nisn'] ?: '—') ?></small></td>
              <td class="text-center font-monospace col-nis"><small><?= esc($st['nis'] ?: '—') ?></small></td>

              <?php foreach ($subjects as $sub): ?>
                <?php
                  $scoreVal = $scores[$st['id']][$sub['id']] ?? null;
                ?>
                <td class="text-center col-score">
                  <?php if ($scoreVal !== null && $scoreVal !== ''): ?>
                    <span class="<?= ((float)$scoreVal < 70) ? 'text-danger fw-bold' : '' ?>">
                      <?= esc(number_format((float)$scoreVal, 0)) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>

              <td class="text-center fw-bold bg-light col-avg">
                <?= $avgScore !== null ? esc(number_format($avgScore, 1)) : '—' ?>
              </td>

              <?php
                $recap = $attendanceRecaps[$st['id']] ?? null;
              ?>
              <td class="text-center font-monospace bg-light col-abs"><?= $recap ? esc($recap['sick']) : '0' ?></td>
              <td class="text-center font-monospace bg-light col-abs"><?= $recap ? esc($recap['permitted']) : '0' ?></td>
              <td class="text-center font-monospace bg-light col-abs"><?= $recap ? esc($recap['absent']) : '0' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Kolom Tanda Tangan (Kepsek & Walikelas) -->
    <?php
      $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      $tglCetak = date('j') . ' ' . $bulanIndo[(int)date('n') - 1] . ' ' . date('Y');
      $kota = !empty($school['city_regency']) ? $school['city_regency'] : 'Kota';
    ?>
    <div class="leger-signature mt-4 pt-2">
      <div class="row text-center align-items-start">
        <div class="col-6">
          <p class="mb-1">Mengetahui,</p>
          <p class="fw-bold mb-0">Kepala Sekolah</p>
          <div class="signature-space"></div>
          <p class="fw-bold text-decoration-underline mb-0"><?= esc($principal['name']) ?></p>
          <small class="d-block text-muted print-text-dark">NIP. <?= esc($principal['nip']) ?></small>
        </div>
        <div class="col-6">
          <p class="mb-1"><?= esc($kota) ?>, <?= $tglCetak ?></p>
          <p class="fw-bold mb-0">Wali Kelas <?= esc($class['name']) ?></p>
          <div class="signature-space"></div>
          <p class="fw-bold text-decoration-underline mb-0"><?= esc($waliKelas['name']) ?></p>
          <small class="d-block text-muted print-text-dark">NIP. <?= esc($waliKelas['nip']) ?></small>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- MODAL OPSI CETAK LEGER -->
<div class="modal fade" id="modalCetakLeger" tabindex="-1" aria-labelledby="modalCetakLegerLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCetakLegerLabel">
          <i class="bi bi-printer-fill me-2"></i> Pengaturan Cetak Leger
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          Pilih komponen dokumen yang ingin disertakan saat mencetak Leger Rapor ke Tab Baru (A4 Landscape):
        </p>

        <!-- Checkbox Options -->
        <div class="card bg-light border-0 p-3 mb-3">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="optUseKop" checked>
            <label class="form-check-label fw-semibold" for="optUseKop">
              📄 Sertakan Kop Surat Sekolah
            </label>
            <div class="form-text mt-0">Menampilkan Kop Surat resmi / Logo Sekolah di bagian paling atas.</div>
          </div>

          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="optUseSignature" checked>
            <label class="form-check-label fw-semibold" for="optUseSignature">
              ✍️ Sertakan Tanda Tangan (Kepsek &amp; Wali Kelas)
            </label>
            <div class="form-text mt-0">Menampilkan blok tanda tangan Kepala Sekolah &amp; Wali Kelas beserta NIP.</div>
          </div>
        </div>

        <!-- Preset Options -->
        <div class="mb-2">
          <label class="form-label fw-bold text-secondary small">Pilihan Cepat Preset:</label>
          <div class="d-flex flex-wrap gap-1">
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="setPreset(true, true)">Lengkap (Kop + TTD)</button>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="setPreset(true, false)">Kop Sahaja</button>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="setPreset(false, true)">TTD Sahaja</button>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="setPreset(false, false)">Polosan (Tabel Saja)</button>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="executePrintTab()">
          🚀 Cetak di Tab Baru
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function setPreset(useKop, useSig) {
  document.getElementById('optUseKop').checked = useKop;
  document.getElementById('optUseSignature').checked = useSig;
}

function executePrintTab() {
  const useKop = document.getElementById('optUseKop').checked ? 1 : 0;
  const useSig = document.getElementById('optUseSignature').checked ? 1 : 0;
  
  const baseUrl = '<?= site_url("admin/erapor/print-leger/{$class['id']}/{$semester}") ?>';
  const targetUrl = baseUrl + '?year_id=<?= $year['id'] ?>&use_kop=' + useKop + '&use_signature=' + useSig;
  
  window.open(targetUrl, '_blank');
  
  const modalEl = document.getElementById('modalCetakLeger');
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) {
    modal.hide();
  }
}
</script>

<style>
.signature-space {
  height: 55px;
}

.leger-table th, .leger-table td {
  padding: 4px 6px;
  font-size: 0.83rem;
}

@media print {
  @page {
    size: A4 landscape;
    margin: 8mm 8mm 8mm 8mm;
  }

  body {
    background: white !important;
    color: black !important;
    font-size: 8pt !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* Sembunyikan elemen UI non-print */
  .no-print,
  .btn,
  .alert,
  header,
  nav,
  aside,
  .sidebar,
  .navbar,
  footer,
  .card-header,
  .main-footer,
  .content-header {
    display: none !important;
  }

  .print-container {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }

  .table-responsive {
    overflow: visible !important;
  }

  .leger-header {
    margin-bottom: 6px !important;
  }

  .leger-header h5 {
    font-size: 11pt !important;
  }

  .leger-header h6 {
    font-size: 9.5pt !important;
    color: black !important;
  }

  .style-subinfo {
    font-size: 8pt !important;
    color: black !important;
  }

  .print-text-dark {
    color: black !important;
  }

  .print-bg-light {
    background-color: #f2f2f2 !important;
    color: black !important;
  }

  .leger-table {
    width: 100% !important;
    border-collapse: collapse !important;
    border: 1px solid #333 !important;
  }

  .leger-table th,
  .leger-table td {
    border: 1px solid #333 !important;
    padding: 2px 3px !important;
    font-size: 7.5pt !important;
    line-height: 1.1 !important;
  }

  .leger-table th {
    background-color: #f0f0f0 !important;
    color: black !important;
  }

  .leger-signature {
    margin-top: 10px !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    font-size: 8.5pt !important;
  }

  .signature-space {
    height: 45px !important;
  }
}
</style>

<?= $this->endSection() ?>


