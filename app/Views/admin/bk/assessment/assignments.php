<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="h3 mb-1 text-gray-800">📋 Penugasan Asesmen BK</h1>
            <p class="text-muted mb-0">Distribusikan instrumen asesmen ke kelas atau siswa tertentu</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPenugasan">
                <i class="fas fa-plus"></i> Buat Penugasan Baru
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Instrumen</th>
                            <th>Target Sasaran</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($assignments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada penugasan asesmen.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($assignments as $assign): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= esc($assign['title']) ?></div>
                                    <small class="text-muted"><?= esc($assign['instrument_title']) ?></small>
                                </td>
                                <td>
                                    <?php if($assign['target_type'] == 'class'): ?>
                                        <span class="badge bg-primary">Kelas Tertentu</span>
                                    <?php elseif($assign['target_type'] == 'level'): ?>
                                        <span class="badge bg-info">Satu Jenjang</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Semua Siswa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($assign['start_date'])) ?> 
                                    - 
                                    <?= date('d M Y', strtotime($assign['end_date'])) ?>
                                </td>
                                <td>
                                    <?php if(strtotime($assign['end_date']) < time()): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php elseif($assign['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(ucfirst($assign['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/bk/pemetaan/penugasan/progress/' . $assign['id']) ?>" class="btn btn-sm btn-info rounded-pill">Lihat Progress</a>
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

<!-- Modal Buat Penugasan -->
<div class="modal fade" id="modalPenugasan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('admin/bk/pemetaan/penugasan/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-primary">Buat Penugasan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Instrumen Asesmen <span class="text-danger">*</span></label>
                        <select name="instrument_id" class="form-select" required>
                            <option value="">-- Pilih Instrumen (Hanya yang berstatus Published) --</option>
                            <?php foreach($instruments as $ins): ?>
                                <option value="<?= $ins['id'] ?>"><?= esc($ins['title']) ?> (<?= esc($ins['target_level']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Judul Penugasan (Opsional)</label>
                        <input type="text" name="title" class="form-control" placeholder="Biarkan kosong untuk menggunakan judul instrumen">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Batas Akhir (End Date) <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe Sasaran <span class="text-danger">*</span></label>
                        <select name="target_type" id="target_type" class="form-select" onchange="toggleTargetId()" required>
                            <option value="all">Semua Siswa di Tahun Ajaran Aktif</option>
                            <option value="class">Kelas Tertentu</option>
                        </select>
                    </div>

                    <div class="mb-3" id="class_selector" style="display: none;">
                        <label class="form-label">Pilih Kelas</label>
                        <select name="target_id" class="form-select">
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Distribusikan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function toggleTargetId() {
        const type = document.getElementById('target_type').value;
        const classSelector = document.getElementById('class_selector');
        
        if (type === 'class') {
            classSelector.style.display = 'block';
        } else {
            classSelector.style.display = 'none';
        }
    }
</script>
<?= $this->endSection() ?>
