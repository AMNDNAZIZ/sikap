<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php $isHomeroom = $is_homeroom ?? false; ?>
<?php $isEdit = !empty($journal['id']); ?>

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

                        <?php if ($isHomeroom && !$isEdit): ?>
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
                                <!-- Entry pertama -->
                                <div class="subject-entry border rounded p-3 mb-2 bg-light" data-index="0">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small">Mapel</label>
                                            <select name="subjects[0][subject_id]" class="form-select form-select-sm subject-select" data-index="0" required>
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($subjects as $s): ?>
                                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small">Lingkup Materi / ATP</label>
                                            <select name="subjects[0][atp_id]" class="form-select form-select-sm atp-select" data-index="0">
                                                <option value="">-- Pilih Materi --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-entry" style="display:none;" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="addSubjectEntry">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Mapel
                            </button>
                        </div>

                        <!-- Hidden fields untuk compatibility (akan diisi dari entry pertama) -->
                        <input type="hidden" name="subject_id" id="hidden_subject_id" value="">
                        <input type="hidden" name="atp_id" id="hidden_atp_id" value="">

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

<?php if ($isHomeroom && !$isEdit): ?>
<!-- Script untuk mode guru kelas (multi-subject) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const entriesContainer = document.getElementById('subjectEntries');
    const addBtn = document.getElementById('addSubjectEntry');
    let entryIndex = 1;

    const subjectsData = <?= json_encode($subjects) ?>;

    // Load ATP berdasarkan class + subject
    function loadAtpForEntry(index) {
        const entry = document.querySelector(`.subject-entry[data-index="${index}"]`);
        const subjectSelect = entry.querySelector('.subject-select');
        const atpSelect = entry.querySelector('.atp-select');
        const classId = classSelect.value;
        const subjectId = subjectSelect.value;

        if (classId && subjectId) {
            fetch(`<?= base_url('admin/teaching-journal/get-atps') ?>?class_id=${classId}&subject_id=${subjectId}`)
                .then(r => r.json())
                .then(data => {
                    atpSelect.innerHTML = '<option value="">-- Pilih Materi --</option>';
                    data.forEach(atp => {
                        const opt = document.createElement('option');
                        opt.value = atp.id;
                        opt.textContent = `Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})`;
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
                        <select name="subjects[${entryIndex}][atp_id]" class="form-select form-select-sm atp-select" data-index="${entryIndex}">
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
            loadAtpForEntry(idx);
        }
    });

    // Class change → reload semua ATP
    classSelect.addEventListener('change', function() {
        document.querySelectorAll('.subject-entry').forEach(entry => {
            const idx = entry.dataset.index;
            loadAtpForEntry(idx);
        });
    });

    function updateRemoveButtons() {
        const entries = document.querySelectorAll('.subject-entry');
        entries.forEach((entry, i) => {
            const btn = entry.querySelector('.remove-entry');
            btn.style.display = entries.length > 1 ? 'inline-block' : 'none';
        });
    }

    // Sebelum submit, isi hidden fields dari entry pertama
    document.getElementById('journalForm').addEventListener('submit', function() {
        const firstSubject = document.querySelector('.subject-entry[data-index="0"] .subject-select');
        const firstAtp = document.querySelector('.subject-entry[data-index="0"] .atp-select');
        if (firstSubject) document.getElementById('hidden_subject_id').value = firstSubject.value;
        if (firstAtp) document.getElementById('hidden_atp_id').value = firstAtp.value;
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
