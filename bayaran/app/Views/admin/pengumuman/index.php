<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4><?= esc($title) ?></h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('msg')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/pengumuman/save') ?>" class="mb-4">
  <?= csrf_field() ?>
  <div class="mb-2">
    <input type="text" name="judul" class="form-control" placeholder="Judul Pengumuman" required>
  </div>
  <div class="mb-2">
    <textarea name="isi" class="form-control" rows="4" placeholder="Isi Pengumuman" required></textarea>
  </div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="aktif" value="1" checked>
    <label class="form-check-label">Aktif</label>
  </div>
  <button class="btn btn-primary">Tambah</button>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Judul</th>
      <th>Isi</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($list as $p): ?>
      <tr>
        <td><?= esc($p['judul']) ?></td>
        <td><?= esc($p['isi']) ?></td>
        <td>
          <?= $p['aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?>
        </td>
        <td>
          <a href="<?= site_url('admin/pengumuman/edit/' . esc($p['id'])) ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="<?= site_url('admin/pengumuman/delete/' . esc($p['id'])) ?>" class="btn btn-sm btn-danger"
            onclick="return confirm('Yakin hapus?')">Hapus</a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>