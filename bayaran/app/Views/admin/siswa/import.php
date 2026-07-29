<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Import Data Siswa (Excel)</h4>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form action="<?= site_url('admin/siswa/import') ?>" method="post" enctype="multipart/form-data" class="row g-3">
  <?= csrf_field() ?>
    <input type="file" name="excel" accept=".xlsx" class="form-control" required>
  </div>
  <div class="col-md-2">
    <button class="btn btn-success">Upload</button>
  </div>
</form>

<hr>
<p><strong>Template Excel:</strong> Kolom 1 = NIS, Kolom 2 = Nama</p>
<table class="table table-bordered w-50">
  <tr><th>NIS</th><th>Nama</th></tr>
  <tr><td>2024001</td><td>Ahmad Fauzi</td></tr>
  <tr><td>2024002</td><td>Siti Nurhaliza</td></tr>
</table>

<a href="<?= site_url('admin/siswa') ?>" class="btn btn-secondary mt-3">← Kembali ke Data Siswa</a>

<?= $this->endSection() ?>
