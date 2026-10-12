<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold"><?= $title ?></h1>
            <p class="text-muted small mb-0">Kelola album dokumentasi kegiatan yang tampil di halaman beranda website.</p>
        </div>
        <a href="<?= base_url('admin/cms/activities/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm rounded-pill px-3 py-2">
            <i class="bi bi-plus-circle"></i>
            <span>Tambah Dokumentasi</span>
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4" width="60">No</th>
                            <th width="140">Cover Album</th>
                            <th>Judul Kegiatan & Deskripsi</th>
                            <th width="120" class="text-center">Jumlah Foto</th>
                            <th width="140">Pengunggah</th>
                            <th width="120">Tanggal</th>
                            <th width="130" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-images display-6 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada dokumentasi kegiatan yang diunggah.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($activities as $a): 
                                $pCount = max(1, (int) ($a['photo_count'] ?? 1));
                            ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-muted"><?= $no++ ?></td>
                                    <td>
                                        <div class="position-relative d-inline-block rounded-3 overflow-hidden shadow-sm" style="width: 100px; height: 68px;">
                                            <img src="<?= base_url('uploads/activities/' . $a['image']) ?>" 
                                                 class="w-100 h-100 object-fit-cover"
                                                 alt="<?= esc($a['title']) ?>"
                                                 onerror="this.src='https://placehold.co/100x68?text=No+Photo'">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?= esc($a['title']) ?></div>
                                        <p class="text-muted small mb-0 text-truncate" style="max-width: 380px;">
                                            <?= esc($a['description']) ?>
                                        </p>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1.5 fw-semibold">
                                            <i class="bi bi-camera me-1"></i> <?= $pCount ?> Foto
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1">
                                            <i class="bi bi-person me-1"></i> <?= esc($a['uploader_name'] ?? 'System') ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y', strtotime($a['date'])) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/cms/activities/edit/' . $a['id']) ?>"
                                               class="btn btn-outline-warning" title="Edit Album">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= base_url('admin/cms/activities/delete/' . $a['id']) ?>"
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus album kegiatan ini beserta seluruh fotonya?')"
                                               title="Hapus Album">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>