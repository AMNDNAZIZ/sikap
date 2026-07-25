<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">🤖 Pembuatan Soal AI</h4>

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
                    <label class="form-label fw-bold">Tujuan Pembelajaran (TP) <small class="text-muted">— pilih yang relevan</small></label>
                    <div id="tp-list" class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                        <span class="text-muted small">Pilih materi atau semester terlebih dahulu</span>
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" id="btn-generate" class="btn btn-primary" disabled>
                        <i class="bi bi-stars me-1"></i> Generate Soal
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
        document.getElementById('btn-generate').disabled = true;
    }
});

// Sumatif: semester change → load semua TP dari semester itu
document.getElementById('semester_id')?.addEventListener('change', function() {
    const sem = this.value;
    const semAtps = allAtp.filter(a => a.semester == sem);
    const container = document.getElementById('tp-list');

    document.getElementById('info-semester').textContent = semAtps.length + ' materi pada semester ' + sem;

    if (semAtps.length === 0) {
        container.innerHTML = '<span class="text-muted small">Tidak ada ATP di semester ini</span>';
        document.getElementById('btn-generate').disabled = true;
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
    document.getElementById('btn-generate').disabled = false;
});

// Formatif: ATP change → load TP
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

    document.getElementById('btn-generate').disabled = !this.value;
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
    btn.innerHTML = '<i class="bi bi-stars me-1"></i> Generate Soal';
});

function renderHasil(soal) {
    const wrapper = document.getElementById('hasil-wrapper');
    const container = document.getElementById('hasil-soal');
    document.getElementById('hasil-count').textContent = soal.length;
    wrapper.classList.remove('d-none');

    const tipeLabel = { pg: 'PG', pg_kompleks: 'PGK', benar_salah: 'B/S', esai: 'Esai' };

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
                html += `<div class="${isCorrect ? 'text-success fw-bold' : ''}">${key}. ${val} ${isCorrect ? '✓' : ''}</div>`;
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
            alert('✅ ' + data.message);
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
</script>

<?= $this->endSection() ?>
