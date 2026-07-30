<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">📋 Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filter -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/kktp') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold">Pilih Kelas:</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()" <?= $auto_class ? 'disabled' : '' ?>>
                    <?php if (!$auto_class): ?><option value="">-- Pilih Kelas --</option><?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($auto_class): ?><input type="hidden" name="class_id" value="<?= $selected_class ?>"><?php endif; ?>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Pilih Mata Pelajaran:</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($selected_class) ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_subject && $selected_class): ?>
<div class="row">
    <?php if (!$readonly): ?>
    <!-- Form Input -->
    <div class="col-md-5">
        <div class="card shadow mb-4 border-start border-4 border-primary">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary" id="form_title">Tambah KKTP</h6>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/administrasi-guru/kktp/store') ?>" method="post" id="kktpForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                    <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                    <input type="hidden" name="id" id="kktp_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tujuan Pembelajaran</label>
                        <?php if (!empty($available_tps)): ?>
                        <select id="tp_select" class="form-select mb-2" onchange="fillTpFromSelect()">
                            <option value="">-- Pilih dari TP yang ada --</option>
                            <?php foreach ($available_tps as $tp): ?>
                                <option value="<?= $tp['id'] ?>" data-desc="<?= esc($tp['deskripsi']) ?>">
                                    <?= esc($tp['kode_tp']) ?> - <?= esc(mb_substr($tp['deskripsi'], 0, 60)) ?>...
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        <input type="hidden" name="tp_id" id="tp_id">
                        <textarea name="tujuan_pembelajaran" id="tujuan_pembelajaran" class="form-control" rows="3" required placeholder="Deskripsikan tujuan pembelajaran..."></textarea>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3">Kriteria Ketercapaian</h6>

                    <?php
                    $defaults = [
                        1 => ['0-40%', 'Belum Tercapai', 'Remedial di seluruh bagian'],
                        2 => ['41-65%', 'Mulai Tercapai', 'Remedial di bagian yang diperlukan'],
                        3 => ['66-85%', 'Tercapai', 'Tidak perlu remedial'],
                        4 => ['86-100%', 'Melampaui', 'Diberikan pengayaan'],
                    ];
                    foreach ($defaults as $num => [$interval, $label, $intervensi]):
                    ?>
                    <div class="card bg-light mb-2">
                        <div class="card-body py-2 px-3">
                            <div class="fw-bold small text-muted mb-1">Kriteria <?= $num ?></div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="text" name="kriteria_<?= $num ?>_interval" class="form-control form-control-sm" value="<?= $interval ?>" placeholder="Interval">
                                </div>
                                <div class="col-4">
                                    <input type="text" name="kriteria_<?= $num ?>_label" class="form-control form-control-sm" value="<?= $label ?>" placeholder="Label">
                                </div>
                                <div class="col-4">
                                    <input type="text" name="kriteria_<?= $num ?>_intervensi" class="form-control form-control-sm" value="<?= $intervensi ?>" placeholder="Intervensi">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100">💾 Simpan KKTP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daftar KKTP -->
    <div class="<?= !$readonly ? 'col-md-7' : 'col-12' ?>">
        <?php if (!empty($kktp_list)): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Daftar KKTP</h5>
            <a href="<?= base_url("admin/administrasi-guru/kktp/print/{$selected_class}/{$selected_subject}") ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white shadow-sm">
                <thead class="table-primary">
                    <tr class="text-center">
                        <th width="40">No</th>
                        <th>Tujuan Pembelajaran</th>
                        <th>Kriteria 1</th>
                        <th>Kriteria 2</th>
                        <th>Kriteria 3</th>
                        <th>Kriteria 4</th>
                        <?php if (!$readonly): ?><th width="80">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kktp_list as $i => $k): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= esc($k['tujuan_pembelajaran']) ?></td>
                        <td class="small">
                            <strong class="text-danger"><?= esc($k['kriteria_1_interval']) ?></strong><br>
                            <?= esc($k['kriteria_1_label']) ?><br>
                            <em class="text-muted"><?= esc($k['kriteria_1_intervensi']) ?></em>
                        </td>
                        <td class="small">
                            <strong class="text-warning"><?= esc($k['kriteria_2_interval']) ?></strong><br>
                            <?= esc($k['kriteria_2_label']) ?><br>
                            <em class="text-muted"><?= esc($k['kriteria_2_intervensi']) ?></em>
                        </td>
                        <td class="small">
                            <strong class="text-success"><?= esc($k['kriteria_3_interval']) ?></strong><br>
                            <?= esc($k['kriteria_3_label']) ?><br>
                            <em class="text-muted"><?= esc($k['kriteria_3_intervensi']) ?></em>
                        </td>
                        <td class="small">
                            <strong class="text-primary"><?= esc($k['kriteria_4_interval']) ?></strong><br>
                            <?= esc($k['kriteria_4_label']) ?><br>
                            <em class="text-muted"><?= esc($k['kriteria_4_intervensi']) ?></em>
                        </td>
                        <?php if (!$readonly): ?>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning edit-kktp" data-kktp='<?= json_encode($k) ?>' title="Edit"><i class="bi bi-pencil"></i></button>
                            <a href="<?= base_url('admin/administrasi-guru/kktp/delete/' . $k['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus KKTP ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Belum ada KKTP untuk kombinasi kelas dan mapel ini. Silakan tambahkan melalui form di samping.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function fillTpFromSelect() {
    const sel = document.getElementById('tp_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        document.getElementById('tp_id').value = opt.value;
        document.getElementById('tujuan_pembelajaran').value = opt.dataset.desc || '';
    }
}

// Edit KKTP
document.querySelectorAll('.edit-kktp').forEach(btn => {
    btn.addEventListener('click', function() {
        const k = JSON.parse(this.dataset.kktp);
        document.getElementById('kktp_id').value = k.id;
        document.getElementById('tujuan_pembelajaran').value = k.tujuan_pembelajaran;
        document.getElementById('tp_id').value = k.tp_id || '';
        document.getElementById('form_title').textContent = 'Edit KKTP #' + k.id;

        // Fill kriteria
        for (let i = 1; i <= 4; i++) {
            document.querySelector(`[name="kriteria_${i}_interval"]`).value = k[`kriteria_${i}_interval`];
            document.querySelector(`[name="kriteria_${i}_label"]`).value = k[`kriteria_${i}_label`];
            document.querySelector(`[name="kriteria_${i}_intervensi"]`).value = k[`kriteria_${i}_intervensi`];
        }

        window.scrollTo({top: 0, behavior: 'smooth'});
    });
});
</script>

<?= $this->endSection() ?>
