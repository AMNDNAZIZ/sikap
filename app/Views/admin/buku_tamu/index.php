<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-book me-2 text-primary"></i>Buku Tamu Digital</h2>
    <small class="text-muted">Kelola data kunjungan tamu sekolah</small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= base_url('admin/buku-tamu/print-qr') ?>" target="_blank"
       class="btn btn-outline-secondary btn-sm" id="btn-print-qr">
      <i class="bi bi-qr-code me-1"></i>Print QR
    </a>
    <a href="<?= base_url('buku-tamu') ?>" target="_blank"
       class="btn btn-outline-info btn-sm" id="btn-lihat-form">
      <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Form Publik
    </a>
  </div>
</div>

<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- ===== STATISTIK CARDS ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-primary" style="font-size:2rem;">📅</div>
        <div class="fw-bold fs-4 text-primary"><?= $stats['hari_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Tamu Hari Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-info" style="font-size:2rem;">📆</div>
        <div class="fw-bold fs-4 text-info"><?= $stats['bulan_ini'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Tamu Bulan Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-success" style="font-size:2rem;">👥</div>
        <div class="fw-bold fs-4 text-success"><?= $stats['total_umum'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Total Tamu Umum</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="text-warning" style="font-size:2rem;">🏛️</div>
        <div class="fw-bold fs-4 text-warning"><?= $stats['total_dinas'] ?></div>
        <div class="text-muted" style="font-size:0.82rem;">Total Tamu Dinas</div>
      </div>
    </div>
  </div>
</div>

<!-- ===== FILTER & EXPORT ===== -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="<?= base_url('admin/buku-tamu') ?>" id="form-filter" class="row g-2 align-items-end">
      <!-- Jenis Tamu -->
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Jenis</label>
        <select name="type" class="form-select form-select-sm" id="filter-type">
          <option value="">Semua</option>
          <option value="umum"  <?= $filter['type'] === 'umum'  ? 'selected' : '' ?>>Tamu Umum</option>
          <option value="dinas" <?= $filter['type'] === 'dinas' ? 'selected' : '' ?>>Tamu Dinas</option>
        </select>
      </div>
      <!-- Bulan -->
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Bulan</label>
        <select name="month" class="form-select form-select-sm" id="filter-month">
          <option value="">Semua</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= (int)$filter['month'] === $m ? 'selected' : '' ?>>
              <?= date('F', mktime(0,0,0,$m,1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <!-- Tahun -->
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Tahun</label>
        <select name="year" class="form-select form-select-sm" id="filter-year">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $filter['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <!-- Search -->
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm fw-semibold mb-1">Cari</label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Nama / Instansi..." value="<?= esc($filter['search']) ?>" id="filter-search">
      </div>
      <!-- Tombol -->
      <div class="col-12 col-md-3 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary btn-sm" id="btn-filter">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="<?= base_url('admin/buku-tamu') ?>" class="btn btn-outline-secondary btn-sm" id="btn-reset">
          <i class="bi bi-x-circle me-1"></i>Reset
        </a>
        <a href="#" data-href="<?= base_url('admin/buku-tamu/export-pdf?' . http_build_query($filter)) ?>"
           class="btn btn-danger btn-sm btn-export" id="btn-export-pdf">
          <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="<?= base_url('admin/buku-tamu/export-excel?' . http_build_query($filter)) ?>"
           class="btn btn-success btn-sm" id="btn-export-excel">
          <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
      </div>
    </form>
  </div>
</div>

<!-- ===== TABEL DATA ===== -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0 p-md-3">
    <div class="table-responsive">
      <table id="tbl-buku-tamu" class="table table-hover table-striped align-middle mb-0" style="font-size:0.85rem;">
        <thead class="table-light">
          <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Nama</th>
            <?php if (($filter['type'] ?? '') !== 'umum'): ?>
            <th>NIP</th>
            <?php endif; ?>
            <th>Instansi / Ket.</th>
            <th>Tujuan</th>
            <th>Bertemu</th>
            <th>No HP</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_tamu)): ?>
            <tr>
              <td colspan="<?= ($filter['type'] ?? '') !== 'umum' ? '10' : '9' ?>" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                Belum ada data tamu.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($data_tamu as $i => $tamu): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= date('d/m/Y H:i', strtotime($tamu['created_at'])) ?></td>
                <td>
                  <?php if ($tamu['guest_type'] === 'dinas'): ?>
                    <span class="badge bg-warning text-dark">Dinas</span>
                  <?php else: ?>
                    <span class="badge bg-primary">Umum</span>
                  <?php endif; ?>
                </td>
                <td class="fw-semibold"><?= esc($tamu['nama']) ?></td>
                <?php if (($filter['type'] ?? '') !== 'umum'): ?>
                <td><?= esc($tamu['nip'] ?: '-') ?></td>
                <?php endif; ?>
                <td>
                  <?php if ($tamu['guest_type'] === 'dinas'): ?>
                    <?= esc($tamu['instansi'] ?? '-') ?>
                  <?php elseif ($tamu['is_ortu_siswa']): ?>
                    <span class="text-muted fst-italic">Orang Tua Siswa</span>
                  <?php else: ?>
                    <?= esc($tamu['instansi'] ?? '-') ?>
                  <?php endif; ?>
                </td>
                <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                    title="<?= esc($tamu['tujuan']) ?>">
                  <?= esc($tamu['tujuan']) ?>
                </td>
                <td><?= esc($tamu['bertemu_dengan'] ?? '-') ?></td>
                <td><?= esc($tamu['no_hp'] ?? '-') ?></td>
                <td>
                  <a href="<?= base_url('admin/buku-tamu/delete/' . $tamu['id']) ?>"
                     class="btn btn-outline-danger btn-sm"
                     onclick="return confirm('Hapus data tamu ini?')"
                     id="btn-hapus-<?= $tamu['id'] ?>">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Include Modal Export KOP -->
<?= $this->include('admin/components/modal_export_kop') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(document).ready(function () {
    if ($('#tbl-buku-tamu tbody tr').length > 1 || ($('#tbl-buku-tamu tbody tr').length === 1 && !$('#tbl-buku-tamu tbody tr td[colspan]').length)) {
      $('#tbl-buku-tamu').DataTable({
        order: [[1, 'desc']],
        columnDefs: [
          { orderable: false, targets: [8] }
        ]
      });
    }
  });
</script>
<?= $this->endSection() ?>
