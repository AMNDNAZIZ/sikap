<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>✏️ Edit Catatan - <?= esc($student['name']) ?> (<?= esc($student['nis'] ?? '-') ?>)</h4>

<form method="post" action="<?= base_url('admin/student-notes/update/'.$note['id']) ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Perilaku</label><br>
    <?php foreach ($behaviors as $b): ?>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox"
               name="behaviors[]"
               value="<?= $b['id'] ?>"
               id="b<?= $b['id'] ?>"
               <?= in_array($b['id'], $selectedBehaviors) ? 'checked' : '' ?>>
        <label class="form-check-label" for="b<?= $b['id'] ?>">
          <?= esc($b['name']) ?> 
          <?php if ($b['points'] > 0): ?>
            <span class="text-success">(+<?= $b['points'] ?>)</span>
          <?php else: ?>
            <span class="text-danger">(<?= $b['points'] ?>)</span>
          <?php endif; ?>
        </label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Catatan Tambahan (opsional)</label>
    <textarea name="note" class="form-control" rows="3"><?= esc($note['note']) ?></textarea>
  </div>

  <button class="btn btn-primary">Update Catatan</button>
  <a href="<?= base_url('admin/student-notes/show/'.$student['id']) ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
