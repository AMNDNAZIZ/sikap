<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="mb-0"><?= $title ?></h5>
            <small class="text-muted">
                <?= esc($document['year_name']) ?> - Semester <?= esc($document['semester']) ?> | 
                Fase <?= esc($document['fase']) ?> - Kelas <?= esc($document['level_kelas']) ?>
                <?php if (!empty($document['class_id'])): ?>
                    <span class="badge bg-info ms-2">Kelas Spesifik: ID <?= $document['class_id'] ?></span>
                <?php else: ?>
                    <span class="badge bg-warning ms-2">Template (Semua Kelas Level <?= esc($document['level_kelas']) ?>)</span>
                <?php endif; ?>
            </small>
        </div>
        <a href="<?= base_url('admin/kokurikuler/penilaian') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Progress Summary -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <strong>Progress Penilaian:</strong> 
                    <?= $summary['sudah_dinilai'] ?> dari <?= $summary['total_students'] ?> siswa 
                    (<?= $summary['persentase'] ?>%)
                </div>
                <div class="col-md-4">
                    <div class="progress" style="height: 25px;">
                        <?php 
                        $progressClass = $summary['persentase'] == 100 ? 'bg-success' : ($summary['persentase'] > 0 ? 'bg-warning' : 'bg-secondary');
                        ?>
                        <div class="progress-bar <?= $progressClass ?>" role="progressbar" 
                             style="width: <?= $summary['persentase'] ?>%;">
                            <?= $summary['persentase'] ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Siswa -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">NIS</th>
                            <th width="30%">Nama Siswa</th>
                            <th width="20%">Kelas</th>
                            <th width="15%">Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-1"><strong>Tidak ada siswa ditemukan</strong></p>
                                        <?php if (empty($document['class_id'])): ?>
                                            <p class="small">Dokumen ini adalah template. Siswa akan muncul setelah dokumen digunakan oleh wali kelas untuk kelas spesifik.</p>
                                        <?php else: ?>
                                            <p class="small">Tidak ada siswa di kelas dengan ID: <?= $document['class_id'] ?>. Pastikan kelas memiliki siswa aktif di tahun ajaran ini.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= esc($student['nis']) ?></td>
                                    <td><?= esc($student['name']) ?></td>
                                    <td><?= esc($student['class_name']) ?></td>
                                    <td>
                                        <?php if ($student['status'] === 'sudah_dinilai'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Sudah Dinilai
                                            </span>
                                            <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($student['penilaian']['updated_at'])) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-clock"></i> Belum Dinilai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-nilai" 
                                                data-document-id="<?= $document['id'] ?>"
                                                data-student-id="<?= $student['id'] ?>"
                                                data-student-name="<?= esc($student['name']) ?>">
                                            <i class="bi bi-clipboard-check"></i> 
                                            <?= $student['status'] === 'sudah_dinilai' ? 'Edit' : 'Nilai' ?>
                                        </button>
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

