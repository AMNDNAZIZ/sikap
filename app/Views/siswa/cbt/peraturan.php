<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<style>
  .info-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    max-width: 900px;
    margin: 0 auto 2rem;
  }
</style>

<div class="info-card">
  <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="mb-3">Peraturan Ujian</h4>

      <p>
        <strong>[<?= esc($test['bank_code']) ?>] <?= esc($test['subject_name']) ?></strong> —
        <?= esc($test['exam_name']) ?>
      </p>
      <ul>
        <li>Durasi: <?= esc($test['duration']) ?> menit</li>
        <li>Berdoalah sebelum memulai</li>
        <li>Dilarang membuka tab lain atau meninggalkan halaman ini.</li>
        <li>Gunakan koneksi internet yang stabil.</li>
        <li>Jawaban disimpan otomatis secara berkala.</li>
        <li>Ingat, Prestasi penting tapi jujurlah yang utama!</li>
      </ul>
      <p>Dengan Klik Mulai Ujian watu Anda akan langsung dihitung</p>

      <div class="text-center mt-4">
        <a href="<?= site_url('siswa/cbt/mulai/' . $test['id']) ?>" class="btn btn-success btn-lg" id="btnMulai">
          <i class="bi bi-play-circle"></i> Mulai Ujian
        </a>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // 🔒 Prevent double-click on start button
  let isSubmitting = false;
  
  // 🔹 FIX: Reset state when page loads (in case of back navigation or reload)
  window.addEventListener('pageshow', function(event) {
    // Reset state if page is loaded from cache (back button)
    if (event.persisted || performance.navigation.type === 2) {
      console.log('[CBT] Page loaded from cache, resetting state...');
      isSubmitting = false;
      const btn = document.getElementById('btnMulai');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle"></i> Mulai Ujian';
        btn.classList.remove('disabled');
      }
    }
  });
  
  document.getElementById('btnMulai').addEventListener('click', function (e) {
    e.preventDefault();
    
    // Prevent double-click
    if (isSubmitting) {
      console.log('[CBT] Already submitting, please wait...');
      return;
    }
    
    isSubmitting = true;
    const btn = this;
    const originalHTML = btn.innerHTML;
    const targetUrl = btn.href;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...';
    btn.classList.add('disabled');
    
    console.log('[CBT] Starting exam, target URL:', targetUrl);
    
    const elem = document.documentElement;

    // Support berbagai browser prefix
    const req = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen || elem.mozRequestFullScreen;

    if (req) {
      console.log('[CBT] Requesting fullscreen...');
      
      req.call(elem).then(function() {
        console.log('[CBT] Fullscreen activated, redirecting in 1 second...');
        // Increase delay to 1000ms untuk browser compatibility
        setTimeout(function() {
          console.log('[CBT] Redirecting to exam page...');
          window.location.href = targetUrl;
        }, 1000);
      }).catch(function(err) {
        console.warn('[CBT] Fullscreen request denied/failed:', err);
        console.log('[CBT] Proceeding without fullscreen...');
        
        // 🔹 FIX: Reset button state if fullscreen fails
        isSubmitting = false;
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        btn.classList.remove('disabled');
        
        // Show error message
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Fullscreen Gagal',
            text: 'Mohon izinkan fullscreen untuk melanjutkan ujian. Coba lagi.',
            icon: 'warning',
            confirmButtonText: 'OK'
          });
        } else {
          alert('Mohon izinkan fullscreen untuk melanjutkan ujian. Coba lagi.');
        }
      });
    } else {
      console.warn('[CBT] Fullscreen API not supported');
      console.log('[CBT] Proceeding without fullscreen...');
      // Proceed without fullscreen
      window.location.href = targetUrl;
    }
  });
</script>
<?= $this->endSection() ?>