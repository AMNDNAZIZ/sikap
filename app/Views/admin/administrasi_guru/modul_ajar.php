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
    <?php 
    $userRoleId = (int)(session()->get('user')['role_id'] ?? 0);
    $canManageMapping = in_array($userRoleId, [1, 2]);
    ?>
    <?php if ($subject_not_mapped): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Perhatian!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> belum di-mapping ke Mapel Master.
            <br>
            <?php if ($canManageMapping): ?>
                Silakan mapping mata pelajaran ini terlebih dahulu di menu <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="alert-link">Mapping Kurikulum</a>.
            <?php else: ?>
                Silakan hubungi Administrator atau Bagian Kurikulum untuk melakukan pemetaan mata pelajaran ini ke Mapel Master (CP).
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <strong>Kesalahan Mapping!</strong> Mata pelajaran <strong><?= esc($subject_name) ?></strong> di-mapping ke Mapel Master untuk jenjang <strong><?= esc($old_jenjang) ?></strong>, 
            tetapi sekolah Anda adalah <strong><?= esc($current_jenjang) ?></strong>.
            <br>
            <?php if ($canManageMapping): ?>
                Silakan perbaiki mapping di menu <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="alert-link">Mapping Kurikulum</a>.
            <?php else: ?>
                Silakan hubungi Administrator atau Bagian Kurikulum untuk memperbarui pemetaan kurikulum mata pelajaran ini sesuai jenjang sekolah.
            <?php endif; ?>
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
                                                <form action="<?= base_url('admin/administrasi-guru/modul-ajar/delete/' . $atp['modul']['id'] . '/' . $selected_subject . '/' . $selected_class) ?>" method="post" class="w-100" onsubmit="return confirm('Hapus file PDF modul ini?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-light text-danger w-100 border">
                                                        <i class="bi bi-trash"></i> Hapus Modul
                                                    </button>
                                                </form>
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
                                                <button type="button" class="btn btn-sm btn-outline-success w-100 btn-manage-lkpd" data-modul-id="<?= $atp['modul']['id'] ?>" data-class-id="<?= $selected_class ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                    <i class="bi bi-journal-check"></i> Kelola LKPD
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 upload-pdf-btn" data-atp-id="<?= $atp['id'] ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                    <i class="bi bi-file-earmark-pdf"></i> Ganti dgn PDF
                                                </button>
                                                <form action="<?= base_url('admin/administrasi-guru/modul-ajar/delete/' . $atp['modul']['id'] . '/' . $selected_subject . '/' . $selected_class) ?>" method="post" class="w-100" onsubmit="return confirm('Hapus modul ini?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-light text-danger w-100 border">
                                                        <i class="bi bi-trash"></i> Hapus Modul
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Read-only (mis. Kepala Sekolah) -->
                                        <?php if (($atp['modul']['tipe'] ?? 'editor') === 'pdf'): ?>
                                            <a href="<?= base_url('admin/administrasi-guru/modul-ajar/pdf/' . $atp['modul']['id']) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="bi bi-eye"></i> Lihat PDF
                                            </a>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-1">
                                                <a href="<?= base_url('admin/administrasi-guru/modul-ajar/print/' . $atp['modul']['id'] . '/' . $selected_class) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                    <i class="bi bi-printer"></i> Cetak
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success w-100 btn-manage-lkpd" data-modul-id="<?= $atp['modul']['id'] ?>" data-class-id="<?= $selected_class ?>" data-materi="<?= esc($atp['lingkup_materi']) ?>">
                                                    <i class="bi bi-journal-check"></i> Lihat LKPD
                                                </button>
                                            </div>
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
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 get-prompt-btn" data-atp-id="<?= $atp['id'] ?>" data-subject-id="<?= $selected_subject ?>" data-class-id="<?= $selected_class ?>">
                                                    <i class="bi bi-clipboard"></i> Salin Prompt AI
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100 get-prompt-btn" data-atp-id="<?= $atp['id'] ?>" data-subject-id="<?= $selected_subject ?>" data-class-id="<?= $selected_class ?>">
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

