<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">📊 Kelola Nilai</h3>
      <a href="<?= base_url('admin/grades/rekap') ?>" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-table"></i> Rekap Nilai Kelas
      </a>
  </div>

  <?php if ($role == 'admin'): ?>
    <!-- Admin: pilih tahun ajaran dulu -->
    <div class="mb-3">
      <label>Tahun Ajaran</label>
      <select id="year-select" class="form-select">
        <?php foreach ($academicYears as $y): ?>
          <option value="<?= $y['id'] ?>" <?= $y['is_active'] ? 'selected' : '' ?>>
            <?= esc($y['year']) ?>     <?= $y['is_active'] ? '(Aktif)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Admin: pilih guru -->
    <div class="mb-3">
      <label>Pilih Guru</label>
      <select id="teacher-select" class="form-select">
        <option value="">-- pilih guru --</option>
        <?php foreach ($teachers as $t): ?>
          <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (<?= esc($t['username']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="next-step"></div>

    <script>
      const yearSelect = document.getElementById('year-select');
      const teacherSelect = document.getElementById('teacher-select');
      const nextStep = document.getElementById('next-step');

      // Reset teacher selection when year changes
      yearSelect.addEventListener('change', function() {
        teacherSelect.value = '';
        nextStep.innerHTML = '';
      });

      teacherSelect.addEventListener('change', async function() {
        const teacherId = this.value;
        const yearId = yearSelect.value;
        nextStep.innerHTML = '';
        if (!teacherId) return;

        const res = await fetch("<?= site_url('admin/grades/teacher-info') ?>/" + teacherId + "?year_id=" + yearId);
        const data = await res.json();

        if (data.status !== 'ok') {
          nextStep.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
          return;
        }

        // jika guru kelas
        if (data.type === 'homeroom') {
          let html = `<h5>Kelas: ${data.class.name}</h5>`;
          html += `<form method="get" onsubmit="this.action='<?= site_url('admin/grades/show') ?>/${data.class.id}/'+this.subject_id.value+'?year_id=${yearId}'">`;
          html += `<div class="mb-3"><label>Pilih Mapel</label><select name="subject_id" class="form-select" required>`;
          data.subjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}</option>`;
          });
          html += `</select></div>`;
          html += `<button class="btn btn-success">Lihat Nilai</button></form>`;
          nextStep.innerHTML = html;
        }

        // jika guru mapel
        if (data.type === 'subject_teacher') {
          let html = `<form method="get" onsubmit="this.action='<?= site_url('admin/grades/show') ?>/'+this.class_id.value+'/'+this.subject_id.value+'?year_id=${yearId}'">`;
          html += `<div class="row mb-3"><div class="col-md-6"><label>Pilih Kelas</label>`;
          html += `<select id="class-select" name="class_id" class="form-select" required><option value="">-- pilih kelas --</option>`;

          let grouped = {};
          data.assignments.forEach(a => {
            if (!grouped[a.class_id]) grouped[a.class_id] = [];
            grouped[a.class_id].push({id: a.subject_id, name: a.subject_name, class_name: a.class_name});
          });

          Object.keys(grouped).forEach(cid => {
            html += `<option value="${cid}">${grouped[cid][0].class_name}</option>`;
          });

          html += `</select></div>`;
          html += `<div class="col-md-6"><label>Pilih Mapel</label><select id="subject-select" name="subject_id" class="form-select" required><option value="">-- pilih mapel --</option></select></div></div>`;
          html += `<button class="btn btn-success">Lihat Nilai</button></form>`;

          nextStep.innerHTML = html;

          // event onchange
          const classSelect = document.getElementById('class-select');
          const subjectSelect = document.getElementById('subject-select');
          classSelect.addEventListener('change', function() {
            subjectSelect.innerHTML = '<option value="">-- pilih mapel --</option>';
            if (grouped[this.value]) {
              grouped[this.value].forEach(s => {
                subjectSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
              });
            }
          });
        }
      });
    </script>
  <?php endif; ?>


  <?php if ($role == 'homeroom'): ?>
    <!-- Guru kelas: otomatis kelas ditampilkan -->
    <h5>Kelas: <?= esc($class['name']) ?></h5>
    <form method="get" onsubmit="this.action='<?= site_url('admin/grades/show/' . $class['id']) ?>/'+this.subject_id.value">
      <div class="mb-3">
        <label>Pilih Mapel</label>
        <select name="subject_id" class="form-select" required>
          <option value="">-- pilih mapel --</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-success">Lihat Nilai</button>
    </form>
  <?php endif; ?>


  <?php if ($role == 'subject_teacher'): ?>
    <!-- Guru mapel: pilih kelas + mapel -->
    <form method="get"
      onsubmit="this.action='<?= site_url('admin/grades/show') ?>/'+this.class_id.value+'/'+this.subject_id.value">
      <div class="row mb-3">
        <div class="col-md-6">
          <label>Pilih Kelas</label>
          <select id="class-select" name="class_id" class="form-select" required>
            <option value="">-- pilih kelas --</option>
            <?php foreach ($assignments as $cid => $a): ?>
              <option value="<?= $cid ?>"><?= esc($a['class']['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label>Pilih Mapel</label>
          <select id="subject-select" name="subject_id" class="form-select" required>
            <option value="">-- pilih mapel --</option>
          </select>
        </div>
      </div>
      <button class="btn btn-success">Lihat Nilai</button>
    </form>

    <script>
      const classSelect = document.getElementById('class-select');
      const subjectSelect = document.getElementById('subject-select');
      const grouped = <?= json_encode($assignments) ?>;
      classSelect.addEventListener('change', function () {
        subjectSelect.innerHTML = '<option value="">-- pilih mapel --</option>';
        if (grouped[this.value]) {
          grouped[this.value].subjects.forEach(s => {
            subjectSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
          });
        }
      });
    </script>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>