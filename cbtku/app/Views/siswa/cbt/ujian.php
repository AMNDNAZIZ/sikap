<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<?php
$test = $test ?? [];
$questions = $questions ?? [];
$savedAnswers = $savedAnswers ?? [];
$remaining_seconds = (int) ($remaining_seconds ?? 0);
?>

<style>
  :root {
    --exam-font-size: 1.1rem;
    --primary-blue: #0d6efd;
    --danger-red: #dc3545;
    --warning-yellow: #ffc107;
  }

  body {
    background-color: #f8f9fa;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
  }

  /* 🔒 ANTI-CHEAT: Disable text selection to prevent AI search on mobile */
  .stimulus-text,
  .q-text,
  .option-text {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-touch-callout: none !important;
    /* iOS Safari */
  }

  /* Allow selection for input fields only */
  input[type="text"],
  textarea {
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    user-select: text !important;
  }

  html {}

  /* Ensure scrolling works in fullscreen mode */
  html:fullscreen,
  html:-webkit-full-screen,
  html:-moz-full-screen,
  html:-ms-fullscreen {
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }

  body:fullscreen,
  body:-webkit-full-screen,
  body:-moz-full-screen,
  body:-ms-fullscreen {
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }

  .exam-pc-root {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    overflow-y: visible;
    /* Allow vertical scroll */
    position: relative;
    /* Establish stacking context */
    z-index: 1;
    /* Ensure content is above body but below footer */
  }

  /* Ensure exam-pc-root can scroll in fullscreen */
  :fullscreen .exam-pc-root,
  :-webkit-full-screen .exam-pc-root,
  :-moz-full-screen .exam-pc-root,
  :-ms-fullscreen .exam-pc-root {
    overflow-y: visible !important;
    height: auto !important;
  }

  /* Header Section */
  .exam-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 25px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
  }

  .q-number-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin: 0;
  }

  .font-zoom-ctrl {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-left: 30px;
  }

  .zoom-btn {
    cursor: pointer;
    color: #6c757d;
    transition: 0.2s;
    user-select: none;
    font-weight: 600;
  }

  .zoom-btn:hover {
    color: var(--primary-blue);
  }

  .zoom-btn.active {
    color: #000;
    font-weight: 800;
    border-bottom: 2px solid #000;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .pill-btn {
    border-radius: 50px !important;
    padding: 8px 25px !important;
    font-weight: 600 !important;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none !important;
  }

  .btn-blue {
    background-color: var(--primary-blue);
    color: #fff;
  }

  .btn-blue:hover {
    background-color: #0b5ed7;
    color: #fff;
  }

  .timer-pill {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    padding: 6px 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: monospace;
    font-weight: 700;
    font-size: 1.1rem;
    min-width: 140px;
    justify-content: center;
  }

  /* Auto-Save Indicator */
  .save-status {
    position: fixed;
    bottom: 100px;
    right: 30px;
    background: #fff;
    padding: 10px 20px;
    border-radius: 30px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    z-index: 1000;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
  }

  .save-status.show {
    opacity: 1;
    transform: translateY(0);
  }

  .save-status.saving {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
  }

  .save-status.saved {
    background: #d1e7dd;
    border: 1px solid #198754;
    color: #0f5132;
  }

  .save-status.error {
    background: #f8d7da;
    border: 1px solid #dc3545;
    color: #842029;
  }

  .save-status i.spin {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  /* Progress Breadcrumb */
  .progress-breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 0.95rem;
    font-weight: 600;
  }

  .progress-breadcrumb .current {
    color: var(--primary-blue);
    font-size: 1.1rem;
  }

  .progress-breadcrumb .separator {
    color: #adb5bd;
  }

  .progress-breadcrumb .total {
    color: #6c757d;
  }

  .progress-bar-mini {
    width: 100px;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-left: 10px;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-blue), #667eea);
    transition: width 0.3s ease;
    border-radius: 3px;
  }

  /* Keyboard Shortcut Hints */
  .keyboard-hints {
    position: fixed;
    bottom: 30px;
    left: 30px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    font-size: 0.85rem;
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
  }

  .keyboard-hints.show {
    opacity: 1;
  }

  .keyboard-hints kbd {
    background: #495057;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.8rem;
    margin: 0 2px;
  }

  .keyboard-hints .hint-item {
    margin: 5px 0;
  }

  /* Two Column Layout */
  .exam-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    height: calc(100vh - 250px);
    min-height: 500px;
    position: relative;
    /* Establish stacking context */
    z-index: 1;
    /* Ensure grid is above body */
  }

  /* Ensure proper height in fullscreen */
  :fullscreen .exam-content-grid,
  :-webkit-full-screen .exam-content-grid,
  :-moz-full-screen .exam-content-grid,
  :-ms-fullscreen .exam-content-grid {
    height: calc(100vh - 200px) !important;
  }

  .stimulus-col,
  .question-col {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    overflow-y: auto;
    overflow-x: hidden;
    position: relative;
    border: 1px solid #eee;
    -webkit-overflow-scrolling: touch;
    /* Smooth scrolling on iOS */
    z-index: 2;
    /* Ensure columns are above grid */
  }

  /* Ensure columns can scroll in fullscreen */
  :fullscreen .stimulus-col,
  :fullscreen .question-col,
  :-webkit-full-screen .stimulus-col,
  :-webkit-full-screen .question-col,
  :-moz-full-screen .stimulus-col,
  :-moz-full-screen .question-col,
  :-ms-fullscreen .stimulus-col,
  :-ms-fullscreen .question-col {
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }


  /* Ensure images can be clicked for zoom */
  .stimulus-text img,
  .q-text img,
  .option-text img {
    pointer-events: auto !important;
  }

  /* Scrollbar styling */
  .stimulus-col::-webkit-scrollbar,
  .question-col::-webkit-scrollbar {
    width: 8px;
  }

  .stimulus-col::-webkit-scrollbar-track,
  .question-col::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .stimulus-col::-webkit-scrollbar-thumb,
  .question-col::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
  }

  .stimulus-col::-webkit-scrollbar-thumb:hover,
  .question-col::-webkit-scrollbar-thumb:hover {
    background: #999;
  }

  .stimulus-title {
    text-align: center;
    font-weight: 800;
    font-size: 1.25rem;
    margin-bottom: 25px;
    color: #111;
  }

  .stimulus-text,
  .q-text {
    font-size: var(--exam-font-size);
    line-height: 1.7;
    color: #2c3e50;
  }

  /* Styling untuk gambar dan paragraf dalam soal */
  .stimulus-text img,
  .q-text img,
  .option-text img {
    display: block !important;
    margin: 15px auto !important;
    max-width: 100% !important;
    height: auto !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    cursor: pointer !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
  }

  .stimulus-text img:hover,
  .q-text img:hover,
  .option-text img:hover {
    transform: scale(1.02) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
  }

  .stimulus-text p,
  .q-text p,
  .option-text p {
    margin-bottom: 1rem !important;
    line-height: 1.7 !important;
  }

  .stimulus-text br,
  .q-text br,
  .option-text br {
    display: block !important;
    content: "" !important;
    margin-bottom: 0.5rem !important;
  }

  .stimulus-text,
  .q-text,
  .option-text {
    white-space: normal !important;
  }

  .stimulus-text ul,
  .stimulus-text ol,
  .q-text ul,
  .q-text ol,
  .option-text ul,
  .option-text ol {
    margin-left: 1.5rem !important;
    margin-bottom: 1rem !important;
  }

  .stimulus-text table,
  .q-text table,
  .option-text table {
    width: 100% !important;
    margin-bottom: 1rem !important;
    border-collapse: collapse !important;
  }

  .stimulus-text table td,
  .stimulus-text table th,
  .q-text table td,
  .q-text table th,
  .option-text table td,
  .option-text table th {
    border: 1px solid #dee2e6 !important;
    padding: 0.5rem !important;
  }

  /* Question Item Visibility */
  .q-item {
    display: none;
  }

  .q-item.active {
    display: block;
  }

  /* Options Styling */
  .option-row {
    cursor: pointer;
    transition: 0.2s;
    border: 2px solid #f1f3f5;
    border-radius: 8px;
    padding: 12px 20px;
    margin-bottom: 12px;
    display: flex;
    align-items: flex-start;
    font-size: var(--exam-font-size);
  }

  .option-text {
    flex: 1;
    line-height: 1.7;
  }

  .option-row:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
  }

  .option-row.selected {
    background: #e7f1ff;
    border-color: var(--primary-blue);
  }

  .option-check {
    appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid #adb5bd;
    border-radius: 4px;
    margin-right: 15px;
    cursor: pointer;
    position: relative;
  }

  .option-check:checked {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
  }

  .option-check:checked::after {
    content: '✔';
    position: absolute;
    color: white;
    font-size: 12px;
    left: 3px;
    top: -1px;
  }

  /* Hide radio but keep checkbox for PGK */
  .q-item[data-type="pg"] .option-check {
    display: none;
  }

  .radio-check {
    border-radius: 50% !important;
  }

  /* Benar-Salah Radio Button Styling */
  .inp-bs {
    width: 22px;
    height: 22px;
    cursor: pointer;
    transform: scale(1.3);
    accent-color: var(--primary-blue);
  }

  .inp-bs:hover {
    transform: scale(1.4);
  }

  .letter-circle {
    width: 35px;
    height: 35px;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 15px;
    background: #fff;
    transition: 0.2s;
    flex-shrink: 0;
  }

  .option-row.selected .letter-circle {
    background: var(--primary-blue);
    color: #fff;
    border-color: var(--primary-blue);
  }

  /* Footer Section */
  .exam-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 150px;
    box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.05);
    z-index: 1000;
  }

  .btn-nav {
    font-size: 1.1rem;
    text-transform: none;
  }

  .btn-prev {
    background-color: var(--danger-red);
    color: white;
  }

  .btn-next {
    background-color: var(--primary-blue);
    color: white;
  }

  .btn-ragu {
    background-color: var(--warning-yellow);
    color: #000;
    font-weight: 700 !important;
  }

  .btn-nav:hover {
    opacity: 0.9;
    color: #fff;
  }

  .btn-ragu:hover {
    opacity: 0.9;
    color: #000;
  }

  .btn-secondary {
    background-color: #6c757d !important;
    color: #fff !important;
  }

  .btn-success {
    background-color: #198754 !important;
    color: #fff !important;
  }

  @media (max-width: 992px) {
    .exam-pc-root {
      padding: 10px;
      padding-bottom: 120px;
      /* Increased to give more space for footer */
      position: relative;
      z-index: 1;
    }

    .exam-header {
      flex-direction: column;
      gap: 15px;
      padding: 15px;
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .font-zoom-ctrl {
      margin-left: 0;
      justify-content: center;
      margin-top: 5px;
    }

    .header-actions {
      width: 100%;
      justify-content: center;
      flex-wrap: wrap;
    }

    .pill-btn {
      padding: 6px 15px !important;
      font-size: 0.9rem !important;
    }

    .exam-content-grid {
      display: flex;
      flex-direction: column;
      height: auto;
      min-height: unset;
      gap: 15px;
      margin-bottom: 20px;
      /* Extra space before footer */
      position: relative;
      z-index: 1;
    }

    .stimulus-col,
    .question-col {
      height: auto;
      max-height: none;
      overflow-y: visible;
      padding: 20px;
      -webkit-overflow-scrolling: touch;
      z-index: 2;
      /* Above grid */
    }

    .exam-footer {
      gap: 10px;
      padding: 15px 10px;
      justify-content: space-around;
    }

    .btn-nav {
      font-size: 0.9rem;
      padding: 8px 15px !important;
    }

    .q-number-title {
      font-size: 1.2rem;
    }

    .timer-pill {
      min-width: 120px;
      font-size: 1rem;
    }
  }

  @media (max-width: 576px) {
    .font-zoom-ctrl {
      display: none;
    }

    .header-actions {
      gap: 5px;
    }

    .exam-footer {
      gap: 10px;
      padding: 10px;
      justify-content: space-between;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      z-index: 999;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
    }

    .exam-pc-root {
      padding-bottom: 80px;
      /* Space for the fixed footer */
    }

    .stimulus-col,
    .question-col {
      max-height: none;
      overflow-y: visible;
      padding: 15px;
    }

    .pill-btn span {
      display: none;
    }

    /* Hide text labels on mobile */

    .btn-nav,
    .btn-ragu {
      flex: 0 0 auto;
      width: 60px;
      height: 60px;
      padding: 0 !important;
      justify-content: center;
      border-radius: 50% !important;
    }

    .btn-ragu input {
      margin-right: 0 !important;
    }

    .btn-nav i {
      font-size: 1.8rem;
    }

    /* Centering Countdown on Finish Button */
    #navFinish.btn-secondary {
      flex-direction: column !important;
      justify-content: center !important;
      gap: 0 !important;
      line-height: 1 !important;
    }

    #navFinish.btn-secondary .finish-text {
      display: none !important;
    }

    #navFinish.btn-secondary .finish-time {
      display: block !important;
      font-size: 0.75rem !important;
      font-weight: 800;
      margin-top: -3px;
    }

    #navFinish.btn-secondary i {
      font-size: 1.4rem !important;
      margin-bottom: 2px;
    }
  }

  /* Prevent horizontal scroll */
  .exam-pc-root {
    overflow-x: hidden;
  }

  .timer-badge {
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    padding: 6px 16px;
    border-radius: 30px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 1.1rem;
  }

  /* Better modal z-index management */
  .modal {
    z-index: 1060 !important;
  }

  .modal-backdrop {
    z-index: 1050 !important;
  }

  /* Fullscreen Overlay Styling */
  #fsOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9998;
    /* Below browser UI (9999+) but above content */
    box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.1);
    pointer-events: auto;
    touch-action: auto;
  }

  /* When requesting fullscreen permission, lower z-index to prevent blocking dialog */
  #fsOverlay.requesting-permission {
    z-index: -1 !important;
    pointer-events: none !important;
  }

  /* When overlay is hidden (display:none), disable pointer events completely */
  #fsOverlay[style*='display: none'],
  #fsOverlay[style*='display:none'] {
    pointer-events: none !important;
    touch-action: none !important;
    z-index: -1 !important;
  }

  #fsOverlay .card {
    transition: 0.3s;
  }

  #fsOverlay .card:hover {
    transform: translateY(-5px);
  }

  /* Spacing adjustment for stimulus and question text */
  .stimulus-text p,
  .q-text p,
  .option-text p {
    margin-bottom: 0.5rem;
  }

  .stimulus-text p:last-child,
  .q-text p:last-child,
  .option-text p:last-child {
    margin-bottom: 0;
  }
