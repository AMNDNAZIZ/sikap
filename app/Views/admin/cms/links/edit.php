<?= $this->extend('layouts/app'); ?>

<?= $this->section('content'); ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?= $title; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('admin/cms/links/update/' . $link['id']); ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="POST">
                        <!-- Method Spoofing if needed, but we use POST for update -->

                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Tautan</label>
                            <input type="text"
                                class="form-control <?= (isset(session('errors')['title'])) ? 'is-invalid' : ''; ?>"
                                id="title" name="title" value="<?= old('title', $link['title']); ?>" required>
                            <div class="invalid-feedback">
                                <?= (isset(session('errors')['title'])) ? session('errors')['title'] : ''; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="url" class="form-label">URL (Tautan Eksternal)</label>
                            <input type="url"
                                class="form-control <?= (isset(session('errors')['url'])) ? 'is-invalid' : ''; ?>"
                                id="url" name="url" value="<?= old('url', $link['url']); ?>" required>
                            <div class="invalid-feedback">
                                <?= (isset(session('errors')['url'])) ? session('errors')['url'] : ''; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="icon" class="form-label">Ikon (Bootstrap Icon Class)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="icon-preview"
                                        class="bi <?= $link['icon']; ?>"></i></span>
                                <input type="text"
                                    class="form-control <?= (isset(session('errors')['icon'])) ? 'is-invalid' : ''; ?>"
                                    id="icon" name="icon" value="<?= old('icon', $link['icon']); ?>" required>
                            </div>
                            <small class="text-muted">Cari ikon di <a href="https://icons.getbootstrap.com/"
                                    target="_blank">Bootstrap Icons</a></small>
                            <div class="invalid-feedback">
                                <?= (isset(session('errors')['icon'])) ? session('errors')['icon'] : ''; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Keterangan Singkat</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="2"><?= old('description', $link['description']); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="order_no" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="order_no" name="order_no"
                                    value="<?= old('order_no', $link['order_no']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" <?= $link['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= base_url('admin/cms/links'); ?>" class="btn btn-secondary px-4">Kembali</a>
                            <button type="submit" class="btn btn-primary px-4">Update Tautan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('icon').addEventListener('input', function () {
        const preview = document.getElementById('icon-preview');
        preview.className = 'bi ' + (this.value || 'bi-link-45deg');
    });
</script>
<?= $this->endSection(); ?>