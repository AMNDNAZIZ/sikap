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
                    <input type="hidden" name="modul_id" id="hidden_modul_id" value="<?= $journal['modul_id'] ?? '' ?>">
                    <input type="hidden" name="pertemuan_ke" id="hidden_pertemuan_ke" value="<?= $journal['pertemuan_ke'] ?? '' ?>">
                    
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

                        <!-- ═══ RUJUKAN MODUL AJAR (OPSIONAL) ═══ -->
                        <div class="col-12">
                            <div class="card border border-primary-subtle bg-light shadow-none mb-1">
                                <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary small">
                                        <i class="bi bi-journal-bookmark me-1"></i> Rujukan Modul Ajar (Opsional)
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary small" id="modulStatusBadge">Opsional</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 align-items-center">
                                        <!-- Dropdown Pilih Modul Ajar -->
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1" for="modul_ajar_id">Pilih Dokumen Modul Ajar</label>
                                            <select id="modul_ajar_id" class="form-select form-select-sm">
                                                <option value="">-- Pilih Kelas & Mapel Terlebih Dahulu --</option>
                                            </select>
                                            <div id="modulHelpText" class="form-text small text-muted">Pilih dokumen untuk melihat daftar pertemuan.</div>
                                        </div>

                                        <!-- Dropdown Pilih Pertemuan -->
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1" for="pertemuan_select">Pilih Pertemuan</label>
                                            <select id="pertemuan_select" class="form-select form-select-sm" disabled>
                                                <option value="">-- Pilih Dokumen Modul Terlebih Dahulu --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Alert Pesan Loading / Status / Error -->
                                    <div id="modulAlertMessage" class="alert alert-warning py-2 px-3 small mt-2 mb-0" style="display: none;"></div>

                                    <!-- Preview Rencana Pertemuan Terpilih -->
                                    <div id="meetingPreviewBox" class="mt-3 p-3 bg-white border rounded" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                            <div>
                                                <h6 class="mb-0 text-primary fw-bold" id="previewMeetingTitle">-</h6>
                                                <span class="badge bg-info-subtle text-info mt-1" id="previewMeetingMeta">-</span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary" id="btnPullMeetingPlan">
                                                <i class="bi bi-arrow-down-square me-1"></i> Tarik Rencana ke Catatan
                                            </button>
                                        </div>

                                        <div class="small fw-semibold text-muted mb-1">Ringkasan Kegiatan yang Direncanakan:</div>
                                        <div id="previewStepsList" class="small bg-light p-2 rounded border" style="max-height: 180px; overflow-y: auto;">
                                            <!-- Diisi oleh JS -->
                                        </div>
                                        <div class="small text-muted mt-2">
                                            <i class="bi bi-info-circle me-1 text-primary"></i>
                                            <em>Rencana dari Modul Ajar berfungsi sebagai panduan. Silakan edit catatan di bawah agar sesuai dengan realisasi proses belajar di kelas.</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ STATUS REALISASI PEMBELAJARAN ═══ -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status Keterlaksanaan Pembelajaran</label>
                            <select name="status_realisasi" id="status_realisasi" class="form-select">
                                <option value="terlaksana" <?= ($journal['status_realisasi'] ?? 'terlaksana') === 'terlaksana' ? 'selected' : '' ?>>
                                    ✅ Terlaksana Penuh
                                </option>
                                <option value="sebagian" <?= ($journal['status_realisasi'] ?? '') === 'sebagian' ? 'selected' : '' ?>>
                                    ⚠️ Terlaksana Sebagian / Penyesuaian
                                </option>
                                <option value="tertunda" <?= ($journal['status_realisasi'] ?? '') === 'tertunda' ? 'selected' : '' ?>>
                                    ⏳ Tertunda / Jam Dipakai Kegiatan Lain
                                </option>
                            </select>
                            <small class="text-muted">Keterlaksanaan rencana di kelas nyata.</small>
                        </div>

                        <!-- ═══ CATATAN REALISASI PEMBELAJARAN ═══ -->
                        <div class="col-12">
                            <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                                <span>Catatan Realisasi Pembelajaran (Aktual)</span>
                                <small class="text-muted fw-normal">Refleksi, hasil kegiatan, dan respon siswa di kelas</small>
                            </label>
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

