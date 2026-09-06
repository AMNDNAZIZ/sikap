<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Nama Ujian</h4>
    <?php if ($isAdmin): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdd">
      <i class="bi bi-plus-circle"></i> Tambah Nama Ujian
    </button>
    <?php endif; ?>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
  <?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
  <?php endif; ?>

  <?php if (!$isAdmin): ?>
  <div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Daftar nama ujian hanya dapat dikelola oleh Admin.
  </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th width="10%">No</th>
            <th>Nama Ujian</th>
            <?php if ($isAdmin): ?>
            <th width="20%">Aksi</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examNames as $i => $exam): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= esc($exam['name']) ?></td>
              <?php if ($isAdmin): ?>
              <td>
                <div class="btn-group">
                  <button class="btn btn-warning btn-sm btn-edit"
                          data-id="<?= $exam['id'] ?>"
                          data-name="<?= esc($exam['name']) ?>"
                          data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="<?= site_url('admin/cbt/examname/delete/' . $exam['id']) ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Yakin hapus nama ujian ini?')">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($examNames)): ?>
            <tr><td colspan="<?= $isAdmin ? 3 : 2 ?>" class="text-center text-muted py-3">Belum ada nama ujian.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal Tambah -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <form action="<?= site_url('admin/cbt/examname/store') ?>" method="post" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Tambah Nama Ujian</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nama Ujian</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEdit" method="post" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Edit Nama Ujian</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id">
        <label class="form-label">Nama Ujian</label>
        <input type="text" id="edit_name" name="name" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success" id="btnSaveEdit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($isAdmin): ?>
<script>
$(function(){
  $('.btn-edit').on('click', function(){
    const id = $(this).data('id');
    const name = $(this).data('name');
    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#formEdit').attr('action', '<?= site_url('admin/cbt/examname/update/') ?>' + id);
  });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
