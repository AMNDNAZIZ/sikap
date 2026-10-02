<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">⚙️ Builder Instrumen: <?= esc($instrument['title']) ?></h1>
        <div>
            <a href="<?= base_url('admin/bk/pemetaan') ?>" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
            <button type="button" class="btn btn-sm btn-info shadow-sm" onclick="previewInstrument()">
                <i class="fas fa-eye fa-sm text-white-50"></i> Preview
            </button>
            <?php if ($instrument['status'] === 'draft'): ?>
            <form action="<?= base_url('admin/bk/pemetaan/instrumen/publish/'.$instrument['id']) ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-success shadow-sm" onclick="return confirm('Publish instrumen ini? Siswa akan dapat diassign setelah di-publish.')">
                    <i class="fas fa-check fa-sm text-white-50"></i> Publish
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs" id="builderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">1. Informasi Umum</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="scale-tab" data-bs-toggle="tab" data-bs-target="#scale" type="button" role="tab">2. Skala Jawaban</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="question-tab" data-bs-toggle="tab" data-bs-target="#question" type="button" role="tab">3. Aspek & Pertanyaan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="interpretation-tab" data-bs-toggle="tab" data-bs-target="#interpretation" type="button" role="tab">4. Interpretasi</button>
        </li>
    </ul>

    <div class="tab-content bg-white border border-top-0 p-4 mb-4" id="builderTabsContent">
        <!-- 1. INFO -->
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <form action="<?= base_url('admin/bk/pemetaan/instrumen/update/'.$instrument['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="row">
                    <!-- Form fields similar to create.php, pre-filled with $instrument data -->
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Judul Instrumen</label>
                            <input type="text" class="form-control" name="title" required value="<?= esc($instrument['title']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3"><?= esc($instrument['description']) ?></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Info</button>
            </form>
        </div>
        
        <!-- 2. SCALE -->
        <div class="tab-pane fade" id="scale" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Konfigurasi Skala Jawaban</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scaleModal">
                    <i class="fas fa-plus"></i> Tambah Skala
                </button>
            </div>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Label Jawaban (Misal: Selalu)</th>
                        <th>Nilai/Bobot (Misal: 4)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($scales)): ?>
                        <tr><td colspan="3" class="text-center">Belum ada skala jawaban</td></tr>
                    <?php else: ?>
                        <?php foreach($scales as $s): ?>
                        <tr>
                            <td><?= esc($s['scale_label']) ?></td>
                            <td><?= $s['scale_value'] ?></td>
                            <td>
                                <form action="<?= base_url('admin/bk/pemetaan/instrumen/scale/delete/' . $s['id']) ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus skala ini?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 3. QUESTION -->
        <div class="tab-pane fade" id="question" role="tabpanel">
            <!-- (Code for tab question stays the same, omitted in this block since we are targeting line 63-121) -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Aspek & Pertanyaan</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#domainModal">
                    <i class="fas fa-plus"></i> Tambah Aspek
                </button>
            </div>
            
            <div id="domains-container">
                <?php foreach ($domains as $domain): ?>
                <div class="card mb-3 border-left-primary shadow-sm" id="domain-<?= $domain['id'] ?>">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= esc($domain['domain_name']) ?> 
                            <?= $domain['sub_domain'] ? ' - <small class="text-muted">'.esc($domain['sub_domain']).'</small>' : '' ?>
                        </h6>
                        <div>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDomain(<?= $domain['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3" id="question-list-<?= $domain['id'] ?>">
                            <?php foreach ($questions as $q): ?>
                                <?php if ($q['domain_id'] == $domain['id']): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center" id="question-<?= $q['id'] ?>">
                                    <div>
                                        <?= esc($q['question_text']) ?>
                                        <?php if ($q['is_reverse']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Reverse</span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-danger" onclick="deleteQuestion(<?= $q['id'] ?>)"><i class="fas fa-times"></i></button>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <button class="btn btn-sm btn-outline-secondary" onclick="openQuestionModal(<?= $domain['id'] ?>)">
                            <i class="fas fa-plus"></i> Tambah Pertanyaan
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 4. INTERPRETATION -->
        <div class="tab-pane fade" id="interpretation" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Level Interpretasi Skor</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#interpModal">
                    <i class="fas fa-plus"></i> Tambah Interpretasi
                </button>
            </div>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Rentang (%)</th>
                        <th>Label (Misal: Sangat Sesuai)</th>
                        <th>Aspek Spesifik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($interpretations)): ?>
                        <tr><td colspan="4" class="text-center">Belum ada interpretasi</td></tr>
                    <?php else: ?>
                        <?php foreach($interpretations as $i): ?>
                        <tr>
                            <td><?= $i['min_percent'] ?>% - <?= $i['max_percent'] ?>%</td>
                            <td><?= esc($i['level_label']) ?></td>
                            <td><?= $i['domain_id'] ? 'Ya' : 'Global' ?></td>
                            <td>
                                <form action="<?= base_url('admin/bk/pemetaan/instrumen/interp/delete/' . $i['id']) ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus interpretasi ini?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Aspek -->
<div class="modal fade" id="domainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddDomain" onsubmit="saveDomain(event)">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Aspek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="instrument_id" value="<?= $instrument['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama Aspek (Contoh: Pribadi, Sosial)</label>
                        <input type="text" class="form-control" name="domain_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sub Aspek (Opsional)</label>
                        <input type="text" class="form-control" name="sub_domain">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Pertanyaan -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddQuestion" onsubmit="saveQuestion(event)">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pertanyaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="instrument_id" value="<?= $instrument['id'] ?>">
                    <input type="hidden" name="domain_id" id="modal_domain_id">
                    <div class="mb-3">
                        <label class="form-label">Teks Pertanyaan</label>
                        <textarea class="form-control" name="question_text" required rows="3"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_reverse" value="1" id="is_reverse">
                        <label class="form-check-label" for="is_reverse">
                            Reverse Scoring (Skor Dibalik)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Skala -->
<div class="modal fade" id="scaleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/bk/pemetaan/instrumen/scale/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Skala Jawaban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="instrument_id" value="<?= $instrument['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Label Jawaban (Misal: Selalu, Sering)</label>
                        <input type="text" class="form-control" name="scale_label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nilai / Bobot Skor</label>
                        <input type="number" class="form-control" name="scale_value" required min="0" step="0.1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Interpretasi -->
<div class="modal fade" id="interpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/bk/pemetaan/instrumen/interp/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Interpretasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="instrument_id" value="<?= $instrument['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Terapkan Pada Aspek</label>
                        <select class="form-select" name="domain_id">
                            <option value="">-- Berlaku Global (Seluruh Instrumen) --</option>
                            <?php foreach($domains as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= esc($d['domain_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Min %</label>
                            <input type="number" class="form-control" name="min_percent" required min="0" max="100" step="0.1">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Max %</label>
                            <input type="number" class="form-control" name="max_percent" required min="0" max="100" step="0.1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label Interpretasi (Misal: Tinggi, Rendah)</label>
                        <input type="text" class="form-control" name="level_label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rekomendasi / Deskripsi Singkat</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const baseUrl = '<?= base_url('admin/bk/pemetaan/instrumen') ?>';
    
    function saveDomain(e) {
        e.preventDefault();
        let formData = new FormData(e.target);
        fetch(baseUrl + '/domain/store', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload(); // Sederhana: reload halaman untuk melihat hasil
            }
        });
    }

    function deleteDomain(id) {
        if (!confirm('Hapus aspek ini beserta semua pertanyaannya?')) return;
        let formData = new FormData();
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        fetch(baseUrl + '/domain/delete/' + id, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') document.getElementById('domain-' + id).remove();
        });
    }

    function openQuestionModal(domainId) {
        document.getElementById('modal_domain_id').value = domainId;
        new bootstrap.Modal(document.getElementById('questionModal')).show();
    }

    function saveQuestion(e) {
        e.preventDefault();
        let formData = new FormData(e.target);
        fetch(baseUrl + '/question/store', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            }
        });
    }

    function deleteQuestion(id) {
        if (!confirm('Hapus pertanyaan ini?')) return;
        let formData = new FormData();
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        fetch(baseUrl + '/question/delete/' + id, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') document.getElementById('question-' + id).remove();
        });
    }
</script>
<?= $this->endSection() ?>
