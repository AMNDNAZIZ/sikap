<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Kartu Peserta page */
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
    margin: 0 !important;
    opacity: 0.95 !important;
    font-size: 0.95rem !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
}

.modern-card-body {
    padding: 2rem !important;
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

.form-control, .form-select {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.form-label {
    font-weight: 500 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
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

.btn-primary-gradient:hover {
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    color: white !important;
}

.info-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-left: 4px solid #667eea !important;
    padding: 1rem !important;
    border-radius: 8px !important;
    margin-bottom: 1.5rem !important;
}

.icon-large {
    font-size: 3rem !important;
    color: #667eea !important;
    margin-bottom: 1rem !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-id-card"></i> Kartu Peserta Ujian</h1>
        <p>Cetak kartu peserta ujian untuk siswa berdasarkan ujian dan kelas</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Main Card -->
            <div class="modern-card">
                <div class="modern-card-body">
                    <!-- Icon -->
                    <div class="text-center">
                        <div class="icon-large">
                            <i class="fas fa-id-card-alt"></i>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-modern alert-danger-gradient">
                            <i class="fas fa-times-circle"></i>
                            <div><?= session()->getFlashdata('error') ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-modern alert-success-gradient">
                            <i class="fas fa-check-circle"></i>
                            <div><?= session()->getFlashdata('success') ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Info Box -->
                    <div class="info-box">
                        <small>
                            <i class="fas fa-info-circle"></i> <strong>Informasi:</strong><br>
                            Pilih nama ujian dan kelas untuk mencetak kartu peserta ujian secara massal.
                        </small>
                    </div>

                    <!-- Form -->
                    <form id="kartuForm">
                        <!-- PILIH NAMA UJIAN -->
                        <div class="mb-3">
                            <label for="examId" class="form-label">
                                <i class="fas fa-clipboard-list text-primary"></i> Pilih Nama Ujian
                            </label>
                            <select name="examId" id="examId" class="form-select" required>
                                <option value="">-- Pilih Ujian --</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?= esc($exam['id']) ?>"><?= esc($exam['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- PILIH KELAS -->
                        <div class="mb-3">
                            <label for="classId" class="form-label">
                                <i class="fas fa-door-open text-success"></i> Pilih Kelas
                            </label>
                            <select name="classId" id="classId" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= esc($class['id']) ?>"><?= esc($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" id="previewBtn" class="btn btn-modern btn-primary-gradient btn-lg">
                                <i class="fas fa-eye"></i> Pratinjau Kartu Peserta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('previewBtn').addEventListener('click', function(e) {
    e.preventDefault();

    const examId  = document.getElementById('examId').value;
    const classId = document.getElementById('classId').value;

    if (!examId) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih nama ujian terlebih dahulu.'
        });
        return;
    }
    if (!classId) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih kelas terlebih dahulu.'
        });
        return;
    }

    // Arahkan langsung ke halaman pratinjau
    const url = '<?= site_url('admin/cbt/kartu-peserta/cetakMassal/') ?>' + examId + '/' + classId;
    window.location.href = url;
});
</script>
<?= $this->endSection() ?>
