<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-envelope-arrow-down me-2 text-success"></i>Surat Masuk</h2>
    <small class="text-muted">Arsip dan pencatatan surat masuk sekolah</small>
  </div>
  <a href="<?= base_url('admin/surat-masuk/create') ?>" class="btn btn-success btn-sm" id="btn-tambah-surat-masuk">
    <i class="bi bi-plus-circle me-1"></i>Catat Surat Masuk
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-success" style="font-size:2rem;">📥</div>
        <div class="fw-bold fs-4 text-success"><?= $stats['bulan_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Surat Bulan Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-info" style="font-size:2rem;">📦</div>
        <div class="fw-bold fs-4 text-info"><?= $stats['tahun_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Surat Tahun Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-secondary" style="font-size:2rem;">📂</div>
        <div class="fw-bold fs-4 text-secondary"><?= $stats['total'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Total Arsip</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="<?= base_url('admin/surat-masuk') ?>" class="row g-2 align-items-end" id="form-filter">
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm fw-semibold mb-1">Cari</label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Nomor / Penanda Tangan / Perihal..." value="<?= esc($filter['search']) ?>" id="filter-search">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Dari</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= esc($filter['date_from']) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Sampai</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= esc($filter['date_to']) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Kategori</label>
        <select name="category" class="form-select form-select-sm" id="filter-category">
          <option value="">Semua</option>
          <?php foreach ($categories as $key => $label): ?>
            <option value="<?= $key ?>" <?= $filter['category'] === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-success btn-sm" id="btn-filter">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="<?= base_url('admin/surat-masuk') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-x-circle me-1"></i>Reset
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0 p-md-3">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0" style="font-size:0.85rem;">
        <thead class="table-light">
          <tr>
            <th>No.</th>
            <th>Tgl Diterima</th>
            <th>Nomor Surat</th>
            <th>Penanda Tangan</th>
            <th>Instansi</th>
            <th>Perihal</th>
            <th>Kategori</th>
            <th>File</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($letters)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>Belum ada surat masuk.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($letters as $i => $l): ?>
              <tr>
                <td><?= ($filter['page'] - 1) * $filter['limit'] + $i + 1 ?></td>
                <td><?= date('d/m/Y', strtotime($l['received_at'])) ?></td>
                <td class="font-monospace small"><?= esc($l['letter_number'] ?? '—') ?></td>
                <td class="fw-semibold"><?= esc($l['sender_name']) ?></td>
                <td><?= esc($l['sender_agency'] ?? '—') ?></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="<?= esc($l['subject']) ?>"><?= esc($l['subject']) ?></td>
                <td>
                  <?php if (!empty($l['letter_category'])): ?>
                    <span class="badge bg-light text-dark border"><?= esc($categories[$l['letter_category']] ?? $l['letter_category']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($l['scan_path'])): ?>
                    <a href="<?= base_url('admin/surat-masuk/scan/' . $l['id']) ?>" target="_blank"
                       class="btn btn-outline-info btn-sm" title="Lihat Scan">
                      <i class="bi bi-eye"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= base_url('admin/surat-masuk/detail/' . $l['id']) ?>"
                     class="btn btn-outline-primary btn-sm" title="Detail"><i class="bi bi-eye"></i></a>
                  <a href="<?= base_url('admin/surat-masuk/edit/' . $l['id']) ?>"
                     class="btn btn-outline-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                  <a href="<?= base_url('admin/surat-masuk/delete/' . $l['id']) ?>"
                     class="btn btn-outline-danger btn-sm" title="Hapus"
                     onclick="return confirm('Hapus surat masuk ini?')" id="btn-del-<?= $l['id'] ?>">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

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

    <div class="text-muted text-end mt-2" style="font-size:0.8rem;">Total: <?= $total ?> surat</div>
  </div>
</div>

<?= $this->endSection() ?>
