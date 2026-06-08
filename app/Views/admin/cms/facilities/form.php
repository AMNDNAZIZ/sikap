<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $title ?>
    </h1>

    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">
            <form
                action="<?= isset($facility) ? base_url('admin/cms/facilities/update/' . $facility['id']) : base_url('admin/cms/facilities/store') ?>"
                method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label">Nama Fasilitas</label>
                            <input type="text" name="name" class="form-control" value="<?= $facility['name'] ?? '' ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="5"
                                required><?= $facility['description'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Gambar Fasilitas</label>
                            <?php if (isset($facility['image']) && $facility['image']): ?>
                                <div class="mb-2">
                                    <img src="<?= base_url('uploads/facilities/' . $facility['image']) ?>"
                                        class="img-thumbnail w-100">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control">
                            <small class="text-muted">Rekomendasi: 800x600 px</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-success">💾 Simpan Fasilitas</button>
                    <a href="<?= base_url('admin/cms/facilities') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>