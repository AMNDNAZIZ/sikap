<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $title ?>
    </h1>

    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">
            <form
                action="<?= isset($activity) ? base_url('admin/cms/activities/update/' . $activity['id']) : base_url('admin/cms/activities/store') ?>"
                method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label">Judul Kegiatan</label>
                            <input type="text" name="title" class="form-control" value="<?= $activity['title'] ?? '' ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Singkat</label>
                            <textarea name="description" class="form-control" rows="3"
                                required><?= $activity['description'] ?? '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Kegiatan</label>
                            <input type="date" name="date" class="form-control"
                                value="<?= $activity['date'] ?? date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Foto Kegiatan</label>
                            <?php if (isset($activity['image']) && $activity['image']): ?>
                                <div class="mb-2">
                                    <img src="<?= base_url('uploads/activities/' . $activity['image']) ?>"
                                        class="img-thumbnail w-100">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" <?= isset($activity) ? '' : 'required' ?>>
                            <small class="text-muted">Rekomendasi: 800x600 px</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-success">💾 Simpan Dokumentasi</button>
                    <a href="<?= base_url('admin/cms/activities') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>