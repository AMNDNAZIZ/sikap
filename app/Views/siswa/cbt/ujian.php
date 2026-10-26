<?= $this->extend('layouts/cbt') ?>
<?php 
$hideLogout = true; 
$hideNavbar = true;
$noMainPadding = true;
$fluidNoPadding = true;
?>
<?= $this->section('content') ?>

<?php
$test = $test ?? [];
$questions = $questions ?? [];
$savedAnswers = $savedAnswers ?? [];
$remaining_seconds = (int) ($remaining_seconds ?? 0);
?>

<style>
  :root {
    --exam-font-size: 1.05rem;
    --primary-blue: #0d6efd;
    --primary-blue-hover: #0b5ed7;
    --primary-blue-light: #ebf5ff;
    --danger-red: #dc3545;
    --warning-yellow: #ffc107;
    --warning-yellow-light: #fff9db;
    --success-green: #198754;
    --bg-page: #f8fafc;
    --card-bg: #ffffff;
    --text-primary: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
  }

  body {
    background-color: var(--bg-page);
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--text-primary);
    -webkit-font-smoothing: antialiased;
  }

  /* 🔒 ANTI-CHEAT: Disable text selection to prevent AI search on mobile */
  .stimulus-text,
  .q-text,
  .option-text,
  .stimulus-header-bar,
  .q-header-bar {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-touch-callout: none !important;
  }

  /* Allow selection for input fields only */
  input[type="text"],
  textarea {
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    user-select: text !important;
  }

  /* 🛠️ Responsive and scrollable tables */
  .stimulus-text table,
  .q-text table,
  .option-text table,
  .exam-pc-root table {
    display: block;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .stimulus-text table td, .stimulus-text table th,
  .q-text table td, .q-text table th,
  .option-text table td, .option-text table th {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
  }

  /* 🖼️ Zoomable Exam Images */
  .stimulus-text img,
  .q-text img,
  .option-text img {
    cursor: pointer;
    transition: opacity 0.15s ease;
  }
  .stimulus-text img:hover,
  .q-text img:hover,
  .option-text img:hover {
    opacity: 0.9;
  }

  /* Fullscreen mode support */
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
    padding: 16px 20px 100px 20px;
    overflow-y: visible;
    position: relative;
    z-index: 1;
    overflow-x: hidden;
  }

  /* Ensure exam-pc-root can scroll in fullscreen */
  :fullscreen .exam-pc-root,
  :-webkit-full-screen .exam-pc-root,
  :-moz-full-screen .exam-pc-root,
  :-ms-fullscreen .exam-pc-root {
    overflow-y: visible !important;
    height: auto !important;
  }

  /* =================================================== */
  /* HEADER SECTION (DESKTOP & RESPONSIVE BASE)          */
  /* =================================================== */
  .exam-header {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
    margin-bottom: 16px;
    transition: all 0.2s ease;
  }

  .header-main-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 24px;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .q-number-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.3px;
  }

  .progress-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 700;
  }

  .progress-breadcrumb .current {
    color: var(--primary-blue);
    font-size: 1rem;
  }

  .progress-breadcrumb .separator {
    color: #94a3b8;
  }

  .progress-breadcrumb .total {
    color: #64748b;
  }

  .progress-bar-mini {
    width: 80px;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-left: 6px;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-blue), #00c6ff);
    transition: width 0.3s ease;
    border-radius: 3px;
  }

  .font-zoom-ctrl {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: 20px;
    background: #f8fafc;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
  }

  .zoom-btn {
    cursor: pointer;
    color: #64748b;
    transition: 0.15s;
    user-select: none;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 4px;
  }

  .zoom-btn:hover {
    color: var(--primary-blue);
  }

  .zoom-btn.active {
    color: #fff;
    background: var(--primary-blue);
    font-weight: 800;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .pill-btn {
    border-radius: 50px !important;
    padding: 8px 20px !important;
    font-weight: 600 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none !important;
    transition: all 0.2s ease;
  }

  .btn-blue {
    background-color: var(--primary-blue);
    color: #fff;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
  }

  .btn-blue:hover {
    background-color: var(--primary-blue-hover);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.35);
  }

  .timer-pill {
    background: #fff;
    border: 1.5px solid var(--border-color);
    border-radius: 50px;
    padding: 6px 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
    font-weight: 700;
    font-size: 1.05rem;
    min-width: 130px;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
  }

  .timer-pill.timer-warning {
    background: #fff9db !important;
    border-color: #ffc107 !important;
    color: #b25e00 !important;
  }

  .timer-pill.timer-danger {
    background: #ffe3e3 !important;
    border-color: #dc3545 !important;
    color: #dc3545 !important;
    animation: pulse-danger 1.5s infinite;
  }

  @keyframes pulse-danger {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    50% { transform: scale(1.02); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
  }

  .subject-strip {
    background: #f8fafc;
    padding: 6px 24px;
    border-top: 1px solid #f1f5f9;
    border-radius: 0 0 16px 16px;
    font-size: 0.85rem;
  }

  /* Auto-Save Indicator */
  .save-status {
    position: fixed;
    bottom: 95px;
    right: 30px;
    background: #fff;
    padding: 8px 18px;
    border-radius: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    z-index: 1050;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.25s ease;
    pointer-events: none;
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
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* Two Column Layout for Desktop */
  .exam-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    height: calc(100vh - 240px);
    min-height: 500px;
    position: relative;
    z-index: 1;
  }

  /* Fullscreen height */
  :fullscreen .exam-content-grid,
  :-webkit-full-screen .exam-content-grid,
  :-moz-full-screen .exam-content-grid,
  :-ms-fullscreen .exam-content-grid {
    height: calc(100vh - 190px) !important;
  }

  .stimulus-col,
  .question-col {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    overflow-y: auto;
    overflow-x: hidden;
    position: relative;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    -webkit-overflow-scrolling: touch;
    z-index: 2;
  }

  .stimulus-col::-webkit-scrollbar,
  .question-col::-webkit-scrollbar {
    width: 6px;
  }

  .stimulus-col::-webkit-scrollbar-track,
  .question-col::-webkit-scrollbar-track {
    background: #f8fafc;
  }

  .stimulus-col::-webkit-scrollbar-thumb,
  .question-col::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
  }

  .stimulus-title {
    text-align: center;
    font-weight: 800;
    font-size: 1.25rem;
    margin-bottom: 20px;
    color: #1e293b;
  }

  .stimulus-text,
  .q-text {
    font-size: var(--exam-font-size);
    line-height: 1.7;
    color: #334155;
  }

  /* Images within questions */
  .stimulus-text img,
  .q-text img,
  .option-text img {
    display: block !important;
    margin: 14px auto !important;
    max-width: 100% !important;
    height: auto !important;
    border-radius: 10px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    cursor: zoom-in !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    pointer-events: auto !important;
  }

  .stimulus-text img:hover,
  .q-text img:hover,
  .option-text img:hover {
    transform: scale(1.01) !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
  }

  .stimulus-text p,
  .q-text p,
  .option-text p {
    margin-bottom: 0.85rem !important;
    line-height: 1.7 !important;
  }

  .stimulus-text p:last-child,
  .q-text p:last-child,
  .option-text p:last-child {
    margin-bottom: 0 !important;
  }

  .stimulus-text ul,
  .stimulus-text ol,
  .q-text ul,
  .q-text ol,
  .option-text ul,
  .option-text ol {
    margin-left: 1.4rem !important;
    margin-bottom: 0.85rem !important;
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
    transition: all 0.15s ease;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 12px;
    display: flex;
    align-items: flex-start;
    font-size: var(--exam-font-size);
    background: #fff;
    -webkit-tap-highlight-color: transparent;
  }

  .option-row:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  .option-row:active {
    transform: scale(0.99);
  }

  .option-row.selected {
    background: var(--primary-blue-light);
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 1px var(--primary-blue);
  }

  .option-text {
    flex: 1;
    line-height: 1.6;
    color: #1e293b;
    word-break: break-word;
  }

  .letter-circle {
    width: 36px;
    height: 36px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    margin-right: 14px;
    background: #fff;
    color: #475569;
    transition: all 0.15s ease;
    flex-shrink: 0;
  }

  .option-row.selected .letter-circle {
    background: var(--primary-blue);
    color: #fff;
    border-color: var(--primary-blue);
  }

  .option-check {
    appearance: none;
    -webkit-appearance: none;
    width: 22px;
    height: 22px;
    border: 2px solid #94a3b8;
    border-radius: 6px;
    margin-right: 14px;
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    margin-top: 2px;
    transition: 0.15s;
  }

  .option-check:checked {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
  }

  .option-check:checked::after {
    content: '✔';
    position: absolute;
    color: white;
    font-size: 13px;
    left: 4px;
    top: 0px;
  }

  .q-item[data-type="pg"] .option-check {
    display: none;
  }

  .radio-check {
    border-radius: 50% !important;
  }

  /* Benar-Salah styling */
  .bs-table-wrap {
    overflow-x: auto;
    border-radius: 12px;
    border: 1.5px solid var(--border-color);
  }

  .inp-bs {
    width: 26px;
    height: 26px;
    cursor: pointer;
    accent-color: var(--primary-blue);
  }

  /* Essay styling */
  .inp-essay {
    font-size: 1rem;
    line-height: 1.6;
    border-radius: 12px;
    border: 1.5px solid var(--border-color);
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .inp-essay:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
  }

  /* Footer Section */
  .exam-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 16px 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 40px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
    z-index: 1000;
  }

  .btn-nav {
    font-size: 1rem;
    padding: 10px 24px !important;
  }

  .btn-prev {
    background-color: #64748b;
    color: white;
  }

  .btn-prev:hover {
    background-color: #475569;
    color: white;
  }

  .btn-next {
    background-color: var(--primary-blue);
    color: white;
  }

  .btn-ragu {
    background-color: var(--warning-yellow-light);
    color: #856404;
    border: 1.5px solid var(--warning-yellow) !important;
    font-weight: 700 !important;
    transition: all 0.2s ease;
  }

  .btn-ragu.btn-ragu-active {
    background-color: var(--warning-yellow) !important;
    color: #000 !important;
    border-color: #d39e00 !important;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.4);
  }

  .btn-secondary {
    background-color: #64748b !important;
    color: #fff !important;
  }

  .btn-success {
    background-color: var(--success-green) !important;
    color: #fff !important;
  }

  .btn-panel {
    background-color: #ebf5ff;
    color: var(--primary-blue);
    border: 1.5px solid #bfdbfe !important;
    font-weight: 700 !important;
    transition: all 0.2s ease;
  }

  .btn-panel:hover {
    background-color: var(--primary-blue);
    color: #fff;
    border-color: var(--primary-blue) !important;
  }

  .btn-finish {
    background-color: var(--success-green);
    color: #fff;
    border: 1.5px solid var(--success-green) !important;
    font-weight: 700 !important;
    transition: all 0.2s ease;
  }

  .btn-finish:hover {
    background-color: #157347;
    color: #fff;
  }

  /* Animasi hitung mundur kunci tombol selesai */
  @keyframes lockTicking {
    0% { transform: scale(1); }
    50% { transform: scale(1.18); color: #f59e0b; }
    100% { transform: scale(1); }
  }

  @keyframes pulseGlowLock {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    70% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
  }

  .btn-finish-locked {
    background: #475569 !important;
    color: #f8fafc !important;
    border: 1.5px solid #64748b !important;
    cursor: not-allowed !important;
    animation: pulseGlowLock 2s infinite !important;
    transition: all 0.3s ease;
  }

  .btn-finish-locked .lock-icon-anim {
    display: inline-block;
    animation: lockTicking 1s infinite ease-in-out;
  }

  .countdown-badge-anim {
    background: rgba(0, 0, 0, 0.35);
    color: #fef08a;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
  }

  /* Modal & Panels */
  .panel-q-btn {
    min-height: 48px;
    width: 100%;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }

  .panel-q-btn:active {
    transform: scale(0.95);
  }

  .panel-q-btn.current-q-btn {
    box-shadow: 0 0 0 3px #0d6efd, 0 4px 12px rgba(13, 110, 253, 0.4) !important;
    border-color: #0d6efd !important;
    font-weight: 800 !important;
    transform: scale(1.05);
    z-index: 5;
  }

  .swal2-container {
    z-index: 20000 !important;
  }

  /* Fullscreen Overlay Styling */
  #fsOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.95);
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #fsOverlay.requesting-permission {
    z-index: -1 !important;
    pointer-events: none !important;
  }

  #fsOverlay[style*='display: none'],
  #fsOverlay[style*='display:none'] {
    pointer-events: none !important;
    touch-action: none !important;
    z-index: -1 !important;
  }

  /* =================================================== */
  /* 📱 MOBILE / HP OPTIMIZATIONS (< 992px & < 576px)    */
  /* =================================================== */
  @media (max-width: 991.98px) {
    .exam-pc-root {
      padding: 0 10px calc(85px + env(safe-area-inset-bottom)) 10px;
    }

    /* 📌 STICKY HEADER FOR SMARTPHONE */
    .exam-header {
      position: sticky;
      top: 0;
      z-index: 1020;
      margin: 0 -10px 14px -10px;
      border-radius: 0 0 18px 18px;
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
      border: none;
      border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .header-main-row {
      padding: 10px 14px;
      gap: 8px;
    }

    .header-left {
      gap: 8px;
    }

    .q-number-title {
      font-size: 1.05rem;
      font-weight: 800;
      white-space: nowrap;
    }

    .progress-breadcrumb {
      padding: 4px 10px;
      font-size: 0.8rem;
    }

    .progress-bar-mini {
      display: none;
    }

    .font-zoom-ctrl {
      display: none;
    }

    .header-actions {
      gap: 6px;
    }

    .timer-pill {
      min-width: 95px;
      font-size: 0.95rem;
      padding: 5px 10px;
      gap: 4px;
    }

    .timer-pill span.timer-label {
      display: none;
    }

    .btn-info-soal-btn {
      display: none;
    }

    .btn-panel-header {
      padding: 6px 12px !important;
      font-size: 0.85rem !important;
    }

    .subject-strip {
      padding: 5px 14px;
      font-size: 0.78rem;
    }

    /* Content Stack */
    .exam-content-grid {
      display: flex;
      flex-direction: column;
      height: auto;
      min-height: unset;
      gap: 14px;
      margin-bottom: 16px;
    }

    .stimulus-col,
    .question-col {
      height: auto;
      max-height: none;
      overflow-y: visible;
      padding: 16px;
      border-radius: 14px;
    }

    /* Bottom Navigation Bar for Mobile */
    .exam-footer {
      padding: 8px 10px;
      padding-bottom: max(10px, env(safe-area-inset-bottom));
      gap: 8px;
      justify-content: space-between;
      box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.07);
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    .btn-nav,
    .btn-ragu {
      flex: 1 1 0;
      min-width: 0;
      height: 46px;
      padding: 0 4px !important;
      font-size: 0.9rem !important;
      border-radius: 50px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      white-space: nowrap;
    }

    /* Pastikan tombol yang disembunyikan benar-benar tersembunyi dan tidak tampil */
    .exam-footer .btn-nav.d-none,
    .exam-footer .btn-finish.d-none,
    .exam-footer .d-none {
      display: none !important;
    }

    /* 📱 KHUSUS TAMPILAN HP: Hilangkan tulisan di navigasi soal bawah */
    .exam-footer .btn-nav-text,
    .exam-footer .btn-nav-label-long,
    .exam-footer .btn-nav-label-short,
    .exam-footer .finish-btn-label {
      display: none !important;
    }

    .exam-footer i {
      font-size: 1.25rem !important;
      margin: 0 !important;
    }

    /* Checkbox kuning tombol ragu-ragu */
    .exam-footer .btn-ragu {
      background-color: #fef3c7 !important;
      border: 1.5px solid #f59e0b !important;
      color: #92400e !important;
    }
    .exam-footer .btn-ragu input {
      margin-right: 4px !important;
      transform: scale(1.2);
      accent-color: #f59e0b;
      cursor: pointer;
    }
    .exam-footer .btn-ragu i {
      font-size: 1.1rem !important;
      color: #d97706;
    }

    /* Hitung mundur pada tombol selesai terkunci tetap tampil di HP di dalam tombol */
    .btn-finish-locked .countdown-badge-anim {
      font-size: 0.84rem !important;
      padding: 2px 6px !important;
      margin-left: 3px !important;
      background: rgba(0, 0, 0, 0.2);
      border-radius: 6px;
      color: #fff !important;
      display: inline-block !important;
    }

    /* Teks Selesai ringkas di HP */
    .exam-footer .finish-btn-mobile-label {
      font-size: 0.85rem !important;
      margin-left: 4px !important;
      color: #fff !important;
    }

    /* Auto-save floating indicator on Mobile: reposition to top-right below sticky header */
    .save-status {
      top: 65px;
      right: 14px;
      bottom: auto;
      padding: 6px 14px;
      font-size: 0.78rem;
      border-radius: 20px;
    }

    /* Input touch adjustments */
    .inp-essay {
      font-size: 16px !important;
      min-height: 120px;
    }

    .option-row {
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 8px;
      min-height: 48px;
      transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
    }

    .option-row:active {
      transform: scale(0.99);
    }

    .letter-circle {
      width: 32px;
      height: 32px;
      font-size: 0.9rem;
      margin-right: 10px;
      border-radius: 8px;
    }

    /* 5-Column Question Grid on Mobile Phones */
    #panelButtons {
      grid-template-columns: repeat(5, 1fr) !important;
      gap: 8px !important;
    }

    .panel-q-btn {
      height: 44px !important;
      min-height: 44px !important;
      font-size: 0.95rem !important;
      border-radius: 10px !important;
      font-weight: 700 !important;
    }

    #panelModal .modal-dialog {
      margin: 0.5rem;
    }

    #panelModal .modal-content {
      border-radius: 20px !important;
    }
  }

  @media (max-width: 480px) {
    .header-main-row {
      padding: 8px 10px;
    }

    .q-number-title {
      font-size: 0.95rem;
    }

    .timer-pill {
      min-width: 85px;
      font-size: 0.88rem;
      padding: 4px 8px;
    }
  }
