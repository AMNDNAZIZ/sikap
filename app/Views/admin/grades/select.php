<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
  <h3>🔍 Pilih Kelas & Mapel</h3>

  <form method="get" action="<?= site_url('admin/grades/view') ?>" class="row g-3">
    
    <?php if (!empty($fixedClass)): ?>
      <input type="hidden" name="class_id" value="<?= esc($fixedClass) ?>">
      <div class="col-md-6">
        <label class="form-label">Mata Pelajaran</label>
        <select name="subject_id" class="form-select" required>
          <option value="">-- pilih mapel --</option>
          <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= esc($sub['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    <?php else: ?>
      <div class="col-md-6">
        <label class="form-label">Kelas</label>
        <select name="class_id" class="form-select" required>
          <option value="">-- pilih kelas --</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Mata Pelajaran</label>
        <select name="subject_id" class="form-select" required>
          <option value="">-- pilih mapel --</option>
          <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= esc($sub['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="col-12">
      <button class="btn btn-primary">Tampilkan</button>
    </div>
  </form>
</div>

<?= $this->endSection() ?>
