<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Bank Soal page */
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

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
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

.modern-card-footer {
    background: #f8f9fa !important;
    padding: 1rem 1.5rem !important;
    border-top: 1px solid #e9ecef !important;
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

.badge-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
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

.form-control, .form-select {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.alert-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 10px !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-book-open"></i> Daftar Bank Soal</h1>
        <p>Kelola bank soal untuk ujian CBT - buat, edit, dan hapus soal dalam kumpulan bank</p>
        <div class="page-header-actions">
            <button class="btn btn-modern btn-primary-gradient" data-bs-toggle="modal" data-bs-target="#modalRestoreBank">
                <i class="fas fa-upload"></i> Restore Bank Soal
            </button>
            <button class="btn btn-modern btn-success-gradient" data-bs-toggle="modal" data-bs-target="#modalAddBank">
                <i class="fas fa-plus-circle"></i> Tambah Bank Soal
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <form method="post" action="<?= site_url('admin/cbt/banksoal/bulkDelete') ?>">
        <?= csrf_field() ?>
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="checkAll"></th>
                                <th width="50">No</th>
                                <th>Kode Bank Soal</th>
                                <th>Mapel</th>
                                <th>Jumlah Soal</th>
                                <th>Pembuat</th>
                                <th width="100">Status</th>
                                <th width="280">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banks as $i => $bank): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= $bank['id'] ?>"></td>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <span class="badge badge-modern badge-primary-gradient">
                                        <?= esc($bank['code']) ?>
                                    </span>
                                </td>
                                <td><?= esc($bank['subject_name']) ?></td>
                                <td>
                                    <div>
                                        <strong><?= esc($bank['total_questions']) ?></strong> soal
                                    </div>
                                    <small class="text-muted">
                                        <?= esc($bank['option_count']) ?> opsi |
                                        <?= esc($bank['total_pg']) ?> PG |
                                        <?= esc($bank['total_pg_kompleks']) ?> PGK |
                                        <?= esc($bank['total_bs']) ?> BS |
                                        <?= esc($bank['total_esai']) ?> Esai
                                    </small>
                                </td>
                                <td><?= esc($bank['creator_name'] ?? '-') ?></td>
                                <td>
                                    <?php if ($bank['is_active']): ?>
                                        <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                                           class="badge badge-modern badge-success-gradient text-decoration-none">
                                            <i class="fas fa-check-circle"></i> Aktif
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                                           class="badge badge-modern bg-secondary text-decoration-none">
                                            <i class="fas fa-times-circle"></i> Nonaktif
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($bank['is_in_use']): ?>
                                        <br>
                                        <span class="badge badge-modern bg-warning text-dark mt-1" 
                                              title="Bank soal sedang digunakan dalam <?= $bank['active_tests_count'] ?> ujian aktif">
                                            <i class="fas fa-exclamation-triangle"></i> Digunakan (<?= $bank['active_tests_count'] ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-warning-gradient btn-edit-bank"
                                                data-id="<?= $bank['id'] ?>" 
                                                data-code="<?= esc($bank['code']) ?>"
                                                data-subject="<?= esc($bank['subject_id']) ?>" 
                                                data-option="<?= esc($bank['option_count']) ?>"
                                                title="Edit Bank Soal">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <a href="<?= site_url('admin/cbt/banksoal/copy/' . $bank['id']) ?>"
                                           class="btn btn-info-gradient" title="Salin Bank Soal">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>"
                                           class="btn btn-primary-gradient" title="Rincian Bank Soal">
                                            <i class="fas fa-list-ul"></i>
                                        </a>
                                        <a href="<?= site_url('admin/cbt/banksoal/print/' . $bank['id']) ?>"
                                           class="btn btn-secondary" title="Cetak PDF">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="<?= site_url('admin/cbt/banksoal/backup/' . $bank['id']) ?>"
                                           class="btn btn-dark" title="Backup Bank Soal">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <?php if ($bank['is_in_use']): ?>
                                            <button type="button" 
                                                    class="btn btn-secondary" 
                                                    disabled
                                                    title="Tidak dapat dihapus - Bank soal sedang digunakan dalam <?= $bank['active_tests_count'] ?> ujian aktif">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= site_url('admin/cbt/banksoal/delete/' . $bank['id']) ?>"
                                               class="btn btn-danger-gradient"
                                               onclick="return confirm('Hapus bank soal ini beserta semua soalnya?')" 
                                               title="Hapus Bank Soal">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modern-card-footer">
                <button type="submit" class="btn btn-modern btn-danger-gradient"
                        onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal Tambah Bank Soal -->
<div class="modal fade modal-modern" id="modalAddBank" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Tambah Bank Soal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAddBank" autocomplete="off" onsubmit="return false;">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Kode Bank Soal</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php foreach (getSubjects() as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Opsi Jawaban</label>
                        <select name="option_count" class="form-select">
                            <option value="3">3</option>
                            <option value="4" selected>4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" id="btnSaveBank" class="btn btn-modern btn-success-gradient">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Bank Soal -->
<div class="modal fade modal-modern" id="modalEditBank" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Bank Soal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditBank" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Kode Bank Soal</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select name="subject_id" id="edit_subject" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php foreach (getSubjects() as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Opsi Jawaban</label>
                        <select name="option_count" id="edit_option" class="form-select">
                            <?php foreach ([3, 4, 5] as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" id="btnUpdateBank" class="btn btn-modern btn-success-gradient">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Restore Bank Soal -->
<div class="modal fade modal-modern" id="modalRestoreBank" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload"></i> Restore Bank Soal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= site_url('admin/cbt/banksoal/restore') ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Pilih file ZIP hasil backup bank soal untuk mengembalikan data dan file pendukungnya.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Backup (.zip)</label>
                        <input type="file" name="backup_file" class="form-control" accept=".zip" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                    <button type="submit" class="btn btn-modern btn-primary-gradient">
                        <i class="fas fa-play"></i> Mulai Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    const table = $('#datatable').DataTable({
        responsive: true,
        autoWidth: false,
        order: [],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data ditemukan",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(disaring dari _MAX_ total data)"
        }
    });

    $('#checkAll').on('click', function() {
        $('input[name="ids[]"]').prop('checked', this.checked);
    });

    $(document).on('click', 'button', function(e) {
        if ($(this).attr('type') === undefined) $(this).attr('type', 'button');
    });

    $('#btnSaveBank').on('click', function(e) {
        e.preventDefault();
        const form = $('#formAddBank');
        const formData = form.serialize();

        $.ajax({
            url: '<?= site_url('admin/cbt/banksoal/storeAjax') ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#btnSaveBank').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(res) {
                if (res.success) {
                    const d = res.data;
                    const modalEl = document.getElementById('modalAddBank');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    form[0].reset();

                    const newRow = table.row.add([
                        `<input type="checkbox" name="ids[]" value="${d.id}">`,
                        table.data().count() + 1,
                        `<span class="badge badge-modern badge-primary-gradient">${d.code}</span>`,
                        d.subject_name,
                        `<div><strong>0</strong> soal</div><small class="text-muted">${d.option_count} opsi | 0 PG | 0 PGK | 0 BS | 0 Esai</small>`,
                        d.creator_name,
                        `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="badge badge-modern bg-secondary text-decoration-none"><i class="fas fa-times-circle"></i> Nonaktif</a>`,
                        `<div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-warning-gradient btn-edit-bank" data-id="${d.id}" data-code="${d.code}" data-subject="${d.subject_id}" data-option="${d.option_count}" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                            <a href="<?= site_url('admin/cbt/banksoal/copy/') ?>${d.id}" class="btn btn-info-gradient" title="Salin"><i class="fas fa-copy"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/detail/') ?>${d.id}" class="btn btn-primary-gradient" title="Rincian"><i class="fas fa-list-ul"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/print/') ?>${d.id}" class="btn btn-secondary" title="Cetak"><i class="fas fa-print"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/backup/') ?>${d.id}" class="btn btn-dark" title="Backup"><i class="fas fa-download"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/delete/') ?>${d.id}" class="btn btn-danger-gradient" title="Hapus" onclick="return confirm('Hapus bank soal ini?')"><i class="fas fa-trash"></i></a>
                        </div>`
                    ]).draw().node();

                    $(newRow).addClass('table-success');
                    setTimeout(() => $(newRow).removeClass('table-success'), 2000);

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                Swal.fire('Gagal', 'Server tidak merespon', 'error');
            },
            complete: function() {
                $('#btnSaveBank').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });

    $(document).on('click', '.btn-edit-bank', function(e) {
        e.preventDefault();
        const btn = $(this);
        $('#edit_id').val(btn.data('id'));
        $('#edit_code').val(btn.data('code'));
        $('#edit_subject').val(btn.data('subject'));
        $('#edit_option').val(btn.data('option'));
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEditBank'));
        modalEdit.show();
    });

    $('#btnUpdateBank').on('click', function(e) {
        e.preventDefault();
        const formData = $('#formEditBank').serialize();

        $.ajax({
            url: '<?= site_url('admin/cbt/banksoal/updateAjax') ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: () => $('#btnUpdateBank').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...'),
            success: function(res) {
                if (res.success) {
                    const d = res.data;
                    const rowEl = $('#datatable').find(`input[value="${d.id}"]`).closest('tr');
                    const rowIndex = table.row(rowEl).index();

                    table.row(rowIndex).data([
                        `<input type="checkbox" name="ids[]" value="${d.id}">`,
                        rowIndex + 1,
                        `<span class="badge badge-modern badge-primary-gradient">${d.code}</span>`,
                        d.subject_name,
                        `<div><strong>${d.total_questions ?? 0}</strong> soal</div><small class="text-muted">${d.option_count} opsi | ${d.total_pg ?? 0} PG | ${d.total_pg_kompleks ?? 0} PGK | ${d.total_bs ?? 0} BS | ${d.total_esai ?? 0} Esai</small>`,
                        d.teacher_name ?? "Admin",
                        d.is_active == 1
                            ? `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="badge badge-modern badge-success-gradient text-decoration-none"><i class="fas fa-check-circle"></i> Aktif</a>`
                            : `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="badge badge-modern bg-secondary text-decoration-none"><i class="fas fa-times-circle"></i> Nonaktif</a>`,
                        `<div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-warning-gradient btn-edit-bank" data-id="${d.id}" data-code="${d.code}" data-subject="${d.subject_id}" data-option="${d.option_count}" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                            <a href="<?= site_url('admin/cbt/banksoal/copy/') ?>${d.id}" class="btn btn-info-gradient" title="Salin"><i class="fas fa-copy"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/detail/') ?>${d.id}" class="btn btn-primary-gradient" title="Rincian"><i class="fas fa-list-ul"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/print/') ?>${d.id}" class="btn btn-secondary" title="Cetak"><i class="fas fa-print"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/backup/') ?>${d.id}" class="btn btn-dark" title="Backup"><i class="fas fa-download"></i></a>
                            <a href="<?= site_url('admin/cbt/banksoal/delete/') ?>${d.id}" class="btn btn-danger-gradient" title="Hapus" onclick="return confirm('Hapus bank soal ini?')"><i class="fas fa-trash"></i></a>
                        </div>`
                    ]).draw(false);

                    const modalEl = document.getElementById('modalEditBank');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', res.error || 'Gagal memperbarui', 'error');
                }
            },
            error: () => Swal.fire('Gagal', 'Server tidak merespon', 'error'),
            complete: () => $('#btnUpdateBank').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Perubahan')
        });
    });

    $('#modalAddBank, #modalEditBank').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
    });
});
</script>
<?= $this->endSection() ?>
