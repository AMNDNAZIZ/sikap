<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Generate Tagihan Siswa</h4>

<?php if(session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/tagihan/seting') ?>">
  <?= csrf_field() ?>
  <div class="row mb-3">
    <div class="col-md-4">
      <label for="kelas">Pilih Kelas</label>
      <select name="kelas_id" class="form-select" required>
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($kelas as $k): ?>
          <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-4">
      <label for="jenis">Pilih Jenis Bayar</label>
      <select name="jenis_bayar_id" class="form-select" required>
        <option value="">-- Pilih Jenis Bayar --</option>
        <?php foreach ($jenisBayar as $j): ?>
          <option value="<?= $j['id'] ?>"><?= esc($j['nama_jenis_bayar']) ?> (<?= $j['tipe'] ?>)</option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-primary">Generate</button>
    </div>
  </div>
</form>

<?= $this->endSection() ?>
