<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $title ?>
    </h1>

    <!-- Error Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <strong>Error!</strong> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <strong>Validation Errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">
            <form
                action="<?= isset($slider) ? base_url('admin/cms/sliders/update/' . $slider['id']) : base_url('admin/cms/sliders/store') ?>"
                method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" name="title" class="form-control" 
                                value="<?= old('title', $slider['title'] ?? '') ?>"
                                placeholder="Judul slider (opsional)">
                            <small class="text-muted">Maksimal 255 karakter</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control"
                                rows="3" placeholder="Deskripsi slider (opsional)"><?= old('description', $slider['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tautan (Link)</label>
                            <input type="url" name="link" class="form-control" 
                                value="<?= old('link', $slider['link'] ?? '') ?>"
                                placeholder="https://example.com (opsional)">
                            <small class="text-muted">Link tujuan saat slider diklik</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Gambar Slider <span class="text-danger">*</span></label>
                            <?php if (isset($slider['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?= base_url('uploads/sliders/' . $slider['image']) ?>" class="img-thumbnail"
                                        style="max-height: 150px;">
                                    <p class="text-muted small mb-0 mt-1">Gambar saat ini: <?= $slider['image'] ?></p>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" 
                                <?= isset($slider) ? '' : 'required' ?>
                                accept="image/jpeg,image/jpg,image/png,image/webp">
                            <small class="text-muted d-block mt-1">
                                • Rekomendasi ukuran: 1920x800 px<br>
                                • Format: JPG, JPEG, PNG, WEBP<br>
                                • Maksimal: 2MB
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" name="order" class="form-control"
                                        value="<?= old('order', $slider['order'] ?? 0) ?>"
                                        min="0" placeholder="0">
                                    <small class="text-muted">Urutan tampil (0 = paling awal)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" <?= (old('is_active', $slider['is_active'] ?? 1) == 1) ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= (old('is_active', $slider['is_active'] ?? 1) == 0) ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-success">💾 Simpan Slider</button>
                    <a href="<?= base_url('admin/cms/sliders') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-3">
        <strong>ℹ️ Tips:</strong>
        <ul class="mb-0 mt-2">
            <li>Gunakan gambar dengan resolusi tinggi untuk hasil terbaik</li>
            <li>Ukuran file maksimal 2MB untuk performa optimal</li>
            <li>Slider akan ditampilkan di halaman utama website</li>
            <li>Atur urutan untuk mengontrol posisi tampil slider</li>
        </ul>
    </div>
</div>

<?= $this->endSection() ?>