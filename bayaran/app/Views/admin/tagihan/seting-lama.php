<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Seting Tarif - <?= esc($jenis['nama_jenis_bayar']) ?> (<?= esc($jenis['tipe']) ?>)</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>
<?php if ($kelasFilter && !$siswaList): ?>
  <div class="alert alert-warning">Tidak ada siswa di kelas ini.</div>
<?php endif ?>

<?php
$bulanMap = [
    1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober', 5 => 'November', 6 => 'Desember',
    7 => 'Januari', 8 => 'Februari', 9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
];
$isGenerated = !empty($tagihanMap);
?>

<!-- Filter kelas -->
<form method="get">
  <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
  <div class="row mb-3">
    <div class="col-md-4">
      <label>Pilih Kelas</label>
      <select name="kelas_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Pilih Kelas --</option>
        <?php foreach ($kelas as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $kelasFilter == $k['id'] ? 'selected' : '' ?>>
            <?= esc($k['nama_kelas']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
  </div>
</form>

<?php if ($kelasFilter && $siswaList): ?>
  <?php if (!$isGenerated): ?>
    <!-- MODE GENERATE MASSAL -->
    <form method="post" action="<?= site_url('admin/tagihan/seting/' . $jenis['id']) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="kelas_id" value="<?= esc($kelasFilter) ?>">

      <div class="mb-3">
        <label>Tarif Default</label>
        <input type="text" class="form-control currency-input text-end" data-target="default_tarif" placeholder="0">
        <input type="hidden" name="tarif" id="default_tarif">
      </div>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Tarif Custom</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach ($siswaList as $s): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($s['nis']) ?></td>
              <td><?= esc($s['nama']) ?></td>
              <td>
                <input type="text" class="form-control currency-input text-end"
                       data-target="custom_<?= $s['siswa_id'] ?>"
                       placeholder="(kosong = default)">
                <input type="hidden" name="custom[<?= $s['siswa_id'] ?>]" id="custom_<?= $s['siswa_id'] ?>">
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>

      <div class="mb-3">
        <a href="<?= site_url('admin/jenis-bayar') ?>" class="btn btn-secondary float-start">← Kembali</a>
        <button class="btn btn-primary float-end">Generate Tagihan</button>
      </div>
    </form>

  <?php else: ?>
    <!-- MODE EDIT / PERSISWA -->
    <form method="post" action="<?= site_url('admin/tagihan/update') ?>">
      <?= csrf_field() ?>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Tagihan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach ($siswaList as $s): ?>
            <?php
              $tags = $tagihanMap[$s['siswa_id']] ?? [];
              $validTags = array_filter($tags, fn($t) => $t['jumlah'] > 0);
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($s['nis']) ?></td>
              <td><?= esc($s['nama']) ?></td>
              <td>
                <?php if (!empty($validTags)): ?>
                  <?php if ($jenis['tipe'] === 'bulanan'): ?>
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#bulan-<?= $s['siswa_id'] ?>">
                      <i class="bi bi-calendar3"></i> Lihat Per Bulan
                    </button>
                    <div class="collapse mt-2" id="bulan-<?= $s['siswa_id'] ?>">
                      <div class="row">
                        <div class="col-md-6">
                          <?php foreach (array_slice($validTags, 0, 6) as $i => $t): ?>
                            <div class="mb-2">
                              <label><?= $bulanMap[$i + 1] ?></label>
                              <input type="text" class="form-control currency-input text-end"
                                     data-target="bulan_<?= $t['id'] ?>"
                                     value="<?= number_format($t['jumlah'], 0, ',', '.') ?>">
                              <input type="hidden" name="jumlah[<?= $t['id'] ?>]" id="bulan_<?= $t['id'] ?>" value="<?= $t['jumlah'] ?>">
                            </div>
                          <?php endforeach ?>
                        </div>
                        <div class="col-md-6">
                          <?php foreach (array_slice($validTags, 6) as $i => $t): ?>
                            <div class="mb-2">
                              <label><?= $bulanMap[$i + 7] ?></label>
                              <input type="text" class="form-control currency-input text-end"
                                     data-target="bulan_<?= $t['id'] ?>"
                                     value="<?= number_format($t['jumlah'], 0, ',', '.') ?>">
                              <input type="hidden" name="jumlah[<?= $t['id'] ?>]" id="bulan_<?= $t['id'] ?>" value="<?= $t['jumlah'] ?>">
                            </div>
                          <?php endforeach ?>
                        </div>
                      </div>
                    </div>
                  <?php else: ?>
                    <input type="text" class="form-control currency-input text-end"
                           data-target="bebas_<?= $validTags[0]['id'] ?>"
                           value="<?= number_format($validTags[0]['jumlah'], 0, ',', '.') ?>">
                    <input type="hidden" name="jumlah[<?= $validTags[0]['id'] ?>]" id="bebas_<?= $validTags[0]['id'] ?>" value="<?= $validTags[0]['jumlah'] ?>">
                  <?php endif ?>
                <?php else: ?>
                  <!-- Form Generate Per Siswa (diluar form utama) -->
                  <form method="post" action="<?= site_url("admin/tagihan/generate-siswa/{$jenis['id']}/{$s['siswa_id']}") ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="kelas_id" value="<?= esc($kelasFilter) ?>">
                    <input type="number" name="jumlah" class="form-control form-control-sm d-inline-block" style="width: 100px;" placeholder="Tarif" required>
                    <button class="btn btn-sm btn-success mt-1">Generate</button>
                  </form>
                <?php endif ?>
              </td>
              <td>
                <?php if (!empty($validTags)): ?>
                  <a href="<?= site_url("admin/tagihan/delete/{$jenis['id']}/{$s['siswa_id']}") ?>"
                     class="btn btn-sm btn-warning"
                     onclick="return confirm('Yakin ingin mengosongkan tagihan siswa ini?')">
                     Reset
                  </a>
                <?php endif ?>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>

      <div class="mb-3">
        <a href="<?= site_url('admin/jenis-bayar') ?>" class="btn btn-secondary float-start">← Kembali</a>
        <button class="btn btn-success float-end"><i class="bi bi-floppy"></i> Simpan Perubahan</button>
      </div>
    </form>
  <?php endif ?>
<?php endif ?>

<script>
document.querySelectorAll('.currency-input').forEach(input => {
  input.addEventListener('input', function () {
    let raw = this.value.replace(/\D/g, '');
    let formatted = new Intl.NumberFormat('id-ID').format(raw);
    this.value = formatted;

    const target = this.dataset.target;
    const hiddenInput = document.getElementById(target);
    if (hiddenInput) hiddenInput.value = raw;
  });
});
</script>

<?= $this->endSection() ?>
