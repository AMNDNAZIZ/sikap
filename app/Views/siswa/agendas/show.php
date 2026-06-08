<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?= esc($agenda['title']) ?></h5>
  </div>
  <div class="card-body">
    <table class="table table-sm table-borderless" style="max-width:500px;">
      <tr>
        <td style="width:120px;"><strong>Tanggal</strong></td>
        <td>: <?= $agenda['date'] ?></td>
      </tr>
      <tr>
        <td><strong>Waktu</strong></td>
        <td>: <?= $agenda['start_time'] ?> - <?= $agenda['end_time'] ?></td>
      </tr>
      <tr>
        <td><strong>Kelas</strong></td>
        <td>: <?= $agenda['class_id'] ? esc($agenda['class_name']) : 'Semua Kelas' ?></td>
      </tr>
      <tr>
        <td><strong>Status</strong></td>
        <td>: <?= $agenda['is_public'] ? 'Publik' : 'Pribadi' ?></td>
      </tr>
    </table>

    <hr>
    <h6>Deskripsi</h6>
    <div><?= $agenda['description'] ?></div>
  </div>
  <div class="card-footer">
    <a href="<?= site_url('siswa/agendas') ?>" class="btn btn-sm btn-secondary">← Kembali</a>
  </div>
</div>

<?= $this->endSection() ?>
