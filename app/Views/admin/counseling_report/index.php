<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-3">Rekapitulasi & Laporan BK</h1>
            <p class="text-muted">Statistik layanan Bimbingan dan Konseling, pencatatan jurnal, dan aktivitas pemanggilan orang tua.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white mb-1">Total Jurnal Konseling</h6>
                            <h2 class="mb-0 fw-bold"><?= $totalJournals ?></h2>
                        </div>
                        <i class="bi bi-journal-text display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white mb-1">Total Janji Temu BK</h6>
                            <h2 class="mb-0 fw-bold"><?= $totalAppointments ?></h2>
                        </div>
                        <i class="bi bi-calendar-check display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-dark mb-1">Total Surat Panggilan</h6>
                            <h2 class="mb-0 fw-bold"><?= $totalSummons ?></h2>
                        </div>
                        <i class="bi bi-envelope-exclamation display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Chart: Jenis Layanan -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Komposisi Jenis Layanan BK</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart: Status Surat Panggilan -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Kehadiran Panggilan Ortu</h5>
                </div>
                <div class="card-body">
                    <canvas id="summonChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Trend Bulanan -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tren Layanan Konseling (6 Bulan Terakhir)</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Laporan</button>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data untuk Chart Jenis Layanan
    const typeLabels = <?= json_encode(array_column($statTypes, 'counseling_type')) ?>;
    const typeData = <?= json_encode(array_column($statTypes, 'total')) ?>;
    
    new Chart(document.getElementById('typeChart'), {
        type: 'pie',
        data: {
            labels: typeLabels.length > 0 ? typeLabels : ['Belum Ada Data'],
            datasets: [{
                data: typeData.length > 0 ? typeData : [1],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Data untuk Chart Surat Panggilan
    const summonLabels = <?= json_encode(array_column($statSummons, 'status')) ?>;
    const summonData = <?= json_encode(array_column($statSummons, 'total')) ?>;

    new Chart(document.getElementById('summonChart'), {
        type: 'doughnut',
        data: {
            labels: summonLabels.length > 0 ? summonLabels : ['Belum Ada Data'],
            datasets: [{
                data: summonData.length > 0 ? summonData : [1],
                backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b', '#4e73df'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Data untuk Chart Tren Bulanan
    const monthlyLabels = <?= json_encode(array_column($statMonthly, 'month')) ?>;
    const monthlyData = <?= json_encode(array_column($statMonthly, 'total')) ?>;

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyLabels.length > 0 ? monthlyLabels : ['Belum Ada Data'],
            datasets: [{
                label: 'Jumlah Layanan BK',
                data: monthlyData.length > 0 ? monthlyData : [0],
                backgroundColor: '#4e73df',
                borderRadius: 5
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
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
