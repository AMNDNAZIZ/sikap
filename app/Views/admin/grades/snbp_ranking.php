<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">🎓 Pemeringkatan Siswa Eligible SNBP</h4>
        <p class="text-muted small mb-0">Perhitungan kuota dan pemeringkatan paralel siswa kelas XII SMA berdasarkan nilai rapor untuk persiapan PDSS SNPMB.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('admin/snbp/export-excel?akreditasi=' . esc($akreditasi)) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel (PDSS)
        </a>
    </div>
</div>

<!-- Filter Akreditasi & Ringkasan Kuota -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Pengaturan Kuota Sekolah</h6>
                <form method="get" action="<?= base_url('admin/snbp') ?>" class="row g-2 align-items-center">
                    <div class="col-8">
                        <select name="akreditasi" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="A" <?= $akreditasi === 'A' ? 'selected' : '' ?>>Akreditasi A (Kuota 40%)</option>
                            <option value="B" <?= $akreditasi === 'B' ? 'selected' : '' ?>>Akreditasi B (Kuota 25%)</option>
                            <option value="C" <?= $akreditasi === 'C' ? 'selected' : '' ?>>Akreditasi C (Kuota 5%)</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan</button>
                    </div>
                </form>
                <div class="mt-3 small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Tahun Ajaran: <strong><?= esc($activeYear['year'] ?? '-') ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-primary text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-white-50">Total Siswa Kelas XII Aktif</div>
                    <h2 class="fw-bold mb-0"><?= $totalSiswaTingkat12 ?></h2>
                    <div class="small mt-1 text-white-50">Terdaftar di rombel kelas 12</div>
                </div>
                <i class="bi bi-people-fill display-5 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-success text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-white-50">Kuota Siswa Eligible SNBP (<?= $kuotaPersen ?>%)</div>
                    <h2 class="fw-bold mb-0"><?= $kuotaEligible ?></h2>
                    <div class="small mt-1 text-white-50">Berhak didaftarkan ke PDSS</div>
                </div>
                <i class="bi bi-award-fill display-5 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Pemeringkatan -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Daftar Peringkat Paralel Kelas XII</h6>
            <span class="badge bg-light text-dark border">
                Peringkat 1 s.d. <?= $kuotaEligible ?> berstatus Eligible
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 70px;">Rank</th>
                        <th>NISN / NIS</th>
                        <th>Nama Peserta Didik</th>
                        <th>Kelas / Rombel</th>
                        <th class="text-center">Rata-Rata Nilai</th>
                        <th>Aspirasi PTN (BK)</th>
                        <th class="text-center" style="width: 140px;">Status SNBP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($studentsRanking)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                Tidak ada data siswa di tingkat kelas 12 untuk tahun ajaran aktif.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($studentsRanking as $row): ?>
                            <tr class="<?= $row['is_eligible'] ? 'table-success bg-opacity-25' : '' ?>">
                                <td class="text-center fw-bold">
                                    <?php if ($row['rank'] <= 3): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-trophy"></i> <?= $row['rank'] ?></span>
                                    <?php else: ?>
                                        <?= $row['rank'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['nisn'] ?: '-') ?></div>
                                    <small class="text-muted">NIS: <?= esc($row['nis'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($row['name']) ?></div>
                                    <small class="text-muted"><?= $row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= esc($row['class_name']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold fs-6 text-primary"><?= number_format($row['avg_score'], 2) ?></span>
                                </td>
                                <td>
                                    <div class="small"><strong>PTN:</strong> <?= esc($row['target_ptn']) ?></div>
                                    <div class="small text-muted"><strong>Prodi:</strong> <?= esc($row['major_interest']) ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['is_eligible']): ?>
                                        <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> ELIGIBLE</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">Tidak Eligible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

