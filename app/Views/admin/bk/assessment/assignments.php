<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<!-- Select2 Bootstrap 5 Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 44px;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        padding: 4px 6px;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection--multiple,
    .select2-container--bootstrap-5.select2-container--open .select2-selection--multiple {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        z-index: 2050 !important;
        border-radius: 0.5rem;
        border-color: #ced4da;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background-color: #e7f1ff;
        border: 1px solid #b6d4fe;
        color: #084298;
        font-weight: 500;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="h3 mb-1 text-gray-800">📋 Penugasan Asesmen BK</h1>
            <p class="text-muted mb-0">Distribusikan instrumen asesmen ke kelas atau siswa tertentu</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPenugasan">
                <i class="fas fa-plus"></i> Buat Penugasan Baru
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Instrumen</th>
                            <th>Target Sasaran</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width: 210px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($assignments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada penugasan asesmen.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($assignments as $assign): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= esc($assign['title']) ?></div>
                                    <small class="text-muted"><?= esc($assign['instrument_title']) ?></small>
                                </td>
                                <td>
                                    <?php if($assign['target_type'] == 'class'): ?>
                                        <?php 
                                            $cNames = [];
                                            if (!empty($assign['notes'])) {
                                                $pNotes = json_decode($assign['notes'], true);
                                                if (!empty($pNotes['class_names']) && is_array($pNotes['class_names'])) {
                                                    $cNames = $pNotes['class_names'];
                                                }
                                            }
                                            if (empty($cNames) && !empty($assign['class_name'])) {
                                                $cNames = [$assign['class_name']];
                                            }
                                        ?>
                                        <?php if (count($cNames) > 1): ?>
                                            <span class="badge bg-primary px-3 py-2 rounded-pill" title="<?= esc(implode(', ', $cNames)) ?>">
                                                <i class="bi bi-mortarboard me-1"></i> <?= count($cNames) ?> Kelas: <?= esc(implode(', ', array_slice($cNames, 0, 2))) ?><?= count($cNames) > 2 ? '...' : '' ?>
                                            </span>
                                        <?php elseif (count($cNames) === 1): ?>
                                            <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                <i class="bi bi-mortarboard me-1"></i> Kelas <?= esc($cNames[0]) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary px-3 py-2 rounded-pill">Kelas Tertentu</span>
                                        <?php endif; ?>
                                    <?php elseif($assign['target_type'] == 'level'): ?>
                                        <span class="badge bg-info px-3 py-2 rounded-pill">Satu Jenjang</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Semua Siswa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($assign['start_date'])) ?> 
                                    - 
                                    <?= date('d M Y', strtotime($assign['end_date'])) ?>
                                </td>
                                <td>
                                    <?php if(strtotime($assign['end_date']) < time()): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php elseif($assign['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(ucfirst($assign['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a href="<?= base_url('admin/bk/pemetaan/penugasan/progress/' . $assign['id']) ?>" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" title="Lihat Progress Siswa">
                                            <i class="bi bi-bar-chart-line me-1"></i> Progress
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="openEditModal(<?= htmlspecialchars(json_encode($assign), ENT_QUOTES, 'UTF-8') ?>)" title="Edit Penugasan">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="confirmDelete(<?= $assign['id'] ?>, '<?= esc($assign['title'], 'js') ?>')" title="Hapus Penugasan">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buat Penugasan -->
<div class="modal fade" id="modalPenugasan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= base_url('admin/bk/pemetaan/penugasan/store') ?>" method="POST" id="formPenugasan">
                <?= csrf_field() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-primary"><i class="bi bi-send-check me-2"></i>Buat Penugasan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instrumen Asesmen <span class="text-danger">*</span></label>
                        <select name="instrument_id" class="form-select" required>
                            <option value="">-- Pilih Instrumen (Hanya yang berstatus Published) --</option>
                            <?php foreach($instruments as $ins): ?>
                                <option value="<?= $ins['id'] ?>"><?= esc($ins['title']) ?> (<?= esc($ins['target_level']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Penugasan (Opsional)</label>
                        <input type="text" name="title" class="form-control" placeholder="Biarkan kosong untuk otomatis memakai nama instrumen + kelas">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Batas Akhir (End Date) <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Sasaran <span class="text-danger">*</span></label>
                        <select name="target_type" id="target_type" class="form-select" onchange="toggleTargetType()" required>
                            <option value="class">Kelas Tertentu (Bisa Pilih Lebih Dari Satu Kelas)</option>
                            <option value="all">Semua Siswa di Tahun Ajaran Aktif</option>
                        </select>
                    </div>

                    <div class="mb-3" id="class_selector">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="form-label fw-semibold mb-0">
                                Pilih Kelas Sasaran <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAllClasses()">
                                    <i class="bi bi-check-all me-1"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetClasses()">
                                    <i class="bi bi-x me-1"></i> Reset
                                </button>
                            </div>
                        </div>

                        <?php 
                        // Kelompokkan kelas berdasarkan tingkat (level)
                        $groupedClasses = [];
                        if (!empty($classes)) {
                            foreach ($classes as $c) {
                                $lvl = !empty($c['level']) ? 'Tingkat ' . $c['level'] : 'Lainnya';
                                $groupedClasses[$lvl][] = $c;
                            }
                        }
                        ?>

                        <select name="target_ids[]" id="select_target_classes" class="form-select select2-classes" multiple="multiple" style="width: 100%;" data-placeholder="-- Cari dan pilih kelas sasaran --">
                            <?php if (empty($groupedClasses)): ?>
                                <option disabled>Tidak ada kelas aktif</option>
                            <?php else: ?>
                                <?php foreach ($groupedClasses as $lvl => $classList): ?>
                                    <optgroup label="<?= esc($lvl) ?>">
                                        <?php foreach ($classList as $c): ?>
                                            <option value="<?= $c['id'] ?>">
                                                <?= esc($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="d-flex justify-content-between align-items-center mt-2 small">
                            <span class="text-primary fw-semibold" id="selectedCountText">0 kelas dipilih</span>
                            <span class="text-muted"><i class="bi bi-info-circle me-1"></i>Ketik untuk mencari, klik untuk memilih banyak kelas</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Distribusikan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Penugasan -->
<div class="modal fade" id="modalEditPenugasan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST" id="formEditPenugasan">
                <?= csrf_field() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-dark"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Penugasan Asesmen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instrumen Asesmen</label>
                        <div class="p-2 bg-light rounded border text-muted fw-semibold">
                            <i class="bi bi-journal-check text-primary me-2"></i><span id="edit_instrument_name">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Penugasan <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required placeholder="Judul penugasan">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Status Penugasan <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active (Aktif)</option>
                                <option value="closed">Closed (Ditutup)</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Batas Akhir (End Date) <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Sasaran <span class="text-danger">*</span></label>
                        <select name="target_type" id="edit_target_type" class="form-select" onchange="toggleEditTargetType()" required>
                            <option value="class">Kelas Tertentu (Bisa Pilih Lebih Dari Satu Kelas)</option>
                            <option value="all">Semua Siswa di Tahun Ajaran Aktif</option>
                        </select>
                    </div>

                    <div class="mb-3" id="edit_class_selector">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="form-label fw-semibold mb-0">
                                Pilih Kelas Sasaran <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAllEditClasses()">
                                    <i class="bi bi-check-all me-1"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetEditClasses()">
                                    <i class="bi bi-x me-1"></i> Reset
                                </button>
                            </div>
                        </div>

                        <select name="target_ids[]" id="edit_select_target_classes" class="form-select select2-classes-edit" multiple="multiple" style="width: 100%;" data-placeholder="-- Cari dan pilih kelas sasaran --">
                            <?php if (empty($groupedClasses)): ?>
                                <option disabled>Tidak ada kelas aktif</option>
                            <?php else: ?>
                                <?php foreach ($groupedClasses as $lvl => $classList): ?>
                                    <optgroup label="<?= esc($lvl) ?>">
                                        <?php foreach ($classList as $c): ?>
                                            <option value="<?= $c['id'] ?>">
                                                <?= esc($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="d-flex justify-content-between align-items-center mt-2 small">
                            <span class="text-primary fw-semibold" id="editSelectedCountText">0 kelas dipilih</span>
                            <span class="text-muted"><i class="bi bi-info-circle me-1"></i>Ketik untuk mencari, klik untuk memilih banyak kelas</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 text-dark fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Penugasan -->
<div class="modal fade" id="modalDeletePenugasan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST" id="formDeletePenugasan">
                <?= csrf_field() ?>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Penugasan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3 fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Hapus Penugasan Ini?</h6>
                    <p class="text-muted mb-3" id="delete_assign_text">Apakah Anda yakin ingin menghapus penugasan ini?</p>
                    <div class="alert alert-warning py-2 small text-start mb-0">
                        <i class="bi bi-info-circle me-1"></i> <strong>Perhatian:</strong> Menghapus penugasan akan menghapus seluruh data pengerjaan siswa yang terkait dengan penugasan ini.
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Ya, Hapus Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            // Select2 untuk Modal Tambah Penugasan
            $('#select_target_classes').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalPenugasan'),
                placeholder: '-- Cari & pilih satu atau lebih kelas sasaran --',
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            }).on('change', function() {
                updateSelectedCount();
            });

            // Select2 untuk Modal Edit Penugasan
            $('#edit_select_target_classes').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditPenugasan'),
                placeholder: '-- Cari & pilih satu atau lebih kelas sasaran --',
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            }).on('change', function() {
                updateEditSelectedCount();
            });
        }

        // Trigger change.select2 saat modal ditampilkan agar lebar tepat 100%
        $('#modalPenugasan').on('shown.bs.modal', function () {
            $('#select_target_classes').trigger('change.select2');
        });
        $('#modalPenugasan').on('hidden.bs.modal', function () {
            $('#select_target_classes').val(null).trigger('change');
        });

        $('#modalEditPenugasan').on('shown.bs.modal', function () {
            $('#edit_select_target_classes').trigger('change.select2');
        });
    });

    function toggleTargetType() {
        const type = document.getElementById('target_type').value;
        const classSelector = document.getElementById('class_selector');
        
        if (type === 'class') {
            classSelector.style.display = 'block';
            $('#select_target_classes').trigger('change.select2');
        } else {
            classSelector.style.display = 'none';
        }
    }

    function selectAllClasses() {
        const allVals = [];
        $('#select_target_classes option').each(function() {
            if ($(this).val()) {
                allVals.push($(this).val());
            }
        });
        $('#select_target_classes').val(allVals).trigger('change');
    }

    function resetClasses() {
        $('#select_target_classes').val(null).trigger('change');
    }

    function updateSelectedCount() {
        const selected = $('#select_target_classes').val() || [];
        const countText = document.getElementById('selectedCountText');
        if (countText) {
            countText.innerText = selected.length + ' kelas dipilih';
        }
    }

    const formPenugasan = document.getElementById('formPenugasan');
    if (formPenugasan) {
        formPenugasan.addEventListener('submit', function(e) {
            const type = document.getElementById('target_type').value;
            if (type === 'class') {
                const selected = $('#select_target_classes').val() || [];
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Silakan pilih minimal satu kelas sasaran penugasan.');
                    return false;
                }
            }
        });
    }

    // --- EDIT & DELETE HANDLERS ---

    function openEditModal(assign) {
        const form = document.getElementById('formEditPenugasan');
        form.action = '<?= base_url('admin/bk/pemetaan/penugasan/update') ?>/' + assign.id;

        document.getElementById('edit_instrument_name').innerText = assign.instrument_title || '-';
        document.getElementById('edit_title').value = assign.title || '';
        document.getElementById('edit_status').value = assign.status || 'active';
        document.getElementById('edit_start_date').value = assign.start_date || '';
        document.getElementById('edit_end_date').value = assign.end_date || '';
        document.getElementById('edit_target_type').value = assign.target_type || 'class';

        toggleEditTargetType();

        let selectedClassIds = [];
        if (assign.notes) {
            try {
                let parsed = JSON.parse(assign.notes);
                if (parsed && Array.isArray(parsed.class_ids)) {
                    selectedClassIds = parsed.class_ids.map(String);
                }
            } catch(e) {}
        }
        if (selectedClassIds.length === 0 && assign.target_id) {
            selectedClassIds.push(String(assign.target_id));
        }

        $('#edit_select_target_classes').val(selectedClassIds).trigger('change');
        updateEditSelectedCount();

        new bootstrap.Modal(document.getElementById('modalEditPenugasan')).show();
    }

    function toggleEditTargetType() {
        const type = document.getElementById('edit_target_type').value;
        const classSelector = document.getElementById('edit_class_selector');
        if (type === 'class') {
            classSelector.style.display = 'block';
            $('#edit_select_target_classes').trigger('change.select2');
        } else {
            classSelector.style.display = 'none';
        }
    }

    function selectAllEditClasses() {
        const allVals = [];
        $('#edit_select_target_classes option').each(function() {
            if ($(this).val()) {
                allVals.push($(this).val());
            }
        });
        $('#edit_select_target_classes').val(allVals).trigger('change');
    }

    function resetEditClasses() {
        $('#edit_select_target_classes').val(null).trigger('change');
    }

    function updateEditSelectedCount() {
        const selected = $('#edit_select_target_classes').val() || [];
        const countText = document.getElementById('editSelectedCountText');
        if (countText) {
            countText.innerText = selected.length + ' kelas dipilih';
        }
    }

    const formEditPenugasan = document.getElementById('formEditPenugasan');
    if (formEditPenugasan) {
        formEditPenugasan.addEventListener('submit', function(e) {
            const type = document.getElementById('edit_target_type').value;
            if (type === 'class') {
                const selected = $('#edit_select_target_classes').val() || [];
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Silakan pilih minimal satu kelas sasaran penugasan.');
                    return false;
                }
            }
        });
    }

    function confirmDelete(id, title) {
        const form = document.getElementById('formDeletePenugasan');
        form.action = '<?= base_url('admin/bk/pemetaan/penugasan/delete') ?>/' + id;
        document.getElementById('delete_assign_text').innerHTML = 'Apakah Anda yakin ingin menghapus penugasan <strong>"' + title + '"</strong>?';
        new bootstrap.Modal(document.getElementById('modalDeletePenugasan')).show();
    }
</script>
<?= $this->endSection() ?>
