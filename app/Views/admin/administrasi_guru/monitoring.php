<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📊 Monitoring Administrasi Guru</h1>
        <p class="text-muted mb-0">Pantau status kelengkapan Alur Tujuan Pembelajaran (ATP) dan Distribusi Program Semester (Promes) guru.</p>
    </div>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Charts Section -->
<div class="row mb-4">
    <!-- Chart ATP -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart-fill me-1"></i> Persentase Kelengkapan Alur Pembelajaran (ATP)</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <?php if ($total > 0): ?>
                    <div style="position: relative; width: 220px; height: 220px;">
                        <canvas id="atpChart"></canvas>
                    </div>
                    <div class="row w-100 mt-4 text-center">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success" style="font-size: 1.1rem;"><?= $atp_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Selesai</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning" style="font-size: 1.1rem;"><?= $atp_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Sebagian</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger" style="font-size: 1.1rem;"><?= $atp_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Kosong</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-pie-chart display-4 mb-2"></i>
                        <p class="mb-0">Tidak ada data plotting pengajaran</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Chart Promes -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart-fill me-1"></i> Persentase Kelengkapan Distribusi Promes</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <?php if ($total > 0): ?>
                    <div style="position: relative; width: 220px; height: 220px;">
                        <canvas id="promesChart"></canvas>
                    </div>
                    <div class="row w-100 mt-4 text-center">
                        <div class="col-4">
                            <span class="d-block fw-bold text-success" style="font-size: 1.1rem;"><?= $promes_stats['complete'] ?></span>
                            <span class="text-xs text-muted">Selesai</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-warning" style="font-size: 1.1rem;"><?= $promes_stats['partial'] ?></span>
                            <span class="text-xs text-muted">Sebagian</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-danger" style="font-size: 1.1rem;"><?= $promes_stats['empty'] ?></span>
                            <span class="text-xs text-muted">Kosong</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-pie-chart display-4 mb-2"></i>
                        <p class="mb-0">Tidak ada data plotting pengajaran</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card shadow mb-4 border-0">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-funnel me-1"></i> Filter Plotting Pengajaran</h6>
    </div>
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/monitoring') ?>" method="get" class="row g-3">
            <div class="col-md-4">
                <label for="teacher_id" class="form-label small fw-bold">Nama Guru</label>
                <select name="teacher_id" id="teacher_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filter_teacher == $t['id'] ? 'selected' : '' ?>>
                            <?= esc($t['name']) ?> (NIP: <?= esc($t['nip'] ?: '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="class_id" class="form-label small fw-bold">Kelas</label>
                <select name="class_id" id="class_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filter_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="subject_id" class="form-label small fw-bold">Mata Pelajaran</label>
                <select name="subject_id" id="subject_id" class="form-select form-select-sm select2-filter">
                    <option value="">-- Semua Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="w-100 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <a href="<?= base_url('admin/administrasi-guru/monitoring') ?>" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Progress Info Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-light border-0 shadow-sm py-2">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h6 class="mb-0 font-weight-bold text-dark"><i class="bi bi-info-circle-fill text-info me-1"></i> Standar Kelengkapan Dokumen Pembelajaran</h6>
                        <ul class="mb-0 small text-muted mt-2 ps-3">
                            <li><strong>Alur Pembelajaran (ATP)</strong> dianggap <strong>Selesai</strong> jika telah diisi dengan minimal <strong>dua materi (ATP) di setiap semester</strong> (Semester 1 & Semester 2).</li>
                            <li><strong>Distribusi Program Semester (Promes)</strong> dianggap <strong>Selesai</strong> jika <strong>seluruh alokasi Jam Pelajaran (JP)</strong> yang terdaftar di ATP sudah didistribusikan ke dalam minggu efektif.</li>
                        </ul>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="badge bg-primary px-3 py-2 fs-7 shadow-sm">
                            <i class="bi bi-calendar2-check-fill me-1"></i> Tahun Ajaran Aktif: <?= esc($active_year['year'] ?? '-') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monitoring Grid Card -->
<div class="card shadow mb-4 border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-grid-3x3-gap-fill me-1"></i> Hasil Monitoring</h6>
        <span class="text-xs text-muted">Menampilkan <?= count($assignments) ?> data dari filter saat ini</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered" id="monitoringTable">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Nama Guru</th>
                        <th width="120" class="text-center">Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th width="280">Status Alur Pembelajaran (ATP)</th>
                        <th width="280">Status Distribusi Promes</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-file-earmark-x display-4 d-block mb-3"></i>
                                Tidak ada data plotting pengajaran yang ditemukan untuk tahun ajaran aktif ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        if (method_exists($pager, 'getCurrentPage')) {
                            $no = 1 + (50 * ($pager->getCurrentPage() - 1));
                        }
                        foreach ($assignments as $row): 
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($row['teacher_name']) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1"><?= esc($row['class_name']) ?></span>
                                </td>
                                <td>
                                    <strong><?= esc($row['subject_name']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($row['atp_status'] == 'complete'): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-check-lg text-white"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-success small">Selesai</span>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['atp_info']) ?></div>
                                            </div>
                                        </div>
                                    <?php elseif ($row['atp_status'] == 'partial'): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-exclamation text-dark fw-bold"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-warning-emphasis small">Selesai sebagian</span>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['atp_info']) ?></div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-x-lg text-white"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-danger small">Belum dikerjakan</span>
                                                <div class="text-muted" style="font-size: 0.75rem;">Kosong</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['promes_status'] == 'complete'): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-check-lg text-white"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-success small">Selesai</span>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['promes_info']) ?></div>
                                            </div>
                                        </div>
                                    <?php elseif ($row['promes_status'] == 'partial'): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-exclamation text-dark fw-bold"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-warning-emphasis small">Belum Lengkap</span>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['promes_info']) ?></div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger rounded-circle p-1 d-inline-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-x-lg text-white"></i>
                                            </span>
                                            <div>
                                                <span class="fw-bold text-danger small">Belum dikerjakan</span>
                                                <div class="text-muted" style="font-size: 0.75rem;">Kosong</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= base_url('admin/administrasi-guru/atp?class_id=' . $row['class_id'] . '&subject_id=' . $row['subject_id']) ?>" 
                                           class="btn btn-xs btn-outline-primary" 
                                           title="Lihat ATP Guru"
                                           target="_blank">
                                            <i class="bi bi-signpost-2"></i> ATP
                                        </a>
                                        <a href="<?= base_url('admin/administrasi-guru/prota-prosem?class_id=' . $row['class_id'] . '&subject_id=' . $row['subject_id']) ?>" 
                                           class="btn btn-xs btn-outline-info" 
                                           title="Lihat Prota & Prosem Guru"
                                           target="_blank">
                                            <i class="bi bi-calendar3"></i> Promes
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($assignments) && $pager): ?>
            <div class="mt-4 d-flex justify-content-center">
                <?= $pager->links('default', 'bootstrap') ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Styling buttons size xs if not predefined */
.btn-xs {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.2rem;
}
.fs-7 {
    font-size: 0.85rem !important;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-filter').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    <?php if ($total > 0): ?>
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return label + value + ' (' + percentage + '%)';
                    }
                }
            }
        },
        cutout: '65%'
    };

    // ATP Chart
    const atpCtx = document.getElementById('atpChart').getContext('2d');
    new Chart(atpCtx, {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Selesai Sebagian', 'Belum Dikerjakan'],
            datasets: [{
                data: [<?= $atp_stats['complete'] ?>, <?= $atp_stats['partial'] ?>, <?= $atp_stats['empty'] ?>],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: chartOptions
    });

    // Promes Chart
    const promesCtx = document.getElementById('promesChart').getContext('2d');
    new Chart(promesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Selesai Sebagian', 'Belum Dikerjakan'],
            datasets: [{
                data: [<?= $promes_stats['complete'] ?>, <?= $promes_stats['partial'] ?>, <?= $promes_stats['empty'] ?>],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: chartOptions
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
