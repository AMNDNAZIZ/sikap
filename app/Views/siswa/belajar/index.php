<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-mortarboard me-2 text-primary"></i>Proses Belajar
  </h5>
  <?php if ($activeYear): ?>
    <span class="badge bg-secondary"><?= esc($activeYear['year']) ?></span>
  <?php endif; ?>
</div>

<!-- Filter tanggal -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 px-3">
    <form method="get" action="" class="d-flex align-items-center gap-3 flex-wrap">
      <div class="d-flex align-items-center gap-2 flex-grow-1">
        <i class="bi bi-calendar3 text-primary flex-shrink-0"></i>
        <input type="date" name="date" id="dateFilter"
               class="form-control form-control-sm"
               style="max-width:160px;"
               value="<?= esc($selectedDate) ?>"
               max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
        <button type="submit" class="btn btn-sm btn-primary px-3">Lihat</button>
        <?php if (!$isToday): ?>
          <a href="<?= base_url('siswa/belajar') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Hari Ini
          </a>
        <?php endif; ?>
      </div>
      <div class="text-muted small flex-shrink-0">
        <?php
          $tgl = date('d', strtotime($selectedDate));
          $bln = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
                  '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
          $blnStr = $bln[date('m', strtotime($selectedDate))] ?? '';
          $thn = date('Y', strtotime($selectedDate));
          echo $dayName . ', ' . $tgl . ' ' . $blnStr . ' ' . $thn;
        ?>
      </div>
    </form>
  </div>
</div>

<!-- Info konteks: jadwal atau semua mapel -->
<?php if ($hasSchedule && !$showAll): ?>
  <div class="alert alert-primary border-0 d-flex align-items-center gap-2 py-2 mb-3 small">
    <i class="bi bi-clock-fill fs-5 flex-shrink-0"></i>
    <div>
      <strong>Jadwal <?= $isToday ? 'Hari Ini' : 'Hari ' . esc($dayName) ?>:</strong>
      Menampilkan mata pelajaran sesuai jadwal.
      <a href="<?= base_url('siswa/belajar?date=' . $selectedDate . '&all=1') ?>"
         class="ms-1 fw-semibold">Lihat semua mapel &rarr;</a>
    </div>
  </div>
<?php elseif ($hasSchedule && $showAll): ?>
  <div class="alert alert-info border-0 d-flex align-items-center gap-2 py-2 mb-3 small">
    <i class="bi bi-list-ul fs-5 flex-shrink-0"></i>
    <div>
      Menampilkan semua mata pelajaran.
      <a href="<?= base_url('siswa/belajar?date=' . $selectedDate) ?>"
         class="ms-1 fw-semibold">&larr; Kembali ke jadwal <?= esc($dayName) ?></a>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-secondary border-0 d-flex align-items-center gap-2 py-2 mb-3 small">
    <i class="bi bi-calendar-x fs-5 flex-shrink-0 text-muted"></i>
    <div>
      Tidak ada jadwal <?= $isToday ? 'hari ini' : 'pada hari ' . esc($dayName) ?>.
      Menampilkan <strong>semua mata pelajaran</strong>.
    </div>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-warning py-2 small"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- Daftar mapel -->
<?php if (empty($subjects)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada mata pelajaran yang tersedia.</p>
  </div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($subjects as $sub): ?>
      <?php
        $pct   = (int)$sub['progress_pct'];
        $color = $pct >= 100 ? 'success' : ($pct >= 50 ? 'primary' : 'secondary');
      ?>
      <div class="col-sm-6 col-xl-4">
        <a href="<?= base_url('siswa/belajar/' . $sub['id']) ?>"
           class="card border-0 shadow-sm h-100 text-decoration-none text-dark belajar-card">
          <div class="card-body pb-2">

            <h6 class="fw-bold mb-3">
              <i class="bi bi-journal-text me-2 text-primary"></i>
              <?= esc($sub['name']) ?>
            </h6>

            <div class="d-flex gap-3 mb-3" style="font-size:0.8rem;">
              <span class="text-muted">
                <i class="bi bi-folder2 me-1"></i><?= $sub['total_parents'] ?> Materi
              </span>
              <span class="text-muted">
                <i class="bi bi-journals me-1"></i><?= $sub['total_sub'] ?> Sub Materi
              </span>
            </div>

            <?php if ($sub['total_sub'] > 0): ?>
              <div class="d-flex align-items-center gap-2" style="font-size:0.75rem;">
                <div class="progress flex-grow-1" style="height:6px;">
                  <div class="progress-bar bg-<?= $color ?>"
                       style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-<?= $color ?> fw-semibold flex-shrink-0">
                  <?= $sub['completed_sub'] ?>/<?= $sub['total_sub'] ?> selesai
                </span>
              </div>
            <?php else: ?>
              <div class="text-muted small">Belum ada sub materi.</div>
            <?php endif; ?>

          </div>
          <div class="card-footer bg-transparent border-top py-2">
            <span class="small text-primary fw-semibold">
              <i class="bi bi-arrow-right-circle me-1"></i>Mulai Belajar
            </span>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
.belajar-card { transition: transform .15s, box-shadow .15s; }
.belajar-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12) !important; }
</style>
<script>
// Submit form otomatis saat tanggal berubah
document.getElementById('dateFilter').addEventListener('change', function() {
  this.closest('form').submit();
});
</script>
<?= $this->endSection() ?>
