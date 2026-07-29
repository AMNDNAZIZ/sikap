<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Kenaikan / Kelulusan Siswa</h4>

<?php if (session()->getFlashdata('msg_success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?= session()->getFlashdata('msg_success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('msg_error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?= session()->getFlashdata('msg_error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('msg') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($jumlahTanpaKelas) && $jumlahTanpaKelas > 0): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Terdapat <strong><?= $jumlahTanpaKelas ?> siswa aktif</strong> yang tidak memiliki kelas di tahun ajaran ini.
    <a href="<?= site_url('admin/kenaikan/perbaiki') ?>" class="alert-link ms-1">Perbaiki sekarang &rarr;</a>
  </div>
<?php endif; ?>

<form method="get" class="row mb-3">
  <div class="col-md-4">
    <label>Filter Kelas Tahun <?= esc($tahunSebelumnya['nama']) ?></label>
    <select name="kelas_id" class="form-select" required onchange="this.form.submit()">
      <option value="">-- Pilih Kelas --</option>
      <?php foreach ($kelas as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasFilter == $k['id'] ? 'selected' : '' ?>>
          <?= esc($k['nama_kelas']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>
</form>

<?php if ($siswa): ?>
<form method="post" action="<?= site_url('admin/kenaikan/proses') ?>" id="formKenaikan">
  <?= csrf_field() ?>
  <input type="hidden" name="tahun_ajaran_id" value="<?= $tahunSekarang['id'] ?>">

  <div class="row mb-3">
    <div class="col-md-4">
      <label>Naik ke Kelas Tujuan <small class="text-muted">(diabaikan untuk siswa yang lulus)</small></label>
      <select name="kelas_id" class="form-select" id="selectKelas">
        <option value="">-- Pilih kelas tujuan --</option>
        <?php foreach ($kelas as $k): ?>
          <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
        <?php endforeach ?>
      </select>
      <div class="invalid-feedback" id="error-kelas">Kelas tujuan wajib diisi jika ada siswa yang naik kelas.</div>
    </div>
  </div>

  <div class="mb-2 d-flex gap-2 align-items-center">
    <span class="badge bg-primary px-3 py-2" id="badge-naik">Naik kelas: <span id="count-naik">0</span></span>
    <span class="badge bg-success px-3 py-2" id="badge-lulus">Lulus: <span id="count-lulus">0</span></span>
    <span class="badge bg-secondary px-3 py-2">Total dipilih: <span id="count-total">0</span></span>
  </div>

  <table class="table table-bordered table-hover" id="tableSiswa">
    <thead class="table-light">
      <tr>
        <th style="width:40px"><input type="checkbox" id="check-all" title="Pilih semua"></th>
        <th>NIS</th>
        <th>Nama</th>
        <th style="width:130px" class="text-center">
          Lulus?
          <div><input type="checkbox" id="check-all-lulus" title="Tandai semua sebagai lulus"></div>
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($siswa as $s): ?>
        <tr class="row-siswa" data-id="<?= $s['siswa_id'] ?>">
          <td><input type="checkbox" name="siswa_ids[]" value="<?= $s['siswa_id'] ?>" class="cb-siswa"></td>
          <td><?= esc($s['nis']) ?></td>
          <td><?= esc($s['nama']) ?></td>
          <td class="text-center">
            <input type="checkbox" name="lulus_ids[]" value="<?= $s['siswa_id'] ?>" class="cb-lulus">
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <button type="submit" class="btn btn-primary" id="btnProses" disabled>
    <i class="bi bi-arrow-up-circle me-1"></i> Proses Kenaikan / Kelulusan
  </button>
  <span class="text-muted ms-2 small" id="hint-pilih">Pilih minimal satu siswa untuk melanjutkan</span>
</form>

<script>
(function () {
  const checkAll      = document.getElementById('check-all');
  const checkAllLulus = document.getElementById('check-all-lulus');
  const btnProses     = document.getElementById('btnProses');
  const hintPilih     = document.getElementById('hint-pilih');
  const countNaik     = document.getElementById('count-naik');
  const countLulus    = document.getElementById('count-lulus');
  const countTotal    = document.getElementById('count-total');
  const form          = document.getElementById('formKenaikan');
  const selectKelas   = document.getElementById('selectKelas');
  const errorKelas    = document.getElementById('error-kelas');

  function updateCounts() {
    const cbSiswa = document.querySelectorAll('.cb-siswa:checked');
    let naik = 0, lulus = 0;

    cbSiswa.forEach(cb => {
      const row = cb.closest('tr');
      const cbL = row.querySelector('.cb-lulus');
      if (cbL && cbL.checked) {
        lulus++;
        row.classList.add('table-success');
        row.classList.remove('table-primary');
      } else {
        naik++;
        row.classList.add('table-primary');
        row.classList.remove('table-success');
      }
    });

    // Reset warna baris yang tidak dipilih
    document.querySelectorAll('.cb-siswa:not(:checked)').forEach(cb => {
      cb.closest('tr').classList.remove('table-primary', 'table-success');
    });

    const total = cbSiswa.length;
    countNaik.textContent  = naik;
    countLulus.textContent = lulus;
    countTotal.textContent = total;

    btnProses.disabled = total === 0;
    hintPilih.style.display = total === 0 ? 'inline' : 'none';

    // Tandai select kelas wajib jika ada yang naik kelas
    validateKelas(naik);
  }

  function validateKelas(jumlahNaik) {
    if (jumlahNaik > 0 && !selectKelas.value) {
      selectKelas.classList.add('is-invalid');
      errorKelas.style.display = 'block';
      return false;
    } else {
      selectKelas.classList.remove('is-invalid');
      errorKelas.style.display = 'none';
      return true;
    }
  }

  // Check all siswa
  checkAll.addEventListener('change', function () {
    document.querySelectorAll('.cb-siswa').forEach(cb => cb.checked = this.checked);
    updateCounts();
  });

  // Check all lulus
  checkAllLulus.addEventListener('change', function () {
    document.querySelectorAll('.cb-lulus').forEach(cb => cb.checked = this.checked);
    updateCounts();
  });

  // Setiap perubahan checkbox
  document.querySelectorAll('.cb-siswa, .cb-lulus').forEach(cb => {
    cb.addEventListener('change', updateCounts);
  });

  // Revalidate saat kelas tujuan diubah
  selectKelas.addEventListener('change', function () {
    validateKelas(parseInt(countNaik.textContent));
  });

  // Konfirmasi + validasi sebelum submit
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const naik  = parseInt(countNaik.textContent);
    const lulus = parseInt(countLulus.textContent);

    // Validasi kelas tujuan wajib ada jika ada siswa naik
    if (!validateKelas(naik)) {
      selectKelas.focus();
      return;
    }

    let msg = 'Konfirmasi proses:\n';
    if (naik > 0)  msg += '  \u2022 ' + naik + ' siswa naik kelas ke ' + selectKelas.options[selectKelas.selectedIndex].text + '\n';
    if (lulus > 0) msg += '  \u2022 ' + lulus + ' siswa dinyatakan LULUS\n';
    msg += '\nLanjutkan?';

    if (confirm(msg)) {
      form.submit();
    }
  });

  // Init
  updateCounts();
})();
</script>

<?php elseif ($kelasFilter): ?>
<div class="alert alert-warning">
  <i class="bi bi-info-circle me-2"></i>
  Tidak ada siswa yang perlu diproses di kelas ini. Semua siswa sudah naik kelas atau lulus.
</div>
<?php endif ?>

<?= $this->endSection() ?>
