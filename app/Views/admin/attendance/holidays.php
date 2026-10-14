<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<form class="d-flex flex-column flex-sm-row gap-2 mb-3" method="get">
  <input type="month" name="month" value="<?= esc($month) ?>" class="form-control" style="max-width: 260px;">
  <button class="btn btn-secondary">Tampilkan</button>
</form>

<form class="row g-2 mb-3" method="post" action="<?= base_url('admin/holidays/store') ?>">
  <?= csrf_field() ?>
  <div class="col-12 col-sm-auto"><input type="date" name="date" class="form-control" required></div>
  <div class="col-12 col-sm"><input type="text" name="title" class="form-control" placeholder="Nama libur" required></div>
  <div class="col-12 col-sm-auto"><button class="btn btn-primary w-100">Tambah</button></div>
</form>

<div class="table-responsive">
<table class="table table-bordered align-middle">
  <thead class="table-light"><tr><th>Tanggal</th><th>Keterangan</th><th width="100">Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($holidays as $h): ?>
    <tr>
      <td><?= esc($h['date']) ?></td>
      <td><?= esc($h['title']) ?></td>
      <td><a class="btn btn-sm btn-danger" href="<?= base_url('admin/holidays/delete/'.$h['id']) ?>" onclick="return confirm('Hapus libur?')">Hapus</a></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>

<?= $this->endSection() ?>