<!-- Modal Penilaian -->
<div class="modal fade" id="penilaianModal" tabindex="-1" aria-labelledby="penilaianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="penilaianModalLabel">Penilaian Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="penilaianContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btnSavePenilaian">
                    <i class="bi bi-save"></i> Simpan Penilaian
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    let currentDocumentId = null;
    let currentStudentId = null;

    // Handle button nilai click
    $('.btn-nilai').on('click', function() {
        currentDocumentId = $(this).data('document-id');
        currentStudentId = $(this).data('student-id');
        const studentName = $(this).data('student-name');

        $('#penilaianModalLabel').text('Penilaian: ' + studentName);
        $('#penilaianContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Memuat data...</p></div>');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('penilaianModal'));
        modal.show();

        // Load form
        loadPenilaianForm(currentDocumentId, currentStudentId);
    });

    // Load penilaian form
    function loadPenilaianForm(documentId, studentId) {
        console.log('Loading penilaian form for document:', documentId, 'student:', studentId);
        
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/penilaian/form') ?>/' + documentId + '/' + studentId,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Penilaian form response:', response);
                
                if (response.success) {
                    if (!response.rubrik || response.rubrik.length === 0) {
                        console.error('No rubrik data received');
                        $('#penilaianContent').html('<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Rubrik penilaian belum tersedia. Silakan hubungi administrator.</div>');
                        return;
                    }
                    
                    console.log('Rubrik count:', response.rubrik.length);
                    renderPenilaianForm(response.rubrik, response.penilaian, response.student);
                } else {
                    $('#penilaianContent').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response status:', xhr.status);
                console.error('Response text:', xhr.responseText);
                
                // Check if redirected to login
                if (xhr.status === 302 || (xhr.responseText && xhr.responseText.includes('login'))) {
                    $('#penilaianContent').html('<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Session expired. Silakan refresh halaman dan login kembali.</div>');
                } else {
                    $('#penilaianContent').html('<div class="alert alert-danger">Terjadi kesalahan saat memuat data. Status: ' + xhr.status + ', Error: ' + error + '</div>');
                }
            }
        });
    }

    // Render penilaian form
    function renderPenilaianForm(rubrik, penilaian, student) {
        console.log('Rendering penilaian form');
        console.log('Rubrik data:', rubrik);
        console.log('Penilaian data:', penilaian);
        console.log('Student data:', student);
        
        let penilaianData = {};
        if (penilaian && penilaian.penilaian_detail) {
            try {
                penilaianData = JSON.parse(penilaian.penilaian_detail);
            } catch (e) {
                console.error('Error parsing penilaian_detail:', e);
            }
        }

        let html = '<form id="formPenilaian">';
        html += '<div class="alert alert-info mb-3">';
        html += '<strong><i class="bi bi-info-circle"></i> Petunjuk Penilaian:</strong><br>';
        html += '<small>';
        html += '<strong>Berkembang:</strong> Siswa mulai menunjukkan kemampuan dasar<br>';
        html += '<strong>Cakap:</strong> Siswa menunjukkan kemampuan yang memadai (Default)<br>';
        html += '<strong>Mahir:</strong> Siswa menunjukkan kemampuan yang sangat baik';
        html += '</small>';
        html += '</div>';
        
        html += '<div class="mb-3"><h6 class="text-primary"><i class="bi bi-clipboard-data"></i> Penilaian Per Sub Dimensi</h6></div>';

        if (!rubrik || rubrik.length === 0) {
            html += '<div class="alert alert-warning">Tidak ada rubrik penilaian yang tersedia.</div>';
        } else {
            rubrik.forEach(function(r, index) {
                console.log('Processing rubrik item:', index, r);
                const capaian = penilaianData[r.id] || 'Cakap'; // Default: Cakap
                
                html += '<div class="card mb-3">';
                html += '<div class="card-body">';
                html += '<div class="row">';
                html += '<div class="col-md-5">';
                html += '<div class="mb-2">';
                html += '<span class="badge bg-primary mb-1"><i class="bi bi-star-fill"></i> ' + r.dimensi_profil + '</span>';
                if (r.sub_dimensi) {
                    html += '<br><span class="badge bg-secondary"><i class="bi bi-arrow-return-right"></i> ' + r.sub_dimensi + '</span>';
                }
                html += '</div>';
                html += '<small class="text-muted">' + r.aspek_dinilai + '</small>';
                html += '</div>';
                html += '<div class="col-md-7">';
                html += '<div class="btn-group w-100" role="group">';
                
                const capaianOptions = [
                    {value: 'Berkembang', label: 'Berkembang', class: 'warning', icon: 'arrow-up-circle'},
                    {value: 'Cakap', label: 'Cakap', class: 'primary', icon: 'check-circle'},
                    {value: 'Mahir', label: 'Mahir', class: 'success', icon: 'star-fill'}
                ];
                
                capaianOptions.forEach(function(opt) {
                    const checked = capaian === opt.value ? 'checked' : '';
                    html += '<input type="radio" class="btn-check" name="rubrik_' + r.id + '" id="rubrik_' + r.id + '_' + opt.value + '" value="' + opt.value + '" ' + checked + '>';
                    html += '<label class="btn btn-outline-' + opt.class + '" for="rubrik_' + r.id + '_' + opt.value + '">';
                    html += '<i class="bi bi-' + opt.icon + '"></i> ' + opt.label;
                    html += '</label>';
                });
                
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
        }

        html += '<div class="mt-3">';
        html += '<label class="form-label"><i class="bi bi-pencil"></i> Catatan Tambahan (Opsional)</label>';
        html += '<textarea class="form-control" id="catatan_tambahan" rows="3" placeholder="Catatan khusus atau anekdot untuk siswa ini...">' + (penilaian ? (penilaian.catatan_tambahan || '') : '') + '</textarea>';
        html += '</div>';

        html += '</form>';

        console.log('Generated HTML length:', html.length);
        $('#penilaianContent').html(html);
    }

    // Save penilaian
    $('#btnSavePenilaian').on('click', function() {
        const $btn = $(this);
        const $form = $('#formPenilaian');

        // Collect penilaian data
        const penilaianDetail = {};
        let allFilled = true;

        $form.find('input[type="radio"]:checked').each(function() {
            const name = $(this).attr('name');
            const rubrikId = name.replace('rubrik_', '');
            penilaianDetail[rubrikId] = $(this).val();
        });

        // Check if all rubrik filled
        const totalRubrik = $form.find('input[type="radio"]').length / 3; // 3 options per rubrik (Berkembang, Cakap, Mahir)
        if (Object.keys(penilaianDetail).length < totalRubrik) {
            alert('Mohon lengkapi semua penilaian!');
            return;
        }

        const catatanTambahan = $('#catatan_tambahan').val();

        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

        $.ajax({
            url: '<?= base_url('admin/kokurikuler/penilaian/save') ?>',
            type: 'POST',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                document_id: currentDocumentId,
                student_id: currentStudentId,
                penilaian_detail: JSON.stringify(penilaianDetail),
                catatan_tambahan: catatanTambahan
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Penilaian berhasil disimpan!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Penilaian');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Penilaian');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
