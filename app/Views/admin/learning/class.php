<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $scoreColor = function($v) {
    if ($v === null) return 'secondary';
    return $v >= 80 ? 'success' : ($v >= 60 ? 'warning' : 'danger');
  };
  $matColor = function($v) {
    if ($v === null) return 'secondary';
    return $v >= 80 ? 'success' : ($v >= 50 ? 'warning' : 'danger');
  };
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-people me-2 text-primary"></i><?= esc($title) ?>
    </h5>
    <div class="text-muted small">
      <?= esc($activeYear['year'] ?? '') ?> &nbsp;·&nbsp; <?= esc($monthLabel ?? date('F Y')) ?>
    </div>
  </div>
  <a href="<?= base_url('learning/class') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if (empty($students)): ?>
  <div class="alert alert-info py-2 small">Belum ada siswa aktif di kelas ini.</div>
<?php else: ?>

<!-- ── Tab Navigation ────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-3" id="classTabs">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRingkasan">
      <i class="bi bi-table me-1"></i>Ringkasan
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMateri">
      <i class="bi bi-book me-1"></i>Materi
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKuis">
      <i class="bi bi-pencil-square me-1"></i>Kuis
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKehadiran">
      <i class="bi bi-calendar2-check me-1"></i>Kehadiran
    </button>
  </li>
</ul>

