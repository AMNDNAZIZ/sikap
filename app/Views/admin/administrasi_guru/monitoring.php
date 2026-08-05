<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📊 Monitoring Administrasi Guru</h1>
        <p class="text-muted mb-0">Pantau status kelengkapan administrasi pembelajaran guru (ATP, KKTP, Promes, Modul Ajar).</p>
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
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart-fill me-1"></i> Distribusi Promes</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <?php if ($total > 0): ?>
                    <div style="position: relative; width: 180px; height: 180px;">
                        <canvas id="promesChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center">
                        <div class="col-4"><span class="d-block fw-bold text-success"><?= $promes_stats['complete'] ?></span><span class="text-xs text-muted">Selesai</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-warning"><?= $promes_stats['partial'] ?></span><span class="text-xs text-muted">Sebagian</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-danger"><?= $promes_stats['empty'] ?></span><span class="text-xs text-muted">Kosong</span></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-muted"><i class="bi bi-pie-chart display-4 mb-2"></i><p class="mb-0">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart KKTP -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart-fill me-1"></i> KKTP</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <?php if ($total > 0): ?>
                    <div style="position: relative; width: 180px; height: 180px;">
                        <canvas id="kktpChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center">
                        <div class="col-4"><span class="d-block fw-bold text-success"><?= $kktp_stats['complete'] ?></span><span class="text-xs text-muted">Selesai</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-warning"><?= $kktp_stats['partial'] ?></span><span class="text-xs text-muted">Sebagian</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-danger"><?= $kktp_stats['empty'] ?></span><span class="text-xs text-muted">Kosong</span></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-muted"><i class="bi bi-pie-chart display-4 mb-2"></i><p class="mb-0">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart Modul Ajar -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-pie-chart-fill me-1"></i> Modul Ajar</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                <?php if ($total > 0): ?>
                    <div style="position: relative; width: 180px; height: 180px;">
                        <canvas id="modulChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center">
                        <div class="col-4"><span class="d-block fw-bold text-success"><?= $modul_stats['complete'] ?></span><span class="text-xs text-muted">Selesai</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-warning"><?= $modul_stats['partial'] ?></span><span class="text-xs text-muted">Sebagian</span></div>
                        <div class="col-4"><span class="d-block fw-bold text-danger"><?= $modul_stats['empty'] ?></span><span class="text-xs text-muted">Kosong</span></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-muted"><i class="bi bi-pie-chart display-4 mb-2"></i><p class="mb-0">Tidak ada data</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Per Guru -->
<?php if (!empty($teacher_summary ?? [])): ?>
<div class="card shadow mb-4 border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people-fill me-1"></i> Ringkasan Per Guru</h6>
        <span class="badge bg-secondary"><?= count($teacher_summary) ?> guru</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40" class="text-center">No</th>
                        <th>Nama Guru</th>
                        <th width="80" class="text-center">Total Mapel</th>
                        <th width="100" class="text-center">Selesai</th>
                        <th width="100" class="text-center">Sebagian</th>
                        <th width="100" class="text-center">Belum</th>
                        <th width="120" class="text-center">Progress</th>
                        <th width="100" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $tNo = 1; foreach ($teacher_summary as $tid => $ts): 
                        $pct = $ts['total'] > 0 ? round(($ts['complete'] / $ts['total']) * 100) : 0;
                        $pct = (int) $pct; // pastikan integer untuk perbandingan
                        $barColor = ($pct >= 100) ? 'bg-success' : (($pct > 0) ? 'bg-warning' : 'bg-danger');
                    ?>
                    <tr>
                        <td class="text-center"><?= $tNo++ ?></td>
                        <td class="fw-bold"><?= esc($ts['name']) ?></td>
                        <td class="text-center"><?= $ts['total'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= $ts['complete'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= $ts['partial'] ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= $ts['empty'] ?></span></td>
                        <td>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar <?= $barColor ?>" style="width: <?= $pct ?>%;" title="<?= $pct ?>%"><?= $pct ?>%</div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($pct >= 100): ?>
                                <span class="badge bg-success">✅ Selesai</span>
                            <?php elseif ($ts['empty'] === $ts['total']): ?>
                                <span class="badge bg-danger">❌ Belum</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">🔄 Proses</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

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
                        <th width="40" class="text-center">No</th>
                        <th>Nama Guru</th>
                        <th width="100" class="text-center">Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th width="200">ATP</th>
                        <th width="200">KKTP</th>
                        <th width="200">Promes</th>
                        <th width="200">Modul Ajar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-file-earmark-x display-4 d-block mb-3"></i>
                                Tidak ada data plotting pengajaran yang ditemukan untuk tahun ajaran aktif ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1 + (($page ?? 1) - 1) * ($perPage ?? 50);
                        foreach ($assignments as $row): 
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><div class="fw-bold"><?= esc($row['teacher_name']) ?></div></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= esc($row['class_name']) ?></span></td>
                                <td><?= esc($row['subject_name']) ?></td>

                                <!-- ATP -->
                                <td>
                                    <?php if ($row['atp_status'] === 'complete'): ?>
                                        <span class="badge bg-success"><?= esc($row['atp_info']) ?></span>
                                    <?php elseif ($row['atp_status'] === 'partial'): ?>
                                        <span class="badge bg-warning text-dark"><?= esc($row['atp_info']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= esc($row['atp_info']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- KKTP -->
                                <td>
                                    <?php if ($row['kktp_status'] === 'complete'): ?>
                                        <span class="badge bg-success"><?= esc($row['kktp_info']) ?></span>
                                    <?php elseif ($row['kktp_status'] === 'partial'): ?>
                                        <span class="badge bg-warning text-dark"><?= esc($row['kktp_info']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= esc($row['kktp_info']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Promes -->
                                <td>
                                    <?php if ($row['promes_status'] === 'complete'): ?>
                                        <span class="badge bg-success"><?= esc($row['promes_info']) ?></span>
                                    <?php elseif ($row['promes_status'] === 'partial'): ?>
                                        <span class="badge bg-warning text-dark"><?= esc($row['promes_info']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= esc($row['promes_info']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Modul Ajar -->
                                <td>
                                    <?php if ($row['modul_status'] === 'complete'): ?>
                                        <span class="badge bg-success"><?= esc($row['modul_info']) ?></span>
                                    <?php elseif ($row['modul_status'] === 'partial'): ?>
                                        <span class="badge bg-warning text-dark"><?= esc($row['modul_info']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= esc($row['modul_info']) ?></span>
                                    <?php endif; ?>
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

    // KKTP Chart
    const kktpCtx = document.getElementById('kktpChart').getContext('2d');
    new Chart(kktpCtx, {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Selesai Sebagian', 'Belum Dikerjakan'],
            datasets: [{
                data: [<?= $kktp_stats['complete'] ?>, <?= $kktp_stats['partial'] ?>, <?= $kktp_stats['empty'] ?>],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: chartOptions
    });

    // Modul Chart
    const modulCtx = document.getElementById('modulChart').getContext('2d');
    new Chart(modulCtx, {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Selesai Sebagian', 'Belum Dikerjakan'],
            datasets: [{
                data: [<?= $modul_stats['complete'] ?>, <?= $modul_stats['partial'] ?>, <?= $modul_stats['empty'] ?>],
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
