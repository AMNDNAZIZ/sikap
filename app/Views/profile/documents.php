<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <div>
            <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Arsip Dokumen</h4>
            <small class="text-muted">Upload dan kelola dokumen pribadi Anda</small>
        </div>
        <a href="<?= base_url('profile') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Profil
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="bi bi-cloud-upload me-1"></i> Upload Dokumen Baru
                </div>
                <div class="card-body">
                    <form action="<?= base_url('profile/upload-document') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                            <input type="text" name="document_title" class="form-control"
                                   placeholder="Contoh: Sertifikat Pelatihan 2024" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="document_file" class="form-control"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required>
                            <div class="form-text">Format: JPG, PNG, GIF, WEBP, PDF &bull; Maks. 5 MB</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload me-1"></i> Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Document List -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="bi bi-archive me-1"></i> Daftar Arsip
                    <span class="badge bg-secondary ms-1"><?= count($documents) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($documents)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:3rem;"></i>
                            <p class="mt-2">Belum ada dokumen yang diunggah.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul</th>
                                        <th>Tipe</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <?php if ($doc['file_type'] === 'pdf'): ?>
                                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                            <?php else: ?>
                                                <i class="bi bi-file-earmark-image text-primary me-1"></i>
                                            <?php endif; ?>
                                            <a href="#" class="text-decoration-none btn-preview"
                                               data-id="<?= $doc['id'] ?>"
                                               data-type="<?= $doc['file_type'] ?>"
                                               data-title="<?= esc($doc['title'], 'attr') ?>"
                                               data-url="<?= base_url('profile/preview-document/' . $doc['id']) ?>">
                                                <?= esc($doc['title']) ?>
                                            </a>
                                            <br><small class="text-muted"><?= esc($doc['original_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $doc['file_type'] === 'pdf' ? 'danger' : 'primary' ?>">
                                                <?= strtoupper($doc['file_type']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= $doc['file_size'] >= 1048576
                                                ? number_format($doc['file_size'] / 1048576, 1) . ' MB'
                                                : number_format($doc['file_size'] / 1024, 1) . ' KB' ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <a href="<?= base_url('profile/download-document/' . $doc['id']) ?>"
                                               class="btn btn-sm btn-outline-success" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a href="<?= base_url('profile/delete-document/' . $doc['id']) ?>"
                                               class="btn btn-sm btn-outline-danger ms-1"
                                               onclick="return confirm('Hapus dokumen \'<?= esc($doc['title'], 'js') ?>\'?')"
                                               title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fullpage Preview Modal -->
<div id="previewOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:9999; flex-direction:column;">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#1e1e1e;">
        <span id="previewTitle" style="color:#fff; font-weight:600; font-size:1rem;"></span>
        <div style="display:flex; gap:8px;">
            <a id="previewDownload" href="#" class="btn btn-sm btn-outline-light">
                <i class="bi bi-download"></i> Download
            </a>
            <button onclick="closePreview()" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg"></i> Tutup
            </button>
        </div>
    </div>
    <div id="previewBody" style="flex:1; overflow:auto; display:flex; align-items:center; justify-content:center; padding:12px;">
        <!-- content injected here -->
    </div>
</div>

<script>
function openPreview(url, type, title, docId) {
    const overlay  = document.getElementById('previewOverlay');
    const body     = document.getElementById('previewBody');
    const titleEl  = document.getElementById('previewTitle');
    const dlBtn    = document.getElementById('previewDownload');

    titleEl.textContent = title;
    dlBtn.href = '<?= base_url('profile/download-document') ?>/' + docId;
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (type === 'pdf') {
        body.innerHTML = `<iframe src="${url}" style="width:100%;height:100%;min-height:85vh;border:none;border-radius:4px;"></iframe>`;
    } else {
        body.innerHTML = `<img src="${url}" style="max-width:100%;max-height:90vh;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,0.5);" alt="${title}">`;
    }
}

function closePreview() {
    document.getElementById('previewOverlay').style.display = 'none';
    document.getElementById('previewBody').innerHTML = '';
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePreview();
});

// Wire up preview links
document.querySelectorAll('.btn-preview').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        openPreview(this.dataset.url, this.dataset.type, this.dataset.title, this.dataset.id);
    });
});
</script>

<?= $this->endSection() ?>
