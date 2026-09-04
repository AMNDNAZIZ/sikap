<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Kuis Mandiri</h5>
  <a href="<?= site_url('admin/quiz/create') ?>" class="btn btn-sm btn-primary">
    <i class="bi bi-plus-circle me-1"></i>Buat Kuis Baru
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Judul Kuis</th>
            <th>Mapel / Bank</th>
            <th class="text-center">Soal</th>
            <th class="text-center">Status</th>
            <th class="text-center">Attempt</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($quizzes)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada kuis.</td></tr>
          <?php else: ?>
            <?php $no = 1; foreach ($quizzes as $q): ?>
              <tr id="row-<?= $q['id'] ?>">
                <td class="text-muted"><?= $no++ ?></td>
                <td>
                  <div class="fw-semibold"><?= esc($q['title']) ?></div>
                  <?php if (!empty($q['description'])): ?>
                    <div class="text-muted text-truncate" style="max-width:280px; font-size:0.78rem;">
                      <?= esc($q['description']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($q['class_ids_arr'])): ?>
                    <div class="mt-1" style="font-size:0.72rem; color:#6c757d;">
                      <i class="bi bi-people me-1"></i><?= count($q['class_ids_arr']) ?> kelas
                    </div>
                  <?php else: ?>
                    <div class="mt-1" style="font-size:0.72rem; color:#6c757d;">
                      <i class="bi bi-globe me-1"></i>Semua kelas
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div><?= esc($q['subject_name'] ?? '-') ?></div>
                  <div class="text-muted" style="font-size:0.72rem;"><?= esc($q['bank_code'] ?? '') ?></div>
                </td>
                <td class="text-center">
                  <?php
                    $tot = ((int)$q['show_pg_count'])
                         + ((int)$q['show_pgk_count'])
                         + ((int)$q['show_bs_count'])
                         + ((int)$q['show_esai_count']);
                  ?>
                  <span class="badge bg-secondary"><?= $tot ?> soal</span>
                </td>
                <td class="text-center">
                  <?= $q['is_published']
                    ? '<span class="badge bg-success">Published</span>'
                    : '<span class="badge bg-warning text-dark">Draft</span>' ?>
                </td>
                <td class="text-center text-muted"><?= number_format($q['total_attempts']) ?></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <a href="<?= site_url('admin/quiz/edit/' . $q['id']) ?>"
                       class="btn btn-warning btn-sm" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="<?= site_url('admin/quiz/results/' . $q['id']) ?>"
                       class="btn btn-info btn-sm text-white" title="Hasil">
                      <i class="bi bi-bar-chart"></i>
                    </a>
                    <button class="btn btn-danger btn-sm btn-delete"
                            data-id="<?= $q['id'] ?>" data-title="<?= esc($q['title']) ?>"
                            title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Hapus Kuis</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        Hapus kuis <strong id="delTitle"></strong>? Semua data sesi siswa ikut terhapus.
      </div>
      <div class="modal-footer py-2">
        <button id="confirmDel" class="btn btn-danger btn-sm">Hapus</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
  var deleteId = null;
  var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
  document.querySelectorAll('.btn-delete').forEach(function(b){
    b.addEventListener('click', function(){
      deleteId = this.dataset.id;
      document.getElementById('delTitle').textContent = this.dataset.title;
      modal.show();
    });
  });
  document.getElementById('confirmDel').addEventListener('click', function(){
    var btn = this; btn.disabled = true;
    fetch('<?= site_url('admin/quiz/delete/') ?>' + deleteId, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:'<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r=>r.json()).then(function(d){
      btn.disabled = false; modal.hide();
      if(d.success){
        var row = document.getElementById('row-'+deleteId);
        if(row){ row.style.opacity='0'; setTimeout(()=>row.remove(),400); }
      } else { alert(d.message); }
    });
  });
})();
</script>
<?= $this->endSection() ?>
