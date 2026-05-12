<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>🏷️ Kelas Aktif - Tahun Ajaran <?= esc($activeYear['year']) ?></h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-4">
        <select name="class_id" class="form-select">
          <option value="">-- Semua Kelas --</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $selectedClass == $c['id'] ? 'selected' : '' ?>>
              <?= esc($c['name']) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">🔍 Cari</button>
      </div>
      <div class="col-md-2">
        <a href="<?= base_url('admin/active-classes') ?>" class="btn btn-secondary w-100">🔄 Reset</a>
      </div>
    </form>
  </div>
</div>

<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Kelas</th>
      <th>Status</th>
      <?php if (session()->get('user')['role_id'] != 2): ?>
        <th>Aksi</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1;
    foreach ($records as $r): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= esc($r['nis']) ?></td>
        <td><?= esc($r['name']) ?></td>
        <td><?= esc($r['class_name'] ?? '-') ?></td>
        <td><span class="badge bg-secondary"><?= esc($r['status']) ?></span></td>
        <?php if (session()->get('user')['role_id'] != 2): ?>
          <td>
            <form method="post" action="<?= base_url('admin/active-classes/update/' . $r['id']) ?>" class="d-flex gap-2">
              <?= csrf_field() ?>
              <select name="class_id" class="form-select form-select-sm" style="width: 140px">
                <option value="">-</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $r['class_id'] == $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                  </option>
                <?php endforeach ?>
              </select>
              <select name="status" class="form-select form-select-sm" style="width: 120px">
                <?php foreach (['aktif', 'nonaktif', 'dropout', 'lulus'] as $st): ?>
                  <option value="<?= $st ?>" <?= $r['status'] == $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach ?>
              </select>
              <button class="btn btn-sm btn-primary">💾 Simpan</button>
            </form>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>