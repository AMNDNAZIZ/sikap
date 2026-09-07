<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-send me-2 text-success"></i>Publikasi Sub Materi
    </h5>
    <div class="text-muted small">
      <?= esc($subject['name'] ?? '') ?>
      &nbsp;&middot;&nbsp;<?= esc($parent['title'] ?? '') ?>
      &nbsp;&middot;&nbsp;<strong><?= esc($subMat['title']) ?></strong>
    </div>
  </div>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="row g-4">

  <!-- Panel kiri: info sub materi -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-journals me-2 text-primary"></i>Info Sub Materi</h6>
        <dl class="row small mb-0">
          <dt class="col-5 text-muted">Judul</dt>
          <dd class="col-7"><?= esc($subMat['title']) ?></dd>
          <dt class="col-5 text-muted">Mapel</dt>
          <dd class="col-7"><?= esc($subject['name'] ?? '') ?></dd>
          <dt class="col-5 text-muted">Level Kelas</dt>
          <dd class="col-7"><?= \App\Models\SubjectMaterialModel::getLevelLabel((int)$subMat['level']) ?></dd>
          <dt class="col-5 text-muted">Semester</dt>
          <dd class="col-7"><?= $subMat['semester'] == 1 ? 'Ganjil' : 'Genap' ?></dd>
          <dt class="col-5 text-muted">Tipe</dt>
          <dd class="col-7"><?= \App\Models\SubjectMaterialModel::getContentTypeLabel($subMat['content_type'] ?? 'text') ?></dd>
        </dl>
      </div>
    </div>

    <div class="card border-0 shadow-sm mt-3 bg-info-subtle">
      <div class="card-body py-3 small">
        <i class="bi bi-info-circle me-2 text-info"></i>
        <strong>Cara Kerja Publikasi:</strong>
        <ul class="mb-0 mt-2 ps-3">
          <li>Centang kelas yang akan mendapat akses sub materi ini</li>
          <li><strong>Jadwal tampil (opsional):</strong> isi tanggal &amp; waktu agar materi baru muncul ke siswa pada waktu tersebut. Kosongkan untuk tampil segera.</li>
          <li>Materi terjadwal tetap bisa dikaitkan dengan kuis meski belum tampil ke siswa</li>
          <li>Thread diskusi otomatis dibuat (shared untuk semua kelas)</li>
          <li>Notifikasi dikirim hanya saat materi benar-benar tampil (bukan yang terjadwal ke depan)</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Panel kanan: checklist kelas -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
          <i class="bi bi-people me-2 text-success"></i>Pilih Kelas Tujuan
        </span>
        <div class="d-flex gap-2">
          <button type="button" id="btnCheckAll"   class="btn btn-xs btn-outline-primary"   style="font-size:0.72rem;">Pilih Semua</button>
          <button type="button" id="btnUncheckAll" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem;">Hapus Semua</button>
        </div>
      </div>
      <div class="card-body">

        <?php if (empty($classes)): ?>
          <div class="text-muted small text-center py-3">
            Tidak ada kelas dengan level yang sesuai untuk mapel ini.
          </div>
        <?php else: ?>
          <div id="classList" class="row g-3">
            <?php foreach ($publishStatus as $cid => $info):
              // Status jadwal: terjadwal ke depan bila scheduled_at > sekarang
              $schedRaw    = $info['scheduled_at'] ?? null;
              $schedTs     = $schedRaw ? strtotime($schedRaw) : false;
              $isScheduled = $info['is_active'] && $schedTs !== false && $schedTs > time();
              // Value untuk input datetime-local (format Y-m-d\TH:i)
              $schedInput  = $schedTs !== false ? date('Y-m-d\TH:i', $schedTs) : '';
            ?>
              <div class="col-sm-6">
                <div class="card border h-100 <?= $info['is_active'] ? ($isScheduled ? 'border-warning bg-warning-subtle' : 'border-success bg-success-subtle') : '' ?>">
                  <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                      <div class="form-check mb-0">
                        <input class="form-check-input class-check" type="checkbox"
                               value="<?= $cid ?>" id="cls_<?= $cid ?>"
                               <?= $info['is_active'] ? 'checked' : '' ?>>
                      </div>
                      <div class="flex-grow-1">
                        <label class="form-check-label fw-semibold small d-block" for="cls_<?= $cid ?>">
                          <?= esc($info['class_name']) ?>
                        </label>
                        <?php if ($isScheduled): ?>
                          <span class="text-warning-emphasis" style="font-size:0.68rem;">
                            <i class="bi bi-clock-history me-1"></i>
                            Terjadwal <?= date('d M Y H:i', $schedTs) ?>
                          </span>
                        <?php elseif ($info['is_active'] && $info['published_at']): ?>
                          <span class="text-success" style="font-size:0.68rem;">
                            <i class="bi bi-check2-circle me-1"></i>
                            Dipublish <?= date('d M Y', strtotime($info['published_at'])) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted" style="font-size:0.68rem;">Belum dipublish</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <!-- Input jadwal publish (muncul saat kelas dicentang) -->
                    <div class="sched-wrap mt-2 <?= $info['is_active'] ? '' : 'd-none' ?>">
                      <label class="text-muted d-block" style="font-size:0.66rem;">
                        <i class="bi bi-calendar-event me-1"></i>Jadwal tampil (opsional)
                      </label>
                      <input type="datetime-local"
                             class="form-control form-control-sm sched-input"
                             data-cid="<?= $cid ?>"
                             value="<?= $schedInput ?>"
                             style="font-size:0.75rem;">
                      <small class="text-muted" style="font-size:0.62rem;">Kosongkan = tampil segera</small>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-4 d-flex gap-2 justify-content-end">
            <button type="button" id="btnSavePublish" class="btn btn-success">
              <i class="bi bi-save me-1"></i>Simpan Publikasi
            </button>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
  var CSRF  = '<?= csrf_token() ?>';
  var HASH  = '<?= csrf_hash() ?>';
  var URL   = '<?= site_url('admin/materials/publish/' . $subMat['id']) ?>';
  var BACK  = '<?= site_url('admin/materials/' . $subject['id']) ?>';

  // Pilih / hapus semua
  document.getElementById('btnCheckAll').addEventListener('click', function(){
    document.querySelectorAll('.class-check').forEach(c => c.checked = true);
    updateCardStyles();
  });
  document.getElementById('btnUncheckAll').addEventListener('click', function(){
    document.querySelectorAll('.class-check').forEach(c => c.checked = false);
    updateCardStyles();
  });
  document.querySelectorAll('.class-check').forEach(c => {
    c.addEventListener('change', updateCardStyles);
  });

  function updateCardStyles() {
    document.querySelectorAll('.class-check').forEach(function(chk){
      var card    = chk.closest('.card');
      var wrap     = card.querySelector('.sched-wrap');
      var schedInp = card.querySelector('.sched-input');
      var hasSched = schedInp && schedInp.value && (new Date(schedInp.value).getTime() > Date.now());

      // Tampilkan input jadwal hanya bila kelas dicentang
      if (wrap) wrap.classList.toggle('d-none', !chk.checked);

      // Reset warna
      card.classList.remove('border-success', 'bg-success-subtle', 'border-warning', 'bg-warning-subtle');
      if (chk.checked) {
        if (hasSched) {
          card.classList.add('border-warning', 'bg-warning-subtle');
        } else {
          card.classList.add('border-success', 'bg-success-subtle');
        }
      }
    });
  }

  // Perbarui warna kartu saat jadwal diubah
  document.querySelectorAll('.sched-input').forEach(function(inp){
    inp.addEventListener('change', updateCardStyles);
  });

  // Simpan
  document.getElementById('btnSavePublish')?.addEventListener('click', function(){
    var btn = this;
    var classIds = [...document.querySelectorAll('.class-check:checked')].map(c => c.value);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    var body = new FormData();
    body.append(CSRF, HASH);
    classIds.forEach(function(id){
      body.append('class_ids[]', id);
      // Sertakan jadwal per kelas (jika diisi)
      var inp = document.querySelector('.sched-input[data-cid="' + id + '"]');
      if (inp && inp.value) {
        body.append('schedules[' + id + ']', inp.value);
      }
    });

    fetch(URL, { method:'POST', body })
      .then(r => r.json())
      .then(function(d){
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i>Simpan Publikasi';
        if (d.success) {
          Swal.fire({ icon:'success', title:'Tersimpan!', text:d.message, timer:2000, showConfirmButton:false })
            .then(() => window.location.href = BACK);
        } else {
          alert(d.message || 'Gagal menyimpan.');
        }
      })
      .catch(function(){ btn.disabled = false; alert('Terjadi kesalahan.'); });
  });
})();
</script>
<?= $this->endSection() ?>
