<?php
// 🎯 Standar Skill Diterapkan: high-end-visual-design (UI)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<!-- ===== HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-inline-flex align-items-center gap-1.5 px-2.5 py-0.5 rounded-pill bg-primary-subtle text-primary border border-primary-subtle text-uppercase tracking-wider fw-semibold mb-1" style="font-size:0.65rem; letter-spacing:0.06em;">
            <i class="bi bi-gear-fill"></i> Pengaturan Sistem Persuratan
        </div>
        <h2 class="h4 fw-bold mb-0 text-dark">KOP, Tanda Tangan & Penomoran Surat</h2>
        <small class="text-muted">Konfigurasi blanko KOP, tanda tangan KS, cap sekolah, dan urutan nomor registrasi surat dinas</small>
    </div>
    <div>
        <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Surat Keluar
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show rounded-3 shadow-xs mb-4" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <i class="bi bi-check-circle-fill me-1"></i> <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show rounded-3 shadow-xs mb-4" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- ===== ROW 1: Penomoran & KOP ===== -->
<div class="row g-4">

    <!-- CARD 1: PENOMORAN SURAT -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                    <i class="bi bi-hash fs-6"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Urutan Penomoran Otomatis</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Atur nomor surat terakhir agar penerbitan berikutnya berlanjut sesuai buku agenda fisik</small>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form action="<?= base_url('admin/settings/nomor-surat/save') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="year" class="form-label fw-semibold small">Tahun Registrasi Surat <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm font-monospace" id="year" name="year" value="<?= esc($current_year ?? date('Y')) ?>" required>
                        <small class="text-muted" style="font-size:0.72rem;">Penomoran surat direset otomatis setiap pergantian tahun takwim.</small>
                    </div>

                    <div class="mb-3">
                        <label for="last_sequence" class="form-label fw-semibold small">Nomor Urut Terakhir yang Terbit <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-123"></i></span>
                            <input type="number" class="form-control form-control-sm font-monospace fw-bold text-primary" id="last_sequence" name="last_sequence" value="<?= esc($last_sequence ?? 0) ?>" min="0" required>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                            Contoh: Jika surat keluar terakhir Anda adalah <strong>101/PK.01.01/2026</strong>, masukkan angka <code>101</code> di sini.
                        </small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label for="default_classification_code" class="form-label fw-semibold small">Kode Klasifikasi Baku</label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="default_classification_code" name="default_classification_code" value="<?= esc($default_classification_code ?? 'PK.01.01') ?>" placeholder="PK.01.01">
                            <small class="text-muted" style="font-size:0.72rem;">Default kode surat keluar dinas.</small>
                        </div>
                        <div class="col-md-5">
                            <label for="letter_number_padding" class="form-label fw-semibold small">Digit Padding</label>
                            <select class="form-select form-select-sm" id="letter_number_padding" name="letter_number_padding">
                                <option value="1" <?= ($letter_number_padding ?? 3) == 1 ? 'selected' : '' ?>>1 digit (1, 101)</option>
                                <option value="2" <?= ($letter_number_padding ?? 3) == 2 ? 'selected' : '' ?>>2 digit (01, 101)</option>
                                <option value="3" <?= ($letter_number_padding ?? 3) == 3 ? 'selected' : '' ?>>3 digit (001, 101)</option>
                                <option value="4" <?= ($letter_number_padding ?? 3) == 4 ? 'selected' : '' ?>>4 digit (0001, 0101)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Live Estimation Box -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="text-uppercase fw-semibold text-muted mb-1" style="font-size:0.65rem; letter-spacing:0.05em;">Estimasi Nomor Surat Berikutnya:</div>
                        <div class="h5 fw-bold text-success font-monospace mb-0" id="preview-next-number">...</div>
                        <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                            Nomor di atas akan otomatis diberikan saat membuat surat keluar baru berikutnya.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-xs">
                        <i class="bi bi-save me-1"></i>Simpan Pengaturan Penomoran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- CARD 2: KOP SURAT -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-circle bg-info bg-opacity-10 text-info p-2">
                    <i class="bi bi-image fs-6"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Pengaturan KOP Surat Digital</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Unggah gambar kepala surat resmi yang akan dicetak pada PDF dinas</small>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form action="<?= base_url('admin/settings/kop-surat/upload') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="kop_file" class="form-label fw-semibold small">Upload Berkas KOP (PNG/JPG, Maks 5MB) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="kop_file" name="kop_file" accept=".png,.jpg,.jpeg" required>
                        <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                            Rekomendasi: Lebar 1500px, latar belakang putih/transparan, rasio proporsional kepala surat.
                        </small>
                    </div>
                    <button type="submit" class="btn btn-info text-white btn-sm rounded-pill px-4 shadow-xs mb-3">
                        <i class="bi bi-upload me-1"></i>Unggah &amp; Perbarui KOP
                    </button>
                </form>

                <h6 class="fw-semibold small text-muted mb-2">Pratinjau KOP Surat Aktif:</h6>
                <div class="border rounded-3 p-3 bg-light text-center" style="min-height: 120px; display:flex; align-items:center; justify-content:center;">
                    <?php if (!empty($kop_surat)) : ?>
                        <img src="<?= base_url($kop_surat) ?>?t=<?= time() ?>" alt="Kop Surat Aktif" class="img-fluid rounded" style="max-height: 160px; object-fit: contain;">
                    <?php else : ?>
                        <div class="text-muted py-3">
                            <i class="bi bi-image fs-3 d-block opacity-50 mb-1"></i>
                            <span class="small">Belum ada berkas KOP surat yang diunggah.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div><!-- /ROW 1 -->

