<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h5>📅 Absensi Bulan <?= date('F Y', strtotime($month . '-01')) ?></h5>

<form method="get" class="mb-3">
  <div class="mb-2 d-flex align-items-center gap-2">
    <?php if ($prevMonth): ?>
      <a href="?month=<?= esc($prevMonth) ?>&activeOnly=<?= $activeOnly ? 1 : 0 ?>"
         class="btn btn-sm btn-outline-primary">◀</a>
    <?php endif; ?>

    <input type="month"
           name="month"
           value="<?= esc($month) ?>"
           min="<?= esc($minMonth) ?>"
           max="<?= esc($maxMonth) ?>"
           onchange="this.form.submit()">

    <?php if ($nextMonth): ?>
      <a href="?month=<?= esc($nextMonth) ?>&activeOnly=<?= $activeOnly ? 1 : 0 ?>"
         class="btn btn-sm btn-outline-primary">▶</a>
    <?php endif; ?>
  </div>

  <div>
    <input type="checkbox" id="activeOnly" name="activeOnly" value="1"
      <?= $activeOnly ? 'checked' : '' ?>
      onchange="this.form.submit()">
    <label for="activeOnly">Tampilkan hanya hari aktif</label>
  </div>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th style="width:50px;">No</th>
      <th style="width:150px;">Tanggal</th>
      <th>Status</th>
      <th>Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; foreach ($records as $r): ?>
      <tr class="<?= ($r['isWeekend'] || $r['isHoliday']) ? 'table-danger' : '' ?>">
        <td><?= $no++ ?></td>
        <td><?= date('d-m-Y', strtotime($r['date'])) ?></td>
        <td><?= esc($r['status']) ?></td>
        <td><?= esc($r['note'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h5>Rekap Bulan Ini</h5>
<table class="table table-sm table-bordered" style="max-width:400px;">
  <thead>
    <tr>
      <th>Status</th>
      <th>Jumlah</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($summary as $label => $count): ?>
      <tr>
        <td><?= esc($label) ?></td>
        <td><?= $count ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h5>Rekap <?= esc($semesterName) ?></h5>
<table class="table table-sm table-bordered" style="max-width:400px;">
  <thead>
    <tr>
      <th>Status</th>
      <th>Jumlah</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($semesterSummary as $label => $count): ?>
      <tr>
        <td><?= esc($label) ?></td>
        <td><?= $count ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<p><strong>Persentase Kehadiran Semester:</strong> 
   <?= $attendancePercentage ?>% 
   (<?= $semesterSummary['Hadir'] ?>/<?= $totalHariAktifSemester ?> hari aktif)
</p>


<?= $this->endSection() ?>
