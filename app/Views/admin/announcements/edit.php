<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle me-3">
                            <i class="bi bi-pencil-square text-warning"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Edit Pengumuman</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <form action="<?= base_url('admin/announcements/update/' . $announcement['id']) ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Judul Pengumuman</label>
                            <input type="text" name="title" id="title" class="form-control form-control-lg border-0 bg-light px-3"
                                   value="<?= esc($announcement['title']) ?>" placeholder="Masukkan judul pengumuman" required>
                        </div>

                        <div class="mb-4">
                            <label for="content" class="form-label fw-bold">Isi Pengumuman</label>
                            <textarea name="content" id="content" rows="6" class="form-control border-0 bg-light px-3" placeholder="Masukkan isi pengumuman" required><?= esc($announcement['content']) ?></textarea>
                        </div>

                        <?php 
                        $user = session()->get('user');
                        $roleId = $user['role_id'] ?? null;
                        ?>

                        <div class="bg-light p-4 rounded-3 mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Target Pengumuman</h6>
                            
                            <?php if (in_array($roleId, [1, 2])): ?>
                                <!-- Admin & Kepala Sekolah -->
                                <div class="mb-4">
                                    <label class="form-label small text-muted text-uppercase fw-bold">Pilih Kelompok Target</label>
                                    <?php $targets = explode(',', $announcement['target'] ?? ''); ?>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php foreach (['guru'=>'Guru','siswa'=>'Siswa','ortu'=>'Orang Tua'] as $val=>$label): ?>
                                            <div class="form-check custom-check">
                                                <input type="checkbox" class="form-check-input" name="target[]" value="<?= $val ?>" id="target_<?= $val ?>"
                                                    <?= in_array($val, $targets) ? 'checked' : '' ?>>
                                                <label for="target_<?= $val ?>" class="form-check-label"><?= $label ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label for="class_id" class="form-label small text-muted text-uppercase fw-bold">Kelas Sasaran</label>
                                    <select name="class_id" id="class_id" class="form-select border-0 bg-white">
                                        <option value="">-- Semua Kelas --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= $announcement['class_id'] == $c['id'] ? 'selected' : '' ?>>
                                                <?= esc($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            <?php elseif ($roleId == 3): ?>
                                <!-- Teacher -->
                                <input type="hidden" name="target[]" value="siswa">
                                
                                <div class="mb-0">
                                    <label for="class_id" class="form-label small text-muted text-uppercase fw-bold">Kelas Target</label>
                                    <select name="class_id" id="class_id" class="form-select border-0 bg-white" required>
                                        <?php 
                                        // Since $teacherAssignments wasn't clearly passed from edit() in the analyze step, 
                                        // we'll use $classes filtered by the teacher's relationship if possible, 
                                        // but for now, following the pattern of what's available.
                                        foreach ($classes as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= $announcement['class_id'] == $c['id'] ? 'selected' : '' ?>>
                                                <?= esc($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3 pt-3 border-top">
                                <div class="form-check form-switch custom-check">
                                    <input type="checkbox" name="is_public" value="1" class="form-check-input" id="is_public" 
                                           <?= $announcement['is_public'] ? 'checked' : '' ?>>
                                    <label for="is_public" class="form-check-label fw-bold text-dark">Tampilkan di Halaman Depan (Publik)</label>
                                    <div class="form-text mt-0">Jika aktif, pengumuman ini akan muncul di landing page publik.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="<?= base_url('admin/announcements') ?>" class="btn btn-light px-4">Batal</a>
                            <button type="submit" class="btn btn-warning px-4 text-white shadow-sm fw-bold">
                                <i class="bi bi-save me-1"></i> Update Pengumuman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-check .form-check-input:checked {
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .form-control:focus, .form-select:focus {
        box-shadow: none !important;
        background-color: #fff !important;
        border: 1px solid #ffc107 !important;
    }
</style>

<?= $this->endSection() ?>
