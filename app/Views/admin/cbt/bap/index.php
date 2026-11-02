<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Header & Action Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-file-earmark-check-fill text-primary me-2"></i>Berita Acara Pelaksanaan (BAP) Ujian
            </h4>
            <p class="text-muted small mb-0">
                Pencatatan kehadiran peserta, pengesahan tanda tangan digital pengawas ruang, dan penanganan ketidakhadiran ujian.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-shield-exclamation me-1"></i> Peninjauan Ujian
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewBap">
                <i class="bi bi-plus-circle me-1"></i> Buka BAP Ruang Baru
            </button>
        </div>
    </div>

    <!-- Modal Buka BAP Baru -->
    <div class="modal fade" id="modalNewBap" tabindex="-1" aria-labelledby="modalNewBapLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalNewBapLabel"><i class="bi bi-door-open me-2 text-primary"></i>Pilih Ujian &amp; Ruang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Paket Ujian</label>
                        <select id="selectExamId" class="form-select form-select-sm" required>
                            <option value="">Pilih Ujian...</option>
                            <?php foreach ($exams as $ex): ?>
                                <option value="<?= esc($ex['id']) ?>"><?= esc($ex['name']) ?> (<?= esc($ex['subject'] ?? 'CBT') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Ruang / Kelas</label>
                        <select id="selectRoomCode" class="form-select form-select-sm" required>
                            <option value="">Pilih Ruang...</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= esc($cl['name']) ?>"><?= esc($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnGoToForm">
                        <i class="bi bi-pencil-square me-1"></i> Masuk ke Formulir BAP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text me-1 text-primary"></i> Daftar Berita Acara yang Terdaftar
            </h6>
            <span class="badge bg-light text-dark border"><?= count($documents) ?> Dokumen</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($documents)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted small mt-2 mb-0">Belum ada dokumen Berita Acara yang dibuat.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>No BAP</th>
                                <th>Test ID</th>
                                <th>Ruang</th>
                                <th>Pengawas</th>
                                <th>Kehadiran</th>
                                <th>Status Verifikasi</th>
                                <th>Waktu Pengesahan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td class="fw-mono fw-bold">
                                        <a href="<?= base_url('admin/cbt/bap/view/' . $doc['id']) ?>" class="text-decoration-none">
                                            <?= esc($doc['bap_number']) ?>
                                        </a>
                                    </td>
                                    <td>#<?= esc($doc['test_id']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= esc($doc['room_code']) ?></span></td>
                                    <td><?= esc($doc['proctor_name']) ?></td>
                                    <td>
                                        <span class="text-success fw-bold"><?= esc($doc['total_present']) ?> Hadir</span> / 
                                        <span class="text-danger fw-bold"><?= esc($doc['total_absent']) ?> Absen</span>
                                        <small class="text-muted d-block">(Total: <?= esc($doc['total_registered']) ?>)</small>
                                    </td>
                                    <td>
                                        <?php if ($doc['verification_status'] === 'AUTHENTICATED'): ?>
                                            <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i> AUTHENTICATED</span>
                                        <?php elseif ($doc['verification_status'] === 'PROBABLE'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> DRAFT (PROBABLE)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-pencil me-1"></i> UNVERIFIED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($doc['signed_at']) && $doc['verification_status'] === 'AUTHENTICATED' ? esc($doc['signed_at']) : '<span class="text-muted fst-italic">Belum disahkan</span>' ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/cbt/bap/form/' . $doc['test_id'] . '/' . urlencode($doc['room_code'])) ?>" class="btn btn-outline-primary" title="Buka Formulir">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= base_url('admin/cbt/bap/view/' . $doc['id']) ?>" class="btn btn-outline-secondary" title="Lihat Detail BAP">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnGoToForm = document.getElementById('btnGoToForm');
    const selectExamId = document.getElementById('selectExamId');
    const selectRoomCode = document.getElementById('selectRoomCode');

    btnGoToForm.addEventListener('click', function () {
        const testId = selectExamId.value;
        const roomCode = selectRoomCode.value;

        if (!testId || !roomCode) {
            alert('Silakan pilih Paket Ujian dan Ruang terlebih dahulu.');
            return;
        }

        window.location.href = '<?= base_url('admin/cbt/bap/form') ?>/' + testId + '/' + encodeURIComponent(roomCode);
    });
});
</script>
<?= $this->endSection() ?>

