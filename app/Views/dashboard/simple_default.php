<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow text-center">
                <div class="card-body py-5">
                    <div style="font-size:3rem;">👤</div>
                    <h4 class="mt-3">Selamat Datang, <?= esc($user['username']) ?></h4>
                    <p class="text-muted">Anda berhasil login. Silakan gunakan menu navigasi untuk mengakses fitur yang tersedia.</p>
                    <a href="<?= base_url('admin/ekskul-pembina') ?>" class="btn btn-primary mt-2">
                        <i class="fas fa-users"></i> Ekskul Saya
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
