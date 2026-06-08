<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Nama Ujian</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdd">
      <i class="bi bi-plus-circle"></i> Tambah Nama Ujian
    </button>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
  <?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th width="10%">No</th>
            <th>Nama Ujian</th>
            <th width="20%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examNames as $i => $exam): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= esc($exam['name']) ?></td>
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
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
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
<?= $this->endSection() ?>
