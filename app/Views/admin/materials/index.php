<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-book me-2 text-primary"></i><?= esc($title) ?>
  </h5>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= site_url('admin/materials/use/' . $subject['id']) ?>"
       class="btn btn-sm btn-outline-info">
      <i class="bi bi-box-arrow-in-down me-1"></i>Gunakan Materi
    </a>
    <a href="<?= site_url('admin/materials/progress/' . $subject['id']) ?>"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-bar-chart me-1"></i>Progress
    </a>
    <button type="button" id="btnSyncAtp" class="btn btn-sm btn-primary">
      <i class="bi bi-arrow-repeat me-1"></i>Sinkron Materi dari ATP
    </button>
  </div>
</div>

<div class="alert alert-info border-0 py-2 small mb-3 d-flex align-items-start gap-2">
  <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
  <div>
    <strong>Materi</strong> otomatis diambil dari <strong>ATP</strong> (Alur Tujuan Pembelajaran) yang sudah Anda buat.
    Tugas Anda adalah menambahkan <strong>Sub Materi</strong> (pertemuan) pada setiap materi, lalu mempublikasikannya ke kelas.
    Jika materi belum muncul, klik <strong>Sinkron Materi dari ATP</strong>.
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 small">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($hierarchy)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-folder2 fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-2">Belum ada materi dari ATP untuk mapel ini.</p>
    <p class="small">Pastikan Anda sudah membuat <strong>ATP (Alur Tujuan Pembelajaran)</strong>
       untuk mata pelajaran ini, lalu klik tombol Sinkron di atas.</p>
    <button type="button" id="btnSyncAtpEmpty" class="btn btn-sm btn-primary">
      <i class="bi bi-arrow-repeat me-1"></i>Sinkron Materi dari ATP
    </button>
  </div>
