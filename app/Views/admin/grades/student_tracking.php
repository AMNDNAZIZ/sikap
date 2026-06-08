<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
    <h3>📈 Tracking Nilai Siswa</h3>

    <div class="card mb-4 shadow-sm border-0 rounded-4">
        <div class="card-body">
            <form action="" method="get">
                <div class="mb-3">
                    <label class="form-label fw-bold">Cari Siswa</label>
                    <div class="position-relative">
                        <input type="text" id="student-search" class="form-control" placeholder="Ketik nama atau NIS..."
                            autocomplete="off">
                        <input type="hidden" name="student_id" id="student-id" value="<?= $student['id'] ?? '' ?>">
                        <div id="search-results" class="list-group position-absolute w-100 shadow"
                            style="z-index: 1000; display: none;"></div>
                    </div>
                    <small class="text-muted">Pilih siswa dari hasil pencarian.</small>
                </div>
            </form>
        </div>
    </div>

    <?php if ($student): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><?= esc($student['name']) ?></h5>
                    <p class="text-muted mb-0">NIS: <?= esc($student['nis']) ?></p>
                </div>
                <div>
                    <a href="<?= site_url('admin/grades/tracking/pdf?student_id=' . $student['id']) ?>" target="_blank"
                        class="btn btn-danger btn-sm">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                    <a href="<?= site_url('admin/grades/tracking/excel?student_id=' . $student['id']) ?>" target="_blank"
                        class="btn btn-success btn-sm">
                        <i class="bi bi-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($records)): ?>
            <div class="alert alert-warning">Belum ada rekam jejak akademik untuk siswa ini.</div>
        <?php endif; ?>

        <div class="timeline">
            <?php foreach ($records as $rec): ?>
                <div class="card mb-3 border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-primary">
                                <?= esc($rec['year_name']) ?> <span class="badge bg-secondary ms-2">
                                    <?= esc($rec['class_name']) ?>
                                </span>
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ([1, 2] as $sem): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 bg-light rounded-3 h-100 border">
                                        <h6 class="fw-bold text-center border-bottom pb-2 mb-3">Semester
                                            <?= $sem ?>
                                        </h6>

                                        <?php if (isset($grades[$rec['id']][$sem])): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    <thead>
                                                        <tr class="text-muted" style="font-size: 0.85rem;">
                                                            <th>Mapel</th>
                                                            <th class="text-center">Formatif</th>
                                                            <th class="text-center">Sumatif</th>
                                                            <?php if ($sem == 2): ?>
                                                                <th class="text-center">Final</th>
                                                            <?php endif; ?>
                                                            <th class="text-center text-secondary">Acuan</th>
                                                            <th class="text-center fw-bold text-success">Erapor</th>
                                                            <th class="text-center fw-bold text-dark">Nilai Akhir</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grades[$rec['id']][$sem] as $g): ?>
                                                            <tr>
                                                                <td><?= esc($g['subject_name']) ?></td>
                                                                <td class="text-center">
                                                                    <?= $g['scores']['formatif_avg'] ?? '-' ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <?= $g['scores']['sumatif_avg'] ?? '-' ?>
                                                                </td>
                                                                <?php if ($sem == 2): ?>
                                                                    <td class="text-center">
                                                                        <?= $g['scores']['final'] ?? '-' ?>
                                                                    </td>
                                                                <?php endif; ?>
                                                                <!-- Nilai acuan sistem -->
                                                                <td class="text-center text-secondary">
                                                                    <?= $g['scores']['rapor'] ?? '-' ?>
                                                                </td>
                                                                <!-- Nilai erapor guru -->
                                                                <td class="text-center fw-semibold text-success">
                                                                    <?php if ($g['scores']['erapor'] !== null): ?>
                                                                        <?= number_format((float)$g['scores']['erapor'], 2) ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted fst-italic small">belum</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <!-- Nilai akhir = erapor ?? rapor -->
                                                                <td class="text-center fw-bold">
                                                                    <?php $na = $g['scores']['nilai_akhir'] ?? null; ?>
                                                                    <?php if ($na !== null): ?>
                                                                        <span class="<?= $g['scores']['erapor'] !== null ? 'text-success' : '' ?>">
                                                                            <?= number_format((float)$na, 2) ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        -
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-center text-muted small my-4">Tidak ada data nilai.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    const searchInput = document.getElementById('student-search');
    const resultsDiv = document.getElementById('search-results');
    const studentIdInput = document.getElementById('student-id');

    let debounceTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const query = this.value;

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(async () => {
            const res = await fetch(`<?= site_url('admin/grades/search-student') ?>?q=${query}`);
            const data = await res.json();

            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                data.forEach(s => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = `${s.name} (${s.nis})`;
                    item.onclick = (e) => {
                        e.preventDefault();
                        searchInput.value = s.name;
                        studentIdInput.value = s.id;
                        resultsDiv.style.display = 'none';
                        // Submit form automatically
                        window.location.href = `<?= site_url('admin/grades/tracking') ?>?student_id=${s.id}`;
                    };
                    resultsDiv.appendChild(item);
                });
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.style.display = 'none';
            }
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && e.target !== resultsDiv) {
            resultsDiv.style.display = 'none';
        }
    });
</script>

<?= $this->endSection() ?>