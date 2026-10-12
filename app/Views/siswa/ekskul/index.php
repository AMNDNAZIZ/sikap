<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">

  <!-- Header -->
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-dribbble text-primary fs-5"></i>
    <h5 class="fw-bold mb-0">Ekstrakurikuler</h5>
    <span class="badge bg-secondary ms-auto"><?= $activeYear ? esc($activeYear['year']) : '-' ?></span>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ══ Ekskul Yang Diikuti ══ -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
      <i class="bi bi-patch-check text-success me-1"></i> Ekskul Yang Diikuti
    </div>
    <div class="card-body">
      <?php if (empty($myEkskuls)): ?>
        <div class="text-center text-muted py-4">
          <i class="bi bi-emoji-neutral fs-1 d-block mb-2 opacity-50"></i>
          Anda belum tergabung dalam ekstrakurikuler apapun.
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($myEkskuls as $e): ?>
            <div class="col-12 col-sm-6 col-xl-4">
              <div class="card border-0 bg-light h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <div class="fw-bold"><?= esc($e['name']) ?></div>
                      <div class="text-muted small"><?= esc($e['kode']) ?> · <?= esc($e['category']) ?></div>
                    </div>
                    <?php if ($e['status'] == 'aktif'): ?>
                      <span class="badge bg-success">Aktif</span>
                    <?php elseif ($e['status'] == 'pending'): ?>
                      <span class="badge bg-warning text-dark">Menunggu</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= esc($e['status']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($e['status'] == 'aktif'): ?>
                    <a href="<?= base_url('siswa/ekskul/detail/' . $e['ekskul_id']) ?>"
                       class="btn btn-sm btn-outline-primary w-100 mt-1">
                      <i class="bi bi-graph-up me-1"></i> Lihat Progress & Nilai
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ Katalog Ekskul Tersedia ══ -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold">
      <i class="bi bi-collection text-primary me-1"></i> Pilihan Ekstrakurikuler Lainnya
    </div>
    <div class="card-body p-0">

      <?php if (empty($availableEkskuls)): ?>
        <div class="text-center text-muted py-4">
          Tidak ada ekstrakurikuler lain yang tersedia.
        </div>
      <?php else: ?>

        <!-- Desktop table -->
        <div class="d-none d-md-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:40px;">No</th>
                  <th>Nama</th>
                  <th>Kategori</th>
                  <th>Deskripsi</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($availableEkskuls as $i => $a): ?>
                  <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= esc($a['name']) ?></td>
                    <td>
                      <span class="badge <?= $a['category'] == 'wajib' ? 'bg-danger' : 'bg-info text-dark' ?>">
                        <?= esc($a['category']) ?>
                      </span>
                    </td>
                    <td class="text-muted small"><?= esc($a['description']) ?></td>
                    <td class="text-center">
                      <form action="<?= base_url('siswa/ekskul/daftar/' . $a['id']) ?>" method="post"
                            onsubmit="return confirm('Daftar ke <?= esc($a['name']) ?>?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-primary">
                          <i class="bi bi-plus-circle me-1"></i> Daftar
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mobile cards -->
        <div class="d-md-none px-3 pb-3">
          <?php foreach ($availableEkskuls as $a): ?>
            <div class="card border mb-2">
              <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold"><?= esc($a['name']) ?></span>
                  <span class="badge <?= $a['category'] == 'wajib' ? 'bg-danger' : 'bg-info text-dark' ?>">
                    <?= esc($a['category']) ?>
                  </span>
                </div>
                <?php if ($a['description']): ?>
                  <p class="text-muted small mb-2"><?= esc($a['description']) ?></p>
                <?php endif; ?>
                <form action="<?= base_url('siswa/ekskul/daftar/' . $a['id']) ?>" method="post"
                      onsubmit="return confirm('Daftar ke <?= esc($a['name']) ?>?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-plus-circle me-1"></i> Daftar Sekarang
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>
  </div>

</div>

<?= $this->endSection() ?>
