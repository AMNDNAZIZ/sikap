<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">✍️ Distribusi Program Semester</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/administrasi-guru/prota-prosem') ?>">Prota & Prosem</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= esc($subject['name']) ?> - Semester <?= $semester ?></li>
            </ol>
        </nav>
    </div>
    <a href="<?= base_url('admin/administrasi-guru/prota-prosem?class_id=' . $class_id . '&subject_id=' . $subject['id']) ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<div class="card shadow mb-4 border-left-primary">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
        <h6 class="m-0 font-weight-bold text-primary">MATRIKS DISTRIBUSI ALOKASI WAKTU (JP)</h6>
        <span class="badge bg-primary px-3 py-2">Semester <?= $semester == 1 ? 'Ganjil' : 'Genap' ?></span>
    </div>
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/prosem/save') ?>" method="post" id="prosemForm">
            <?= csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= esc($class_id) ?>">
            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" style="min-width: 1200px;">
                    <thead class="bg-primary text-white text-center">
                        <tr>
                            <th rowspan="2" class="align-middle" width="300">Lingkup Materi / Tujuan Pembelajaran</th>
                            <th rowspan="2" class="align-middle" width="60">Total JP</th>
                            <?php foreach ($months as $mCode => $mName): ?>
                                <th colspan="5"><?= $mName ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="align-middle bg-dark" width="60">Sisa</th>
                        </tr>
                        <tr class="bg-light text-dark small">
                            <?php foreach ($months as $mCode => $mName): ?>
                                <th width="30">1</th>
                                <th width="30">2</th>
                                <th width="30">3</th>
                                <th width="30">4</th>
                                <th width="30">5</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atp_list as $atp): ?>
                            <tr class="atp-row" data-total="<?= $atp['alokasi_waktu'] ?>">
                                <td>
                                    <div class="fw-bold text-primary"><?= esc($atp['lingkup_materi']) ?></div>
                                    <div class="small text-muted">
                                        <?php foreach ($atp['tps'] as $tp): ?>
                                            • <?= esc($tp['kode_tp']) ?> <?= esc($tp['deskripsi']) ?><br>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold bg-light"><?= $atp['alokasi_waktu'] ?></td>
                                
                                <?php foreach ($months as $mCode => $mName): ?>
                                    <?php for ($w = 1; $w <= 5; $w++): ?>
                                        <td class="p-0">
                                            <input type="number" 
                                                   name="dist[<?= $atp['id'] ?>][<?= $mCode ?>][<?= $w ?>]" 
                                                   class="form-control form-control-sm border-0 text-center jp-input" 
                                                   value="<?= $atp['distributions'][$mCode][$w] ?? '' ?>"
                                                   min="0" 
                                                   max="<?= $atp['alokasi_waktu'] ?>"
                                                   onclick="this.select()"
                                                   placeholder="0">
                                        </td>
                                    <?php endfor; ?>
                                <?php endforeach; ?>

                                <td class="text-center fw-bold bg-light remaining-jp">-</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center p-3 bg-light rounded border">
                <div class="small">
                    <span class="text-danger fw-bold">* Tips:</span> Masukkan angka JP pada kolom minggu yang direncanakan.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                        <i class="bi bi-save"></i> Simpan Distribusi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.jp-input {
    height: 38px;
    background: transparent;
}
.jp-input:focus {
    background: #eef2ff;
    box-shadow: none;
    font-weight: bold;
}
.jp-input::-webkit-inner-spin-button { display: none; }
.atp-row:hover { background-color: #f8f9fc; }
.remaining-jp.text-danger { color: #e74a3b !important; }
.remaining-jp.text-success { color: #1cc88a !important; }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    function calculateRemaining() {
        $('.atp-row').each(function() {
            const total = parseInt($(this).data('total')) || 0;
            let used = 0;
            
            $(this).find('.jp-input').each(function() {
                used += parseInt($(this).val()) || 0;
            });
            
            const remaining = total - used;
            const $rem = $(this).find('.remaining-jp');
            
            $rem.text(remaining);
            
            if (remaining === 0) {
                $rem.removeClass('text-danger').addClass('text-success');
            } else if (remaining < 0) {
                $rem.removeClass('text-success').addClass('text-danger');
            } else {
                $rem.removeClass('text-success text-danger');
            }
        });
    }

    $('.jp-input').on('input change', function() {
        calculateRemaining();
    });

    calculateRemaining();
});
</script>
<?= $this->endSection() ?>
