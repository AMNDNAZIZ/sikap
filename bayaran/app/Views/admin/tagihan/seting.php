<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$bulanMap = [
    1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober', 5 => 'November', 6 => 'Desember',
    7 => 'Januari', 8 => 'Februari', 9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
];
?>
<h4>Seting Tarif - <?= esc($jenis['nama_jenis_bayar']) ?> (<?= esc($jenis['tipe']) ?>)</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif ?>
<?php if ($kelasFilter && !$siswaList): ?>
  <div class="alert alert-warning">Tidak ada siswa di kelas ini.</div>
<?php endif ?>

<form method="get" id="kelasForm">
  <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
  <div class="row mb-3">
    <div class="col-md-4">
      <label>Pilih Kelas</label>
      <select name="kelas_id" class="form-select" id="kelasSelect">
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
  <form method="post" action="<?= site_url('admin/tagihan/update') ?>" id="updateForm">
    <?= csrf_field() ?>
    <input type="hidden" name="kelas_id" value="<?= esc($kelasFilter) ?>">

    <?php
      $adaYangBelum = false;
      foreach ($siswaList as $s) {
          $tags = $tagihanMap[$s['siswa_id']] ?? [];
          if (empty(array_filter($tags, fn($t) => $t['jumlah'] > 0))) {
              $adaYangBelum = true;
              break;
          }
      }
    ?>

    <?php if ($adaYangBelum): ?>
      <div class="mb-3">
        <label>Tarif Default</label>
        <input type="text" class="form-control currency-input text-end" data-target="default_tarif" placeholder="0">
        <input type="hidden" name="tarif" id="default_tarif">
      </div>
    <?php endif ?>

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
                        <?php foreach (array_slice($validTags, 0, 6) as $t): ?>
                          <div class="mb-2">
                            <label><?= $bulanMap[$t['bulan']] ?? 'Bulan ' . $t['bulan'] ?></label>
                            <input type="text" class="form-control currency-input text-end"
                                   data-target="bulan_<?= $t['id'] ?>"
                                   value="<?= number_format($t['jumlah'], 0, ',', '.') ?>">
                            <input type="hidden" name="jumlah[<?= $t['id'] ?>]" id="bulan_<?= $t['id'] ?>" value="<?= $t['jumlah'] ?>">
                          </div>
                        <?php endforeach ?>
                      </div>
                      <div class="col-md-6">
                        <?php foreach (array_slice($validTags, 6) as $t): ?>
                          <div class="mb-2">
                            <label><?= $bulanMap[$t['bulan']] ?? 'Bulan ' . $t['bulan'] ?></label>
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
                <button type="button" class="btn btn-sm btn-success mt-1 generate-btn" 
                        data-jenis-id="<?= $jenis['id'] ?>" 
                        data-siswa-id="<?= $s['siswa_id'] ?>"
                        data-kelas-id="<?= esc($kelasFilter) ?>">
                  Generate Tarif
                </button>
                <div class="generate-form d-none mt-2" id="generate-form-<?= $s['siswa_id'] ?>">
                  <input type="number" class="form-control form-control-sm d-inline-block" 
                         style="width: 100px;" placeholder="Tarif" required
                         id="jumlah-input-<?= $s['siswa_id'] ?>">
                  <button class="btn btn-sm btn-primary mt-1 confirm-generate">Konfirmasi</button>
                  <button class="btn btn-sm btn-secondary mt-1 cancel-generate">Batal</button>
                </div>
              <?php endif ?>
            </td>
            <td>
              <?php if (!empty($validTags)): ?>
                <a href="<?= site_url("admin/tagihan/delete/{$jenis['id']}/{$s['siswa_id']}") ?>" class="btn btn-sm btn-warning">Reset</a>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>

    <div class="mb-3">
      <a href="<?= site_url('admin/jenis-bayar') ?>" class="btn btn-secondary float-start">← Kembali</a>
      <button class="btn btn-success float-end"><i class="bi bi-floppy"></i> Simpan</button>
    </div>
  </form>

  <?php if ($adaYangBelum): ?>
    <form method="post" action="<?= site_url('admin/tagihan/seting/' . $jenis['id']) ?>" id="generate-masal-form" class="mb-4">
      <?= csrf_field() ?>
      <input type="hidden" name="kelas_id" value="<?= esc($kelasFilter) ?>">
      <input type="hidden" name="tarif" id="hidden_tarif_for_masal">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Generate Tagihan Masal
      </button>
    </form>
  <?php endif ?>
<?php endif ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('kelasSelect')?.addEventListener('change', () => {
    document.getElementById('kelasForm')?.submit();
  });

  document.querySelectorAll('.currency-input').forEach(input => {
    input.addEventListener('input', function () {
      let raw = this.value.replace(/\D/g, '');
      this.value = new Intl.NumberFormat('id-ID').format(raw);

      const target = this.dataset.target;
      const hidden = document.getElementById(target);
      if (hidden) hidden.value = raw;

      // Sync to massal
      if (target === 'default_tarif') {
        const masal = document.getElementById('hidden_tarif_for_masal');
        if (masal) masal.value = raw;
      }
    });
  });

  // Enter untuk default tarif
  const tarifInput = document.querySelector('[data-target="default_tarif"]');
  tarifInput?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('generate-masal-form')?.submit();
    }
  });

  document.querySelectorAll('.generate-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.siswaId;
      this.classList.add('d-none');
      document.getElementById(`generate-form-${id}`)?.classList.remove('d-none');
      document.getElementById(`jumlah-input-${id}`)?.focus();
    });
  });

  document.querySelectorAll('.cancel-generate').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('.generate-form');
      const id = form.id.split('-')[2];
      form.classList.add('d-none');
      document.querySelector(`.generate-btn[data-siswa-id="${id}"]`)?.classList.remove('d-none');
    });
  });

  document.querySelectorAll('.confirm-generate').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('.generate-form');
      const id = form.id.split('-')[2];
      const jumlah = document.getElementById(`jumlah-input-${id}`).value;
      const jenis = document.querySelector(`.generate-btn[data-siswa-id="${id}"]`)?.dataset.jenisId;
      const kelas = document.querySelector(`.generate-btn[data-siswa-id="${id}"]`)?.dataset.kelasId;

      if (!jumlah) return alert('Masukkan tarif');

      fetch(`<?= site_url('admin/tagihan/generate-siswa/') ?>${jenis}/${id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: `jumlah=${jumlah}&kelas_id=${kelas}&<?= csrf_token() ?>=<?= csrf_hash() ?>`
      })
      .then(r => r.json())
      .then(d => d.success ? location.reload() : alert(d.message || 'Gagal'))
      .catch(() => alert('Gagal kirim data'));
    });
  });
});
</script>

<?= $this->endSection() ?>
