<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">📖 <?= esc($book['title']) ?></h5>
  <div>
    <a href="<?= base_url('siswa/ebook') ?>" class="btn btn-secondary btn-sm">⬅ Kembali</a>
    <a href="<?= base_url('siswa/ebook/download/' . $book['id']) ?>" class="btn btn-outline-primary btn-sm">⬇️ Download</a>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <!-- PDF Viewer Controls -->
    <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <button id="prevPage" class="btn btn-outline-secondary btn-sm" disabled>⬅</button>
        <span class="small">
          Hal <input type="number" id="pageInput" class="form-control form-control-sm d-inline-block" style="width:60px;" min="1" value="1">
          / <span id="pageCount">-</span>
        </span>
        <button id="nextPage" class="btn btn-outline-secondary btn-sm">➡</button>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button id="zoomOut" class="btn btn-outline-secondary btn-sm">➖</button>
        <span id="zoomLevel" class="small">100%</span>
        <button id="zoomIn" class="btn btn-outline-secondary btn-sm">➕</button>
      </div>
    </div>

    <!-- PDF Canvas -->
    <div id="pdfContainer" class="text-center p-3 overflow-auto" style="max-height: 80vh; background: #525659;">
      <canvas id="pdfCanvas"></canvas>
    </div>

    <!-- Error state -->
    <div id="pdfError" class="text-center py-5" style="display:none;">
      <p class="fs-1">⚠️</p>
      <p class="text-muted">Buku tidak dapat ditampilkan. File tidak tersedia.</p>
      <a href="<?= base_url('siswa/ebook') ?>" class="btn btn-secondary btn-sm">⬅ Kembali ke Daftar</a>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const url = '<?= base_url('admin/ebook/file/read/' . $book['id']) ?>';
  const canvas = document.getElementById('pdfCanvas');
  const ctx = canvas.getContext('2d');
  const container = document.getElementById('pdfContainer');
  const errorDiv = document.getElementById('pdfError');

  let pdfDoc = null;
  let currentPage = 1;
  let zoom = 1.0;
  const minZoom = 0.5;
  const maxZoom = 2.0;
  const zoomStep = 0.25;

  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  function renderPage(num) {
    pdfDoc.getPage(num).then(function(page) {
      const viewport = page.getViewport({ scale: zoom });
      canvas.height = viewport.height;
      canvas.width = viewport.width;

      page.render({ canvasContext: ctx, viewport: viewport });

      document.getElementById('pageInput').value = num;
      document.getElementById('prevPage').disabled = (num <= 1);
      document.getElementById('nextPage').disabled = (num >= pdfDoc.numPages);
    });
  }

  function updateZoomLabel() {
    document.getElementById('zoomLevel').textContent = Math.round(zoom * 100) + '%';
  }

  // Load PDF
  pdfjsLib.getDocument(url).promise.then(function(pdf) {
    pdfDoc = pdf;
    document.getElementById('pageCount').textContent = pdf.numPages;
    renderPage(1);
  }).catch(function(err) {
    console.error('PDF load error:', err);
    container.style.display = 'none';
    errorDiv.style.display = 'block';
  });

  // Navigation
  document.getElementById('prevPage').addEventListener('click', function() {
    if (currentPage <= 1) return;
    currentPage--;
    renderPage(currentPage);
  });

  document.getElementById('nextPage').addEventListener('click', function() {
    if (!pdfDoc || currentPage >= pdfDoc.numPages) return;
    currentPage++;
    renderPage(currentPage);
  });

  document.getElementById('pageInput').addEventListener('change', function() {
    let page = parseInt(this.value);
    if (!pdfDoc || isNaN(page)) return;
    if (page < 1) page = 1;
    if (page > pdfDoc.numPages) page = pdfDoc.numPages;
    currentPage = page;
    renderPage(currentPage);
  });

  // Zoom
  document.getElementById('zoomIn').addEventListener('click', function() {
    if (zoom >= maxZoom) return;
    zoom = Math.min(zoom + zoomStep, maxZoom);
    updateZoomLabel();
    renderPage(currentPage);
  });

  document.getElementById('zoomOut').addEventListener('click', function() {
    if (zoom <= minZoom) return;
    zoom = Math.max(zoom - zoomStep, minZoom);
    updateZoomLabel();
    renderPage(currentPage);
  });
});
</script>

<?= $this->endSection() ?>
