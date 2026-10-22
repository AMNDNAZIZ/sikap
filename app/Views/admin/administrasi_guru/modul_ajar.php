<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
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
                <input type="hidden" name="class_id" value="<?= esc($selected_class) ?>">
                <input type="hidden" name="subject_id" value="<?= esc($selected_subject) ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="apiKeyModalLabel"><i class="bi bi-robot"></i> Konfigurasi API AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Pilih Provider AI:</label>
                        <select name="ai_provider" class="form-select mb-2" id="providerSelect">
                            <option value="groq" <?= $ai_provider == 'groq' ? 'selected' : '' ?>>Groq (OpenAI GPT-OSS 120B / Qwen3.6 27B - Ultra Fast & Kuota Tinggi)</option>
                            <option value="gemini" <?= $ai_provider == 'gemini' ? 'selected' : '' ?>>Google Gemini (2.0 Flash / 1.5 Flash)</option>
                        </select>
                        <div id="groqInstructions" class="small text-muted p-2 bg-light rounded <?= $ai_provider != 'groq' ? 'd-none' : '' ?>">
                            <p class="mb-1"><strong>Groq (Sangat Disarankan):</strong> Didukung mesin OpenAI GPT-OSS 120B dan Qwen3.6 27B via Groq LPU, proses generate sangat cepat dan jarang limit.</p>
                            <a href="https://console.groq.com/keys" target="_blank" class="btn btn-sm btn-link p-0 text-success">Dapatkan API Key Groq <i class="bi bi-box-arrow-up-right"></i></a>
                        </div>
                        <div id="geminiInstructions" class="small text-muted p-2 bg-light rounded <?= $ai_provider != 'gemini' ? 'd-none' : '' ?>">
                            <p class="mb-1"><strong>Google Gemini:</strong> Gratis via Google AI Studio. Didukung model Gemini 2.0 Flash / 1.5 Flash.</p>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-sm btn-link p-0">Dapatkan API Key Gemini <i class="bi bi-box-arrow-up-right"></i></a>
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
            <div class="col-md-2 text-md-end text-start">
                <button type="button" class="btn <?= empty($gemini_api_key) ? 'btn-danger' : 'btn-outline-primary' ?> w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#apiKeyModal">
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
    <div class="card-header py-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Modul Ajar (Level Kelas)</h6>
        <span class="badge bg-success-subtle text-success border border-success">
            <i class="bi bi-arrow-repeat me-1"></i> Modul tidak terikat tahun ajaran — bisa digunakan kembali
        </span>
    </div>
    <div class="card-body">
        <?php if (!($is_multi_guru ?? true) && !empty($atp_list)): ?>
        <div class="alert alert-primary py-2 mb-3">
            <i class="bi bi-share-fill me-1"></i>
            <strong>Mode Guru Tunggal:</strong> Modul berlaku untuk semua kelas se-level yang Anda ampu. Tidak perlu membuat modul terpisah per kelas.
        </div>
        <?php endif; ?>
        <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-info text-center">
                <tr>
                    <th width="50">No</th>
                    <th>Elemen CP</th>
                    <th>Lingkup Materi / Tujuan Pembelajaran</th>
                    <th width="120">Status Modul</th>
                    <th width="220">Aksi</th>
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
                                    <?php if (($atp['modul']['tipe'] ?? 'editor') === 'pdf'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>Dokumen PDF
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success">
                                            <i class="bi bi-file-earmark-text me-1"></i>Tersedia (Teks)
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Dibuat</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($atp['modul']): ?>
                                    <?php $canManage = in_array(session()->get('user')['role_id'], [1, 3]) && !$readonly; ?>
                                    <?php if ($canManage): ?>
                                        <?php if (($atp['modul']['tipe'] ?? 'editor') === 'pdf'): ?>
                                            <div class="d-flex flex-column gap-1">
                                                <a href="<?= base_url('admin/administrasi-guru/modul-ajar/pdf/' . $atp['modul']['id']) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                    <i class="bi bi-eye"></i> Buka / Cetak PDF
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 upload-pdf-btn" data-atp-id="<?= $atp['id'] ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                    <i class="bi bi-arrow-repeat"></i> Ganti File PDF
                                                </button>
                                                <a href="<?= base_url('admin/administrasi-guru/modul-ajar/delete/' . $atp['modul']['id'] . '/' . $selected_subject . '/' . $selected_class) ?>" 
                                                   class="btn btn-sm btn-light text-danger w-100 border" 
                                                   onclick="return confirm('Hapus file PDF modul ini?')">
                                                    <i class="bi bi-trash"></i> Hapus Modul
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="btn-group w-100">
                                                    <a href="<?= base_url('admin/administrasi-guru/modul-ajar/edit/' . $atp['modul']['id'] . '/' . $selected_class) ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="<?= base_url('admin/administrasi-guru/modul-ajar/print/' . $atp['modul']['id'] . '/' . $selected_class) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-printer"></i> Cetak
                                                    </a>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 upload-pdf-btn" data-atp-id="<?= $atp['id'] ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                    <i class="bi bi-file-earmark-pdf"></i> Ganti dgn PDF
                                                </button>
                                                <a href="<?= base_url('admin/administrasi-guru/modul-ajar/delete/' . $atp['modul']['id'] . '/' . $selected_subject . '/' . $selected_class) ?>" 
                                                   class="btn btn-sm btn-light text-danger w-100 border" 
                                                   onclick="return confirm('Hapus modul ini?')">
                                                    <i class="bi bi-trash"></i> Hapus Modul
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Read-only (mis. Kepala Sekolah) -->
                                        <?php if (($atp['modul']['tipe'] ?? 'editor') === 'pdf'): ?>
                                            <a href="<?= base_url('admin/administrasi-guru/modul-ajar/pdf/' . $atp['modul']['id']) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="bi bi-eye"></i> Lihat PDF
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/administrasi-guru/modul-ajar/print/' . $atp['modul']['id'] . '/' . $selected_class) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="bi bi-printer"></i> Cetak
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (in_array(session()->get('user')['role_id'], [1, 3]) && !$readonly): ?>
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Pilihan 1: Generate AI -->
                                            <?php if (!empty($gemini_api_key)): ?>
                                                <form action="<?= base_url('admin/administrasi-guru/modul-ajar/generate') ?>" method="post" class="generate-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="atp_id" value="<?= $atp['id'] ?>">
                                                    <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                                                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary w-100 generate-btn">
                                                        <i class="bi bi-magic"></i> Generate AI
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 get-prompt-btn" data-atp-id="<?= $atp['id'] ?>" data-subject-id="<?= $selected_subject ?>">
                                                    <i class="bi bi-clipboard"></i> Salin Prompt AI
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100 get-prompt-btn" data-atp-id="<?= $atp['id'] ?>" data-subject-id="<?= $selected_subject ?>">
                                                    <i class="bi bi-clipboard"></i> Salin Prompt AI
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#apiKeyModal">
                                                    <i class="bi bi-robot"></i> Set API Key AI
                                                </button>
                                            <?php endif; ?>

                                            <!-- Pilihan 2: Buat Mandiri / Copas Word -->
                                            <a href="<?= base_url('admin/administrasi-guru/modul-ajar/create/' . $atp['id'] . '/' . $selected_class) ?>" class="btn btn-sm btn-outline-success w-100">
                                                <i class="bi bi-file-earmark-richtext"></i> Copas Word / Mandiri
                                            </a>

                                            <!-- Pilihan 3: Upload Dokumen PDF -->
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 upload-pdf-btn" data-atp-id="<?= $atp['id'] ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                <i class="bi bi-file-earmark-arrow-up"></i> Upload PDF
                                            </button>

                                            <!-- Pilihan 4: Salin dari kelas lain se-level jika ada -->
                                            <?php if (!empty($atp['modul_source'])): ?>
                                            <form action="<?= base_url('admin/administrasi-guru/modul-ajar/copy') ?>" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="source_modul_id" value="<?= $atp['modul_source']['id'] ?>">
                                                <input type="hidden" name="target_class_id" value="<?= $selected_class ?>">
                                                <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                                                <input type="hidden" name="atp_id" value="<?= $atp['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-info w-100"
                                                    onclick="return confirm('Salin modul dari <?= esc($atp['modul_source']['class_name']) ?>?')">
                                                    <i class="bi bi-copy"></i> Salin dari <?= esc($atp['modul_source']['class_name']) ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
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
</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal: Generate Manual (Copy Prompt → Paste Result) -->
<div class="modal fade" id="manualPromptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-clipboard me-2"></i>Generate Modul Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Prompt -->
                <div id="promptStep">
                    <div class="alert alert-info py-2">
                        <strong>Langkah 1:</strong> Copy prompt di bawah, lalu paste ke ChatGPT / Gemini / AI lainnya langsung di browser.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Prompt (klik untuk copy):</label>
                        <textarea id="promptText" class="form-control" rows="8" readonly style="font-size:0.8rem; background:#f8f9fa;"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" id="copyPromptBtn">
                            <i class="bi bi-clipboard-check me-1"></i>Copy Prompt
                        </button>
                        <button type="button" class="btn btn-primary" id="goToPasteStep">
                            <i class="bi bi-arrow-right me-1"></i>Selanjutnya: Paste Hasil AI
                        </button>
                    </div>
                </div>

                <!-- Step 2: Paste Result -->
                <div id="pasteStep" style="display:none;">
                    <div class="alert alert-success py-2">
                        <strong>Langkah 2:</strong> Paste hasil dari AI ke kotak di bawah, lalu klik Simpan.
                    </div>
                    <form action="<?= base_url('admin/administrasi-guru/modul-ajar/save-manual') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="atp_id" id="manualAtpId">
                        <input type="hidden" name="subject_id" id="manualSubjectId">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hasil dari AI (HTML):</label>
                            <textarea name="modul_content" id="pasteArea" class="form-control" rows="12" required placeholder="Paste hasil HTML dari AI di sini..."></textarea>
                            <div class="form-text small text-muted mt-1"><i class="bi bi-magic text-primary me-1"></i>Sistem otomatis membersihkan tanda backslash (\), rumus LaTeX, dan format berlebih.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="backToPrompt">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Simpan Modul
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Upload Dokumen PDF Modul Ajar -->
<div class="modal fade" id="uploadPdfModal" tabindex="-1" aria-labelledby="uploadPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('admin/administrasi-guru/modul-ajar/upload-pdf') ?>" method="post" enctype="multipart/form-data" id="uploadPdfForm">
                <?= csrf_field() ?>
                <input type="hidden" name="atp_id" id="uploadAtpId">
                <input type="hidden" name="class_id" value="<?= esc($selected_class) ?>">
                <input type="hidden" name="subject_id" value="<?= esc($selected_subject) ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="uploadPdfModalLabel"><i class="bi bi-file-earmark-pdf me-2"></i>Upload File PDF Modul Ajar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Gunakan opsi ini jika Anda sudah memiliki dokumen Modul Ajar dalam bentuk PDF (hasil ekspor dari Microsoft Word, Canva, atau Kemdikbud).
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Lingkup Materi / ATP:</label>
                        <div id="uploadMateriDisplay" class="fw-bold text-dark p-2 bg-light rounded border"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Dokumen PDF: <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf" required id="pdfFileInput">
                        <div class="form-text small">Format wajib <strong>.pdf</strong>, ukuran maksimal <strong>15 MB</strong>.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="uploadPdfSubmitBtn">
                        <i class="bi bi-upload me-1"></i> Unggah PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    // Manual prompt flow
    $('.get-prompt-btn').on('click', function() {
        const atpId = $(this).data('atp-id');
        const subjectId = $(this).data('subject-id');
        const btn = $(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/get-prompt') ?>', {
            atp_id: atpId,
            subject_id: subjectId,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        }, function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-clipboard"></i> Salin Prompt AI');
            if (res.error) {
                alert(res.error);
                return;
            }
            $('#promptText').val(res.prompt);
            $('#manualAtpId').val(atpId);
            $('#manualSubjectId').val(subjectId);
            $('#promptStep').show();
            $('#pasteStep').hide();
            new bootstrap.Modal(document.getElementById('manualPromptModal')).show();
        }).fail(function() {
            btn.prop('disabled', false).html('<i class="bi bi-clipboard"></i> Salin Prompt AI');
            alert('Gagal mengambil prompt.');
        });
    });

    $('#copyPromptBtn').on('click', function() {
        const textarea = document.getElementById('promptText');
        textarea.select();
        document.execCommand('copy');
        $(this).html('<i class="bi bi-check me-1"></i>Tersalin!').addClass('btn-dark').removeClass('btn-success');
        setTimeout(() => {
            $(this).html('<i class="bi bi-clipboard-check me-1"></i>Copy Prompt').addClass('btn-success').removeClass('btn-dark');
        }, 2000);
    });

    $('#goToPasteStep').on('click', function() {
        $('#promptStep').hide();
        $('#pasteStep').show();
    });

    $('#backToPrompt').on('click', function() {
        $('#pasteStep').hide();
        $('#promptStep').show();
    });

    function cleanModulInput(val) {
        if (!val) return val;
        val = val.replace(/\\div\b/g, '÷')
                 .replace(/\\times\b/g, '×')
                 .replace(/\\pm\b/g, '±')
                 .replace(/\\(?:le|leq)\b/g, '≤')
                 .replace(/\\(?:ge|geq)\b/g, '≥')
                 .replace(/\\cdot\b/g, '·')
                 .replace(/\\approx\b/g, '≈')
                 .replace(/\\(?:neq|ne)\b/g, '≠')
                 .replace(/\\infty\b/g, '∞')
                 .replace(/\\degree\b/g, '°')
                 .replace(/\^\\(?:circ)\b/g, '°');

        val = val.replace(/\\\(/g, '(')
                 .replace(/\\\)/g, ')')
                 .replace(/\\\[/g, '[')
                 .replace(/\\\]/g, ']')
                 .replace(/\\\{/g, '{')
                 .replace(/\\\}/g, '}')
                 .replace(/\\%/g, '%');

        val = val.replace(/<(p|div|span|h[1-6]|li)[^>]*>\s*(?:\\[\s\\]*)+\s*<\/\1>/gi, '');
        val = val.replace(/^[ \t\r\n]*(?:\\[ \t\\]*)+[ \t\r\n]*$/gm, '');
        val = val.replace(/(^|[\s>])\\+([a-zA-Z0-9]+)\./gm, '$1$2.');
        val = val.replace(/\\+([.,:;!?%])/g, '$1');
        val = val.replace(/([.,:;!?%])\\+/g, '$1');
        val = val.replace(/\\+\s*(?=<\/(?:p|h[1-6]|td|th|li|div|span|strong|b|em|i)>|$|\r|\n)/gi, '');

        val = val.split(/(<[^>]+>)/).map(function(part) {
            return (part && part[0] !== '<') ? part.replace(/\\+/g, '') : part;
        }).join('');

        return val.trim();
    }

    $('#pasteArea').on('paste', function() {
        setTimeout(function() {
            var el = $('#pasteArea');
            el.val(cleanModulInput(el.val()));
        }, 50);
    });

    $('#pasteStep form').on('submit', function() {
        var el = $('#pasteArea');
        el.val(cleanModulInput(el.val()));
    });

    // Upload PDF modal trigger
    $('.upload-pdf-btn').on('click', function() {
        const atpId = $(this).data('atp-id');
        const materi = $(this).data('materi');
        $('#uploadAtpId').val(atpId);
        $('#uploadMateriDisplay').text(materi || '-');
        $('#pdfFileInput').val('');
        new bootstrap.Modal(document.getElementById('uploadPdfModal')).show();
    });

    $('#uploadPdfForm').on('submit', function() {
        const fileInput = document.getElementById('pdfFileInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Silakan pilih file PDF terlebih dahulu.');
            return false;
        }
        const file = fileInput.files[0];
        if (file.size > 15 * 1024 * 1024) {
            alert('Ukuran file PDF melebihi 15 MB.');
            return false;
        }
        const btn = $('#uploadPdfSubmitBtn');
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...');
        btn.prop('disabled', true);
    });
</script>
<?= $this->endSection() ?>
