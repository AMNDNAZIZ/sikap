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
                    
                    <?php if (!empty($today_sessions)): ?>
                    <!-- ═══ ASISTEN JADWAL & PROMES HARI INI ═══ -->
                    <div class="card border border-primary-subtle bg-primary bg-opacity-10 mb-3 shadow-none">
                        <div class="card-body p-2 px-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="small text-primary">
                                    <i class="bi bi-magic me-1"></i>
                                    <strong>Sesi Mengajar Hari Ini:</strong> Klik salah satu sesi untuk mengisi otomatis:
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($today_sessions as $ses): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-white bg-white border border-primary-subtle py-1 px-2 text-start btn-apply-session"
                                                data-class-id="<?= $ses['class_id'] ?>"
                                                data-subject-id="<?= $ses['subject_id'] ?>"
                                                data-atp-id="<?= $ses['promes_target']['atp_id'] ?? '' ?>"
                                                title="Terapkan Kelas <?= esc($ses['class_name']) ?> - <?= esc($ses['subject_name']) ?>">
                                            <span class="fw-bold text-primary font-monospace"><?= esc($ses['time_label']) ?>:</span>
                                            <?= esc($ses['class_name']) ?> (<?= esc($ses['subject_code'] ?: $ses['subject_name']) ?>)
                                            <?php if (!empty($ses['promes_target'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">
                                                    Promes Siap
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?= $journal['date'] ?? ($today ?? date('Y-m-d')) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kelas</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($journal['class_id'] ?? ($selected_class ?? '')) == $c['id'] ? 'selected' : '' ?>>
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
                                            <div>
                                                <button type="button" class="btn btn-sm btn-primary" id="btnPullMeetingPlan" title="Tarik ringkasan rencana dari Modul Ajar ke Catatan Pelaksanaan">
                                                    <i class="bi bi-arrow-down-square me-1"></i> Tarik Rencana ke Catatan
                                                </button>
                                            </div>
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

                        <!-- ═══ PRESENSI SISWA SESI INI (OPSIONAL) ═══ -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">
                                    <i class="bi bi-people me-1 text-primary"></i> Presensi Siswa di Sesi Ini
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnOpenQuickAttendance" style="font-size: 0.78rem;">
                                    <i class="bi bi-pencil-square me-1"></i> Buka Presensi
                                </button>
                            </div>
                            <div id="attendanceSummaryWidget" class="p-2 border rounded bg-light small d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <span class="text-muted" id="attSummaryText"><i class="bi bi-info-circle me-1"></i> Pilih kelas untuk memuat kehadiran</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Sinkron otomatis dengan absensi harian kelas.</small>
                                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold" id="btnInsertAttendanceToNotes" style="font-size: 0.78rem; display: none;">
                                    <i class="bi bi-clipboard-plus me-1"></i> Sisipkan ke Catatan
                                </button>
                            </div>
                        </div>

                        <!-- ═══ CATATAN REALISASI PEMBELAJARAN ═══ -->
                        <div class="col-12 mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <label class="form-label fw-bold mb-0">
                                    <span>Catatan Realisasi Pembelajaran (Aktual)</span>
                                </label>
                                <div class="d-flex align-items-center flex-wrap gap-1" id="reflectionAssistantBar">
                                    <span class="small text-muted me-1"><i class="bi bi-magic text-primary me-1"></i>Bantu Refleksi:</span>
                                    <div id="reflectionPillsContainer" class="d-inline-flex flex-wrap gap-1">
                                        <!-- Diisi dinamis oleh JS berdasarkan status_realisasi -->
                                    </div>
                                </div>
                            </div>
                            <textarea name="notes" id="notesTextarea" class="form-control" rows="5" placeholder="Contoh: Menyampaikan materi tentang..., siswa aktif bertanya mengenai..., evaluasi dilakukan dengan..." required><?= $journal['notes'] ?? '' ?></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap">
                                <small class="text-muted"><i class="bi bi-pencil me-1"></i>Catatan bersifat bebas dan fleksibel. Anda dapat mengedit, menambah, atau mengurangi teks catatan kapan saja.</small>
                                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0" id="btnClearNotes" style="font-size: 0.78rem;">
                                    <i class="bi bi-eraser me-1"></i> Bersihkan Catatan
                                </button>
                            </div>
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


<!-- ═══ MODAL TAHAP 3: PRESENSI CEPAT SISWA ═══ -->
<div class="modal fade" id="modalQuickAttendance" tabindex="-1" aria-labelledby="modalQuickAttendanceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="modalQuickAttendanceLabel">
                    <i class="bi bi-people me-1 text-primary"></i> Presensi Siswa Sesi Mengajar
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div id="quickAttModalMeta" class="small fw-semibold text-secondary">
                        Memuat data siswa...
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnMarkAllPresent">
                            <i class="bi bi-check-all me-1"></i> Semua Hadir (H)
                        </button>
                    </div>
                </div>

                <div id="quickAttLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="small text-muted ms-2">Memuat daftar siswa...</span>
                </div>

                <div class="table-responsive border rounded" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0" id="quickAttendanceTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Nama Siswa</th>
                                <th style="width: 50px;" class="text-center">L/P</th>
                                <th style="width: 280px;" class="text-center">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody id="quickAttendanceTbody">
                            <!-- Diisi oleh JS -->
                        </tbody>
                    </table>
                </div>

                <div id="quickAttAlert" class="alert alert-success mt-2 py-1 px-2 small" style="display: none;"></div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSaveAttendanceOnly">
                        <i class="bi bi-save me-1"></i> Simpan Saja
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="btnSaveAttendanceAndInsert">
                        <i class="bi bi-save me-1"></i> Simpan & Sisipkan ke Catatan
                    </button>
                </div>
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
        const dateInput = document.querySelector('input[name="date"]');
        const journalDate = dateInput ? dateInput.value : '<?= $today ?? date('Y-m-d') ?>';

        if (classId && subjectId) {
            fetch(`${atpBaseUrl}?class_id=${classId}&subject_id=${subjectId}&date=${encodeURIComponent(journalDate)}`)
                .then(r => r.json())
                .then(data => {
                    atpSelect.innerHTML = '<option value="">-- Pilih Materi --</option>';
                    let targetPromesAtpId = '';
                    data.forEach(atp => {
                        const opt = document.createElement('option');
                        opt.value = atp.id;
                        const promesPrefix = atp.is_promes_target ? '⭐ ' : '';
                        const promesBadge = atp.promes_badge ? ` [${atp.promes_badge}]` : '';
                        opt.textContent = `${promesPrefix}Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})${promesBadge}`;
                        if (atp.is_promes_target && !targetPromesAtpId) {
                            targetPromesAtpId = String(atp.id);
                        }
                        if (String(atp.id) === String(currentAtp)) {
                            opt.selected = true;
                        }
                        atpSelect.appendChild(opt);
                    });
                    if (!atpSelect.value && targetPromesAtpId) {
                        atpSelect.value = targetPromesAtpId;
                    }
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
        const requestedAtpId = '<?= $selected_atp ?? '' ?>';

        function loadAtps() {
            const classId = classSelect.value;
            const subjectId = subjectSelect.value;
            const dateInput = document.querySelector('input[name="date"]');
            const journalDate = dateInput ? dateInput.value : '<?= $today ?? date('Y-m-d') ?>';

            if (classId && subjectId) {
                fetch(`<?= base_url('admin/teaching-journal/get-atps') ?>?class_id=${classId}&subject_id=${subjectId}&date=${encodeURIComponent(journalDate)}`)
                    .then(response => response.json())
                    .then(data => {
                        atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
                        let autoSelectId = currentAtpId || requestedAtpId || '';
                        let targetPromesAtpId = '';

                        data.forEach(atp => {
                            const option = document.createElement('option');
                            option.value = atp.id;
                            const promesPrefix = atp.is_promes_target ? '⭐ ' : '';
                            const promesBadge = atp.promes_badge ? ` [${atp.promes_badge}]` : '';
                            option.textContent = `${promesPrefix}Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})${promesBadge}`;

                            if (atp.is_promes_target && !targetPromesAtpId) {
                                targetPromesAtpId = String(atp.id);
                            }

                            if (String(atp.id) === String(autoSelectId)) {
                                option.selected = true;
                            }
                            atpSelect.appendChild(option);
                        });

                        // Jika belum ada yang terpilih dan ada target Promes minggu ini, pilih target Promes
                        if (!atpSelect.value && targetPromesAtpId) {
                            atpSelect.value = targetPromesAtpId;
                        }

                        // Jika ATP sudah terpilih secara otomatis, segarkan modul yang cocok
                        if (atpSelect.value) {
                            loadModulList();
                        }
                    })
                    .catch(error => console.error('Error loading ATPs:', error));
            } else {
                atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
            }
        }

        classSelect.addEventListener('change', loadAtps);
        subjectSelect.addEventListener('change', loadAtps);
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput) {
            dateInput.addEventListener('change', loadAtps);
        }

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
                // Jika server mengirimkan ID modul yang cocok dari pencocokan ATP / Lingkup Materi
                if (!matchedModul && data.matched_modul_id) {
                    matchedModul = data.moduls.find(m => String(m.id) === String(data.matched_modul_id));
                }
                if (!matchedModul && atpId && data.moduls.length > 0) {
                    matchedModul = data.moduls.find(m => String(m.atp_id) === String(atpId)) || data.moduls[0];
                }
                if (!matchedModul && data.moduls.length === 1 && !savedModulId) {
                    matchedModul = data.moduls[0];
                }

                if (matchedModul) {
                    modulSelect.value = matchedModul.id;
                    if (hiddenModulInput) hiddenModulInput.value = matchedModul.id;
                    loadModulMeetings(matchedModul.id, savedPertemuanKe);
                } else if (!savedModulId) {
                    if (hiddenModulInput) hiddenModulInput.value = '';
                    if (hiddenPertemuanInput) hiddenPertemuanInput.value = '';
                    loadModulMeetings(null);
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
            loadModulList();
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

    const csrfTokenName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    // ══════════════════════════════════════════════════════════════
    // TAHAP 3: Presensi Cepat Siswa & Auto-Fill Refleksi Realisasi
    // ══════════════════════════════════════════════════════════════
    const attWidget = document.getElementById('attendanceSummaryWidget');
    const attSummaryText = document.getElementById('attSummaryText');
    const btnInsertAtt = document.getElementById('btnInsertAttendanceToNotes');
    const btnOpenQuickAtt = document.getElementById('btnOpenQuickAttendance');
    const modalQuickAttEl = document.getElementById('modalQuickAttendance');
    const quickAttMeta = document.getElementById('quickAttModalMeta');
    const quickAttTbody = document.getElementById('quickAttendanceTbody');
    const quickAttLoading = document.getElementById('quickAttLoading');
    const quickAttAlert = document.getElementById('quickAttAlert');
    const btnMarkAllPresent = document.getElementById('btnMarkAllPresent');
    const btnSaveAttOnly = document.getElementById('btnSaveAttendanceOnly');
    const btnSaveAttAndInsert = document.getElementById('btnSaveAttendanceAndInsert');
    const statusRealisasiSelect = document.getElementById('status_realisasi');
    const reflectionContainer = document.getElementById('reflectionPillsContainer');
    const btnClearNotes = document.getElementById('btnClearNotes');

    let quickAttModal = null;
    let currentAttendanceData = null;

    const getAttUrl = '<?= base_url('admin/teaching-journal/get-class-attendance') ?>';
    const saveAttUrl = '<?= base_url('admin/teaching-journal/save-quick-attendance') ?>';
    const getSuggestionsUrl = '<?= base_url('admin/teaching-journal/get-reflection-suggestions') ?>';

    function getJournalDate() {
        const dateInput = document.querySelector('input[name="date"]');
        return dateInput ? dateInput.value : '<?= $today ?? date('Y-m-d') ?>';
    }

    function loadClassAttendance() {
        if (!classSelect || !attSummaryText) return;
        const classId = classSelect.value;
        const date = getJournalDate();

        if (!classId) {
            attSummaryText.innerHTML = '<span class="text-muted"><i class="bi bi-info-circle me-1"></i> Pilih kelas untuk memuat kehadiran</span>';
            if (btnInsertAtt) btnInsertAtt.style.display = 'none';
            currentAttendanceData = null;
            return;
        }

        attSummaryText.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-1"></span> <span class="text-muted">Memuat kehadiran...</span>';

        fetch(`${getAttUrl}?class_id=${encodeURIComponent(classId)}&date=${encodeURIComponent(date)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.summary) {
                    attSummaryText.innerHTML = '<span class="text-muted">Presensi belum tersedia.</span>';
                    if (btnInsertAtt) btnInsertAtt.style.display = 'none';
                    return;
                }

                currentAttendanceData = data;
                renderAttendanceSummaryWidget(data.summary);
            })
            .catch(err => {
                console.error('Error loading attendance:', err);
                attSummaryText.innerHTML = '<span class="text-danger small">Gagal memuat absensi.</span>';
            });
    }

    function renderAttendanceSummaryWidget(summary) {
        if (!attSummaryText) return;
        if (summary.total === 0) {
            attSummaryText.innerHTML = '<span class="text-muted small">Belum ada siswa terdaftar di kelas ini.</span>';
            if (btnInsertAtt) btnInsertAtt.style.display = 'none';
            return;
        }

        let badges = `<span class="badge bg-secondary me-1">${summary.total} Siswa</span>`;
        badges += `<span class="badge bg-success me-1">${summary.hadir} Hadir</span>`;
        if (summary.sakit > 0) badges += `<span class="badge bg-warning text-dark me-1">${summary.sakit} Sakit</span>`;
        if (summary.izin > 0) badges += `<span class="badge bg-info text-dark me-1">${summary.izin} Izin</span>`;
        if (summary.alpa > 0) badges += `<span class="badge bg-danger me-1">${summary.alpa} Alpa</span>`;

        attSummaryText.innerHTML = badges;
        if (btnInsertAtt) {
            btnInsertAtt.style.display = 'inline-block';
        }
    }

    function insertAttendanceToNotes() {
        if (!currentAttendanceData || !currentAttendanceData.attendance_text || !notesTextarea) return;

        const textToInsert = currentAttendanceData.attendance_text;
        const currentVal = notesTextarea.value.trim();

        if (currentVal.indexOf('Kehadiran:') !== -1) {
            // Sudah ada info kehadiran sebelumnya, ganti baris kehadiran
            notesTextarea.value = currentVal.replace(/Kehadiran: [^\n\r]+/i, textToInsert);
        } else if (currentVal.length > 0) {
            notesTextarea.value = currentVal + '\n\n' + textToInsert;
        } else {
            notesTextarea.value = textToInsert;
        }

        notesTextarea.classList.add('is-valid');
        setTimeout(() => notesTextarea.classList.remove('is-valid'), 1200);
    }

    if (btnInsertAtt) {
        btnInsertAtt.addEventListener('click', insertAttendanceToNotes);
    }

    if (btnOpenQuickAtt) {
        btnOpenQuickAtt.addEventListener('click', function() {
            const classId = classSelect ? classSelect.value : '';
            const date = getJournalDate();

            if (!classId) {
                alert('Pilih kelas terlebih dahulu.');
                return;
            }

            if (window.bootstrap && bootstrap.Modal && modalQuickAttEl) {
                if (!quickAttModal) {
                    quickAttModal = new bootstrap.Modal(modalQuickAttEl);
                }
                quickAttModal.show();
            }

            const className = classSelect.options[classSelect.selectedIndex] ? classSelect.options[classSelect.selectedIndex].text : 'Kelas';
            if (quickAttMeta) {
                quickAttMeta.innerHTML = `<strong>${escapeHtml(className)}</strong> &bull; Tanggal: ${escapeHtml(date)}`;
            }

            if (quickAttAlert) quickAttAlert.style.display = 'none';
            if (quickAttLoading) quickAttLoading.style.display = 'block';
            if (quickAttTbody) quickAttTbody.innerHTML = '';

            fetch(`${getAttUrl}?class_id=${encodeURIComponent(classId)}&date=${encodeURIComponent(date)}`)
                .then(r => r.json())
                .then(data => {
                    if (quickAttLoading) quickAttLoading.style.display = 'none';
                    if (!data.success || !data.students) {
                        alert(data.message || 'Gagal memuat siswa.');
                        return;
                    }
                    currentAttendanceData = data;
                    renderQuickAttendanceTable(data.students);
                })
                .catch(err => {
                    if (quickAttLoading) quickAttLoading.style.display = 'none';
                    console.error('Error fetching students for quick attendance:', err);
                });
        });
    }

    function renderQuickAttendanceTable(students) {
        if (!quickAttTbody) return;
        if (students.length === 0) {
            quickAttTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data siswa aktif di kelas ini.</td></tr>';
            return;
        }

        let html = '';
        students.forEach((s, idx) => {
            const st = (s.status || 'H').toUpperCase();
            html += `
                <tr>
                    <td class="text-center small">${idx + 1}</td>
                    <td>
                        <div class="fw-semibold small">${escapeHtml(s.name)}</div>
                        ${s.nisn ? `<small class="text-muted" style="font-size:0.75rem;">NISN: ${escapeHtml(s.nisn)}</small>` : ''}
                    </td>
                    <td class="text-center small">${escapeHtml(s.gender || '-')}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check att-radio" name="att_st_${s.id}" id="att_h_${s.id}" value="H" ${st === 'H' ? 'checked' : ''} data-student-id="${s.id}">
                            <label class="btn btn-outline-success py-0" for="att_h_${s.id}">Hadir</label>

                            <input type="radio" class="btn-check att-radio" name="att_st_${s.id}" id="att_s_${s.id}" value="S" ${st === 'S' ? 'checked' : ''} data-student-id="${s.id}">
                            <label class="btn btn-outline-warning py-0 text-dark" for="att_s_${s.id}">Sakit</label>

                            <input type="radio" class="btn-check att-radio" name="att_st_${s.id}" id="att_i_${s.id}" value="I" ${st === 'I' ? 'checked' : ''} data-student-id="${s.id}">
                            <label class="btn btn-outline-info py-0 text-dark" for="att_i_${s.id}">Izin</label>

                            <input type="radio" class="btn-check att-radio" name="att_st_${s.id}" id="att_a_${s.id}" value="A" ${st === 'A' ? 'checked' : ''} data-student-id="${s.id}">
                            <label class="btn btn-outline-danger py-0" for="att_a_${s.id}">Alpa</label>
                        </div>
                    </td>
                </tr>
            `;
        });
        quickAttTbody.innerHTML = html;
    }

    if (btnMarkAllPresent) {
        btnMarkAllPresent.addEventListener('click', function() {
            document.querySelectorAll('#quickAttendanceTbody input[value="H"]').forEach(radio => {
                radio.checked = true;
            });
        });
    }

    function saveQuickAttendance(shouldInsertToNotes = false) {
        const classId = classSelect ? classSelect.value : '';
        const date = getJournalDate();
        if (!classId) return;

        const statusMap = {};
        document.querySelectorAll('#quickAttendanceTbody input[type="radio"]:checked').forEach(radio => {
            const sId = radio.dataset.studentId;
            if (sId) {
                statusMap[sId] = radio.value;
            }
        });

        const activeBtn = shouldInsertToNotes ? btnSaveAttAndInsert : btnSaveAttOnly;
        if (activeBtn) {
            activeBtn.disabled = true;
            activeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        }

        fetch(saveAttUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfHash
            },
            body: JSON.stringify({
                class_id: classId,
                date: date,
                status: statusMap,
                [csrfTokenName]: csrfHash
            })
        })
        .then(r => r.json())
        .then(result => {
            if (activeBtn) {
                activeBtn.disabled = false;
                activeBtn.innerHTML = shouldInsertToNotes ?
                    '<i class="bi bi-save me-1"></i> Simpan & Sisipkan ke Catatan' :
                    '<i class="bi bi-save me-1"></i> Simpan Saja';
            }

            if (result.csrf) csrfHash = result.csrf;

            if (result.success) {
                currentAttendanceData = result;
                renderAttendanceSummaryWidget(result.summary);

                if (quickAttAlert) {
                    quickAttAlert.className = 'alert alert-success mt-2 py-1 px-2 small';
                    quickAttAlert.innerHTML = '<i class="bi bi-check-circle me-1"></i> Presensi berhasil disimpan!';
                    quickAttAlert.style.display = 'block';
                }

                if (shouldInsertToNotes) {
                    insertAttendanceToNotes();
                }

                setTimeout(() => {
                    if (quickAttModal) quickAttModal.hide();
                }, 700);
            } else {
                alert(result.message || 'Gagal menyimpan presensi.');
            }
        })
        .catch(err => {
            console.error('Error saving attendance:', err);
            if (activeBtn) {
                activeBtn.disabled = false;
                activeBtn.innerHTML = 'Simpan';
            }
            alert('Terjadi kesalahan koneksi saat menyimpan presensi.');
        });
    }

    if (btnSaveAttOnly) {
        btnSaveAttOnly.addEventListener('click', () => saveQuickAttendance(false));
    }
    if (btnSaveAttAndInsert) {
        btnSaveAttAndInsert.addEventListener('click', () => saveQuickAttendance(true));
    }

    // ─── Asisten Saran Refleksi Berdasarkan Status Realisasi ───
    function updateReflectionSuggestions() {
        if (!statusRealisasiSelect || !reflectionContainer) return;
        const status = statusRealisasiSelect.value;
        let materiTitle = '';
        if (modulSelect && modulSelect.options[modulSelect.selectedIndex]) {
            materiTitle = modulSelect.options[modulSelect.selectedIndex].text;
        }

        fetch(`${getSuggestionsUrl}?status=${encodeURIComponent(status)}&materi=${encodeURIComponent(materiTitle)}`)
            .then(r => r.json())
            .then(data => {
                reflectionContainer.innerHTML = '';
                if (!data.success || !data.suggestions || data.suggestions.length === 0) return;

                data.suggestions.forEach(sug => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small';
                    btn.style.fontSize = '0.75rem';
                    btn.textContent = `+ ${sug.label}`;
                    btn.title = sug.text;

                    btn.addEventListener('click', function() {
                        if (!notesTextarea) return;
                        const cur = notesTextarea.value.trim();
                        if (cur.length === 0) {
                            notesTextarea.value = sug.text;
                        } else {
                            const conf = confirm('Tambahkan saran refleksi ini ke catatan yang sudah ada?');
                            if (conf) {
                                notesTextarea.value = cur + '\n\n' + sug.text;
                            }
                        }
                        notesTextarea.focus();
                        notesTextarea.classList.add('is-valid');
                        setTimeout(() => notesTextarea.classList.remove('is-valid'), 1200);
                    });

                    reflectionContainer.appendChild(btn);
                });
            })
            .catch(err => console.error('Error getting reflection suggestions:', err));
    }

    if (statusRealisasiSelect) {
        statusRealisasiSelect.addEventListener('change', updateReflectionSuggestions);
    }

    if (btnClearNotes) {
        btnClearNotes.addEventListener('click', function() {
            if (!notesTextarea) return;
            if (notesTextarea.value.trim().length > 0) {
                if (confirm('Kosongkan catatan realisasi pembelajaran?')) {
                    notesTextarea.value = '';
                    notesTextarea.focus();
                }
            }
        });
    }

    // Event listener reload attendance saat class atau tanggal berubah
    if (classSelect) {
        classSelect.addEventListener('change', loadClassAttendance);
    }
    const dateInputEl = document.querySelector('input[name="date"]');
    if (dateInputEl) {
        dateInputEl.addEventListener('change', loadClassAttendance);
    }

    // Terapkan Sesi Terjadwal dari Tombol Asisten
    document.querySelectorAll('.btn-apply-session').forEach(btn => {
        btn.addEventListener('click', function() {
            const classId = this.dataset.classId;
            const subjectId = this.dataset.subjectId;
            const atpId = this.dataset.atpId;

            if (classSelect && classId) {
                classSelect.value = classId;
                classSelect.dispatchEvent(new Event('change'));
            }

            setTimeout(() => {
                const singleSubj = document.getElementById('subject_id');
                if (singleSubj && subjectId) {
                    singleSubj.value = subjectId;
                    singleSubj.dispatchEvent(new Event('change'));
                }

                const firstSubj = document.querySelector('.subject-entry .subject-select');
                if (firstSubj && subjectId) {
                    firstSubj.value = subjectId;
                    firstSubj.dispatchEvent(new Event('change'));
                }

                if (atpId) {
                    setTimeout(() => {
                        const singleAtp = document.getElementById('atp_id');
                        if (singleAtp) {
                            singleAtp.value = atpId;
                            singleAtp.dispatchEvent(new Event('change'));
                        }
                        const firstAtp = document.querySelector('.subject-entry .atp-select');
                        if (firstAtp) {
                            firstAtp.value = atpId;
                            firstAtp.dispatchEvent(new Event('change'));
                        }
                    }, 400);
                }
            }, 250);
        });
    });

    setTimeout(() => {
        if (classSelect && classSelect.value) {
            loadClassAttendance();
            if (getActiveSubjectId()) {
                loadModulList();
            }
        }
        updateReflectionSuggestions();
    }, 300);
});
</script>

<?= $this->endSection() ?>