</style>

<div id="fsOverlay"
  class="position-fixed top-0 start-0 w-100 h-100 bg-light d-flex flex-column align-items-center justify-content-center"
  style="z-index: 9999;">
  <div class="card text-center p-5 shadow-lg rounded-5 border-0" style="max-width: 450px;">
    <div class="mb-4">
      <i class="bi bi-shield-lock-fill text-primary" style="font-size: 5rem;"></i>
    </div>
    <h3 class="fw-bold mb-3">SakaSalika SafeExam</h3>
    <p class="text-muted mb-4 fs-5">Ujian ini mewajibkan mode <b>Layar Penuh</b> untuk menjaga integritas keluar dari
      layar penuh dianggap pelanggaran.</p>
    <button id="startFS" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow">
      MULAI UJIAN SEKARANG
    </button>
  </div>
</div>

<div class="exam-pc-root" style="opacity: 0;">
  <!-- PC HEADER -->
  <header class="exam-header">
    <div class="d-flex align-items-center">
      <h1 class="q-number-title" id="q_display_num">Soal nomor 1</h1>

      <!-- Progress Breadcrumb -->
      <div class="progress-breadcrumb ms-3">
        <span class="current" id="current_num">1</span>
        <span class="separator">/</span>
        <span class="total" id="total_num">0</span>
        <div class="progress-bar-mini">
          <div class="progress-fill" id="progress_fill" style="width: 0%"></div>
        </div>
      </div>

      <div class="font-zoom-ctrl">
        <span class="text-muted small">Ukuran font soal:</span>
        <span class="zoom-btn" data-zoom="1rem">A</span>
        <span class="zoom-btn active" data-zoom="1.1rem">A</span>
        <span class="zoom-btn" data-zoom="1.3rem">A</span>
      </div>
    </div>

    <div class="header-actions">
      <button class="pill-btn btn-blue" id="btn_info_soal"><i class="bi bi-info-circle"></i> INFORMASI SOAL</button>
      <div class="timer-pill">
        <span class="text-muted small me-1">Sisa Waktu :</span>
        <span id="exam_timer">00:00:00</span>
      </div>
      <button class="pill-btn btn-blue" id="navPanel"><i class="bi bi-grid-3x3-gap-fill"></i> Daftar Soal</button>
    </div>
  </header>

  <div class="text-end mb-2 pe-3 text-muted small fw-bold">
    <?= esc($test['subject_name'] ?? 'Ujian Utama') ?>
  </div>

  <!-- CONTENT GRID -->
  <div id="questions-container">
    <?php if (!empty($questions)): ?>
      <?php foreach ($questions as $idx => $q): ?>
        <?php
        $qid = (int) $q['id'];
        $type = $q['type_norm'] ?? 'pg';
        $opts = (array) ($q['options'] ?? []);
        $saved = (string) ($savedAnswers[$qid] ?? '');
        $isDoubtful = (int) ($doubtfulAnswers[$qid] ?? 0);

        // Prioritaskan raw_text jika ada (untuk konsistensi dengan admin detail view)
        $qText = '';
        $useRawText = !empty($q['raw_text_original']);

        if ($useRawText) {
          $qText = $q['raw_text_original'];
        } else {
          $qText = $q['question_text'] ?? '';
          if (empty(strip_tags($qText)) && !empty($q['question_text_db'])) {
            $qText = $q['question_text_db'];
          }
        }
        ?>
        <div class="q-item <?= $idx === 0 ? 'active' : '' ?>" data-idx="<?= $idx ?>" data-qid="<?= $qid ?>"
          data-type="<?= $type ?>" data-doubtful="<?= $isDoubtful ?>" data-use-raw="<?= $useRawText ? '1' : '0' ?>">
          <div class="exam-content-grid">
            <!-- LEFT COLUMN: STIMULUS/BACAAN -->
            <div class="stimulus-col">
              <div class="stimulus-text"
                data-raw-content="<?= $useRawText ? htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') : '' ?>">
                <?php if ($useRawText): ?>
                  <!-- Content will be cleaned and rendered by JavaScript -->
                <?php else: ?>
                  <?= $qText ?: '<div class="alert alert-warning">Konten bacaan tidak tersedia.</div>' ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- RIGHT COLUMN: QUESTION & OPTIONS -->
            <div class="question-col">
              <div class="q-text mb-4">
                <?php if ($type === 'pg_kompleks' || $type === 'pgk'): ?>
                  <span class="badge bg-primary rounded-pill me-2">PG Kompleks</span> Klik pada setiap pilihan jawaban yang
                  benar. Jawaban benar lebih dari satu.
                <?php elseif ($type === 'benar_salah'): ?>
                  <span class="badge bg-warning text-dark rounded-pill me-2">Benar / Salah</span> Tentukan apakah setiap
                  pernyataan berikut benar atau salah.
                <?php elseif ($type === 'esai' || $type === 'essay'): ?>
                  <span class="badge bg-success rounded-pill me-2">Esai</span> Tuliskan jawaban Anda secara lengkap dan jelas
                  pada kolom yang tersedia.
                <?php else: ?>
                  <span class="badge bg-secondary rounded-pill me-2">Pilihan Ganda</span> Pilihlah salah satu jawaban yang
                  menurut Anda paling benar.
                <?php endif; ?>
              </div>

              <div class="options-list">
                <?php if ($type === 'pg' || $type === 'pg_kompleks'): ?>
                  <?php
                  $savedArr = ($saved !== '') ? explode(',', $saved) : [];
                  $i = 0;
                  foreach ($opts as $key => $val):
                    $selected = in_array((string) $key, $savedArr);
                    $inpType = ($type === 'pg_kompleks' ? 'checkbox' : 'radio');
                    $letter = chr(65 + $i);
                    ?>
                    <label class="option-row <?= $selected ? 'selected' : '' ?> <?= $type === 'pg_kompleks' ? 'pgk-row' : '' ?>"
                      data-letter="<?= $letter ?>">
                      <input type="<?= $inpType ?>" name="ans_<?= $qid ?>[]" value="<?= esc($key) ?>"
                        class="option-check inp-target <?= $type === 'pg' ? 'radio-check' : '' ?>" data-qid="<?= $qid ?>"
                        data-type="<?= $type ?>" <?= $selected ? 'checked' : '' ?>>

                      <?php if ($type === 'pg'): ?>
                        <div class="letter-circle"><?= $letter ?></div>
                      <?php endif; ?>

                      <div class="option-text"><?= $val ?></div>
                    </label>
                    <?php $i++; endforeach; ?>

                <?php elseif ($type === 'benar_salah'): ?>
                  <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-3">Pernyataan</th>
                          <th width="60" class="text-center">B</th>
                          <th width="60" class="text-center">S</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $savedBS = explode(',', $saved);
                        foreach (['A', 'B', 'C', 'D', 'E'] as $rowIdx => $letter):
                          if (empty($opts[$letter]))
                            continue;
                          $ansValue = $savedBS[$rowIdx] ?? '';
                          ?>
                          <tr>
                            <td class="ps-3 small"><?= $opts[$letter] ?></td>
                            <td class="text-center"><input type="radio" name="bs_<?= $qid ?>_<?= $letter ?>" value="B"
                                class="inp-bs" data-qid="<?= $qid ?>" data-row="<?= $rowIdx ?>" <?= $ansValue === 'B' ? 'checked' : '' ?>></td>
                            <td class="text-center"><input type="radio" name="bs_<?= $qid ?>_<?= $letter ?>" value="S"
                                class="inp-bs" data-qid="<?= $qid ?>" data-row="<?= $rowIdx ?>" <?= $ansValue === 'S' ? 'checked' : '' ?>></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                <?php else: // ESSAY ?>
                  <textarea class="form-control inp-essay p-3" rows="10" data-qid="<?= $qid ?>"
                    placeholder="Ketik jawaban Anda di sini..."><?= esc($saved) ?></textarea>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- PC FOOTER -->
  <footer class="exam-footer">
    <button class="pill-btn btn-nav btn-prev" id="navBack"><i class="bi bi-arrow-left-circle-fill"></i> <span>Soal
        sebelumnya</span></button>

    <label class="pill-btn btn-ragu m-0" style="cursor:pointer">
      <input type="checkbox" id="check_ragu" class="me-2" style="transform: scale(1.2)"> <span>Ragu-ragu</span>
    </label>

    <button class="pill-btn btn-nav btn-next" id="navNext"><span>Soal berikutnya</span> <i
        class="bi bi-arrow-right-circle-fill"></i></button>
    <button class="pill-btn btn-nav btn-next d-none" id="navFinish"><span>Selesaikan Ujian</span> <i
        class="bi bi-check-circle-fill"></i></button>
  </footer>

  <!-- Auto-Save Indicator -->
  <div id="saveStatus" class="save-status">
    <i class="bi bi-check-circle-fill"></i>
    <span>Tersimpan</span>
  </div>

  <!-- Keyboard Hints -->
  <div id="keyboardHints" class="keyboard-hints">
    <div class="hint-item"><kbd>←</kbd> <kbd>→</kbd> Navigasi soal</div>
    <div class="hint-item"><kbd>A</kbd>-<kbd>E</kbd> Pilih jawaban</div>
    <div class="hint-item"><kbd>Space</kbd> Tandai ragu-ragu</div>
    <div class="hint-item"><kbd>Ctrl</kbd>+<kbd>P</kbd> Daftar soal</div>
    <div class="hint-item"><kbd>Esc</kbd> Tutup modal</div>
    <div class="hint-item"><kbd>?</kbd> Tampilkan/sembunyikan bantuan</div>
  </div>
