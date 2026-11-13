<?php
// 🎯 Standar Skill Diterapkan: high-end-visual-design (UI)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .transition-all {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
  }
  .hover-translate-y:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.08) !important;
  }
  .btn:active {
    transform: scale(0.98) !important;
  }
  .action-btn-pill {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50rem;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .action-btn-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  }
  .border-start-md {
    border-left: 1px solid #e2e8f0;
  }
  .hover-primary {
    transition: color 0.15s ease-in-out;
  }
  .hover-primary:hover {
    color: var(--bs-primary, #0d6efd) !important;
  }
  @media (max-width: 767.98px) {
    .border-start-md {
      border-left: none !important;
      border-top: 1px solid #e2e8f0;
      padding-top: 1rem;
    }
  }
</style>

<!-- ===== TOP HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <div class="d-inline-flex align-items-center gap-1.5 px-2.5 py-0.5 rounded-pill bg-primary-subtle text-primary border border-primary-subtle text-uppercase tracking-wider fw-semibold mb-1" style="font-size:0.65rem; letter-spacing:0.06em;">
      <i class="bi bi-envelope-paper-fill"></i> Administrasi &amp; Persuratan
    </div>
    <h2 class="h4 fw-bold mb-0 text-dark">Arsip &amp; Pembuatan Surat Keluar</h2>
    <small class="text-muted">Kelola penerbitan naskah dinas, verifikasi QR code, dan arsip dokumen sekolah</small>
  </div>
  <div class="d-flex align-items-center gap-2">
    <div class="btn-group shadow-xs">
      <a href="<?= base_url('admin/surat-keluar/create') ?>" class="btn btn-primary btn-sm rounded-pill ps-3 pe-2 py-1.5 d-flex align-items-center gap-2">
        <span class="fw-semibold">Buat Surat Baru</span>
        <span class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width:22px; height:22px;">
          <i class="bi bi-plus-lg text-white" style="font-size:12px;"></i>
        </span>
      </a>
      <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split rounded-pill ms-1" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="visually-hidden">Toggle Dropdown</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-1">
        <li>
          <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('admin/surat-keluar/create') ?>">
            <i class="bi bi-pencil-square text-primary"></i>Buat via Editor Sistem
          </a>
        </li>
        <li>
          <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('admin/surat-keluar/create-eksternal') ?>">
            <i class="bi bi-upload text-info"></i>Upload PDF Eksternal
          </a>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('admin/settings/kop-surat') ?>">
            <i class="bi bi-gear text-secondary"></i>Atur KOP &amp; Penomoran
          </a>
        </li>
      </ul>
    </div>
    <a href="<?= base_url('admin/settings/kop-surat') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs" title="Pengaturan Nomor Terakhir & KOP Surat">
      <i class="bi bi-gear me-1"></i>Atur Nomor
    </a>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-xs d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill text-success fs-5"></i>
    <div class="flex-grow-1 small"><?= session()->getFlashdata('success') ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
    <div class="flex-grow-1 small"><?= session()->getFlashdata('error') ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- ===== STATISTIK BENTO RIBBON (DOUBLE-BEZEL) ===== -->
<div class="p-1.5 bg-slate-100 border border-slate-200/80 rounded-4 mb-4 shadow-xs" style="background:#f8fafc; border:1px solid #e2e8f0;">
  <div class="bg-white rounded-3 p-3 p-md-3 border border-slate-100">
    <div class="row g-3 align-items-center">

      <!-- Stat 1: Bulan Ini -->
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary" style="width:46px; height:46px; min-width:46px;">
            <i class="bi bi-send-check-fill fs-5"></i>
          </div>
          <div>
            <div class="text-uppercase fw-semibold text-muted" style="font-size:0.68rem; letter-spacing:0.07em;">Surat Bulan Ini</div>
            <div class="d-flex align-items-baseline gap-2">
              <span class="fs-4 fw-bold text-dark font-monospace"><?= esc($stats['bulan_ini']) ?></span>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-0.5 font-sans" style="font-size:0.68rem;">Diterbitkan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stat 2: Tahun Ini -->
      <div class="col-12 col-md-4 border-start-md">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="d-flex align-items-center justify-content-center rounded-3 bg-info-subtle text-info" style="width:46px; height:46px; min-width:46px;">
            <i class="bi bi-calendar2-range-fill fs-5"></i>
          </div>
          <div>
            <div class="text-uppercase fw-semibold text-muted" style="font-size:0.68rem; letter-spacing:0.07em;">Registrasi Tahun Ini</div>
            <div class="d-flex align-items-baseline gap-2">
              <span class="fs-4 fw-bold text-dark font-monospace"><?= esc($stats['tahun_ini']) ?></span>
              <span class="text-muted small" style="font-size:0.72rem;">Dokumen</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stat 3: Total Arsip -->
      <div class="col-12 col-md-4 border-start-md">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="d-flex align-items-center justify-content-center rounded-3 text-success" style="width:46px; height:46px; min-width:46px; background:#ecfdf5; color:#059669;">
            <i class="bi bi-archive-fill fs-5"></i>
          </div>
          <div>
            <div class="text-uppercase fw-semibold text-muted" style="font-size:0.68rem; letter-spacing:0.07em;">Total Seluruh Arsip</div>
            <div class="d-flex align-items-baseline gap-2">
              <span class="fs-4 fw-bold text-dark font-monospace"><?= esc($stats['total']) ?></span>
              <span class="text-muted small" style="font-size:0.72rem;">Berkas Resmi</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php
// Helper untuk sorting kolom tabel
$currentSort = $filter['sort_by'] ?? 'issued_at';
$currentOrder = strtolower($filter['order'] ?? 'desc');

$buildSortUrl = function(string $column) use ($filter, $currentSort, $currentOrder) {
    $nextOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params = array_merge($filter, ['sort_by' => $column, 'order' => $nextOrder, 'page' => 1]);
    return '?' . http_build_query($params);
};

$getSortIcon = function(string $column) use ($currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up text-muted opacity-50 ms-1" style="font-size:0.65rem;"></i>';
    }
    if ($currentOrder === 'asc') {
        return '<i class="bi bi-sort-down-alt text-primary fw-bold ms-1" style="font-size:0.75rem;"></i>';
    }
    return '<i class="bi bi-sort-down text-primary fw-bold ms-1" style="font-size:0.75rem;"></i>';
};
?>

