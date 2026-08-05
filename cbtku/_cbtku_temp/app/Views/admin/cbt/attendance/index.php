<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Attendance page */
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

.btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
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
        <h1><i class="fas fa-user-check"></i> Daftar Hadir Ujian</h1>
        <p>Cetak daftar hadir penilaian berdasarkan ujian dan ruang</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Main Card -->
            <div class="modern-card">
                <div class="modern-card-body">
                    <!-- Icon -->
                    <div class="text-center">
                        <div class="icon-large">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="info-box">
                        <small>
                            <i class="fas fa-info-circle"></i> <strong>Informasi:</strong><br>
                            Pilih nama ujian dan ruang untuk mencetak daftar hadir penilaian.
                        </small>
                    </div>

                    <!-- Form -->
                    <form id="attendanceForm">
                        <!-- Pilihan Nama Ujian -->
                        <div class="mb-3">
                            <label for="exam_id" class="form-label">
                                <i class="fas fa-clipboard-list text-primary"></i> Pilih Nama Ujian
                            </label>
                            <select id="exam_id" name="exam_id" class="form-select" required>
                                <option value="">-- Pilih Ujian --</option>
                                <?php foreach ($exams as $e): ?>
                                    <option value="<?= esc($e['id']) ?>"><?= esc($e['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Pilihan Ruang -->
                        <div class="mb-3">
                            <label for="room" class="form-label">
                                <i class="fas fa-door-closed text-success"></i> Pilih Ruang
                            </label>
                            <select id="room" name="room" class="form-select" required>
                                <option value="">-- Pilih Ruang --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= esc($r['room']) ?>"><?= esc($r['room']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="button" id="printPdfBtn" class="btn btn-modern btn-danger-gradient btn-lg">
                                <i class="fas fa-file-pdf"></i> Cetak PDF
                            </button>
                            <button type="button" id="printHalBtn" class="btn btn-modern btn-primary-gradient btn-lg">
                                <i class="fas fa-print"></i> Pratinjau Cetak
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
document.getElementById('printPdfBtn').addEventListener('click', function(e){
    e.preventDefault();
    const examId = document.getElementById('exam_id').value;
    const room = document.getElementById('room').value;

    if (!examId) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih nama ujian terlebih dahulu.'
        });
        return;
    }
    if (!room) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih ruang terlebih dahulu.'
        });
        return;
    }

    const url = "<?= site_url('admin/cbt/attendance/printPdf/') ?>" 
                + encodeURIComponent(examId) + '/' + encodeURIComponent(room);
    window.open(url, '_blank');
});

document.getElementById('printHalBtn').addEventListener('click', function(e){
    e.preventDefault();
    const examId = document.getElementById('exam_id').value;
    const room = document.getElementById('room').value;

    if (!examId) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih nama ujian terlebih dahulu.'
        });
        return;
    }
    if (!room) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih ruang terlebih dahulu.'
        });
        return;
    }

    window.location.href = "<?= site_url('admin/cbt/attendance/printByRoom/') ?>" 
                          + encodeURIComponent(examId) + '/' + encodeURIComponent(room);
});
</script>
<?= $this->endSection() ?>
