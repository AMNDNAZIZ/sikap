<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">📋 Rekap & Monitoring Absensi Pembina Ekskul</h1>
            <p class="text-muted mb-0">Verifikasi kehadiran pembina/pelatih dan rekapitulasi pertemuan untuk perhitungan honorarium.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/ekskul-absensi/print?month=' . $month . '&year=' . $year . '&ekskul_id=' . $ekskulId) ?>" 
               target="_blank" class="btn btn-outline-danger shadow-sm">
                <i class="fas fa-print fa-sm mr-1"></i> Cetak Rekap SPJ (Bulan <?= esc($monthName) ?>)
            </a>
            <a href="<?= base_url('admin/ekskul') ?>" class="btn btn-outline-secondary shadow-sm ml-2">
                <i class="fas fa-cog fa-sm mr-1"></i> Kelola Ekskul
            </a>
        </div>
    </div>

    <!-- Alert Flashdata -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form action="<?= base_url('admin/ekskul-absensi') ?>" method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold text-muted mb-1">Bulan</label>
                    <select name="month" class="form-select form-select-sm">
                        <?php foreach ($monthsIndo as $mNum => $mLabel) : ?>
                            <option value="<?= $mNum ?>" <?= $mNum == $month ? 'selected' : '' ?>>
                                <?= $mLabel ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-bold text-muted mb-1">Tahun</label>
                    <select name="year" class="form-select form-select-sm">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++) : ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small font-weight-bold text-muted mb-1">Ekstrakurikuler</label>
                    <select name="ekskul_id" class="form-select form-select-sm">
                        <option value="">-- Semua Ekstrakurikuler --</option>
                        <?php foreach ($allEkskuls as $e) : ?>
                            <option value="<?= $e['id'] ?>" <?= $ekskulId == $e['id'] ? 'selected' : '' ?>>
                                <?= esc($e['name']) ?> (<?= ucfirst($e['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter fa-sm mr-1"></i> Terapkan Filter
                    </button>
                    <a href="<?= base_url('admin/ekskul-absensi') ?>" class="btn btn-outline-secondary btn-sm" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Hadir Terverifikasi (Sah)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalHonorMeetings ?> Pertemuan</div>
                            <small class="text-muted font-italic">Siap diproses untuk honor</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Menunggu Verifikasi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPending ?> Jurnal</div>
                            <small class="text-muted font-italic">Perlu diperiksa admin/staf</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Verifikasi Ditolak
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalRejected ?> Jurnal</div>
                            <small class="text-muted font-italic">Tidak dihitung kehadiran</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Pembina Bertugas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($rekapPembina) ?> Pembina</div>
                            <small class="text-muted font-italic">Di <?= count($allEkskuls) ?> cabang ekskul</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <ul class="nav nav-tabs nav-tabs-bordered mb-3" id="absensiTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active font-weight-bold" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#tabRekap" type="button" role="tab">
                💰 Rekapitulasi Honor & Kehadiran Bulanan (<?= esc($monthName) ?> <?= $year ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link font-weight-bold" id="log-tab" data-bs-toggle="tab" data-bs-target="#tabLog" type="button" role="tab">
                📝 Log Jurnal Kegiatan & Verifikasi Harian
                <?php if ($totalPending > 0): ?>
                    <span class="badge bg-warning text-dark ml-1"><?= $totalPending ?> Pending</span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="absensiTabContent">
        <!-- TAB 1: REKAP BULANAN PEMBINA (HONORARIUM) -->
        <div class="tab-pane fade show active" id="tabRekap" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Daftar Kehadiran Pembina Bulan <?= esc($monthName) ?> <?= $year ?> — TA <?= esc($activeYear['year']) ?>
                    </h6>
                    <span class="badge bg-light text-dark border">
                        * Kehadiran terverifikasi menjadi dasar perhitungan SPJ/Honorarium
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th>Nama Pembina / Pelatih</th>
                                    <th>Ekstrakurikuler</th>
                                    <th>Status Pembina</th>
                                    <th class="text-center">Jurnal Masuk</th>
                                    <th class="text-center bg-success text-white">Hadir Sah (Terverifikasi)</th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Ditolak</th>
                                    <th width="12%" class="text-center">Rincian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rekapPembina)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            Tidak ada data pembina ekstrakurikuler yang ditugaskan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rekapPembina as $idx => $rp) : ?>
                                        <tr>
                                            <td class="text-center font-weight-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="font-weight-bold text-gray-800"><?= esc($rp['pembina_name']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary text-white"><?= esc($rp['ekskul_name']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($rp['is_external']): ?>
                                                    <span class="badge bg-info text-white">Pelatih Eksternal</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white">Guru Internal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center font-weight-bold"><?= $rp['total_meetings'] ?> kali</td>
                                            <td class="text-center font-weight-bold text-success fs-6 bg-success-subtle">
                                                <span class="badge bg-success fs-6 px-3 py-1"><?= $rp['verified'] ?> kali</span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rp['pending'] > 0): ?>
                                                    <span class="badge bg-warning text-dark"><?= $rp['pending'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rp['rejected'] > 0): ?>
                                                    <span class="badge bg-danger"><?= $rp['rejected'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('admin/ekskul-absensi/detail/' . $rp['user_id'] . '/' . $rp['ekskul_id'] . '?month=' . $month . '&year=' . $year) ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-list-alt mr-1"></i> Rincian
                                                </a>
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

        <!-- TAB 2: LOG JURNAL HARIAN & VERIFIKASI -->
        <div class="tab-pane fade" id="tabLog" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Log Pertemuan Ekskul & Verifikasi Kehadiran</h6>
                    
                    <!-- Batch Action Form Trigger -->
                    <div>
                        <button type="button" class="btn btn-sm btn-success" id="btnBatchApprove" disabled onclick="submitBatch('verified')">
                            <i class="fas fa-check-circle mr-1"></i> Setujui Terpilih
                        </button>
                        <button type="button" class="btn btn-sm btn-danger ml-1" id="btnBatchReject" disabled onclick="submitBatch('rejected')">
                            <i class="fas fa-times-circle mr-1"></i> Tolak Terpilih
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form id="formBatchVerify" action="<?= base_url('admin/ekskul-absensi/batch-verify') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" id="batchActionInput" value="verified">
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%" class="text-center">
                                            <input type="checkbox" id="checkAll" title="Pilih Semua">
                                        </th>
                                        <th width="12%">Tanggal</th>
                                        <th width="15%">Ekskul</th>
                                        <th width="15%">Pembina</th>
                                        <th width="20%">Materi Pelatihan</th>
                                        <th width="10%" class="text-center">Siswa Hadir</th>
                                        <th width="13%" class="text-center">Status Verifikasi</th>
                                        <th width="12%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logJurnal)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                Belum ada jurnal kegiatan ekskul yang disubmit pada bulan ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logJurnal as $log) : 
                                            $vStatus = $log['verification_status'] ?? 'pending';
                                        ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="jurnal_ids[]" value="<?= $log['id'] ?>" class="jurnal-checkbox">
                                                </td>
                                                <td>
                                                    <strong><?= date('d M Y', strtotime($log['date'])) ?></strong>
                                                    <div class="small text-muted"><?= date('l', strtotime($log['date'])) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary text-white"><?= esc($log['ekskul_name']) ?></span>
                                                </td>
                                                <td>
                                                    <strong><?= esc($log['pembina_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="text-wrap" style="max-width:240px;"><?= esc($log['materi']) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info text-white">
                                                        <?= $log['siswa_hadir'] ?> / <?= $log['total_members'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($vStatus === 'verified'): ?>
                                                        <span class="badge bg-success px-2 py-1">
                                                            <i class="fas fa-check-circle"></i> Disetujui
                                                        </span>
                                                        <?php if (!empty($log['verifier_name'])): ?>
                                                            <div class="small text-muted mt-1" style="font-size:0.75rem;">
                                                                Oleh: <?= esc($log['verifier_name']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php elseif ($vStatus === 'rejected'): ?>
                                                        <span class="badge bg-danger px-2 py-1">
                                                            <i class="fas fa-times-circle"></i> Ditolak
                                                        </span>
                                                        <?php if (!empty($log['verification_notes'])): ?>
                                                            <div class="small text-danger mt-1 fst-italic" style="font-size:0.75rem;">
                                                                "<?= esc($log['verification_notes']) ?>"
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark px-2 py-1">
                                                            <i class="fas fa-clock"></i> Menunggu Verifikasi
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <!-- Modal Verifikasi Button -->
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="openVerifyModal(<?= $log['id'] ?>, '<?= esc(addslashes($log['pembina_name'])) ?>', '<?= esc(addslashes($log['ekskul_name'])) ?>', '<?= $log['date'] ?>', '<?= $vStatus ?>', '<?= esc(addslashes($log['verification_notes'] ?? '')) ?>')">
                                                            <i class="fas fa-check-circle mr-1"></i> Verifikasi
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Single Verification -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="verifyForm" method="post" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="verifyModalLabel">Verifikasi Kehadiran Pembina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="row mb-1">
                            <span class="col-4 text-muted small font-weight-bold">Pembina:</span>
                            <span class="col-8 font-weight-bold text-primary" id="mPembinaName">-</span>
                        </div>
                        <div class="row mb-1">
                            <span class="col-4 text-muted small font-weight-bold">Ekskul:</span>
                            <span class="col-8" id="mEkskulName">-</span>
                        </div>
                        <div class="row">
                            <span class="col-4 text-muted small font-weight-bold">Tanggal:</span>
                            <span class="col-8" id="mDate">-</span>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Keputusan Verifikasi</label>
                        <select name="action" id="mActionSelect" class="form-select" required>
                            <option value="verified">✅ Setujui (Hadir & Dihitung Honor)</option>
                            <option value="rejected">❌ Tolak (Tidak Dihitung Kehadiran)</option>
                            <option value="pending">⏳ Kembalikan ke Pending</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label font-weight-bold">Catatan Verifikasi (Opsional)</label>
                        <textarea name="notes" id="mNotesInput" class="form-control" rows="2" placeholder="Tulis alasan jika menolak, atau catatan administratif lainnya..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        // Handle Check All
        $('#checkAll').on('change', function() {
            $('.jurnal-checkbox').prop('checked', this.checked);
            updateBatchButtons();
        });

        // Individual checkbox change
        $('.jurnal-checkbox').on('change', function() {
            updateBatchButtons();
            if (!this.checked) {
                $('#checkAll').prop('checked', false);
            }
        });

        function updateBatchButtons() {
            const selectedCount = $('.jurnal-checkbox:checked').length;
            $('#btnBatchApprove').prop('disabled', selectedCount === 0);
            $('#btnBatchReject').prop('disabled', selectedCount === 0);
        }
    });

    function submitBatch(action) {
        const count = $('.jurnal-checkbox:checked').length;
        if (count === 0) return;

        const actionText = action === 'verified' ? 'menyetujui' : 'menolak';
        if (confirm(`Yakin ingin ${actionText} ${count} kehadiran jurnal terpilih?`)) {
            $('#batchActionInput').val(action);
            $('#formBatchVerify').submit();
        }
    }

    function openVerifyModal(jurnalId, pembinaName, ekskulName, date, currentStatus, currentNotes) {
        $('#verifyForm').attr('action', '<?= base_url('admin/ekskul-absensi/verify/') ?>/' + jurnalId);
        $('#mPembinaName').text(pembinaName);
        $('#mEkskulName').text(ekskulName);
        $('#mDate').text(date);
        $('#mActionSelect').val(currentStatus);
        $('#mNotesInput').val(currentNotes);
        
        var modal = new bootstrap.Modal(document.getElementById('verifyModal'));
        modal.show();
    }
</script>
<?= $this->endSection() ?>
