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
  
  document.getElementById('btnMulai').addEventListener('click', function (e) {
    e.preventDefault();
    
    // Prevent double-click
    if (isSubmitting) {
      console.log('Already submitting, please wait...');
      return;
    }
    
    isSubmitting = true;
    const btn = this;
    const originalHTML = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...';
    btn.classList.add('disabled');
    
    const targetUrl = btn.href;
    const elem = document.documentElement;

    // Support berbagai browser prefix
    const req = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen || elem.mozRequestFullScreen;

    if (req) {
      req.call(elem).then(() => {
        // Beri jeda sedikit agar transisi fullscreen mulus sebelum pindah halaman
        setTimeout(() => {
          window.location.href = targetUrl;
        }, 300);
      }).catch(err => {
        console.warn('Fullscreen request denied/failed:', err);
        window.location.href = targetUrl;
      });
    } else {
      window.location.href = targetUrl;
    }
  });
</script>
<?= $this->endSection() ?>