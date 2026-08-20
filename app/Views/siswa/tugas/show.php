<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0 pb-5">
  <div class="d-flex align-items-center gap-2 mt-3 mb-3">
    <a href="<?= site_url('siswa/tugas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0 flex-grow-1"><?= esc($tugas['judul']) ?></h4>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 shadow-sm"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>

  <?php
    $nilaiInfo = [
      'sangat_bagus' => ['⭐ Sangat Bagus', 'success'],
      'bagus'        => ['👍 Bagus',        'primary'],
      'kurang'       => ['⚠️ Kurang',       'warning'],
      'belajar_lagi' => ['🔄 Belajar Lagi', 'danger'],
    ];
    $sudahDikumpul  = !empty($submission['dikumpul_at']);
    $sudahDinilai   = !empty($submission['nilai']);
    $bisaKerjakan   = ($statusWaktu === 'aktif') && !$sudahDinilai;
    $bisaEdit       = $bisaKerjakan && $sudahDikumpul;
  ?>

  <!-- ── Info Tugas ── -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
          <div class="text-muted small">Mata Pelajaran</div>
          <div class="fw-semibold"><?= esc($subject['name'] ?? '-') ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small">Mulai</div>
          <div class="fw-semibold small"><?= date('d/m/Y H:i', strtotime($tugas['mulai_at'])) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small">Batas Waktu</div>
          <div class="fw-semibold small"><?= date('d/m/Y H:i', strtotime($tugas['selesai_at'])) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-muted small">Status</div>
          <?php if ($statusWaktu === 'belum'): ?>
            <span class="badge bg-secondary">Belum Dimulai</span>
          <?php elseif ($statusWaktu === 'terlewat'): ?>
            <span class="badge bg-danger">⏰ Terlewat</span>
          <?php else: ?>
            <span class="badge bg-success">Aktif</span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($tugas['deskripsi'])): ?>
        <hr class="my-2">
        <div class="text-muted small fw-semibold mb-1">Deskripsi Tugas:</div>
        <div class="border rounded p-3 bg-light tugas-deskripsi" style="overflow:auto;">
          <?= $tugas['deskripsi'] ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Status Pengumpulan & Nilai ── -->
  <?php if ($sudahDikumpul): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <div class="text-muted small">Dikumpulkan pada</div>
          <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($submission['dikumpul_at'])) ?></div>
        </div>
        <div>
          <?php if ($sudahDinilai): ?>
            <div class="text-muted small mb-1">Nilai dari Guru</div>
            <span class="badge bg-<?= $nilaiInfo[$submission['nilai']][1] ?> fs-6">
              <?= $nilaiInfo[$submission['nilai']][0] ?>
            </span>
          <?php else: ?>
            <span class="badge bg-light text-muted border">⏳ Menunggu penilaian guru</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($sudahDinilai && !empty($submission['catatan_guru'])): ?>
        <div class="mt-3 p-3 bg-light border rounded">
          <div class="small fw-semibold text-muted mb-1">💬 Catatan dari Guru:</div>
          <div><?= esc($submission['catatan_guru']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Jawaban yang sudah dikumpul (bisa diedit jika belum dinilai) ── -->
  <?php if ($sudahDikumpul && $bisaEdit): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 fw-semibold">
      Jawabanmu
      <span class="badge bg-warning text-dark ms-1">Masih bisa diedit</span>
    </div>
    <div class="card-body">
      <div class="border rounded p-3 bg-light mb-3" style="min-height:80px;">
        <?= $submission['jawaban'] ?>
      </div>
    </div>
  </div>
  <?php elseif ($sudahDikumpul && $sudahDinilai): ?>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 fw-semibold">Jawabanmu</div>
    <div class="card-body">
      <div class="border rounded p-3 bg-light" style="min-height:80px;">
        <?= $submission['jawaban'] ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── FORM PENGERJAAN ── -->
  <?php if ($statusWaktu === 'terlewat' && !$sudahDikumpul): ?>
    <div class="alert alert-danger border-0 shadow-sm">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <strong>Terlewat.</strong> Batas waktu pengumpulan telah berakhir pada
      <?= date('d/m/Y H:i', strtotime($tugas['selesai_at'])) ?>.
    </div>

  <?php elseif ($statusWaktu === 'belum'): ?>
    <div class="alert alert-secondary border-0 shadow-sm">
      <i class="bi bi-clock me-2"></i>
      Tugas ini baru bisa dikerjakan mulai
      <strong><?= date('d/m/Y H:i', strtotime($tugas['mulai_at'])) ?></strong>.
    </div>

  <?php elseif ($bisaKerjakan): ?>
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 fw-semibold">
      <?= $bisaEdit ? '✏️ Edit Jawaban' : '✏️ Kerjakan Tugas' ?>
      <small class="text-muted fw-normal ms-1">— bisa memuat teks dan gambar</small>
    </div>
    <div class="card-body">
      <form action="<?= site_url('siswa/tugas/' . $tugas['id'] . '/submit') ?>" method="post">
        <?= csrf_field() ?>
        <textarea id="jawaban" name="jawaban"><?= $bisaEdit ? ($submission['jawaban'] ?? '') : '' ?></textarea>
        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-send me-1"></i>Kumpulkan Tugas
          </button>
          <a href="<?= site_url('siswa/tugas') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- CKEditor dimuat di scripts section agar jQuery sudah tersedia -->
<?php if ($bisaKerjakan): ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/super-build/ckeditor.js"></script>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($bisaKerjakan): ?>
<script>
$(function () {
  CKEDITOR.ClassicEditor.create(document.querySelector('#jawaban'), {
    toolbar: {
      items: [
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', 'underline', '|',
        'link', 'uploadImage', 'blockQuote', '|',
        'bulletedList', 'numberedList', '|',
        'alignment', '|',
        'removeFormat'
      ],
      shouldNotGroupWhenFull: true
    },
    image: {
      toolbar: ['imageTextAlternative', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side']
    },
    removePlugins: [
      'AIAssistant', 'AIAssistantUI', 'AIAdapter', 'CKBox', 'CKBoxImageEdit', 'CKBoxImageEditEditing',
      'CKBoxUtils', 'CloudServices', 'CloudServicesUploadAdapter', 'EasyImage', 'Comments', 'CommentsRepository',
      'RealTimeCollaborativeComments', 'TrackChanges', 'TrackChangesEditing', 'TrackChangesData',
      'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'RealTimeCollaborativeRevisionHistory',
      'PresenceList', 'RealTimeCollaboration', 'Pagination', 'WProofreader', 'MathType', 'ChemType',
      'Mentions', 'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents',
      'PasteFromOfficeEnhanced', 'CaseChange', 'WideSidebar', 'ExportPdf', 'ExportWord'
    ],
  })
  .then(editor => {
    editor.editing.view.change(writer => {
      writer.setStyle('min-height', '200px', editor.editing.view.document.getRoot());
    });

    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => ({
      upload: () => loader.file.then(file => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        $.ajax({
          url: '<?= site_url('siswa/tugas/upload-image') ?>',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: (r) => r.success ? resolve({ default: r.url }) : reject(r.message),
          error: (xhr) => reject('Upload gagal: ' + xhr.statusText)
        });
      }))
    });
  })
  .catch(err => console.error('CKEditor error:', err));
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
