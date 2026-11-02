<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/cbt/report') ?>">Laporan Hasil Ujian</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page"><?= esc($exam['name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('admin/cbt/aktivitas/analisis/' . $exam['id']) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-bar-chart-steps me-1"></i> Analisis Penguasaan TP
            </a>
            <button type="button" class="btn btn-success btn-sm" id="btnOpenBatchPublish">
                <i class="bi bi-send-check me-1"></i> Kirim Nilai Massal Siswa Memenuhi Syarat
            </button>
            <a href="<?= base_url('admin/cbt/report') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
            </a>
        </div>
    </div>

    <!-- Exam Summary Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <span class="badge bg-primary px-2 py-1 mb-2">Paket Ujian #<?= esc($exam['id']) ?></span>
                    <h4 class="fw-bold mb-1 text-dark"><?= esc($exam['name']) ?></h4>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-book me-1"></i> Mata Pelajaran: <strong><?= esc($exam['subject'] ?? 'CBT') ?></strong>
                        <span class="mx-2">·</span>
                        <i class="bi bi-people me-1"></i> Peserta Mengerjakan: <strong><?= count($results) ?> Siswa</strong>
                    </p>
                </div>
                <div class="text-md-end">
                    <span class="text-muted small d-block mb-1">Status Aturan Penilaian:</span>
                    <span class="badge bg-success px-2 py-1 me-1">Penilaian Esai Lengkap</span>
                    <span class="badge bg-info text-dark px-2 py-1">Aturan Akademik Terpenuhi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Results Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table me-1 text-primary"></i> Rincian Nilai Siswa &amp; Kelayakan Buku Nilai
            </h6>
            <span class="badge bg-light text-dark border"><?= count($results) ?> Sesi</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($results)): ?>
                <div class="text-center py-5 text-muted small">
                    Belum ada sesi pengerjaan ujian untuk paket ini.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Sesi</th>
                                <th>Nama &amp; ID Siswa</th>
                                <th>Nilai PG &amp; BS</th>
                                <th>Nilai Esai</th>
                                <th>Nilai Akhir</th>
                                <th>Kelayakan Nilai</th>
                                <th>Status Buku Nilai</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td class="fw-mono">#<?= esc($row['session_id']) ?></td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= esc($row['student_name'] ?? ('Siswa #' . $row['student_id'])) ?></span>
                                        <small class="text-muted" style="font-size: 0.72rem;">
                                            NISN: <?= esc($row['nisn'] ?? '-') ?> · ID: <?= esc($row['student_id']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold"><?= esc($row['score_pg'] ?? 0) ?></span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            (PG: <?= esc($row['score_pg'] ?? 0) ?> · BS: <?= esc($row['score_bs'] ?? 0) ?>)
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ((int)($row['pending_essay_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i> Menunggu Penilaian (<?= esc($row['pending_essay_count']) ?>)</span>
                                        <?php elseif ($row['score_esai'] !== null): ?>
                                            <span class="fw-bold text-primary"><?= esc($row['score_esai']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Tanpa Esai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['final_score'] !== null): ?>
                                            <span class="fs-6 fw-bold text-success"><?= esc($row['final_score']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i> Ditahan</span>
                                            <?php if ((int)($row['pending_essay_count'] ?? 0) > 0): ?>
                                                <small class="text-warning d-block" style="font-size: 0.68rem;"><i class="bi bi-hourglass-split me-1"></i>Menunggu Penilaian Esai</small>
                                            <?php elseif (!empty($row['session_status']) && in_array(strtoupper($row['session_status']), ['ABSENT', 'EXCUSED', 'DISPENSED', 'SAKIT', 'IZIN'], true)): ?>
                                                <small class="text-muted d-block" style="font-size: 0.68rem;"><i class="bi bi-person-x me-1"></i>Siswa Tidak Hadir (<?= esc(strtoupper($row['session_status'])) ?>)</small>
                                            <?php else: ?>
                                                <small class="text-muted d-block" style="font-size: 0.68rem;"><i class="bi bi-info-circle me-1"></i>Menunggu Verifikasi</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['publication_eligibility'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> SIAP DIKIRIM</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> BELUM LENGKAP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['pub_status'] === 'PUBLISHED'): ?>
                                            <span class="badge bg-primary"><i class="bi bi-check2-all me-1"></i> TERKIRIM</span>
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.68rem;"><?= esc($row['target_table']) ?>: <?= esc($row['published_score']) ?></small>
                                        <?php elseif ($row['pub_status'] === 'REVISED'): ?>
                                            <span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat me-1"></i> DIPERBARUI</span>
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.68rem;"><?= esc($row['target_table']) ?>: <?= esc($row['published_score']) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Belum Terkirim</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['publication_eligibility']) && $row['final_score'] !== null): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm btn-publish"
                                                    data-session-id="<?= esc($row['session_id']) ?>"
                                                    data-student-id="<?= esc($row['student_id']) ?>"
                                                    data-test-id="<?= esc($exam['id']) ?>"
                                                    data-final-score="<?= esc($row['final_score']) ?>">
                                                <i class="bi bi-send me-1"></i> Kirim ke Buku Nilai
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary btn-sm disabled" title="Nilai belum difinalisasi atau esai belum dinilai">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal Publish Gradebook -->
<div class="modal fade" id="modalPublish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-arrow-up text-success me-2"></i>Kirim Nilai Siswa ke Buku Nilai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPublish">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" id="pubSessionId">
                <input type="hidden" name="student_id" id="pubStudentId">
                <input type="hidden" name="test_id" id="pubTestId">

                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        <span class="d-block">Sesi: <strong id="lblSessionId">-</strong> · Siswa: <strong id="lblStudentId">-</strong></span>
                        <span class="d-block">Nilai Akhir yang Dikirim: <strong class="text-success fs-5" id="lblFinalScore">-</strong></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Buku Nilai Sasaran</label>
                        <select name="target_table" class="form-select form-select-sm" required>
                            <option value="summative_scores">Nilai Asesmen Sumatif (Harian / Materi)</option>
                            <option value="final_exam_scores">Nilai Asesmen Akhir Semester (SAS / UAS)</option>
                            <option value="pts_scores">Nilai Asesmen Tengah Semester (STS / PTS)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ID Mata Pelajaran</label>
                            <input type="number" name="subject_id" class="form-control form-control-sm" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ID Tahun Ajaran</label>
                            <input type="number" name="year_id" class="form-control form-control-sm" value="1" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" class="form-select form-select-sm">
                                <option value="1">Semester 1 (Ganjil)</option>
                                <option value="2">Semester 2 (Genap)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jenis Penilaian</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="tulis">Tes Tertulis</option>
                                <option value="penugasan">Penugasan / Portofolio</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnSubmitPub">
                        <i class="bi bi-check2-circle me-1"></i> Kirim Nilai Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Batch Publish Gradebook -->
<div class="modal fade" id="modalBatchPublish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-send-check text-success me-2"></i>Kirim Nilai Massal ke Buku Nilai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formBatchPublish">
                <?= csrf_field() ?>
                <input type="hidden" name="test_id" value="<?= esc($exam['id']) ?>">

                <div class="modal-body">
                    <div class="alert alert-info border small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Sistem hanya akan mengirim nilai siswa yang berstatus <strong>SIAP DIKIRIM</strong> (seluruh esai telah dinilai dan skor lengkap). Siswa yang esainya masih belum dinilai atau tidak hadir akan otomatis dilewati dengan aman.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Buku Nilai Sasaran</label>
                        <select name="target_table" class="form-select form-select-sm" required>
                            <option value="summative_scores">Nilai Asesmen Sumatif (Harian / Materi)</option>
                            <option value="final_exam_scores">Nilai Asesmen Akhir Semester (SAS / UAS)</option>
                            <option value="pts_scores">Nilai Asesmen Tengah Semester (STS / PTS)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ID Mata Pelajaran</label>
                            <input type="number" name="subject_id" class="form-control form-control-sm" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ID Tahun Ajaran</label>
                            <input type="number" name="year_id" class="form-control form-control-sm" value="1" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" class="form-select form-select-sm">
                                <option value="1">Semester 1 (Ganjil)</option>
                                <option value="2">Semester 2 (Genap)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jenis Penilaian</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="tulis">Tes Tertulis</option>
                                <option value="penugasan">Penugasan / Portofolio</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch small">
                        <input class="form-check-input" type="checkbox" name="allow_override" value="1" id="batchAllowOverride">
                        <label class="form-check-label" for="batchAllowOverride">Perbarui nilai jika nilai ujian siswa sudah pernah dikirimkan sebelumnya</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnSubmitBatchPub">
                        <i class="bi bi-send-check me-1"></i> Mulai Pengiriman Massal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalPublish      = new bootstrap.Modal(document.getElementById('modalPublish'));
    const modalBatchPublish = new bootstrap.Modal(document.getElementById('modalBatchPublish'));
    const formPublish       = document.getElementById('formPublish');
    const formBatchPublish  = document.getElementById('formBatchPublish');
    const btnSubmitPub      = document.getElementById('btnSubmitPub');
    const btnSubmitBatchPub = document.getElementById('btnSubmitBatchPub');
    const btnOpenBatch      = document.getElementById('btnOpenBatchPublish');

    if (btnOpenBatch) {
        btnOpenBatch.addEventListener('click', function () {
            modalBatchPublish.show();
        });
    }

    document.querySelectorAll('.btn-publish').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('pubSessionId').value = this.getAttribute('data-session-id');
            document.getElementById('pubStudentId').value = this.getAttribute('data-student-id');
            document.getElementById('pubTestId').value    = this.getAttribute('data-test-id');
            document.getElementById('lblSessionId').textContent  = '#' + this.getAttribute('data-session-id');
            document.getElementById('lblStudentId').textContent  = 'Siswa #' + this.getAttribute('data-student-id');
            document.getElementById('lblFinalScore').textContent = this.getAttribute('data-final-score');
            modalPublish.show();
        });
    });

    formPublish.addEventListener('submit', function (e) {
        e.preventDefault();
        btnSubmitPub.disabled = true;
        btnSubmitPub.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim ke Buku Nilai...';

        const formData = new FormData(formPublish);

        fetch('<?= base_url('admin/cbt/report/publish') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            btnSubmitPub.disabled = false;
            btnSubmitPub.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Kirim Nilai Sekarang';
            if (data.success) {
                alert(data.message);
                modalPublish.hide();
                window.location.reload();
            } else {
                alert('Pengiriman Nilai Belum Berhasil: ' + (data.message || 'Terjadi kesalahan sistem'));
            }
        })
        .catch(err => {
            btnSubmitPub.disabled = false;
            btnSubmitPub.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Kirim Nilai Sekarang';
            alert('Gangguan koneksi internet: ' + err.message);
        });
    });

    formBatchPublish.addEventListener('submit', function (e) {
        e.preventDefault();
        btnSubmitBatchPub.disabled = true;
        btnSubmitBatchPub.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses Pengiriman Massal...';

        const formData = new FormData(formBatchPublish);

        fetch('<?= base_url('admin/cbt/report/publish-batch') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            btnSubmitBatchPub.disabled = false;
            btnSubmitBatchPub.innerHTML = '<i class="bi bi-send-check me-1"></i> Mulai Pengiriman Massal';
            if (data.success) {
                alert(data.message);
                modalBatchPublish.hide();
                window.location.reload();
            } else {
                alert('Pengiriman Massal Belum Berhasil: ' + (data.message || 'Terjadi kesalahan sistem'));
            }
        })
        .catch(err => {
            btnSubmitBatchPub.disabled = false;
            btnSubmitBatchPub.innerHTML = '<i class="bi bi-send-check me-1"></i> Mulai Pengiriman Massal';
            alert('Gangguan koneksi internet: ' + err.message);
        });
    });
});
</script>
<?= $this->endSection() ?>

