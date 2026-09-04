<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .bg-holiday   { background-color: #f8d7da !important; color: #842029; }
  .bg-weekend   { background-color: #e2e3e5 !important; color: #41464b; }
  .bg-no-sched  { background-color: #dc3545 !important; color: #fff !important; }
  .bg-not-rec   { background-color: #fff3cd !important; color: #664d03; }
  .grid-table th, .grid-table td { padding: 4px 5px; vertical-align: middle; }
  .grid-table thead th { position: sticky; top: 0; z-index: 2; }
  .col-sticky-0 { position: sticky; left: 0; z-index: 3; background: #fff; }
  .col-sticky-1 { position: sticky; left: 38px; z-index: 3; background: #fff; }
  thead .col-sticky-0, thead .col-sticky-1 { z-index: 4; }
</style>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0">&#128197; Grid Absensi Guru &mdash; <?= date('F Y', strtotime($month . '-01')) ?></h4>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= base_url('admin/teacher-attendance/grid-month/pdf?month=' . urlencode($month)) ?>"
         class="btn btn-danger btn-sm" target="_blank">&#128196; Cetak PDF</a>
      <a href="<?= base_url('admin/teacher-attendance/report?month=' . urlencode($month)) ?>" class="btn btn-outline-secondary btn-sm">&#128202; Laporan Bulanan</a>
      <a href="<?= base_url('admin/teacher-attendance') ?>" class="btn btn-outline-secondary btn-sm">&larr; Input Harian</a>
    </div>
  </div>

  <!-- Filter -->
  <div class="card shadow-sm mb-3">
    <div class="card-body py-2">
      <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
        <label class="fw-semibold mb-0">Bulan:</label>
        <input type="month" name="month" class="form-control form-control-sm" style="width:auto" value="<?= esc($month) ?>">
        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
      </form>
    </div>
  </div>

  <!-- Keterangan -->
  <div class="d-flex flex-wrap gap-2 mb-3 small">
    <span><span class="badge text-bg-success">3</span> = JP hadir penuh</span>
    <span><span class="badge text-bg-warning">2</span> = JP hadir sebagian</span>
    <span><span class="badge text-bg-danger">0</span> = Tidak hadir</span>
    <span><span class="badge bg-danger">&mdash;</span> = Tidak ada jadwal (merah)</span>
    <span><span class="badge bg-warning text-dark">?/n</span> = Ada jadwal, belum diisi</span>
    <span><span class="badge bg-secondary">&mdash;</span> = Akhir pekan / Libur</span>
  </div>

  <!-- Tabel -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <?php if (empty($rekap)): ?>
        <div class="text-center text-muted py-4"><p>Tidak ada data. Pastikan ada sesi absensi yang sudah diisi.</p></div>
      <?php else: ?>
        <div class="table-responsive" style="max-height:75vh;">
          <table class="table table-bordered grid-table mb-0 text-center" style="font-size:12px; min-width:900px;">
            <thead class="table-dark">
              <tr>
                <th class="col-sticky-0" style="width:38px;">No</th>
                <th class="col-sticky-1 text-start" style="min-width:160px;">Nama Guru</th>
                <?php
                  $hari = [1=>'Sn',2=>'Sl',3=>'Rb',4=>'Km',5=>'Jm',6=>'Sb',7=>'Mg'];
                  foreach ($dates as $d):
                    $dow = (int) date('N', strtotime($d));
                    $we  = ($dow >= 6);
                ?>
                <th class="<?= $we ? 'bg-secondary' : '' ?>" style="min-width:32px;">
                  <div><?= date('d', strtotime($d)) ?></div>
                  <div style="font-size:10px;opacity:.8;"><?= $hari[$dow] ?></div>
                </th>
                <?php endforeach; ?>
                <th style="min-width:48px;">Total JP</th>
                <th style="min-width:48px;">Hadir</th>
                <th style="min-width:48px;">TH</th>
                <th style="min-width:52px;">%</th>
              </tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach ($rekap as $r): ?>
              <tr>
                <td class="col-sticky-0 text-muted"><?= $no++ ?></td>
                <td class="col-sticky-1 text-start text-nowrap fw-semibold"><?= esc($r['teacher_name']) ?></td>
                <?php foreach ($dates as $d):
                  $cell = $r['daily'][$d] ?? ['status'=>'not_recorded','hadir'=>0,'total'=>0];
                  $st = $cell['status']; $h = $cell['hadir']; $tot = $cell['total'];
                  if ($st === 'holiday')          { $cls='bg-holiday'; $text='<span title="Libur">&mdash;</span>'; }
                  elseif ($st === 'weekend')      { $cls='bg-weekend'; $text=''; }
                  elseif ($st === 'no_schedule')  { $cls='bg-no-sched'; $text='<span title="Tidak ada jam">&mdash;</span>'; }
                  elseif ($st === 'not_recorded') {
                    if ($tot > 0) { $cls='bg-not-rec'; $text='?/'.$tot; }
                    else          { $cls='bg-no-sched'; $text='<span title="Tidak ada jam">&mdash;</span>'; }
                  } else {
                    $cls  = $h === 0 ? 'text-danger fw-bold' : ($h < $tot ? 'text-warning fw-bold' : 'text-success fw-bold');
                    $text = $h;
                  }
                ?>
                <td class="<?= $cls ?>"><?= $text ?></td>
                <?php endforeach; ?>
                <?php $pb = $r['persen'] >= 90 ? 'success' : ($r['persen'] >= 75 ? 'warning' : 'danger'); ?>
                <td class="fw-bold"><?= $r['total_jp'] ?></td>
                <td class="text-success fw-bold"><?= $r['jp_hadir'] ?></td>
                <td class="<?= $r['jp_th'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['jp_th'] ?></td>
                <td><span class="badge bg-<?= $pb ?>"><?= $r['persen'] ?>%</span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
