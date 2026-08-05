<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Manajemen Kelas Tahun Ajaran <?= esc($tahun['nama']) ?></h4>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form method="get" class="row mb-3">
  <div class="col-md-4">
    <label>Pilih Kelas</label>
    <select name="kelas_id" class="form-select" onchange="this.form.submit()" required>
      <option value="">-- Pilih Kelas --</option>
      <?php foreach ($kelas as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasFilter == $k['id'] ? 'selected' : '' ?>>
          <?= esc($k['nama_kelas']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>
</form>

<?php $no=1; ?>
<?php if ($kelasFilter && $list): ?>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Kelas Sekarang</th>
      <th>Pindah Kelas / Ubah Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($list as $s): ?>
    <tr>
      <td><?= $no++ ?></td>
      <td><?= esc($s['nis']) ?></td>
      <td><?= esc($s['nama']) ?></td>
      <td><?= esc($s['nama_kelas']) ?></td>
      <td>
        <form method="post" action="<?= site_url('admin/kelas-aktif/pindah') ?>" class="d-flex">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $s['id'] ?>">

          <select name="kelas_id" class="form-select form-select-sm me-2">
            <option value="">-- Pindah Kelas --</option>
            <?php foreach ($kelas as $k): ?>
              <?php if ($k['id'] != $s['kelas_id']): ?>
              <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
              <?php endif ?>
            <?php endforeach ?>
          </select>

          <select name="status" class="form-select form-select-sm me-2">
            <option value="">-- Ubah Status --</option>
            <option value="aktif" <?= $s['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="pindah" <?= $s['status'] == 'pindah' ? 'selected' : '' ?>>Pindah</option>
            <option value="do" <?= $s['status'] == 'do' ? 'selected' : '' ?>>Drop Out</option>
          </select>

          <button class="btn btn-sm btn-outline-primary">Proses</button>
        </form>
      </td>
      <td>
        <a href="<?= site_url('admin/kelas-aktif/delete/'.$s['id']) ?>"
           onclick="return confirm('Yakin ingin menghapus penempatan ini?')"
           class="btn btn-sm btn-danger">Hapus</a>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php elseif ($kelasFilter): ?>
<div class="alert alert-warning">Tidak ada siswa di kelas ini.</div>
<?php endif ?>

<?= $this->endSection() ?>
