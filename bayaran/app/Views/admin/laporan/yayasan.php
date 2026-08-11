<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <h4 class="font-outfit fw-bold text-dark mb-1"><i class="bi bi-building me-2 text-primary"></i>Laporan Penerimaan Yayasan</h4>
      <p class="text-muted small">Laporan penerimaan SPP (Bulanan) untuk persentase Yayasan.</p>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small fw-bold text-muted">Tahun Ajaran</label>
          <select name="tahun_ajaran_id" class="form-select">
            <?php foreach ($semuaTahunAjaran as $ta): ?>
              <option value="<?= $ta['id'] ?>" <?= $tahunAjaranId == $ta['id'] ? 'selected' : '' ?>>
                <?= esc($ta['nama']) ?> <?= $ta['aktif'] ? '(Aktif)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold text-muted">Bulan Pembayaran</label>
          <select name="bulan" class="form-select" required>
            <?php 
              $bulanList = [
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'
              ];
              foreach ($bulanList as $num => $name): 
            ?>
              <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100 fw-bold shadow-none"><i class="bi bi-search me-2"></i>Tampilkan</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($data)): ?>
    <div class="alert alert-info border-0 shadow-sm" style="border-left: 4px solid #0dcaf0 !important;">
      <i class="bi bi-info-circle me-2"></i> Tidak ada data penerimaan bulanan (SPP) pada bulan ini.
    </div>
  <?php else: ?>
    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">#</th>
                <th>Tanggal Bayar</th>
                <th>NIS - Nama Siswa</th>
                <th>Kelas</th>
                <th>Jenis Pembayaran</th>
                <th>Bulan Tagihan</th>
                <th class="text-end pe-4">Jumlah Bayar</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; ?>
              <?php foreach ($data as $row): ?>
                <tr>
                  <td class="ps-4"><?= $no++; ?></td>
                  <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                  <td><span class="fw-bold"><?= esc($row['nis']) ?></span> - <?= esc($row['nama']) ?></td>
                  <td><span class="badge bg-secondary"><?= esc($row['nama_kelas']) ?></span></td>
                  <td><?= esc($row['nama_jenis_bayar']) ?></td>
                  <?php 
                    $bulanNama = [
                      1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                      5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                      9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                  ?>
                  <td><?= isset($bulanNama[$row['tagihan_bulan']]) ? $bulanNama[$row['tagihan_bulan']] : '-' ?></td>
                  <td class="text-end pe-4 fw-bold">Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="6" class="text-end">Total Penerimaan SPP Bulan Ini:</th>
                <th class="text-end pe-4 text-primary fs-5">Rp <?= number_format($totalPenerimaan, 0, ',', '.') ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Ringkasan Yayasan -->
    <div class="row mt-4">
        <div class="col-md-6 ms-auto">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid var(--primary) !important;">
                <div class="card-body">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Ringkasan Bagian Yayasan</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Penerimaan:</span>
                        <span class="fw-bold">Rp <?= number_format($totalPenerimaan, 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Persentase Yayasan:</span>
                        <span class="fw-bold text-primary"><?= esc($persentase) ?>%</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Jumlah Untuk Yayasan:</span>
                        <span class="fw-bold text-success fs-4">Rp <?= number_format($totalYayasan, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
  <?php endif; ?>

</div>

<?= $this->endSection() ?>
