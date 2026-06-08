<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $title ?>
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= base_url('admin/changelogs') ?>">Manajemen Changelog</a></li>
        <li class="breadcrumb-item active">
            <?= $title ?>
        </li>
    </ol>

    <div class="card shadow-sm border-0 mb-4" style="max-width: 800px;">
        <div class="card-body p-4">
            <form action="<?= base_url('admin/changelogs/save') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $changelog['id'] ?? '' ?>">

                <div class="row g-3">
                    <div class="col-md-6 text-start">
                        <label class="form-label fw-bold">Versi</label>
                        <input type="text" name="version" class="form-control" placeholder="Contoh: 1.1.2"
                            value="<?= esc($changelog['version'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 text-start">
                        <label class="form-label fw-bold">Tanggal Rilis</label>
                        <input type="date" name="release_date" class="form-control"
                            value="<?= esc($changelog['release_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-12 text-start">
                        <label class="form-label fw-bold">Diskripsi Update</label>
                        <textarea name="description" class="form-control" rows="8"
                            placeholder="Tulis rincian perubahan di sini..."
                            required><?= esc($changelog['description'] ?? '') ?></textarea>
                        <div class="form-text mt-1">Gunakan baris baru untuk memisahkan setiap item perubahan.</div>
                    </div>
                    <div class="col-12 text-start">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_stable" id="isStable" value="1"
                                <?= (isset($changelog['is_stable']) && $changelog['is_stable'] == 0) ? '' : 'checked' ?>>
                            <label class="form-check-label" for="isStable">Status Stable (Siap Produksi)</label>
                        </div>
                    </div>
                    <div class="col-12 pt-3 mt-4 border-top d-flex justify-content-end gap-2 text-start">
                        <a href="<?= base_url('admin/changelogs') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Changelog</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>