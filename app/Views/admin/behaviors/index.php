<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>🎭 Daftar Perilaku & Poin</h4>

<a href="<?= base_url('admin/behaviors/create') ?>" class="btn btn-primary mb-3">+ Tambah Perilaku</a>

<table class="table table-bordered">
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
          <a href="<?= base_url('admin/behaviors/delete/'.$b['id']) ?>" 
             onclick="return confirm('Yakin hapus?')" 
             class="btn btn-sm btn-danger">Hapus</a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
