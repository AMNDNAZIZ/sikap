<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>Daftar Pemetaan Guru - Kelas - Mapel</h2>

<form method="get" action="<?= site_url('admin/teachingassignments') ?>" class="row g-2 mb-3 align-items-end">
    <div class="col-md-4">
        <label class="form-label fw-semibold mb-1">Cari</label>
        <input type="text" name="q" value="<?= esc($keyword) ?>" class="form-control"
            placeholder="Cari guru, kelas, atau mapel...">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold mb-1">Tahun Ajaran</label>
        <select name="year_id" class="form-select">
            <option value="">-- Semua Tahun Ajaran --</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $yearId == $y['id'] ? 'selected' : '' ?>>
                    <?= esc($y['year']) ?><?= $y['is_active'] ? ' ✅ (Aktif)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-auto">
        <button type="submit" class="btn btn-primary">🔍 Cari</button>
        <a href="<?= site_url('admin/teachingassignments') ?>" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<?php if ($yearId): ?>
    <?php
    $selectedYear = null;
    foreach ($years as $y) { if ($y['id'] == $yearId) { $selectedYear = $y; break; } }
    ?>
    <?php if ($selectedYear): ?>
    <div class="alert alert-info py-2 mb-3">
        <i class="bi bi-calendar-check me-1"></i>
        Menampilkan pemetaan untuk tahun ajaran: <strong><?= esc($selectedYear['year']) ?></strong>
        <?= $selectedYear['is_active'] ? '<span class="badge bg-success ms-1">Aktif</span>' : '' ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/teachingassignments/bulk-delete') ?>" id="bulkDeleteForm">
    <?= csrf_field() ?>
    <?php if (session()->get('user')['role_id'] != 2): ?>
        <div class="mb-3">
            <a href="<?= site_url('admin/teachingassignments/create') ?>" class="btn btn-success">Tambah</a>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin hapus data terpilih?')">Hapus
                Terpilih</button>
        </div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <?php if (session()->get('user')['role_id'] != 2): ?>
                    <th><input type="checkbox" id="selectAll"></th>
                <?php endif; ?>
                <th>Guru</th>
                <th>Kelas</th>
                <th>Mata Pelajaran</th>
                <th>Tahun Ajaran</th>
                <th>Role</th>
                <?php if (session()->get('user')['role_id'] != 2): ?>
                    <th>Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($assignments)): ?>
                <?php foreach ($assignments as $a): ?>
                    <tr>
                        <?php if (session()->get('user')['role_id'] != 2): ?>
                            <td><input type="checkbox" name="ids[]" value="<?= $a['id'] ?>"></td>
                        <?php endif; ?>
                        <td><?= esc($a['teacher']) ?></td>
                        <td><?= esc($a['class']) ?></td>
                        <td><?= esc($a['subject']) ?></td>
                        <td><?= esc($a['academic_year']) ?></td>
                        <td><?= esc($a['role']) ?></td>
                        <?php if (session()->get('user')['role_id'] != 2): ?>
                            <td>
                                <a href="<?= site_url('admin/teachingassignments/edit/' . $a['id']) ?>"
                                    class="btn btn-sm btn-warning">Edit</a>
                                <a href="<?= site_url('admin/teachingassignments/delete/' . $a['id']) ?>"
                                    class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</form>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    <?= $pager->links('default', 'bootstrap') ?>
</div>

<script>
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

<?= $this->endSection() ?>