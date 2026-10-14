<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">

  <!-- Header -->
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-heart-pulse text-primary fs-5"></i>
    <h5 class="fw-bold mb-0"><?= $title ?></h5>
  </div>
  <p class="text-muted small mb-3">Ajukan sesi konseling/curhat dengan Guru BK.</p>

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

  <!-- Banner Angket Karir -->
  <?php $angketTitle = is_school_sd() ? 'Angket Minat & Bakat' : 'Angket Karir & Bakat'; ?>
  <?php if (empty($careerProfile)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
      <i class="bi bi-lightbulb-fill fs-3 text-warning flex-shrink-0"></i>
      <div class="flex-grow-1">
        <strong>Belum isi <?= $angketTitle ?>!</strong>
        <span class="d-none d-sm-inline"> Bantu Guru BK mengenali rencana dan potensimu.</span>
      </div>
      <a href="<?= base_url('siswa/counseling/career') ?>" class="btn btn-warning btn-sm fw-bold text-nowrap">🎯 Isi</a>
    </div>
  <?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-3 mb-3">
      <i class="bi bi-check-circle-fill fs-3 flex-shrink-0"></i>
      <div class="flex-grow-1 small">
        <strong><?= $angketTitle ?> sudah diisi</strong> — <?= esc($careerProfile['post_graduate_plan']) ?>
      </div>
      <a href="<?= base_url('siswa/counseling/career') ?>" class="btn btn-outline-success btn-sm text-nowrap">Perbarui</a>
    </div>
  <?php endif; ?>

  <!-- Layout: di HP form di atas, riwayat di bawah. Di desktop side-by-side -->
  <div class="row g-3">

    <!-- Kolom Form Pengajuan (tampil pertama di HP berkat order-md-2) -->
    <div class="col-12 col-md-4 order-1 order-md-1">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-send me-1"></i> Ajukan Janji Temu</h6>
        </div>
        <div class="card-body">
          <form action="<?= base_url('siswa/counseling/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Topik / Keluhan <span class="text-danger">*</span></label>
              <textarea name="topic" class="form-control" rows="3"
                        placeholder="Ceritakan secara singkat..." required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Usulan Tanggal & Waktu <span class="text-danger">*</span></label>
              <input type="datetime-local" name="proposed_date" class="form-control" required>
              <div class="form-text">Guru BK mungkin akan menyesuaikan waktu ini.</div>
            </div>
            <div class="alert alert-info py-2 small mb-3">
              <i class="bi bi-info-circle me-1"></i> Data Anda dijamin <strong>kerahasiaannya</strong>.
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-send me-1"></i> Kirim Pengajuan
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Kolom Riwayat -->
    <div class="col-12 col-md-8 order-2 order-md-2">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
          <i class="bi bi-clock-history me-1 text-primary"></i> Riwayat Janji Temu
        </div>
        <div class="card-body p-0">

          <?php if(empty($appointments)): ?>
            <div class="text-center text-muted py-5">
              <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
              Belum ada riwayat janji temu.
            </div>
          <?php else: ?>

            <!-- Desktop table -->
            <div class="d-none d-md-block table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Tanggal</th>
                    <th>Topik</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($appointments as $row): ?>
                    <tr>
                      <td>
                        <strong><?= date('d M Y', strtotime($row['requested_date'])) ?></strong><br>
                        <small class="text-muted"><?= $row['requested_time'] ? date('H:i', strtotime($row['requested_time'])) . ' WIB' : '-' ?></small>
                      </td>
                      <td class="small"><?= esc($row['topic']) ?></td>
                      <td>
                        <?php if ($row['status'] == 'Pending'): ?>
                          <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                        <?php elseif ($row['status'] == 'Approved'): ?>
                          <span class="badge bg-info"><i class="bi bi-check-circle"></i> Disetujui</span>
                        <?php elseif ($row['status'] == 'Rejected'): ?>
                          <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                        <?php elseif ($row['status'] == 'Completed'): ?>
                          <span class="badge bg-success"><i class="bi bi-check-all"></i> Selesai</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($row['status'] == 'Pending'): ?>
                          <a href="<?= base_url('siswa/counseling/cancel/' . $row['id']) ?>"
                             class="btn btn-sm btn-outline-danger"
                             onclick="return confirm('Batalkan pengajuan ini?')">Batalkan</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile cards -->
            <div class="d-md-none px-3 pb-3 pt-2">
              <?php foreach($appointments as $row): ?>
                <div class="card border mb-2">
                  <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="small fw-semibold"><?= date('d M Y', strtotime($row['requested_date'])) ?>
                        <?php if ($row['requested_time']): ?>
                          <span class="text-muted"> · <?= date('H:i', strtotime($row['requested_time'])) ?> WIB</span>
                        <?php endif; ?>
                      </span>
                      <?php if ($row['status'] == 'Pending'): ?>
                        <span class="badge bg-warning text-dark">Menunggu</span>
                      <?php elseif ($row['status'] == 'Approved'): ?>
                        <span class="badge bg-info">Disetujui</span>
                      <?php elseif ($row['status'] == 'Rejected'): ?>
                        <span class="badge bg-danger">Ditolak</span>
                      <?php elseif ($row['status'] == 'Completed'): ?>
                        <span class="badge bg-success">Selesai</span>
                      <?php endif; ?>
                    </div>
                    <p class="small text-muted mb-2"><?= esc($row['topic']) ?></p>
                    <?php if ($row['status'] == 'Pending'): ?>
                      <a href="<?= base_url('siswa/counseling/cancel/' . $row['id']) ?>"
                         class="btn btn-sm btn-outline-danger w-100"
                         onclick="return confirm('Batalkan pengajuan ini?')">
                        <i class="bi bi-x-circle me-1"></i> Batalkan
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
