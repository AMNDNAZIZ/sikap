<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
    /* Inline styles untuk Subjects page */
    .page-header {
        background: linear-gradient(135deg, #041cf5ff 0%, #2cf903ff 100%) !important;
        color: white !important;
        padding: 2rem !important;
        border-radius: 12px !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
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
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
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

    .badge-success-gradient {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
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
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-book"></i> Daftar Mata Pelajaran</h1>
        <p>Kelola mata pelajaran untuk sistem CBT</p>
        <div class="page-header-actions">
            <a href="<?= base_url('admin/subjects/create') ?>" class="btn btn-modern btn-primary-gradient">
                <i class="fas fa-plus"></i> Tambah Mata Pelajaran
            </a>
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
            <?php endif; ?>

            <!-- Search -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari mata pelajaran...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Total: <strong><?= count($subjects) ?></strong> mata pelajaran
                    </span>
                </div>
            </div>

            <!-- Table -->
            <?php if (!empty($subjects)): ?>
                <div class="table-responsive">
                    <table class="table table-modern" id="subjectsTable">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th width="120">Kode</th>
                                <th>Nama Mata Pelajaran</th>
                                <th width="120">Jenis</th>
                                <th width="120">Agama</th>
                                <th width="120">Status</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($subjects as $s): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <span class="badge badge-modern badge-primary-gradient">
                                            <?= esc($s['code']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-book-open text-primary"></i>
                                            <strong><?= esc($s['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (($s['subject_type'] ?? 'umum') === 'agama'): ?>
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                                <i class="fas fa-mosque"></i> Agama
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                                                <i class="fas fa-book"></i> Umum
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['religion'])): ?>
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                                                <?= esc($s['religion']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['is_active']): ?>
                                            <span class="badge badge-modern badge-success-gradient">
                                                <i class="fas fa-check-circle"></i> Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-modern bg-secondary">
                                                <i class="fas fa-times-circle"></i> Nonaktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/subjects/edit/' . $s['id']) ?>"
                                            class="btn btn-action btn-warning-gradient" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= base_url('admin/subjects/delete/' . $s['id']) ?>" method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus mata pelajaran ini?\n\nData yang akan terhapus:\n- Data mata pelajaran\n- Bank soal terkait\n- Ujian terkait\n\nTindakan ini tidak dapat dibatalkan!')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-action btn-danger-gradient" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Data Mata Pelajaran</h3>
                    <p class="empty-state-text">
                        Mulai tambahkan mata pelajaran untuk membuat bank soal.
                    </p>
                    <a href="<?= base_url('admin/subjects/create') ?>" class="btn btn-modern btn-primary-gradient">
                        <i class="fas fa-plus"></i> Tambah Mata Pelajaran Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const searchValue = this.value.toLowerCase();
        const table = document.getElementById('subjectsTable');

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
</script>

<?= $this->endSection() ?>