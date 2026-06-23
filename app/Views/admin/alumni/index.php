<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>🎓 Data Alumni</h4>
  <a href="<?= base_url('admin/alumni/export-pdf' . ($selectedYear ? '?year=' . urlencode($selectedYear) : '')) ?>"
     class="btn btn-danger" target="_blank">
    <i class="bi bi-file-pdf"></i> Export PDF
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<div class="card">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-3">
        <select name="year" class="form-select">
          <option value="">Semua Tahun</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= esc($y['year']) ?>" <?= $selectedYear == $y['year'] ? 'selected' : '' ?>>
              <?= esc($y['year']) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Cari nama / NIS / NISN..."
               value="<?= esc($search) ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
      </div>
      <?php if ($selectedYear || $search): ?>
        <div class="col-md-2">
          <a href="<?= base_url('admin/alumni') ?>" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
      <?php endif ?>
    </form>

    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th width="5%">No</th>
            <th>NISN</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>JK</th>
            <th>Kelas Terakhir</th>
            <th>Tahun Ajaran</th>
            <th>Tanggal Lulus</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($alumni)): ?>
            <?php $no = 1; foreach ($alumni as $a): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($a['nisn']) ?></td>
                <td><?= esc($a['nis']) ?></td>
                <td><?= esc($a['name']) ?></td>
                <td><?= $a['gender'] == 'L' ? 'L' : 'P' ?></td>
                <td><?= esc($a['class_name'] ?? '-') ?></td>
                <td><?= esc($a['academic_year'] ?? '-') ?></td>
                <td><?= $a['graduation_date'] ? date('d/m/Y', strtotime($a['graduation_date'])) : '-' ?></td>
              </tr>
            <?php endforeach ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted">Belum ada data alumni.</td>
            </tr>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="d-flex justify-content-center mt-3">
  <?= $pager->links('default', 'bootstrap') ?>
</div>

<?= $this->endSection() ?>
