<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen Jenis Bayar - Tahun Ajaran <?= esc($tahun['nama']) ?></h4>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<!-- Form Tambah Jenis Bayar (tanpa tarif default) -->
<form method="post" action="<?= site_url('admin/jenis-bayar/save') ?>" class="row g-2 mb-4">
  <?= csrf_field() ?>
  <input type="hidden" name="tahun_ajaran_id" value="<?= $tahun['id'] ?>">

  <div class="col-md-3">
    <select name="pos_bayar_id" class="form-select" required>
      <option value="">Pilih Pos Bayar</option>
      <?php foreach ($pos as $p): ?>
        <option value="<?= $p['id'] ?>"><?= esc($p['nama_pos']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <input type="text" name="nama_jenis_bayar" class="form-control" placeholder="Nama Jenis Bayar" required>
  </div>

  <div class="col-md-2">
    <select name="tipe" class="form-select" required>
      <option value="bulanan">Bulanan</option>
      <option value="bebas">Bebas</option>
    </select>
  </div>

  <div class="col-md-2">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="wajib" value="1" checked>
      <label class="form-check-label">Wajib</label>
    </div>
  </div>

  <div class="col-md-2">
    <button class="btn btn-primary w-100">Tambah</button>
  </div>
</form>

<!-- Daftar Jenis Bayar -->
<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Pos</th>
      <th>Jenis</th>
      <th>Tipe</th>
      <th>Wajib</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; ?>
    <?php foreach ($list as $item): ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= esc($item['nama_pos']) ?></td>
        <td><?= esc($item['nama_jenis_bayar']) ?></td>
        <td><span class="badge bg-info"><?= esc($item['tipe']) ?></span></td>
        <td><?= $item['wajib'] ? 'Ya' : 'Tidak' ?></td>
        <td>
          <a href="<?= site_url('admin/tagihan/seting/'.$item['id']) ?>" class="btn btn-sm btn-success">
            Seting Tarif
          </a>
          <a href="<?= site_url('admin/jenis-bayar/delete/'.$item['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">
            Hapus
          </a>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
