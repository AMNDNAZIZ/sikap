<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-person-lines-fill text-warning me-2"></i>Perencanaan Individual Siswa</h3>
            <p class="text-muted mb-0">Pemetaan Potensi, Minat, Target Perkembangan, &amp; Goal Setting Siswa</p>
        </div>
        <div>
            <button class="btn btn-warning text-dark rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBuatTarget">
                <i class="bi bi-plus-circle me-1"></i> Buat Target Individual
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <?php if (empty($plans)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-person-slash fs-1 d-block mb-3 text-secondary"></i>
                    <h5 class="fw-bold">Belum Ada Perencanaan Individual</h5>
                    <p>Klik <strong>Buat Target Individual</strong> untuk menetapkan tujuan dan potensi siswa.</p>
                    <button class="btn btn-warning text-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalBuatTarget">
                        <i class="bi bi-plus-circle me-1"></i> Buat Target Individual
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Gaya Belajar</th>
                                <th>Target Karir / PTN</th>
                                <th>Target Akademik</th>
                                <th style="min-width:130px;">Progress</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $i => $p): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-bold text-dark"><?= esc($p['student_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= esc($p['class_name'] ?: '-') ?></span></td>
                                <td><span class="badge bg-info-subtle text-info"><?= esc($p['learning_style'] ?: 'Belum Diisi') ?></span></td>
                                <td><?= esc($p['career_target'] ?: '-') ?></td>
                                <td><?= esc($p['academic_target'] ?: '-') ?></td>
                                <td>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <div class="progress-bar bg-warning" role="progressbar"
                                            style="width: <?= $p['progress_percent'] ?>%"
                                            aria-valuenow="<?= $p['progress_percent'] ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted fw-bold"><?= $p['progress_percent'] ?>% Tercapai</small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning text-dark rounded-pill px-2 me-1"
                                        title="Kelola Goal"
                                        onclick="editPlan(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success rounded-pill px-2 me-1"
                                        title="Update Progress"
                                        onclick="updateProgress(<?= $p['id'] ?>, <?= $p['progress_percent'] ?>)">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </button>
                                    <form action="<?= base_url('admin/bk/perencanaan-individual/delete/' . $p['id']) ?>" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus perencanaan individual ini?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
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

<!-- ===== MODAL: BUAT / EDIT TARGET INDIVIDUAL ===== -->
<div class="modal fade" id="modalBuatTarget" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <form id="formTarget" action="<?= base_url('admin/bk/perencanaan-individual/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i><span id="targetModalTitle">Buat Target Individual Siswa</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12" id="studentSelectGroup">
                            <label class="form-label fw-semibold">Pilih Siswa <span class="text-danger">*</span></label>
                            <select class="form-select" name="student_id" id="studentSelect" required>
                                <option value="">-- Cari dan Pilih Siswa --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> - <?= esc($s['class_name'] ?? '') ?> (<?= esc($s['nisn'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="fw-bold text-muted small text-uppercase">Profil Potensi & Minat</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kekuatan / Potensi Diri</label>
                            <textarea class="form-control" name="strengths" id="fStrengths" rows="3"
                                placeholder="Misal: Kemampuan analitis, komunikasi yang baik, kreatif..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Area yang Perlu Dikembangkan</label>
                            <textarea class="form-control" name="growth_areas" id="fGrowthAreas" rows="3"
                                placeholder="Misal: Manajemen waktu, kepercayaan diri..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Minat &amp; Hobi</label>
                            <input type="text" class="form-control" name="interests" id="fInterests"
                                placeholder="Misal: Teknologi, Seni, Olahraga">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gaya Belajar</label>
                            <select class="form-select" name="learning_style" id="fLearningStyle">
                                <option value="">-- Pilih --</option>
                                <option value="Visual">Visual</option>
                                <option value="Auditori">Auditori</option>
                                <option value="Kinestetik">Kinestetik</option>
                                <option value="Membaca/Menulis">Membaca / Menulis</option>
                                <option value="Multimodal">Multimodal</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="fw-bold text-muted small text-uppercase">Target & Tujuan</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Target Karir / PTN Impian</label>
                            <input type="text" class="form-control" name="career_target" id="fCareerTarget"
                                placeholder="Misal: Dokter, Teknik Informatika UI">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Target Akademik</label>
                            <input type="text" class="form-control" name="academic_target" id="fAcademicTarget"
                                placeholder="Misal: Nilai rata-rata 85, Juara Olimpiade Matematika">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Target Kebiasaan Positif</label>
                            <input type="text" class="form-control" name="habit_target" id="fHabitTarget"
                                placeholder="Misal: Belajar 2 jam/hari, tidak menunda tugas">
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="fw-bold text-muted small text-uppercase">Catatan Guru BK</h6></div>
                        <div class="col-12">
                            <textarea class="form-control" name="counselor_notes" id="fCounselorNotes" rows="3"
                                placeholder="Catatan khusus Guru BK mengenai siswa ini (bersifat rahasia, hanya dapat dilihat oleh Guru BK &amp; Admin)..."></textarea>
                            <div class="form-text"><i class="bi bi-lock-fill text-warning me-1"></i>Catatan ini bersifat rahasia.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Target
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL: UPDATE PROGRESS ===== -->
<div class="modal fade" id="modalProgress" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded-4">
            <form id="formProgress" action="" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-graph-up-arrow me-2"></i>Update Progress</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label fw-semibold">Persentase Tercapai</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="progress_percent" id="progressInput"
                            min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function editPlan(p) {
    document.getElementById('targetModalTitle').innerText = 'Edit Target Individual: ' + p.student_name;
    document.getElementById('formTarget').action = '<?= base_url('admin/bk/perencanaan-individual/update') ?>/' + p.id;
    
    // Hide student selector (can't change student on edit)
    document.getElementById('studentSelectGroup').style.display = 'none';
    document.getElementById('studentSelect').removeAttribute('required');

    document.getElementById('fStrengths').value     = p.strengths || '';
    document.getElementById('fGrowthAreas').value   = p.growth_areas || '';
    document.getElementById('fInterests').value     = p.interests || '';
    document.getElementById('fLearningStyle').value = p.learning_style || '';
    document.getElementById('fCareerTarget').value  = p.career_target || '';
    document.getElementById('fAcademicTarget').value= p.academic_target || '';
    document.getElementById('fHabitTarget').value   = p.habit_target || '';
    document.getElementById('fCounselorNotes').value= p.counselor_notes || '';

    new bootstrap.Modal(document.getElementById('modalBuatTarget')).show();
}

function updateProgress(planId, currentVal) {
    document.getElementById('formProgress').action = '<?= base_url('admin/bk/perencanaan-individual/progress') ?>/' + planId;
    document.getElementById('progressInput').value = currentVal;
    new bootstrap.Modal(document.getElementById('modalProgress')).show();
}

// Reset modal ketika ditutup
document.getElementById('modalBuatTarget').addEventListener('hidden.bs.modal', function () {
    document.getElementById('targetModalTitle').innerText = 'Buat Target Individual Siswa';
    document.getElementById('formTarget').action = '<?= base_url('admin/bk/perencanaan-individual/store') ?>';
    document.getElementById('formTarget').reset();
    document.getElementById('studentSelectGroup').style.display = 'block';
    document.getElementById('studentSelect').setAttribute('required', 'required');
});
</script>
<?= $this->endSection() ?>
