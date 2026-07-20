<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">Manajemen Tahun Ajaran</h1>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>

  <?php if (session()->get('user')['role_id'] != 2): ?>
    <a href="<?= base_url('admin/academic-year/create') ?>" class="btn btn-primary mb-3">➕ Tambah Tahun Ajaran</a>
  <?php endif; ?>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Tahun</th>
        <th>Periode</th>
        <th>Bobot (F:S)</th>
        <th>Hari Sekolah</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($years as $y): ?>
        <tr>
          <td><?= $y['year'] ?></td>
          <td><?= date('d M Y', strtotime($y['start_date'])) ?> - <?= date('d M Y', strtotime($y['end_date'])) ?></td>
          <td><?= $y['formatif_weight'] ?>% : <?= $y['sumatif_weight'] ?>%</td>
          <td>
            <span class="badge bg-info">
              <?= ($y['school_days'] ?? 5) == 5 ? '5 Hari (Sen-Jum)' : '6 Hari (Sen-Sab)' ?>
            </span>
          </td>
          <td>
            <?php if ($y['is_active']): ?>
              <span class="badge bg-success">Aktif</span>
            <?php else: ?>
              <span class="badge bg-secondary">Tidak Aktif</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (session()->get('user')['role_id'] != 2): ?>
              <a href="<?= base_url('admin/academic-year/edit/' . $y['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
              <?php if (!$y['is_active']): ?>
                <form method="post" action="<?= base_url('admin/academic-year/set-active/' . $y['id']) ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('Aktifkan tahun ajaran <?= esc($y['year']) ?>?')">
                    ✅ Aktifkan
                  </button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge bg-secondary">Read Only</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>