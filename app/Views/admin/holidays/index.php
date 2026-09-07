<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>📅 Daftar Hari Libur</h4>
  <div>
    <a href="<?= base_url('admin/holidays/create') ?>" class="btn btn-primary">+ Tambah Hari Libur</a>
    <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalSyncHoliday">
      🔄 Sync Libur Nasional (API)
    </button>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('info')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('info') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<div class="table-responsive">
  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($holidays)): 
        $page = request()->getVar('page') ?: 1;
        $perPage = 10;
        $no = 1 + ($page - 1) * $perPage;
        foreach ($holidays as $h): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= date('d-m-Y', strtotime($h['date'])) ?></td>
        <td><?= esc($h['description']) ?></td>
        <td>
          <a href="<?= base_url('admin/holidays/edit/'.$h['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
          <a href="<?= base_url('admin/holidays/delete/'.$h['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">🗑️ Hapus</a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="4" class="text-center">Belum ada data</td></tr>
      <?php endif ?>
    </tbody>
  </table>
</div>

<div class="mt-3">
  <?= $pager->links('default', 'bootstrap') ?>
</div>

<!-- Modal Sync Hari Libur -->
<div class="modal fade" id="modalSyncHoliday" tabindex="-1" aria-labelledby="modalSyncHolidayLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?= base_url('admin/holidays/sync') ?>" method="post">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title" id="modalSyncHolidayLabel">🔄 Sync Hari Libur Nasional</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            Fitur ini akan mengimpor data Hari Libur Nasional & Cuti Bersama secara otomatis dari 
            <strong>APIHariLibur_V2</strong> (Google Calendar / Pemerintah).
          </p>
          <div class="mb-3">
            <label for="sync_year" class="form-label font-weight-bold">Pilih Tahun</label>
            <select name="year" id="sync_year" class="form-select" required>
              <?php 
                $currentY = (int) date('Y');
                for ($y = $currentY - 1; $y <= $currentY + 2; $y++): 
              ?>
                <option value="<?= $y ?>" <?= $y === $currentY ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            Mulai Sync
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
