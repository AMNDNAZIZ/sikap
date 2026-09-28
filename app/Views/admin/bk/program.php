<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-calendar-range-fill text-success me-2"></i>Program &amp; RPL Bimbingan Konseling</h3>
            <p class="text-muted mb-0">Program Tahunan (Prota), Program Semester (Prosem), &amp; Rencana Pelaksanaan Layanan (RPL BK)</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBuatProgram">
                <i class="bi bi-plus-circle me-1"></i> Buat Program Baru
            </button>
            <button class="btn btn-outline-success rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBuatRpl">
                <i class="bi bi-file-earmark-plus me-1"></i> Buat RPL BK
            </button>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-pills mb-4 gap-2" id="programTab">
        <li class="nav-item">
            <button class="nav-link active rounded-pill px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tabProgram">
                <i class="bi bi-grid-1x2-fill me-1"></i> Program BK
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tabRpl">
                <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> RPL BK
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB: PROGRAM -->
        <div class="tab-pane fade show active" id="tabProgram">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if (empty($programs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2-x fs-1 d-block mb-3 text-secondary"></i>
                            <h5>Belum Ada Program BK</h5>
                            <p>Klik tombol <strong>Buat Program Baru</strong> untuk memulai.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Judul Program</th>
                                        <th>Jenis</th>
                                        <th>Bidang</th>
                                        <th>Bulan/Semester</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $i => $p): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td class="fw-bold"><?= esc($p['title']) ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary"><?= esc($p['program_type']) ?></span></td>
                                        <td><?= esc($p['field'] ?? '-') ?></td>
                                        <td><?= esc($p['period_month'] ?? '-') ?></td>
                                        <td>
                                            <?php $sc = $p['status'] === 'active' ? 'success' : 'secondary'; ?>
                                            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($p['status']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                                                onclick="editProgram(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form action="<?= base_url('admin/bk/program/delete/' . $p['id']) ?>" method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus program ini?')">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-trash-fill"></i></button>
                                            </form>
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

        <!-- TAB: RPL -->
        <div class="tab-pane fade" id="tabRpl">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if (empty($rpls)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                            <h5>Belum Ada RPL BK</h5>
                            <p>Klik tombol <strong>Buat RPL BK</strong> untuk memulai.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Topik RPL</th>
                                        <th>Jenis Layanan</th>
                                        <th>Bidang</th>
                                        <th>Sasaran Kelas</th>
                                        <th>Durasi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rpls as $i => $r): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td class="fw-bold text-dark"><?= esc($r['title']) ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?= esc($r['service_type']) ?></span></td>
                                        <td><span class="badge bg-primary-subtle text-primary"><?= esc($r['field']) ?></span></td>
                                        <td><?= esc($r['class_name'] ?: 'Semua Kelas') ?></td>
                                        <td><?= $r['duration_minutes'] ?> Menit</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3 me-1"
                                                onclick="viewRpl(<?= $r['id'] ?>)">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                                                onclick="editRpl(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form action="<?= base_url('admin/bk/rpl/delete/' . $r['id']) ?>" method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus RPL ini?')">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-trash-fill"></i></button>
                                            </form>
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
    </div>
</div>

<!-- ===== MODAL: BUAT PROGRAM ===== -->
<div class="modal fade" id="modalBuatProgram" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <form id="formProgram" action="<?= base_url('admin/bk/program/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i><span id="programModalTitle">Buat Program BK Baru</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="_method" id="programMethod" value="">
                    <input type="hidden" name="program_id_hidden" id="programIdHidden" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Judul Program <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="progTitle" required placeholder="Misal: Program Semester Ganjil 2025">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Program</label>
                            <select class="form-select" name="program_type" id="progType">
                                <option value="Tahunan">Tahunan (Prota)</option>
                                <option value="Semesteran">Semesteran (Prosem)</option>
                                <option value="Mingguan">Mingguan</option>
                                <option value="Harian">Harian</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bidang BK</label>
                            <select class="form-select" name="field" id="progField">
                                <option value="Pribadi">Pribadi</option>
                                <option value="Sosial">Sosial</option>
                                <option value="Belajar">Belajar</option>
                                <option value="Karir">Karir</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Periode / Bulan</label>
                            <input type="text" class="form-control" name="period_month" id="progPeriod" placeholder="Misal: Juli - Desember 2025">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sasaran Kelas</label>
                            <select class="form-select" name="target_class_id" id="progClass">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi / Tujuan</label>
                            <textarea class="form-control" name="description" id="progDesc" rows="3" placeholder="Uraikan tujuan dan sasaran program..."></textarea>
                        </div>
                        <div class="col-md-4" id="progStatusGroup" style="display:none;">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="progStatus">
                                <option value="draft">Draft</option>
                                <option value="active">Aktif</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Program
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL: BUAT RPL ===== -->
<div class="modal fade" id="modalBuatRpl" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <form id="formRpl" action="<?= base_url('admin/bk/rpl/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i><span id="rplModalTitle">Buat RPL BK Baru</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="rpl_id_hidden" id="rplIdHidden" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Topik / Judul RPL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="rplTitle" required placeholder="Misal: Mengenal Potensi Diri">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis Layanan</label>
                            <select class="form-select" name="service_type" id="rplServiceType">
                                <option value="Bimbingan Klasikal">Bimbingan Klasikal</option>
                                <option value="Bimbingan Kelompok">Bimbingan Kelompok</option>
                                <option value="Konseling Individual">Konseling Individual</option>
                                <option value="Konseling Kelompok">Konseling Kelompok</option>
                                <option value="Layanan Informasi">Layanan Informasi</option>
                                <option value="Layanan Orientasi">Layanan Orientasi</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Bidang BK</label>
                            <select class="form-select" name="field" id="rplField">
                                <option value="Pribadi">Pribadi</option>
                                <option value="Sosial">Sosial</option>
                                <option value="Belajar">Belajar</option>
                                <option value="Karir">Karir</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sasaran Kelas</label>
                            <select class="form-select" name="target_class_id" id="rplClass">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Durasi (Menit)</label>
                            <input type="number" class="form-control" name="duration_minutes" id="rplDuration" value="45" min="1">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Tautkan ke Program</label>
                            <select class="form-select" name="program_id" id="rplProgramId">
                                <option value="">-- Tidak Ditautkan --</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= esc($p['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tujuan Layanan</label>
                            <textarea class="form-control" name="purpose" id="rplPurpose" rows="3" placeholder="Setelah mengikuti layanan ini, siswa diharapkan mampu..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Media &amp; Alat</label>
                            <input type="text" class="form-control" name="media_tools" id="rplMedia" placeholder="Misal: Laptop, Proyektor, Lembar Kerja">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode</label>
                            <input type="text" class="form-control" name="methods" id="rplMethods" placeholder="Misal: Ceramah, Diskusi, Role Play">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Langkah Evaluasi</label>
                            <textarea class="form-control" name="evaluation_steps" id="rplEval" rows="2" placeholder="Uraikan cara mengevaluasi keberhasilan layanan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan RPL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL: VIEW RPL ===== -->
<div class="modal fade" id="modalViewRpl" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 bg-info text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Detail RPL BK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="rplDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-info"></div></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak RPL
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ---- PROGRAM FUNCTIONS ----
function editProgram(p) {
    document.getElementById('programModalTitle').innerText = 'Edit Program BK';
    document.getElementById('formProgram').action = '<?= base_url('admin/bk/program/update') ?>/' + p.id;
    document.getElementById('progTitle').value = p.title;
    document.getElementById('progType').value = p.program_type;
    document.getElementById('progField').value = p.field || 'Umum';
    document.getElementById('progPeriod').value = p.period_month || '';
    document.getElementById('progClass').value = p.target_class_id || '';
    document.getElementById('progDesc').value = p.description || '';
    document.getElementById('progStatus').value = p.status || 'draft';
    document.getElementById('progStatusGroup').style.display = 'block';
    new bootstrap.Modal(document.getElementById('modalBuatProgram')).show();
}

// Reset modal on close
document.getElementById('modalBuatProgram').addEventListener('hidden.bs.modal', function () {
    document.getElementById('programModalTitle').innerText = 'Buat Program BK Baru';
    document.getElementById('formProgram').action = '<?= base_url('admin/bk/program/store') ?>';
    document.getElementById('formProgram').reset();
    document.getElementById('progStatusGroup').style.display = 'none';
});

// ---- RPL FUNCTIONS ----
function editRpl(r) {
    document.getElementById('rplModalTitle').innerText = 'Edit RPL BK';
    document.getElementById('formRpl').action = '<?= base_url('admin/bk/rpl/update') ?>/' + r.id;
    document.getElementById('rplTitle').value = r.title;
    document.getElementById('rplServiceType').value = r.service_type;
    document.getElementById('rplField').value = r.field;
    document.getElementById('rplClass').value = r.target_class_id || '';
    document.getElementById('rplDuration').value = r.duration_minutes || 45;
    document.getElementById('rplProgramId').value = r.program_id || '';
    document.getElementById('rplPurpose').value = r.purpose || '';
    document.getElementById('rplMedia').value = r.media_tools || '';
    document.getElementById('rplMethods').value = r.methods || '';
    document.getElementById('rplEval').value = r.evaluation_steps || '';
    new bootstrap.Modal(document.getElementById('modalBuatRpl')).show();
}

document.getElementById('modalBuatRpl').addEventListener('hidden.bs.modal', function () {
    document.getElementById('rplModalTitle').innerText = 'Buat RPL BK Baru';
    document.getElementById('formRpl').action = '<?= base_url('admin/bk/rpl/store') ?>';
    document.getElementById('formRpl').reset();
});

function viewRpl(id) {
    document.getElementById('rplDetailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-info"></div></div>';
    new bootstrap.Modal(document.getElementById('modalViewRpl')).show();

    fetch('<?= base_url('admin/bk/rpl/detail') ?>/' + id)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const r = data.rpl;
                document.getElementById('rplDetailBody').innerHTML = `
                    <div class="row g-3">
                        <div class="col-12"><h4 class="fw-bold text-dark">${r.title}</h4></div>
                        <div class="col-md-4">
                            <p class="text-muted small mb-1">Jenis Layanan</p>
                            <span class="badge bg-success-subtle text-success fs-6">${r.service_type}</span>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted small mb-1">Bidang</p>
                            <span class="badge bg-primary-subtle text-primary fs-6">${r.field}</span>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted small mb-1">Durasi</p>
                            <strong>${r.duration_minutes} Menit</strong>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Sasaran Kelas</p>
                            <strong>${r.class_name || 'Semua Kelas'}</strong>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Program Induk</p>
                            <strong>${r.program_title || '-'}</strong>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                <h6 class="fw-bold">Tujuan Layanan</h6>
                                <p class="mb-0">${r.purpose || '-'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                <h6 class="fw-bold">Media & Alat</h6>
                                <p class="mb-0">${r.media_tools || '-'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                <h6 class="fw-bold">Metode</h6>
                                <p class="mb-0">${r.methods || '-'}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card bg-light border-0 rounded-3 p-3">
                                <h6 class="fw-bold">Langkah Evaluasi</h6>
                                <p class="mb-0">${r.evaluation_steps || '-'}</p>
                            </div>
                        </div>
                    </div>`;
            }
        });
}
</script>
<?= $this->endSection() ?>
