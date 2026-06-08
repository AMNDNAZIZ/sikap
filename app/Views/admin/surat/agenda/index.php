<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h2 class="h4 fw-bold mb-0"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Buku Agenda Surat</h2>
    <small class="text-muted">Rekapitulasi surat keluar &amp; masuk terpusat</small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= base_url('admin/surat-keluar/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus me-1"></i>Buat Surat Keluar
    </a>
    <a href="<?= base_url('admin/surat-masuk/create') ?>" class="btn btn-success btn-sm">
      <i class="bi bi-plus me-1"></i>Catat Surat Masuk
    </a>
    <a href="<?= base_url('admin/agenda-surat/export-excel?' . http_build_query($filter)) ?>"
       class="btn btn-outline-success btn-sm" id="btn-export-excel">
      <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
  </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-primary" style="font-size:1.8rem;">📬</div>
        <div class="fw-bold fs-4 text-primary"><?= $out_stats['bulan_ini'] ?></div>
        <div class="text-muted" style="font-size:0.8rem;">Keluar Bulan Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-success" style="font-size:1.8rem;">📥</div>
        <div class="fw-bold fs-4 text-success"><?= $in_stats['bulan_ini'] ?></div>
        <div class="text-muted" style="font-size:0.8rem;">Masuk Bulan Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-info" style="font-size:1.8rem;">📋</div>
        <div class="fw-bold fs-4 text-info"><?= $out_stats['tahun_ini'] ?></div>
        <div class="text-muted" style="font-size:0.8rem;">Keluar Tahun Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body">
        <div class="text-warning" style="font-size:1.8rem;">📦</div>
        <div class="fw-bold fs-4 text-warning"><?= $in_stats['tahun_ini'] ?></div>
        <div class="text-muted" style="font-size:0.8rem;">Masuk Tahun Ini</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="<?= base_url('admin/agenda-surat') ?>" class="row g-2 align-items-end" id="form-filter">
      <input type="hidden" name="tab" id="active-tab-input" value="<?= esc($tab) ?>">
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm fw-semibold mb-1">Cari</label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Nomor / Nama / Perihal..." value="<?= esc($filter['search'] ?? '') ?>" id="filter-search">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Dari</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= esc($filter['date_from'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Sampai</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= esc($filter['date_to'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm fw-semibold mb-1">Jenis Surat</label>
        <select name="letter_type" class="form-select form-select-sm">
          <option value="">Semua Jenis</option>
          <?php foreach ($letter_types as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($filter['letter_type'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="<?= base_url('admin/agenda-surat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i>Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="agenda-tabs">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'keluar' ? 'active' : '' ?> tab-link" href="#" data-tab="keluar">
      <i class="bi bi-envelope-paper me-1 text-primary"></i>
      Surat Keluar
      <span class="badge bg-primary ms-1"><?= $out_total ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'masuk' ? 'active' : '' ?> tab-link" href="#" data-tab="masuk">
      <i class="bi bi-envelope-arrow-down me-1 text-success"></i>
      Surat Masuk
      <span class="badge bg-success ms-1"><?= $in_total ?></span>
    </a>
  </li>
</ul>

<div class="card border-0 shadow-sm" style="border-top-left-radius:0;">
  <div class="card-body p-0 p-md-3">

    <!-- Tab Keluar -->
    <div id="tab-keluar" class="tab-content-panel <?= $tab !== 'keluar' ? 'd-none' : '' ?>">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0" style="font-size:0.85rem;">
          <thead class="table-light">
            <tr>
              <th>No.</th><th>Nomor Surat</th><th>Tanggal</th><th>Jenis</th><th>Penerima</th><th>Perihal</th><th>Status</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($outgoing)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada data.</td></tr>
            <?php else: ?>
              <?php foreach ($outgoing as $i => $l): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td class="fw-semibold font-monospace small"><?= esc($l['letter_number']) ?></td>
                  <td><?= date('d/m/Y', strtotime($l['issued_at'])) ?></td>
                  <td><span class="badge bg-secondary" style="font-size:0.72rem;"><?= esc($letter_types[$l['letter_type']] ?? $l['letter_type']) ?></span></td>
                  <td><?= esc($l['recipient_name']) ?></td>
                  <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= esc($l['subject']) ?>"><?= esc($l['subject']) ?></td>
                  <td><?= $l['status'] === 'active' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Dicabut</span>' ?></td>
                  <td>
                    <a href="<?= base_url('admin/surat-keluar/detail/' . $l['id']) ?>" class="btn btn-outline-primary btn-sm" title="Detail"><i class="bi bi-eye"></i></a>
                    <a href="<?= base_url('admin/surat-keluar/download/' . $l['id']) ?>" class="btn btn-outline-success btn-sm" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab Masuk -->
    <div id="tab-masuk" class="tab-content-panel <?= $tab !== 'masuk' ? 'd-none' : '' ?>">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0" style="font-size:0.85rem;">
          <thead class="table-light">
            <tr>
              <th>No.</th><th>Tgl Diterima</th><th>Nomor Surat</th><th>Pengirim</th><th>Instansi</th><th>Perihal</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($incoming)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada data.</td></tr>
            <?php else: ?>
              <?php foreach ($incoming as $i => $l): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= date('d/m/Y', strtotime($l['received_at'])) ?></td>
                  <td class="font-monospace small"><?= esc($l['letter_number'] ?? '—') ?></td>
                  <td class="fw-semibold"><?= esc($l['sender_name']) ?></td>
                  <td><?= esc($l['sender_agency'] ?? '—') ?></td>
                  <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= esc($l['subject']) ?>"><?= esc($l['subject']) ?></td>
                  <td>
                    <a href="<?= base_url('admin/surat-masuk/detail/' . $l['id']) ?>" class="btn btn-outline-primary btn-sm" title="Detail"><i class="bi bi-eye"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.tab-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    const tab = this.dataset.tab;
    document.getElementById('active-tab-input').value = tab;
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.add('d-none'));
    document.getElementById('tab-' + tab).classList.remove('d-none');
  });
});
</script>
<?= $this->endSection() ?>