<!-- ===== FILTER CARD ===== -->
<div class="card border-0 shadow-xs rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= base_url('admin/surat-keluar') ?>" class="row g-2 align-items-end" id="form-filter">
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Cari Naskah</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control form-control-sm border-start-0"
                 placeholder="Nomor / Nama / Perihal..." value="<?= esc($filter['search']) ?>" id="filter-search">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Dari Tanggal</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= esc($filter['date_from']) ?>" id="filter-from">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Sampai Tanggal</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= esc($filter['date_to']) ?>" id="filter-to">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Jenis Format</label>
        <select name="letter_type" class="form-select form-select-sm" id="filter-type">
          <option value="">Semua Jenis</option>
          <?php foreach ($letter_types as $key => $label): ?>
            <option value="<?= $key ?>" <?= $filter['letter_type'] === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-1">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Status</label>
        <select name="status" class="form-select form-select-sm" id="filter-status">
          <option value="">Semua</option>
          <option value="active"  <?= $filter['status'] === 'active'  ? 'selected' : '' ?>>Aktif</option>
          <option value="revoked" <?= $filter['status'] === 'revoked' ? 'selected' : '' ?>>Dicabut</option>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size:0.75rem;">Urutkan</label>
        <select name="sort_order_combo" class="form-select form-select-sm" id="filter-sort-combo" onchange="applySortCombo(this.value)">
          <option value="issued_at:desc" <?= ($currentSort === 'issued_at' && $currentOrder === 'desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
          <option value="issued_at:asc" <?= ($currentSort === 'issued_at' && $currentOrder === 'asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
          <option value="sequence_number:desc" <?= ($currentSort === 'sequence_number' && $currentOrder === 'desc') ? 'selected' : '' ?>>No. Terbesar</option>
          <option value="sequence_number:asc" <?= ($currentSort === 'sequence_number' && $currentOrder === 'asc') ? 'selected' : '' ?>>No. Terkecil</option>
          <option value="recipient_name:asc" <?= ($currentSort === 'recipient_name' && $currentOrder === 'asc') ? 'selected' : '' ?>>Penerima (A - Z)</option>
          <option value="recipient_name:desc" <?= ($currentSort === 'recipient_name' && $currentOrder === 'desc') ? 'selected' : '' ?>>Penerima (Z - A)</option>
          <option value="subject:asc" <?= ($currentSort === 'subject' && $currentOrder === 'asc') ? 'selected' : '' ?>>Perihal (A - Z)</option>
        </select>
        <input type="hidden" name="sort_by" id="input-sort-by" value="<?= esc($currentSort) ?>">
        <input type="hidden" name="order" id="input-order" value="<?= esc($currentOrder) ?>">
      </div>
      <div class="col-12 d-flex justify-content-end gap-2 mt-2">
        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-xs" id="btn-filter">
          <i class="bi bi-funnel me-1"></i>Filter Data
        </button>
        <a href="<?= base_url('admin/surat-keluar') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs" id="btn-reset">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
        </a>
      </div>
    </form>
  </div>
</div>

<script>
function applySortCombo(val) {
  if (!val) return;
  const parts = val.split(':');
  document.getElementById('input-sort-by').value = parts[0];
  document.getElementById('input-order').value = parts[1];
}
</script>

<!-- ===== TABEL ARSIP ===== -->
<div class="card border-0 shadow-xs rounded-3 overflow-hidden">
  <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="badge rounded-circle bg-primary bg-opacity-10 text-primary p-2">
        <i class="bi bi-table fs-6"></i>
      </span>
      <div>
        <h6 class="fw-bold mb-0 text-dark">Buku Agenda Surat Keluar</h6>
        <small class="text-muted" style="font-size:0.75rem;">Daftar naskah dinas resmi yang telah diterbitkan (Klik judul kolom untuk mengurutkan)</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small font-monospace">
        Total: <?= esc($total) ?> berkas
      </span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:0.84rem;">
        <thead class="bg-light text-uppercase tracking-wider text-muted border-bottom" style="font-size:0.7rem; letter-spacing:0.05em;">
          <tr>
            <th class="ps-3 py-3" width="50">No.</th>
            <th>
              <a href="<?= $buildSortUrl('sequence_number') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Nomor">
                <span>Nomor Registrasi</span>
                <?= $getSortIcon('sequence_number') ?>
              </a>
            </th>
            <th>
              <a href="<?= $buildSortUrl('issued_at') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Tanggal">
                <span>Tanggal</span>
                <?= $getSortIcon('issued_at') ?>
              </a>
            </th>
            <th>
              <a href="<?= $buildSortUrl('letter_type') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Jenis Format">
                <span>Jenis Surat</span>
                <?= $getSortIcon('letter_type') ?>
              </a>
            </th>
            <th>
              <a href="<?= $buildSortUrl('recipient_name') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Penerima">
                <span>Penerima</span>
                <?= $getSortIcon('recipient_name') ?>
              </a>
            </th>
            <th>
              <a href="<?= $buildSortUrl('subject') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Perihal">
                <span>Perihal / Hal</span>
                <?= $getSortIcon('subject') ?>
              </a>
            </th>
            <th>
              <a href="<?= $buildSortUrl('status') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center hover-primary" title="Urutkan berdasarkan Status">
                <span>Status</span>
                <?= $getSortIcon('status') ?>
              </a>
            </th>
            <th class="text-end pe-3" width="110">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($letters)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                <div class="d-inline-flex p-3 rounded-circle bg-light mb-2 text-muted">
                  <i class="bi bi-folder2-open fs-2"></i>
                </div>
                <div class="fw-semibold text-dark">Belum ada naskah surat keluar</div>
                <small class="text-muted d-block mb-3">Klik tombol "Buat Surat Baru" untuk mulai menyusun naskah dinas.</small>
                <a href="<?= base_url('admin/surat-keluar/create') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                  <i class="bi bi-plus-lg me-1"></i>Buat Sekarang
                </a>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($letters as $i => $l): ?>
              <tr>
                <td class="ps-3 text-muted"><?= ($filter['page'] - 1) * $filter['limit'] + $i + 1 ?></td>
                <td>
                  <span class="fw-semibold font-monospace text-primary"><?= esc($l['letter_number']) ?></span>
                </td>
                <td class="text-muted"><?= date('d/m/Y', strtotime($l['issued_at'])) ?></td>
                <td>
                  <?php if ($l['letter_type'] === 'surat_eksternal'): ?>
                    <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-2 py-0.5">
                      <i class="bi bi-upload me-1"></i>Eksternal
                    </span>
                  <?php elseif ($l['letter_type'] === 'surat_custom'): ?>
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5">
                      <i class="bi bi-lightning-charge-fill me-1"></i>Dinamis
                    </span>
                  <?php else: ?>
                    <span class="badge rounded-pill bg-slate-100 text-secondary border px-2 py-0.5" style="background:#f1f5f9;">
                      <?= esc($letter_types[$l['letter_type']] ?? $l['letter_type']) ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="fw-medium text-dark"><?= esc($l['recipient_name']) ?></span>
                </td>
                <td style="max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                    title="<?= esc($l['subject']) ?>">
                  <span class="text-secondary"><?= esc($l['subject']) ?></span>
                </td>
                <td>
                  <?php if ($l['status'] === 'active'): ?>
                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-0.5">
                      <i class="bi bi-check-circle-fill me-1"></i>Aktif
                    </span>
                  <?php else: ?>
                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5">
                      <i class="bi bi-x-circle-fill me-1"></i>Dicabut
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-end pe-3">
                  <div class="d-inline-flex gap-1">
                    <a href="<?= base_url('admin/surat-keluar/detail/' . $l['id']) ?>"
                       class="action-btn-pill btn btn-outline-primary btn-sm" title="Lihat Detail">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button type="button"
                            class="action-btn-pill btn btn-outline-success btn-sm btn-trigger-download"
                            data-bs-toggle="modal"
                            data-bs-target="#modalDownloadPdf"
                            data-letter-id="<?= $l['id'] ?>"
                            data-letter-number="<?= esc($l['letter_number']) ?>"
                            data-recipient-name="<?= esc($l['recipient_name']) ?>"
                            data-letter-type="<?= esc($letter_types[$l['letter_type']] ?? $l['letter_type']) ?>"
                            title="Download PDF Resmi">
                      <i class="bi bi-file-earmark-pdf"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="p-3 border-top d-flex justify-content-center">
        <nav>
          <ul class="pagination pagination-sm justify-content-center mb-0 gap-1">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link rounded-circle text-center" style="width:32px; height:32px; line-height:20px;"
                   href="?<?= http_build_query(array_merge($filter, ['page' => $p])) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>

  </div>
</div>

<?= view('admin/surat/keluar/_modal_download') ?>

<?= $this->endSection() ?>
