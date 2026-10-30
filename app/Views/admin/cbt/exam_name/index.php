<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  @media (max-width: 576px) {
    .btn-header-action {
      width: 100%;
    }
    .table-exam td, .table-exam th {
      padding: 0.6rem 0.5rem;
      font-size: 0.9rem;
    }
    .btn-action-touch {
      min-width: 36px;
      min-height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
  }
</style>

<div class="container-fluid py-2">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Daftar Nama Ujian</h4>
      <small class="text-muted">Master data penamaan ujian CBT</small>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn btn-success btn-header-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
      <i class="bi bi-plus-circle me-1"></i> Tambah Nama Ujian
    </button>
    <?php endif; ?>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?= session('success') ?></div>
  <?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?= session('error') ?></div>
  <?php endif; ?>

  <?php if (!$isAdmin): ?>
  <div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Daftar nama ujian hanya dapat dikelola oleh Admin.
  </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body p-0 p-md-3">
      <div class="table-responsive">
      <table class="table table-hover table-exam align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="8%" class="text-center">No</th>
            <th>Nama Ujian</th>
            <?php if ($isAdmin): ?>
            <th width="15%" class="text-center">Aksi</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examNames as $i => $exam): ?>
            <tr>
              <td class="text-center fw-semibold text-muted"><?= $i + 1 ?></td>
              <td class="fw-semibold text-dark"><?= esc($exam['name']) ?></td>
              <?php if ($isAdmin): ?>
              <td class="text-center">
                <div class="btn-group shadow-sm">
                  <button class="btn btn-warning btn-sm btn-edit btn-action-touch"
                          data-id="<?= $exam['id'] ?>"
                          data-name="<?= esc($exam['name']) ?>"
                          data-bs-toggle="modal" data-bs-target="#modalEdit"
                          title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="<?= site_url('admin/cbt/examname/delete/' . $exam['id']) ?>"
                     class="btn btn-danger btn-sm btn-action-touch"
                     onclick="return confirm('Yakin hapus nama ujian ini?')"
                     title="Hapus">
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
