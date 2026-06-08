<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Kokurikuler - Perencanaan</h4>
            <small class="text-muted">Kelola dokumen rencana kokurikuler sesuai Kurikulum Merdeka</small>
        </div>
        <div>
            <?php 
            $user = session()->get('user');
            $roleId = $user['role_id'] ?? 0;
            ?>
            <?php if ($roleId == 3): // Guru Kelas ?>
                <button type="button" class="btn btn-info me-2" id="btn_show_templates">
                    <i class="bi bi-search"></i> Cari Template
                </button>
            <?php endif; ?>
            <a href="<?= base_url('admin/kokurikuler/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Buat Dokumen Baru
            </a>
        </div>
    </div>

    <!-- Alert untuk info -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('info')): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <h5>Belum ada dokumen kokurikuler</h5>
                    <p>Klik tombol "Buat Dokumen Baru" untuk memulai<?= $roleId == 3 ? ' atau "Cari Template" untuk menggunakan template yang sudah ada' : '' ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="datatable" class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tahun Ajaran</th>
                                <th>Semester</th>
                                <th>Fase</th>
                                <th>Level Kelas</th>
                                <th>Tema</th>
                                <th>Jenis Kokurikuler</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($doc['year_name']) ?></td>
                                    <td><span class="badge bg-secondary">Semester <?= esc($doc['semester']) ?></span></td>
                                    <td><span class="badge bg-info">Fase <?= esc($doc['fase']) ?></span></td>
                                    <td><?= esc($doc['level_kelas']) ?></td>
                                    <td><?= esc($doc['tema']) ?></td>
                                    <td>
                                        <?php
                                        $jenis = [
                                            'lintas_disiplin' => 'Lintas Disiplin Ilmu',
                                            '7kaih' => '7 KAIH',
                                            'lainnya' => 'Lainnya'
                                        ];
                                        // Support both old and new field names for backward compatibility
                                        $jenisValue = $doc['jenis_kokurikuler'] ?? $doc['bentuk_kegiatan'] ?? '';
                                        echo $jenis[$jenisValue] ?? $jenisValue;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($doc['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= esc($doc['creator_name']) ?>
                                        <?php if (!empty($doc['parent_id'])): ?>
                                            <br><small class="text-muted">(dari template)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/kokurikuler/view/' . $doc['id']) ?>" 
                                               class="btn btn-info" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($doc['status'] === 'completed'): ?>
                                                <a href="<?= base_url('admin/kokurikuler/export-pdf/' . $doc['id']) ?>" 
                                                   class="btn btn-success" title="Export PDF">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= base_url('admin/kokurikuler/activate-old-plan/' . $doc['id']) ?>" 
                                               class="btn btn-warning" 
                                               title="Aktifkan ke Tahun Ajaran Baru">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </a>
                                            <a href="<?= base_url('admin/kokurikuler/delete/' . $doc['id']) ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Yakin ingin menghapus dokumen ini?')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Template List -->
<div class="modal fade" id="modalTemplates" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cari Template Rencana Kokurikuler</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Template adalah rencana yang sudah dibuat oleh Admin atau Wali Kelas lain. 
                    Anda bisa menggunakan template yang sesuai dengan level kelas Anda.
                </div>
                <div id="templates_loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat template...</p>
                </div>
                <div id="templates_content" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Fase</th>
                                    <th>Level</th>
                                    <th>Tema</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="templates_list">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="templates_empty" class="d-none text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>Belum ada template yang tersedia untuk level kelas Anda</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    <?php if (!empty($documents)): ?>
    $('#datatable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        order: [[1, 'desc']] // Sort by tahun ajaran
    });
    <?php endif; ?>

    // Show templates modal
    $('#btn_show_templates').on('click', function() {
        $('#modalTemplates').modal('show');
        loadTemplates();
    });

    function loadTemplates() {
        $('#templates_loading').removeClass('d-none');
        $('#templates_content').addClass('d-none');
        $('#templates_empty').addClass('d-none');

        $.ajax({
            url: '<?= base_url('admin/kokurikuler/get-available-templates') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#templates_loading').addClass('d-none');
                
                if (response.success && response.data.length > 0) {
                    $('#templates_content').removeClass('d-none');
                    let html = '';
                    
                    response.data.forEach(function(template) {
                        html += '<tr>';
                        html += '<td><span class="badge bg-secondary">Semester ' + template.semester + '</span></td>';
                        html += '<td><span class="badge bg-info">Fase ' + template.fase + '</span></td>';
                        html += '<td>' + template.level_kelas + '</td>';
                        html += '<td>' + template.tema + '</td>';
                        html += '<td>' + template.creator_name + '</td>';
                        html += '<td>';
                        html += '<a href="<?= base_url('admin/kokurikuler/use-template/') ?>' + template.id + '" class="btn btn-sm btn-primary">';
                        html += '<i class="bi bi-check-circle"></i> Gunakan';
                        html += '</a>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    $('#templates_list').html(html);
                } else {
                    $('#templates_empty').removeClass('d-none');
                }
            },
            error: function() {
                $('#templates_loading').addClass('d-none');
                $('#templates_empty').removeClass('d-none');
                $('#templates_empty p').text('Gagal memuat template. Silakan coba lagi.');
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
