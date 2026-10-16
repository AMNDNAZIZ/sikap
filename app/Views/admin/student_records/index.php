<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Riwayat Siswa (Per Tahun)</h2>

<form class="row g-2 mb-3" method="get" action="">
  <div class="col-md-3">
    <select name="year_id" class="form-select">
      <option value="all" <?= ($selectedYear === 'all') ? 'selected' : '' ?>>
        — Semua Tahun Ajaran —
      </option>
      <?php foreach ($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= ($selectedYear == $y['id']) ? 'selected' : '' ?>>
          <?= esc($y['year']) ?><?= $y['is_active'] ? ' (aktif)' : '' ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <select name="class_id" class="form-select">
      <option value="">Semua Kelas</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($selectedClass == $c['id']) ? 'selected' : '' ?>>
          <?= esc($c['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <input type="text" class="form-control" name="search" value="<?= esc($search) ?>" placeholder="Cari NIS/Nama">
  </div>

  <div class="col-md-3 d-grid">
    <button class="btn btn-primary">Filter</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>No</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>Kelas</th>
        <th>Tahun</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-3">Tidak ada data.</td>
        </tr>
      <?php else: ?>
        <?php $no = 1;
        foreach ($records as $r): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($r['nis']) ?></td>
            <td><?= esc($r['name']) ?></td>
            <td><?= esc($r['class_name'] ?? '-') ?></td>
            <td><?= esc($r['year']) ?></td>
            <td><span
                class="badge bg-<?= $r['status'] === 'aktif' ? 'success' : ($r['status'] === 'lulus' ? 'primary' : 'secondary') ?>">
                <?= esc(ucfirst($r['status'])) ?></span>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <a class="btn btn-sm btn-outline-primary"
                  href="<?= base_url('admin/student-records/' . $r['student_id']) ?>"
                  title="Lihat Riwayat Siswa">
                  <i class="bi bi-clock-history me-1"></i> Riwayat
                </a>
                <a class="btn btn-sm btn-outline-success"
                  href="<?= site_url('admin/grades/tracking?student_id=' . $r['student_id']) ?>"
                  title="Tracking Nilai Siswa">
                  <i class="bi bi-activity me-1"></i> Tracking Nilai
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      <?php endif ?>
    </tbody>
  </table>
</div>

<?= $pager->links('default', 'bootstrap') ?>

<?= $this->endSection() ?>