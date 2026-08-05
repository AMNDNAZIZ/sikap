<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
  <h3>Laporan Penerimaan Per Tahun Ajaran Berdasarkan Rentang Tanggal</h3>

  <form method="GET" action="<?= base_url('admin/laporanlain/rentang') ?>" class="form-inline mb-3" id="formFilter">
    <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
      <div class="form-group me-2">
        <label class="me-2" for="start">Dari:</label>
        <input type="date" name="start" id="start" value="<?= esc($start) ?>" class="form-control" required>
      </div>

      <div class="form-group me-2">
        <label class="me-2" for="end">Sampai:</label>
        <input type="date" name="end" id="end" value="<?= esc($end) ?>" class="form-control" required>
      </div>

      <div class="form-group me-2">
        <label class="me-2" for="pos">Pos Bayar:</label>
        <select name="pos" id="pos" class="form-control" required>
          <option value="">-- Semua Pos --</option>
          <?php foreach ($posList as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $pos == $p['id'] ? 'selected' : '' ?>>
              <?= $p['nama_pos'] ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <button class="btn btn-primary me-2 mt-3" type="submit" id="btnTampil" disabled>Tampilkan</button>
    <?php if(isset($data) && $data): ?>
      <a class="btn btn-success mt-3"
         href="<?= base_url('admin/laporanlain/export-rentang') . "?start=$start&end=$end&pos=$pos" ?>">
         Export Excel
      </a>
    <?php endif ?>
  </form>

  <?php if(isset($data) && $data): ?>
    <table class="table table-bordered">
      <thead class="thead-light">
        <tr><th>No</th><th>Tahun Ajaran</th><th>Total Penerimaan</th></tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($data as $row): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($row['tahun_ajaran']) ?></td>
            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php elseif($start && $end && $pos): ?>
    <div class="alert alert-warning">Tidak ada data dalam rentang ini.</div>
  <?php endif ?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const startInput = document.getElementById('start');
    const endInput   = document.getElementById('end');
    const posInput   = document.getElementById('pos');
    const btnTampil  = document.getElementById('btnTampil');

    function validateForm() {
      btnTampil.disabled = !(startInput.value && endInput.value && posInput.value);
    }

    [startInput, endInput, posInput].forEach(el => {
      el.addEventListener('input', validateForm);
      el.addEventListener('change', validateForm);
    });

    validateForm(); // cek awal
  });
</script>
<?= $this->endSection() ?>
