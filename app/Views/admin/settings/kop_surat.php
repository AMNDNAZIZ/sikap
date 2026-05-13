<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header border-bottom">
        <h5 class="mb-0">Pengaturan Kop Surat Dinamis</h5>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Sukses - </strong> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Error - </strong> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <form action="<?= base_url('admin/settings/kop-surat/upload') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="kop_file" class="form-label">Upload Kop Surat (PNG/JPG, Maks 5MB)</label>
                        <input type="file" class="form-control" id="kop_file" name="kop_file" accept=".png, .jpg, .jpeg" required>
                        <small class="text-muted d-block mt-1">Rekomendasi: Lebar 1500px, transparan, format konten memanjang.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Kop Surat</button>
                </form>
            </div>
            <div class="col-md-6">
                <h6>Preview Kop Surat Aktif:</h6>
                <div class="border p-2 bg-light text-center">
                    <?php if (!empty($kop_surat)) : ?>
                        <img src="<?= base_url($kop_surat) ?>?t=<?= time() ?>" alt="Kop Surat Aktif" class="img-fluid" style="max-height: 200px;">
                    <?php else : ?>
                        <p class="text-muted mb-0">Belum ada kop surat yang diunggah.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
