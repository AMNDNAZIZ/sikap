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

    <?php if (empty($available_tps ?? [])): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Belum ada Tujuan Pembelajaran (TP).</strong>
            Silakan susun ATP terlebih dahulu agar KKTP bisa otomatis ter-generate dari TP yang ada di ATP.
            <div class="mt-2">
                <a href="<?= base_url('admin/administrasi-guru/atp?class_id=' . $selected_class . '&subject_id=' . $selected_subject) ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-arrow-right me-1"></i>Buka ATP
                </a>
            </div>
        </div>
    <?php elseif (!empty($kktp_list)): ?>

        <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-info-circle me-1"></i>
            <strong>KKTP otomatis ter-generate dari TP yang ada di ATP.</strong>
            Anda bisa mengubah interval dan kriteria sesuai kebutuhan dengan klik tombol edit.
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Daftar KKTP (<?= count($kktp_list) ?> TP)</h5>
            <a href="<?= base_url("admin/administrasi-guru/kktp/print/{$selected_class}/{$selected_subject}") ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak PDF
            </a>
        </div>

        <!-- Tabel KKTP -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white shadow-sm">
                <thead class="table-primary">
                    <tr class="text-center">
                        <th width="40">No</th>
                        <th>Tujuan Pembelajaran</th>
                        <th width="140">Kriteria 1<br><small class="fw-normal text-danger">Belum Tercapai</small></th>
                        <th width="140">Kriteria 2<br><small class="fw-normal text-warning">Mulai Tercapai</small></th>
                        <th width="140">Kriteria 3<br><small class="fw-normal text-success">Tercapai</small></th>
                        <th width="140">Kriteria 4<br><small class="fw-normal text-primary">Melampaui</small></th>
                        <?php if (!$readonly): ?><th width="70">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kktp_list as $i => $k): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= esc($k['tujuan_pembelajaran']) ?></td>
                        <td class="small text-center">
                            <strong class="text-danger"><?= esc($k['kriteria_1_interval']) ?></strong><br>
                            <em><?= esc($k['kriteria_1_intervensi']) ?></em>
                        </td>
                        <td class="small text-center">
                            <strong class="text-warning"><?= esc($k['kriteria_2_interval']) ?></strong><br>
                            <em><?= esc($k['kriteria_2_intervensi']) ?></em>
                        </td>
                        <td class="small text-center">
                            <strong class="text-success"><?= esc($k['kriteria_3_interval']) ?></strong><br>
                            <em><?= esc($k['kriteria_3_intervensi']) ?></em>
                        </td>
                        <td class="small text-center">
                            <strong class="text-primary"><?= esc($k['kriteria_4_interval']) ?></strong><br>
                            <em><?= esc($k['kriteria_4_intervensi']) ?></em>
                        </td>
                        <?php if (!$readonly): ?>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning edit-kktp" data-kktp='<?= json_encode($k) ?>' title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
<?php endif; ?>

<!-- Modal Edit KKTP -->
<?php if (!($readonly ?? false)): ?>
<div class="modal fade" id="editKktpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/administrasi-guru/kktp/store') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="modal_kktp_id">
                <input type="hidden" name="class_id" value="<?= $selected_class ?? '' ?>">
                <input type="hidden" name="subject_id" value="<?= $selected_subject ?? '' ?>">
                <input type="hidden" name="tp_id" id="modal_tp_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Kriteria KKTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tujuan Pembelajaran</label>
                        <textarea name="tujuan_pembelajaran" id="modal_tp_text" class="form-control" rows="2" readonly></textarea>
                    </div>

                    <?php
                    $labels = [1 => 'Belum Tercapai', 2 => 'Mulai Tercapai', 3 => 'Tercapai', 4 => 'Melampaui'];
                    $colors = [1 => 'danger', 2 => 'warning', 3 => 'success', 4 => 'primary'];
                    foreach ([1,2,3,4] as $n):
                    ?>
                    <div class="card bg-light mb-2">
                        <div class="card-body py-2 px-3">
                            <div class="fw-bold small text-<?= $colors[$n] ?> mb-1">Kriteria <?= $n ?> — <?= $labels[$n] ?></div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small">Interval</label>
                                    <input type="text" name="kriteria_<?= $n ?>_interval" id="modal_k<?= $n ?>_interval" class="form-control form-control-sm">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Label</label>
                                    <input type="text" name="kriteria_<?= $n ?>_label" id="modal_k<?= $n ?>_label" class="form-control form-control-sm">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Intervensi</label>
                                    <input type="text" name="kriteria_<?= $n ?>_intervensi" id="modal_k<?= $n ?>_intervensi" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-kktp').forEach(btn => {
    btn.addEventListener('click', function() {
        const k = JSON.parse(this.dataset.kktp);
        document.getElementById('modal_kktp_id').value = k.id;
        document.getElementById('modal_tp_id').value = k.tp_id || '';
        document.getElementById('modal_tp_text').value = k.tujuan_pembelajaran;

        for (let i = 1; i <= 4; i++) {
            document.getElementById(`modal_k${i}_interval`).value = k[`kriteria_${i}_interval`];
            document.getElementById(`modal_k${i}_label`).value = k[`kriteria_${i}_label`];
            document.getElementById(`modal_k${i}_intervensi`).value = k[`kriteria_${i}_intervensi`];
        }

        new bootstrap.Modal(document.getElementById('editKktpModal')).show();
    });
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>
