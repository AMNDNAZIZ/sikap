<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">Manajemen Guru</h1>
  <?php if (session()->get('user')['role_id'] != 2): ?>
    <div class="mb-3 d-flex gap-2">
      <a href="<?= base_url('admin/teachers/create') ?>" class="btn btn-primary">➕ Tambah Guru</a>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
        📥 Import Guru
      </button>
    </div>
  <?php endif; ?>

  <?php if (session()->get('success')): ?>
    <div class="alert alert-success"><?= session()->get('success') ?></div>
  <?php endif; ?>
  <?php if (session()->get('error')): ?>
    <div class="alert alert-danger"><?= session()->get('error') ?></div>
  <?php endif; ?>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>#</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>Jenis Kelamin</th>
        <th>Telepon</th>
        <th>Email</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $i => $t): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= $t['nip'] ?></td>
          <td><?= $t['name'] ?></td>
          <td><?= $t['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
          <td><?= $t['phone'] ?></td>
          <td><?= $t['email'] ?></td>
          <td>
            <?php if (session()->get('user')['role_id'] != 2): ?>
              <a href="<?= base_url('admin/teachers/edit/' . $t['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
              <a href="<?= base_url('admin/teachers/delete/' . $t['id']) ?>" class="btn btn-sm btn-danger"
                onclick="return confirm('Yakin hapus guru ini?')">🗑 Hapus</a>
            <?php else: ?>
              <span class="badge bg-secondary">Read Only</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="<?= base_url('admin/teachers/import') ?>" method="POST" enctype="multipart/form-data"
      class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Import Data Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih File Excel (.xlsx / .xls)</label>
          <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls" required>
          <div class="form-text">
            Pastikan format sesuai dengan template.
            <a href="<?= base_url('admin/teachers/downloadTemplate') ?>" class="fw-bold">Download Template di sini.</a>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success">Mulai Import</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>