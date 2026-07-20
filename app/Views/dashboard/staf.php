<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid p-0">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold mb-1">Selamat Datang,
                <?= esc($user['fullname'] ?? $user['username']) ?>!
            </h3>
            <p class="text-muted">Ringkasan data akademik sekolah hari ini.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-person-badge fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Jumlah Guru Aktif</h6>
                            <h3 class="fw-bold mb-0"><?= $stats['total_teachers'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-mortarboard fs-4 text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Jumlah Siswa Aktif</h6>
                            <h3 class="fw-bold mb-0"><?= $stats['active_students'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-building fs-4 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Jumlah Kelas</h6>
                            <h3 class="fw-bold mb-0"><?= $stats['total_classes'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-calendar-check fs-4 text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Tahun Ajaran Berjalan</h6>
                            <h3 class="fw-bold mb-0"><?= esc($activeYear['year'] ?? '-') ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <!-- Statistik Siswa per Kelas -->
  <?= $this->include('dashboard/_student_stats_table') ?>

</div>
<?= $this->endSection() ?>
