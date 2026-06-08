<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Edit Exam Schedule page */
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
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
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

.form-section {
    margin-bottom: 2rem !important;
    padding-bottom: 2rem !important;
    border-bottom: 1px solid #e9ecef !important;
}

.form-section:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.form-section-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 1.25rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.form-label {
    font-weight: 500 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.form-label .text-danger {
    margin-left: 0.25rem !important;
}

.form-control, .form-select {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
    transition: all 0.2s ease !important;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.info-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
    border-left: 4px solid #667eea !important;
    padding: 1rem !important;
    border-radius: 8px !important;
    margin-bottom: 1.5rem !important;
}

.info-box i {
    color: #667eea !important;
    margin-right: 0.5rem !important;
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-secondary-gradient {
    background: linear-gradient(135deg, #868e96 0%, #6c757d 100%) !important;
    color: white !important;
}

.action-buttons {
    display: flex !important;
    gap: 0.75rem !important;
    justify-content: flex-start !important;
    padding-top: 1.5rem !important;
    border-top: 1px solid #e9ecef !important;
    margin-top: 2rem !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-edit"></i> Edit Jadwal Ujian</h1>
    </div>

    <!-- Main Card -->
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Informasi:</strong> Perbarui informasi jadwal ujian. Field yang ditandai dengan <span class="text-danger">*</span> wajib diisi.
            </div>

            <form action="<?= site_url('admin/exam-schedule/update/' . $schedule['id']) ?>" method="post">
                <?= csrf_field() ?>

                <!-- Section: Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-book"></i> Informasi Dasar
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">
                                Mata Pelajaran <span class="text-danger">*</span>
                            </label>
                            <select name="subject_id" id="subject_id" class="form-select" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $schedule['subject_id'] ? 'selected' : '' ?>>
                                        <?= esc($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label">
                                Kelas
                            </label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?= $cls['id'] ?>" <?= $cls['id'] == $schedule['class_id'] ? 'selected' : '' ?>>
                                        <?= esc($cls['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Kosongkan jika ujian untuk semua kelas</small>
                        </div>
                    </div>
                </div>

                <!-- Section: Waktu Ujian -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-clock"></i> Waktu Ujian
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="exam_date" class="form-label">
                                Tanggal Ujian <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="exam_date" id="exam_date" class="form-control" 
                                   value="<?= esc($schedule['exam_date']) ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">
                                Jam Mulai <span class="text-danger">*</span>
                            </label>
                            <input type="time" name="start_time" id="start_time" class="form-control" 
                                   value="<?= esc(substr($schedule['start_time'],0,5)) ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">
                                Jam Selesai <span class="text-danger">*</span>
                            </label>
                            <input type="time" name="end_time" id="end_time" class="form-control" 
                                   value="<?= esc(substr($schedule['end_time'],0,5)) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Section: Keterangan -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-comment-alt"></i> Keterangan Tambahan
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Keterangan</label>
                        <textarea name="description" id="description" rows="3" class="form-control" 
                                  placeholder="Tambahkan keterangan atau catatan khusus untuk ujian ini..."><?= esc($schedule['description']) ?></textarea>
                        <small class="text-muted">Opsional - Informasi tambahan tentang ujian</small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="<?= site_url('admin/exam-schedule') ?>" class="btn btn-modern btn-secondary-gradient">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-modern btn-primary-gradient">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
