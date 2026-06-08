<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
    .table-penilaian {
        font-size: 0.9rem;
    }
    .table-penilaian th {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .select-capaian {
        width: 120px;
        font-size: 0.85rem;
    }
    .input-catatan {
        min-width: 200px;
        font-size: 0.85rem;
    }
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .badge-dimensi {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0"><?= $title ?></h5>
            <small class="text-muted">
                <?= esc($document['year_name']) ?> - Semester <?= esc($document['semester']) ?> | 
                Fase <?= esc($document['fase']) ?> - Kelas <?= esc($document['level_kelas']) ?>
            </small>
        </div>
        <a href="<?= base_url('admin/kokurikuler/penilaian') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="bi bi-star-fill text-primary"></i> Pilih Dimensi Profil</label>
                <select id="filterDimensi" class="form-select">
                    <option value="">-- Pilih Dimensi --</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="bi bi-arrow-return-right text-secondary"></i> Pilih Sub Dimensi</label>
                <select id="filterSubDimensi" class="form-select" disabled>
                    <option value="">-- Pilih Dimensi terlebih dahulu --</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" id="btnLoadPenilaian" class="btn btn-primary w-100" disabled>
                    <i class="bi bi-search"></i> Tampilkan Penilaian
                </button>
            </div>
        </div>
    </div>

    <!-- Selected Info -->
    <div id="selectedInfo" class="alert alert-info d-none">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="bi bi-info-circle"></i> Penilaian untuk:</strong><br>
                <span class="badge bg-primary badge-dimensi me-2" id="infoDimensi"></span>
                <span class="badge bg-secondary badge-dimensi" id="infoSubDimensi"></span>
            </div>
            <button type="button" id="btnSaveAll" class="btn btn-success">
                <i class="bi bi-save"></i> Simpan Semua Penilaian
            </button>
        </div>
    </div>

    <!-- Progress Summary -->
    <div id="progressSection" class="card shadow-sm mb-3 d-none">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <strong>Progress Penilaian:</strong> 
                    <span id="progressText">0 dari 0 siswa (0%)</span>
                </div>
                <div class="col-md-4">
                    <div class="progress" style="height: 25px;">
                        <div id="progressBar" class="progress-bar bg-secondary" role="progressbar" style="width: 0%;">
                            0%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Penilaian -->
    <div id="penilaianSection" class="d-none">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-penilaian table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">NIS</th>
                                <th width="25%">Nama Siswa</th>
                                <th width="15%">Capaian</th>
                                <th width="40%">Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="penilaianTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-data text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-muted">Pilih Dimensi dan Sub Dimensi</h5>
            <p class="text-muted">Pilih dimensi dan sub dimensi di atas untuk mulai melakukan penilaian</p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    const documentId = <?= $document['id'] ?>;
    let rubrikData = [];
    let studentsData = [];
    let currentRubrikId = null;
    let penilaianData = {}; // Store all penilaian data

    // Load rubrik data
    loadRubrikData();

    function loadRubrikData() {
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/penilaian/get-rubrik') ?>/' + documentId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Response from getRubrik:', response);
                if (response.success && response.rubrik) {
                    rubrikData = response.rubrik;
                    console.log('Rubrik data loaded:', rubrikData);
                    console.log('Total rubrik items:', rubrikData.length);
                    
                    // Show alert if regenerated
                    if (response.regenerated) {
                        console.log('⚠️ Rubrik was regenerated because sub_dimensi was missing');
                    }
                    
                    // Check if rubrik has sub_dimensi
                    let hasSubDimensi = rubrikData.some(r => r.sub_dimensi);
                    console.log('Has sub_dimensi:', hasSubDimensi);
                    
                    if (!hasSubDimensi && rubrikData.length > 0) {
                        alert('⚠️ PERINGATAN: Rubrik tidak memiliki data sub_dimensi!\n\nSilakan:\n1. Cek log server\n2. Atau hapus rubrik manual via database\n3. Kemudian buka halaman view dokumen untuk regenerate');
                    }
                    
                    populateDimensiFilter();
                } else {
                    alert('Gagal memuat rubrik: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                console.error('Error loading rubrik:', xhr);
                console.error('Response text:', xhr.responseText);
                alert('Terjadi kesalahan saat memuat rubrik. Cek console untuk detail.');
            }
        });
    }

    function populateDimensiFilter() {
        // Get unique dimensi
        const dimensiSet = new Set();
        rubrikData.forEach(r => {
            if (r.dimensi_profil) {
                dimensiSet.add(r.dimensi_profil);
            }
        });

        const $select = $('#filterDimensi');
        $select.empty().append('<option value="">-- Pilih Dimensi --</option>');
        
        dimensiSet.forEach(dimensi => {
            $select.append($('<option>', {
                value: dimensi,
                text: dimensi
            }));
        });
    }

    // Handle dimensi change
    $('#filterDimensi').on('change', function() {
        const selectedDimensi = $(this).val();
        const $subDimensiSelect = $('#filterSubDimensi');
        
        console.log('Selected Dimensi:', selectedDimensi);
        
        $subDimensiSelect.empty().prop('disabled', true);
        $('#btnLoadPenilaian').prop('disabled', true);
        
        if (selectedDimensi) {
            // Get sub dimensi for selected dimensi
            const subDimensiSet = new Set();
            rubrikData.forEach(r => {
                console.log('Checking rubrik:', r);
                if (r.dimensi_profil === selectedDimensi && r.sub_dimensi) {
                    console.log('Found matching sub_dimensi:', r.sub_dimensi);
                    subDimensiSet.add(r.sub_dimensi);
                }
            });
            
            console.log('Sub Dimensi Set:', Array.from(subDimensiSet));
            
            $subDimensiSelect.append('<option value="">-- Pilih Sub Dimensi --</option>');
            subDimensiSet.forEach(subDimensi => {
                $subDimensiSelect.append($('<option>', {
                    value: subDimensi,
                    text: subDimensi
                }));
            });
            
            $subDimensiSelect.prop('disabled', false);
        } else {
            $subDimensiSelect.append('<option value="">-- Pilih Dimensi terlebih dahulu --</option>');
        }
    });

    // Handle sub dimensi change
    $('#filterSubDimensi').on('change', function() {
        const selectedSubDimensi = $(this).val();
        $('#btnLoadPenilaian').prop('disabled', !selectedSubDimensi);
    });

    // Handle load penilaian
    $('#btnLoadPenilaian').on('click', function() {
        const selectedDimensi = $('#filterDimensi').val();
        const selectedSubDimensi = $('#filterSubDimensi').val();
        
        console.log('=== LOAD PENILAIAN ===');
        console.log('Selected Dimensi:', selectedDimensi);
        console.log('Selected Sub Dimensi:', selectedSubDimensi);
        
        if (!selectedDimensi || !selectedSubDimensi) {
            alert('Pilih dimensi dan sub dimensi terlebih dahulu');
            return;
        }
        
        // Find rubrik_id
        console.log('Searching in rubrikData:', rubrikData);
        const rubrik = rubrikData.find(r => {
            console.log('Checking rubrik:', r);
            console.log('  dimensi_profil:', r.dimensi_profil, '=== selectedDimensi:', selectedDimensi, '?', r.dimensi_profil === selectedDimensi);
            console.log('  sub_dimensi:', r.sub_dimensi, '=== selectedSubDimensi:', selectedSubDimensi, '?', r.sub_dimensi === selectedSubDimensi);
            return r.dimensi_profil === selectedDimensi && r.sub_dimensi === selectedSubDimensi;
        });
        
        console.log('Found rubrik:', rubrik);
        
        if (!rubrik) {
            alert('Rubrik tidak ditemukan!\n\nDimensi: ' + selectedDimensi + '\nSub Dimensi: ' + selectedSubDimensi);
            return;
        }
        
        currentRubrikId = rubrik.id;
        console.log('Current rubrik ID:', currentRubrikId);
        
        // Update info
        $('#infoDimensi').text(selectedDimensi);
        $('#infoSubDimensi').text(selectedSubDimensi);
        $('#selectedInfo').removeClass('d-none');
        
        // Load students and penilaian
        console.log('About to call loadPenilaianData()...');
        try {
            loadPenilaianData();
            console.log('loadPenilaianData() called successfully');
        } catch (error) {
            console.error('Error calling loadPenilaianData():', error);
        }
    });

    function loadPenilaianData() {
        console.log('=== LOAD PENILAIAN DATA ===');
        console.log('Document ID:', documentId);
        console.log('Current Rubrik ID:', currentRubrikId);
        
        if (!currentRubrikId) {
            console.error('ERROR: currentRubrikId is null or undefined!');
            alert('Error: Rubrik ID tidak valid');
            return;
        }
        
        const url = '<?= base_url('admin/kokurikuler/penilaian/get-students-penilaian') ?>/' + documentId + '/' + currentRubrikId;
        console.log('AJAX URL:', url);
        console.log('Sending AJAX request NOW...');
        
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                console.log('✓ AJAX beforeSend triggered');
                console.log('XHR object:', xhr);
            },
            success: function(response) {
                console.log('✓ AJAX Success! Response:', response);
                
                // Show debug info if available
                if (response.debug) {
                    console.log('📊 DEBUG INFO:', response.debug);
                    console.log('  - Document ID:', response.debug.document_id);
                    console.log('  - Rubrik ID:', response.debug.rubrik_id);
                    console.log('  - Class ID:', response.debug.class_id);
                    console.log('  - Year ID:', response.debug.year_id);
                    console.log('  - Students Count:', response.debug.students_count);
                }
                
                if (response.success) {
                    studentsData = response.students;
                    penilaianData = response.penilaian_data || {};
                    console.log('Students data:', studentsData);
                    console.log('Students count:', studentsData.length);
                    console.log('Penilaian data:', penilaianData);
                    console.log('Penilaian data keys:', Object.keys(penilaianData));
                    // Test lookup for first student
                    if (studentsData.length > 0) {
                        const firstId = studentsData[0].id;
                        console.log('First student id:', firstId, 'type:', typeof firstId);
                        console.log('penilaian_data type:', Array.isArray(penilaianData) ? 'array' : typeof penilaianData);
                        console.log('Lookup penilaianData[firstId]:', penilaianData[firstId]);
                        console.log('Lookup penilaianData[parseInt(firstId)]:', penilaianData[parseInt(firstId)]);
                    }
                    renderPenilaianTable();
                    
                    $('#emptyState').addClass('d-none');
                    $('#penilaianSection').removeClass('d-none');
                    $('#progressSection').removeClass('d-none');
                } else {
                    console.error('Response not success:', response.message);
                    alert('Gagal memuat data: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('✗ AJAX Error!');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                alert('Terjadi kesalahan saat memuat data penilaian\nStatus: ' + status + '\nError: ' + error);
            },
            complete: function() {
                console.log('✓ AJAX complete (finished)');
            }
        });
        
        console.log('AJAX request initiated (waiting for response...)');
    }

    function renderPenilaianTable() {
        const $tbody = $('#penilaianTableBody');
        $tbody.empty();
        
        if (studentsData.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada siswa ditemukan</td></tr>');
            return;
        }
        
        studentsData.forEach((student, index) => {
            const sid = String(student.id);
            const penilaian = penilaianData[sid] || penilaianData[parseInt(sid)] || {};
            const capaian = penilaian.capaian || 'Cakap';
            const catatan = penilaian.catatan || '';
            const isSaved = !!(penilaianData[sid] || penilaianData[parseInt(sid)]);
            
            const savedBadge = isSaved 
                ? '<span class="badge bg-success ms-1" style="font-size:0.7rem;">✓ Tersimpan</span>'
                : '<span class="badge bg-warning text-dark ms-1" style="font-size:0.7rem;">Belum disimpan</span>';
            
            const row = `
                <tr data-student-id="${student.id}" data-saved="${isSaved}">
                    <td>${index + 1}</td>
                    <td>${escapeHtml(student.nis)}</td>
                    <td>${escapeHtml(student.name)} ${savedBadge}</td>
                    <td>
                        <select class="form-select form-select-sm select-capaian" data-student-id="${student.id}">
                            <option value="Berkembang" ${capaian === 'Berkembang' ? 'selected' : ''}>🟡 Berkembang</option>
                            <option value="Cakap" ${capaian === 'Cakap' ? 'selected' : ''}>🔵 Cakap</option>
                            <option value="Mahir" ${capaian === 'Mahir' ? 'selected' : ''}>🟢 Mahir</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm input-catatan" 
                               data-student-id="${student.id}" 
                               value="${escapeHtml(catatan)}"
                               placeholder="Catatan untuk ${escapeHtml(student.name)}...">
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
        
        // Update progress after render
        updateProgress();
    }

    function updateProgress() {
        // Count only students that have been SAVED (data-saved="true"), not just filled in UI
        let saved = 0;
        $('tr[data-student-id]').each(function() {
            if ($(this).attr('data-saved') === 'true') {
                saved++;
            }
        });
        
        const total = studentsData.length;
        const percentage = total > 0 ? Math.round((saved / total) * 100) : 0;
        
        $('#progressText').text(`${saved} dari ${total} siswa tersimpan (${percentage}%)`);
        $('#progressBar').css('width', percentage + '%').text(percentage + '%');
        
        // Update progress bar color
        let progressClass = 'bg-secondary';
        if (percentage === 100) {
            progressClass = 'bg-success';
        } else if (percentage > 0) {
            progressClass = 'bg-warning';
        }
        $('#progressBar').removeClass('bg-secondary bg-warning bg-success').addClass(progressClass);
    }

    // Save all penilaian
    $('#btnSaveAll').on('click', function() {
        const $btn = $(this);
        
        // Collect all penilaian data
        const penilaianList = [];
        studentsData.forEach(student => {
            const capaian = $(`.select-capaian[data-student-id="${student.id}"]`).val();
            const catatan = $(`.input-catatan[data-student-id="${student.id}"]`).val();
            
            penilaianList.push({
                student_id: student.id,
                rubrik_id: currentRubrikId,
                capaian: capaian,
                catatan: catatan
            });
        });
        
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');
        
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/penilaian/save-batch') ?>',
            type: 'POST',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                document_id: documentId,
                rubrik_id: currentRubrikId,
                penilaian_list: JSON.stringify(penilaianList)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update penilaianData in memory and refresh UI directly (no reload needed)
                    penilaianList.forEach(item => {
                        penilaianData[String(item.student_id)] = {
                            capaian: item.capaian,
                            catatan: item.catatan
                        };
                        penilaianData[parseInt(item.student_id)] = {
                            capaian: item.capaian,
                            catatan: item.catatan
                        };
                    });
                    
                    // Mark all rows as saved
                    $('tr[data-student-id]').attr('data-saved', 'true');
                    
                    // Update badges to "Tersimpan"
                    $('tr[data-student-id]').each(function() {
                        $(this).find('.badge').removeClass('bg-warning text-dark').addClass('bg-success').text('✓ Tersimpan');
                    });
                    
                    // Update progress
                    updateProgress();
                    
                    alert('✓ Penilaian berhasil disimpan untuk semua siswa!');
                } else {
                    alert('Error: ' + response.message);
                }
                $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Semua Penilaian');
            },
            error: function(xhr) {
                console.error('Error saving penilaian:', xhr);
                alert('Terjadi kesalahan saat menyimpan penilaian');
                $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Simpan Semua Penilaian');
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>
<?= $this->endSection() ?>
