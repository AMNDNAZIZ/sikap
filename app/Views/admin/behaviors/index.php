<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>🎭 Daftar Perilaku & Poin</h4>

<a href="<?= base_url('admin/behaviors/create') ?>" class="btn btn-primary mb-3">+ Tambah Perilaku</a>

<div class="table-responsive">
<table class="table table-bordered align-middle">
  <thead class="table-success">
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>Poin</th>
      <th>Jenis</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; foreach ($behaviors as $b): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= esc($b['name']) ?></td>
        <td><?= esc($b['points']) ?></td>
        <td><?= $b['type'] == 'positive' ? 'Positif' : 'Negatif' ?></td>
        <td>
          <a href="<?= base_url('admin/behaviors/edit/'.$b['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
          <form action="<?= base_url('admin/behaviors/delete/'.$b['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>

<?= $this->endSection() ?>
