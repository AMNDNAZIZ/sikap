<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📒 Catatan Siswa</h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<?php if (!empty($classes)): ?>
  <!-- Form pilih kelas -->
  <form method="get" class="mb-3 row g-2">
    <div class="col-md-3">
      <label class="form-label fw-bold">Tahun Ajaran:</label>
      <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
        <option value="all">Semua Tahun Ajaran</option>
        <?php foreach ($academicYears as $year): ?>
          <option value="<?= $year['id'] ?>" <?= $selectedYear == $year['id'] ? 'selected' : '' ?>>
            <?= esc($year['year']) ?> <?= $year['is_active'] ? '✅' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-bold">Pilih Kelas:</label>
      <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($classId == $c['id']) ? 'selected' : '' ?>>
            <?= esc($c['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-bold">Status Siswa:</label>
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
        <option value="aktif" <?= $selectedStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
        <option value="lulus" <?= $selectedStatus === 'lulus' ? 'selected' : '' ?>>Lulus</option>
        <option value="pindah" <?= $selectedStatus === 'pindah' ? 'selected' : '' ?>>Pindah</option>
        <option value="dropout" <?= $selectedStatus === 'dropout' ? 'selected' : '' ?>>Drop Out</option>
        <option value="nonaktif" <?= $selectedStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <a href="<?= base_url('admin/student-notes') ?>" class="btn btn-secondary w-100">🔄 Reset</a>
    </div>
  </form>
<?php endif; ?>

<?php if (!empty($students)): ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-success">
        <tr>
          <th>No</th>
          <th>NIS</th>
          <th>Nama</th>
          <th>JK</th>
          <th>Total Poin</th>
          <th>Predikat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        foreach ($students as $s):
          $points = $studentPoints[$s['id']] ?? 0;

          if ($points > 10)
            $predikat = "Sangat Baik";
          elseif ($points >= 0)
            $predikat = "Baik";
          elseif ($points >= -10)
            $predikat = "Kurang Baik";
          else
            $predikat = "Tidak Baik";
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($s['nis'] ?? '-') ?></td>
            <td class="text-start"><?= esc($s['name']) ?></td>
            <td><?= esc($s['gender']) ?></td>
            <td>
              <?php if ($points > 0): ?>
                <b class="text-success">+<?= $points ?></b>
              <?php elseif ($points < 0): ?>
                <b class="text-danger"><?= $points ?></b>
              <?php else: ?>
                <b>0</b>
              <?php endif; ?>
            </td>
            <td><?= $predikat ?></td>
            <td>
              <a href="<?= base_url('admin/student-notes/show/' . $s['id']) ?>" class="btn btn-sm btn-primary">Detail</a>
              <?php if (session()->get('user')['role_id'] != 2): ?>
                <a href="<?= base_url('admin/student-notes/create/' . $s['id']) ?>" class="btn btn-sm btn-success">+ Catatan</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php elseif ($classId): ?>
  <p class="text-muted">Belum ada siswa di kelas ini.</p>
<?php else: ?>
  <p class="text-muted">Silakan pilih kelas terlebih dahulu.</p>
<?php endif; ?>

<?= $this->endSection() ?>