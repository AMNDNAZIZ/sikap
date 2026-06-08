<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">📝 Nilai Erapor</h4>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">Pilih Guru & Tahun Ajaran</div>
  <div class="card-body">
    <form method="get" action="<?= site_url('admin/erapor/subjects') ?>" class="row g-3">
      <div class="col-md-5">
        <label class="form-label fw-semibold">Guru</label>
        <select name="teacher_id" class="form-select select2" required>
          <option value="">-- Pilih Guru --</option>
          <?php foreach ($teachers as $t): ?>
            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Tahun Ajaran</label>
        <select name="year_id" class="form-select">
          <?php foreach ($years as $y): ?>
            <option value="<?= $y['id'] ?>" <?= ($activeYear && $y['id'] == $activeYear['id']) ? 'selected' : '' ?>>
              <?= esc($y['year']) ?> <?= $y['is_active'] ? '(Aktif)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Lihat Daftar Mapel →</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
