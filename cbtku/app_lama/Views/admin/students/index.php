<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Students page */
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

/* Fix untuk tombol aksi - gunakan padding lebih kecil */
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

.btn-success-gradient:hover {
    box-shadow: 0 5px 15px rgba(17, 153, 142, 0.3) !important;
    color: white !important;
}

.btn-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
}

.btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.btn-warning-gradient {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
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

.alert-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
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
    border: none !important;
}

.badge-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
    border: none !important;
}

.badge-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
    border: none !important;
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

.required-mark {
    color: #dc3545 !important;
}

.pagination {
    margin-top: 1.5rem !important;
    justify-content: center !important;
}

.pagination .page-link {
    border: 1px solid #dee2e6 !important;
    color: #667eea !important;
    padding: 0.5rem 0.875rem !important;
    margin: 0 0.25rem !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
}

.pagination .page-link:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-color: #667eea !important;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-color: #667eea !important;
    color: white !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-user-graduate"></i> Manajemen Siswa</h1>
        <p>Kelola data siswa dan akun pengguna untuk sistem CBT</p>
        <div class="page-header-actions">
            <a href="<?= base_url('admin/students/create') ?>" class="btn btn-modern btn-primary-gradient">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
            <button type="button" class="btn btn-modern btn-success-gradient" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-import"></i> Impor Siswa
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

            <?php if (session()->getFlashdata('import_success')): ?>
                <div class="alert alert-modern alert-success-gradient">
                    <div>
                        <div class="fw-bold mb-2">
                            <i class="fas fa-check-circle"></i> Berhasil diimpor:
                        </div>
                        <ul class="mb-0">
                            <?php foreach (session()->getFlashdata('import_success') as $s): ?>
                                <li><?= esc($s) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('import_failed')): ?>
                <div class="alert alert-modern alert-danger-gradient">
                    <div>
                        <div class="fw-bold mb-2">
                            <i class="fas fa-exclamation-triangle"></i> Gagal diimpor:
                        </div>
                        <ul class="mb-0">
                            <?php foreach (session()->getFlashdata('import_failed') as $f): ?>
                                <li><?= esc($f) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <form method="get" class="row g-3 mb-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari nama / NIS / Username..."
                               value="<?= esc($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-modern btn-primary-gradient w-100">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?= base_url('admin/students') ?>" class="btn btn-modern btn-secondary w-100">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-muted">
                        Total: <strong><?= count($students) ?></strong> siswa
                    </span>
                </div>
            </form>

            <!-- Table -->
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th width="60">L/P</th>
                                <th>Agama</th>
                                <th>Ruang</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($students as $student): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <span class="badge badge-modern badge-primary-gradient">
                                        <?= esc($student['nis']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-user-circle text-primary"></i>
                                        <strong><?= esc($student['name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($student['class_name'])): ?>
                                        <span class="badge badge-modern badge-info-gradient">
                                            <?= esc($student['class_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($student['username']) ?></td>
                                <td>
                                    <code class="text-muted"><?= esc($student['plain_password']) ?></code>
                                </td>
                                <td class="text-center">
                                    <?php if ($student['gender'] === 'L'): ?>
                                        <i class="fas fa-mars text-primary" title="Laki-laki"></i>
                                    <?php else: ?>
                                        <i class="fas fa-venus text-danger" title="Perempuan"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($student['religion']) ?></td>
                                <td><?= esc($student['room']) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('admin/students/edit/'.$student['id']) ?>" 
                                       class="btn btn-action btn-warning-gradient"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?= base_url('admin/students/delete/'.$student['id']) ?>" 
                                          method="post" class="d-inline" 
                                          onsubmit="return confirm('Yakin ingin menghapus siswa ini?\n\nData yang akan dihapus:\n- Akun siswa\n- Riwayat ujian\n- Nilai ujian\n\nTindakan ini tidak dapat dibatalkan!')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-action btn-danger-gradient" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    <?= $pager->links('default', 'bootstrap') ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Data Siswa</h3>
                    <p class="empty-state-text">
                        Mulai tambahkan siswa untuk mengikuti ujian CBT.
                    </p>
                    <a href="<?= base_url('admin/students/create') ?>" class="btn btn-modern btn-primary-gradient">
                        <i class="fas fa-plus"></i> Tambah Siswa Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade modal-modern" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-import"></i> Impor Data Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">
                            Pilih File Excel
                            <span class="required-mark">*</span>
                        </label>
                        <input type="file" name="file" id="file" class="form-control" 
                               accept=".xlsx,.xls" required>
                    </div>
                    
                    <div class="alert alert-modern alert-info-gradient">
                        <div>
                            <div class="fw-bold mb-2">
                                <i class="fas fa-info-circle"></i> Format Excel:
                            </div>
                            <ul class="mb-2">
                                <li><strong>NIS</strong> (wajib, unique)</li>
                                <li><strong>Username</strong> (wajib, unique)</li>
                                <li><strong>Password</strong> (wajib)</li>
                                <li><strong>Nama Lengkap</strong> (wajib)</li>
                                <li><strong>Kelas</strong> (opsional)</li>
                                <li><strong>Gender</strong> (L/P)</li>
                                <li><strong>Agama</strong> (opsional)</li>
                                <li><strong>Ruang</strong> (opsional)</li>
                            </ul>
                            <a href="<?= base_url('admin/students/template') ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-modern btn-success-gradient">
                        <i class="fas fa-upload"></i> Upload & Impor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
