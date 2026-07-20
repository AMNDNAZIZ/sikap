<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* Fix Select2 dropdown width and positioning */
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container .select2-selection--multiple {
        min-height: 38px;
    }
    
    /* Prevent dropdown from overflowing */
    .select2-dropdown {
        z-index: 10000 !important;
    }
    
    /* Ensure dropdown stays within container */
    .form-group-select2 {
        position: relative;
        z-index: 1;
        margin-bottom: 1.5rem;
    }
    
    /* Add spacing between form groups */
    .mb-3 {
        margin-bottom: 1.5rem !important;
    }
    
    /* Ensure sections have proper spacing */
    #kaih_section, #lintas_disiplin_section, #lainnya_section {
        margin-top: 1rem;
        margin-bottom: 2rem;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><?= $title ?></h4>
            <small class="text-muted">Isi form di bawah untuk membuat dokumen rencana kokurikuler</small>
        </div>
        <a href="<?= base_url('admin/kokurikuler') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <form id="formKokurikuler">
        <?= csrf_field() ?>
        
        <!-- Step 1: Informasi Dasar -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-1-circle"></i> Informasi Dasar</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Tahun Ajaran (Auto-detect) -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="<?= $activeYear['year'] ?? 'Tidak ada tahun aktif' ?>" readonly>
                        <small class="text-muted">Otomatis terdeteksi dari tahun ajaran aktif</small>
                    </div>

                    <!-- Semester -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" id="semester" class="form-select" required>
                            <option value="">-- Pilih Semester --</option>
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </select>
                        <small class="text-muted">Pilih semester untuk rencana kokurikuler ini</small>
                    </div>

                    <!-- Fase -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fase <span class="text-danger">*</span></label>
                        <select name="fase" id="fase" class="form-select" required>
                            <option value="">-- Pilih Fase --</option>
                            <?php if ($schoolLevel == 1): // SD ?>
                                <option value="A">Fase A (Kelas 1-2)</option>
                                <option value="B">Fase B (Kelas 3-4)</option>
                                <option value="C">Fase C (Kelas 5-6)</option>
                            <?php elseif ($schoolLevel == 2): // SMP ?>
                                <option value="D">Fase D (Kelas 7-9)</option>
                            <?php elseif ($schoolLevel == 3): // SMA ?>
                                <option value="E">Fase E (Kelas 10)</option>
                                <option value="F">Fase F (Kelas 11-12)</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Level Kelas -->
                    <div class="col-md-6 mb-3 form-group-select2">
                        <label class="form-label">Level Kelas <span class="text-danger">*</span></label>
                        <select name="level_kelas[]" id="level_kelas" class="form-select" multiple required disabled>
                            <option value="">-- Pilih Fase terlebih dahulu --</option>
                        </select>
                        <small class="text-muted">Bisa pilih lebih dari satu kelas (sesuai fase yang dipilih)</small>
                    </div>

                    <!-- Jumlah Pertemuan -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah Pertemuan <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_pertemuan" id="jumlah_pertemuan" class="form-control" min="1" required>
                        <small class="text-muted">Masukkan jumlah pertemuan yang direncanakan</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Dimensi Profil Lulusan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-2-circle"></i> Dimensi Profil Lulusan (DPL)</h5>
            </div>
            <div class="card-body">
                <div class="mb-3 form-group-select2">
                    <label class="form-label">Pilih Dimensi Profil Lulusan <span class="text-danger">*</span></label>
                    <select name="dimensi_profil[]" id="dimensi_profil" class="form-select" multiple required>
                        <option value="Keimanan dan Ketakwaan Terhadap Tuhan YME">1. Keimanan dan Ketakwaan Terhadap Tuhan YME</option>
                        <option value="Kewargaan">2. Kewargaan</option>
                        <option value="Penalaran Kritis">3. Penalaran Kritis</option>
                        <option value="Kreativitas">4. Kreativitas</option>
                        <option value="Kemandirian">5. Kemandirian</option>
                        <option value="Kolaborasi">6. Kolaborasi</option>
                        <option value="Kesehatan">7. Kesehatan</option>
                        <option value="Komunikasi">8. Komunikasi</option>
                    </select>
                    <small class="text-muted">Pilih minimal 1 dan maksimal 3 dimensi</small>
                </div>

                <div class="alert alert-info">
                    <strong>8 Dimensi Profil Lulusan:</strong>
                    <ol class="mb-0 mt-2">
                        <li><strong>Keimanan dan Ketakwaan:</strong> Memiliki keyakinan teguh dan berakhlak mulia</li>
                        <li><strong>Kewargaan:</strong> Cinta tanah air dan bertanggung jawab pada lingkungan</li>
                        <li><strong>Penalaran Kritis:</strong> Berpikir logis, analitis, dan memecahkan masalah</li>
                        <li><strong>Kreativitas:</strong> Berpikir inovatif dan orisinal</li>
                        <li><strong>Kemandirian:</strong> Bertanggung jawab atas proses belajar</li>
                        <li><strong>Kolaborasi:</strong> Bekerja sama secara efektif</li>
                        <li><strong>Kesehatan:</strong> Fisik prima dan kesehatan mental seimbang</li>
                        <li><strong>Komunikasi:</strong> Berkomunikasi dengan baik</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Step 3: Tema dan Bentuk Kegiatan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-3-circle"></i> Tema dan Bentuk Kegiatan</h5>
            </div>
            <div class="card-body">
                <!-- Tema -->
                <div class="mb-3">
                    <label class="form-label">Tema Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" name="tema" id="tema" class="form-control" placeholder="Contoh: Menjaga Lingkungan Sekolah" required>
                    <small class="text-muted">Tema yang akan menjadi fokus kegiatan kokurikuler</small>
                </div>

                <!-- Jenis Kokurikuler (dulu: Bentuk Kegiatan) -->
                <div class="mb-3">
                    <label class="form-label">Jenis Kokurikuler <span class="text-danger">*</span></label>
                    <select name="jenis_kokurikuler" id="jenis_kokurikuler" class="form-select" required>
                        <option value="">-- Pilih Jenis Kokurikuler --</option>
                        <option value="lintas_disiplin">Kolaboratif Lintas Disiplin Ilmu</option>
                        <option value="7kaih">Melalui 7 KAIH (Kegiatan Anak Indonesia Hebat)</option>
                        <option value="lainnya">Kegiatan Lainnya</option>
                    </select>
                    <small class="text-muted">Pilih jenis kokurikuler yang akan dilaksanakan</small>
                </div>

                <!-- Bentuk Kegiatan Konkret (NEW!) -->
                <div class="mb-3">
                    <label class="form-label">Bentuk Kegiatan Konkret <span class="text-danger">*</span></label>
                    <input type="text" name="bentuk_kegiatan_konkret" id="bentuk_kegiatan_konkret" class="form-control" 
                           placeholder="Contoh: Membuat poster dan video edukasi digital" required>
                    <small class="text-muted">Jelaskan kegiatan nyata yang akan dilakukan siswa (akan muncul di rapor)</small>
                </div>

                <!-- Dynamic Content Based on Bentuk Kegiatan -->
                
                <!-- A. Lintas Disiplin Ilmu -->
                <div id="lintas_disiplin_section" class="d-none">
                    <div class="alert alert-primary">
                        <strong>Kolaboratif Lintas Disiplin Ilmu</strong><br>
                        Pilih minimal 2 mata pelajaran dan tujuan pembelajaran untuk setiap mata pelajaran.
                    </div>
                    
                    <div id="lintas_subjects_container">
                        <!-- Subject items will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_subject">
                        <i class="bi bi-plus-circle"></i> Tambah Mata Pelajaran
                    </button>
                </div>

                <!-- B. 7 KAIH -->
                <div id="kaih_section" class="d-none">
                    <div class="alert alert-primary">
                        <strong>7 KAIH (Kegiatan Anak Indonesia Hebat)</strong><br>
                        Pilih minimal 1 kegiatan dari 7 kebiasaan positif dan tentukan dimensi profil untuk setiap kebiasaan.
                    </div>
                    
                    <div id="kaih_items_container">
                        <!-- KAIH items will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_kaih">
                        <i class="bi bi-plus-circle"></i> Tambah Kebiasaan
                    </button>
                </div>

                <!-- C. Lainnya -->
                <div id="lainnya_section" class="d-none">
                    <div class="alert alert-primary">
                        <strong>Kegiatan Lainnya</strong><br>
                        Jelaskan nilai-nilai, kekhasan sekolah, keunggulan yang dimiliki, atau kebijakan daerah setempat.
                    </div>
                    
                    <textarea name="lainnya_text" id="lainnya_text" class="form-control mb-3" rows="5" 
                              placeholder="Jelaskan kegiatan lainnya yang akan dilakukan..."></textarea>

                    <!-- Sub Dimensi untuk Penilaian -->
                    <div class="card border-secondary mb-2">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-list-check me-1"></i> Sub Dimensi untuk Penilaian</h6>
                            <small class="text-muted">Tentukan sub dimensi Profil Pelajar Pancasila yang menjadi aspek penilaian kegiatan ini.</small>
                        </div>
                        <div class="card-body pb-2">
                            <div id="lainnya_subdimensi_container">
                                <!-- Akan diisi dinamis -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn_add_lainnya_subdimensi">
                                <i class="bi bi-plus-circle"></i> Tambah Sub Dimensi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Kemitraan dan Teknologi -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-4-circle"></i> Kemitraan dan Teknologi Digital</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Kemitraan -->
                    <div class="col-md-6 mb-3 form-group-select2">
                        <label class="form-label">Kemitraan Pembelajaran</label>
                        <select name="kemitraan[]" id="kemitraan" class="form-select" multiple>
                            <option value="Kepala Sekolah">Kepala Sekolah</option>
                            <option value="Pendidik">Pendidik</option>
                            <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                            <option value="Warga Sekolah Lain">Warga Sekolah Lain</option>
                            <option value="Orang Tua">Orang Tua</option>
                            <option value="Komunitas">Komunitas</option>
                            <option value="Dunia Industri">Dunia Industri</option>
                            <option value="Tokoh Masyarakat">Tokoh Masyarakat</option>
                            <option value="Instansi Lain">Instansi Lain</option>
                            <option value="Media">Media</option>
                        </select>
                        <small class="text-muted">Pilih pihak yang terlibat dalam pembelajaran</small>
                    </div>

                    <!-- Teknologi Digital -->
                    <div class="col-md-6 mb-3 form-group-select2">
                        <label class="form-label">Pemanfaatan Teknologi Digital</label>
                        <select name="teknologi_digital[]" id="teknologi_digital" class="form-select" multiple>
                            <option value="Media Sosial">Media Sosial</option>
                            <option value="Tayangan Video">Tayangan Video</option>
                            <option value="Internet">Internet</option>
                        </select>
                        <small class="text-muted">Pilih teknologi yang akan digunakan</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="<?= base_url('admin/kokurikuler') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" id="btn_submit">
                        <i class="bi bi-save"></i> Simpan & Generate AI
                    </button>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Setelah menyimpan, sistem akan menggunakan AI untuk menghasilkan:<br>
                        Tujuan Pembelajaran, Praktik Pedagogis, Lingkungan Belajar, dan Kegiatan Kokurikuler
                    </small>
                </div>
            </div>
        </div>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Template for Lintas Disiplin Subject Item -->