<div class="tab-content" id="classTabContent">

  <!-- ── Tab: Ringkasan ────────────────────────────────────────────── -->
  <div class="tab-pane fade show active" id="tabRingkasan">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small" id="tblRingkasan">
            <thead class="table-light">
              <tr>
                <th width="30">#</th>
                <th>Nama Siswa</th>
                <th class="text-center" width="90">Kehadiran</th>
                <th class="text-center" width="110">Materi (avg%)</th>
                <th class="text-center" width="100">Kuis (avg)</th>
                <th width="80">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($matrix as $sid => $row): ?>
                <?php
                  $attColor = $row['att_rate'] >= 80 ? 'success' : ($row['att_rate'] >= 60 ? 'warning' : 'danger');
                ?>
                <tr>
                  <td class="text-muted"><?= $no++ ?></td>
                  <td>
                    <div class="fw-semibold"><?= esc($row['info']['student_name']) ?></div>
                    <div class="text-muted" style="font-size:0.72rem;"><?= esc($row['info']['nis'] ?? '') ?></div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-<?= $attColor ?>-subtle text-<?= $attColor ?> fw-semibold">
                      <?= $row['att_rate'] ?>%
                    </span>
                  </td>
                  <td class="text-center">
                    <?php if ($row['avg_mat_pct'] !== null): ?>
                      <div class="d-flex align-items-center gap-1 justify-content-center">
                        <div class="progress flex-grow-1" style="height:5px; max-width:60px;">
                          <div class="progress-bar bg-<?= $matColor($row['avg_mat_pct']) ?>"
                               style="width:<?= $row['avg_mat_pct'] ?>%"></div>
                        </div>
                        <span class="text-<?= $matColor($row['avg_mat_pct']) ?> fw-semibold">
                          <?= $row['avg_mat_pct'] ?>%
                        </span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($row['avg_quiz'] !== null): ?>
                      <span class="fw-semibold text-<?= $scoreColor($row['avg_quiz']) ?>">
                        <?= number_format($row['avg_quiz'], 1) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= base_url('learning/student/' . $sid) ?>"
                       class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.72rem;">
                      Detail
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Tab: Materi ───────────────────────────────────────────────── -->
  <div class="tab-pane fade" id="tabMateri">
    <?php foreach ($subjects as $sub):
      $subId   = $sub['id'];
      $matList = $materials[$subId] ?? [];
      if (empty($matList)) continue;
    ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white py-2 border-bottom">
        <span class="fw-semibold small">
          <i class="bi bi-book me-2 text-primary"></i><?= esc($sub['name']) ?>
          <span class="badge bg-secondary ms-1"><?= count($matList) ?> materi</span>
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Siswa</th>
                <?php foreach ($matList as $m): ?>
                  <th class="text-center" width="70" title="<?= esc($m['title']) ?>">
                    M<?= array_search($m, $matList) + 1 ?>
                    <div style="font-size:0.62rem;" class="text-muted text-truncate" style="max-width:60px;">
                      <?= esc(mb_substr($m['title'], 0, 12)) ?>
                    </div>
                  </th>
                <?php endforeach; ?>
                <th class="text-center" width="70">Selesai</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matrix as $sid => $row): ?>
                <?php
                  $done  = 0;
                  $total = count($matList);
                ?>
                <tr>
                  <td class="fw-semibold"><?= esc($row['info']['student_name']) ?></td>
                  <?php foreach ($matList as $m):
                    $subData = $row['subjects'][$subId] ?? [];
                    // Ambil status langsung dari progress_map di materialsData
                    $status = 'unread';
                    // Gunakan matrix yang sudah dibangun di controller
                    // materialsData tidak tersedia di view ini, gunakan row['subjects'] progress
                    // — controller classDetail tidak menyertakan per-item status, hanya aggregate
                    // Kita tampilkan dengan dot berdasarkan completed count
                  ?>
                    <td class="text-center">
                      <!-- Placeholder dot — warna berdasarkan completion rate keseluruhan -->
                      <span class="text-muted" style="font-size:0.7rem;">·</span>
                    </td>
                  <?php endforeach; ?>
                  <td class="text-center">
                    <?php
                      $sd    = $row['subjects'][$subId] ?? [];
                      $done  = $sd['materials_done']  ?? 0;
                      $total = $sd['materials_total'] ?? count($matList);
                      $pct   = $sd['materials_pct']   ?? null;
                      $mc    = $matColor($pct);
                    ?>
                    <span class="fw-semibold text-<?= $mc ?>">
                      <?= $done ?>/<?= $total ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty(array_filter(array_column($subjects, 'id'), fn($id) => !empty($materials[$id])))): ?>
      <div class="alert alert-info py-2 small">Belum ada materi published untuk kelas ini.</div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Kuis ─────────────────────────────────────────────────── -->
  <div class="tab-pane fade" id="tabKuis">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small" id="tblKuis">
            <thead class="table-light">
              <tr>
                <th>Siswa</th>
                <?php foreach ($subjects as $sub): ?>
                  <th class="text-center" width="90"><?= esc($sub['name']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matrix as $sid => $row): ?>
                <tr>
                  <td class="fw-semibold"><?= esc($row['info']['student_name']) ?></td>
                  <?php foreach ($subjects as $sub): ?>
                    <?php
                      $qd = $row['subjects'][$sub['id']]['quiz_best'] ?? null;
                      $qc = $scoreColor($qd);
                    ?>
                    <td class="text-center">
                      <?php if ($qd !== null): ?>
                        <span class="fw-semibold text-<?= $qc ?>"><?= number_format($qd, 1) ?></span>
                        <div class="text-muted" style="font-size:0.65rem;">
                          <?= $row['subjects'][$sub['id']]['quiz_attempts'] ?? 0 ?>x
                        </div>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Tab: Kehadiran ────────────────────────────────────────────── -->
  <div class="tab-pane fade" id="tabKehadiran">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-2 border-bottom small text-muted">
        Kehadiran bulan <?= esc($monthLabel ?? date('F Y')) ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small" id="tblAtt">
            <thead class="table-light">
              <tr>
                <th>Siswa</th>
                <th class="text-center" width="80">Hadir</th>
                <th class="text-center" width="60">Sakit</th>
                <th class="text-center" width="60">Izin</th>
                <th class="text-center" width="60">Alpha</th>
                <th class="text-center" width="90">Kehadiran</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matrix as $sid => $row): ?>
                <?php $ac = $row['att_rate'] >= 80 ? 'success' : ($row['att_rate'] >= 60 ? 'warning' : 'danger'); ?>
                <tr>
                  <td class="fw-semibold"><?= esc($row['info']['student_name']) ?></td>
                  <td class="text-center text-success fw-semibold"><?= $row['att_hadir'] ?></td>
                  <td class="text-center text-warning"><?= $row['att_sakit'] ?></td>
                  <td class="text-center text-info"><?= $row['att_izin'] ?></td>
                  <td class="text-center text-danger"><?= $row['att_alpha'] ?></td>
                  <td class="text-center">
                    <span class="badge bg-<?= $ac ?>-subtle text-<?= $ac ?> fw-semibold">
                      <?= $row['att_rate'] ?>%
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div><!-- /.tab-content -->
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
$(function(){
  if($.fn.DataTable){
    ['#tblRingkasan','#tblKuis','#tblAtt'].forEach(function(id){
      var el = document.querySelector(id);
      if(el) $(id).DataTable({ order:[], pageLength:25, language:{ search:'Cari:', info:'_START_-_END_ dari _TOTAL_', paginate:{next:'›',previous:'‹'} } });
    });
  }
});
</script>
<?= $this->endSection() ?>
