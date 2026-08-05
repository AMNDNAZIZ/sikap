<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Data Siswa</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('msg')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach (session('errors') as $e): ?>
        <li><?= esc($e) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
<?php endif ?>
<a href="<?= site_url('admin/siswa/import') ?>" class="btn btn-outline-success mb-3">📥 Import Excel</a>
<form method="post" action="<?= site_url('admin/siswa/save') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= esc($item['id'] ?? '') ?>">

  <div class="col-md-2">
    <input type="text" name="nis" class="form-control" placeholder="NIS"
      value="<?= esc(old('nis', $item['nis'] ?? '')) ?>" required>
  </div>
  <div class="col-md-3">
    <input type="text" name="nama" class="form-control" placeholder="Nama"
      value="<?= esc(old('nama', $item['nama'] ?? '')) ?>" required>
  </div>
  <div class="col-md-2">
    <button class="btn btn-<?= isset($item) ? 'warning' : 'primary' ?> w-100">
      <?= isset($item) ? 'Update' : 'Tambah' ?>
    </button>
  </div>
</form>

<form method="get" action="<?= site_url('admin/siswa') ?>" class="row g-2 mb-3">
  <div class="col-md-4">
    <input type="text" name="q" class="form-control" placeholder="Cari nama atau NIS"
      value="<?= esc($_GET['q'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-primary w-100">Cari</button>
  </div>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; ?>
    <?php foreach ($list as $s): ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= esc($s['nis']) ?></td>
        <td><?= esc($s['nama']) ?></td>
        <td>
          <a href="<?= site_url('admin/siswa/edit/' . esc($s['id'])) ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="<?= site_url('admin/siswa/delete/' . esc($s['id'])) ?>" onclick="return confirm('Yakin hapus?')"
            class="btn btn-sm btn-danger">Hapus</a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?= $pager->links('default', 'bootstrap') ?>

<?= $this->endSection() ?>