</style>

<div id="fsOverlay"
  class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3"
  style="z-index: 9999;">
  <div class="card text-center p-4 p-md-5 shadow-lg border-0" style="max-width: 440px; border-radius: 24px; background: #ffffff;">
    <div class="mb-3">
      <i class="bi bi-shield-lock-fill text-primary" style="font-size: 4rem;"></i>
    </div>
    <h3 class="fw-bold mb-2 fs-4">SakaSalika SafeExam</h3>
    <p class="text-muted mb-4 small">Ujian ini mewajibkan mode <b>Layar Penuh</b> untuk menjaga integritas. Keluar dari layar penuh dianggap pelanggaran.</p>
    <button id="startFS" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow">
      MULAI UJIAN SEKARANG
    </button>
  </div>
</div>

<div class="exam-pc-root" style="opacity: 0;">
  <!-- EXAM HEADER (STICKY ON MOBILE, FLOATING CARD ON PC) -->
  <header class="exam-header" id="examHeader">
    <div class="header-main-row">
      <div class="header-left">
        <h1 class="q-number-title" id="q_display_num">Soal nomor 1</h1>

        <!-- Progress Breadcrumb -->
        <div class="progress-breadcrumb">
          <span class="current" id="current_num">1</span>
          <span class="separator">/</span>
          <span class="total" id="total_num"><?= count($questions) ?></span>
          <div class="progress-bar-mini d-none d-lg-block">
            <div class="progress-fill" id="progress_fill_mini" style="width: 0%"></div>
          </div>
        </div>

        <!-- Font Zoom Controls (Desktop) -->
        <div class="font-zoom-ctrl d-none d-lg-flex">
          <span class="text-muted small me-1">Font:</span>
          <span class="zoom-btn" data-zoom="0.95rem">A</span>
          <span class="zoom-btn active" data-zoom="1.05rem">A</span>
          <span class="zoom-btn" data-zoom="1.2rem">A</span>
        </div>
      </div>

      <div class="header-actions">
        <!-- Font Zoom Cycle (Mobile) -->
        <button type="button" class="btn btn-sm btn-light border rounded-pill d-lg-none py-1 px-2" id="btnFontCycle" title="Ubah Ukuran Tulisan">
          <i class="bi bi-type"></i> <span id="fontCycleLabel" class="small fw-bold">A</span>
        </button>

        <button class="pill-btn btn-blue d-none d-lg-inline-flex btn-info-soal-btn" id="btn_info_soal_desktop">
          <i class="bi bi-info-circle"></i> <span>INFORMASI SOAL</span>
        </button>

        <div class="timer-pill" id="headerTimerPill">
          <i class="bi bi-clock-fill text-primary me-1"></i>
          <span id="exam_timer">00:00:00</span>
        </div>

        <button class="pill-btn btn-blue btn-panel-header" id="navPanel">
          <i class="bi bi-grid-3x3-gap-fill"></i>
          <span class="d-none d-sm-inline">Daftar Soal</span>
          <span class="badge bg-white text-primary rounded-pill ms-1" id="answeredCountBadge">0/<?= count($questions) ?></span>
        </button>
      </div>
    </div>

    <!-- Progress Bar (Full Width under Header for Mobile) -->
    <div class="w-100" style="height: 3px; background: #e2e8f0; overflow: hidden;">
      <div class="progress-fill" id="progress_fill" style="width: 0%; height: 100%;"></div>
    </div>

    <!-- Subject & Info Sub-Bar -->
    <div class="subject-strip d-flex justify-content-between align-items-center">
      <span class="text-truncate fw-semibold text-secondary">
        <i class="bi bi-journal-bookmark me-1 text-primary"></i><?= esc($test['subject_name'] ?? 'Ujian Utama') ?>
      </span>
      <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none small" id="btn_info_soal">
        <i class="bi bi-info-circle"></i> Info Ujian
      </button>
    </div>
  </header>

  <!-- CONTENT GRID -->
  <div id="questions-container">
    <?php
    /**
     * Helper: rewrite URL gambar localhost/relative ke URL production
     * Dipanggil untuk semua konten HTML yang mungkin mengandung <img>
     */
    $baseUrl = base_url();
    $rewriteImageUrls = function(string $html) use ($baseUrl): string {
        if (empty($html) || strpos($html, '<img') === false) {
            return $html;
        }
        // Ganti URL localhost
        $html = preg_replace(
            '#src="https?://localhost(?::\d+)?/(uploads/)#i',
            'src="' . $baseUrl . '$1',
            $html
        );
        $html = preg_replace(
            '#src="https?://127\.0\.0\.1(?::\d+)?/(uploads/)#i',
            'src="' . $baseUrl . '$1',
            $html
        );
        // Ganti URL yang mengandung /public/uploads/
        $html = preg_replace(
            '#src="https?://[^"]+/public/(uploads/)#i',
            'src="' . $baseUrl . '$1',
            $html
        );
        // Ganti relative path uploads/ → absolute URL
        $html = preg_replace(
            '#src="(?!https?://)(uploads/)#i',
            'src="' . $baseUrl . '$1',
            $html
        );
        // FIX MIXED CONTENT: Ganti http:// → https:// untuk domain yang sama
        // Ini menangani gambar yang disimpan saat baseURL masih http://
        $host = parse_url($baseUrl, PHP_URL_HOST);
        if ($host) {
            $html = preg_replace(
                '#src="http://' . preg_quote($host, '#') . '/#i',
                'src="https://' . $host . '/',
                $html
            );
        }
        return $html;
    };

    /**
     * Helper: bersihkan raw_text di server-side (PHP) agar langsung siap tampil,
     * menghilangkan delay JavaScript dan layout shift / loading visual tiba-tiba.
     */
    $cleanRawTextPhp = function(string $html): string {
        if (empty($html)) return '';
        $out = $html;

        // 1. Simpan tag img dengan placeholder
        $imgTags = [];
        $out = preg_replace_callback('/<img[^>]+>/i', function ($m) use (&$imgTags) {
            $imgTags[] = $m[0];
            return '[[IMG_' . (count($imgTags) - 1) . ']]';
        }, $out);

        // 2. Inject newlines di sekitar block tags
        $blockTags = ['p', 'div', 'li', 'h[1-6]', 'blockquote', 'tr'];
        foreach ($blockTags as $tag) {
            $out = preg_replace('/<' . $tag . '[^>]*>/i', "\n$0", $out);
            $out = preg_replace('/<\/' . $tag . '>/i', "$0\n", $out);
        }

        // 3. Ganti <br> dengan newline
        $out = preg_replace('/<br\s*\/?>/i', "\n", $out);

        // 4. Regex opsi & kunci
        $out = preg_replace('/^(?:<[^>]+>)*[\s\xc2\xa0\x{00a0}]*[\(]?[A-Ea-e]\s*(?:[:.)]|\s+-)\s*.*$/imu', '', $out);
        $out = preg_replace('/^(?:<[^>]+>)*[\s\xc2\xa0\x{00a0}]*(?:Kunci|Tipe)\s*[\:\=\-]\s*.*$/imu', '', $out);
        $out = preg_replace('/Tipe\s*:\s*[A-Za-z0-9_]+/i', '', $out);
        $out = preg_replace('/Kunci\s*:\s*[A-Za-z0-9,\s]+/i', '', $out);

        // 5. Hapus span/font
        $out = preg_replace('/<\/?(span|font)[^>]*>/i', '', $out);

        // 6. Hapus tag kosong
        $out = preg_replace('/<(p|div|li)[^>]*>([\s\n\r]|&nbsp;|<br\s*\/?>)*<\/\1>/i', '', $out);
        $out = preg_replace('/\n\s*\n/', "\n", $out);

        // 7. Kembalikan img
        foreach ($imgTags as $imgIdx => $tag) {
            $out = str_replace('[[IMG_' . $imgIdx . ']]', $tag, $out);
        }

        $out = preg_replace('/>\s*\n/', '>', $out);
        $out = preg_replace('/\n\s*</', '<', $out);
        $out = nl2br($out);

        return trim($out);
    };
    ?>
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
            <div class="stimulus-col" id="stimulus_col_<?= $idx ?>">
              <div class="stimulus-header-bar d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small">
                  <i class="bi bi-book-half me-1"></i> Teks Bacaan / Stimulus
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill small py-0 px-2 btn-jump-to-q d-lg-none" data-target="q_col_<?= $idx ?>">
                  <i class="bi bi-arrow-down-short"></i> Ke Soal
                </button>
              </div>

              <?php
              $qTextForRender = $rewriteImageUrls($qText);
              if ($useRawText) {
                $qTextForRender = $cleanRawTextPhp($qTextForRender);
              }
              ?>
              <div class="stimulus-text">
                <?= $qTextForRender ?: '<div class="alert alert-warning">Konten bacaan tidak tersedia.</div>' ?>
              </div>
            </div>

            <!-- RIGHT COLUMN: QUESTION & OPTIONS -->
            <div class="question-col" id="q_col_<?= $idx ?>">
              <div class="q-header-bar d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom d-lg-none">
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded-pill small">
                  <i class="bi bi-ui-checks me-1"></i> Pilihan Jawaban
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill small py-0 px-2 btn-jump-to-stimulus" data-target="stimulus_col_<?= $idx ?>">
                  <i class="bi bi-arrow-up-short"></i> Ke Bacaan
                </button>
              </div>

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
                    $val = $rewriteImageUrls($val);
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
                  <div class="table-responsive rounded-3 border bs-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-3">Pernyataan</th>
                          <th width="70" class="text-center">Benar</th>
                          <th width="70" class="text-center">Salah</th>
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
                            <td class="ps-3 py-2 small fw-medium"><?= $opts[$letter] ?></td>
                            <td class="text-center py-2"><input type="radio" name="bs_<?= $qid ?>_<?= $letter ?>" value="B"
                                class="inp-bs" data-qid="<?= $qid ?>" data-row="<?= $rowIdx ?>" <?= $ansValue === 'B' ? 'checked' : '' ?>></td>
                            <td class="text-center py-2"><input type="radio" name="bs_<?= $qid ?>_<?= $letter ?>" value="S"
                                class="inp-bs" data-qid="<?= $qid ?>" data-row="<?= $rowIdx ?>" <?= $ansValue === 'S' ? 'checked' : '' ?>></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                <?php else: // ESSAY ?>
                  <textarea class="form-control inp-essay p-3" rows="8" data-qid="<?= $qid ?>"
                    placeholder="Ketik jawaban Anda di sini..."><?= esc($saved) ?></textarea>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- EXAM FOOTER -->
  <footer class="exam-footer">
    <button type="button" class="pill-btn btn-nav btn-prev" id="navBack" title="Soal Sebelumnya">
      <i class="bi bi-chevron-left"></i>
      <span class="btn-nav-text ms-1">Sebelumnya</span>
    </button>

    <label class="pill-btn btn-ragu m-0" id="label_ragu" style="cursor:pointer" title="Tandai Ragu-ragu">
      <input type="checkbox" id="check_ragu" class="me-1">
      <i class="bi bi-flag me-1"></i>
      <span class="btn-nav-text">Ragu-ragu</span>
    </label>

    <button type="button" class="pill-btn btn-nav btn-panel" id="navPanelFooter" title="Lihat Seluruh Soal">
      <i class="bi bi-grid-3x3-gap-fill me-1"></i>
      <span class="btn-nav-text">Daftar Soal</span>
    </button>

    <button type="button" class="pill-btn btn-nav btn-next" id="navNext" title="Soal Berikutnya">
      <span class="btn-nav-text me-1">Berikutnya</span>
      <i class="bi bi-chevron-right"></i>
    </button>
    <button type="button" class="pill-btn btn-nav btn-finish d-none" id="navFinish" title="Selesaikan Ujian" style="display: none;">
      <i class="bi bi-check2-all me-1"></i>
      <span class="btn-nav-text finish-btn-label">Selesaikan Ujian</span>
      <span class="finish-btn-mobile-label d-inline d-md-none fw-bold ms-1">Selesai</span>
    </button>
  </footer>

  <!-- Auto-Save Indicator -->
  <div id="saveStatus" class="save-status">
    <i class="bi bi-check-circle-fill"></i>
    <span>Tersimpan</span>
  </div>

  <!-- Keyboard Hints -->
  <div id="keyboardHints" class="keyboard-hints d-none d-lg-block">
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

