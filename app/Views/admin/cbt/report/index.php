<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Header Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Laporan Hasil Ujian CBT
            </h4>
            <p class="text-muted small mb-0">
                Pusat evaluasi penilaian ujian CBT, pemeriksaan kelayakan nilai esai, dan penyelarasan langsung ke Buku Nilai Siswa.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/cbt/bap') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-check me-1"></i> Berita Acara (BAP)
            </a>
            <a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-shield-exclamation me-1"></i> Peninjauan Ujian
            </a>
        </div>
    </div>

    <!-- Exam List Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text me-1 text-primary"></i> Daftar Paket Ujian CBT
            </h6>
            <span class="badge bg-light text-dark border"><?= count($exams) ?> Paket Ujian</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($exams)): ?>
                <div class="text-center py-5 text-muted small">
                    Belum ada paket ujian CBT yang terdaftar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Ujian</th>
                                <th>Mata Pelajaran</th>
                                <th>Tipe Asesmen</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $ex): ?>
                                <tr>
                                    <td class="fw-mono">#<?= esc($ex['id']) ?></td>
                                    <td class="fw-bold">
                                        <a href="<?= base_url('admin/cbt/report/detail/' . $ex['id']) ?>" class="text-decoration-none text-dark">
                                            <?= esc($ex['name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= esc($ex['subject'] ?? 'CBT') ?></span></td>
                                    <td><span class="badge bg-secondary"><?= esc($ex['type'] ?? 'Sumatif / CBT') ?></span></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/cbt/report/detail/' . $ex['id']) ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye me-1"></i> Buka Rekap Hasil
                                        </a>
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
<?= $this->endSection() ?>

