<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📌 Pilih Siswa untuk Catatan</h4>

<div class="card mb-3">
  <div class="card-body">
    <?php if ($roleId == 1 || $roleId == 2): ?>
      <!-- Admin / Kepala Sekolah: pilih kelas -->
      <form method="get" class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Pilih Kelas</label>
          <select name="class_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Kelas --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($selectedClass == $c['id']) ? 'selected':'' ?>>
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

    <?php elseif ($roleId == 3): ?>
      <!-- Guru: jika kelas sudah terdeteksi sebagai wali & hanya 1 -->
      <?php if (!empty($classes) && count($classes) == 1): ?>
        <div class="alert alert-info">Anda adalah wali kelas: <strong><?= esc($classes[0]['name']) ?></strong>. Daftar siswa ada di bawah.</div>
      <?php else: ?>
        <form method="get" class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Pilih Kelas (yang Anda ampu)</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- Pilih Kelas --</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($selectedClass == $c['id']) ? 'selected':'' ?>>
                  <?= esc($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($students)): ?>
  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped table-bordered">
        <thead class="table-light">
          <tr>
            <th>No</th>
            <th>Induk</th>
            <th>Nama</th>
            <th>JK</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; foreach ($students as $s): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($s['nis'] ?? '-') ?></td>
              <td><?= esc($s['name']) ?></td>
              <td><?= esc($s['gender']) ?></td>
              <td>
                <a href="<?= base_url('admin/student-notes/show/'.$s['id']) ?>" class="btn btn-sm btn-primary">Lihat Catatan</a>
                <a href="<?= base_url('admin/student-notes/create/'.$s['id']) ?>" class="btn btn-sm btn-success">Tambah Catatan</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <?php if ($selectedClass): ?>
    <div class="alert alert-warning">Belum ada siswa untuk kelas ini atau siswa belum ditempatkan pada tahun ajaran aktif.</div>
  <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
