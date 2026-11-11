<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<style>
  .rules-container {
    max-width: 680px;
    margin: 1rem auto 2.5rem;
    padding: 0 12px;
  }
  .rules-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
  }
  .rules-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
    color: #ffffff;
    padding: 24px 20px;
    text-align: center;
    position: relative;
  }
  .rules-header .icon-wrap {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 10px;
  }
  .exam-summary-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 20px;
  }
  .rules-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
  }
  .rule-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
  }
  .rule-item:last-child {
    border-bottom: none;
  }
  .rule-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.95rem;
    margin-top: 2px;
  }
  .btn-start-exam-main {
    border-radius: 50px;
    padding: 14px 20px;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 14px rgba(13, 110, 253, 0.35);
    transition: all 0.2s ease;
  }
  .btn-start-exam-main:active {
    transform: scale(0.98);
  }

  @media (max-width: 576px) {
    .rules-container {
      margin-top: 0.5rem;
      padding: 0 8px;
    }
    .rules-header {
      padding: 20px 15px;
    }
    .rules-header h3 {
      font-size: 1.25rem !important;
    }
    .rules-header .icon-wrap {
      width: 50px;
      height: 50px;
      font-size: 1.5rem;
      margin-bottom: 8px;
    }
    .rule-item {
      font-size: 0.9rem;
    }
    .btn-start-exam-main {
      padding: 12px 18px;
      font-size: 1rem;
    }
  }
</style>

<div class="rules-container">
  <div class="rules-card">
    <div class="rules-header">
      <div class="icon-wrap">
        <i class="bi bi-shield-check"></i>
      </div>
      <h3 class="fw-bold mb-1 fs-4">Petunjuk & Peraturan Ujian</h3>
      <p class="mb-0 opacity-90 small">Harap baca dengan cermat sebelum memulai ujian</p>
    </div>

    <div class="p-3 p-md-4">
      <!-- 🚀 In-App Browser Warning (WhatsApp / IG / Line) -->
      <div id="inAppBrowserWarning" class="alert alert-warning d-none mb-3" style="border-radius: 14px; border: 1px solid #f6ad55; background: #fffaf0;">
        <div class="d-flex align-items-start gap-2">
          <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0 mt-1"></i>
          <div>
            <b class="text-dark">Terbuka di Aplikasi Pesan (WhatsApp / Media Sosial)</b>
            <div class="small text-muted mt-1">
              Untuk kelancaran layar penuh dan mencegah halaman tertutup tiba-tiba, disarankan membuka ujian di browser resmi (<b>Google Chrome</b> atau <b>Safari</b>).
            </div>
            <div class="mt-2">
              <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick="copyCurrentExamLink()">
                <i class="bi bi-clipboard me-1"></i> Salin Link Ujian
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="exam-summary-box">
        <div class="fw-bold text-primary mb-1 fs-5"><?= esc($test['subject_name'] ?? 'Mata Pelajaran') ?></div>
        <div class="small text-muted mb-2"><?= esc($test['exam_name'] ?? 'Ujian') ?> &bull; Kode: <?= esc($test['bank_code'] ?? '-') ?></div>
        <div class="d-flex flex-wrap align-items-center gap-2 pt-2 border-top">
          <span class="badge bg-primary text-white rounded-pill px-3 py-2">
            <i class="bi bi-clock me-1"></i> Durasi: <?= esc($test['duration'] ?? 0) ?> menit
          </span>
          <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">
            <i class="bi bi-journal-text me-1"></i> Mode CBT Online
          </span>
        </div>
      </div>

      <div class="rules-list">
        <div class="rule-item">
          <div class="rule-icon"><i class="bi bi-heart-fill text-danger"></i></div>
          <div><b>Berdoa sebelum mulai</b> untuk ketenangan dan kelancaran pengerjaan.</div>
        </div>
        <div class="rule-item">
          <div class="rule-icon"><i class="bi bi-fullscreen text-primary"></i></div>
          <div>Ujian berjalan dalam <b>Layar Penuh (Full Screen)</b>. Jangan berpindah aplikasi, tab, atau membuka notifikasi.</div>
        </div>
        <div class="rule-item">
          <div class="rule-icon"><i class="bi bi-wifi text-success"></i></div>
          <div>Pastikan koneksi internet aktif. Seluruh jawaban <b>disimpan otomatis</b> ke server secara berkala.</div>
        </div>
        <div class="rule-item">
          <div class="rule-icon"><i class="bi bi-hourglass-split text-warning"></i></div>
          <div>Waktu ujian akan langsung berkurang begitu tombol <b>Mulai Ujian</b> ditekan.</div>
        </div>
        <div class="rule-item">
          <div class="rule-icon"><i class="bi bi-patch-check-fill text-info"></i></div>
          <div>Prestasi penting, namun <b>kejujuran adalah yang utama</b>!</div>
        </div>
      </div>

      <div class="pt-2">
        <a href="<?= site_url('siswa/cbt/mulai/' . $test['id']) ?>" class="btn btn-primary btn-lg w-100 btn-start-exam-main" id="btnMulai">
          <i class="bi bi-play-circle-fill me-2"></i> Mulai Ujian Sekarang
        </a>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  let isSubmitting = false;

  // 🚀 Deteksi In-App Browser (WhatsApp, Instagram, FB, Line, dll)
  try {
    const isAppWebView = /WhatsApp|FBAN|FBAV|Instagram|Line|wv/i.test(navigator.userAgent);
    if (isAppWebView) {
      const banner = document.getElementById('inAppBrowserWarning');
      if (banner) banner.classList.remove('d-none');
    }
  } catch (e) {}

  function copyCurrentExamLink() {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link ujian berhasil disalin! Silakan buka Google Chrome atau Safari dan tempel link tersebut.');
      }).catch(() => {
        prompt('Silakan salin link berikut:', window.location.href);
      });
    } else {
      prompt('Silakan salin link berikut:', window.location.href);
    }
  }

  // Reset state if returned via back/forward cache
  window.addEventListener('pageshow', function (event) {
    if (event.persisted || performance.navigation.type === 2) {
      isSubmitting = false;
      const btn = document.getElementById('btnMulai');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> Mulai Ujian Sekarang';
        btn.classList.remove('disabled');
      }
    }
  });

  document.getElementById('btnMulai').addEventListener('click', function (e) {
    e.preventDefault();
    if (isSubmitting) return;

    isSubmitting = true;
    const btn = this;
    const targetUrl = btn.href;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyiapkan Ujian...';
    btn.classList.add('disabled');

    // 🚀 SHARED HOSTING OPTIMIZATION: Smooth jitter (150ms - 800ms) to prevent EP 50 overflow spike
    const jitter = Math.floor(Math.random() * 650) + 150;

    // Detect iOS (iPhone / iPad / iPod) - Fullscreen API is unsupported on mobile Safari
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIOS) {
      setTimeout(function () {
        window.location.href = targetUrl;
      }, jitter);
      return;
    }

    const elem = document.documentElement;
    const req = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen || elem.mozRequestFullScreen;

    if (req) {
      req.call(elem).then(function () {
        setTimeout(function () {
          window.location.href = targetUrl;
        }, 400 + jitter);
      }).catch(function (err) {
        console.warn('[CBT] Fullscreen permission skipped:', err);
        setTimeout(function () {
          window.location.href = targetUrl;
        }, jitter);
      });
    } else {
      setTimeout(function () {
        window.location.href = targetUrl;
      }, jitter);
    }
  });
</script>
<?= $this->endSection() ?>