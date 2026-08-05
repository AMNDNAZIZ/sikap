<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Exam Name page */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 12px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

.page-header h1 {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

.page-header p {
    margin: 0 0 1rem 0 !important;
    opacity: 0.95 !important;
}

.page-header-actions {
    display: flex !important;
    gap: 0.75rem !important;
    flex-wrap: wrap !important;
}

.btn-modern {
    padding: 0.625rem 1.5rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
}

.btn-action {
    padding: 0.375rem 0.75rem !important;
    font-size: 0.875rem !important;
    border-radius: 6px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    white-space: nowrap !important;
}

.btn-action:hover {
    transform: translateY(-1px) !important;
}

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-primary-gradient:hover {
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    color: white !important;
}

.btn-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.btn-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
    color: white !important;
}

.btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
}

.modern-card-body {
    padding: 1.5rem !important;
}

.alert-modern {
    border: none !important;
    border-radius: 10px !important;
    padding: 1rem 1.25rem !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 0.75rem !important;
    margin-bottom: 1.5rem !important;
}

.alert-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.alert-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.badge-modern {
    padding: 0.375rem 0.75rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.8rem !important;
}

.badge-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.table-modern thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #495057 !important;
    padding: 0.875rem !important;
}

.table-modern tbody tr {
    transition: background-color 0.2s ease !important;
}

.table-modern tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table-modern tbody td {
    padding: 0.875rem !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f3f5 !important;
}

.empty-state {
    text-align: center !important;
    padding: 2.5rem 1rem !important;
}

.empty-state-icon {
    font-size: 3.5rem !important;
    color: #dee2e6 !important;
    margin-bottom: 1rem !important;
}

.empty-state-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.empty-state-text {
    color: #6c757d !important;
    font-size: 0.9rem !important;
    margin-bottom: 1.5rem !important;
}

.modal-modern .modal-content {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 32px rgba(0,0,0,0.15) !important;
}

.modal-modern .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.25rem !important;
}

.modal-modern .modal-title {
    font-weight: 600 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.modal-modern .btn-close {
    filter: brightness(0) invert(1) !important;
}

.modal-modern .modal-body {
    padding: 1.5rem !important;
}

.modal-modern .modal-footer {
    border-top: 1px solid #e9ecef !important;
    padding: 1.25rem !important;
}

.input-group-text {
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 8px 0 0 8px !important;
}

.form-control {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.form-label {
    font-weight: 500 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-clipboard-list"></i> Nama Ujian</h1>
        <p>Kelola jenis/nama ujian untuk sistem CBT</p>
        <div class="page-header-actions">
            <button class="btn btn-modern btn-success-gradient" data-bs-toggle="modal" data-bs-target="#modalAdd">
                <i class="fas fa-plus"></i> Tambah Nama Ujian
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="modern-card">
        <div class="modern-card-body">
            <!-- Alerts -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-modern alert-success-gradient">
                    <i class="fas fa-check-circle"></i>
                    <div><?= session()->getFlashdata('success') ?></div>
                </div>
            <?php elseif (session()->getFlashdata('error')): ?>
                <div class="alert alert-modern alert-danger-gradient">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= session()->getFlashdata('error') ?></div>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama ujian...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Total: <strong><?= count($examNames) ?></strong> nama ujian
                    </span>
                </div>
            </div>

            <!-- Table -->
            <?php if (!empty($examNames)): ?>
                <div class="table-responsive">
                    <table class="table table-modern" id="examTable">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Nama Ujian</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examNames as $i => $exam): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-tag text-primary"></i>
                                        <strong><?= esc($exam['name']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-action btn-warning-gradient btn-edit" 
                                            data-id="<?= $exam['id'] ?>" 
                                            data-name="<?= esc($exam['name']) ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="<?= site_url('admin/cbt/examname/delete/' . $exam['id']) ?>"
                                       class="btn btn-action btn-danger-gradient"
                                       onclick="return confirm('Yakin hapus nama ujian ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Nama Ujian</h3>
                    <p class="empty-state-text">
                        Mulai tambahkan nama ujian seperti UTS, UAS, Ulangan Harian, dll.
                    </p>
                    <button class="btn btn-modern btn-success-gradient" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        <i class="fas fa-plus"></i> Tambah Nama Ujian Pertama
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade modal-modern" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= site_url('admin/cbt/examname/store') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Tambah Nama Ujian
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Ujian</label>
                    <input type="text" name="name" class="form-control" 
                           placeholder="Contoh: UTS, UAS, Ulangan Harian" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-modern btn-success-gradient">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-modern" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form id="formEdit" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Nama Ujian
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Nama Ujian</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-modern btn-success-gradient">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('examTable');
    
    if (table) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
});

// Edit button handler
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('formEdit').action = '<?= site_url('admin/cbt/examname/update/') ?>' + id;
    });
});
</script>

<?= $this->endSection() ?>
