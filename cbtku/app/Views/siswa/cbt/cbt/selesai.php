<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<div class="finish-container">
  <div class="finish-shape shape-1"></div>
  <div class="finish-shape shape-2"></div>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-md-9">
        <div class="finish-card shadow-lg animate__animated animate__fadeInUp">
          <div class="finish-card-body p-4 p-md-5">
            <div class="text-center mb-5">
              <div class="success-icon-wrapper mb-4 animate__animated animate__zoomIn animate__delay-1s">
                <i class="bi bi-check2-circle"></i>
              </div>
              <h1 class="fw-extra-bold text-gradient mb-2">Ujian Selesai!</h1>
              <p class="text-muted-modern px-md-5">Terima kasih telah menyelesaikan ujian dengan jujur. Semua jawaban
                Anda telah berhasil disimpan.</p>
            </div>

            <?php
            $isForced = session()->getFlashdata('forced_submit') === true || request()->getGet('forced') === '1';
            ?>

            <?php if ($isForced): ?>
              <div class="alert-premium alert-warning-modern mb-5 animate__animated animate__shakeX animate__delay-1s">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0 ms-1 me-4 fs-1">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">PENGHENTIAN PAKSA</h6>
                    <p class="small mb-0 opacity-75">Ujian dihentikan sistem karena pelanggaran integritas (keluar
                      jendela/full-screen).</p>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if (strtolower($test['show_score'] ?? 'tidak') === 'ya'): ?>
              <div
                class="score-showcase mb-5 p-4 rounded-4 shadow-sm animate__animated animate__fadeIn animate__delay-2s">
                <div class="row align-items-center">
                  <div class="col-md-7 text-center text-md-start mb-4 mb-md-0">
                    <h5 class="mb-1 text-primary-gradient fw-bold"><?= esc($test['exam_name'] ?? 'Ujian') ?></h5>
                    <p class="mb-0 text-muted small text-uppercase ls-1">
                      <?= esc($test['subject_name'] ?? '-') ?> &bull; <?= esc($test['bank_code'] ?? '-') ?>
                    </p>
                  </div>
                  <div class="col-md-5 text-center text-md-end">
                    <div class="score-circle">
                      <div class="score-value"><?= number_format($calculated_total ?? 0, 1) ?></div>
                      <div class="score-label">POIN TOTAL</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="table-container mb-5 animate__animated animate__fadeIn animate__delay-2s">
                <h6 class="text-muted-modern text-uppercase ls-2 mb-3 small fw-bold">Ringkasan Poin Per Kategori</h6>
                <div class="table-responsive rounded-4 border overflow-hidden">
                  <table class="table table-finish align-middle mb-0">
                    <thead>
                      <tr>
                        <th class="ps-4">Kategori</th>
                        <th class="text-center">Benar / Raw</th>
                        <th class="text-end pe-4">Kontribusi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $labels = ['pg' => 'Pilihan Ganda', 'pgk' => 'PG Kompleks', 'bs' => 'Benar / Salah', 'esai' => 'Esai'];
                      foreach ($stats as $key => $s):
                        if ($s['total'] == 0 && $key !== 'esai')
                          continue;
                        ?>
                        <tr>
                          <td class="ps-4">
                            <div class="d-flex align-items-center">
                              <div class="category-indicator bg-<?= $key ?>"></div>
                              <span class="fw-medium"><?= $labels[$key] ?></span>
                            </div>
                          </td>
                          <td class="text-center">
                            <?php if ($key === 'esai'): ?>
                              <span class="badge-glass"><?= number_format($s['earned'], 1) ?></span>
                            <?php else: ?>
                              <span class="text-dark fw-bold"><?= number_format($s['earned'], 1) ?></span>
                              <span class="text-muted small">/ <?= $s['total'] ?></span>
                            <?php endif; ?>
                          </td>
                          <td class="text-end pe-4">
                            <span class="fw-bold text-<?= $key ?>"><?= number_format($s['contribution'], 2) ?></span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr class="table-active-row">
                        <td colspan="2" class="ps-4 fw-bold text-uppercase ls-1">Skor Akhir Terakumulasi</td>
                        <td class="text-end pe-4">
                          <span class="total-badge"><?= number_format($calculated_total ?? 0, 2) ?></span>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            <?php else: ?>
              <div
                class="alert-premium alert-info-modern mb-5 p-4 rounded-4 shadow-sm animate__animated animate__fadeIn animate__delay-2s text-center">
                <div class="mb-3 fs-1 text-info opacity-75">
                  <i class="bi bi-file-earmark-lock2"></i>
                </div>
                <h5 class="fw-bold mb-1 text-dark"><?= esc($test['exam_name'] ?? 'Ujian') ?></h5>
                <p class="mb-0 text-muted opacity-75"><?= esc($test['subject_name'] ?? '-') ?>
                  (<?= esc($test['bank_code'] ?? '-') ?>)</p>
                <hr class="my-4 opacity-10">
                <p class="text-muted small mb-0">Rincian skor akan diumumkan oleh guru setelah periode ujian berakhir.</p>
              </div>
            <?php endif; ?>

            <?php
            $isPastEndTime = time() > strtotime($test['end_time'] ?? '');
            $showScore = strtolower($test['show_score'] ?? 'tidak') === 'ya';

            if ($showScore && !$isPastEndTime && !$isForced): ?>
              <div
                class="alert alert-info border-0 rounded-4 py-3 px-4 small mb-4 animate__animated animate__fadeIn animate__delay-3s">
                <div class="d-flex align-items-center">
                  <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                  <div>
                    <h6 class="fw-bold mb-1">Review Belum Tersedia</h6>
                    <p class="mb-0 opacity-75">Review jawaban dapat diakses setelah seluruh jadwal ujian berakhir (pukul
                      <?= date('H:i', strtotime($test['end_time'])) ?>).</p>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div
              class="action-buttons d-grid gap-3 d-sm-flex justify-content-center animate__animated animate__fadeInUp animate__delay-3s">
              <a href="<?= site_url('siswa/cbt') ?>" class="btn-premium btn-secondary-modern">
                <i class="bi bi-box-arrow-left"></i> Kembali ke Dashboard
              </a>
              <?php if ($showScore && !$isForced && $isPastEndTime): ?>
                <a href="<?= site_url('siswa/cbt/hasil/' . $test['id']) ?>"
                  class="btn-premium btn-primary-modern shadow-lg">
                  <i class="bi bi-journal-text"></i> Periksa Jawaban
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="text-center mt-5 mb-4 animate__animated animate__fadeIn animate__delay-4s">
          <p class="text-muted-modern small mb-0">&copy; <?= date('Y') ?> &bull; SakaSalika CBT System <span
              class="badge bg-secondary-soft text-dark px-2 ms-1">v2.1.1</span></p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

  :root {
    --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    --secondary-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --glass-bg: rgba(255, 255, 255, 0.95);
    --text-modern: #334155;
    --ls-spacing: 0.05em;
  }

  body {
    font-family: 'Outfit', sans-serif !important;
    background-color: #f1f5f9;
    margin: 0;
    overflow-x: hidden;
  }

  .finish-container {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background-color: #f8fafc;
  }

  /* Decorative Shapes */
  .finish-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    z-index: 0;
    opacity: 0.4;
  }

  .shape-1 {
    width: 400px;
    height: 400px;
    background: #3b82f6;
    top: -100px;
    right: -100px;
  }

  .shape-2 {
    width: 300px;
    height: 300px;
    background: #a855f7;
    bottom: -50px;
    left: -100px;
  }

  .container {
    position: relative;
    z-index: 1;
  }

  .finish-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 2rem;
    overflow: hidden;
  }

  .fw-extra-bold {
    font-weight: 800;
  }

  .ls-1 {
    letter-spacing: 1px;
  }

  .ls-2 {
    letter-spacing: 2px;
  }

  .text-gradient {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .text-primary-gradient {
    color: #3b82f6;
  }

  .text-muted-modern {
    color: #64748b;
  }

  .success-icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100px;
    height: 100px;
    background: #ecfdf5;
    color: #10b981;
    border-radius: 2.5rem;
    font-size: 3.5rem;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.15);
  }

  .alert-premium {
    padding: 1.25rem 1.5rem;
    border-radius: 1.25rem;
    border-left: 6px solid;
  }

  .alert-warning-modern {
    background-color: #fffbeb;
    border-color: #f59e0b;
    color: #92400e;
  }

  .alert-info-modern {
    background-color: #f0f9ff;
    border-color: #0ea5e9;
    color: #075985;
  }

  .score-showcase {
    background: var(--secondary-gradient);
    border: 1px solid #e2e8f0;
  }

  .score-circle {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 130px;
    height: 130px;
    background: #fff;
    border: 8px solid #f1f5f9;
    border-radius: 50%;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
  }

  .score-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
  }

  .score-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 1.5px;
    margin-top: 4px;
  }

  .table-finish {
    border-collapse: separate;
    border-spacing: 0;
  }

  .table-finish thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 1rem;
  }

  .table-finish td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
  }

  .category-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 12px;
  }

  .bg-pg {
    background-color: #3b82f6;
  }

  .text-pg {
    color: #3b82f6;
  }

  .bg-pgk {
    background-color: #8b5cf6;
  }

  .text-pgk {
    color: #8b5cf6;
  }

  .bg-bs {
    background-color: #f59e0b;
  }

  .text-bs {
    color: #f59e0b;
  }

  .bg-esai {
    background-color: #ec4899;
  }

  .text-esai {
    color: #ec4899;
  }

  .badge-glass {
    background: rgba(148, 163, 184, 0.1);
    padding: 0.35rem 0.75rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .table-active-row td {
    background: #f8fafc !important;
    border-top: 2px solid #e2e8f0;
  }

  .total-badge {
    background: var(--primary-gradient);
    color: white;
    padding: 0.5rem 1.25rem;
    border-radius: 1rem;
    font-weight: 800;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }

  .btn-premium {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 1rem 2rem;
    border-radius: 1.25rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 1rem;
  }

  .btn-primary-modern {
    background: var(--primary-gradient);
    color: white;
    border: none;
  }

  .btn-primary-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
    color: white;
  }

  .btn-secondary-modern {
    background: white;
    color: #64748b;
    border: 1px solid #e2e8f0;
  }

  .btn-secondary-modern:hover {
    background: #f1f5f9;
    color: #1e293b;
    transform: translateY(-2px);
  }

  .bg-secondary-soft {
    background-color: rgba(148, 163, 184, 0.15);
  }

  @media (max-width: 768px) {
    .finish-card-body {
      padding: 2rem 1.5rem !important;
    }

    .success-icon-wrapper {
      width: 80px;
      height: 80px;
      font-size: 2.8rem;
    }

    .score-circle {
      width: 110px;
      height: 110px;
    }

    .score-value {
      font-size: 2rem;
    }

    .btn-premium {
      width: 100%;
    }

    .finish-shape {
      opacity: 0.2;
    }
  }
</style>

<!-- Add Animate.css for entrance animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?= $this->endSection() ?>