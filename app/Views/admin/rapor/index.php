<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$formatCardDate = function($cfg, $sch) {
    $city = !empty($cfg['place_city']) ? $cfg['place_city'] : ($sch['city_regency'] ?? 'Kota/Kab.');
    if (!empty($cfg['report_date'])) {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $time = strtotime($cfg['report_date']);
        $m = (int) date('n', $time);
        $dateStr = date('j', $time) . ' ' . ($months[$m] ?? date('F', $time)) . ' ' . date('Y', $time);
        return esc($city . ', ' . $dateStr);
    }
    return '<span class="text-muted fst-italic">' . esc($city) . ', ' . date('d F Y') . ' (Default Hari Ini)</span>';
};
?>

<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h3 class="fw-bold mb-1">🖨️ Rapor PTS (Penilaian Tengah Semester)</h3>
      <p class="text-muted mb-0">Pilih kelas dan semester untuk mencetak Rapor Tengah Semester (PTS/STS).</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfigPts">
        <i class="bi bi-gear-fill me-1"></i> ⚙️ Atur Titimangsa Rapor PTS
      </button>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Ringkasan Pengaturan Titimangsa PTS -->
  <div class="row g-3 mb-4">
    <!-- Semester 1 (Ganjil) -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #0d6efd !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="badge bg-primary text-white mb-1">Semester 1 (Ganjil)</span>
              <h6 class="fw-bold text-dark mb-0">Titimangsa PTS Ganjil</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openConfigTab('sem1')">
              ✏️ Atur
            </button>
          </div>
          <div class="mt-2">
            <div class="d-flex align-items-center mb-1">
              <span class="text-muted small me-2" style="width: 80px;">Titimangsa:</span>
              <strong class="text-dark small"><?= $formatCardDate($configSem1, $school) ?></strong>
            </div>
            <div class="d-flex align-items-center mb-1">
              <span class="text-muted small me-2" style="width: 80px;">TTD Digital:</span>
              <span class="badge <?= !empty($configSem1['show_signature']) ? 'bg-success' : 'bg-secondary' ?>">
                <?= !empty($configSem1['show_signature']) ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </div>
            <div class="d-flex align-items-center">
              <span class="text-muted small me-2" style="width: 80px;">Watermark:</span>
              <span class="badge <?= !empty($configSem1['show_watermark']) ? 'bg-info text-dark' : 'bg-secondary' ?>">
                <?= !empty($configSem1['show_watermark']) ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Semester 2 (Genap) -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #198754 !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="badge bg-success text-white mb-1">Semester 2 (Genap)</span>
              <h6 class="fw-bold text-dark mb-0">Titimangsa PTS Genap</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="openConfigTab('sem2')">
              ✏️ Atur
            </button>
          </div>
          <div class="mt-2">
            <div class="d-flex align-items-center mb-1">
              <span class="text-muted small me-2" style="width: 80px;">Titimangsa:</span>
              <strong class="text-dark small"><?= $formatCardDate($configSem2, $school) ?></strong>
            </div>
            <div class="d-flex align-items-center mb-1">
              <span class="text-muted small me-2" style="width: 80px;">TTD Digital:</span>
              <span class="badge <?= !empty($configSem2['show_signature']) ? 'bg-success' : 'bg-secondary' ?>">
                <?= !empty($configSem2['show_signature']) ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </div>
            <div class="d-flex align-items-center">
              <span class="text-muted small me-2" style="width: 80px;">Watermark:</span>
              <span class="badge <?= !empty($configSem2['show_watermark']) ? 'bg-info text-dark' : 'bg-secondary' ?>">
                <?= !empty($configSem2['show_watermark']) ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($classes)): ?>
    <div class="alert alert-warning">
      ⚠️ Tidak ada kelas yang tersedia untuk dicetak.
      <?php if (($roleId ?? 0) == 3): ?>
        Anda belum menjadi Wali Kelas. Hubungi Admin untuk penugasan.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0 text-primary">Daftar Kelas Rapor PTS</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle table-hover">
            <thead class="table-light">
              <tr>
                <th style="width:50px;" class="text-center">#</th>
                <th>Kelas</th>
                <th>Wali Kelas</th>
                <th>Tahun Ajaran Aktif</th>
                <th style="width:420px;" class="text-center">Aksi Cetak Rapor PTS</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($classes as $cls): ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td class="fw-semibold"><?= esc($cls['name']) ?></td>
                  <td><?= esc($cls['wali_name'] ?? '-') ?></td>
                  <td><?= esc($activeYear['year'] ?? '-') ?></td>
                  <td class="text-center">
                    <a href="<?= site_url("admin/rapor/pts/{$cls['id']}/1") ?>"
                       class="btn btn-sm btn-outline-primary me-2" target="_blank" title="Cetak Rapor PTS Semester 1 (Ganjil)">
                      🖨️ Semester 1 (Ganjil)
                    </a>
                    <a href="<?= site_url("admin/rapor/pts/{$cls['id']}/2") ?>"
                       class="btn btn-sm btn-outline-success" target="_blank" title="Cetak Rapor PTS Semester 2 (Genap)">
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
        💡 Tombol cetak membuka halaman baru (pop-up) yang langsung menampilkan format cetak resmi Rapor PTS.
        Titimangsa tanggal dan kota penandatanganan akan otomatis mengikuti konfigurasi di atas.
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Konfigurasi Titimangsa PTS -->
<div class="modal fade" id="modalConfigPts" tabindex="-1" aria-labelledby="modalConfigPtsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="modalConfigPtsLabel">
          ⚙️ Pengaturan Titimangsa & Cetak Rapor PTS
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-light border small text-muted mb-3">
          Atur titimangsa (kota & tanggal penyerahan) serta preferensi cetak Rapor PTS untuk Tahun Ajaran <strong><?= esc($activeYear['year'] ?? '-') ?></strong>. Pengaturan ini khusus untuk Rapor PTS dan terpisah dari Rapor Semester Akhir.
        </div>

        <!-- Tabs Semester -->
        <ul class="nav nav-tabs nav-fill mb-3" id="configTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="tab-sem1" data-bs-toggle="tab" data-bs-target="#pane-sem1" type="button" role="tab" aria-selected="true">
              📘 Semester 1 (Ganjil)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-sem2" data-bs-toggle="tab" data-bs-target="#pane-sem2" type="button" role="tab" aria-selected="false">
              📗 Semester 2 (Genap)
            </button>
          </li>
        </ul>

        <div class="tab-content" id="configTabsContent">
          <!-- PANE SEMESTER 1 -->
          <div class="tab-pane fade show active" id="pane-sem1" role="tabpanel" aria-labelledby="tab-sem1">
            <form id="form-config-sem1" class="form-config-pts">
              <?= csrf_field() ?>
              <input type="hidden" name="year_id" value="<?= esc($activeYear['id'] ?? '') ?>">
              <input type="hidden" name="semester" value="1">

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Tempat / Kota Penandatanganan</label>
                  <input type="text" name="place_city" class="form-control"
                         value="<?= esc($configSem1['place_city'] ?? ($school['city_regency'] ?? '')) ?>"
                         placeholder="Contoh: Surabaya atau Kab. Mojokerto" required>
                  <small class="text-muted">Muncul di atas tanggal: [Kota], [Tanggal].</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Tanggal Pembagian Rapor PTS Ganjil</label>
                  <input type="date" name="report_date" class="form-control"
                         value="<?= esc($configSem1['report_date'] ?? '') ?>" required>
                  <small class="text-muted">Tanggal resmi penyerahan hasil belajar PTS Semester 1.</small>
                </div>
              </div>

              <div class="card bg-light border p-3 mb-3">
                <h6 class="fw-bold mb-2">Opsi Tampilan Cetak Rapor PTS Sem 1</h6>
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" role="switch" name="show_signature" id="show_signature_sem1" value="1"
                         <?= !empty($configSem1['show_signature']) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="show_signature_sem1">
                    Tampilkan Tanda Tangan Digital Guru Wali Kelas & Kepala Sekolah
                  </label>
                  <div class="small text-muted">Gambar tanda tangan yang telah diunggah akan otomatis tercetak di atas nama penandatangan.</div>
                </div>

                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch" name="show_watermark" id="show_watermark_sem1" value="1"
                         <?= !empty($configSem1['show_watermark']) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="show_watermark_sem1">
                    Tampilkan Watermark Logo Sekolah di Lembar PTS
                  </label>
                  <div class="small text-muted">Mencetak watermark logo sekolah transparan di latar belakang rapor PTS.</div>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary btn-save">
                  💾 Simpan Titimangsa Semester 1
                </button>
              </div>
            </form>
          </div>

          <!-- PANE SEMESTER 2 -->
          <div class="tab-pane fade" id="pane-sem2" role="tabpanel" aria-labelledby="tab-sem2">
            <form id="form-config-sem2" class="form-config-pts">
              <?= csrf_field() ?>
              <input type="hidden" name="year_id" value="<?= esc($activeYear['id'] ?? '') ?>">
              <input type="hidden" name="semester" value="2">

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Tempat / Kota Penandatanganan</label>
                  <input type="text" name="place_city" class="form-control"
                         value="<?= esc($configSem2['place_city'] ?? ($school['city_regency'] ?? '')) ?>"
                         placeholder="Contoh: Surabaya atau Kab. Mojokerto" required>
                  <small class="text-muted">Muncul di atas tanggal: [Kota], [Tanggal].</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Tanggal Pembagian Rapor PTS Genap</label>
                  <input type="date" name="report_date" class="form-control"
                         value="<?= esc($configSem2['report_date'] ?? '') ?>" required>
                  <small class="text-muted">Tanggal resmi penyerahan hasil belajar PTS Semester 2.</small>
                </div>
              </div>

              <div class="card bg-light border p-3 mb-3">
                <h6 class="fw-bold mb-2">Opsi Tampilan Cetak Rapor PTS Sem 2</h6>
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" role="switch" name="show_signature" id="show_signature_sem2" value="1"
                         <?= !empty($configSem2['show_signature']) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="show_signature_sem2">
                    Tampilkan Tanda Tangan Digital Guru Wali Kelas & Kepala Sekolah
                  </label>
                  <div class="small text-muted">Gambar tanda tangan yang telah diunggah akan otomatis tercetak di atas nama penandatangan.</div>
                </div>

                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch" name="show_watermark" id="show_watermark_sem2" value="1"
                         <?= !empty($configSem2['show_watermark']) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="show_watermark_sem2">
                    Tampilkan Watermark Logo Sekolah di Lembar PTS
                  </label>
                  <div class="small text-muted">Mencetak watermark logo sekolah transparan di latar belakang rapor PTS.</div>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-success btn-save">
                  💾 Simpan Titimangsa Semester 2
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function openConfigTab(tab) {
  var modalEl = document.getElementById('modalConfigPts');
  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  if (tab === 'sem1') {
    var tabBtn = document.getElementById('tab-sem1');
    var tabObj = bootstrap.Tab.getOrCreateInstance(tabBtn);
    tabObj.show();
  } else if (tab === 'sem2') {
    var tabBtn = document.getElementById('tab-sem2');
    var tabObj = bootstrap.Tab.getOrCreateInstance(tabBtn);
    tabObj.show();
  }
}

$(function() {
  $('.form-config-pts').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var btn = form.find('.btn-save');
    var origText = btn.text();
    btn.prop('disabled', true).text('Menyimpan...');

    $.ajax({
      url: "<?= site_url('admin/rapor/save-config') ?>",
      type: 'POST',
      data: form.serialize(),
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert(res.message || 'Pengaturan titimangsa Rapor PTS berhasil disimpan.');
          location.reload();
        } else {
          alert(res.message || 'Gagal menyimpan pengaturan titimangsa.');
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan koneksi saat menyimpan pengaturan.');
      },
      complete: function() {
        btn.prop('disabled', false).text(origText);
      }
    });
  });
});
</script>
<?= $this->endSection() ?>
