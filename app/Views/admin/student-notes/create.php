<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<style>
  .behavior-section {
    margin-bottom: 1.5rem;
  }

  .behavior-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: .5rem 1rem;
  }

  .behavior-grid .form-check {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: .5rem .75rem;
    border-radius: .4rem;
    transition: background-color .2s;
  }

  /* Warna pembeda */
  .behavior-positive .form-check {
    background: #e8f5e9; /* hijau muda */
  }

  .behavior-negative .form-check {
    background: #fdecea; /* merah muda */
  }

  .behavior-section h6 {
    font-weight: 600;
    margin-bottom: .5rem;
    color: #495057;
  }
</style>
<h4>➕ Tambah Catatan - <?= esc($student['name']) ?> (<?= esc($student['nis'] ?? '-') ?>)</h4>

<form method="post" action="<?= base_url('admin/student-notes/store') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="student_id" value="<?= $student['id'] ?>">

  <div class="mb-3">
  <label class="form-label">Perilaku</label>

  <!-- Perilaku Positif -->
  <div class="behavior-section behavior-positive">
    <h6>🌿 Perilaku Positif</h6>
    <div class="behavior-grid">
      <?php foreach ($behaviors as $b): ?>
        <?php if ($b['points'] > 0): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   name="behaviors[]" value="<?= $b['id'] ?>" id="b<?= $b['id'] ?>">
            <label class="form-check-label" for="b<?= $b['id'] ?>">
              <?= esc($b['name']) ?> 
              <span class="text-success">(+<?= $b['points'] ?>)</span>
            </label>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Perilaku Negatif -->
  <div class="behavior-section behavior-negative">
    <h6>⚠️ Perilaku Negatif</h6>
    <div class="behavior-grid">
      <?php foreach ($behaviors as $b): ?>
        <?php if ($b['points'] < 0): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   name="behaviors[]" value="<?= $b['id'] ?>" id="b<?= $b['id'] ?>">
            <label class="form-check-label" for="b<?= $b['id'] ?>">
              <?= esc($b['name']) ?> 
              <span class="text-danger">(<?= $b['points'] ?>)</span>
            </label>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

  <div class="mb-3">
    <label class="form-label">Catatan Tambahan (opsional)</label>
    <textarea name="note" class="form-control" rows="3"></textarea>
  </div>

  <button class="btn btn-primary">Simpan Catatan</button>
  <a href="<?= base_url('admin/student-notes/show/'.$student['id']) ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
