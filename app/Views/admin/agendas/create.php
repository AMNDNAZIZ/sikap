<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
    <h5>Buat Agenda Baru</h5>
  </div>
  <div class="card-body">
    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach (session()->getFlashdata('errors') as $e): ?>
            <li><?= esc($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/agendas/store') ?>" method="post">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="title" class="form-label">Judul</label>
        <input type="text" name="title" id="title" class="form-control" value="<?= old('title') ?>" required>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="date" class="form-label">Tanggal</label>
          <input type="date" name="date" id="date" class="form-control"
            value="<?= old('date', $prefillDate ?? date('Y-m-d')) ?>" <?= !empty($prefillDate) ? 'readonly' : '' ?>
            required>
          <?php if (!empty($prefillDate)): ?>
            <small class="text-info">Tanggal dikunci dari pilihan kalender.</small>
          <?php endif; ?>
        </div>
        <div class="col-md-4 mb-3">
          <label for="start_time" class="form-label">Jam Mulai</label>
          <input type="time" name="start_time" id="start_time" class="form-control" value="<?= old('start_time') ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label for="end_time" class="form-label">Jam Selesai</label>
          <input type="time" name="end_time" id="end_time" class="form-control" value="<?= old('end_time') ?>">
        </div>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Deskripsi</label>
        <textarea name="description" id="description"
          class="form-control summernote"><?= old('description') ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="class_id" class="form-label">Kelas</label>
          <select name="class_id[]" id="class_id" class="form-select select2" multiple>
            <?php if (session()->get('user')['role_id'] == 1): ?>
              <option value="">Umum / Semua Kelas</option>
            <?php endif; ?>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (isset($agenda) && $agenda['class_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Bisa pilih lebih dari satu kelas. Kosongkan jika untuk umum.</small>
        </div>
        <div class="col-md-6 mb-3">
          <label for="is_public" class="form-label">Publik?</label>
          <select name="is_public" id="is_public" class="form-select">
            <option value="1" <?= old('is_public') == '1' ? 'selected' : '' ?>>Ya</option>
            <option value="0" <?= old('is_public') == '0' ? 'selected' : '' ?>>Tidak</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="<?= site_url('admin/agendas') ?>" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Summernote CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
  $(function () {
    $('.summernote').summernote({
      height: 200,
      placeholder: 'Tulis detail agenda di sini...',
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['codeview']]
      ]
    });

    $('.select2').select2({
      placeholder: "Pilih kelas",
      allowClear: true,
      width: '100%'
    });
  });
</script>
<?= $this->endSection() ?>