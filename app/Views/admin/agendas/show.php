<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?= esc($agenda['title']) ?></h5>
    <div>
      <?php if ($isOwnerOrAdmin): ?>
        <a href="<?= site_url('admin/agendas/' . $agenda['id'] . '/edit') ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
        <a href="<?= site_url('admin/agendas/' . $agenda['id'] . '/delete') ?>" class="btn btn-sm btn-danger"
          onclick="return confirm('Yakin ingin menghapus agenda ini?')">🗑️ Hapus</a>
      <?php endif; ?>
      <a href="<?= site_url('admin/agendas') ?>" class="btn btn-sm btn-secondary">← Kembali ke Kalender</a>
    </div>
  </div>
  <div class="card-body">
    <table class="table table-sm table-borderless" style="max-width:500px;">
      <tr>
        <td style="width:120px;"><strong>📅 Tanggal</strong></td>
        <td>: <?= $agenda['date'] ?></td>
      </tr>
      <tr>
        <td><strong>⏰ Waktu</strong></td>
        <td>: <?= $agenda['start_time'] ?> - <?= $agenda['end_time'] ?></td>
      </tr>
      <tr>
        <td><strong>🎓 Kelas</strong></td>
        <td>: <?= esc($classNames) ?></td>
      </tr>
      <tr>
        <td><strong>🌐 Status</strong></td>
        <td>: <?= $agenda['is_public'] ? 'Publik' : 'Pribadi' ?></td>
      </tr>
    </table>

    <hr>
    <h6>Deskripsi</h6>
    <div><?= $agenda['description'] ?></div>
  </div>
</div>

<?= $this->endSection() ?>