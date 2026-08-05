<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen Pos Bayar</h4>

<?php if(session()->getFlashdata('msg')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/pos-bayar/save') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">

  <div class="col-md-4">
    <input type="text" name="nama_pos" class="form-control" placeholder="Nama Pos"
           value="<?= esc($item['nama_pos'] ?? '') ?>" required>
  </div>
  <div class="col-md-4">
    <input type="text" name="keterangan" class="form-control" placeholder="Keterangan"
           value="<?= esc($item['keterangan'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <div class="form-check mt-2">
      <input type="checkbox" name="aktif" class="form-check-input"
             <?= isset($item['aktif']) && $item['aktif'] ? 'checked' : '' ?>>
      <label class="form-check-label">Aktif</label>
    </div>
  </div>
  <div class="col-md-2">
    <button class="btn btn-<?= isset($item) ? 'warning' : 'primary' ?> w-100">
      <?= isset($item) ? 'Update' : 'Tambah' ?>
    </button>
  </div>
</form>


<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama Pos</th>
      <th>Keterangan</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; ?>
    <?php foreach ($list as $item): ?>
    <tr>
      <td><?= $no++; ?></td>
      <td><?= esc($item['nama_pos']) ?></td>
      <td><?= esc($item['keterangan']) ?></td>
      <td>
        <span class="badge <?= $item['aktif'] ? 'bg-success' : 'bg-secondary' ?>">
          <?= $item['aktif'] ? 'Aktif' : 'Nonaktif' ?>
        </span>
      </td>
      <td>
        <a href="<?= site_url('admin/pos-bayar/edit/'.$item['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
        <a href="<?= site_url('admin/pos-bayar/delete/'.$item['id']) ?>" onclick="return confirm('Hapus data?')" class="btn btn-sm btn-danger">Hapus</a>
      </td>

    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
