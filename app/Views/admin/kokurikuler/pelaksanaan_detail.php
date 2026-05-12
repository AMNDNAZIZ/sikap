<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="mb-0"><?= $title ?></h5>
            <small class="text-muted"><?= esc($document['year_name']) ?> - Semester <?= esc($document['semester']) ?> | Fase <?= esc($document['fase']) ?> - Kelas <?= esc($document['level_kelas']) ?></small>
        </div>
        <a href="<?= base_url('admin/kokurikuler/pelaksanaan') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- List Kegiatan - Compact Table Style -->
    <?php if (!empty($kegiatanList)): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="8%" class="text-center">Pertemuan</th>
                                <th width="25%">Kegiatan</th>
                                <th width="20%">Status</th>
                                <th width="47%">Detail Pelaksanaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kegiatanList as $index => $kegiatan): ?>
                                <?php 
                                $pertemuanKe = $kegiatan['pertemuan'];
                                $pelaksanaan = $pelaksanaanMap[$pertemuanKe] ?? null;
                                $status = $pelaksanaan['status'] ?? 'belum_dilaksanakan';
                                
                                // Status badge
                                $badgeClass = match($status) {
                                    'terlaksana' => 'bg-success',
                                    'tidak_terlaksana' => 'bg-danger',
                                    default => 'bg-warning'
                                };
                                $badgeIcon = match($status) {
                                    'terlaksana' => 'check-circle-fill',
                                    'tidak_terlaksana' => 'x-circle-fill',
                                    default => 'clock-fill'
                                };
                                ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <strong><?= $pertemuanKe ?></strong>
                                    </td>
                                    <td class="align-middle">
                                        <div class="fw-bold small"><?= esc($kegiatan['kegiatan']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= esc($kegiatan['deskripsi']) ?></div>
                                    </td>
                                    <td class="align-middle">
                                        <form class="form-pelaksanaan" data-pelaksanaan-id="<?= $pelaksanaan['id'] ?? '' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="pelaksanaan_id" value="<?= $pelaksanaan['id'] ?? '' ?>">
                                            
                                            <div class="btn-group btn-group-sm d-flex" role="group">
                                                <input type="radio" class="btn-check" name="status_<?= $pertemuanKe ?>" 
                                                       id="status_belum_<?= $pertemuanKe ?>" value="belum_dilaksanakan" 
                                                       <?= $status === 'belum_dilaksanakan' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-warning" for="status_belum_<?= $pertemuanKe ?>" title="Belum Dilaksanakan">
                                                    <i class="bi bi-clock"></i>
                                                </label>

                                                <input type="radio" class="btn-check" name="status_<?= $pertemuanKe ?>" 
                                                       id="status_terlaksana_<?= $pertemuanKe ?>" value="terlaksana"
                                                       <?= $status === 'terlaksana' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-success" for="status_terlaksana_<?= $pertemuanKe ?>" title="Terlaksana">
                                                    <i class="bi bi-check-circle"></i>
                                                </label>

                                                <input type="radio" class="btn-check" name="status_<?= $pertemuanKe ?>" 
                                                       id="status_tidak_<?= $pertemuanKe ?>" value="tidak_terlaksana"
                                                       <?= $status === 'tidak_terlaksana' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-danger" for="status_tidak_<?= $pertemuanKe ?>" title="Tidak Terlaksana">
                                                    <i class="bi bi-x-circle"></i>
                                                </label>
                                            </div>
                                            
                                            <div class="mt-1 text-center">
                                                <span class="badge <?= $badgeClass ?> badge-status">
                                                    <i class="bi bi-<?= $badgeIcon ?>"></i>
                                                </span>
                                            </div>
                                    </td>
                                    <td class="align-middle">
                                            <!-- Form Terlaksana -->
                                            <div class="form-terlaksana" style="display: <?= $status === 'terlaksana' ? 'block' : 'none' ?>;">
                                                <div class="row g-1 mb-1">
                                                    <div class="col-4">
                                                        <input type="date" class="form-control form-control-sm" name="tanggal_pelaksanaan" 
                                                               placeholder="Tanggal" value="<?= $pelaksanaan['tanggal_pelaksanaan'] ?? '' ?>">
                                                    </div>
                                                    <div class="col-8">
                                                        <input type="text" class="form-control form-control-sm" name="catatan_pelaksanaan" 
                                                               placeholder="Catatan singkat..." value="<?= $pelaksanaan['catatan_pelaksanaan'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="row g-1">
                                                    <div class="col-9">
                                                        <input type="file" class="form-control form-control-sm" name="dokumentasi[]" 
                                                               accept="image/*,.pdf" title="Upload dokumentasi (1 file)">
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="submit" class="btn btn-primary btn-sm w-100" title="Simpan">
                                                            <i class="bi bi-save"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php if (!empty($pelaksanaan['dokumentasi'])): ?>
                                                    <?php $files = json_decode($pelaksanaan['dokumentasi'], true); ?>
                                                    <?php if (!empty($files) && is_array($files)): ?>
                                                        <div class="mt-1">
                                                            <?php foreach ($files as $idx => $file): ?>
                                                                <?php 
                                                                $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                                $fileName = basename($file);
                                                                // Use route to serve file from writable directory
                                                                $fileUrl = base_url('admin/kokurikuler/pelaksanaan/file/' . $fileName);
                                                                ?>
                                                                <a href="javascript:void(0);" class="preview-file text-decoration-none" 
                                                                   data-file="<?= $fileUrl ?>" 
                                                                   data-type="<?= $fileExt ?>"
                                                                   data-name="<?= esc($fileName) ?>">
                                                                    <small class="text-primary" style="font-size: 0.7rem;">
                                                                        <i class="bi bi-paperclip"></i> <?= esc($fileName) ?>
                                                                    </small>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Form Tidak Terlaksana -->
                                            <div class="form-tidak-terlaksana" style="display: <?= $status === 'tidak_terlaksana' ? 'block' : 'none' ?>;">
                                                <div class="row g-1">
                                                    <div class="col-9">
                                                        <input type="text" class="form-control form-control-sm" name="alasan_tidak_terlaksana" 
                                                               placeholder="Alasan tidak terlaksana..." value="<?= $pelaksanaan['alasan_tidak_terlaksana'] ?? '' ?>">
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="submit" class="btn btn-primary btn-sm w-100" title="Simpan">
                                                            <i class="bi bi-save"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Belum Dilaksanakan -->
                                            <div class="form-belum" style="display: <?= $status === 'belum_dilaksanakan' ? 'block' : 'none' ?>;">
                                                <small class="text-muted">Pilih status untuk mengisi detail</small>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Preview File -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview Dokumentasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="previewContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <a href="#" id="downloadLink" class="btn btn-primary btn-sm" download>
                    <i class="bi bi-download"></i> Download
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    console.log('Document ready - initializing pelaksanaan handlers');
    
    // Handle status change
    $('input[type="radio"][name^="status_"]').on('change', function() {
        const $row = $(this).closest('tr');
        const status = $(this).val();
        
        $row.find('.form-terlaksana').hide();
        $row.find('.form-tidak-terlaksana').hide();
        $row.find('.form-belum').hide();
        
        if (status === 'terlaksana') {
            $row.find('.form-terlaksana').show();
        } else if (status === 'tidak_terlaksana') {
            $row.find('.form-tidak-terlaksana').show();
        } else {
            $row.find('.form-belum').show();
        }
    });

    // Handle form submit
    $('.form-pelaksanaan').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]:focus, button[type="submit"]:hover').first();
        const $row = $form.closest('tr');
        
        // Get status value
        const statusRadio = $form.find('input[type="radio"][name^="status_"]:checked');
        const status = statusRadio.val();
        
        console.log('Form submit - Status:', status);
        
        // Validation based on status
        if (status === 'terlaksana') {
            const $terlaksanaDiv = $row.find('.form-terlaksana:visible');
            const tanggal = $terlaksanaDiv.find('input[name="tanggal_pelaksanaan"]').val();
            console.log('Validating terlaksana - Tanggal:', tanggal);
            if (!tanggal || tanggal.trim() === '') {
                alert('Tanggal pelaksanaan harus diisi!');
                $terlaksanaDiv.find('input[name="tanggal_pelaksanaan"]').focus();
                return;
            }
        } else if (status === 'tidak_terlaksana') {
            const $tidakTerlaksanaDiv = $row.find('.form-tidak-terlaksana:visible');
            const alasan = $tidakTerlaksanaDiv.find('input[name="alasan_tidak_terlaksana"]').val();
            console.log('Validating tidak_terlaksana - Alasan:', alasan);
            if (!alasan || alasan.trim() === '') {
                alert('Alasan tidak terlaksana harus diisi!');
                $tidakTerlaksanaDiv.find('input[name="alasan_tidak_terlaksana"]').focus();
                return;
            }
        }
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('status', status);
        
        // Disable button
        const $submitBtn = $row.find('button[type="submit"]:visible');
        $submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');
        
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/pelaksanaan/save') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update badge
                    let badgeClass = 'bg-warning';
                    let badgeIcon = 'clock-fill';
                    
                    if (status === 'terlaksana') {
                        badgeClass = 'bg-success';
                        badgeIcon = 'check-circle-fill';
                    } else if (status === 'tidak_terlaksana') {
                        badgeClass = 'bg-danger';
                        badgeIcon = 'x-circle-fill';
                    }
                    
                    $row.find('.badge-status')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(badgeClass)
                        .html('<i class="bi bi-' + badgeIcon + '"></i>');
                    
                    // Show success message
                    const $alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;" role="alert">' +
                        '<i class="bi bi-check-circle"></i> Data berhasil disimpan!' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('body').append($alert);
                    setTimeout(() => $alert.alert('close'), 3000);
                    
                    // Re-enable button
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-save"></i>');
                    
                    // Reload page to show uploaded files
                    const fileInput = $form.find('input[name="dokumentasi[]"]')[0];
                    if (status === 'terlaksana' && fileInput && fileInput.files && fileInput.files.length > 0) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    alert('Error: ' + response.message);
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-save"></i>');
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
                $submitBtn.prop('disabled', false).html('<i class="bi bi-save"></i>');
            }
        });
    });

    // Handle file preview - using event delegation
    $(document).on('click', '.preview-file', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $link = $(this);
        const fileUrl = $link.attr('data-file');
        const fileType = $link.attr('data-type');
        const fileName = $link.attr('data-name');
        
        console.log('Preview file clicked');
        console.log('File URL:', fileUrl);
        console.log('File Type:', fileType);
        console.log('File Name:', fileName);
        
        if (!fileUrl || !fileType || !fileName) {
            console.error('Missing data attributes:', {fileUrl, fileType, fileName});
            alert('Data file tidak lengkap. Silakan refresh halaman.');
            return;
        }
        
        // Set modal title and download link
        $('#previewModalLabel').text('Preview: ' + fileName);
        $('#downloadLink').attr('href', fileUrl);
        
        let content = '';
        const fileTypeLower = fileType.toLowerCase();
        
        // Check file type
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].indexOf(fileTypeLower) !== -1) {
            // Image preview
            console.log('Showing image preview');
            content = '<div class="text-center">' +
                '<img src="' + fileUrl + '" class="img-fluid" alt="' + fileName + '" style="max-height: 70vh; max-width: 100%;" onerror="this.onerror=null; this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EGambar tidak dapat dimuat%3C/text%3E%3C/svg%3E\';">' +
                '</div>';
        } else if (fileTypeLower === 'pdf') {
            // PDF preview
            console.log('Showing PDF preview');
            content = '<iframe src="' + fileUrl + '" style="width: 100%; height: 70vh; border: none;"></iframe>';
        } else {
            // Other files - show download link
            console.log('File type not supported for preview:', fileTypeLower);
            content = '<div class="py-5">' +
                '<i class="bi bi-file-earmark text-muted" style="font-size: 4rem;"></i>' +
                '<p class="mt-3">File ini tidak dapat di-preview.</p>' +
                '<p class="text-muted">Tipe file: ' + fileType + '</p>' +
                '<p class="text-muted">Klik tombol Download untuk mengunduh file.</p>' +
                '</div>';
        }
        
        // Set content
        $('#previewContent').html(content);
        
        // Show modal using Bootstrap 5 API
        try {
            const modalElement = document.getElementById('previewModal');
            if (modalElement) {
                const previewModal = new bootstrap.Modal(modalElement);
                previewModal.show();
                console.log('Modal shown successfully');
            } else {
                console.error('Modal element not found');
                alert('Modal tidak ditemukan. Silakan refresh halaman.');
            }
        } catch (error) {
            console.error('Error showing modal:', error);
            alert('Gagal membuka preview. Error: ' + error.message);
        }
    });
    
    // Debug: Check if preview links exist
    const previewLinks = $('.preview-file');
    console.log('Found ' + previewLinks.length + ' preview links');
    if (previewLinks.length > 0) {
        console.log('First preview link data:', {
            file: previewLinks.first().attr('data-file'),
            type: previewLinks.first().attr('data-type'),
            name: previewLinks.first().attr('data-name')
        });
    }
});
</script>
<?= $this->endSection() ?>