<!-- Modal: Kelola LKPD Per Pertemuan -->
<div class="modal fade" id="lkpdModal" tabindex="-1" aria-labelledby="lkpdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="lkpdModalLabel">
                    <i class="bi bi-journal-check me-2"></i>Kelola LKPD (Lembar Kerja Peserta Didik)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                    <div class="small">
                        <strong>Hemat Token & Fleksibel:</strong> LKPD digenerate secara terpisah per pertemuan. Anda berhak menentukan pertemuan mana yang memerlukan LKPD dan LKPD dilengkapi stimulus visual berupa ilustrasi/diagram vektor (SVG).
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-muted small">Lingkup Materi:</span>
                    <h6 id="lkpdModalMateri" class="fw-bold text-dark mb-0"></h6>
                </div>

                <div id="lkpdLoadingState" class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Memuat data...</span>
                    </div>
                    <div class="small text-muted mt-2">Menganalisis pertemuan pada Modul Ajar...</div>
                </div>

                <div id="lkpdTableContainer" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="70" class="text-center">Pertemuan</th>
                                    <th>Topik / Fokus Aktivitas</th>
                                    <th width="120" class="text-center">Status LKPD</th>
                                    <th width="200" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="lkpdPertemuanRows">
                                <!-- Baris pertemuan diisi via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Manual / Ketik Mandiri LKPD -->
<div class="modal fade" id="lkpdEditModal" tabindex="-1" aria-labelledby="lkpdEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="lkpdEditModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit / Input Mandiri Konten LKPD
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="lkpdEditForm">
                <?= csrf_field() ?>
                <input type="hidden" name="modul_id" id="editLkpdModulId">
                <input type="hidden" name="pertemuan" id="editLkpdPertemuan">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul / Topik LKPD:</label>
                        <input type="text" name="title" id="editLkpdTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konten Lembar Kerja (HTML & Diagram Vektor SVG):</label>
                        <textarea name="content" id="editLkpdContent" class="form-control font-monospace" rows="16" style="font-size:0.85rem;" required></textarea>
                        <div class="form-text small text-muted">
                            Mendukung elemen HTML tabel, diagram visual inline SVG (<code>&lt;svg ...&gt;</code>), ruang jawaban bergaris, dan tata letak lembar kerja siswa.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveLkpdBtn">
                        <i class="bi bi-save me-1"></i> Simpan LKPD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Salin Prompt AI & Generate Manual LKPD -->