</div>

<!-- IMAGE ZOOM MODAL -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0 bg-black bg-opacity-75">
        <h5 class="modal-title text-white"><i class="bi bi-zoom-in"></i> Gambar Soal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex align-items-center justify-content-center p-0"
        style="background: rgba(0,0,0,0.95);">
        <img id="zoomedImage" src="" alt="Zoomed Image"
          style="max-width: 95vw; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 0 30px rgba(255,255,255,0.1);">
      </div>
      <div class="modal-footer border-0 bg-black bg-opacity-75 justify-content-center">
        <button type="button" class="btn btn-light btn-lg px-5" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- PANEL MODAL - PLACED AT ROOT FOR BEST COMPATIBILITY -->
<div class="modal fade" id="panelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg border-0" style="border-radius: 25px;">
      <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block">
        <h4 class="modal-title fw-bold mx-auto">Navigasi Soal</h4>
        <p class="text-muted small">Klik nomor soal untuk berpindah halaman</p>
        <button type="button" class="btn-close position-absolute end-0 top-0 m-4" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="panelButtons" class="d-flex flex-wrap gap-3 justify-content-center pt-2 pb-4">
          <!-- Dynamic Buttons -->
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Lock orientation IMMEDIATELY on page load (before overlay shows)
  if (screen.orientation && screen.orientation.lock) {
    // Try to lock orientation early
    // Note: This might fail if not in fullscreen, but we try anyway
    screen.orientation.lock('portrait').catch(err => {
      console.log('Early orientation lock failed (expected):', err.message);
    });
  }

  // 🔹 FIX: Prevent back/forward cache from showing finished exam page
  // If user clicks back after finishing, redirect to dashboard immediately
  window.addEventListener('pageshow', function (event) {
    // Check if page was restored from bfcache (back/forward cache)
    if (event.persisted) {
      console.log('[BFCACHE] Page restored from cache, checking exam status...');

      // Check if exam was finished (flag set by finish button)
      if (window.examFinished || sessionStorage.getItem('exam_finished_<?= $test['id'] ?>')) {
        console.log('[BFCACHE] Exam already finished, redirecting to dashboard...');
        window.location.href = '<?= site_url('siswa/cbt') ?>';
        return;
      }
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    const TEST_ID = <?= (int) ($test['id'] ?? 0) ?>;
    const SECONDS_LEFT = <?= $remaining_seconds ?>;
    const items = document.querySelectorAll('.q-item');
    const root = document.querySelector('.exam-pc-root');
    const fsOverlay = document.getElementById('fsOverlay');
    const startFS = document.getElementById('startFS');
    const timerBox = document.getElementById('exam_timer');

    // ============================================
    // MULTIPLE TABS DETECTION
    // ============================================
    const TAB_KEY = 'cbt_active_tab_' + TEST_ID;
    const TAB_ID = Date.now() + '_' + Math.random();

    // Check if exam is already open in another tab
    const existingTab = localStorage.getItem(TAB_KEY);
    if (existingTab && existingTab !== TAB_ID) {
      // Another tab is active
      Swal.fire({
        title: 'Ujian Sudah Dibuka',
        html: 'Ujian ini sudah dibuka di tab/window lain.<br><br>Hanya satu tab yang diperbolehkan untuk ujian.',
        icon: 'error',
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: 'Tutup Tab Ini'
      }).then(() => {
        window.close();
        // If window.close() doesn't work (some browsers block it)
        setTimeout(() => {
          window.location.href = '<?= site_url('siswa/cbt') ?>';
        }, 500);
      });
      return; // Stop execution
    }

    // Register this tab as active
    localStorage.setItem(TAB_KEY, TAB_ID);

    // Listen for storage changes (other tabs opening)
    window.addEventListener('storage', (e) => {
      if (e.key === TAB_KEY && e.newValue !== TAB_ID) {
        // Another tab is trying to open the exam
        Swal.fire({
          title: 'Tab Lain Terdeteksi',
          text: 'Ujian dibuka di tab lain. Tab ini akan ditutup.',
          icon: 'warning',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          timer: 2000
        }).then(() => {
          window.close();
          setTimeout(() => {
            window.location.href = '<?= site_url('siswa/cbt') ?>';
          }, 500);
        });
      }
    });

    let isUnloading = false;

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
      isUnloading = true;
      const currentTab = localStorage.getItem(TAB_KEY);
      if (currentTab === TAB_ID) {
        localStorage.removeItem(TAB_KEY);
      }
    });

    // Periodic check (every 2 seconds)
    setInterval(() => {
      if (isUnloading) return; // Prevent race conditions during redirect
      const currentTab = localStorage.getItem(TAB_KEY);
      if (currentTab !== TAB_ID) {
        // This tab is no longer the active one
        window.location.href = '<?= site_url('siswa/cbt') ?>';
      }
    }, 2000);

    // ============================================
    // ANTI-CHEAT: DISABLE TEXT SELECTION & CONTEXT MENU
    // ============================================

    // Disable context menu (right-click / long-press)
    document.addEventListener('contextmenu', function (e) {
      // Allow context menu only for input/textarea
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        e.stopPropagation();
        console.log('[ANTI-CHEAT] Context menu blocked');
        return false;
      }
    }, true);

    // Disable text selection via mouse/touch
    document.addEventListener('selectstart', function (e) {
      // Allow selection only for input/textarea
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        console.log('[ANTI-CHEAT] Text selection blocked');
        return false;
      }
    }, true);

    // Disable copy/cut (but allow paste for essay answers)
    document.addEventListener('copy', function (e) {
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        console.log('[ANTI-CHEAT] Copy blocked');
        return false;
      }
    }, true);

    document.addEventListener('cut', function (e) {
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        console.log('[ANTI-CHEAT] Cut blocked');
        return false;
      }
    }, true);

    // Disable drag (prevents drag-to-search on mobile)
    document.addEventListener('dragstart', function (e) {
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        console.log('[ANTI-CHEAT] Drag blocked');
        return false;
      }
    }, true);

    // Additional mobile-specific protections
    if ('ontouchstart' in window) {
      // Detect long press (which triggers context menu on mobile)
      let longPressTimer = null;

      document.addEventListener('touchstart', function (e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA' &&
          !e.target.closest('button') && !e.target.closest('.option-row')) {
          longPressTimer = setTimeout(() => {
            console.log('[ANTI-CHEAT] Long press detected and blocked');
          }, 500);
        }
      }, true);

      document.addEventListener('touchend', function () {
        if (longPressTimer) {
          clearTimeout(longPressTimer);
          longPressTimer = null;
        }
      }, true);

      document.addEventListener('touchmove', function () {
        if (longPressTimer) {
          clearTimeout(longPressTimer);
          longPressTimer = null;
        }
      }, true);
    }

    console.log('[ANTI-CHEAT] Text selection and context menu protections enabled');

    // ============================================
    // SCREEN WAKE LOCK: PREVENT SCREEN FROM SLEEPING
    // ============================================
    let wakeLock = null;

    /**
     * Request wake lock to keep screen on during exam
     */
    const requestWakeLock = async () => {
      try {
        // Check if Wake Lock API is supported
        if ('wakeLock' in navigator) {
          wakeLock = await navigator.wakeLock.request('screen');
          console.log('[WAKE LOCK] Screen wake lock activated - screen will stay on');

          // Listen for wake lock release (e.g., when tab is hidden)
          wakeLock.addEventListener('release', () => {
            console.log('[WAKE LOCK] Wake lock released');
          });

          // Show notification to user
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
          });

          Toast.fire({
            icon: 'info',
            title: '🔒 Layar akan tetap hidup selama ujian'
          });

          return true;
        } else {
          console.warn('[WAKE LOCK] Wake Lock API not supported in this browser');

          // Show warning for unsupported browsers
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
          });

          Toast.fire({
            icon: 'warning',
            title: '⚠️ Browser tidak support wake lock - layar bisa mati otomatis'
          });

          return false;
        }
      } catch (err) {
        console.error('[WAKE LOCK] Failed to acquire wake lock:', err);
        return false;
      }
    };

    /**
     * Release wake lock when exam ends
     */
    const releaseWakeLock = async () => {
      if (wakeLock !== null) {
        try {
          await wakeLock.release();
          wakeLock = null;
          console.log('[WAKE LOCK] Wake lock released manually');
        } catch (err) {
          console.error('[WAKE LOCK] Failed to release wake lock:', err);
        }
      }
    };

    /**
     * Re-acquire wake lock when page becomes visible again
     */
    const reacquireWakeLockOnVisible = async () => {
      if (document.visibilityState === 'visible' && wakeLock === null) {
        console.log('[WAKE LOCK] Page visible again, re-acquiring wake lock');
        await requestWakeLock();
      }
    };

    // Add visibility change listener for wake lock
    document.addEventListener('visibilitychange', reacquireWakeLockOnVisible);

    // Release wake lock when exam ends
    window.addEventListener('beforeunload', () => {
      releaseWakeLock();
    });

    console.log('[WAKE LOCK] Wake lock system initialized');

    // 🔹 Flag to disable fullscreen handler when exam is finished
    let examFinished = false;

    const qLabelNum = document.getElementById('q_display_num');

    // ============================================
    // NETWORK DISCONNECT HANDLING
    // ============================================
    const OFFLINE_QUEUE_KEY = 'cbt_offline_queue_' + TEST_ID;
    let isOnline = navigator.onLine;
    let offlineNotificationShown = false;

    // Function to queue answer for offline mode
    const queueOfflineAnswer = (qid, answer) => {
      const queue = JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');

      // Update or add answer in queue
      const existingIndex = queue.findIndex(item => item.question_id === qid);
      if (existingIndex >= 0) {
        queue[existingIndex].answer = answer;
        queue[existingIndex].timestamp = Date.now();
      } else {
        queue.push({
          question_id: qid,
          answer: answer,
          timestamp: Date.now()
        });
      }

      localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
      console.log('[OFFLINE] Answer queued:', qid, answer);
    };

    // Function to flush offline queue when back online
    const flushOfflineQueue = () => {
      const queue = JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');

      if (queue.length === 0) return;

      console.log('[OFFLINE] Flushing queue:', queue.length, 'answers');
      showSaveStatus('saving', 'Menyinkronkan...');

      // Send all queued answers
      fetch('<?= site_url('siswa/cbt/saveAnswersBulk') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          test_id: TEST_ID,
          answers: queue
        })
      })
        .then(r => r.json())
        .then(res => {
          if (res.status === 'ok') {
            // Clear queue on success
            localStorage.removeItem(OFFLINE_QUEUE_KEY);
            showSaveStatus('saved', 'Tersinkronkan (' + queue.length + ')');
            console.log('[OFFLINE] Queue flushed successfully');
          } else {
            showSaveStatus('error', 'Gagal sinkronisasi');
          }
        })
        .catch(err => {
          console.error('[OFFLINE] Flush failed:', err);
          showSaveStatus('error', 'Gagal sinkronisasi');
        });
    };

    // Online event handler
    window.addEventListener('online', () => {
      console.log('[NETWORK] Back online');
      isOnline = true;
      offlineNotificationShown = false;

      // Show notification
      Swal.fire({
        title: 'Koneksi Kembali',
        text: 'Koneksi internet tersambung kembali. Menyinkronkan jawaban...',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });

      // Flush offline queue
      flushOfflineQueue();
    });

    // Offline event handler
    window.addEventListener('offline', () => {
      console.log('[NETWORK] Offline detected');
      isOnline = false;

      if (!offlineNotificationShown) {
        offlineNotificationShown = true;
        Swal.fire({
          title: 'Koneksi Terputus',
          html: 'Koneksi internet terputus.<br><br>Jawaban akan disimpan sementara dan dikirim otomatis saat koneksi kembali.',
          icon: 'warning',
          confirmButtonText: 'OK, Saya Mengerti'
        });
      }
    });

    // Check connection status periodically
    setInterval(() => {
      const currentOnline = navigator.onLine;
      if (currentOnline !== isOnline) {
        isOnline = currentOnline;
        if (isOnline) {
          window.dispatchEvent(new Event('online'));
        } else {
          window.dispatchEvent(new Event('offline'));
        }
      }
    }, 5000);

    // Try to flush queue on page load (in case of previous offline session)
    if (isOnline) {
      setTimeout(() => {
        flushOfflineQueue();
      }, 1000);
    }

    // ============================================
    // END NETWORK DISCONNECT HANDLING
    // ============================================

    // Try to lock orientation again after DOM loaded
    if (screen.orientation && screen.orientation.lock) {
      screen.orientation.lock('portrait').catch(err => {
        console.log('DOM orientation lock failed (expected if not fullscreen):', err.message);
      });
    }

    // Function to clean raw text (same as admin detail view)
    function cleanRawText(html) {
      let out = html;

      // 1. Simpan tag img dulu dengan placeholder
      const imgTags = [];
      out = out.replace(/<img[^>]+>/gi, function (match) {
        imgTags.push(match);
        return `[[IMG_${imgTags.length - 1}]]`;
      });

      // 2. Inject newlines di sekitar block tags agar regex line-based (^) bisa bekerja
      // meskipun opsi berada di dalam tag <p> atau <div> yang berbeda
      const blockTags = ['p', 'div', 'li', 'h[1-6]', 'blockquote', 'tr'];
      blockTags.forEach(tag => {
        const reOpen = new RegExp('<' + tag + '[^>]*>', 'gi');
        const reClose = new RegExp('</' + tag + '>', 'gi');
        out = out.replace(reOpen, '\n$&').replace(reClose, '$&\n');
      });

      // 3. Ganti <br> dengan newline juga
      out = out.replace(/<br\s*\/?>/gi, '\n');

      // 4. Regex Pembersihan Opsi (TAG-AWARE)
      // ^(?:<[^>]+>)*  --> Mengizinkan tag HTML pembuka di awal baris (misal <p>)
      // [\s\xc2\xa0&nbsp;]* --> Mengizinkan spasi/nbsp
      // [\(]?[A-Ea-e] --> Pola Opsi A-E
      const optionRegex = /^(?:<[^>]+>)*[\s\xc2\xa0&nbsp;]*[\(]?[A-Ea-e]\s*(?:[:.)]|\s+-)\s*.*$/gim;
      out = out.replace(optionRegex, '');

      const keyTypeRegex = /^(?:<[^>]+>)*[\s\xc2\xa0&nbsp;]*(?:Kunci|Tipe)\s*[\:\=\-]\s*.*$/gim;
      out = out.replace(keyTypeRegex, '');

      // 5. Hapus inline Tipe: dan Kunci:
      out = out.replace(/Tipe\s*:\s*[A-Za-z0-9_]+/gi, '');
      out = out.replace(/Kunci\s*:\s*[A-Za-z0-9,\s]+/gi, '');

      // 6. Hapus formatting tags (span, strong, em, font, u, b, i, etc.)
      out = out.replace(/<\/?(span|strong|em|font|u|b|i)[^>]*>/gi, '');

      // 7. Hapus paragraf/div/li kosong (termasuk yang berisi whitespace atau &nbsp;)
      out = out.replace(/<(p|div|li)[^>]*>([\s\n\xc2\xa0\r]|&nbsp;|<br\s*\/?>)*<\/\1>/gi, '');

      // 8. Bersihkan whitespace berlebih hasil penghapusan
      out = out.replace(/\n\s*\n/g, '\n');

      // 9. Kembalikan tag img
      imgTags.forEach((tag, index) => {
        out = out.replace(`[[IMG_${index}]]`, tag);
      });

      // 10. Bersihkan newline yang menempel pada tag blok sebelum konversi ke <br>
      out = out.replace(/>\s*\n/g, '>');
      out = out.replace(/\n\s*</g, '<');

      // 11. Terakhir, ganti newline kembali ke <br> agar tampilan tetap terjaga
      out = out.replace(/\n/g, '<br>');

      return out.trim();
    }

    // Clean and render raw text for all questions that use raw_text
    items.forEach(item => {
      const useRaw = item.dataset.useRaw === '1';
      if (useRaw) {
        const stimulusDiv = item.querySelector('.stimulus-text');
        if (stimulusDiv) {
          const rawContent = stimulusDiv.dataset.rawContent;
          if (rawContent) {
            const cleanedHtml = cleanRawText(rawContent);
            stimulusDiv.innerHTML = cleanedHtml || '<div class="alert alert-warning">Konten bacaan tidak tersedia.</div>';
          }
        }
      }
    });

    // Image Zoom Modal Handler
    const imageZoomModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    const zoomedImage = document.getElementById('zoomedImage');
    const imageZoomModalElement = document.getElementById('imageZoomModal');

    // Close modal when clicking on the dark background (not on the image)
    if (imageZoomModalElement) {
      imageZoomModalElement.querySelector('.modal-body').addEventListener('click', function (e) {
        if (e.target === this) {
          imageZoomModal.hide();
        }
      });
    }

    // Add click handler to all images in questions
    const setupImageZoom = () => {
      document.querySelectorAll('.stimulus-text img, .q-text img, .option-text img').forEach(img => {
        img.style.cursor = 'pointer';
        img.title = 'Klik untuk memperbesar';

        // Remove old event listeners by cloning
        const newImg = img.cloneNode(true);
        img.parentNode.replaceChild(newImg, img);

        newImg.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          // Set the zoomed image source
          zoomedImage.src = this.src;
          zoomedImage.alt = this.alt || 'Gambar Soal';

          // Show modal
          imageZoomModal.show();
        });
      });
    };

    // Setup image zoom on page load
    setupImageZoom();

    const btnBack = document.getElementById('navBack');
    const btnNext = document.getElementById('navNext');
    const btnFinish = document.getElementById('navFinish');
    const btnPanel = document.getElementById('navPanel');
    const checkRagu = document.getElementById('check_ragu');
    const modalPanel = new bootstrap.Modal(document.getElementById('panelModal'));
    const ANTI_CHEAT = "<?= $test['anti_cheat'] ?? 'tidak' ?>";

    // 🔒 FETCH EXISTING VIOLATION COUNT FROM SERVER
    let violationCount = <?= (int) ($existingViolationCount ?? 0) ?>;

    // Function to disable all interactions immediately (defined early for use in checks)
    const disableAllInteractions = () => {
      document.querySelectorAll('input, textarea, button, select').forEach(el => {
        el.disabled = true;
        el.style.pointerEvents = 'none';
      });
      document.querySelectorAll('.option-row, .pill-btn, .btn').forEach(el => {
        el.style.pointerEvents = 'none';
        el.style.opacity = '0.5';
      });
      const overlay = document.createElement('div');
      overlay.id = 'lockdown-overlay';
      overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 9998; cursor: not-allowed;';
      document.body.appendChild(overlay);
    };

    // 🔒 ANTI-CHEAT LIMITS:
    // - Tidak: No limit (999)
    // - Kuat: 3 warnings, 4th violation = stop (limit 4)
    // - Sangat Kuat: 1 warning, 2nd violation = stop (limit 2)
    let violationLimit = (ANTI_CHEAT === 'sangat_kuat') ? 2 : (ANTI_CHEAT === 'kuat' ? 4 : 999);

    // Function to handle time expiration
    const handleTimeExpired = () => {
      handleLockdown('Waktu Habis!', 'Waktu ujian telah berakhir.', false);
    };

    let pingInterval = null;

    const IS_NEW_SESSION = <?= json_encode($isNewSession ?? false) ?>;
    const DURATION_SEC = <?= (int) ($durationSec ?? 0) ?>;
    const LOCK_PERCENT = <?= (float) ($test['finish_button_lock'] ?? 0) ?>;
    const LOCK_DURATION = Math.ceil(DURATION_SEC * LOCK_PERCENT);
    let elapsedSeconds = <?= (int) ($elapsed_seconds ?? 0) ?>;

    // 🔹 FIX: Initialize activeIdx properly for new session
    let activeIdx = 0; // Default to first question

    if (IS_NEW_SESSION) {
      // New session: always start from question 1
      activeIdx = 0;
      localStorage.removeItem('cbt_active_idx_' + TEST_ID); // Clear old position
      localStorage.removeItem('cbt_offline_queue_' + TEST_ID); // Clear old offline queue
      console.log('[CBT] New session detected, starting from question 1, cleared old data');
    } else {
      // Continuing session: restore last position
      activeIdx = parseInt(localStorage.getItem('cbt_active_idx_' + TEST_ID)) || 0;
      console.log('[CBT] Continuing session, restored position:', activeIdx);
    }

    // Safety check: ensure activeIdx is valid
    if (activeIdx >= items.length || activeIdx < 0) {
      activeIdx = 0;
      console.warn('[CBT] Invalid activeIdx, reset to 0');
    }

    // Fullscreen Check
    if (document.fullscreenElement) {
      if (fsOverlay) fsOverlay.style.setProperty('display', 'none', 'important');
      if (root) root.style.opacity = '1';
    } else {
      if (root) root.style.opacity = '0';
    }

    // ============================================================================
    // BROWSER DETECTION & COMPATIBILITY CHECK
    // ============================================================================

    /**
     * Get Chrome version number
     * @returns {number} Chrome version or 0 if not Chrome
     */
    function getChromeVersion() {
      const raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
      return raw ? parseInt(raw[2], 10) : 0;
    }

    /**
     * Check if browser is supported for fullscreen exam
     * @returns {object} { supported: boolean, reason: string, action: string, version: number }
     */
    function isBrowserSupported() {
      const chromeVersion = getChromeVersion();
      const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
      const isEdge = navigator.userAgent.toLowerCase().indexOf('edg/') > -1;

      // Chrome < 71 has critical fullscreen bugs
      if (chromeVersion > 0 && chromeVersion < 71) {
        return {
          supported: false,
          reason: 'Chrome versi lama (v' + chromeVersion + ') memiliki bug pada mode fullscreen.',
          action: 'update',
          recommendation: 'Update Chrome ke versi 91 atau lebih baru.',
          version: chromeVersion
        };
      }

      // Chrome 71-90 has minor issues but workable
      if (chromeVersion > 0 && chromeVersion < 91) {
        return {
          supported: true,
          warning: true,
          reason: 'Chrome versi ' + chromeVersion + ' mungkin memiliki masalah minor.',
          recommendation: 'Disarankan update ke Chrome v91+ untuk pengalaman terbaik.',
          version: chromeVersion
        };
      }

      // Check fullscreen API support
      if (!document.fullscreenEnabled &&
        !document.webkitFullscreenEnabled &&
        !document.mozFullScreenEnabled &&
        !document.msFullscreenEnabled) {
        return {
          supported: false,
          reason: 'Browser tidak mendukung mode fullscreen.',
          action: 'change',
          recommendation: 'Gunakan browser modern: Chrome v91+, Firefox, atau Edge terbaru.'
        };
      }

      return {
        supported: true,
        version: chromeVersion,
        browser: chromeVersion > 0 ? 'Chrome' : (isFirefox ? 'Firefox' : (isEdge ? 'Edge' : 'Unknown'))
      };
    }

    /**
     * Handle fullscreen success
     */
    function handleFullscreenSuccess() {
      console.log('✅ Fullscreen activated successfully');

      // Lock screen orientation to portrait on mobile
      if (screen.orientation && screen.orientation.lock) {
        screen.orientation.lock('portrait').catch(err => {
          console.log('Orientation lock not supported or failed:', err);
        });
      }

      if (fsOverlay) fsOverlay.style.setProperty('display', 'none', 'important');
      if (root) root.style.opacity = '1';
      renderActive(activeIdx);

      // 🔒 Request wake lock to keep screen on during exam
      setTimeout(() => {
        requestWakeLock();
      }, 1000);
    }

    /**
     * Handle fullscreen timeout (stuck permission dialog)
     */
    function handleFullscreenTimeout() {
      console.warn('⏱️ Fullscreen permission timeout');

      Swal.fire({
        title: 'Fullscreen Tidak Merespon',
        html: `
          <div class="text-start">
            <p class="mb-3">Permintaan fullscreen tidak merespon dalam 5 detik.</p>
            <p class="text-warning fw-bold mb-2">Kemungkinan penyebab:</p>
            <ul class="mb-3">
              <li>Browser versi lama (perlu update)</li>
              <li>Permission dialog tertutup atau tidak terlihat</li>
              <li>Browser tidak support fullscreen dengan baik</li>
            </ul>
            <p class="fw-bold text-primary">Solusi:</p>
            <ol>
              <li>Klik "Refresh" untuk mencoba lagi</li>
              <li>Atau update browser ke versi terbaru</li>
              <li>Atau gunakan browser lain (Chrome v91+, Firefox, Edge)</li>
            </ol>
          </div>
        `,
        icon: 'warning',
        confirmButtonText: '<i class="fas fa-sync"></i> Refresh & Coba Lagi',
        showCancelButton: true,
        cancelButtonText: '<i class="fas fa-arrow-left"></i> Kembali',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          location.reload();
        } else {
          window.location.href = '<?= site_url('siswa/cbt') ?>';
        }
      });
    }

    /**
     * Handle fullscreen error
     */
    function handleFullscreenError(err) {
      console.error('❌ Fullscreen error:', err);

      const chromeVersion = getChromeVersion();
      const isOldChrome = chromeVersion > 0 && chromeVersion < 71;

      Swal.fire({
        title: 'Error Fullscreen',
        html: `
          <div class="text-start">
            <p class="mb-3">Gagal masuk mode layar penuh.</p>
            <p class="text-muted small mb-3"><strong>Error:</strong> ${err.message}</p>
            ${isOldChrome ? `
              <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Browser Terlalu Lama!</strong><br>
                Chrome v${chromeVersion} memiliki bug pada fullscreen.<br>
                <strong>Minimal Chrome v71 diperlukan.</strong>
              </div>
            ` : ''}
            <p class="fw-bold text-primary">Solusi:</p>
            <ol>
              <li>Update browser ke versi terbaru (Chrome v91+)</li>
              <li>Atau gunakan browser lain (Firefox/Edge terbaru)</li>
              <li>Refresh halaman dan coba lagi</li>
            </ol>
          </div>
        `,
        icon: 'error',
        confirmButtonText: '<i class="fas fa-sync"></i> Coba Lagi',
        showCancelButton: true,
        cancelButtonText: '<i class="fas fa-arrow-left"></i> Kembali',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          location.reload();
        } else {
          window.location.href = '<?= site_url('siswa/cbt') ?>';
        }
      });
    }

    /**
     * Handle no fullscreen support
     */
    function handleNoFullscreenSupport() {
      console.error('❌ No fullscreen method available');

      Swal.fire({
        title: 'Browser Tidak Didukung',
        html: `
          <div class="text-start">
            <p class="mb-3">Browser Anda tidak mendukung mode layar penuh.</p>
            <p class="fw-bold text-danger mb-2">Browser yang didukung:</p>
            <ul class="mb-3">
              <li><i class="fab fa-chrome"></i> Google Chrome v91+ (Direkomendasikan)</li>
              <li><i class="fab fa-firefox"></i> Mozilla Firefox (terbaru)</li>
              <li><i class="fab fa-edge"></i> Microsoft Edge (terbaru)</li>
            </ul>
            <p class="text-warning">
              <i class="fas fa-info-circle"></i>
              Internet Explorer tidak didukung.
            </p>
          </div>
        `,
        icon: 'error',
        confirmButtonText: '<i class="fas fa-arrow-left"></i> Kembali',
        confirmButtonColor: '#dc3545',
        allowOutsideClick: false
      }).then(() => {
        window.location.href = '<?= site_url('siswa/cbt') ?>';
      });
    }

    /**
     * Request fullscreen with timeout and fallback
     */
    function requestFullscreenWithFallback() {
      const elem = document.documentElement;

      // Detect fullscreen method
      let requestFS;
      if (elem.requestFullscreen) {
        requestFS = elem.requestFullscreen.bind(elem);
        console.log('Using standard requestFullscreen');
      } else if (elem.webkitRequestFullscreen) {
        requestFS = elem.webkitRequestFullscreen.bind(elem);
        console.log('Using webkitRequestFullscreen (Safari/Chrome)');
      } else if (elem.webkitRequestFullScreen) {
        // Safari older versions (capital S)
        requestFS = elem.webkitRequestFullScreen.bind(elem);
        console.log('Using webkitRequestFullScreen (Safari old)');
      } else if (elem.mozRequestFullScreen) {
        requestFS = elem.mozRequestFullScreen.bind(elem);
        console.log('Using mozRequestFullScreen (Firefox)');
      } else if (elem.msRequestFullscreen) {
        requestFS = elem.msRequestFullscreen.bind(elem);
        console.log('Using msRequestFullscreen (IE/Edge old)');
      }

      if (!requestFS) {
        handleNoFullscreenSupport();
        return;
      }

      try {
        const fsPromise = requestFS();

        // Set timeout untuk handle stuck permission dialog (5 seconds)
        const timeoutId = setTimeout(() => {
          console.warn('Fullscreen permission timeout after 5 seconds');
          handleFullscreenTimeout();
        }, 5000);

        // Handle promise (modern browsers)
        if (fsPromise && typeof fsPromise.then === 'function') {
          fsPromise.then(() => {
            clearTimeout(timeoutId);
            handleFullscreenSuccess();
          }).catch(err => {
            clearTimeout(timeoutId);
            handleFullscreenError(err);
          });
        } else {
          // Old browser - check after delay
          setTimeout(() => {
            clearTimeout(timeoutId);

            // Check if fullscreen actually activated
            if (document.fullscreenElement ||
              document.webkitFullscreenElement ||
              document.mozFullScreenElement ||
              document.msFullscreenElement) {
              handleFullscreenSuccess();
            } else {
              // Fullscreen not activated, but no error thrown
              console.warn('Fullscreen not activated (old browser)');
              handleFullscreenTimeout();
            }
          }, 500);
        }
      } catch (err) {
        handleFullscreenError(err);
      }
    }

    // ============================================================================
    // START FULLSCREEN BUTTON HANDLER
    // ============================================================================

    if (startFS) {
      startFS.onclick = () => {
        console.log('[BUTTON] Button clicked, violationCount:', violationCount, 'violationLimit:', violationLimit);

        // 🔒 CHECK VIOLATION LIMIT BEFORE ALLOWING START
        // 🔹 FIX: Allow one more chance even if limit reached (for false positives like screen lock)
        if (ANTI_CHEAT !== 'tidak' && violationCount > violationLimit) {
          // Only block if EXCEEDED limit (not just reached)
          console.log('[BUTTON] Blocked - exceeded limit');
          Swal.fire({
            title: 'Akses Ditolak',
            text: 'Anda telah melebihi batas pelanggaran. Ujian tidak dapat dilanjutkan.',
            icon: 'error',
            confirmButtonText: 'Kembali'
          }).then(() => {
            window.location.href = '<?= site_url('siswa/cbt') ?>';
          });
          return;
        }

        // 🔹 FIX: Show warning if at limit (last chance)
        if (ANTI_CHEAT !== 'tidak' && violationCount === violationLimit) {
          console.log('[BUTTON] At limit - showing warning');
          Swal.fire({
            title: 'Peringatan Terakhir!',
            html: 'Anda sudah mencapai batas pelanggaran.<br><br>' +
              '<strong>Ini adalah kesempatan terakhir Anda.</strong><br>' +
              'Pelanggaran berikutnya akan menghentikan ujian secara otomatis.<br><br>' +
              '<small class="text-muted">Tips: Jangan keluar dari fullscreen atau switch tab/app.</small>',
            icon: 'warning',
            confirmButtonText: 'Saya Mengerti, Lanjutkan',
            showCancelButton: true,
            cancelButtonText: 'Kembali',
            confirmButtonColor: '#f5576c'
          }).then((result) => {
            if (!result.isConfirmed) {
              window.location.href = '<?= site_url('siswa/cbt') ?>';
              return;
            }
            // Continue to fullscreen request below
            console.log('[BUTTON] User confirmed, proceeding to fullscreen');
            proceedToFullscreen(); // Call existing function
          });
          return;
        }

        // Normal flow: request fullscreen (call existing function)
        console.log('[BUTTON] Normal flow, proceeding to fullscreen');
        proceedToFullscreen();
      };

      /**
       * Proceed to fullscreen (called after violation check)
       */
      function proceedToFullscreen() {
        // Detect browser dan device
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        console.log('Browser detection:', { isSafari, isIOS });

        // iOS Safari tidak support fullscreen API untuk document
        if (isIOS) {
          console.log('iOS detected - using alternative fullscreen');

          // Hide address bar (tanpa lock scroll)
          setTimeout(() => {
            window.scrollTo(0, 1);
          }, 100);

          // JANGAN lock scroll - biarkan user bisa scroll konten soal
          // Hanya prevent pull-to-refresh
          document.body.style.overscrollBehavior = 'none';

          // Hide overlay and show exam
          if (fsOverlay) fsOverlay.style.setProperty('display', 'none', 'important');
          if (root) root.style.opacity = '1';
          renderActive(activeIdx);

          // Show info untuk iOS users
          Swal.fire({
            title: 'Mode Ujian Aktif',
            html: 'Jangan keluar dari halaman ini atau beralih ke aplikasi lain.<br><small class="text-muted">iOS tidak mendukung fullscreen penuh</small>',
            icon: 'info',
            timer: 3000,
            showConfirmButton: false
          });

          return;
        }

        // Desktop/Android: Check browser compatibility
        const browserCheck = isBrowserSupported();

        if (!browserCheck.supported) {
          // Browser not supported - show error
          Swal.fire({
            title: 'Browser Tidak Didukung',
            html: `
              <div class="text-start">
                <p class="mb-3">${browserCheck.reason}</p>
                <p class="text-danger fw-bold mb-3">
                  <i class="fas fa-exclamation-triangle"></i>
                  Ujian tidak dapat dilakukan dengan browser ini.
                </p>
                <p class="fw-bold text-primary mb-2">Solusi:</p>
                <p>${browserCheck.recommendation}</p>
              </div>
            `,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-exclamation-circle"></i> Lanjutkan (Risiko Sendiri)',
            cancelButtonText: '<i class="fas fa-arrow-left"></i> Kembali',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
          }).then((result) => {
            if (result.isConfirmed) {
              // User accepts risk, proceed anyway
              console.warn('User proceeding with unsupported browser');
              proceedToFullscreen();
            } else {
              // Go back
              window.location.href = '<?= site_url('siswa/cbt') ?>';
            }
          });
          return;
        }

        if (browserCheck.warning) {
          // Browser has minor issues - show warning
          Swal.fire({
            title: 'Peringatan Browser',
            html: `
              <div class="text-start">
                <p class="mb-3">${browserCheck.reason}</p>
                <p class="text-warning mb-3">
                  <i class="fas fa-info-circle"></i>
                  ${browserCheck.recommendation}
                </p>
                <p>Anda masih bisa melanjutkan, tapi mungkin ada masalah minor.</p>
              </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Lanjutkan',
            cancelButtonText: '<i class="fas fa-arrow-left"></i> Kembali',
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
          }).then((result) => {
            if (result.isConfirmed) {
              proceedToFullscreen();
            } else {
              window.location.href = '<?= site_url('siswa/cbt') ?>';
            }
          });
          return;
        }

        // Browser supported - proceed normally
        proceedToFullscreen();
      };

      /**
       * Proceed to fullscreen (called after browser check)
       */
      function proceedToFullscreen() {
        // IMPORTANT: Lower overlay z-index BEFORE requesting fullscreen
        // This prevents overlay from blocking browser permission dialog
        if (fsOverlay) {
          fsOverlay.style.zIndex = '-1';
          fsOverlay.style.pointerEvents = 'none';
        }

        // Add small delay to ensure z-index applied
        setTimeout(() => {
          requestFullscreenWithFallback();
        }, 50);
      }
    }


    const handleLockdown = (title, message, isCheat = true) => {
      // Disable all interactions
      disableAllInteractions();

      // Stop timer immediately
      if (timerInterval) clearInterval(timerInterval);
      if (pingInterval) clearInterval(pingInterval);

      // Show detailed lockdown message with countdown
      const lockdownMessage = isCheat
        ? `${message}<br><br><div class="alert alert-danger mt-3 mb-3"><strong>⚠️ UJIAN DIHENTIKAN PAKSA</strong><br>Anda telah melakukan pelanggaran berulang kali.<br>Sistem akan mengumpulkan jawaban Anda dan menghentikan ujian.</div><div class="text-muted small">Jawaban yang sudah dijawab akan tetap tersimpan.</div><br><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div>`
        : `${message}<br><br><div class="alert alert-info mt-3 mb-3">Jawaban Anda sedang dikumpulkan...</div><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>`;

      Swal.fire({
        title: title,
        html: lockdownMessage,
        icon: isCheat ? 'error' : 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          // 🔹 Set flag to disable fullscreen handler
          examFinished = true;

          // Disable beforeunload warning
          window.onbeforeunload = null;

          if (isCheat) {
            // Force submit immediately via AJAX
            fetch('<?= site_url('siswa/cbt/submit/' . $test['id']) ?>?forced=1', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              body: JSON.stringify({ forced: true })
            })
              .then(r => r.json())
              .then(res => {
                setTimeout(() => {
                  window.location.href = res.redirect || '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>?forced=1';
                }, 1500); // Increased delay to show message
              })
              .catch(() => {
                setTimeout(() => {
                  window.location.href = '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>?forced=1';
                }, 2000);
              });
          } else {
            // Non-cheat (timeout)
            setTimeout(() => {
              window.location.href = '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>?timeout=1';
            }, 2000);
          }
        }
      });
    };

    const reportViolation = (event) => {
      if (ANTI_CHEAT === 'tidak') return;

      const msg = `[VIOLATION] ${event}`;

      // Ping server with violation event
      ping(msg).then(res => {
        if (res.is_locked) return; // handled by ping internally

        const remainingChances = res.violation_limit - res.violation_count;

        // Build detailed warning message
        let warningHtml = '<div class="text-start">';
        warningHtml += '<p class="mb-3"><strong>Anda telah melakukan pelanggaran:</strong></p>';
        warningHtml += '<ul class="mb-3">';
        if (event.includes('Exit Fullscreen')) {
          warningHtml += '<li>Keluar dari mode layar penuh (ESC atau minimize)</li>';
        } else if (event.includes('Tab/Window Switched')) {
          warningHtml += '<li>Berpindah tab atau aplikasi lain</li>';
        }
        warningHtml += '</ul>';

        if (remainingChances > 0) {
          warningHtml += `<div class="alert alert-warning mb-3">`;
          warningHtml += `<strong>⚠️ Peringatan ke-${res.violation_count} dari ${res.violation_limit - 1}</strong><br>`;
          warningHtml += `Sisa kesempatan: <strong>${remainingChances} kali</strong>`;
          warningHtml += `</div>`;
          warningHtml += '<p class="text-danger mb-2"><strong>Jangan ulangi pelanggaran!</strong></p>';
          warningHtml += '<p class="small text-muted">Jika Anda melakukan pelanggaran lagi, ujian akan dihentikan secara otomatis dan jawaban akan dikumpulkan paksa.</p>';
        } else {
          warningHtml += `<div class="alert alert-danger mb-3">`;
          warningHtml += `<strong>🚨 PERINGATAN TERAKHIR!</strong><br>`;
          warningHtml += `Ini adalah kesempatan terakhir Anda.`;
          warningHtml += `</div>`;
          warningHtml += '<p class="text-danger mb-2"><strong>Pelanggaran berikutnya akan menghentikan ujian!</strong></p>';
          warningHtml += '<p class="small text-muted">Tetap di halaman ujian dan jangan keluar dari mode layar penuh.</p>';
        }
        warningHtml += '</div>';

        // Pause timer saat notifikasi muncul
        if (timerInterval) clearInterval(timerInterval);

        Swal.fire({
          title: 'Peringatan Kecurangan!',
          html: warningHtml,
          icon: 'warning',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: true,
          confirmButtonText: 'OK, Saya Mengerti',
          confirmButtonColor: '#f5576c',
          width: '500px'
        }).then(() => {
          startTimer();
        });
      });
    };

    const ping = (event = null) => {
      const data = { test_id: TEST_ID };
      if (event) data.event = event;

      return fetch('<?= site_url('siswa/cbt/ping') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
      }).then(r => r.json()).then(res => {
        // Sync local violation count and limit with server
        if (res.violation_count !== undefined) violationCount = res.violation_count;
        if (res.violation_limit !== undefined) violationLimit = res.violation_limit;

        if (res.status === 'finished' || res.is_locked) {
          handleLockdown('Sesi Berakhir', res.message || 'Ujian telah dihentikan oleh sistem.', true);
        }
        return res;
      }).catch(err => {
        console.warn("Ping failed", err);
        throw err;
      });
    };

    // Detect iOS untuk skip fullscreen check
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    // Fullscreen Event - Support semua browser termasuk Safari
    const fullscreenChangeHandler = () => {
      // 🔹 Skip if exam is finished (prevent overlay on redirect)
      if (examFinished) {
        console.log('Exam finished - skipping fullscreen handler');
        return;
      }

      // Skip fullscreen check untuk iOS (karena tidak support)
      if (isIOS) return;

      const isFullscreen = document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.webkitFullScreenElement || // Safari old (capital S)
        document.mozFullScreenElement ||
        document.msFullscreenElement;

      console.log('Fullscreen change:', { isFullscreen });

      if (!isFullscreen) {
        console.log('Exited fullscreen - showing overlay');
        if (fsOverlay) {
          fsOverlay.style.setProperty('display', 'flex', 'important');
          // 🔹 FIX: Reset overlay z-index to make button clickable again
          fsOverlay.style.zIndex = '9998';
          fsOverlay.style.pointerEvents = 'auto';
        }
        if (root) root.style.opacity = '0';

        // 🔹 FIX: Reset button state to ensure it's clickable
        if (startFS) {
          startFS.disabled = false;
          startFS.style.pointerEvents = 'auto';
          startFS.style.opacity = '1';
          console.log('[BUTTON] Button state reset after fullscreen exit');
        }

        if (ANTI_CHEAT !== 'tidak') reportViolation('Exit Fullscreen');
      }
    };

    // Add event listeners untuk semua browser (skip untuk iOS)
    if (!isIOS) {
      document.addEventListener('fullscreenchange', fullscreenChangeHandler);
      document.addEventListener('webkitfullscreenchange', fullscreenChangeHandler); // Safari
      document.addEventListener('mozfullscreenchange', fullscreenChangeHandler); // Firefox
      document.addEventListener('msfullscreenchange', fullscreenChangeHandler); // IE/Edge
      console.log('Fullscreen event listeners added');
    } else {
      console.log('iOS detected - fullscreen listeners skipped');
    }

    // Visibility Event
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && ANTI_CHEAT !== 'tidak') {
        reportViolation('Tab/Window Switched');
      }
    });

    // Orientation Change Event - Keep trying to lock to portrait
    if (screen.orientation) {
      screen.orientation.addEventListener('change', () => {
        console.log('Orientation changed to:', screen.orientation.type);

        // If orientation changed to landscape, try to lock back to portrait
        if (screen.orientation.type.includes('landscape')) {
          console.log('Detected landscape, attempting to lock to portrait...');

          if (screen.orientation.lock) {
            screen.orientation.lock('portrait').then(() => {
              console.log('Successfully locked back to portrait');
            }).catch(err => {
              console.log('Failed to lock to portrait:', err.message);
            });
          }
        }
      });
    }

    // Font Zoom Logic
    document.querySelectorAll('.zoom-btn').forEach(btn => {
      btn.onclick = () => {
        const size = btn.dataset.zoom;
        document.documentElement.style.setProperty('--exam-font-size', size);
        document.querySelectorAll('.zoom-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      };
    });

    const renderActive = (idx) => {
      items.forEach((item, i) => {
        item.classList.toggle('active', i === idx);
      });
      activeIdx = idx;
      localStorage.setItem('cbt_active_idx_' + TEST_ID, idx);

      // Update Title
      if (qLabelNum) qLabelNum.innerText = `Soal nomor ${idx + 1}`;

      // Update Progress Breadcrumb
      updateProgressBreadcrumb();

      // Update Navigation
      if (btnBack) btnBack.disabled = (idx === 0);
      const isEnd = (idx === items.length - 1);
      if (btnNext) btnNext.classList.toggle('d-none', isEnd);
      if (btnFinish) btnFinish.classList.toggle('d-none', !isEnd);

      // Sync Check Ragu-ragu
      const currentItem = items[idx];
      if (currentItem && checkRagu) {
        checkRagu.checked = (currentItem.dataset.doubtful == '1');
      }

      window.scrollTo({ top: 0, behavior: 'smooth' });

      // Setup image zoom for current question
      setupImageZoom();

      // Render MathJax untuk formula matematika
      if (typeof MathJax !== 'undefined') {
        MathJax.typesetPromise().catch((err) => console.log('MathJax error:', err));
      }
    };

    // Update Progress Breadcrumb
    const updateProgressBreadcrumb = () => {
      const currentNum = document.getElementById('current_num');
      const totalNum = document.getElementById('total_num');
      const progressFill = document.getElementById('progress_fill');

      if (currentNum) currentNum.textContent = activeIdx + 1;
      if (totalNum) totalNum.textContent = items.length;
      if (progressFill) {
        const percentage = ((activeIdx + 1) / items.length) * 100;
        progressFill.style.width = percentage + '%';
      }
    };

    // Auto-Save Status Indicator
    let saveStatusTimeout = null;
    const showSaveStatus = (status = 'saved', message = 'Tersimpan') => {
      const saveStatus = document.getElementById('saveStatus');
      if (!saveStatus) return;

      // Clear previous timeout
      if (saveStatusTimeout) clearTimeout(saveStatusTimeout);

      // Remove all status classes
      saveStatus.classList.remove('saving', 'saved', 'error', 'show');

      // Update content based on status
      if (status === 'saving') {
        saveStatus.innerHTML = '<i class="bi bi-arrow-repeat spin"></i><span>Menyimpan...</span>';
        saveStatus.classList.add('saving');
      } else if (status === 'saved') {
        saveStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>' + message + '</span>';
        saveStatus.classList.add('saved');
      } else if (status === 'error') {
        saveStatus.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span>' + message + '</span>';
        saveStatus.classList.add('error');
      }

      // Show indicator
      saveStatus.classList.add('show');

      // Auto-hide after 2 seconds (except for saving status)
      if (status !== 'saving') {
        saveStatusTimeout = setTimeout(() => {
          saveStatus.classList.remove('show');
        }, 2000);
      }
    };

    if (btnBack) btnBack.onclick = () => { if (activeIdx > 0) renderActive(activeIdx - 1); };
    if (btnNext) btnNext.onclick = () => { if (activeIdx < items.length - 1) renderActive(activeIdx + 1); };

    // Commit logic - Support offline queue
    const commitAnswer = (qid, val) => {
      // Prevent saving if time expired
      if (timeExpiredHandled) {
        showSaveStatus('error', 'Waktu habis');
        return;
      }

      // Check if online
      if (!isOnline) {
        // Queue answer for later
        queueOfflineAnswer(qid, val);
        showSaveStatus('saved', 'Tersimpan (offline)');
        return;
      }

      showSaveStatus('saving');

      const fd = new FormData();
      fd.append('test_id', TEST_ID);
      fd.append('question_id', qid);
      if (val !== null) fd.append('answer', val);
      fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

      fetch('<?= site_url('siswa/cbt/saveAnswer') ?>', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(response => {
          // Check HTTP status first
          if (!response.ok) {
            console.error('[SAVE] HTTP Error:', response.status, response.statusText);

            // Session expired or unauthorized
            if (response.status === 403 || response.status === 401) {
              Swal.fire({
                title: 'Sesi Berakhir',
                text: 'Sesi ujian Anda telah berakhir. Jawaban disimpan offline. Halaman akan dimuat ulang.',
                icon: 'warning',
                allowOutsideClick: false,
                confirmButtonText: 'Muat Ulang'
              }).then(() => {
                // Flush offline queue first
                flushOfflineQueue();
                setTimeout(() => {
                  window.location.reload();
                }, 1000);
              });
              throw new Error('Session expired');
            }
          }
          return response.json();
        })
        .then(data => {
          // Server returns { status: 'ok', saved: {...} } on success
          if (data.status === 'ok' || data.saved) {
            showSaveStatus('saved', 'Tersimpan');
            console.log('[SAVE] Success:', qid, val);
          } else if (data.error) {
            console.error('[SAVE] Server Error:', data.error);
            showSaveStatus('error', data.error);
            // Queue answer as fallback
            queueOfflineAnswer(qid, val);
          } else {
            console.error('[SAVE] Unknown response:', data);
            showSaveStatus('error', 'Gagal menyimpan');
            // Queue answer as fallback
            queueOfflineAnswer(qid, val);
          }
        })
        .catch(err => {
          console.error("[SAVE] Network Failure:", err);
          // Queue answer for retry
          queueOfflineAnswer(qid, val);
          showSaveStatus('error', 'Koneksi gagal (tersimpan offline)');
        });
    };

    // Ragu-ragu toggle - HANYA UI, tidak kirim ke server
    if (checkRagu) {
      checkRagu.onchange = (e) => {
        const activeQ = items[activeIdx];
        if (!activeQ) return;
        const isChecked = e.target.checked;

        // Update UI saja
        activeQ.dataset.doubtful = isChecked ? '1' : '0';

        // Simpan ke localStorage untuk persistence
        const doubtfulKey = `cbt_doubtful_${TEST_ID}`;
        let doubtfulList = JSON.parse(localStorage.getItem(doubtfulKey) || '[]');
        const qid = activeQ.dataset.qid;

        if (isChecked) {
          if (!doubtfulList.includes(qid)) doubtfulList.push(qid);
        } else {
          doubtfulList = doubtfulList.filter(id => id !== qid);
        }

        localStorage.setItem(doubtfulKey, JSON.stringify(doubtfulList));
      };
    }

    // Handle Option Selection
    document.querySelectorAll('.inp-target').forEach(inp => {
      inp.onchange = (e) => {
        const wrap = e.target.closest('.q-item');
        const qid = wrap.dataset.qid;
        const type = wrap.dataset.type;
        const label = e.target.closest('.option-row');

        let val = '';
        if (type === 'pg_kompleks' || type === 'pgk') {
          val = Array.from(wrap.querySelectorAll('.option-check:checked')).map(i => i.value).join(',');
          label.classList.toggle('selected', e.target.checked);
        } else {
          val = e.target.value;
          wrap.querySelectorAll('.option-row').forEach(r => r.classList.remove('selected'));
          label.classList.add('selected');
        }

        // Kirim jawaban saja, jangan ubah status ragu-ragu
        commitAnswer(qid, val);
      };
    });

    // Benar Salah
    document.querySelectorAll('.inp-bs').forEach(inp => {
      inp.onchange = (e) => {
        const wrap = e.target.closest('.q-item');
        const qid = wrap.dataset.qid;
        let bsData = [];
        for (let i = 0; i < 5; i++) {
          const picked = wrap.querySelector(`.inp-bs[data-row="${i}"]:checked`);
          bsData[i] = picked ? picked.value : '';
        }

        // Kirim jawaban saja, jangan ubah status ragu-ragu
        commitAnswer(qid, bsData.join(','));
      };
    });

    // Essay
    document.querySelectorAll('.inp-essay').forEach(inp => {
      inp.onblur = (e) => {
        const qid = e.target.dataset.qid;

        // Kirim jawaban saja, jangan ubah status ragu-ragu
        commitAnswer(qid, e.target.value);
      };
    });

    // Timer Logic
    let timeRaw = SECONDS_LEFT;
    let timerInterval = null; // Variable to store timer interval
    let timeExpiredHandled = false; // Flag to prevent multiple submissions

    const ticker = () => {
      // Countdown Timer
      if (timeRaw > 0) {
        const h = Math.floor(timeRaw / 3600), m = Math.floor((timeRaw % 3600) / 60), s = timeRaw % 60;
        if (timerBox) {
          timerBox.textContent = [h, m, s].map(v => v < 10 ? '0' + v : v).join(':');

          // Warning when 5 minutes left
          if (timeRaw === 300 && timeRaw > 0) {
            Swal.fire({
              title: 'Perhatian!',
              text: 'Waktu ujian tersisa 5 menit lagi.',
              icon: 'warning',
              timer: 3000,
              showConfirmButton: false
            });
          }

          // Warning when 1 minute left
          if (timeRaw === 60) {
            Swal.fire({
              title: 'Perhatian!',
              text: 'Waktu ujian tersisa 1 menit lagi.',
              icon: 'warning',
              timer: 3000,
              showConfirmButton: false
            });
          }
        }
        timeRaw--;
      } else {
        // Time expired - auto submit
        if (timerBox) {
          timerBox.textContent = "00:00:00";
          timerBox.style.color = '#dc3545';
          timerBox.style.fontWeight = 'bold';
        }

        // Handle time expiration only once
        if (!timeExpiredHandled) {
          timeExpiredHandled = true;
          handleTimeExpired();
        }
      }

      // Finish Button Lock Logic
      if (LOCK_DURATION > 0 && btnFinish) {
        if (elapsedSeconds < LOCK_DURATION) {
          const waitTime = LOCK_DURATION - elapsedSeconds;
          const wm = Math.floor(waitTime / 60), ws = waitTime % 60;
          const waitText = (wm > 0 ? wm + 'm ' : '') + ws + 's';

          btnFinish.disabled = true;
          btnFinish.innerHTML = `<i class="bi bi-lock-fill"></i> <span class="finish-text">Selesaikan</span> <span class="finish-time">${waitText}</span>`;
          btnFinish.classList.remove('btn-next', 'btn-success');
          btnFinish.classList.add('btn-secondary');
        } else {
          btnFinish.disabled = false;
          btnFinish.innerHTML = `<i class="bi bi-check2-all"></i> <span class="finish-text">Selesaikan Ujian</span>`;
          btnFinish.classList.remove('btn-secondary', 'btn-next');
          btnFinish.classList.add('btn-success');
        }
      }

      elapsedSeconds++;
    };

    // Function to start/resume timer
    const startTimer = () => {
      if (timerInterval) {
        clearInterval(timerInterval);
      }
      timerInterval = setInterval(ticker, 1000);
      ticker(); // Run immediately
    };

    // Start timer initially
    startTimer();

    // Periodic Ping (every 30s)
    pingInterval = setInterval(() => ping(), 30000);

    // ============================================
    // TIMER SYNC WITH SERVER
    // ============================================
    // Sync timer with server every 60 seconds to prevent desync
    let timerSyncInterval = setInterval(() => {
      if (timeExpiredHandled) {
        clearInterval(timerSyncInterval);
        return;
      }

      fetch('<?= site_url('siswa/cbt/getTimerSync') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ test_id: TEST_ID })
      })
        .then(r => r.json())
        .then(res => {
          if (res.success && res.remaining_seconds !== undefined) {
            const serverTime = parseInt(res.remaining_seconds);
            const clientTime = timeRaw;
            const diff = Math.abs(serverTime - clientTime);

            // If difference is more than 5 seconds, sync with server
            if (diff > 5) {
              console.log('[TIMER SYNC] Adjusting timer. Client:', clientTime, 'Server:', serverTime, 'Diff:', diff);
              timeRaw = serverTime;

              // Show notification if significant difference
              if (diff > 30) {
                Swal.fire({
                  title: 'Waktu Disesuaikan',
                  text: 'Timer telah disesuaikan dengan server.',
                  icon: 'info',
                  timer: 2000,
                  showConfirmButton: false
                });
              }
            }
          }
        })
        .catch(err => {
          console.warn('[TIMER SYNC] Failed:', err);
        });
    }, 60000); // Every 60 seconds

    // Panel Rendering
    const renderPanel = () => {
      if (!panelButtons) return;
      panelButtons.innerHTML = '';
      items.forEach((item, i) => {
        const qid = item.dataset.qid;
        const type = item.dataset.type;
        const isDoubtful = (item.dataset.doubtful == '1');
        let answered = false;
        let answerLabel = '';

        if (type === 'pg') {
          const checked = item.querySelector('.inp-target:checked');
          if (checked) {
            answered = true;
            const row = checked.closest('.option-row');
            answerLabel = row ? row.dataset.letter : '';
          }
        } else if (type === 'pg_kompleks' || type === 'pgk') {
          answered = !!item.querySelector('.inp-target:checked');
          if (answered) answerLabel = '√';
        } else if (type === 'benar_salah') {
          // 🔒 VALIDATION: Check if ALL options are answered for benar-salah
          const bsRows = item.querySelectorAll('.inp-bs[data-row]');
          const totalRows = new Set();
          bsRows.forEach(inp => totalRows.add(inp.dataset.row));
          const rowCount = totalRows.size;

          let answeredRows = 0;
          for (let i = 0; i < rowCount; i++) {
            const picked = item.querySelector(`.inp-bs[data-row="${i}"]:checked`);
            if (picked) answeredRows++;
          }

          // All rows must be answered
          answered = (answeredRows === rowCount && rowCount > 0);
          if (answered) {
            answerLabel = '√';
          } else if (answeredRows > 0) {
            // Partially answered - show progress
            answerLabel = `${answeredRows}/${rowCount}`;
          }
        } else {
          const essay = item.querySelector('.inp-essay');
          answered = (essay && essay.value.trim().length > 0);
          if (answered) answerLabel = '√';
        }

        const btnWrap = document.createElement('div');
        btnWrap.className = 'position-relative';

        const btn = document.createElement('button');
        let bgColor = answered ? 'btn-success' : 'btn-outline-secondary';
        if (isDoubtful) bgColor = 'btn-warning';

        btn.className = `btn ${bgColor} m-1 fw-bold`;
        btn.style.width = '55px';
        btn.style.height = '55px';
        btn.style.borderRadius = '12px';
        btn.style.position = 'relative';
        btn.innerText = (i + 1);

        if (answered && answerLabel) {
          const badge = document.createElement('span');
          badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark border border-light';
          badge.style.zIndex = '10';
          badge.style.fontSize = '0.7rem';
          badge.innerText = answerLabel;
          btn.appendChild(badge);
        } else if (!answered && answerLabel) {
          // Show partial progress for incomplete benar-salah
          const badge = document.createElement('span');
          badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-light';
          badge.style.zIndex = '10';
          badge.style.fontSize = '0.65rem';
          badge.innerText = answerLabel;
          btn.appendChild(badge);
        }

        btn.onclick = () => {
          renderActive(i);
          modalPanel.hide();
        };
        panelButtons.appendChild(btn);
      });
    };

    if (btnPanel) {
      btnPanel.onclick = () => {
        renderPanel();
        modalPanel.show();
      };
    }

    if (btnFinish) {
      btnFinish.onclick = () => {
        // Prevent double-click
        if (btnFinish.disabled) return;

        let unanswered = 0;
        let doubtful = 0;
        let incompleteBS = []; // Track incomplete benar-salah questions

        items.forEach((item, idx) => {
          const type = item.dataset.type;
          const isDoubtful = (item.dataset.doubtful == '1');
          let hasAnswer = false;

          if (type === 'pg' || type === 'pg_kompleks' || type === 'pgk') {
            hasAnswer = !!item.querySelector('.inp-target:checked');
          } else if (type === 'benar_salah') {
            const bsRows = item.querySelectorAll('.inp-bs[data-row]');
            const totalRows = new Set();
            bsRows.forEach(inp => totalRows.add(inp.dataset.row));
            const rowCount = totalRows.size;
            let answeredRows = 0;
            for (let i = 0; i < rowCount; i++) {
              const picked = item.querySelector(`.inp-bs[data-row="${i}"]:checked`);
              if (picked) answeredRows++;
            }
            hasAnswer = (answeredRows === rowCount && rowCount > 0);
            if (!hasAnswer && rowCount > 0) {
              incompleteBS.push({ num: idx + 1, answered: answeredRows, total: rowCount });
            }
          } else {
            const essay = item.querySelector('.inp-essay');
            hasAnswer = (essay && essay.value.trim().length > 0);
          }

          if (isDoubtful) doubtful++;
          if (!hasAnswer) unanswered++;
        });

        if (unanswered > 0 || doubtful > 0) {
          let warnMsg = "Ujian belum bisa diselesaikan:<br>";
          if (unanswered > 0) {
            warnMsg += `- Masih ada <b>${unanswered}</b> soal belum dijawab.<br>`;
            if (incompleteBS.length > 0) {
              warnMsg += `<br><b>Soal Benar/Salah yang belum lengkap:</b><br>`;
              incompleteBS.forEach(q => {
                warnMsg += `&nbsp;&nbsp;• Soal #${q.num}: ${q.answered}/${q.total} pernyataan dijawab<br>`;
              });
              warnMsg += `<br><small class='text-muted'>Semua pernyataan harus dijawab (Benar atau Salah)</small><br>`;
            }
          }
          if (doubtful > 0) warnMsg += `- Masih ada <b>${doubtful}</b> soal dalam status Ragu-ragu.<br>`;
          warnMsg += "<br><small class='text-muted'>Silakan selesaikan semua soal terlebih dahulu.</small>";
          Swal.fire({ title: 'Perhatian!', html: warnMsg, icon: 'warning', confirmButtonText: 'Kembali Ke Soal', confirmButtonColor: '#0d6efd' });
          return;
        }

        Swal.fire({
          title: '<h3 class="fw-bold mb-0">Selesaikan Ujian?</h3>',
          html: `
            <div class="mt-3 mb-4">
              <div class="p-3 rounded-4 bg-light border text-start shadow-sm">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Total Soal</span>
                  <span class="fw-bold text-dark">${items.length}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Terjawab</span>
                  <span class="fw-bold text-success">${items.length}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Status</span>
                  <span class="badge bg-success text-white px-2">Siap Dikirim</span>
                </div>
              </div>
              <p class="mt-4 mb-0 text-muted small">Setelah mengklik tombol di bawah, jawaban Anda akan <br>dikirim secara permanen dan tidak dapat diubah lagi.</p>
            </div>
          `,
          icon: 'question',
          iconColor: '#3b82f6',
          showCancelButton: true,
          confirmButtonText: 'Ya, Akhiri Ujian',
          confirmButtonColor: '#4f46e5',
          cancelButtonText: 'Batal',
          cancelButtonColor: '#64748b',
          showLoaderOnConfirm: true,
          width: '450px',
          padding: '2rem',
          customClass: {
            popup: 'rounded-5 border-0 shadow-lg',
            confirmButton: 'px-4 py-2 fw-bold rounded-3 ms-2',
            cancelButton: 'px-4 py-2 fw-bold rounded-3 me-2'
          },
          allowOutsideClick: false,
          preConfirm: () => {
            // 🔹 Set flag to disable fullscreen handler
            examFinished = true;

            // 🔹 FIX: Set sessionStorage flag to prevent bfcache restore
            sessionStorage.setItem('exam_finished_<?= $test['id'] ?>', 'true');

            // Disable button immediately
            btnFinish.disabled = true;
            btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

            // Disable beforeunload warning
            window.onbeforeunload = null;

            // Get CSRF token
            const csrfTokenName = '<?= csrf_token() ?>';
            const csrfHash = document.querySelector(`meta[name='${csrfTokenName}']`)?.getAttribute('content') || '<?= csrf_hash() ?>';
            const csrfData = new URLSearchParams({ [csrfTokenName]: csrfHash }).toString();

            // AJAX submit first, then redirect (faster - scoring runs on server)
            return fetch('<?= site_url('siswa/cbt/submit/' . $test['id']) ?>', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: csrfData
            })
              .then(r => r.json())
              .then(res => {
                if (res.redirect) return res.redirect;
                // Even if there's an error, redirect to selesai as fallback
                return '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>';
              })
              .catch(() => {
                // Network error - redirect to selesai as fallback
                return '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>';
              });
          }
        }).then(result => {
          if (result.isConfirmed && result.value) {
            window.location.href = result.value;
          }
        });
      };
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
      // Don't trigger shortcuts when typing in textarea
      if (document.activeElement.tagName === 'TEXTAREA') return;

      const key = e.key.toUpperCase();

      // A-E or 1-5: Select answer option
      if (['A', 'B', 'C', 'D', 'E', '1', '2', '3', '4', '5'].includes(key)) {
        const target = ['1', '2', '3', '4', '5'].includes(key) ? chr(64 + parseInt(key)) : key;
        const activeQ = items[activeIdx];
        if (activeQ) {
          const row = activeQ.querySelector(`.option-row[data-letter="${target}"]`);
          if (row) {
            const radio = row.querySelector('.option-check');
            if (radio) radio.click();
          }
        }
      }

      // Arrow Left: Previous question
      if (key === 'ARROWLEFT' && btnBack && !btnBack.disabled) {
        e.preventDefault();
        btnBack.click();
      }

      // Arrow Right: Next question
      if (key === 'ARROWRIGHT' && btnNext && !btnNext.classList.contains('d-none')) {
        e.preventDefault();
        btnNext.click();
      }

      // Space: Toggle ragu-ragu
      if (e.code === 'Space' && checkRagu && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        checkRagu.checked = !checkRagu.checked;
        checkRagu.dispatchEvent(new Event('change'));
      }

      // Ctrl+P: Open panel
      if (e.ctrlKey && key === 'P' && btnPanel) {
        e.preventDefault();
        btnPanel.click();
      }

      // Escape: Close modal
      if (key === 'ESCAPE') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
          const modalInstance = bootstrap.Modal.getInstance(openModal);
          if (modalInstance) modalInstance.hide();
        }
      }

      // ? key: Toggle keyboard hints
      if (e.key === '?' && document.activeElement.tagName !== 'TEXTAREA') {
        e.preventDefault();
        if (keyboardHints) keyboardHints.classList.toggle('show');
      }
    });

    // Show keyboard hints on first load (hide after 5 seconds)
    const keyboardHints = document.getElementById('keyboardHints');
    if (keyboardHints) {
      setTimeout(() => {
        keyboardHints.classList.add('show');
        setTimeout(() => {
          keyboardHints.classList.remove('show');
        }, 5000);
      }, 1000);
    }

    function chr(c) { return String.fromCharCode(c); }

    // Restore ragu-ragu dari localStorage
    const doubtfulKey = `cbt_doubtful_${TEST_ID}`;
    const doubtfulList = JSON.parse(localStorage.getItem(doubtfulKey) || '[]');
    items.forEach(item => {
      const qid = item.dataset.qid;
      if (doubtfulList.includes(qid)) {
        item.dataset.doubtful = '1';
      }
    });

    renderActive(activeIdx);
    const infoBtn = document.getElementById('btn_info_soal');
    if (infoBtn) {
      infoBtn.onclick = () => Swal.fire('Informasi', 'Anda sedang mengerjakan ujian <?= esc($test['subject_name']) ?>.', 'info');
    }
  });
</script>
<?= $this->endSection() ?>