<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-box-arrow-in-down me-2 text-info"></i>Gunakan Materi
    </h5>
    <div class="text-muted small"><?= esc($subject['name'] ?? '') ?> &mdash; <?= esc($activeYear['year'] ?? '') ?></div>
  </div>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="alert alert-info py-2 small">
  <i class="bi bi-info-circle me-2"></i>
  Daftar sub materi di bawah dibuat oleh guru lain untuk level dan mata pelajaran yang sama.
  Anda bisa langsung <strong>menggunakannya</strong> — tidak ada duplikasi data, sub materi akan dipublish ke kelas Anda.
</div>

<?php if (empty($available)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-40"></i>
    <p class="mb-0">Belum ada sub materi dari guru lain yang tersedia untuk level Anda.</p>
  </div>
<?php else: ?>

  <?php foreach ($available as $level => $items): ?>
    <h6 class="fw-bold mb-3">
      <span class="badge bg-info text-dark me-2">Kelas <?= $level ?></span>
      <?= count($items) ?> Sub Materi tersedia
    </h6>

    <div class="row g-3 mb-4">
      <?php foreach ($items as $item): ?>
        <?php
          $icon = \App\Models\SubjectMaterialModel::getContentTypeIcon($item['content_type'] ?? 'text');
          $typeLabel = \App\Models\SubjectMaterialModel::getContentTypeLabel($item['content_type'] ?? 'text');
          $classes = $classesByLevel[$level] ?? [];
        ?>
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h6 class="fw-semibold mb-1" style="font-size:0.875rem;"><?= esc($item['title']) ?></h6>
                  <div class="text-muted small"><?= esc($item['parent_title'] ?? '—') ?></div>
                </div>
                <span class="badge bg-secondary" style="font-size:0.62rem;">
                  Sem <?= $item['semester'] == 1 ? 'Ganjil' : 'Genap' ?>
                </span>
              </div>

              <?php if (!empty($item['description'])): ?>
                <p class="text-muted small mb-2" style="font-size:0.78rem;">
                  <?= esc(mb_substr($item['description'], 0, 100)) ?><?= mb_strlen($item['description']) > 100 ? '...' : '' ?>
                </p>
              <?php endif; ?>

              <div class="d-flex gap-2 mb-3" style="font-size:0.72rem;color:#6c757d;">
                <span><i class="<?= $icon ?> me-1"></i><?= $typeLabel ?></span>
                <?php if ($item['estimated_minutes']): ?>
                  <span><i class="bi bi-clock me-1"></i><?= $item['estimated_minutes'] ?> mnt</span>
                <?php endif; ?>
                <span class="text-muted"><i class="bi bi-person me-1"></i><?= esc($item['creator_name'] ?? '—') ?></span>
              </div>

              <!-- Pilih kelas tujuan -->
              <?php if (!empty($classes)): ?>
                <div class="border rounded p-2 mb-2 bg-light" style="font-size:0.78rem;">
                  <div class="fw-semibold mb-1">Publish ke kelas:</div>
                  <?php foreach ($classes as $cls): ?>
                    <div class="form-check mb-0">
                      <input class="form-check-input use-class-check"
                             type="checkbox"
                             value="<?= $cls['id'] ?>"
                             id="use_<?= $item['id'] ?>_<?= $cls['id'] ?>"
                             data-mat="<?= $item['id'] ?>">
                      <label class="form-check-label" for="use_<?= $item['id'] ?>_<?= $cls['id'] ?>">
                        <?= esc($cls['name']) ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="text-muted small">Tidak ada kelas level ini yang Anda ampu.</div>
              <?php endif; ?>

            </div>
            <div class="card-footer bg-transparent py-2">
              <button class="btn btn-info btn-sm text-white w-100 btn-use"
                      data-mat-id="<?= $item['id'] ?>"
                      data-mat-title="<?= esc($item['title']) ?>"
                      <?= empty($classes) ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-down me-1"></i>Gunakan Sub Materi Ini
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
  var CSRF  = '<?= csrf_token() ?>';
  var HASH  = '<?= csrf_hash() ?>';
  var URL   = '<?= site_url('admin/materials/use') ?>';
  var BACK  = '<?= site_url('admin/materials/' . $subject['id']) ?>';

  document.querySelectorAll('.btn-use').forEach(function(btn){
    btn.addEventListener('click', function(){
      var matId    = this.dataset.matId;
      var matTitle = this.dataset.matTitle;

      // Kumpulkan kelas yang dicentang untuk sub materi ini
      var classIds = [...document.querySelectorAll('.use-class-check[data-mat="' + matId + '"]:checked')]
                       .map(c => c.value);

      if (classIds.length === 0) {
        alert('Pilih minimal satu kelas terlebih dahulu.');
        return;
      }

      if (!confirm('Gunakan sub materi "' + matTitle + '" untuk ' + classIds.length + ' kelas yang dipilih?')) {
        return;
      }

      var self = this;
      self.disabled = true;
      self.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

      var body = new FormData();
      body.append(CSRF, HASH);
      body.append('sub_mat_id', matId);
      classIds.forEach(id => body.append('class_ids[]', id));

      fetch(URL, { method:'POST', body })
        .then(r => r.json())
        .then(function(d){
          self.disabled = false;
          if (d.success) {
            self.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Berhasil Digunakan';
            self.classList.replace('btn-info', 'btn-success');
            self.disabled = true;
          } else {
            self.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i>Gunakan Sub Materi Ini';
            alert(d.message || 'Gagal.');
          }
        })
        .catch(function(){
          self.disabled = false;
          self.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i>Gunakan Sub Materi Ini';
          alert('Terjadi kesalahan.');
        });
    });
  });
})();
</script>
<?= $this->endSection() ?>