<div class="modal fade" id="manualLkpdPromptModal" tabindex="-1" aria-labelledby="manualLkpdPromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manualLkpdPromptModalLabel">
                    <i class="bi bi-clipboard-check me-2"></i>Generate Manual LKPD via Browser (Hemat Token)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Salin Prompt -->
                <div id="lkpdPromptStep">
                    <div class="alert alert-info py-2 small">
                        <strong>Langkah 1:</strong> Salin prompt di bawah, lalu buka <strong>ChatGPT / Gemini / Claude</strong> di tab browser baru dan tempelkan (*paste*).
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Prompt LKPD:</label>
                            <span class="badge bg-secondary" id="lkpdPromptPertemuanBadge">Pertemuan 1</span>
                        </div>
                        <textarea id="lkpdPromptText" class="form-control font-monospace" rows="9" readonly style="font-size:0.8rem; background:#f8f9fa;"></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-success" id="btnCopyLkpdPrompt">
                            <i class="bi bi-clipboard me-1"></i>Salin Prompt
                        </button>
                        <button type="button" class="btn btn-primary" id="btnGoToLkpdPaste">
                            Lanjut: Paste Hasil AI <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Tempel Hasil AI -->
                <div id="lkpdPasteStep" style="display:none;">
                    <div class="alert alert-success py-2 small">
                        <strong>Langkah 2:</strong> Tempel (*paste*) hasil jawaban dari ChatGPT / Gemini ke kotak di bawah, lalu klik Simpan.
                    </div>
                    <form id="lkpdManualSaveForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="modul_id" id="manualLkpdModulId">
                        <input type="hidden" name="pertemuan" id="manualLkpdPertemuan">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul / Topik LKPD:</label>
                            <input type="text" name="title" id="manualLkpdTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Paste Hasil AI (HTML & SVG):</label>
                            <textarea name="content" id="manualLkpdPasteArea" class="form-control font-monospace" rows="12" style="font-size:0.85rem;" placeholder="Tempel hasil AI di sini..." required></textarea>
                            <div class="form-text small text-muted">Format Math/LaTeX yang berlebih akan dibersihkan secara otomatis.</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" id="btnBackToLkpdPrompt">
                                <i class="bi bi-arrow-left me-1"></i>Kembali ke Prompt
                            </button>
                            <button type="submit" class="btn btn-success" id="btnSaveManualLkpd">
                                <i class="bi bi-check-circle me-1"></i>Simpan LKPD
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Upload / Import File Dokumen PDF LKPD -->
<div class="modal fade" id="uploadLkpdPdfModal" tabindex="-1" aria-labelledby="uploadLkpdPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="uploadLkpdPdfModalLabel">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Upload File PDF LKPD
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadLkpdPdfForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="modul_id" id="uploadLkpdModulId">
                <input type="hidden" name="pertemuan" id="uploadLkpdPertemuan">
                <div class="modal-body">
                    <div class="mb-2">
                        <span class="text-muted small">Target Pertemuan:</span>
                        <h6 id="uploadLkpdPertemuanLabel" class="fw-bold text-dark mb-0"></h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul LKPD (Opsional):</label>
                        <input type="text" name="title" id="uploadLkpdTitle" class="form-control form-control-sm" placeholder="Contoh: LKPD Pertemuan 1 - Pancasila">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Dokumen PDF: <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf" required id="lkpdPdfFileInput">
                        <div class="form-text small">Format wajib <strong>.pdf</strong>, ukuran maksimal <strong>15 MB</strong>. Dokumen ini dapat langsung dicetak atau dibagikan ke siswa.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="btnUploadLkpdPdfSubmit">
                        <i class="bi bi-upload me-1"></i> Unggah Dokumen PDF
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
        const classId = $(this).data('class-id');
        const btn = $(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/get-prompt') ?>', {
            atp_id: atpId,
            subject_id: subjectId,
            class_id: classId,
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

    // ==========================================
    // MANAJEMEN LKPD PER PERTEMUAN (INTERAKTIF)
    // ==========================================
    let currentLkpdModulId = null;
    let currentLkpdClassId = null;
    let currentLkpdDataCache = {};

    $('.btn-manage-lkpd').on('click', function() {
        currentLkpdModulId = $(this).data('modul-id');
        currentLkpdClassId = $(this).data('class-id');
        const materi = $(this).data('materi');

        $('#lkpdModalMateri').text(materi || '-');
        $('#lkpdLoadingState').show();
        $('#lkpdTableContainer').hide();
        new bootstrap.Modal(document.getElementById('lkpdModal')).show();

        loadLkpdList(currentLkpdModulId, currentLkpdClassId);
    });

    function loadLkpdList(modulId, classId) {
        $('#lkpdLoadingState').show();
        $('#lkpdTableContainer').hide();

        $.get(`<?= base_url('admin/administrasi-guru/modul-ajar/lkpd') ?>/${modulId}/${classId}`)
            .done(function(res) {
                $('#lkpdLoadingState').hide();
                $('#lkpdTableContainer').show();

                if (!res.success) {
                    $('#lkpdPertemuanRows').html(`<tr><td colspan="4" class="text-center text-danger py-3">${res.message || 'Gagal memuat data'}</td></tr>`);
                    return;
                }

                currentLkpdDataCache = res.lkpd_data || {};
                const list = res.pertemuan_list || res.meetings || [];
                const canManage = (res.can_manage !== undefined) ? res.can_manage : true;

                if (list.length === 0) {
                    $('#lkpdPertemuanRows').html(`<tr><td colspan="4" class="text-center text-muted py-3">Tidak ditemukan penanda struktur pertemuan pada Modul Ajar ini.</td></tr>`);
                    return;
                }

                let html = '';
                list.forEach(function(item) {
                    const num = item.pertemuan;
                    const title = item.title || item.label || `Pertemuan ${num}`;
                    const hasLkpd = item.has_lkpd;
                    const tipe = item.tipe || (item.file_pdf ? 'pdf' : 'editor');
                    const lkpdItem = item.lkpd_item || currentLkpdDataCache[num] || {};

                    let statusBadge = '';
                    if (hasLkpd) {
                        if (tipe === 'pdf') {
                            statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Dokumen PDF</span>`;
                        } else {
                            statusBadge = `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-file-earmark-text me-1"></i>Tersedia (Teks)</span>`;
                        }
                    } else {
                        statusBadge = `<span class="badge bg-secondary">Belum Dibuat</span>`;
                    }

                    let actionBtns = '';
                    const printUrl = `<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/print') ?>/${modulId}/${classId}/${num}`;
                    const pdfUrl = `<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/pdf') ?>/${modulId}/${num}`;

                    if (canManage) {
                        if (hasLkpd) {
                            if (tipe === 'pdf') {
                                actionBtns = `
                                    <div class="d-flex flex-column gap-1">
                                        <a href="${pdfUrl}" target="_blank" class="btn btn-sm btn-outline-danger w-100" title="Buka / Cetak File PDF">
                                            <i class="bi bi-eye me-1"></i> Buka PDF
                                        </a>
                                        <div class="btn-group btn-group-sm w-100">
                                            <button type="button" class="btn btn-outline-secondary btn-upload-lkpd-trigger" data-pertemuan="${num}" data-title="${encodeURIComponent(lkpdItem.title || title)}" title="Ganti File PDF">
                                                <i class="bi bi-arrow-repeat"></i> Ganti PDF
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-lkpd-action" data-pertemuan="${num}" title="Hapus LKPD">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            } else {
                                actionBtns = `
                                    <div class="d-flex flex-column gap-1">
                                        <div class="btn-group btn-group-sm w-100">
                                            <a href="${printUrl}" target="_blank" class="btn btn-outline-danger" title="Cetak Dokumen LKPD A4">
                                                <i class="bi bi-printer"></i> Cetak
                                            </a>
                                            <button type="button" class="btn btn-outline-primary btn-edit-lkpd-trigger" data-pertemuan="${num}" data-title="${encodeURIComponent(lkpdItem.title || title)}" title="Edit Teks / Format LKPD">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </div>
                                        <div class="dropdown w-100">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 py-0" type="button" data-bs-toggle="dropdown" style="font-size:0.78rem;">
                                                Opsi Lain
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item btn-generate-lkpd-action" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-arrow-repeat text-primary me-2"></i>Generate Ulang (AI)</a></li>
                                                <li><a class="dropdown-item btn-prompt-lkpd-trigger" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-clipboard text-info me-2"></i>Salin Prompt AI (Manual)</a></li>
                                                <li><a class="dropdown-item btn-upload-lkpd-trigger" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-file-earmark-arrow-up text-danger me-2"></i>Ganti dengan File PDF</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger btn-delete-lkpd-action" href="javascript:void(0)" data-pertemuan="${num}"><i class="bi bi-trash me-2"></i>Hapus LKPD</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                `;
                            }
                        } else {
                            actionBtns = `
                                <div class="dropdown w-100">
                                    <button class="btn btn-sm btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-plus-circle me-1"></i> Buat LKPD
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item btn-generate-lkpd-action" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-magic text-primary me-2"></i>Generate AI (Otomatis)</a></li>
                                        <li><a class="dropdown-item btn-prompt-lkpd-trigger" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-clipboard text-info me-2"></i>Salin Prompt AI (Manual Browser)</a></li>
                                        <li><a class="dropdown-item btn-edit-lkpd-trigger" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}" data-is-new="1"><i class="bi bi-pencil-square text-success me-2"></i>Copas Word / Ketik Mandiri</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item btn-upload-lkpd-trigger" href="javascript:void(0)" data-pertemuan="${num}" data-title="${encodeURIComponent(title)}"><i class="bi bi-file-earmark-arrow-up text-danger me-2"></i>Upload File Dokumen PDF</a></li>
                                    </ul>
                                </div>
                            `;
                        }
                    } else {
                        if (hasLkpd) {
                            if (tipe === 'pdf') {
                                actionBtns = `
                                    <a href="${pdfUrl}" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                        <i class="bi bi-eye me-1"></i> Lihat PDF
                                    </a>
                                `;
                            } else {
                                actionBtns = `
                                    <a href="${printUrl}" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                        <i class="bi bi-printer me-1"></i> Cetak LKPD
                                    </a>
                                `;
                            }
                        } else {
                            actionBtns = `<span class="text-muted small">Belum Tersedia</span>`;
                        }
                    }

                    html += `
                        <tr>
                            <td class="text-center fw-bold bg-light">${num}</td>
                            <td>
                                <div class="fw-semibold text-dark">${title}</div>
                                ${hasLkpd && lkpdItem.updated_at ? `<small class="text-muted"><i class="bi bi-clock me-1"></i>Diperbarui: ${lkpdItem.updated_at}</small>` : ''}
                            </td>
                            <td class="text-center">${statusBadge}</td>
                            <td class="text-center">${actionBtns}</td>
                        </tr>
                    `;
                });

                $('#lkpdPertemuanRows').html(html);
            })
            .fail(function() {
                $('#lkpdLoadingState').hide();
                $('#lkpdTableContainer').show();
                $('#lkpdPertemuanRows').html(`<tr><td colspan="4" class="text-center text-danger py-3">Terjadi kesalahan saat memuat daftar pertemuan.</td></tr>`);
            });
    }

    // Aksi Generate AI LKPD per pertemuan
    $(document).on('click', '.btn-generate-lkpd-action', function() {
        const pertemuan = $(this).data('pertemuan');
        const title = decodeURIComponent($(this).data('title') || `Pertemuan ${pertemuan}`);
        const btn = $(this);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Generate LKPD Pertemuan ${pertemuan}?`,
                html: `AI akan menyusun Lembar Kerja Peserta Didik interaktif dengan stimulus visual diagram/vektor (SVG) berdasarkan topik: <strong>${title}</strong>.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Generate Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    processGenerateLkpd(pertemuan, title, btn);
                }
            });
        } else {
            if (confirm(`Generate LKPD Pertemuan ${pertemuan} via AI?`)) {
                processGenerateLkpd(pertemuan, title, btn);
            }
        }
    });

    function processGenerateLkpd(pertemuan, title, btn) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyusun LKPD...',
                html: 'Asis AI sedang menyusun materi interaktif dan stimulus diagram visual vektor (SVG)... Mohon tunggu.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        } else {
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyusun...');
        }

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/generate') ?>', {
            <?= csrf_token() ?>: '<?= csrf_hash() ?>',
            modul_id: currentLkpdModulId,
            class_id: currentLkpdClassId,
            pertemuan: pertemuan,
            title: title
        }).done(function(res) {
            if (res.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message || 'LKPD berhasil dibuat.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                loadLkpdList(currentLkpdModulId, currentLkpdClassId);
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal Generate', res.message || 'Terjadi kesalahan pada AI.', 'error');
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan.'));
                }
                loadLkpdList(currentLkpdModulId, currentLkpdClassId);
            }
        }).fail(function(xhr) {
            let errMsg = 'Gagal menghubungi server.';
            try {
                const json = JSON.parse(xhr.responseText);
                if (json.message) errMsg = json.message;
            } catch(e) {}

            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', errMsg, 'error');
            } else {
                alert('Error: ' + errMsg);
            }
            loadLkpdList(currentLkpdModulId, currentLkpdClassId);
        });
    }

    // Aksi Edit / Ketik Mandiri LKPD
    $(document).on('click', '.btn-edit-lkpd-trigger', function() {
        const pertemuan = $(this).data('pertemuan');
        const isNew = $(this).data('is-new') === 1 || $(this).data('is-new') === '1';
        const lkpdItem = (!isNew && currentLkpdDataCache[pertemuan]) ? currentLkpdDataCache[pertemuan] : {};

        $('#editLkpdModulId').val(currentLkpdModulId);
        $('#editLkpdPertemuan').val(pertemuan);
        $('#editLkpdTitle').val(lkpdItem.title || decodeURIComponent($(this).data('title') || `Pertemuan ${pertemuan}`));
        $('#editLkpdContent').val(lkpdItem.content || '');

        new bootstrap.Modal(document.getElementById('lkpdEditModal')).show();
    });

    $('#lkpdEditForm').on('submit', function(e) {
        e.preventDefault();
        var el = $('#editLkpdContent');
        el.val(cleanLkpdInput(el.val()));

        const btn = $('#saveLkpdBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/save') ?>', $(this).serialize())
            .done(function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan LKPD');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('lkpdEditModal')).hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: res.message || 'Konten LKPD berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    loadLkpdList(currentLkpdModulId, currentLkpdClassId);
                } else {
                    alert(res.message || 'Gagal menyimpan.');
                }
            })
            .fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan LKPD');
                alert('Gagal menghubungi server saat menyimpan LKPD.');
            });
    });

    // ========================================================
    // ALUR GENERATE MANUAL VIA BROWSER (COPY PROMPT → PASTE)
    // ========================================================
    $(document).on('click', '.btn-prompt-lkpd-trigger', function() {
        const pertemuan = $(this).data('pertemuan');
        const title = decodeURIComponent($(this).data('title') || `Pertemuan ${pertemuan}`);
        const btn = $(this);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyiapkan Prompt LKPD...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/prompt') ?>', {
            <?= csrf_token() ?>: '<?= csrf_hash() ?>',
            modul_id: currentLkpdModulId,
            class_id: currentLkpdClassId,
            pertemuan: pertemuan,
            title: title
        }).done(function(res) {
            if (typeof Swal !== 'undefined') Swal.close();

            if (res.error || !res.success) {
                alert(res.error || 'Gagal menyiapkan prompt.');
                return;
            }

            $('#lkpdPromptText').val(res.prompt);
            $('#lkpdPromptPertemuanBadge').text(`Pertemuan ${res.pertemuan}`);
            $('#manualLkpdModulId').val(currentLkpdModulId);
            $('#manualLkpdPertemuan').val(res.pertemuan);
            $('#manualLkpdTitle').val(res.title || title);
            $('#manualLkpdPasteArea').val('');

            $('#lkpdPromptStep').show();
            $('#lkpdPasteStep').hide();
            new bootstrap.Modal(document.getElementById('manualLkpdPromptModal')).show();
        }).fail(function() {
            if (typeof Swal !== 'undefined') Swal.close();
            alert('Gagal mengambil prompt LKPD dari server.');
        });
    });

    $('#btnCopyLkpdPrompt').on('click', function() {
        const textarea = document.getElementById('lkpdPromptText');
        textarea.select();
        document.execCommand('copy');
        $(this).html('<i class="bi bi-check me-1"></i>Tersalin!').addClass('btn-dark').removeClass('btn-success');
        setTimeout(() => {
            $(this).html('<i class="bi bi-clipboard me-1"></i>Salin Prompt').addClass('btn-success').removeClass('btn-dark');
        }, 2000);
    });

    $('#btnGoToLkpdPaste').on('click', function() {
        $('#lkpdPromptStep').hide();
        $('#lkpdPasteStep').show();
        setTimeout(() => $('#manualLkpdPasteArea').focus(), 150);
    });

    $('#btnBackToLkpdPrompt').on('click', function() {
        $('#lkpdPasteStep').hide();
        $('#lkpdPromptStep').show();
    });

    function cleanLkpdInput(val) {
        if (!val) return val;

        // Ubah markdown ```css ... ``` menjadi tag <style>
        val = val.replace(/```css\s*([\s\S]*?)```/gi, '<style>\n$1\n</style>');

        // Hapus markdown code blocks lainnya
        val = val.replace(/```(?:html|xml)?\s*([\s\S]*?)```/gi, '$1');
        val = val.replace(/```\s*$/gm, '');

        // Deteksi CSS mentah tanpa tag <style> di awal dokumen (misal /* 1. HEADER */ .lkpd-header { ... })
        var cssMatch = val.match(/^\s*(\/\*[\s\S]*?\*\/[\s\S]*?)(<(?:h[1-6]|div|table|p|section|svg|article|ul|ol)\b)/i);
        if (cssMatch && cssMatch[1].indexOf('{') !== -1 && cssMatch[1].indexOf('}') !== -1) {
            val = '<style>\n' + cssMatch[1].trim() + '\n</style>\n' + cssMatch[2] + val.substring(cssMatch[0].length);
        } else {
            var cssMatch2 = val.match(/^\s*([.#a-zA-Z0-9_\-\s,>:+*]+\s*\{[\s\S]*?\})\s*(<(?:h[1-6]|div|table|p|section|svg|article|ul|ol)\b)/i);
            if (cssMatch2 && cssMatch2[1].indexOf('{') !== -1 && cssMatch2[1].indexOf('}') !== -1) {
                val = '<style>\n' + cssMatch2[1].trim() + '\n</style>\n' + cssMatch2[2] + val.substring(cssMatch2[0].length);
            }
        }

        return cleanModulInput(val);
    }

    $('#manualLkpdPasteArea').on('paste', function() {
        setTimeout(function() {
            var el = $('#manualLkpdPasteArea');
            el.val(cleanLkpdInput(el.val()));
        }, 50);
    });

    $('#lkpdManualSaveForm').on('submit', function(e) {
        e.preventDefault();
        var el = $('#manualLkpdPasteArea');
        el.val(cleanLkpdInput(el.val()));

        const btn = $('#btnSaveManualLkpd');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.post('<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/save') ?>', $(this).serialize())
            .done(function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan LKPD');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('manualLkpdPromptModal')).hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Disimpan!',
                            text: res.message || 'LKPD dari AI browser berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    loadLkpdList(currentLkpdModulId, currentLkpdClassId);
                } else {
                    alert(res.message || 'Gagal menyimpan LKPD.');
                }
            })
            .fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan LKPD');
                alert('Gagal menghubungi server saat menyimpan LKPD.');
            });
    });

    // ========================================================
    // ALUR UPLOAD / IMPORT FILE DOKUMEN PDF LKPD
    // ========================================================
    $(document).on('click', '.btn-upload-lkpd-trigger', function() {
        const pertemuan = $(this).data('pertemuan');
        const title = decodeURIComponent($(this).data('title') || `Pertemuan ${pertemuan}`);

        $('#uploadLkpdModulId').val(currentLkpdModulId);
        $('#uploadLkpdPertemuan').val(pertemuan);
        $('#uploadLkpdPertemuanLabel').text(`Pertemuan ke-${pertemuan}: ${title}`);
        $('#uploadLkpdTitle').val(`LKPD Pertemuan ${pertemuan} - ${title}`);
        $('#lkpdPdfFileInput').val('');

        new bootstrap.Modal(document.getElementById('uploadLkpdPdfModal')).show();
    });

    $('#uploadLkpdPdfForm').on('submit', function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('lkpdPdfFileInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Silakan pilih file PDF terlebih dahulu.');
            return false;
        }

        const file = fileInput.files[0];
        if (file.size > 15 * 1024 * 1024) {
            alert('Ukuran file PDF melebihi 15 MB.');
            return false;
        }

        const btn = $('#btnUploadLkpdPdfSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...');

        const formData = new FormData(this);

        $.ajax({
            url: '<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/upload-pdf') ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Unggah Dokumen PDF');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('uploadLkpdPdfModal')).hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Diunggah!',
                            text: res.message || 'File PDF LKPD berhasil diunggah.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    loadLkpdList(currentLkpdModulId, currentLkpdClassId);
                } else {
                    alert(res.message || 'Gagal mengunggah file.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Unggah Dokumen PDF');
                let err = 'Terjadi kesalahan saat mengunggah file PDF LKPD.';
                try {
                    const j = JSON.parse(xhr.responseText);
                    if (j.message) err = j.message;
                } catch(e) {}
                alert(err);
            }
        });
    });

    // Aksi Hapus LKPD
    $(document).on('click', '.btn-delete-lkpd-action', function() {
        const pertemuan = $(this).data('pertemuan');

        const doDelete = () => {
            $.post('<?= base_url('admin/administrasi-guru/modul-ajar/lkpd/delete') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                modul_id: currentLkpdModulId,
                pertemuan: pertemuan
            }).done(function(res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message || 'LKPD pertemuan berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    loadLkpdList(currentLkpdModulId, currentLkpdClassId);
                } else {
                    alert(res.message || 'Gagal menghapus.');
                }
            }).fail(function() {
                alert('Gagal menghubungi server saat menghapus LKPD.');
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Hapus LKPD Pertemuan ${pertemuan}?`,
                text: 'Lembar kerja ini akan dihapus dari modul ajar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) doDelete();
            });
        } else {
            if (confirm(`Hapus LKPD Pertemuan ${pertemuan}?`)) doDelete();
        }
    });
</script>
<?= $this->endSection() ?>