<script type="text/template" id="subject_item_template">
    <div class="card mb-3 subject-item" data-subject-index="{{INDEX}}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Mata Pelajaran <span class="subject-number"></span></h6>
                <button type="button" class="btn btn-sm btn-danger btn-remove-subject">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Pilih Mata Pelajaran</label>
                <select name="lintas_subjects[]" class="form-select subject-select" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= esc($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3 form-group-select2">
                <label class="form-label">Pilih Tujuan Pembelajaran</label>
                <select name="lintas_tujuan_{{INDEX}}[]" class="form-select atp-select" multiple required>
                    <option value="">-- Pilih mata pelajaran terlebih dahulu --</option>
                </select>
                <small class="text-muted">Pilih tujuan pembelajaran dari ATP</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Dimensi Profil untuk TP ini <span class="text-danger">*</span></label>
                <select name="lintas_dimensi_{{INDEX}}" class="form-select dimensi-select" required>
                    <option value="">-- Pilih Dimensi Profil --</option>
                </select>
                <small class="text-muted">Pilih dimensi profil yang sesuai dengan tujuan pembelajaran ini</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Sub Dimensi <span class="text-danger">*</span></label>
                <select name="lintas_subdimensi_{{INDEX}}" class="form-select subdimensi-select" required disabled>
                    <option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>
                </select>
                <small class="text-muted">Pilih sub dimensi yang sesuai</small>
            </div>
        </div>
    </div>
</script>

<!-- Template for 7 KAIH Item -->
<script type="text/template" id="kaih_item_template">
    <div class="card mb-3 kaih-item" data-kaih-index="{{INDEX}}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Kebiasaan <span class="kaih-number"></span></h6>
                <button type="button" class="btn btn-sm btn-danger btn-remove-kaih">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Pilih Kebiasaan 7 KAIH <span class="text-danger">*</span></label>
                <select name="kaih_items_{{INDEX}}" class="form-select kaih-select" required>
                    <option value="">-- Pilih Kebiasaan --</option>
                    <option value="Bangun Pagi">1. Bangun Pagi</option>
                    <option value="Beribadah">2. Beribadah</option>
                    <option value="Berolahraga">3. Berolahraga</option>
                    <option value="Makan Sehat">4. Makan Sehat</option>
                    <option value="Belajar">5. Belajar</option>
                    <option value="Bermasyarakat">6. Bermasyarakat</option>
                    <option value="Tidur Lebih Awal">7. Tidur Lebih Awal</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Dimensi Profil untuk Kebiasaan ini <span class="text-danger">*</span></label>
                <select name="kaih_dimensi_{{INDEX}}" class="form-select kaih-dimensi-select" required>
                    <option value="">-- Pilih Dimensi Profil --</option>
                </select>
                <small class="text-muted">Pilih dimensi profil yang sesuai dengan kebiasaan ini</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Sub Dimensi <span class="text-danger">*</span></label>
                <select name="kaih_subdimensi_{{INDEX}}" class="form-select kaih-subdimensi-select" required disabled>
                    <option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>
                </select>
                <small class="text-muted">Pilih sub dimensi yang sesuai</small>
            </div>
        </div>
    </div>
</script>
                <small class="text-muted">Pilih dimensi profil yang sesuai dengan kebiasaan ini</small>
            </div>
        </div>
    </div>
</script>

<!-- Template for Lainnya Sub Dimensi Item -->
<script type="text/template" id="lainnya_subdimensi_template">
    <div class="d-flex gap-2 mb-2 lainnya-subdimensi-item" data-index="{{INDEX}}">
        <select name="lainnya_dimensi_{{INDEX}}" class="form-select form-select-sm lainnya-dimensi-select" required>
            <option value="">-- Dimensi --</option>
        </select>
        <select name="lainnya_subdimensi_{{INDEX}}" class="form-select form-select-sm lainnya-subdimensi-select" required disabled>
            <option value="">-- Sub Dimensi --</option>
        </select>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-lainnya flex-shrink-0">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</script>

<script>
$(document).ready(function() {
    let subjectCount = 0;

    // ========== Sub Dimensi Data ==========
    const subDimensiData = <?= json_encode($subDimensiData) ?>;

    // ========== Helper Functions ==========
    
    // Populate Sub Dimensi Options based on selected Dimensi
    function populateSubDimensiOptions($select, dimensi) {
        $select.empty();
        $select.append('<option value="">-- Pilih Sub Dimensi --</option>');
        
        if (dimensi && subDimensiData[dimensi]) {
            subDimensiData[dimensi].forEach(function(subDimensi) {
                $select.append(new Option(subDimensi, subDimensi));
            });
            $select.prop('disabled', false);
        } else {
            $select.append('<option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>');
            $select.prop('disabled', true);
        }
    }
    
    // Populate Dimensi Profil Options
    function populateDimensiOptions($select, selectedDimensi = null) {
        if (!selectedDimensi) {
            selectedDimensi = $('#dimensi_profil').val();
        }
        
        const currentValue = $select.val();
        $select.empty();
        $select.append('<option value="">-- Pilih Dimensi Profil --</option>');
        
        if (selectedDimensi && selectedDimensi.length > 0) {
            selectedDimensi.forEach(function(dimensi) {
                $select.append(new Option(dimensi, dimensi));
            });
            
            // Restore previous value if still valid
            if (currentValue && selectedDimensi.includes(currentValue)) {
                $select.val(currentValue);
            }
        }
    }

    // Initialize Select2
    $('#level_kelas').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih level kelas',
        allowClear: true,
        dropdownParent: $('#level_kelas').parent(),
        width: '100%'
    });

    $('#dimensi_profil').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih dimensi profil (min 1, max 3)',
        maximumSelectionLength: 3,
        allowClear: true,
        dropdownParent: $('#dimensi_profil').parent(),
        width: '100%'
    });

    $('#kaih_items').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih kegiatan 7 KAIH (minimal 1)',
        allowClear: true,
        dropdownParent: $('body'),
        width: '100%'
    });

    $('#kemitraan').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih kemitraan pembelajaran',
        allowClear: true,
        dropdownParent: $('#kemitraan').parent(),
        width: '100%'
    });

    $('#teknologi_digital').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih teknologi digital',
        allowClear: true,
        dropdownParent: $('#teknologi_digital').parent(),
        width: '100%'
    });

    // Handle Fase Change - Update Level Kelas Options
    $('#fase').on('change', function() {
        const fase = $(this).val();
        const $levelKelas = $('#level_kelas');
        
        // Clear current options
        $levelKelas.empty();
        
        if (fase) {
            // Enable the select
            $levelKelas.prop('disabled', false);
            
            // Add options based on fase
            let options = [];
            switch(fase) {
                case 'A':
                    options = [
                        {value: '1', text: 'Kelas 1'},
                        {value: '2', text: 'Kelas 2'}
                    ];
                    break;
                case 'B':
                    options = [
                        {value: '3', text: 'Kelas 3'},
                        {value: '4', text: 'Kelas 4'}
                    ];
                    break;
                case 'C':
                    options = [
                        {value: '5', text: 'Kelas 5'},
                        {value: '6', text: 'Kelas 6'}
                    ];
                    break;
                case 'D':
                    options = [
                        {value: '7', text: 'Kelas 7'},
                        {value: '8', text: 'Kelas 8'},
                        {value: '9', text: 'Kelas 9'}
                    ];
                    break;
                case 'E':
                    options = [
                        {value: '10', text: 'Kelas 10'}
                    ];
                    break;
                case 'F':
                    options = [
                        {value: '11', text: 'Kelas 11'},
                        {value: '12', text: 'Kelas 12'}
                    ];
                    break;
            }
            
            // Add options to select
            options.forEach(function(opt) {
                $levelKelas.append(new Option(opt.text, opt.value, false, false));
            });
            
            // Trigger change to update Select2
            $levelKelas.trigger('change');
        } else {
            // Disable if no fase selected
            $levelKelas.prop('disabled', true);
            $levelKelas.append(new Option('-- Pilih Fase terlebih dahulu --', '', false, false));
            $levelKelas.trigger('change');
        }
    });

    // Handle Jenis Kokurikuler Change (dulu: Bentuk Kegiatan)
    $('#jenis_kokurikuler').on('change', function() {
        const value = $(this).val();
        
        // Hide all sections
        $('#lintas_disiplin_section, #kaih_section, #lainnya_section').addClass('d-none');
        
        // Show selected section
        if (value === 'lintas_disiplin') {
            $('#lintas_disiplin_section').removeClass('d-none');
            // Add first subject if empty
            if (subjectCount === 0) {
                addSubjectItem();
            }
        } else if (value === '7kaih') {
            $('#kaih_section').removeClass('d-none');
            // Reinitialize Select2 for kaih_items to fix positioning
            $('#kaih_items').select2('destroy');
            $('#kaih_items').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih kegiatan 7 KAIH (minimal 1)',
                allowClear: true,
                dropdownParent: $('body'),
                width: '100%'
            });
        } else if (value === 'lainnya') {
            $('#lainnya_section').removeClass('d-none');
            // Add first sub-dimension if empty
            if ($('.lainnya-subdimensi-item').length === 0) {
                addLainnyaSubDimensiItem();
            }
        }
    });

    // Add Subject Item
    $('#btn_add_subject').on('click', function() {
        addSubjectItem();
    });

    function addSubjectItem() {
        subjectCount++;
        let template = $('#subject_item_template').html();
        
        // Replace {{INDEX}} with actual index
        template = template.replace(/\{\{INDEX\}\}/g, subjectCount);
        
        const $item = $(template);
        
        $item.find('.subject-number').text(subjectCount);
        $item.attr('data-subject-index', subjectCount);
        
        $('#lintas_subjects_container').append($item);
        
        // Initialize Select2 for ATP
        $item.find('.atp-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih tujuan pembelajaran',
            allowClear: true,
            dropdownParent: $item.find('.atp-select').parent(),
            width: '100%'
        });
        
        // Populate dimensi profil options for this item
        populateDimensiOptions($item.find('.dimensi-select'));
        
        updateSubjectNumbers();
    }

    // Remove Subject Item
    $(document).on('click', '.btn-remove-subject', function() {
        if ($('.subject-item').length > 1) {
            $(this).closest('.subject-item').remove();
            updateSubjectNumbers();
        } else {
            alert('Minimal harus ada 1 mata pelajaran');
        }
    });

    function updateSubjectNumbers() {
        $('.subject-item').each(function(index) {
            $(this).find('.subject-number').text(index + 1);
        });
    }

    // Handle Dimensi Change in Lintas Disiplin - Populate Sub Dimensi
    $(document).on('change', '.dimensi-select', function() {
        const dimensi = $(this).val();
        const $subDimensiSelect = $(this).closest('.subject-item').find('.subdimensi-select');
        populateSubDimensiOptions($subDimensiSelect, dimensi);
    });

    // ========== 7 KAIH Management ==========
    let kaihCount = 0;

    // Add KAIH Item
    $('#btn_add_kaih').on('click', function() {
        addKaihItem();
    });

    function addKaihItem() {
        const template = $('#kaih_item_template').html();
        const html = template.replace(/\{\{INDEX\}\}/g, kaihCount);
        
        $('#kaih_items_container').append(html);
        
        // Populate dimensi profil options
        populateDimensiOptions($('.kaih-item[data-kaih-index="' + kaihCount + '"]').find('.kaih-dimensi-select'));
        
        kaihCount++;
        updateKaihNumbers();
    }

    // Remove KAIH Item
    $(document).on('click', '.btn-remove-kaih', function() {
        if ($('.kaih-item').length > 1) {
            $(this).closest('.kaih-item').remove();
            updateKaihNumbers();
        } else {
            alert('Minimal harus ada 1 kebiasaan');
        }
    });

    function updateKaihNumbers() {
        $('.kaih-item').each(function(index) {
            $(this).find('.kaih-number').text(index + 1);
        });
    }

    // Handle Dimensi Change in KAIH - Populate Sub Dimensi
    $(document).on('change', '.kaih-dimensi-select', function() {
        const dimensi = $(this).val();
        const $subDimensiSelect = $(this).closest('.kaih-item').find('.kaih-subdimensi-select');
        populateSubDimensiOptions($subDimensiSelect, dimensi);
    });

    // ========== Lainnya Sub Dimensi Management ==========
    let lainnyaSubdimensiCount = 0;

    $('#btn_add_lainnya_subdimensi').on('click', function() {
        addLainnyaSubDimensiItem();
    });

    function addLainnyaSubDimensiItem() {
        let template = $('#lainnya_subdimensi_template').html();
        template = template.replace(/\{\{INDEX\}\}/g, lainnyaSubdimensiCount);
        
        const $item = $(template);
        $('#lainnya_subdimensi_container').append($item);
        
        // Populate dimensi options
        populateDimensiOptions($item.find('.lainnya-dimensi-select'));
        
        lainnyaSubdimensiCount++;
    }

    // Remove Lainnya Sub Dimensi Item
    $(document).on('click', '.btn-remove-lainnya', function() {
        $(this).closest('.lainnya-subdimensi-item').remove();
    });

    // Handle Dimensi Change in Lainnya - Populate Sub Dimensi
    $(document).on('change', '.lainnya-dimensi-select', function() {
        const dimensi = $(this).val();
        const $subDimensiSelect = $(this).closest('.lainnya-subdimensi-item').find('.lainnya-subdimensi-select');
        populateSubDimensiOptions($subDimensiSelect, dimensi);
    });

    // ========== Dimensi Profil Management ==========
    
    // When dimensi profil changes, update all dimensi select options
    $('#dimensi_profil').on('change', function() {
        const selectedDimensi = $(this).val();
        
        // Update lintas disiplin dimensi selects
        $('.dimensi-select').each(function() {
            populateDimensiOptions($(this), selectedDimensi);
        });
        
        // Update KAIH dimensi selects
        $('.kaih-dimensi-select').each(function() {
            populateDimensiOptions($(this), selectedDimensi);
        });
        
        // Update lainnya dimensi selects
        $('.lainnya-dimensi-select').each(function() {
            populateDimensiOptions($(this), selectedDimensi);
        });
    });

    // Load ATP when subject is selected
    $(document).on('change', '.subject-select', function() {
        const subjectId = $(this).val();
        const $atpSelect = $(this).closest('.subject-item').find('.atp-select');
        
        if (subjectId) {
            $.ajax({
                url: '<?= base_url('admin/kokurikuler/get-atp') ?>/' + subjectId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $atpSelect.empty();
                        
                        if (response.data.length > 0) {
                            response.data.forEach(function(tp) {
                                // Display kode_tp and deskripsi
                                const label = tp.kode_tp ? tp.kode_tp + ' - ' + tp.deskripsi : tp.deskripsi;
                                $atpSelect.append(new Option(label, tp.id));
                            });
                        } else {
                            $atpSelect.append(new Option('Tidak ada Tujuan Pembelajaran untuk mata pelajaran ini', ''));
                        }
                        
                        $atpSelect.trigger('change');
                    }
                },
                error: function() {
                    alert('Gagal memuat tujuan pembelajaran');
                }
            });
        } else {
            $atpSelect.empty().append(new Option('-- Pilih mata pelajaran terlebih dahulu --', ''));
        }
    });

    // Form Submit
    $('#formKokurikuler').on('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const jenisKokurikuler = $('#jenis_kokurikuler').val();
        
        if (jenisKokurikuler === 'lintas_disiplin') {
            if ($('.subject-item').length < 2) {
                alert('Minimal harus ada 2 mata pelajaran untuk lintas disiplin ilmu');
                return false;
            }
        } else if (jenisKokurikuler === '7kaih') {
            if ($('.kaih-item').length === 0) {
                alert('Minimal pilih 1 kegiatan 7 KAIH');
                return false;
            }
        } else if (jenisKokurikuler === 'lainnya') {
            if ($('#lainnya_text').val().trim() === '') {
                alert('Jelaskan kegiatan lainnya yang akan dilakukan');
                return false;
            }
            if ($('.lainnya-subdimensi-item').length === 0) {
                alert('Minimal harus ada 1 sub dimensi untuk penilaian');
                return false;
            }
        }
        
        const dimensi = $('#dimensi_profil').val();
        if (dimensi.length === 0 || dimensi.length > 3) {
            alert('Pilih minimal 1 dan maksimal 3 dimensi profil lulusan');
            return false;
        }
        
        // Validate bentuk kegiatan konkret
        const bentukKegiatanKonkret = $('#bentuk_kegiatan_konkret').val().trim();
        if (bentukKegiatanKonkret.length < 10) {
            alert('Bentuk kegiatan konkret minimal 10 karakter');
            return false;
        }
        
        // Prepare data
        const formData = new FormData(this);
        
        // Convert level_kelas array to comma-separated string
        const levelKelas = $('#level_kelas').val();
        formData.delete('level_kelas[]');
        formData.append('level_kelas', levelKelas.join(','));
        
        // Disable submit button
        $('#btn_submit').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');
        
        // Submit via AJAX
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/store') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.message);
                    
                    // Redirect to generate AI page or view page
                    window.location.href = '<?= base_url('admin/kokurikuler/view') ?>/' + response.document_id;
                } else {
                    alert('Error: ' + response.message);
                    $('#btn_submit').prop('disabled', false).html('<i class="bi bi-save"></i> Simpan & Generate AI');
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
                $('#btn_submit').prop('disabled', false).html('<i class="bi bi-save"></i> Simpan & Generate AI');
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
