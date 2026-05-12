<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Tambah Pemetaan Guru</h2>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Petunjuk:</strong> Kelas yang sudah memiliki guru pengampu untuk mata pelajaran tertentu akan ditandai dengan warna merah dan informasi guru yang mengampu.
</div>

<form method="post" action="<?= site_url('admin/teachingassignments/store') ?>" id="assignmentForm">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label>Guru</label>
        <select name="teacher_id" id="teacher_id" class="form-control select2" required>
            <option value="">-- Pilih Guru --</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" id="role" class="form-control" required>
            <option value="">-- Pilih Role --</option>
            <option value="guru_kelas">Guru Kelas</option>
            <option value="guru_mapel">Guru Mapel</option>
        </select>
    </div>

    <!-- Guru Kelas: 1 kelas -->
    <div class="mb-3" id="kelasWrapper">
        <label>Kelas</label>
        <select name="class_id" id="class_id" class="form-control select2">
            <option value="">-- Pilih Kelas --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Guru Kelas: multi mapel -->
    <div class="mb-3 d-none" id="multiMapelWrapper">
        <label>Pilih Beberapa Mata Pelajaran</label>
        <select name="subject_ids[]" id="subject_ids" class="form-control select2" multiple>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <div id="multiMapelConflictInfo" class="mt-2"></div>
    </div>

    <!-- Guru Mapel: 1 mapel -->
    <div class="mb-3 d-none" id="subjectWrapper">
        <label>Mata Pelajaran</label>
        <select name="subject_id" id="subject_id" class="form-control select2">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Guru Mapel: multi kelas -->
    <div class="mb-3 d-none" id="multiKelasWrapper">
        <label>Pilih Beberapa Kelas</label>
        <select name="class_ids[]" id="class_ids" class="form-control select2" multiple>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" data-class-name="<?= esc($c['name']) ?>"><?= $c['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <div id="conflictInfo" class="mt-2"></div>
    </div>

    <div class="mb-3">
        <label>Tahun Ajaran</label>
        <select name="academic_year_id" id="academic_year_id" class="form-control select2" required>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $y['is_active'] ? 'selected' : '' ?>><?= $y['year'] ?> <?= $y['is_active'] ? '(aktif)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-success" id="submitBtn">Simpan</button>
    <a href="<?= site_url('admin/teachingassignments') ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.conflict-option {
    background-color: #ffebee !important;
    color: #c62828 !important;
}

.conflict-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    margin: 0.25rem;
    background-color: #ffebee;
    border: 1px solid #ef5350;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.conflict-badge i {
    color: #c62828;
}

.select2-results__option[aria-disabled="true"] {
    opacity: 0.6;
}
</style>

<script>
let conflictData = {};
let currentYearId = $('#academic_year_id').val();

// Handle role change
document.getElementById('role').addEventListener('change', function() {
    const role = this.value;
    const kelasWrapper = document.getElementById('kelasWrapper');
    const multiKelasWrapper = document.getElementById('multiKelasWrapper');
    const multiMapelWrapper = document.getElementById('multiMapelWrapper');
    const subjectWrapper = document.getElementById('subjectWrapper');

    if (role === 'guru_kelas') {
        kelasWrapper.classList.remove('d-none');
        multiMapelWrapper.classList.remove('d-none');
        multiKelasWrapper.classList.add('d-none');
        subjectWrapper.classList.add('d-none');
    } else if (role === 'guru_mapel') {
        kelasWrapper.classList.add('d-none');
        multiMapelWrapper.classList.add('d-none');
        multiKelasWrapper.classList.remove('d-none');
        subjectWrapper.classList.remove('d-none');
    } else {
        kelasWrapper.classList.remove('d-none');
        multiMapelWrapper.classList.add('d-none');
        multiKelasWrapper.classList.add('d-none');
        subjectWrapper.classList.add('d-none');
    }
    
    // Reset conflict data when role changes
    conflictData = {};
    updateConflictDisplay();
});

