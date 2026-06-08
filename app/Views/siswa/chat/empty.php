<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card shadow-sm border-0 py-5">
                <div class="card-body">
                    <div class="mb-4">
                        <i class="fas fa-comments text-secondary opacity-25" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Obrolan Kelas Belum Tersedia</h4>
                    <p class="text-muted">
                        Maaf, sepertinya Anda belum terdaftar di kelas aktif mana pun untuk tahun ajaran ini.
                        Silakan hubungi bagian Akademik atau Wali Kelas Anda untuk informasi lebih lanjut.
                    </p>
                    <div class="mt-4">
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary px-4 rounded-pill">
                            <i class="fas fa-home me-2"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>