<!-- PANEL MODAL - ENHANCED FOR MOBILE AND DESKTOP -->
<div class="modal fade" id="panelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content shadow-lg border-0" style="border-radius: 24px;">
      <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block position-relative">
        <h4 class="modal-title fw-bold mx-auto fs-5 mb-1"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Daftar Seluruh Soal</h4>
        <p class="text-muted small mb-2">Pilih nomor soal untuk langsung melompat ke soal tersebut</p>
        <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

        <!-- Status Badges / Legend -->
        <div class="d-flex flex-wrap gap-2 justify-content-center pt-1 pb-2 border-bottom">
          <span class="badge bg-success text-white px-2 py-1 rounded-pill small">
            <i class="bi bi-check-circle-fill me-1"></i> Sudah Dijawab: <b id="summary_answered">0</b>
          </span>
          <span class="badge bg-warning text-dark px-2 py-1 rounded-pill small">
            <i class="bi bi-flag-fill me-1"></i> Ragu-ragu: <b id="summary_doubtful">0</b>
          </span>
          <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill small">
            <i class="bi bi-circle me-1"></i> Belum Dijawab: <b id="summary_unanswered">0</b>
          </span>
          <span class="badge bg-primary text-white px-2 py-1 rounded-pill small">
            <i class="bi bi-geo-alt-fill me-1"></i> Soal Aktif: No. <b id="summary_current_num">1</b>
          </span>
        </div>
      </div>
      <div class="modal-body p-3 p-md-4">
        <div id="panelButtons" class="d-grid gap-2 pt-2 pb-3" style="grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));">
          <!-- Dynamic Buttons -->
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 pb-3 justify-content-center">
        <button type="button" class="btn btn-light rounded-pill px-4 small border" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-1"></i> Tutup Navigasi
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- 🔹 Load MathJax untuk Formula Matematika -->
<script>
  window.MathJax = {
    tex: {
      inlineMath: [['$', '$'], ['\\(', '\\)']],
      displayMath: [['$$', '$$'], ['\\[', '\\]']],
      processEscapes: true,
      processEnvironments: true
    },
    options: {
      skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
    },
    startup: {
      typeset: false, // 🚀 Matikan auto-typeset seluruh halaman agar buka ujian instan
      pageReady: () => {
        return Promise.resolve();
      }
    }
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>

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
        html: 'Sistem mendeteksi ujian ini sudah dibuka di tab lain.<br><br><strong>Hanya diperbolehkan satu tab aktif.</strong>',
        icon: 'warning',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCancelButton: true,
        confirmButtonText: 'Gunakan Tab Ini (Ambil Alih)',
        cancelButtonText: 'Tutup Tab Ini',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#dc3545'
      }).then((result) => {
        if (result.isConfirmed) {
          // 🔄 TAKE OVER: Force this tab to be the active one
          console.log('[TAB] Taking over session...');
          localStorage.setItem(TAB_KEY, TAB_ID);
          window.location.reload();
        } else {
          // Close or redirect
          window.close();
          setTimeout(() => {
            window.location.href = '<?= site_url('siswa/cbt') ?>';
          }, 500);
        }
      });
      return; // Stop execution of the rest of the script
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
      if (currentTab && currentTab !== TAB_ID) {
        // Another tab is active
        window.location.href = '<?= site_url('siswa/cbt') ?>';
      } else if (!currentTab) {
        // Tab key was cleared or dropped (e.g. mobile app backgrounding), re-claim
        localStorage.setItem(TAB_KEY, TAB_ID);
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
    const flushOfflineQueue = (retryCount) => {
      if (typeof retryCount === 'undefined') retryCount = 0;
      
      const queue = JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');

      if (queue.length === 0) return;

      const MAX_RETRIES = 3;
      const RETRY_DELAY = 5000; // 5 seconds

      console.log('[OFFLINE] Flushing queue:', queue.length, 'answers, retry:', retryCount);
      showSaveStatus('saving', 'Menyinkronkan...');

      // Send all queued answers
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout

      fetch('<?= site_url('siswa/cbt/saveAnswersBulk') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal,
        body: JSON.stringify({
          test_id: TEST_ID,
          answers: queue
        })
      })
        .then(function(r) {
          clearTimeout(timeoutId);
          if (!r.ok) {
            throw new Error('HTTP ' + r.status);
          }
          return r.json();
        })
        .then(function(res) {
          if (res.status === 'ok') {
            // Clear queue on success
            localStorage.removeItem(OFFLINE_QUEUE_KEY);
            showSaveStatus('saved', 'Tersinkronkan (' + queue.length + ')');
            console.log('[OFFLINE] Queue flushed successfully');
          } else {
            throw new Error(res.message || 'Sync failed');
          }
        })
        .catch(function(err) {
          console.error('[OFFLINE] Flush failed:', err);

          // Retry with exponential backoff
          if (retryCount < MAX_RETRIES) {
            var delay = RETRY_DELAY * Math.pow(2, retryCount);
            console.log('[OFFLINE] Retrying in', delay, 'ms');
            showSaveStatus('error', 'Gagal sinkronisasi, mencoba lagi...');

            setTimeout(function() {
              flushOfflineQueue(retryCount + 1);
            }, delay);
          } else {
            showSaveStatus('error', 'Gagal sinkronisasi setelah ' + MAX_RETRIES + ' percobaan');

            // Show manual retry option
            Swal.fire({
              title: 'Sinkronisasi Gagal',
              text: 'Jawaban offline gagal disinkronkan. Coba lagi?',
              icon: 'error',
              showCancelButton: true,
              confirmButtonText: 'Coba Lagi',
              cancelButtonText: 'Nanti'
            }).then(function(result) {
              if (result.isConfirmed) {
                flushOfflineQueue(0); // Reset retry count
              }
            });
          }
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

    // ============================================
    // SESSION KEEP-ALIVE
    // ============================================
    const SESSION_REFRESH_INTERVAL = 1800000; // 30 minutes in milliseconds
    let keepAliveInterval = null;

    /**
     * Send keep-alive ping to server to prevent session expiration
     */
    const sendKeepAlive = () => {
      // Don't send if exam is finished or time expired
      if (examFinished || timeExpiredHandled) {
        console.log('[SESSION] Keep-alive skipped - exam finished');
        return;
      }

      // Don't send if offline
      if (!isOnline) {
        console.log('[SESSION] Keep-alive skipped - offline');
        return;
      }

      console.log('[SESSION] Sending keep-alive ping...');

      fetch('<?= site_url('siswa/cbt/keepAlive') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          test_id: TEST_ID
        })
      })
        .then(function(r) {
          if (!r.ok) {
            throw new Error('HTTP ' + r.status);
          }
          return r.json();
        })
        .then(function(res) {
          if (res.status === 'ok') {
            console.log('[SESSION] Keep-alive successful at', new Date().toLocaleTimeString());

            // Update CSRF token if provided
            if (res.csrf_token) {
              // Update all CSRF token inputs
              var csrfInputs = document.querySelectorAll('input[name="<?= csrf_token() ?>"]');
              for (var i = 0; i < csrfInputs.length; i++) {
                csrfInputs[i].value = res.csrf_token;
              }
              console.log('[SESSION] CSRF token refreshed');
            }

            // Show subtle notification (optional)
            if (typeof showSaveStatus === 'function') {
              showSaveStatus('saved', 'Sesi diperpanjang');
            }
          } else {
            console.warn('[SESSION] Keep-alive failed:', res.message);

            // If session invalid, show warning
            if (res.status === 'error' && res.message.indexOf('Session') !== -1) {
              Swal.fire({
                title: 'Peringatan Sesi',
                text: 'Sesi ujian Anda mungkin bermasalah. Halaman akan dimuat ulang.',
                icon: 'warning',
                confirmButtonText: 'Muat Ulang'
              }).then(function() {
                window.location.reload();
              });
            }
          }
        })
        .catch(function(err) {
          console.error('[SESSION] Keep-alive error:', err);
          // Don't show error to user - will retry in 30 minutes
        });
    };

    /**
     * Start keep-alive interval
     */
    const startKeepAlive = () => {
      // Send first keep-alive after 5 minutes (to establish baseline)
      setTimeout(function() {
        sendKeepAlive();
      }, 300000); // 5 minutes

      // Then send every 30 minutes
      keepAliveInterval = setInterval(function() {
        sendKeepAlive();
      }, SESSION_REFRESH_INTERVAL);

      console.log('[SESSION] Keep-alive started - will ping every 30 minutes');
    };

    /**
     * Stop keep-alive interval
     */
    const stopKeepAlive = () => {
      if (keepAliveInterval) {
        clearInterval(keepAliveInterval);
        keepAliveInterval = null;
        console.log('[SESSION] Keep-alive stopped');
      }
    };

    // Start keep-alive when page loads
    startKeepAlive();

    // Stop keep-alive when exam finishes
    window.addEventListener('beforeunload', function() {
      stopKeepAlive();
    });

    console.log('[SESSION] Keep-alive system initialized');

    // ============================================
    // END SESSION KEEP-ALIVE
    // ============================================

    // Try to lock orientation again after DOM loaded
    if (screen.orientation && screen.orientation.lock) {
      screen.orientation.lock('portrait').catch(err => {
        console.log('DOM orientation lock failed (expected if not fullscreen):', err.message);
      });
    }

    // Image Zoom Modal Handler (Event Delegation tanpa klon DOM)
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

    // Event delegation efisien: tangani klik zoom gambar tanpa merusak DOM
    const qContainer = document.getElementById('questions-container');
    if (qContainer) {
      qContainer.addEventListener('click', function (e) {
        const img = e.target.closest('.stimulus-text img, .q-text img, .option-text img');
        if (img) {
          e.preventDefault();
          e.stopPropagation();
          if (zoomedImage) {
            zoomedImage.src = img.src;
            zoomedImage.alt = img.alt || 'Gambar Soal';
          }
          imageZoomModal.show();
        }
      });
    }

    const btnBack = document.getElementById('navBack');
    const btnNext = document.getElementById('navNext');
    const btnFinish = document.getElementById('navFinish');
    const btnPanel = document.getElementById('navPanel');
    const btnPanelFooter = document.getElementById('navPanelFooter');
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
        console.log('[BUTTON] Normal flow, proceeding to fullscreenRequest');
        proceedToFullscreen();
      };

      /**
       * Proceed to fullscreen (called after all checks)
       */
      function proceedToFullscreen() {
        // 1. Detect device/browser
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        console.log('[FS] Browser detection:', { isSafari, isIOS });

        // 2. iOS Safari Workaround
        if (isIOS) {
          console.log('[FS] iOS detected - using alternative mode');
          
          // Hide address bar on iOS
          setTimeout(() => {
            window.scrollTo(0, 1);
          }, 100);

          // Prevent pull-to-refresh but allow content scroll
          document.body.style.overscrollBehavior = 'none';

          // Show exam root
          if (fsOverlay) fsOverlay.style.setProperty('display', 'none', 'important');
          if (root) root.style.opacity = '1';
          renderActive(activeIdx);

          Swal.fire({
            title: 'Mode Ujian Aktif',
            html: 'Bekerja dalam mode optimal untuk iOS.<br><small class="text-muted">Jangan keluar dari tab ini selama ujian.</small>',
            icon: 'info',
            timer: 3000,
            showConfirmButton: false
          });
          return;
        }

        // 3. Desktop/Android: Browser Compatibility Check
        const browserCheck = isBrowserSupported();
        if (!browserCheck.supported) {
          Swal.fire({
            title: 'Browser Tidak Didukung',
            html: `<div class="text-start"><p>${browserCheck.reason}</p><p class="text-danger fw-bold">Ujian mungkin tidak berjalan stabil.</p><p>${browserCheck.recommendation}</p></div>`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Lanjutkan (Resiko Sendiri)',
            cancelButtonText: 'Kembali',
            confirmButtonColor: '#dc3545'
          }).then((result) => {
            if (result.isConfirmed) {
              console.warn('[FS] User proceeding with unsupported browser');
              executeFullscreenRequest();
            } else {
              window.location.href = '<?= site_url('siswa/cbt') ?>';
            }
          });
          return;
        }

        if (browserCheck.warning) {
          console.warn('[FS] Browser warning:', browserCheck.reason);
        }

        // 4. Proceed to actual request
        executeFullscreenRequest();
      }

      /**
       * Execute the actual fullscreen request with UI adjustments
       */
      function executeFullscreenRequest() {
        // IMPORTANT: Lower overlay z-index BEFORE requesting fullscreen
        // This prevents overlay from blocking browser permission dialog
        if (fsOverlay) {
          fsOverlay.style.zIndex = '-1';
          fsOverlay.style.pointerEvents = 'none';
        }

        // Add small delay to ensure z-index applied before API call
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

    const ping = (event = null, retry = 0) => {
      const data = { test_id: TEST_ID };
      if (event) data.event = event;

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

      return fetch('<?= site_url('siswa/cbt/ping') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        signal: controller.signal,
        body: JSON.stringify(data)
      }).then(r => {
        clearTimeout(timeoutId);
        if (!r.ok && r.status >= 500 && retry < 2) {
            throw new Error('Server Busy');
        }
        return r.json();
      }).then(res => {
        // Sync local violation count and limit with server
        if (res.violation_count !== undefined) violationCount = res.violation_count;
        if (res.violation_limit !== undefined) violationLimit = res.violation_limit;

        if (res.status === 'finished' || res.is_locked) {
          handleLockdown('Sesi Berakhir', res.message || 'Ujian telah dihentikan oleh sistem.', true);
        }
        return res;
      }).catch(err => {
        console.warn("Ping failed", err);
        if (retry < 2) {
            console.log("Retrying ping...", retry + 1);
            return new Promise(resolve => setTimeout(() => resolve(ping(event, retry + 1)), 2000));
        }
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

      if (isFullscreen) {
        // Layar penuh aktif: pastikan konten ujian terlihat normal dan overlay tersembunyi
        if (fsOverlay) fsOverlay.style.setProperty('display', 'none', 'important');
        if (root) root.style.opacity = '1';
        return;
      }

      // 🔹 Proteksi HP / Virtual Keyboard:
      // Pada HP/tablet, memencet input/textarea membuka soft-keyboard
      // yang dapat memicu event keluar fullscreen sementara. Jangan tampilkan overlay jika user sedang mengetik!
      const activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA')) {
        console.log('Fullscreen exit ignored: soft keyboard is open');
        return;
      }

      // Berikan debounce 400ms untuk menghindari false-positive saat rotasi atau scroll layar HP
      setTimeout(() => {
        if (examFinished) return;
        const stillFullscreen = document.fullscreenElement ||
          document.webkitFullscreenElement ||
          document.mozFullScreenElement ||
          document.msFullscreenElement;

        const currentActive = document.activeElement;
        if (currentActive && (currentActive.tagName === 'INPUT' || currentActive.tagName === 'TEXTAREA')) {
          return;
        }

        if (!stillFullscreen) {
          console.log('Exited fullscreen - showing overlay');
          if (fsOverlay) {
            fsOverlay.style.setProperty('display', 'flex', 'important');
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
      }, 400);
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
        if (screen.orientation.type && screen.orientation.type.includes('landscape')) {
          if (screen.orientation.lock && (document.fullscreenElement || document.webkitFullscreenElement)) {
            screen.orientation.lock('portrait').catch(() => {});
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

    // Update Ragu-ragu visual on button
    const updateRaguVisual = () => {
      const isChecked = checkRagu ? checkRagu.checked : false;
      const labelRagu = document.getElementById('label_ragu');
      if (labelRagu) {
        labelRagu.classList.toggle('btn-ragu-active', isChecked);
        const icon = labelRagu.querySelector('i');
        if (icon) {
          icon.className = isChecked ? 'bi bi-flag-fill me-1' : 'bi bi-flag me-1';
        }
      }
    };

    // Update Mobile Header info
    const updateMobileHeader = (idx) => {
      const activeQ = items[idx];
      if (!activeQ) return;
      const type = activeQ.dataset.type || 'pg';
      const typeMap = {
        'pg': 'Pilihan Ganda',
        'pg_kompleks': 'PG Kompleks',
        'pgk': 'PG Kompleks',
        'benar_salah': 'Benar/Salah',
        'esai': 'Esai',
        'essay': 'Esai'
      };
      const badgeType = document.getElementById('badge_q_type');
      if (badgeType) badgeType.textContent = typeMap[type] || 'Soal';
    };

    // Update Answer Stats Counter
    const updateAnswerStats = () => {
      let answeredCount = 0;
      let doubtfulCount = 0;
      items.forEach(item => {
        const type = item.dataset.type;
        const isDoubtful = (item.dataset.doubtful == '1');
        let answered = false;
        if (type === 'pg' || type === 'pg_kompleks' || type === 'pgk') {
          answered = !!item.querySelector('.inp-target:checked');
        } else if (type === 'benar_salah') {
          const bsRows = item.querySelectorAll('.inp-bs[data-row]');
          const totalRows = new Set();
          bsRows.forEach(inp => totalRows.add(inp.dataset.row));
          const rowCount = totalRows.size;
          let answeredRows = 0;
          for (let i = 0; i < rowCount; i++) {
            if (item.querySelector(`.inp-bs[data-row="${i}"]:checked`)) answeredRows++;
          }
          answered = (answeredRows === rowCount && rowCount > 0);
        } else {
          const essay = item.querySelector('.inp-essay');
          answered = (essay && essay.value.trim().length > 0);
        }
        if (answered) answeredCount++;
        if (isDoubtful) doubtfulCount++;
      });

      const badge = document.getElementById('answeredCountBadge');
      if (badge) badge.textContent = `${answeredCount}/${items.length}`;
      const sumAns = document.getElementById('summary_answered');
      if (sumAns) sumAns.textContent = answeredCount;
      const sumDoubt = document.getElementById('summary_doubtful');
      if (sumDoubt) sumDoubt.textContent = doubtfulCount;
      const sumUnans = document.getElementById('summary_unanswered');
      if (sumUnans) sumUnans.textContent = items.length - answeredCount;
      const sumCurr = document.getElementById('summary_current_num');
      if (sumCurr) sumCurr.textContent = activeIdx + 1;
    };

    // Finish Button State & Animated Countdown Logic
    const updateFinishButtonState = () => {
      if (!btnFinish) return;

      if (LOCK_DURATION > 0 && elapsedSeconds < LOCK_DURATION) {
        const waitTime = LOCK_DURATION - elapsedSeconds;
        const wm = Math.floor(waitTime / 60);
        const ws = waitTime % 60;
        const waitFormatted = (wm < 10 ? '0' + wm : wm) + ':' + (ws < 10 ? '0' + ws : ws);

        btnFinish.disabled = true;
        btnFinish.classList.remove('btn-next', 'btn-success');
        btnFinish.classList.add('btn-secondary', 'btn-finish-locked');
        btnFinish.innerHTML = `<i class="bi bi-lock-fill lock-icon-anim me-1"></i><span class="btn-nav-text finish-btn-label me-1">Selesai dalam</span><span class="countdown-badge-anim font-monospace fw-bold">${waitFormatted}</span>`;
        btnFinish.title = `Selesaikan Ujian (Terkunci selama ${waitFormatted} lagi)`;
      } else {
        btnFinish.disabled = false;
        btnFinish.classList.remove('btn-secondary', 'btn-finish-locked', 'btn-next');
        btnFinish.classList.add('btn-success');
        btnFinish.innerHTML = `<i class="bi bi-check2-all me-1"></i><span class="btn-nav-text finish-btn-label">Selesaikan Ujian</span><span class="finish-btn-mobile-label d-inline d-md-none fw-bold ms-1">Selesai</span>`;
        btnFinish.title = 'Selesaikan Ujian';
      }
    };

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
      
      // Slot ke-4: Tombol Berikutnya BERUBAH menjadi Tombol Selesai di nomor terakhir
      if (btnNext) {
        if (isEnd) {
          btnNext.classList.add('d-none');
          btnNext.style.setProperty('display', 'none', 'important');
        } else {
          btnNext.classList.remove('d-none');
          btnNext.style.removeProperty('display');
        }
      }
      if (btnFinish) {
        if (isEnd) {
          btnFinish.classList.remove('d-none');
          btnFinish.style.removeProperty('display');
          updateFinishButtonState();
        } else {
          btnFinish.classList.add('d-none');
          btnFinish.style.setProperty('display', 'none', 'important');
        }
      }

      // Sync Check Ragu-ragu
      const currentItem = items[idx];
      if (currentItem && checkRagu) {
        checkRagu.checked = (currentItem.dataset.doubtful == '1');
      }
      updateRaguVisual();
      updateMobileHeader(idx);
      updateAnswerStats();

      window.scrollTo(0, 0);

      // Lazy typesetting MathJax HANYA pada soal aktif jika mengandung LaTeX
      if (currentItem && !currentItem.dataset.mathTypeset) {
        const text = currentItem.textContent || '';
        if (text.includes('$') || text.includes('\\(') || text.includes('\\[') || text.includes('\\begin')) {
          if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {
            MathJax.typesetPromise([currentItem]).then(() => {
              currentItem.dataset.mathTypeset = '1';
            }).catch(() => {});
          } else if (typeof MathJax !== 'undefined' && MathJax.startup && MathJax.startup.promise) {
            MathJax.startup.promise.then(() => {
              if (typeof MathJax.typesetPromise === 'function') {
                MathJax.typesetPromise([currentItem]).then(() => {
                  currentItem.dataset.mathTypeset = '1';
                }).catch(() => {});
              }
            }).catch(() => {});
          }
        } else {
          currentItem.dataset.mathTypeset = '1';
        }
      }
    };

    // Update Progress Breadcrumb
    const updateProgressBreadcrumb = () => {
      const currentNum = document.getElementById('current_num');
      const totalNum = document.getElementById('total_num');
      const progressFill = document.getElementById('progress_fill');
      const progressFillMini = document.getElementById('progress_fill_mini');

      if (currentNum) currentNum.textContent = activeIdx + 1;
      if (totalNum) totalNum.textContent = items.length;
      const percentage = ((activeIdx + 1) / items.length) * 100;
      if (progressFill) progressFill.style.width = percentage + '%';
      if (progressFillMini) progressFillMini.style.width = percentage + '%';
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

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 20000); // 20s timeout for answer save

      fetch('<?= site_url('siswa/cbt/saveAnswer') ?>', {
        method: 'POST',
        body: fd,
        signal: controller.signal,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(response => {
          clearTimeout(timeoutId);
          // Check HTTP status first
          if (!response.ok) {
            console.error('[SAVE] HTTP Error:', response.status, response.statusText);

            // Session expired or unauthorized
            if (response.status === 403 || response.status === 401) {
              Swal.fire({
                title: 'Sesi Berakhir',
                text: 'Sesi ujian Anda telah berakhir atau akun Anda digunakan di perangkat lain. Jawaban Anda saat ini telah disimpan secara offline.',
                icon: 'warning',
                allowOutsideClick: false,
                confirmButtonText: 'Login Ulang'
              }).then(() => {
                window.location.reload();
              });
              throw new Error('Session expired');
            }
            
            // For other errors (500, 504, etc.), treat as network failure and save offline
            throw new Error('Server error: ' + response.status);
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
        updateRaguVisual();
        updateAnswerStats();

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
        updateAnswerStats();
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
        updateAnswerStats();
      };
    });

    // Essay
    document.querySelectorAll('.inp-essay').forEach(inp => {
      inp.onblur = (e) => {
        const qid = e.target.dataset.qid;

        // Kirim jawaban saja, jangan ubah status ragu-ragu
        commitAnswer(qid, e.target.value);
        updateAnswerStats();
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

          const headerTimerPill = document.getElementById('headerTimerPill');
          if (headerTimerPill) {
            if (timeRaw <= 60) {
              headerTimerPill.classList.remove('timer-warning');
              headerTimerPill.classList.add('timer-danger');
            } else if (timeRaw <= 300) {
              headerTimerPill.classList.add('timer-warning');
            } else {
              headerTimerPill.classList.remove('timer-warning', 'timer-danger');
            }
          }

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

      // Update finish button countdown & state
      updateFinishButtonState();

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
      updateAnswerStats();

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

        const btn = document.createElement('button');
        btn.type = 'button';
        let bgColor = answered ? 'btn-success text-white' : 'btn-outline-secondary';
        if (isDoubtful) bgColor = 'btn-warning text-dark';

        btn.className = `btn ${bgColor} panel-q-btn fw-bold ${i === activeIdx ? 'current-q-btn' : ''}`;
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

    const openPanelModal = () => {
      renderPanel();
      modalPanel.show();
    };

    if (btnPanel) {
      btnPanel.onclick = openPanelModal;
    }
    if (btnPanelFooter) {
      btnPanelFooter.onclick = openPanelModal;
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
          position: 'center', // 🔹 FIX: Explicitly set position to center
          heightAuto: false,  // 🔹 FIX: Prevent layout shift on mobile
          grow: 'column',     // Ensure it doesn't try to grow too wide
          html: `
            <div class="mt-2 mb-3">
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
              .then(async r => {
                const res = await r.json();
                if (!r.ok || res.error) {
                  throw new Error(res.error || 'Ujian belum bisa diselesaikan.');
                }
                if (res.redirect) return res.redirect;
                return '<?= site_url('siswa/cbt/selesai/' . $test['id']) ?>';
              })
              .catch((err) => {
                examFinished = false;
                btnFinish.disabled = false;
                updateFinishButtonState();
                Swal.showValidationMessage(err.message || 'Gagal menyelesaikan ujian.');
                return false;
              });
          }
        }).then(result => {
          if (result && result.isConfirmed && result.value) {
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
      if (e.ctrlKey && key === 'P') {
        e.preventDefault();
        openPanelModal();
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

    // Info buttons handler (mobile & desktop)
    const showExamInfo = () => {
      Swal.fire({
        title: 'Informasi Ujian',
        html: `
          <div class="text-start">
            <p class="mb-2"><strong>Mata Pelajaran:</strong> <?= esc($test['subject_name'] ?? '-') ?></p>
            <p class="mb-2"><strong>Nama Ujian:</strong> <?= esc($test['exam_name'] ?? '-') ?></p>
            <p class="mb-2"><strong>Kode Bank:</strong> <?= esc($test['bank_code'] ?? '-') ?></p>
            <p class="mb-0"><strong>Total Soal:</strong> <?= count($questions) ?> butir</p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#0d6efd'
      });
    };

    const infoBtn = document.getElementById('btn_info_soal');
    if (infoBtn) infoBtn.onclick = showExamInfo;
    const infoBtnDesktop = document.getElementById('btn_info_soal_desktop');
    if (infoBtnDesktop) infoBtnDesktop.onclick = showExamInfo;

    // Mobile Font Zoom Cycle
    const btnFontCycle = document.getElementById('btnFontCycle');
    if (btnFontCycle) {
      const fontSizes = ['0.95rem', '1.05rem', '1.2rem'];
      const fontLabels = ['A-', 'A', 'A+'];
      let currentFontIdx = 1;

      btnFontCycle.onclick = () => {
        currentFontIdx = (currentFontIdx + 1) % fontSizes.length;
        document.documentElement.style.setProperty('--exam-font-size', fontSizes[currentFontIdx]);
        const cycleLabel = document.getElementById('fontCycleLabel');
        if (cycleLabel) cycleLabel.textContent = fontLabels[currentFontIdx];
      };
    }

    // Mobile Jump to Questions / Stimulus
    document.addEventListener('click', function(e) {
      const jumpQ = e.target.closest('.btn-jump-to-q');
      if (jumpQ) {
        const targetId = jumpQ.dataset.target;
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
          const headerHeight = document.getElementById('examHeader') ? document.getElementById('examHeader').offsetHeight : 60;
          const y = targetEl.getBoundingClientRect().top + window.pageYOffset - headerHeight - 10;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      }

      const jumpStimulus = e.target.closest('.btn-jump-to-stimulus');
      if (jumpStimulus) {
        const targetId = jumpStimulus.dataset.target;
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
          const headerHeight = document.getElementById('examHeader') ? document.getElementById('examHeader').offsetHeight : 60;
          const y = targetEl.getBoundingClientRect().top + window.pageYOffset - headerHeight - 10;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      }
    });
  });
</script>
<?= $this->endSection() ?>