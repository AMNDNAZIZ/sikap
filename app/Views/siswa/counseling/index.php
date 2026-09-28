<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
                <p class="text-subtitle text-muted">Ajukan sesi konseling/curhat dengan Guru BK.</p>
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

        <!-- Banner Angket Karir -->
        <?php $angketTitle = is_school_sd() ? 'Angket Minat & Bakat' : 'Angket Karir & Bakat'; ?>
        <?php if (empty($careerProfile)): ?>
            <div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-lightbulb-fill fs-3 text-warning"></i>
                <div class="flex-grow-1">
                    <strong>Belum isi <?= $angketTitle ?>!</strong> Bantu Guru BK mengenali rencana dan potensimu dengan mengisi angket.
                </div>
                <a href="<?= base_url('siswa/counseling/career') ?>" class="btn btn-warning btn-sm fw-bold text-nowrap">🎯 Isi Sekarang</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-check-circle-fill fs-3"></i>
                <div class="flex-grow-1">
                    <strong><?= $angketTitle ?> sudah diisi</strong> pada <?= date('d M Y', strtotime($careerProfile['filled_at'])) ?>. Rencana: <strong><?= esc($careerProfile['post_graduate_plan']) ?></strong> — Cita-cita: <?= esc($careerProfile['dream_job'] ?: '-') ?>
                </div>
                <a href="<?= base_url('siswa/counseling/career') ?>" class="btn btn-outline-success btn-sm text-nowrap">Perbarui</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Kolom Form Pengajuan -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title text-white mb-0">Ajukan Janji Temu</h5>
                    </div>
                    <div class="card-body mt-3">
                        <form action="<?= base_url('siswa/counseling/store') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Topik / Keluhan <span class="text-danger">*</span></label>
                                <textarea name="topic" class="form-control" rows="3" placeholder="Ceritakan secara singkat apa yang ingin Anda diskusikan..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Usulan Tanggal & Waktu <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="proposed_date" class="form-control" required>
                                <small class="text-muted">Guru BK mungkin akan menyesuaikan waktu ini.</small>
                            </div>
                            <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                                <i class="bi bi-info-circle me-1"></i> Data dan percakapan Anda dengan Guru BK dijamin <strong>kerahasiaannya</strong>.
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i> Kirim Pengajuan</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kolom Riwayat -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Riwayat Janji Temu Saya</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal Pengajuan</th>
                                        <th>Topik</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($appointments)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada riwayat janji temu.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($appointments as $row): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= date('d M Y', strtotime($row['requested_date'])) ?></strong><br>
                                                    <small class="text-muted"><?= $row['requested_time'] ? date('H:i', strtotime($row['requested_time'])) . ' WIB' : '-' ?></small>
                                                </td>
                                                <td><?= esc($row['topic']) ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'Pending'): ?>
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                                    <?php elseif ($row['status'] == 'Approved'): ?>
                                                        <span class="badge bg-info"><i class="bi bi-check-circle"></i> Disetujui</span>
                                                    <?php elseif ($row['status'] == 'Rejected'): ?>
                                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                                                    <?php elseif ($row['status'] == 'Completed'): ?>
                                                        <span class="badge bg-success"><i class="bi bi-check-all"></i> Selesai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['status'] == 'Pending'): ?>
                                                        <a href="<?= base_url('siswa/counseling/cancel/' . $row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan pengajuan ini?')">Batalkan</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
