<?php if (empty($agendas)): ?>
  <div class="text-muted">Tidak ada agenda pada tanggal ini.</div>
<?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($agendas as $a): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <span class="me-2">🕒 <?= $a['start_time'] ?> - <?= $a['end_time'] ?></span>
          <strong><?= esc($a['title']) ?></strong>
        </div>
        <a href="<?= site_url('siswa/agendas/'.$a['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
