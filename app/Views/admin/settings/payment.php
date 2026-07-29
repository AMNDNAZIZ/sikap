<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mt-4 fw-bold">Integrasi Pembayaran</h1>
            <p class="text-muted">Hubungkan SIAKAD dengan aplikasi web pembayaran sekolah untuk menampilkan info tagihan di portal siswa.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/settings/payment/save') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold">URL Aplikasi Pembayaran</label>
                            <input type="url" name="payment_api_url" class="form-control"
                                value="<?= esc($payment_api_url) ?>"
                                placeholder="https://pembayaran.sekolah.sch.id">
                            <small class="text-muted">
                                URL dasar dari aplikasi web pembayaran. Endpoint yang dipanggil: <code>{URL}/api/payment-info?nis=xxx</code>
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">API Key (opsional)</label>
                            <input type="text" name="payment_api_key" class="form-control"
                                value="<?= esc($payment_api_key) ?>"
                                placeholder="Masukkan API key jika diperlukan">
                            <small class="text-muted">
                                Dikirim sebagai header <code>X-API-Key</code>. Kosongkan jika tidak diperlukan.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-save me-2"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Panduan Integrasi</h6>
                    <hr>
                    <p class="small text-muted">Aplikasi pembayaran Anda perlu menyediakan endpoint API berikut:</p>

                    <div class="bg-light rounded p-3 mb-3">
                        <code>GET /api/payment-info?nis={nis_siswa}</code>
                    </div>

                    <p class="small fw-bold">Format Response JSON yang diharapkan:</p>
                    <pre class="bg-light rounded p-3 small mb-3">{
  "success": true,
  "data": {
    "summary": {
      "total_tagihan": "Rp 5.000.000",
      "total_dibayar": "Rp 3.000.000",
      "sisa_tunggakan": "Rp 2.000.000",
      "status": "sebagian"
    },
    "payments": [
      {
        "tanggal": "2026-07-15",
        "keterangan": "SPP Juli 2026",
        "jumlah": "Rp 500.000",
        "status": "lunas"
      }
    ]
  }
}</pre>
                    <p class="small text-muted mb-0">
                        <strong>Status:</strong> <code>lunas</code>, <code>sebagian</code>, atau <code>belum</code><br>
                        <strong>Identifikasi:</strong> Menggunakan NIS siswa yang sama di kedua sistem.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
