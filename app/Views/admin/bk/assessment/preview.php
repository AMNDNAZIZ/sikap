<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header & Action Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-info text-dark px-2 py-1"><i class="fas fa-eye me-1"></i> MODE PREVIEW</span>
                <?php if ($instrument['status'] === 'published'): ?>
                    <span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i> Published</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-pencil-alt me-1"></i> Draft</span>
                <?php endif; ?>
                <span class="badge bg-secondary px-2 py-1"><?= esc(strtoupper($instrument['assessment_type'] ?? 'AKPD')) ?></span>
            </div>
            <h1 class="h3 mb-0 text-gray-800"><?= esc($instrument['title']) ?></h1>
            <?php if (!empty($instrument['description'])): ?>
                <p class="text-muted small mb-0 mt-1"><?= esc($instrument['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/bk/pemetaan/instrumen/edit/' . $instrument['id']) ?>" class="btn btn-sm btn-outline-secondary shadow-sm">
                <i class="fas fa-edit me-1"></i> Kembali ke Builder
            </a>
            <a href="<?= base_url('admin/bk/pemetaan') ?>" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Menu Pemetaan
            </a>
        </div>
    </div>

    <!-- Alert Preview Notice -->
    <div class="alert alert-info border-left-info shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
        <div>
            <strong>Simulasi Tampilan Siswa:</strong> Halaman ini menampilkan instrumen kuesioner persis seperti yang akan dilihat dan diisi oleh siswa. Anda dapat menguji klik pilihan jawaban. Seluruh interaksi di mode ini <strong>tidak akan disimpan</strong> ke database.
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Aspek</small>
                        <div class="h5 mb-0 fw-bold text-primary"><?= count($domains) ?></div>
                    </div>
                    <i class="fas fa-layer-group text-gray-300 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Pertanyaan</small>
                        <div class="h5 mb-0 fw-bold text-success"><?= count($questions) ?></div>
                    </div>
                    <i class="fas fa-question-circle text-gray-300 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Pilihan Skala</small>
                        <div class="h5 mb-0 fw-bold text-info"><?= count($scales) ?> Opsi</div>
                    </div>
                    <i class="fas fa-sliders-h text-gray-300 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Level Interpretasi</small>
                        <div class="h5 mb-0 fw-bold text-warning"><?= count($interpretations) ?> Tingkat</div>
                    </div>
                    <i class="fas fa-chart-pie text-gray-300 fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Questionnaire Preview -->
    <div class="row">
        <!-- Sidebar Domain Navigation -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-ul me-2"></i>Aspek / Bidang</h6>
                </div>
                <div class="list-group list-group-flush" id="previewDomainTabs" role="tablist">
                    <?php if (empty($domains)): ?>
                        <div class="p-3 text-muted small text-center">Belum ada aspek yang dibuat.</div>
                    <?php else: ?>
                        <?php foreach ($domains as $idx => $d): 
                            $qCount = count(array_filter($questions, fn($q) => $q['domain_id'] == $d['id']));
                        ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $idx === 0 ? 'active' : '' ?>"
                               id="tab-domain-<?= $d['id'] ?>"
                               data-bs-toggle="tab"
                               href="#content-domain-<?= $d['id'] ?>"
                               role="tab">
                                <span class="text-truncate me-2"><?= esc($d['domain_name']) ?></span>
                                <span class="badge rounded-pill <?= $idx === 0 ? 'bg-light text-dark' : 'bg-primary' ?>"><?= $qCount ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light py-2 text-center">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Klik aspek untuk berpindah</small>
                </div>
            </div>
        </div>

        <!-- Question Content -->
        <div class="col-lg-9 col-md-8 mb-4">
            <?php if (empty($domains)): ?>
                <div class="card border-0 shadow-sm rounded-3 p-5 text-center bg-white">
                    <i class="fas fa-folder-open text-muted fa-4x mb-3"></i>
                    <h5 class="fw-bold text-gray-700">Belum Ada Aspek & Pertanyaan</h5>
                    <p class="text-muted mb-3">Instrumen ini belum memiliki konfigurasi aspek dan butir pertanyaan.</p>
                    <div>
                        <a href="<?= base_url('admin/bk/pemetaan/instrumen/edit/' . $instrument['id']) ?>" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fas fa-plus me-1"></i> Buka Builder & Tambah Pertanyaan
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="tab-content" id="previewDomainContent">
                    <?php foreach ($domains as $idx => $d): 
                        $domainQuestions = array_values(array_filter($questions, fn($q) => $q['domain_id'] == $d['id']));
                    ?>
                        <div class="tab-pane fade <?= $idx === 0 ? 'show active' : '' ?>" 
                             id="content-domain-<?= $d['id'] ?>" 
                             role="tabpanel">
                            
                            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
                                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-white text-primary mb-1">Aspek <?= $idx + 1 ?></span>
                                        <h5 class="mb-0 text-white fw-bold"><?= esc($d['domain_name']) ?></h5>
                                        <?php if (!empty($d['sub_domain'])): ?>
                                            <small class="text-white-50 d-block mt-1"><?= esc($d['sub_domain']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-light text-dark fw-bold"><?= count($domainQuestions) ?> Butir</span>
                                </div>

                                <div class="card-body p-4">
                                    <?php if (empty($domainQuestions)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-question-circle fa-2x mb-2 text-gray-300"></i>
                                            <p class="mb-2">Belum ada pertanyaan pada aspek ini.</p>
                                            <a href="<?= base_url('admin/bk/pemetaan/instrumen/edit/' . $instrument['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-plus me-1"></i> Tambah Pertanyaan
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($domainQuestions as $qNo => $q): ?>
                                            <div class="mb-4 pb-4 <?= $qNo < count($domainQuestions) - 1 ? 'border-bottom' : '' ?>">
                                                <div class="d-flex align-items-start mb-3">
                                                    <span class="badge bg-secondary me-2 mt-1"><?= $qNo + 1 ?></span>
                                                    <p class="mb-0 fw-semibold text-gray-800 fs-6">
                                                        <?= esc($q['question_text']) ?>
                                                    </p>
                                                </div>

                                                <!-- Scale Options (Simulasi Siswa) -->
                                                <div class="row g-2 ps-md-4">
                                                    <?php foreach ($scales as $s): 
                                                        $sVal = $s['scale_value'] ?? $s['value'] ?? '';
                                                        $sLbl = $s['scale_label'] ?? $s['label'] ?? '';
                                                    ?>
                                                        <div class="col-12 col-sm-6 col-md-3">
                                                            <div class="form-check form-check-inline w-100 p-0 m-0">
                                                                <input type="radio" 
                                                                       class="btn-check" 
                                                                       name="preview_q_<?= $q['id'] ?>" 
                                                                       id="preview_q_<?= $q['id'] ?>_<?= $sVal ?>" 
                                                                       value="<?= $sVal ?>"
                                                                       autocomplete="off">
                                                                <label class="btn btn-outline-primary w-100 text-start h-100 p-2 d-flex align-items-center gap-2 rounded-2" 
                                                                       for="preview_q_<?= $q['id'] ?>_<?= $sVal ?>">
                                                                    <span class="badge bg-light text-primary border"><?= esc($sVal) ?></span>
                                                                    <span class="small lh-sm"><?= esc($sLbl) ?></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($idx > 0): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="switchTab(<?= $idx - 1 ?>)">
                                                <i class="fas fa-chevron-left me-1"></i> Aspek Sebelumnya
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($idx < count($domains) - 1): ?>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="switchTab(<?= $idx + 1 ?>)">
                                                Aspek Berikutnya <i class="fas fa-chevron-right ms-1"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="alert('Ini adalah mode preview simulasi. Pada akun siswa, tombol ini akan mengirimkan seluruh jawaban.')">
                                                <i class="fas fa-check-circle me-1"></i> Selesai & Kirim (Simulasi)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Collapsible: Konfigurasi Skala & Interpretasi -->
            <div class="accordion shadow-sm rounded-3 mt-4" id="accordionConfig">
                <!-- Skala -->
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingScale">
                        <button class="accordion-button collapsed fw-bold text-gray-800" type="button" data-bs-toggle="collapse" data-bs-target="#collapseScale" aria-expanded="false" aria-controls="collapseScale">
                            <i class="fas fa-sliders-h me-2 text-info"></i> Referensi Skala Penilaian (<?= count($scales) ?> Pilihan)
                        </button>
                    </h2>
                    <div id="collapseScale" class="accordion-collapse collapse" aria-labelledby="headingScale" data-bs-parent="#accordionConfig">
                        <div class="accordion-body bg-light">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;" class="text-center">Skor / Bobot</th>
                                            <th>Label Jawaban</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scales as $s): 
                                            $sVal = $s['scale_value'] ?? $s['value'] ?? '';
                                            $sLbl = $s['scale_label'] ?? $s['label'] ?? '';
                                        ?>
                                            <tr>
                                                <td class="text-center fw-bold text-primary"><?= esc($sVal) ?></td>
                                                <td><?= esc($sLbl) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interpretasi -->
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="headingInterp">
                        <button class="accordion-button collapsed fw-bold text-gray-800" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInterp" aria-expanded="false" aria-controls="collapseInterp">
                            <i class="fas fa-chart-line me-2 text-warning"></i> Kriteria Interpretasi Hasil (<?= count($interpretations) ?> Kategori)
                        </button>
                    </h2>
                    <div id="collapseInterp" class="accordion-collapse collapse" aria-labelledby="headingInterp" data-bs-parent="#accordionConfig">
                        <div class="accordion-body bg-light">
                            <?php if (empty($interpretations)): ?>
                                <p class="text-muted small mb-0">Belum ada rentang interpretasi yang dikonfigurasi pada instrumen ini.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 140px;" class="text-center">Rentang %</th>
                                                <th style="width: 160px;">Tingkat / Label</th>
                                                <th>Deskripsi & Rekomendasi BK</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($interpretations as $interp): ?>
                                                <tr>
                                                    <td class="text-center font-monospace fw-bold">
                                                        <?= esc($interp['min_percent']) ?>% - <?= esc($interp['max_percent']) ?>%
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= esc($interp['level_label']) ?></span>
                                                    </td>
                                                    <td class="small"><?= esc($interp['description'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function switchTab(index) {
        const tabs = document.querySelectorAll('#previewDomainTabs .list-group-item');
        if (tabs[index]) {
            const tabTrigger = new bootstrap.Tab(tabs[index]);
            tabTrigger.show();
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }
    }
</script>
<?= $this->endSection() ?>
