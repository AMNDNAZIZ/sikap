<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-broom"></i> Pembersihan Data
            </h1>
            <p class="text-muted mb-0">Reset database ke kondisi awal (kosong)</p>
        </div>
    </div>

    <!-- Warning Alert -->
    <div class="alert alert-danger border-left-danger" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
            <div>
                <h5 class="alert-heading mb-1">
                    <i class="fas fa-exclamation-circle"></i> PERINGATAN PENTING!
                </h5>
                <p class="mb-0">
                    Operasi pembersihan data bersifat <strong>PERMANEN</strong> dan <strong>TIDAK DAPAT DIBATALKAN</strong>. 
                    Pastikan Anda telah melakukan backup database sebelum melanjutkan.
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Data Siswa
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-students">
                                <?= number_format($stats['students']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tes Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-tests">
                                <?= number_format($stats['test_status']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Sesi Ujian
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-sessions">
                                <?= number_format($stats['sessions']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Jawaban Siswa
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-answers">
                                <?= number_format($stats['answers']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Cheat Logs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-cheat-logs">
                                <?= number_format($stats['cheat_logs'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-secret fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cleanup Options -->
    <div class="row">
        <!-- Clean Students -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-users"></i> Hapus Data Siswa
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Menghapus semua data siswa beserta:
                    </p>
                    <ul class="text-muted">
                        <li>Data siswa</li>
                        <li>Sesi ujian siswa</li>
                        <li>Jawaban siswa</li>
                        <li>Catatan kecurangan</li>
                    </ul>
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Data tes status (jadwal ujian) tidak akan dihapus
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger btn-block" onclick="confirmCleanStudents()">
                        <i class="fas fa-trash"></i> Hapus Data Siswa
                    </button>
                </div>
            </div>
        </div>

        <!-- Clean Test Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-clipboard-list"></i> Hapus Tes Status
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Menghapus semua tes status beserta:
                    </p>
                    <ul class="text-muted">
                        <li>Tes status (jadwal ujian)</li>
                        <li>Sesi ujian</li>
                        <li>Jawaban siswa</li>
                        <li>Catatan kecurangan</li>
                    </ul>
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Data siswa tidak akan dihapus
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger btn-block" onclick="confirmCleanTestStatus()">
                        <i class="fas fa-trash"></i> Hapus Tes Status
                    </button>
                </div>
            </div>
        </div>

        <!-- Clean All -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-broom"></i> Hapus Semua Data
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Menghapus SEMUA data:
                    </p>
                    <ul class="text-muted">
                        <li>Data siswa</li>
                        <li>Tes status (jadwal ujian)</li>
                        <li>Sesi ujian</li>
                        <li>Jawaban siswa</li>
                        <li>Catatan kecurangan</li>
                    </ul>
                    <div class="alert alert-danger">
                        <small>
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Database akan kembali kosong!</strong>
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger btn-block" onclick="confirmCleanAll()">
                        <i class="fas fa-exclamation-triangle"></i> Hapus Semua Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Reminder -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-database"></i> Backup Database
            </h6>
        </div>
        <div class="card-body">
            <p class="mb-2">
                <strong>Sangat disarankan untuk melakukan backup database sebelum melakukan pembersihan data.</strong>
            </p>
            <p class="text-muted mb-0">
                Anda dapat melakukan backup melalui phpMyAdmin atau menggunakan command:
            </p>
            <pre class="bg-light p-3 rounded mt-2"><code>mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql</code></pre>
        </div>
    </div>
</div>

<script>
// Refresh statistics
function refreshStats() {
    fetch('<?= site_url('admin/data-cleanup/get-stats') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stat-students').textContent = data.stats.students.toLocaleString();
                document.getElementById('stat-tests').textContent = data.stats.test_status.toLocaleString();
                document.getElementById('stat-sessions').textContent = data.stats.sessions.toLocaleString();
                document.getElementById('stat-answers').textContent = data.stats.answers.toLocaleString();
                if (document.getElementById('stat-cheat-logs')) {
                    document.getElementById('stat-cheat-logs').textContent = (data.stats.cheat_logs || 0).toLocaleString();
                }
            }
        });
}

// Confirm clean students
function confirmCleanStudents() {
    Swal.fire({
        title: 'Hapus Data Siswa?',
        html: `
            <div class="text-left">
                <p class="text-danger"><strong>PERINGATAN:</strong> Operasi ini akan menghapus:</p>
                <ul>
                    <li>Semua data siswa</li>
                    <li>Semua sesi ujian siswa</li>
                    <li>Semua jawaban siswa</li>
                </ul>
                <p class="text-danger"><strong>Operasi ini TIDAK DAPAT DIBATALKAN!</strong></p>
                <p>Ketik <strong>HAPUS SEMUA DATA SISWA</strong> untuk konfirmasi:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'HAPUS SEMUA DATA SISWA',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: (confirmation) => {
            const formData = new FormData();
            formData.append('confirmation', confirmation);
            
            return fetch('<?= site_url('admin/data-cleanup/clean-students') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: result.value.message,
                icon: 'success'
            });
            refreshStats();
        }
    });
}

// Confirm clean test status
function confirmCleanTestStatus() {
    Swal.fire({
        title: 'Hapus Tes Status?',
        html: `
            <div class="text-left">
                <p class="text-danger"><strong>PERINGATAN:</strong> Operasi ini akan menghapus:</p>
                <ul>
                    <li>Semua tes status (jadwal ujian)</li>
                    <li>Semua sesi ujian</li>
                    <li>Semua jawaban siswa</li>
                    <li>Semua catatan kecurangan</li>
                </ul>
                <p class="text-danger"><strong>Operasi ini TIDAK DAPAT DIBATALKAN!</strong></p>
                <p>Ketik <strong>HAPUS SEMUA TES STATUS</strong> untuk konfirmasi:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'HAPUS SEMUA TES STATUS',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: (confirmation) => {
            const formData = new FormData();
            formData.append('confirmation', confirmation);
            
            return fetch('<?= site_url('admin/data-cleanup/clean-test-status') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: result.value.message,
                icon: 'success'
            });
            refreshStats();
        }
    });
}

// Confirm clean all
function confirmCleanAll() {
    Swal.fire({
        title: 'Hapus SEMUA Data?',
        html: `
            <div class="text-left">
                <p class="text-danger"><strong>PERINGATAN KERAS:</strong> Operasi ini akan menghapus:</p>
                <ul>
                    <li>Semua data siswa</li>
                    <li>Semua tes status (jadwal ujian)</li>
                    <li>Semua sesi ujian</li>
                    <li>Semua jawaban siswa</li>
                    <li>Semua catatan kecurangan</li>
                </ul>
                <p class="text-danger"><strong>DATABASE AKAN KEMBALI KOSONG!</strong></p>
                <p class="text-danger"><strong>OPERASI INI TIDAK DAPAT DIBATALKAN!</strong></p>
                <p>Ketik <strong>HAPUS SEMUA DATA</strong> untuk konfirmasi:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'HAPUS SEMUA DATA',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: (confirmation) => {
            const formData = new FormData();
            formData.append('confirmation', confirmation);
            
            return fetch('<?= site_url('admin/data-cleanup/clean-all') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: result.value.message,
                icon: 'success'
            });
            refreshStats();
        }
    });
}
</script>

<?= $this->endSection() ?>
