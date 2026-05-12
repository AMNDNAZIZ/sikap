<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📅 Daftar Hari Libur</h4>

<a href="<?= base_url('admin/holidays/create') ?>" class="btn btn-primary mb-3">+ Tambah Hari Libur</a>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<div class="table-responsive">
  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($holidays)): 
        $page = request()->getVar('page') ?: 1;
        $perPage = 10;
        $no = 1 + ($page - 1) * $perPage;
        foreach ($holidays as $h): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= date('d-m-Y', strtotime($h['date'])) ?></td>
        <td><?= esc($h['description']) ?></td>
        <td>
          <a href="<?= base_url('admin/holidays/edit/'.$h['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
          <a href="<?= base_url('admin/holidays/delete/'.$h['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">🗑️ Hapus</a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="4" class="text-center">Belum ada data</td></tr>
      <?php endif ?>
    </tbody>
  </table>
</div>

<div class="mt-3">
  <?= $pager->links('default', 'bootstrap') ?>
</div>

<?= $this->endSection() ?>
