<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-1"><?= is_school_sd() ? 'Angket Penelusuran Minat & Bakat' : 'Angket Penelusuran Karir & Minat Bakat' ?></h1>
            <p class="text-muted">Rekap data rencana <?= is_school_sd() ? 'lanjutan' : 'karir' ?> dan minat bakat seluruh siswa yang sudah mengisi angket.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Sudah Mengisi</div>
                        <h3 class="fw-bold mb-0"><?= $totalFilled ?></h3>
                    </div>
                    <i class="bi bi-check-circle display-5 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Belum Mengisi</div>
                        <h3 class="fw-bold mb-0"><?= $totalNotFilled ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split display-5 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Rencana Terbanyak</div>
                        <?php
                            $topPlan = !empty($statPlan) ? array_reduce($statPlan, fn($carry, $item) => (!$carry || $item['total'] > $carry['total']) ? $item : $carry, null) : null;
                        ?>
                        <h4 class="fw-bold mb-0"><?= $topPlan ? $topPlan['post_graduate_plan'] : '-' ?></h4>
                    </div>
                    <i class="bi bi-mortarboard display-5 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Chart -->
        <div class="col-md-5 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Distribusi Rencana Setelah Lulus</h5></div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="planChart" style="max-height:280px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Data Angket Siswa</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Rencana</th>
                                    <th>Cita-Cita</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($profiles as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($row['student_name']) ?></strong><br>
                                            <small class="text-muted"><?= esc($row['nis']) ?></small>
                                        </td>
                                        <td><?= esc($row['class_name']) ?></td>
                                        <td>
                                            <?php
                                                $colors = ['Kuliah'=>'primary','Kerja'=>'success','Wirausaha'=>'warning','Kursus/Diklat'=>'info','Belum Tahu'=>'secondary'];
                                                $plan   = $row['post_graduate_plan'];
                                                $color  = $colors[$plan] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= esc($plan) ?></span>
                                        </td>
                                        <td><?= esc($row['dream_job'] ?: '-') ?></td>
                                        <td>
                                            <a href="<?= base_url('admin/counseling-career/show/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($profiles)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada siswa yang mengisi angket.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode(array_column($statPlan, 'post_graduate_plan')) ?>;
    const values = <?= json_encode(array_column($statPlan, 'total')) ?>;
    new Chart(document.getElementById('planChart'), {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['Belum Ada Data'],
            datasets: [{
                data: values.length ? values : [1],
                backgroundColor: ['#4e73df','#1cc88a','#f6c23e','#36b9cc','#858796'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>

<?= $this->endSection() ?>
