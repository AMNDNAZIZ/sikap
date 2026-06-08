<?php if (empty($agendas)): ?>
  <div class="text-muted">Tidak ada agenda pada tanggal ini.</div>
<?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($agendas as $a): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <span class="me-2">🕒 <?= $a['start_time'] ?> - <?= $a['end_time'] ?></span>
          <strong><?= esc($a['title']) ?></strong>
          <div class="small text-muted">
            Kelas: <?= !empty($a['class_names']) ? esc($a['class_names']) : '<span class="text-primary">Umum</span>' ?>
          </div>
        </div>
        <a href="<?= site_url('admin/agendas/' . $a['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<div class="mt-3 text-end">
  <a href="<?= site_url('admin/agendas/create?date=' . $date) ?>" class="btn btn-sm btn-primary">+ Buat Agenda di
    Tanggal Ini</a>
</div>