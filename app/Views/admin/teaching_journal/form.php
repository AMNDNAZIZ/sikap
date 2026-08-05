<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
    $isHomeroom = $is_homeroom ?? false;
    $isEdit = !empty($journal['id']);
    $journalSubjects = $journal_subjects ?? [];
    
    // Jika edit + homeroom + ada data di pivot, gunakan data pivot
    // Jika edit + homeroom + tidak ada di pivot, fallback ke subject_id utama
    if ($isEdit && $isHomeroom && empty($journalSubjects) && !empty($journal['subject_id'])) {
        $journalSubjects = [
            ['subject_id' => $journal['subject_id'], 'atp_id' => $journal['atp_id'] ?? '']
        ];
    }
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= esc($title) ?></h5>
                <a href="<?= base_url('admin/teaching-journal') ?>" class="btn btn-sm btn-light">⬅️ Kembali</a>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/teaching-journal/store') ?>" method="post" id="journalForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $journal['id'] ?? '' ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?= $journal['date'] ?? $today ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kelas</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($journal['class_id'] ?? $selected_class) == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($isHomeroom): ?>
                        <!-- ═══ MODE GURU KELAS: Multi-Subject ═══ -->
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Mode Guru Kelas:</strong> Anda bisa menambahkan beberapa mata pelajaran sekaligus dalam satu jurnal.
                            </div>
                        </div>

                        <div class="col-12" id="multiSubjectSection">
                            <label class="form-label fw-bold">Mata Pelajaran & CP</label>
                            <div id="subjectEntries">
                                <?php
                                    $entries = !empty($journalSubjects) ? $journalSubjects : [['subject_id' => '', 'atp_id' => '']];
                                    foreach ($entries as $idx => $entry):
                                ?>
                                <div class="subject-entry border rounded p-3 mb-2 bg-light" data-index="<?= $idx ?>">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small">Mapel</label>
                                            <select name="subjects[<?= $idx ?>][subject_id]" class="form-select form-select-sm subject-select" data-index="<?= $idx ?>" required>
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($subjects as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= ($entry['subject_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small">Lingkup Materi / ATP</label>
                                            <select name="subjects[<?= $idx ?>][atp_id]" class="form-select form-select-sm atp-select" data-index="<?= $idx ?>" data-current="<?= $entry['atp_id'] ?? '' ?>">
                                                <option value="">-- Pilih Materi --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-entry" title="Hapus" <?= count($entries) <= 1 ? 'style="display:none;"' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="addSubjectEntry">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Mapel
                            </button>
                        </div>

                        <!-- Hidden fields: diisi dari JS sebelum submit -->
                        <input type="hidden" name="subject_id" id="hidden_subject_id" value="<?= $journal['subject_id'] ?? '' ?>">
                        <input type="hidden" name="atp_id" id="hidden_atp_id" value="<?= $journal['atp_id'] ?? '' ?>">

                        <?php else: ?>
                        <!-- ═══ MODE BIASA: Single Subject ═══ -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mata Pelajaran</label>
                            <select name="subject_id" id="subject_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($journal['subject_id'] ?? $selected_subject) == $s['id'] ? 'selected' : '' ?>>
                                        <?= esc($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lingkup Materi / ATP</label>
                            <select name="atp_id" id="atp_id" class="form-select">
                                <option value="">-- Pilih Materi --</option>
                            </select>
                            <small class="text-muted">Format: Sem[X] | Lingkup Materi (Elemen CP)</small>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label fw-bold">Catatan Proses Pembelajaran</label>
                            <textarea name="notes" class="form-control" rows="5" placeholder="Contoh: Menyampaikan materi tentang..., siswa aktif bertanya mengenai..., evaluasi dilakukan dengan..." required><?= $journal['notes'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success px-4">💾 Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isHomeroom): ?>
<!-- Script untuk mode guru kelas (multi-subject) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const entriesContainer = document.getElementById('subjectEntries');
    const addBtn = document.getElementById('addSubjectEntry');
    let entryIndex = <?= count($entries) ?>;

    const subjectsData = <?= json_encode($subjects) ?>;
    const atpBaseUrl = '<?= base_url('admin/teaching-journal/get-atps') ?>';

    // Load ATP berdasarkan class + subject untuk entry tertentu
    function loadAtpForEntry(index, preselect) {
        const entry = document.querySelector(`.subject-entry[data-index="${index}"]`);
        if (!entry) return;
        const subjectSelect = entry.querySelector('.subject-select');
        const atpSelect = entry.querySelector('.atp-select');
        const classId = classSelect.value;
        const subjectId = subjectSelect.value;
        const currentAtp = preselect || atpSelect.dataset.current || '';

        if (classId && subjectId) {
            fetch(`${atpBaseUrl}?class_id=${classId}&subject_id=${subjectId}`)
                .then(r => r.json())
                .then(data => {
                    atpSelect.innerHTML = '<option value="">-- Pilih Materi --</option>';
                    data.forEach(atp => {
                        const opt = document.createElement('option');
                        opt.value = atp.id;
                        opt.textContent = `Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})`;
                        if (String(atp.id) === String(currentAtp)) {
                            opt.selected = true;
                        }
                        atpSelect.appendChild(opt);
                    });
                });
        } else {
            atpSelect.innerHTML = '<option value="">-- Pilih Materi --</option>';
        }
    }

    // Tambah entry baru
    addBtn.addEventListener('click', function() {
        const optionsHtml = subjectsData.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        const html = `
            <div class="subject-entry border rounded p-3 mb-2 bg-light" data-index="${entryIndex}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">Mapel</label>
                        <select name="subjects[${entryIndex}][subject_id]" class="form-select form-select-sm subject-select" data-index="${entryIndex}" required>
                            <option value="">-- Pilih --</option>
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Lingkup Materi / ATP</label>
                        <select name="subjects[${entryIndex}][atp_id]" class="form-select form-select-sm atp-select" data-index="${entryIndex}" data-current="">
                            <option value="">-- Pilih Materi --</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-entry" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        entriesContainer.insertAdjacentHTML('beforeend', html);
        entryIndex++;
        updateRemoveButtons();
    });

    // Remove entry
    entriesContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-entry')) {
            e.target.closest('.subject-entry').remove();
            updateRemoveButtons();
        }
    });

    // Subject change → load ATP
    entriesContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('subject-select')) {
            const idx = e.target.dataset.index;
            loadAtpForEntry(idx, '');
        }
    });

    // Class change → reload semua ATP
    classSelect.addEventListener('change', function() {
        document.querySelectorAll('.subject-entry').forEach(entry => {
            loadAtpForEntry(entry.dataset.index, '');
        });
    });

    function updateRemoveButtons() {
        const entries = document.querySelectorAll('.subject-entry');
        entries.forEach(entry => {
            const btn = entry.querySelector('.remove-entry');
            btn.style.display = entries.length > 1 ? 'inline-block' : 'none';
        });
    }

    // Sebelum submit: isi hidden fields dari entry pertama
    document.getElementById('journalForm').addEventListener('submit', function(e) {
        const firstSubject = document.querySelector('.subject-entry .subject-select');
        const firstAtp = document.querySelector('.subject-entry .atp-select');
        if (firstSubject) document.getElementById('hidden_subject_id').value = firstSubject.value;
        if (firstAtp) document.getElementById('hidden_atp_id').value = firstAtp.value;
    });

    // Initial load ATPs untuk semua entries yang sudah punya subject terpilih
    document.querySelectorAll('.subject-entry').forEach(entry => {
        const subjectSelect = entry.querySelector('.subject-select');
        if (subjectSelect.value) {
            loadAtpForEntry(entry.dataset.index);
        }
    });

    updateRemoveButtons();
});
</script>

<?php else: ?>
<!-- Script untuk mode biasa (single subject) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const atpSelect = document.getElementById('atp_id');
        const currentAtpId = '<?= $journal['atp_id'] ?? '' ?>';

        function loadAtps() {
            const classId = classSelect.value;
            const subjectId = subjectSelect.value;

            if (classId && subjectId) {
                fetch(`<?= base_url('admin/teaching-journal/get-atps') ?>?class_id=${classId}&subject_id=${subjectId}`)
                    .then(response => response.json())
                    .then(data => {
                        atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
                        data.forEach(atp => {
                            const option = document.createElement('option');
                            option.value = atp.id;
                            option.textContent = `Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})`;
                            if (atp.id == currentAtpId) {
                                option.selected = true;
                            }
                            atpSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error loading ATPs:', error));
            } else {
                atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
            }
        }

        classSelect.addEventListener('change', loadAtps);
        subjectSelect.addEventListener('change', loadAtps);

        if (classSelect.value && subjectSelect.value) {
            loadAtps();
        }
    });
</script>
<?php endif; ?>

<?= $this->endSection() ?>
