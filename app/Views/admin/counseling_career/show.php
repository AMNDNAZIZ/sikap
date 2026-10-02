<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col">
            <a href="<?= base_url('admin/counseling-career') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="row">
        <!-- Info Profil Karir -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Profil Karir: <strong><?= esc($profile['student_name']) ?></strong></h5>
                    <span class="badge bg-secondary"><?= esc($profile['class_name']) ?></span>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Rencana Setelah Lulus</dt>
                        <dd class="col-sm-7"><span class="badge bg-primary fs-6"><?= esc($profile['post_graduate_plan']) ?></span></dd>

                        <dt class="col-sm-5"><?= is_school_sma() ? 'Target Perguruan Tinggi' : 'Target Sekolah Lanjutan' ?></dt>
                        <dd class="col-sm-7"><?= esc($profile['university_target'] ?: '-') ?></dd>

                        <dt class="col-sm-5">Jurusan / Bidang Minat</dt>
                        <dd class="col-sm-7"><?= esc($profile['major_interest'] ?: '-') ?></dd>

                        <dt class="col-sm-5">Cita-Cita Pekerjaan</dt>
                        <dd class="col-sm-7"><strong><?= esc($profile['dream_job'] ?: '-') ?></strong></dd>

                        <dt class="col-sm-5">Pelajaran Favorit</dt>
                        <dd class="col-sm-7"><?= esc($profile['favorite_subject'] ?: '-') ?></dd>

                        <dt class="col-sm-5">Hobi</dt>
                        <dd class="col-sm-7"><?= nl2br(esc($profile['hobby'] ?: '-')) ?></dd>

                        <dt class="col-sm-5">Bakat / Kemampuan</dt>
                        <dd class="col-sm-7"><?= nl2br(esc($profile['talent'] ?: '-')) ?></dd>

                        <dt class="col-sm-5">Penghasilan Orang Tua</dt>
                        <dd class="col-sm-7"><?= esc($profile['family_income'] ?: '-') ?></dd>

                        <dt class="col-sm-5">Dukungan Keluarga</dt>
                        <dd class="col-sm-7">
                            <?= $profile['family_support'] ? '<span class="badge bg-success">Mendukung</span>' : '<span class="badge bg-danger">Tidak Mendukung</span>' ?>
                        </dd>

                        <dt class="col-sm-5">Motivasi & Harapan</dt>
                        <dd class="col-sm-7 fst-italic"><?= nl2br(esc($profile['motivation'] ?: '-')) ?></dd>

                        <dt class="col-sm-5">Diisi Pada</dt>
                        <dd class="col-sm-7"><?= $profile['filled_at'] ? date('d M Y H:i', strtotime($profile['filled_at'])) : '-' ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Catatan Guru BK -->
        <div class="col-md-5 mb-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white"><i class="bi bi-journal-text"></i> Catatan Guru BK</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('admin/counseling-career/save-note/' . $profile['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Rekomendasi & Catatan</label>
                            <textarea name="counselor_note" class="form-control" rows="8" placeholder="Tuliskan rekomendasi, arahan, atau catatan perkembangan karir siswa ini..."><?= esc($profile['counselor_note'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan Catatan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
