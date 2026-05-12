<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-lg-8 shadow-sm">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                            <i class="bi bi-megaphone-fill text-primary"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Tambah Pengumuman</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/announcements/store') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Judul Pengumuman</label>
                            <input type="text" name="title" id="title"
                                class="form-control form-control-lg border-0 bg-light px-3"
                                placeholder="Masukkan judul pengumuman yang jelas" required>
                        </div>

                        <div class="mb-4">
                            <label for="content" class="form-label fw-bold">Isi Pengumuman</label>
                            <textarea name="content" id="content" rows="6" class="form-control border-0 bg-light px-3"
                                placeholder="Tuliskan isi detail pengumuman di sini..." required></textarea>
                        </div>

                        <?php
                        $user = session()->get('user');
                        $roleId = $user['role_id'] ?? null;
                        ?>

                        <div class="bg-light p-4 rounded-3 mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Target Pengumuman</h6>

                            <?php if ($roleId == 1 || $roleId == 2): ?>
                                <!-- Admin / Principal -->
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label small text-muted text-uppercase fw-bold">Pilih Kelompok
                                            Target</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php foreach (['guru' => 'Guru', 'kepala' => 'Kepala Sekolah', 'ortu' => 'Orang Tua', 'siswa' => 'Siswa'] as $val => $label): ?>
                                                <div class="form-check custom-check">
                                                    <input type="checkbox" name="target[]" value="<?= $val ?>"
                                                        class="form-check-input" id="t-<?= $val ?>">
                                                    <label for="t-<?= $val ?>" class="form-check-label"><?= $label ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label for="class_id" class="form-label small text-muted text-uppercase fw-bold">Pilih
                                        Kelas Khusus (Opsional)</label>
                                    <select name="class_id[]" id="class_id" class="form-select select2" multiple>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text mt-1">Biarkan kosong jika pengumuman untuk semua kelas.</div>
                                </div>

                            <?php elseif ($roleId == 3): ?>
                                <!-- Teacher -->
                                <?php if ($isHomeroom && $teacherClass): ?>
                                    <!-- Homeroom Teacher -->
                                    <input type="hidden" name="target[]" value="siswa">
                                    <input type="hidden" name="class_id[]" value="<?= $teacherClass['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-2 rounded me-3 text-success">
                                            <i class="bi bi-door-open-fill"></i>
                                        </div>
                                        <div>
                                            <div class="text-uppercase small fw-bold text-muted" style="letter-spacing: 0.5px;">
                                                Siswa Target</div>
                                            <div class="fw-bold fs-5 text-dark">Siswa Kelas <?= esc($teacherClass['name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif (!empty($teachingClasses)): ?>
                                    <!-- Subject Teacher -->
                                    <input type="hidden" name="target[]" value="siswa">
                                    <div class="mb-0">
                                        <label for="class_id" class="form-label small text-muted text-uppercase fw-bold">Pilih
                                            Kelas Target</label>
                                        <select name="class_id[]" id="class_id" class="form-select select2" multiple required>
                                            <?php foreach ($teachingClasses as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="mt-3 pt-3 border-top">
                                <div class="form-check form-switch custom-check">
                                    <input type="checkbox" name="is_public" value="1" class="form-check-input"
                                        id="is_public">
                                    <label for="is_public" class="form-check-label fw-bold">Tampilkan di Halaman Depan
                                        (Publik)</label>
                                    <div class="form-text mt-0">Jika aktif, pengumuman ini akan muncul di landing page
                                        publik.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="<?= base_url('admin/announcements') ?>" class="btn btn-light px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-send me-1"></i> Simpan Pengumuman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Select2 & Styles -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple {
        border: none !important;
        background-color: #f8f9fa !important;
        min-height: 45px;
        padding: 5px;
    }

    .custom-check .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .form-control:focus,
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        box-shadow: none !important;
        background-color: #fff !important;
        border: 1px solid #0d6efd !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Pilih kelas sasaran...",
            allowClear: true,
            width: '100%'
        });
    });
</script>

<?= $this->endSection() ?>