<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
.school-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px 12px 0 0;
    margin-bottom: 0;
}

.school-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.school-header p {
    margin: 0.25rem 0 0 0;
    opacity: 0.9;
    font-size: 0.85rem;
}

.school-card {
    border: none;
    box-shadow: 0 0 15px rgba(0,0,0,0.06);
    border-radius: 12px;
    overflow: hidden;
}

.school-form {
    padding: 1.25rem 1.5rem;
}

.form-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section-title i {
    color: #667eea;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.35rem;
    font-size: 0.9rem;
}

.form-control, .form-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.logo-preview {
    background: white;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    padding: 0.5rem;
    text-align: center;
    margin-bottom: 0;
}

.logo-preview img {
    max-height: 80px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.logo-preview-empty {
    padding: 1rem;
    color: #6c757d;
}

.logo-preview-empty i {
    font-size: 2rem;
    color: #dee2e6;
    margin-bottom: 0.25rem;
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 0.6rem 2rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.25);
}

.alert-success {
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    border-radius: 6px 0 0 6px;
}

.required-mark {
    color: #dc3545;
    margin-left: 0.2rem;
}
</style>

<div class="container-fluid px-4">
    <div class="school-card mt-4">
        <div class="school-header">
            <h1><i class="fas fa-school"></i> Identitas Sekolah</h1>
            <p>Kelola informasi dan identitas sekolah Anda</p>
        </div>

        <div class="school-form">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/school/update') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $school['id'] ?? '' ?>">

                <!-- Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i>
                        Informasi Dasar
                    </div>

                    <div class="row g-2">
                        <div class="col-md-5 mb-2">
                            <label class="form-label">
                                Nama Sekolah
                                <span class="required-mark">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-school"></i></span>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= esc($school['name'] ?? '') ?>" 
                                       placeholder="Contoh: SMA Negeri 1 Jakarta" required>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">
                                Level Sekolah
                                <span class="required-mark">*</span>
                            </label>
                            <select name="level" class="form-select" required>
                                <option value="">Pilih Level</option>
                                <option value="1" <?= ($school['level'] ?? '') == 1 ? 'selected' : '' ?>>
                                    SD
                                </option>
                                <option value="2" <?= ($school['level'] ?? '') == 2 ? 'selected' : '' ?>>
                                    SMP
                                </option>
                                <option value="3" <?= ($school['level'] ?? '') == 3 ? 'selected' : '' ?>>
                                    SMA
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Nama Kepala Sekolah</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                <input type="text" name="headmaster" class="form-control" 
                                       value="<?= esc($school['headmaster'] ?? '') ?>" 
                                       placeholder="Nama Kepala Sekolah">
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Alamat Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea name="address" class="form-control" rows="2" 
                                      placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota"><?= esc($school['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Kontak -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-address-book"></i>
                        Informasi Kontak
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Nomor Telepon</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= esc($school['phone'] ?? '') ?>" 
                                       placeholder="021-12345678">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Sekolah</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= esc($school['email'] ?? '') ?>" 
                                       placeholder="info@sekolah.sch.id">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Logo -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-image"></i>
                        Logo Sekolah
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="logo-preview">
                                <?php if (!empty($school['logo'])): ?>
                                    <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" 
                                         alt="Logo Sekolah" id="logoPreview">
                                    <div class="mt-2">
                                        <small class="text-muted">Logo saat ini</small>
                                    </div>
                                <?php else: ?>
                                    <div class="logo-preview-empty">
                                        <i class="fas fa-image"></i>
                                        <div class="mt-2">
                                            <small class="text-muted">Belum ada logo</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Upload Logo Baru</label>
                            <input type="file" name="logo" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewLogo(event)">
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> 
                                Format: JPG, PNG, GIF, atau WebP. Maksimal 2MB.
                                <br>
                                Rekomendasi: Ukuran 500x500 pixel dengan background transparan (PNG).
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewLogo(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logoPreview');
            if (preview) {
                preview.src = e.target.result;
            } else {
                // Jika belum ada preview, buat elemen baru
                const container = document.querySelector('.logo-preview');
                container.innerHTML = `
                    <img src="${e.target.result}" alt="Logo Preview" id="logoPreview">
                    <div class="mt-2">
                        <small class="text-muted">Preview logo baru</small>
                    </div>
                `;
            }
        }
        reader.readAsDataURL(file);
    }
}
</script>

<?= $this->endSection() ?>