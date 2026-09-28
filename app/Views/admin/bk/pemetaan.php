<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-diagram-3-fill text-primary me-2"></i> Pemetaan Siswa & Asesmen BK</h3>
            <p class="text-muted mb-0">Instrumen Kebutuhan (AKPD), Hasil Asesmen, & Profil Perkembangan Siswa</p>
        </div>
        <div>
            <a href="<?= base_url('admin/bk/pemetaan/penugasan') ?>" class="btn btn-info rounded-pill fw-semibold shadow-sm me-2 text-white">
                <i class="bi bi-send-check me-1"></i> Penugasan Asesmen
            </a>
            <a href="<?= base_url('admin/bk/pemetaan/instrumen/create') ?>" class="btn btn-primary rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Buat Angket Asesmen
            </a>
        </div>
    </div>

    <!-- Tabs Nav -->
    <ul class="nav nav-pills mb-4 bg-light p-2 rounded-4" id="pemetaanTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-semibold" id="instrumen-tab" data-bs-toggle="tab" data-bs-target="#instrumen-pane" type="button" role="tab"><i class="bi bi-file-earmark-text me-2"></i>Instrumen Asesmen</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="hasil-tab" data-bs-toggle="tab" data-bs-target="#hasil-pane" type="button" role="tab"><i class="bi bi-bar-chart-line me-2"></i>Hasil Asesmen Siswa</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="peta-tab" data-bs-toggle="tab" data-bs-target="#peta-pane" type="button" role="tab"><i class="bi bi-grid-3x3-gap me-2"></i>Peta Kebutuhan Kelas</button>
        </li>
    </ul>

    <div class="tab-content" id="pemetaanTabContent">
        <!-- Pane 1: Instrumen Asesmen -->
        <div class="tab-pane fade show active" id="instrumen-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Judul Instrumen</th>
                                    <th>Tipe</th>
                                    <th>Sasaran Tingkat</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($assessments)) : ?>
                                    <?php foreach ($assessments as $a) : ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= esc($a['title']) ?></td>
                                            <td><span class="badge bg-info-subtle text-info fw-bold"><?= esc($a['assessment_type']) ?></span></td>
                                            <td><?= esc($a['target_level']) ?></td>
                                            <td>
                                                <?php if ($a['status'] === 'published') : ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php elseif ($a['status'] === 'draft') : ?>
                                                    <span class="badge bg-secondary">Draft</span>
                                                <?php else : ?>
                                                    <span class="badge bg-dark"><?= esc(ucfirst($a['status'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('admin/bk/pemetaan/instrumen/edit/' . $a['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Builder</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                                            Belum ada instrumen asesmen. Silakan buat instrumen AKPD baru.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pane 2: Hasil Asesmen -->
        <div class="tab-pane fade" id="hasil-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Instrumen</th>
                                    <th>Skor</th>
                                    <th>%</th>
                                    <th>Interpretasi</th>
                                    <th>Tanggal Pengisian</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($responses)) : ?>
                                    <?php foreach ($responses as $r) : ?>
                                        <tr>
                                            <td class="fw-bold"><?= esc($r['student_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= esc($r['class_name'] ?: '-') ?></span></td>
                                            <td>
                                                <?= esc($r['assessment_title']) ?><br>
                                                <small class="text-muted"><?= esc($r['assessment_type']) ?></small>
                                            </td>
                                            <td><span class="badge bg-primary-subtle text-primary"><?= $r['total_score'] ?> / <?= $r['total_max_score'] ?></span></td>
                                            <td><span class="fw-bold"><?= $r['percentage'] ?>%</span></td>
                                            <td><span class="badge bg-info"><?= esc($r['overall_level']) ?></span></td>
                                            <td><?= date('d M Y H:i', strtotime($r['calculated_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="viewDetail(<?= $r['id'] ?>)">Detail</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Belum ada respon asesmen dari siswa.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pane 3: Peta Kebutuhan Kelas -->
        <div class="tab-pane fade" id="peta-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form id="formPetaKelas" onsubmit="loadClassMap(event)">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Pilih Instrumen</label>
                                <select class="form-select" name="instrument_id" required>
                                    <option value="">-- Pilih Instrumen --</option>
                                    <?php foreach ($assessments as $a) : ?>
                                        <option value="<?= $a['id'] ?>"><?= esc($a['title']) ?> (<?= esc($a['assessment_type']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Pilih Kelas</label>
                                <select class="form-select" name="class_id" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($classes as $c) : ?>
                                        <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Tampilkan Peta</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="petaResultContainer" style="display: none;">
                <div class="row mb-4" id="classAveragesContainer">
                    <!-- Averages cards populated by JS -->
                </div>
                
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Matriks Individu</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center" id="petaMatrixTable">
                                <thead class="table-light" id="petaMatrixHead">
                                    <!-- Populated by JS -->
                                </thead>
                                <tbody id="petaMatrixBody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Result Detail -->
<div class="modal fade" id="modalResultDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary">Detail Hasil Asesmen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Nama Siswa</p>
                        <h6 class="fw-bold" id="detail_student_name"></h6>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1 text-muted small">Kelas</p>
                        <h6 class="fw-bold" id="detail_class_name"></h6>
                    </div>
                </div>
                
                <div class="alert alert-info border-0 rounded-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-muted small">Total Skor Keseluruhan</p>
                            <h4 class="mb-0 fw-bold" id="detail_total_score"></h4>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm" id="detail_overall_level"></span>
                        </div>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Rincian Per Aspek (Domain)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Aspek / Dimensi</th>
                                <th class="text-center">Skor / Max</th>
                                <th>Persentase</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody id="detail_domain_list">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary rounded-pill px-4"><i class="bi bi-printer me-2"></i>Cetak</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function viewDetail(resultId) {
        fetch('<?= base_url('admin/bk/pemetaan/hasil/detail') ?>/' + resultId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('detail_student_name').innerText = data.result.student_name + ' (' + data.result.nisn + ')';
                    document.getElementById('detail_class_name').innerText = data.result.class_name;
                    document.getElementById('detail_total_score').innerText = data.result.total_score + ' / ' + data.result.total_max_score + ' (' + parseFloat(data.result.percentage).toFixed(1) + '%)';
                    document.getElementById('detail_overall_level').innerText = data.result.overall_level;
                    
                    let tbody = document.getElementById('detail_domain_list');
                    tbody.innerHTML = '';
                    
                    data.details.forEach(d => {
                        let colorClass = 'bg-secondary';
                        if(d.level_label === 'Sangat Tinggi' || d.level_label === 'Sangat Sesuai') colorClass = 'bg-primary';
                        else if(d.level_label === 'Tinggi' || d.level_label === 'Sesuai') colorClass = 'bg-success';
                        else if(d.level_label === 'Sedang' || d.level_label === 'Kurang Sesuai') colorClass = 'bg-warning text-dark';
                        else if(d.level_label === 'Rendah' || d.level_label === 'Tidak Sesuai') colorClass = 'bg-danger';

                        let tr = `<tr>
                            <td class="fw-bold">${d.domain_name} ${d.sub_domain ? '<small class="text-muted d-block">'+d.sub_domain+'</small>' : ''}</td>
                            <td class="text-center">${d.domain_score} / ${d.domain_max_score}</td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${d.percentage}%"></div>
                                </div>
                                <small class="text-muted">${parseFloat(d.percentage).toFixed(1)}%</small>
                            </td>
                            <td><span class="badge ${colorClass}">${d.level_label}</span></td>
                        </tr>`;
                        tbody.innerHTML += tr;
                    });
                    
                    new bootstrap.Modal(document.getElementById('modalResultDetail')).show();
                } else {
                    alert('Data tidak ditemukan');
                }
            });
    }
    function loadClassMap(e) {
        e.preventDefault();
        
        let form = e.target;
        let formData = new FormData(form);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        
        // Show loading state...
        document.getElementById('petaResultContainer').style.display = 'block';
        document.getElementById('classAveragesContainer').innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat Peta Kelas...</p></div>';
        document.getElementById('petaMatrixHead').innerHTML = '';
        document.getElementById('petaMatrixBody').innerHTML = '';
        
        fetch('<?= base_url('admin/bk/pemetaan/peta-kelas') ?>', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'error') {
                document.getElementById('classAveragesContainer').innerHTML = `<div class="col-12"><div class="alert alert-warning">${data.message}</div></div>`;
                return;
            }
            
            // Render Averages Cards
            let avgHtml = '';
            data.averages.forEach(avg => {
                avgHtml += `
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">${avg.domain_name}</h6>
                            <h3 class="fw-bold text-dark mb-0">${avg.average_percentage}%</h3>
                        </div>
                    </div>
                </div>`;
            });
            document.getElementById('classAveragesContainer').innerHTML = avgHtml;
            
            // Render Table Head
            let thead = `<tr>
                <th class="text-start" style="width: 250px;">Nama Siswa</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 120px;">Interpretasi</th>`;
            data.domains.forEach(d => {
                thead += `<th>${d.domain_name}</th>`;
            });
            thead += `</tr>`;
            document.getElementById('petaMatrixHead').innerHTML = thead;
            
            // Render Table Body
            let tbody = '';
            data.map.forEach(student => {
                let statusBadge = student.has_result ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>' : '<span class="badge bg-secondary">Belum Mengerjakan</span>';
                let levelBadge = student.has_result ? `<span class="badge bg-info">${student.overall_level}</span>` : '-';
                
                tbody += `<tr>
                    <td class="text-start fw-bold">${student.name} <br><small class="text-muted fw-normal">${student.nisn}</small></td>
                    <td>${statusBadge}</td>
                    <td>${levelBadge}</td>`;
                
                data.domains.forEach(d => {
                    let dData = student.domains[d.id];
                    if(dData) {
                        let color = 'text-dark';
                        if(dData.level_label.includes('Tinggi') || dData.level_label.includes('Sangat Membutuhkan')) color = 'text-danger fw-bold';
                        
                        tbody += `<td>
                            <div class="${color}">${parseFloat(dData.percentage).toFixed(1)}%</div>
                            <small class="text-muted" style="font-size:10px;">${dData.level_label}</small>
                        </td>`;
                    } else {
                        tbody += `<td class="text-muted">-</td>`;
                    }
                });
                
                tbody += `</tr>`;
            });
            document.getElementById('petaMatrixBody').innerHTML = tbody;
        })
        .catch(err => {
            document.getElementById('classAveragesContainer').innerHTML = `<div class="col-12"><div class="alert alert-danger">Terjadi kesalahan pada server.</div></div>`;
        });
    }
</script>
<?= $this->endSection() ?>
