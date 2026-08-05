<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen Kelas</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form action="<?= site_url('admin/kelas/save') ?>" method="post" class="row g-2 mb-4">
  <?= csrf_field() ?>
    <input type="text" name="nama_kelas" class="form-control" placeholder="Nama Kelas" required>
  </div>
  <div class="col-md-4">
    <input type="text" name="keterangan" class="form-control" placeholder="Keterangan (opsional)">
  </div>
  <div class="col-md-1">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="aktif" checked>
      <label class="form-check-label">Aktif</label>
    </div>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Tambah</button>
  </div>
</form>

<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama</th>
      <th>Keterangan</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; ?>
    <?php foreach ($list as $item): ?>
    <tr>
      <td><?= $no++; ?></td>
      <td><?= esc($item['nama_kelas']) ?></td>
      <td><?= esc($item['keterangan']) ?></td>
      <td>
        <span class="badge <?= $item['aktif'] ? 'bg-success' : 'bg-secondary' ?>">
          <?= $item['aktif'] ? 'Aktif' : 'Nonaktif' ?>
        </span>
      </td>
      <td>
        <a href="<?= site_url('admin/kelas/delete/' . $item['id']) ?>" onclick="return confirm('Hapus kelas ini?')" class="btn btn-sm btn-danger">Hapus</a>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
