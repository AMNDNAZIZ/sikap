<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h5>📒 Catatan - <?= esc($student['name']) ?> (<?= esc($student['nis'] ?? '-') ?>)</h5>

<div class="table-responsive mt-3">
  <table class="table table-bordered align-middle">
    <thead class="table-success">
      <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Perilaku</th>
        <th>Catatan Tambahan</th>
        <th>Total Poin</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($notes)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted">Belum ada catatan.</td>
        </tr>
      <?php else: ?>
        <?php $no=1; $total=0; foreach ($notes as $n): 
          $sumPoints = 0;
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= date('d-m-Y', strtotime($n['created_at'])) ?></td>
          <td>
            <ul class="mb-0">
              <?php foreach ($n['behaviors'] as $b): 
                $sumPoints += $b['points'];
              ?>
                <li>
                  <?= esc($b['name']) ?> 
                  <?php if ($b['points'] > 0): ?>
                    <span class="text-success">(+<?= $b['points'] ?>)</span>
                  <?php else: ?>
                    <span class="text-danger">(<?= $b['points'] ?>)</span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </td>
          <td><?= esc($n['note'] ?? '-') ?></td>
          <td>
            <?php if ($sumPoints > 0): ?>
              <b class="text-success">+<?= $sumPoints ?></b>
            <?php elseif ($sumPoints < 0): ?>
              <b class="text-danger"><?= $sumPoints ?></b>
            <?php else: ?>
              <b>0</b>
            <?php endif; ?>
          </td>
        </tr>
        <?php $total += $sumPoints; endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if (!empty($notes)): ?>
  <div class="alert alert-info mt-3">
    <b>Total Poin:</b> 
    <?php if ($total > 0): ?>
      <span class="text-success">+<?= $total ?></span>
    <?php elseif ($total < 0): ?>
      <span class="text-danger"><?= $total ?></span>
    <?php else: ?>
      <span>0</span>
    <?php endif; ?>

    <?php
      if ($total > 10) {
        $predikat = "Sangat Baik";
        $badge    = "success";
      } elseif ($total >= 0) {
        $predikat = "Baik";
        $badge    = "primary";
      } elseif ($total >= -10) {
        $predikat = "Kurang Baik";
        $badge    = "warning";
      } else {
        $predikat = "Tidak Baik";
        $badge    = "danger";
      }
    ?>
    <br>
    <b>Predikat:</b> <span class="badge bg-<?= $badge ?>"><?= $predikat ?></span>
  </div>
<?php endif; ?>



<?= $this->endSection() ?>
