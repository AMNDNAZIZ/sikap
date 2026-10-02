<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1">🖨️ Cetak Rapor</h3>
      <p class="text-muted mb-0">Pilih kelas dan semester untuk mencetak Rapor PTS.</p>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($classes)): ?>
    <div class="alert alert-warning">
      ⚠️ Tidak ada kelas yang tersedia untuk dicetak.
      <?php if (($roleId ?? 0) == 3): ?>
        Anda belum menjadi Wali Kelas. Hubungi Admin untuk penugasan.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:50px;">#</th>
                <th>Kelas</th>
                <th>Wali Kelas</th>
                <th>Tahun Ajaran Aktif</th>
                <th style="width:280px;">Cetak Rapor PTS</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($classes as $cls): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td class="fw-semibold"><?= esc($cls['name']) ?></td>
                  <td><?= esc($cls['wali_name'] ?? '-') ?></td>
                  <td><?= esc($activeYear['year'] ?? '-') ?></td>
                  <td>
                    <a href="<?= site_url("admin/rapor/pts/{$cls['id']}/1") ?>"
                       class="btn btn-sm btn-outline-primary" target="_blank">
                      🖨️ Semester 1 (Ganjil)
                    </a>
                    <a href="<?= site_url("admin/rapor/pts/{$cls['id']}/2") ?>"
                       class="btn btn-sm btn-outline-success" target="_blank">
                      🖨️ Semester 2 (Genap)
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="mt-3">
      <div class="alert alert-info">
        💡 Tombol cetak membuka halaman baru (pop-up) yang langsung menampilkan dialog cetak.
        Pastikan data nilai PTS sudah diinput terlebih dahulu melalui menu <strong>Input Penilaian → PTS</strong>.
      </div>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
