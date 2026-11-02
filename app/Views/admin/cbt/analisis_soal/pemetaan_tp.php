<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3" id="pemetaan-tp-container">

    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%); color: white;">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                    <span class="badge bg-white bg-opacity-20 text-white px-2.5 py-1">
                        <i class="bi bi-robot me-1"></i> ESPATA AI ASSESSMENT MAPPING
                    </span>
                    <span class="badge bg-warning text-dark px-2.5 py-1 fw-bold">
                        <i class="bi bi-link-45deg me-1"></i> PENYELARASAN ATP &amp; TP
                    </span>
                </div>
                <h4 class="fw-bold mb-1 text-white">Penyelarasan Materi &amp; Pemetaan TP Soal</h4>
                <p class="mb-0 text-white-50 small">
                    <i class="bi bi-card-checklist me-1"></i> Ujian: <strong><?= esc($test['exam_name'] ?? ('Ujian #' . $test['id'])) ?></strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-book me-1"></i> Mapel: <strong><?= esc($test['subject_name'] ?? 'Mata Pelajaran') ?></strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-mortarboard me-1"></i> Sasaran: <strong><?= esc($scheduling['level_label'] ?? 'Kelas') ?></strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-calendar3 me-1"></i> <strong><?= esc($scheduling['semester_label'] ?? 'Semester') ?></strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-question-circle me-1"></i> <strong><?= count($questions) ?> Butir Soal</strong>
                </p>
            </div>
            <div class="d-flex gap-2 align-self-start align-self-md-center">
                <a href="<?= base_url('admin/cbt/aktivitas/analisis/' . $test['id']) ?>" class="btn btn-light btn-sm text-primary fw-semibold shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Analisis
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi / Status -->
    <div id="alertContainer"></div>

    <!-- FORM UTAMA PEMETAAN -->
    <form id="formPemetaanTp" method="POST" action="<?= base_url('admin/cbt/aktivitas/pemetaan-tp/save/' . $test['id']) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="assessment_type" id="hiddenAssessmentType" value="<?= esc($assessment_type) ?>">
        <input type="hidden" name="mappings" id="hiddenMappings" value="">
        <input type="hidden" name="selected_tp_ids_json" id="hiddenSelectedTpIds" value="">

        <!-- =========================================================================
             LANGKAH 1: PENENTUAN JENIS ASESMEN & PEMILIHAN LINGKUP MATERI DARI ATP
             ========================================================================= -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <h6 class="mb-0 fw-bold text-dark">Penentuan Jenis Asesmen &amp; Lingkup Materi (ATP Guru)</h6>
                </div>
                <span class="badge bg-light text-muted border px-2 py-1 small">Wajib Diisi</span>
            </div>
            <div class="card-body p-4">
                
                <!-- Pilihan Tipe Asesmen: Formatif vs Sumatif -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2">Pilih Jenis Asesmen:</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="card h-100 p-3 border cursor-pointer assessment-type-card <?= $assessment_type === 'formatif' ? 'border-primary bg-primary bg-opacity-10' : '' ?>" style="border-radius: 10px; cursor: pointer;">
                                <div class="d-flex align-items-start">
                                    <input class="form-check-input me-3 mt-1" type="radio" name="assessment_type_radio" value="formatif" <?= $assessment_type === 'formatif' ? 'checked' : '' ?> onchange="onAssessmentTypeChange('formatif')">
                                    <div>
                                        <strong class="d-block text-dark fs-6"><i class="bi bi-bookmark-check text-primary me-1"></i> Asesmen Formatif (1 Pokok Materi)</strong>
                                        <span class="text-muted small">
                                            Ujian ini menguji ketercapaian siswa pada <strong>satu pokok bahasan/lingkup materi</strong> tertentu beserta rangkaian TP di dalamnya.
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="card h-100 p-3 border cursor-pointer assessment-type-card <?= $assessment_type === 'sumatif' ? 'border-primary bg-primary bg-opacity-10' : '' ?>" style="border-radius: 10px; cursor: pointer;">
                                <div class="d-flex align-items-start">
                                    <input class="form-check-input me-3 mt-1" type="radio" name="assessment_type_radio" value="sumatif" <?= $assessment_type === 'sumatif' ? 'checked' : '' ?> onchange="onAssessmentTypeChange('sumatif')">
                                    <div>
                                        <strong class="d-block text-dark fs-6"><i class="bi bi-collection text-primary me-1"></i> Asesmen Sumatif (Beberapa Pokok Materi)</strong>
                                        <span class="text-muted small">
                                            Ujian ini menguji ketercapaian beberapa bab (misal: Sumatif Tengah Semester atau Akhir Semester) yang mencakup <strong>beberapa lingkup materi</strong>.
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <!-- Daftar Lingkup Materi dari ATP -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <label class="form-label fw-bold text-dark mb-0">
                            Pilih Lingkup Materi (ATP) yang Diujikan:
                        </label>
                        <span class="text-muted small" id="materiSelectInstruction">
                            <?= $assessment_type === 'formatif' ? 'Pilih salah satu materi pokok' : 'Centang satu atau beberapa materi pokok' ?>
                        </span>
                    </div>

                    <!-- Indikator Filter Otomatis Berdasarkan Jadwal Ujian -->
                    <div class="alert alert-light border border-primary border-opacity-25 d-flex align-items-center justify-content-between p-2.5 px-3 mb-3 small" style="border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-funnel-fill text-primary me-2"></i>
                            <span>
                                Materi otomatis difilter sesuai jadwal ujian: 
                                <strong><?= esc($scheduling['level_label'] ?? 'Kelas') ?></strong> · <strong><?= esc($scheduling['semester_label'] ?? 'Semester') ?></strong>
                            </span>
                        </div>
                        <?php if (!empty($scheduling['target_class_names'])): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <?= esc(implode(', ', $scheduling['target_class_names'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($atp_list)): ?>
                        <div class="alert alert-warning border-0 d-flex align-items-center p-3" style="border-radius: 8px;">
                            <i class="bi bi-exclamation-circle fs-4 me-3 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Data ATP untuk <?= esc($scheduling['level_label'] ?? 'Kelas') ?> <?= esc($scheduling['semester_label'] ?? '') ?> Belum Ditemukan</strong>
                                <span class="small text-muted">
                                    Belum ada data Alur Tujuan Pembelajaran (ATP) yang didaftarkan guru untuk tingkat kelas dan semester ini. Silakan buat ATP terlebih dahulu pada menu <strong>Administrasi Guru &gt; Analisis ATP</strong>.
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-3" id="atpCardsContainer">
                            <?php foreach ($atp_list as $atp): ?>
                                <?php 
                                    $isAtpSelected = in_array($atp['id'], $selected_atp_ids) || (count($atp_list) === 1);
                                    $tpCount = count($atp['tps']);
                                ?>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100 border p-3 atp-card <?= $isAtpSelected ? 'border-primary bg-primary bg-opacity-10' : '' ?>" style="border-radius: 10px;">
                                        <div class="d-flex align-items-start">
                                            <input class="form-check-input me-3 mt-1 atp-input" 
                                                   type="<?= $assessment_type === 'formatif' ? 'radio' : 'checkbox' ?>" 
                                                   name="selected_atp_ids[]" 
                                                   value="<?= esc($atp['id']) ?>" 
                                                   id="atp_<?= esc($atp['id']) ?>"
                                                   <?= $isAtpSelected ? 'checked' : '' ?>
                                                   onchange="onAtpSelectionChange()">
                                            <label class="form-check-label w-100 cursor-pointer" for="atp_<?= esc($atp['id']) ?>" style="cursor: pointer;">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="badge bg-primary bg-opacity-25 text-primary small">Semester <?= esc($atp['semester']) ?></span>
                                                    <span class="badge bg-secondary bg-opacity-25 text-secondary small"><?= $tpCount ?> TP</span>
                                                </div>
                                                <strong class="d-block text-dark fs-6 mb-1"><?= esc($atp['lingkup_materi']) ?></strong>
                                                <div class="small text-muted mb-2">
                                                    <?php foreach (array_slice($atp['tps'], 0, 3) as $tp): ?>
                                                        <span class="badge bg-light text-dark border me-1 mb-1" style="font-size: 0.72rem;">
                                                            <?= esc($tp['kode_tp']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if ($tpCount > 3): ?>
                                                        <span class="text-muted" style="font-size: 0.72rem;">+<?= $tpCount - 3 ?> TP lainnya</span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pratinjau Daftar TP Aktif -->
                <div class="p-3 bg-light rounded-3 mt-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-dark">
                            <i class="bi bi-check2-all text-success me-1"></i> Rincian Tujuan Pembelajaran (TP) yang Diujikan:
                        </span>
                        <span class="badge bg-success" id="activeTpCountBadge">0 TP Aktif</span>
                    </div>
                    <div id="activeTpChipsContainer" class="d-flex flex-wrap gap-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

            </div>
        </div>

        <!-- =========================================================================
             LANGKAH 2: PEMETAAN BUTIR SOAL KE TP (AI & PENYESUAIAN GURU)
             ========================================================================= -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Pemetaan Butir Soal ke Tujuan Pembelajaran (TP)</h6>
                        <small class="text-muted">Gunakan bantuan AI untuk mencocokkan soal secara otomatis atau tentukan manual per butir soal</small>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold shadow-sm" id="btnAiMatch" onclick="runAiMatching()">
                        <i class="bi bi-robot me-1"></i> Analisis &amp; Petakan Soal dengan AI
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                
                <!-- Indikator Loading AI -->
                <div id="aiLoadingIndicator" class="p-4 text-center d-none bg-light border-bottom">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">Sedang Menganalisis Butir Soal dengan AI...</h6>
                    <p class="small text-muted mb-0">AI sedang membaca teks soal, stimulus, dan membandingkannya dengan kata kerja serta deskripsi TP dari ATP terpilih.</p>
                </div>

                <!-- Tabel Soal & Dropdown TP -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablePemetaanSoal">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-3" style="width: 60px;">No.</th>
                                <th style="width: 100px;">Tipe</th>
                                <th>Cuplikan Soal Ujian</th>
                                <th style="width: 220px;">Rekomendasi AI</th>
                                <th style="width: 320px;" class="pe-3">Tujuan Pembelajaran (TP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-x fs-2 d-block mb-2 text-muted"></i>
                                        Tidak ada butir soal dalam bank soal ujian ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $idx => $q): ?>
                                    <?php 
                                        $qid = (int)$q['id'];
                                        $saved = $saved_mappings[$qid] ?? null;
                                        $currentTpId = $saved['tp_id'] ?? null;
                                        $cleanSnippet = strip_tags($q['raw_text'] ?? ($q['question_text'] ?? ''));
                                        $cleanSnippet = mb_substr(preg_replace('/\s+/', ' ', $cleanSnippet), 0, 160);
                                    ?>
                                    <tr id="row_q_<?= $qid ?>" data-qid="<?= $qid ?>">
                                        <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border small text-uppercase">
                                                <?= esc($q['question_type'] ?? 'pg') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="d-block text-dark small mb-1"><?= esc($cleanSnippet) ?>...</span>
                                            <a class="text-primary text-decoration-none small" style="font-size: 0.75rem;" data-bs-toggle="collapse" href="#collapseQ_<?= $qid ?>" role="button">
                                                <i class="bi bi-chevron-down me-1"></i> Lihat teks lengkap &amp; opsi
                                            </a>
                                            <div class="collapse mt-2" id="collapseQ_<?= $qid ?>">
                                                <div class="card card-body p-2 bg-light border small text-muted">
                                                    <?= nl2br(esc($q['raw_text'] ?? $q['question_text'])) ?>
                                                    <?php if (!empty($q['option_a'])): ?>
                                                        <hr class="my-1">
                                                        <div><strong>A:</strong> <?= esc(strip_tags($q['option_a'])) ?></div>
                                                        <div><strong>B:</strong> <?= esc(strip_tags($q['option_b'])) ?></div>
                                                        <?php if (!empty($q['option_c'])): ?><div><strong>C:</strong> <?= esc(strip_tags($q['option_c'])) ?></div><?php endif; ?>
                                                        <?php if (!empty($q['option_d'])): ?><div><strong>D:</strong> <?= esc(strip_tags($q['option_d'])) ?></div><?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small" id="ai_rec_<?= $qid ?>">
                                            <?php if ($saved): ?>
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5">
                                                        <i class="bi bi-check-circle me-1"></i> Terpetakan
                                                    </span>
                                                    <small class="text-muted"><?= round($saved['confidence']) ?>%</small>
                                                </div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;"><?= esc($saved['reasoning'] ?: 'Pemetaan terpasang') ?></small>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic" style="font-size: 0.78rem;">
                                                    <i class="bi bi-dash-circle me-1"></i> Belum dianalisis
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3">
                                            <select class="form-select form-select-sm select-tp" 
                                                    id="select_tp_<?= $qid ?>" 
                                                    data-qid="<?= $qid ?>" 
                                                    onchange="onQuestionTpChange(<?= $qid ?>)">
                                                <option value="">-- Pilih Tujuan Pembelajaran (TP) --</option>
                                                <!-- Populated dynamically based on active TPs -->
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            
            <!-- Card Footer: Simpan & Terapkan -->
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="<?= base_url('admin/cbt/aktivitas/analisis/' . $test['id']) ?>" class="btn btn-outline-secondary btn-sm">
                    Batal
                </a>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 shadow-sm" id="btnSimpanPemetaan">
                        <i class="bi bi-save2 me-1"></i> Simpan &amp; Terapkan ke Analisis
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<!-- Data JSON untuk Client-Side Interactivity -->
<script>
    const ATP_DATA = <?= json_encode($atp_list) ?>;
    const SAVED_MAPPINGS = <?= json_encode($saved_mappings) ?>;
    const TEST_ID = <?= (int)$test['id'] ?>;
    const CSRF_TOKEN = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    // State pemetaan saat ini: { qid: { tp_id: X, confidence: 90, reasoning: '...', mapping_source: 'ai' } }
    let currentMappings = {};
    for (const [qid, map] of Object.entries(SAVED_MAPPINGS)) {
        currentMappings[qid] = {
            tp_id: parseInt(map.tp_id),
            confidence: parseFloat(map.confidence || 100),
            reasoning: map.reasoning || '',
            mapping_source: map.mapping_source || 'manual'
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        refreshActiveTps();
    });

    /**
     * Handler saat jenis asesmen diubah (Formatif <-> Sumatif)
     */
    function onAssessmentTypeChange(type) {
        document.getElementById('hiddenAssessmentType').value = type;

        // Update tampilan kartu tipe
        document.querySelectorAll('.assessment-type-card').forEach(el => {
            el.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        });
        const selectedRadio = document.querySelector(`input[name="assessment_type_radio"][value="${type}"]`);
        if (selectedRadio) {
            selectedRadio.closest('.assessment-type-card').classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
        }

        // Ubah input ATP menjadi radio jika formatif, checkbox jika sumatif
        const atpInputs = document.querySelectorAll('.atp-input');
        atpInputs.forEach(input => {
            input.type = (type === 'formatif') ? 'radio' : 'checkbox';
        });

        const instruction = document.getElementById('materiSelectInstruction');
        if (instruction) {
            instruction.innerText = (type === 'formatif') ? 'Pilih salah satu materi pokok' : 'Centang satu atau beberapa materi pokok';
        }

        // Jika formatif dan terpilih > 1, sisakan hanya 1 yang pertama
        if (type === 'formatif') {
            let foundFirst = false;
            atpInputs.forEach(input => {
                if (input.checked) {
                    if (!foundFirst) {
                        foundFirst = true;
                    } else {
                        input.checked = false;
                    }
                }
            });
            if (!foundFirst && atpInputs.length > 0) {
                atpInputs[0].checked = true;
            }
        }

        onAtpSelectionChange();
    }

    /**
     * Handler saat pilihan ATP berubah
     */
    function onAtpSelectionChange() {
        // Update highlight kartu ATP
        document.querySelectorAll('.atp-card').forEach(card => {
            const input = card.querySelector('.atp-input');
            if (input && input.checked) {
                card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            } else {
                card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            }
        });

        refreshActiveTps();
    }

    /**
     * Ambil daftar TP yang aktif berdasarkan ATP yang dipilih saat ini
     */
    function getActiveTps() {
        const selectedAtpIds = Array.from(document.querySelectorAll('.atp-input:checked')).map(el => parseInt(el.value));
        const activeTps = [];

        ATP_DATA.forEach(atp => {
            if (selectedAtpIds.includes(parseInt(atp.id)) || atp.id === 0) {
                atp.tps.forEach(tp => {
                    activeTps.push({
                        id: parseInt(tp.id),
                        kode_tp: tp.kode_tp,
                        deskripsi: tp.deskripsi,
                        lingkup_materi: atp.lingkup_materi
                    });
                });
            }
        });

        return activeTps;
    }

    /**
     * Perbarui chip TP aktif dan opsi di semua dropdown soal
     */
    function refreshActiveTps() {
        const activeTps = getActiveTps();

        // 1. Update Badge & Chips
        const countBadge = document.getElementById('activeTpCountBadge');
        if (countBadge) {
            countBadge.innerText = activeTps.length + ' TP Aktif';
        }

        const chipsContainer = document.getElementById('activeTpChipsContainer');
        if (chipsContainer) {
            if (activeTps.length === 0) {
                chipsContainer.innerHTML = '<span class="text-muted small fst-italic">Belum ada materi/TP yang dipilih di Langkah 1.</span>';
            } else {
                chipsContainer.innerHTML = activeTps.map(tp => `
                    <div class="badge bg-white text-dark border p-2 d-flex align-items-center gap-1 shadow-sm" style="font-size: 0.78rem;">
                        <span class="text-primary fw-bold">${tp.kode_tp}:</span>
                        <span class="text-muted text-truncate" style="max-width: 280px;" title="${tp.deskripsi}">${tp.deskripsi || tp.lingkup_materi}</span>
                    </div>
                `).join('');
            }
        }

        // 2. Update Options pada seluruh select dropdown tabel soal
        document.querySelectorAll('.select-tp').forEach(select => {
            const qid = parseInt(select.getAttribute('data-qid'));
            const currentSelected = currentMappings[qid] ? currentMappings[qid].tp_id : select.value;

            select.innerHTML = '<option value="">-- Pilih Tujuan Pembelajaran (TP) --</option>' +
                activeTps.map(tp => {
                    const isSelected = (parseInt(currentSelected) === tp.id) ? 'selected' : '';
                    return `<option value="${tp.id}" ${isSelected}>[${tp.kode_tp}] ${tp.lingkup_materi} - ${tp.deskripsi.substring(0, 50)}...</option>`;
                }).join('');
        });
    }

    /**
     * Handler saat guru mengubah pilihan TP manual di dropdown
     */
    function onQuestionTpChange(qid) {
        const select = document.getElementById('select_tp_' + qid);
        const val = select.value ? parseInt(select.value) : null;

        if (val) {
            currentMappings[qid] = {
                tp_id: val,
                confidence: 100.0,
                reasoning: 'Pilihan manual oleh guru',
                mapping_source: 'manual'
            };
            const recEl = document.getElementById('ai_rec_' + qid);
            if (recEl) {
                recEl.innerHTML = `
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-secondary px-2 py-0.5">Manual</span>
                        <small class="text-muted">100%</small>
                    </div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Disesuaikan oleh guru</small>
                `;
            }
        } else {
            delete currentMappings[qid];
            const recEl = document.getElementById('ai_rec_' + qid);
            if (recEl) {
                recEl.innerHTML = `<span class="text-muted fst-italic" style="font-size: 0.78rem;"><i class="bi bi-dash-circle me-1"></i> Belum dipilih</span>`;
            }
        }
    }

    /**
     * Jalankan Analisis & Pencocokan Butir Soal dengan AI via AJAX
     */
    function runAiMatching() {
        const activeTps = getActiveTps();
        if (activeTps.length === 0) {
            alert('Pilih minimal satu Lingkup Materi (ATP) pada Langkah 1 sebelum menjalankan pemetaan AI.');
            return;
        }

        const btnAi = document.getElementById('btnAiMatch');
        const loader = document.getElementById('aiLoadingIndicator');
        btnAi.disabled = true;
        loader.classList.remove('d-none');

        const selectedAtpIds = Array.from(document.querySelectorAll('.atp-input:checked')).map(el => parseInt(el.value));
        const selectedTpIds = activeTps.map(tp => tp.id);
        const assessmentType = document.getElementById('hiddenAssessmentType').value;

        const formData = new FormData();
        formData.append('assessment_type', assessmentType);
        selectedAtpIds.forEach(id => formData.append('selected_atp_ids[]', id));
        selectedTpIds.forEach(id => formData.append('selected_tp_ids[]', id));
        formData.append(CSRF_TOKEN, CSRF_HASH);

        fetch('<?= base_url('admin/cbt/aktivitas/pemetaan-tp/ai-match/' . $test['id']) ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            btnAi.disabled = false;
            loader.classList.add('d-none');

            if (data.success && data.mappings) {
                let mappedCount = 0;
                for (const [qidStr, rec] of Object.entries(data.mappings)) {
                    const qid = parseInt(qidStr);
                    currentMappings[qid] = {
                        tp_id: parseInt(rec.tp_id),
                        confidence: parseFloat(rec.confidence || 85),
                        reasoning: rec.reasoning || '',
                        mapping_source: rec.mapping_source || 'ai'
                    };

                    // Update UI dropdown
                    const select = document.getElementById('select_tp_' + qid);
                    if (select) {
                        select.value = rec.tp_id;
                    }

                    // Update UI cell rekomendasi
                    const recEl = document.getElementById('ai_rec_' + qid);
                    if (recEl) {
                        const isAi = (rec.mapping_source === 'ai_gemini');
                        const badgeColor = isAi ? 'bg-primary' : 'bg-info text-dark';
                        const badgeLabel = isAi ? 'AI Gemini' : 'Semantik TP';
                        recEl.innerHTML = `
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="badge ${badgeColor} px-2 py-0.5">${badgeLabel}</span>
                                <small class="text-success fw-bold">${Math.round(rec.confidence)}%</small>
                            </div>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">${rec.reasoning || 'Kecocokan indikator'}</small>
                        `;
                    }
                    mappedCount++;
                }

                showAlert('success', `Berhasil! AI telah mencocokkan ${mappedCount} butir soal ke Tujuan Pembelajaran (TP) yang relevan. Periksa kembali dan klik "Simpan & Terapkan".`);
            } else {
                showAlert('danger', 'Gagal melakukan pemetaan AI: ' + (data.message || 'Terjadi kesalahan sistem.'));
            }
        })
        .catch(err => {
            btnAi.disabled = false;
            loader.classList.add('d-none');
            showAlert('danger', 'Terjadi gangguan jaringan saat menghubungi layanan AI: ' + err.message);
        });
    }

    /**
     * Intersep submit form untuk memvalidasi dan mem-bundle JSON mappings
     */
    document.getElementById('formPemetaanTp').addEventListener('submit', function (e) {
        const activeTps = getActiveTps();
        if (activeTps.length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu Lingkup Materi (ATP) pada Langkah 1.');
            return;
        }

        const mappedTotal = Object.keys(currentMappings).length;
        if (mappedTotal === 0) {
            if (!confirm('Belum ada butir soal yang dipetakan ke TP. Apakah Anda yakin ingin menyimpan tanpa pemetaan soal?')) {
                e.preventDefault();
                return;
            }
        }

        // Set value input hidden mappings
        document.getElementById('hiddenMappings').value = JSON.stringify(currentMappings);
    });

    function showAlert(type, message) {
        const container = document.getElementById('alertContainer');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 10px;">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }
</script>

<?= $this->endSection() ?>