// Check for conflicts when subject is selected (Guru Mapel)
$('#subject_id').on('change', function() {
    const subjectId = $(this).val();
    const yearId = $('#academic_year_id').val();
    
    if (subjectId && yearId) {
        checkConflicts(subjectId, yearId);
    } else {
        conflictData = {};
        updateConflictDisplay();
    }
});

// Check for conflicts when year changes
$('#academic_year_id').on('change', function() {
    currentYearId = $(this).val();
    const subjectId = $('#subject_id').val();
    
    if (subjectId && currentYearId) {
        checkConflicts(subjectId, currentYearId);
    }
});

// Function to check conflicts via AJAX
function checkConflicts(subjectId, yearId) {
    $.ajax({
        url: '<?= site_url('admin/teachingassignments/get-existing') ?>',
        method: 'GET',
        data: {
            subject_id: subjectId,
            year_id: yearId
        },
        success: function(response) {
            if (response.success) {
                conflictData = response.conflicts;
                updateConflictDisplay();
            }
        },
        error: function() {
            console.error('Failed to check conflicts');
        }
    });
}

// Update visual display of conflicts
function updateConflictDisplay() {
    const $classSelect = $('#class_ids');
    const $conflictInfo = $('#conflictInfo');
    
    // Clear previous styling
    $classSelect.find('option').removeClass('conflict-option');
    
    // Apply conflict styling
    Object.keys(conflictData).forEach(classId => {
        const $option = $classSelect.find(`option[value="${classId}"]`);
        $option.addClass('conflict-option');
    });
    
    // Refresh Select2 to apply styling
    $classSelect.trigger('change.select2');
    
    // Show conflict info for selected classes
    updateSelectedConflictInfo();
}

// Update conflict info when classes are selected
$('#class_ids').on('change', function() {
    updateSelectedConflictInfo();
});

function updateSelectedConflictInfo() {
    const selectedClasses = $('#class_ids').val() || [];
    const $conflictInfo = $('#conflictInfo');
    
    let conflictHtml = '';
    let hasConflict = false;
    
    selectedClasses.forEach(classId => {
        if (conflictData[classId]) {
            hasConflict = true;
            const className = $(`#class_ids option[value="${classId}"]`).data('class-name');
            conflictHtml += `
                <div class="conflict-badge">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>${className}</strong> sudah diampu oleh: <strong>${conflictData[classId].teacher_name}</strong>
                </div>
            `;
        }
    });
    
    if (hasConflict) {
        $conflictInfo.html(`
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Peringatan Konflik:</strong>
                ${conflictHtml}
            </div>
        `);
    } else {
        $conflictInfo.html('');
    }
}

// Form validation before submit
$('#assignmentForm').on('submit', function(e) {
    const role = $('#role').val();
    const selectedClasses = $('#class_ids').val() || [];
    let hasConflict = false;
    
    if (role === 'guru_mapel') {
        selectedClasses.forEach(classId => {
            if (conflictData[classId]) {
                hasConflict = true;
            }
        });
        
        if (hasConflict) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Konflik Terdeteksi!',
                html: 'Beberapa kelas yang dipilih sudah memiliki guru pengampu untuk mata pelajaran ini.<br><br>Apakah Anda yakin ingin melanjutkan? Ini akan membuat duplikasi pengampu.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Remove the submit event listener and submit
                    $('#assignmentForm').off('submit').submit();
                }
            });
        }
    }
});

// Initialize Select2 with custom template for conflict display
$('#class_ids').select2({
    templateResult: formatClassOption,
    templateSelection: formatClassSelection
});

function formatClassOption(option) {
    if (!option.id) {
        return option.text;
    }
    
    const classId = option.id;
    const $option = $(option.element);
    
    if (conflictData[classId]) {
        const $result = $(
            `<span>
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                ${option.text}
                <small class="text-danger"> (sudah ada: ${conflictData[classId].teacher_name})</small>
            </span>`
        );
        return $result;
    }
    
    return option.text;
}

function formatClassSelection(option) {
    return option.text;
}
</script>
<?= $this->endSection() ?>

