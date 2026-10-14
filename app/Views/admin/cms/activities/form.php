<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold"><?= $title ?></h1>
            <p class="text-muted small mb-0">Kelola dokumentasi kegiatan sekolah lengkap dengan multi-foto dan deskripsi masing-masing foto.</p>
        </div>
        <a href="<?= base_url('admin/cms/activities') ?>" class="btn btn-outline-secondary rounded-pill px-3 py-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?= isset($activity) ? base_url('admin/cms/activities/update/' . $activity['id']) : base_url('admin/cms/activities/store') ?>"
          method="post" enctype="multipart/form-data" id="activityForm">
        <?= csrf_field() ?>

        <div class="row g-4">
            <!-- Informasi Utama Kegiatan -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 sticky-top" style="top: 80px;">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="card-title fw-bold mb-0 text-primary">
                            <i class="bi bi-info-circle me-1"></i> Informasi Kegiatan
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" 
                                   value="<?= old('title', $activity['title'] ?? '') ?>" 
                                   placeholder="Contoh: Upacara Hari Pahlawan 2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control rounded-3" 
                                   value="<?= old('date', $activity['date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi Umum Album / Kegiatan</label>
                            <textarea name="description" class="form-control rounded-3" rows="4" 
                                      placeholder="Ringkasan atau keterangan umum tentang jalannya kegiatan ini..."><?= old('description', $activity['description'] ?? '') ?></textarea>
                            <small class="text-muted">Deskripsi ini akan menjadi keterangan umum album dan teks pembuka kegiatan.</small>
                        </div>

                        <div class="alert alert-info border-0 rounded-3 small p-3 mb-0">
                            <i class="bi bi-lightbulb me-1 fw-bold"></i>
                            <strong>Tips Album:</strong> Anda dapat mengunggah beberapa foto sekaligus. Pilih salah satu foto sebagai <strong>Cover Album</strong> yang akan menjadi tampilan utama kartu kegiatan di beranda. Setiap foto dapat diberi deskripsi spesifik.
                        </div>

                        <div class="mt-4 pt-3 border-top d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-semibold shadow-sm">
                                <i class="bi bi-cloud-arrow-up me-1"></i> <?= isset($activity) ? 'Perbarui Album Kegiatan' : 'Simpan Album Kegiatan' ?>
                            </button>
                            <a href="<?= base_url('admin/cms/activities') ?>" class="btn btn-light rounded-pill py-2 text-secondary">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Foto-foto Album & Deskripsi -->
            <div class="col-lg-8">
                <?php if (isset($activity) && !empty($photos)): ?>
                    <!-- KELOLA FOTO YANG SUDAH ADA -->
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-success">
                                    <i class="bi bi-images me-1"></i> Foto-foto Dalam Album Ini (<?= count($photos) ?> Foto)
                                </h5>
                                <small class="text-muted">Pilih satu foto sebagai cover album, edit deskripsi foto, atau tandai foto yang ingin dihapus.</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3" id="existingPhotosGrid">
                                <?php foreach ($photos as $idx => $p): ?>
                                    <div class="col-md-6 col-xl-6 existing-photo-card" id="card_existing_<?= $p['id'] ?>">
                                        <div class="border rounded-4 p-3 h-100 bg-light bg-opacity-50 position-relative d-flex flex-column justify-content-between">
                                            <div>
                                                <!-- Cover Badge & Thumbnail -->
                                                <div class="position-relative mb-2 rounded-3 overflow-hidden" style="height: 180px;">
                                                    <img src="<?= base_url('uploads/activities/' . $p['image']) ?>" 
                                                         class="w-100 h-100 object-fit-cover rounded-3"
                                                         alt="Foto <?= $idx + 1 ?>">
                                                    <?php if ($p['is_cover'] == 1): ?>
                                                        <span class="badge bg-success position-absolute top-0 start-0 m-2 rounded-pill px-2.5 py-1.5 shadow-sm">
                                                            <i class="bi bi-star-fill me-1"></i> Cover Utama
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Radio Cover Selector -->
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="cover_choice" 
                                                           id="cover_exist_<?= $p['id'] ?>" 
                                                           value="existing_<?= $p['id'] ?>" 
                                                           <?= $p['is_cover'] == 1 ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold text-dark small" for="cover_exist_<?= $p['id'] ?>">
                                                        Jadikan Cover Album
                                                    </label>
                                                </div>

                                                <!-- Deskripsi Khusus Foto -->
                                                <div class="mb-2">
                                                    <label class="form-label small fw-semibold text-secondary mb-1">
                                                        <i class="bi bi-card-text me-1"></i> Deskripsi Foto:
                                                    </label>
                                                    <textarea name="existing_captions[<?= $p['id'] ?>]" 
                                                              class="form-control form-control-sm rounded-3" 
                                                              rows="2" 
                                                              placeholder="Keterangan momen pada foto ini..."><?= esc($p['caption'] ?: $activity['description']) ?></textarea>
                                                </div>
                                            </div>

                                            <!-- Opsi Hapus Foto -->
                                            <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input border-danger" type="checkbox" 
                                                           name="delete_photos[]" 
                                                           value="<?= $p['id'] ?>" 
                                                           id="del_<?= $p['id'] ?>"
                                                           onchange="toggleDeleteCard(this, 'card_existing_<?= $p['id'] ?>')">
                                                    <label class="form-check-label text-danger small fw-semibold" for="del_<?= $p['id'] ?>">
                                                        <i class="bi bi-trash"></i> Hapus Foto Ini
                                                    </label>
                                                </div>
                                                <small class="text-muted">#<?= $idx + 1 ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- UPLOAD FOTO BARU -->
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-primary">
                                <i class="bi bi-cloud-arrow-up me-1"></i> <?= isset($activity) ? 'Tambah Foto Baru ke Album' : 'Unggah Foto-foto Album Kegiatan' ?>
                            </h5>
                            <small class="text-muted">Pilih satu atau beberapa file foto (JPG, PNG, WEBP maks 5MB per file).</small>
                        </div>
                        <label for="bulkPhotoInput" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 cursor-pointer">
                            <i class="bi bi-plus-circle me-1"></i> Pilih Banyak Foto Sekaligus
                        </label>
                    </div>
                    <div class="card-body">
                        <!-- Input File Picker (Multiple) -->
                        <div class="mb-4">
                            <input type="file" id="bulkPhotoInput" class="d-none" multiple accept="image/jpeg,image/png,image/webp,image/jpg">
                            
                            <div class="dropzone-box border-2 border-dashed rounded-4 p-4 text-center bg-light bg-opacity-50 cursor-pointer" 
                                 onclick="document.getElementById('bulkPhotoInput').click()" id="dropArea">
                                <i class="bi bi-cloud-upload display-5 text-primary opacity-75 d-block mb-2"></i>
                                <h6 class="fw-bold mb-1">Klik untuk memilih foto atau seret file ke sini</h6>
                                <p class="text-muted small mb-0">Dapat memilih banyak foto sekaligus (Maksimal 5MB per foto)</p>
                            </div>
                        </div>

                        <!-- Container List Foto yang akan diunggah -->
                        <div id="newPhotosContainer" class="row g-3">
                            <!-- Diisi otomatis oleh JavaScript setelah memilih foto -->
                        </div>

                        <?php if (!isset($activity)): ?>
                            <div id="noPhotoPlaceholder" class="text-center py-4 text-muted">
                                <i class="bi bi-image fs-1 d-block text-secondary opacity-50 mb-1"></i>
                                Belum ada foto yang dipilih. Silakan pilih minimal 1 foto kegiatan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.border-dashed {
    border: 2px dashed #b8c2cc !important;
    transition: all 0.2s ease-in-out;
}
.border-dashed:hover {
    border-color: #0d6efd !important;
    background-color: #f0f7ff !important;
}
.cursor-pointer {
    cursor: pointer;
}
.object-fit-cover {
    object-fit: cover;
}
.deleted-card {
    opacity: 0.45;
    filter: grayscale(100%);
    border-color: #dc3545 !important;
}
</style>

