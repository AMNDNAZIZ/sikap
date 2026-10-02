<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h4 mb-1">📋 <?= is_school_sd() ? 'Angket Penelusuran Minat & Bakat' : 'Angket Penelusuran Karir & Minat Bakat' ?></h1>
            <p class="text-muted small">Isi angket ini untuk membantu Guru BK memahami rencana dan potensi kamu <?= is_school_sd() ? 'untuk jenjang selanjutnya' : 'setelah lulus' ?>. Data ini bersifat rahasia.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <?php if ($careerProfile): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title">Perbarui Angket</h5>
                    <span class="badge bg-success">Sudah diisi pada <?= date('d M Y', strtotime($careerProfile['filled_at'])) ?></span>
                </div>
            <?php else: ?>
                <h5 class="mb-0 card-title">Isi Angket</h5>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form action="<?= base_url('siswa/counseling/save-career') ?>" method="post">
                <?= csrf_field() ?>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">🎓 Rencana Setelah Lulus</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Rencana Setelah Lulus <span class="text-danger">*</span></label>
                        <select name="post_graduate_plan" class="form-select" id="planSelect" required>
                            <option value="">-- Pilih Rencana --</option>
                            <?php 
                                $plans = ['Kuliah', 'Kerja', 'Wirausaha', 'Kursus/Diklat', 'Belum Tahu'];
                                if (is_school_smp()) {
                                    $plans = ['SMA', 'SMK', 'MA', 'Pesantren', 'Kerja', 'Belum Tahu'];
                                } elseif (is_school_sd()) {
                                    $plans = ['SMP', 'MTs', 'Pesantren', 'Belum Tahu'];
                                }
                                foreach($plans as $p): 
                            ?>
                                <option value="<?= $p ?>" <?= ($careerProfile['post_graduate_plan'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="universityBox">
                        <label class="form-label fw-semibold">
                            <?= is_school_sma() ? 'Target Perguruan Tinggi / Tempat Kerja' : 'Target Sekolah Lanjutan' ?>
                        </label>
                        <input type="text" name="university_target" class="form-control" placeholder="<?= is_school_sma() ? 'Contoh: UI, ITB, UGM / PT Astra...' : 'Contoh: SMAN 1, SMPN 2, dll...' ?>" value="<?= esc($careerProfile['university_target'] ?? '') ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jurusan / Bidang yang Diminati</label>
                        <input type="text" name="major_interest" class="form-control" placeholder="Contoh: IPA, IPS, Teknik Informatika..." value="<?= esc($careerProfile['major_interest'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cita-Cita Pekerjaan</label>
                        <input type="text" name="dream_job" class="form-control" placeholder="Contoh: Dokter, Programmer, Wirausaha..." value="<?= esc($careerProfile['dream_job'] ?? '') ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">💡 Minat & Bakat</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hobi / Aktivitas yang Disukai</label>
                        <textarea name="hobby" class="form-control" rows="3" placeholder="Ceritakan hobi dan aktivitas yang kamu nikmati..."><?= esc($careerProfile['hobby'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kemampuan / Bakat yang Kamu Miliki</label>
                        <textarea name="talent" class="form-control" rows="3" placeholder="Contoh: Bermain musik, desain grafis, public speaking..."><?= esc($careerProfile['talent'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mata Pelajaran Favorit</label>
                    <input type="text" name="favorite_subject" class="form-control" placeholder="Contoh: Matematika, Biologi, Bahasa Inggris..." value="<?= esc($careerProfile['favorite_subject'] ?? '') ?>">
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">🏠 Kondisi & Dukungan Keluarga</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Perkiraan Penghasilan Orang Tua/Wali per Bulan</label>
                        <select name="family_income" class="form-select">
                            <option value="">-- Pilih --</option>
                            <?php foreach(['< Rp 1.000.000', 'Rp 1.000.000 - 3.000.000', 'Rp 3.000.000 - 5.000.000', '> Rp 5.000.000'] as $inc): ?>
                                <option value="<?= $inc ?>" <?= ($careerProfile['family_income'] ?? '') === $inc ? 'selected' : '' ?>><?= $inc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="family_support" id="familySupport" value="1" <?= ($careerProfile['family_support'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="familySupport">
                                Orang tua / wali saya mendukung rencana karir saya
                            </label>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">✨ Motivasi & Harapan</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tuliskan motivasi dan harapanmu ke depan</label>
                    <textarea name="motivation" class="form-control" rows="4" placeholder="Ceritakan impian, motivasi, dan harapanmu untuk masa depan..."><?= esc($careerProfile['motivation'] ?? '') ?></textarea>
                </div>

                <?php if ($careerProfile && !empty($careerProfile['counselor_note'])): ?>
                    <div class="alert alert-info mt-3">
                        <strong><i class="bi bi-chat-quote"></i> Catatan dari Guru BK:</strong><br>
                        <?= nl2br(esc($careerProfile['counselor_note'])) ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send"></i> Simpan Angket</button>
                    <a href="<?= base_url('siswa/counseling') ?>" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const planSelect = document.getElementById('planSelect');
    const universityBox = document.getElementById('universityBox');
    function toggleUniversityBox() {
        universityBox.style.display = planSelect.value === 'Kuliah' ? 'block' : 'none';
    }
    planSelect.addEventListener('change', toggleUniversityBox);
    toggleUniversityBox();
</script>

<?= $this->endSection() ?>
