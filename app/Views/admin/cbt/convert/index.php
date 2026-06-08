<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex align-items-center">
            <div class="me-3">
                <div class="icon bg-light rounded-circle p-3">
                    <i class="bi bi-calculator fs-3 text-primary"></i>
                </div>
            </div>
            <div>
                <h5 class="mb-1">Konversi Nilai CBT</h5>
                <p class="mb-0 text-muted">Konversi nilai hasil ujian CBT ke skala nilai raport (Formatif, Sumatif,
                    Final).</p>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">1. Tentukan Nilai yang akan dikonversi</h6>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('admin/cbt/convertnilai/preview') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Pilih Bank Soal</label>
                            <select name="bank_id" class="form-select select2" required>
                                <option value="">-- Pilih Bank Soal --</option>
                                <?php foreach ($banks as $b): ?>
                                    <option value="<?= $b['id'] ?>">
                                        <?= esc($b['code']) ?> -
                                        <?= esc($b['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Kelas</label>
                            <select name="class_id" class="form-select select2" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= esc($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3">Target Konversi</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai Terbesar (YA)</label>
                                <input type="number" name="ya" class="form-control" value="100" min="0" max="100"
                                    required>
                                <div class="form-text">Nilai maksimal yang diinginkan</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai Terkecil (YB)</label>
                                <input type="number" name="yb" class="form-control" value="75" min="0" max="100"
                                    required>
                                <div class="form-text">Nilai minimal yang diinginkan</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Generate Daftar Nilai
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 bg-light border-0">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Cara Kerja Konversi</h6>
                    <p class="small text-muted">
                        Sistem akan mengambil nilai asli (CBT) dari semua siswa dalam kelas yang dipilih, kemudian
                        melakukan penskalaan linear menggunakan rumus:
                    </p>
                    <div class="bg-white p-3 rounded text-center font-monospace small border mb-3">
                        ((YA - YB) / (XA - XB)) x (NX - XB) + YB
                    </div>
                    <ul class="small text-muted ps-3">
                        <li><strong>YA</strong>: Nilai Tertinggi yang Anda targetkan.</li>
                        <li><strong>YB</strong>: Nilai Terendah yang Anda targetkan.</li>
                        <li><strong>XA</strong>: Nilai Asli Tertinggi di kelas tersebut.</li>
                        <li><strong>XB</strong>: Nilai Asli Terendah di kelas tersebut.</li>
                        <li><strong>NX</strong>: Nilai Asli Siswa saat ini.</li>
                    </ul>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Pastikan ujian sudah selesai dilakukan agar data
                        nilai yang ditarik adalah data final.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>