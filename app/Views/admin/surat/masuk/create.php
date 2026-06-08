<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-envelope-arrow-down me-2 text-success"></i>Catat Surat Masuk</h2>
    <small class="text-muted">Upload file surat dan gunakan OCR otomatis untuk mengisi form</small>
  </div>
  <a href="<?= base_url('admin/surat-masuk') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <strong>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach (session()->getFlashdata('errors') as $e): ?>
        <li><?= esc($e) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('admin/surat-masuk/store') ?>" enctype="multipart/form-data" id="form-surat-masuk">
  <?= csrf_field() ?>
  <!-- Hidden fields untuk data OCR -->
  <input type="hidden" name="ocr_confidence" id="ocr_confidence_val">
  <input type="hidden" name="ocr_raw_text" id="ocr_raw_text_val">

  <div class="row g-4">
    <!-- Kolom Kiri: Upload & OCR -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-success text-white fw-semibold py-2">
          <i class="bi bi-cloud-upload me-2"></i>1. Upload Scan Surat
        </div>
        <div class="card-body">
          <!-- Drop Zone -->
          <div id="drop-zone" class="border border-2 border-dashed rounded text-center p-4 mb-3"
               style="cursor:pointer; min-height:200px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
            <i class="bi bi-file-earmark-arrow-up fs-1 text-muted mb-2"></i>
            <p class="mb-1 fw-semibold">Klik atau drag file ke sini</p>
            <p class="text-muted small mb-2">Format: PDF, JPG, PNG, WebP (maks. 15 MB)</p>
            <input type="file" name="scan_file" id="scan_file" accept=".pdf,.jpg,.jpeg,.png,.webp"
                   class="d-none">
            <button type="button" class="btn btn-outline-success btn-sm" id="btn-browse">
              <i class="bi bi-folder2-open me-1"></i>Pilih File
            </button>
          </div>

          <!-- Preview -->
          <div id="file-preview" style="display:none;">
            <p class="fw-semibold small mb-1">Preview:</p>
            <img id="img-preview" src="" alt="Preview" class="img-fluid rounded border mb-2" style="max-height:300px; display:none;">
            <div id="pdf-preview-label" class="alert alert-secondary py-2 small" style="display:none;">
              <i class="bi bi-file-earmark-pdf me-1 text-danger"></i><span id="pdf-filename"></span>
            </div>
          </div>

          <!-- OCR Status -->
          <div id="ocr-status" style="display:none;" class="mt-3">
            <div id="ocr-loading" style="display:none;" class="text-center py-3">
              <div class="spinner-border text-success spinner-border-sm me-2"></div>
              <span>Memproses OCR... harap tunggu</span>
            </div>
            <div id="ocr-done" style="display:none;" class="alert alert-success py-2 small">
              <i class="bi bi-check-circle me-1"></i>
              OCR selesai! Data otomatis terisi di form. Mohon periksa dan koreksi jika perlu.
              <br><small class="text-muted">Akurasi: <span id="ocr-confidence-label"></span>%</small>
            </div>
            <div id="ocr-error" style="display:none;" class="alert alert-warning py-2 small">
              <i class="bi bi-exclamation-triangle me-1"></i>
              OCR tidak berhasil membaca teks. Isi form secara manual.
            </div>
            <div id="ocr-preview-container" style="display:none;" class="mt-2 text-start">
              <a data-bs-toggle="collapse" href="#ocrTextPreview" role="button" aria-expanded="false" class="small text-decoration-none fw-semibold">
                <i class="bi bi-file-text me-1"></i> Lihat Hasil Bacaan Teks (Raw)
              </a>
              <div class="collapse mt-2" id="ocrTextPreview">
                <textarea id="ocr-preview-textarea" class="form-control form-control-sm font-monospace text-muted bg-light" rows="8" readonly style="font-size: 11px;"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Form Data -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-light fw-semibold py-2">
          <i class="bi bi-pencil-square me-2"></i>2. Data Surat Masuk
          <small class="text-muted fw-normal ms-2">(Review & koreksi hasil OCR)</small>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="received_at">Tanggal Diterima <span class="text-danger">*</span></label>
              <input type="date" name="received_at" id="received_at" class="form-control"
                     value="<?= old('received_at', date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="letter_date">Tanggal Surat</label>
              <input type="date" name="letter_date" id="letter_date" class="form-control"
                     value="<?= old('letter_date') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" for="letter_number">Nomor Surat</label>
              <input type="text" name="letter_number" id="letter_number" class="form-control"
                     placeholder="cth: 045/PK.01.01/2026" value="<?= old('letter_number') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="sender_name">Nama Penanda Tangan <span class="text-danger">*</span></label>
              <input type="text" name="sender_name" id="sender_name" class="form-control" required
                     placeholder="Nama penanda tangan surat" value="<?= old('sender_name') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="sender_agency">Instansi Pengirim</label>
              <input type="text" name="sender_agency" id="sender_agency" class="form-control"
                     list="agency_options" placeholder="Pilih atau ketik instansi..." value="<?= old('sender_agency') ?>">
              <datalist id="agency_options">
                <option value="Dinas Pendidikan"></option>
                <option value="Sudindik JB 1"></option>
              </datalist>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" for="subject">Perihal / Hal <span class="text-danger">*</span></label>
              <input type="text" name="subject" id="subject" class="form-control" required
                     placeholder="Isi perihal surat" value="<?= old('subject') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="letter_category">Kategori</label>
              <select name="letter_category" id="letter_category" class="form-select">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $key => $label): ?>
                  <option value="<?= $key ?>" <?= old('letter_category') === $key ? 'selected' : '' ?>>
                    <?= $label ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="disposition">Disposisi</label>
              <input type="text" name="disposition" id="disposition" class="form-control"
                     placeholder="Untuk..." value="<?= old('disposition') ?>">
            </div>
          </div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
          <button type="submit" class="btn btn-success" id="btn-save-surat">
            <i class="bi bi-save me-2"></i>Simpan Surat Masuk
          </button>
          <a href="<?= base_url('admin/surat-masuk') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
