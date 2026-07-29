<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Backup & Restore Database</h4>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif ?>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session('success') ?></div>
<?php endif ?>

<div class="row">
  <div class="col-md-6">
    <div class="card border-primary mb-4">
      <div class="card-header bg-primary text-white">🗄 Backup Database</div>
      <div class="card-body">
        <p>Tekan tombol di bawah untuk membackup seluruh database ke dalam file ZIP.</p>
        <a href="<?= site_url('admin/backup-restore/backup') ?>" class="btn btn-success">Backup Sekarang</a>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-warning mb-4">
      <div class="card-header bg-warning">🔁 Restore Database</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('admin/backup-restore/restore') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Pilih file backup (.zip)</label>
            <input type="file" name="backup_file" class="form-control" required accept=".zip">
          </div>
          <button class="btn btn-danger">Restore Sekarang</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
