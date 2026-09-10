<?php
/**
 * Partial: render HTML materi interaktif di dalam iframe sandbox.
 *
 * Keamanan:
 *  - sandbox TANPA 'allow-same-origin' → iframe punya origin unik, script di
 *    dalamnya TIDAK bisa mengakses cookie/localStorage/DOM aplikasi utama.
 *    Ini mencegah XSS ke sesi pengguna meski HTML dibuat guru.
 *  - allow-scripts: JS materi tetap berjalan (interaktif).
 *  - allow-popups / allow-popups-to-escape-sandbox: izinkan buka tab baru.
 *  - allow-forms / allow-modals: form & dialog di dalam materi.
 *
 * Auto-resize: skrip kecil disuntikkan ke dalam konten untuk mengirim tinggi
 * dokumen ke parent via postMessage; parent menyesuaikan tinggi iframe.
 *
 * @var string $html  HTML mentah materi (dari kolom content).
 */

$__frameId = 'htmlFrame_' . bin2hex(random_bytes(4));

// Skrip auto-resize yang disuntik ke dalam iframe (berjalan di origin sandbox).
$__resizeScript = <<<JS
<script>
(function(){
  function sendHeight(){
    try {
      var h = Math.max(
        document.body ? document.body.scrollHeight : 0,
        document.documentElement ? document.documentElement.scrollHeight : 0
      );
      parent.postMessage({ __matFrame: '{$__frameId}', height: h }, '*');
    } catch(e){}
  }
  window.addEventListener('load', function(){ sendHeight(); setTimeout(sendHeight, 300); });
  window.addEventListener('resize', sendHeight);
  // Pantau perubahan DOM (konten dinamis)
  if (window.MutationObserver){
    try { new MutationObserver(sendHeight).observe(document.documentElement, {subtree:true, childList:true, attributes:true}); } catch(e){}
  }
  setInterval(sendHeight, 1500);
})();
</script>
JS;

// Meta CSP untuk DOKUMEN DI DALAM iframe (bukan parent). Dengan sandbox tanpa
// allow-same-origin, dokumen ini berorigin 'null' & terisolasi. Policy ini
// mengizinkan konten materi memakai inline script/style + resource https/data
// (mis. gambar, library CDN) agar HTML interaktif berjalan penuh, sekaligus
// tetap independen dari CSP aplikasi utama.
$__innerCsp = "<meta http-equiv=\"Content-Security-Policy\" "
    . "content=\"default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; "
    . "img-src 'self' https: data: blob:; "
    . "media-src 'self' https: data: blob:; "
    . "style-src 'self' https: 'unsafe-inline'; "
    . "script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; "
    . "font-src 'self' https: data:;\">";

// Gabungkan: meta CSP + konten materi + skrip resize. Konten materi HTML mentah
// (boleh berisi <!DOCTYPE>, <html>, <head>, <body>). Meta CSP di awal & skrip
// resize di akhir tetap valid diproses browser meski struktur tidak lengkap.
$__srcdoc = $__innerCsp . "\n" . $html . "\n" . $__resizeScript;
?>
<div class="html-frame-wrap border rounded bg-white" style="overflow:hidden;">
  <iframe id="<?= $__frameId ?>"
          sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox allow-forms allow-modals"
          referrerpolicy="no-referrer"
          loading="lazy"
          style="width:100%; border:0; min-height:200px; display:block;"
          srcdoc="<?= esc($__srcdoc, 'attr') ?>"></iframe>
</div>
<div class="text-muted mt-1" style="font-size:0.68rem;">
  <i class="bi bi-shield-check me-1"></i>Konten interaktif berjalan dalam mode aman (terisolasi).
</div>
<script>
(function(){
  var frame = document.getElementById('<?= $__frameId ?>');
  if (!frame) return;
  window.addEventListener('message', function(e){
    var d = e.data;
    if (d && d.__matFrame === '<?= $__frameId ?>' && typeof d.height === 'number'){
      // Batasi tinggi wajar (hindari 0 atau ekstrem)
      var h = Math.min(Math.max(d.height, 200), 20000);
      frame.style.height = (h + 24) + 'px';
    }
  });
})();
</script>
