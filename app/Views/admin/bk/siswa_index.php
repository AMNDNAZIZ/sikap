<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Profil & Pemantauan Siswa BK</h3>
            <p class="text-muted mb-0">
                Menampilkan siswa <strong>aktif</strong> pada Tahun Ajaran
                <span class="badge bg-primary-subtle text-primary fw-bold"><?= esc($activeYear['year'] ?? '-') ?></span>
                — Integrasi Data Multimodul ESPATA
            </p>
        </div>
        <div>
            <a href="<?= base_url('admin/bk/layanan') ?>" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-journal-check me-1"></i> Layanan BK
            </a>
            <a href="<?= base_url('admin/bk/kasus') ?>" class="btn btn-outline-danger rounded-pill px-3 ms-2">
                <i class="bi bi-shield-exclamation me-1"></i> Penanganan Kasus
            </a>
        </div>
    </div>

    <!-- Alert -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Box -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="get" action="<?= base_url('admin/bk/siswa') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-search me-1"></i> Cari Nama / NISN</label>
                        <input type="text" name="search" class="form-control rounded-3"
                            placeholder="Nama siswa, NISN, kelas..."
                            value="<?= esc($search ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-building me-1"></i> Filter Kelas</label>
                        <select name="class_id" class="form-select rounded-3">
                            <option value="">Semua Kelas (Rombel)</option>
                            <?php foreach ($classes as $cl) : ?>
                                <option value="<?= $cl['id'] ?>" <?= ($classId == $cl['id']) ? 'selected' : '' ?>>
                                    <?= esc($cl['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold fs-7 text-secondary"><i class="bi bi-calendar3 me-1"></i> Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select rounded-3">
                            <?php foreach ($academicYears as $ay) : ?>
                                <option value="<?= $ay['id'] ?>" <?= ($filterYearId == $ay['id']) ? 'selected' : '' ?>>
                                    <?= esc($ay['year']) ?> <?= $ay['is_active'] ? '(Aktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-3 fw-semibold w-100">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        <a href="<?= base_url('admin/bk/siswa') ?>" class="btn btn-light rounded-3 px-3">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Student List Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people text-primary me-2"></i>
                Daftar Siswa Aktif
            </h5>
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
                Total: <?= count($students) ?> Siswa
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Nama Siswa & NISN</th>
                            <th>JK</th>
                            <th>Kelas (Rombel)</th>
                            <th>Wali Kelas</th>
                            <th class="text-center">Alpa</th>
                            <th class="text-center">Layanan BK</th>
                            <th class="text-center">Kasus Aktif</th>
                            <th class="text-center">Sinyal</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)) : ?>
                            <?php foreach ($students as $idx => $s) : ?>
                                <?php
                                    $hasSignal = ($s['total_alpa'] >= 2) || ($s['active_cases'] > 0);
                                    $rowClass  = $hasSignal ? 'table-warning' : '';
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="ps-4 fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <i class="bi bi-person-fill text-info me-1"></i>
                                            <?= esc($s['name']) ?>
                                        </div>
                                        <small class="text-muted">NISN: <?= esc($s['nisn'] ?: '-') ?>
                                            <?php if (!empty($s['nis'])) : ?> | NIS: <?= esc($s['nis']) ?><?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($s['gender'] == 'L') : ?>
                                            <span class="badge bg-info-subtle text-info fw-semibold"><i class="bi bi-gender-male me-1"></i>L</span>
                                        <?php elseif ($s['gender'] == 'P') : ?>
                                            <span class="badge bg-danger-subtle text-danger fw-semibold"><i class="bi bi-gender-female me-1"></i>P</span>
                                        <?php else : ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-bold">
                                            <i class="bi bi-building me-1"></i><?= esc($s['class_name'] ?: 'Tanpa Kelas') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small">
                                            <i class="bi bi-person-badge text-secondary me-1"></i>
                                            <?= esc($s['wali_kelas_name'] ?: '-') ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['total_alpa'] >= 2) : ?>
                                            <span class="badge bg-danger fw-bold"><?= $s['total_alpa'] ?></span>
                                        <?php elseif ($s['total_alpa'] > 0) : ?>
                                            <span class="badge bg-warning text-dark fw-bold"><?= $s['total_alpa'] ?></span>
                                        <?php else : ?>
                                            <span class="text-muted small">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['total_services'] > 0) : ?>
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= $s['total_services'] ?></span>
                                        <?php else : ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['active_cases'] > 0) : ?>
                                            <span class="badge bg-danger fw-bold"><?= $s['active_cases'] ?></span>
                                        <?php else : ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($hasSignal) : ?>
                                            <span class="badge bg-warning text-dark" title="Siswa ini memiliki sinyal yang perlu perhatian">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Perhatian
                                            </span>
                                        <?php else : ?>
                                            <span class="text-success small"><i class="bi bi-check-circle-fill"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="<?= base_url('admin/bk/siswa/' . $s['id']) ?>"
                                            class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm">
                                            <i class="bi bi-card-checklist me-1"></i> Profil BK
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="bi bi-person-x fs-1 text-secondary mb-3 d-block"></i>
                                    <h5>Siswa aktif tidak ditemukan.</h5>
                                    <p class="text-muted">Pastikan tahun ajaran aktif sudah ditetapkan dan siswa sudah terdaftar di tahun ajaran tersebut.</p>
                                    <a href="<?= base_url('admin/bk/siswa') ?>" class="btn btn-outline-primary rounded-pill mt-2">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($students)) : ?>
        <div class="card-footer bg-light border-0 rounded-bottom-4 py-2 px-4">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Menampilkan <strong><?= count($students) ?></strong> siswa aktif pada tahun ajaran
                <strong><?= esc($activeYear['year'] ?? '-') ?></strong>.
                <span class="text-warning fw-semibold ms-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> = Perlu perhatian (alpa ≥ 2 atau kasus aktif)
                </span>
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
