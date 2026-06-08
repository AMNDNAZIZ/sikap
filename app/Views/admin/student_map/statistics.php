<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>📊 <?= esc($title) ?></h3>
        <a href="<?= site_url('admin/student-map') ?>" class="btn btn-secondary">
            ← Kembali ke Peta
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h2><?= $statistics['total'] ?></h2>
                    <p class="mb-0">Total Siswa</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2><?= $statistics['withCoordinates'] ?></h2>
                    <p class="mb-0">Sudah Diisi (<?= $statistics['percentage'] ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h2><?= $statistics['withoutCoordinates'] ?></h2>
                    <p class="mb-0">Belum Diisi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h2><?= $statistics['avgDistance'] ?> km</h2>
                    <p class="mb-0">Jarak Rata-rata</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Distance Statistics -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📏 Sebaran Berdasarkan Jarak</h5>
                </div>
                <div class="card-body">
                    <canvas id="distanceChart"></canvas>
                    
                    <div class="mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Jarak</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statistics['byDistance'] as $range => $count): ?>
                                    <?php 
                                    $percentage = $statistics['withCoordinates'] > 0 
                                        ? round(($count / $statistics['withCoordinates']) * 100, 1)
                                        : 0;
                                    ?>
                                    <tr>
                                        <td><?= esc($range) ?> km</td>
                                        <td class="text-end"><?= $count ?></td>
                                        <td class="text-end"><?= $percentage ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($statistics['withCoordinates'] > 0): ?>
                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>Jarak Terdekat:</strong> <?= $statistics['minDistance'] ?> km<br>
                                <strong>Jarak Terjauh:</strong> <?= $statistics['maxDistance'] ?> km
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Level Statistics -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🎓 Sebaran Berdasarkan Tingkat</h5>
                </div>
                <div class="card-body">
                    <canvas id="levelChart"></canvas>
                    
                    <div class="mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tingkat</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statistics['byLevel'] as $level => $count): ?>
                                    <?php 
                                    $percentage = $statistics['withCoordinates'] > 0 
                                        ? round(($count / $statistics['withCoordinates']) * 100, 1)
                                        : 0;
                                    ?>
                                    <tr>
                                        <td><?= esc($level) ?></td>
                                        <td class="text-end"><?= $count ?></td>
                                        <td class="text-end"><?= $percentage ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Statistics -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🏫 Sebaran Berdasarkan Kelas</h5>
                </div>
                <div class="card-body">
                    <canvas id="classChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Distance Chart
const distanceCtx = document.getElementById('distanceChart').getContext('2d');
new Chart(distanceCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($statistics['byDistance'])) ?>,
        datasets: [{
            data: <?= json_encode(array_values($statistics['byDistance'])) ?>,
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: false
            }
        }
    }
});

// Level Chart
const levelCtx = document.getElementById('levelChart').getContext('2d');
new Chart(levelCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($statistics['byLevel'])) ?>,
        datasets: [{
            data: <?= json_encode(array_values($statistics['byLevel'])) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Class Chart
const classCtx = document.getElementById('classChart').getContext('2d');
new Chart(classCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($statistics['byClass'])) ?>,
        datasets: [{
            label: 'Jumlah Siswa',
            data: <?= json_encode(array_values($statistics['byClass'])) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
<?= $this->endSection() ?>
