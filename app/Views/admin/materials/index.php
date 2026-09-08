<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>

<style>
  /* Judul materi induk maksimal 2 baris */
  .text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .min-w-0 { min-width: 0; }

  /* Tombol aksi sub materi */
  .mat-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }
  .mat-actions .btn { font-size: 0.75rem; }

  /* ── Mobile (< 576px): tombol aksi jadi grid penuh, mudah disentuh ── */
  @media (max-width: 575.98px) {
    .mat-child-item { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .mat-actions {
      padding-left: 0 !important;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }
    .mat-actions .btn {
      width: 100%;
      padding-top: 0.4rem;
      padding-bottom: 0.4rem;
      font-size: 0.8rem;
    }
    .mat-child-item .ps-4 { padding-left: 0 !important; }
  }

  /* ── Desktop (>= 768px): tombol ringkas rata kiri ── */
  @media (min-width: 768px) {
    .mat-actions { justify-content: flex-start; }
  }
</style>

<!-- Header halaman -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-book me-2 text-primary"></i><?= esc($title) ?>
  </h5>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= site_url('admin/materials/use/' . $subject['id']) ?>"
       class="btn btn-sm btn-outline-info">
      <i class="bi bi-box-arrow-in-down me-1"></i><span class="d-none d-sm-inline">Gunakan </span>Materi
    </a>
    <a href="<?= site_url('admin/materials/progress/' . $subject['id']) ?>"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-bar-chart me-1"></i>Progress
    </a>
    <button type="button" id="btnSyncAtp" class="btn btn-sm btn-primary">
      <i class="bi bi-arrow-repeat me-1"></i><span class="d-none d-sm-inline">Sinkron </span>ATP
    </button>
  </div>
</div>

<div class="alert alert-info border-0 py-2 small mb-3 d-flex align-items-start gap-2">
  <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
  <div>
    <strong>Materi</strong> otomatis diambil dari <strong>ATP</strong> (Alur Tujuan Pembelajaran) yang sudah Anda buat.
    Tugas Anda adalah menambahkan <strong>Sub Materi</strong> (pertemuan) pada setiap materi, lalu mempublikasikannya ke kelas.
    Jika materi belum muncul, klik <strong>Sinkron ATP</strong>.
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
    <div class="card border-0 shadow-sm mb-3 mat-parent-card" id="parent-<?= $parent['id'] ?>">

      <!-- ── Header Materi Induk ── -->
      <div class="card-header bg-primary bg-opacity-10 border-bottom py-2 px-3">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
            <i class="bi bi-folder2-open text-primary fs-5 flex-shrink-0" style="margin-top:2px;"></i>
            <div class="min-w-0">
              <div class="fw-bold text-truncate-2" style="font-size:0.9rem;line-height:1.3;">
                <?= esc($parent['title']) ?>
              </div>
              <div class="d-flex flex-wrap gap-1 mt-1">
                <span class="badge bg-secondary" style="font-size:0.6rem;"><?= $semLabel ?></span>
                <span class="badge bg-info text-dark" style="font-size:0.6rem;"><?= $lvLabel ?></span>
                <?php if ($parent['is_published']): ?>
                  <span class="badge bg-success" style="font-size:0.6rem;">Siap</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Draft</span>
                <?php endif; ?>
                <span class="badge bg-light text-dark border" style="font-size:0.6rem;">
                  <i class="bi bi-list-ol me-1"></i><?= count($children) ?> sub materi
                </span>
              </div>
            </div>
          </div>
          <a href="<?= site_url('admin/materials/create/' . $subject['id'] . '?parent_id=' . $parent['id']) ?>"
             class="btn btn-sm btn-primary flex-shrink-0 px-2" title="Tambah Sub Materi (Pertemuan)"
             style="font-size:0.75rem;white-space:nowrap;">
            <i class="bi bi-plus-circle"></i><span class="d-none d-sm-inline ms-1">Sub Materi</span>
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
            <div class="list-group-item px-3 py-3 mat-child-item" id="child-<?= $child['id'] ?>">

              <!-- Baris atas: nomor + judul + badge -->
              <div class="d-flex align-items-start gap-2 mb-2">
                <span class="flex-shrink-0 rounded-circle bg-primary text-white d-flex align-items-center
                             justify-content-center fw-bold"
                      style="width:26px;height:26px;font-size:0.72rem;">
                  <?= $idx + 1 ?>
                </span>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-semibold" style="font-size:0.875rem;line-height:1.3;">
                    <?= esc($child['title']) ?>
                  </div>
                  <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                    <?php if ($child['is_published']): ?>
                      <span class="badge bg-success" style="font-size:0.6rem;">Siap</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Draft</span>
                    <?php endif; ?>
                    <?php if ($publishedIds > 0): ?>
                      <span class="badge bg-primary" style="font-size:0.6rem;">
                        <i class="bi bi-send me-1"></i><?= $publishedIds ?> kelas
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Baris meta: tipe, durasi, progres, diskusi -->
              <div class="d-flex flex-wrap gap-2 mb-2 ps-4" style="font-size:0.7rem;color:#6c757d;">
                <span><i class="<?= $icon ?> me-1"></i><?= $typeLabel ?></span>
                <?php if ($child['estimated_minutes']): ?>
                  <span><i class="bi bi-clock me-1"></i><?= $child['estimated_minutes'] ?> mnt</span>
                <?php endif; ?>
                <span class="<?= $child['is_published'] ? 'text-success' : '' ?>">
                  <i class="bi bi-check2-circle me-1"></i><?= $prog['completed'] ?> selesai
                </span>
                <span class="<?= $hasThread ? 'text-primary' : '' ?>">
                  <i class="bi bi-chat-dots me-1"></i><?= $hasThread ? 'Diskusi aktif' : 'Belum ada diskusi' ?>
                </span>
              </div>

              <!-- Baris aksi: tombol full-width di HP, ringkas di desktop -->
              <div class="mat-actions ps-4">
                <a href="<?= site_url('admin/materials/show/' . $child['id']) ?>"
                   class="btn btn-sm btn-outline-primary" title="Lihat Konten & Diskusi">
                  <i class="bi bi-eye"></i><span class="ms-1">Lihat</span>
                </a>
                <?php if ($child['is_published']): ?>
                  <a href="<?= site_url('admin/materials/publish/' . $child['id']) ?>"
                     class="btn btn-sm btn-outline-success" title="Kelola Publikasi ke Kelas">
                    <i class="bi bi-send"></i><span class="ms-1">Publish</span>
                  </a>
                <?php endif; ?>
                <a href="<?= site_url('admin/materials/edit/' . $child['id']) ?>"
                   class="btn btn-sm btn-outline-warning" title="Edit">
                  <i class="bi bi-pencil"></i><span class="ms-1 d-sm-none">Edit</span>
                </a>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="<?= $child['id'] ?>" data-title="<?= esc($child['title']) ?>" title="Hapus">
                  <i class="bi bi-trash"></i><span class="ms-1 d-sm-none">Hapus</span>
                </button>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<div class="mt-2 mb-4">
  <a href="<?= site_url('admin/materials') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Mapel
  </a>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
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
