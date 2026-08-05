<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
    /* Inline styles untuk Aktivitas page */
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
        margin: 0 !important;
        opacity: 0.95 !important;
        font-size: 0.95rem !important;
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

    .btn-action {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        border-radius: 6px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.375rem !important;
        white-space: nowrap !important;
        min-width: 36px !important;
    }

    .btn-action.btn-icon-only {
        padding: 0.5rem !important;
        width: 36px !important;
        height: 36px !important;
    }

    .btn-action.btn-icon-only i {
        margin: 0 !important;
    }

    .btn-action:hover {
        transform: translateY(-1px) !important;
    }

    .btn-primary-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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

    .btn-info-gradient {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
        color: white !important;
    }

    .badge-ongoing {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        color: white !important;
        animation: pulse 2s ease-in-out infinite !important;
    }

    .badge-finished {
        background: linear-gradient(135deg, #868f96 0%, #596164 100%) !important;
        color: white !important;
    }

    .badge-upcoming {
        background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%) !important;
        color: white !important;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(17, 153, 142, 0.7);
        }

        50% {
            box-shadow: 0 0 0 10px rgba(17, 153, 142, 0);
        }
    }

    .row-ongoing {
        background-color: #f0fdf4 !important;
        border-left: 4px solid #10b981 !important;
    }

    .row-ongoing:hover {
        background-color: #dcfce7 !important;
    }

    .status-icon {
        font-size: 1.1rem !important;
        line-height: 1 !important;
    }

    .status-icon.ongoing {
        color: #10b981 !important;
        animation: blink 1.5s ease-in-out infinite !important;
    }

    .status-icon.finished {
        color: #00ff88 !important; /* Spring Green for high visibility */
        filter: drop-shadow(0 0 2px rgba(0, 255, 136, 0.3)) !important;
    }

    .status-icon.upcoming {
        color: #f59e0b !important;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }

    .badge-modern {
        padding: 0.4rem 0.8rem !important;
        border-radius: 50px !important;
        font-weight: 500 !important;
        font-size: 0.8rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        line-height: 1 !important;
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

    .modal-modern .modal-content {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15) !important;
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

    .form-control,
    .form-select {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        padding: 0.625rem 0.875rem !important;
    }

    .form-control:focus,
    .form-select:focus {
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
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1><i class="fas fa-chart-line"></i> Aktivitas Ujian & Analisis</h1>
                <p>Pantau aktivitas ujian, unduh nilai, analisis soal, dan cetak laporan jawaban siswa</p>
            </div>
            <!-- Toggle buttons removed for automated filtering -->
        </div>
    </div>

    <!-- Main Card -->
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="table-responsive">
                <table id="tableAktivitas" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="120">Status</th>
                            <th>Kode Tes</th>
                            <th>Mata Pelajaran</th>
                            <th width="100">Aktivitas</th>
                            <th width="60">Nilai</th>
                            <th width="100">Analisis</th>
                            <th width="60">Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $i => $s): ?>
                            <tr class="<?= $s['status'] === 'ongoing' ? 'row-ongoing' : '' ?>">
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <?php if ($s['status'] === 'ongoing'): ?>
                                        <span class="badge badge-modern badge-ongoing">
                                            <i class="fas fa-circle status-icon ongoing"></i>
                                            BERLANGSUNG
                                        </span>
                                    <?php elseif ($s['status'] === 'finished'): ?>
                                        <span class="badge badge-modern badge-finished">
                                            <i class="fas fa-check-circle status-icon finished"></i>
                                            SELESAI
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-modern badge-upcoming">
                                            <i class="fas fa-clock status-icon upcoming"></i>
                                            AKAN DATANG
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-modern badge-primary-gradient">
                                        <?= esc($s['test_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-book text-primary"></i>
                                        <strong><?= esc($s['subject_name']) ?></strong>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('d M Y, H:i', strtotime($s['start_time'])) ?> -
                                        <?= date('H:i', strtotime($s['end_time'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= site_url('admin/cbt/aktivitas/detail/' . $s['test_id']) ?>"
                                            class="btn btn-action btn-icon-only btn-danger-gradient"
                                            data-bs-toggle="tooltip" title="Lihat Aktivitas">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-action btn-icon-only btn-warning-gradient btn-belum-tes"
                                            data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                            data-bs-toggle="tooltip" title="Siswa Belum Tes">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-action btn-icon-only btn-primary-gradient btn-unduh-nilai"
                                        data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                        data-bs-toggle="tooltip" title="Unduh Nilai">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= site_url('admin/cbt/aktivitas/analisis/' . $s['test_id']) ?>"
                                            class="btn btn-action btn-icon-only btn-warning-gradient"
                                            data-bs-toggle="tooltip" title="Lihat Analisis">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-action btn-icon-only btn-info-gradient btn-analisis-download"
                                            data-id="<?= $s['test_id'] ?>" data-name="<?= esc($s['subject_name']) ?>"
                                            data-bs-toggle="tooltip" title="Unduh Analisis">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= site_url('admin/cbt/aktivitas/laporan/' . $s['test_id']) ?>"
                                        class="btn btn-action btn-icon-only btn-success-gradient" data-bs-toggle="tooltip"
                                        title="Laporan Jawaban">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Belum Tes -->
<div class="modal fade modal-modern" id="modalBelumTes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-times"></i> Daftar Siswa Belum Tes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="belumTesContent" class="text-center text-muted">
                    Memuat data...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unduh Nilai -->
<div class="modal fade modal-modern" id="modalUnduhNilai" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download"></i> Unduh Nilai
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Pilih kelas untuk mengunduh nilai:</p>
                <form id="formUnduhNilai" method="get">
                    <?= csrf_field() ?>
                    <input type="hidden" name="test_id" id="nilaiTestId">
                    <div class="mb-3">
                        <label for="classSelect" class="form-label">Pilih Kelas:</label>
                        <select id="classSelect" name="class" class="form-select">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= esc($cls['id']) ?>"><?= esc($cls['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-action btn-success-gradient w-100">
                        <i class="fas fa-download"></i> Unduh Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unduh Analisis -->
<div class="modal fade modal-modern" id="modalUnduhAnalisis" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download"></i> Download Analisis Jawaban
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Anda bisa mengunduh semua analisis atau per kelas!</p>
                <div class="d-flex justify-content-center gap-3 mb-3">
                    <button id="btnAnalisisTotal" class="btn btn-action btn-success-gradient">
                        <i class="fas fa-file-excel"></i> Total
                    </button>
                    <button id="btnAnalisisPerKelas" class="btn btn-action btn-primary-gradient">
                        <i class="fas fa-file-excel"></i> Per Kelas
                    </button>
                </div>

                <div id="analisisClassSection" class="d-none">
                    <label class="form-label">Pilih Kelas</label>
                    <select id="analisisClassSelect" class="form-select mb-3">
                        <option value="">Pilih Kelas...</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= esc($cls['id']) ?>"><?= esc($cls['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnDownloadAnalisisKelas" class="btn btn-action btn-success-gradient w-100">
                        <i class="fas fa-download"></i> Unduh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(function () {
        // Initialize Bootstrap Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // DataTable
        $('#tableAktivitas').DataTable({
            responsive: true,
            order: [],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Tidak ada data ditemukan",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data tersedia"
            }
        });

        // Tombol Belum Tes
        $(document).on('click', '.btn-belum-tes', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#modalBelumTes .modal-title').html('<i class="fas fa-user-times"></i> Siswa Belum Tes - ' + name);
            $('#belumTesContent').html('<div class="text-center text-muted">Memuat data...</div>');
            $('#modalBelumTes').modal('show');

            $.get('<?= site_url('admin/cbt/aktivitas/belumTes/') ?>' + id, function (html) {
                $('#belumTesContent').html(html);
            }).fail(function (xhr) {
                $('#belumTesContent').html('<div class="alert alert-danger">Gagal memuat data.<br>' + xhr.status + ' ' + xhr.statusText + '</div>');
            });
        });

        // Tombol Unduh Nilai
        $(document).on('click', '.btn-unduh-nilai', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#nilaiTestId').val(id);
            $('#modalUnduhNilai .modal-title').html('<i class="fas fa-download"></i> Unduh Nilai - ' + name);
            $('#modalUnduhNilai').modal('show');
        });

        // Submit Form Unduh Nilai
        $('#formUnduhNilai').on('submit', function (e) {
            e.preventDefault();
            const id = $('#nilaiTestId').val();
            const kelas = $('#classSelect').val();
            window.location.href = `<?= site_url('admin/cbt/aktivitas/unduhNilai/') ?>${id}?class=${kelas}`;
        });

        // Tombol Unduh Analisis
        $(document).on('click', '.btn-analisis-download', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#modalUnduhAnalisis .modal-title').html('<i class="fas fa-download"></i> Download Analisis - ' + name);
            $('#modalUnduhAnalisis').data('test-id', id);
            $('#analisisClassSection').addClass('d-none');
            $('#analisisClassSelect').val('');
            $('#modalUnduhAnalisis').modal('show');
        });

        // Tombol Total
        $('#btnAnalisisTotal').on('click', function () {
            const id = $('#modalUnduhAnalisis').data('test-id');
            const url = `<?= site_url('admin/cbt/aktivitas/analisisjawaban/download/') ?>${id}?mode=total`;
            window.location.href = url;
        });

        // Tombol Per Kelas
        $('#btnAnalisisPerKelas').on('click', function () {
            $('#analisisClassSection').removeClass('d-none');
        });

        // Tombol Unduh (Per Kelas)
        $('#btnDownloadAnalisisKelas').on('click', function () {
            const id = $('#modalUnduhAnalisis').data('test-id');
            const classId = $('#analisisClassSelect').val();
            if (!classId) {
                alert('Silakan pilih kelas terlebih dahulu.');
                return;
            }
            const url = `<?= site_url('admin/cbt/aktivitas/analisisjawaban/download/') ?>${id}?mode=kelas&class=${classId}`;
            window.location.href = url;
        });

        // Auto-refresh status setiap 3 menit (180 detik) untuk ujian yang sedang berlangsung
        setInterval(function () {
            const hasOngoing = $('.badge-ongoing').length > 0;
            if (hasOngoing) {
                // Reload page to update status
                location.reload();
            }
        }, 180000); // 3 minutes

        // Add visual indicator for ongoing exams in page title
        const ongoingCount = $('.badge-ongoing').length;
        if (ongoingCount > 0) {
            document.title = `(${ongoingCount}) Ujian Berlangsung - Aktivitas CBT`;
        }
    });
</script>
<?= $this->endSection() ?>