<script>
let photoIndex = 0;
const isEditMode = <?= isset($activity) ? 'true' : 'false' ?>;

function toggleDeleteCard(checkbox, cardId) {
    const card = document.getElementById(cardId);
    if (!card) return;
    if (checkbox.checked) {
        card.classList.add('deleted-card');
    } else {
        card.classList.remove('deleted-card');
    }
}

document.getElementById('bulkPhotoInput').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    const container = document.getElementById('newPhotosContainer');
    const placeholder = document.getElementById('noPhotoPlaceholder');
    if (placeholder) placeholder.style.display = 'none';

    files.forEach((file) => {
        // Validasi tipe file
        if (!file.type.match('image.*')) {
            alert('File ' + file.name + ' bukan gambar yang valid!');
            return;
        }

        const currentIndex = photoIndex++;
        const cardCol = document.createElement('div');
        cardCol.className = 'col-md-6 new-photo-item';
        cardCol.id = `new_card_${currentIndex}`;

        // Cek apakah ini foto pertama di mode create (otomatis jadi cover jika create)
        const isDefaultCover = (!isEditMode && document.querySelectorAll('.new-photo-item').length === 0);

        const reader = new FileReader();
        reader.onload = function(evt) {
            cardCol.innerHTML = `
                <div class="border rounded-4 p-3 h-100 bg-white shadow-sm d-flex flex-column justify-content-between">
                    <div>
                        <div class="position-relative mb-2 rounded-3 overflow-hidden" style="height: 180px;">
                            <img src="${evt.target.result}" class="w-100 h-100 object-fit-cover rounded-3" alt="Preview">
                            <span class="badge bg-primary position-absolute top-0 start-0 m-2 rounded-pill px-2.5 py-1">
                                Foto Baru
                            </span>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" 
                                   name="${isEditMode ? 'cover_choice' : 'cover_index'}" 
                                   id="new_cover_${currentIndex}" 
                                   value="${isEditMode ? 'new_' + currentIndex : currentIndex}" 
                                   ${isDefaultCover ? 'checked' : ''}>
                            <label class="form-check-label fw-bold text-dark small" for="new_cover_${currentIndex}">
                                Jadikan Cover Album
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">
                                <i class="bi bi-card-text me-1"></i> Deskripsi Foto:
                            </label>
                            <textarea name="${isEditMode ? 'new_photo_captions[]' : 'photo_captions[]'}" 
                                      class="form-control form-control-sm rounded-3" 
                                      rows="2" 
                                      placeholder="Keterangan untuk foto ini..."></textarea>
                        </div>
                    </div>

                    <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                        <span class="small text-muted text-truncate" style="max-width: 180px;">${file.name}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" 
                                onclick="removeNewPhotoCard('new_card_${currentIndex}')">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            `;

            // Append input file clone into container for submission
            const dt = new DataTransfer();
            dt.items.add(file);
            const hiddenFileInput = document.createElement('input');
            hiddenFileInput.type = 'file';
            hiddenFileInput.name = 'photos[]';
            hiddenFileInput.style.display = 'none';
            hiddenFileInput.files = dt.files;
            cardCol.appendChild(hiddenFileInput);

            container.appendChild(cardCol);
        };
        reader.readAsDataURL(file);
    });

    // Reset value agar bisa memilih file yang sama jika diinginkan
    e.target.value = '';
});

function removeNewPhotoCard(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.remove();
    }
    const container = document.getElementById('newPhotosContainer');
    const placeholder = document.getElementById('noPhotoPlaceholder');
    if (placeholder && container.querySelectorAll('.new-photo-item').length === 0) {
        placeholder.style.display = 'block';
    }
}

// Drag & drop support
const dropArea = document.getElementById('dropArea');
['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropArea.classList.add('border-primary', 'bg-white');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropArea.classList.remove('border-primary', 'bg-white');
    }, false);
});

dropArea.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    const bulkInput = document.getElementById('bulkPhotoInput');
    bulkInput.files = files;
    bulkInput.dispatchEvent(new Event('change'));
}, false);
</script>

<?= $this->endSection() ?>