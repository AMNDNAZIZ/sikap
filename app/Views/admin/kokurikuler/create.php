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

    <!-- ✨ KONSULTAN AI PERENCANA KOKURIKULER ESPATA ✨ -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8faff 0%, #eef5ff 100%); border-left: 5px solid #0d6efd !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-stars"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold text-dark">Konsultan AI Perencana Kokurikuler</h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">ESPATA AI Assistant</span>
                        </div>
                        <small class="text-muted">Rancang projek kokurikuler lintas disiplin ilmu secara otomatis berdasarkan analisis jenjang, fase, dan Tujuan Pembelajaran (TP) aktif.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn_toggle_ai_consultant" data-bs-toggle="collapse" data-bs-target="#ai_consultant_collapse">
                    <i class="bi bi-sliders"></i> Filter Parameter
                </button>
            </div>

            <div class="collapse show" id="ai_consultant_collapse">
                <div class="row g-3 align-items-end p-3 bg-white rounded-3 border">
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Semester</label>
                        <select id="ai_consult_semester" class="form-select form-select-sm">
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Fase</label>
                        <select id="ai_consult_fase" class="form-select form-select-sm">
                            <?php if ($schoolLevel == 1): // SD ?>
                                <option value="A" selected>Fase A (Kelas 1-2)</option>
                                <option value="B">Fase B (Kelas 3-4)</option>
                                <option value="C">Fase C (Kelas 5-6)</option>
                            <?php elseif ($schoolLevel == 2): // SMP ?>
                                <option value="D" selected>Fase D (Kelas 7-9)</option>
                            <?php elseif ($schoolLevel == 3): // SMA ?>
                                <option value="E" selected>Fase E (Kelas 10)</option>
                                <option value="F">Fase F (Kelas 11-12)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Level Kelas</label>
                        <select id="ai_consult_level_kelas" class="form-select form-select-sm">
                            <!-- Populated dynamically based on Fase -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Fokus / Minat Topik (Opsional)</label>
                        <input type="text" id="ai_consult_focus" class="form-control form-control-sm" placeholder="Misal: Daur ulang sampah, Kuliner lokal, Digital...">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_run_ai_consult" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                            <i class="bi bi-magic me-1"></i> Analisis AI
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="ai_consult_loading" class="text-center py-4 d-none">
                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                <div class="fw-semibold text-primary small">AI Asisten ESPATA sedang menganalisis database TP dan merancang kolaborasi lintas mapel...</div>
                <small class="text-muted">Menyinkronkan capaian pembelajaran dengan dimensi profil lulusan Kurikulum Merdeka</small>
            </div>

            <!-- Error State -->
            <div id="ai_consult_error" class="alert alert-danger alert-dismissible fade show mt-3 d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="ai_consult_error_text">Terjadi kesalahan.</span>
            </div>

            <!-- AI Recommended Packages Grid -->
            <div id="ai_consult_packages" class="mt-3 row g-3 d-none">
                <!-- Populated via JS -->
            </div>
        </div>
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

    // =========================================================================
    // ✨ ASISTEN AI KONSULTAN PERENCANA KOKURIKULER ESPATA ✨
    // =========================================================================
    let currentAiPackages = [];

    // Dinamisasi Pilihan Level Kelas pada Panel AI Consultant
    function updateAiConsultLevelOptions(fase) {
        const $select = $('#ai_consult_level_kelas');
        $select.empty();
        
        switch(fase) {
            case 'A':
                $select.append(new Option('Semua Kelas Fase A (1-2)', ''));
                $select.append(new Option('Kelas 1 Saja', '1'));
                $select.append(new Option('Kelas 2 Saja', '2'));
                break;
            case 'B':
                $select.append(new Option('Semua Kelas Fase B (3-4)', ''));
                $select.append(new Option('Kelas 3 Saja', '3'));
                $select.append(new Option('Kelas 4 Saja', '4'));
                break;
            case 'C':
                $select.append(new Option('Semua Kelas Fase C (5-6)', ''));
                $select.append(new Option('Kelas 5 Saja', '5'));
                $select.append(new Option('Kelas 6 Saja', '6'));
                break;
            case 'D':
                $select.append(new Option('Semua Kelas Fase D (7-9)', ''));
                $select.append(new Option('Kelas 7 Saja', '7'));
                $select.append(new Option('Kelas 8 Saja', '8'));
                $select.append(new Option('Kelas 9 Saja', '9'));
                break;
            case 'E':
                $select.append(new Option('Kelas 10', '10'));
                break;
            case 'F':
                $select.append(new Option('Semua Kelas Fase F (11-12)', ''));
                $select.append(new Option('Kelas 11 Saja', '11'));
                $select.append(new Option('Kelas 12 Saja', '12'));
                break;
            default:
                $select.append(new Option('Sesuai Fase', ''));
        }
    }

    // Inisialisasi awal
    updateAiConsultLevelOptions($('#ai_consult_fase').val());

    // Update opsi level kelas saat fase berubah
    $('#ai_consult_fase').on('change', function() {
        updateAiConsultLevelOptions($(this).val());
    });

    $('#btn_run_ai_consult').on('click', function() {
        const semester = $('#ai_consult_semester').val();
        const fase = $('#ai_consult_fase').val();
        const levelKelas = $('#ai_consult_level_kelas').val();
        const focusKeyword = $('#ai_consult_focus').val().trim();

        const $btn = $(this);
        const $loading = $('#ai_consult_loading');
        const $error = $('#ai_consult_error');
        const $packagesContainer = $('#ai_consult_packages');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menganalisis Level & TP...');
        $loading.removeClass('d-none');
        $error.addClass('d-none');
        $packagesContainer.addClass('d-none').empty();

        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = $('input[name="' + csrfName + '"]').val() || '<?= csrf_hash() ?>';

        const postData = {
            semester: semester,
            fase: fase,
            level_kelas: levelKelas,
            focus_keyword: focusKeyword
        };
        postData[csrfName] = csrfHash;

        $.ajax({
            url: '<?= base_url('admin/kokurikuler/recommend-ai') ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                if (res.success && res.packages && res.packages.length > 0) {
                    currentAiPackages = res.packages;
                    renderAiPackages(res.packages, res);
                    $packagesContainer.removeClass('d-none');
                } else {
                    $('#ai_consult_error_text').text(res.message || 'Tidak ada rekomendasi yang berhasil disusun.');
                    $error.removeClass('d-none');
                }
            },
            error: function(xhr) {
                let errText = 'Gagal menghubungi server konsultan AI.';
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed.message) errText = parsed.message;
                } catch(e) {}
                $('#ai_consult_error_text').text(errText);
                $error.removeClass('d-none');
            },
            complete: function() {
                $loading.addClass('d-none');
                $btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i> Analisis AI');
            }
        });
    });

    function renderAiPackages(packages, meta) {
        const $container = $('#ai_consult_packages');
        $container.empty();

        // Render Status & Origin Banner
        if (meta) {
            let bannerHtml = '';
            if (meta.source && meta.source.startsWith('ai_')) {
                const provName = (meta.provider || 'Groq').toUpperCase();
                bannerHtml = `
                    <div class="col-12 mb-2">
                        <div class="p-3 rounded-3 bg-white border border-success-subtle shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success text-white me-2 px-2 py-1"><i class="bi bi-cpu-fill me-1"></i> Generasi AI Real-Time</span>
                                <span class="small fw-semibold text-dark">Engine: <span class="badge bg-success-subtle text-success border border-success-subtle">${provName}: ${meta.model_used || 'Active Model'}</span></span>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-database-check text-success me-1"></i> Dianalisis dari <strong>${meta.total_tps_analyzed || 0} Tujuan Pembelajaran</strong> & <strong>${meta.total_subjects_analyzed || 0} Mata Pelajaran</strong> di <strong>${meta.school_name || 'Sekolah'}</strong>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                bannerHtml = `
                    <div class="col-12 mb-2">
                        <div class="p-3 rounded-3 bg-white border border-warning shadow-sm">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning text-dark me-2 px-2 py-1"><i class="bi bi-shield-check me-1"></i> Mode Kurasi Adaptif (Fallback)</span>
                                    <span class="small text-muted fw-semibold">Database Sekolah Aktif</span>
                                </div>
                                ${meta.diag_reason ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle small"><i class="bi bi-exclamation-triangle-fill me-1"></i>${meta.diag_reason}</span>` : ''}
                            </div>
                            <div class="small text-muted">
                                ${meta.diag_tip ? `<div class="mb-1 text-dark"><i class="bi bi-lightbulb-fill text-warning me-1"></i><strong>Petunjuk Solusi:</strong> ${meta.diag_tip}</div>` : ''}
                                <div><i class="bi bi-check2-circle text-success me-1"></i>Paket projek di bawah tetap secara cerdas dipetakan ke mata pelajaran dan bank TP aktif di sekolah Anda.</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            $container.append(bannerHtml);
        }

        packages.forEach(function(pkg, idx) {
            const dimensiBadges = (pkg.dimensi_profil || []).map(function(d) {
                return `<span class="badge bg-light text-dark border me-1 mb-1 small">${d}</span>`;
            }).join('');

            const kelasBadges = (pkg.level_kelas || []).map(function(k) {
                return `<span class="badge bg-secondary-subtle text-secondary me-1">Kelas ${k}</span>`;
            }).join('');

            let lintasItemsHtml = '';
            if (pkg.lintas_disiplin && pkg.lintas_disiplin.length > 0) {
                lintasItemsHtml = pkg.lintas_disiplin.map(function(ld) {
                    return `
                        <div class="p-2 mb-2 bg-light rounded border-start border-3 border-primary small">
                            <div class="fw-bold text-primary"><i class="bi bi-book me-1"></i>${ld.subject_name || 'Mapel'}</div>
                            <div class="text-muted"><i class="bi bi-bullseye me-1"></i>DPL: <strong>${ld.dimensi || '-'}</strong> (${ld.sub_dimensi || '-'})</div>
                        </div>
                    `;
                }).join('');
            } else {
                lintasItemsHtml = '<div class="text-muted small">Kolaboratif lintas mapel umum</div>';
            }

            const cardHtml = `
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary-subtle text-primary fw-bold">Opsi ${idx + 1}</span>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-tag-fill me-1"></i>${pkg.tema}</span>
                            </div>
                            
                            <h6 class="card-title fw-bold text-dark mb-2">${pkg.judul_paket}</h6>
                            
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1 fw-semibold">Target Level & Alokasi:</small>
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1"><i class="bi bi-mortarboard-fill me-1"></i>${(pkg.level_kelas || []).map(k => 'Kelas ' + k).join(', ')} (Fase ${meta.fase || 'D'})</span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">${pkg.jumlah_pertemuan || 16} Pertemuan</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block mb-1 fw-semibold">Dimensi Profil (DPL):</small>
                                <div>${dimensiBadges}</div>
                            </div>

                            <div class="p-2 mb-3 bg-primary-subtle rounded border border-primary-subtle small">
                                <strong class="d-block text-primary mb-1"><i class="bi bi-activity me-1"></i>Bentuk Nyata Kegiatan:</strong>
                                <span class="text-dark">${pkg.bentuk_kegiatan_konkret}</span>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block mb-1 fw-semibold">Kolaborasi Lintas Mapel:</small>
                                ${lintasItemsHtml}
                            </div>

                            ${pkg.alasan_analisis ? `
                                <div class="p-2 mb-3 bg-light rounded border small">
                                    <strong class="d-block text-secondary mb-1"><i class="bi bi-diagram-3-fill me-1 text-primary"></i>Analisis Keterkaitan TP & Level Kognitif:</strong>
                                    <span class="text-dark">${pkg.alasan_analisis}</span>
                                </div>
                            ` : ''}

                            <div class="mt-auto">
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-sm btn-apply-ai-package" data-index="${idx}">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Terapkan Saran ke Form (1-Klik)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $container.append(cardHtml);
        });
    }

    // Handle 1-Click Apply Recommendation
    $(document).on('click', '.btn-apply-ai-package', function() {
        const idx = $(this).data('index');
        const pkg = currentAiPackages[idx];
        if (!pkg) return;

        // 1. Set Semester
        const targetSemester = $('#ai_consult_semester').val() || '1';
        $('#semester').val(targetSemester).trigger('change');

        // 2. Set Fase
        const targetFase = $('#ai_consult_fase').val() || 'D';
        $('#fase').val(targetFase).trigger('change');

        // 3. Set Level Kelas (Wait 150ms for options to populate)
        setTimeout(function() {
            if (pkg.level_kelas && Array.isArray(pkg.level_kelas) && pkg.level_kelas.length > 0) {
                $('#level_kelas').val(pkg.level_kelas).trigger('change');
            } else if ($('#ai_consult_level_kelas').val()) {
                $('#level_kelas').val([$('#ai_consult_level_kelas').val()]).trigger('change');
            }
        }, 150);

        // 4. Set Jumlah Pertemuan
        $('#jumlah_pertemuan').val(pkg.jumlah_pertemuan || 16);

        // 5. Set Dimensi Profil Lulusan (Max 3)
        if (pkg.dimensi_profil && Array.isArray(pkg.dimensi_profil)) {
            const selectedDpl = pkg.dimensi_profil.slice(0, 3);
            $('#dimensi_profil').val(selectedDpl).trigger('change');
        }

        // 6. Set Tema & Jenis Kokurikuler
        $('#tema').val(pkg.tema);
        $('#jenis_kokurikuler').val('lintas_disiplin').trigger('change');

        // 7. Set Bentuk Kegiatan Konkret
        $('#bentuk_kegiatan_konkret').val(pkg.bentuk_kegiatan_konkret);

        // 8. Set Lintas Disiplin Subjects & TPs
        $('#lintas_subjects_container').empty();
        subjectCount = 0;

        if (pkg.lintas_disiplin && pkg.lintas_disiplin.length > 0) {
            pkg.lintas_disiplin.forEach(function(ld, i) {
                addSubjectItem();
                const $item = $('.subject-item').last();
                const $subjectSelect = $item.find('.subject-select');
                const $atpSelect = $item.find('.atp-select');
                const $dimensiSelect = $item.find('.dimensi-select');
                const $subDimensiSelect = $item.find('.subdimensi-select');

                // Set Subject
                $subjectSelect.val(ld.subject_id);

                // Fetch real TPs for this subject
                if (ld.subject_id) {
                    $.ajax({
                        url: '<?= base_url('admin/kokurikuler/get-atp') ?>/' + ld.subject_id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                $atpSelect.empty();
                                if (resp.data.length > 0) {
                                    resp.data.forEach(function(tp) {
                                        const label = tp.kode_tp ? tp.kode_tp + ' - ' + tp.deskripsi : tp.deskripsi;
                                        $atpSelect.append(new Option(label, tp.id));
                                    });

                                    // Pre-select TP IDs if provided
                                    if (ld.tp_ids && ld.tp_ids.length > 0) {
                                        const strIds = ld.tp_ids.map(String);
                                        $atpSelect.val(strIds);
                                    } else if (resp.data[0]) {
                                        // Pilih TP pertama secara default jika tidak spesifik
                                        $atpSelect.val([String(resp.data[0].id)]);
                                    }
                                } else {
                                    $atpSelect.append(new Option('Tidak ada Tujuan Pembelajaran untuk mata pelajaran ini', ''));
                                }
                                $atpSelect.trigger('change');
                            }
                        }
                    });
                }

                // Populate and set Dimensi
                setTimeout(function() {
                    populateDimensiOptions($dimensiSelect, pkg.dimensi_profil);
                    if (ld.dimensi) {
                        $dimensiSelect.val(ld.dimensi).trigger('change');
                        setTimeout(function() {
                            if (ld.sub_dimensi) {
                                $subDimensiSelect.val(ld.sub_dimensi);
                            }
                        }, 50);
                    }
                }, 100);
            });
        }

        // Show Feedback Banner
        $('#applied_ai_alert').remove();
        const alertHtml = `
            <div id="applied_ai_alert" class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Rekomendasi AI Berhasil Diterapkan ke Form!</h6>
                    <small>Seluruh isian dokumen untuk projek "<strong>${pkg.judul_paket}</strong>" telah terisi otomatis. Anda dapat meninjau atau menyesuaikan isian sebelum menekan tombol simpan.</small>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#formKokurikuler').prepend(alertHtml);

        // Scroll smoothly to Form
        $('html, body').animate({
            scrollTop: $('#formKokurikuler').offset().top - 70
        }, 500);
    });
});
</script>

<?= $this->endSection() ?>
