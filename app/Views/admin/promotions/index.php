<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">⬆️ Kenaikan &amp; Kelulusan</h4>
    <?php if (!empty($activeYear)): ?>
        <span class="badge bg-primary fs-6">Tahun Ajaran Aktif: <?= esc($activeYear['year']) ?></span>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (empty($prevYear)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Tidak ada tahun ajaran sebelumnya. Kenaikan kelas hanya bisa dilakukan setelah ada minimal dua tahun ajaran.
    </div>
<?php else: ?>

<!-- Step 1: Filter Kelas Sebelumnya -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold">
        <i class="bi bi-funnel me-1"></i> Langkah 1: Pilih Kelas dari Tahun Ajaran <strong><?= esc($prevYear['year']) ?></strong>
    </div>
    <div class="card-body">
        <form method="get" class="d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label mb-1">Kelas Sebelumnya</label>
                <select name="class_id" class="form-select" style="min-width:200px;" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($prevClasses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClassId == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?> (Level <?= $c['level'] ?>)
                            <?php if ((int)$c['level'] === $finalLevel): ?>
                                <span>🎓 Kelas Akhir</span>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filterClassId): ?>
                <a href="/admin/promotions" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($filterClassId && empty($students) && empty($graduatedStudents)): ?>
    <div class="alert alert-info">Tidak ada siswa aktif di kelas ini pada tahun ajaran <?= esc($prevYear['year']) ?>.</div>

<?php elseif (!empty($students) || !empty($graduatedStudents)): ?>

    <?php $isLastLevel = $isLastLevel ?? false; ?>

    <div class="alert <?= $isLastLevel ? 'alert-warning' : 'alert-info' ?> py-2">
        <i class="bi bi-info-circle me-1"></i>
        Kelas <strong><?= esc($selectedClass['name'] ?? '') ?></strong> (Level <?= $selectedClass['level'] ?? '' ?>)
        <?php if ($isLastLevel): ?>
            — <strong class="text-danger">Kelas Akhir (Level <?= $finalLevel ?>)</strong>. Siswa di kelas ini dapat diluluskan.
        <?php else: ?>
            — Siswa dapat dinaikkan ke kelas berikutnya.
        <?php endif; ?>
        <?php if (!empty($students)): ?>
            <span class="ms-2"><?= count($students) ?> siswa belum diproses</span>
        <?php endif; ?>
        <?php if (!empty($graduatedStudents)): ?>
            <span class="ms-2 badge bg-success"><?= count($graduatedStudents) ?> sudah diproses</span>
        <?php endif; ?>
    </div>

    <!-- Form Naikkan/Pindahkan -->
    <?php if (!$isLastLevel): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-semibold">
            <i class="bi bi-arrow-up-circle me-1"></i> Naikkan / Pindahkan Kelas
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('admin/promotions/promote') ?>" id="formPromote">
                <?= csrf_field() ?>
                <input type="hidden" name="filter_class_id" value="<?= esc($filterClassId) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kelas Tujuan <span class="text-danger">*</span></label>
                        <select name="target_class_id" class="form-select" required>
                            <option value="">-- Pilih Kelas Tujuan --</option>
                            <?php foreach ($targetClasses as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= esc($c['name']) ?> (Level <?= $c['level'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="checkAllPromote" title="Pilih Semua">
                                </th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas Sebelumnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="student_ids[]"
                                            value="<?= $s['student_id'] ?>" class="cb-promote">
                                    </td>
                                    <td><?= esc($s['nis'] ?? '-') ?></td>
                                    <td><?= esc($s['name']) ?></td>
                                    <td><?= esc($s['class_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-success px-4"
                    onclick="return validateSelection('promote')">
                    <i class="bi bi-arrow-up-circle me-1"></i> Naikkan / Pindahkan
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Luluskan (hanya tampil jika kelas akhir) -->
    <?php if ($isLastLevel): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark fw-semibold">
            <i class="bi bi-mortarboard me-1"></i> Luluskan Siswa
            <span class="badge bg-dark ms-2">Hanya kelas level <?= $finalLevel ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i>
                    Semua siswa di kelas ini sudah diproses (diluluskan atau dipindahkan).
                </div>
            <?php else: ?>
            <form method="post" action="<?= base_url('admin/promotions/graduate') ?>" id="formGraduate">
                <?= csrf_field() ?>
                <input type="hidden" name="filter_class_id" value="<?= esc($filterClassId) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Kelulusan <span class="text-danger">*</span></label>
                        <input type="date" name="graduation_date" class="form-control" required
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        <div class="form-text">Tanggal resmi kelulusan yang akan tercantum di data alumni.</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="checkAllGraduate" title="Pilih Semua">
                                </th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="student_ids[]"
                                            value="<?= $s['student_id'] ?>" class="cb-graduate">
                                    </td>
                                    <td><?= esc($s['nis'] ?? '-') ?></td>
                                    <td><?= esc($s['name']) ?></td>
                                    <td><?= esc($s['class_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold"
                    onclick="return validateSelection('graduate')">
                    <i class="bi bi-mortarboard me-1"></i> Luluskan Siswa Terpilih
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daftar siswa yang sudah diproses di tahun ajaran aktif -->
    <?php if (!empty($graduatedStudents)): ?>
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-check-circle me-1"></i> Sudah Diproses di Tahun Ajaran <?= esc($activeYear['year'] ?? '') ?>
                <span class="badge bg-light text-dark ms-2"><?= count($graduatedStudents) ?> siswa</span>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="post" action="<?= base_url('admin/promotions/cancel') ?>" id="formCancel">
                <?= csrf_field() ?>
                <input type="hidden" name="filter_class_id" value="<?= esc($filterClassId) ?>">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40" class="text-center">
                                    <input type="checkbox" id="checkAllCancel" title="Pilih Semua">
                                </th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas Sebelumnya</th>
                                <th>Status / Kelas Tujuan</th>
                                <th>Tanggal Kelulusan</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graduatedStudents as $s): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="student_ids[]"
                                            value="<?= $s['student_id'] ?>" class="cb-cancel">
                                    </td>
                                    <td><?= esc($s['nis'] ?? '-') ?></td>
                                    <td><?= esc($s['name']) ?></td>
                                    <td><?= esc($s['class_name']) ?></td>
                                    <td>
                                        <?php if ($s['processed_status'] === 'lulus'): ?>
                                            <span class="badge bg-success">🎓 Lulus</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">⬆️ Naik ke <?= esc($s['target_class_name'] ?? 'Kelas Baru') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $s['graduation_date'] ? date('d/m/Y', strtotime($s['graduation_date'])) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="cancelSingle('<?= $s['student_id'] ?>', '<?= esc(addslashes($s['name'])) ?>')">
                                            <i class="bi bi-arrow-counterclockwise"></i> Batal
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light border-top">
                    <button type="submit" class="btn btn-danger btn-sm px-3"
                        onclick="return validateSelection('cancel')">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan Kenaikan / Kelulusan Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($filterClassId): ?>
    <!-- sudah ditangani di atas -->
<?php else: ?>
    <div class="alert alert-light border text-muted text-center">
        <i class="bi bi-arrow-up fs-3 d-block mb-2"></i>
        Pilih kelas di atas untuk menampilkan daftar siswa yang akan dinaikkan atau diluluskan.
    </div>
<?php endif; ?>

<?php endif; ?>

<script>
// Select All untuk form promote
document.getElementById('checkAllPromote')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-promote').forEach(cb => cb.checked = this.checked);
});

// Select All untuk form graduate
document.getElementById('checkAllGraduate')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-graduate').forEach(cb => cb.checked = this.checked);
});

// Select All untuk form cancel
document.getElementById('checkAllCancel')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-cancel').forEach(cb => cb.checked = this.checked);
});

function validateSelection(type) {
    const cls = type === 'promote' ? '.cb-promote' : (type === 'graduate' ? '.cb-graduate' : '.cb-cancel');
    const checked = document.querySelectorAll(cls + ':checked').length;
    if (checked === 0) {
        alert('Pilih minimal satu siswa terlebih dahulu.');
        return false;
    }
    let action = 'menaikkan';
    if (type === 'graduate') action = 'meluluskan';
    if (type === 'cancel') action = 'membatalkan kenaikan/kelulusan';

    return confirm('Yakin ingin ' + action + ' ' + checked + ' siswa terpilih?');
}

function cancelSingle(studentId, studentName) {
    if (confirm('Yakin ingin membatalkan kenaikan/kelulusan untuk siswa ' + studentName + '?')) {
        const form = document.getElementById('formCancel');
        document.querySelectorAll('.cb-cancel').forEach(cb => cb.checked = (cb.value == studentId));
        form.submit();
    }
}
</script>

<?= $this->endSection() ?>
