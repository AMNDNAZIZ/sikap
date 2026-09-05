<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">Pembuatan Soal AI</h4>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<!-- Filter Kelas & Mapel -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Kelas</label>
                <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($selected_class == $c['id']) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Mata Pelajaran</label>
                <select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($selected_subject == $s['id']) ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selected_class): ?>
                <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($gemini_api_key)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>API Key belum dikonfigurasi.</strong> Silakan atur di halaman
        <a href="<?= base_url('admin/administrasi-guru/modul-ajar') ?>">Modul Ajar</a>.
    </div>
<?php elseif ($selected_subject && $selected_class): ?>

    <!-- Form Generate Soal -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-magic me-1"></i> Generate Soal</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Jenis Asesmen -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Jenis Asesmen</label>
                    <select id="jenis_asesmen" class="form-select">
                        <option value="formatif">Formatif (1 Materi)</option>
                        <option value="sumatif">Sumatif (1 Semester)</option>
                    </select>
                </div>

                <!-- Formatif: pilih 1 materi -->
                <div class="col-md-5" id="wrap-materi">
                    <label class="form-label fw-bold">Materi (ATP)</label>
                    <select id="atp_id" class="form-select">
                        <option value="">-- Pilih Materi --</option>
                        <?php foreach ($atp_list as $atp): ?>
                            <option value="<?= $atp['id'] ?>"
                                data-semester="<?= $atp['semester'] ?>"
                                data-tps='<?= json_encode($atp['tps'] ?? []) ?>'>
                                Sem <?= $atp['semester'] ?> - <?= esc($atp['lingkup_materi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sumatif: pilih semester -->
                <div class="col-md-5 d-none" id="wrap-semester">
                    <label class="form-label fw-bold">Semester</label>
                    <select id="semester_id" class="form-select">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                    <small class="text-muted mt-1 d-block" id="info-semester"></small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Tipe & Jumlah Soal</label>
                    <div class="border rounded p-2">
                        <div class="form-check d-flex align-items-center gap-2 mb-1">
                            <input class="form-check-input tipe-check" type="checkbox" value="pg" id="chk_pg" checked>
                            <label class="form-check-label small" for="chk_pg">Pilihan Ganda</label>
                            <input type="number" class="form-control form-control-sm ms-auto tipe-jumlah" data-tipe="pg" value="5" min="0" max="50" style="width:60px">
                        </div>
                        <div class="form-check d-flex align-items-center gap-2 mb-1">
                            <input class="form-check-input tipe-check" type="checkbox" value="pg_kompleks" id="chk_pgk">
                            <label class="form-check-label small" for="chk_pgk">PG Kompleks</label>
                            <input type="number" class="form-control form-control-sm ms-auto tipe-jumlah" data-tipe="pg_kompleks" value="3" min="0" max="50" style="width:60px">
                        </div>
                        <div class="form-check d-flex align-items-center gap-2 mb-1">
                            <input class="form-check-input tipe-check" type="checkbox" value="benar_salah" id="chk_bs">
                            <label class="form-check-label small" for="chk_bs">Benar / Salah</label>
                            <input type="number" class="form-control form-control-sm ms-auto tipe-jumlah" data-tipe="benar_salah" value="5" min="0" max="50" style="width:60px">
                        </div>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input class="form-check-input tipe-check" type="checkbox" value="esai" id="chk_esai">
                            <label class="form-check-label small" for="chk_esai">Esai / Uraian</label>
                            <input type="number" class="form-control form-control-sm ms-auto tipe-jumlah" data-tipe="esai" value="2" min="0" max="50" style="width:60px">
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Tujuan Pembelajaran (TP) <small class="text-muted">&mdash; pilih yang relevan</small></label>
                    <div id="tp-list" class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                        <span class="text-muted small">Pilih materi atau semester terlebih dahulu</span>
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" id="btn-generate" class="btn btn-primary" disabled>
                        <i class="bi bi-stars me-1"></i> Generate Soal (API)
                    </button>
                    <button type="button" id="btn-manual-prompt" class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-clipboard me-1"></i> Generate Manual (Copy Prompt)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Generate -->
    <div id="hasil-wrapper" class="d-none">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-check-circle me-1"></i> Hasil Generate (<span id="hasil-count">0</span> soal)</h6>
            </div>
            <div class="card-body p-0">
                <div id="hasil-soal" class="list-group list-group-flush" style="max-height:400px; overflow-y:auto;"></div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Simpan ke Bank Soal:</label>
                        <select id="bank_id" class="form-select form-select-sm">
                            <option value="">-- Pilih Bank Soal --</option>
                            <?php foreach ($bank_soal as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= esc($b['code']) ?> (<?= $b['total_questions'] ?> soal)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" id="btn-save" class="btn btn-success" disabled>
                            <i class="bi bi-floppy me-1"></i> Simpan ke Bank Soal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif (!$selected_subject || !$selected_class): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i> Pilih kelas dan mata pelajaran untuk mulai membuat soal.
    </div>
<?php endif; ?>

<script>
const CSRF_NAME = '<?= csrf_token() ?>';
let csrfHash = '<?= csrf_hash() ?>';
let generatedSoal = [];
let generatedTipe = '';
let soalManualModalInstance = null;

function setGenerateButtons(disabled) {
    document.getElementById('btn-generate').disabled = disabled;
    document.getElementById('btn-manual-prompt').disabled = disabled;
}
// Data ATP untuk sumatif
const allAtp = <?= json_encode($atp_list) ?>;

// Toggle Formatif / Sumatif
document.getElementById('jenis_asesmen')?.addEventListener('change', function() {
    const isSubmatif = this.value === 'sumatif';
    document.getElementById('wrap-materi').classList.toggle('d-none', isSubmatif);
    document.getElementById('wrap-semester').classList.toggle('d-none', !isSubmatif);

    if (isSubmatif) {
        document.getElementById('semester_id').dispatchEvent(new Event('change'));
    } else {
        document.getElementById('tp-list').innerHTML = '<span class="text-muted small">Pilih materi terlebih dahulu</span>';
        setGenerateButtons(true);
    }
});

// Sumatif: semester change -> load semua TP dari semester itu
document.getElementById('semester_id')?.addEventListener('change', function() {
    const sem = this.value;
    const semAtps = allAtp.filter(a => a.semester == sem);
    const container = document.getElementById('tp-list');

    document.getElementById('info-semester').textContent = semAtps.length + ' materi pada semester ' + sem;

    if (semAtps.length === 0) {
        container.innerHTML = '<span class="text-muted small">Tidak ada ATP di semester ini</span>';
        setGenerateButtons(true);
        return;
    }

    let html = '';
    semAtps.forEach(atp => {
        html += `<div class="mb-2"><strong class="small text-primary">${atp.lingkup_materi}</strong>`;
        (atp.tps || []).forEach(tp => {
            html += `<div class="form-check ms-2">
                <input class="form-check-input tp-check" type="checkbox" value="${tp.id}" id="tp_${tp.id}" checked>
                <label class="form-check-label small" for="tp_${tp.id}">${tp.kode_tp ? tp.kode_tp + ': ' : ''}${tp.deskripsi}</label>
            </div>`;
        });
        html += '</div>';
    });
    container.innerHTML = html;
    setGenerateButtons(false);
});

// Formatif: ATP change -> load TP
document.getElementById('atp_id')?.addEventListener('change', function() {
    if (document.getElementById('jenis_asesmen').value === 'sumatif') return;

    const opt = this.options[this.selectedIndex];
    const tps = JSON.parse(opt.dataset.tps || '[]');
    const container = document.getElementById('tp-list');

    if (tps.length === 0) {
        container.innerHTML = '<span class="text-muted small">Tidak ada TP untuk materi ini</span>';
    } else {
        container.innerHTML = tps.map(tp =>
            `<div class="form-check">
                <input class="form-check-input tp-check" type="checkbox" value="${tp.id}" id="tp_${tp.id}" checked>
                <label class="form-check-label small" for="tp_${tp.id}">${tp.kode_tp ? tp.kode_tp + ': ' : ''}${tp.deskripsi}</label>
            </div>`
        ).join('');
    }

    setGenerateButtons(!this.value);
});

// Generate
document.getElementById('btn-generate')?.addEventListener('click', async function() {
    const btn = this;
    const jenis = document.getElementById('jenis_asesmen').value;

    // Kumpulkan tipe yang dipilih beserta jumlahnya
    const tipeList = [];
    document.querySelectorAll('.tipe-check:checked').forEach(chk => {
        const jumlah = document.querySelector(`.tipe-jumlah[data-tipe="${chk.value}"]`).value;
        if (parseInt(jumlah) > 0) {
            tipeList.push({ tipe: chk.value, jumlah: parseInt(jumlah) });
        }
    });

    if (tipeList.length === 0) { alert('Pilih minimal satu tipe soal dengan jumlah > 0'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    const tpIds = [...document.querySelectorAll('.tp-check:checked')].map(el => el.value);

    const body = new FormData();
    body.append('subject_id', '<?= $selected_subject ?>');
    body.append('class_id', '<?= $selected_class ?>');
    body.append('tipe_list', JSON.stringify(tipeList));
    body.append('jenis_asesmen', jenis);
    tpIds.forEach(id => body.append('tp_ids[]', id));

    if (jenis === 'formatif') {
        body.append('atp_id', document.getElementById('atp_id').value);
    } else {
        // Sumatif: kirim semester dan semua ATP IDs di semester itu
        const sem = document.getElementById('semester_id').value;
        body.append('semester', sem);
        const semAtpIds = allAtp.filter(a => a.semester == sem).map(a => a.id);
        semAtpIds.forEach(id => body.append('atp_ids[]', id));
    }

    body.append(CSRF_NAME, csrfHash);

    try {
        const res = await fetch('<?= base_url("admin/administrasi-guru/soal-ai/generate") ?>', { method: 'POST', body });
        const data = await res.json();
        if (data.csrf) csrfHash = data.csrf;

        if (data.success) {
            generatedSoal = data.soal;
            generatedTipe = 'multi';
            renderHasil(data.soal);
        } else {
            alert('Gagal: ' + (data.error || 'Unknown error'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-stars me-1"></i> Generate Soal (API)';
});

function renderHasil(soal) {
    const wrapper = document.getElementById('hasil-wrapper');
    const container = document.getElementById('hasil-soal');
    document.getElementById('hasil-count').textContent = soal.length;
    wrapper.classList.remove('d-none');

    const tipeLabel = { pg: 'PG', pg_kompleks: 'PGK', benar_salah: 'B/S', esai: 'Esai' };

    // Normalisasi tipe dari output AI yang mungkin bervariasi
    const normalizeType = (t) => {
        t = (t || 'pg').toLowerCase().trim();
        if (['pg', 'pilihan_ganda', 'pilgan', 'multiple_choice'].includes(t)) return 'pg';
        if (['pg_kompleks', 'pgk', 'pilihan_ganda_kompleks', 'pg kompleks'].includes(t)) return 'pg_kompleks';
        if (['benar_salah', 'bs', 'true_false'].includes(t)) return 'benar_salah';
        if (['esai', 'essay', 'uraian'].includes(t)) return 'esai';
        return t;
    };

    // Normalisasi tipe di data soal sebelum render
    soal.forEach(s => { s.type = normalizeType(s.type); });

    container.innerHTML = soal.map((s, i) => {
        let html = `<div class="list-group-item">
            <div class="d-flex justify-content-between">
                <div class="fw-bold mb-1">${i+1}. ${s.question}</div>
                <span class="badge bg-secondary">${tipeLabel[s.type] || s.type}</span>
            </div>`;

        if (s.options) {
            html += '<div class="ms-3 small">';
            for (const [key, val] of Object.entries(s.options)) {
                const isCorrect = s.answer && s.answer.includes(key);
                html += `<div class="${isCorrect ? 'text-success fw-bold' : ''}">${key}. ${val} ${isCorrect ? '&#10003;' : ''}</div>`;
            }
            html += '</div>';
        }

        if (s.type === 'benar_salah' || s.type === 'esai') {
            html += `<div class="ms-3 small text-success"><b>Jawaban:</b> ${s.answer}</div>`;
        }

        html += '</div>';
        return html;
    }).join('');

    document.getElementById('btn-save').disabled = false;
}

// Save to bank
document.getElementById('btn-save')?.addEventListener('click', async function() {
    const bankId = document.getElementById('bank_id').value;
    if (!bankId) { alert('Pilih bank soal terlebih dahulu'); return; }
    if (generatedSoal.length === 0) { alert('Tidak ada soal untuk disimpan'); return; }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const body = new FormData();
    body.append('bank_id', bankId);
    body.append('soal_json', JSON.stringify(generatedSoal));
    body.append('tipe_soal', 'multi'); // flag multi-tipe
    body.append(CSRF_NAME, csrfHash);

    try {
        const res = await fetch('<?= base_url("admin/administrasi-guru/soal-ai/save-to-bank") ?>', { method: 'POST', body });
        const data = await res.json();
        if (data.csrf) csrfHash = data.csrf;

        if (data.success) {
            alert('Berhasil! ' + data.message);
            location.reload();
        } else {
            alert('Gagal: ' + (data.error || 'Unknown'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }

    this.disabled = false;
    this.innerHTML = '<i class="bi bi-floppy me-1"></i> Simpan ke Bank Soal';
});

// === Manual Prompt ===
document.getElementById('btn-manual-prompt')?.addEventListener('click', async function() {
    const btn = this;
    const jenis = document.getElementById('jenis_asesmen').value;
    const subjectId = '<?= $selected_subject ?? '' ?>';
    const classId = '<?= $selected_class ?? '' ?>';

    // Gather tipe list
    const tipeList = [];
    document.querySelectorAll('.tipe-check:checked').forEach(chk => {
        const jumlah = parseInt(document.querySelector(`.tipe-jumlah[data-tipe="${chk.value}"]`).value || 0);
        if (jumlah > 0) tipeList.push({ tipe: chk.value, jumlah });
    });
    if (tipeList.length === 0) { alert('Pilih minimal satu tipe soal.'); return; }

    const body = new FormData();
    body.append('subject_id', subjectId);
    body.append('class_id', classId);
    body.append('tipe_list', JSON.stringify(tipeList));
    body.append('jenis_asesmen', jenis);
    body.append(CSRF_NAME, csrfHash);

    if (jenis === 'formatif') {
        const atpId = document.getElementById('atp_id')?.value;
        if (!atpId) { alert('Pilih materi ATP.'); return; }
        body.append('atp_id', atpId);
        const tpChecks = document.querySelectorAll('.tp-check:checked');
        tpChecks.forEach(c => body.append('tp_ids[]', c.value));
    } else {
        const semester = document.getElementById('semester_id')?.value;
        body.append('semester', semester);
        const semAtpIds = allAtp.filter(a => a.semester == semester).map(a => a.id);
        semAtpIds.forEach(id => body.append('atp_ids[]', id));
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan...';

    try {
        const res = await fetch('<?= base_url("admin/administrasi-guru/soal-ai/get-prompt") ?>', { method: 'POST', body });
        const data = await res.json();
        if (data.csrf) csrfHash = data.csrf;

        if (data.success) {
            document.getElementById('soalPromptText').value = data.prompt;
            document.getElementById('soalPasteStep').style.display = 'none';
            document.getElementById('soalPromptStep').style.display = 'block';
            const modalEl = document.getElementById('soalManualModal');
            soalManualModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            soalManualModalInstance.show();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Generate Manual (Copy Prompt)';
});
</script>

<!-- Modal: Manual Prompt Soal -->
<div class="modal fade" id="soalManualModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="bi bi-clipboard me-2"></i>Generate Soal Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1 -->
                <div id="soalPromptStep">
                    <div class="alert alert-info py-2">
                    <strong>Langkah 1:</strong> Copy prompt di bawah &rarr; paste ke ChatGPT / Gemini / AI lainnya.
                    </div>
                    <textarea id="soalPromptText" class="form-control mb-3" rows="10" readonly style="font-size:0.8rem; background:#f8f9fa;"></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" id="copySoalPrompt" class="btn btn-success">
                            <i class="bi bi-clipboard-check me-1"></i>Copy Prompt
                        </button>
                        <button type="button" id="goSoalPaste" class="btn btn-primary">
                            <i class="bi bi-arrow-right me-1"></i>Selanjutnya: Paste Hasil
                        </button>
                    </div>
                </div>
                <!-- Step 2 -->
                <div id="soalPasteStep" style="display:none;">
                    <div class="alert alert-success py-2">
                        <strong>Langkah 2:</strong> Paste hasil JSON dari AI, lalu klik "Parse & Tampilkan".
                    </div>
                    <textarea id="soalPasteArea" class="form-control mb-3" rows="10" placeholder='Paste JSON array hasil AI di sini... contoh: [{"type":"pg","question":"...","options":{...},"answer":"A"}]'></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" id="backSoalPrompt" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </button>
                        <button type="button" id="parseSoalManual" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Parse & Tampilkan Soal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk modal (harus setelah modal ada di DOM) -->
<script>
// ── Inisialisasi modal instance ────────────────────────────────────────────
const soalModalEl = document.getElementById('soalManualModal');
let soalModalInst = null;

function getModalInstance() {
    if (!soalModalInst) {
        soalModalInst = bootstrap.Modal.getOrCreateInstance(soalModalEl);
    }
    return soalModalInst;
}

// ── Reset modal saat ditutup ───────────────────────────────────────────────
soalModalEl.addEventListener('hidden.bs.modal', function () {
    document.getElementById('soalPasteArea').value     = '';
    document.getElementById('soalPasteStep').style.display  = 'none';
    document.getElementById('soalPromptStep').style.display = 'block';
});

// ── Step navigation ─────────────────────────────────────────────────────────
document.getElementById('copySoalPrompt').addEventListener('click', function () {
    const ta = document.getElementById('soalPromptText');
    ta.select();
    try {
        navigator.clipboard.writeText(ta.value).catch(() => document.execCommand('copy'));
    } catch (_) {
        document.execCommand('copy');
    }
    this.innerHTML = '<i class="bi bi-check me-1"></i>Tersalin!';
    setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard-check me-1"></i>Copy Prompt'; }, 2000);
});

document.getElementById('goSoalPaste').addEventListener('click', function () {
    document.getElementById('soalPromptStep').style.display = 'none';
    document.getElementById('soalPasteStep').style.display  = 'block';
    document.getElementById('soalPasteArea').focus();
});

document.getElementById('backSoalPrompt').addEventListener('click', function () {
    document.getElementById('soalPasteStep').style.display  = 'none';
    document.getElementById('soalPromptStep').style.display = 'block';
});

// ── JSON Cleaning Engine ────────────────────────────────────────────────────

/**
 * Deteksi apakah setelah koma (posisi `pos`) ada elemen JSON berikutnya.
 * Dipakai untuk membedakan koma dalam teks nilai vs koma pemisah JSON.
 */
function isJsonContinuation(s, pos) {
    while (pos < s.length && ' \t\n\r'.includes(s[pos])) pos++;
    if (pos >= s.length) return true;
    const ch = s[pos];
    if ('{}[]'.includes(ch))           return true;
    if ('0123456789tfn-'.includes(ch)) return true;
    if (ch === '"') {
        // Cek pola "key": (property JSON)
        let k = pos + 1;
        while (k < s.length && s[k] !== '"' && s[k] !== '\n') k++;
        if (k < s.length && s[k] === '"') {
            let m = k + 1;
            while (m < s.length && (s[m] === ' ' || s[m] === '\t')) m++;
            if (m < s.length && s[m] === ':') return true;
        }
        return false;
    }
    return false;
}

/**
 * Bersihkan output AI: escape karakter tidak valid di dalam nilai string JSON.
 * Kasus: newline literal, kutip raw di tengah nilai, BOM/ZWSP.
 * Smart quotes (" ") TIDAK diganti — mereka valid di dalam string JSON.
 */
function cleanJsonString(str) {
    const PH = '\x01\x02\x03'; // placeholder untuk \" yang sudah sah
    let s = str.replace(/\\"/g, PH);
    let result = '', inString = false;

    for (let i = 0; i < s.length; i++) {
        if (s[i] === PH[0] && s.substring(i, i + 3) === PH) {
            result += '\\"'; i += 2; continue;
        }
        const ch = s[i], code = ch.charCodeAt(0);

        if (ch === '"') {
            if (!inString) { inString = true; result += ch; continue; }

            // Cek apakah kutip ini adalah penutup yang sah
            let j = i + 1;
            while (j < s.length && ' \t\n\r'.includes(s[j])) j++;
            const nxt = j < s.length ? s[j] : '';

            let isClose = (nxt === '' || nxt === '}' || nxt === ']' || nxt === ':');
            if (!isClose && nxt === ',') isClose = isJsonContinuation(s, j + 1);

            if (isClose) { inString = false; result += ch; }
            else         { result += '\\"'; }
            continue;
        }

        if (inString) {
            if (ch === '\n') { result += '\\n';  continue; }
            if (ch === '\r') { result += '\\r';  continue; }
            if (ch === '\t') { result += '\\t';  continue; }
            if (code < 32 && code !== 0x01) {
                result += '\\u' + code.toString(16).padStart(4, '0'); continue;
            }
        }
        result += ch;
    }
    return result;
}

/**
 * Fallback agresif: flatten semua escape (\") dulu lalu rebuild dari nol.
 * Untuk kasus kutip asimetris: \"teks" (buka di-escape, tutup tidak).
 */
function deepCleanJson(str) {
    let s = str.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\\"/g, '"');
    let result = '', i = 0;

    while (i < s.length) {
        if (s[i] !== '"') { result += s[i++]; continue; }

        result += '"'; i++;
        let inner = '';

        while (i < s.length) {
            const c = s[i];
            if (c === '"') {
                let j = i + 1;
                while (j < s.length && ' \t\n\r'.includes(s[j])) j++;
                const nxt = j < s.length ? s[j] : '';
                let isClose = (nxt === '' || nxt === '}' || nxt === ']' || nxt === ':');
                if (!isClose && nxt === ',') isClose = isJsonContinuation(s, j + 1);
                if (isClose) break;
                inner += '\\"'; i++; continue;
            }
            const code = c.charCodeAt(0);
            if (c === '\n') { inner += '\\n'; i++; continue; }
            if (c === '\r') { inner += '\\r'; i++; continue; }
            if (c === '\t') { inner += '\\t'; i++; continue; }
            if (code < 32)  { inner += '\\u' + code.toString(16).padStart(4, '0'); i++; continue; }
            inner += c; i++;
        }
        result += inner + '"';
        if (i < s.length && s[i] === '"') i++;
    }
    return result;
}

/**
 * Pipeline lengkap: bersihkan output AI dan kembalikan array soal.
 * Melempar Error jika semua upaya gagal.
 */
function parseAiJson(raw) {
    // 1. Strip markdown wrapper (```json ... ```)
    let s = raw.replace(/```(?:json)?([\s\S]*?)```/g, '$1').trim();

    // 2. Ambil blok [ ... ] terluar — abaikan teks preamble AI
    const f = s.indexOf('['), l = s.lastIndexOf(']');
    if (f !== -1 && l > f) s = s.substring(f, l + 1);

    // 3. Hapus karakter tidak terlihat
    s = s.replace(/[\uFEFF\u200B\u200C\u200D\u00AD]/g, '');

    // 4. Hapus komentar JS-style
    s = s.replace(/\/\/[^\n\r"]*/g, '');
    s = s.replace(/\/\*[\s\S]*?\*\//g, '');

    // 5. Hapus trailing comma
    s = s.replace(/,(\s*[\}\]])/g, '$1');

    // 6. Coba: parse setelah cleanJsonString
    try {
        const arr = JSON.parse(cleanJsonString(s));
        if (Array.isArray(arr) && arr.length > 0) return arr;
    } catch (_) {}

    // 7. Coba: parse setelah deepCleanJson (flatten + rebuild)
    try {
        const arr = JSON.parse(deepCleanJson(s));
        if (Array.isArray(arr) && arr.length > 0) return arr;
    } catch (_) {}

    // 8. Coba: parse langsung tanpa cleaning (mungkin sudah valid)
    const arr = JSON.parse(s);
    if (!Array.isArray(arr)) throw new Error('Output bukan JSON array');
    return arr;
}

/**
 * Normalisasi tipe soal dari berbagai variasi string output AI.
 */
function normalizeSoalType(t) {
    t = (t || 'pg').toLowerCase().replace(/[\s\-]/g, '_').trim();
    if (['pg', 'pilihan_ganda', 'pilgan', 'multiple_choice'].includes(t))                return 'pg';
    if (['pg_kompleks', 'pgk', 'pilihan_ganda_kompleks', 'pg_complex'].includes(t))      return 'pg_kompleks';
    if (['benar_salah', 'bs', 'true_false', 'betul_salah', 'benar/salah'].includes(t))  return 'benar_salah';
    if (['esai', 'essay', 'uraian', 'short_answer'].includes(t))                         return 'esai';
    return 'pg';
}

/**
 * Validasi cepat: pastikan setiap soal punya field wajib.
 */
function validateSoalArr(arr) {
    const errs = [];
    arr.forEach((s, i) => {
        const no = i + 1;
        if (!s.type)                             errs.push(`Soal ${no}: tidak ada "type"`);
        if (!s.question)                         errs.push(`Soal ${no}: tidak ada "question"`);
        if (s.answer === undefined || s.answer === null || s.answer === '')
                                                 errs.push(`Soal ${no}: tidak ada "answer"`);
        const tipe = normalizeSoalType(s.type || '');
        if (tipe !== 'esai' && (!s.options || typeof s.options !== 'object'))
                                                 errs.push(`Soal ${no}: tidak ada "options"`);
    });
    return errs;
}

// ── Handler "Parse & Tampilkan Soal" ──────────────────────────────────────
document.getElementById('parseSoalManual').addEventListener('click', function () {
    const btn = this;
    const raw = document.getElementById('soalPasteArea').value.trim();
    if (!raw) { alert('Paste hasil AI terlebih dahulu.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

    // setTimeout(0): beri browser 1 frame untuk render spinner dulu
    setTimeout(function () {
        try {
            const rawArr = parseAiJson(raw);

            // Normalisasi tipe
            rawArr.forEach(s => { s.type = normalizeSoalType(s.type); });

            // Validasi field wajib — tampilkan konfirmasi jika ada masalah
            const errs = validateSoalArr(rawArr);
            if (errs.length > 0) {
                const sample = errs.slice(0, 5).join('\n');
                const more   = errs.length > 5 ? `\n... dan ${errs.length - 5} masalah lainnya` : '';
                if (!confirm(
                    `Ditemukan ${errs.length} masalah pada struktur soal:\n\n${sample}${more}\n\n` +
                    'Soal tetap ditampilkan dan bisa disimpan, tapi mungkin tidak masuk dengan benar ke bank soal.\n\n' +
                    'Lanjutkan?'
                )) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Parse & Tampilkan Soal';
                    return;
                }
            }

            // Berhasil
            generatedSoal = rawArr;
            renderHasil(rawArr);

            // Tutup modal
            getModalInstance().hide();

            // Scroll ke hasil
            setTimeout(() => {
                const hw = document.getElementById('hasil-wrapper');
                if (hw) hw.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 400);

        } catch (e) {
            // Bangun pesan error yang informatif
            let ctx = '';
            const pm = e.message.match(/position (\d+)/);
            if (pm) {
                const pos = parseInt(pm[1]);
                const snip = raw.substring(Math.max(0, pos - 25), Math.min(raw.length, pos + 25))
                                  .replace(/\n/g, 'LF').replace(/\r/g, 'CR').replace(/\t/g, 'TAB');
                ctx = `\n\nSekitar posisi error:\n"${snip}"`;
            }

            alert(
                '&#10060; Gagal memproses JSON dari AI.\n\n' +
                'Error: ' + e.message + ctx + '\n\n' +
                'Saran:\n' +
                '1. Copy SELURUH output dari AI (mulai [ hingga akhir ])\n' +
                '2. Minta AI: "Kirim ulang hanya JSON array murni, tanpa penjelasan"\n' +
                '3. Atau gunakan "Generate Soal (API)" jika API key tersedia'
            );
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Parse & Tampilkan Soal';
    }, 0);
});
</script>

<?= $this->endSection() ?>