<!-- ===== ROW 2: Tanda Tangan & Cap ===== -->
<div class="row g-4 mt-1">

    <!-- CARD 3: TANDA TANGAN KEPALA SEKOLAH -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-circle bg-success bg-opacity-10 text-success p-2">
                    <i class="bi bi-pen fs-6"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Tanda Tangan Kepala Sekolah</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Upload PNG transparan, maks 150 KB, lebar ideal ± 400 px</small>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">

                <!-- Form Upload -->
                <form action="<?= base_url('admin/settings/signature/upload') ?>" method="post" enctype="multipart/form-data" class="mb-3">
                    <?= csrf_field() ?>
                    <label for="signature_file" class="form-label fw-semibold small">
                        Upload Tanda Tangan KS <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control form-control-sm" id="signature_file"
                               name="signature_file" accept=".png" required>
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                        Wajib PNG dengan <strong>background transparan</strong>. Ukuran file maks 150 KB.
                    </small>
                </form>

                <!-- Pratinjau (checkerboard bg = tampilkan transparansi) -->
                <h6 class="fw-semibold small text-muted mb-2">Pratinjau Tanda Tangan Aktif:</h6>
                <div class="border rounded-3 p-3 text-center mb-3"
                     style="min-height:100px;display:flex;align-items:center;justify-content:center;
                            background:repeating-conic-gradient(#ddd 0% 25%,#fff 0% 50%) 0 0/20px 20px;">
                    <?php if (!empty($ks_signature_path)) : ?>
                        <img src="<?= base_url($ks_signature_path) ?>?t=<?= time() ?>"
                             alt="Tanda Tangan KS"
                             style="max-height:100px;max-width:200px;object-fit:contain;">
                    <?php else : ?>
                        <div class="text-muted py-2">
                            <i class="bi bi-pen fs-3 d-block opacity-50 mb-1"></i>
                            <span class="small">Belum ada tanda tangan yang diunggah.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Preferensi checkbox -->
                <form action="<?= base_url('admin/settings/signature-prefs/save') ?>" method="post">
                    <?= csrf_field() ?>
                    <!-- Simpan nilai seal agar tidak tertimpa -->
                    <input type="hidden" name="use_seal" value="<?= $use_seal ? '1' : '0' ?>">
                    <input type="hidden" name="use_signature" value="0">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="use_signature" name="use_signature" value="1"
                               <?= $use_signature ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="use_signature">
                            Gunakan tanda tangan KS pada PDF
                        </label>
                    </div>
                    <small class="text-muted d-block mb-2" style="font-size:0.72rem;">
                        Jika aktif, tanda tangan akan tampil di area <code>.signature-space</code> pada PDF.
                    </small>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="bi bi-save me-1"></i>Simpan Preferensi
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- CARD 4: CAP SEKOLAH -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-circle bg-warning bg-opacity-10 text-warning p-2">
                    <i class="bi bi-patch-check fs-6"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Cap / Stempel Sekolah</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Upload PNG transparan, maks 150 KB, ukuran asli cap (± 300 × 300 px)</small>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">

                <!-- Form Upload -->
                <form action="<?= base_url('admin/settings/seal/upload') ?>" method="post" enctype="multipart/form-data" class="mb-3">
                    <?= csrf_field() ?>
                    <label for="seal_file" class="form-label fw-semibold small">
                        Upload Cap Sekolah <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control form-control-sm" id="seal_file"
                               name="seal_file" accept=".png" required>
                        <button type="submit" class="btn btn-warning btn-sm px-3">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                        Wajib PNG dengan <strong>background transparan</strong>. Ukuran file maks 150 KB.
                        Dimensi ideal menyesuaikan ukuran cap asli (biasanya 300 × 300 px).
                    </small>
                </form>

                <!-- Pratinjau -->
                <h6 class="fw-semibold small text-muted mb-2">Pratinjau Cap Aktif:</h6>
                <div class="border rounded-3 p-3 text-center mb-3"
                     style="min-height:100px;display:flex;align-items:center;justify-content:center;
                            background:repeating-conic-gradient(#ddd 0% 25%,#fff 0% 50%) 0 0/20px 20px;">
                    <?php if (!empty($school_seal_path)) : ?>
                        <img src="<?= base_url($school_seal_path) ?>?t=<?= time() ?>"
                             alt="Cap Sekolah"
                             style="max-height:120px;max-width:120px;object-fit:contain;">
                    <?php else : ?>
                        <div class="text-muted py-2">
                            <i class="bi bi-patch-check fs-3 d-block opacity-50 mb-1"></i>
                            <span class="small">Belum ada cap yang diunggah.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Preferensi checkbox -->
                <form action="<?= base_url('admin/settings/signature-prefs/save') ?>" method="post">
                    <?= csrf_field() ?>
                    <!-- Simpan nilai signature agar tidak tertimpa -->
                    <input type="hidden" name="use_signature" value="<?= $use_signature ? '1' : '0' ?>">
                    <input type="hidden" name="use_seal" value="0">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="use_seal" name="use_seal" value="1"
                               <?= $use_seal ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="use_seal">
                            Gunakan cap sekolah pada PDF
                        </label>
                    </div>
                    <small class="text-muted d-block mb-2" style="font-size:0.72rem;">
                        Jika aktif, cap akan tampil di area <code>.seal-space</code> pada PDF.
                    </small>
                    <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                        <i class="bi bi-save me-1"></i>Simpan Preferensi
                    </button>
                </form>

            </div>
        </div>
    </div>

</div><!-- /ROW 2 -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seqInput = document.getElementById('last_sequence');
    const yearInput = document.getElementById('year');
    const codeInput = document.getElementById('default_classification_code');
    const padSelect = document.getElementById('letter_number_padding');
    const previewEl = document.getElementById('preview-next-number');

    function updatePreview() {
        const lastSeq = parseInt(seqInput.value || 0, 10);
        const nextSeq = lastSeq + 1;
        const year = yearInput.value || '<?= date('Y') ?>';
        const code = (codeInput.value || 'PK.01.01').trim();
        const pad = parseInt(padSelect.value || 3, 10);

        const paddedSeq = String(nextSeq).padStart(pad, '0');
        previewEl.textContent = `${paddedSeq}/${code}/${year}`;
    }

    seqInput.addEventListener('input', updatePreview);
    yearInput.addEventListener('input', updatePreview);
    codeInput.addEventListener('input', updatePreview);
    padSelect.addEventListener('change', updatePreview);

    updatePreview();
});
</script>

<?= $this->endSection() ?>