<!-- Script Integrasi Rujukan Modul Ajar (Berlaku untuk Single & Multi-Subject) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const modulSelect = document.getElementById('modul_ajar_id');
    const pertemuanSelect = document.getElementById('pertemuan_select');
    const previewBox = document.getElementById('meetingPreviewBox');
    const previewTitle = document.getElementById('previewMeetingTitle');
    const previewMeta = document.getElementById('previewMeetingMeta');
    const previewSteps = document.getElementById('previewStepsList');
    const btnPull = document.getElementById('btnPullMeetingPlan');
    const notesTextarea = document.querySelector('textarea[name="notes"]');
    const alertBox = document.getElementById('modulAlertMessage');
    const statusBadge = document.getElementById('modulStatusBadge');

    const getModulsUrl = '<?= base_url('admin/teaching-journal/get-moduls') ?>';
    const getMeetingsUrl = '<?= base_url('admin/teaching-journal/get-modul-meetings') ?>';

    let currentMeetings = [];
    let currentModuls = [];

    function showAlert(msg, type = 'warning') {
        if (!alertBox) return;
        alertBox.className = `alert alert-${type} py-2 px-3 small mt-2 mb-0`;
        alertBox.innerHTML = msg;
        alertBox.style.display = 'block';
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    function getActiveSubjectId() {
        const singleSubject = document.getElementById('subject_id');
        if (singleSubject && singleSubject.value) return singleSubject.value;
        const firstMultiSubject = document.querySelector('.subject-entry .subject-select');
        if (firstMultiSubject && firstMultiSubject.value) return firstMultiSubject.value;
        return '';
    }

    function getActiveAtpId() {
        const singleAtp = document.getElementById('atp_id');
        if (singleAtp && singleAtp.value) return singleAtp.value;
        const firstMultiAtp = document.querySelector('.subject-entry .atp-select');
        if (firstMultiAtp && firstMultiAtp.value) return firstMultiAtp.value;
        return '';
    }

    const hiddenModulInput = document.getElementById('hidden_modul_id');
    const hiddenPertemuanInput = document.getElementById('hidden_pertemuan_ke');

    function loadModulList() {
        if (!modulSelect || !pertemuanSelect) return;

        const classId = classSelect ? classSelect.value : '';
        const subjectId = getActiveSubjectId();
        const atpId = getActiveAtpId();

        modulSelect.innerHTML = '<option value="">-- Memuat Modul Ajar... --</option>';
        modulSelect.disabled = true;
        pertemuanSelect.innerHTML = '<option value="">-- Pilih Dokumen Modul Terlebih Dahulu --</option>';
        pertemuanSelect.disabled = true;
        if (previewBox) previewBox.style.display = 'none';
        currentMeetings = [];
        hideAlert();

        if (!classId || !subjectId) {
            modulSelect.innerHTML = '<option value="">-- Pilih Kelas & Mapel Terlebih Dahulu --</option>';
            if (statusBadge) {
                statusBadge.className = 'badge bg-secondary-subtle text-secondary small';
                statusBadge.textContent = 'Opsional';
            }
            return;
        }

        let url = `${getModulsUrl}?class_id=${encodeURIComponent(classId)}&subject_id=${encodeURIComponent(subjectId)}`;
        if (atpId) {
            url += `&atp_id=${encodeURIComponent(atpId)}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.moduls || data.moduls.length === 0) {
                    modulSelect.innerHTML = '<option value="">-- Belum ada Modul Ajar --</option>';
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-light text-muted small';
                        statusBadge.textContent = 'Tidak Ada Modul';
                    }
                    showAlert('<i class="bi bi-info-circle me-1"></i> Belum ada Modul Ajar untuk kelas dan mapel ini. Anda tetap dapat mengisi catatan proses pembelajaran secara manual.', 'secondary');
                    return;
                }

                currentModuls = data.moduls;
                if (statusBadge) {
                    statusBadge.className = 'badge bg-success-subtle text-success small';
                    statusBadge.textContent = `${data.moduls.length} Modul Tersedia`;
                }

                modulSelect.innerHTML = '<option value="">-- Pilih Dokumen Modul Ajar --</option>';
                data.moduls.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.label;
                    modulSelect.appendChild(opt);
                });

                modulSelect.disabled = false;

                // Cek apakah ada modul yang tersimpan sebelumnya (mode edit)
                const savedModulId = hiddenModulInput ? hiddenModulInput.value : '';
                const savedPertemuanKe = hiddenPertemuanInput ? hiddenPertemuanInput.value : '';

                let matchedModul = null;
                if (savedModulId) {
                    matchedModul = data.moduls.find(m => String(m.id) === String(savedModulId));
                }
                if (!matchedModul && atpId) {
                    matchedModul = data.moduls.find(m => String(m.atp_id) === String(atpId));
                }
                if (!matchedModul && data.moduls.length === 1 && !savedModulId) {
                    matchedModul = data.moduls[0];
                }

                if (matchedModul) {
                    modulSelect.value = matchedModul.id;
                    if (hiddenModulInput) hiddenModulInput.value = matchedModul.id;
                    loadModulMeetings(matchedModul.id, savedPertemuanKe);
                }
            })
            .catch(err => {
                console.error('Error loading moduls:', err);
                modulSelect.innerHTML = '<option value="">-- Gagal memuat Modul Ajar --</option>';
                showAlert('Gagal menghubungi server untuk memuat modul ajar.', 'danger');
            });
    }

    function loadModulMeetings(modulId, preselectPertemuanKe = null) {
        if (!modulId) {
            pertemuanSelect.innerHTML = '<option value="">-- Pilih Dokumen Modul Terlebih Dahulu --</option>';
            pertemuanSelect.disabled = true;
            if (previewBox) previewBox.style.display = 'none';
            currentMeetings = [];
            hideAlert();
            if (hiddenModulInput) hiddenModulInput.value = '';
            if (hiddenPertemuanInput) hiddenPertemuanInput.value = '';
            return;
        }

        pertemuanSelect.innerHTML = '<option value="">Memuat rincian pertemuan...</option>';
        pertemuanSelect.disabled = true;
        if (previewBox) previewBox.style.display = 'none';
        hideAlert();

        fetch(`${getMeetingsUrl}?modul_id=${encodeURIComponent(modulId)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    showAlert(data.message || 'Gagal memuat rincian pertemuan.', 'danger');
                    pertemuanSelect.innerHTML = '<option value="">-- Gagal memuat pertemuan --</option>';
                    return;
                }

                currentMeetings = data.pertemuan || [];
                if (currentMeetings.length === 0) {
                    pertemuanSelect.innerHTML = '<option value="">-- Tidak ada pertemuan terdeteksi --</option>';
                    showAlert('<i class="bi bi-exclamation-triangle me-1"></i> Tidak ada rincian pertemuan terdeteksi dalam modul ini. Catatan dapat diisi manual.', 'warning');
                    return;
                }

                pertemuanSelect.innerHTML = '<option value="">-- Pilih Pertemuan --</option>';
                currentMeetings.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.pertemuan_ke;
                    opt.textContent = m.label;
                    pertemuanSelect.appendChild(opt);
                });
                pertemuanSelect.disabled = false;

                if (preselectPertemuanKe) {
                    pertemuanSelect.value = preselectPertemuanKe;
                    if (hiddenPertemuanInput) hiddenPertemuanInput.value = preselectPertemuanKe;
                    displayMeetingPreview(preselectPertemuanKe);
                }
            })
            .catch(err => {
                console.error('Error loading modul meetings:', err);
                pertemuanSelect.innerHTML = '<option value="">-- Gagal memuat pertemuan --</option>';
                showAlert('Gagal memuat rincian pertemuan dari modul.', 'danger');
            });
    }

    function displayMeetingPreview(pertemuanKe) {
        if (!previewBox) return;

        if (!pertemuanKe) {
            previewBox.style.display = 'none';
            return;
        }

        const meeting = currentMeetings.find(m => String(m.pertemuan_ke) === String(pertemuanKe));
        if (!meeting) {
            previewBox.style.display = 'none';
            return;
        }

        previewTitle.textContent = meeting.judul;
        const metaParts = [];
        if (meeting.model) metaParts.push(meeting.model);
        if (meeting.durasi) metaParts.push(meeting.durasi);
        previewMeta.textContent = metaParts.length > 0 ? metaParts.join(' • ') : 'Rencana Pertemuan';

        if (meeting.tahap_list && meeting.tahap_list.length > 0) {
            let listHtml = '<ul class="list-unstyled mb-0">';
            meeting.tahap_list.forEach(st => {
                const headerParts = [];
                if (st.tahap) headerParts.push(`<strong>${escapeHtml(st.tahap)}</strong>`);
                if (st.sintaks) headerParts.push(`<span class="text-muted">(${escapeHtml(st.sintaks)})</span>`);
                if (st.waktu) headerParts.push(`<span class="badge bg-secondary-subtle text-secondary">${escapeHtml(st.waktu)}</span>`);
                const headerStr = headerParts.join(' ');
                listHtml += `<li class="mb-1 pb-1 border-bottom border-light-subtle">
                    ${headerStr ? headerStr + '<br>' : ''}
                    <span class="text-secondary">${escapeHtml(st.deskripsi)}</span>
                </li>`;
            });
            listHtml += '</ul>';
            previewSteps.innerHTML = listHtml;
        } else {
            previewSteps.innerHTML = '<em>Deskripsi tahapan tidak tersedia dalam tabel.</em>';
        }

        previewBox.style.display = 'block';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (modulSelect) {
        modulSelect.addEventListener('change', function() {
            if (hiddenModulInput) hiddenModulInput.value = this.value;
            if (!this.value && hiddenPertemuanInput) hiddenPertemuanInput.value = '';
            loadModulMeetings(this.value);
        });
    }

    if (pertemuanSelect) {
        pertemuanSelect.addEventListener('change', function() {
            if (hiddenPertemuanInput) hiddenPertemuanInput.value = this.value;
            displayMeetingPreview(this.value);
        });
    }

    if (btnPull) {
        btnPull.addEventListener('click', function() {
            const selectedKe = pertemuanSelect ? pertemuanSelect.value : '';
            if (!selectedKe) {
                alert('Pilih pertemuan terlebih dahulu.');
                return;
            }

            const meeting = currentMeetings.find(m => String(m.pertemuan_ke) === String(selectedKe));
            if (!meeting || !meeting.rencana_teks) {
                alert('Rencana kegiatan untuk pertemuan ini tidak tersedia.');
                return;
            }

            const currentNotes = notesTextarea ? notesTextarea.value.trim() : '';
            if (currentNotes.length > 0) {
                const confirmed = confirm(
                    'Catatan proses pembelajaran sudah terisi.\n\n' +
                    'Menarik rencana dari Modul Ajar akan mengganti catatan saat ini.\n' +
                    'Apakah Anda yakin ingin menimpanya?'
                );
                if (!confirmed) {
                    return;
                }
            }

            if (notesTextarea) {
                notesTextarea.value = meeting.rencana_teks;
                notesTextarea.focus();

                notesTextarea.classList.add('is-valid');
                setTimeout(() => {
                    notesTextarea.classList.remove('is-valid');
                }, 1800);
            }
        });
    }

    if (classSelect) {
        classSelect.addEventListener('change', loadModulList);
    }

    const singleSubject = document.getElementById('subject_id');
    if (singleSubject) {
        singleSubject.addEventListener('change', loadModulList);
    }

    const singleAtp = document.getElementById('atp_id');
    if (singleAtp) {
        singleAtp.addEventListener('change', function() {
            const targetAtpId = this.value;
            if (targetAtpId && currentModuls.length > 0 && modulSelect) {
                const match = currentModuls.find(m => String(m.atp_id) === String(targetAtpId));
                if (match && modulSelect.value !== String(match.id)) {
                    modulSelect.value = match.id;
                    loadModulMeetings(match.id);
                }
            }
        });
    }

    const entriesContainer = document.getElementById('subjectEntries');
    if (entriesContainer) {
        entriesContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('subject-select') || e.target.classList.contains('atp-select')) {
                loadModulList();
            }
        });
    }

    // Pastikan nilai modul_id dan pertemuan_ke selalu sinkron saat form disubmit
    const journalForm = document.querySelector('form[action*="teaching-journal/store"]');
    if (journalForm) {
        journalForm.addEventListener('submit', function() {
            if (modulSelect && hiddenModulInput && modulSelect.value) {
                hiddenModulInput.value = modulSelect.value;
            }
            if (pertemuanSelect && hiddenPertemuanInput && pertemuanSelect.value) {
                hiddenPertemuanInput.value = pertemuanSelect.value;
            }
        });
    }

    setTimeout(() => {
        if (classSelect && classSelect.value && getActiveSubjectId()) {
            loadModulList();
        }
    }, 300);
});
</script>

<?= $this->endSection() ?>