<?php else: ?>

  <?php foreach ($hierarchy as $parent): ?>
    <?php
      $children  = $parent['children'] ?? [];
      $semLabel  = $parent['semester'] == 1 ? 'Ganjil' : 'Genap';
      $lvLabel   = \App\Models\SubjectMaterialModel::getLevelLabel((int)$parent['level']);
    ?>
    <div class="card border-0 shadow-sm mb-4" id="parent-<?= $parent['id'] ?>">

      <!-- ── Header Materi Induk ── -->
      <div class="card-header bg-primary bg-opacity-10 border-bottom py-2
                  d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-folder2-open text-primary fs-5"></i>
          <div>
            <span class="fw-bold"><?= esc($parent['title']) ?></span>
            <span class="ms-2 badge bg-secondary" style="font-size:0.62rem;"><?= $semLabel ?></span>
            <span class="ms-1 badge bg-info text-dark" style="font-size:0.62rem;"><?= $lvLabel ?></span>
            <?php if ($parent['is_published']): ?>
              <span class="ms-1 badge bg-success" style="font-size:0.62rem;">Siap</span>
            <?php else: ?>
              <span class="ms-1 badge bg-warning text-dark" style="font-size:0.62rem;">Draft</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex gap-1">
          <a href="<?= site_url('admin/materials/create/' . $subject['id'] . '?parent_id=' . $parent['id']) ?>"
             class="btn btn-sm btn-primary py-0 px-2" title="Tambah Sub Materi (Pertemuan)" style="font-size:0.78rem;">
            <i class="bi bi-plus-circle me-1"></i>Sub Materi
          </a>
        </div>
      </div>

      <!-- ── Daftar Sub Materi ── -->
      <?php if (empty($children)): ?>
        <div class="card-body text-muted small py-3 text-center">
          Belum ada sub materi. Klik <strong>+ Sub Materi</strong> untuk menambahkan.
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($children as $idx => $child): ?>
            <?php
              $icon        = \App\Models\SubjectMaterialModel::getContentTypeIcon($child['content_type'] ?? 'text');
              $typeLabel   = \App\Models\SubjectMaterialModel::getContentTypeLabel($child['content_type'] ?? 'text');
              $prog        = $progSummary[$child['id']] ?? ['completed' => 0, 'in_progress' => 0];
              $publishedIds= \Config\Database::connect()
                ->table('subject_material_publishes')
                ->select('COUNT(*) as cnt')
                ->where('material_id', $child['id'])
                ->where('is_active', 1)
                ->get()->getRowArray()['cnt'] ?? 0;
              $hasThread   = \Config\Database::connect()
                ->table('forum_threads')
                ->where('related_type', 'material')
                ->where('related_id', $child['id'])
                ->where('is_system', 1)
                ->countAllResults() > 0;
            ?>
            <div class="list-group-item px-4 py-3" id="child-<?= $child['id'] ?>">
              <div class="d-flex align-items-start gap-3">

                <!-- Nomor -->
                <span class="flex-shrink-0 rounded-circle bg-primary text-white d-flex align-items-center
                             justify-content-center fw-bold"
                      style="width:26px;height:26px;font-size:0.72rem;margin-top:3px;">
                  <?= $idx + 1 ?>
                </span>

                <!-- Info -->
                <div class="flex-grow-1 overflow-hidden">
                  <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="fw-semibold" style="font-size:0.875rem;"><?= esc($child['title']) ?></span>
                    <?php if ($child['is_published']): ?>
                      <span class="badge bg-success" style="font-size:0.62rem;">Siap</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark" style="font-size:0.62rem;">Draft</span>
                    <?php endif; ?>
                    <?php if ($publishedIds > 0): ?>
                      <span class="badge bg-primary" style="font-size:0.62rem;">
                        Published ke <?= $publishedIds ?> kelas
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex flex-wrap gap-3" style="font-size:0.72rem;color:#6c757d;">
                    <span><i class="<?= $icon ?> me-1"></i><?= $typeLabel ?></span>
                    <?php if ($child['estimated_minutes']): ?>
                      <span><i class="bi bi-clock me-1"></i><?= $child['estimated_minutes'] ?> menit</span>
                    <?php endif; ?>
                    <span class="<?= $child['is_published'] ? 'text-success' : '' ?>">
                      <i class="bi bi-check2-circle me-1"></i><?= $prog['completed'] ?> selesai
                    </span>
                    <span class="<?= $hasThread ? 'text-primary' : '' ?>">
                      <i class="bi bi-chat-dots me-1"></i><?= $hasThread ? 'Diskusi aktif' : 'Belum ada diskusi' ?>
                    </span>
                  </div>
                </div>

                <!-- Aksi -->
                <div class="d-flex gap-1 flex-shrink-0">
                  <a href="<?= site_url('admin/materials/show/' . $child['id']) ?>"
                     class="btn btn-sm btn-outline-primary py-0 px-2"
                     title="Lihat Konten & Diskusi" style="font-size:0.72rem;">
                    <i class="bi bi-eye me-1"></i>Lihat
                  </a>
                  <?php if ($child['is_published']): ?>
                    <a href="<?= site_url('admin/materials/publish/' . $child['id']) ?>"
                       class="btn btn-sm btn-outline-success py-0 px-2"
                       title="Kelola Publikasi ke Kelas" style="font-size:0.72rem;">
                      <i class="bi bi-send me-1"></i>Publish
                    </a>
                  <?php endif; ?>
                  <a href="<?= site_url('admin/materials/edit/' . $child['id']) ?>"
                     class="btn btn-sm btn-outline-warning py-0 px-2" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete"
                          data-id="<?= $child['id'] ?>" data-title="<?= esc($child['title']) ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<div class="mt-2">
  <a href="<?= site_url('admin/materials') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Mapel
  </a>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Hapus</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        Hapus <strong id="delTitle"></strong>?
        <div id="delWarn" class="text-danger mt-1 d-none small">Semua Sub Materi di dalamnya ikut terhapus.</div>
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
  var delId = null;
  var modal = new bootstrap.Modal(document.getElementById('deleteModal'));

  document.querySelectorAll('.btn-delete').forEach(function(btn){
    btn.addEventListener('click', function(){
      delId = this.dataset.id;
      document.getElementById('delTitle').textContent = this.dataset.title;
      var isParent = !!this.closest('.card-header');
      document.getElementById('delWarn').classList.toggle('d-none', !isParent);
      modal.show();
    });
  });

  document.getElementById('confirmDel').addEventListener('click', function(){
    var btn = this; btn.disabled = true;
    fetch('<?= site_url('admin/materials/delete/') ?>' + delId, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body: '<?= $csrfName ?>=<?= $csrfHash ?>'
    }).then(r => r.json()).then(function(d){
      btn.disabled = false; modal.hide();
      if (d.status === 'success') {
        var p = document.getElementById('parent-' + delId);
        var c = document.getElementById('child-' + delId);
        if (p) { p.style.opacity='0'; setTimeout(()=>p.remove(),400); }
        if (c) { c.style.opacity='0'; setTimeout(()=>c.remove(),400); }
      } else { alert(d.message); }
    });
  });

  // ── Sinkron dari ATP ──────────────────────────────────────────────
  function doSyncAtp(btn) {
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyinkronkan...';

    fetch('<?= site_url('admin/materials/sync-atp/' . $subject['id']) ?>', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body: '<?= $csrfName ?>=<?= $csrfHash ?>'
    }).then(r => r.json()).then(function(d){
      if (d.success) {
        // Reload agar materi baru muncul
        location.reload();
      } else {
        alert(d.message || 'Gagal sinkron.');
        btn.disabled = false;
        btn.innerHTML = original;
      }
    }).catch(function(){
      alert('Terjadi kesalahan koneksi.');
      btn.disabled = false;
      btn.innerHTML = original;
    });
  }

  var btnSync = document.getElementById('btnSyncAtp');
  if (btnSync) btnSync.addEventListener('click', function(){ doSyncAtp(this); });

  var btnSyncEmpty = document.getElementById('btnSyncAtpEmpty');
  if (btnSyncEmpty) btnSyncEmpty.addEventListener('click', function(){ doSyncAtp(this); });
})();
</script>
<?= $this->endSection() ?>
