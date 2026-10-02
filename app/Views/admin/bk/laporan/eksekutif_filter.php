<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="<?= base_url('admin/bk/laporan') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Laporan Eksekutif BK – Kepala Sekolah</h4>
            <p class="text-muted small mb-0">Laporan ringkas pelaksanaan BK tanpa membuka catatan konseling rahasia.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="alert alert-primary border-0 py-2 small mb-3 rounded-3">
        <i class="bi bi-info-circle me-1"></i>
        Laporan eksekutif hanya menampilkan <strong>statistik ringkasan</strong>: total program, layanan, kasus, dan capaian.
        Tidak ada nama siswa, catatan sesi, atau informasi sensitif.
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-2 text-primary"></i>Filter Laporan Eksekutif</h6>
        </div>
        <div class="card-body p-4">

            <form method="post" action="<?= base_url('admin/bk/laporan/eksekutif/preview') ?>" id="formPreview">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih Tahun Ajaran —</option>
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
                            <option value="">— Seluruh Tahun —</option>
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Tanggal Selesai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>

                </div><!-- /.row -->

                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary rounded-pill">
                        <i class="bi bi-eye me-1"></i>Tampilkan Preview
                    </button>
                    <button type="submit" form="formPdf"
                            class="btn btn-primary rounded-pill">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak Laporan Kepsek
                    </button>
                </div>
            </form>

            <form method="post" action="<?= base_url('admin/bk/laporan/eksekutif/pdf') ?>" id="formPdf">
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
