<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between mb-3">
    <h4>🔌 Integrasi Dapodik</h4>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">⚙️ Pengaturan API Web Service</h5>
            </div>
            <div class="card-body">
                <form id="form-test-koneksi">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">URL Web Service</label>
                        <input type="text" name="api_url" id="api_url" class="form-control" value="<?= esc($api_url) ?>"
                            placeholder="http://localhost:5774/WebService/">
                        <small class="text-muted">Default: <code>http://localhost:5774/WebService/</code></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Web Service Key (Token)</label>
                        <input type="text" name="api_key" id="api_key" class="form-control" value="<?= esc($api_key) ?>"
                            placeholder="Masukkan Key dari Dapodik">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NPSN Sekolah</label>
                        <input type="text" name="npsn" id="npsn" class="form-control" value="<?= esc($npsn) ?>"
                            placeholder="Masukkan NPSN Sekolah">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" id="btn-test-koneksi" class="btn btn-outline-primary">🔍 Cek
                            Koneksi</button>
                    </div>
                </form>
                <div id="test-result" class="mt-3" style="display:none;">
                    <div class="alert mb-0" id="test-alert"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">🔄 Sinkronisasi Data</h5>
            </div>
            <div class="card-body">
                <p>Setelah koneksi berhasil, Anda dapat menarik data siswa dan guru dari Dapodik untuk dimasukkan ke
                    dalam sistem Sikap.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Catatan:</strong> 
                    <ul class="mb-0 mt-2">
                        <li>Proses pengambilan data siswa mungkin membutuhkan waktu 1-2 menit</li>
                        <li>Anda dapat memilih cara menangani data yang sudah ada (skip/update/merge)</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-3">
                    <button onclick="fetchData('students')" class="btn btn-success py-3" id="btn-fetch-students">
                        <i class="fas fa-user-graduate me-2"></i> 👨‍🎓 Tarik Data Siswa
                    </button>
                    <button onclick="fetchData('teachers')" class="btn btn-info py-3 text-white" id="btn-fetch-teachers">
                        <i class="fas fa-chalkboard-teacher me-2"></i> 👨‍🏫 Tarik Data Guru
                    </button>
                </div>
            </div>
        </div>

        <div class="alert alert-warning border-start border-4 border-warning">
            <h5>💡 Petunjuk Singkat:</h5>
            <ol class="mb-0">
                <li>Buka aplikasi <strong>Dapodik</strong> anda.</li>
                <li>Pilih menu <strong>Pengaturan</strong> > <strong>Web Service</strong>.</li>
                <li>Tambahkan nama aplikasi (misal: "Sikap") dan salin <strong>Key/Token</strong> yang muncul.</li>
                <li>Masukkan Key tersebut ke form pengaturan di samping.</li>
                <li>Buka port <strong>5774</strong> di firewall jika diperlukan.</li>
            </ol>
        </div>
    </div>
</div>

<script>
    document.getElementById('btn-test-koneksi').addEventListener('click', function () {
        const btn = this;
        const resultDiv = document.getElementById('test-result');
        const alertDiv = document.getElementById('test-alert');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghubungkan...';

        const formData = new FormData(document.getElementById('form-test-koneksi'));

        fetch('<?= base_url('admin/dapodik/testConnection') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server merespons dengan status ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                resultDiv.style.display = 'block';
                if (data.status === 'success') {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.innerHTML = '✅ ' + data.message;
                } else {
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = '❌ ' + data.message;
                }
            })
            .catch(error => {
                resultDiv.style.display = 'block';
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = '❌ Terjadi kesalahan sistem: ' + error.message;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '🔍 Cek Koneksi';
            });
    });

    function fetchData(type) {
        const btnId = type === 'students' ? 'btn-fetch-students' : 'btn-fetch-teachers';
        const btn = document.getElementById(btnId);
        const originalHtml = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        if (type === 'students') {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengambil data siswa... Mohon tunggu (1-2 menit)';
        } else {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengambil data guru... Mohon tunggu';
        }
        
        // Redirect to fetch page
        const url = type === 'students' 
            ? '<?= base_url('admin/dapodik/fetchStudents') ?>'
            : '<?= base_url('admin/dapodik/fetchTeachers') ?>';
        
        window.location.href = url;
    }
</script>

<?= $this->endSection() ?>