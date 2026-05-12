<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
.card, .card-body {
  min-width: 0;
}
.list-group-item {
  overflow-wrap: anywhere;
  word-break: break-word;
}
.list-group-item.d-flex {
  flex-wrap: wrap;
}
</style>

<div class="card">
  <div class="card-header">Chat Room Kelas</div>
  <div class="card-body">
    <?php if (!empty($classes)): ?>
      <ul class="list-group">
        <?php foreach($classes as $c): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= esc($c['name']) ?></span>
            <a href="<?= site_url('admin/chat/room/'.$c['id']) ?>" class="btn btn-sm btn-primary">Masuk</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">Tidak ada kelas yang tersedia untuk Anda.</p>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
