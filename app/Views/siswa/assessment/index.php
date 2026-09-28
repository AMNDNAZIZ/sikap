<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
                <p class="text-subtitle text-muted">Daftar instrumen asesmen dan angket yang harus Anda isi.</p>
            </div>
        </div>
    </div>

    <section class="section">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($assessments)): ?>
                <div class="col-12">
                    <div class="alert alert-light text-center py-5">
                        <i class="bi bi-card-checklist fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Tidak ada asesmen yang ditugaskan saat ini.</h5>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($assessments as $a): ?>
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm border-0 <?= $a['status'] === 'COMPLETED' ? 'border-start border-success border-5' : 'border-start border-primary border-5' ?> mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light-primary text-primary"><?= esc($a['assessment_type']) ?></span>
                                    <?php if ($a['status'] === 'COMPLETED'): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php elseif ($a['status'] === 'IN_PROGRESS'): ?>
                                        <span class="badge bg-warning text-dark">Sedang Dikerjakan</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Belum Mulai</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title fw-bold"><?= esc($a['title']) ?></h5>
                                <p class="card-text text-muted small mb-3"><?= esc($a['description']) ?></p>
                                
                                <div class="d-flex justify-content-between align-items-end mt-3 pt-3 border-top">
                                    <div>
                                        <small class="text-muted d-block">Batas Waktu:</small>
                                        <span class="fw-bold <?= strtotime($a['end_date'].' 23:59:59') < time() && $a['status'] !== 'COMPLETED' ? 'text-danger' : 'text-dark' ?>">
                                            <?= date('d M Y', strtotime($a['end_date'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($a['status'] === 'COMPLETED'): ?>
                                            <button class="btn btn-sm btn-outline-success" disabled><i class="bi bi-check-circle"></i> Selesai</button>
                                        <?php elseif (strtotime($a['end_date'].' 23:59:59') < time()): ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Waktu Habis</button>
                                        <?php else: ?>
                                            <a href="<?= base_url('siswa/asesmen/fill/' . $a['assignment_student_id']) ?>" class="btn btn-sm <?= $a['status'] === 'IN_PROGRESS' ? 'btn-warning' : 'btn-primary' ?> px-3 rounded-pill fw-bold">
                                                <?= $a['status'] === 'IN_PROGRESS' ? 'Lanjutkan' : 'Mulai Mengisi' ?> <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
