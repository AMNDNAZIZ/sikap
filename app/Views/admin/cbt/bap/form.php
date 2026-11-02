<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/cbt/bap') ?>">Digital BAP</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page"><?= esc($document['bap_number']) ?></li>
            </ol>
        </nav>
        <a href="<?= base_url('admin/cbt/bap') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar BAP
        </a>
    </div>

    <!-- Document Info Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary px-2 py-1 fw-bold"><?= esc($document['bap_number']) ?></span>
                        <span class="badge bg-light text-dark border">Ruang: <?= esc($document['room_code']) ?></span>
                        <?php if ($document['verification_status'] === 'AUTHENTICATED'): ?>
                            <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i> AUTHENTICATED (TERKUNCI)</span>
                        <?php elseif ($document['verification_status'] === 'PROBABLE'): ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> DRAFT (PROBABLE)</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-pencil me-1"></i> DRAFT (UNVERIFIED)</span>
                        <?php endif; ?>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark"><?= esc($exam['name'] ?? 'Ujian CBT') ?></h4>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-book me-1"></i> Mapel: <strong><?= esc($exam['subject'] ?? 'CBT') ?></strong>
                        <span class="mx-2">·</span>
                        <i class="bi bi-person-badge me-1"></i> Pengawas: <strong><?= esc($document['proctor_name']) ?></strong>
                        <span class="mx-2">·</span>
                        <i class="bi bi-clock me-1"></i> Tanggal: <strong><?= date('d M Y') ?></strong>
                    </p>
                </div>
                <div class="text-md-end">
                    <span class="text-muted small d-block">Status Dokumen:</span>
                    <span class="fs-6 fw-bold <?= $document['verification_status'] === 'AUTHENTICATED' ? 'text-success' : 'text-warning' ?>">
                        <?= esc($document['verification_status']) ?>
                    </span>
                    <?php if ($document['verification_status'] === 'AUTHENTICATED'): ?>
                        <small class="text-muted d-block font-monospace" style="font-size: 0.7rem;">
                            SHA-256: <?= substr(esc($document['document_sha256']), 0, 16) ?>...
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Discrepancy Alert Container -->
    <div id="discrepancyAlertContainer" class="mb-4" style="display: none;"></div>

    <!-- Attendance Form -->
    <form id="bapForm">
        <?= csrf_field() ?>
        <input type="hidden" name="bap_id" value="<?= esc($document['id']) ?>">

        <div class="row g-4">
            <!-- Left Column: Student Attendance Table -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-people-fill me-1 text-primary"></i> Presensi Kehadiran Siswa
                        </h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-info btn-sm" id="btnCheckDiscrepancy">
                                <i class="bi bi-search me-1"></i> Cek Anomali Telemetri
                            </button>
                            <?php if ($document['verification_status'] !== 'AUTHENTICATED'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" id="btnMarkAllPresent">
                                    <i class="bi bi-check2-all me-1"></i> Tandai Semua Hadir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 25%;">Nama Siswa</th>
                                        <th style="width: 45%;">Status Kehadiran</th>
                                        <th style="width: 25%;">Catatan Khusus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                Tidak ada data siswa terdaftar untuk ruang ujian ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($students as $st): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++ ?></td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block">Siswa #<?= esc($st['student_id']) ?></span>
                                                    <small class="text-muted">ID: <?= esc($st['student_id']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php 
                                                            $statuses = ['HADIR', 'TIDAK_HADIR', 'SAKIT', 'IZIN', 'DISPENSASI'];
                                                            $currStatus = $st['reported_status'] ?? 'HADIR';
                                                            $isLocked = ($document['verification_status'] === 'AUTHENTICATED');
                                                        ?>
                                                        <?php foreach ($statuses as $stat): ?>
                                                            <input type="radio" 
                                                                   class="btn-check status-radio" 
                                                                   name="attendance[<?= esc($st['student_id']) ?>][status]" 
                                                                   id="status_<?= esc($st['student_id']) ?>_<?= $stat ?>" 
                                                                   value="<?= $stat ?>" 
                                                                   <?= $currStatus === $stat ? 'checked' : '' ?>
                                                                   <?= $isLocked ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-<?= $stat === 'HADIR' ? 'success' : ($stat === 'TIDAK_HADIR' ? 'danger' : 'warning') ?> py-1 px-2 text-uppercase" 
                                                                   for="status_<?= esc($st['student_id']) ?>_<?= $stat ?>" 
                                                                   style="font-size: 0.72rem;">
                                                                <?= $stat ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="attendance[<?= esc($st['student_id']) ?>][notes]" 
                                                           class="form-control form-control-sm" 
                                                           placeholder="Catatan..." 
                                                           value="<?= esc($st['proctor_notes'] ?? '') ?>"
                                                           <?= $isLocked ? 'readonly' : '' ?>>
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

            <!-- Right Column: Supervisor Info, Incidents & Actions -->
            <div class="col-12 col-lg-4">
                <!-- Implementation Details Card -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clipboard2-check me-1 text-primary"></i> Data Pelaksanaan
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Nama Pengawas Utama</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="<?= esc($document['proctor_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Nama Pengawas Pendamping / Supervisor</label>
                            <input type="text" 
                                   name="supervisor_name" 
                                   class="form-control form-control-sm" 
                                   value="<?= esc($document['supervisor_name'] ?? '') ?>" 
                                   placeholder="Nama guru/pengawas kedua..."
                                   <?= $document['verification_status'] === 'AUTHENTICATED' ? 'readonly' : '' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Catatan Kejadian Khusus / Kendala Teknis</label>
                            <textarea name="incident_notes" 
                                      class="form-control form-control-sm" 
                                      rows="4" 
                                      placeholder="Tuliskan kendala teknis (listrik padam, jaringan, dll) atau insiden jika ada..."
                                      <?= $document['verification_status'] === 'AUTHENTICATED' ? 'readonly' : '' ?>></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body p-3">
                        <?php if ($document['verification_status'] === 'AUTHENTICATED'): ?>
                            <div class="alert alert-success py-2 px-3 small mb-3">
                                <i class="bi bi-shield-check me-1"></i> <strong>Dokumen Terautentikasi:</strong> Dokumen BAP ini telah disahkan dan disegel secara kriptografis. Perubahan lebih lanjut dikunci demi integritas data.
                            </div>
                            <a href="<?= base_url('admin/cbt/bap/view/' . $document['id']) ?>" class="btn btn-outline-primary btn-sm w-100 py-2 fw-semibold">
                                <i class="bi bi-printer me-1"></i> Lihat Dokumen BAP Sah
                            </a>
                        <?php else: ?>
                            <div class="alert alert-light border small text-muted mb-3">
                                <i class="bi bi-info-circle me-1"></i> Gunakan "Simpan Draft" untuk penyimpanan sementara. "Tandatangani &amp; Segel Integritas Digital BAP" akan menyegel dokumen dengan Hash SHA-256 dan mencatat identitas penandatangan pada audit trail.
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" id="btnSaveDraft" class="btn btn-outline-primary btn-sm py-2 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Draft
                                </button>
                                <button type="button" id="btnSubmitBap" class="btn btn-primary btn-sm py-2 fw-semibold">
                                    <i class="bi bi-shield-check me-1"></i> Tandatangani &amp; Segel Integritas Digital BAP
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bapForm = document.getElementById('bapForm');
    const btnSaveDraft = document.getElementById('btnSaveDraft');
    const btnSubmitBap = document.getElementById('btnSubmitBap');
    const btnCheckDiscrepancy = document.getElementById('btnCheckDiscrepancy');
    const btnMarkAllPresent = document.getElementById('btnMarkAllPresent');
    const discrepancyContainer = document.getElementById('discrepancyAlertContainer');

    if (btnMarkAllPresent) {
        btnMarkAllPresent.addEventListener('click', function () {
            document.querySelectorAll('.status-radio[value="HADIR"]').forEach(r => r.checked = true);
        });
    }

    if (btnSaveDraft) {
        btnSaveDraft.addEventListener('click', function () {
            btnSaveDraft.disabled = true;
            btnSaveDraft.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan Draft...';

            const formData = new FormData(bapForm);

            fetch('<?= base_url('admin/cbt/bap/save-draft') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btnSaveDraft.disabled = false;
                btnSaveDraft.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Draft';
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(err => {
                btnSaveDraft.disabled = false;
                btnSaveDraft.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Draft';
                alert('Kesalahan jaringan: ' + err.message);
            });
        });
    }

    if (btnSubmitBap) {
        btnSubmitBap.addEventListener('click', function () {
            if (!confirm('Apakah Anda yakin ingin menyegel Berita Acara ini? Identitas penandatangan berdasarkan akun terautentikasi akan dicatat dan ringkasan data disegel menggunakan Hash SHA-256 pada riwayat penyegelan dan audit trail. Dokumen yang telah disegel bersifat immutable (tidak dapat diubah lagi).')) {
                return;
            }

            btnSubmitBap.disabled = true;
            btnSubmitBap.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyegel Integritas Digital...';

            const formData = new FormData(bapForm);

            fetch('<?= base_url('admin/cbt/bap/submit') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Segel Integritas Digital BAP berhasil dibentuk!\nHash SHA-256: ' + data.document_sha256 + '\nIdentitas penandatangan dan riwayat penyegelan tersimpan pada audit trail.');
                    window.location.reload();
                } else {
                    btnSubmitBap.disabled = false;
                    btnSubmitBap.innerHTML = '<i class="bi bi-shield-check me-1"></i> Tandatangani &amp; Segel Integritas Digital BAP';
                    alert('Penyegelan Gagal: ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(err => {
                btnSubmitBap.disabled = false;
                btnSubmitBap.innerHTML = '<i class="bi bi-shield-check me-1"></i> Tandatangani &amp; Segel Integritas Digital BAP';
                alert('Kesalahan jaringan: ' + err.message);
            });
        });
    }

    if (btnCheckDiscrepancy) {
        btnCheckDiscrepancy.addEventListener('click', function () {
            btnCheckDiscrepancy.disabled = true;
            btnCheckDiscrepancy.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menganalisis...';

            fetch('<?= base_url('admin/cbt/bap/discrepancies/' . $document['id']) ?>', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(result => {
                btnCheckDiscrepancy.disabled = false;
                btnCheckDiscrepancy.innerHTML = '<i class="bi bi-search me-1"></i> Cek Anomali Telemetri';

                if (result.success && result.data) {
                    const data = result.data;
                    discrepancyContainer.style.display = 'block';

                    if (data.has_discrepancies) {
                        let html = '<div class="alert alert-warning border border-warning shadow-sm p-3">';
                        html += '<h6 class="fw-bold text-dark mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Ditemukan ' + data.discrepancy_count + ' Anomali Antara BAP dan Telemetri CBT</h6>';
                        html += '<p class="small text-muted mb-2">Terdapat perbedaan data kehadiran antara laporan fisik pengawas dan rekam telemetri server. Sesuai OVERRIDE-SYNC-POLICY, kedua data dipertahankan.</p>';
                        html += '<ul class="list-group list-group-flush small bg-transparent">';
                        data.discrepancies.forEach(d => {
                            html += '<li class="list-group-item px-0 py-1 bg-transparent border-0">';
                            html += '<strong>Siswa #' + d.student_id + ':</strong> ' + d.discrepancy_code + ' — ' + d.governance_note;
                            html += '</li>';
                        });
                        html += '</ul>';
                        html += '<div class="mt-2"><a href="<?= base_url('admin/cbt/review') ?>" class="btn btn-sm btn-warning fw-semibold">Buka di Review Dashboard</a></div>';
                        html += '</div>';
                        discrepancyContainer.innerHTML = html;
                    } else {
                        discrepancyContainer.innerHTML = '<div class="alert alert-success py-2 px-3 small"><i class="bi bi-check-circle-fill me-1"></i> Seluruh data presensi BAP selaras dengan telemetri CBT server (' + data.matched_count + ' siswa sinkron).</div>';
                    }
                }
            })
            .catch(err => {
                btnCheckDiscrepancy.disabled = false;
                btnCheckDiscrepancy.innerHTML = '<i class="bi bi-search me-1"></i> Cek Anomali Telemetri';
                alert('Gagal memeriksa anomali: ' + err.message);
            });
        });
    }
});
</script>
<?= $this->endSection() ?>

