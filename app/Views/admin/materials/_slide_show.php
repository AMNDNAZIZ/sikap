<?php
/**
 * Partial: slideshow gambar materi (mobile-first).
 *
 * @var array $slides   Baris subject_material_slides (image_path, caption, sort_order)
 * @var bool  $canComplete  (opsional) true di sisi siswa → picu progres selesai di slide terakhir
 *
 * Fitur: geser (swipe) kiri/kanan, tombol prev/next besar, indikator "n / total",
 * titik navigasi, tap untuk zoom fullscreen, tombol unduh gambar.
 */
$slides      = $slides ?? [];
$canComplete = $canComplete ?? false;
$fid         = 'slideshow_' . bin2hex(random_bytes(3));
?>
<?php if (empty($slides)): ?>
  <p class="text-muted fst-italic">Belum ada slide untuk materi ini.</p>
<?php else: ?>
<div class="slideshow" id="<?= $fid ?>" data-total="<?= count($slides) ?>">
  <!-- Panggung slide -->
  <div class="ss-stage">
    <div class="ss-track">
      <?php foreach ($slides as $i => $sl):
        $url = base_url('uploads/materials/slides/' . rawurlencode($sl['image_path'])); ?>
        <div class="ss-slide" data-index="<?= $i ?>">
          <img src="<?= esc($url) ?>" alt="Slide <?= $i + 1 ?>" loading="lazy" class="ss-img">
          <?php if (!empty($sl['caption'])): ?>
            <div class="ss-caption"><?= esc($sl['caption']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Tombol navigasi -->
    <button type="button" class="ss-nav ss-prev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
    <button type="button" class="ss-nav ss-next" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>
  </div>

  <!-- Bar kontrol bawah -->
  <div class="ss-controls">
    <span class="ss-counter"><span class="ss-cur">1</span> / <?= count($slides) ?></span>
    <div class="ss-dots">
      <?php foreach ($slides as $i => $sl): ?>
        <button type="button" class="ss-dot<?= $i === 0 ? ' active' : '' ?>" data-goto="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <div class="ss-actions">
      <a class="btn btn-sm btn-outline-secondary ss-download" download title="Unduh gambar ini">
        <i class="bi bi-download"></i>
      </a>
      <button type="button" class="btn btn-sm btn-outline-secondary ss-zoom" title="Perbesar (layar penuh)">
        <i class="bi bi-arrows-fullscreen"></i>
      </button>
    </div>
  </div>
</div>

<style>
  .slideshow { max-width: 100%; }
  .slideshow .ss-stage {
    position: relative;
    background: #0b1020;
    border-radius: 10px;
    overflow: hidden;
  }
  .slideshow .ss-track {
    display: flex;
    transition: transform .3s ease;
    touch-action: pan-y pinch-zoom;
  }
  .slideshow .ss-slide {
    min-width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
  .slideshow .ss-img {
    width: 100%;
    max-height: 70vh;
    object-fit: contain;
    display: block;
    user-select: none;
    -webkit-user-drag: none;
  }
  .slideshow .ss-caption {
    width: 100%;
    color: #e9edf5;
    background: rgba(0,0,0,.45);
    font-size: .82rem;
    padding: .4rem .7rem;
    text-align: center;
  }
  .slideshow .ss-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border: 0; border-radius: 50%;
    background: rgba(255,255,255,.85); color: #17324d;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.25);
  }
  .slideshow .ss-nav:disabled { opacity: .35; cursor: default; }
  .slideshow .ss-prev { left: 8px; }
  .slideshow .ss-next { right: 8px; }
  .slideshow .ss-controls {
    display: flex; align-items: center; justify-content: space-between;
    gap: .5rem; padding: .5rem .2rem; flex-wrap: wrap;
  }
  .slideshow .ss-counter { font-size: .8rem; color: #60758a; font-weight: 600; }
  .slideshow .ss-dots { display: flex; gap: 5px; flex-wrap: wrap; justify-content: center; flex: 1; }
  .slideshow .ss-dot {
    width: 9px; height: 9px; border-radius: 50%; border: 0;
    background: #cbd5e1; padding: 0; cursor: pointer;
  }
  .slideshow .ss-dot.active { background: #3b82f6; transform: scale(1.2); }
  .slideshow .ss-actions { display: flex; gap: .35rem; }

  /* Fullscreen */
  .slideshow.ss-fs .ss-stage {
    position: fixed; inset: 0; z-index: 3000; border-radius: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .slideshow.ss-fs .ss-img { max-height: 100vh; }
</style>

<script>
(function(){
  var root = document.getElementById('<?= $fid ?>');
  if (!root) return;
  var track   = root.querySelector('.ss-track');
  var slides  = root.querySelectorAll('.ss-slide');
  var total   = slides.length;
  var cur     = 0;
  var prevBtn = root.querySelector('.ss-prev');
  var nextBtn = root.querySelector('.ss-next');
  var curEl   = root.querySelector('.ss-cur');
  var dots    = root.querySelectorAll('.ss-dot');
  var dlBtn   = root.querySelector('.ss-download');
  var zoomBtn = root.querySelector('.ss-zoom');
  var reachedLast = false;
  var COMPLETE_URL = <?= $canComplete && !empty($subMat['id'])
        ? json_encode(base_url('siswa/belajar/sub/' . $subMat['id'] . '/complete'))
        : 'null' ?>;
  var CSRF_NAME = "<?= csrf_token() ?>";
  var CSRF_HASH = "<?= csrf_hash() ?>";

  function render() {
    track.style.transform = 'translateX(' + (-cur * 100) + '%)';
    curEl.textContent = (cur + 1);
    prevBtn.disabled = (cur === 0);
    nextBtn.disabled = (cur === total - 1);
    dots.forEach(function(d, i){ d.classList.toggle('active', i === cur); });
    // Tautkan tombol unduh ke gambar aktif
    var img = slides[cur].querySelector('.ss-img');
    if (img && dlBtn) dlBtn.href = img.getAttribute('src');
    // Picu progres selesai saat mencapai slide terakhir
    if (cur === total - 1 && !reachedLast && COMPLETE_URL) {
      reachedLast = true;
      markComplete();
    }
  }

  function go(i) {
    cur = Math.max(0, Math.min(total - 1, i));
    render();
  }

  function markComplete() {
    var body = new URLSearchParams();
    body.append(CSRF_NAME, CSRF_HASH);
    fetch(COMPLETE_URL, {
      method: 'POST',
      headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
      body: body.toString()
    }).then(function(r){ return r.json(); }).then(function(d){
      if (d && d.success) {
        // Perbarui UI tombol "Tandai Selesai" bila ada
        var banner = document.getElementById('btnMarkComplete');
        if (banner) {
          var card = banner.closest('.card');
          if (card) card.style.display = 'none';
        }
        var badge = document.getElementById('ssCompleteInfo');
        if (badge) badge.classList.remove('d-none');
      }
    }).catch(function(){});
  }

  prevBtn.addEventListener('click', function(){ go(cur - 1); });
  nextBtn.addEventListener('click', function(){ go(cur + 1); });
  dots.forEach(function(d){ d.addEventListener('click', function(){ go(parseInt(this.dataset.goto, 10)); }); });

  // Keyboard
  root.addEventListener('keydown', function(e){
    if (e.key === 'ArrowLeft') go(cur - 1);
    if (e.key === 'ArrowRight') go(cur + 1);
  });

  // Swipe (touch)
  var startX = 0, startY = 0, swiping = false;
  var stage = root.querySelector('.ss-stage');
  stage.addEventListener('touchstart', function(e){
    startX = e.touches[0].clientX; startY = e.touches[0].clientY; swiping = true;
  }, { passive: true });
  stage.addEventListener('touchend', function(e){
    if (!swiping) return;
    swiping = false;
    var dx = e.changedTouches[0].clientX - startX;
    var dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
      if (dx < 0) go(cur + 1); else go(cur - 1);
    }
  }, { passive: true });

  // Zoom fullscreen (toggle class + fullscreen API bila ada)
  zoomBtn.addEventListener('click', function(){
    var isFs = root.classList.toggle('ss-fs');
    if (isFs && root.requestFullscreen) { root.requestFullscreen().catch(function(){}); }
    else if (!isFs && document.fullscreenElement) { document.exitFullscreen().catch(function(){}); }
  });
  document.addEventListener('fullscreenchange', function(){
    if (!document.fullscreenElement) root.classList.remove('ss-fs');
  });

  render();
})();
</script>
<?php endif; ?>
