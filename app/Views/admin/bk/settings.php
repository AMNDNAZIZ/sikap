<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-gear-fill me-2 text-secondary"></i> Pengaturan Guru BK</h3>
            <p class="text-muted mb-0">Ploting Guru BK per Kelas untuk SMP/SMA — tentukan siapa menangani kelas mana.</p>
        </div>
        <a href="<?= base_url('admin/bk/dashboard') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard BK
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
        <div class="small">
            <strong>Cara kerja:</strong> Halaman ini hanya relevan untuk <strong>SMP/SMA</strong> di mana guru BK
            adalah guru tersendiri. Daftar di bawah menampilkan guru yang sudah diberi permission
            <code>bk.manage</code> melalui <a href="<?= base_url('admin/role-permission') ?>">Manajemen Role &amp; Permission</a>.
            Centang kelas-kelas yang ditangani tiap guru BK untuk tahun ajaran aktif.
            <br>
            <span class="text-muted">Untuk <strong>SD</strong>, guru kelas otomatis menjadi pengampu BK kelasnya sendiri — tidak perlu ploting di sini.</span>
        </div>
    </div>

    <!-- Filter Tahun Ajaran -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-semibold">Tahun Ajaran:</label>
                </div>
                <div class="col-auto">
                    <select name="year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= $ay['id'] ?>"
                                <?= ($ay['id'] == ($activeYear['id'] ?? 0)) ? 'selected' : '' ?>>
                                <?= esc($ay['year']) ?> <?= $ay['is_active'] ? '(Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto text-muted small">
                    Menampilkan ploting untuk:
                    <strong><?= esc($activeYear['year'] ?? '-') ?></strong>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($counselors)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Belum ada guru dengan permission <code>bk.manage</code>. Silakan atur di
            <a href="<?= base_url('admin/role-permission') ?>">Manajemen Role &amp; Permission</a>
            terlebih dahulu.
        </div>
    <?php else: ?>

    <!-- Tabel Ploting -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-primary"></i> Daftar Guru BK & Kelas yang Ditangani</h6>
            <span class="badge bg-primary-subtle text-primary"><?= count($counselors) ?> Guru</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px;">Guru BK</th>
                            <th style="width:140px;">NIP / Jenis PTK</th>
                            <th>Kelas yang Ditangani <small class="text-muted fw-normal">(centang untuk assign)</small></th>
                            <th style="width:120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($counselors as $c): ?>
                            <?php $assigned = $assignmentMap[$c['teacher_id']] ?? []; ?>
                            <tr id="row-teacher-<?= $c['teacher_id'] ?>">
                                <td>
                                    <div class="fw-semibold"><?= esc($c['teacher_name']) ?></div>
                                    <?php if (!empty($c['jenis_ptk'])): ?>
                                        <small class="badge bg-info-subtle text-info"><?= esc($c['jenis_ptk']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">jenis_ptk belum diisi</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= esc($c['nip'] ?: '-') ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2 align-items-center py-1"
                                         id="classes-<?= $c['teacher_id'] ?>">
                                        <?php foreach ($classes as $cls): ?>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input class-check"
                                                       type="checkbox"
                                                       id="cls-<?= $c['teacher_id'] ?>-<?= $cls['id'] ?>"
                                                       data-teacher="<?= $c['teacher_id'] ?>"
                                                       value="<?= $cls['id'] ?>"
                                                       <?= in_array((int)$cls['id'], $assigned) ? 'checked' : '' ?>>
                                                <label class="form-check-label small"
                                                       for="cls-<?= $c['teacher_id'] ?>-<?= $cls['id'] ?>">
                                                    <?= esc($cls['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($classes)): ?>
                                            <span class="text-muted small">Belum ada kelas aktif.</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary btn-save-assignment"
                                            data-teacher="<?= $c['teacher_id'] ?>"
                                            data-year="<?= $activeYear['id'] ?? 0 ?>">
                                        <i class="bi bi-save me-1"></i> Simpan
                                    </button>
                                    <div class="spinner-border spinner-border-sm text-primary d-none mt-1"
                                         id="spin-<?= $c['teacher_id'] ?>"></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-save-assignment').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const teacherId = this.dataset.teacher;
        const yearId    = this.dataset.year;
        const spin      = document.getElementById('spin-' + teacherId);
        const checked   = document.querySelectorAll(
            `.class-check[data-teacher="${teacherId}"]:checked`
        );
        const classIds  = Array.from(checked).map(cb => cb.value);

        btn.disabled = true;
        if (spin) spin.classList.remove('d-none');

        const fd = new FormData();
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        fd.append('teacher_id', teacherId);
        fd.append('year_id', yearId);
        classIds.forEach(id => fd.append('class_ids[]', id));

        fetch('<?= base_url('admin/bk/settings/save') ?>', {
            method: 'POST',
            body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Terjadi kesalahan.', 'danger');
            }
        })
        .catch(() => showToast('Koneksi gagal.', 'danger'))
        .finally(() => {
            btn.disabled = false;
            if (spin) spin.classList.add('d-none');
        });
    });
});

function showToast(msg, type) {
    const wrap = document.createElement('div');
    wrap.innerHTML = `<div class="toast align-items-center text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:9999">
        <div class="d-flex"><div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div></div>`;
    document.body.appendChild(wrap);
    setTimeout(() => wrap.remove(), 4000);
}
</script>

<?= $this->endSection() ?>
