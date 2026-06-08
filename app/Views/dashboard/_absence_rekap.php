<?php
$days = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$months = [
    'January' => 'Januari',
    'February' => 'Februari',
    'March' => 'Maret',
    'April' => 'April',
    'May' => 'Mei',
    'June' => 'Juni',
    'July' => 'Juli',
    'August' => 'Agustus',
    'September' => 'September',
    'October' => 'Oktober',
    'November' => 'November',
    'December' => 'Desember'
];
$dayName = $days[date('l', strtotime($rekapDate))];
$monthName = $months[date('F', strtotime($rekapDate))];
$formattedDate = $dayName . ', ' . date('j', strtotime($rekapDate)) . ' ' . $monthName . ' ' . date('Y', strtotime($rekapDate));
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 text-primary"><i class="bi bi-person-x-fill me-2"></i>Rekap Ketidakhadiran</h5>
            <small class="text-muted">
                <?= $formattedDate ?>
            </small>
        </div>
        <div style="width: 160px;">
            <input type="date" id="absenceDateInput" class="form-control form-control-sm" value="<?= $rekapDate ?>"
                max="<?= date('Y-m-d') ?>">
        </div>
    </div>
    <div class="card-body">
        <?php 
        // Check if selected date is holiday or weekend
        helper('holiday');
        $holidayInfo = get_holiday_info($rekapDate);
        ?>
        
        <?php if (!empty($holidayInfo) && $holidayInfo['is_holiday']): ?>
            <!-- Holiday/Weekend Notice -->
            <div class="alert alert-<?= $holidayInfo['color'] ?> border-0 mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="bi <?= $holidayInfo['icon'] ?> fs-3"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="alert-heading mb-1 fw-bold">
                            <?= $holidayInfo['type'] == 'holiday' ? 'Hari Libur Resmi' : 'Akhir Pekan' ?>
                        </h6>
                        <p class="mb-2"><?= esc($holidayInfo['description']) ?></p>
                        <small class="opacity-75">
                            <i class="bi bi-info-circle me-1"></i>
                            Tidak ada kegiatan belajar mengajar pada tanggal ini, sehingga tidak ada data ketidakhadiran yang perlu dicatat.
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($absenceRekap)): ?>
            <?php if (empty($holidayInfo) || !$holidayInfo['is_holiday']): ?>
                <!-- Regular day with no absences -->
                <div class="text-center py-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-2 text-muted mb-0">Semua siswa hadir pada tanggal ini.</p>
                    <small class="text-muted">Tidak ada siswa yang sakit, izin, atau alpa.</small>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $totalAbsent = 0;
            foreach ($absenceRekap as $className => $students):
                $totalAbsent += count($students);
                ?>
                <div class="mb-3">
                    <h6 class="fw-bold border-bottom pb-1">
                        <i class="bi bi-door-closed text-primary me-1"></i>
                        <?= esc($className) ?>
                    </h6>
                    <ol class="ps-3 mb-0">
                        <?php foreach ($students as $s): ?>
                            <li class="mb-1">
                                <?= esc($s['name']) ?>
                                <span
                                    class="badge bg-<?= ($s['status'] == 'A' ? 'danger' : ($s['status'] == 'S' ? 'warning' : 'info')) ?> rounded-pill ms-1">
                                    <?php 
                                    $statusLabel = [
                                        'A' => 'Alpa',
                                        'S' => 'Sakit',
                                        'I' => 'Izin'
                                    ];
                                    echo $statusLabel[$s['status']] ?? $s['status'];
                                    ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endforeach; ?>

            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold">Total Ketidakhadiran:</span>
                    <span class="badge bg-danger rounded-pill fs-6">
                        <?= $totalAbsent ?> Siswa
                    </span>
                </div>
                <div class="row text-center mt-3">
                    <?php
                    $statusCount = ['S' => 0, 'I' => 0, 'A' => 0];
                    foreach ($absenceRekap as $students) {
                        foreach ($students as $s) {
                            if (isset($statusCount[$s['status']])) {
                                $statusCount[$s['status']]++;
                            }
                        }
                    }
                    ?>
                    <div class="col-4">
                        <div class="p-2 bg-warning bg-opacity-10 rounded">
                            <div class="h5 mb-0 fw-bold text-warning"><?= $statusCount['S'] ?></div>
                            <small class="text-muted">Sakit</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-info bg-opacity-10 rounded">
                            <div class="h5 mb-0 fw-bold text-info"><?= $statusCount['I'] ?></div>
                            <small class="text-muted">Izin</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-danger bg-opacity-10 rounded">
                            <div class="h5 mb-0 fw-bold text-danger"><?= $statusCount['A'] ?></div>
                            <small class="text-muted">Alpa</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('absenceDateInput');
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                const selectedDate = this.value;
                window.location.href = '<?= site_url('dashboard') ?>?date=' + selectedDate;
            });
        }
    });
</script>