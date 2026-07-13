<?php
/**
 * View: admin/teacher_attendance/report.php
 * Laporan rekap absensi guru bulanan
 */
?>
<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h4 class="mb-0">📊 Laporan Absensi Guru</h4>
        <a href="<?= base_url('admin/teacher-attendance') ?>" class="btn btn-outline-secondary btn-sm">
            ← Input Harian
        </a>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
                <label class="fw-semibold mb-0">Bulan:</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width:auto"
                    value="<?= esc($month) ?>">

                <label class="fw-semibold mb-0 ms-2">Guru:</label>
                <select name="teacher_id" class="form-select form-select-sm" style="width:auto">
                    <option value="">— Semua Guru —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= ((int)($filterTeacherId ?? 0) === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= esc($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
        </div>
    </div>

    <!-- Export -->
    <div class="d-flex gap-2 mb-3">
        <a href="<?= base_url('admin/teacher-attendance/export-excel?month=' . urlencode($month)) ?>"
            class="btn btn-success btn-sm">
            📥 Export Excel
        </a>
        <a href="<?= base_url('admin/teacher-attendance/export-pdf?month=' . urlencode($month)) ?>"
            class="btn btn-danger btn-sm">
            📄 Export PDF
        </a>
    </div>

    <!-- Tabel rekap -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($rekap)): ?>
                <div class="text-center text-muted py-4">
                    <p>Tidak ada data untuk bulan ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th class="text-center">Total JP Terjadwal</th>
                                <th class="text-center">JP Hadir</th>
                                <th class="text-center">JP Tidak Hadir</th>
                                <th class="text-center">% Kehadiran</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rekap as $r): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= esc($r['teacher_name']) ?></td>
                                    <td><?= esc($r['nip'] ?? '-') ?></td>
                                    <td class="text-center"><?= $r['total_jp'] ?></td>
                                    <td class="text-center"><?= $r['jp_hadir'] ?></td>
                                    <td class="text-center">
                                        <?php if ($r['jp_th'] > 0): ?>
                                            <a href="<?= base_url('admin/teacher-attendance/report/detail/' . $r['teacher_id'] . '?month=' . urlencode($month)) ?>"
                                                class="text-danger fw-semibold text-decoration-none">
                                                <?= $r['jp_th'] ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $persen = $r['persen'];
                                        $badgeClass = $persen >= 90 ? 'success' : ($persen >= 75 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= $persen ?>%</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/teacher-attendance/report/detail/' . $r['teacher_id'] . '?month=' . urlencode($month)) ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