// Konfigurasi worker PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('scan_file');

document.getElementById('btn-browse').addEventListener('click', () => fileInput.click());
dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('bg-light'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bg-light'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('bg-light');
  if (e.dataTransfer.files.length) {
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    fileInput.files = dt.files;
    handleFile(e.dataTransfer.files[0]);
  }
});

fileInput.addEventListener('change', () => {
  if (fileInput.files.length) handleFile(fileInput.files[0]);
});

async function handleFile(file) {
  // Show preview
  document.getElementById('file-preview').style.display = 'block';
  document.getElementById('ocr-status').style.display = 'block';
  document.getElementById('ocr-done').style.display = 'none';
  document.getElementById('ocr-error').style.display = 'none';

  if (file.type.startsWith('image/')) {
    const url = URL.createObjectURL(file);
    const img = document.getElementById('img-preview');
    img.src = url;
    img.style.display = 'block';
    document.getElementById('pdf-preview-label').style.display = 'none';
    
    // Jalankan OCR langsung
    runOCR(file);
  } else if (file.type === 'application/pdf') {
    document.getElementById('img-preview').style.display = 'none';
    const pdfLabel = document.getElementById('pdf-preview-label');
    pdfLabel.style.display = 'block';
    document.getElementById('pdf-filename').textContent = file.name;
    
    // Konversi semua halaman PDF ke Gambar untuk di-OCR
    document.getElementById('ocr-loading').style.display = 'block';
    document.querySelector('#ocr-loading span').textContent = 'Membaca PDF...';
    
    try {
      const fileUrl = URL.createObjectURL(file);
      const loadingTask = pdfjsLib.getDocument(fileUrl);
      const pdf = await loadingTask.promise;
      const numPages = pdf.numPages;

      const scale = 1.5;

      // Render semua halaman ke canvas masing-masing, kumpulkan ukuran
      const canvases = [];
      let totalHeight = 0;
      let maxWidth = 0;

      for (let pageNum = 1; pageNum <= numPages; pageNum++) {
        document.querySelector('#ocr-loading span').textContent = `Membaca halaman ${pageNum} dari ${numPages}...`;
        const page = await pdf.getPage(pageNum);
        const viewport = page.getViewport({ scale: scale });

        const c = document.createElement('canvas');
        c.width  = viewport.width;
        c.height = viewport.height;
        await page.render({ canvasContext: c.getContext('2d'), viewport }).promise;

        canvases.push(c);
        totalHeight += viewport.height;
        if (viewport.width > maxWidth) maxWidth = viewport.width;
      }

      // Gabungkan semua halaman ke satu canvas panjang (atas ke bawah)
      const mergedCanvas = document.createElement('canvas');
      mergedCanvas.width  = maxWidth;
      mergedCanvas.height = totalHeight;
      const mergedCtx = mergedCanvas.getContext('2d');

      let offsetY = 0;
      for (const c of canvases) {
        mergedCtx.drawImage(c, 0, offsetY);
        offsetY += c.height;
      }

      // Ubah ke base64 image dan jalankan OCR
      const base64Img = mergedCanvas.toDataURL('image/jpeg');
      document.querySelector('#ocr-loading span').textContent = `Memproses OCR ${numPages} halaman... harap tunggu`;
      runOCR(base64Img);

    } catch (err) {
      console.error('PDF JS error:', err);
      document.getElementById('ocr-loading').style.display = 'none';
      const errDiv = document.getElementById('ocr-error');
      errDiv.style.display = 'block';
      errDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Gagal membaca PDF: ' + err.message;
    }
  }
}

