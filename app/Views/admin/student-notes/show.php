<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>📒 Catatan Siswa - <?= esc($student['name']) ?> (<?= esc($student['nis'] ?? '-') ?>)</h4>
<?php if (session()->get('user')['role_id'] != 2): ?>
  <a href="<?= base_url('admin/student-notes/create/' . $student['id']) ?>" class="btn btn-success mb-3">+ Tambah
    Catatan</a>
<?php endif; ?>
<a href="<?= base_url('admin/student-notes') ?>" class="btn btn-secondary mb-3">Kembali</a>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-success">
      <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Perilaku</th>
        <th>Catatan Tambahan</th>
        <th>Total Poin</th>
        <?php if (session()->get('user')['role_id'] != 2): ?>
          <th>Aksi</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1;
      $total = 0;
      foreach ($notes as $n):
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
          <?php if (session()->get('user')['role_id'] != 2): ?>
            <td>
              <a href="<?= base_url('admin/student-notes/edit/' . $n['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="<?= base_url('admin/student-notes/delete/' . $n['id']) ?>" class="btn btn-sm btn-danger"
                onclick="return confirm('Yakin ingin menghapus catatan ini?')">Hapus</a>
            </td>
          <?php endif; ?>
        </tr>
        <?php $total += $sumPoints; endforeach; ?>
    </tbody>
  </table>
</div>

<div class="alert alert-info mt-3">
  <b>Total Poin:</b>
  <?php if ($total > 0): ?>
    <span class="text-success">+<?= $total ?></span>
  <?php elseif ($total < 0): ?>
    <span class="text-danger"><?= $total ?></span>
  <?php else: ?>
    <span>0</span>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>