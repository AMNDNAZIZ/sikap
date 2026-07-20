<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <div>
      <h1 class="mb-0">Manajemen Kelas</h1>
      <small class="text-muted">Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span> | Tingkat Kelas: <?= esc($level_range) ?></small>
    </div>
    <?php if (session()->get('user')['role_id'] != 2): ?>
      <a href="<?= base_url('admin/classes/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Kelas
      </a>
    <?php endif; ?>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th width="50">#</th>
              <th>Nama Kelas</th>
              <th width="100" class="text-center">Tingkat</th>
              <th>Wali Kelas</th>
              <th width="100" class="text-center">Status</th>
              <th width="200" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($classes as $i => $c): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($c['name']) ?></td>
                <td class="text-center">
                  <span class="badge bg-info"><?= esc($c['level']) ?></span>
                </td>
                <td><?= esc($c['teacher_name'] ?: '-') ?></td>
                <td class="text-center">
                  <?php if (isset($c['is_active']) && $c['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Tidak Aktif</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if (session()->get('user')['role_id'] != 2): ?>
                    <a href="<?= base_url('admin/classes/toggle/' . $c['id']) ?>" class="btn btn-sm <?= (isset($c['is_active']) && $c['is_active']) ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="Ubah Status Aktif">
                      <i class="bi bi-power"></i>
                    </a>
                    <a href="<?= base_url('admin/classes/edit/' . $c['id']) ?>" class="btn btn-sm btn-warning">
                      <i class="bi bi-pencil-square"></i> Edit
                    </a>
                    <a href="<?= base_url('admin/classes/delete/' . $c['id']) ?>" class="btn btn-sm btn-danger"
                      onclick="return confirm('Yakin hapus kelas ini?')">
                      <i class="bi bi-trash"></i> Hapus
                    </a>
                  <?php else: ?>
                    <span class="badge bg-secondary">Read Only</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>