async function runOCR(imageFile) {
  document.getElementById('ocr-loading').style.display = 'block';
  try {
    const { data } = await Tesseract.recognize(imageFile, 'ind', {
      logger: m => console.log('OCR Progress:', m)
    });

    const text = data.text || '';
    const confidence = Math.round(data.confidence || 0);

    // Simpan ke hidden field
    document.getElementById('ocr_confidence_val').value = confidence;
    document.getElementById('ocr_raw_text_val').value = text;

    // Parse teks OCR → isi form
    parseOCRResult(text, confidence);

    document.getElementById('ocr-loading').style.display = 'none';
    document.getElementById('ocr-done').style.display = 'block';
    document.getElementById('ocr-confidence-label').textContent = confidence;
    
    // Tampilkan raw text
    document.getElementById('ocr-preview-textarea').value = text;
    document.getElementById('ocr-preview-container').style.display = 'block';
  } catch (err) {
    console.error('OCR error:', err);
    document.getElementById('ocr-loading').style.display = 'none';
    const errDiv = document.getElementById('ocr-error');
    errDiv.style.display = 'block';
    errDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> OCR Gagal: ' + err.message;
  }
}

function parseOCRResult(text, confidence) {
  const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
  if (lines.length === 0) return;

  // 1. Nomor Surat
  // Mencari "Nomor: 298 / KG.11.00" atau "Nomor: e-0552/PK.01.01"
  // Harus berupa huruf/angka/strip lalu diikuti garis miring (slash)
  const noMatch = text.match(/(?:Nomor|No)[\s\.:]+([a-zA-Z0-9\-]{1,10}[\s]*\/[\s]*[a-zA-Z0-9\/\-\.]+)/i) || text.match(/[a-zA-Z0-9\-]{1,10}\s*\/\s*[a-zA-Z0-9\.\-]+\s*\/\s*\d{2,4}/i);
  if (noMatch) {
    const nomor = noMatch[1] ? noMatch[1].trim() : noMatch[0].trim();
    setIfEmpty('letter_number', nomor);
  }

  // 2. Perihal / Hal
  // Di-kosongkan sesuai permintaan pengguna.

  // 3. Tanggal surat (format Indonesia)
  const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const tglMatch = text.match(/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i);
  if (tglMatch) {
    const day   = tglMatch[1].padStart(2,'0');
    const month = String(bulan.indexOf(tglMatch[2]) + 1).padStart(2,'0');
    const year  = tglMatch[3];
    setIfEmpty('letter_date', `${year}-${month}-${day}`);
  }

  // Helper untuk membersihkan artefak OCR pada nama
  const cleanName = (str) => {
    return str.replace(/^[^a-zA-Z]+/i, '') // Hapus simbol di awal
              .replace(/^(?:NETT|NET|TTD|Ttd|Plt\.|Plh\.)\s*/i, '') // Hapus kata "NETT" / TTD
              .trim();
  };

  // 4. Nama Pengirim (Penanda tangan di bawah)
  let senderName = '';
  // Cari NIP dari bawah ke atas (batas 30 baris terakhir). Mendukung NIP dengan spasi
  for (let i = lines.length - 1; i >= Math.max(0, lines.length - 30); i--) {
    if (/NIP[\s\.:]*(\d[\d\s]{14,25})/i.test(lines[i])) {
      // NIP ketemu, cari nama 1-5 baris di atasnya
      for (let j = i - 1; j >= Math.max(0, i - 5); j--) {
        const upLine = lines[j];
        if (upLine.length > 3 && !/Pembina|Gubernur|Walikota|Kepala|Bupati|Sekretaris|Direktur|Tembusan/i.test(upLine)) {
          senderName = cleanName(upLine);
          break;
        }
      }
      if (senderName) break;
    }
  }
  
  if (!senderName) {
    for (let i = lines.length - 1; i >= Math.max(0, lines.length - 20); i--) {
      if (lines[i].match(/[A-Z][a-z]+,\s*(S\.Pd|M\.Pd|M\.Si|M\.M|Dr\.|Prof\.|S\.E|S\.Kom|S\.T)/i) || lines[i].match(/^(H\.|Hj\.|Dr\.|Drs\.|Ir\.)\s+/i)) {
        senderName = cleanName(lines[i]);
        break;
      }
    }
  }

  if (senderName) {
    setIfEmpty('sender_name', senderName);
  }

  // 5. Instansi Pengirim (KOP Surat)
  // Fitur OCR Instansi dimatikan sesuai permintaan, user menggunakan Dropdown/Freetext.
}

function setIfEmpty(fieldId, value) {
  const el = document.getElementById(fieldId);
  if (el && !el.value.trim()) el.value = value;
}
</script>
<?= $this->endSection() ?>
