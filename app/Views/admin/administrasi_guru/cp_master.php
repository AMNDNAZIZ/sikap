<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📜 Master Capaian Pembelajaran (CP)</h1>
        <small class="text-muted">Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span></small>
    </div>
    <?php if (!$isReadOnly): ?>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cpModal">
        <i class="bi bi-plus-lg"></i> Tambah Master CP
    </button>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="card shadow mb-3">
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/administrasi-guru/cp-master') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="bi bi-funnel me-1"></i> Filter Mata Pelajaran:
                </label>
                <select name="mapel_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Mata Pelajaran --</option>
                    <?php foreach ($mapel_master as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($filter_mapel == $m['id']) ? 'selected' : '' ?>>
                            <?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-funnel me-1"></i> Filter Fase:
                </label>
                <select name="fase" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Fase --</option>
                    <?php if ($school_level == 1): // SD ?>
                        <option value="A" <?= ($filter_fase == 'A') ? 'selected' : '' ?>>Fase A (Kls 1-2)</option>
                        <option value="B" <?= ($filter_fase == 'B') ? 'selected' : '' ?>>Fase B (Kls 3-4)</option>
                        <option value="C" <?= ($filter_fase == 'C') ? 'selected' : '' ?>>Fase C (Kls 5-6)</option>
                    <?php elseif ($school_level == 2): // SMP ?>
                        <option value="D" <?= ($filter_fase == 'D') ? 'selected' : '' ?>>Fase D (Kls 7-9)</option>
                    <?php elseif ($school_level == 3): // SMA ?>
                        <option value="E" <?= ($filter_fase == 'E') ? 'selected' : '' ?>>Fase E (Kls 10)</option>
                        <option value="F" <?= ($filter_fase == 'F') ? 'selected' : '' ?>>Fase F (Kls 11-12)</option>
                    <?php else: ?>
                        <option value="A" <?= ($filter_fase == 'A') ? 'selected' : '' ?>>Fase A</option>
                        <option value="B" <?= ($filter_fase == 'B') ? 'selected' : '' ?>>Fase B</option>
                        <option value="C" <?= ($filter_fase == 'C') ? 'selected' : '' ?>>Fase C</option>
                        <option value="D" <?= ($filter_fase == 'D') ? 'selected' : '' ?>>Fase D</option>
                        <option value="E" <?= ($filter_fase == 'E') ? 'selected' : '' ?>>Fase E</option>
                        <option value="F" <?= ($filter_fase == 'F') ? 'selected' : '' ?>>Fase F</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <?php if ($filter_mapel || $filter_fase): ?>
                    <a href="<?= base_url('admin/administrasi-guru/cp-master') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-2 text-end">
                <?php 
                $totalRecords = count($cp_master);
                $currentPage = $pager->getCurrentPage();
                $perPage = $pager->getPerPage();
                $totalPages = $pager->getPageCount();
                ?>
                <small class="text-muted d-block">
                    Halaman <?= $currentPage ?> dari <?= $totalPages ?>
                </small>
                <small class="text-muted">
                    Total: <strong><?= $pager->getTotal() ?></strong> data
                </small>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="cpTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>
                            Jenjang, Mapel & Elemen
                            <?php 
                            $sortUrl = 'admin/administrasi-guru/cp-master?sort=mapel_nama&order=' . ($current_sort == 'mapel_nama' && $current_order == 'asc' ? 'desc' : 'asc');
                            if ($filter_mapel) $sortUrl .= '&mapel_id=' . $filter_mapel;
                            if ($filter_fase) $sortUrl .= '&fase=' . $filter_fase;
                            ?>
                            <a href="<?= base_url($sortUrl) ?>" 
                               class="text-decoration-none ms-1" 
                               title="Urutkan berdasarkan Mata Pelajaran">
                                <?php if ($current_sort == 'mapel_nama'): ?>
                                    <i class="bi bi-sort-<?= $current_order == 'asc' ? 'alpha-down' : 'alpha-up' ?>"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th width="80">
                            Fase
                            <?php 
                            $sortUrl = 'admin/administrasi-guru/cp-master?sort=fase&order=' . ($current_sort == 'fase' && $current_order == 'asc' ? 'desc' : 'asc');
                            if ($filter_mapel) $sortUrl .= '&mapel_id=' . $filter_mapel;
                            if ($filter_fase) $sortUrl .= '&fase=' . $filter_fase;
                            ?>
                            <a href="<?= base_url($sortUrl) ?>" 
                               class="text-decoration-none ms-1" 
                               title="Urutkan berdasarkan Fase">
                                <?php if ($current_sort == 'fase'): ?>
                                    <i class="bi bi-sort-<?= $current_order == 'asc' ? 'alpha-down' : 'alpha-up' ?>"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Deskripsi CP</th>
                        <th width="150">SK/Tahun</th>
                        <?php if (!$isReadOnly): ?>
                        <th width="100">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1 + (20 * ($pager->getCurrentPage() - 1)); foreach ($cp_master as $cp): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="mb-1">
                                    <small class="badge bg-light text-muted border"><?= esc($cp['jenjang_nama']) ?></small>
                                </div>
                                <strong><?= esc($cp['mapel_nama']) ?></strong><br>
                                <span class="text-info small fw-bold"><i class="bi bi-tag-fill me-1"></i><?= esc($cp['elemen'] ?: '-') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-primary"><?= esc($cp['fase']) ?></span>
                            </td>
                            <td>
                                <div class="text-wrap small" style="max-height: 100px; overflow-y: auto;">
                                    <?= nl2br(esc(substr($cp['deskripsi'], 0, 250))) ?><?= strlen($cp['deskripsi']) > 250 ? '...' : '' ?>
                                </div>
                            </td>
                            <td>
                                <small class="d-block text-truncate" title="<?= esc($cp['nomor_sk']) ?>"><?= esc($cp['nomor_sk']) ?></small>
                                <span class="badge bg-light text-dark border mt-1">TA <?= esc($cp['tahun']) ?></span>
                            </td>
                            <?php if (!$isReadOnly): ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white btn-edit" 
                                    data-id="<?= $cp['id'] ?>" 
                                    data-mapel="<?= $cp['mapel_master_id'] ?>" 
                                    data-elemen="<?= esc($cp['elemen'], 'attr') ?>"
                                    data-fase="<?= $cp['fase'] ?>" 
                                    data-deskripsi="<?= esc($cp['deskripsi'], 'attr') ?>"
                                    data-sk="<?= $cp['nomor_sk'] ?>"
                                    data-tahun="<?= $cp['tahun'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="<?= base_url('admin/administrasi-guru/cp-master/delete/'.$cp['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus CP master ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 d-flex justify-content-center">
            <?= $pager->links('default', 'bootstrap') ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="cpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('admin/administrasi-guru/cp-master/store') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="cp_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Master CP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Hanya menampilkan mata pelajaran untuk level sekolah: <strong><?= esc($school_level_name) ?></strong>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Mata Pelajaran Master:</label>
                            <select name="mapel_master_id" id="mapel_master_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php foreach ($mapel_master as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Mapel sesuai jenjang <?= esc($school_level_name) ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Elemen:</label>
                            <input type="text" name="elemen" id="elemen" class="form-control" placeholder="MISAL: Bilangan" required>
                            <small class="text-muted">Masukkan 1 nama elemen saja (misal: Bilangan).</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fase:</label>
                            <select name="fase" id="fase" class="form-select" required>
                                <?php if ($school_level == 1): // SD ?>
                                    <option value="A">Fase A (Kls 1-2)</option>
                                    <option value="B">Fase B (Kls 3-4)</option>
                                    <option value="C">Fase C (Kls 5-6)</option>
                                <?php elseif ($school_level == 2): // SMP ?>
                                    <option value="D">Fase D (Kls 7-9)</option>
                                <?php elseif ($school_level == 3): // SMA ?>
                                    <option value="E">Fase E (Kls 10)</option>
                                    <option value="F">Fase F (Kls 11-12)</option>
                                <?php else: // Fallback: show all ?>
                                    <option value="A">Fase A (Kls 1-2)</option>
                                    <option value="B">Fase B (Kls 3-4)</option>
                                    <option value="C">Fase C (Kls 5-6)</option>
                                    <option value="D">Fase D (Kls 7-9)</option>
                                    <option value="E">Fase E (Kls 10)</option>
                                    <option value="F">Fase F (Kls 11-12)</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Fase sesuai jenjang</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nomor SK:</label>
                            <input type="text" name="nomor_sk" id="nomor_sk" class="form-control" placeholder="MISAL: 033/H/KR/2022">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tahun:</label>
                            <input type="number" name="tahun" id="tahun" class="form-control" value="<?= date('Y') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi Capaian Pembelajaran:</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="8" required placeholder="Paste konten CP di sini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan CP</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('.btn-edit').click(function() {
        $('#cp_id').val($(this).attr('data-id'));
        $('#mapel_master_id').val($(this).attr('data-mapel'));
        $('#elemen').val($(this).attr('data-elemen'));
        $('#fase').val($(this).attr('data-fase'));
        $('#deskripsi').val($(this).attr('data-deskripsi'));
        $('#nomor_sk').val($(this).attr('data-sk'));
        $('#tahun').val($(this).attr('data-tahun'));
        $('#modalTitle').text('Edit Master CP');
        $('#cpModal').modal('show');
    });

    $('#cpModal').on('hidden.bs.modal', function () {
        $('#cp_id').val('');
        $('#elemen').val('');
        $('#deskripsi').val('');
        $('#modalTitle').text('Tambah Master CP');
    });
});
</script>
<?= $this->endSection() ?>
