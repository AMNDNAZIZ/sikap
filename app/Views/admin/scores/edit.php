<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h1 class="h3 mb-4">✏️ Edit Nilai</h1>

<form method="post" action="<?= base_url('admin/scores/update/' . $score['id']) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Siswa</label>
      <select name="student_id" class="form-select">
        <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $s['id'] == $score['student_id'] ? 'selected' : '' ?>>
            <?= esc($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Mata Pelajaran</label>
      <select name="subject_id" class="form-select">
        <?php foreach ($subjects as $sub): ?>
          <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $score['subject_id'] ? 'selected' : '' ?>>
            <?= esc($sub['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Materi</label>
      <select name="material_id" class="form-select">
        <option value="">-- Pilih Materi --</option>
        <?php foreach ($materials as $m): ?>
          <option value="<?= $m['id'] ?>" <?= $m['id'] == $score['material_id'] ? 'selected' : '' ?>>
            <?= esc($m['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Jenis</label>
      <select name="type" class="form-select">
        <option value="formatif" <?= $score['type']=='formatif'?'selected':'' ?>>Formatif</option>
        <option value="sumatif" <?= $score['type']=='sumatif'?'selected':'' ?>>Sumatif</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Sub Jenis</label>
      <select name="sub_type" class="form-select">
        <option value="tulis" <?= $score['sub_type']=='tulis'?'selected':'' ?>>Tulis</option>
        <option value="lisan" <?= $score['sub_type']=='lisan'?'selected':'' ?>>Lisan</option>
        <option value="projek" <?= $score['sub_type']=='projek'?'selected':'' ?>>Projek</option>
        <option value="observasi" <?= $score['sub_type']=='observasi'?'selected':'' ?>>Observasi</option>
        <option value="semester_ganjil" <?= $score['sub_type']=='semester_ganjil'?'selected':'' ?>>Semester Ganjil</option>
        <option value="semester_genap" <?= $score['sub_type']=='semester_genap'?'selected':'' ?>>Semester Genap</option>
        <option value="ujian_akhir" <?= $score['sub_type']=='ujian_akhir'?'selected':'' ?>>Ujian Akhir</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Nilai</label>
      <input type="number" step="0.01" name="score" class="form-control" value="<?= $score['score'] ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">💾 Update</button>
    </div>
  </div>
</form>

<?= $this->endSection() ?>
