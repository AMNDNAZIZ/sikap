<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
function fmt($val) {
    return $val !== null && $val !== '' ? number_format((float)$val, 2) : null;
}
?>
<style>
.table-rekap { font-size: 0.8rem; }
.table-rekap th { background: #f0f4ff; white-space: nowrap; text-align: center; vertical-align: middle; }
.table-rekap td { vertical-align: middle; }
.table-rekap td.score { text-align: center; }
.table-rekap tr.avg-row td { background: #fff8e1; font-weight: bold; }
.score-null { color: #ccc; }
.score-high { color: #198754; font-weight: 600; }
.score-mid  { color: #0d6efd; }
.score-low  { color: #dc3545; }
.score-empty { color: #adb5bd; font-style: italic; font-size: 0.75rem; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0"><?= $title ?></h5>
            <small class="text-muted">Rekap nilai seluruh siswa dan mapel dalam satu tampilan</small>
        </div>
        <a href="<?= base_url('admin/grades') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" action="" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-bold">Tahun Ajaran</label>
                    <select name="year_id" class="form-select form-select-sm">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y['id'] ?>" <?= $yearId == $y['id'] ? 'selected' : '' ?>>
                                <?= esc($y['year']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-bold">Kelas</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-bold">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="1" <?= $semester == '1' ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $semester == '2' ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-bold">Jenis Nilai</label>
                    <select name="score_type" class="form-select form-select-sm">
                        <option value="formatif" <?= $scoreType === 'formatif' ? 'selected' : '' ?>>Formatif</option>
                        <option value="sumatif"  <?= $scoreType === 'sumatif'  ? 'selected' : '' ?>>Sumatif</option>
                        <option value="final"    <?= $scoreType === 'final'    ? 'selected' : '' ?>>Final</option>
                        <option value="rapor"    <?= $scoreType === 'rapor'    ? 'selected' : '' ?>>Nilai Rapor (Acuan Sistem)</option>
                        <option value="erapor"   <?= $scoreType === 'erapor'   ? 'selected' : '' ?>>Nilai Erapor (Input Guru)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($rekap): ?>
    <!-- Info & Cetak -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <strong><?= esc($rekap['class']['name']) ?></strong>
            &bull; <?= esc($rekap['year']['year']) ?>
            &bull; Semester <?= $semester ?>
            &bull; <span class="badge <?= $scoreType === 'erapor' ? 'bg-success' : 'bg-primary' ?>"><?= esc($rekap['score_type_label']) ?></span>
            &bull; <span class="text-muted"><?= count($rekap['students']) ?> siswa, <?= count($rekap['subjects']) ?> mapel</span>
        </div>
        <a href="<?= base_url('admin/grades/rekap/cetak?class_id=' . $classId . '&semester=' . $semester . '&score_type=' . $scoreType . '&year_id=' . $yearId) ?>"
           class="btn btn-success btn-sm" target="_blank">
            <i class="bi bi-printer"></i> Cetak
        </a>
        <a href="<?= base_url('admin/grades/rekap/excel?class_id=' . $classId . '&semester=' . $semester . '&score_type=' . $scoreType . '&year_id=' . $yearId) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </a>
    </div>

    <?php if (empty($rekap['students'])): ?>
        <div class="alert alert-warning">Tidak ada siswa ditemukan untuk kelas ini.</div>
    <?php elseif (empty($rekap['subjects'])): ?>
        <div class="alert alert-warning">Tidak ada mata pelajaran ditemukan untuk kelas ini.</div>
    <?php else: ?>

    <?php if ($scoreType === 'erapor'): ?>
    <div class="alert alert-success py-2 small mb-2">
        <i class="fas fa-info-circle"></i>
        <strong>Nilai Erapor</strong> adalah nilai rapor final yang diinput langsung oleh guru (prerogratif guru).
        Nilai <span class="score-empty fst-italic">belum</span> berarti guru belum mengisi nilai erapor untuk mapel tersebut.
        Gunakan menu <a href="<?= base_url('admin/erapor') ?>" class="alert-link">Nilai Erapor</a> untuk mengisi.
    </div>
    <?php endif; ?>
    <!-- Tabel Rekap -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-rekap mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" width="3%">No</th>
                            <th rowspan="2" width="8%">NIS</th>
                            <th rowspan="2" width="18%">Nama Siswa</th>
                            <?php foreach ($rekap['subjects'] as $subj): ?>
                                <th title="<?= esc($subj['name']) ?>"><?= esc($subj['code']) ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="table-warning">Jumlah</th>
                            <th rowspan="2" class="table-warning">Rata-rata</th>
                        </tr>
                        <tr class="d-none"><!-- spacer for rowspan --></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekap['students'] as $i => $student): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= esc($student['nis']) ?></td>
                            <td><?= esc($student['name']) ?></td>
                            <?php foreach ($rekap['subjects'] as $subj): ?>
                                <?php
                                $val = $rekap['scores'][$student['id']][$subj['id']] ?? null;
                                if ($val !== null) {
                                    $cls = $val >= 80 ? 'score-high' : ($val >= 65 ? 'score-mid' : 'score-low');
                                    $display = number_format((float)$val, 2);
                                } else {
                                    $cls = 'score-null';
                                    // Untuk erapor: tampilkan "belum" jika belum diisi guru
                                    $display = $scoreType === 'erapor'
                                        ? '<span class="score-empty">belum</span>'
                                        : '<span class="score-null">-</span>';
                                }
                                ?>
                                <td class="score <?= $cls ?>"><?= $display ?></td>
                            <?php endforeach; ?>
                            <td class="score table-warning fw-bold"><?= $student['row_total'] !== null ? number_format((float)$student['row_total'], 2) : '-' ?></td>
                            <td class="score table-warning fw-bold"><?= $student['row_avg'] !== null ? number_format((float)$student['row_avg'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="avg-row">
                            <td colspan="3" class="text-end fw-bold">Rata-rata Kelas</td>
                            <?php
                            $grandSum = 0; $grandCount = 0;
                            foreach ($rekap['subjects'] as $subj):
                                $avg = $rekap['col_avg'][$subj['id']] ?? null;
                                if ($avg !== null) { $grandSum += $avg; $grandCount++; }
                            ?>
                                <td class="score"><?= $avg !== null ? number_format((float)$avg, 2) : '-' ?></td>
                            <?php endforeach; ?>
                            <td class="score"><?= $grandCount > 0 ? number_format($grandSum, 2) : '-' ?></td>
                            <td class="score"><?= $grandCount > 0 ? number_format($grandSum / $grandCount, 2) : '-' ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend kode mapel -->
    <div class="mt-2">
        <small class="text-muted">
            <strong>Keterangan kode mapel:</strong>
            <?php foreach ($rekap['subjects'] as $i => $subj): ?>
                <span class="me-2"><?= esc($subj['code']) ?> = <?= esc($subj['name']) ?></span><?= $i < count($rekap['subjects']) - 1 ? '&bull;' : '' ?>
            <?php endforeach; ?>
        </small>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-table" style="font-size:3rem;"></i>
            <p class="mt-3">Pilih kelas, semester, dan jenis nilai lalu klik Tampilkan</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
