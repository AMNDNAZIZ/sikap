<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="<?= base_url('admin/bk/laporan') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-shield-exclamation text-danger me-2"></i>Laporan Penanganan Kasus BK</h4>
            <p class="text-muted small mb-0">Pilih filter untuk menampilkan rekapitulasi kasus sebelum mencetak PDF.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="alert alert-warning py-2 small mb-3 border-0 rounded-3">
        <i class="bi bi-lock-fill me-1"></i>
        <strong>Privasi:</strong> Laporan ini tidak menampilkan catatan konseling rahasia, isi sesi, atau informasi sensitif siswa. Hanya data rekapitulasi yang ditampilkan.
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-2 text-danger"></i>Filter Laporan Kasus</h6>
        </div>
        <div class="card-body p-4">

            <form method="post" action="<?= base_url('admin/bk/laporan/kasus/preview') ?>" id="formPreview">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select form-select-sm">
                            <option value="">— Semua Tahun —</option>
                            <?php foreach ($academicYears as $ay): ?>
                                <option value="<?= $ay['id'] ?>"
                                    <?= ($activeYear['id'] ?? '') == $ay['id'] ? 'selected' : '' ?>>
                                    <?= esc($ay['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Kategori Kasus</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">— Semua Kategori —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Bobot / Tingkat</label>
                        <select name="severity" class="form-select form-select-sm">
                            <option value="">— Semua Bobot —</option>
                            <?php foreach ($severities as $sv): ?>
                                <option value="<?= esc($sv) ?>"><?= esc($sv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Status Kasus</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">— Semua Status —</option>
                            <?php
                            $statusLabels = [
                                'DRAFT'=>'Draft','REPORTED'=>'Dilaporkan','VERIFIED'=>'Terverifikasi',
                                'IN_ASSESSMENT'=>'Asesmen','IN_PROGRESS'=>'Dalam Penanganan',
                                'MONITORING'=>'Monitoring','REFERRED'=>'Dirujuk',
                                'RESOLVED'=>'Selesai','CLOSED'=>'Ditutup',
                            ];
                            foreach ($caseStatuses as $st): ?>
                                <option value="<?= esc($st) ?>"><?= esc($statusLabels[$st] ?? $st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Kelas / Rombel</label>
                        <select name="class_id" class="form-select form-select-sm">
                            <option value="">— Semua Kelas —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Guru BK / Penanganan</label>
                        <select name="counselor_id" class="form-select form-select-sm">
                            <option value="">— Semua Konselor —</option>
                            <?php foreach ($counselors as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small d-block">&nbsp;</label>
                        <!-- spacer -->
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Kejadian Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Kejadian Selesai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>

                </div><!-- /.row -->

                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-danger rounded-pill">
                        <i class="bi bi-eye me-1"></i>Tampilkan Preview
                    </button>
                    <button type="submit" form="formPdf"
                            class="btn btn-danger rounded-pill">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Langsung Cetak PDF
                    </button>
                </div>
            </form>

            <form method="post" action="<?= base_url('admin/bk/laporan/kasus/pdf') ?>" id="formPdf">
                <?= csrf_field() ?>
                <div id="hiddenInputsPdf"></div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const main = document.getElementById('formPreview');
    const hiddenDiv = document.getElementById('hiddenInputsPdf');
    main.addEventListener('submit', function (e) {
        const from = main.querySelector('[name=date_from]').value;
        const to   = main.querySelector('[name=date_to]').value;
        if (from && to && from > to) {
            e.preventDefault();
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
        }
    });
    document.getElementById('formPdf').addEventListener('submit', function () {
        hiddenDiv.innerHTML = '';
        main.querySelectorAll('select,input[type!=hidden]').forEach(inp => {
            if (!inp.name) return;
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = inp.name; h.value = inp.value;
            hiddenDiv.appendChild(h);
        });
    });
});
</script>
<?= $this->endSection() ?>
