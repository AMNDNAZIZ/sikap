<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">📂 Modul Ajar (AI Generated)</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (in_array(session()->get('user')['role_id'], [1, 3])): ?>
<!-- API Key Configuration Modal -->
<div class="modal fade" id="apiKeyModal" tabindex="-1" aria-labelledby="apiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('admin/administrasi-guru/modul-ajar/saveApiKey') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="apiKeyModalLabel"><i class="bi bi-robot"></i> Konfigurasi API AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Pilih Provider AI:</label>
                        <select name="ai_provider" class="form-select mb-2" id="providerSelect">
                            <option value="gemini" <?= $ai_provider == 'gemini' ? 'selected' : '' ?>>Google Gemini (Flash)</option>
                            <option value="groq" <?= $ai_provider == 'groq' ? 'selected' : '' ?>>Groq (Llama 3.3 - Ultra Fast & Free)</option>
                        </select>
                        <div id="geminiInstructions" class="small text-muted p-2 bg-light rounded <?= $ai_provider != 'gemini' ? 'd-none' : '' ?>">
                            <p class="mb-1"><strong>Google Gemini:</strong> Gratis, namun sering terkena limit kuota di wilayah tertentu.</p>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-sm btn-link p-0">Dapatkan API Key Gemini <i class="bi bi-box-arrow-up-right"></i></a>
                        </div>
                        <div id="groqInstructions" class="small text-muted p-2 bg-light rounded <?= $ai_provider != 'groq' ? 'd-none' : '' ?>">
                            <p class="mb-1"><strong>Groq (Sangat Disarankan):</strong> Jauh lebih cepat dan jarang terkena limit kuota.</p>
                            <a href="https://console.groq.com/keys" target="_blank" class="btn btn-sm btn-link p-0 text-success">Dapatkan API Key Groq <i class="bi bi-box-arrow-up-right"></i></a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">API Key Anda:</label>
                        <input type="password" name="gemini_api_key" class="form-control" value="<?= esc($gemini_api_key) ?>" placeholder="Masukkan API Key sesuai provider..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/modul-ajar') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Kelas:</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()" <?= $auto_class ? 'disabled' : '' ?>>
                    <?php if (!$auto_class): ?><option value="">-- Pilih Kelas --</option><?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($auto_class): ?>
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Mata Pelajaran:</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($selected_class) ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (in_array(session()->get('user')['role_id'], [1, 3])): ?>
            <div class="col-md-2 text-end">
                <button type="button" class="btn <?= empty($gemini_api_key) ? 'btn-danger' : 'btn-outline-primary' ?>" data-bs-toggle="modal" data-bs-target="#apiKeyModal">
                    <i class="bi bi-key"></i> API Key
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($selected_subject && $selected_class): ?>
    <?php if ($subject_not_mapped): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Perhatian!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> belum di-mapping ke Mapel Master.
            <br>
            Silakan mapping mata pelajaran ini terlebih dahulu di menu <a href="<?= base_url('admin/subjects') ?>" class="alert-link">Mata Pelajaran</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <strong>Kesalahan Mapping!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> di-mapping ke Mapel Master untuk jenjang <strong><?= esc($old_jenjang) ?></strong>, 
            tetapi sekolah Anda adalah <strong><?= esc($current_jenjang) ?></strong>.
            <br>
            Silakan perbaiki mapping di menu <a href="<?= base_url('admin/subjects') ?>" class="alert-link">Mata Pelajaran</a> atau hubungi administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php else: ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Modul Ajar (Level Kelas)</h6>
        <span class="badge bg-success-subtle text-success border border-success">
            <i class="bi bi-arrow-repeat me-1"></i> Modul tidak terikat tahun ajaran — bisa digunakan kembali
        </span>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-info text-center">
                <tr>
                    <th width="50">No</th>
                    <th>Elemen CP</th>
                    <th>Lingkup Materi / Tujuan Pembelajaran</th>
                    <th width="100">Status Modul</th>
                    <th width="200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($atp_list)): ?>
                    <tr><td colspan="5" class="text-center">Belum ada Alur Tujuan Pembelajaran untuk filter ini.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($atp_list as $atp): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $no++ ?></td>
                            <td class="fw-bold text-success">
                                <?php foreach ($atp['elemen_list'] ?? [] as $el): ?>
                                    <div><?= esc($el['elemen'] ?? '-') ?></div>
                                <?php endforeach; ?>
                                <?php if (empty($atp['elemen_list'])): ?>-<?php endif; ?>
                            </td>
                            <td>
                                <strong><?= esc($atp['lingkup_materi']) ?></strong>
                                <?php if (!empty($atp['tps'])): ?>
                                    <ul class="mb-0 small text-muted mt-1 ps-3">
                                        <?php foreach ($atp['tps'] as $tp): ?>
                                            <li><?= esc($tp['kode_tp']) ?>: <?= esc($tp['deskripsi']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($atp['modul']): ?>
                                    <span class="badge bg-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Dibuat</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($atp['modul']): ?>
                                    <div class="btn-group w-100 mb-1">
                                        <a href="<?= base_url('admin/administrasi-guru/modul-ajar/edit/' . $atp['modul']['id'] . '/' . $selected_class) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?= base_url('admin/administrasi-guru/modul-ajar/print/' . $atp['modul']['id'] . '/' . $selected_class) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-printer"></i> Cetak
                                        </a>
                                    </div>
                                    <a href="<?= base_url('admin/administrasi-guru/modul-ajar/delete/' . $atp['modul']['id'] . '/' . $selected_subject . '/' . $selected_class) ?>" 
                                       class="btn btn-sm btn-light text-danger w-100 border" 
                                       onclick="return confirm('Hapus modul ini untuk generate ulang dari awal?')">
                                        <i class="bi bi-trash"></i> Hapus & Reginaerasi
                                    </a>
                                <?php else: ?>
                                    <?php if (in_array(session()->get('user')['role_id'], [1, 3]) && !$readonly): ?>
                                        <?php if (empty($gemini_api_key)): ?>
                                            <button class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#apiKeyModal">
                                                <i class="bi bi-robot"></i> Set API Key
                                            </button>
                                        <?php else: ?>
                                            <form action="<?= base_url('admin/administrasi-guru/modul-ajar/generate') ?>" method="post" class="generate-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="atp_id" value="<?= $atp['id'] ?>">
                                                <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                                                <button type="submit" class="btn btn-sm btn-primary w-100 generate-btn">
                                                    <i class="bi bi-magic"></i> Generate Modul (AI)
                                                </button>
                                            </form>
                                            <?php if (!empty($atp['modul_source'])): ?>
                                            <form action="<?= base_url('admin/administrasi-guru/modul-ajar/copy') ?>" method="post" class="mt-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="source_modul_id" value="<?= $atp['modul_source']['id'] ?>">
                                                <input type="hidden" name="target_class_id" value="<?= $selected_class ?>">
                                                <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                                                <input type="hidden" name="atp_id" value="<?= $atp['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success w-100"
                                                    onclick="return confirm('Salin modul dari <?= esc($atp['modul_source']['class_name']) ?>?')">
                                                    <i class="bi bi-copy"></i> Salin dari <?= esc($atp['modul_source']['class_name']) ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Hubungi Guru Mapel</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $('.generate-form').on('submit', function() {
        const btn = $(this).find('.generate-btn');
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <small>Menyusun Modul...</small>');
        btn.prop('disabled', true);
    });

    $('#providerSelect').on('change', function() {
        const provider = $(this).val();
        if (provider === 'groq') {
            $('#groqInstructions').removeClass('d-none');
            $('#geminiInstructions').addClass('d-none');
        } else {
            $('#geminiInstructions').removeClass('d-none');
            $('#groqInstructions').addClass('d-none');
        }
    });
</script>
<?= $this->endSection() ?>
