<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mt-4 fw-bold">Integrasi Pembayaran</h1>
            <p class="text-muted">Hubungkan ESPATA dengan aplikasi web pembayaran sekolah untuk menampilkan info tagihan dan riwayat pembayaran di portal siswa.<br>Hanya berlaku untuk aplikasi pembayaran yang dikeluarkan SakaSalika</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('warning') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Alert hasil uji koneksi (AJAX) -->
    <div id="testConnAlert" class="d-none alert border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i id="testConnIcon" class="bi me-2 fs-5"></i>
            <span id="testConnMessage"></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/settings/payment/save') ?>" method="post" id="paymentSettingsForm">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold">URL Aplikasi Pembayaran</label>
                            <input type="url" name="payment_api_url" id="payment_api_url" class="form-control"
                                value="<?= esc($payment_api_url) ?>"
                                placeholder="http://localhost:8080/bayaran atau https://bayaran.sekolah.sch.id" required>
                            <small class="text-muted">
                                URL dasar aplikasi web pembayaran. Endpoint yang dipanggil: <code>{URL}/api/payment-info?nis=xxx</code>
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold d-flex align-items-center justify-content-between">
                                <span>Secret API Key <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Wajib</span></span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key-fill text-primary"></i></span>
                                <input type="text" name="payment_api_key" id="payment_api_key" class="form-control font-monospace border-start-0"
                                    value="<?= esc($payment_api_key) ?>"
                                    placeholder="Masukkan Secret API Key dari aplikasi pembayaran" required>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Dikirim sebagai header HTTP <code>X-API-Key</code>. Harus sama persis dengan yang ada di menu <strong>Pengaturan Sekolah &gt; Integrasi API (ESPATA)</strong> aplikasi pembayaran.
                            </small>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill">
                                <i class="bi bi-save me-2"></i>Simpan Pengaturan
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" id="btnTestConn" onclick="runTestConnection()">
                                <span id="testSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                                <i id="testIcon" class="bi bi-lightning-charge me-1"></i>
                                <span>Uji Koneksi API</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-2 text-primary"></i>Panduan &amp; Spesifikasi Integrasi</h6>
                    <hr class="mt-0">
                    <p class="small text-muted mb-2">Endpoint API pada aplikasi pembayaran:</p>

                    <div class="bg-light rounded p-3 mb-3 font-monospace small text-break">
                        <span class="badge bg-primary me-1">GET</span> <code>/api/payment-info?nis={nis_siswa}</code>
                    </div>

                    <p class="small text-muted mb-1"><strong>Header HTTP Wajib:</strong></p>
                    <div class="bg-light rounded p-2 mb-3 font-monospace small text-break">
                        <code>X-API-Key: {secret_api_key}</code>
                    </div>

                    <p class="small fw-bold mb-1">Format Response JSON dari Aplikasi Pembayaran:</p>
                    <pre class="bg-light rounded p-3 small mb-3" style="max-height: 220px; overflow-y: auto;">{
  "success": true,
  "data": {
    "siswa": {
      "nis": "12345",
      "nama": "Nama Siswa",
      "kelas": "VII-A"
    },
    "summary": {
      "total": 3500000,
      "dibayar": 2000000,
      "sisa": 1500000
    },
    "bulanan": [ ... ],
    "bebas": [ ... ]
  }
}</pre>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        Aplikasi pembayaran memvalidasi <code>X-API-Key</code> menggunakan pencocokan aman (<code>hash_equals</code>). Jika API Key tidak cocok atau kosong, server pembayaran akan menolak akses dengan HTTP 401 Unauthorized.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runTestConnection() {
    const urlInput = document.getElementById('payment_api_url');
    const keyInput = document.getElementById('payment_api_key');
    const alertBox = document.getElementById('testConnAlert');
    const alertMsg = document.getElementById('testConnMessage');
    const alertIco = document.getElementById('testConnIcon');
    const btn      = document.getElementById('btnTestConn');
    const spinner  = document.getElementById('testSpinner');
    const icon     = document.getElementById('testIcon');

    const url = (urlInput.value || '').trim();
    const key = (keyInput.value || '').trim();

    if (!url) {
        showTestAlert(false, 'Harap isi URL Aplikasi Pembayaran terlebih dahulu.');
        urlInput.focus();
        return;
    }

    if (!key) {
        showTestAlert(false, 'Harap isi Secret API Key terlebih dahulu.');
        keyInput.focus();
        return;
    }

    // Set UI loading
    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    alertBox.classList.add('d-none');

    const formData = new FormData();
    formData.append('payment_api_url', url);
    formData.append('payment_api_key', key);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url('admin/settings/payment/test') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showTestAlert(data.success, data.message);
    })
    .catch(err => {
        showTestAlert(false, 'Gagal melakukan pengujian koneksi: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
    });
}

function showTestAlert(isSuccess, message) {
    const alertBox = document.getElementById('testConnAlert');
    const alertMsg = document.getElementById('testConnMessage');
    const alertIco = document.getElementById('testConnIcon');

    alertBox.className = 'alert border-0 shadow-sm mb-4 ' + (isSuccess ? 'alert-success' : 'alert-danger');
    alertIco.className = 'bi me-2 fs-5 ' + (isSuccess ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger');
    alertMsg.textContent = message;
    alertBox.classList.remove('d-none');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?= $this->endSection() ?>
