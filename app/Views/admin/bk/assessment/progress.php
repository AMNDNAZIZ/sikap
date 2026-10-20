<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Breadcrumb & Navigation -->
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="<?= base_url('admin/bk/pemetaan/penugasan') ?>" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Penugasan
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Rekap Progress
            </button>
            <a href="<?= base_url('admin/bk/pemetaan') ?>" class="btn btn-outline-info rounded-pill px-3 shadow-sm btn-sm">
                <i class="bi bi-diagram-3 me-1"></i> Peta Kebutuhan Siswa
            </a>
        </div>
    </div>

    <!-- Assignment Info Card -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                            <i class="bi bi-journal-text me-1"></i> <?= esc($assignment['assessment_type'] ?? 'Asesmen') ?>
                        </span>
                        <?php if ($assignment['target_type'] === 'class'): ?>
                            <?php 
                                $tClasses = $assignment['target_class_names'] ?? [];
                                if (empty($tClasses) && !empty($assignment['class_name'])) {
                                    $tClasses = [$assignment['class_name']];
                                }
                            ?>
                            <span class="badge bg-info-subtle text-info fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-mortarboard me-1"></i> Kelas <?= esc(!empty($tClasses) ? implode(', ', $tClasses) : 'Kelas Sasaran') ?> <?= count($tClasses) > 1 ? '(' . count($tClasses) . ' Kelas)' : '' ?>
                            </span>
                        <?php elseif ($assignment['target_type'] === 'level'): ?>
                            <span class="badge bg-info-subtle text-info fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-layers me-1"></i> Jenjang <?= esc($assignment['target_id'] ?? '') ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-people me-1"></i> Semua Siswa
                            </span>
                        <?php endif; ?>

                        <?php 
                            $isExpired = strtotime($assignment['end_date'] . ' 23:59:59') < time();
                            $status = $assignment['status'] ?? 'draft';
                        ?>
                        <?php if ($isExpired): ?>
                            <span class="badge bg-danger-subtle text-danger fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-clock-history me-1"></i> Berakhir (Expired)
                            </span>
                        <?php elseif ($status === 'active'): ?>
                            <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-check-circle me-1"></i> Aktif
                            </span>
                        <?php elseif ($status === 'closed'): ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-lock me-1"></i> Ditutup
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                <?= esc(ucfirst($status)) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="h4 fw-bold text-dark mb-1"><?= esc($assignment['title']) ?></h2>
                    <p class="text-muted mb-2">
                        <strong>Instrumen:</strong> <?= esc($assignment['instrument_title']) ?>
                        <?php if (!empty($assignment['instrument_description'])): ?>
                            <?php 
                            $desc = strip_tags($assignment['instrument_description']);
                            $shortDesc = (mb_strlen($desc) > 120) ? mb_substr($desc, 0, 117) . '...' : $desc;
                            ?>
                            &bull; <span class="small"><?= esc($shortDesc) ?></span>
                        <?php endif; ?>
                    </p>
                    <div class="small text-muted d-flex align-items-center gap-3 flex-wrap">
                        <span><i class="bi bi-calendar3 me-1 text-primary"></i> <strong>Periode:</strong> <?= date('d M Y', strtotime($assignment['start_date'])) ?> s/d <?= date('d M Y', strtotime($assignment['end_date'])) ?></span>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <div class="bg-light rounded-4 p-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-muted small">Tingkat Penyelesaian</span>
                            <span class="fw-bold fs-5 text-primary"><?= $completionPercentage ?>%</span>
                        </div>
                        <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 style="width: <?= $completionPercentage ?>%;" 
                                 aria-valuenow="<?= $completionPercentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <div class="small text-muted text-center">
                            <strong><?= $completedCount ?></strong> dari <strong><?= $totalStudents ?></strong> siswa telah menyelesaikan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-primary border-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Siswa Sasaran</div>
                        <div class="h3 fw-bold mb-0 text-dark"><?= $totalStudents ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Selesai (Completed)</div>
                        <div class="h3 fw-bold mb-0 text-success"><?= $completedCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-warning border-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Sedang Mengerjakan</div>
                        <div class="h3 fw-bold mb-0 text-warning"><?= $inProgressCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-secondary border-4">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-secondary-subtle text-secondary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Belum Mulai (Assigned)</div>
                        <div class="h3 fw-bold mb-0 text-secondary"><?= $assignedCount ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student List Card -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 px-4 border-0">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check text-primary me-2"></i>Daftar Siswa &amp; Status Pengerjaan</h5>
                </div>
                <div class="col-md-7">
                    <div class="d-flex gap-2 justify-content-md-end flex-wrap align-items-center">
                        <?php 
                            $uniqueClasses = array_unique(array_filter(array_column($students, 'current_class_name')));
                            sort($uniqueClasses);
                        ?>
                        <?php if (count($uniqueClasses) > 1): ?>
                            <select id="classFilter" class="form-select form-select-sm" style="max-width: 140px;">
                                <option value="all">Semua Kelas</option>
                                <?php foreach ($uniqueClasses as $uc): ?>
                                    <option value="<?= esc($uc) ?>">Kelas <?= esc($uc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <div class="btn-group" role="group" aria-label="Filter Status">
                            <button type="button" class="btn btn-sm btn-outline-primary active filter-btn" data-filter="all">Semua (<?= $totalStudents ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="COMPLETED">Selesai (<?= $completedCount ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="IN_PROGRESS">Sedang (<?= $inProgressCount ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="ASSIGNED">Belum (<?= $assignedCount ?>)</button>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 bg-light" placeholder="Cari siswa/NIS...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableStudents">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th class="text-center">Status</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Hasil / Skor</th>
                            <th class="text-center pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ada siswa yang terdaftar pada penugasan ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($students as $st): ?>
                                <?php 
                                    $stStatus = strtoupper($st['student_status'] ?? 'ASSIGNED');
                                    $hasResult = !empty($st['result_id']);
                                    $searchable = strtolower(($st['student_name'] ?? '') . ' ' . ($st['nis'] ?? '') . ' ' . ($st['nisn'] ?? ''));
                                    $stClassName = $st['current_class_name'] ?? $assignment['class_name'] ?? '-';
                                ?>
                                <tr class="student-row" data-status="<?= $stStatus ?>" data-class="<?= esc($stClassName) ?>" data-search="<?= esc($searchable) ?>">
                                    <td class="ps-4 text-muted small row-number"><?= $no++ ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= esc($st['nis'] ?: '-') ?></span>
                                        <?php if (!empty($st['nisn'])): ?>
                                            <br><small class="text-muted"><?= esc($st['nisn']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= esc($st['student_name']) ?></div>
                                        <small class="text-muted">
                                            <?= ($st['gender'] ?? '') === 'L' ? '<i class="bi bi-gender-male text-primary"></i> Laki-laki' : (($st['gender'] ?? '') === 'P' ? '<i class="bi bi-gender-female text-danger"></i> Perempuan' : '') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= esc($st['current_class_name'] ?? $assignment['class_name'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($stStatus === 'COMPLETED'): ?>
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                            </span>
                                        <?php elseif ($stStatus === 'IN_PROGRESS'): ?>
                                            <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill fw-semibold">
                                                <i class="bi bi-hourglass-split me-1"></i> Sedang Mengerjakan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-semibold">
                                                <i class="bi bi-clock me-1"></i> Belum Mulai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($st['started_at']) ? date('d/m/Y H:i', strtotime($st['started_at'])) : '-' ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($st['completed_at']) ? date('d/m/Y H:i', strtotime($st['completed_at'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <div>
                                                <span class="fw-bold text-dark"><?= $st['total_score'] ?> / <?= $st['total_max_score'] ?></span>
                                                <small class="text-muted ms-1">(<?= round($st['percentage'], 1) ?>%)</small>
                                            </div>
                                            <?php if (!empty($st['overall_level'])): ?>
                                                <span class="badge bg-info-subtle text-info mt-1"><?= esc($st['overall_level']) ?></span>
                                            <?php endif; ?>
                                        <?php elseif ($stStatus === 'COMPLETED'): ?>
                                            <span class="badge bg-success-subtle text-success">Sudah Dikirim</span>
                                        <?php elseif ($stStatus === 'IN_PROGRESS'): ?>
                                            <span class="text-warning small fst-italic"><i class="bi bi-pencil me-1"></i>Dalam pengerjaan...</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <?php if ($hasResult): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="viewDetail(<?= $st['result_id'] ?>)">
                                                <i class="bi bi-eye me-1"></i> Hasil
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-light text-muted rounded-pill px-3" disabled title="Belum ada hasil asesmen">
                                                <i class="bi bi-eye-slash me-1"></i> Detail
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($students)): ?>
            <div class="card-footer bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap small text-muted">
                <div>
                    Menampilkan <strong id="shownCount"><?= count($students) ?></strong> dari <strong><?= count($students) ?></strong> siswa
                </div>
                <div>
                    <i class="bi bi-info-circle me-1 text-primary"></i> Data diperbarui secara otomatis setiap siswa mengumpulkan asesmen.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail Hasil Siswa -->
<div class="modal fade" id="modalResultDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold text-primary"><i class="bi bi-person-badge me-2"></i>Detail Hasil Asesmen Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 bg-light p-3 rounded-4 g-2">
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Nama Siswa / NISN</span>
                        <strong class="text-dark fs-6" id="detail_student_name">-</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Kelas</span>
                        <strong class="text-dark fs-6" id="detail_class_name">-</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Total Skor &amp; (%)</span>
                        <strong class="text-dark fs-6" id="detail_total_score">-</strong>
                    </div>
                    <div class="col-12 mt-2 pt-2 border-top">
                        <span class="text-muted small d-block">Interpretasi Keseluruhan</span>
                        <span class="badge bg-primary px-3 py-2 mt-1" id="detail_overall_level">-</span>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Rincian Skor Aspek / Domain</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Aspek / Bidang Kebutuhan</th>
                                <th class="text-center" style="width: 120px;">Skor</th>
                                <th style="width: 200px;">Persentase</th>
                                <th style="width: 150px;">Kategori</th>
                            </tr>
                        </thead>
                        <tbody id="detail_domain_list">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #tableStudents, #tableStudents * {
        visibility: visible;
    }
    #tableStudents {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .btn, .input-group, .btn-group, .modal {
        display: none !important;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const classFilter = document.getElementById('classFilter');
    const rows = document.querySelectorAll('.student-row');
    const shownCountEl = document.getElementById('shownCount');

    let currentFilter = 'all';
    let currentClass = 'all';
    let searchQuery = '';

    function applyFilter() {
        let count = 0;
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const rowClass = row.getAttribute('data-class') || '';
            const search = row.getAttribute('data-search') || '';

            const matchFilter = (currentFilter === 'all') || (status === currentFilter);
            const matchClass = (currentClass === 'all') || (rowClass === currentClass);
            const matchSearch = (!searchQuery) || (search.indexOf(searchQuery) !== -1);

            if (matchFilter && matchClass && matchSearch) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (shownCountEl) {
            shownCountEl.innerText = count;
        }
    }

    if (classFilter) {
        classFilter.addEventListener('change', function () {
            currentClass = this.value;
            applyFilter();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQuery = this.value.toLowerCase().trim();
            applyFilter();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyFilter();
        });
    });
});

function viewDetail(resultId) {
    if (!resultId) return;

    fetch('<?= base_url('admin/bk/pemetaan/hasil/detail') ?>/' + resultId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('detail_student_name').innerText = (data.result.student_name || '-') + ' (' + (data.result.nisn || data.result.nis || '-') + ')';
                document.getElementById('detail_class_name').innerText = data.result.class_name || '-';
                document.getElementById('detail_total_score').innerText = (data.result.total_score || '0') + ' / ' + (data.result.total_max_score || '0') + ' (' + parseFloat(data.result.percentage || 0).toFixed(1) + '%)';
                document.getElementById('detail_overall_level').innerText = data.result.overall_level || '-';
                
                let tbody = document.getElementById('detail_domain_list');
                tbody.innerHTML = '';
                
                if (data.details && data.details.length > 0) {
                    data.details.forEach(d => {
                        let colorClass = 'bg-secondary';
                        let label = (d.level_label || '').toLowerCase();
                        if (label.includes('sangat tinggi') || label.includes('sangat sesuai') || label.includes('sangat butuh')) {
                            colorClass = 'bg-danger';
                        } else if (label.includes('tinggi') || label.includes('sesuai') || label.includes('butuh')) {
                            colorClass = 'bg-warning text-dark';
                        } else if (label.includes('sedang') || label.includes('cukup')) {
                            colorClass = 'bg-primary';
                        } else {
                            colorClass = 'bg-success';
                        }

                        let pct = parseFloat(d.percentage || 0).toFixed(1);
                        let tr = `<tr>
                            <td class="fw-bold">${d.domain_name || '-'} ${d.sub_domain ? '<small class="text-muted d-block">'+d.sub_domain+'</small>' : ''}</td>
                            <td class="text-center">${d.domain_score || '0'} / ${d.domain_max_score || '0'}</td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${pct}%"></div>
                                </div>
                                <small class="text-muted">${pct}%</small>
                            </td>
                            <td><span class="badge ${colorClass}">${d.level_label || '-'}</span></td>
                        </tr>`;
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada detail aspek.</td></tr>';
                }
                
                new bootstrap.Modal(document.getElementById('modalResultDetail')).show();
            } else {
                alert(data.message || 'Data hasil asesmen tidak ditemukan.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Gagal mengambil detail hasil asesmen.');
        });
}
</script>
<?= $this->endSection() ?>
