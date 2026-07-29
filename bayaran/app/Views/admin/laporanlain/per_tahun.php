<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
  <h3>Laporan Penerimaan per Tahun Ajaran</h3>
  <form method="GET" class="form-inline mb-3">
    <div class="form-group mr-2">
      <label for="tahun" class="mr-2">Tahun Ajaran:</label>
      <select name="tahun" id="tahun" class="form-control">
        <option value="">-- Pilih Tahun --</option>
        <?php foreach($tahunList as $ta): ?>
          <option value="<?= $ta['id'] ?>" <?= (isset($tahun) && $tahun==$ta['id'])?'selected':'' ?>>
            <?= $ta['nama'] ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="form-group mr-2">
      <label for="pos" class="mr-2">Pos Bayar:</label>
      <select name="pos" id="pos" class="form-control">
        <option value="">-- Semua Pos --</option>
        <?php foreach($posList as $posItem): ?>
          <option value="<?= $posItem['id'] ?>" <?= (isset($pos) && $pos==$posItem['id'])?'selected':'' ?>>
            <?= $posItem['nama_pos'] ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary mr-2 mt-3">Tampilkan</button>
    <?php if(isset($data)): ?>
        <a class="btn btn-success mt-3"
           href="<?= base_url('admin/laporanlain/export-pertahun') . "?tahun=$tahun&pos=$pos" ?>">
           Export Excel
        </a>
    <?php endif ?>
  </form>

  <?php if(isset($data)): ?>
    <table class="table table-bordered">
      <thead class="thead-light">
        <tr><th>Tahun Ajaran</th><th>Total Penerimaan</th></tr>
      </thead>
      <tbody>
        <tr>
          <td><?= esc($data['tahun_ajaran']) ?></td>
          <td>Rp <?= number_format($data['total'],0,',','.') ?></td>
        </tr>
      </tbody>
    </table>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
