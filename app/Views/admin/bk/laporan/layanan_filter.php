<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="<?= base_url('admin/bk/laporan') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-journal-check text-info me-2"></i>Laporan Rekap Layanan BK</h4>
            <p class="text-muted small mb-0">Pilih filter untuk menampilkan data layanan sebelum mencetak PDF.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-2 text-info"></i>Filter Laporan Layanan</h6>
        </div>
        <div class="card-body p-4">

            <!-- ── Form Preview ── -->
            <form method="post" action="<?= base_url('admin/bk/laporan/layanan/preview') ?>" id="formPreview">
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
                        <label class="form-label fw-semibold small">Semester</label>
                        <select name="semester" class="form-select form-select-sm">
                            <option value="">— Semua Semester —</option>
                            <option value="1">Ganjil (Jul–Des)</option>
                            <option value="2">Genap (Jan–Jun)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Jenis Layanan</label>
                        <select name="service_type" class="form-select form-select-sm">
                            <option value="">— Semua Jenis —</option>
                            <?php foreach ($serviceTypes as $t): ?>
                                <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
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
                        <label class="form-label fw-semibold small">Guru BK / Konselor</label>
                        <select name="counselor_id" class="form-select form-select-sm">
                            <option value="">— Semua Konselor —</option>
                            <?php foreach ($counselors as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Status Layanan</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">— Semua Status —</option>
                            <?php foreach (['Rencana','Penjadwalan','Pelaksanaan','Evaluasi','Tindak Lanjut','Selesai','Batal'] as $s): ?>
                                <option value="<?= esc($s) ?>"><?= esc($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Bidang</label>
                        <select name="field" class="form-select form-select-sm">
                            <option value="">— Semua Bidang —</option>
                            <?php foreach (['Pribadi','Sosial','Belajar','Karir'] as $b): ?>
                                <option value="<?= esc($b) ?>"><?= esc($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Selesai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>

                </div><!-- /.row -->

                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="action" value="preview"
                            class="btn btn-outline-info rounded-pill">
                        <i class="bi bi-eye me-1"></i>Tampilkan Preview
                    </button>
                    <button type="submit" name="action" value="pdf" form="formPdf"
                            class="btn btn-info rounded-pill text-white">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Langsung Cetak PDF
                    </button>
                </div>
            </form>

            <!-- form pdf duplikasi filter (disubmit ke endpoint pdf) -->
            <form method="post" action="<?= base_url('admin/bk/laporan/layanan/pdf') ?>" id="formPdf">
                <?= csrf_field() ?>
                <div id="hiddenInputsPdf"></div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Validasi tanggal & duplikasi ke form PDF
    const main = document.getElementById('formPreview');
    const pdfForm = document.getElementById('formPdf');
    const hiddenDiv = document.getElementById('hiddenInputsPdf');

    main.addEventListener('submit', function (e) {
        const from = main.querySelector('[name=date_from]').value;
        const to   = main.querySelector('[name=date_to]').value;
        if (from && to && from > to) {
            e.preventDefault();
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
            return;
        }
    });

    // Isi hidden form PDF dengan nilai form filter
    document.querySelectorAll('[form=formPdf]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            hiddenDiv.innerHTML = '';
            const inputs = main.querySelectorAll('select,input');
            inputs.forEach(inp => {
                if (!inp.name) return;
                const h = document.createElement('input');
                h.type  = 'hidden';
                h.name  = inp.name;
                h.value = inp.value;
                hiddenDiv.appendChild(h);
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
