<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7 mb-2 mb-md-0">
            <h1 class="h3 mb-1 text-gray-800">Rekapitulasi Presensi: <?= esc($ekskul['name']) ?></h1>
            <p class="text-muted mb-0">
                <i class="fas fa-calendar-alt mr-1"></i> Tahun Ajaran <?= esc($activeYear['year']) ?> &bull; 
                <span class="badge bg-primary px-2 py-1"><?= esc($periodLabel) ?></span>
            </p>
        </div>
        <div class="col-md-5 text-md-right">
            <a href="<?= base_url('admin/ekskul-pembina') ?>" class="btn btn-secondary btn-sm mr-1">
                <i class="fas fa-arrow-left fa-sm"></i> Dashboard
            </a>
            <a href="<?= base_url('admin/ekskul-pembina/jurnal/' . $ekskul['id']) ?>" class="btn btn-info btn-sm mr-1">
                <i class="fas fa-book fa-sm"></i> Jurnal & Absen
            </a>
            <a href="<?= base_url('admin/ekskul-pembina/rekap/print/' . $ekskul['id'] . '?type=' . $type . '&month=' . $month . '&year=' . $year . '&semester=' . $semester) ?>" 
               target="_blank" class="btn btn-success btn-sm">
                <i class="fas fa-print fa-sm"></i> Cetak Rekap
            </a>
        </div>
    </div>

    <!-- Mode Selector & Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <ul class="nav nav-pills card-header-pills" id="rekapTypeTabs">
                <li class="nav-item">
                    <a class="nav-link <?= $type === 'monthly' ? 'active' : '' ?>" 
                       href="<?= base_url('admin/ekskul-pembina/rekap/' . $ekskul['id'] . '?type=monthly&month=' . $month . '&year=' . $year) ?>">
                        <i class="fas fa-calendar-day mr-1"></i> Rekap Bulanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $type === 'semester' ? 'active' : '' ?>" 
                       href="<?= base_url('admin/ekskul-pembina/rekap/' . $ekskul['id'] . '?type=semester&semester=' . ($semester ?: '1')) ?>">
                        <i class="fas fa-layer-group mr-1"></i> Rekap Per Semester
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body bg-light">
            <?php if ($type === 'monthly'): ?>
                <!-- Filter Bulanan -->
                <form action="<?= base_url('admin/ekskul-pembina/rekap/' . $ekskul['id']) ?>" method="get" class="form-inline">
                    <input type="hidden" name="type" value="monthly">
                    
                    <label class="mr-2 font-weight-bold text-gray-700">Pilih Bulan:</label>
                    <select name="month" class="form-control form-control-sm mr-3 mb-2 mb-md-0" style="min-width: 140px;">
                        <?php 
                        $daftarBulan = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        foreach ($daftarBulan as $mNum => $mName): 
                        ?>
                            <option value="<?= $mNum ?>" <?= ((int)$month === $mNum) ? 'selected' : '' ?>>
                                <?= $mName ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="mr-2 font-weight-bold text-gray-700">Tahun:</label>
                    <select name="year" class="form-control form-control-sm mr-3 mb-2 mb-md-0" style="min-width: 100px;">
                        <?php 
                        $years = array_unique([$startYear, $endYear, (int)date('Y')]);
                        sort($years);
                        foreach ($years as $yVal):
                        ?>
                            <option value="<?= $yVal ?>" <?= ((int)$year === $yVal) ? 'selected' : '' ?>>
                                <?= $yVal ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter fa-sm mr-1"></i> Terapkan Filter
                    </button>
                </form>
            <?php else: ?>
                <!-- Filter Semester -->
                <form action="<?= base_url('admin/ekskul-pembina/rekap/' . $ekskul['id']) ?>" method="get" class="form-inline">
                    <input type="hidden" name="type" value="semester">
                    
                    <label class="mr-2 font-weight-bold text-gray-700">Pilih Semester:</label>
                    <div class="custom-control custom-radio custom-control-inline mr-3">
                        <input type="radio" id="sem1" name="semester" value="1" class="custom-control-input" <?= ($semester === '1') ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="sem1">Semester 1 (Ganjil &bull; Juli - Desember)</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline mr-3">
                        <input type="radio" id="sem2" name="semester" value="2" class="custom-control-input" <?= ($semester === '2') ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="sem2">Semester 2 (Genap &bull; Januari - Juni)</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter fa-sm mr-1"></i> Terapkan Filter
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ringkasan Statistik (Quick Stats) -->
    <div class="row">
        <!-- Total Anggota -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Anggota Aktif
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalMembers ?> Siswa</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pertemuan -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pertemuan Terlaksana
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalMeetings ?> Kali Pertemuan</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rata-rata Kehadiran -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Rata-rata Kehadiran
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?= $avgPercentage ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= min(100, $avgPercentage) ?>%" 
                                             aria-valuenow="<?= $avgPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Siswa 100% Hadir -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Kehadiran Sempurna (100%)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $fullAttendanceCount ?> Siswa</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-medal fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Rekapitulasi Presensi -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-1"></i> Matriks Rekapitulasi Presensi (<?= esc($periodLabel) ?>)
            </h6>
            <div class="mt-2 mt-md-0">
                <span class="badge bg-success text-white px-2 py-1 me-1">H = Hadir</span>
                <span class="badge bg-warning text-dark px-2 py-1 me-1">S = Sakit</span>
                <span class="badge bg-info text-dark px-2 py-1 me-1">I = Izin</span>
                <span class="badge bg-danger text-white px-2 py-1">A = Alfa</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($members)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle mr-1"></i> Belum ada anggota terdaftar pada ekstrakurikuler ini untuk tahun ajaran aktif.
                </div>
            <?php elseif ($totalMeetings === 0): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Belum ada jurnal pertemuan yang tercatat pada <strong><?= esc($periodLabel) ?></strong>.
                    <div class="mt-2">
                        <a href="<?= base_url('admin/ekskul-pembina/jurnal/create/' . $ekskul['id']) ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus fa-sm mr-1"></i> Input Jurnal Sekarang
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <?php if ($type === 'monthly'): ?>
                    <!-- TAMPILAN MATRIKS BULANAN (Menampilkan tiap tanggal pertemuan) -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm text-center align-middle" id="rekapBulananTable">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th rowspan="2" class="align-middle" style="width: 35px;">No</th>
                                    <th rowspan="2" class="align-middle text-start text-left" style="min-width: 90px; text-align: left !important;">NIS</th>
                                    <th rowspan="2" class="align-middle text-start text-left" style="min-width: 170px; text-align: left !important;">Nama Siswa</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 70px;">Kelas</th>
                                    <!-- Tanggal Pertemuan -->
                                    <th colspan="<?= count($jurnals) ?>" class="align-middle">Tanggal Pertemuan</th>
                                    <!-- Rekap Akumulasi -->
                                    <th colspan="4" class="align-middle">Total</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 50px;">Jml</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 80px;">% Hadir</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 90px;">Predikat</th>
                                </tr>
                                <tr>
                                    <?php foreach ($jurnals as $j): ?>
                                        <th class="align-middle px-1" style="font-size: 11px; min-width: 45px;" 
                                            title="Tgl: <?= date('d M Y', strtotime($j['date'])) ?>&#013;Materi: <?= esc($j['materi']) ?>">
                                            <?= date('d/m', strtotime($j['date'])) ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="align-middle bg-success text-white" style="width: 32px;" title="Hadir">H</th>
                                    <th class="align-middle bg-warning text-dark" style="width: 32px;" title="Sakit">S</th>
                                    <th class="align-middle bg-info text-white" style="width: 32px;" title="Izin">I</th>
                                    <th class="align-middle bg-danger text-white" style="width: 32px;" title="Alfa">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekapSiswa as $idx => $row): 
                                    $m = $row['member'];
                                ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td class="text-start text-left font-weight-bold" style="text-align: left !important;"><?= esc($m['nis']) ?></td>
                                        <td class="text-start text-left text-nowrap" style="text-align: left !important;"><?= esc($m['student_name']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= esc($m['class_name'] ?? '-') ?></span></td>
                                        
                                        <!-- Kehadiran per pertemuan -->
                                        <?php foreach ($jurnals as $j): 
                                            $detail = $row['meetingDetails'][$j['id']] ?? ['status' => 'hadir', 'notes' => ''];
                                            $st = $detail['status'];
                                        ?>
                                            <td class="px-1" title="<?= esc(ucfirst($st)) ?><?= !empty($detail['notes']) ? ' (' . esc($detail['notes']) . ')' : '' ?>">
                                                <?php if ($st === 'hadir'): ?>
                                                    <span class="badge bg-success text-white" style="font-size: 10px; min-width: 20px;">H</span>
                                                <?php elseif ($st === 'sakit'): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size: 10px; min-width: 20px;">S</span>
                                                <?php elseif ($st === 'izin'): ?>
                                                    <span class="badge bg-info text-dark" style="font-size: 10px; min-width: 20px;">I</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger text-white" style="font-size: 10px; min-width: 20px;">A</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <!-- Ringkasan H, S, I, A -->
                                        <td class="font-weight-bold text-success"><?= $row['h'] ?></td>
                                        <td class="font-weight-bold text-warning"><?= $row['s'] ?></td>
                                        <td class="font-weight-bold text-info"><?= $row['i'] ?></td>
                                        <td class="font-weight-bold text-danger"><?= $row['a'] ?></td>
                                        <td class="font-weight-bold text-gray-800"><?= $row['total_meetings'] ?></td>
                                        <td>
                                            <div class="font-weight-bold <?= $row['percentage'] >= 75 ? 'text-success' : ($row['percentage'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                <?= $row['percentage'] ?>%
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                                $predBadgeClass = !empty($row['predClass']) && str_contains($row['predClass'], 'bg-') ? $row['predClass'] : match($row['predicate'] ?? '') {
                                                    'Sangat Baik' => 'bg-success text-white',
                                                    'Baik'        => 'bg-info text-dark',
                                                    'Cukup'       => 'bg-warning text-dark',
                                                    default       => 'bg-danger text-white',
                                                };
                                            ?>
                                            <span class="badge <?= $predBadgeClass ?>">
                                                <?= esc($row['predicate']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="4" class="text-end text-right">Total Kehadiran Keseluruhan:</td>
                                    <?php 
                                    // Hitung total hadir per pertemuan
                                    foreach ($jurnals as $j): 
                                        $hCol = 0;
                                        foreach ($rekapSiswa as $r) {
                                            if (($r['meetingDetails'][$j['id']]['status'] ?? 'hadir') === 'hadir') {
                                                $hCol++;
                                            }
                                        }
                                    ?>
                                        <td class="text-success" style="font-size: 11px;" title="Total hadir"><?= $hCol ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-success"><?= $totalAllH ?></td>
                                    <td class="text-warning"><?= $totalAllS ?></td>
                                    <td class="text-info"><?= $totalAllI ?></td>
                                    <td class="text-danger"><?= $totalAllA ?></td>
                                    <td><?= $totalMeetings * $totalMembers ?></td>
                                    <td class="text-primary font-weight-bold"><?= $avgPercentage ?>%</td>
                                    <td>-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- TAMPILAN MATRIKS SEMESTER (Breakdown per bulan dalam semester) -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm text-center align-middle" id="rekapSemesterTable">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th rowspan="2" class="align-middle" style="width: 35px;">No</th>
                                    <th rowspan="2" class="align-middle text-start text-left" style="min-width: 90px; text-align: left !important;">NIS</th>
                                    <th rowspan="2" class="align-middle text-start text-left" style="min-width: 170px; text-align: left !important;">Nama Siswa</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 70px;">Kelas</th>
                                    <!-- Kolom Per Bulan (Kehadiran H/Total) -->
                                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): ?>
                                        <th colspan="2" class="align-middle px-1" style="min-width: 75px; font-size: 12px;">
                                            <?= esc(explode(' ', $mLabel)[0]) ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <!-- Total Semester -->
                                    <th colspan="4" class="align-middle">Total Semester</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 50px;">Total Temu</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 80px;">% Kehadiran</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 95px;">Predikat</th>
                                </tr>
                                <tr>
                                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): ?>
                                        <th class="align-middle bg-success text-white" style="width: 32px; font-size: 11px;" title="Hadir">H</th>
                                        <th class="align-middle bg-secondary text-white" style="width: 35px; font-size: 11px;" title="Tidak Hadir (S+I+A)">Tdk</th>
                                    <?php endforeach; ?>
                                    <th class="align-middle bg-success text-white" style="width: 32px;" title="Hadir">H</th>
                                    <th class="align-middle bg-warning text-dark" style="width: 32px;" title="Sakit">S</th>
                                    <th class="align-middle bg-info text-white" style="width: 32px;" title="Izin">I</th>
                                    <th class="align-middle bg-danger text-white" style="width: 32px;" title="Alfa">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekapSiswa as $idx => $row): 
                                    $m = $row['member'];
                                ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td class="text-start text-left font-weight-bold" style="text-align: left !important;"><?= esc($m['nis']) ?></td>
                                        <td class="text-start text-left text-nowrap" style="text-align: left !important;"><?= esc($m['student_name']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= esc($m['class_name'] ?? '-') ?></span></td>
                                        
                                        <!-- Per bulan -->
                                        <?php foreach ($monthsInPeriod as $mNum => $mLabel): 
                                            $mo = $row['monthlyDetails'][$mNum] ?? ['h' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
                                            $nonH = $mo['s'] + $mo['i'] + $mo['a'];
                                        ?>
                                            <td class="text-success font-weight-bold"><?= $mo['h'] ?></td>
                                            <td class="<?= $nonH > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>"><?= $nonH ?></td>
                                        <?php endforeach; ?>

                                        <!-- Ringkasan Semester -->
                                        <td class="font-weight-bold text-success"><?= $row['h'] ?></td>
                                        <td class="font-weight-bold text-warning"><?= $row['s'] ?></td>
                                        <td class="font-weight-bold text-info"><?= $row['i'] ?></td>
                                        <td class="font-weight-bold text-danger"><?= $row['a'] ?></td>
                                        <td class="font-weight-bold text-gray-800"><?= $row['total_meetings'] ?></td>
                                        <td>
                                            <div class="font-weight-bold <?= $row['percentage'] >= 75 ? 'text-success' : ($row['percentage'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                <?= $row['percentage'] ?>%
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                                $predBadgeClass = !empty($row['predClass']) && str_contains($row['predClass'], 'bg-') ? $row['predClass'] : match($row['predicate'] ?? '') {
                                                    'Sangat Baik' => 'bg-success text-white',
                                                    'Baik'        => 'bg-info text-dark',
                                                    'Cukup'       => 'bg-warning text-dark',
                                                    default       => 'bg-danger text-white',
                                                };
                                            ?>
                                            <span class="badge <?= $predBadgeClass ?>">
                                                <?= esc($row['predicate']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="4" class="text-end text-right">Total Akumulasi:</td>
                                    <?php foreach ($monthsInPeriod as $mNum => $mLabel): 
                                        $sumH = 0;
                                        $sumNonH = 0;
                                        foreach ($rekapSiswa as $r) {
                                            $sumH += $r['monthlyDetails'][$mNum]['h'] ?? 0;
                                            $sumNonH += ($r['monthlyDetails'][$mNum]['s'] ?? 0) + ($r['monthlyDetails'][$mNum]['i'] ?? 0) + ($r['monthlyDetails'][$mNum]['a'] ?? 0);
                                        }
                                    ?>
                                        <td class="text-success"><?= $sumH ?></td>
                                        <td class="<?= $sumNonH > 0 ? 'text-danger' : 'text-muted' ?>"><?= $sumNonH ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-success"><?= $totalAllH ?></td>
                                    <td class="text-warning"><?= $totalAllS ?></td>
                                    <td class="text-info"><?= $totalAllI ?></td>
                                    <td class="text-danger"><?= $totalAllA ?></td>
                                    <td><?= $totalMeetings * $totalMembers ?></td>
                                    <td class="text-primary font-weight-bold"><?= $avgPercentage ?>%</td>
                                    <td>-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <div class="card-footer bg-white text-muted small">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <strong>Keterangan Predikat:</strong>
                    <span class="badge bg-success text-white ms-1">Sangat Baik (&ge; 90%)</span>
                    <span class="badge bg-info text-dark ms-1">Baik (&ge; 75%)</span>
                    <span class="badge bg-warning text-dark ms-1">Cukup (&ge; 60%)</span>
                    <span class="badge bg-danger text-white ms-1">Kurang (&lt; 60%)</span>
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <em>Perhitungan: Persentase = (Jumlah Hadir &divide; Total Pertemuan) &times; 100%</em>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
