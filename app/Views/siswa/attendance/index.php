<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">

  <!-- Header -->
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-calendar-check text-primary fs-5"></i>
    <h5 class="fw-bold mb-0">Presensi — <?= date('F Y', strtotime($month . '-01')) ?></h5>
  </div>

  <!-- Filter navigasi bulan -->
  <form method="get" class="mb-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <?php if ($prevMonth): ?>
            <a href="?month=<?= esc($prevMonth) ?>&activeOnly=<?= $activeOnly ? 1 : 0 ?>"
               class="btn btn-sm btn-outline-primary">◀</a>
          <?php endif; ?>

          <input type="month"
                 name="month"
                 value="<?= esc($month) ?>"
                 min="<?= esc($minMonth) ?>"
                 max="<?= esc($maxMonth) ?>"
                 onchange="this.form.submit()"
                 class="form-control form-control-sm"
                 style="max-width:160px;">

          <?php if ($nextMonth): ?>
            <a href="?month=<?= esc($nextMonth) ?>&activeOnly=<?= $activeOnly ? 1 : 0 ?>"
               class="btn btn-sm btn-outline-primary">▶</a>
          <?php endif; ?>

          <div class="form-check mb-0 ms-auto">
            <input type="checkbox" class="form-check-input" id="activeOnly"
                   name="activeOnly" value="1"
                   <?= $activeOnly ? 'checked' : '' ?>
                   onchange="this.form.submit()">
            <label class="form-check-label small" for="activeOnly">Hanya hari aktif</label>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- Tabel Absensi -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;" class="text-center">No</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach ($records as $r): ?>
              <tr class="<?= ($r['isWeekend'] || $r['isHoliday']) ? 'table-danger bg-opacity-50' : '' ?>">
                <td class="text-center"><?= $no++ ?></td>
                <td class="small"><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td>
                  <?php
                    $s = $r['status'] ?? '-';
                    $badge = match($s) {
                      'Hadir'  => 'bg-success',
                      'Sakit'  => 'bg-warning text-dark',
                      'Izin'   => 'bg-info text-dark',
                      'Alfa'   => 'bg-danger',
                      default  => 'bg-secondary',
                    };
                  ?>
                  <span class="badge <?= $badge ?>"><?= esc($s) ?></span>
                </td>
                <td class="small text-muted"><?= esc($r['note'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Rekap -->
  <div class="row g-3">

    <!-- Rekap Bulan -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent fw-semibold">
          <i class="bi bi-bar-chart-fill text-primary me-1"></i> Rekap Bulan <?= ($month === date('Y-m')) ? 'Ini' : '(' . date('M Y', strtotime($month . '-01')) . ')' ?>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr><th>Status</th><th class="text-end">Hari</th></tr>
            </thead>
            <tbody>
              <?php foreach ($summary as $label => $count): ?>
                <tr>
                  <td><?= esc($label) ?></td>
                  <td class="text-end fw-bold"><?= $count ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-between align-items-center">
            <span class="small text-muted">Persentase Kehadiran</span>
            <span class="fw-bold text-primary fs-5"><?= $monthAttendancePercentage ?>%</span>
          </div>
          <div class="progress mt-1" style="height:6px;">
            <div class="progress-bar bg-primary" style="width:<?= $monthAttendancePercentage ?>%"></div>
          </div>
          <small class="text-muted"><?= $summary['Hadir'] ?>/<?= $totalHariAktifBulan ?> hari aktif</small>
        </div>
      </div>
    </div>

    <!-- Rekap Semester -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent fw-semibold">
          <i class="bi bi-clipboard-data text-primary me-1"></i> Rekap <?= esc($semesterName) ?>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr><th>Status</th><th class="text-end">Hari</th></tr>
            </thead>
            <tbody>
              <?php foreach ($semesterSummary as $label => $count): ?>
                <tr>
                  <td><?= esc($label) ?></td>
                  <td class="text-end fw-bold"><?= $count ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-between align-items-center">
            <span class="small text-muted">Persentase Kehadiran</span>
            <span class="fw-bold text-primary fs-5"><?= $attendancePercentage ?>%</span>
          </div>
          <div class="progress mt-1" style="height:6px;">
            <div class="progress-bar bg-primary" style="width:<?= $attendancePercentage ?>%"></div>
          </div>
          <small class="text-muted"><?= $semesterSummary['Hadir'] ?>/<?= $totalHariAktifSemester ?> hari aktif</small>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
