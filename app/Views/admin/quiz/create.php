<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php $isEdit = !empty($quiz); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2 text-primary"></i>
    <?= $title ?>
  </h5>
  <a href="<?= site_url('admin/quiz') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if (session()->getFlashdata('errors') || session()->getFlashdata('error')): ?>
  <div class="alert alert-danger py-2 small">
    <?= session()->getFlashdata('error') ?>
    <?php foreach ((array)(session()->getFlashdata('errors') ?? []) as $e): ?>
      <div><?= esc($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post"
          action="<?= $isEdit ? site_url('admin/quiz/update/' . $quiz['id']) : site_url('admin/quiz/store') ?>">
      <?= csrf_field() ?>

      <div class="row g-3">

        <!-- Judul -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Judul Kuis <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control form-control-sm"
                 value="<?= esc($quiz['title'] ?? old('title')) ?>" required>
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"
                    placeholder="Petunjuk pengerjaan, topik, dll."><?= esc($quiz['description'] ?? old('description')) ?></textarea>
        </div>

        <!-- Bank Soal -->
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Bank Soal <span class="text-danger">*</span></label>
          <select name="bank_id" id="bankId" class="form-select form-select-sm select2" required>
            <option value="">-- Pilih Bank Soal --</option>
            <?php foreach ($banks as $b): ?>
              <option value="<?= $b['id'] ?>"
                      data-pg="<?= $b['total_pg'] ?>"
                      data-pgk="<?= $b['total_pg_kompleks'] ?>"
                      data-bs="<?= $b['total_bs'] ?>"
                      data-esai="<?= $b['total_esai'] ?>"
                      <?= ($quiz['bank_id'] ?? old('bank_id')) == $b['id'] ? 'selected' : '' ?>>
                <?= esc($b['subject_name'] . ' — ' . $b['code']) ?>
                (PG:<?= $b['total_pg'] ?> PGK:<?= $b['total_pg_kompleks'] ?> BS:<?= $b['total_bs'] ?> Esai:<?= $b['total_esai'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sub Materi Terkait -->
        <div class="col-12">
          <label class="form-label small fw-semibold">
            Kaitkan ke Sub Materi
            <span class="text-muted fw-normal">(opsional)</span>
          </label>
          <select name="material_id" id="materialId" class="form-select form-select-sm select2">
            <option value="">— Tidak dikaitkan ke sub materi —</option>
            <?php
              // Kelompokkan per mapel → materi induk
              $grouped = [];
              foreach ($subMats as $sm) {
                  $grouped[$sm['subject_name']][$sm['parent_title']][] = $sm;
              }
              $selectedMat = (int)($quiz['material_id'] ?? old('material_id', 0));
            ?>
            <?php foreach ($grouped as $subjectName => $parents): ?>
              <optgroup label="📚 <?= esc($subjectName) ?>">
                <?php foreach ($parents as $parentTitle => $subs): ?>
                  <?php foreach ($subs as $sm): ?>
                    <option value="<?= $sm['id'] ?>"
                            <?= $selectedMat === (int)$sm['id'] ? 'selected' : '' ?>>
                      <?= esc($parentTitle) ?> › <?= esc($sm['sub_title']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            Jika dikaitkan, kuis hanya muncul di halaman sub materi tersebut dan bisa dikerjakan
            setelah siswa menandai sub materi selesai.
          </div>
        </div>

        <!-- Kelas Target -->
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Kelas Target</label>
          <select name="class_ids[]" class="form-select form-select-sm select2" multiple>
            <?php
              $selectedClasses = $quiz['class_ids_arr'] ?? (old('class_ids') ? array_map('intval', (array) old('class_ids')) : []);
            ?>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>"
                      <?= in_array((int)$c['id'], $selectedClasses) ? 'selected' : '' ?>>
                <?= esc($c['name']) ?> (Level <?= $c['level'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Biarkan kosong = semua kelas dapat mengerjakan.</div>
        </div>

        <!-- Jumlah Soal per Tipe -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Jumlah Soal yang Ditampilkan</label>
          <div class="row g-2">
            <?php
              $typeLabels = ['pg' => 'Pilihan Ganda', 'pgk' => 'PG Kompleks', 'bs' => 'Benar/Salah', 'esai' => 'Esai'];
              foreach ($typeLabels as $key => $label):
                $fieldName = 'show_' . $key . '_count';
                $max       = ['pg' => 'total_pg', 'pgk' => 'total_pg_kompleks', 'bs' => 'total_bs', 'esai' => 'total_esai'][$key];
            ?>
              <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:0.75rem;"><?= $label ?></label>
                <input type="number" name="<?= $fieldName ?>" id="<?= $fieldName ?>"
                       class="form-control form-control-sm"
                       min="0" value="<?= esc($quiz[$fieldName] ?? old($fieldName, 0)) ?>"
                       data-type="<?= $key ?>">
                <div class="form-text" id="max_<?= $key ?>">Tersedia: —</div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="form-text">Isi 0 = tampilkan semua soal tipe tersebut dari bank.</div>
        </div>

        <!-- Bobot Penilaian -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Bobot Penilaian (%)</label>
          <div class="row g-2" id="bobotRow">
            <?php foreach (['pg' => 'PG', 'pgk' => 'PGK', 'bs' => 'BS', 'esai' => 'Esai'] as $k => $l): ?>
              <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:0.75rem;"><?= $l ?></label>
                <input type="number" name="bobot_<?= $k ?>" class="form-control form-control-sm bobot-input"
                       min="0" max="100"
                       value="<?= esc($quiz['bobot_' . $k] ?? old('bobot_' . $k, $k === 'pg' ? 100 : 0)) ?>">
              </div>
            <?php endforeach; ?>
          </div>
          <div id="bobotWarning" class="text-danger small mt-1" style="display:none;">
            Total bobot harus = 100%.
          </div>
          <div class="form-text">Total bobot harus 100%. Jika semua 0, nilai dihitung dari jumlah benar saja.</div>
        </div>

        <!-- Opsi -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Durasi (menit)</label>
          <input type="number" name="duration" class="form-control form-control-sm"
                 min="0" value="<?= esc($quiz['duration'] ?? old('duration', 0)) ?>">
          <div class="form-text">0 = tidak terbatas</div>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold">Max Pengulangan</label>
          <input type="number" name="max_attempts" class="form-control form-control-sm"
                 min="0" value="<?= esc($quiz['max_attempts'] ?? old('max_attempts', 0)) ?>">
          <div class="form-text">0 = tidak terbatas</div>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold">Acak Soal</label>
          <select name="shuffle_question" class="form-select form-select-sm">
            <option value="ya"    <?= ($quiz['shuffle_question'] ?? 'ya') === 'ya'    ? 'selected' : '' ?>>Ya</option>
            <option value="tidak" <?= ($quiz['shuffle_question'] ?? 'ya') === 'tidak' ? 'selected' : '' ?>>Tidak</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold">Acak Opsi Jawaban</label>
          <select name="shuffle_option" class="form-select form-select-sm">
            <option value="ya"    <?= ($quiz['shuffle_option'] ?? 'ya') === 'ya'    ? 'selected' : '' ?>>Ya</option>
            <option value="tidak" <?= ($quiz['shuffle_option'] ?? 'ya') === 'tidak' ? 'selected' : '' ?>>Tidak</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold">Tampilkan Pembahasan Setelah Submit</label>
          <select name="show_answer" class="form-select form-select-sm">
            <option value="ya"    <?= ($quiz['show_answer'] ?? 'ya') === 'ya'    ? 'selected' : '' ?>>Ya — siswa lihat mana yang benar/salah</option>
            <option value="tidak" <?= ($quiz['show_answer'] ?? 'ya') === 'tidak' ? 'selected' : '' ?>>Tidak — hanya tampilkan skor total</option>
          </select>
        </div>

        <!-- Publish -->
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished"
                   <?= ($quiz['is_published'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label small fw-semibold" for="isPublished">
              Publish — siswa dapat mengerjakan kuis ini
              <?php if (!($quiz['is_published'] ?? 0)): ?>
                <span class="text-info">(aktifkan untuk kirim notifikasi ke siswa)</span>
              <?php endif; ?>
            </label>
          </div>
        </div>

      </div><!-- /.row -->

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm">
          <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Kuis' ?>
        </button>
        <a href="<?= site_url('admin/quiz') ?>" class="btn btn-secondary btn-sm">Batal</a>
      </div>

    </form>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
  var bankSel    = document.getElementById('bankId');
  var bankInfoUrl = "<?= site_url('admin/quiz/bank-info/') ?>";
  var csrfName   = "<?= csrf_token() ?>";
  var csrfHash   = "<?= csrf_hash() ?>";

  // ── Terapkan angka ke label + set max input ────────────────────────
  function applyInfo(info) {
    ['pg','pgk','bs','esai'].forEach(function(k) {
      var count = parseInt(info[k]) || 0;
      var label = document.getElementById('max_' + k);
      var inp   = document.getElementById('show_' + k + '_count');
      if (label) label.textContent = 'Tersedia: ' + count;
      if (inp)   inp.setAttribute('max', count);
    });
  }

  // ── Ambil data dari server (live count), fallback ke data-* cache ──
  function updateBankInfo() {
    var val = bankSel.value;

    if (!val) {
      ['pg','pgk','bs','esai'].forEach(function(k) {
        var el = document.getElementById('max_' + k);
        if (el) el.textContent = 'Tersedia: —';
      });
      return;
    }

    // Langsung tampilkan dari data-* dulu (instan, tidak perlu tunggu fetch)
    var opt = bankSel.querySelector('option[value="' + val + '"]');
    if (opt) {
      applyInfo({
        pg:   opt.getAttribute('data-pg'),
        pgk:  opt.getAttribute('data-pgk'),
        bs:   opt.getAttribute('data-bs'),
        esai: opt.getAttribute('data-esai')
      });
    }

    // Kemudian verifikasi dengan live count dari server
    fetch(bankInfoUrl + val, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        applyInfo(d);
        // Update data-* supaya sinkron untuk selanjutnya
        if (opt) {
          opt.setAttribute('data-pg',   d.pg);
          opt.setAttribute('data-pgk',  d.pgk);
          opt.setAttribute('data-bs',   d.bs);
          opt.setAttribute('data-esai', d.esai);
        }
      }
    })
    .catch(function() { /* tetap pakai data-* yang sudah ditampilkan */ });
  }

  // Trigger saat native change
  bankSel.addEventListener('change', updateBankInfo);

  // Kompatibel dengan select2
  if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
    $(bankSel).on('select2:select select2:unselect', updateBankInfo);
    $(document).ready(function() { setTimeout(updateBankInfo, 100); });
  }

  // Panggil saat load (kasus edit — bank sudah ter-select)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateBankInfo);
  } else {
    updateBankInfo();
  }

  // ── Validasi total bobot ───────────────────────────────────────────
  document.querySelectorAll('.bobot-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
      var total = 0;
      document.querySelectorAll('.bobot-input').forEach(function(i) {
        total += parseInt(i.value) || 0;
      });
      var warn = document.getElementById('bobotWarning');
      if (warn) warn.style.display = (total > 0 && total !== 100) ? 'block' : 'none';
    });
  });
})();
</script>
<?= $this->endSection() ?>
