<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-envelope-paper me-2 text-primary"></i>Surat Keluar</h2>
    <small class="text-muted">Kelola pembuatan dan arsip surat keluar sekolah</small>
  </div>
  <div class="btn-group">
    <a href="<?= base_url('admin/surat-keluar/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-circle me-1"></i>Buat Surat Baru
    </a>
    <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= base_url('admin/surat-keluar/create') ?>"><i class="bi bi-pencil-square me-2"></i>Buat via Sistem</a></li>
      <li><a class="dropdown-item" href="<?= base_url('admin/surat-keluar/create-eksternal') ?>"><i class="bi bi-upload me-2"></i>Upload Surat Eksternal</a></li>
    </ul>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- ===== STATISTIK CARDS ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-primary" style="font-size:2rem;">📬</div>
        <div class="fw-bold fs-4 text-primary"><?= $stats['bulan_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Surat Bulan Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-info" style="font-size:2rem;">📋</div>
        <div class="fw-bold fs-4 text-info"><?= $stats['tahun_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Surat Tahun Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-success" style="font-size:2rem;">📁</div>
        <div class="fw-bold fs-4 text-success"><?= $stats['total'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Total Arsip</div>
      </div>
    </div>
  </div>
</div>

<!-- ===== FILTER ===== -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="<?= base_url('admin/surat-keluar') ?>" class="row g-2 align-items-end" id="form-filter">
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm fw-semibold mb-1">Cari</label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Nomor / Nama / Perihal..." value="<?= esc($filter['search']) ?>" id="filter-search">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Dari</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= esc($filter['date_from']) ?>" id="filter-from">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Sampai</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= esc($filter['date_to']) ?>" id="filter-to">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Jenis</label>
        <select name="letter_type" class="form-select form-select-sm" id="filter-type">
          <option value="">Semua Jenis</option>
          <?php foreach ($letter_types as $key => $label): ?>
            <option value="<?= $key ?>" <?= $filter['letter_type'] === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-1">
        <label class="form-label form-label-sm fw-semibold mb-1">Status</label>
        <select name="status" class="form-select form-select-sm" id="filter-status">
          <option value="">Semua</option>
          <option value="active"  <?= $filter['status'] === 'active'  ? 'selected' : '' ?>>Aktif</option>
          <option value="revoked" <?= $filter['status'] === 'revoked' ? 'selected' : '' ?>>Dicabut</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary btn-sm" id="btn-filter">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm" id="btn-reset">
          <i class="bi bi-x-circle me-1"></i>Reset
        </a>
      </div>
    </form>
  </div>
</div>

<!-- ===== TABEL ===== -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0 p-md-3">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0" style="font-size:0.85rem;">
        <thead class="table-light">
          <tr>
            <th>No.</th>
            <th>Nomor Surat</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Penerima</th>
            <th>Perihal</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($letters)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Belum ada surat keluar.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($letters as $i => $l): ?>
              <tr>
                <td><?= ($filter['page'] - 1) * $filter['limit'] + $i + 1 ?></td>
                <td class="fw-semibold font-monospace"><?= esc($l['letter_number']) ?></td>
                <td><?= date('d/m/Y', strtotime($l['issued_at'])) ?></td>
                <td>
                  <?php if ($l['letter_type'] === 'surat_eksternal'): ?>
                    <span class="badge bg-info"><i class="bi bi-upload me-1"></i>Eksternal</span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?= esc($letter_types[$l['letter_type']] ?? $l['letter_type']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= esc($l['recipient_name']) ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="<?= esc($l['subject']) ?>"><?= esc($l['subject']) ?></td>
                <td>
                  <?php if ($l['status'] === 'active'): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Dicabut</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= base_url('admin/surat-keluar/detail/' . $l['id']) ?>"
                     class="btn btn-outline-primary btn-sm" title="Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= base_url('admin/surat-keluar/download/' . $l['id']) ?>"
                     class="btn btn-outline-success btn-sm" title="Download PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($filter, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="text-muted text-end mt-2" style="font-size:0.8rem;">
      Total: <?= $total ?> surat
    </div>
  </div>
</div>

<?= $this->endSection() ?>
