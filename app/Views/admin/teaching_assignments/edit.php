<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Edit Pemetaan Guru</h2>
<form method="post" action="<?= site_url('admin/teachingassignments/update/'.$assignment['id']) ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label>Guru</label>
        <select name="teacher_id" class="form-control select2" required>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $assignment['teacher_id'] == $t['id'] ? 'selected' : '' ?>>
                    <?= $t['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" id="role" class="form-control" required>
            <option value="guru_kelas" <?= $assignment['role'] == 'guru_kelas' ? 'selected' : '' ?>>Guru Kelas</option>
            <option value="guru_mapel" <?= $assignment['role'] == 'guru_mapel' ? 'selected' : '' ?>>Guru Mapel</option>
        </select>
    </div>

    <!-- Guru Kelas: kelas tunggal -->
    <div class="mb-3" id="kelasWrapper">
        <label>Kelas</label>
        <select name="class_id" class="form-control select2">
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $assignment['class_id'] == $c['id'] ? 'selected' : '' ?>>
                    <?= $c['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Guru Mapel: mapel tunggal -->
    <div class="mb-3 d-none" id="subjectWrapper">
        <label>Mata Pelajaran</label>
        <select name="subject_id" class="form-control select2">
            <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $assignment['subject_id'] == $s['id'] ? 'selected' : '' ?>>
                    <?= $s['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Tahun Ajaran</label>
        <select name="academic_year_id" class="form-control select2" required>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $assignment['academic_year_id'] == $y['id'] ? 'selected' : '' ?>>
                    <?= $y['year'] ?> <?= $y['is_active'] ? '(aktif)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="btn btn-success">Update</button>
</form>

<script>
function toggleRoleUI(role) {
    const kelasWrapper = document.getElementById('kelasWrapper');
    const subjectWrapper = document.getElementById('subjectWrapper');

    if (role === 'guru_kelas') {
        kelasWrapper.classList.remove('d-none');
        subjectWrapper.classList.add('d-none');
    } else if (role === 'guru_mapel') {
        kelasWrapper.classList.add('d-none');
        subjectWrapper.classList.remove('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    toggleRoleUI(roleSelect.value);
    roleSelect.addEventListener('change', function() {
        toggleRoleUI(this.value);
    });
});
</script>

<?= $this->endSection() ?>
